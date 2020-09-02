const mason  = require('@joomlatools/mason-tools-v1');
const https  = require('https');
const http   = require('http');
const chalk  = require('chalk');

class masonCache
{
    options = {
        origin      : null, //required
        endpoint    : '/cache.json',
        concurrency : 10,
        force       : false,
        unauthorized: false,
        config      : 'mason.json',
    }

    constructor(options)
    {
        this.options = {...this.options, ...options }
        this.config  = this.getConfig()

        //Turn off certificate validation for TLS connections (only needed for self-signed certs)
        if(this.options.unauthorized) {
            process.env['NODE_TLS_REJECT_UNAUTHORIZED'] = 0
        }

        //Check if the endpoint has been defined
        if(!this.options.origin) {
            throw new Error(`Origin config option is required`)
        }
    }

    async revalidate(url, collections)
    {
        //Cache origin url
        const origin = new URL(this.options.origin)
        const time = Date.now()

        // Sanitize the url
        if (url && url.indexOf('/') == 0) {
            url = origin.origin + url
        }

        if (url && !/^https?:\/\//i.test(url)) {
            url = origin.protocol + '//' + url
        }

        //Cache endpoint url
        const cache = new URL(this.options.endpoint, this.options.origin)

        //Filter:id
        if (url)
        {
            let page = new URL(url)

            if(url.substr(-1) == '/' || page.pathname.length > 1) {
                cache.searchParams.set('id', page.pathname + page.search)
            }
        }

        //Filter:valid
        if (!this.options.force) {
            cache.searchParams.set('filter[valid]', 'neq:true')
        }

        //Filter:collection
        if (collections)
        {
            if(collections instanceof Array) {
                collections = collections.join(',')
            }

            cache.searchParams.set('filter[collections]', 'in:'+collections)
        }

        this.getRequest(cache).then((response) =>
        {
            var data = response.json()

            //Check data
            if (data['data']) {
                data = data['data']
            } else {
                throw new Error('Response Not Valid')
            }

            //Check status code
            if (response.statusCode !== 200) {
                throw new Error(`Request Failed Status Code: ${response.statusCode}`)
            }

            //Get request time
            mason.log.debug('\n',`${chalk.green('✔')} Cache fetched from: ${decodeURIComponent(cache.toString())} - ${response.time()} s` )

            //Calculate the total elapsed time
            let totalTime = () =>
            {
                let date = new Date(Date.now() - time)
                let min = date.getUTCMinutes()
                let sec = date.getUTCSeconds()
                let ms  = ((date.getUTCMilliseconds() / 1000).toFixed(2)) * 1000;

                return min + ":" + sec  + '.' +  ms
            }

            if (data.length != undefined)
            {
                if (data.length > 0)
                {
                    mason.log.debug(` ${chalk.green('✔')} Revalidation started  total: ${data.length}, concurrency: ${this.options.concurrency}…`)

                    // Async function to revalidate the cache with a concurrency limit
                    this.revalidateList(data).finally(() => {
                        mason.log.debug('\n',chalk.bold(`Revalidation completed in ${totalTime()} min`))
                    })
                }
                else mason.log.debug('\n', chalk.bold(`Cache is valid`))

            }
            else
            {
                mason.log.debug(` ${chalk.green('✔')} Revalidation started, total: 1`)

                this.revalidateUrl(data['attributes']).finally(() => {
                    mason.log.debug('\n',chalk.bold(`Revalidation completed in ${totalTime()} min`))
                })
            }

        }).catch((error) => {
            mason.log.error(error.message)
        })
    }

    async revalidateList(list)
    {
        for (let i = 0; i < list.length; i += this.options.concurrency)
        {
            const requests = list.slice(i, i + this.options.concurrency).map((item) => {
                return this.revalidateUrl(item['attributes'])
            })

            await Promise.allSettled(requests)
        }
    }

    async revalidateUrl(item)
    {
        let url     = item['url']
        let options =
            {
                headers: {
                    'Cache-Control': 'max-age=0, must-revalidate',
                    'Accept': 'text/html',
                }
            }

        let request = this.getRequest(url, options).then((response) =>
        {
            let time = response.time();

            if(response.headers.etag == item['hash']) {
                mason.log.debug(chalk.gray('  » Not Modified: ' + url + ' - ' + time + ' s'))
            } else {
                mason.log.debug('  » Regenerated: ' + url + ' - ' + time + ' s')
            }

        }).catch((error)  => {
            mason.log.error('Failed: ' + url + ' with error: ' + error.message)
        })

        return request
    }

    async getRequest(url, options)
    {
        options = options || {}

        let promise = new Promise((resolve, reject) =>
        {
            let callback = (response) =>
            {
                let data = ''

                response.on('data', (chunk) => {
                    data += chunk
                })

                response.on('end', () =>
                {
                    response.data = data
                    resolve (response)
                })

                response.on('error', (error) => {
                    reject(error)
                })

                response.json = () => {
                    return JSON.parse(data)
                }

                response.time = (metric = 'tot') =>
                {
                    let time   = null
                    let timing = response.timing();

                    if(timing[metric])
                    {
                        metric = timing[metric];
                        time   = (metric.dur / 1000).toFixed(2);
                    }

                    return time;
                }

                response.timing = () =>
                {
                    var timing = {};

                    if(response.headers['server-timing'])
                    {
                        let timings = response.headers['server-timing'].split(',')

                        timings.forEach((metric) =>
                        {
                            metric = metric.split(';');

                            var values = {};
                            var name   = '';

                            metric.forEach((part) =>
                            {
                                if(part.includes('='))
                                {
                                    var [key, value]  = part.split('=');
                                    values[key] = value.replace(/"/g,'')
                                }
                                else name = part;
                            })

                            if(name) {
                                timing[name] = values;
                            }
                        })
                    }

                    return timing;
                }
            }

            //Create the request based on the protocol
            let protocol = new URL(url).protocol.replace(':', '');

            if(protocol == 'https') {
                var request = https.get(url, options, callback);
            } else {
                var request = http.get(url, options, callback);
            }

            request.on('error', (error) => {
                reject(error)
            })

            request.end()
        })

        return promise
    }

    async getConfig()
    {
        var config

        if(config == null)
        {
            const fs   = require('fs')
            const path = `${process.cwd()}/${this.options.config}`

            if (fs.existsSync(path)) {
                config = require(path)
            } else {
                config = {}
            }
        }

        return config
    }
}

module.exports = {
    version: 1.0,
    masonCache
}