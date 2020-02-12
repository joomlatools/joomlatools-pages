/**
 * Joomlatools Pages
 *
 * @copyright   Copyright (C) 2018 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link        https://github.com/joomlatools/joomlatools-pages for the canonical source repository
 */

/**
 * Prefetcher is a link prefetcher and runtime caching solution.
 *
 * It offers two key features:
 *
 * - Interaction-based link prefetching on hover
 * - Customizable and throttled prefetching in-viewport links during idle time
 */
class Prefetcher
{
    constructor(options)
    {
        //Cache of URLs we've prefetched
        this.cache = new Set();

        //Timers
        this.hoverTimer;
        this.hoverTimestamp;

        //Options
        options =  options || {};
        options.onload  = options.onload || false;
        options.onhover = options.onhover || true;
        options.onclick = options.onclick || false;

        options.limit    = options.limit|| 1/0;
        options.throttle = options.throttle || 1/0;
        options.delay    = options.delay || 100;
        options.timeout  = options.timeout|| 1000;
        options.savedata = options.savedata|| true;
        options.elements = options.elements || 'a';
        options.origins  = options.origins || [location.hostname];
        options.ignored  = options.ignored || [uri => uri.includes('#')]
        options.debug    = options.debug || false;

        this.options = options;
        this.log("Prefetcher Options", this.options);

        if(this.options.onload) {
            document.addEventListener("DOMContentLoaded", () => this.onLoad());
        }

        if(this.options.onhover) {
            this.onHover();
        }

        if(this.options.onclick) {
            this.onClick();
        }
    }

    /**
     * Prerender of (if not supported prefetch) a URL on click
     */
    onClick()
    {
        document.addEventListener('mousedown', (function (event)
        {
            if(this.hoverTimestamp > (performance.now() - 500)) {
                return;
            }

            var element = event.target;

            if (this.isPrefetchable(element)) {
                this.prerender(element.href, 'click');
            }

        }).bind(this), {capture: true, passive: true});
    }

    /**
     * Prerender or (if not supported prefetch) a URL on hover or touch.
     */
    onHover()
    {
        document.addEventListener('touchstart', (function (event)
        {
            var element = event.target;

            if (this.isPrefetchable(element))
            {
                this.hoverTimestamp = performance.now();
                this.prerender(element.href, 'touch');
            }

        }).bind(this), {capture: true, passive: true});

        document.addEventListener('mouseover', (function (event)
        {
            if(this.hoverTimestamp > (performance.now() - 500)) {
                return;
            }

            var element = event.target;

            if (this.isPrefetchable(element))
            {
                this.hoverTimer = setTimeout(() =>
                {
                    this.prerender(element.href, 'hover');
                    this.hoverTimer = false

                }, this.options.delay)

                element.addEventListener('mouseout', (function(event)
                {
                    if (this.hoverTimer)
                    {
                        clearTimeout(this.hoverTimer)
                        this.hoverTimer = undefined
                    }
                }).bind(this), {capture: true, passive: true});
            }

        }).bind(this), {capture: true, passive: true});
    }

    /**
     * Prefetch an array of URLs if the user's effective connection type and data-saver preferences suggests
     * it would be useful. By default, looks at in-viewport links for `document`. Can also work off one or
     * more supplied DOM elements.
     */
    onLoad()
    {
        // Compatibility check
        if (!window.IntersectionObserver) {
            return;
        }

        // Don't prefetch if using 2G or if Save-Data is enabled.
        const conn = navigator.connection;

        if (conn && this.options.savedata)
        {
            if ( conn.effectiveType.includes('2g') ||  conn.saveData) {
                return;
            }
        }

        const[toAdd, isDone] = this.throttle(this.options.throttle);

        var observer = new IntersectionObserver(entries =>
        {
            entries.forEach(entry =>
            {
                if (entry.isIntersecting)
                {
                    observer.unobserve(entry = entry.target);

                    // Do not prefetch if will match/exceed limit
                    if (this.cache.size < this.options.limit)
                    {
                        toAdd(() =>  {
                            this.prefetch(entry.href, 'load').then(isDone).catch(err => { isDone(); });
                        });
                    }
                }
            });
        });

        window.requestIdleCallback(() =>
        {
            document.querySelectorAll(this.options.elements).forEach(element =>
            {
                //Only select anchor elements
                if(!(element instanceof HTMLAnchorElement))
                {
                    element.querySelectorAll('a').forEach(element =>
                    {
                        if (this.isPrefetchable(element)) {
                            observer.observe(element)
                        }
                    });
                }
                else
                {
                    if(this.isPrefetchable(element)) {
                        observer.observe(element)
                    };
                }
            });
        }, {timeout: this.options.timeout});

        return function ()
        {
            // Disconnect observer
            observer.disconnect();
        };
    }

    /**
     * Prerender a given URL
     *
     * Try to prerender the url high priority using `<link rel=prefetch> and fallback to high priority fetch
     *
     * @param {String} url - the URL to fetch
     * @param {String} context - the prefetch context
     * @return {Object} a Promise
     */
    prerender(url, context)
    {
        var promise;
        var url  = new URL(url, location.href).toString();

        if(!this.cache.has(url))
        {
            this.cache.add(url);

            promise = this.isSupported('prerender') ? this.prerenderViaDOM(url) : fetch(url, {credentials: 'include'})
            promise.then(result =>  this.log('Prerendering on ' + context + ':', url));
        }

        return Promise.all([promise]);
    }

