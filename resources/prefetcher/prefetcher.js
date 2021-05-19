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
class Prefetcher {
  constructor(options) {

    this.version = '1.1.2';

    //Cache of URLs we've prefetched
    this.cache = new Set(Array.from(this.getSessionStorage().load("cache")));

    //Timers
    this._hoverTimer;
    this._hoverTimestamp;

    //Load the runtime configuration
    this.config = this.getSessionStorage().load("config");

    //Initialize the prefetcher
    var defaults = {
      onload: false,
      onhover: true,
      onclick: false,
      limit: 1 / 0,
      throttle: 1 / 0,
      delay: 100,
      timeout: 1000,
      savedata: true,
      selector: "a",
      ignored: [(uri) => uri.includes("#")],
      origins: [location.hostname],
      debug: false,
    };

    this.initalize({ ...defaults, ...options, ...this.config });

    //Attach the prefetcher to the window
    window.Prefetcher = this;

    //Store the config in the sessionStorage on unload
    window.addEventListener("beforeunload", (event) => {
      if (this.config) {
        this.getSessionStorage().store("config", this.config);
      }

      if (this.cache) {
        this.getSessionStorage().store("cache", Array.from(this.cache));
      }
    });
  }

  /**
   * Initialze the object
   *
   * @param {Array} options - The options
   * @return {Object} a Promise
   */
  initalize(options) {
    if (this.dispatchEvent("initialize", options, true)) {
      this.options = options;

      this.log("Initialize completed");
      this.debug("Options", this.options);

      if (this.options.onload && !navigator.webdriver) {
        this.onLoad();
      }

      if (this.options.onhover && !navigator.webdriver) {
        this.onHover();
      }

      if (this.options.onclick) {
        this.onClick();
      }
    } else {
      this.log("Initialize prevented");
      this.debug("Options", this.options);
    }
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
  prerender(url, context) {
    var promise;
    var url = new URL(url, location.href).toString();

    if (!this.cache.has(url)) {
      if (
        this.dispatchEvent("prerender", { url: url, context: context }, true)
      ) {
        promise = this.canPrerender()
          ? this.prerenderViaDOM(url)
          : fetch(url, { credentials: "include" });
        promise.then((result) => this.cache.add(url));
        promise.then((result) =>
          this.log("Prerendered on " + context + ":", url)
        );
        promise.catch((err) =>
          this.dispatchEvent("error", { error: err, context: context })
        );
      } else this.debug("Prerender prevented on " + context + ":", url);
    } else this.debug("Prerender cancelled on " + context + ":", url);

    return Promise.all([promise]);
  }

  /**
   * Prefetch a given URL
   *
   * Try to prefetch the url low priority using `<link rel=prefetch> and fallback to async XMLHttpRequest
   *
   * @param {String} url - the URL to fetch
   * @param {String} context - the prefetch context
   * @return {Object} a Promise
   */
  prefetch(url, context) {
    var promise;
    var url = new URL(url, location.href).toString();

    if (!this.cache.has(url)) {
      if (
        this.dispatchEvent("prefetch", { url: url, context: context }, true)
      ) {
        promise = this.canPrefetch()
          ? this.prefetchViaDOM(url)
          : this.prefetchViaXHR(url);
        promise.then((result) => this.cache.add(url));
        promise.then((result) =>
          this.log("Prefetched on " + context + ":", url)
        );
        promise.catch((err) =>
          this.dispatchEvent("error", { error: err, context: context })
        );
      } else this.debug("Prefetch prevented on " + context + ":", url);
    } else this.debug("Prefetch cancelled on " + context + ":", url);

    return Promise.all([promise]);
  }

  /**
   * Prerender of (if not supported prefetch) a URL on click
   */
  onClick() {
    document.addEventListener(
      "mousedown",
      function (event) {
        if (this._hoverTimestamp > performance.now() - 500) {
          return;
        }

        var element = event.target;

        if (this.isPrefetchable(element)) {
          this.prerender(element.href, "click");
        }
      }.bind(this),
      { capture: true, passive: true }
    );
  }

  /**
   * Prerender or (if not supported prefetch) a URL on hover or touch.
   */
  onHover() {
    document.addEventListener(
      "touchstart",
      function (event) {
        var element = event.target;

        if (this.isPrefetchable(element)) {
          this._hoverTimestamp = performance.now();
          this.prerender(element.href, "touch");
        }
      }.bind(this),
      { capture: true, passive: true }
    );

    document.addEventListener(
      "mouseover",
      function (event) {
        if (this._hoverTimestamp > performance.now() - 500) {
          return;
        }

        var element = event.target;

        if (this.isPrefetchable(element)) {
          this._hoverTimer = setTimeout(() => {
            this.prerender(element.href, "hover");
            this._hoverTimer = false;
          }, this.options.delay);

          element.addEventListener(
            "mouseout",
            function (event) {
              if (this._hoverTimer) {
                clearTimeout(this._hoverTimer);
                this._hoverTimer = undefined;
              }
            }.bind(this),
            { capture: true, passive: true }
          );
        }
      }.bind(this),
      { capture: true, passive: true }
    );
  }

  /**
   * Prefetch an array of URLs if the user's effective connection type and data-saver preferences suggests
   * it would be useful. By default, looks at in-viewport links for `document`. Can also work off one or
   * more supplied DOM elements. Prefetching is automatically disabled for reload and history traversals.
   */
  onLoad() {
    // Compatibility check
    if (!window.IntersectionObserver) {
      return;
    }

    // Don't prefetch if using 2G or if Save-Data is enabled.
    const conn = navigator.connection;

    if (conn && this.options.savedata) {
      if (conn.saveData) {
        this.log("Load disabled: Save-Data is enabled");
        return;
      }

      if (conn.effectiveType.includes("2g")) {
        this.log("Load disabled: network conditions are poor");
        return;
      }
    }

    // Don't prefetch when reloading or history traversal
    const performance = window.performance.navigation;

    if (performance) {
      if (performance.type == PerformanceNavigation.TYPE_RELOAD) {
        this.log("Load disabled: browser reloading");
        this.log("Cache cleared on reload");
        this.cache.clear();
        return;
      }

      if (performance.type == PerformanceNavigation.TYPE_BACK_FORWARD) {
        this.log("Load disabled: history traversal");
        return;
      }

      this.log("Load enabled: user navigation");
    }

    const [enqueue, dequeue] = this.getThrottledQueue(this.options.concurrency);

    var observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          observer.unobserve((entry = entry.target));

          // Do not prefetch if will match/exceed limit
          if (this.cache.size < this.options.limit) {
            enqueue(() => {
              this.prefetch(entry.href, "load")
                .then(dequeue())
                .catch((err) => {
                  dequeue();
                });
            });
          }
        }
      });
    });

    window.requestIdleCallback(
      () => {
        document.querySelectorAll(this.options.selector).forEach((element) => {
          //Only select anchor elements
          if (!(element instanceof HTMLAnchorElement)) {
            element.querySelectorAll("a").forEach((element) => {
              if (this.isPrefetchable(element)) {
                observer.observe(element);
              }
            });
          } else {
            if (this.isPrefetchable(element)) {
              observer.observe(element);
            }
          }
        });
      },
      { timeout: this.options.timeout }
    );

    return function () {
      // Disconnect observer
      observer.disconnect();
    };
  }

  /**
   * Renders a given URL using `<link rel=prerender>`
   *
   * @param {string} url - the URL to fetch
   * @return {Object} a Promise
   */
  prerenderViaDOM(url) {
    return new Promise((resolve, reject, link) => {
      link = document.createElement("link");
      link.rel = "prerender";
      link.href = url;
      link.as = "document";

      link.onload = resolve();
      link.onerror = reject();

      document.head.appendChild(link);
    });
  }

  /**
   * Fetches a given URL using `<link rel=prefetch>`
   *
   * @param {string} url - the URL to fetch
   * @return {Object} a Promise
   */
  prefetchViaDOM(url) {
    return new Promise((resolve, reject, link) => {
      link = document.createElement("link");
      link.rel = "prefetch";
      link.href = url;
      link.as = "document";

      link.onload = resolve();
      link.onerror = reject();

      document.head.appendChild(link);
    });
  }

  /**
   * Fetches a given URL using an async XMLHttpRequest
   *
   * @param {string} url - the URL to fetch
   * @return {Object} a Promise
   */
  prefetchViaXHR(url) {
    return new Promise((resolve, reject, req) => {
      req = new XMLHttpRequest();
      req.open("GET", url, (req.withCredentials = true), true);
      req.onload = () => {
        req.status === 200 ? resolve() : reject();
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
  isPrefetchable(element) {
    //Is not link
    if (!element || !element.href) {
      return false;
    }

    //Is a download link
    if (element.download) {
      return false;
    }

    //Is same page
    if (element.href.split("#")[0] === location.href.split("#")[0]) {
      return false;
    }

    //Is origin allowed ( a`[]` or `true` means everything is allowed)
    if (
      this.options.origins.length &&
      !this.options.origins.includes(
        new URL(element.href, location.href).hostname
      )
    ) {
      return false;
    }

    //Is not http
    if (!["http:", "https:"].includes(element.protocol)) {
      return false;
    }

    //Is ignored
    if (this.isIgnored(element, this.options.ignored)) {
      return false;
    }

    //Is offline
    if (!window.navigator.onLine) {
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
  isIgnored(element, filter) {
    return Array.isArray(filter)
      ? filter.some((x) => this.isIgnored(element, x))
      : (filter.test || filter).call(filter, element.href, element);
  }

  /**
   * Checks if prefetch is supported
   *
   * @return {Boolean} whether the feature is supported
   */
  canPrefetch() {
    var link = document.createElement("link");
    return (
      link.relList && link.relList.supports && link.relList.supports("prefetch")
    );
  }

  /**
   * Checks if prerender is supported
   *
   * @return {Boolean} whether the feature is supported
   */
  canPrerender() {
    //Prerender no longer seems to work in Chrome
    //var link = document.createElement('link');
    //return link.relList && link.relList.supports && link.relList.supports('prerender');

    return false;
  }

  /**
   * Dispatch a custom event
   *
   * @param  {String}  name        The name of the event
   * @param  {Array}   attributes  The event attributes
   * @param  {Boolean} cancelable  The event is cancellable. Default false
   * @return {Boolean} whether the feature is supported
   */
  dispatchEvent(name, attributes, cancelable = false) {
    var event = new CustomEvent("prefetcher:" + name, {
      cancelable: cancelable,
      detail: attributes,
    });

    return window.dispatchEvent(event);
  }

  /**
   * Utility to log to console
   */
  log() {
    var args = Array.from(arguments);
    args.unshift(
      "%c[" + this.constructor.name.toLowerCase() + "]",
      "color: gray"
    );

    if (this.options.debug && console) {
      if (typeof console.log === "function") {
        console.log.apply(console, args);
      } else {
        console.log(args);
      }
    }
  }

  /**
   * Utility to log to console
   */
  debug() {
    var args = Array.from(arguments);
    args.unshift(
      "%c[" + this.constructor.name.toLowerCase() + "]",
      "color: gray"
    );

    if (this.options.debug && console) {
      if (typeof console.debug === "function") {
        console.debug.apply(console, args);
      } else {
        console.debug(args);
      }
    }
  }

  /**
   * Simple wrapper for sessionStorage
   *
   * @returns {Array} Returns an array of named methods
   */
  getSessionStorage() {
    const namespace = this.constructor.name;

    function load(key) {
      var item = {};
      if (sessionStorage.getItem(namespace + ":" + key)) {
        try {
          item = JSON.parse(sessionStorage.getItem(namespace + ":" + key));
        } catch (e) {
          item = {};
        }
      }

      return item;
    }

    function store(key, value) {
      sessionStorage.setItem(namespace + ":" + key, JSON.stringify(value));
    }

    function remove(key) {
      sessionStorage.removeItem(key);
    }

    return { load: load, store: store, remove: remove };
  }

  /**
   * Throttled queue to regulate the execution rate of your functions
   *
   * @param {Number} limit The throttle's concurrency limit. By default, runs your functions one at a time.
   * @returns {Array} Returns a tuple of [enqueue, dequeue] actions.
   */
  getThrottledQueue(limit) {
    limit = limit || 1;
    var queue = [],
      size = 0;

    function enqueue(fn) {
      queue.push(fn) > 1 || run(); // initializes if 1st
    }

    function dequeue() {
      size--;
      run();
    }

    function run() {
      if (size < limit && queue.length > 0) {
        queue.shift()();
        size++;
      }
    }

    return [enqueue, dequeue];
  }
}

// Polyfill for requestIdleCallback
window.requestIdleCallback =
  window.requestIdleCallback ||
  function (cb) {
    const start = Math.floor(Date.now() / 1000);
    return setTimeout(function () {
      cb({
        didTimeout: false,
        timeRemaining: function () {
          return Math.max(0, 50 - (Math.floor(Date.now() / 1000) - start));
        },
      });
    }, 1);
  };