    /**
     * Renders a given URL using `<link rel=prerender>`
     *
     * @param {string} url - the URL to fetch
     * @return {Object} a Promise
     */
    prerenderViaDOM(url)
    {
        return new Promise((resolve, reject, link) =>
        {
            link = document.createElement('link');
            link.rel = 'prerender';
            link.href = url;
            link.as = 'document';

            link.onload = resolve();
            link.onerror = reject();

            document.head.appendChild(link);
        });
    };

    /**
     * Prefetch a given URL
     *
     * Try to prefetch the url low priority using `<link rel=prefetch> and fallback to async XMLHttpRequest
     *
     * @param {String} url - the URL to fetch
     * @param {String} context - the prefetch context
     * @return {Object} a Promise
     */
    prefetch(url, context)
    {
        var promise;
        var url  = new URL(url, location.href).toString();

        if(!this.cache.has(url))
        {
            this.cache.add(url);

            promise = this.isSupported('prefetch') ? this.prefetchViaDOM(url) : this.prefetchViaXHR(url);
            promise.then(result =>  this.log('Prefetched on ' + context + ':', url));
        }

        return Promise.all([promise]);
    }

    /**
     * Fetches a given URL using `<link rel=prefetch>`
     *
     * @param {string} url - the URL to fetch
     * @return {Object} a Promise
     */
    prefetchViaDOM(url)
    {
        return new Promise((resolve, reject, link) =>
        {
            link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = url;
            link.as = 'document';

            link.onload = resolve();
            link.onerror = reject();

            document.head.appendChild(link);
        });
    };

    /**
     * Fetches a given URL using an async XMLHttpRequest
     *
     * @param {string} url - the URL to fetch
     * @return {Object} a Promise
     */
    prefetchViaXHR(url)
    {
        return new Promise((resolve, reject, req) =>
        {
            req = new XMLHttpRequest();
            req.open('GET', url, req.withCredentials=true, true);
            req.onload = () => {
                (req.status === 200) ? resolve() : reject();
            };

            req.send();
        });
    }

    /**
     * Checks if a link element can be prefetched
     *
     * @param {element} element - the link Element
     * @return {boolean}
     */
    isPrefetchable(element)
    {
        //Is not link
        if (!element || !element.href) {
            return false;
        }

        //Is a download link
        if(element.download) {
            return false;
        }

        //Is origin allowed ( a`[]` or `true` means everything is allowed)
        if (this.options.origins.length && !this.options.origins.includes((new URL(element.href, location.href)).hostname)) {
            return false;
        }

        //Is not http
        if (!['http:', 'https:'].includes(element.protocol)) {
            return false;
        }


        //Is ignored
        if (this.isIgnored(element, this.options.ignored)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if link should be prefetched.
     *
     * A filter can be a RegExp, Function, or Array of both.
     *   - Function receives `element.href, node` arguments
     *   - RegExp receives `element.href` only (the full URL)
     *
     * @param  {Element}  element    the link Element
     * @param  {Mixed}    filter  The custom filter(s)
     * @return {Boolean}  If true, then it should be ignored
     */
    isIgnored(element, filter)
    {
        return Array.isArray(filter)
            ? filter.some(x => this.isIgnored(element, x))
            : (filter.test || filter).call(filter, element.href, element);
    }

    /**
     * Checks if a resource hint is supported
     *
     * @return {Boolean} whether the feature is supported
     */
    isSupported(hint)
    {
        var link = document.createElement('link');
        return link.relList && link.relList.supports && link.relList.supports(hint);
    }

    /**
     * Utitly to log to console
     */
    log()
    {
        if (this.options.debug && console)
        {
            if (typeof console.log === "function") {
                console.log.apply(console, arguments);
            } else if (console.log) {
                console.log(arguments);
            }
        }
    }

    /**
     * Utility to regulate the execution rate of your functions
     *
     * @param {Number} limit The throttle's concurrency limit. By default, runs your functions one at a time.
     * @returns {Array} Returns a tuple of [toAdd, isDone] actions.
     */
    throttle(limit)
    {
        limit = limit || 1;
        var queue = [], wip = 0;

        function toAdd(fn) {
            queue.push(fn) > 1 || run(); // initializes if 1st
        }

        function isDone()
        {
            wip--; // make room for next
            run();
        }

        function run()
        {
            if (wip < limit && queue.length > 0)
            {
                queue.shift()();
                wip++; // is now WIP
            }
        }

        return [toAdd, isDone];
    }
}

// Polyfill for requestIdleCallback
window.requestIdleCallback = window.requestIdleCallback || function (cb)
{
    var start = Date.now();
    return setTimeout(function ()
    {
        cb({
            didTimeout: false,
            timeRemaining: function () {
                return Math.max(0, 50 - (Date.now() - start));
            }
        });
    }, 1);
}