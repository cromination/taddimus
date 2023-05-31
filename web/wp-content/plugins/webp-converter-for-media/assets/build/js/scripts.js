/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/axios/index.js":
/*!*************************************!*\
  !*** ./node_modules/axios/index.js ***!
  \*************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

module.exports = __webpack_require__(/*! ./lib/axios */ "./node_modules/axios/lib/axios.js");

/***/ }),

/***/ "./node_modules/axios/lib/adapters/xhr.js":
/*!************************************************!*\
  !*** ./node_modules/axios/lib/adapters/xhr.js ***!
  \************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var settle = __webpack_require__(/*! ./../core/settle */ "./node_modules/axios/lib/core/settle.js");
var cookies = __webpack_require__(/*! ./../helpers/cookies */ "./node_modules/axios/lib/helpers/cookies.js");
var buildURL = __webpack_require__(/*! ./../helpers/buildURL */ "./node_modules/axios/lib/helpers/buildURL.js");
var buildFullPath = __webpack_require__(/*! ../core/buildFullPath */ "./node_modules/axios/lib/core/buildFullPath.js");
var parseHeaders = __webpack_require__(/*! ./../helpers/parseHeaders */ "./node_modules/axios/lib/helpers/parseHeaders.js");
var isURLSameOrigin = __webpack_require__(/*! ./../helpers/isURLSameOrigin */ "./node_modules/axios/lib/helpers/isURLSameOrigin.js");
var createError = __webpack_require__(/*! ../core/createError */ "./node_modules/axios/lib/core/createError.js");

module.exports = function xhrAdapter(config) {
  return new Promise(function dispatchXhrRequest(resolve, reject) {
    var requestData = config.data;
    var requestHeaders = config.headers;
    var responseType = config.responseType;

    if (utils.isFormData(requestData)) {
      delete requestHeaders['Content-Type']; // Let the browser set it
    }

    var request = new XMLHttpRequest();

    // HTTP basic authentication
    if (config.auth) {
      var username = config.auth.username || '';
      var password = config.auth.password ? unescape(encodeURIComponent(config.auth.password)) : '';
      requestHeaders.Authorization = 'Basic ' + btoa(username + ':' + password);
    }

    var fullPath = buildFullPath(config.baseURL, config.url);
    request.open(config.method.toUpperCase(), buildURL(fullPath, config.params, config.paramsSerializer), true);

    // Set the request timeout in MS
    request.timeout = config.timeout;

    function onloadend() {
      if (!request) {
        return;
      }
      // Prepare the response
      var responseHeaders = 'getAllResponseHeaders' in request ? parseHeaders(request.getAllResponseHeaders()) : null;
      var responseData = !responseType || responseType === 'text' ||  responseType === 'json' ?
        request.responseText : request.response;
      var response = {
        data: responseData,
        status: request.status,
        statusText: request.statusText,
        headers: responseHeaders,
        config: config,
        request: request
      };

      settle(resolve, reject, response);

      // Clean up request
      request = null;
    }

    if ('onloadend' in request) {
      // Use onloadend if available
      request.onloadend = onloadend;
    } else {
      // Listen for ready state to emulate onloadend
      request.onreadystatechange = function handleLoad() {
        if (!request || request.readyState !== 4) {
          return;
        }

        // The request errored out and we didn't get a response, this will be
        // handled by onerror instead
        // With one exception: request that using file: protocol, most browsers
        // will return status as 0 even though it's a successful request
        if (request.status === 0 && !(request.responseURL && request.responseURL.indexOf('file:') === 0)) {
          return;
        }
        // readystate handler is calling before onerror or ontimeout handlers,
        // so we should call onloadend on the next 'tick'
        setTimeout(onloadend);
      };
    }

    // Handle browser request cancellation (as opposed to a manual cancellation)
    request.onabort = function handleAbort() {
      if (!request) {
        return;
      }

      reject(createError('Request aborted', config, 'ECONNABORTED', request));

      // Clean up request
      request = null;
    };

    // Handle low level network errors
    request.onerror = function handleError() {
      // Real errors are hidden from us by the browser
      // onerror should only fire if it's a network error
      reject(createError('Network Error', config, null, request));

      // Clean up request
      request = null;
    };

    // Handle timeout
    request.ontimeout = function handleTimeout() {
      var timeoutErrorMessage = 'timeout of ' + config.timeout + 'ms exceeded';
      if (config.timeoutErrorMessage) {
        timeoutErrorMessage = config.timeoutErrorMessage;
      }
      reject(createError(
        timeoutErrorMessage,
        config,
        config.transitional && config.transitional.clarifyTimeoutError ? 'ETIMEDOUT' : 'ECONNABORTED',
        request));

      // Clean up request
      request = null;
    };

    // Add xsrf header
    // This is only done if running in a standard browser environment.
    // Specifically not if we're in a web worker, or react-native.
    if (utils.isStandardBrowserEnv()) {
      // Add xsrf header
      var xsrfValue = (config.withCredentials || isURLSameOrigin(fullPath)) && config.xsrfCookieName ?
        cookies.read(config.xsrfCookieName) :
        undefined;

      if (xsrfValue) {
        requestHeaders[config.xsrfHeaderName] = xsrfValue;
      }
    }

    // Add headers to the request
    if ('setRequestHeader' in request) {
      utils.forEach(requestHeaders, function setRequestHeader(val, key) {
        if (typeof requestData === 'undefined' && key.toLowerCase() === 'content-type') {
          // Remove Content-Type if data is undefined
          delete requestHeaders[key];
        } else {
          // Otherwise add header to the request
          request.setRequestHeader(key, val);
        }
      });
    }

    // Add withCredentials to request if needed
    if (!utils.isUndefined(config.withCredentials)) {
      request.withCredentials = !!config.withCredentials;
    }

    // Add responseType to request if needed
    if (responseType && responseType !== 'json') {
      request.responseType = config.responseType;
    }

    // Handle progress if needed
    if (typeof config.onDownloadProgress === 'function') {
      request.addEventListener('progress', config.onDownloadProgress);
    }

    // Not all browsers support upload events
    if (typeof config.onUploadProgress === 'function' && request.upload) {
      request.upload.addEventListener('progress', config.onUploadProgress);
    }

    if (config.cancelToken) {
      // Handle cancellation
      config.cancelToken.promise.then(function onCanceled(cancel) {
        if (!request) {
          return;
        }

        request.abort();
        reject(cancel);
        // Clean up request
        request = null;
      });
    }

    if (!requestData) {
      requestData = null;
    }

    // Send the request
    request.send(requestData);
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/axios.js":
/*!*****************************************!*\
  !*** ./node_modules/axios/lib/axios.js ***!
  \*****************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./utils */ "./node_modules/axios/lib/utils.js");
var bind = __webpack_require__(/*! ./helpers/bind */ "./node_modules/axios/lib/helpers/bind.js");
var Axios = __webpack_require__(/*! ./core/Axios */ "./node_modules/axios/lib/core/Axios.js");
var mergeConfig = __webpack_require__(/*! ./core/mergeConfig */ "./node_modules/axios/lib/core/mergeConfig.js");
var defaults = __webpack_require__(/*! ./defaults */ "./node_modules/axios/lib/defaults.js");

/**
 * Create an instance of Axios
 *
 * @param {Object} defaultConfig The default config for the instance
 * @return {Axios} A new instance of Axios
 */
function createInstance(defaultConfig) {
  var context = new Axios(defaultConfig);
  var instance = bind(Axios.prototype.request, context);

  // Copy axios.prototype to instance
  utils.extend(instance, Axios.prototype, context);

  // Copy context to instance
  utils.extend(instance, context);

  return instance;
}

// Create the default instance to be exported
var axios = createInstance(defaults);

// Expose Axios class to allow class inheritance
axios.Axios = Axios;

// Factory for creating new instances
axios.create = function create(instanceConfig) {
  return createInstance(mergeConfig(axios.defaults, instanceConfig));
};

// Expose Cancel & CancelToken
axios.Cancel = __webpack_require__(/*! ./cancel/Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");
axios.CancelToken = __webpack_require__(/*! ./cancel/CancelToken */ "./node_modules/axios/lib/cancel/CancelToken.js");
axios.isCancel = __webpack_require__(/*! ./cancel/isCancel */ "./node_modules/axios/lib/cancel/isCancel.js");

// Expose all/spread
axios.all = function all(promises) {
  return Promise.all(promises);
};
axios.spread = __webpack_require__(/*! ./helpers/spread */ "./node_modules/axios/lib/helpers/spread.js");

// Expose isAxiosError
axios.isAxiosError = __webpack_require__(/*! ./helpers/isAxiosError */ "./node_modules/axios/lib/helpers/isAxiosError.js");

module.exports = axios;

// Allow use of default import syntax in TypeScript
module.exports["default"] = axios;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/Cancel.js":
/*!*************************************************!*\
  !*** ./node_modules/axios/lib/cancel/Cancel.js ***!
  \*************************************************/
/***/ (function(module) {

"use strict";


/**
 * A `Cancel` is an object that is thrown when an operation is canceled.
 *
 * @class
 * @param {string=} message The message.
 */
function Cancel(message) {
  this.message = message;
}

Cancel.prototype.toString = function toString() {
  return 'Cancel' + (this.message ? ': ' + this.message : '');
};

Cancel.prototype.__CANCEL__ = true;

module.exports = Cancel;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/CancelToken.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/cancel/CancelToken.js ***!
  \******************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var Cancel = __webpack_require__(/*! ./Cancel */ "./node_modules/axios/lib/cancel/Cancel.js");

/**
 * A `CancelToken` is an object that can be used to request cancellation of an operation.
 *
 * @class
 * @param {Function} executor The executor function.
 */
function CancelToken(executor) {
  if (typeof executor !== 'function') {
    throw new TypeError('executor must be a function.');
  }

  var resolvePromise;
  this.promise = new Promise(function promiseExecutor(resolve) {
    resolvePromise = resolve;
  });

  var token = this;
  executor(function cancel(message) {
    if (token.reason) {
      // Cancellation has already been requested
      return;
    }

    token.reason = new Cancel(message);
    resolvePromise(token.reason);
  });
}

/**
 * Throws a `Cancel` if cancellation has been requested.
 */
CancelToken.prototype.throwIfRequested = function throwIfRequested() {
  if (this.reason) {
    throw this.reason;
  }
};

/**
 * Returns an object that contains a new `CancelToken` and a function that, when called,
 * cancels the `CancelToken`.
 */
CancelToken.source = function source() {
  var cancel;
  var token = new CancelToken(function executor(c) {
    cancel = c;
  });
  return {
    token: token,
    cancel: cancel
  };
};

module.exports = CancelToken;


/***/ }),

/***/ "./node_modules/axios/lib/cancel/isCancel.js":
/*!***************************************************!*\
  !*** ./node_modules/axios/lib/cancel/isCancel.js ***!
  \***************************************************/
/***/ (function(module) {

"use strict";


module.exports = function isCancel(value) {
  return !!(value && value.__CANCEL__);
};


/***/ }),

/***/ "./node_modules/axios/lib/core/Axios.js":
/*!**********************************************!*\
  !*** ./node_modules/axios/lib/core/Axios.js ***!
  \**********************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var buildURL = __webpack_require__(/*! ../helpers/buildURL */ "./node_modules/axios/lib/helpers/buildURL.js");
var InterceptorManager = __webpack_require__(/*! ./InterceptorManager */ "./node_modules/axios/lib/core/InterceptorManager.js");
var dispatchRequest = __webpack_require__(/*! ./dispatchRequest */ "./node_modules/axios/lib/core/dispatchRequest.js");
var mergeConfig = __webpack_require__(/*! ./mergeConfig */ "./node_modules/axios/lib/core/mergeConfig.js");
var validator = __webpack_require__(/*! ../helpers/validator */ "./node_modules/axios/lib/helpers/validator.js");

var validators = validator.validators;
/**
 * Create a new instance of Axios
 *
 * @param {Object} instanceConfig The default config for the instance
 */
function Axios(instanceConfig) {
  this.defaults = instanceConfig;
  this.interceptors = {
    request: new InterceptorManager(),
    response: new InterceptorManager()
  };
}

/**
 * Dispatch a request
 *
 * @param {Object} config The config specific for this request (merged with this.defaults)
 */
Axios.prototype.request = function request(config) {
  /*eslint no-param-reassign:0*/
  // Allow for axios('example/url'[, config]) a la fetch API
  if (typeof config === 'string') {
    config = arguments[1] || {};
    config.url = arguments[0];
  } else {
    config = config || {};
  }

  config = mergeConfig(this.defaults, config);

  // Set config.method
  if (config.method) {
    config.method = config.method.toLowerCase();
  } else if (this.defaults.method) {
    config.method = this.defaults.method.toLowerCase();
  } else {
    config.method = 'get';
  }

  var transitional = config.transitional;

  if (transitional !== undefined) {
    validator.assertOptions(transitional, {
      silentJSONParsing: validators.transitional(validators.boolean, '1.0.0'),
      forcedJSONParsing: validators.transitional(validators.boolean, '1.0.0'),
      clarifyTimeoutError: validators.transitional(validators.boolean, '1.0.0')
    }, false);
  }

  // filter out skipped interceptors
  var requestInterceptorChain = [];
  var synchronousRequestInterceptors = true;
  this.interceptors.request.forEach(function unshiftRequestInterceptors(interceptor) {
    if (typeof interceptor.runWhen === 'function' && interceptor.runWhen(config) === false) {
      return;
    }

    synchronousRequestInterceptors = synchronousRequestInterceptors && interceptor.synchronous;

    requestInterceptorChain.unshift(interceptor.fulfilled, interceptor.rejected);
  });

  var responseInterceptorChain = [];
  this.interceptors.response.forEach(function pushResponseInterceptors(interceptor) {
    responseInterceptorChain.push(interceptor.fulfilled, interceptor.rejected);
  });

  var promise;

  if (!synchronousRequestInterceptors) {
    var chain = [dispatchRequest, undefined];

    Array.prototype.unshift.apply(chain, requestInterceptorChain);
    chain = chain.concat(responseInterceptorChain);

    promise = Promise.resolve(config);
    while (chain.length) {
      promise = promise.then(chain.shift(), chain.shift());
    }

    return promise;
  }


  var newConfig = config;
  while (requestInterceptorChain.length) {
    var onFulfilled = requestInterceptorChain.shift();
    var onRejected = requestInterceptorChain.shift();
    try {
      newConfig = onFulfilled(newConfig);
    } catch (error) {
      onRejected(error);
      break;
    }
  }

  try {
    promise = dispatchRequest(newConfig);
  } catch (error) {
    return Promise.reject(error);
  }

  while (responseInterceptorChain.length) {
    promise = promise.then(responseInterceptorChain.shift(), responseInterceptorChain.shift());
  }

  return promise;
};

Axios.prototype.getUri = function getUri(config) {
  config = mergeConfig(this.defaults, config);
  return buildURL(config.url, config.params, config.paramsSerializer).replace(/^\?/, '');
};

// Provide aliases for supported request methods
utils.forEach(['delete', 'get', 'head', 'options'], function forEachMethodNoData(method) {
  /*eslint func-names:0*/
  Axios.prototype[method] = function(url, config) {
    return this.request(mergeConfig(config || {}, {
      method: method,
      url: url,
      data: (config || {}).data
    }));
  };
});

utils.forEach(['post', 'put', 'patch'], function forEachMethodWithData(method) {
  /*eslint func-names:0*/
  Axios.prototype[method] = function(url, data, config) {
    return this.request(mergeConfig(config || {}, {
      method: method,
      url: url,
      data: data
    }));
  };
});

module.exports = Axios;


/***/ }),

/***/ "./node_modules/axios/lib/core/InterceptorManager.js":
/*!***********************************************************!*\
  !*** ./node_modules/axios/lib/core/InterceptorManager.js ***!
  \***********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

function InterceptorManager() {
  this.handlers = [];
}

/**
 * Add a new interceptor to the stack
 *
 * @param {Function} fulfilled The function to handle `then` for a `Promise`
 * @param {Function} rejected The function to handle `reject` for a `Promise`
 *
 * @return {Number} An ID used to remove interceptor later
 */
InterceptorManager.prototype.use = function use(fulfilled, rejected, options) {
  this.handlers.push({
    fulfilled: fulfilled,
    rejected: rejected,
    synchronous: options ? options.synchronous : false,
    runWhen: options ? options.runWhen : null
  });
  return this.handlers.length - 1;
};

/**
 * Remove an interceptor from the stack
 *
 * @param {Number} id The ID that was returned by `use`
 */
InterceptorManager.prototype.eject = function eject(id) {
  if (this.handlers[id]) {
    this.handlers[id] = null;
  }
};

/**
 * Iterate over all the registered interceptors
 *
 * This method is particularly useful for skipping over any
 * interceptors that may have become `null` calling `eject`.
 *
 * @param {Function} fn The function to call for each interceptor
 */
InterceptorManager.prototype.forEach = function forEach(fn) {
  utils.forEach(this.handlers, function forEachHandler(h) {
    if (h !== null) {
      fn(h);
    }
  });
};

module.exports = InterceptorManager;


/***/ }),

/***/ "./node_modules/axios/lib/core/buildFullPath.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/core/buildFullPath.js ***!
  \******************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var isAbsoluteURL = __webpack_require__(/*! ../helpers/isAbsoluteURL */ "./node_modules/axios/lib/helpers/isAbsoluteURL.js");
var combineURLs = __webpack_require__(/*! ../helpers/combineURLs */ "./node_modules/axios/lib/helpers/combineURLs.js");

/**
 * Creates a new URL by combining the baseURL with the requestedURL,
 * only when the requestedURL is not already an absolute URL.
 * If the requestURL is absolute, this function returns the requestedURL untouched.
 *
 * @param {string} baseURL The base URL
 * @param {string} requestedURL Absolute or relative URL to combine
 * @returns {string} The combined full path
 */
module.exports = function buildFullPath(baseURL, requestedURL) {
  if (baseURL && !isAbsoluteURL(requestedURL)) {
    return combineURLs(baseURL, requestedURL);
  }
  return requestedURL;
};


/***/ }),

/***/ "./node_modules/axios/lib/core/createError.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/core/createError.js ***!
  \****************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var enhanceError = __webpack_require__(/*! ./enhanceError */ "./node_modules/axios/lib/core/enhanceError.js");

/**
 * Create an Error with the specified message, config, error code, request and response.
 *
 * @param {string} message The error message.
 * @param {Object} config The config.
 * @param {string} [code] The error code (for example, 'ECONNABORTED').
 * @param {Object} [request] The request.
 * @param {Object} [response] The response.
 * @returns {Error} The created error.
 */
module.exports = function createError(message, config, code, request, response) {
  var error = new Error(message);
  return enhanceError(error, config, code, request, response);
};


/***/ }),

/***/ "./node_modules/axios/lib/core/dispatchRequest.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/core/dispatchRequest.js ***!
  \********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var transformData = __webpack_require__(/*! ./transformData */ "./node_modules/axios/lib/core/transformData.js");
var isCancel = __webpack_require__(/*! ../cancel/isCancel */ "./node_modules/axios/lib/cancel/isCancel.js");
var defaults = __webpack_require__(/*! ../defaults */ "./node_modules/axios/lib/defaults.js");

/**
 * Throws a `Cancel` if cancellation has been requested.
 */
function throwIfCancellationRequested(config) {
  if (config.cancelToken) {
    config.cancelToken.throwIfRequested();
  }
}

/**
 * Dispatch a request to the server using the configured adapter.
 *
 * @param {object} config The config that is to be used for the request
 * @returns {Promise} The Promise to be fulfilled
 */
module.exports = function dispatchRequest(config) {
  throwIfCancellationRequested(config);

  // Ensure headers exist
  config.headers = config.headers || {};

  // Transform request data
  config.data = transformData.call(
    config,
    config.data,
    config.headers,
    config.transformRequest
  );

  // Flatten headers
  config.headers = utils.merge(
    config.headers.common || {},
    config.headers[config.method] || {},
    config.headers
  );

  utils.forEach(
    ['delete', 'get', 'head', 'post', 'put', 'patch', 'common'],
    function cleanHeaderConfig(method) {
      delete config.headers[method];
    }
  );

  var adapter = config.adapter || defaults.adapter;

  return adapter(config).then(function onAdapterResolution(response) {
    throwIfCancellationRequested(config);

    // Transform response data
    response.data = transformData.call(
      config,
      response.data,
      response.headers,
      config.transformResponse
    );

    return response;
  }, function onAdapterRejection(reason) {
    if (!isCancel(reason)) {
      throwIfCancellationRequested(config);

      // Transform response data
      if (reason && reason.response) {
        reason.response.data = transformData.call(
          config,
          reason.response.data,
          reason.response.headers,
          config.transformResponse
        );
      }
    }

    return Promise.reject(reason);
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/core/enhanceError.js":
/*!*****************************************************!*\
  !*** ./node_modules/axios/lib/core/enhanceError.js ***!
  \*****************************************************/
/***/ (function(module) {

"use strict";


/**
 * Update an Error with the specified config, error code, and response.
 *
 * @param {Error} error The error to update.
 * @param {Object} config The config.
 * @param {string} [code] The error code (for example, 'ECONNABORTED').
 * @param {Object} [request] The request.
 * @param {Object} [response] The response.
 * @returns {Error} The error.
 */
module.exports = function enhanceError(error, config, code, request, response) {
  error.config = config;
  if (code) {
    error.code = code;
  }

  error.request = request;
  error.response = response;
  error.isAxiosError = true;

  error.toJSON = function toJSON() {
    return {
      // Standard
      message: this.message,
      name: this.name,
      // Microsoft
      description: this.description,
      number: this.number,
      // Mozilla
      fileName: this.fileName,
      lineNumber: this.lineNumber,
      columnNumber: this.columnNumber,
      stack: this.stack,
      // Axios
      config: this.config,
      code: this.code
    };
  };
  return error;
};


/***/ }),

/***/ "./node_modules/axios/lib/core/mergeConfig.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/core/mergeConfig.js ***!
  \****************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ../utils */ "./node_modules/axios/lib/utils.js");

/**
 * Config-specific merge-function which creates a new config-object
 * by merging two configuration objects together.
 *
 * @param {Object} config1
 * @param {Object} config2
 * @returns {Object} New object resulting from merging config2 to config1
 */
module.exports = function mergeConfig(config1, config2) {
  // eslint-disable-next-line no-param-reassign
  config2 = config2 || {};
  var config = {};

  var valueFromConfig2Keys = ['url', 'method', 'data'];
  var mergeDeepPropertiesKeys = ['headers', 'auth', 'proxy', 'params'];
  var defaultToConfig2Keys = [
    'baseURL', 'transformRequest', 'transformResponse', 'paramsSerializer',
    'timeout', 'timeoutMessage', 'withCredentials', 'adapter', 'responseType', 'xsrfCookieName',
    'xsrfHeaderName', 'onUploadProgress', 'onDownloadProgress', 'decompress',
    'maxContentLength', 'maxBodyLength', 'maxRedirects', 'transport', 'httpAgent',
    'httpsAgent', 'cancelToken', 'socketPath', 'responseEncoding'
  ];
  var directMergeKeys = ['validateStatus'];

  function getMergedValue(target, source) {
    if (utils.isPlainObject(target) && utils.isPlainObject(source)) {
      return utils.merge(target, source);
    } else if (utils.isPlainObject(source)) {
      return utils.merge({}, source);
    } else if (utils.isArray(source)) {
      return source.slice();
    }
    return source;
  }

  function mergeDeepProperties(prop) {
    if (!utils.isUndefined(config2[prop])) {
      config[prop] = getMergedValue(config1[prop], config2[prop]);
    } else if (!utils.isUndefined(config1[prop])) {
      config[prop] = getMergedValue(undefined, config1[prop]);
    }
  }

  utils.forEach(valueFromConfig2Keys, function valueFromConfig2(prop) {
    if (!utils.isUndefined(config2[prop])) {
      config[prop] = getMergedValue(undefined, config2[prop]);
    }
  });

  utils.forEach(mergeDeepPropertiesKeys, mergeDeepProperties);

  utils.forEach(defaultToConfig2Keys, function defaultToConfig2(prop) {
    if (!utils.isUndefined(config2[prop])) {
      config[prop] = getMergedValue(undefined, config2[prop]);
    } else if (!utils.isUndefined(config1[prop])) {
      config[prop] = getMergedValue(undefined, config1[prop]);
    }
  });

  utils.forEach(directMergeKeys, function merge(prop) {
    if (prop in config2) {
      config[prop] = getMergedValue(config1[prop], config2[prop]);
    } else if (prop in config1) {
      config[prop] = getMergedValue(undefined, config1[prop]);
    }
  });

  var axiosKeys = valueFromConfig2Keys
    .concat(mergeDeepPropertiesKeys)
    .concat(defaultToConfig2Keys)
    .concat(directMergeKeys);

  var otherKeys = Object
    .keys(config1)
    .concat(Object.keys(config2))
    .filter(function filterAxiosKeys(key) {
      return axiosKeys.indexOf(key) === -1;
    });

  utils.forEach(otherKeys, mergeDeepProperties);

  return config;
};


/***/ }),

/***/ "./node_modules/axios/lib/core/settle.js":
/*!***********************************************!*\
  !*** ./node_modules/axios/lib/core/settle.js ***!
  \***********************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var createError = __webpack_require__(/*! ./createError */ "./node_modules/axios/lib/core/createError.js");

/**
 * Resolve or reject a Promise based on response status.
 *
 * @param {Function} resolve A function that resolves the promise.
 * @param {Function} reject A function that rejects the promise.
 * @param {object} response The response.
 */
module.exports = function settle(resolve, reject, response) {
  var validateStatus = response.config.validateStatus;
  if (!response.status || !validateStatus || validateStatus(response.status)) {
    resolve(response);
  } else {
    reject(createError(
      'Request failed with status code ' + response.status,
      response.config,
      null,
      response.request,
      response
    ));
  }
};


/***/ }),

/***/ "./node_modules/axios/lib/core/transformData.js":
/*!******************************************************!*\
  !*** ./node_modules/axios/lib/core/transformData.js ***!
  \******************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");
var defaults = __webpack_require__(/*! ./../defaults */ "./node_modules/axios/lib/defaults.js");

/**
 * Transform the data for a request or a response
 *
 * @param {Object|String} data The data to be transformed
 * @param {Array} headers The headers for the request or response
 * @param {Array|Function} fns A single function or Array of functions
 * @returns {*} The resulting transformed data
 */
module.exports = function transformData(data, headers, fns) {
  var context = this || defaults;
  /*eslint no-param-reassign:0*/
  utils.forEach(fns, function transform(fn) {
    data = fn.call(context, data, headers);
  });

  return data;
};


/***/ }),

/***/ "./node_modules/axios/lib/defaults.js":
/*!********************************************!*\
  !*** ./node_modules/axios/lib/defaults.js ***!
  \********************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";
/* provided dependency */ var process = __webpack_require__(/*! process/browser */ "./node_modules/process/browser.js");


var utils = __webpack_require__(/*! ./utils */ "./node_modules/axios/lib/utils.js");
var normalizeHeaderName = __webpack_require__(/*! ./helpers/normalizeHeaderName */ "./node_modules/axios/lib/helpers/normalizeHeaderName.js");
var enhanceError = __webpack_require__(/*! ./core/enhanceError */ "./node_modules/axios/lib/core/enhanceError.js");

var DEFAULT_CONTENT_TYPE = {
  'Content-Type': 'application/x-www-form-urlencoded'
};

function setContentTypeIfUnset(headers, value) {
  if (!utils.isUndefined(headers) && utils.isUndefined(headers['Content-Type'])) {
    headers['Content-Type'] = value;
  }
}

function getDefaultAdapter() {
  var adapter;
  if (typeof XMLHttpRequest !== 'undefined') {
    // For browsers use XHR adapter
    adapter = __webpack_require__(/*! ./adapters/xhr */ "./node_modules/axios/lib/adapters/xhr.js");
  } else if (typeof process !== 'undefined' && Object.prototype.toString.call(process) === '[object process]') {
    // For node use HTTP adapter
    adapter = __webpack_require__(/*! ./adapters/http */ "./node_modules/axios/lib/adapters/xhr.js");
  }
  return adapter;
}

function stringifySafely(rawValue, parser, encoder) {
  if (utils.isString(rawValue)) {
    try {
      (parser || JSON.parse)(rawValue);
      return utils.trim(rawValue);
    } catch (e) {
      if (e.name !== 'SyntaxError') {
        throw e;
      }
    }
  }

  return (encoder || JSON.stringify)(rawValue);
}

var defaults = {

  transitional: {
    silentJSONParsing: true,
    forcedJSONParsing: true,
    clarifyTimeoutError: false
  },

  adapter: getDefaultAdapter(),

  transformRequest: [function transformRequest(data, headers) {
    normalizeHeaderName(headers, 'Accept');
    normalizeHeaderName(headers, 'Content-Type');

    if (utils.isFormData(data) ||
      utils.isArrayBuffer(data) ||
      utils.isBuffer(data) ||
      utils.isStream(data) ||
      utils.isFile(data) ||
      utils.isBlob(data)
    ) {
      return data;
    }
    if (utils.isArrayBufferView(data)) {
      return data.buffer;
    }
    if (utils.isURLSearchParams(data)) {
      setContentTypeIfUnset(headers, 'application/x-www-form-urlencoded;charset=utf-8');
      return data.toString();
    }
    if (utils.isObject(data) || (headers && headers['Content-Type'] === 'application/json')) {
      setContentTypeIfUnset(headers, 'application/json');
      return stringifySafely(data);
    }
    return data;
  }],

  transformResponse: [function transformResponse(data) {
    var transitional = this.transitional;
    var silentJSONParsing = transitional && transitional.silentJSONParsing;
    var forcedJSONParsing = transitional && transitional.forcedJSONParsing;
    var strictJSONParsing = !silentJSONParsing && this.responseType === 'json';

    if (strictJSONParsing || (forcedJSONParsing && utils.isString(data) && data.length)) {
      try {
        return JSON.parse(data);
      } catch (e) {
        if (strictJSONParsing) {
          if (e.name === 'SyntaxError') {
            throw enhanceError(e, this, 'E_JSON_PARSE');
          }
          throw e;
        }
      }
    }

    return data;
  }],

  /**
   * A timeout in milliseconds to abort a request. If set to 0 (default) a
   * timeout is not created.
   */
  timeout: 0,

  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',

  maxContentLength: -1,
  maxBodyLength: -1,

  validateStatus: function validateStatus(status) {
    return status >= 200 && status < 300;
  }
};

defaults.headers = {
  common: {
    'Accept': 'application/json, text/plain, */*'
  }
};

utils.forEach(['delete', 'get', 'head'], function forEachMethodNoData(method) {
  defaults.headers[method] = {};
});

utils.forEach(['post', 'put', 'patch'], function forEachMethodWithData(method) {
  defaults.headers[method] = utils.merge(DEFAULT_CONTENT_TYPE);
});

module.exports = defaults;


/***/ }),

/***/ "./node_modules/axios/lib/helpers/bind.js":
/*!************************************************!*\
  !*** ./node_modules/axios/lib/helpers/bind.js ***!
  \************************************************/
/***/ (function(module) {

"use strict";


module.exports = function bind(fn, thisArg) {
  return function wrap() {
    var args = new Array(arguments.length);
    for (var i = 0; i < args.length; i++) {
      args[i] = arguments[i];
    }
    return fn.apply(thisArg, args);
  };
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/buildURL.js":
/*!****************************************************!*\
  !*** ./node_modules/axios/lib/helpers/buildURL.js ***!
  \****************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

function encode(val) {
  return encodeURIComponent(val).
    replace(/%3A/gi, ':').
    replace(/%24/g, '$').
    replace(/%2C/gi, ',').
    replace(/%20/g, '+').
    replace(/%5B/gi, '[').
    replace(/%5D/gi, ']');
}

/**
 * Build a URL by appending params to the end
 *
 * @param {string} url The base of the url (e.g., http://www.google.com)
 * @param {object} [params] The params to be appended
 * @returns {string} The formatted url
 */
module.exports = function buildURL(url, params, paramsSerializer) {
  /*eslint no-param-reassign:0*/
  if (!params) {
    return url;
  }

  var serializedParams;
  if (paramsSerializer) {
    serializedParams = paramsSerializer(params);
  } else if (utils.isURLSearchParams(params)) {
    serializedParams = params.toString();
  } else {
    var parts = [];

    utils.forEach(params, function serialize(val, key) {
      if (val === null || typeof val === 'undefined') {
        return;
      }

      if (utils.isArray(val)) {
        key = key + '[]';
      } else {
        val = [val];
      }

      utils.forEach(val, function parseValue(v) {
        if (utils.isDate(v)) {
          v = v.toISOString();
        } else if (utils.isObject(v)) {
          v = JSON.stringify(v);
        }
        parts.push(encode(key) + '=' + encode(v));
      });
    });

    serializedParams = parts.join('&');
  }

  if (serializedParams) {
    var hashmarkIndex = url.indexOf('#');
    if (hashmarkIndex !== -1) {
      url = url.slice(0, hashmarkIndex);
    }

    url += (url.indexOf('?') === -1 ? '?' : '&') + serializedParams;
  }

  return url;
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/combineURLs.js":
/*!*******************************************************!*\
  !*** ./node_modules/axios/lib/helpers/combineURLs.js ***!
  \*******************************************************/
/***/ (function(module) {

"use strict";


/**
 * Creates a new URL by combining the specified URLs
 *
 * @param {string} baseURL The base URL
 * @param {string} relativeURL The relative URL
 * @returns {string} The combined URL
 */
module.exports = function combineURLs(baseURL, relativeURL) {
  return relativeURL
    ? baseURL.replace(/\/+$/, '') + '/' + relativeURL.replace(/^\/+/, '')
    : baseURL;
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/cookies.js":
/*!***************************************************!*\
  !*** ./node_modules/axios/lib/helpers/cookies.js ***!
  \***************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

module.exports = (
  utils.isStandardBrowserEnv() ?

  // Standard browser envs support document.cookie
    (function standardBrowserEnv() {
      return {
        write: function write(name, value, expires, path, domain, secure) {
          var cookie = [];
          cookie.push(name + '=' + encodeURIComponent(value));

          if (utils.isNumber(expires)) {
            cookie.push('expires=' + new Date(expires).toGMTString());
          }

          if (utils.isString(path)) {
            cookie.push('path=' + path);
          }

          if (utils.isString(domain)) {
            cookie.push('domain=' + domain);
          }

          if (secure === true) {
            cookie.push('secure');
          }

          document.cookie = cookie.join('; ');
        },

        read: function read(name) {
          var match = document.cookie.match(new RegExp('(^|;\\s*)(' + name + ')=([^;]*)'));
          return (match ? decodeURIComponent(match[3]) : null);
        },

        remove: function remove(name) {
          this.write(name, '', Date.now() - 86400000);
        }
      };
    })() :

  // Non standard browser env (web workers, react-native) lack needed support.
    (function nonStandardBrowserEnv() {
      return {
        write: function write() {},
        read: function read() { return null; },
        remove: function remove() {}
      };
    })()
);


/***/ }),

/***/ "./node_modules/axios/lib/helpers/isAbsoluteURL.js":
/*!*********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isAbsoluteURL.js ***!
  \*********************************************************/
/***/ (function(module) {

"use strict";


/**
 * Determines whether the specified URL is absolute
 *
 * @param {string} url The URL to test
 * @returns {boolean} True if the specified URL is absolute, otherwise false
 */
module.exports = function isAbsoluteURL(url) {
  // A URL is considered absolute if it begins with "<scheme>://" or "//" (protocol-relative URL).
  // RFC 3986 defines scheme name as a sequence of characters beginning with a letter and followed
  // by any combination of letters, digits, plus, period, or hyphen.
  return /^([a-z][a-z\d\+\-\.]*:)?\/\//i.test(url);
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/isAxiosError.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isAxiosError.js ***!
  \********************************************************/
/***/ (function(module) {

"use strict";


/**
 * Determines whether the payload is an error thrown by Axios
 *
 * @param {*} payload The value to test
 * @returns {boolean} True if the payload is an error thrown by Axios, otherwise false
 */
module.exports = function isAxiosError(payload) {
  return (typeof payload === 'object') && (payload.isAxiosError === true);
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/isURLSameOrigin.js":
/*!***********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/isURLSameOrigin.js ***!
  \***********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

module.exports = (
  utils.isStandardBrowserEnv() ?

  // Standard browser envs have full support of the APIs needed to test
  // whether the request URL is of the same origin as current location.
    (function standardBrowserEnv() {
      var msie = /(msie|trident)/i.test(navigator.userAgent);
      var urlParsingNode = document.createElement('a');
      var originURL;

      /**
    * Parse a URL to discover it's components
    *
    * @param {String} url The URL to be parsed
    * @returns {Object}
    */
      function resolveURL(url) {
        var href = url;

        if (msie) {
        // IE needs attribute set twice to normalize properties
          urlParsingNode.setAttribute('href', href);
          href = urlParsingNode.href;
        }

        urlParsingNode.setAttribute('href', href);

        // urlParsingNode provides the UrlUtils interface - http://url.spec.whatwg.org/#urlutils
        return {
          href: urlParsingNode.href,
          protocol: urlParsingNode.protocol ? urlParsingNode.protocol.replace(/:$/, '') : '',
          host: urlParsingNode.host,
          search: urlParsingNode.search ? urlParsingNode.search.replace(/^\?/, '') : '',
          hash: urlParsingNode.hash ? urlParsingNode.hash.replace(/^#/, '') : '',
          hostname: urlParsingNode.hostname,
          port: urlParsingNode.port,
          pathname: (urlParsingNode.pathname.charAt(0) === '/') ?
            urlParsingNode.pathname :
            '/' + urlParsingNode.pathname
        };
      }

      originURL = resolveURL(window.location.href);

      /**
    * Determine if a URL shares the same origin as the current location
    *
    * @param {String} requestURL The URL to test
    * @returns {boolean} True if URL shares the same origin, otherwise false
    */
      return function isURLSameOrigin(requestURL) {
        var parsed = (utils.isString(requestURL)) ? resolveURL(requestURL) : requestURL;
        return (parsed.protocol === originURL.protocol &&
            parsed.host === originURL.host);
      };
    })() :

  // Non standard browser envs (web workers, react-native) lack needed support.
    (function nonStandardBrowserEnv() {
      return function isURLSameOrigin() {
        return true;
      };
    })()
);


/***/ }),

/***/ "./node_modules/axios/lib/helpers/normalizeHeaderName.js":
/*!***************************************************************!*\
  !*** ./node_modules/axios/lib/helpers/normalizeHeaderName.js ***!
  \***************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ../utils */ "./node_modules/axios/lib/utils.js");

module.exports = function normalizeHeaderName(headers, normalizedName) {
  utils.forEach(headers, function processHeader(value, name) {
    if (name !== normalizedName && name.toUpperCase() === normalizedName.toUpperCase()) {
      headers[normalizedName] = value;
      delete headers[name];
    }
  });
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/parseHeaders.js":
/*!********************************************************!*\
  !*** ./node_modules/axios/lib/helpers/parseHeaders.js ***!
  \********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var utils = __webpack_require__(/*! ./../utils */ "./node_modules/axios/lib/utils.js");

// Headers whose duplicates are ignored by node
// c.f. https://nodejs.org/api/http.html#http_message_headers
var ignoreDuplicateOf = [
  'age', 'authorization', 'content-length', 'content-type', 'etag',
  'expires', 'from', 'host', 'if-modified-since', 'if-unmodified-since',
  'last-modified', 'location', 'max-forwards', 'proxy-authorization',
  'referer', 'retry-after', 'user-agent'
];

/**
 * Parse headers into an object
 *
 * ```
 * Date: Wed, 27 Aug 2014 08:58:49 GMT
 * Content-Type: application/json
 * Connection: keep-alive
 * Transfer-Encoding: chunked
 * ```
 *
 * @param {String} headers Headers needing to be parsed
 * @returns {Object} Headers parsed into an object
 */
module.exports = function parseHeaders(headers) {
  var parsed = {};
  var key;
  var val;
  var i;

  if (!headers) { return parsed; }

  utils.forEach(headers.split('\n'), function parser(line) {
    i = line.indexOf(':');
    key = utils.trim(line.substr(0, i)).toLowerCase();
    val = utils.trim(line.substr(i + 1));

    if (key) {
      if (parsed[key] && ignoreDuplicateOf.indexOf(key) >= 0) {
        return;
      }
      if (key === 'set-cookie') {
        parsed[key] = (parsed[key] ? parsed[key] : []).concat([val]);
      } else {
        parsed[key] = parsed[key] ? parsed[key] + ', ' + val : val;
      }
    }
  });

  return parsed;
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/spread.js":
/*!**************************************************!*\
  !*** ./node_modules/axios/lib/helpers/spread.js ***!
  \**************************************************/
/***/ (function(module) {

"use strict";


/**
 * Syntactic sugar for invoking a function and expanding an array for arguments.
 *
 * Common use case would be to use `Function.prototype.apply`.
 *
 *  ```js
 *  function f(x, y, z) {}
 *  var args = [1, 2, 3];
 *  f.apply(null, args);
 *  ```
 *
 * With `spread` this example can be re-written.
 *
 *  ```js
 *  spread(function(x, y, z) {})([1, 2, 3]);
 *  ```
 *
 * @param {Function} callback
 * @returns {Function}
 */
module.exports = function spread(callback) {
  return function wrap(arr) {
    return callback.apply(null, arr);
  };
};


/***/ }),

/***/ "./node_modules/axios/lib/helpers/validator.js":
/*!*****************************************************!*\
  !*** ./node_modules/axios/lib/helpers/validator.js ***!
  \*****************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var pkg = __webpack_require__(/*! ./../../package.json */ "./node_modules/axios/package.json");

var validators = {};

// eslint-disable-next-line func-names
['object', 'boolean', 'number', 'function', 'string', 'symbol'].forEach(function(type, i) {
  validators[type] = function validator(thing) {
    return typeof thing === type || 'a' + (i < 1 ? 'n ' : ' ') + type;
  };
});

var deprecatedWarnings = {};
var currentVerArr = pkg.version.split('.');

/**
 * Compare package versions
 * @param {string} version
 * @param {string?} thanVersion
 * @returns {boolean}
 */
function isOlderVersion(version, thanVersion) {
  var pkgVersionArr = thanVersion ? thanVersion.split('.') : currentVerArr;
  var destVer = version.split('.');
  for (var i = 0; i < 3; i++) {
    if (pkgVersionArr[i] > destVer[i]) {
      return true;
    } else if (pkgVersionArr[i] < destVer[i]) {
      return false;
    }
  }
  return false;
}

/**
 * Transitional option validator
 * @param {function|boolean?} validator
 * @param {string?} version
 * @param {string} message
 * @returns {function}
 */
validators.transitional = function transitional(validator, version, message) {
  var isDeprecated = version && isOlderVersion(version);

  function formatMessage(opt, desc) {
    return '[Axios v' + pkg.version + '] Transitional option \'' + opt + '\'' + desc + (message ? '. ' + message : '');
  }

  // eslint-disable-next-line func-names
  return function(value, opt, opts) {
    if (validator === false) {
      throw new Error(formatMessage(opt, ' has been removed in ' + version));
    }

    if (isDeprecated && !deprecatedWarnings[opt]) {
      deprecatedWarnings[opt] = true;
      // eslint-disable-next-line no-console
      console.warn(
        formatMessage(
          opt,
          ' has been deprecated since v' + version + ' and will be removed in the near future'
        )
      );
    }

    return validator ? validator(value, opt, opts) : true;
  };
};

/**
 * Assert object's properties type
 * @param {object} options
 * @param {object} schema
 * @param {boolean?} allowUnknown
 */

function assertOptions(options, schema, allowUnknown) {
  if (typeof options !== 'object') {
    throw new TypeError('options must be an object');
  }
  var keys = Object.keys(options);
  var i = keys.length;
  while (i-- > 0) {
    var opt = keys[i];
    var validator = schema[opt];
    if (validator) {
      var value = options[opt];
      var result = value === undefined || validator(value, opt, options);
      if (result !== true) {
        throw new TypeError('option ' + opt + ' must be ' + result);
      }
      continue;
    }
    if (allowUnknown !== true) {
      throw Error('Unknown option ' + opt);
    }
  }
}

module.exports = {
  isOlderVersion: isOlderVersion,
  assertOptions: assertOptions,
  validators: validators
};


/***/ }),

/***/ "./node_modules/axios/lib/utils.js":
/*!*****************************************!*\
  !*** ./node_modules/axios/lib/utils.js ***!
  \*****************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

"use strict";


var bind = __webpack_require__(/*! ./helpers/bind */ "./node_modules/axios/lib/helpers/bind.js");

// utils is a library of generic helper functions non-specific to axios

var toString = Object.prototype.toString;

/**
 * Determine if a value is an Array
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an Array, otherwise false
 */
function isArray(val) {
  return toString.call(val) === '[object Array]';
}

/**
 * Determine if a value is undefined
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if the value is undefined, otherwise false
 */
function isUndefined(val) {
  return typeof val === 'undefined';
}

/**
 * Determine if a value is a Buffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Buffer, otherwise false
 */
function isBuffer(val) {
  return val !== null && !isUndefined(val) && val.constructor !== null && !isUndefined(val.constructor)
    && typeof val.constructor.isBuffer === 'function' && val.constructor.isBuffer(val);
}

/**
 * Determine if a value is an ArrayBuffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an ArrayBuffer, otherwise false
 */
function isArrayBuffer(val) {
  return toString.call(val) === '[object ArrayBuffer]';
}

/**
 * Determine if a value is a FormData
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an FormData, otherwise false
 */
function isFormData(val) {
  return (typeof FormData !== 'undefined') && (val instanceof FormData);
}

/**
 * Determine if a value is a view on an ArrayBuffer
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a view on an ArrayBuffer, otherwise false
 */
function isArrayBufferView(val) {
  var result;
  if ((typeof ArrayBuffer !== 'undefined') && (ArrayBuffer.isView)) {
    result = ArrayBuffer.isView(val);
  } else {
    result = (val) && (val.buffer) && (val.buffer instanceof ArrayBuffer);
  }
  return result;
}

/**
 * Determine if a value is a String
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a String, otherwise false
 */
function isString(val) {
  return typeof val === 'string';
}

/**
 * Determine if a value is a Number
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Number, otherwise false
 */
function isNumber(val) {
  return typeof val === 'number';
}

/**
 * Determine if a value is an Object
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is an Object, otherwise false
 */
function isObject(val) {
  return val !== null && typeof val === 'object';
}

/**
 * Determine if a value is a plain Object
 *
 * @param {Object} val The value to test
 * @return {boolean} True if value is a plain Object, otherwise false
 */
function isPlainObject(val) {
  if (toString.call(val) !== '[object Object]') {
    return false;
  }

  var prototype = Object.getPrototypeOf(val);
  return prototype === null || prototype === Object.prototype;
}

/**
 * Determine if a value is a Date
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Date, otherwise false
 */
function isDate(val) {
  return toString.call(val) === '[object Date]';
}

/**
 * Determine if a value is a File
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a File, otherwise false
 */
function isFile(val) {
  return toString.call(val) === '[object File]';
}

/**
 * Determine if a value is a Blob
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Blob, otherwise false
 */
function isBlob(val) {
  return toString.call(val) === '[object Blob]';
}

/**
 * Determine if a value is a Function
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Function, otherwise false
 */
function isFunction(val) {
  return toString.call(val) === '[object Function]';
}

/**
 * Determine if a value is a Stream
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a Stream, otherwise false
 */
function isStream(val) {
  return isObject(val) && isFunction(val.pipe);
}

/**
 * Determine if a value is a URLSearchParams object
 *
 * @param {Object} val The value to test
 * @returns {boolean} True if value is a URLSearchParams object, otherwise false
 */
function isURLSearchParams(val) {
  return typeof URLSearchParams !== 'undefined' && val instanceof URLSearchParams;
}

/**
 * Trim excess whitespace off the beginning and end of a string
 *
 * @param {String} str The String to trim
 * @returns {String} The String freed of excess whitespace
 */
function trim(str) {
  return str.trim ? str.trim() : str.replace(/^\s+|\s+$/g, '');
}

/**
 * Determine if we're running in a standard browser environment
 *
 * This allows axios to run in a web worker, and react-native.
 * Both environments support XMLHttpRequest, but not fully standard globals.
 *
 * web workers:
 *  typeof window -> undefined
 *  typeof document -> undefined
 *
 * react-native:
 *  navigator.product -> 'ReactNative'
 * nativescript
 *  navigator.product -> 'NativeScript' or 'NS'
 */
function isStandardBrowserEnv() {
  if (typeof navigator !== 'undefined' && (navigator.product === 'ReactNative' ||
                                           navigator.product === 'NativeScript' ||
                                           navigator.product === 'NS')) {
    return false;
  }
  return (
    typeof window !== 'undefined' &&
    typeof document !== 'undefined'
  );
}

/**
 * Iterate over an Array or an Object invoking a function for each item.
 *
 * If `obj` is an Array callback will be called passing
 * the value, index, and complete array for each item.
 *
 * If 'obj' is an Object callback will be called passing
 * the value, key, and complete object for each property.
 *
 * @param {Object|Array} obj The object to iterate
 * @param {Function} fn The callback to invoke for each item
 */
function forEach(obj, fn) {
  // Don't bother if no value provided
  if (obj === null || typeof obj === 'undefined') {
    return;
  }

  // Force an array if not already something iterable
  if (typeof obj !== 'object') {
    /*eslint no-param-reassign:0*/
    obj = [obj];
  }

  if (isArray(obj)) {
    // Iterate over array values
    for (var i = 0, l = obj.length; i < l; i++) {
      fn.call(null, obj[i], i, obj);
    }
  } else {
    // Iterate over object keys
    for (var key in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) {
        fn.call(null, obj[key], key, obj);
      }
    }
  }
}

/**
 * Accepts varargs expecting each argument to be an object, then
 * immutably merges the properties of each object and returns result.
 *
 * When multiple objects contain the same key the later object in
 * the arguments list will take precedence.
 *
 * Example:
 *
 * ```js
 * var result = merge({foo: 123}, {foo: 456});
 * console.log(result.foo); // outputs 456
 * ```
 *
 * @param {Object} obj1 Object to merge
 * @returns {Object} Result of all merge properties
 */
function merge(/* obj1, obj2, obj3, ... */) {
  var result = {};
  function assignValue(val, key) {
    if (isPlainObject(result[key]) && isPlainObject(val)) {
      result[key] = merge(result[key], val);
    } else if (isPlainObject(val)) {
      result[key] = merge({}, val);
    } else if (isArray(val)) {
      result[key] = val.slice();
    } else {
      result[key] = val;
    }
  }

  for (var i = 0, l = arguments.length; i < l; i++) {
    forEach(arguments[i], assignValue);
  }
  return result;
}

/**
 * Extends object a by mutably adding to it the properties of object b.
 *
 * @param {Object} a The object to be extended
 * @param {Object} b The object to copy properties from
 * @param {Object} thisArg The object to bind function to
 * @return {Object} The resulting value of object a
 */
function extend(a, b, thisArg) {
  forEach(b, function assignValue(val, key) {
    if (thisArg && typeof val === 'function') {
      a[key] = bind(val, thisArg);
    } else {
      a[key] = val;
    }
  });
  return a;
}

/**
 * Remove byte order marker. This catches EF BB BF (the UTF-8 BOM)
 *
 * @param {string} content with BOM
 * @return {string} content value without BOM
 */
function stripBOM(content) {
  if (content.charCodeAt(0) === 0xFEFF) {
    content = content.slice(1);
  }
  return content;
}

module.exports = {
  isArray: isArray,
  isArrayBuffer: isArrayBuffer,
  isBuffer: isBuffer,
  isFormData: isFormData,
  isArrayBufferView: isArrayBufferView,
  isString: isString,
  isNumber: isNumber,
  isObject: isObject,
  isPlainObject: isPlainObject,
  isUndefined: isUndefined,
  isDate: isDate,
  isFile: isFile,
  isBlob: isBlob,
  isFunction: isFunction,
  isStream: isStream,
  isURLSearchParams: isURLSearchParams,
  isStandardBrowserEnv: isStandardBrowserEnv,
  forEach: forEach,
  merge: merge,
  extend: extend,
  trim: trim,
  stripBOM: stripBOM
};


/***/ }),

/***/ "./assets-src/js/Core.js":
/*!*******************************!*\
  !*** ./assets-src/js/Core.js ***!
  \*******************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _classes_AdminNoticeManager__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./classes/AdminNoticeManager */ "./assets-src/js/classes/AdminNoticeManager.js");
/* harmony import */ var _classes_ConversionStatsManager__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./classes/ConversionStatsManager */ "./assets-src/js/classes/ConversionStatsManager.js");
/* harmony import */ var _classes_DataToggleTrigger__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./classes/DataToggleTrigger */ "./assets-src/js/classes/DataToggleTrigger.js");
/* harmony import */ var _classes_ImageConversionManager__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./classes/ImageConversionManager */ "./assets-src/js/classes/ImageConversionManager.js");
/* harmony import */ var _classes_ImagesStatsFetcher__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./classes/ImagesStatsFetcher */ "./assets-src/js/classes/ImagesStatsFetcher.js");
/* harmony import */ var _classes_ImagesTreeGenerator__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./classes/ImagesTreeGenerator */ "./assets-src/js/classes/ImagesTreeGenerator.js");
/* harmony import */ var _classes_PlansButtonGenerator__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./classes/PlansButtonGenerator */ "./assets-src/js/classes/PlansButtonGenerator.js");
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }









var Core = function Core() {
  _classCallCheck(this, Core);

  var conversion_stats_manager = new _classes_ConversionStatsManager__WEBPACK_IMPORTED_MODULE_1__["default"]();
  var images_tree_generator = new _classes_ImagesTreeGenerator__WEBPACK_IMPORTED_MODULE_5__["default"]();
  var plans_button_generator = new _classes_PlansButtonGenerator__WEBPACK_IMPORTED_MODULE_6__["default"]();
  new _classes_AdminNoticeManager__WEBPACK_IMPORTED_MODULE_0__["default"]();
  new _classes_ImageConversionManager__WEBPACK_IMPORTED_MODULE_3__["default"](conversion_stats_manager);
  new _classes_ImagesStatsFetcher__WEBPACK_IMPORTED_MODULE_4__["default"](conversion_stats_manager, images_tree_generator, plans_button_generator);
  new _classes_DataToggleTrigger__WEBPACK_IMPORTED_MODULE_2__["default"]();
};

new Core();

/***/ }),

/***/ "./assets-src/js/classes/AdminNoticeManager.js":
/*!*****************************************************!*\
  !*** ./assets-src/js/classes/AdminNoticeManager.js ***!
  \*****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ AdminNoticeManager; }
/* harmony export */ });
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_0__);
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }



var AdminNoticeManagerCore = /*#__PURE__*/function () {
  function AdminNoticeManagerCore(notice) {
    _classCallCheck(this, AdminNoticeManagerCore);

    this.notice = notice;

    if (!this.set_vars()) {
      return;
    }

    this.set_events();
  }

  _createClass(AdminNoticeManagerCore, [{
    key: "set_vars",
    value: function set_vars() {
      this.settings = {
        ajax_action: this.notice.getAttribute('data-notice-action'),
        ajax_url: this.notice.getAttribute('data-notice-url'),
        button_close_class: '.notice-dismiss',
        button_hide_class: '[data-permanently]'
      };
      this.events = {
        click_on_close: this.click_on_close.bind(this)
      };
      return true;
    }
  }, {
    key: "set_events",
    value: function set_events() {
      this.notice.addEventListener('click', this.events.click_on_close);
    }
  }, {
    key: "click_on_close",
    value: function click_on_close(e) {
      var _this$settings = this.settings,
          button_close_class = _this$settings.button_close_class,
          button_hide_class = _this$settings.button_hide_class;

      if (e.target.matches(button_close_class)) {
        this.notice.removeEventListener('click', this.events.click_on_close);
        this.hide_notice(false);
      } else if (e.target.matches(button_hide_class)) {
        this.notice.removeEventListener('click', this.events.click_on_close);
        this.hide_notice(true);
      }
    }
  }, {
    key: "hide_notice",
    value: function hide_notice(is_permanently) {
      var button_close_class = this.settings.button_close_class;
      this.send_request(is_permanently);

      if (is_permanently) {
        this.notice.querySelector(button_close_class).click();
      }
    }
  }, {
    key: "send_request",
    value: function send_request(is_permanently) {
      var ajax_url = this.settings.ajax_url;
      axios__WEBPACK_IMPORTED_MODULE_0___default()({
        method: 'POST',
        url: ajax_url,
        data: this.get_data_for_request(is_permanently)
      });
    }
  }, {
    key: "get_data_for_request",
    value: function get_data_for_request(is_permanently) {
      var ajax_action = this.settings.ajax_action;
      var form_data = new FormData();
      form_data.append('action', ajax_action);
      form_data.append('is_permanently', is_permanently ? 1 : 0);
      return form_data;
    }
  }]);

  return AdminNoticeManagerCore;
}();

var AdminNoticeManager = function AdminNoticeManager() {
  _classCallCheck(this, AdminNoticeManager);

  var notices = document.querySelectorAll('.notice[data-notice="webp-converter-for-media"][data-notice-action]');
  var length = notices.length;

  for (var i = 0; i < length; i++) {
    new AdminNoticeManagerCore(notices[i]);
  }
};



/***/ }),

/***/ "./assets-src/js/classes/ConversionStatsManager.js":
/*!*********************************************************!*\
  !*** ./assets-src/js/classes/ConversionStatsManager.js ***!
  \*********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ ConversionStatsManager; }
/* harmony export */ });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var ConversionStatsManager = /*#__PURE__*/function () {
  function ConversionStatsManager() {
    _classCallCheck(this, ConversionStatsManager);

    this.status = this.set_vars();
  }

  _createClass(ConversionStatsManager, [{
    key: "set_vars",
    value: function set_vars() {
      this.counter_webp = document.querySelector('[data-counter="webp"]');
      this.counter_avif = document.querySelector('[data-counter="avif"]');

      if (!this.counter_webp || !this.counter_avif) {
        return;
      }

      this.counter_webp_percent = this.counter_webp.querySelector('[data-counter-percent]');
      this.counter_webp_images = this.counter_webp.querySelector('[data-counter-left]');
      this.counter_webp_loader = this.counter_webp.querySelector('[data-counter-loader]');
      this.counter_avif_percent = this.counter_avif.querySelector('[data-counter-percent]');
      this.counter_avif_images = this.counter_avif.querySelector('[data-counter-left]');
      this.counter_avif_loader = this.counter_avif.querySelector('[data-counter-loader]');
      this.data = {
        webp_converted: 0,
        webp_unconverted: 0,
        webp_all: 0,
        avif_converted: 0,
        avif_unconverted: 0,
        avif_all: 0
      };
      this.atts = {
        counter_percent: 'data-percent'
      };
      return true;
    }
  }, {
    key: "set_files_webp",
    value: function set_files_webp(count_converted, count_all) {
      if (!this.status) {
        return;
      }

      this.data.webp_converted += count_converted;
      this.data.webp_unconverted = count_all - count_converted;
      this.data.webp_all = count_all || this.data.webp_all;
      this.refresh_stats();
    }
  }, {
    key: "reset_files_webp",
    value: function reset_files_webp() {
      if (!this.status) {
        return;
      }

      this.data.webp_converted = 0;
      this.data.webp_unconverted = this.data.webp_all;
      this.refresh_stats();
    }
  }, {
    key: "set_files_avif",
    value: function set_files_avif(count_converted, count_all) {
      if (!this.status) {
        return;
      }

      this.data.avif_converted += count_converted;
      this.data.avif_unconverted = count_all - count_converted;
      this.data.avif_all = count_all;
      this.refresh_stats();
    }
  }, {
    key: "set_error",
    value: function set_error() {
      this.counter_webp_loader.setAttribute('hidden', 'hidden');
      this.counter_avif_loader.setAttribute('hidden', 'hidden');
    }
  }, {
    key: "reset_files_avif",
    value: function reset_files_avif() {
      if (!this.status) {
        return;
      }

      this.data.avif_converted = 0;
      this.data.avif_unconverted = this.data.avif_all;
      this.refresh_stats();
    }
  }, {
    key: "add_files_webp",
    value: function add_files_webp(count_successful) {
      if (!this.status) {
        return;
      }

      this.data.webp_converted += count_successful;
      this.data.webp_unconverted -= count_successful;
      this.refresh_stats();
    }
  }, {
    key: "add_files_avif",
    value: function add_files_avif(count_successful) {
      if (!this.status) {
        return;
      }

      this.data.avif_converted += count_successful;
      this.data.avif_unconverted -= count_successful;
      this.refresh_stats();
    }
  }, {
    key: "refresh_stats",
    value: function refresh_stats() {
      var _this$data = this.data,
          webp_converted = _this$data.webp_converted,
          webp_unconverted = _this$data.webp_unconverted,
          webp_all = _this$data.webp_all,
          avif_converted = _this$data.avif_converted,
          avif_unconverted = _this$data.avif_unconverted,
          avif_all = _this$data.avif_all;
      var counter_percent = this.atts.counter_percent;
      var percent_webp = webp_all > 0 ? Math.floor(webp_converted / webp_all * 100) : 0;
      var percent_avif = avif_all > 0 ? Math.floor(avif_converted / avif_all * 100) : 0;
      this.counter_webp.setAttribute(counter_percent, percent_webp);
      this.counter_webp_percent.innerText = percent_webp;
      this.counter_webp_images.innerText = Math.max(webp_unconverted, 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
      this.counter_avif.setAttribute(counter_percent, percent_avif);
      this.counter_avif_percent.innerText = percent_avif;
      this.counter_avif_images.innerText = Math.max(avif_unconverted, 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
  }]);

  return ConversionStatsManager;
}();



/***/ }),

/***/ "./assets-src/js/classes/DataToggleTrigger.js":
/*!****************************************************!*\
  !*** ./assets-src/js/classes/DataToggleTrigger.js ***!
  \****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ DataToggleTrigger; }
/* harmony export */ });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var DataToggleTrigger = /*#__PURE__*/function () {
  function DataToggleTrigger() {
    _classCallCheck(this, DataToggleTrigger);

    if (!this.set_vars()) {
      return;
    }

    this.set_events();
  }

  _createClass(DataToggleTrigger, [{
    key: "set_vars",
    value: function set_vars() {
      this.triggers = document.querySelectorAll('[data-toggle-trigger]');

      if (!this.triggers.length) {
        return false;
      }

      this.outputs = document.querySelectorAll('[data-toggle-output-values]');
      return true;
    }
  }, {
    key: "set_events",
    value: function set_events() {
      var length = this.triggers.length;

      for (var i = 0; i < length; i++) {
        this.triggers[i].addEventListener('change', this.toggle_output.bind(this));
      }
    }
  }, {
    key: "toggle_output",
    value: function toggle_output(e) {
      var field_name = e.currentTarget.getAttribute('name');
      var field_type = e.currentTarget.getAttribute('type');
      var field_values = field_type === 'checkbox' ? this.get_checkbox_value(field_name) : this.get_radio_value(field_name);
      var length = this.outputs.length;

      for (var i = 0; i < length; i++) {
        if (this.outputs[i].getAttribute('data-toggle-output') === field_name) {
          var output_attr = this.outputs[i].getAttribute('data-toggle-output-attr');

          if (this.validate_output(this.outputs[i], field_values)) {
            this.outputs[i].removeAttribute(output_attr);
          } else {
            this.outputs[i].setAttribute(output_attr, output_attr);
          }
        }
      }
    }
  }, {
    key: "get_checkbox_value",
    value: function get_checkbox_value(field_name) {
      var values = [];
      var length = this.triggers.length;

      for (var i = 0; i < length; i++) {
        if (this.triggers[i].getAttribute('name') === field_name && this.triggers[i].checked) {
          values.push(this.triggers[i].getAttribute('value'));
        }
      }

      return values;
    }
  }, {
    key: "get_radio_value",
    value: function get_radio_value(field_name) {
      var length = this.triggers.length;

      for (var i = 0; i < length; i++) {
        if (this.triggers[i].getAttribute('name') === field_name && this.triggers[i].checked) {
          return [this.triggers[i].getAttribute('value')];
        }
      }

      return [];
    }
  }, {
    key: "validate_output",
    value: function validate_output(output, field_values) {
      var available_values = output.getAttribute('data-toggle-output-values').split(';');
      var length = field_values.length;

      for (var i = 0; i < length; i++) {
        if (available_values.indexOf(field_values[i]) >= 0) {
          return true;
        }
      }

      return false;
    }
  }]);

  return DataToggleTrigger;
}();



/***/ }),

/***/ "./assets-src/js/classes/ImageConversionManager.js":
/*!*********************************************************!*\
  !*** ./assets-src/js/classes/ImageConversionManager.js ***!
  \*********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ ImageConversionManager; }
/* harmony export */ });
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_0__);
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }



var ImageConversionManager = /*#__PURE__*/function () {
  /**
   * @param {ConversionStatsManager} conversion_stats_manager
   */
  function ImageConversionManager(conversion_stats_manager) {
    _classCallCheck(this, ImageConversionManager);

    this.conversion_stats_manager = conversion_stats_manager;

    if (!this.set_vars()) {
      return;
    }

    this.set_events();
  }

  _createClass(ImageConversionManager, [{
    key: "set_vars",
    value: function set_vars() {
      this.section = document.querySelector('.webpcLoader');

      if (!this.section) {
        return;
      }

      this.wrapper_status = this.section.querySelector('[data-status]');

      if (!this.wrapper_status) {
        return;
      }

      this.progress = this.wrapper_status.querySelector('[data-status-progress]');
      this.progress_size = this.section.querySelector('[data-status-count-size]');
      this.progress_success = this.section.querySelector('[data-status-count-success]');
      this.progress_failed = this.section.querySelector('[data-status-count-error]');
      this.wrapper_errors = this.section.querySelector('[data-errors]');
      this.errors_output = this.wrapper_errors.querySelector('[data-errors-output]');
      this.wrapper_success = this.section.querySelector('[data-success]');
      this.option_force = this.section.querySelector('input[name="regenerate_force"]');
      this.submit_button = this.section.querySelector('[data-submit]');
      this.data = {
        count: 0,
        max: 0,
        items: [],
        size: {
          before: 0,
          after: 0
        },
        files_counter: {
          all: 0,
          converted: 0
        },
        errors: 0
      };
      this.settings = {
        is_disabled: false,
        ajax: {
          url_paths: this.section.getAttribute('data-api-paths').split('|')[0],
          url_paths_nonce: this.section.getAttribute('data-api-paths').split('|')[1],
          url_regenerate: this.section.getAttribute('data-api-regenerate').split('|')[0],
          url_regenerate_nonce: this.section.getAttribute('data-api-regenerate').split('|')[1],
          error_message: this.section.getAttribute('data-api-error-message')
        },
        units: ['kB', 'MB', 'GB'],
        max_errors: 1000,
        connection_timeout: 60000
      };
      this.atts = {
        progress: 'data-percent',
        counter_percent: 'data-percent'
      };
      this.classes = {
        progress_error: 'webpcLoader__statusProgress--error',
        button_disabled: 'webpcLoader__button--disabled',
        error_message: 'webpcLoader__errorsContentError'
      };
      return true;
    }
  }, {
    key: "set_events",
    value: function set_events() {
      this.submit_button.addEventListener('click', this.init_regeneration.bind(this));
    }
  }, {
    key: "init_regeneration",
    value: function init_regeneration(e) {
      e.preventDefault();

      if (this.settings.is_disabled) {
        return;
      }

      this.settings.is_disabled = true;
      this.submit_button.classList.add(this.classes.button_disabled);
      this.option_force.setAttribute('disabled', 'disabled');
      this.wrapper_status.removeAttribute('hidden');
      this.send_request_for_paths();
    }
  }, {
    key: "send_request_for_paths",
    value: function send_request_for_paths() {
      var _this = this;

      var _this$settings$ajax = this.settings.ajax,
          url_paths = _this$settings$ajax.url_paths,
          url_paths_nonce = _this$settings$ajax.url_paths_nonce;
      axios__WEBPACK_IMPORTED_MODULE_0___default()({
        method: 'POST',
        url: url_paths,
        data: {
          regenerate_force: this.option_force.checked ? 1 : 0
        },
        headers: {
          'X-WP-Nonce': url_paths_nonce
        }
      }).then(function (response) {
        var paths = _this.parse_files_paths(response.data);

        _this.data.items = paths;
        _this.data.max = paths.length;

        if (_this.option_force.checked) {
          _this.conversion_stats_manager.reset_files_webp();

          _this.conversion_stats_manager.reset_files_avif();
        }

        _this.regenerate_next_images();
      }).catch(function (error) {
        console.warn(error);

        _this.catch_request_error(error, true);
      });
    }
  }, {
    key: "parse_files_paths",
    value: function parse_files_paths(response_data) {
      var paths = [];

      for (var directory in response_data) {
        var length = response_data[directory].files.length;

        for (var i = 0; i < length; i++) {
          var part_paths = [];

          for (var j = 0; j < response_data[directory].files[i].length; j++) {
            part_paths.push(response_data[directory].path + '/' + response_data[directory].files[i][j]);
          }

          paths.push(part_paths);
        }
      }

      return paths;
    }
  }, {
    key: "regenerate_next_images",
    value: function regenerate_next_images() {
      var attempt = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;

      if (this.data.max === 0) {
        this.update_progress();
      }

      if (this.data.count >= this.data.max) {
        return;
      }

      if (attempt >= 3) {
        attempt = 0;
      } else if (attempt > 0) {
        this.data.count--;
      }

      var items = this.data.items[this.data.count];
      this.data.count++;
      this.send_request_for_regeneration(items, attempt);
    }
  }, {
    key: "send_request_for_regeneration",
    value: function send_request_for_regeneration(items, attempt) {
      var _this2 = this;

      var _this$settings$ajax2 = this.settings.ajax,
          url_regenerate = _this$settings$ajax2.url_regenerate,
          url_regenerate_nonce = _this$settings$ajax2.url_regenerate_nonce;
      axios__WEBPACK_IMPORTED_MODULE_0___default()({
        method: 'POST',
        url: url_regenerate,
        data: {
          regenerate_force: this.option_force.checked ? 1 : 0,
          paths: items
        },
        headers: {
          'X-WP-Nonce': url_regenerate_nonce
        },
        timeout: this.settings.connection_timeout
      }).then(function (response) {
        var is_fatal_error = response.data.is_fatal_error;

        _this2.update_errors(response.data.errors, is_fatal_error);

        if (!is_fatal_error) {
          _this2.update_size(response.data);

          _this2.update_files_count(response.data);

          _this2.update_progress();

          _this2.regenerate_next_images();
        }
      }).catch(function (error) {
        console.warn(error);

        if (error.response) {
          _this2.catch_request_error(error, false, items);

          setTimeout(_this2.regenerate_next_images.bind(_this2), 1000);
        } else {
          setTimeout(_this2.regenerate_next_images.bind(_this2, attempt + 1), 1000);
        }
      });
    }
  }, {
    key: "update_errors",
    value: function update_errors(errors) {
      var is_fatal_error = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

      if (this.data.errors > this.settings.max_errors) {
        this.data.errors = 0;
        this.errors_output.innerHTML = '';
      }

      var current_date = this.get_date();

      for (var i = 0; i < errors.length; i++) {
        this.print_error_message(errors[i], is_fatal_error, false, current_date);
        this.data.errors++;
      }

      if (is_fatal_error) {
        this.set_fatal_error();
      }
    }
  }, {
    key: "get_date",
    value: function get_date() {
      var current_date = new Date();
      var hour = ('0' + current_date.getHours()).substr(-2);
      var minute = ('0' + current_date.getMinutes()).substr(-2);
      var second = ('0' + current_date.getSeconds()).substr(-2);
      return "".concat(hour, ":").concat(minute, ":").concat(second);
    }
  }, {
    key: "set_fatal_error",
    value: function set_fatal_error() {
      this.progress.classList.add(this.classes.progress_error);
    }
  }, {
    key: "catch_request_error",
    value: function catch_request_error(error, is_fatal_error) {
      var params = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : null;

      if (is_fatal_error) {
        this.print_error_message([this.settings.ajax.error_message], true, false);
        this.set_fatal_error();
      }

      var params_value = params !== null ? "[\"".concat(params.join('", "'), "\"]") : '';
      this.print_error_message("".concat(error.response.status, " - ").concat(error.response.statusText, " (").concat(error.response.config.url, ") ").concat(params_value), true, true);
    }
  }, {
    key: "print_error_message",
    value: function print_error_message(error_message, is_fatal_error, has_pre_wrapper) {
      var current_date = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : null;
      var element = document.createElement('p');
      var date_prefix = current_date ? current_date : this.get_date();

      if (has_pre_wrapper) {
        var pre_element = document.createElement('pre');
        pre_element.innerText = error_message;
        element.appendChild(pre_element);
      } else {
        element.innerHTML = "<strong>".concat(date_prefix, "</strong> - ").concat(error_message);
      }

      if (is_fatal_error) {
        element.classList.add(this.classes.error_message);
      }

      this.wrapper_errors.removeAttribute('hidden');
      this.errors_output.appendChild(element);
    }
  }, {
    key: "update_size",
    value: function update_size(response_data) {
      var size = this.data.size;
      size.before += response_data.size.before;
      size.after += response_data.size.after;
      var bytes = size.before - size.after;

      if (bytes < 0) {
        bytes = 0;
      }

      if (bytes === 0) {
        return;
      }

      var percent = Math.round((1 - size.after / size.before) * 100);

      if (percent < 0) {
        percent = 0;
      }

      var index = -1;

      do {
        index++;
        bytes /= 1024;
      } while (bytes > 1024);

      var number = bytes.toFixed(2);
      var unit = this.settings.units[index];
      this.progress_size.innerHTML = "".concat(number, " ").concat(unit, " (").concat(percent, "%)");
    }
  }, {
    key: "update_files_count",
    value: function update_files_count(response_data) {
      var files_counter = this.data.files_counter;
      var files = response_data.files;
      this.conversion_stats_manager.add_files_webp(files.webp_available);
      this.conversion_stats_manager.add_files_avif(files.avif_available);
      files_counter.converted += files.webp_converted + files.avif_converted;
      files_counter.all += files.webp_available + files.avif_available;
      this.progress_success.innerText = files_counter.converted.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
      this.progress_failed.innerText = (files_counter.all - files_counter.converted).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }
  }, {
    key: "update_progress",
    value: function update_progress() {
      var percent = this.data.max > 0 ? Math.floor(this.data.count / this.data.max * 100) : 100;

      if (percent > 100) {
        percent = 100;
      }

      if (percent === 100) {
        this.wrapper_success.removeAttribute('hidden');
      }

      this.progress.setAttribute(this.atts.progress, percent.toString());
    }
  }]);

  return ImageConversionManager;
}();



/***/ }),

/***/ "./assets-src/js/classes/ImagesStatsFetcher.js":
/*!*****************************************************!*\
  !*** ./assets-src/js/classes/ImagesStatsFetcher.js ***!
  \*****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ ImagesStatsFetcher; }
/* harmony export */ });
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! axios */ "./node_modules/axios/index.js");
/* harmony import */ var axios__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(axios__WEBPACK_IMPORTED_MODULE_0__);
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }



var ImagesStatsFetcher = /*#__PURE__*/function () {
  /**
   * @param {ConversionStatsManager} conversion_stats_manager
   * @param {ImagesTreeGenerator} images_tree_generator
   * @param {PlansButtonGenerator} plans_button_generator
   */
  function ImagesStatsFetcher(conversion_stats_manager, images_tree_generator, plans_button_generator) {
    _classCallCheck(this, ImagesStatsFetcher);

    this.conversion_stats_manager = conversion_stats_manager;
    this.images_tree_generator = images_tree_generator;
    this.plans_button_generator = plans_button_generator;

    if (!this.set_vars()) {
      return;
    }

    this.send_request();
  }

  _createClass(ImagesStatsFetcher, [{
    key: "set_vars",
    value: function set_vars() {
      this.section = document.querySelector('[data-api-stats]');

      if (!this.section) {
        return;
      }

      this.error_output = this.section.querySelector('[data-api-stats-error]');
      this.settings = {
        ajax_url: this.section.getAttribute('data-api-stats').split('|')[0],
        ajax_nonce: this.section.getAttribute('data-api-stats').split('|')[1]
      };
      return true;
    }
  }, {
    key: "send_request",
    value: function send_request() {
      var _this = this;

      var attempt = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 0;

      if (attempt >= 3) {
        this.conversion_stats_manager.set_error();
        this.images_tree_generator.set_error();
        this.plans_button_generator.set_error();
        return;
      }

      var start_time = new Date();
      axios__WEBPACK_IMPORTED_MODULE_0___default()({
        method: 'GET',
        url: this.settings.ajax_url,
        headers: {
          'X-WP-Nonce': this.settings.ajax_nonce
        }
      }).then(function (response) {
        if (response.data) {
          var webp_all = response.data.value_webp_all || 0;
          var webp_converted = response.data.value_webp_converted || 0;
          var avif_all = response.data.value_avif_all || 0;
          var avif_converted = response.data.value_avif_converted || 0;

          _this.conversion_stats_manager.set_files_webp(webp_converted, webp_all);

          _this.conversion_stats_manager.set_files_avif(avif_converted, avif_all);

          _this.images_tree_generator.generate_tree(response.data.tree);

          _this.plans_button_generator.show_button(webp_all - webp_converted, avif_all - avif_converted);
        } else {
          _this.send_request(attempt + 1);

          _this.show_request_error(start_time, response);
        }
      }).catch(function (error) {
        console.warn(error);

        _this.send_request(attempt + 1);

        if (error.response) {
          _this.show_request_error(start_time, error.response);
        }
      });
    }
  }, {
    key: "show_request_error",
    value: function show_request_error(start_time, response) {
      var request_time = (new Date() - start_time) / 1000;
      var request_status = response.status;
      var request_data = JSON.stringify(response.data);
      this.error_output.innerText = "HTTP Error ".concat(request_status, " (").concat(request_time, "s): ").concat(request_data);
      this.error_output.removeAttribute('hidden');
    }
  }]);

  return ImagesStatsFetcher;
}();



/***/ }),

/***/ "./assets-src/js/classes/ImagesTreeGenerator.js":
/*!******************************************************!*\
  !*** ./assets-src/js/classes/ImagesTreeGenerator.js ***!
  \******************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ ImagesTreeGenerator; }
/* harmony export */ });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var ImagesTreeGenerator = /*#__PURE__*/function () {
  function ImagesTreeGenerator() {
    _classCallCheck(this, ImagesTreeGenerator);

    this.status = this.set_vars();
  }

  _createClass(ImagesTreeGenerator, [{
    key: "set_vars",
    value: function set_vars() {
      this.section = document.querySelector('[data-tree]');

      if (!this.section) {
        return;
      }

      this.loader = this.section.querySelector('[data-tree-loader]');
      return true;
    }
  }, {
    key: "generate_tree",
    value: function generate_tree(response_data) {
      if (!this.status) {
        return;
      }

      this.loader = null;
      this.section.innerHTML = this.draw_tree(response_data);
      var inputs = this.section.querySelectorAll('.webpcTree__itemCheckbox');

      for (var i = 0; i < inputs.length; i++) {
        inputs[i].addEventListener('change', function (e) {
          if (!e.currentTarget.checked) {
            var _inputs = e.currentTarget.parentNode.querySelectorAll('.webpcTree__itemCheckbox');

            for (var _i = 0; _i < _inputs.length; _i++) {
              _inputs[_i].checked = false;
            }
          }
        });
      }
    }
  }, {
    key: "set_error",
    value: function set_error() {
      if (this.loader) {
        this.loader.setAttribute('hidden', 'hidden');
      }
    }
  }, {
    key: "draw_tree",
    value: function draw_tree(values) {
      var level = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
      var id = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'tree';
      var content = '';
      var length = values.length;

      if (!length) {
        return content;
      }

      if (level === 0) {
        content += '<ul class="webpcTree__items">';
      }

      for (var i = 0; i < length; i++) {
        var item_id = "".concat(id, "-").concat(values[i].name).replace(/\s/g, '');
        content += '<li class="webpcTree__item">';
        content += "<input type=\"checkbox\" id=\"".concat(item_id, "\" class=\"webpcTree__itemCheckbox\">");
        content += "<label for=\"".concat(item_id, "\" class=\"webpcTree__itemLabel\">");
        content += "".concat(values[i].name, " <strong>(").concat(values[i].count.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' '), ")</strong>");
        content += '</label>';
        content += '<ul class="webpcTree__items">';

        if (values[i].items) {
          content += this.draw_tree(values[i].items, level + 1, item_id);
        }

        for (var j = 0; j < values[i].files.length; j++) {
          content += '<li class="webpcTree__item">';
          content += "<span class=\"webpcTree__itemName\">".concat(values[i].files[j], "</span>");
          content += '</li>';
        }

        content += '</ul>';
        content += '</li>';
      }

      if (level === 0) {
        content += '</ul>';
      }

      return content;
    }
  }]);

  return ImagesTreeGenerator;
}();



/***/ }),

/***/ "./assets-src/js/classes/PlansButtonGenerator.js":
/*!*******************************************************!*\
  !*** ./assets-src/js/classes/PlansButtonGenerator.js ***!
  \*******************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": function() { return /* binding */ PlansButtonGenerator; }
/* harmony export */ });
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var PlansButtonGenerator = /*#__PURE__*/function () {
  function PlansButtonGenerator() {
    _classCallCheck(this, PlansButtonGenerator);

    this.status = this.set_vars();
  }

  _createClass(PlansButtonGenerator, [{
    key: "set_vars",
    value: function set_vars() {
      this.section = document.querySelector('[data-plans]');

      if (!this.section) {
        return;
      }

      this.button = this.section.querySelector('[data-plans-button]');
      this.loader = this.section.querySelector('[data-plans-loader]');
      this.settings = {
        button_url: this.button.getAttribute('href')
      };
      return true;
    }
  }, {
    key: "show_button",
    value: function show_button(webp_count, avif_count) {
      if (!this.status) {
        return;
      }

      var url = this.settings.button_url.replace('webp=0', "webp=".concat(webp_count)).replace('avif=0', "avif=".concat(avif_count));
      this.button.setAttribute('href', url);
      this.button.removeAttribute('hidden');
      this.loader.setAttribute('hidden', 'hidden');
    }
  }, {
    key: "set_error",
    value: function set_error() {
      this.button.removeAttribute('hidden');
      this.loader.setAttribute('hidden', 'hidden');
    }
  }]);

  return PlansButtonGenerator;
}();



/***/ }),

/***/ "./assets-src/scss/Core.scss":
/*!***********************************!*\
  !*** ./assets-src/scss/Core.scss ***!
  \***********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./node_modules/process/browser.js":
/*!*****************************************!*\
  !*** ./node_modules/process/browser.js ***!
  \*****************************************/
/***/ (function(module) {

// shim for using process in browser
var process = module.exports = {};

// cached from whatever global is present so that test runners that stub it
// don't break things.  But we need to wrap it in a try catch in case it is
// wrapped in strict mode code which doesn't define any globals.  It's inside a
// function because try/catches deoptimize in certain engines.

var cachedSetTimeout;
var cachedClearTimeout;

function defaultSetTimout() {
    throw new Error('setTimeout has not been defined');
}
function defaultClearTimeout () {
    throw new Error('clearTimeout has not been defined');
}
(function () {
    try {
        if (typeof setTimeout === 'function') {
            cachedSetTimeout = setTimeout;
        } else {
            cachedSetTimeout = defaultSetTimout;
        }
    } catch (e) {
        cachedSetTimeout = defaultSetTimout;
    }
    try {
        if (typeof clearTimeout === 'function') {
            cachedClearTimeout = clearTimeout;
        } else {
            cachedClearTimeout = defaultClearTimeout;
        }
    } catch (e) {
        cachedClearTimeout = defaultClearTimeout;
    }
} ())
function runTimeout(fun) {
    if (cachedSetTimeout === setTimeout) {
        //normal enviroments in sane situations
        return setTimeout(fun, 0);
    }
    // if setTimeout wasn't available but was latter defined
    if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
        cachedSetTimeout = setTimeout;
        return setTimeout(fun, 0);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedSetTimeout(fun, 0);
    } catch(e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't trust the global object when called normally
            return cachedSetTimeout.call(null, fun, 0);
        } catch(e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error
            return cachedSetTimeout.call(this, fun, 0);
        }
    }


}
function runClearTimeout(marker) {
    if (cachedClearTimeout === clearTimeout) {
        //normal enviroments in sane situations
        return clearTimeout(marker);
    }
    // if clearTimeout wasn't available but was latter defined
    if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
        cachedClearTimeout = clearTimeout;
        return clearTimeout(marker);
    }
    try {
        // when when somebody has screwed with setTimeout but no I.E. maddness
        return cachedClearTimeout(marker);
    } catch (e){
        try {
            // When we are in I.E. but the script has been evaled so I.E. doesn't  trust the global object when called normally
            return cachedClearTimeout.call(null, marker);
        } catch (e){
            // same as above but when it's a version of I.E. that must have the global object for 'this', hopfully our context correct otherwise it will throw a global error.
            // Some versions of I.E. have different rules for clearTimeout vs setTimeout
            return cachedClearTimeout.call(this, marker);
        }
    }



}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;

function cleanUpNextTick() {
    if (!draining || !currentQueue) {
        return;
    }
    draining = false;
    if (currentQueue.length) {
        queue = currentQueue.concat(queue);
    } else {
        queueIndex = -1;
    }
    if (queue.length) {
        drainQueue();
    }
}

function drainQueue() {
    if (draining) {
        return;
    }
    var timeout = runTimeout(cleanUpNextTick);
    draining = true;

    var len = queue.length;
    while(len) {
        currentQueue = queue;
        queue = [];
        while (++queueIndex < len) {
            if (currentQueue) {
                currentQueue[queueIndex].run();
            }
        }
        queueIndex = -1;
        len = queue.length;
    }
    currentQueue = null;
    draining = false;
    runClearTimeout(timeout);
}

process.nextTick = function (fun) {
    var args = new Array(arguments.length - 1);
    if (arguments.length > 1) {
        for (var i = 1; i < arguments.length; i++) {
            args[i - 1] = arguments[i];
        }
    }
    queue.push(new Item(fun, args));
    if (queue.length === 1 && !draining) {
        runTimeout(drainQueue);
    }
};

// v8 likes predictible objects
function Item(fun, array) {
    this.fun = fun;
    this.array = array;
}
Item.prototype.run = function () {
    this.fun.apply(null, this.array);
};
process.title = 'browser';
process.browser = true;
process.env = {};
process.argv = [];
process.version = ''; // empty string to avoid regexp issues
process.versions = {};

function noop() {}

process.on = noop;
process.addListener = noop;
process.once = noop;
process.off = noop;
process.removeListener = noop;
process.removeAllListeners = noop;
process.emit = noop;
process.prependListener = noop;
process.prependOnceListener = noop;

process.listeners = function (name) { return [] }

process.binding = function (name) {
    throw new Error('process.binding is not supported');
};

process.cwd = function () { return '/' };
process.chdir = function (dir) {
    throw new Error('process.chdir is not supported');
};
process.umask = function() { return 0; };


/***/ }),

/***/ "./node_modules/axios/package.json":
/*!*****************************************!*\
  !*** ./node_modules/axios/package.json ***!
  \*****************************************/
/***/ (function(module) {

"use strict";
module.exports = JSON.parse('{"name":"axios","version":"0.21.4","description":"Promise based HTTP client for the browser and node.js","main":"index.js","scripts":{"test":"grunt test","start":"node ./sandbox/server.js","build":"NODE_ENV=production grunt build","preversion":"npm test","version":"npm run build && grunt version && git add -A dist && git add CHANGELOG.md bower.json package.json","postversion":"git push && git push --tags","examples":"node ./examples/server.js","coveralls":"cat coverage/lcov.info | ./node_modules/coveralls/bin/coveralls.js","fix":"eslint --fix lib/**/*.js"},"repository":{"type":"git","url":"https://github.com/axios/axios.git"},"keywords":["xhr","http","ajax","promise","node"],"author":"Matt Zabriskie","license":"MIT","bugs":{"url":"https://github.com/axios/axios/issues"},"homepage":"https://axios-http.com","devDependencies":{"coveralls":"^3.0.0","es6-promise":"^4.2.4","grunt":"^1.3.0","grunt-banner":"^0.6.0","grunt-cli":"^1.2.0","grunt-contrib-clean":"^1.1.0","grunt-contrib-watch":"^1.0.0","grunt-eslint":"^23.0.0","grunt-karma":"^4.0.0","grunt-mocha-test":"^0.13.3","grunt-ts":"^6.0.0-beta.19","grunt-webpack":"^4.0.2","istanbul-instrumenter-loader":"^1.0.0","jasmine-core":"^2.4.1","karma":"^6.3.2","karma-chrome-launcher":"^3.1.0","karma-firefox-launcher":"^2.1.0","karma-jasmine":"^1.1.1","karma-jasmine-ajax":"^0.1.13","karma-safari-launcher":"^1.0.0","karma-sauce-launcher":"^4.3.6","karma-sinon":"^1.0.5","karma-sourcemap-loader":"^0.3.8","karma-webpack":"^4.0.2","load-grunt-tasks":"^3.5.2","minimist":"^1.2.0","mocha":"^8.2.1","sinon":"^4.5.0","terser-webpack-plugin":"^4.2.3","typescript":"^4.0.5","url-search-params":"^0.10.0","webpack":"^4.44.2","webpack-dev-server":"^3.11.0"},"browser":{"./lib/adapters/http.js":"./lib/adapters/xhr.js"},"jsdelivr":"dist/axios.min.js","unpkg":"dist/axios.min.js","typings":"./index.d.ts","dependencies":{"follow-redirects":"^1.14.0"},"bundlesize":[{"path":"./dist/axios.min.js","threshold":"5kB"}]}');

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	!function() {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = function(result, chunkIds, fn, priority) {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var chunkIds = deferred[i][0];
/******/ 				var fn = deferred[i][1];
/******/ 				var priority = deferred[i][2];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every(function(key) { return __webpack_require__.O[key](chunkIds[j]); })) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	!function() {
/******/ 		// no baseURI
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"/assets/build/js/scripts": 0,
/******/ 			"assets/build/css/styles": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = function(chunkId) { return installedChunks[chunkId] === 0; };
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = function(parentChunkLoadingFunction, data) {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some(function(id) { return installedChunks[id] !== 0; })) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkIds[i]] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunkwebp_converter_for_media"] = self["webpackChunkwebp_converter_for_media"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	__webpack_require__.O(undefined, ["assets/build/css/styles"], function() { return __webpack_require__("./assets-src/js/Core.js"); })
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["assets/build/css/styles"], function() { return __webpack_require__("./assets-src/scss/Core.scss"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7O1dBRUE7O1dBRUE7O1dBRUE7O1dBRUE7O1dBRUE7O1dBRUEsOENBQThDOztXQUU5QztXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0EsaUNBQWlDLG1DQUFtQztXQUNwRTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0EsTUFBTSxxQkFBcUI7V0FDM0I7V0FDQTtXQUNBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQTs7V0FFQTtXQUNBO1dBQ0E7Ozs7O1VFbkRBO1VBQ0E7VUFDQTtVQUNBLDJFQUEyRSx3REFBd0Q7VUFDbkkscUdBQXFHLDREQUE0RDtVQUNqSyIsInNvdXJjZXMiOlsid2VicGFjazovL3dlYnAtY29udmVydGVyLWZvci1tZWRpYS8uL25vZGVfbW9kdWxlcy9heGlvcy9pbmRleC5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2FkYXB0ZXJzL3hoci5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2F4aW9zLmpzIiwid2VicGFjazovL3dlYnAtY29udmVydGVyLWZvci1tZWRpYS8uL25vZGVfbW9kdWxlcy9heGlvcy9saWIvY2FuY2VsL0NhbmNlbC5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2NhbmNlbC9DYW5jZWxUb2tlbi5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2NhbmNlbC9pc0NhbmNlbC5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2NvcmUvQXhpb3MuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vbm9kZV9tb2R1bGVzL2F4aW9zL2xpYi9jb3JlL0ludGVyY2VwdG9yTWFuYWdlci5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2NvcmUvYnVpbGRGdWxsUGF0aC5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2NvcmUvY3JlYXRlRXJyb3IuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vbm9kZV9tb2R1bGVzL2F4aW9zL2xpYi9jb3JlL2Rpc3BhdGNoUmVxdWVzdC5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2NvcmUvZW5oYW5jZUVycm9yLmpzIiwid2VicGFjazovL3dlYnAtY29udmVydGVyLWZvci1tZWRpYS8uL25vZGVfbW9kdWxlcy9heGlvcy9saWIvY29yZS9tZXJnZUNvbmZpZy5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2NvcmUvc2V0dGxlLmpzIiwid2VicGFjazovL3dlYnAtY29udmVydGVyLWZvci1tZWRpYS8uL25vZGVfbW9kdWxlcy9heGlvcy9saWIvY29yZS90cmFuc2Zvcm1EYXRhLmpzIiwid2VicGFjazovL3dlYnAtY29udmVydGVyLWZvci1tZWRpYS8uL25vZGVfbW9kdWxlcy9heGlvcy9saWIvZGVmYXVsdHMuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vbm9kZV9tb2R1bGVzL2F4aW9zL2xpYi9oZWxwZXJzL2JpbmQuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vbm9kZV9tb2R1bGVzL2F4aW9zL2xpYi9oZWxwZXJzL2J1aWxkVVJMLmpzIiwid2VicGFjazovL3dlYnAtY29udmVydGVyLWZvci1tZWRpYS8uL25vZGVfbW9kdWxlcy9heGlvcy9saWIvaGVscGVycy9jb21iaW5lVVJMcy5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2hlbHBlcnMvY29va2llcy5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2hlbHBlcnMvaXNBYnNvbHV0ZVVSTC5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2hlbHBlcnMvaXNBeGlvc0Vycm9yLmpzIiwid2VicGFjazovL3dlYnAtY29udmVydGVyLWZvci1tZWRpYS8uL25vZGVfbW9kdWxlcy9heGlvcy9saWIvaGVscGVycy9pc1VSTFNhbWVPcmlnaW4uanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vbm9kZV9tb2R1bGVzL2F4aW9zL2xpYi9oZWxwZXJzL25vcm1hbGl6ZUhlYWRlck5hbWUuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vbm9kZV9tb2R1bGVzL2F4aW9zL2xpYi9oZWxwZXJzL3BhcnNlSGVhZGVycy5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvYXhpb3MvbGliL2hlbHBlcnMvc3ByZWFkLmpzIiwid2VicGFjazovL3dlYnAtY29udmVydGVyLWZvci1tZWRpYS8uL25vZGVfbW9kdWxlcy9heGlvcy9saWIvaGVscGVycy92YWxpZGF0b3IuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vbm9kZV9tb2R1bGVzL2F4aW9zL2xpYi91dGlscy5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9hc3NldHMtc3JjL2pzL0NvcmUuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vYXNzZXRzLXNyYy9qcy9jbGFzc2VzL0FkbWluTm90aWNlTWFuYWdlci5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9hc3NldHMtc3JjL2pzL2NsYXNzZXMvQ29udmVyc2lvblN0YXRzTWFuYWdlci5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9hc3NldHMtc3JjL2pzL2NsYXNzZXMvRGF0YVRvZ2dsZVRyaWdnZXIuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vYXNzZXRzLXNyYy9qcy9jbGFzc2VzL0ltYWdlQ29udmVyc2lvbk1hbmFnZXIuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vYXNzZXRzLXNyYy9qcy9jbGFzc2VzL0ltYWdlc1N0YXRzRmV0Y2hlci5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9hc3NldHMtc3JjL2pzL2NsYXNzZXMvSW1hZ2VzVHJlZUdlbmVyYXRvci5qcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9hc3NldHMtc3JjL2pzL2NsYXNzZXMvUGxhbnNCdXR0b25HZW5lcmF0b3IuanMiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhLy4vYXNzZXRzLXNyYy9zY3NzL0NvcmUuc2NzcyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvLi9ub2RlX21vZHVsZXMvcHJvY2Vzcy9icm93c2VyLmpzIiwid2VicGFjazovL3dlYnAtY29udmVydGVyLWZvci1tZWRpYS93ZWJwYWNrL2Jvb3RzdHJhcCIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvd2VicGFjay9ydW50aW1lL2NodW5rIGxvYWRlZCIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvd2VicGFjay9ydW50aW1lL2NvbXBhdCBnZXQgZGVmYXVsdCBleHBvcnQiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhL3dlYnBhY2svcnVudGltZS9kZWZpbmUgcHJvcGVydHkgZ2V0dGVycyIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvd2VicGFjay9ydW50aW1lL2hhc093blByb3BlcnR5IHNob3J0aGFuZCIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvd2VicGFjay9ydW50aW1lL21ha2UgbmFtZXNwYWNlIG9iamVjdCIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvd2VicGFjay9ydW50aW1lL2pzb25wIGNodW5rIGxvYWRpbmciLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhL3dlYnBhY2svYmVmb3JlLXN0YXJ0dXAiLCJ3ZWJwYWNrOi8vd2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhL3dlYnBhY2svc3RhcnR1cCIsIndlYnBhY2s6Ly93ZWJwLWNvbnZlcnRlci1mb3ItbWVkaWEvd2VicGFjay9hZnRlci1zdGFydHVwIl0sInNvdXJjZXNDb250ZW50IjpbIm1vZHVsZS5leHBvcnRzID0gcmVxdWlyZSgnLi9saWIvYXhpb3MnKTsiLCIndXNlIHN0cmljdCc7XG5cbnZhciB1dGlscyA9IHJlcXVpcmUoJy4vLi4vdXRpbHMnKTtcbnZhciBzZXR0bGUgPSByZXF1aXJlKCcuLy4uL2NvcmUvc2V0dGxlJyk7XG52YXIgY29va2llcyA9IHJlcXVpcmUoJy4vLi4vaGVscGVycy9jb29raWVzJyk7XG52YXIgYnVpbGRVUkwgPSByZXF1aXJlKCcuLy4uL2hlbHBlcnMvYnVpbGRVUkwnKTtcbnZhciBidWlsZEZ1bGxQYXRoID0gcmVxdWlyZSgnLi4vY29yZS9idWlsZEZ1bGxQYXRoJyk7XG52YXIgcGFyc2VIZWFkZXJzID0gcmVxdWlyZSgnLi8uLi9oZWxwZXJzL3BhcnNlSGVhZGVycycpO1xudmFyIGlzVVJMU2FtZU9yaWdpbiA9IHJlcXVpcmUoJy4vLi4vaGVscGVycy9pc1VSTFNhbWVPcmlnaW4nKTtcbnZhciBjcmVhdGVFcnJvciA9IHJlcXVpcmUoJy4uL2NvcmUvY3JlYXRlRXJyb3InKTtcblxubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbiB4aHJBZGFwdGVyKGNvbmZpZykge1xuICByZXR1cm4gbmV3IFByb21pc2UoZnVuY3Rpb24gZGlzcGF0Y2hYaHJSZXF1ZXN0KHJlc29sdmUsIHJlamVjdCkge1xuICAgIHZhciByZXF1ZXN0RGF0YSA9IGNvbmZpZy5kYXRhO1xuICAgIHZhciByZXF1ZXN0SGVhZGVycyA9IGNvbmZpZy5oZWFkZXJzO1xuICAgIHZhciByZXNwb25zZVR5cGUgPSBjb25maWcucmVzcG9uc2VUeXBlO1xuXG4gICAgaWYgKHV0aWxzLmlzRm9ybURhdGEocmVxdWVzdERhdGEpKSB7XG4gICAgICBkZWxldGUgcmVxdWVzdEhlYWRlcnNbJ0NvbnRlbnQtVHlwZSddOyAvLyBMZXQgdGhlIGJyb3dzZXIgc2V0IGl0XG4gICAgfVxuXG4gICAgdmFyIHJlcXVlc3QgPSBuZXcgWE1MSHR0cFJlcXVlc3QoKTtcblxuICAgIC8vIEhUVFAgYmFzaWMgYXV0aGVudGljYXRpb25cbiAgICBpZiAoY29uZmlnLmF1dGgpIHtcbiAgICAgIHZhciB1c2VybmFtZSA9IGNvbmZpZy5hdXRoLnVzZXJuYW1lIHx8ICcnO1xuICAgICAgdmFyIHBhc3N3b3JkID0gY29uZmlnLmF1dGgucGFzc3dvcmQgPyB1bmVzY2FwZShlbmNvZGVVUklDb21wb25lbnQoY29uZmlnLmF1dGgucGFzc3dvcmQpKSA6ICcnO1xuICAgICAgcmVxdWVzdEhlYWRlcnMuQXV0aG9yaXphdGlvbiA9ICdCYXNpYyAnICsgYnRvYSh1c2VybmFtZSArICc6JyArIHBhc3N3b3JkKTtcbiAgICB9XG5cbiAgICB2YXIgZnVsbFBhdGggPSBidWlsZEZ1bGxQYXRoKGNvbmZpZy5iYXNlVVJMLCBjb25maWcudXJsKTtcbiAgICByZXF1ZXN0Lm9wZW4oY29uZmlnLm1ldGhvZC50b1VwcGVyQ2FzZSgpLCBidWlsZFVSTChmdWxsUGF0aCwgY29uZmlnLnBhcmFtcywgY29uZmlnLnBhcmFtc1NlcmlhbGl6ZXIpLCB0cnVlKTtcblxuICAgIC8vIFNldCB0aGUgcmVxdWVzdCB0aW1lb3V0IGluIE1TXG4gICAgcmVxdWVzdC50aW1lb3V0ID0gY29uZmlnLnRpbWVvdXQ7XG5cbiAgICBmdW5jdGlvbiBvbmxvYWRlbmQoKSB7XG4gICAgICBpZiAoIXJlcXVlc3QpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgICAgfVxuICAgICAgLy8gUHJlcGFyZSB0aGUgcmVzcG9uc2VcbiAgICAgIHZhciByZXNwb25zZUhlYWRlcnMgPSAnZ2V0QWxsUmVzcG9uc2VIZWFkZXJzJyBpbiByZXF1ZXN0ID8gcGFyc2VIZWFkZXJzKHJlcXVlc3QuZ2V0QWxsUmVzcG9uc2VIZWFkZXJzKCkpIDogbnVsbDtcbiAgICAgIHZhciByZXNwb25zZURhdGEgPSAhcmVzcG9uc2VUeXBlIHx8IHJlc3BvbnNlVHlwZSA9PT0gJ3RleHQnIHx8ICByZXNwb25zZVR5cGUgPT09ICdqc29uJyA/XG4gICAgICAgIHJlcXVlc3QucmVzcG9uc2VUZXh0IDogcmVxdWVzdC5yZXNwb25zZTtcbiAgICAgIHZhciByZXNwb25zZSA9IHtcbiAgICAgICAgZGF0YTogcmVzcG9uc2VEYXRhLFxuICAgICAgICBzdGF0dXM6IHJlcXVlc3Quc3RhdHVzLFxuICAgICAgICBzdGF0dXNUZXh0OiByZXF1ZXN0LnN0YXR1c1RleHQsXG4gICAgICAgIGhlYWRlcnM6IHJlc3BvbnNlSGVhZGVycyxcbiAgICAgICAgY29uZmlnOiBjb25maWcsXG4gICAgICAgIHJlcXVlc3Q6IHJlcXVlc3RcbiAgICAgIH07XG5cbiAgICAgIHNldHRsZShyZXNvbHZlLCByZWplY3QsIHJlc3BvbnNlKTtcblxuICAgICAgLy8gQ2xlYW4gdXAgcmVxdWVzdFxuICAgICAgcmVxdWVzdCA9IG51bGw7XG4gICAgfVxuXG4gICAgaWYgKCdvbmxvYWRlbmQnIGluIHJlcXVlc3QpIHtcbiAgICAgIC8vIFVzZSBvbmxvYWRlbmQgaWYgYXZhaWxhYmxlXG4gICAgICByZXF1ZXN0Lm9ubG9hZGVuZCA9IG9ubG9hZGVuZDtcbiAgICB9IGVsc2Uge1xuICAgICAgLy8gTGlzdGVuIGZvciByZWFkeSBzdGF0ZSB0byBlbXVsYXRlIG9ubG9hZGVuZFxuICAgICAgcmVxdWVzdC5vbnJlYWR5c3RhdGVjaGFuZ2UgPSBmdW5jdGlvbiBoYW5kbGVMb2FkKCkge1xuICAgICAgICBpZiAoIXJlcXVlc3QgfHwgcmVxdWVzdC5yZWFkeVN0YXRlICE9PSA0KSB7XG4gICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gVGhlIHJlcXVlc3QgZXJyb3JlZCBvdXQgYW5kIHdlIGRpZG4ndCBnZXQgYSByZXNwb25zZSwgdGhpcyB3aWxsIGJlXG4gICAgICAgIC8vIGhhbmRsZWQgYnkgb25lcnJvciBpbnN0ZWFkXG4gICAgICAgIC8vIFdpdGggb25lIGV4Y2VwdGlvbjogcmVxdWVzdCB0aGF0IHVzaW5nIGZpbGU6IHByb3RvY29sLCBtb3N0IGJyb3dzZXJzXG4gICAgICAgIC8vIHdpbGwgcmV0dXJuIHN0YXR1cyBhcyAwIGV2ZW4gdGhvdWdoIGl0J3MgYSBzdWNjZXNzZnVsIHJlcXVlc3RcbiAgICAgICAgaWYgKHJlcXVlc3Quc3RhdHVzID09PSAwICYmICEocmVxdWVzdC5yZXNwb25zZVVSTCAmJiByZXF1ZXN0LnJlc3BvbnNlVVJMLmluZGV4T2YoJ2ZpbGU6JykgPT09IDApKSB7XG4gICAgICAgICAgcmV0dXJuO1xuICAgICAgICB9XG4gICAgICAgIC8vIHJlYWR5c3RhdGUgaGFuZGxlciBpcyBjYWxsaW5nIGJlZm9yZSBvbmVycm9yIG9yIG9udGltZW91dCBoYW5kbGVycyxcbiAgICAgICAgLy8gc28gd2Ugc2hvdWxkIGNhbGwgb25sb2FkZW5kIG9uIHRoZSBuZXh0ICd0aWNrJ1xuICAgICAgICBzZXRUaW1lb3V0KG9ubG9hZGVuZCk7XG4gICAgICB9O1xuICAgIH1cblxuICAgIC8vIEhhbmRsZSBicm93c2VyIHJlcXVlc3QgY2FuY2VsbGF0aW9uIChhcyBvcHBvc2VkIHRvIGEgbWFudWFsIGNhbmNlbGxhdGlvbilcbiAgICByZXF1ZXN0Lm9uYWJvcnQgPSBmdW5jdGlvbiBoYW5kbGVBYm9ydCgpIHtcbiAgICAgIGlmICghcmVxdWVzdCkge1xuICAgICAgICByZXR1cm47XG4gICAgICB9XG5cbiAgICAgIHJlamVjdChjcmVhdGVFcnJvcignUmVxdWVzdCBhYm9ydGVkJywgY29uZmlnLCAnRUNPTk5BQk9SVEVEJywgcmVxdWVzdCkpO1xuXG4gICAgICAvLyBDbGVhbiB1cCByZXF1ZXN0XG4gICAgICByZXF1ZXN0ID0gbnVsbDtcbiAgICB9O1xuXG4gICAgLy8gSGFuZGxlIGxvdyBsZXZlbCBuZXR3b3JrIGVycm9yc1xuICAgIHJlcXVlc3Qub25lcnJvciA9IGZ1bmN0aW9uIGhhbmRsZUVycm9yKCkge1xuICAgICAgLy8gUmVhbCBlcnJvcnMgYXJlIGhpZGRlbiBmcm9tIHVzIGJ5IHRoZSBicm93c2VyXG4gICAgICAvLyBvbmVycm9yIHNob3VsZCBvbmx5IGZpcmUgaWYgaXQncyBhIG5ldHdvcmsgZXJyb3JcbiAgICAgIHJlamVjdChjcmVhdGVFcnJvcignTmV0d29yayBFcnJvcicsIGNvbmZpZywgbnVsbCwgcmVxdWVzdCkpO1xuXG4gICAgICAvLyBDbGVhbiB1cCByZXF1ZXN0XG4gICAgICByZXF1ZXN0ID0gbnVsbDtcbiAgICB9O1xuXG4gICAgLy8gSGFuZGxlIHRpbWVvdXRcbiAgICByZXF1ZXN0Lm9udGltZW91dCA9IGZ1bmN0aW9uIGhhbmRsZVRpbWVvdXQoKSB7XG4gICAgICB2YXIgdGltZW91dEVycm9yTWVzc2FnZSA9ICd0aW1lb3V0IG9mICcgKyBjb25maWcudGltZW91dCArICdtcyBleGNlZWRlZCc7XG4gICAgICBpZiAoY29uZmlnLnRpbWVvdXRFcnJvck1lc3NhZ2UpIHtcbiAgICAgICAgdGltZW91dEVycm9yTWVzc2FnZSA9IGNvbmZpZy50aW1lb3V0RXJyb3JNZXNzYWdlO1xuICAgICAgfVxuICAgICAgcmVqZWN0KGNyZWF0ZUVycm9yKFxuICAgICAgICB0aW1lb3V0RXJyb3JNZXNzYWdlLFxuICAgICAgICBjb25maWcsXG4gICAgICAgIGNvbmZpZy50cmFuc2l0aW9uYWwgJiYgY29uZmlnLnRyYW5zaXRpb25hbC5jbGFyaWZ5VGltZW91dEVycm9yID8gJ0VUSU1FRE9VVCcgOiAnRUNPTk5BQk9SVEVEJyxcbiAgICAgICAgcmVxdWVzdCkpO1xuXG4gICAgICAvLyBDbGVhbiB1cCByZXF1ZXN0XG4gICAgICByZXF1ZXN0ID0gbnVsbDtcbiAgICB9O1xuXG4gICAgLy8gQWRkIHhzcmYgaGVhZGVyXG4gICAgLy8gVGhpcyBpcyBvbmx5IGRvbmUgaWYgcnVubmluZyBpbiBhIHN0YW5kYXJkIGJyb3dzZXIgZW52aXJvbm1lbnQuXG4gICAgLy8gU3BlY2lmaWNhbGx5IG5vdCBpZiB3ZSdyZSBpbiBhIHdlYiB3b3JrZXIsIG9yIHJlYWN0LW5hdGl2ZS5cbiAgICBpZiAodXRpbHMuaXNTdGFuZGFyZEJyb3dzZXJFbnYoKSkge1xuICAgICAgLy8gQWRkIHhzcmYgaGVhZGVyXG4gICAgICB2YXIgeHNyZlZhbHVlID0gKGNvbmZpZy53aXRoQ3JlZGVudGlhbHMgfHwgaXNVUkxTYW1lT3JpZ2luKGZ1bGxQYXRoKSkgJiYgY29uZmlnLnhzcmZDb29raWVOYW1lID9cbiAgICAgICAgY29va2llcy5yZWFkKGNvbmZpZy54c3JmQ29va2llTmFtZSkgOlxuICAgICAgICB1bmRlZmluZWQ7XG5cbiAgICAgIGlmICh4c3JmVmFsdWUpIHtcbiAgICAgICAgcmVxdWVzdEhlYWRlcnNbY29uZmlnLnhzcmZIZWFkZXJOYW1lXSA9IHhzcmZWYWx1ZTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICAvLyBBZGQgaGVhZGVycyB0byB0aGUgcmVxdWVzdFxuICAgIGlmICgnc2V0UmVxdWVzdEhlYWRlcicgaW4gcmVxdWVzdCkge1xuICAgICAgdXRpbHMuZm9yRWFjaChyZXF1ZXN0SGVhZGVycywgZnVuY3Rpb24gc2V0UmVxdWVzdEhlYWRlcih2YWwsIGtleSkge1xuICAgICAgICBpZiAodHlwZW9mIHJlcXVlc3REYXRhID09PSAndW5kZWZpbmVkJyAmJiBrZXkudG9Mb3dlckNhc2UoKSA9PT0gJ2NvbnRlbnQtdHlwZScpIHtcbiAgICAgICAgICAvLyBSZW1vdmUgQ29udGVudC1UeXBlIGlmIGRhdGEgaXMgdW5kZWZpbmVkXG4gICAgICAgICAgZGVsZXRlIHJlcXVlc3RIZWFkZXJzW2tleV07XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgLy8gT3RoZXJ3aXNlIGFkZCBoZWFkZXIgdG8gdGhlIHJlcXVlc3RcbiAgICAgICAgICByZXF1ZXN0LnNldFJlcXVlc3RIZWFkZXIoa2V5LCB2YWwpO1xuICAgICAgICB9XG4gICAgICB9KTtcbiAgICB9XG5cbiAgICAvLyBBZGQgd2l0aENyZWRlbnRpYWxzIHRvIHJlcXVlc3QgaWYgbmVlZGVkXG4gICAgaWYgKCF1dGlscy5pc1VuZGVmaW5lZChjb25maWcud2l0aENyZWRlbnRpYWxzKSkge1xuICAgICAgcmVxdWVzdC53aXRoQ3JlZGVudGlhbHMgPSAhIWNvbmZpZy53aXRoQ3JlZGVudGlhbHM7XG4gICAgfVxuXG4gICAgLy8gQWRkIHJlc3BvbnNlVHlwZSB0byByZXF1ZXN0IGlmIG5lZWRlZFxuICAgIGlmIChyZXNwb25zZVR5cGUgJiYgcmVzcG9uc2VUeXBlICE9PSAnanNvbicpIHtcbiAgICAgIHJlcXVlc3QucmVzcG9uc2VUeXBlID0gY29uZmlnLnJlc3BvbnNlVHlwZTtcbiAgICB9XG5cbiAgICAvLyBIYW5kbGUgcHJvZ3Jlc3MgaWYgbmVlZGVkXG4gICAgaWYgKHR5cGVvZiBjb25maWcub25Eb3dubG9hZFByb2dyZXNzID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICByZXF1ZXN0LmFkZEV2ZW50TGlzdGVuZXIoJ3Byb2dyZXNzJywgY29uZmlnLm9uRG93bmxvYWRQcm9ncmVzcyk7XG4gICAgfVxuXG4gICAgLy8gTm90IGFsbCBicm93c2VycyBzdXBwb3J0IHVwbG9hZCBldmVudHNcbiAgICBpZiAodHlwZW9mIGNvbmZpZy5vblVwbG9hZFByb2dyZXNzID09PSAnZnVuY3Rpb24nICYmIHJlcXVlc3QudXBsb2FkKSB7XG4gICAgICByZXF1ZXN0LnVwbG9hZC5hZGRFdmVudExpc3RlbmVyKCdwcm9ncmVzcycsIGNvbmZpZy5vblVwbG9hZFByb2dyZXNzKTtcbiAgICB9XG5cbiAgICBpZiAoY29uZmlnLmNhbmNlbFRva2VuKSB7XG4gICAgICAvLyBIYW5kbGUgY2FuY2VsbGF0aW9uXG4gICAgICBjb25maWcuY2FuY2VsVG9rZW4ucHJvbWlzZS50aGVuKGZ1bmN0aW9uIG9uQ2FuY2VsZWQoY2FuY2VsKSB7XG4gICAgICAgIGlmICghcmVxdWVzdCkge1xuICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIHJlcXVlc3QuYWJvcnQoKTtcbiAgICAgICAgcmVqZWN0KGNhbmNlbCk7XG4gICAgICAgIC8vIENsZWFuIHVwIHJlcXVlc3RcbiAgICAgICAgcmVxdWVzdCA9IG51bGw7XG4gICAgICB9KTtcbiAgICB9XG5cbiAgICBpZiAoIXJlcXVlc3REYXRhKSB7XG4gICAgICByZXF1ZXN0RGF0YSA9IG51bGw7XG4gICAgfVxuXG4gICAgLy8gU2VuZCB0aGUgcmVxdWVzdFxuICAgIHJlcXVlc3Quc2VuZChyZXF1ZXN0RGF0YSk7XG4gIH0pO1xufTtcbiIsIid1c2Ugc3RyaWN0JztcblxudmFyIHV0aWxzID0gcmVxdWlyZSgnLi91dGlscycpO1xudmFyIGJpbmQgPSByZXF1aXJlKCcuL2hlbHBlcnMvYmluZCcpO1xudmFyIEF4aW9zID0gcmVxdWlyZSgnLi9jb3JlL0F4aW9zJyk7XG52YXIgbWVyZ2VDb25maWcgPSByZXF1aXJlKCcuL2NvcmUvbWVyZ2VDb25maWcnKTtcbnZhciBkZWZhdWx0cyA9IHJlcXVpcmUoJy4vZGVmYXVsdHMnKTtcblxuLyoqXG4gKiBDcmVhdGUgYW4gaW5zdGFuY2Ugb2YgQXhpb3NcbiAqXG4gKiBAcGFyYW0ge09iamVjdH0gZGVmYXVsdENvbmZpZyBUaGUgZGVmYXVsdCBjb25maWcgZm9yIHRoZSBpbnN0YW5jZVxuICogQHJldHVybiB7QXhpb3N9IEEgbmV3IGluc3RhbmNlIG9mIEF4aW9zXG4gKi9cbmZ1bmN0aW9uIGNyZWF0ZUluc3RhbmNlKGRlZmF1bHRDb25maWcpIHtcbiAgdmFyIGNvbnRleHQgPSBuZXcgQXhpb3MoZGVmYXVsdENvbmZpZyk7XG4gIHZhciBpbnN0YW5jZSA9IGJpbmQoQXhpb3MucHJvdG90eXBlLnJlcXVlc3QsIGNvbnRleHQpO1xuXG4gIC8vIENvcHkgYXhpb3MucHJvdG90eXBlIHRvIGluc3RhbmNlXG4gIHV0aWxzLmV4dGVuZChpbnN0YW5jZSwgQXhpb3MucHJvdG90eXBlLCBjb250ZXh0KTtcblxuICAvLyBDb3B5IGNvbnRleHQgdG8gaW5zdGFuY2VcbiAgdXRpbHMuZXh0ZW5kKGluc3RhbmNlLCBjb250ZXh0KTtcblxuICByZXR1cm4gaW5zdGFuY2U7XG59XG5cbi8vIENyZWF0ZSB0aGUgZGVmYXVsdCBpbnN0YW5jZSB0byBiZSBleHBvcnRlZFxudmFyIGF4aW9zID0gY3JlYXRlSW5zdGFuY2UoZGVmYXVsdHMpO1xuXG4vLyBFeHBvc2UgQXhpb3MgY2xhc3MgdG8gYWxsb3cgY2xhc3MgaW5oZXJpdGFuY2VcbmF4aW9zLkF4aW9zID0gQXhpb3M7XG5cbi8vIEZhY3RvcnkgZm9yIGNyZWF0aW5nIG5ldyBpbnN0YW5jZXNcbmF4aW9zLmNyZWF0ZSA9IGZ1bmN0aW9uIGNyZWF0ZShpbnN0YW5jZUNvbmZpZykge1xuICByZXR1cm4gY3JlYXRlSW5zdGFuY2UobWVyZ2VDb25maWcoYXhpb3MuZGVmYXVsdHMsIGluc3RhbmNlQ29uZmlnKSk7XG59O1xuXG4vLyBFeHBvc2UgQ2FuY2VsICYgQ2FuY2VsVG9rZW5cbmF4aW9zLkNhbmNlbCA9IHJlcXVpcmUoJy4vY2FuY2VsL0NhbmNlbCcpO1xuYXhpb3MuQ2FuY2VsVG9rZW4gPSByZXF1aXJlKCcuL2NhbmNlbC9DYW5jZWxUb2tlbicpO1xuYXhpb3MuaXNDYW5jZWwgPSByZXF1aXJlKCcuL2NhbmNlbC9pc0NhbmNlbCcpO1xuXG4vLyBFeHBvc2UgYWxsL3NwcmVhZFxuYXhpb3MuYWxsID0gZnVuY3Rpb24gYWxsKHByb21pc2VzKSB7XG4gIHJldHVybiBQcm9taXNlLmFsbChwcm9taXNlcyk7XG59O1xuYXhpb3Muc3ByZWFkID0gcmVxdWlyZSgnLi9oZWxwZXJzL3NwcmVhZCcpO1xuXG4vLyBFeHBvc2UgaXNBeGlvc0Vycm9yXG5heGlvcy5pc0F4aW9zRXJyb3IgPSByZXF1aXJlKCcuL2hlbHBlcnMvaXNBeGlvc0Vycm9yJyk7XG5cbm1vZHVsZS5leHBvcnRzID0gYXhpb3M7XG5cbi8vIEFsbG93IHVzZSBvZiBkZWZhdWx0IGltcG9ydCBzeW50YXggaW4gVHlwZVNjcmlwdFxubW9kdWxlLmV4cG9ydHMuZGVmYXVsdCA9IGF4aW9zO1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG4vKipcbiAqIEEgYENhbmNlbGAgaXMgYW4gb2JqZWN0IHRoYXQgaXMgdGhyb3duIHdoZW4gYW4gb3BlcmF0aW9uIGlzIGNhbmNlbGVkLlxuICpcbiAqIEBjbGFzc1xuICogQHBhcmFtIHtzdHJpbmc9fSBtZXNzYWdlIFRoZSBtZXNzYWdlLlxuICovXG5mdW5jdGlvbiBDYW5jZWwobWVzc2FnZSkge1xuICB0aGlzLm1lc3NhZ2UgPSBtZXNzYWdlO1xufVxuXG5DYW5jZWwucHJvdG90eXBlLnRvU3RyaW5nID0gZnVuY3Rpb24gdG9TdHJpbmcoKSB7XG4gIHJldHVybiAnQ2FuY2VsJyArICh0aGlzLm1lc3NhZ2UgPyAnOiAnICsgdGhpcy5tZXNzYWdlIDogJycpO1xufTtcblxuQ2FuY2VsLnByb3RvdHlwZS5fX0NBTkNFTF9fID0gdHJ1ZTtcblxubW9kdWxlLmV4cG9ydHMgPSBDYW5jZWw7XG4iLCIndXNlIHN0cmljdCc7XG5cbnZhciBDYW5jZWwgPSByZXF1aXJlKCcuL0NhbmNlbCcpO1xuXG4vKipcbiAqIEEgYENhbmNlbFRva2VuYCBpcyBhbiBvYmplY3QgdGhhdCBjYW4gYmUgdXNlZCB0byByZXF1ZXN0IGNhbmNlbGxhdGlvbiBvZiBhbiBvcGVyYXRpb24uXG4gKlxuICogQGNsYXNzXG4gKiBAcGFyYW0ge0Z1bmN0aW9ufSBleGVjdXRvciBUaGUgZXhlY3V0b3IgZnVuY3Rpb24uXG4gKi9cbmZ1bmN0aW9uIENhbmNlbFRva2VuKGV4ZWN1dG9yKSB7XG4gIGlmICh0eXBlb2YgZXhlY3V0b3IgIT09ICdmdW5jdGlvbicpIHtcbiAgICB0aHJvdyBuZXcgVHlwZUVycm9yKCdleGVjdXRvciBtdXN0IGJlIGEgZnVuY3Rpb24uJyk7XG4gIH1cblxuICB2YXIgcmVzb2x2ZVByb21pc2U7XG4gIHRoaXMucHJvbWlzZSA9IG5ldyBQcm9taXNlKGZ1bmN0aW9uIHByb21pc2VFeGVjdXRvcihyZXNvbHZlKSB7XG4gICAgcmVzb2x2ZVByb21pc2UgPSByZXNvbHZlO1xuICB9KTtcblxuICB2YXIgdG9rZW4gPSB0aGlzO1xuICBleGVjdXRvcihmdW5jdGlvbiBjYW5jZWwobWVzc2FnZSkge1xuICAgIGlmICh0b2tlbi5yZWFzb24pIHtcbiAgICAgIC8vIENhbmNlbGxhdGlvbiBoYXMgYWxyZWFkeSBiZWVuIHJlcXVlc3RlZFxuICAgICAgcmV0dXJuO1xuICAgIH1cblxuICAgIHRva2VuLnJlYXNvbiA9IG5ldyBDYW5jZWwobWVzc2FnZSk7XG4gICAgcmVzb2x2ZVByb21pc2UodG9rZW4ucmVhc29uKTtcbiAgfSk7XG59XG5cbi8qKlxuICogVGhyb3dzIGEgYENhbmNlbGAgaWYgY2FuY2VsbGF0aW9uIGhhcyBiZWVuIHJlcXVlc3RlZC5cbiAqL1xuQ2FuY2VsVG9rZW4ucHJvdG90eXBlLnRocm93SWZSZXF1ZXN0ZWQgPSBmdW5jdGlvbiB0aHJvd0lmUmVxdWVzdGVkKCkge1xuICBpZiAodGhpcy5yZWFzb24pIHtcbiAgICB0aHJvdyB0aGlzLnJlYXNvbjtcbiAgfVxufTtcblxuLyoqXG4gKiBSZXR1cm5zIGFuIG9iamVjdCB0aGF0IGNvbnRhaW5zIGEgbmV3IGBDYW5jZWxUb2tlbmAgYW5kIGEgZnVuY3Rpb24gdGhhdCwgd2hlbiBjYWxsZWQsXG4gKiBjYW5jZWxzIHRoZSBgQ2FuY2VsVG9rZW5gLlxuICovXG5DYW5jZWxUb2tlbi5zb3VyY2UgPSBmdW5jdGlvbiBzb3VyY2UoKSB7XG4gIHZhciBjYW5jZWw7XG4gIHZhciB0b2tlbiA9IG5ldyBDYW5jZWxUb2tlbihmdW5jdGlvbiBleGVjdXRvcihjKSB7XG4gICAgY2FuY2VsID0gYztcbiAgfSk7XG4gIHJldHVybiB7XG4gICAgdG9rZW46IHRva2VuLFxuICAgIGNhbmNlbDogY2FuY2VsXG4gIH07XG59O1xuXG5tb2R1bGUuZXhwb3J0cyA9IENhbmNlbFRva2VuO1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uIGlzQ2FuY2VsKHZhbHVlKSB7XG4gIHJldHVybiAhISh2YWx1ZSAmJiB2YWx1ZS5fX0NBTkNFTF9fKTtcbn07XG4iLCIndXNlIHN0cmljdCc7XG5cbnZhciB1dGlscyA9IHJlcXVpcmUoJy4vLi4vdXRpbHMnKTtcbnZhciBidWlsZFVSTCA9IHJlcXVpcmUoJy4uL2hlbHBlcnMvYnVpbGRVUkwnKTtcbnZhciBJbnRlcmNlcHRvck1hbmFnZXIgPSByZXF1aXJlKCcuL0ludGVyY2VwdG9yTWFuYWdlcicpO1xudmFyIGRpc3BhdGNoUmVxdWVzdCA9IHJlcXVpcmUoJy4vZGlzcGF0Y2hSZXF1ZXN0Jyk7XG52YXIgbWVyZ2VDb25maWcgPSByZXF1aXJlKCcuL21lcmdlQ29uZmlnJyk7XG52YXIgdmFsaWRhdG9yID0gcmVxdWlyZSgnLi4vaGVscGVycy92YWxpZGF0b3InKTtcblxudmFyIHZhbGlkYXRvcnMgPSB2YWxpZGF0b3IudmFsaWRhdG9ycztcbi8qKlxuICogQ3JlYXRlIGEgbmV3IGluc3RhbmNlIG9mIEF4aW9zXG4gKlxuICogQHBhcmFtIHtPYmplY3R9IGluc3RhbmNlQ29uZmlnIFRoZSBkZWZhdWx0IGNvbmZpZyBmb3IgdGhlIGluc3RhbmNlXG4gKi9cbmZ1bmN0aW9uIEF4aW9zKGluc3RhbmNlQ29uZmlnKSB7XG4gIHRoaXMuZGVmYXVsdHMgPSBpbnN0YW5jZUNvbmZpZztcbiAgdGhpcy5pbnRlcmNlcHRvcnMgPSB7XG4gICAgcmVxdWVzdDogbmV3IEludGVyY2VwdG9yTWFuYWdlcigpLFxuICAgIHJlc3BvbnNlOiBuZXcgSW50ZXJjZXB0b3JNYW5hZ2VyKClcbiAgfTtcbn1cblxuLyoqXG4gKiBEaXNwYXRjaCBhIHJlcXVlc3RcbiAqXG4gKiBAcGFyYW0ge09iamVjdH0gY29uZmlnIFRoZSBjb25maWcgc3BlY2lmaWMgZm9yIHRoaXMgcmVxdWVzdCAobWVyZ2VkIHdpdGggdGhpcy5kZWZhdWx0cylcbiAqL1xuQXhpb3MucHJvdG90eXBlLnJlcXVlc3QgPSBmdW5jdGlvbiByZXF1ZXN0KGNvbmZpZykge1xuICAvKmVzbGludCBuby1wYXJhbS1yZWFzc2lnbjowKi9cbiAgLy8gQWxsb3cgZm9yIGF4aW9zKCdleGFtcGxlL3VybCdbLCBjb25maWddKSBhIGxhIGZldGNoIEFQSVxuICBpZiAodHlwZW9mIGNvbmZpZyA9PT0gJ3N0cmluZycpIHtcbiAgICBjb25maWcgPSBhcmd1bWVudHNbMV0gfHwge307XG4gICAgY29uZmlnLnVybCA9IGFyZ3VtZW50c1swXTtcbiAgfSBlbHNlIHtcbiAgICBjb25maWcgPSBjb25maWcgfHwge307XG4gIH1cblxuICBjb25maWcgPSBtZXJnZUNvbmZpZyh0aGlzLmRlZmF1bHRzLCBjb25maWcpO1xuXG4gIC8vIFNldCBjb25maWcubWV0aG9kXG4gIGlmIChjb25maWcubWV0aG9kKSB7XG4gICAgY29uZmlnLm1ldGhvZCA9IGNvbmZpZy5tZXRob2QudG9Mb3dlckNhc2UoKTtcbiAgfSBlbHNlIGlmICh0aGlzLmRlZmF1bHRzLm1ldGhvZCkge1xuICAgIGNvbmZpZy5tZXRob2QgPSB0aGlzLmRlZmF1bHRzLm1ldGhvZC50b0xvd2VyQ2FzZSgpO1xuICB9IGVsc2Uge1xuICAgIGNvbmZpZy5tZXRob2QgPSAnZ2V0JztcbiAgfVxuXG4gIHZhciB0cmFuc2l0aW9uYWwgPSBjb25maWcudHJhbnNpdGlvbmFsO1xuXG4gIGlmICh0cmFuc2l0aW9uYWwgIT09IHVuZGVmaW5lZCkge1xuICAgIHZhbGlkYXRvci5hc3NlcnRPcHRpb25zKHRyYW5zaXRpb25hbCwge1xuICAgICAgc2lsZW50SlNPTlBhcnNpbmc6IHZhbGlkYXRvcnMudHJhbnNpdGlvbmFsKHZhbGlkYXRvcnMuYm9vbGVhbiwgJzEuMC4wJyksXG4gICAgICBmb3JjZWRKU09OUGFyc2luZzogdmFsaWRhdG9ycy50cmFuc2l0aW9uYWwodmFsaWRhdG9ycy5ib29sZWFuLCAnMS4wLjAnKSxcbiAgICAgIGNsYXJpZnlUaW1lb3V0RXJyb3I6IHZhbGlkYXRvcnMudHJhbnNpdGlvbmFsKHZhbGlkYXRvcnMuYm9vbGVhbiwgJzEuMC4wJylcbiAgICB9LCBmYWxzZSk7XG4gIH1cblxuICAvLyBmaWx0ZXIgb3V0IHNraXBwZWQgaW50ZXJjZXB0b3JzXG4gIHZhciByZXF1ZXN0SW50ZXJjZXB0b3JDaGFpbiA9IFtdO1xuICB2YXIgc3luY2hyb25vdXNSZXF1ZXN0SW50ZXJjZXB0b3JzID0gdHJ1ZTtcbiAgdGhpcy5pbnRlcmNlcHRvcnMucmVxdWVzdC5mb3JFYWNoKGZ1bmN0aW9uIHVuc2hpZnRSZXF1ZXN0SW50ZXJjZXB0b3JzKGludGVyY2VwdG9yKSB7XG4gICAgaWYgKHR5cGVvZiBpbnRlcmNlcHRvci5ydW5XaGVuID09PSAnZnVuY3Rpb24nICYmIGludGVyY2VwdG9yLnJ1bldoZW4oY29uZmlnKSA9PT0gZmFsc2UpIHtcbiAgICAgIHJldHVybjtcbiAgICB9XG5cbiAgICBzeW5jaHJvbm91c1JlcXVlc3RJbnRlcmNlcHRvcnMgPSBzeW5jaHJvbm91c1JlcXVlc3RJbnRlcmNlcHRvcnMgJiYgaW50ZXJjZXB0b3Iuc3luY2hyb25vdXM7XG5cbiAgICByZXF1ZXN0SW50ZXJjZXB0b3JDaGFpbi51bnNoaWZ0KGludGVyY2VwdG9yLmZ1bGZpbGxlZCwgaW50ZXJjZXB0b3IucmVqZWN0ZWQpO1xuICB9KTtcblxuICB2YXIgcmVzcG9uc2VJbnRlcmNlcHRvckNoYWluID0gW107XG4gIHRoaXMuaW50ZXJjZXB0b3JzLnJlc3BvbnNlLmZvckVhY2goZnVuY3Rpb24gcHVzaFJlc3BvbnNlSW50ZXJjZXB0b3JzKGludGVyY2VwdG9yKSB7XG4gICAgcmVzcG9uc2VJbnRlcmNlcHRvckNoYWluLnB1c2goaW50ZXJjZXB0b3IuZnVsZmlsbGVkLCBpbnRlcmNlcHRvci5yZWplY3RlZCk7XG4gIH0pO1xuXG4gIHZhciBwcm9taXNlO1xuXG4gIGlmICghc3luY2hyb25vdXNSZXF1ZXN0SW50ZXJjZXB0b3JzKSB7XG4gICAgdmFyIGNoYWluID0gW2Rpc3BhdGNoUmVxdWVzdCwgdW5kZWZpbmVkXTtcblxuICAgIEFycmF5LnByb3RvdHlwZS51bnNoaWZ0LmFwcGx5KGNoYWluLCByZXF1ZXN0SW50ZXJjZXB0b3JDaGFpbik7XG4gICAgY2hhaW4gPSBjaGFpbi5jb25jYXQocmVzcG9uc2VJbnRlcmNlcHRvckNoYWluKTtcblxuICAgIHByb21pc2UgPSBQcm9taXNlLnJlc29sdmUoY29uZmlnKTtcbiAgICB3aGlsZSAoY2hhaW4ubGVuZ3RoKSB7XG4gICAgICBwcm9taXNlID0gcHJvbWlzZS50aGVuKGNoYWluLnNoaWZ0KCksIGNoYWluLnNoaWZ0KCkpO1xuICAgIH1cblxuICAgIHJldHVybiBwcm9taXNlO1xuICB9XG5cblxuICB2YXIgbmV3Q29uZmlnID0gY29uZmlnO1xuICB3aGlsZSAocmVxdWVzdEludGVyY2VwdG9yQ2hhaW4ubGVuZ3RoKSB7XG4gICAgdmFyIG9uRnVsZmlsbGVkID0gcmVxdWVzdEludGVyY2VwdG9yQ2hhaW4uc2hpZnQoKTtcbiAgICB2YXIgb25SZWplY3RlZCA9IHJlcXVlc3RJbnRlcmNlcHRvckNoYWluLnNoaWZ0KCk7XG4gICAgdHJ5IHtcbiAgICAgIG5ld0NvbmZpZyA9IG9uRnVsZmlsbGVkKG5ld0NvbmZpZyk7XG4gICAgfSBjYXRjaCAoZXJyb3IpIHtcbiAgICAgIG9uUmVqZWN0ZWQoZXJyb3IpO1xuICAgICAgYnJlYWs7XG4gICAgfVxuICB9XG5cbiAgdHJ5IHtcbiAgICBwcm9taXNlID0gZGlzcGF0Y2hSZXF1ZXN0KG5ld0NvbmZpZyk7XG4gIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgcmV0dXJuIFByb21pc2UucmVqZWN0KGVycm9yKTtcbiAgfVxuXG4gIHdoaWxlIChyZXNwb25zZUludGVyY2VwdG9yQ2hhaW4ubGVuZ3RoKSB7XG4gICAgcHJvbWlzZSA9IHByb21pc2UudGhlbihyZXNwb25zZUludGVyY2VwdG9yQ2hhaW4uc2hpZnQoKSwgcmVzcG9uc2VJbnRlcmNlcHRvckNoYWluLnNoaWZ0KCkpO1xuICB9XG5cbiAgcmV0dXJuIHByb21pc2U7XG59O1xuXG5BeGlvcy5wcm90b3R5cGUuZ2V0VXJpID0gZnVuY3Rpb24gZ2V0VXJpKGNvbmZpZykge1xuICBjb25maWcgPSBtZXJnZUNvbmZpZyh0aGlzLmRlZmF1bHRzLCBjb25maWcpO1xuICByZXR1cm4gYnVpbGRVUkwoY29uZmlnLnVybCwgY29uZmlnLnBhcmFtcywgY29uZmlnLnBhcmFtc1NlcmlhbGl6ZXIpLnJlcGxhY2UoL15cXD8vLCAnJyk7XG59O1xuXG4vLyBQcm92aWRlIGFsaWFzZXMgZm9yIHN1cHBvcnRlZCByZXF1ZXN0IG1ldGhvZHNcbnV0aWxzLmZvckVhY2goWydkZWxldGUnLCAnZ2V0JywgJ2hlYWQnLCAnb3B0aW9ucyddLCBmdW5jdGlvbiBmb3JFYWNoTWV0aG9kTm9EYXRhKG1ldGhvZCkge1xuICAvKmVzbGludCBmdW5jLW5hbWVzOjAqL1xuICBBeGlvcy5wcm90b3R5cGVbbWV0aG9kXSA9IGZ1bmN0aW9uKHVybCwgY29uZmlnKSB7XG4gICAgcmV0dXJuIHRoaXMucmVxdWVzdChtZXJnZUNvbmZpZyhjb25maWcgfHwge30sIHtcbiAgICAgIG1ldGhvZDogbWV0aG9kLFxuICAgICAgdXJsOiB1cmwsXG4gICAgICBkYXRhOiAoY29uZmlnIHx8IHt9KS5kYXRhXG4gICAgfSkpO1xuICB9O1xufSk7XG5cbnV0aWxzLmZvckVhY2goWydwb3N0JywgJ3B1dCcsICdwYXRjaCddLCBmdW5jdGlvbiBmb3JFYWNoTWV0aG9kV2l0aERhdGEobWV0aG9kKSB7XG4gIC8qZXNsaW50IGZ1bmMtbmFtZXM6MCovXG4gIEF4aW9zLnByb3RvdHlwZVttZXRob2RdID0gZnVuY3Rpb24odXJsLCBkYXRhLCBjb25maWcpIHtcbiAgICByZXR1cm4gdGhpcy5yZXF1ZXN0KG1lcmdlQ29uZmlnKGNvbmZpZyB8fCB7fSwge1xuICAgICAgbWV0aG9kOiBtZXRob2QsXG4gICAgICB1cmw6IHVybCxcbiAgICAgIGRhdGE6IGRhdGFcbiAgICB9KSk7XG4gIH07XG59KTtcblxubW9kdWxlLmV4cG9ydHMgPSBBeGlvcztcbiIsIid1c2Ugc3RyaWN0JztcblxudmFyIHV0aWxzID0gcmVxdWlyZSgnLi8uLi91dGlscycpO1xuXG5mdW5jdGlvbiBJbnRlcmNlcHRvck1hbmFnZXIoKSB7XG4gIHRoaXMuaGFuZGxlcnMgPSBbXTtcbn1cblxuLyoqXG4gKiBBZGQgYSBuZXcgaW50ZXJjZXB0b3IgdG8gdGhlIHN0YWNrXG4gKlxuICogQHBhcmFtIHtGdW5jdGlvbn0gZnVsZmlsbGVkIFRoZSBmdW5jdGlvbiB0byBoYW5kbGUgYHRoZW5gIGZvciBhIGBQcm9taXNlYFxuICogQHBhcmFtIHtGdW5jdGlvbn0gcmVqZWN0ZWQgVGhlIGZ1bmN0aW9uIHRvIGhhbmRsZSBgcmVqZWN0YCBmb3IgYSBgUHJvbWlzZWBcbiAqXG4gKiBAcmV0dXJuIHtOdW1iZXJ9IEFuIElEIHVzZWQgdG8gcmVtb3ZlIGludGVyY2VwdG9yIGxhdGVyXG4gKi9cbkludGVyY2VwdG9yTWFuYWdlci5wcm90b3R5cGUudXNlID0gZnVuY3Rpb24gdXNlKGZ1bGZpbGxlZCwgcmVqZWN0ZWQsIG9wdGlvbnMpIHtcbiAgdGhpcy5oYW5kbGVycy5wdXNoKHtcbiAgICBmdWxmaWxsZWQ6IGZ1bGZpbGxlZCxcbiAgICByZWplY3RlZDogcmVqZWN0ZWQsXG4gICAgc3luY2hyb25vdXM6IG9wdGlvbnMgPyBvcHRpb25zLnN5bmNocm9ub3VzIDogZmFsc2UsXG4gICAgcnVuV2hlbjogb3B0aW9ucyA/IG9wdGlvbnMucnVuV2hlbiA6IG51bGxcbiAgfSk7XG4gIHJldHVybiB0aGlzLmhhbmRsZXJzLmxlbmd0aCAtIDE7XG59O1xuXG4vKipcbiAqIFJlbW92ZSBhbiBpbnRlcmNlcHRvciBmcm9tIHRoZSBzdGFja1xuICpcbiAqIEBwYXJhbSB7TnVtYmVyfSBpZCBUaGUgSUQgdGhhdCB3YXMgcmV0dXJuZWQgYnkgYHVzZWBcbiAqL1xuSW50ZXJjZXB0b3JNYW5hZ2VyLnByb3RvdHlwZS5lamVjdCA9IGZ1bmN0aW9uIGVqZWN0KGlkKSB7XG4gIGlmICh0aGlzLmhhbmRsZXJzW2lkXSkge1xuICAgIHRoaXMuaGFuZGxlcnNbaWRdID0gbnVsbDtcbiAgfVxufTtcblxuLyoqXG4gKiBJdGVyYXRlIG92ZXIgYWxsIHRoZSByZWdpc3RlcmVkIGludGVyY2VwdG9yc1xuICpcbiAqIFRoaXMgbWV0aG9kIGlzIHBhcnRpY3VsYXJseSB1c2VmdWwgZm9yIHNraXBwaW5nIG92ZXIgYW55XG4gKiBpbnRlcmNlcHRvcnMgdGhhdCBtYXkgaGF2ZSBiZWNvbWUgYG51bGxgIGNhbGxpbmcgYGVqZWN0YC5cbiAqXG4gKiBAcGFyYW0ge0Z1bmN0aW9ufSBmbiBUaGUgZnVuY3Rpb24gdG8gY2FsbCBmb3IgZWFjaCBpbnRlcmNlcHRvclxuICovXG5JbnRlcmNlcHRvck1hbmFnZXIucHJvdG90eXBlLmZvckVhY2ggPSBmdW5jdGlvbiBmb3JFYWNoKGZuKSB7XG4gIHV0aWxzLmZvckVhY2godGhpcy5oYW5kbGVycywgZnVuY3Rpb24gZm9yRWFjaEhhbmRsZXIoaCkge1xuICAgIGlmIChoICE9PSBudWxsKSB7XG4gICAgICBmbihoKTtcbiAgICB9XG4gIH0pO1xufTtcblxubW9kdWxlLmV4cG9ydHMgPSBJbnRlcmNlcHRvck1hbmFnZXI7XG4iLCIndXNlIHN0cmljdCc7XG5cbnZhciBpc0Fic29sdXRlVVJMID0gcmVxdWlyZSgnLi4vaGVscGVycy9pc0Fic29sdXRlVVJMJyk7XG52YXIgY29tYmluZVVSTHMgPSByZXF1aXJlKCcuLi9oZWxwZXJzL2NvbWJpbmVVUkxzJyk7XG5cbi8qKlxuICogQ3JlYXRlcyBhIG5ldyBVUkwgYnkgY29tYmluaW5nIHRoZSBiYXNlVVJMIHdpdGggdGhlIHJlcXVlc3RlZFVSTCxcbiAqIG9ubHkgd2hlbiB0aGUgcmVxdWVzdGVkVVJMIGlzIG5vdCBhbHJlYWR5IGFuIGFic29sdXRlIFVSTC5cbiAqIElmIHRoZSByZXF1ZXN0VVJMIGlzIGFic29sdXRlLCB0aGlzIGZ1bmN0aW9uIHJldHVybnMgdGhlIHJlcXVlc3RlZFVSTCB1bnRvdWNoZWQuXG4gKlxuICogQHBhcmFtIHtzdHJpbmd9IGJhc2VVUkwgVGhlIGJhc2UgVVJMXG4gKiBAcGFyYW0ge3N0cmluZ30gcmVxdWVzdGVkVVJMIEFic29sdXRlIG9yIHJlbGF0aXZlIFVSTCB0byBjb21iaW5lXG4gKiBAcmV0dXJucyB7c3RyaW5nfSBUaGUgY29tYmluZWQgZnVsbCBwYXRoXG4gKi9cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24gYnVpbGRGdWxsUGF0aChiYXNlVVJMLCByZXF1ZXN0ZWRVUkwpIHtcbiAgaWYgKGJhc2VVUkwgJiYgIWlzQWJzb2x1dGVVUkwocmVxdWVzdGVkVVJMKSkge1xuICAgIHJldHVybiBjb21iaW5lVVJMcyhiYXNlVVJMLCByZXF1ZXN0ZWRVUkwpO1xuICB9XG4gIHJldHVybiByZXF1ZXN0ZWRVUkw7XG59O1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG52YXIgZW5oYW5jZUVycm9yID0gcmVxdWlyZSgnLi9lbmhhbmNlRXJyb3InKTtcblxuLyoqXG4gKiBDcmVhdGUgYW4gRXJyb3Igd2l0aCB0aGUgc3BlY2lmaWVkIG1lc3NhZ2UsIGNvbmZpZywgZXJyb3IgY29kZSwgcmVxdWVzdCBhbmQgcmVzcG9uc2UuXG4gKlxuICogQHBhcmFtIHtzdHJpbmd9IG1lc3NhZ2UgVGhlIGVycm9yIG1lc3NhZ2UuXG4gKiBAcGFyYW0ge09iamVjdH0gY29uZmlnIFRoZSBjb25maWcuXG4gKiBAcGFyYW0ge3N0cmluZ30gW2NvZGVdIFRoZSBlcnJvciBjb2RlIChmb3IgZXhhbXBsZSwgJ0VDT05OQUJPUlRFRCcpLlxuICogQHBhcmFtIHtPYmplY3R9IFtyZXF1ZXN0XSBUaGUgcmVxdWVzdC5cbiAqIEBwYXJhbSB7T2JqZWN0fSBbcmVzcG9uc2VdIFRoZSByZXNwb25zZS5cbiAqIEByZXR1cm5zIHtFcnJvcn0gVGhlIGNyZWF0ZWQgZXJyb3IuXG4gKi9cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24gY3JlYXRlRXJyb3IobWVzc2FnZSwgY29uZmlnLCBjb2RlLCByZXF1ZXN0LCByZXNwb25zZSkge1xuICB2YXIgZXJyb3IgPSBuZXcgRXJyb3IobWVzc2FnZSk7XG4gIHJldHVybiBlbmhhbmNlRXJyb3IoZXJyb3IsIGNvbmZpZywgY29kZSwgcmVxdWVzdCwgcmVzcG9uc2UpO1xufTtcbiIsIid1c2Ugc3RyaWN0JztcblxudmFyIHV0aWxzID0gcmVxdWlyZSgnLi8uLi91dGlscycpO1xudmFyIHRyYW5zZm9ybURhdGEgPSByZXF1aXJlKCcuL3RyYW5zZm9ybURhdGEnKTtcbnZhciBpc0NhbmNlbCA9IHJlcXVpcmUoJy4uL2NhbmNlbC9pc0NhbmNlbCcpO1xudmFyIGRlZmF1bHRzID0gcmVxdWlyZSgnLi4vZGVmYXVsdHMnKTtcblxuLyoqXG4gKiBUaHJvd3MgYSBgQ2FuY2VsYCBpZiBjYW5jZWxsYXRpb24gaGFzIGJlZW4gcmVxdWVzdGVkLlxuICovXG5mdW5jdGlvbiB0aHJvd0lmQ2FuY2VsbGF0aW9uUmVxdWVzdGVkKGNvbmZpZykge1xuICBpZiAoY29uZmlnLmNhbmNlbFRva2VuKSB7XG4gICAgY29uZmlnLmNhbmNlbFRva2VuLnRocm93SWZSZXF1ZXN0ZWQoKTtcbiAgfVxufVxuXG4vKipcbiAqIERpc3BhdGNoIGEgcmVxdWVzdCB0byB0aGUgc2VydmVyIHVzaW5nIHRoZSBjb25maWd1cmVkIGFkYXB0ZXIuXG4gKlxuICogQHBhcmFtIHtvYmplY3R9IGNvbmZpZyBUaGUgY29uZmlnIHRoYXQgaXMgdG8gYmUgdXNlZCBmb3IgdGhlIHJlcXVlc3RcbiAqIEByZXR1cm5zIHtQcm9taXNlfSBUaGUgUHJvbWlzZSB0byBiZSBmdWxmaWxsZWRcbiAqL1xubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbiBkaXNwYXRjaFJlcXVlc3QoY29uZmlnKSB7XG4gIHRocm93SWZDYW5jZWxsYXRpb25SZXF1ZXN0ZWQoY29uZmlnKTtcblxuICAvLyBFbnN1cmUgaGVhZGVycyBleGlzdFxuICBjb25maWcuaGVhZGVycyA9IGNvbmZpZy5oZWFkZXJzIHx8IHt9O1xuXG4gIC8vIFRyYW5zZm9ybSByZXF1ZXN0IGRhdGFcbiAgY29uZmlnLmRhdGEgPSB0cmFuc2Zvcm1EYXRhLmNhbGwoXG4gICAgY29uZmlnLFxuICAgIGNvbmZpZy5kYXRhLFxuICAgIGNvbmZpZy5oZWFkZXJzLFxuICAgIGNvbmZpZy50cmFuc2Zvcm1SZXF1ZXN0XG4gICk7XG5cbiAgLy8gRmxhdHRlbiBoZWFkZXJzXG4gIGNvbmZpZy5oZWFkZXJzID0gdXRpbHMubWVyZ2UoXG4gICAgY29uZmlnLmhlYWRlcnMuY29tbW9uIHx8IHt9LFxuICAgIGNvbmZpZy5oZWFkZXJzW2NvbmZpZy5tZXRob2RdIHx8IHt9LFxuICAgIGNvbmZpZy5oZWFkZXJzXG4gICk7XG5cbiAgdXRpbHMuZm9yRWFjaChcbiAgICBbJ2RlbGV0ZScsICdnZXQnLCAnaGVhZCcsICdwb3N0JywgJ3B1dCcsICdwYXRjaCcsICdjb21tb24nXSxcbiAgICBmdW5jdGlvbiBjbGVhbkhlYWRlckNvbmZpZyhtZXRob2QpIHtcbiAgICAgIGRlbGV0ZSBjb25maWcuaGVhZGVyc1ttZXRob2RdO1xuICAgIH1cbiAgKTtcblxuICB2YXIgYWRhcHRlciA9IGNvbmZpZy5hZGFwdGVyIHx8IGRlZmF1bHRzLmFkYXB0ZXI7XG5cbiAgcmV0dXJuIGFkYXB0ZXIoY29uZmlnKS50aGVuKGZ1bmN0aW9uIG9uQWRhcHRlclJlc29sdXRpb24ocmVzcG9uc2UpIHtcbiAgICB0aHJvd0lmQ2FuY2VsbGF0aW9uUmVxdWVzdGVkKGNvbmZpZyk7XG5cbiAgICAvLyBUcmFuc2Zvcm0gcmVzcG9uc2UgZGF0YVxuICAgIHJlc3BvbnNlLmRhdGEgPSB0cmFuc2Zvcm1EYXRhLmNhbGwoXG4gICAgICBjb25maWcsXG4gICAgICByZXNwb25zZS5kYXRhLFxuICAgICAgcmVzcG9uc2UuaGVhZGVycyxcbiAgICAgIGNvbmZpZy50cmFuc2Zvcm1SZXNwb25zZVxuICAgICk7XG5cbiAgICByZXR1cm4gcmVzcG9uc2U7XG4gIH0sIGZ1bmN0aW9uIG9uQWRhcHRlclJlamVjdGlvbihyZWFzb24pIHtcbiAgICBpZiAoIWlzQ2FuY2VsKHJlYXNvbikpIHtcbiAgICAgIHRocm93SWZDYW5jZWxsYXRpb25SZXF1ZXN0ZWQoY29uZmlnKTtcblxuICAgICAgLy8gVHJhbnNmb3JtIHJlc3BvbnNlIGRhdGFcbiAgICAgIGlmIChyZWFzb24gJiYgcmVhc29uLnJlc3BvbnNlKSB7XG4gICAgICAgIHJlYXNvbi5yZXNwb25zZS5kYXRhID0gdHJhbnNmb3JtRGF0YS5jYWxsKFxuICAgICAgICAgIGNvbmZpZyxcbiAgICAgICAgICByZWFzb24ucmVzcG9uc2UuZGF0YSxcbiAgICAgICAgICByZWFzb24ucmVzcG9uc2UuaGVhZGVycyxcbiAgICAgICAgICBjb25maWcudHJhbnNmb3JtUmVzcG9uc2VcbiAgICAgICAgKTtcbiAgICAgIH1cbiAgICB9XG5cbiAgICByZXR1cm4gUHJvbWlzZS5yZWplY3QocmVhc29uKTtcbiAgfSk7XG59O1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG4vKipcbiAqIFVwZGF0ZSBhbiBFcnJvciB3aXRoIHRoZSBzcGVjaWZpZWQgY29uZmlnLCBlcnJvciBjb2RlLCBhbmQgcmVzcG9uc2UuXG4gKlxuICogQHBhcmFtIHtFcnJvcn0gZXJyb3IgVGhlIGVycm9yIHRvIHVwZGF0ZS5cbiAqIEBwYXJhbSB7T2JqZWN0fSBjb25maWcgVGhlIGNvbmZpZy5cbiAqIEBwYXJhbSB7c3RyaW5nfSBbY29kZV0gVGhlIGVycm9yIGNvZGUgKGZvciBleGFtcGxlLCAnRUNPTk5BQk9SVEVEJykuXG4gKiBAcGFyYW0ge09iamVjdH0gW3JlcXVlc3RdIFRoZSByZXF1ZXN0LlxuICogQHBhcmFtIHtPYmplY3R9IFtyZXNwb25zZV0gVGhlIHJlc3BvbnNlLlxuICogQHJldHVybnMge0Vycm9yfSBUaGUgZXJyb3IuXG4gKi9cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24gZW5oYW5jZUVycm9yKGVycm9yLCBjb25maWcsIGNvZGUsIHJlcXVlc3QsIHJlc3BvbnNlKSB7XG4gIGVycm9yLmNvbmZpZyA9IGNvbmZpZztcbiAgaWYgKGNvZGUpIHtcbiAgICBlcnJvci5jb2RlID0gY29kZTtcbiAgfVxuXG4gIGVycm9yLnJlcXVlc3QgPSByZXF1ZXN0O1xuICBlcnJvci5yZXNwb25zZSA9IHJlc3BvbnNlO1xuICBlcnJvci5pc0F4aW9zRXJyb3IgPSB0cnVlO1xuXG4gIGVycm9yLnRvSlNPTiA9IGZ1bmN0aW9uIHRvSlNPTigpIHtcbiAgICByZXR1cm4ge1xuICAgICAgLy8gU3RhbmRhcmRcbiAgICAgIG1lc3NhZ2U6IHRoaXMubWVzc2FnZSxcbiAgICAgIG5hbWU6IHRoaXMubmFtZSxcbiAgICAgIC8vIE1pY3Jvc29mdFxuICAgICAgZGVzY3JpcHRpb246IHRoaXMuZGVzY3JpcHRpb24sXG4gICAgICBudW1iZXI6IHRoaXMubnVtYmVyLFxuICAgICAgLy8gTW96aWxsYVxuICAgICAgZmlsZU5hbWU6IHRoaXMuZmlsZU5hbWUsXG4gICAgICBsaW5lTnVtYmVyOiB0aGlzLmxpbmVOdW1iZXIsXG4gICAgICBjb2x1bW5OdW1iZXI6IHRoaXMuY29sdW1uTnVtYmVyLFxuICAgICAgc3RhY2s6IHRoaXMuc3RhY2ssXG4gICAgICAvLyBBeGlvc1xuICAgICAgY29uZmlnOiB0aGlzLmNvbmZpZyxcbiAgICAgIGNvZGU6IHRoaXMuY29kZVxuICAgIH07XG4gIH07XG4gIHJldHVybiBlcnJvcjtcbn07XG4iLCIndXNlIHN0cmljdCc7XG5cbnZhciB1dGlscyA9IHJlcXVpcmUoJy4uL3V0aWxzJyk7XG5cbi8qKlxuICogQ29uZmlnLXNwZWNpZmljIG1lcmdlLWZ1bmN0aW9uIHdoaWNoIGNyZWF0ZXMgYSBuZXcgY29uZmlnLW9iamVjdFxuICogYnkgbWVyZ2luZyB0d28gY29uZmlndXJhdGlvbiBvYmplY3RzIHRvZ2V0aGVyLlxuICpcbiAqIEBwYXJhbSB7T2JqZWN0fSBjb25maWcxXG4gKiBAcGFyYW0ge09iamVjdH0gY29uZmlnMlxuICogQHJldHVybnMge09iamVjdH0gTmV3IG9iamVjdCByZXN1bHRpbmcgZnJvbSBtZXJnaW5nIGNvbmZpZzIgdG8gY29uZmlnMVxuICovXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uIG1lcmdlQ29uZmlnKGNvbmZpZzEsIGNvbmZpZzIpIHtcbiAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIG5vLXBhcmFtLXJlYXNzaWduXG4gIGNvbmZpZzIgPSBjb25maWcyIHx8IHt9O1xuICB2YXIgY29uZmlnID0ge307XG5cbiAgdmFyIHZhbHVlRnJvbUNvbmZpZzJLZXlzID0gWyd1cmwnLCAnbWV0aG9kJywgJ2RhdGEnXTtcbiAgdmFyIG1lcmdlRGVlcFByb3BlcnRpZXNLZXlzID0gWydoZWFkZXJzJywgJ2F1dGgnLCAncHJveHknLCAncGFyYW1zJ107XG4gIHZhciBkZWZhdWx0VG9Db25maWcyS2V5cyA9IFtcbiAgICAnYmFzZVVSTCcsICd0cmFuc2Zvcm1SZXF1ZXN0JywgJ3RyYW5zZm9ybVJlc3BvbnNlJywgJ3BhcmFtc1NlcmlhbGl6ZXInLFxuICAgICd0aW1lb3V0JywgJ3RpbWVvdXRNZXNzYWdlJywgJ3dpdGhDcmVkZW50aWFscycsICdhZGFwdGVyJywgJ3Jlc3BvbnNlVHlwZScsICd4c3JmQ29va2llTmFtZScsXG4gICAgJ3hzcmZIZWFkZXJOYW1lJywgJ29uVXBsb2FkUHJvZ3Jlc3MnLCAnb25Eb3dubG9hZFByb2dyZXNzJywgJ2RlY29tcHJlc3MnLFxuICAgICdtYXhDb250ZW50TGVuZ3RoJywgJ21heEJvZHlMZW5ndGgnLCAnbWF4UmVkaXJlY3RzJywgJ3RyYW5zcG9ydCcsICdodHRwQWdlbnQnLFxuICAgICdodHRwc0FnZW50JywgJ2NhbmNlbFRva2VuJywgJ3NvY2tldFBhdGgnLCAncmVzcG9uc2VFbmNvZGluZydcbiAgXTtcbiAgdmFyIGRpcmVjdE1lcmdlS2V5cyA9IFsndmFsaWRhdGVTdGF0dXMnXTtcblxuICBmdW5jdGlvbiBnZXRNZXJnZWRWYWx1ZSh0YXJnZXQsIHNvdXJjZSkge1xuICAgIGlmICh1dGlscy5pc1BsYWluT2JqZWN0KHRhcmdldCkgJiYgdXRpbHMuaXNQbGFpbk9iamVjdChzb3VyY2UpKSB7XG4gICAgICByZXR1cm4gdXRpbHMubWVyZ2UodGFyZ2V0LCBzb3VyY2UpO1xuICAgIH0gZWxzZSBpZiAodXRpbHMuaXNQbGFpbk9iamVjdChzb3VyY2UpKSB7XG4gICAgICByZXR1cm4gdXRpbHMubWVyZ2Uoe30sIHNvdXJjZSk7XG4gICAgfSBlbHNlIGlmICh1dGlscy5pc0FycmF5KHNvdXJjZSkpIHtcbiAgICAgIHJldHVybiBzb3VyY2Uuc2xpY2UoKTtcbiAgICB9XG4gICAgcmV0dXJuIHNvdXJjZTtcbiAgfVxuXG4gIGZ1bmN0aW9uIG1lcmdlRGVlcFByb3BlcnRpZXMocHJvcCkge1xuICAgIGlmICghdXRpbHMuaXNVbmRlZmluZWQoY29uZmlnMltwcm9wXSkpIHtcbiAgICAgIGNvbmZpZ1twcm9wXSA9IGdldE1lcmdlZFZhbHVlKGNvbmZpZzFbcHJvcF0sIGNvbmZpZzJbcHJvcF0pO1xuICAgIH0gZWxzZSBpZiAoIXV0aWxzLmlzVW5kZWZpbmVkKGNvbmZpZzFbcHJvcF0pKSB7XG4gICAgICBjb25maWdbcHJvcF0gPSBnZXRNZXJnZWRWYWx1ZSh1bmRlZmluZWQsIGNvbmZpZzFbcHJvcF0pO1xuICAgIH1cbiAgfVxuXG4gIHV0aWxzLmZvckVhY2godmFsdWVGcm9tQ29uZmlnMktleXMsIGZ1bmN0aW9uIHZhbHVlRnJvbUNvbmZpZzIocHJvcCkge1xuICAgIGlmICghdXRpbHMuaXNVbmRlZmluZWQoY29uZmlnMltwcm9wXSkpIHtcbiAgICAgIGNvbmZpZ1twcm9wXSA9IGdldE1lcmdlZFZhbHVlKHVuZGVmaW5lZCwgY29uZmlnMltwcm9wXSk7XG4gICAgfVxuICB9KTtcblxuICB1dGlscy5mb3JFYWNoKG1lcmdlRGVlcFByb3BlcnRpZXNLZXlzLCBtZXJnZURlZXBQcm9wZXJ0aWVzKTtcblxuICB1dGlscy5mb3JFYWNoKGRlZmF1bHRUb0NvbmZpZzJLZXlzLCBmdW5jdGlvbiBkZWZhdWx0VG9Db25maWcyKHByb3ApIHtcbiAgICBpZiAoIXV0aWxzLmlzVW5kZWZpbmVkKGNvbmZpZzJbcHJvcF0pKSB7XG4gICAgICBjb25maWdbcHJvcF0gPSBnZXRNZXJnZWRWYWx1ZSh1bmRlZmluZWQsIGNvbmZpZzJbcHJvcF0pO1xuICAgIH0gZWxzZSBpZiAoIXV0aWxzLmlzVW5kZWZpbmVkKGNvbmZpZzFbcHJvcF0pKSB7XG4gICAgICBjb25maWdbcHJvcF0gPSBnZXRNZXJnZWRWYWx1ZSh1bmRlZmluZWQsIGNvbmZpZzFbcHJvcF0pO1xuICAgIH1cbiAgfSk7XG5cbiAgdXRpbHMuZm9yRWFjaChkaXJlY3RNZXJnZUtleXMsIGZ1bmN0aW9uIG1lcmdlKHByb3ApIHtcbiAgICBpZiAocHJvcCBpbiBjb25maWcyKSB7XG4gICAgICBjb25maWdbcHJvcF0gPSBnZXRNZXJnZWRWYWx1ZShjb25maWcxW3Byb3BdLCBjb25maWcyW3Byb3BdKTtcbiAgICB9IGVsc2UgaWYgKHByb3AgaW4gY29uZmlnMSkge1xuICAgICAgY29uZmlnW3Byb3BdID0gZ2V0TWVyZ2VkVmFsdWUodW5kZWZpbmVkLCBjb25maWcxW3Byb3BdKTtcbiAgICB9XG4gIH0pO1xuXG4gIHZhciBheGlvc0tleXMgPSB2YWx1ZUZyb21Db25maWcyS2V5c1xuICAgIC5jb25jYXQobWVyZ2VEZWVwUHJvcGVydGllc0tleXMpXG4gICAgLmNvbmNhdChkZWZhdWx0VG9Db25maWcyS2V5cylcbiAgICAuY29uY2F0KGRpcmVjdE1lcmdlS2V5cyk7XG5cbiAgdmFyIG90aGVyS2V5cyA9IE9iamVjdFxuICAgIC5rZXlzKGNvbmZpZzEpXG4gICAgLmNvbmNhdChPYmplY3Qua2V5cyhjb25maWcyKSlcbiAgICAuZmlsdGVyKGZ1bmN0aW9uIGZpbHRlckF4aW9zS2V5cyhrZXkpIHtcbiAgICAgIHJldHVybiBheGlvc0tleXMuaW5kZXhPZihrZXkpID09PSAtMTtcbiAgICB9KTtcblxuICB1dGlscy5mb3JFYWNoKG90aGVyS2V5cywgbWVyZ2VEZWVwUHJvcGVydGllcyk7XG5cbiAgcmV0dXJuIGNvbmZpZztcbn07XG4iLCIndXNlIHN0cmljdCc7XG5cbnZhciBjcmVhdGVFcnJvciA9IHJlcXVpcmUoJy4vY3JlYXRlRXJyb3InKTtcblxuLyoqXG4gKiBSZXNvbHZlIG9yIHJlamVjdCBhIFByb21pc2UgYmFzZWQgb24gcmVzcG9uc2Ugc3RhdHVzLlxuICpcbiAqIEBwYXJhbSB7RnVuY3Rpb259IHJlc29sdmUgQSBmdW5jdGlvbiB0aGF0IHJlc29sdmVzIHRoZSBwcm9taXNlLlxuICogQHBhcmFtIHtGdW5jdGlvbn0gcmVqZWN0IEEgZnVuY3Rpb24gdGhhdCByZWplY3RzIHRoZSBwcm9taXNlLlxuICogQHBhcmFtIHtvYmplY3R9IHJlc3BvbnNlIFRoZSByZXNwb25zZS5cbiAqL1xubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbiBzZXR0bGUocmVzb2x2ZSwgcmVqZWN0LCByZXNwb25zZSkge1xuICB2YXIgdmFsaWRhdGVTdGF0dXMgPSByZXNwb25zZS5jb25maWcudmFsaWRhdGVTdGF0dXM7XG4gIGlmICghcmVzcG9uc2Uuc3RhdHVzIHx8ICF2YWxpZGF0ZVN0YXR1cyB8fCB2YWxpZGF0ZVN0YXR1cyhyZXNwb25zZS5zdGF0dXMpKSB7XG4gICAgcmVzb2x2ZShyZXNwb25zZSk7XG4gIH0gZWxzZSB7XG4gICAgcmVqZWN0KGNyZWF0ZUVycm9yKFxuICAgICAgJ1JlcXVlc3QgZmFpbGVkIHdpdGggc3RhdHVzIGNvZGUgJyArIHJlc3BvbnNlLnN0YXR1cyxcbiAgICAgIHJlc3BvbnNlLmNvbmZpZyxcbiAgICAgIG51bGwsXG4gICAgICByZXNwb25zZS5yZXF1ZXN0LFxuICAgICAgcmVzcG9uc2VcbiAgICApKTtcbiAgfVxufTtcbiIsIid1c2Ugc3RyaWN0JztcblxudmFyIHV0aWxzID0gcmVxdWlyZSgnLi8uLi91dGlscycpO1xudmFyIGRlZmF1bHRzID0gcmVxdWlyZSgnLi8uLi9kZWZhdWx0cycpO1xuXG4vKipcbiAqIFRyYW5zZm9ybSB0aGUgZGF0YSBmb3IgYSByZXF1ZXN0IG9yIGEgcmVzcG9uc2VcbiAqXG4gKiBAcGFyYW0ge09iamVjdHxTdHJpbmd9IGRhdGEgVGhlIGRhdGEgdG8gYmUgdHJhbnNmb3JtZWRcbiAqIEBwYXJhbSB7QXJyYXl9IGhlYWRlcnMgVGhlIGhlYWRlcnMgZm9yIHRoZSByZXF1ZXN0IG9yIHJlc3BvbnNlXG4gKiBAcGFyYW0ge0FycmF5fEZ1bmN0aW9ufSBmbnMgQSBzaW5nbGUgZnVuY3Rpb24gb3IgQXJyYXkgb2YgZnVuY3Rpb25zXG4gKiBAcmV0dXJucyB7Kn0gVGhlIHJlc3VsdGluZyB0cmFuc2Zvcm1lZCBkYXRhXG4gKi9cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24gdHJhbnNmb3JtRGF0YShkYXRhLCBoZWFkZXJzLCBmbnMpIHtcbiAgdmFyIGNvbnRleHQgPSB0aGlzIHx8IGRlZmF1bHRzO1xuICAvKmVzbGludCBuby1wYXJhbS1yZWFzc2lnbjowKi9cbiAgdXRpbHMuZm9yRWFjaChmbnMsIGZ1bmN0aW9uIHRyYW5zZm9ybShmbikge1xuICAgIGRhdGEgPSBmbi5jYWxsKGNvbnRleHQsIGRhdGEsIGhlYWRlcnMpO1xuICB9KTtcblxuICByZXR1cm4gZGF0YTtcbn07XG4iLCIndXNlIHN0cmljdCc7XG5cbnZhciB1dGlscyA9IHJlcXVpcmUoJy4vdXRpbHMnKTtcbnZhciBub3JtYWxpemVIZWFkZXJOYW1lID0gcmVxdWlyZSgnLi9oZWxwZXJzL25vcm1hbGl6ZUhlYWRlck5hbWUnKTtcbnZhciBlbmhhbmNlRXJyb3IgPSByZXF1aXJlKCcuL2NvcmUvZW5oYW5jZUVycm9yJyk7XG5cbnZhciBERUZBVUxUX0NPTlRFTlRfVFlQRSA9IHtcbiAgJ0NvbnRlbnQtVHlwZSc6ICdhcHBsaWNhdGlvbi94LXd3dy1mb3JtLXVybGVuY29kZWQnXG59O1xuXG5mdW5jdGlvbiBzZXRDb250ZW50VHlwZUlmVW5zZXQoaGVhZGVycywgdmFsdWUpIHtcbiAgaWYgKCF1dGlscy5pc1VuZGVmaW5lZChoZWFkZXJzKSAmJiB1dGlscy5pc1VuZGVmaW5lZChoZWFkZXJzWydDb250ZW50LVR5cGUnXSkpIHtcbiAgICBoZWFkZXJzWydDb250ZW50LVR5cGUnXSA9IHZhbHVlO1xuICB9XG59XG5cbmZ1bmN0aW9uIGdldERlZmF1bHRBZGFwdGVyKCkge1xuICB2YXIgYWRhcHRlcjtcbiAgaWYgKHR5cGVvZiBYTUxIdHRwUmVxdWVzdCAhPT0gJ3VuZGVmaW5lZCcpIHtcbiAgICAvLyBGb3IgYnJvd3NlcnMgdXNlIFhIUiBhZGFwdGVyXG4gICAgYWRhcHRlciA9IHJlcXVpcmUoJy4vYWRhcHRlcnMveGhyJyk7XG4gIH0gZWxzZSBpZiAodHlwZW9mIHByb2Nlc3MgIT09ICd1bmRlZmluZWQnICYmIE9iamVjdC5wcm90b3R5cGUudG9TdHJpbmcuY2FsbChwcm9jZXNzKSA9PT0gJ1tvYmplY3QgcHJvY2Vzc10nKSB7XG4gICAgLy8gRm9yIG5vZGUgdXNlIEhUVFAgYWRhcHRlclxuICAgIGFkYXB0ZXIgPSByZXF1aXJlKCcuL2FkYXB0ZXJzL2h0dHAnKTtcbiAgfVxuICByZXR1cm4gYWRhcHRlcjtcbn1cblxuZnVuY3Rpb24gc3RyaW5naWZ5U2FmZWx5KHJhd1ZhbHVlLCBwYXJzZXIsIGVuY29kZXIpIHtcbiAgaWYgKHV0aWxzLmlzU3RyaW5nKHJhd1ZhbHVlKSkge1xuICAgIHRyeSB7XG4gICAgICAocGFyc2VyIHx8IEpTT04ucGFyc2UpKHJhd1ZhbHVlKTtcbiAgICAgIHJldHVybiB1dGlscy50cmltKHJhd1ZhbHVlKTtcbiAgICB9IGNhdGNoIChlKSB7XG4gICAgICBpZiAoZS5uYW1lICE9PSAnU3ludGF4RXJyb3InKSB7XG4gICAgICAgIHRocm93IGU7XG4gICAgICB9XG4gICAgfVxuICB9XG5cbiAgcmV0dXJuIChlbmNvZGVyIHx8IEpTT04uc3RyaW5naWZ5KShyYXdWYWx1ZSk7XG59XG5cbnZhciBkZWZhdWx0cyA9IHtcblxuICB0cmFuc2l0aW9uYWw6IHtcbiAgICBzaWxlbnRKU09OUGFyc2luZzogdHJ1ZSxcbiAgICBmb3JjZWRKU09OUGFyc2luZzogdHJ1ZSxcbiAgICBjbGFyaWZ5VGltZW91dEVycm9yOiBmYWxzZVxuICB9LFxuXG4gIGFkYXB0ZXI6IGdldERlZmF1bHRBZGFwdGVyKCksXG5cbiAgdHJhbnNmb3JtUmVxdWVzdDogW2Z1bmN0aW9uIHRyYW5zZm9ybVJlcXVlc3QoZGF0YSwgaGVhZGVycykge1xuICAgIG5vcm1hbGl6ZUhlYWRlck5hbWUoaGVhZGVycywgJ0FjY2VwdCcpO1xuICAgIG5vcm1hbGl6ZUhlYWRlck5hbWUoaGVhZGVycywgJ0NvbnRlbnQtVHlwZScpO1xuXG4gICAgaWYgKHV0aWxzLmlzRm9ybURhdGEoZGF0YSkgfHxcbiAgICAgIHV0aWxzLmlzQXJyYXlCdWZmZXIoZGF0YSkgfHxcbiAgICAgIHV0aWxzLmlzQnVmZmVyKGRhdGEpIHx8XG4gICAgICB1dGlscy5pc1N0cmVhbShkYXRhKSB8fFxuICAgICAgdXRpbHMuaXNGaWxlKGRhdGEpIHx8XG4gICAgICB1dGlscy5pc0Jsb2IoZGF0YSlcbiAgICApIHtcbiAgICAgIHJldHVybiBkYXRhO1xuICAgIH1cbiAgICBpZiAodXRpbHMuaXNBcnJheUJ1ZmZlclZpZXcoZGF0YSkpIHtcbiAgICAgIHJldHVybiBkYXRhLmJ1ZmZlcjtcbiAgICB9XG4gICAgaWYgKHV0aWxzLmlzVVJMU2VhcmNoUGFyYW1zKGRhdGEpKSB7XG4gICAgICBzZXRDb250ZW50VHlwZUlmVW5zZXQoaGVhZGVycywgJ2FwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZDtjaGFyc2V0PXV0Zi04Jyk7XG4gICAgICByZXR1cm4gZGF0YS50b1N0cmluZygpO1xuICAgIH1cbiAgICBpZiAodXRpbHMuaXNPYmplY3QoZGF0YSkgfHwgKGhlYWRlcnMgJiYgaGVhZGVyc1snQ29udGVudC1UeXBlJ10gPT09ICdhcHBsaWNhdGlvbi9qc29uJykpIHtcbiAgICAgIHNldENvbnRlbnRUeXBlSWZVbnNldChoZWFkZXJzLCAnYXBwbGljYXRpb24vanNvbicpO1xuICAgICAgcmV0dXJuIHN0cmluZ2lmeVNhZmVseShkYXRhKTtcbiAgICB9XG4gICAgcmV0dXJuIGRhdGE7XG4gIH1dLFxuXG4gIHRyYW5zZm9ybVJlc3BvbnNlOiBbZnVuY3Rpb24gdHJhbnNmb3JtUmVzcG9uc2UoZGF0YSkge1xuICAgIHZhciB0cmFuc2l0aW9uYWwgPSB0aGlzLnRyYW5zaXRpb25hbDtcbiAgICB2YXIgc2lsZW50SlNPTlBhcnNpbmcgPSB0cmFuc2l0aW9uYWwgJiYgdHJhbnNpdGlvbmFsLnNpbGVudEpTT05QYXJzaW5nO1xuICAgIHZhciBmb3JjZWRKU09OUGFyc2luZyA9IHRyYW5zaXRpb25hbCAmJiB0cmFuc2l0aW9uYWwuZm9yY2VkSlNPTlBhcnNpbmc7XG4gICAgdmFyIHN0cmljdEpTT05QYXJzaW5nID0gIXNpbGVudEpTT05QYXJzaW5nICYmIHRoaXMucmVzcG9uc2VUeXBlID09PSAnanNvbic7XG5cbiAgICBpZiAoc3RyaWN0SlNPTlBhcnNpbmcgfHwgKGZvcmNlZEpTT05QYXJzaW5nICYmIHV0aWxzLmlzU3RyaW5nKGRhdGEpICYmIGRhdGEubGVuZ3RoKSkge1xuICAgICAgdHJ5IHtcbiAgICAgICAgcmV0dXJuIEpTT04ucGFyc2UoZGF0YSk7XG4gICAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgIGlmIChzdHJpY3RKU09OUGFyc2luZykge1xuICAgICAgICAgIGlmIChlLm5hbWUgPT09ICdTeW50YXhFcnJvcicpIHtcbiAgICAgICAgICAgIHRocm93IGVuaGFuY2VFcnJvcihlLCB0aGlzLCAnRV9KU09OX1BBUlNFJyk7XG4gICAgICAgICAgfVxuICAgICAgICAgIHRocm93IGU7XG4gICAgICAgIH1cbiAgICAgIH1cbiAgICB9XG5cbiAgICByZXR1cm4gZGF0YTtcbiAgfV0sXG5cbiAgLyoqXG4gICAqIEEgdGltZW91dCBpbiBtaWxsaXNlY29uZHMgdG8gYWJvcnQgYSByZXF1ZXN0LiBJZiBzZXQgdG8gMCAoZGVmYXVsdCkgYVxuICAgKiB0aW1lb3V0IGlzIG5vdCBjcmVhdGVkLlxuICAgKi9cbiAgdGltZW91dDogMCxcblxuICB4c3JmQ29va2llTmFtZTogJ1hTUkYtVE9LRU4nLFxuICB4c3JmSGVhZGVyTmFtZTogJ1gtWFNSRi1UT0tFTicsXG5cbiAgbWF4Q29udGVudExlbmd0aDogLTEsXG4gIG1heEJvZHlMZW5ndGg6IC0xLFxuXG4gIHZhbGlkYXRlU3RhdHVzOiBmdW5jdGlvbiB2YWxpZGF0ZVN0YXR1cyhzdGF0dXMpIHtcbiAgICByZXR1cm4gc3RhdHVzID49IDIwMCAmJiBzdGF0dXMgPCAzMDA7XG4gIH1cbn07XG5cbmRlZmF1bHRzLmhlYWRlcnMgPSB7XG4gIGNvbW1vbjoge1xuICAgICdBY2NlcHQnOiAnYXBwbGljYXRpb24vanNvbiwgdGV4dC9wbGFpbiwgKi8qJ1xuICB9XG59O1xuXG51dGlscy5mb3JFYWNoKFsnZGVsZXRlJywgJ2dldCcsICdoZWFkJ10sIGZ1bmN0aW9uIGZvckVhY2hNZXRob2ROb0RhdGEobWV0aG9kKSB7XG4gIGRlZmF1bHRzLmhlYWRlcnNbbWV0aG9kXSA9IHt9O1xufSk7XG5cbnV0aWxzLmZvckVhY2goWydwb3N0JywgJ3B1dCcsICdwYXRjaCddLCBmdW5jdGlvbiBmb3JFYWNoTWV0aG9kV2l0aERhdGEobWV0aG9kKSB7XG4gIGRlZmF1bHRzLmhlYWRlcnNbbWV0aG9kXSA9IHV0aWxzLm1lcmdlKERFRkFVTFRfQ09OVEVOVF9UWVBFKTtcbn0pO1xuXG5tb2R1bGUuZXhwb3J0cyA9IGRlZmF1bHRzO1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uIGJpbmQoZm4sIHRoaXNBcmcpIHtcbiAgcmV0dXJuIGZ1bmN0aW9uIHdyYXAoKSB7XG4gICAgdmFyIGFyZ3MgPSBuZXcgQXJyYXkoYXJndW1lbnRzLmxlbmd0aCk7XG4gICAgZm9yICh2YXIgaSA9IDA7IGkgPCBhcmdzLmxlbmd0aDsgaSsrKSB7XG4gICAgICBhcmdzW2ldID0gYXJndW1lbnRzW2ldO1xuICAgIH1cbiAgICByZXR1cm4gZm4uYXBwbHkodGhpc0FyZywgYXJncyk7XG4gIH07XG59O1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG52YXIgdXRpbHMgPSByZXF1aXJlKCcuLy4uL3V0aWxzJyk7XG5cbmZ1bmN0aW9uIGVuY29kZSh2YWwpIHtcbiAgcmV0dXJuIGVuY29kZVVSSUNvbXBvbmVudCh2YWwpLlxuICAgIHJlcGxhY2UoLyUzQS9naSwgJzonKS5cbiAgICByZXBsYWNlKC8lMjQvZywgJyQnKS5cbiAgICByZXBsYWNlKC8lMkMvZ2ksICcsJykuXG4gICAgcmVwbGFjZSgvJTIwL2csICcrJykuXG4gICAgcmVwbGFjZSgvJTVCL2dpLCAnWycpLlxuICAgIHJlcGxhY2UoLyU1RC9naSwgJ10nKTtcbn1cblxuLyoqXG4gKiBCdWlsZCBhIFVSTCBieSBhcHBlbmRpbmcgcGFyYW1zIHRvIHRoZSBlbmRcbiAqXG4gKiBAcGFyYW0ge3N0cmluZ30gdXJsIFRoZSBiYXNlIG9mIHRoZSB1cmwgKGUuZy4sIGh0dHA6Ly93d3cuZ29vZ2xlLmNvbSlcbiAqIEBwYXJhbSB7b2JqZWN0fSBbcGFyYW1zXSBUaGUgcGFyYW1zIHRvIGJlIGFwcGVuZGVkXG4gKiBAcmV0dXJucyB7c3RyaW5nfSBUaGUgZm9ybWF0dGVkIHVybFxuICovXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uIGJ1aWxkVVJMKHVybCwgcGFyYW1zLCBwYXJhbXNTZXJpYWxpemVyKSB7XG4gIC8qZXNsaW50IG5vLXBhcmFtLXJlYXNzaWduOjAqL1xuICBpZiAoIXBhcmFtcykge1xuICAgIHJldHVybiB1cmw7XG4gIH1cblxuICB2YXIgc2VyaWFsaXplZFBhcmFtcztcbiAgaWYgKHBhcmFtc1NlcmlhbGl6ZXIpIHtcbiAgICBzZXJpYWxpemVkUGFyYW1zID0gcGFyYW1zU2VyaWFsaXplcihwYXJhbXMpO1xuICB9IGVsc2UgaWYgKHV0aWxzLmlzVVJMU2VhcmNoUGFyYW1zKHBhcmFtcykpIHtcbiAgICBzZXJpYWxpemVkUGFyYW1zID0gcGFyYW1zLnRvU3RyaW5nKCk7XG4gIH0gZWxzZSB7XG4gICAgdmFyIHBhcnRzID0gW107XG5cbiAgICB1dGlscy5mb3JFYWNoKHBhcmFtcywgZnVuY3Rpb24gc2VyaWFsaXplKHZhbCwga2V5KSB7XG4gICAgICBpZiAodmFsID09PSBudWxsIHx8IHR5cGVvZiB2YWwgPT09ICd1bmRlZmluZWQnKSB7XG4gICAgICAgIHJldHVybjtcbiAgICAgIH1cblxuICAgICAgaWYgKHV0aWxzLmlzQXJyYXkodmFsKSkge1xuICAgICAgICBrZXkgPSBrZXkgKyAnW10nO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgdmFsID0gW3ZhbF07XG4gICAgICB9XG5cbiAgICAgIHV0aWxzLmZvckVhY2godmFsLCBmdW5jdGlvbiBwYXJzZVZhbHVlKHYpIHtcbiAgICAgICAgaWYgKHV0aWxzLmlzRGF0ZSh2KSkge1xuICAgICAgICAgIHYgPSB2LnRvSVNPU3RyaW5nKCk7XG4gICAgICAgIH0gZWxzZSBpZiAodXRpbHMuaXNPYmplY3QodikpIHtcbiAgICAgICAgICB2ID0gSlNPTi5zdHJpbmdpZnkodik7XG4gICAgICAgIH1cbiAgICAgICAgcGFydHMucHVzaChlbmNvZGUoa2V5KSArICc9JyArIGVuY29kZSh2KSk7XG4gICAgICB9KTtcbiAgICB9KTtcblxuICAgIHNlcmlhbGl6ZWRQYXJhbXMgPSBwYXJ0cy5qb2luKCcmJyk7XG4gIH1cblxuICBpZiAoc2VyaWFsaXplZFBhcmFtcykge1xuICAgIHZhciBoYXNobWFya0luZGV4ID0gdXJsLmluZGV4T2YoJyMnKTtcbiAgICBpZiAoaGFzaG1hcmtJbmRleCAhPT0gLTEpIHtcbiAgICAgIHVybCA9IHVybC5zbGljZSgwLCBoYXNobWFya0luZGV4KTtcbiAgICB9XG5cbiAgICB1cmwgKz0gKHVybC5pbmRleE9mKCc/JykgPT09IC0xID8gJz8nIDogJyYnKSArIHNlcmlhbGl6ZWRQYXJhbXM7XG4gIH1cblxuICByZXR1cm4gdXJsO1xufTtcbiIsIid1c2Ugc3RyaWN0JztcblxuLyoqXG4gKiBDcmVhdGVzIGEgbmV3IFVSTCBieSBjb21iaW5pbmcgdGhlIHNwZWNpZmllZCBVUkxzXG4gKlxuICogQHBhcmFtIHtzdHJpbmd9IGJhc2VVUkwgVGhlIGJhc2UgVVJMXG4gKiBAcGFyYW0ge3N0cmluZ30gcmVsYXRpdmVVUkwgVGhlIHJlbGF0aXZlIFVSTFxuICogQHJldHVybnMge3N0cmluZ30gVGhlIGNvbWJpbmVkIFVSTFxuICovXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uIGNvbWJpbmVVUkxzKGJhc2VVUkwsIHJlbGF0aXZlVVJMKSB7XG4gIHJldHVybiByZWxhdGl2ZVVSTFxuICAgID8gYmFzZVVSTC5yZXBsYWNlKC9cXC8rJC8sICcnKSArICcvJyArIHJlbGF0aXZlVVJMLnJlcGxhY2UoL15cXC8rLywgJycpXG4gICAgOiBiYXNlVVJMO1xufTtcbiIsIid1c2Ugc3RyaWN0JztcblxudmFyIHV0aWxzID0gcmVxdWlyZSgnLi8uLi91dGlscycpO1xuXG5tb2R1bGUuZXhwb3J0cyA9IChcbiAgdXRpbHMuaXNTdGFuZGFyZEJyb3dzZXJFbnYoKSA/XG5cbiAgLy8gU3RhbmRhcmQgYnJvd3NlciBlbnZzIHN1cHBvcnQgZG9jdW1lbnQuY29va2llXG4gICAgKGZ1bmN0aW9uIHN0YW5kYXJkQnJvd3NlckVudigpIHtcbiAgICAgIHJldHVybiB7XG4gICAgICAgIHdyaXRlOiBmdW5jdGlvbiB3cml0ZShuYW1lLCB2YWx1ZSwgZXhwaXJlcywgcGF0aCwgZG9tYWluLCBzZWN1cmUpIHtcbiAgICAgICAgICB2YXIgY29va2llID0gW107XG4gICAgICAgICAgY29va2llLnB1c2gobmFtZSArICc9JyArIGVuY29kZVVSSUNvbXBvbmVudCh2YWx1ZSkpO1xuXG4gICAgICAgICAgaWYgKHV0aWxzLmlzTnVtYmVyKGV4cGlyZXMpKSB7XG4gICAgICAgICAgICBjb29raWUucHVzaCgnZXhwaXJlcz0nICsgbmV3IERhdGUoZXhwaXJlcykudG9HTVRTdHJpbmcoKSk7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgaWYgKHV0aWxzLmlzU3RyaW5nKHBhdGgpKSB7XG4gICAgICAgICAgICBjb29raWUucHVzaCgncGF0aD0nICsgcGF0aCk7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgaWYgKHV0aWxzLmlzU3RyaW5nKGRvbWFpbikpIHtcbiAgICAgICAgICAgIGNvb2tpZS5wdXNoKCdkb21haW49JyArIGRvbWFpbik7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgaWYgKHNlY3VyZSA9PT0gdHJ1ZSkge1xuICAgICAgICAgICAgY29va2llLnB1c2goJ3NlY3VyZScpO1xuICAgICAgICAgIH1cblxuICAgICAgICAgIGRvY3VtZW50LmNvb2tpZSA9IGNvb2tpZS5qb2luKCc7ICcpO1xuICAgICAgICB9LFxuXG4gICAgICAgIHJlYWQ6IGZ1bmN0aW9uIHJlYWQobmFtZSkge1xuICAgICAgICAgIHZhciBtYXRjaCA9IGRvY3VtZW50LmNvb2tpZS5tYXRjaChuZXcgUmVnRXhwKCcoXnw7XFxcXHMqKSgnICsgbmFtZSArICcpPShbXjtdKiknKSk7XG4gICAgICAgICAgcmV0dXJuIChtYXRjaCA/IGRlY29kZVVSSUNvbXBvbmVudChtYXRjaFszXSkgOiBudWxsKTtcbiAgICAgICAgfSxcblxuICAgICAgICByZW1vdmU6IGZ1bmN0aW9uIHJlbW92ZShuYW1lKSB7XG4gICAgICAgICAgdGhpcy53cml0ZShuYW1lLCAnJywgRGF0ZS5ub3coKSAtIDg2NDAwMDAwKTtcbiAgICAgICAgfVxuICAgICAgfTtcbiAgICB9KSgpIDpcblxuICAvLyBOb24gc3RhbmRhcmQgYnJvd3NlciBlbnYgKHdlYiB3b3JrZXJzLCByZWFjdC1uYXRpdmUpIGxhY2sgbmVlZGVkIHN1cHBvcnQuXG4gICAgKGZ1bmN0aW9uIG5vblN0YW5kYXJkQnJvd3NlckVudigpIHtcbiAgICAgIHJldHVybiB7XG4gICAgICAgIHdyaXRlOiBmdW5jdGlvbiB3cml0ZSgpIHt9LFxuICAgICAgICByZWFkOiBmdW5jdGlvbiByZWFkKCkgeyByZXR1cm4gbnVsbDsgfSxcbiAgICAgICAgcmVtb3ZlOiBmdW5jdGlvbiByZW1vdmUoKSB7fVxuICAgICAgfTtcbiAgICB9KSgpXG4pO1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG4vKipcbiAqIERldGVybWluZXMgd2hldGhlciB0aGUgc3BlY2lmaWVkIFVSTCBpcyBhYnNvbHV0ZVxuICpcbiAqIEBwYXJhbSB7c3RyaW5nfSB1cmwgVGhlIFVSTCB0byB0ZXN0XG4gKiBAcmV0dXJucyB7Ym9vbGVhbn0gVHJ1ZSBpZiB0aGUgc3BlY2lmaWVkIFVSTCBpcyBhYnNvbHV0ZSwgb3RoZXJ3aXNlIGZhbHNlXG4gKi9cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24gaXNBYnNvbHV0ZVVSTCh1cmwpIHtcbiAgLy8gQSBVUkwgaXMgY29uc2lkZXJlZCBhYnNvbHV0ZSBpZiBpdCBiZWdpbnMgd2l0aCBcIjxzY2hlbWU+Oi8vXCIgb3IgXCIvL1wiIChwcm90b2NvbC1yZWxhdGl2ZSBVUkwpLlxuICAvLyBSRkMgMzk4NiBkZWZpbmVzIHNjaGVtZSBuYW1lIGFzIGEgc2VxdWVuY2Ugb2YgY2hhcmFjdGVycyBiZWdpbm5pbmcgd2l0aCBhIGxldHRlciBhbmQgZm9sbG93ZWRcbiAgLy8gYnkgYW55IGNvbWJpbmF0aW9uIG9mIGxldHRlcnMsIGRpZ2l0cywgcGx1cywgcGVyaW9kLCBvciBoeXBoZW4uXG4gIHJldHVybiAvXihbYS16XVthLXpcXGRcXCtcXC1cXC5dKjopP1xcL1xcLy9pLnRlc3QodXJsKTtcbn07XG4iLCIndXNlIHN0cmljdCc7XG5cbi8qKlxuICogRGV0ZXJtaW5lcyB3aGV0aGVyIHRoZSBwYXlsb2FkIGlzIGFuIGVycm9yIHRocm93biBieSBBeGlvc1xuICpcbiAqIEBwYXJhbSB7Kn0gcGF5bG9hZCBUaGUgdmFsdWUgdG8gdGVzdFxuICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdGhlIHBheWxvYWQgaXMgYW4gZXJyb3IgdGhyb3duIGJ5IEF4aW9zLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbiBpc0F4aW9zRXJyb3IocGF5bG9hZCkge1xuICByZXR1cm4gKHR5cGVvZiBwYXlsb2FkID09PSAnb2JqZWN0JykgJiYgKHBheWxvYWQuaXNBeGlvc0Vycm9yID09PSB0cnVlKTtcbn07XG4iLCIndXNlIHN0cmljdCc7XG5cbnZhciB1dGlscyA9IHJlcXVpcmUoJy4vLi4vdXRpbHMnKTtcblxubW9kdWxlLmV4cG9ydHMgPSAoXG4gIHV0aWxzLmlzU3RhbmRhcmRCcm93c2VyRW52KCkgP1xuXG4gIC8vIFN0YW5kYXJkIGJyb3dzZXIgZW52cyBoYXZlIGZ1bGwgc3VwcG9ydCBvZiB0aGUgQVBJcyBuZWVkZWQgdG8gdGVzdFxuICAvLyB3aGV0aGVyIHRoZSByZXF1ZXN0IFVSTCBpcyBvZiB0aGUgc2FtZSBvcmlnaW4gYXMgY3VycmVudCBsb2NhdGlvbi5cbiAgICAoZnVuY3Rpb24gc3RhbmRhcmRCcm93c2VyRW52KCkge1xuICAgICAgdmFyIG1zaWUgPSAvKG1zaWV8dHJpZGVudCkvaS50ZXN0KG5hdmlnYXRvci51c2VyQWdlbnQpO1xuICAgICAgdmFyIHVybFBhcnNpbmdOb2RlID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnYScpO1xuICAgICAgdmFyIG9yaWdpblVSTDtcblxuICAgICAgLyoqXG4gICAgKiBQYXJzZSBhIFVSTCB0byBkaXNjb3ZlciBpdCdzIGNvbXBvbmVudHNcbiAgICAqXG4gICAgKiBAcGFyYW0ge1N0cmluZ30gdXJsIFRoZSBVUkwgdG8gYmUgcGFyc2VkXG4gICAgKiBAcmV0dXJucyB7T2JqZWN0fVxuICAgICovXG4gICAgICBmdW5jdGlvbiByZXNvbHZlVVJMKHVybCkge1xuICAgICAgICB2YXIgaHJlZiA9IHVybDtcblxuICAgICAgICBpZiAobXNpZSkge1xuICAgICAgICAvLyBJRSBuZWVkcyBhdHRyaWJ1dGUgc2V0IHR3aWNlIHRvIG5vcm1hbGl6ZSBwcm9wZXJ0aWVzXG4gICAgICAgICAgdXJsUGFyc2luZ05vZGUuc2V0QXR0cmlidXRlKCdocmVmJywgaHJlZik7XG4gICAgICAgICAgaHJlZiA9IHVybFBhcnNpbmdOb2RlLmhyZWY7XG4gICAgICAgIH1cblxuICAgICAgICB1cmxQYXJzaW5nTm9kZS5zZXRBdHRyaWJ1dGUoJ2hyZWYnLCBocmVmKTtcblxuICAgICAgICAvLyB1cmxQYXJzaW5nTm9kZSBwcm92aWRlcyB0aGUgVXJsVXRpbHMgaW50ZXJmYWNlIC0gaHR0cDovL3VybC5zcGVjLndoYXR3Zy5vcmcvI3VybHV0aWxzXG4gICAgICAgIHJldHVybiB7XG4gICAgICAgICAgaHJlZjogdXJsUGFyc2luZ05vZGUuaHJlZixcbiAgICAgICAgICBwcm90b2NvbDogdXJsUGFyc2luZ05vZGUucHJvdG9jb2wgPyB1cmxQYXJzaW5nTm9kZS5wcm90b2NvbC5yZXBsYWNlKC86JC8sICcnKSA6ICcnLFxuICAgICAgICAgIGhvc3Q6IHVybFBhcnNpbmdOb2RlLmhvc3QsXG4gICAgICAgICAgc2VhcmNoOiB1cmxQYXJzaW5nTm9kZS5zZWFyY2ggPyB1cmxQYXJzaW5nTm9kZS5zZWFyY2gucmVwbGFjZSgvXlxcPy8sICcnKSA6ICcnLFxuICAgICAgICAgIGhhc2g6IHVybFBhcnNpbmdOb2RlLmhhc2ggPyB1cmxQYXJzaW5nTm9kZS5oYXNoLnJlcGxhY2UoL14jLywgJycpIDogJycsXG4gICAgICAgICAgaG9zdG5hbWU6IHVybFBhcnNpbmdOb2RlLmhvc3RuYW1lLFxuICAgICAgICAgIHBvcnQ6IHVybFBhcnNpbmdOb2RlLnBvcnQsXG4gICAgICAgICAgcGF0aG5hbWU6ICh1cmxQYXJzaW5nTm9kZS5wYXRobmFtZS5jaGFyQXQoMCkgPT09ICcvJykgP1xuICAgICAgICAgICAgdXJsUGFyc2luZ05vZGUucGF0aG5hbWUgOlxuICAgICAgICAgICAgJy8nICsgdXJsUGFyc2luZ05vZGUucGF0aG5hbWVcbiAgICAgICAgfTtcbiAgICAgIH1cblxuICAgICAgb3JpZ2luVVJMID0gcmVzb2x2ZVVSTCh3aW5kb3cubG9jYXRpb24uaHJlZik7XG5cbiAgICAgIC8qKlxuICAgICogRGV0ZXJtaW5lIGlmIGEgVVJMIHNoYXJlcyB0aGUgc2FtZSBvcmlnaW4gYXMgdGhlIGN1cnJlbnQgbG9jYXRpb25cbiAgICAqXG4gICAgKiBAcGFyYW0ge1N0cmluZ30gcmVxdWVzdFVSTCBUaGUgVVJMIHRvIHRlc3RcbiAgICAqIEByZXR1cm5zIHtib29sZWFufSBUcnVlIGlmIFVSTCBzaGFyZXMgdGhlIHNhbWUgb3JpZ2luLCBvdGhlcndpc2UgZmFsc2VcbiAgICAqL1xuICAgICAgcmV0dXJuIGZ1bmN0aW9uIGlzVVJMU2FtZU9yaWdpbihyZXF1ZXN0VVJMKSB7XG4gICAgICAgIHZhciBwYXJzZWQgPSAodXRpbHMuaXNTdHJpbmcocmVxdWVzdFVSTCkpID8gcmVzb2x2ZVVSTChyZXF1ZXN0VVJMKSA6IHJlcXVlc3RVUkw7XG4gICAgICAgIHJldHVybiAocGFyc2VkLnByb3RvY29sID09PSBvcmlnaW5VUkwucHJvdG9jb2wgJiZcbiAgICAgICAgICAgIHBhcnNlZC5ob3N0ID09PSBvcmlnaW5VUkwuaG9zdCk7XG4gICAgICB9O1xuICAgIH0pKCkgOlxuXG4gIC8vIE5vbiBzdGFuZGFyZCBicm93c2VyIGVudnMgKHdlYiB3b3JrZXJzLCByZWFjdC1uYXRpdmUpIGxhY2sgbmVlZGVkIHN1cHBvcnQuXG4gICAgKGZ1bmN0aW9uIG5vblN0YW5kYXJkQnJvd3NlckVudigpIHtcbiAgICAgIHJldHVybiBmdW5jdGlvbiBpc1VSTFNhbWVPcmlnaW4oKSB7XG4gICAgICAgIHJldHVybiB0cnVlO1xuICAgICAgfTtcbiAgICB9KSgpXG4pO1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG52YXIgdXRpbHMgPSByZXF1aXJlKCcuLi91dGlscycpO1xuXG5tb2R1bGUuZXhwb3J0cyA9IGZ1bmN0aW9uIG5vcm1hbGl6ZUhlYWRlck5hbWUoaGVhZGVycywgbm9ybWFsaXplZE5hbWUpIHtcbiAgdXRpbHMuZm9yRWFjaChoZWFkZXJzLCBmdW5jdGlvbiBwcm9jZXNzSGVhZGVyKHZhbHVlLCBuYW1lKSB7XG4gICAgaWYgKG5hbWUgIT09IG5vcm1hbGl6ZWROYW1lICYmIG5hbWUudG9VcHBlckNhc2UoKSA9PT0gbm9ybWFsaXplZE5hbWUudG9VcHBlckNhc2UoKSkge1xuICAgICAgaGVhZGVyc1tub3JtYWxpemVkTmFtZV0gPSB2YWx1ZTtcbiAgICAgIGRlbGV0ZSBoZWFkZXJzW25hbWVdO1xuICAgIH1cbiAgfSk7XG59O1xuIiwiJ3VzZSBzdHJpY3QnO1xuXG52YXIgdXRpbHMgPSByZXF1aXJlKCcuLy4uL3V0aWxzJyk7XG5cbi8vIEhlYWRlcnMgd2hvc2UgZHVwbGljYXRlcyBhcmUgaWdub3JlZCBieSBub2RlXG4vLyBjLmYuIGh0dHBzOi8vbm9kZWpzLm9yZy9hcGkvaHR0cC5odG1sI2h0dHBfbWVzc2FnZV9oZWFkZXJzXG52YXIgaWdub3JlRHVwbGljYXRlT2YgPSBbXG4gICdhZ2UnLCAnYXV0aG9yaXphdGlvbicsICdjb250ZW50LWxlbmd0aCcsICdjb250ZW50LXR5cGUnLCAnZXRhZycsXG4gICdleHBpcmVzJywgJ2Zyb20nLCAnaG9zdCcsICdpZi1tb2RpZmllZC1zaW5jZScsICdpZi11bm1vZGlmaWVkLXNpbmNlJyxcbiAgJ2xhc3QtbW9kaWZpZWQnLCAnbG9jYXRpb24nLCAnbWF4LWZvcndhcmRzJywgJ3Byb3h5LWF1dGhvcml6YXRpb24nLFxuICAncmVmZXJlcicsICdyZXRyeS1hZnRlcicsICd1c2VyLWFnZW50J1xuXTtcblxuLyoqXG4gKiBQYXJzZSBoZWFkZXJzIGludG8gYW4gb2JqZWN0XG4gKlxuICogYGBgXG4gKiBEYXRlOiBXZWQsIDI3IEF1ZyAyMDE0IDA4OjU4OjQ5IEdNVFxuICogQ29udGVudC1UeXBlOiBhcHBsaWNhdGlvbi9qc29uXG4gKiBDb25uZWN0aW9uOiBrZWVwLWFsaXZlXG4gKiBUcmFuc2Zlci1FbmNvZGluZzogY2h1bmtlZFxuICogYGBgXG4gKlxuICogQHBhcmFtIHtTdHJpbmd9IGhlYWRlcnMgSGVhZGVycyBuZWVkaW5nIHRvIGJlIHBhcnNlZFxuICogQHJldHVybnMge09iamVjdH0gSGVhZGVycyBwYXJzZWQgaW50byBhbiBvYmplY3RcbiAqL1xubW9kdWxlLmV4cG9ydHMgPSBmdW5jdGlvbiBwYXJzZUhlYWRlcnMoaGVhZGVycykge1xuICB2YXIgcGFyc2VkID0ge307XG4gIHZhciBrZXk7XG4gIHZhciB2YWw7XG4gIHZhciBpO1xuXG4gIGlmICghaGVhZGVycykgeyByZXR1cm4gcGFyc2VkOyB9XG5cbiAgdXRpbHMuZm9yRWFjaChoZWFkZXJzLnNwbGl0KCdcXG4nKSwgZnVuY3Rpb24gcGFyc2VyKGxpbmUpIHtcbiAgICBpID0gbGluZS5pbmRleE9mKCc6Jyk7XG4gICAga2V5ID0gdXRpbHMudHJpbShsaW5lLnN1YnN0cigwLCBpKSkudG9Mb3dlckNhc2UoKTtcbiAgICB2YWwgPSB1dGlscy50cmltKGxpbmUuc3Vic3RyKGkgKyAxKSk7XG5cbiAgICBpZiAoa2V5KSB7XG4gICAgICBpZiAocGFyc2VkW2tleV0gJiYgaWdub3JlRHVwbGljYXRlT2YuaW5kZXhPZihrZXkpID49IDApIHtcbiAgICAgICAgcmV0dXJuO1xuICAgICAgfVxuICAgICAgaWYgKGtleSA9PT0gJ3NldC1jb29raWUnKSB7XG4gICAgICAgIHBhcnNlZFtrZXldID0gKHBhcnNlZFtrZXldID8gcGFyc2VkW2tleV0gOiBbXSkuY29uY2F0KFt2YWxdKTtcbiAgICAgIH0gZWxzZSB7XG4gICAgICAgIHBhcnNlZFtrZXldID0gcGFyc2VkW2tleV0gPyBwYXJzZWRba2V5XSArICcsICcgKyB2YWwgOiB2YWw7XG4gICAgICB9XG4gICAgfVxuICB9KTtcblxuICByZXR1cm4gcGFyc2VkO1xufTtcbiIsIid1c2Ugc3RyaWN0JztcblxuLyoqXG4gKiBTeW50YWN0aWMgc3VnYXIgZm9yIGludm9raW5nIGEgZnVuY3Rpb24gYW5kIGV4cGFuZGluZyBhbiBhcnJheSBmb3IgYXJndW1lbnRzLlxuICpcbiAqIENvbW1vbiB1c2UgY2FzZSB3b3VsZCBiZSB0byB1c2UgYEZ1bmN0aW9uLnByb3RvdHlwZS5hcHBseWAuXG4gKlxuICogIGBgYGpzXG4gKiAgZnVuY3Rpb24gZih4LCB5LCB6KSB7fVxuICogIHZhciBhcmdzID0gWzEsIDIsIDNdO1xuICogIGYuYXBwbHkobnVsbCwgYXJncyk7XG4gKiAgYGBgXG4gKlxuICogV2l0aCBgc3ByZWFkYCB0aGlzIGV4YW1wbGUgY2FuIGJlIHJlLXdyaXR0ZW4uXG4gKlxuICogIGBgYGpzXG4gKiAgc3ByZWFkKGZ1bmN0aW9uKHgsIHksIHopIHt9KShbMSwgMiwgM10pO1xuICogIGBgYFxuICpcbiAqIEBwYXJhbSB7RnVuY3Rpb259IGNhbGxiYWNrXG4gKiBAcmV0dXJucyB7RnVuY3Rpb259XG4gKi9cbm1vZHVsZS5leHBvcnRzID0gZnVuY3Rpb24gc3ByZWFkKGNhbGxiYWNrKSB7XG4gIHJldHVybiBmdW5jdGlvbiB3cmFwKGFycikge1xuICAgIHJldHVybiBjYWxsYmFjay5hcHBseShudWxsLCBhcnIpO1xuICB9O1xufTtcbiIsIid1c2Ugc3RyaWN0JztcblxudmFyIHBrZyA9IHJlcXVpcmUoJy4vLi4vLi4vcGFja2FnZS5qc29uJyk7XG5cbnZhciB2YWxpZGF0b3JzID0ge307XG5cbi8vIGVzbGludC1kaXNhYmxlLW5leHQtbGluZSBmdW5jLW5hbWVzXG5bJ29iamVjdCcsICdib29sZWFuJywgJ251bWJlcicsICdmdW5jdGlvbicsICdzdHJpbmcnLCAnc3ltYm9sJ10uZm9yRWFjaChmdW5jdGlvbih0eXBlLCBpKSB7XG4gIHZhbGlkYXRvcnNbdHlwZV0gPSBmdW5jdGlvbiB2YWxpZGF0b3IodGhpbmcpIHtcbiAgICByZXR1cm4gdHlwZW9mIHRoaW5nID09PSB0eXBlIHx8ICdhJyArIChpIDwgMSA/ICduICcgOiAnICcpICsgdHlwZTtcbiAgfTtcbn0pO1xuXG52YXIgZGVwcmVjYXRlZFdhcm5pbmdzID0ge307XG52YXIgY3VycmVudFZlckFyciA9IHBrZy52ZXJzaW9uLnNwbGl0KCcuJyk7XG5cbi8qKlxuICogQ29tcGFyZSBwYWNrYWdlIHZlcnNpb25zXG4gKiBAcGFyYW0ge3N0cmluZ30gdmVyc2lvblxuICogQHBhcmFtIHtzdHJpbmc/fSB0aGFuVmVyc2lvblxuICogQHJldHVybnMge2Jvb2xlYW59XG4gKi9cbmZ1bmN0aW9uIGlzT2xkZXJWZXJzaW9uKHZlcnNpb24sIHRoYW5WZXJzaW9uKSB7XG4gIHZhciBwa2dWZXJzaW9uQXJyID0gdGhhblZlcnNpb24gPyB0aGFuVmVyc2lvbi5zcGxpdCgnLicpIDogY3VycmVudFZlckFycjtcbiAgdmFyIGRlc3RWZXIgPSB2ZXJzaW9uLnNwbGl0KCcuJyk7XG4gIGZvciAodmFyIGkgPSAwOyBpIDwgMzsgaSsrKSB7XG4gICAgaWYgKHBrZ1ZlcnNpb25BcnJbaV0gPiBkZXN0VmVyW2ldKSB7XG4gICAgICByZXR1cm4gdHJ1ZTtcbiAgICB9IGVsc2UgaWYgKHBrZ1ZlcnNpb25BcnJbaV0gPCBkZXN0VmVyW2ldKSB7XG4gICAgICByZXR1cm4gZmFsc2U7XG4gICAgfVxuICB9XG4gIHJldHVybiBmYWxzZTtcbn1cblxuLyoqXG4gKiBUcmFuc2l0aW9uYWwgb3B0aW9uIHZhbGlkYXRvclxuICogQHBhcmFtIHtmdW5jdGlvbnxib29sZWFuP30gdmFsaWRhdG9yXG4gKiBAcGFyYW0ge3N0cmluZz99IHZlcnNpb25cbiAqIEBwYXJhbSB7c3RyaW5nfSBtZXNzYWdlXG4gKiBAcmV0dXJucyB7ZnVuY3Rpb259XG4gKi9cbnZhbGlkYXRvcnMudHJhbnNpdGlvbmFsID0gZnVuY3Rpb24gdHJhbnNpdGlvbmFsKHZhbGlkYXRvciwgdmVyc2lvbiwgbWVzc2FnZSkge1xuICB2YXIgaXNEZXByZWNhdGVkID0gdmVyc2lvbiAmJiBpc09sZGVyVmVyc2lvbih2ZXJzaW9uKTtcblxuICBmdW5jdGlvbiBmb3JtYXRNZXNzYWdlKG9wdCwgZGVzYykge1xuICAgIHJldHVybiAnW0F4aW9zIHYnICsgcGtnLnZlcnNpb24gKyAnXSBUcmFuc2l0aW9uYWwgb3B0aW9uIFxcJycgKyBvcHQgKyAnXFwnJyArIGRlc2MgKyAobWVzc2FnZSA/ICcuICcgKyBtZXNzYWdlIDogJycpO1xuICB9XG5cbiAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIGZ1bmMtbmFtZXNcbiAgcmV0dXJuIGZ1bmN0aW9uKHZhbHVlLCBvcHQsIG9wdHMpIHtcbiAgICBpZiAodmFsaWRhdG9yID09PSBmYWxzZSkge1xuICAgICAgdGhyb3cgbmV3IEVycm9yKGZvcm1hdE1lc3NhZ2Uob3B0LCAnIGhhcyBiZWVuIHJlbW92ZWQgaW4gJyArIHZlcnNpb24pKTtcbiAgICB9XG5cbiAgICBpZiAoaXNEZXByZWNhdGVkICYmICFkZXByZWNhdGVkV2FybmluZ3Nbb3B0XSkge1xuICAgICAgZGVwcmVjYXRlZFdhcm5pbmdzW29wdF0gPSB0cnVlO1xuICAgICAgLy8gZXNsaW50LWRpc2FibGUtbmV4dC1saW5lIG5vLWNvbnNvbGVcbiAgICAgIGNvbnNvbGUud2FybihcbiAgICAgICAgZm9ybWF0TWVzc2FnZShcbiAgICAgICAgICBvcHQsXG4gICAgICAgICAgJyBoYXMgYmVlbiBkZXByZWNhdGVkIHNpbmNlIHYnICsgdmVyc2lvbiArICcgYW5kIHdpbGwgYmUgcmVtb3ZlZCBpbiB0aGUgbmVhciBmdXR1cmUnXG4gICAgICAgIClcbiAgICAgICk7XG4gICAgfVxuXG4gICAgcmV0dXJuIHZhbGlkYXRvciA/IHZhbGlkYXRvcih2YWx1ZSwgb3B0LCBvcHRzKSA6IHRydWU7XG4gIH07XG59O1xuXG4vKipcbiAqIEFzc2VydCBvYmplY3QncyBwcm9wZXJ0aWVzIHR5cGVcbiAqIEBwYXJhbSB7b2JqZWN0fSBvcHRpb25zXG4gKiBAcGFyYW0ge29iamVjdH0gc2NoZW1hXG4gKiBAcGFyYW0ge2Jvb2xlYW4/fSBhbGxvd1Vua25vd25cbiAqL1xuXG5mdW5jdGlvbiBhc3NlcnRPcHRpb25zKG9wdGlvbnMsIHNjaGVtYSwgYWxsb3dVbmtub3duKSB7XG4gIGlmICh0eXBlb2Ygb3B0aW9ucyAhPT0gJ29iamVjdCcpIHtcbiAgICB0aHJvdyBuZXcgVHlwZUVycm9yKCdvcHRpb25zIG11c3QgYmUgYW4gb2JqZWN0Jyk7XG4gIH1cbiAgdmFyIGtleXMgPSBPYmplY3Qua2V5cyhvcHRpb25zKTtcbiAgdmFyIGkgPSBrZXlzLmxlbmd0aDtcbiAgd2hpbGUgKGktLSA+IDApIHtcbiAgICB2YXIgb3B0ID0ga2V5c1tpXTtcbiAgICB2YXIgdmFsaWRhdG9yID0gc2NoZW1hW29wdF07XG4gICAgaWYgKHZhbGlkYXRvcikge1xuICAgICAgdmFyIHZhbHVlID0gb3B0aW9uc1tvcHRdO1xuICAgICAgdmFyIHJlc3VsdCA9IHZhbHVlID09PSB1bmRlZmluZWQgfHwgdmFsaWRhdG9yKHZhbHVlLCBvcHQsIG9wdGlvbnMpO1xuICAgICAgaWYgKHJlc3VsdCAhPT0gdHJ1ZSkge1xuICAgICAgICB0aHJvdyBuZXcgVHlwZUVycm9yKCdvcHRpb24gJyArIG9wdCArICcgbXVzdCBiZSAnICsgcmVzdWx0KTtcbiAgICAgIH1cbiAgICAgIGNvbnRpbnVlO1xuICAgIH1cbiAgICBpZiAoYWxsb3dVbmtub3duICE9PSB0cnVlKSB7XG4gICAgICB0aHJvdyBFcnJvcignVW5rbm93biBvcHRpb24gJyArIG9wdCk7XG4gICAgfVxuICB9XG59XG5cbm1vZHVsZS5leHBvcnRzID0ge1xuICBpc09sZGVyVmVyc2lvbjogaXNPbGRlclZlcnNpb24sXG4gIGFzc2VydE9wdGlvbnM6IGFzc2VydE9wdGlvbnMsXG4gIHZhbGlkYXRvcnM6IHZhbGlkYXRvcnNcbn07XG4iLCIndXNlIHN0cmljdCc7XG5cbnZhciBiaW5kID0gcmVxdWlyZSgnLi9oZWxwZXJzL2JpbmQnKTtcblxuLy8gdXRpbHMgaXMgYSBsaWJyYXJ5IG9mIGdlbmVyaWMgaGVscGVyIGZ1bmN0aW9ucyBub24tc3BlY2lmaWMgdG8gYXhpb3NcblxudmFyIHRvU3RyaW5nID0gT2JqZWN0LnByb3RvdHlwZS50b1N0cmluZztcblxuLyoqXG4gKiBEZXRlcm1pbmUgaWYgYSB2YWx1ZSBpcyBhbiBBcnJheVxuICpcbiAqIEBwYXJhbSB7T2JqZWN0fSB2YWwgVGhlIHZhbHVlIHRvIHRlc3RcbiAqIEByZXR1cm5zIHtib29sZWFufSBUcnVlIGlmIHZhbHVlIGlzIGFuIEFycmF5LCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNBcnJheSh2YWwpIHtcbiAgcmV0dXJuIHRvU3RyaW5nLmNhbGwodmFsKSA9PT0gJ1tvYmplY3QgQXJyYXldJztcbn1cblxuLyoqXG4gKiBEZXRlcm1pbmUgaWYgYSB2YWx1ZSBpcyB1bmRlZmluZWRcbiAqXG4gKiBAcGFyYW0ge09iamVjdH0gdmFsIFRoZSB2YWx1ZSB0byB0ZXN0XG4gKiBAcmV0dXJucyB7Ym9vbGVhbn0gVHJ1ZSBpZiB0aGUgdmFsdWUgaXMgdW5kZWZpbmVkLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNVbmRlZmluZWQodmFsKSB7XG4gIHJldHVybiB0eXBlb2YgdmFsID09PSAndW5kZWZpbmVkJztcbn1cblxuLyoqXG4gKiBEZXRlcm1pbmUgaWYgYSB2YWx1ZSBpcyBhIEJ1ZmZlclxuICpcbiAqIEBwYXJhbSB7T2JqZWN0fSB2YWwgVGhlIHZhbHVlIHRvIHRlc3RcbiAqIEByZXR1cm5zIHtib29sZWFufSBUcnVlIGlmIHZhbHVlIGlzIGEgQnVmZmVyLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNCdWZmZXIodmFsKSB7XG4gIHJldHVybiB2YWwgIT09IG51bGwgJiYgIWlzVW5kZWZpbmVkKHZhbCkgJiYgdmFsLmNvbnN0cnVjdG9yICE9PSBudWxsICYmICFpc1VuZGVmaW5lZCh2YWwuY29uc3RydWN0b3IpXG4gICAgJiYgdHlwZW9mIHZhbC5jb25zdHJ1Y3Rvci5pc0J1ZmZlciA9PT0gJ2Z1bmN0aW9uJyAmJiB2YWwuY29uc3RydWN0b3IuaXNCdWZmZXIodmFsKTtcbn1cblxuLyoqXG4gKiBEZXRlcm1pbmUgaWYgYSB2YWx1ZSBpcyBhbiBBcnJheUJ1ZmZlclxuICpcbiAqIEBwYXJhbSB7T2JqZWN0fSB2YWwgVGhlIHZhbHVlIHRvIHRlc3RcbiAqIEByZXR1cm5zIHtib29sZWFufSBUcnVlIGlmIHZhbHVlIGlzIGFuIEFycmF5QnVmZmVyLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNBcnJheUJ1ZmZlcih2YWwpIHtcbiAgcmV0dXJuIHRvU3RyaW5nLmNhbGwodmFsKSA9PT0gJ1tvYmplY3QgQXJyYXlCdWZmZXJdJztcbn1cblxuLyoqXG4gKiBEZXRlcm1pbmUgaWYgYSB2YWx1ZSBpcyBhIEZvcm1EYXRhXG4gKlxuICogQHBhcmFtIHtPYmplY3R9IHZhbCBUaGUgdmFsdWUgdG8gdGVzdFxuICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdmFsdWUgaXMgYW4gRm9ybURhdGEsIG90aGVyd2lzZSBmYWxzZVxuICovXG5mdW5jdGlvbiBpc0Zvcm1EYXRhKHZhbCkge1xuICByZXR1cm4gKHR5cGVvZiBGb3JtRGF0YSAhPT0gJ3VuZGVmaW5lZCcpICYmICh2YWwgaW5zdGFuY2VvZiBGb3JtRGF0YSk7XG59XG5cbi8qKlxuICogRGV0ZXJtaW5lIGlmIGEgdmFsdWUgaXMgYSB2aWV3IG9uIGFuIEFycmF5QnVmZmVyXG4gKlxuICogQHBhcmFtIHtPYmplY3R9IHZhbCBUaGUgdmFsdWUgdG8gdGVzdFxuICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdmFsdWUgaXMgYSB2aWV3IG9uIGFuIEFycmF5QnVmZmVyLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNBcnJheUJ1ZmZlclZpZXcodmFsKSB7XG4gIHZhciByZXN1bHQ7XG4gIGlmICgodHlwZW9mIEFycmF5QnVmZmVyICE9PSAndW5kZWZpbmVkJykgJiYgKEFycmF5QnVmZmVyLmlzVmlldykpIHtcbiAgICByZXN1bHQgPSBBcnJheUJ1ZmZlci5pc1ZpZXcodmFsKTtcbiAgfSBlbHNlIHtcbiAgICByZXN1bHQgPSAodmFsKSAmJiAodmFsLmJ1ZmZlcikgJiYgKHZhbC5idWZmZXIgaW5zdGFuY2VvZiBBcnJheUJ1ZmZlcik7XG4gIH1cbiAgcmV0dXJuIHJlc3VsdDtcbn1cblxuLyoqXG4gKiBEZXRlcm1pbmUgaWYgYSB2YWx1ZSBpcyBhIFN0cmluZ1xuICpcbiAqIEBwYXJhbSB7T2JqZWN0fSB2YWwgVGhlIHZhbHVlIHRvIHRlc3RcbiAqIEByZXR1cm5zIHtib29sZWFufSBUcnVlIGlmIHZhbHVlIGlzIGEgU3RyaW5nLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNTdHJpbmcodmFsKSB7XG4gIHJldHVybiB0eXBlb2YgdmFsID09PSAnc3RyaW5nJztcbn1cblxuLyoqXG4gKiBEZXRlcm1pbmUgaWYgYSB2YWx1ZSBpcyBhIE51bWJlclxuICpcbiAqIEBwYXJhbSB7T2JqZWN0fSB2YWwgVGhlIHZhbHVlIHRvIHRlc3RcbiAqIEByZXR1cm5zIHtib29sZWFufSBUcnVlIGlmIHZhbHVlIGlzIGEgTnVtYmVyLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNOdW1iZXIodmFsKSB7XG4gIHJldHVybiB0eXBlb2YgdmFsID09PSAnbnVtYmVyJztcbn1cblxuLyoqXG4gKiBEZXRlcm1pbmUgaWYgYSB2YWx1ZSBpcyBhbiBPYmplY3RcbiAqXG4gKiBAcGFyYW0ge09iamVjdH0gdmFsIFRoZSB2YWx1ZSB0byB0ZXN0XG4gKiBAcmV0dXJucyB7Ym9vbGVhbn0gVHJ1ZSBpZiB2YWx1ZSBpcyBhbiBPYmplY3QsIG90aGVyd2lzZSBmYWxzZVxuICovXG5mdW5jdGlvbiBpc09iamVjdCh2YWwpIHtcbiAgcmV0dXJuIHZhbCAhPT0gbnVsbCAmJiB0eXBlb2YgdmFsID09PSAnb2JqZWN0Jztcbn1cblxuLyoqXG4gKiBEZXRlcm1pbmUgaWYgYSB2YWx1ZSBpcyBhIHBsYWluIE9iamVjdFxuICpcbiAqIEBwYXJhbSB7T2JqZWN0fSB2YWwgVGhlIHZhbHVlIHRvIHRlc3RcbiAqIEByZXR1cm4ge2Jvb2xlYW59IFRydWUgaWYgdmFsdWUgaXMgYSBwbGFpbiBPYmplY3QsIG90aGVyd2lzZSBmYWxzZVxuICovXG5mdW5jdGlvbiBpc1BsYWluT2JqZWN0KHZhbCkge1xuICBpZiAodG9TdHJpbmcuY2FsbCh2YWwpICE9PSAnW29iamVjdCBPYmplY3RdJykge1xuICAgIHJldHVybiBmYWxzZTtcbiAgfVxuXG4gIHZhciBwcm90b3R5cGUgPSBPYmplY3QuZ2V0UHJvdG90eXBlT2YodmFsKTtcbiAgcmV0dXJuIHByb3RvdHlwZSA9PT0gbnVsbCB8fCBwcm90b3R5cGUgPT09IE9iamVjdC5wcm90b3R5cGU7XG59XG5cbi8qKlxuICogRGV0ZXJtaW5lIGlmIGEgdmFsdWUgaXMgYSBEYXRlXG4gKlxuICogQHBhcmFtIHtPYmplY3R9IHZhbCBUaGUgdmFsdWUgdG8gdGVzdFxuICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdmFsdWUgaXMgYSBEYXRlLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNEYXRlKHZhbCkge1xuICByZXR1cm4gdG9TdHJpbmcuY2FsbCh2YWwpID09PSAnW29iamVjdCBEYXRlXSc7XG59XG5cbi8qKlxuICogRGV0ZXJtaW5lIGlmIGEgdmFsdWUgaXMgYSBGaWxlXG4gKlxuICogQHBhcmFtIHtPYmplY3R9IHZhbCBUaGUgdmFsdWUgdG8gdGVzdFxuICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdmFsdWUgaXMgYSBGaWxlLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNGaWxlKHZhbCkge1xuICByZXR1cm4gdG9TdHJpbmcuY2FsbCh2YWwpID09PSAnW29iamVjdCBGaWxlXSc7XG59XG5cbi8qKlxuICogRGV0ZXJtaW5lIGlmIGEgdmFsdWUgaXMgYSBCbG9iXG4gKlxuICogQHBhcmFtIHtPYmplY3R9IHZhbCBUaGUgdmFsdWUgdG8gdGVzdFxuICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdmFsdWUgaXMgYSBCbG9iLCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNCbG9iKHZhbCkge1xuICByZXR1cm4gdG9TdHJpbmcuY2FsbCh2YWwpID09PSAnW29iamVjdCBCbG9iXSc7XG59XG5cbi8qKlxuICogRGV0ZXJtaW5lIGlmIGEgdmFsdWUgaXMgYSBGdW5jdGlvblxuICpcbiAqIEBwYXJhbSB7T2JqZWN0fSB2YWwgVGhlIHZhbHVlIHRvIHRlc3RcbiAqIEByZXR1cm5zIHtib29sZWFufSBUcnVlIGlmIHZhbHVlIGlzIGEgRnVuY3Rpb24sIG90aGVyd2lzZSBmYWxzZVxuICovXG5mdW5jdGlvbiBpc0Z1bmN0aW9uKHZhbCkge1xuICByZXR1cm4gdG9TdHJpbmcuY2FsbCh2YWwpID09PSAnW29iamVjdCBGdW5jdGlvbl0nO1xufVxuXG4vKipcbiAqIERldGVybWluZSBpZiBhIHZhbHVlIGlzIGEgU3RyZWFtXG4gKlxuICogQHBhcmFtIHtPYmplY3R9IHZhbCBUaGUgdmFsdWUgdG8gdGVzdFxuICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdmFsdWUgaXMgYSBTdHJlYW0sIG90aGVyd2lzZSBmYWxzZVxuICovXG5mdW5jdGlvbiBpc1N0cmVhbSh2YWwpIHtcbiAgcmV0dXJuIGlzT2JqZWN0KHZhbCkgJiYgaXNGdW5jdGlvbih2YWwucGlwZSk7XG59XG5cbi8qKlxuICogRGV0ZXJtaW5lIGlmIGEgdmFsdWUgaXMgYSBVUkxTZWFyY2hQYXJhbXMgb2JqZWN0XG4gKlxuICogQHBhcmFtIHtPYmplY3R9IHZhbCBUaGUgdmFsdWUgdG8gdGVzdFxuICogQHJldHVybnMge2Jvb2xlYW59IFRydWUgaWYgdmFsdWUgaXMgYSBVUkxTZWFyY2hQYXJhbXMgb2JqZWN0LCBvdGhlcndpc2UgZmFsc2VcbiAqL1xuZnVuY3Rpb24gaXNVUkxTZWFyY2hQYXJhbXModmFsKSB7XG4gIHJldHVybiB0eXBlb2YgVVJMU2VhcmNoUGFyYW1zICE9PSAndW5kZWZpbmVkJyAmJiB2YWwgaW5zdGFuY2VvZiBVUkxTZWFyY2hQYXJhbXM7XG59XG5cbi8qKlxuICogVHJpbSBleGNlc3Mgd2hpdGVzcGFjZSBvZmYgdGhlIGJlZ2lubmluZyBhbmQgZW5kIG9mIGEgc3RyaW5nXG4gKlxuICogQHBhcmFtIHtTdHJpbmd9IHN0ciBUaGUgU3RyaW5nIHRvIHRyaW1cbiAqIEByZXR1cm5zIHtTdHJpbmd9IFRoZSBTdHJpbmcgZnJlZWQgb2YgZXhjZXNzIHdoaXRlc3BhY2VcbiAqL1xuZnVuY3Rpb24gdHJpbShzdHIpIHtcbiAgcmV0dXJuIHN0ci50cmltID8gc3RyLnRyaW0oKSA6IHN0ci5yZXBsYWNlKC9eXFxzK3xcXHMrJC9nLCAnJyk7XG59XG5cbi8qKlxuICogRGV0ZXJtaW5lIGlmIHdlJ3JlIHJ1bm5pbmcgaW4gYSBzdGFuZGFyZCBicm93c2VyIGVudmlyb25tZW50XG4gKlxuICogVGhpcyBhbGxvd3MgYXhpb3MgdG8gcnVuIGluIGEgd2ViIHdvcmtlciwgYW5kIHJlYWN0LW5hdGl2ZS5cbiAqIEJvdGggZW52aXJvbm1lbnRzIHN1cHBvcnQgWE1MSHR0cFJlcXVlc3QsIGJ1dCBub3QgZnVsbHkgc3RhbmRhcmQgZ2xvYmFscy5cbiAqXG4gKiB3ZWIgd29ya2VyczpcbiAqICB0eXBlb2Ygd2luZG93IC0+IHVuZGVmaW5lZFxuICogIHR5cGVvZiBkb2N1bWVudCAtPiB1bmRlZmluZWRcbiAqXG4gKiByZWFjdC1uYXRpdmU6XG4gKiAgbmF2aWdhdG9yLnByb2R1Y3QgLT4gJ1JlYWN0TmF0aXZlJ1xuICogbmF0aXZlc2NyaXB0XG4gKiAgbmF2aWdhdG9yLnByb2R1Y3QgLT4gJ05hdGl2ZVNjcmlwdCcgb3IgJ05TJ1xuICovXG5mdW5jdGlvbiBpc1N0YW5kYXJkQnJvd3NlckVudigpIHtcbiAgaWYgKHR5cGVvZiBuYXZpZ2F0b3IgIT09ICd1bmRlZmluZWQnICYmIChuYXZpZ2F0b3IucHJvZHVjdCA9PT0gJ1JlYWN0TmF0aXZlJyB8fFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG5hdmlnYXRvci5wcm9kdWN0ID09PSAnTmF0aXZlU2NyaXB0JyB8fFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG5hdmlnYXRvci5wcm9kdWN0ID09PSAnTlMnKSkge1xuICAgIHJldHVybiBmYWxzZTtcbiAgfVxuICByZXR1cm4gKFxuICAgIHR5cGVvZiB3aW5kb3cgIT09ICd1bmRlZmluZWQnICYmXG4gICAgdHlwZW9mIGRvY3VtZW50ICE9PSAndW5kZWZpbmVkJ1xuICApO1xufVxuXG4vKipcbiAqIEl0ZXJhdGUgb3ZlciBhbiBBcnJheSBvciBhbiBPYmplY3QgaW52b2tpbmcgYSBmdW5jdGlvbiBmb3IgZWFjaCBpdGVtLlxuICpcbiAqIElmIGBvYmpgIGlzIGFuIEFycmF5IGNhbGxiYWNrIHdpbGwgYmUgY2FsbGVkIHBhc3NpbmdcbiAqIHRoZSB2YWx1ZSwgaW5kZXgsIGFuZCBjb21wbGV0ZSBhcnJheSBmb3IgZWFjaCBpdGVtLlxuICpcbiAqIElmICdvYmonIGlzIGFuIE9iamVjdCBjYWxsYmFjayB3aWxsIGJlIGNhbGxlZCBwYXNzaW5nXG4gKiB0aGUgdmFsdWUsIGtleSwgYW5kIGNvbXBsZXRlIG9iamVjdCBmb3IgZWFjaCBwcm9wZXJ0eS5cbiAqXG4gKiBAcGFyYW0ge09iamVjdHxBcnJheX0gb2JqIFRoZSBvYmplY3QgdG8gaXRlcmF0ZVxuICogQHBhcmFtIHtGdW5jdGlvbn0gZm4gVGhlIGNhbGxiYWNrIHRvIGludm9rZSBmb3IgZWFjaCBpdGVtXG4gKi9cbmZ1bmN0aW9uIGZvckVhY2gob2JqLCBmbikge1xuICAvLyBEb24ndCBib3RoZXIgaWYgbm8gdmFsdWUgcHJvdmlkZWRcbiAgaWYgKG9iaiA9PT0gbnVsbCB8fCB0eXBlb2Ygb2JqID09PSAndW5kZWZpbmVkJykge1xuICAgIHJldHVybjtcbiAgfVxuXG4gIC8vIEZvcmNlIGFuIGFycmF5IGlmIG5vdCBhbHJlYWR5IHNvbWV0aGluZyBpdGVyYWJsZVxuICBpZiAodHlwZW9mIG9iaiAhPT0gJ29iamVjdCcpIHtcbiAgICAvKmVzbGludCBuby1wYXJhbS1yZWFzc2lnbjowKi9cbiAgICBvYmogPSBbb2JqXTtcbiAgfVxuXG4gIGlmIChpc0FycmF5KG9iaikpIHtcbiAgICAvLyBJdGVyYXRlIG92ZXIgYXJyYXkgdmFsdWVzXG4gICAgZm9yICh2YXIgaSA9IDAsIGwgPSBvYmoubGVuZ3RoOyBpIDwgbDsgaSsrKSB7XG4gICAgICBmbi5jYWxsKG51bGwsIG9ialtpXSwgaSwgb2JqKTtcbiAgICB9XG4gIH0gZWxzZSB7XG4gICAgLy8gSXRlcmF0ZSBvdmVyIG9iamVjdCBrZXlzXG4gICAgZm9yICh2YXIga2V5IGluIG9iaikge1xuICAgICAgaWYgKE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbChvYmosIGtleSkpIHtcbiAgICAgICAgZm4uY2FsbChudWxsLCBvYmpba2V5XSwga2V5LCBvYmopO1xuICAgICAgfVxuICAgIH1cbiAgfVxufVxuXG4vKipcbiAqIEFjY2VwdHMgdmFyYXJncyBleHBlY3RpbmcgZWFjaCBhcmd1bWVudCB0byBiZSBhbiBvYmplY3QsIHRoZW5cbiAqIGltbXV0YWJseSBtZXJnZXMgdGhlIHByb3BlcnRpZXMgb2YgZWFjaCBvYmplY3QgYW5kIHJldHVybnMgcmVzdWx0LlxuICpcbiAqIFdoZW4gbXVsdGlwbGUgb2JqZWN0cyBjb250YWluIHRoZSBzYW1lIGtleSB0aGUgbGF0ZXIgb2JqZWN0IGluXG4gKiB0aGUgYXJndW1lbnRzIGxpc3Qgd2lsbCB0YWtlIHByZWNlZGVuY2UuXG4gKlxuICogRXhhbXBsZTpcbiAqXG4gKiBgYGBqc1xuICogdmFyIHJlc3VsdCA9IG1lcmdlKHtmb286IDEyM30sIHtmb286IDQ1Nn0pO1xuICogY29uc29sZS5sb2cocmVzdWx0LmZvbyk7IC8vIG91dHB1dHMgNDU2XG4gKiBgYGBcbiAqXG4gKiBAcGFyYW0ge09iamVjdH0gb2JqMSBPYmplY3QgdG8gbWVyZ2VcbiAqIEByZXR1cm5zIHtPYmplY3R9IFJlc3VsdCBvZiBhbGwgbWVyZ2UgcHJvcGVydGllc1xuICovXG5mdW5jdGlvbiBtZXJnZSgvKiBvYmoxLCBvYmoyLCBvYmozLCAuLi4gKi8pIHtcbiAgdmFyIHJlc3VsdCA9IHt9O1xuICBmdW5jdGlvbiBhc3NpZ25WYWx1ZSh2YWwsIGtleSkge1xuICAgIGlmIChpc1BsYWluT2JqZWN0KHJlc3VsdFtrZXldKSAmJiBpc1BsYWluT2JqZWN0KHZhbCkpIHtcbiAgICAgIHJlc3VsdFtrZXldID0gbWVyZ2UocmVzdWx0W2tleV0sIHZhbCk7XG4gICAgfSBlbHNlIGlmIChpc1BsYWluT2JqZWN0KHZhbCkpIHtcbiAgICAgIHJlc3VsdFtrZXldID0gbWVyZ2Uoe30sIHZhbCk7XG4gICAgfSBlbHNlIGlmIChpc0FycmF5KHZhbCkpIHtcbiAgICAgIHJlc3VsdFtrZXldID0gdmFsLnNsaWNlKCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIHJlc3VsdFtrZXldID0gdmFsO1xuICAgIH1cbiAgfVxuXG4gIGZvciAodmFyIGkgPSAwLCBsID0gYXJndW1lbnRzLmxlbmd0aDsgaSA8IGw7IGkrKykge1xuICAgIGZvckVhY2goYXJndW1lbnRzW2ldLCBhc3NpZ25WYWx1ZSk7XG4gIH1cbiAgcmV0dXJuIHJlc3VsdDtcbn1cblxuLyoqXG4gKiBFeHRlbmRzIG9iamVjdCBhIGJ5IG11dGFibHkgYWRkaW5nIHRvIGl0IHRoZSBwcm9wZXJ0aWVzIG9mIG9iamVjdCBiLlxuICpcbiAqIEBwYXJhbSB7T2JqZWN0fSBhIFRoZSBvYmplY3QgdG8gYmUgZXh0ZW5kZWRcbiAqIEBwYXJhbSB7T2JqZWN0fSBiIFRoZSBvYmplY3QgdG8gY29weSBwcm9wZXJ0aWVzIGZyb21cbiAqIEBwYXJhbSB7T2JqZWN0fSB0aGlzQXJnIFRoZSBvYmplY3QgdG8gYmluZCBmdW5jdGlvbiB0b1xuICogQHJldHVybiB7T2JqZWN0fSBUaGUgcmVzdWx0aW5nIHZhbHVlIG9mIG9iamVjdCBhXG4gKi9cbmZ1bmN0aW9uIGV4dGVuZChhLCBiLCB0aGlzQXJnKSB7XG4gIGZvckVhY2goYiwgZnVuY3Rpb24gYXNzaWduVmFsdWUodmFsLCBrZXkpIHtcbiAgICBpZiAodGhpc0FyZyAmJiB0eXBlb2YgdmFsID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICBhW2tleV0gPSBiaW5kKHZhbCwgdGhpc0FyZyk7XG4gICAgfSBlbHNlIHtcbiAgICAgIGFba2V5XSA9IHZhbDtcbiAgICB9XG4gIH0pO1xuICByZXR1cm4gYTtcbn1cblxuLyoqXG4gKiBSZW1vdmUgYnl0ZSBvcmRlciBtYXJrZXIuIFRoaXMgY2F0Y2hlcyBFRiBCQiBCRiAodGhlIFVURi04IEJPTSlcbiAqXG4gKiBAcGFyYW0ge3N0cmluZ30gY29udGVudCB3aXRoIEJPTVxuICogQHJldHVybiB7c3RyaW5nfSBjb250ZW50IHZhbHVlIHdpdGhvdXQgQk9NXG4gKi9cbmZ1bmN0aW9uIHN0cmlwQk9NKGNvbnRlbnQpIHtcbiAgaWYgKGNvbnRlbnQuY2hhckNvZGVBdCgwKSA9PT0gMHhGRUZGKSB7XG4gICAgY29udGVudCA9IGNvbnRlbnQuc2xpY2UoMSk7XG4gIH1cbiAgcmV0dXJuIGNvbnRlbnQ7XG59XG5cbm1vZHVsZS5leHBvcnRzID0ge1xuICBpc0FycmF5OiBpc0FycmF5LFxuICBpc0FycmF5QnVmZmVyOiBpc0FycmF5QnVmZmVyLFxuICBpc0J1ZmZlcjogaXNCdWZmZXIsXG4gIGlzRm9ybURhdGE6IGlzRm9ybURhdGEsXG4gIGlzQXJyYXlCdWZmZXJWaWV3OiBpc0FycmF5QnVmZmVyVmlldyxcbiAgaXNTdHJpbmc6IGlzU3RyaW5nLFxuICBpc051bWJlcjogaXNOdW1iZXIsXG4gIGlzT2JqZWN0OiBpc09iamVjdCxcbiAgaXNQbGFpbk9iamVjdDogaXNQbGFpbk9iamVjdCxcbiAgaXNVbmRlZmluZWQ6IGlzVW5kZWZpbmVkLFxuICBpc0RhdGU6IGlzRGF0ZSxcbiAgaXNGaWxlOiBpc0ZpbGUsXG4gIGlzQmxvYjogaXNCbG9iLFxuICBpc0Z1bmN0aW9uOiBpc0Z1bmN0aW9uLFxuICBpc1N0cmVhbTogaXNTdHJlYW0sXG4gIGlzVVJMU2VhcmNoUGFyYW1zOiBpc1VSTFNlYXJjaFBhcmFtcyxcbiAgaXNTdGFuZGFyZEJyb3dzZXJFbnY6IGlzU3RhbmRhcmRCcm93c2VyRW52LFxuICBmb3JFYWNoOiBmb3JFYWNoLFxuICBtZXJnZTogbWVyZ2UsXG4gIGV4dGVuZDogZXh0ZW5kLFxuICB0cmltOiB0cmltLFxuICBzdHJpcEJPTTogc3RyaXBCT01cbn07XG4iLCJpbXBvcnQgQWRtaW5Ob3RpY2VNYW5hZ2VyIGZyb20gJy4vY2xhc3Nlcy9BZG1pbk5vdGljZU1hbmFnZXInO1xuaW1wb3J0IENvbnZlcnNpb25TdGF0c01hbmFnZXIgZnJvbSBcIi4vY2xhc3Nlcy9Db252ZXJzaW9uU3RhdHNNYW5hZ2VyXCI7XG5pbXBvcnQgRGF0YVRvZ2dsZVRyaWdnZXIgZnJvbSBcIi4vY2xhc3Nlcy9EYXRhVG9nZ2xlVHJpZ2dlclwiO1xuaW1wb3J0IEltYWdlQ29udmVyc2lvbk1hbmFnZXIgZnJvbSAnLi9jbGFzc2VzL0ltYWdlQ29udmVyc2lvbk1hbmFnZXInO1xuaW1wb3J0IEltYWdlc1N0YXRzRmV0Y2hlciBmcm9tIFwiLi9jbGFzc2VzL0ltYWdlc1N0YXRzRmV0Y2hlclwiO1xuaW1wb3J0IEltYWdlc1RyZWVHZW5lcmF0b3IgZnJvbSBcIi4vY2xhc3Nlcy9JbWFnZXNUcmVlR2VuZXJhdG9yXCI7XG5pbXBvcnQgUGxhbnNCdXR0b25HZW5lcmF0b3IgZnJvbSBcIi4vY2xhc3Nlcy9QbGFuc0J1dHRvbkdlbmVyYXRvclwiO1xuXG5jbGFzcyBDb3JlIHtcblxuXHRjb25zdHJ1Y3RvcigpIHtcblx0XHRjb25zdCBjb252ZXJzaW9uX3N0YXRzX21hbmFnZXIgPSBuZXcgQ29udmVyc2lvblN0YXRzTWFuYWdlcigpO1xuXHRcdGNvbnN0IGltYWdlc190cmVlX2dlbmVyYXRvciAgICA9IG5ldyBJbWFnZXNUcmVlR2VuZXJhdG9yKCk7XG5cdFx0Y29uc3QgcGxhbnNfYnV0dG9uX2dlbmVyYXRvciAgID0gbmV3IFBsYW5zQnV0dG9uR2VuZXJhdG9yKCk7XG5cblx0XHRuZXcgQWRtaW5Ob3RpY2VNYW5hZ2VyKCk7XG5cdFx0bmV3IEltYWdlQ29udmVyc2lvbk1hbmFnZXIoIGNvbnZlcnNpb25fc3RhdHNfbWFuYWdlciApO1xuXHRcdG5ldyBJbWFnZXNTdGF0c0ZldGNoZXIoIGNvbnZlcnNpb25fc3RhdHNfbWFuYWdlciwgaW1hZ2VzX3RyZWVfZ2VuZXJhdG9yLCBwbGFuc19idXR0b25fZ2VuZXJhdG9yICk7XG5cdFx0bmV3IERhdGFUb2dnbGVUcmlnZ2VyKCk7XG5cdH1cbn1cblxubmV3IENvcmUoKTtcbiIsImltcG9ydCBheGlvcyBmcm9tICdheGlvcyc7XG5cbmNsYXNzIEFkbWluTm90aWNlTWFuYWdlckNvcmUge1xuXG5cdGNvbnN0cnVjdG9yKCBub3RpY2UgKSB7XG5cdFx0dGhpcy5ub3RpY2UgPSBub3RpY2U7XG5cdFx0aWYgKCAhIHRoaXMuc2V0X3ZhcnMoKSApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHR0aGlzLnNldF9ldmVudHMoKTtcblx0fVxuXG5cdHNldF92YXJzKCkge1xuXHRcdHRoaXMuc2V0dGluZ3MgPSB7XG5cdFx0XHRhamF4X2FjdGlvbjogdGhpcy5ub3RpY2UuZ2V0QXR0cmlidXRlKCAnZGF0YS1ub3RpY2UtYWN0aW9uJyApLFxuXHRcdFx0YWpheF91cmw6IHRoaXMubm90aWNlLmdldEF0dHJpYnV0ZSggJ2RhdGEtbm90aWNlLXVybCcgKSxcblx0XHRcdGJ1dHRvbl9jbG9zZV9jbGFzczogJy5ub3RpY2UtZGlzbWlzcycsXG5cdFx0XHRidXR0b25faGlkZV9jbGFzczogJ1tkYXRhLXBlcm1hbmVudGx5XScsXG5cdFx0fTtcblx0XHR0aGlzLmV2ZW50cyAgID0ge1xuXHRcdFx0Y2xpY2tfb25fY2xvc2U6IHRoaXMuY2xpY2tfb25fY2xvc2UuYmluZCggdGhpcyApLFxuXHRcdH07XG5cblx0XHRyZXR1cm4gdHJ1ZTtcblx0fVxuXG5cdHNldF9ldmVudHMoKSB7XG5cdFx0dGhpcy5ub3RpY2UuYWRkRXZlbnRMaXN0ZW5lciggJ2NsaWNrJywgdGhpcy5ldmVudHMuY2xpY2tfb25fY2xvc2UgKTtcblx0fVxuXG5cdGNsaWNrX29uX2Nsb3NlKCBlICkge1xuXHRcdGNvbnN0IHsgYnV0dG9uX2Nsb3NlX2NsYXNzLCBidXR0b25faGlkZV9jbGFzcyB9ID0gdGhpcy5zZXR0aW5ncztcblxuXHRcdGlmICggZS50YXJnZXQubWF0Y2hlcyggYnV0dG9uX2Nsb3NlX2NsYXNzICkgKSB7XG5cdFx0XHR0aGlzLm5vdGljZS5yZW1vdmVFdmVudExpc3RlbmVyKCAnY2xpY2snLCB0aGlzLmV2ZW50cy5jbGlja19vbl9jbG9zZSApO1xuXHRcdFx0dGhpcy5oaWRlX25vdGljZSggZmFsc2UgKTtcblx0XHR9IGVsc2UgaWYgKCBlLnRhcmdldC5tYXRjaGVzKCBidXR0b25faGlkZV9jbGFzcyApICkge1xuXHRcdFx0dGhpcy5ub3RpY2UucmVtb3ZlRXZlbnRMaXN0ZW5lciggJ2NsaWNrJywgdGhpcy5ldmVudHMuY2xpY2tfb25fY2xvc2UgKTtcblx0XHRcdHRoaXMuaGlkZV9ub3RpY2UoIHRydWUgKTtcblx0XHR9XG5cdH1cblxuXHRoaWRlX25vdGljZSggaXNfcGVybWFuZW50bHkgKSB7XG5cdFx0Y29uc3QgeyBidXR0b25fY2xvc2VfY2xhc3MgfSA9IHRoaXMuc2V0dGluZ3M7XG5cblx0XHR0aGlzLnNlbmRfcmVxdWVzdCggaXNfcGVybWFuZW50bHkgKTtcblx0XHRpZiAoIGlzX3Blcm1hbmVudGx5ICkge1xuXHRcdFx0dGhpcy5ub3RpY2UucXVlcnlTZWxlY3RvciggYnV0dG9uX2Nsb3NlX2NsYXNzICkuY2xpY2soKTtcblx0XHR9XG5cdH1cblxuXHRzZW5kX3JlcXVlc3QoIGlzX3Blcm1hbmVudGx5ICkge1xuXHRcdGNvbnN0IHsgYWpheF91cmwgfSA9IHRoaXMuc2V0dGluZ3M7XG5cblx0XHRheGlvcygge1xuXHRcdFx0bWV0aG9kOiAnUE9TVCcsXG5cdFx0XHR1cmw6IGFqYXhfdXJsLFxuXHRcdFx0ZGF0YTogdGhpcy5nZXRfZGF0YV9mb3JfcmVxdWVzdCggaXNfcGVybWFuZW50bHkgKSxcblx0XHR9ICk7XG5cdH1cblxuXHRnZXRfZGF0YV9mb3JfcmVxdWVzdCggaXNfcGVybWFuZW50bHkgKSB7XG5cdFx0Y29uc3QgeyBhamF4X2FjdGlvbiB9ID0gdGhpcy5zZXR0aW5ncztcblxuXHRcdGNvbnN0IGZvcm1fZGF0YSA9IG5ldyBGb3JtRGF0YSgpO1xuXHRcdGZvcm1fZGF0YS5hcHBlbmQoICdhY3Rpb24nLCBhamF4X2FjdGlvbiApO1xuXHRcdGZvcm1fZGF0YS5hcHBlbmQoICdpc19wZXJtYW5lbnRseScsICggaXNfcGVybWFuZW50bHkgKSA/IDEgOiAwICk7XG5cblx0XHRyZXR1cm4gZm9ybV9kYXRhO1xuXHR9XG59XG5cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIEFkbWluTm90aWNlTWFuYWdlciB7XG5cblx0Y29uc3RydWN0b3IoKSB7XG5cdFx0Y29uc3Qgbm90aWNlcyAgICA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoICcubm90aWNlW2RhdGEtbm90aWNlPVwid2VicC1jb252ZXJ0ZXItZm9yLW1lZGlhXCJdW2RhdGEtbm90aWNlLWFjdGlvbl0nICk7XG5cdFx0Y29uc3QgeyBsZW5ndGggfSA9IG5vdGljZXM7XG5cdFx0Zm9yICggbGV0IGkgPSAwOyBpIDwgbGVuZ3RoOyBpKysgKSB7XG5cdFx0XHRuZXcgQWRtaW5Ob3RpY2VNYW5hZ2VyQ29yZSggbm90aWNlc1sgaSBdICk7XG5cdFx0fVxuXHR9XG59XG4iLCJleHBvcnQgZGVmYXVsdCBjbGFzcyBDb252ZXJzaW9uU3RhdHNNYW5hZ2VyIHtcblxuXHRjb25zdHJ1Y3RvcigpIHtcblx0XHR0aGlzLnN0YXR1cyA9IHRoaXMuc2V0X3ZhcnMoKTtcblx0fVxuXG5cdHNldF92YXJzKCkge1xuXHRcdHRoaXMuY291bnRlcl93ZWJwID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvciggJ1tkYXRhLWNvdW50ZXI9XCJ3ZWJwXCJdJyApO1xuXHRcdHRoaXMuY291bnRlcl9hdmlmID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvciggJ1tkYXRhLWNvdW50ZXI9XCJhdmlmXCJdJyApO1xuXHRcdGlmICggISB0aGlzLmNvdW50ZXJfd2VicCB8fCAhIHRoaXMuY291bnRlcl9hdmlmICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdHRoaXMuY291bnRlcl93ZWJwX3BlcmNlbnQgPSB0aGlzLmNvdW50ZXJfd2VicC5xdWVyeVNlbGVjdG9yKCAnW2RhdGEtY291bnRlci1wZXJjZW50XScgKTtcblx0XHR0aGlzLmNvdW50ZXJfd2VicF9pbWFnZXMgID0gdGhpcy5jb3VudGVyX3dlYnAucXVlcnlTZWxlY3RvciggJ1tkYXRhLWNvdW50ZXItbGVmdF0nICk7XG5cdFx0dGhpcy5jb3VudGVyX3dlYnBfbG9hZGVyICA9IHRoaXMuY291bnRlcl93ZWJwLnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS1jb3VudGVyLWxvYWRlcl0nICk7XG5cdFx0dGhpcy5jb3VudGVyX2F2aWZfcGVyY2VudCA9IHRoaXMuY291bnRlcl9hdmlmLnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS1jb3VudGVyLXBlcmNlbnRdJyApO1xuXHRcdHRoaXMuY291bnRlcl9hdmlmX2ltYWdlcyAgPSB0aGlzLmNvdW50ZXJfYXZpZi5xdWVyeVNlbGVjdG9yKCAnW2RhdGEtY291bnRlci1sZWZ0XScgKTtcblx0XHR0aGlzLmNvdW50ZXJfYXZpZl9sb2FkZXIgID0gdGhpcy5jb3VudGVyX2F2aWYucXVlcnlTZWxlY3RvciggJ1tkYXRhLWNvdW50ZXItbG9hZGVyXScgKTtcblxuXHRcdHRoaXMuZGF0YSA9IHtcblx0XHRcdHdlYnBfY29udmVydGVkOiAwLFxuXHRcdFx0d2VicF91bmNvbnZlcnRlZDogMCxcblx0XHRcdHdlYnBfYWxsOiAwLFxuXHRcdFx0YXZpZl9jb252ZXJ0ZWQ6IDAsXG5cdFx0XHRhdmlmX3VuY29udmVydGVkOiAwLFxuXHRcdFx0YXZpZl9hbGw6IDAsXG5cdFx0fTtcblx0XHR0aGlzLmF0dHMgPSB7XG5cdFx0XHRjb3VudGVyX3BlcmNlbnQ6ICdkYXRhLXBlcmNlbnQnLFxuXHRcdH07XG5cblx0XHRyZXR1cm4gdHJ1ZTtcblx0fVxuXG5cdHNldF9maWxlc193ZWJwKCBjb3VudF9jb252ZXJ0ZWQsIGNvdW50X2FsbCApIHtcblx0XHRpZiAoICEgdGhpcy5zdGF0dXMgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0dGhpcy5kYXRhLndlYnBfY29udmVydGVkICs9IGNvdW50X2NvbnZlcnRlZDtcblx0XHR0aGlzLmRhdGEud2VicF91bmNvbnZlcnRlZCA9ICggY291bnRfYWxsIC0gY291bnRfY29udmVydGVkICk7XG5cdFx0dGhpcy5kYXRhLndlYnBfYWxsICAgICAgICAgPSBjb3VudF9hbGwgfHwgdGhpcy5kYXRhLndlYnBfYWxsO1xuXHRcdHRoaXMucmVmcmVzaF9zdGF0cygpO1xuXHR9XG5cblx0cmVzZXRfZmlsZXNfd2VicCgpIHtcblx0XHRpZiAoICEgdGhpcy5zdGF0dXMgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0dGhpcy5kYXRhLndlYnBfY29udmVydGVkICAgPSAwO1xuXHRcdHRoaXMuZGF0YS53ZWJwX3VuY29udmVydGVkID0gdGhpcy5kYXRhLndlYnBfYWxsO1xuXHRcdHRoaXMucmVmcmVzaF9zdGF0cygpO1xuXHR9XG5cblx0c2V0X2ZpbGVzX2F2aWYoIGNvdW50X2NvbnZlcnRlZCwgY291bnRfYWxsICkge1xuXHRcdGlmICggISB0aGlzLnN0YXR1cyApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHR0aGlzLmRhdGEuYXZpZl9jb252ZXJ0ZWQgKz0gY291bnRfY29udmVydGVkO1xuXHRcdHRoaXMuZGF0YS5hdmlmX3VuY29udmVydGVkID0gKCBjb3VudF9hbGwgLSBjb3VudF9jb252ZXJ0ZWQgKTtcblx0XHR0aGlzLmRhdGEuYXZpZl9hbGwgICAgICAgICA9IGNvdW50X2FsbDtcblx0XHR0aGlzLnJlZnJlc2hfc3RhdHMoKTtcblx0fVxuXG5cdHNldF9lcnJvcigpIHtcblx0XHR0aGlzLmNvdW50ZXJfd2VicF9sb2FkZXIuc2V0QXR0cmlidXRlKCAnaGlkZGVuJywgJ2hpZGRlbicgKTtcblx0XHR0aGlzLmNvdW50ZXJfYXZpZl9sb2FkZXIuc2V0QXR0cmlidXRlKCAnaGlkZGVuJywgJ2hpZGRlbicgKTtcblx0fVxuXG5cdHJlc2V0X2ZpbGVzX2F2aWYoKSB7XG5cdFx0aWYgKCAhIHRoaXMuc3RhdHVzICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdHRoaXMuZGF0YS5hdmlmX2NvbnZlcnRlZCAgID0gMDtcblx0XHR0aGlzLmRhdGEuYXZpZl91bmNvbnZlcnRlZCA9IHRoaXMuZGF0YS5hdmlmX2FsbDtcblx0XHR0aGlzLnJlZnJlc2hfc3RhdHMoKTtcblx0fVxuXG5cdGFkZF9maWxlc193ZWJwKCBjb3VudF9zdWNjZXNzZnVsICkge1xuXHRcdGlmICggISB0aGlzLnN0YXR1cyApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHR0aGlzLmRhdGEud2VicF9jb252ZXJ0ZWQgKz0gY291bnRfc3VjY2Vzc2Z1bDtcblx0XHR0aGlzLmRhdGEud2VicF91bmNvbnZlcnRlZCAtPSBjb3VudF9zdWNjZXNzZnVsO1xuXHRcdHRoaXMucmVmcmVzaF9zdGF0cygpO1xuXHR9XG5cblx0YWRkX2ZpbGVzX2F2aWYoIGNvdW50X3N1Y2Nlc3NmdWwgKSB7XG5cdFx0aWYgKCAhIHRoaXMuc3RhdHVzICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdHRoaXMuZGF0YS5hdmlmX2NvbnZlcnRlZCArPSBjb3VudF9zdWNjZXNzZnVsO1xuXHRcdHRoaXMuZGF0YS5hdmlmX3VuY29udmVydGVkIC09IGNvdW50X3N1Y2Nlc3NmdWw7XG5cdFx0dGhpcy5yZWZyZXNoX3N0YXRzKCk7XG5cdH1cblxuXHRyZWZyZXNoX3N0YXRzKCkge1xuXHRcdGNvbnN0IHsgd2VicF9jb252ZXJ0ZWQsIHdlYnBfdW5jb252ZXJ0ZWQsIHdlYnBfYWxsLCBhdmlmX2NvbnZlcnRlZCwgYXZpZl91bmNvbnZlcnRlZCwgYXZpZl9hbGwgfSA9IHRoaXMuZGF0YTtcblx0XHRjb25zdCB7IGNvdW50ZXJfcGVyY2VudCB9ICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgPSB0aGlzLmF0dHM7XG5cblx0XHRjb25zdCBwZXJjZW50X3dlYnAgPSAoIHdlYnBfYWxsID4gMCApID8gTWF0aC5mbG9vciggKCB3ZWJwX2NvbnZlcnRlZCAvIHdlYnBfYWxsICkgKiAxMDAgKSA6IDA7XG5cdFx0Y29uc3QgcGVyY2VudF9hdmlmID0gKCBhdmlmX2FsbCA+IDAgKSA/IE1hdGguZmxvb3IoICggYXZpZl9jb252ZXJ0ZWQgLyBhdmlmX2FsbCApICogMTAwICkgOiAwO1xuXG5cdFx0dGhpcy5jb3VudGVyX3dlYnAuc2V0QXR0cmlidXRlKCBjb3VudGVyX3BlcmNlbnQsIHBlcmNlbnRfd2VicCApO1xuXHRcdHRoaXMuY291bnRlcl93ZWJwX3BlcmNlbnQuaW5uZXJUZXh0ID0gcGVyY2VudF93ZWJwO1xuXHRcdHRoaXMuY291bnRlcl93ZWJwX2ltYWdlcy5pbm5lclRleHQgID0gTWF0aC5tYXgoIHdlYnBfdW5jb252ZXJ0ZWQsIDAgKS50b1N0cmluZygpLnJlcGxhY2UoIC9cXEIoPz0oXFxkezN9KSsoPyFcXGQpKS9nLCAnICcgKTtcblxuXHRcdHRoaXMuY291bnRlcl9hdmlmLnNldEF0dHJpYnV0ZSggY291bnRlcl9wZXJjZW50LCBwZXJjZW50X2F2aWYgKTtcblx0XHR0aGlzLmNvdW50ZXJfYXZpZl9wZXJjZW50LmlubmVyVGV4dCA9IHBlcmNlbnRfYXZpZjtcblx0XHR0aGlzLmNvdW50ZXJfYXZpZl9pbWFnZXMuaW5uZXJUZXh0ICA9IE1hdGgubWF4KCBhdmlmX3VuY29udmVydGVkLCAwICkudG9TdHJpbmcoKS5yZXBsYWNlKCAvXFxCKD89KFxcZHszfSkrKD8hXFxkKSkvZywgJyAnICk7XG5cdH1cbn1cbiIsImV4cG9ydCBkZWZhdWx0IGNsYXNzIERhdGFUb2dnbGVUcmlnZ2VyIHtcblxuXHRjb25zdHJ1Y3RvcigpIHtcblx0XHRpZiAoICEgdGhpcy5zZXRfdmFycygpICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdHRoaXMuc2V0X2V2ZW50cygpO1xuXHR9XG5cblx0c2V0X3ZhcnMoKSB7XG5cdFx0dGhpcy50cmlnZ2VycyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoICdbZGF0YS10b2dnbGUtdHJpZ2dlcl0nICk7XG5cdFx0aWYgKCAhIHRoaXMudHJpZ2dlcnMubGVuZ3RoICkge1xuXHRcdFx0cmV0dXJuIGZhbHNlO1xuXHRcdH1cblxuXHRcdHRoaXMub3V0cHV0cyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoICdbZGF0YS10b2dnbGUtb3V0cHV0LXZhbHVlc10nICk7XG5cblx0XHRyZXR1cm4gdHJ1ZTtcblx0fVxuXG5cdHNldF9ldmVudHMoKSB7XG5cdFx0Y29uc3QgeyBsZW5ndGggfSA9IHRoaXMudHJpZ2dlcnM7XG5cdFx0Zm9yICggbGV0IGkgPSAwOyBpIDwgbGVuZ3RoOyBpKysgKSB7XG5cdFx0XHR0aGlzLnRyaWdnZXJzWyBpIF0uYWRkRXZlbnRMaXN0ZW5lciggJ2NoYW5nZScsIHRoaXMudG9nZ2xlX291dHB1dC5iaW5kKCB0aGlzICkgKTtcblx0XHR9XG5cdH1cblxuXHR0b2dnbGVfb3V0cHV0KCBlICkge1xuXHRcdGNvbnN0IGZpZWxkX25hbWUgICA9IGUuY3VycmVudFRhcmdldC5nZXRBdHRyaWJ1dGUoICduYW1lJyApO1xuXHRcdGNvbnN0IGZpZWxkX3R5cGUgICA9IGUuY3VycmVudFRhcmdldC5nZXRBdHRyaWJ1dGUoICd0eXBlJyApO1xuXHRcdGNvbnN0IGZpZWxkX3ZhbHVlcyA9ICggZmllbGRfdHlwZSA9PT0gJ2NoZWNrYm94JyApID8gdGhpcy5nZXRfY2hlY2tib3hfdmFsdWUoIGZpZWxkX25hbWUgKSA6IHRoaXMuZ2V0X3JhZGlvX3ZhbHVlKCBmaWVsZF9uYW1lICk7XG5cblx0XHRjb25zdCB7IGxlbmd0aCB9ID0gdGhpcy5vdXRwdXRzO1xuXHRcdGZvciAoIGxldCBpID0gMDsgaSA8IGxlbmd0aDsgaSsrICkge1xuXHRcdFx0aWYgKCAoIHRoaXMub3V0cHV0c1sgaSBdLmdldEF0dHJpYnV0ZSggJ2RhdGEtdG9nZ2xlLW91dHB1dCcgKSA9PT0gZmllbGRfbmFtZSApICkge1xuXHRcdFx0XHRjb25zdCBvdXRwdXRfYXR0ciA9IHRoaXMub3V0cHV0c1sgaSBdLmdldEF0dHJpYnV0ZSggJ2RhdGEtdG9nZ2xlLW91dHB1dC1hdHRyJyApO1xuXHRcdFx0XHRpZiAoIHRoaXMudmFsaWRhdGVfb3V0cHV0KCB0aGlzLm91dHB1dHNbIGkgXSwgZmllbGRfdmFsdWVzICkgKSB7XG5cdFx0XHRcdFx0dGhpcy5vdXRwdXRzWyBpIF0ucmVtb3ZlQXR0cmlidXRlKCBvdXRwdXRfYXR0ciApO1xuXHRcdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRcdHRoaXMub3V0cHV0c1sgaSBdLnNldEF0dHJpYnV0ZSggb3V0cHV0X2F0dHIsIG91dHB1dF9hdHRyICk7XG5cdFx0XHRcdH1cblx0XHRcdH1cblx0XHR9XG5cdH1cblxuXHRnZXRfY2hlY2tib3hfdmFsdWUoIGZpZWxkX25hbWUgKSB7XG5cdFx0Y29uc3QgdmFsdWVzICAgICA9IFtdO1xuXHRcdGNvbnN0IHsgbGVuZ3RoIH0gPSB0aGlzLnRyaWdnZXJzO1xuXHRcdGZvciAoIGxldCBpID0gMDsgaSA8IGxlbmd0aDsgaSsrICkge1xuXHRcdFx0aWYgKCAoIHRoaXMudHJpZ2dlcnNbIGkgXS5nZXRBdHRyaWJ1dGUoICduYW1lJyApID09PSBmaWVsZF9uYW1lICkgJiYgdGhpcy50cmlnZ2Vyc1sgaSBdLmNoZWNrZWQgKSB7XG5cdFx0XHRcdHZhbHVlcy5wdXNoKCB0aGlzLnRyaWdnZXJzWyBpIF0uZ2V0QXR0cmlidXRlKCAndmFsdWUnICkgKTtcblx0XHRcdH1cblx0XHR9XG5cblx0XHRyZXR1cm4gdmFsdWVzO1xuXHR9XG5cblx0Z2V0X3JhZGlvX3ZhbHVlKCBmaWVsZF9uYW1lICkge1xuXHRcdGNvbnN0IHsgbGVuZ3RoIH0gPSB0aGlzLnRyaWdnZXJzO1xuXHRcdGZvciAoIGxldCBpID0gMDsgaSA8IGxlbmd0aDsgaSsrICkge1xuXHRcdFx0aWYgKCAoIHRoaXMudHJpZ2dlcnNbIGkgXS5nZXRBdHRyaWJ1dGUoICduYW1lJyApID09PSBmaWVsZF9uYW1lICkgJiYgdGhpcy50cmlnZ2Vyc1sgaSBdLmNoZWNrZWQgKSB7XG5cdFx0XHRcdHJldHVybiBbIHRoaXMudHJpZ2dlcnNbIGkgXS5nZXRBdHRyaWJ1dGUoICd2YWx1ZScgKSBdO1xuXHRcdFx0fVxuXHRcdH1cblxuXHRcdHJldHVybiBbXTtcblx0fVxuXG5cdHZhbGlkYXRlX291dHB1dCggb3V0cHV0LCBmaWVsZF92YWx1ZXMgKSB7XG5cdFx0Y29uc3QgYXZhaWxhYmxlX3ZhbHVlcyA9IG91dHB1dC5nZXRBdHRyaWJ1dGUoICdkYXRhLXRvZ2dsZS1vdXRwdXQtdmFsdWVzJyApLnNwbGl0KCAnOycgKTtcblx0XHRjb25zdCB7IGxlbmd0aCB9ICAgICAgID0gZmllbGRfdmFsdWVzO1xuXHRcdGZvciAoIGxldCBpID0gMDsgaSA8IGxlbmd0aDsgaSsrICkge1xuXHRcdFx0aWYgKCBhdmFpbGFibGVfdmFsdWVzLmluZGV4T2YoIGZpZWxkX3ZhbHVlc1sgaSBdICkgPj0gMCApIHtcblx0XHRcdFx0cmV0dXJuIHRydWU7XG5cdFx0XHR9XG5cdFx0fVxuXG5cdFx0cmV0dXJuIGZhbHNlO1xuXHR9XG59XG4iLCJpbXBvcnQgYXhpb3MgZnJvbSAnYXhpb3MnO1xuXG5leHBvcnQgZGVmYXVsdCBjbGFzcyBJbWFnZUNvbnZlcnNpb25NYW5hZ2VyIHtcblxuXHQvKipcblx0ICogQHBhcmFtIHtDb252ZXJzaW9uU3RhdHNNYW5hZ2VyfSBjb252ZXJzaW9uX3N0YXRzX21hbmFnZXJcblx0ICovXG5cdGNvbnN0cnVjdG9yKCBjb252ZXJzaW9uX3N0YXRzX21hbmFnZXIgKSB7XG5cdFx0dGhpcy5jb252ZXJzaW9uX3N0YXRzX21hbmFnZXIgPSBjb252ZXJzaW9uX3N0YXRzX21hbmFnZXI7XG5cdFx0aWYgKCAhIHRoaXMuc2V0X3ZhcnMoKSApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHR0aGlzLnNldF9ldmVudHMoKTtcblx0fVxuXG5cdHNldF92YXJzKCkge1xuXHRcdHRoaXMuc2VjdGlvbiA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoICcud2VicGNMb2FkZXInICk7XG5cdFx0aWYgKCAhIHRoaXMuc2VjdGlvbiApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHR0aGlzLndyYXBwZXJfc3RhdHVzID0gdGhpcy5zZWN0aW9uLnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS1zdGF0dXNdJyApO1xuXHRcdGlmICggISB0aGlzLndyYXBwZXJfc3RhdHVzICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdHRoaXMucHJvZ3Jlc3MgICAgICAgICA9IHRoaXMud3JhcHBlcl9zdGF0dXMucXVlcnlTZWxlY3RvciggJ1tkYXRhLXN0YXR1cy1wcm9ncmVzc10nICk7XG5cdFx0dGhpcy5wcm9ncmVzc19zaXplICAgID0gdGhpcy5zZWN0aW9uLnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS1zdGF0dXMtY291bnQtc2l6ZV0nICk7XG5cdFx0dGhpcy5wcm9ncmVzc19zdWNjZXNzID0gdGhpcy5zZWN0aW9uLnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS1zdGF0dXMtY291bnQtc3VjY2Vzc10nICk7XG5cdFx0dGhpcy5wcm9ncmVzc19mYWlsZWQgID0gdGhpcy5zZWN0aW9uLnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS1zdGF0dXMtY291bnQtZXJyb3JdJyApO1xuXHRcdHRoaXMud3JhcHBlcl9lcnJvcnMgICA9IHRoaXMuc2VjdGlvbi5xdWVyeVNlbGVjdG9yKCAnW2RhdGEtZXJyb3JzXScgKTtcblx0XHR0aGlzLmVycm9yc19vdXRwdXQgICAgPSB0aGlzLndyYXBwZXJfZXJyb3JzLnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS1lcnJvcnMtb3V0cHV0XScgKTtcblx0XHR0aGlzLndyYXBwZXJfc3VjY2VzcyAgPSB0aGlzLnNlY3Rpb24ucXVlcnlTZWxlY3RvciggJ1tkYXRhLXN1Y2Nlc3NdJyApO1xuXHRcdHRoaXMub3B0aW9uX2ZvcmNlICAgICA9IHRoaXMuc2VjdGlvbi5xdWVyeVNlbGVjdG9yKCAnaW5wdXRbbmFtZT1cInJlZ2VuZXJhdGVfZm9yY2VcIl0nICk7XG5cdFx0dGhpcy5zdWJtaXRfYnV0dG9uICAgID0gdGhpcy5zZWN0aW9uLnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS1zdWJtaXRdJyApO1xuXG5cdFx0dGhpcy5kYXRhICAgICA9IHtcblx0XHRcdGNvdW50OiAwLFxuXHRcdFx0bWF4OiAwLFxuXHRcdFx0aXRlbXM6IFtdLFxuXHRcdFx0c2l6ZToge1xuXHRcdFx0XHRiZWZvcmU6IDAsXG5cdFx0XHRcdGFmdGVyOiAwLFxuXHRcdFx0fSxcblx0XHRcdGZpbGVzX2NvdW50ZXI6IHtcblx0XHRcdFx0YWxsOiAwLFxuXHRcdFx0XHRjb252ZXJ0ZWQ6IDAsXG5cdFx0XHR9LFxuXHRcdFx0ZXJyb3JzOiAwLFxuXHRcdH07XG5cdFx0dGhpcy5zZXR0aW5ncyA9IHtcblx0XHRcdGlzX2Rpc2FibGVkOiBmYWxzZSxcblx0XHRcdGFqYXg6IHtcblx0XHRcdFx0dXJsX3BhdGhzOiB0aGlzLnNlY3Rpb24uZ2V0QXR0cmlidXRlKCAnZGF0YS1hcGktcGF0aHMnICkuc3BsaXQoICd8JyApWzBdLFxuXHRcdFx0XHR1cmxfcGF0aHNfbm9uY2U6IHRoaXMuc2VjdGlvbi5nZXRBdHRyaWJ1dGUoICdkYXRhLWFwaS1wYXRocycgKS5zcGxpdCggJ3wnIClbMV0sXG5cdFx0XHRcdHVybF9yZWdlbmVyYXRlOiB0aGlzLnNlY3Rpb24uZ2V0QXR0cmlidXRlKCAnZGF0YS1hcGktcmVnZW5lcmF0ZScgKS5zcGxpdCggJ3wnIClbMF0sXG5cdFx0XHRcdHVybF9yZWdlbmVyYXRlX25vbmNlOiB0aGlzLnNlY3Rpb24uZ2V0QXR0cmlidXRlKCAnZGF0YS1hcGktcmVnZW5lcmF0ZScgKS5zcGxpdCggJ3wnIClbMV0sXG5cdFx0XHRcdGVycm9yX21lc3NhZ2U6IHRoaXMuc2VjdGlvbi5nZXRBdHRyaWJ1dGUoICdkYXRhLWFwaS1lcnJvci1tZXNzYWdlJyApLFxuXHRcdFx0fSxcblx0XHRcdHVuaXRzOiBbICdrQicsICdNQicsICdHQicgXSxcblx0XHRcdG1heF9lcnJvcnM6IDEwMDAsXG5cdFx0XHRjb25uZWN0aW9uX3RpbWVvdXQ6IDYwMDAwLFxuXHRcdH07XG5cdFx0dGhpcy5hdHRzICAgICA9IHtcblx0XHRcdHByb2dyZXNzOiAnZGF0YS1wZXJjZW50Jyxcblx0XHRcdGNvdW50ZXJfcGVyY2VudDogJ2RhdGEtcGVyY2VudCcsXG5cdFx0fTtcblx0XHR0aGlzLmNsYXNzZXMgID0ge1xuXHRcdFx0cHJvZ3Jlc3NfZXJyb3I6ICd3ZWJwY0xvYWRlcl9fc3RhdHVzUHJvZ3Jlc3MtLWVycm9yJyxcblx0XHRcdGJ1dHRvbl9kaXNhYmxlZDogJ3dlYnBjTG9hZGVyX19idXR0b24tLWRpc2FibGVkJyxcblx0XHRcdGVycm9yX21lc3NhZ2U6ICd3ZWJwY0xvYWRlcl9fZXJyb3JzQ29udGVudEVycm9yJyxcblx0XHR9O1xuXG5cdFx0cmV0dXJuIHRydWU7XG5cdH1cblxuXHRzZXRfZXZlbnRzKCkge1xuXHRcdHRoaXMuc3VibWl0X2J1dHRvbi5hZGRFdmVudExpc3RlbmVyKCAnY2xpY2snLCB0aGlzLmluaXRfcmVnZW5lcmF0aW9uLmJpbmQoIHRoaXMgKSApO1xuXHR9XG5cblx0aW5pdF9yZWdlbmVyYXRpb24oIGUgKSB7XG5cdFx0ZS5wcmV2ZW50RGVmYXVsdCgpO1xuXHRcdGlmICggdGhpcy5zZXR0aW5ncy5pc19kaXNhYmxlZCApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHR0aGlzLnNldHRpbmdzLmlzX2Rpc2FibGVkID0gdHJ1ZTtcblx0XHR0aGlzLnN1Ym1pdF9idXR0b24uY2xhc3NMaXN0LmFkZCggdGhpcy5jbGFzc2VzLmJ1dHRvbl9kaXNhYmxlZCApO1xuXHRcdHRoaXMub3B0aW9uX2ZvcmNlLnNldEF0dHJpYnV0ZSggJ2Rpc2FibGVkJywgJ2Rpc2FibGVkJyApO1xuXG5cdFx0dGhpcy53cmFwcGVyX3N0YXR1cy5yZW1vdmVBdHRyaWJ1dGUoICdoaWRkZW4nICk7XG5cdFx0dGhpcy5zZW5kX3JlcXVlc3RfZm9yX3BhdGhzKCk7XG5cdH1cblxuXHRzZW5kX3JlcXVlc3RfZm9yX3BhdGhzKCkge1xuXHRcdGNvbnN0IHsgdXJsX3BhdGhzLCB1cmxfcGF0aHNfbm9uY2UgfSA9IHRoaXMuc2V0dGluZ3MuYWpheDtcblxuXHRcdGF4aW9zKCB7XG5cdFx0XHRtZXRob2Q6ICdQT1NUJyxcblx0XHRcdHVybDogdXJsX3BhdGhzLFxuXHRcdFx0ZGF0YToge1xuXHRcdFx0XHRyZWdlbmVyYXRlX2ZvcmNlOiAoIHRoaXMub3B0aW9uX2ZvcmNlLmNoZWNrZWQgKSA/IDEgOiAwLFxuXHRcdFx0fSxcblx0XHRcdGhlYWRlcnM6IHtcblx0XHRcdFx0J1gtV1AtTm9uY2UnOiB1cmxfcGF0aHNfbm9uY2UsXG5cdFx0XHR9LFxuXHRcdH0gKVxuXHRcdFx0LnRoZW4oICggcmVzcG9uc2UgKSA9PiB7XG5cdFx0XHRcdGNvbnN0IHBhdGhzICAgICA9IHRoaXMucGFyc2VfZmlsZXNfcGF0aHMoIHJlc3BvbnNlLmRhdGEgKTtcblx0XHRcdFx0dGhpcy5kYXRhLml0ZW1zID0gcGF0aHM7XG5cdFx0XHRcdHRoaXMuZGF0YS5tYXggICA9IHBhdGhzLmxlbmd0aDtcblxuXHRcdFx0XHRpZiAoIHRoaXMub3B0aW9uX2ZvcmNlLmNoZWNrZWQgKSB7XG5cdFx0XHRcdFx0dGhpcy5jb252ZXJzaW9uX3N0YXRzX21hbmFnZXIucmVzZXRfZmlsZXNfd2VicCgpO1xuXHRcdFx0XHRcdHRoaXMuY29udmVyc2lvbl9zdGF0c19tYW5hZ2VyLnJlc2V0X2ZpbGVzX2F2aWYoKTtcblx0XHRcdFx0fVxuXG5cdFx0XHRcdHRoaXMucmVnZW5lcmF0ZV9uZXh0X2ltYWdlcygpO1xuXHRcdFx0fSApXG5cdFx0XHQuY2F0Y2goICggZXJyb3IgKSA9PiB7XG5cdFx0XHRcdGNvbnNvbGUud2FybiggZXJyb3IgKTtcblx0XHRcdFx0dGhpcy5jYXRjaF9yZXF1ZXN0X2Vycm9yKCBlcnJvciwgdHJ1ZSApXG5cdFx0XHR9ICk7XG5cdH1cblxuXHRwYXJzZV9maWxlc19wYXRocyggcmVzcG9uc2VfZGF0YSApIHtcblx0XHRjb25zdCBwYXRocyA9IFtdO1xuXHRcdGZvciAoIGNvbnN0IGRpcmVjdG9yeSBpbiByZXNwb25zZV9kYXRhICkge1xuXHRcdFx0Y29uc3QgeyBsZW5ndGggfSA9IHJlc3BvbnNlX2RhdGFbIGRpcmVjdG9yeSBdLmZpbGVzO1xuXHRcdFx0Zm9yICggbGV0IGkgPSAwOyBpIDwgbGVuZ3RoOyBpKysgKSB7XG5cdFx0XHRcdGNvbnN0IHBhcnRfcGF0aHMgPSBbXTtcblx0XHRcdFx0Zm9yICggbGV0IGogPSAwOyBqIDwgcmVzcG9uc2VfZGF0YVsgZGlyZWN0b3J5IF0uZmlsZXNbIGkgXS5sZW5ndGg7IGorKyApIHtcblx0XHRcdFx0XHRwYXJ0X3BhdGhzLnB1c2goIHJlc3BvbnNlX2RhdGFbIGRpcmVjdG9yeSBdLnBhdGggKyAnLycgKyByZXNwb25zZV9kYXRhWyBkaXJlY3RvcnkgXS5maWxlc1sgaSBdWyBqIF0gKTtcblx0XHRcdFx0fVxuXHRcdFx0XHRwYXRocy5wdXNoKCBwYXJ0X3BhdGhzICk7XG5cdFx0XHR9XG5cdFx0fVxuXHRcdHJldHVybiBwYXRocztcblx0fVxuXG5cdHJlZ2VuZXJhdGVfbmV4dF9pbWFnZXMoIGF0dGVtcHQgPSAwICkge1xuXHRcdGlmICggdGhpcy5kYXRhLm1heCA9PT0gMCApIHtcblx0XHRcdHRoaXMudXBkYXRlX3Byb2dyZXNzKCk7XG5cdFx0fVxuXHRcdGlmICggdGhpcy5kYXRhLmNvdW50ID49IHRoaXMuZGF0YS5tYXggKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0aWYgKCBhdHRlbXB0ID49IDMgKSB7XG5cdFx0XHRhdHRlbXB0ID0gMDtcblx0XHR9IGVsc2UgaWYgKCBhdHRlbXB0ID4gMCApIHtcblx0XHRcdHRoaXMuZGF0YS5jb3VudC0tO1xuXHRcdH1cblx0XHRjb25zdCBpdGVtcyA9IHRoaXMuZGF0YS5pdGVtc1sgdGhpcy5kYXRhLmNvdW50IF07XG5cdFx0dGhpcy5kYXRhLmNvdW50Kys7XG5cdFx0dGhpcy5zZW5kX3JlcXVlc3RfZm9yX3JlZ2VuZXJhdGlvbiggaXRlbXMsIGF0dGVtcHQgKTtcblx0fVxuXG5cdHNlbmRfcmVxdWVzdF9mb3JfcmVnZW5lcmF0aW9uKCBpdGVtcywgYXR0ZW1wdCApIHtcblx0XHRjb25zdCB7IHVybF9yZWdlbmVyYXRlLCB1cmxfcmVnZW5lcmF0ZV9ub25jZSB9ID0gdGhpcy5zZXR0aW5ncy5hamF4O1xuXG5cdFx0YXhpb3MoIHtcblx0XHRcdG1ldGhvZDogJ1BPU1QnLFxuXHRcdFx0dXJsOiB1cmxfcmVnZW5lcmF0ZSxcblx0XHRcdGRhdGE6IHtcblx0XHRcdFx0cmVnZW5lcmF0ZV9mb3JjZTogKCB0aGlzLm9wdGlvbl9mb3JjZS5jaGVja2VkICkgPyAxIDogMCxcblx0XHRcdFx0cGF0aHM6IGl0ZW1zLFxuXHRcdFx0fSxcblx0XHRcdGhlYWRlcnM6IHtcblx0XHRcdFx0J1gtV1AtTm9uY2UnOiB1cmxfcmVnZW5lcmF0ZV9ub25jZSxcblx0XHRcdH0sXG5cdFx0XHR0aW1lb3V0OiB0aGlzLnNldHRpbmdzLmNvbm5lY3Rpb25fdGltZW91dCxcblx0XHR9IClcblx0XHRcdC50aGVuKCAoIHJlc3BvbnNlICkgPT4ge1xuXHRcdFx0XHRjb25zdCB7IGlzX2ZhdGFsX2Vycm9yIH0gPSByZXNwb25zZS5kYXRhO1xuXHRcdFx0XHR0aGlzLnVwZGF0ZV9lcnJvcnMoIHJlc3BvbnNlLmRhdGEuZXJyb3JzLCBpc19mYXRhbF9lcnJvciApO1xuXG5cdFx0XHRcdGlmICggISBpc19mYXRhbF9lcnJvciApIHtcblx0XHRcdFx0XHR0aGlzLnVwZGF0ZV9zaXplKCByZXNwb25zZS5kYXRhICk7XG5cdFx0XHRcdFx0dGhpcy51cGRhdGVfZmlsZXNfY291bnQoIHJlc3BvbnNlLmRhdGEgKTtcblx0XHRcdFx0XHR0aGlzLnVwZGF0ZV9wcm9ncmVzcygpO1xuXHRcdFx0XHRcdHRoaXMucmVnZW5lcmF0ZV9uZXh0X2ltYWdlcygpO1xuXHRcdFx0XHR9XG5cdFx0XHR9IClcblx0XHRcdC5jYXRjaCggKCBlcnJvciApID0+IHtcblx0XHRcdFx0Y29uc29sZS53YXJuKCBlcnJvciApO1xuXHRcdFx0XHRpZiAoIGVycm9yLnJlc3BvbnNlICkge1xuXHRcdFx0XHRcdHRoaXMuY2F0Y2hfcmVxdWVzdF9lcnJvciggZXJyb3IsIGZhbHNlLCBpdGVtcyApO1xuXHRcdFx0XHRcdHNldFRpbWVvdXQoIHRoaXMucmVnZW5lcmF0ZV9uZXh0X2ltYWdlcy5iaW5kKCB0aGlzICksIDEwMDAgKTtcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHRzZXRUaW1lb3V0KCB0aGlzLnJlZ2VuZXJhdGVfbmV4dF9pbWFnZXMuYmluZCggdGhpcywgKCBhdHRlbXB0ICsgMSApICksIDEwMDAgKTtcblx0XHRcdFx0fVxuXHRcdFx0fSApO1xuXHR9XG5cblx0dXBkYXRlX2Vycm9ycyggZXJyb3JzLCBpc19mYXRhbF9lcnJvciA9IGZhbHNlICkge1xuXHRcdGlmICggdGhpcy5kYXRhLmVycm9ycyA+IHRoaXMuc2V0dGluZ3MubWF4X2Vycm9ycyApIHtcblx0XHRcdHRoaXMuZGF0YS5lcnJvcnMgICAgICAgICAgICAgPSAwO1xuXHRcdFx0dGhpcy5lcnJvcnNfb3V0cHV0LmlubmVySFRNTCA9ICcnO1xuXHRcdH1cblxuXHRcdGNvbnN0IGN1cnJlbnRfZGF0ZSA9IHRoaXMuZ2V0X2RhdGUoKTtcblx0XHRmb3IgKCBsZXQgaSA9IDA7IGkgPCBlcnJvcnMubGVuZ3RoOyBpKysgKSB7XG5cdFx0XHR0aGlzLnByaW50X2Vycm9yX21lc3NhZ2UoIGVycm9yc1sgaSBdLCBpc19mYXRhbF9lcnJvciwgZmFsc2UsIGN1cnJlbnRfZGF0ZSApO1xuXHRcdFx0dGhpcy5kYXRhLmVycm9ycysrO1xuXHRcdH1cblxuXHRcdGlmICggaXNfZmF0YWxfZXJyb3IgKSB7XG5cdFx0XHR0aGlzLnNldF9mYXRhbF9lcnJvcigpO1xuXHRcdH1cblx0fVxuXG5cdGdldF9kYXRlKCkge1xuXHRcdGNvbnN0IGN1cnJlbnRfZGF0ZSA9IG5ldyBEYXRlKCk7XG5cdFx0Y29uc3QgaG91ciAgICAgICAgID0gKCAnMCcgKyBjdXJyZW50X2RhdGUuZ2V0SG91cnMoKSApLnN1YnN0ciggLTIgKTtcblx0XHRjb25zdCBtaW51dGUgICAgICAgPSAoICcwJyArIGN1cnJlbnRfZGF0ZS5nZXRNaW51dGVzKCkgKS5zdWJzdHIoIC0yICk7XG5cdFx0Y29uc3Qgc2Vjb25kICAgICAgID0gKCAnMCcgKyBjdXJyZW50X2RhdGUuZ2V0U2Vjb25kcygpICkuc3Vic3RyKCAtMiApO1xuXG5cdFx0cmV0dXJuIGAkeyBob3VyIH06JHsgbWludXRlIH06JHsgc2Vjb25kIH1gO1xuXHR9XG5cblx0c2V0X2ZhdGFsX2Vycm9yKCkge1xuXHRcdHRoaXMucHJvZ3Jlc3MuY2xhc3NMaXN0LmFkZCggdGhpcy5jbGFzc2VzLnByb2dyZXNzX2Vycm9yICk7XG5cdH1cblxuXHRjYXRjaF9yZXF1ZXN0X2Vycm9yKCBlcnJvciwgaXNfZmF0YWxfZXJyb3IsIHBhcmFtcyA9IG51bGwgKSB7XG5cdFx0aWYgKCBpc19mYXRhbF9lcnJvciApIHtcblx0XHRcdHRoaXMucHJpbnRfZXJyb3JfbWVzc2FnZShcblx0XHRcdFx0WyB0aGlzLnNldHRpbmdzLmFqYXguZXJyb3JfbWVzc2FnZSBdLFxuXHRcdFx0XHR0cnVlLFxuXHRcdFx0XHRmYWxzZVxuXHRcdFx0KTtcblx0XHRcdHRoaXMuc2V0X2ZhdGFsX2Vycm9yKCk7XG5cdFx0fVxuXG5cdFx0Y29uc3QgcGFyYW1zX3ZhbHVlID0gKCBwYXJhbXMgIT09IG51bGwgKSA/IGBbXCIkeyBwYXJhbXMuam9pbiggJ1wiLCBcIicgKSB9XCJdYCA6ICcnO1xuXHRcdHRoaXMucHJpbnRfZXJyb3JfbWVzc2FnZShcblx0XHRcdGAkeyBlcnJvci5yZXNwb25zZS5zdGF0dXMgfSAtICR7IGVycm9yLnJlc3BvbnNlLnN0YXR1c1RleHQgfSAoJHsgZXJyb3IucmVzcG9uc2UuY29uZmlnLnVybCB9KSAkeyBwYXJhbXNfdmFsdWUgfWAsXG5cdFx0XHR0cnVlLFxuXHRcdFx0dHJ1ZVxuXHRcdCk7XG5cdH1cblxuXHRwcmludF9lcnJvcl9tZXNzYWdlKCBlcnJvcl9tZXNzYWdlLCBpc19mYXRhbF9lcnJvciwgaGFzX3ByZV93cmFwcGVyLCBjdXJyZW50X2RhdGUgPSBudWxsICkge1xuXHRcdGNvbnN0IGVsZW1lbnQgICAgID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCggJ3AnICk7XG5cdFx0Y29uc3QgZGF0ZV9wcmVmaXggPSAoIGN1cnJlbnRfZGF0ZSApID8gY3VycmVudF9kYXRlIDogdGhpcy5nZXRfZGF0ZSgpO1xuXG5cdFx0aWYgKCBoYXNfcHJlX3dyYXBwZXIgKSB7XG5cdFx0XHRjb25zdCBwcmVfZWxlbWVudCAgICAgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCAncHJlJyApO1xuXHRcdFx0cHJlX2VsZW1lbnQuaW5uZXJUZXh0ID0gZXJyb3JfbWVzc2FnZTtcblx0XHRcdGVsZW1lbnQuYXBwZW5kQ2hpbGQoIHByZV9lbGVtZW50ICk7XG5cdFx0fSBlbHNlIHtcblx0XHRcdGVsZW1lbnQuaW5uZXJIVE1MID0gYDxzdHJvbmc+JHsgZGF0ZV9wcmVmaXggfTwvc3Ryb25nPiAtICR7IGVycm9yX21lc3NhZ2UgfWA7XG5cdFx0fVxuXG5cdFx0aWYgKCBpc19mYXRhbF9lcnJvciApIHtcblx0XHRcdGVsZW1lbnQuY2xhc3NMaXN0LmFkZCggdGhpcy5jbGFzc2VzLmVycm9yX21lc3NhZ2UgKTtcblx0XHR9XG5cblx0XHR0aGlzLndyYXBwZXJfZXJyb3JzLnJlbW92ZUF0dHJpYnV0ZSggJ2hpZGRlbicgKTtcblx0XHR0aGlzLmVycm9yc19vdXRwdXQuYXBwZW5kQ2hpbGQoIGVsZW1lbnQgKTtcblx0fVxuXG5cdHVwZGF0ZV9zaXplKCByZXNwb25zZV9kYXRhICkge1xuXHRcdGNvbnN0IHsgc2l6ZSB9ID0gdGhpcy5kYXRhO1xuXHRcdHNpemUuYmVmb3JlICs9IHJlc3BvbnNlX2RhdGEuc2l6ZS5iZWZvcmU7XG5cdFx0c2l6ZS5hZnRlciArPSByZXNwb25zZV9kYXRhLnNpemUuYWZ0ZXI7XG5cblx0XHRsZXQgYnl0ZXMgPSBzaXplLmJlZm9yZSAtIHNpemUuYWZ0ZXI7XG5cdFx0aWYgKCBieXRlcyA8IDAgKSB7XG5cdFx0XHRieXRlcyA9IDA7XG5cdFx0fVxuXHRcdGlmICggYnl0ZXMgPT09IDAgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0bGV0IHBlcmNlbnQgPSBNYXRoLnJvdW5kKCAoIDEgLSAoIHNpemUuYWZ0ZXIgLyBzaXplLmJlZm9yZSApICkgKiAxMDAgKTtcblx0XHRpZiAoIHBlcmNlbnQgPCAwICkge1xuXHRcdFx0cGVyY2VudCA9IDA7XG5cdFx0fVxuXG5cdFx0bGV0IGluZGV4ID0gLTE7XG5cdFx0ZG8ge1xuXHRcdFx0aW5kZXgrKztcblx0XHRcdGJ5dGVzIC89IDEwMjQ7XG5cdFx0fSB3aGlsZSAoIGJ5dGVzID4gMTAyNCApO1xuXG5cdFx0Y29uc3QgbnVtYmVyICAgICAgICAgICAgICAgICA9IGJ5dGVzLnRvRml4ZWQoIDIgKTtcblx0XHRjb25zdCB1bml0ICAgICAgICAgICAgICAgICAgID0gdGhpcy5zZXR0aW5ncy51bml0c1sgaW5kZXggXTtcblx0XHR0aGlzLnByb2dyZXNzX3NpemUuaW5uZXJIVE1MID0gYCR7IG51bWJlciB9ICR7IHVuaXQgfSAoJHsgcGVyY2VudCB9JSlgO1xuXHR9XG5cblx0dXBkYXRlX2ZpbGVzX2NvdW50KCByZXNwb25zZV9kYXRhICkge1xuXHRcdGNvbnN0IHsgZmlsZXNfY291bnRlciB9ID0gdGhpcy5kYXRhO1xuXHRcdGNvbnN0IHsgZmlsZXMgfSAgICAgICAgID0gcmVzcG9uc2VfZGF0YTtcblxuXHRcdHRoaXMuY29udmVyc2lvbl9zdGF0c19tYW5hZ2VyLmFkZF9maWxlc193ZWJwKCBmaWxlcy53ZWJwX2F2YWlsYWJsZSApO1xuXHRcdHRoaXMuY29udmVyc2lvbl9zdGF0c19tYW5hZ2VyLmFkZF9maWxlc19hdmlmKCBmaWxlcy5hdmlmX2F2YWlsYWJsZSApO1xuXG5cdFx0ZmlsZXNfY291bnRlci5jb252ZXJ0ZWQgKz0gZmlsZXMud2VicF9jb252ZXJ0ZWQgKyBmaWxlcy5hdmlmX2NvbnZlcnRlZDtcblx0XHRmaWxlc19jb3VudGVyLmFsbCArPSBmaWxlcy53ZWJwX2F2YWlsYWJsZSArIGZpbGVzLmF2aWZfYXZhaWxhYmxlO1xuXG5cdFx0dGhpcy5wcm9ncmVzc19zdWNjZXNzLmlubmVyVGV4dCA9IGZpbGVzX2NvdW50ZXIuY29udmVydGVkLnRvU3RyaW5nKCkucmVwbGFjZSggL1xcQig/PShcXGR7M30pKyg/IVxcZCkpL2csICcgJyApO1xuXHRcdHRoaXMucHJvZ3Jlc3NfZmFpbGVkLmlubmVyVGV4dCAgPSAoIGZpbGVzX2NvdW50ZXIuYWxsIC0gZmlsZXNfY291bnRlci5jb252ZXJ0ZWQgKS50b1N0cmluZygpLnJlcGxhY2UoIC9cXEIoPz0oXFxkezN9KSsoPyFcXGQpKS9nLCAnICcgKTtcblx0fVxuXG5cdHVwZGF0ZV9wcm9ncmVzcygpIHtcblx0XHRsZXQgcGVyY2VudCA9ICggdGhpcy5kYXRhLm1heCA+IDAgKSA/IE1hdGguZmxvb3IoICggdGhpcy5kYXRhLmNvdW50IC8gdGhpcy5kYXRhLm1heCApICogMTAwICkgOiAxMDA7XG5cdFx0aWYgKCBwZXJjZW50ID4gMTAwICkge1xuXHRcdFx0cGVyY2VudCA9IDEwMDtcblx0XHR9XG5cblx0XHRpZiAoIHBlcmNlbnQgPT09IDEwMCApIHtcblx0XHRcdHRoaXMud3JhcHBlcl9zdWNjZXNzLnJlbW92ZUF0dHJpYnV0ZSggJ2hpZGRlbicgKTtcblx0XHR9XG5cdFx0dGhpcy5wcm9ncmVzcy5zZXRBdHRyaWJ1dGUoIHRoaXMuYXR0cy5wcm9ncmVzcywgcGVyY2VudC50b1N0cmluZygpICk7XG5cdH1cbn1cbiIsImltcG9ydCBheGlvcyBmcm9tICdheGlvcyc7XG5cbmV4cG9ydCBkZWZhdWx0IGNsYXNzIEltYWdlc1N0YXRzRmV0Y2hlciB7XG5cblx0LyoqXG5cdCAqIEBwYXJhbSB7Q29udmVyc2lvblN0YXRzTWFuYWdlcn0gY29udmVyc2lvbl9zdGF0c19tYW5hZ2VyXG5cdCAqIEBwYXJhbSB7SW1hZ2VzVHJlZUdlbmVyYXRvcn0gaW1hZ2VzX3RyZWVfZ2VuZXJhdG9yXG5cdCAqIEBwYXJhbSB7UGxhbnNCdXR0b25HZW5lcmF0b3J9IHBsYW5zX2J1dHRvbl9nZW5lcmF0b3Jcblx0ICovXG5cdGNvbnN0cnVjdG9yKCBjb252ZXJzaW9uX3N0YXRzX21hbmFnZXIsIGltYWdlc190cmVlX2dlbmVyYXRvciwgcGxhbnNfYnV0dG9uX2dlbmVyYXRvciApIHtcblx0XHR0aGlzLmNvbnZlcnNpb25fc3RhdHNfbWFuYWdlciA9IGNvbnZlcnNpb25fc3RhdHNfbWFuYWdlcjtcblx0XHR0aGlzLmltYWdlc190cmVlX2dlbmVyYXRvciAgICA9IGltYWdlc190cmVlX2dlbmVyYXRvcjtcblx0XHR0aGlzLnBsYW5zX2J1dHRvbl9nZW5lcmF0b3IgICA9IHBsYW5zX2J1dHRvbl9nZW5lcmF0b3I7XG5cdFx0aWYgKCAhIHRoaXMuc2V0X3ZhcnMoKSApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHR0aGlzLnNlbmRfcmVxdWVzdCgpO1xuXHR9XG5cblx0c2V0X3ZhcnMoKSB7XG5cdFx0dGhpcy5zZWN0aW9uID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvciggJ1tkYXRhLWFwaS1zdGF0c10nICk7XG5cdFx0aWYgKCAhIHRoaXMuc2VjdGlvbiApIHtcblx0XHRcdHJldHVybjtcblx0XHR9XG5cblx0XHR0aGlzLmVycm9yX291dHB1dCA9IHRoaXMuc2VjdGlvbi5xdWVyeVNlbGVjdG9yKCAnW2RhdGEtYXBpLXN0YXRzLWVycm9yXScgKTtcblxuXHRcdHRoaXMuc2V0dGluZ3MgPSB7XG5cdFx0XHRhamF4X3VybDogdGhpcy5zZWN0aW9uLmdldEF0dHJpYnV0ZSggJ2RhdGEtYXBpLXN0YXRzJyApLnNwbGl0KCAnfCcgKVswXSxcblx0XHRcdGFqYXhfbm9uY2U6IHRoaXMuc2VjdGlvbi5nZXRBdHRyaWJ1dGUoICdkYXRhLWFwaS1zdGF0cycgKS5zcGxpdCggJ3wnIClbMV0sXG5cdFx0fTtcblxuXHRcdHJldHVybiB0cnVlO1xuXHR9XG5cblx0c2VuZF9yZXF1ZXN0KCBhdHRlbXB0ID0gMCApIHtcblx0XHRpZiAoIGF0dGVtcHQgPj0gMyApIHtcblx0XHRcdHRoaXMuY29udmVyc2lvbl9zdGF0c19tYW5hZ2VyLnNldF9lcnJvcigpO1xuXHRcdFx0dGhpcy5pbWFnZXNfdHJlZV9nZW5lcmF0b3Iuc2V0X2Vycm9yKCk7XG5cdFx0XHR0aGlzLnBsYW5zX2J1dHRvbl9nZW5lcmF0b3Iuc2V0X2Vycm9yKCk7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0Y29uc3Qgc3RhcnRfdGltZSA9IG5ldyBEYXRlKCk7XG5cblx0XHRheGlvcygge1xuXHRcdFx0bWV0aG9kOiAnR0VUJyxcblx0XHRcdHVybDogdGhpcy5zZXR0aW5ncy5hamF4X3VybCxcblx0XHRcdGhlYWRlcnM6IHtcblx0XHRcdFx0J1gtV1AtTm9uY2UnOiB0aGlzLnNldHRpbmdzLmFqYXhfbm9uY2UsXG5cdFx0XHR9LFxuXHRcdH0gKVxuXHRcdFx0LnRoZW4oICggcmVzcG9uc2UgKSA9PiB7XG5cdFx0XHRcdGlmICggcmVzcG9uc2UuZGF0YSApIHtcblx0XHRcdFx0XHRjb25zdCB3ZWJwX2FsbCAgICAgICA9IHJlc3BvbnNlLmRhdGEudmFsdWVfd2VicF9hbGwgfHwgMDtcblx0XHRcdFx0XHRjb25zdCB3ZWJwX2NvbnZlcnRlZCA9IHJlc3BvbnNlLmRhdGEudmFsdWVfd2VicF9jb252ZXJ0ZWQgfHwgMDtcblx0XHRcdFx0XHRjb25zdCBhdmlmX2FsbCAgICAgICA9IHJlc3BvbnNlLmRhdGEudmFsdWVfYXZpZl9hbGwgfHwgMDtcblx0XHRcdFx0XHRjb25zdCBhdmlmX2NvbnZlcnRlZCA9IHJlc3BvbnNlLmRhdGEudmFsdWVfYXZpZl9jb252ZXJ0ZWQgfHwgMDtcblxuXHRcdFx0XHRcdHRoaXMuY29udmVyc2lvbl9zdGF0c19tYW5hZ2VyLnNldF9maWxlc193ZWJwKCB3ZWJwX2NvbnZlcnRlZCwgd2VicF9hbGwgKTtcblx0XHRcdFx0XHR0aGlzLmNvbnZlcnNpb25fc3RhdHNfbWFuYWdlci5zZXRfZmlsZXNfYXZpZiggYXZpZl9jb252ZXJ0ZWQsIGF2aWZfYWxsICk7XG5cdFx0XHRcdFx0dGhpcy5pbWFnZXNfdHJlZV9nZW5lcmF0b3IuZ2VuZXJhdGVfdHJlZSggcmVzcG9uc2UuZGF0YS50cmVlICk7XG5cdFx0XHRcdFx0dGhpcy5wbGFuc19idXR0b25fZ2VuZXJhdG9yLnNob3dfYnV0dG9uKCAoIHdlYnBfYWxsIC0gd2VicF9jb252ZXJ0ZWQgKSwgKCBhdmlmX2FsbCAtIGF2aWZfY29udmVydGVkICkgKTtcblx0XHRcdFx0fSBlbHNlIHtcblx0XHRcdFx0XHR0aGlzLnNlbmRfcmVxdWVzdCggKCBhdHRlbXB0ICsgMSApICk7XG5cdFx0XHRcdFx0dGhpcy5zaG93X3JlcXVlc3RfZXJyb3IoIHN0YXJ0X3RpbWUsIHJlc3BvbnNlICk7XG5cdFx0XHRcdH1cblx0XHRcdH0gKVxuXHRcdFx0LmNhdGNoKCAoIGVycm9yICkgPT4ge1xuXHRcdFx0XHRjb25zb2xlLndhcm4oIGVycm9yICk7XG5cdFx0XHRcdHRoaXMuc2VuZF9yZXF1ZXN0KCAoIGF0dGVtcHQgKyAxICkgKTtcblxuXHRcdFx0XHRpZiAoIGVycm9yLnJlc3BvbnNlICkge1xuXHRcdFx0XHRcdHRoaXMuc2hvd19yZXF1ZXN0X2Vycm9yKCBzdGFydF90aW1lLCBlcnJvci5yZXNwb25zZSApO1xuXHRcdFx0XHR9XG5cdFx0XHR9ICk7XG5cdH1cblxuXHRzaG93X3JlcXVlc3RfZXJyb3IoIHN0YXJ0X3RpbWUsIHJlc3BvbnNlICkge1xuXHRcdGNvbnN0IHJlcXVlc3RfdGltZSAgID0gKCBuZXcgRGF0ZSgpIC0gc3RhcnRfdGltZSApIC8gMTAwMDtcblx0XHRjb25zdCByZXF1ZXN0X3N0YXR1cyA9IHJlc3BvbnNlLnN0YXR1cztcblx0XHRjb25zdCByZXF1ZXN0X2RhdGEgICA9IEpTT04uc3RyaW5naWZ5KCByZXNwb25zZS5kYXRhICk7XG5cblx0XHR0aGlzLmVycm9yX291dHB1dC5pbm5lclRleHQgPSBgSFRUUCBFcnJvciAkeyByZXF1ZXN0X3N0YXR1cyB9ICgkeyByZXF1ZXN0X3RpbWUgfXMpOiAkeyByZXF1ZXN0X2RhdGEgfWA7XG5cdFx0dGhpcy5lcnJvcl9vdXRwdXQucmVtb3ZlQXR0cmlidXRlKCAnaGlkZGVuJyApO1xuXHR9XG59XG4iLCJleHBvcnQgZGVmYXVsdCBjbGFzcyBJbWFnZXNUcmVlR2VuZXJhdG9yIHtcblxuXHRjb25zdHJ1Y3RvcigpIHtcblx0XHR0aGlzLnN0YXR1cyA9IHRoaXMuc2V0X3ZhcnMoKTtcblx0fVxuXG5cdHNldF92YXJzKCkge1xuXHRcdHRoaXMuc2VjdGlvbiA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS10cmVlXScgKTtcblx0XHRpZiAoICEgdGhpcy5zZWN0aW9uICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdHRoaXMubG9hZGVyID0gdGhpcy5zZWN0aW9uLnF1ZXJ5U2VsZWN0b3IoICdbZGF0YS10cmVlLWxvYWRlcl0nICk7XG5cblx0XHRyZXR1cm4gdHJ1ZTtcblx0fVxuXG5cdGdlbmVyYXRlX3RyZWUoIHJlc3BvbnNlX2RhdGEgKSB7XG5cdFx0aWYgKCAhIHRoaXMuc3RhdHVzICkge1xuXHRcdFx0cmV0dXJuO1xuXHRcdH1cblxuXHRcdHRoaXMubG9hZGVyICAgICAgICAgICAgPSBudWxsO1xuXHRcdHRoaXMuc2VjdGlvbi5pbm5lckhUTUwgPSB0aGlzLmRyYXdfdHJlZSggcmVzcG9uc2VfZGF0YSApO1xuXG5cdFx0Y29uc3QgaW5wdXRzID0gdGhpcy5zZWN0aW9uLnF1ZXJ5U2VsZWN0b3JBbGwoICcud2VicGNUcmVlX19pdGVtQ2hlY2tib3gnICk7XG5cdFx0Zm9yICggbGV0IGkgPSAwOyBpIDwgaW5wdXRzLmxlbmd0aDsgaSsrICkge1xuXHRcdFx0aW5wdXRzWyBpIF0uYWRkRXZlbnRMaXN0ZW5lciggJ2NoYW5nZScsICggZSApID0+IHtcblx0XHRcdFx0aWYgKCAhIGUuY3VycmVudFRhcmdldC5jaGVja2VkICkge1xuXHRcdFx0XHRcdGNvbnN0IGlucHV0cyA9IGUuY3VycmVudFRhcmdldC5wYXJlbnROb2RlLnF1ZXJ5U2VsZWN0b3JBbGwoICcud2VicGNUcmVlX19pdGVtQ2hlY2tib3gnICk7XG5cdFx0XHRcdFx0Zm9yICggbGV0IGkgPSAwOyBpIDwgaW5wdXRzLmxlbmd0aDsgaSsrICkge1xuXHRcdFx0XHRcdFx0aW5wdXRzWyBpIF0uY2hlY2tlZCA9IGZhbHNlO1xuXHRcdFx0XHRcdH1cblx0XHRcdFx0fVxuXHRcdFx0fSApO1xuXHRcdH1cblx0fVxuXG5cdHNldF9lcnJvcigpIHtcblx0XHRpZiAoIHRoaXMubG9hZGVyICkge1xuXHRcdFx0dGhpcy5sb2FkZXIuc2V0QXR0cmlidXRlKCAnaGlkZGVuJywgJ2hpZGRlbicgKTtcblx0XHR9XG5cdH1cblxuXHRkcmF3X3RyZWUoIHZhbHVlcywgbGV2ZWwgPSAwLCBpZCA9ICd0cmVlJyApIHtcblx0XHRsZXQgY29udGVudCA9ICcnO1xuXG5cdFx0Y29uc3QgeyBsZW5ndGggfSA9IHZhbHVlcztcblx0XHRpZiAoICEgbGVuZ3RoICkge1xuXHRcdFx0cmV0dXJuIGNvbnRlbnQ7XG5cdFx0fVxuXG5cdFx0aWYgKCBsZXZlbCA9PT0gMCApIHtcblx0XHRcdGNvbnRlbnQgKz0gJzx1bCBjbGFzcz1cIndlYnBjVHJlZV9faXRlbXNcIj4nO1xuXHRcdH1cblxuXHRcdGZvciAoIGxldCBpID0gMDsgaSA8IGxlbmd0aDsgaSsrICkge1xuXHRcdFx0Y29uc3QgaXRlbV9pZCA9IGAkeyBpZCB9LSR7IHZhbHVlc1sgaSBdLm5hbWUgfWAucmVwbGFjZSggL1xccy9nLCAnJyApO1xuXHRcdFx0Y29udGVudCArPSAnPGxpIGNsYXNzPVwid2VicGNUcmVlX19pdGVtXCI+Jztcblx0XHRcdGNvbnRlbnQgKz0gYDxpbnB1dCB0eXBlPVwiY2hlY2tib3hcIiBpZD1cIiR7IGl0ZW1faWQgfVwiIGNsYXNzPVwid2VicGNUcmVlX19pdGVtQ2hlY2tib3hcIj5gO1xuXHRcdFx0Y29udGVudCArPSBgPGxhYmVsIGZvcj1cIiR7IGl0ZW1faWQgfVwiIGNsYXNzPVwid2VicGNUcmVlX19pdGVtTGFiZWxcIj5gO1xuXHRcdFx0Y29udGVudCArPSBgJHsgdmFsdWVzWyBpIF0ubmFtZSB9IDxzdHJvbmc+KCR7IHZhbHVlc1sgaSBdLmNvdW50LnRvU3RyaW5nKCkucmVwbGFjZSggL1xcQig/PShcXGR7M30pKyg/IVxcZCkpL2csICcgJyApIH0pPC9zdHJvbmc+YDtcblx0XHRcdGNvbnRlbnQgKz0gJzwvbGFiZWw+Jztcblx0XHRcdGNvbnRlbnQgKz0gJzx1bCBjbGFzcz1cIndlYnBjVHJlZV9faXRlbXNcIj4nO1xuXHRcdFx0aWYgKCB2YWx1ZXNbIGkgXS5pdGVtcyApIHtcblx0XHRcdFx0Y29udGVudCArPSB0aGlzLmRyYXdfdHJlZSggdmFsdWVzWyBpIF0uaXRlbXMsICggbGV2ZWwgKyAxICksIGl0ZW1faWQgKTtcblx0XHRcdH1cblx0XHRcdGZvciAoIGxldCBqID0gMDsgaiA8IHZhbHVlc1sgaSBdLmZpbGVzLmxlbmd0aDsgaisrICkge1xuXHRcdFx0XHRjb250ZW50ICs9ICc8bGkgY2xhc3M9XCJ3ZWJwY1RyZWVfX2l0ZW1cIj4nO1xuXHRcdFx0XHRjb250ZW50ICs9IGA8c3BhbiBjbGFzcz1cIndlYnBjVHJlZV9faXRlbU5hbWVcIj4keyB2YWx1ZXNbIGkgXS5maWxlc1sgaiBdIH08L3NwYW4+YDtcblx0XHRcdFx0Y29udGVudCArPSAnPC9saT4nO1xuXHRcdFx0fVxuXHRcdFx0Y29udGVudCArPSAnPC91bD4nO1xuXHRcdFx0Y29udGVudCArPSAnPC9saT4nO1xuXHRcdH1cblx0XHRpZiAoIGxldmVsID09PSAwICkge1xuXHRcdFx0Y29udGVudCArPSAnPC91bD4nO1xuXHRcdH1cblxuXHRcdHJldHVybiBjb250ZW50O1xuXHR9XG59XG4iLCJleHBvcnQgZGVmYXVsdCBjbGFzcyBQbGFuc0J1dHRvbkdlbmVyYXRvciB7XG5cblx0Y29uc3RydWN0b3IoKSB7XG5cdFx0dGhpcy5zdGF0dXMgPSB0aGlzLnNldF92YXJzKCk7XG5cdH1cblxuXHRzZXRfdmFycygpIHtcblx0XHR0aGlzLnNlY3Rpb24gPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCAnW2RhdGEtcGxhbnNdJyApO1xuXHRcdGlmICggISB0aGlzLnNlY3Rpb24gKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0dGhpcy5idXR0b24gPSB0aGlzLnNlY3Rpb24ucXVlcnlTZWxlY3RvciggJ1tkYXRhLXBsYW5zLWJ1dHRvbl0nICk7XG5cdFx0dGhpcy5sb2FkZXIgPSB0aGlzLnNlY3Rpb24ucXVlcnlTZWxlY3RvciggJ1tkYXRhLXBsYW5zLWxvYWRlcl0nICk7XG5cblx0XHR0aGlzLnNldHRpbmdzID0ge1xuXHRcdFx0YnV0dG9uX3VybDogdGhpcy5idXR0b24uZ2V0QXR0cmlidXRlKCAnaHJlZicgKSxcblx0XHR9O1xuXG5cdFx0cmV0dXJuIHRydWU7XG5cdH1cblxuXHRzaG93X2J1dHRvbiggd2VicF9jb3VudCwgYXZpZl9jb3VudCApIHtcblx0XHRpZiAoICEgdGhpcy5zdGF0dXMgKSB7XG5cdFx0XHRyZXR1cm47XG5cdFx0fVxuXG5cdFx0Y29uc3QgdXJsID0gdGhpcy5zZXR0aW5ncy5idXR0b25fdXJsXG5cdFx0XHQucmVwbGFjZSggJ3dlYnA9MCcsIGB3ZWJwPSR7IHdlYnBfY291bnQgfWAgKVxuXHRcdFx0LnJlcGxhY2UoICdhdmlmPTAnLCBgYXZpZj0keyBhdmlmX2NvdW50IH1gICk7XG5cblx0XHR0aGlzLmJ1dHRvbi5zZXRBdHRyaWJ1dGUoICdocmVmJywgdXJsICk7XG5cdFx0dGhpcy5idXR0b24ucmVtb3ZlQXR0cmlidXRlKCAnaGlkZGVuJyApO1xuXHRcdHRoaXMubG9hZGVyLnNldEF0dHJpYnV0ZSggJ2hpZGRlbicsICdoaWRkZW4nICk7XG5cdH1cblxuXHRzZXRfZXJyb3IoKSB7XG5cdFx0dGhpcy5idXR0b24ucmVtb3ZlQXR0cmlidXRlKCAnaGlkZGVuJyApO1xuXHRcdHRoaXMubG9hZGVyLnNldEF0dHJpYnV0ZSggJ2hpZGRlbicsICdoaWRkZW4nICk7XG5cdH1cbn1cbiIsIi8vIGV4dHJhY3RlZCBieSBtaW5pLWNzcy1leHRyYWN0LXBsdWdpblxuZXhwb3J0IHt9OyIsIi8vIHNoaW0gZm9yIHVzaW5nIHByb2Nlc3MgaW4gYnJvd3NlclxudmFyIHByb2Nlc3MgPSBtb2R1bGUuZXhwb3J0cyA9IHt9O1xuXG4vLyBjYWNoZWQgZnJvbSB3aGF0ZXZlciBnbG9iYWwgaXMgcHJlc2VudCBzbyB0aGF0IHRlc3QgcnVubmVycyB0aGF0IHN0dWIgaXRcbi8vIGRvbid0IGJyZWFrIHRoaW5ncy4gIEJ1dCB3ZSBuZWVkIHRvIHdyYXAgaXQgaW4gYSB0cnkgY2F0Y2ggaW4gY2FzZSBpdCBpc1xuLy8gd3JhcHBlZCBpbiBzdHJpY3QgbW9kZSBjb2RlIHdoaWNoIGRvZXNuJ3QgZGVmaW5lIGFueSBnbG9iYWxzLiAgSXQncyBpbnNpZGUgYVxuLy8gZnVuY3Rpb24gYmVjYXVzZSB0cnkvY2F0Y2hlcyBkZW9wdGltaXplIGluIGNlcnRhaW4gZW5naW5lcy5cblxudmFyIGNhY2hlZFNldFRpbWVvdXQ7XG52YXIgY2FjaGVkQ2xlYXJUaW1lb3V0O1xuXG5mdW5jdGlvbiBkZWZhdWx0U2V0VGltb3V0KCkge1xuICAgIHRocm93IG5ldyBFcnJvcignc2V0VGltZW91dCBoYXMgbm90IGJlZW4gZGVmaW5lZCcpO1xufVxuZnVuY3Rpb24gZGVmYXVsdENsZWFyVGltZW91dCAoKSB7XG4gICAgdGhyb3cgbmV3IEVycm9yKCdjbGVhclRpbWVvdXQgaGFzIG5vdCBiZWVuIGRlZmluZWQnKTtcbn1cbihmdW5jdGlvbiAoKSB7XG4gICAgdHJ5IHtcbiAgICAgICAgaWYgKHR5cGVvZiBzZXRUaW1lb3V0ID09PSAnZnVuY3Rpb24nKSB7XG4gICAgICAgICAgICBjYWNoZWRTZXRUaW1lb3V0ID0gc2V0VGltZW91dDtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGNhY2hlZFNldFRpbWVvdXQgPSBkZWZhdWx0U2V0VGltb3V0O1xuICAgICAgICB9XG4gICAgfSBjYXRjaCAoZSkge1xuICAgICAgICBjYWNoZWRTZXRUaW1lb3V0ID0gZGVmYXVsdFNldFRpbW91dDtcbiAgICB9XG4gICAgdHJ5IHtcbiAgICAgICAgaWYgKHR5cGVvZiBjbGVhclRpbWVvdXQgPT09ICdmdW5jdGlvbicpIHtcbiAgICAgICAgICAgIGNhY2hlZENsZWFyVGltZW91dCA9IGNsZWFyVGltZW91dDtcbiAgICAgICAgfSBlbHNlIHtcbiAgICAgICAgICAgIGNhY2hlZENsZWFyVGltZW91dCA9IGRlZmF1bHRDbGVhclRpbWVvdXQ7XG4gICAgICAgIH1cbiAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgIGNhY2hlZENsZWFyVGltZW91dCA9IGRlZmF1bHRDbGVhclRpbWVvdXQ7XG4gICAgfVxufSAoKSlcbmZ1bmN0aW9uIHJ1blRpbWVvdXQoZnVuKSB7XG4gICAgaWYgKGNhY2hlZFNldFRpbWVvdXQgPT09IHNldFRpbWVvdXQpIHtcbiAgICAgICAgLy9ub3JtYWwgZW52aXJvbWVudHMgaW4gc2FuZSBzaXR1YXRpb25zXG4gICAgICAgIHJldHVybiBzZXRUaW1lb3V0KGZ1biwgMCk7XG4gICAgfVxuICAgIC8vIGlmIHNldFRpbWVvdXQgd2Fzbid0IGF2YWlsYWJsZSBidXQgd2FzIGxhdHRlciBkZWZpbmVkXG4gICAgaWYgKChjYWNoZWRTZXRUaW1lb3V0ID09PSBkZWZhdWx0U2V0VGltb3V0IHx8ICFjYWNoZWRTZXRUaW1lb3V0KSAmJiBzZXRUaW1lb3V0KSB7XG4gICAgICAgIGNhY2hlZFNldFRpbWVvdXQgPSBzZXRUaW1lb3V0O1xuICAgICAgICByZXR1cm4gc2V0VGltZW91dChmdW4sIDApO1xuICAgIH1cbiAgICB0cnkge1xuICAgICAgICAvLyB3aGVuIHdoZW4gc29tZWJvZHkgaGFzIHNjcmV3ZWQgd2l0aCBzZXRUaW1lb3V0IGJ1dCBubyBJLkUuIG1hZGRuZXNzXG4gICAgICAgIHJldHVybiBjYWNoZWRTZXRUaW1lb3V0KGZ1biwgMCk7XG4gICAgfSBjYXRjaChlKXtcbiAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgIC8vIFdoZW4gd2UgYXJlIGluIEkuRS4gYnV0IHRoZSBzY3JpcHQgaGFzIGJlZW4gZXZhbGVkIHNvIEkuRS4gZG9lc24ndCB0cnVzdCB0aGUgZ2xvYmFsIG9iamVjdCB3aGVuIGNhbGxlZCBub3JtYWxseVxuICAgICAgICAgICAgcmV0dXJuIGNhY2hlZFNldFRpbWVvdXQuY2FsbChudWxsLCBmdW4sIDApO1xuICAgICAgICB9IGNhdGNoKGUpe1xuICAgICAgICAgICAgLy8gc2FtZSBhcyBhYm92ZSBidXQgd2hlbiBpdCdzIGEgdmVyc2lvbiBvZiBJLkUuIHRoYXQgbXVzdCBoYXZlIHRoZSBnbG9iYWwgb2JqZWN0IGZvciAndGhpcycsIGhvcGZ1bGx5IG91ciBjb250ZXh0IGNvcnJlY3Qgb3RoZXJ3aXNlIGl0IHdpbGwgdGhyb3cgYSBnbG9iYWwgZXJyb3JcbiAgICAgICAgICAgIHJldHVybiBjYWNoZWRTZXRUaW1lb3V0LmNhbGwodGhpcywgZnVuLCAwKTtcbiAgICAgICAgfVxuICAgIH1cblxuXG59XG5mdW5jdGlvbiBydW5DbGVhclRpbWVvdXQobWFya2VyKSB7XG4gICAgaWYgKGNhY2hlZENsZWFyVGltZW91dCA9PT0gY2xlYXJUaW1lb3V0KSB7XG4gICAgICAgIC8vbm9ybWFsIGVudmlyb21lbnRzIGluIHNhbmUgc2l0dWF0aW9uc1xuICAgICAgICByZXR1cm4gY2xlYXJUaW1lb3V0KG1hcmtlcik7XG4gICAgfVxuICAgIC8vIGlmIGNsZWFyVGltZW91dCB3YXNuJ3QgYXZhaWxhYmxlIGJ1dCB3YXMgbGF0dGVyIGRlZmluZWRcbiAgICBpZiAoKGNhY2hlZENsZWFyVGltZW91dCA9PT0gZGVmYXVsdENsZWFyVGltZW91dCB8fCAhY2FjaGVkQ2xlYXJUaW1lb3V0KSAmJiBjbGVhclRpbWVvdXQpIHtcbiAgICAgICAgY2FjaGVkQ2xlYXJUaW1lb3V0ID0gY2xlYXJUaW1lb3V0O1xuICAgICAgICByZXR1cm4gY2xlYXJUaW1lb3V0KG1hcmtlcik7XG4gICAgfVxuICAgIHRyeSB7XG4gICAgICAgIC8vIHdoZW4gd2hlbiBzb21lYm9keSBoYXMgc2NyZXdlZCB3aXRoIHNldFRpbWVvdXQgYnV0IG5vIEkuRS4gbWFkZG5lc3NcbiAgICAgICAgcmV0dXJuIGNhY2hlZENsZWFyVGltZW91dChtYXJrZXIpO1xuICAgIH0gY2F0Y2ggKGUpe1xuICAgICAgICB0cnkge1xuICAgICAgICAgICAgLy8gV2hlbiB3ZSBhcmUgaW4gSS5FLiBidXQgdGhlIHNjcmlwdCBoYXMgYmVlbiBldmFsZWQgc28gSS5FLiBkb2Vzbid0ICB0cnVzdCB0aGUgZ2xvYmFsIG9iamVjdCB3aGVuIGNhbGxlZCBub3JtYWxseVxuICAgICAgICAgICAgcmV0dXJuIGNhY2hlZENsZWFyVGltZW91dC5jYWxsKG51bGwsIG1hcmtlcik7XG4gICAgICAgIH0gY2F0Y2ggKGUpe1xuICAgICAgICAgICAgLy8gc2FtZSBhcyBhYm92ZSBidXQgd2hlbiBpdCdzIGEgdmVyc2lvbiBvZiBJLkUuIHRoYXQgbXVzdCBoYXZlIHRoZSBnbG9iYWwgb2JqZWN0IGZvciAndGhpcycsIGhvcGZ1bGx5IG91ciBjb250ZXh0IGNvcnJlY3Qgb3RoZXJ3aXNlIGl0IHdpbGwgdGhyb3cgYSBnbG9iYWwgZXJyb3IuXG4gICAgICAgICAgICAvLyBTb21lIHZlcnNpb25zIG9mIEkuRS4gaGF2ZSBkaWZmZXJlbnQgcnVsZXMgZm9yIGNsZWFyVGltZW91dCB2cyBzZXRUaW1lb3V0XG4gICAgICAgICAgICByZXR1cm4gY2FjaGVkQ2xlYXJUaW1lb3V0LmNhbGwodGhpcywgbWFya2VyKTtcbiAgICAgICAgfVxuICAgIH1cblxuXG5cbn1cbnZhciBxdWV1ZSA9IFtdO1xudmFyIGRyYWluaW5nID0gZmFsc2U7XG52YXIgY3VycmVudFF1ZXVlO1xudmFyIHF1ZXVlSW5kZXggPSAtMTtcblxuZnVuY3Rpb24gY2xlYW5VcE5leHRUaWNrKCkge1xuICAgIGlmICghZHJhaW5pbmcgfHwgIWN1cnJlbnRRdWV1ZSkge1xuICAgICAgICByZXR1cm47XG4gICAgfVxuICAgIGRyYWluaW5nID0gZmFsc2U7XG4gICAgaWYgKGN1cnJlbnRRdWV1ZS5sZW5ndGgpIHtcbiAgICAgICAgcXVldWUgPSBjdXJyZW50UXVldWUuY29uY2F0KHF1ZXVlKTtcbiAgICB9IGVsc2Uge1xuICAgICAgICBxdWV1ZUluZGV4ID0gLTE7XG4gICAgfVxuICAgIGlmIChxdWV1ZS5sZW5ndGgpIHtcbiAgICAgICAgZHJhaW5RdWV1ZSgpO1xuICAgIH1cbn1cblxuZnVuY3Rpb24gZHJhaW5RdWV1ZSgpIHtcbiAgICBpZiAoZHJhaW5pbmcpIHtcbiAgICAgICAgcmV0dXJuO1xuICAgIH1cbiAgICB2YXIgdGltZW91dCA9IHJ1blRpbWVvdXQoY2xlYW5VcE5leHRUaWNrKTtcbiAgICBkcmFpbmluZyA9IHRydWU7XG5cbiAgICB2YXIgbGVuID0gcXVldWUubGVuZ3RoO1xuICAgIHdoaWxlKGxlbikge1xuICAgICAgICBjdXJyZW50UXVldWUgPSBxdWV1ZTtcbiAgICAgICAgcXVldWUgPSBbXTtcbiAgICAgICAgd2hpbGUgKCsrcXVldWVJbmRleCA8IGxlbikge1xuICAgICAgICAgICAgaWYgKGN1cnJlbnRRdWV1ZSkge1xuICAgICAgICAgICAgICAgIGN1cnJlbnRRdWV1ZVtxdWV1ZUluZGV4XS5ydW4oKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgfVxuICAgICAgICBxdWV1ZUluZGV4ID0gLTE7XG4gICAgICAgIGxlbiA9IHF1ZXVlLmxlbmd0aDtcbiAgICB9XG4gICAgY3VycmVudFF1ZXVlID0gbnVsbDtcbiAgICBkcmFpbmluZyA9IGZhbHNlO1xuICAgIHJ1bkNsZWFyVGltZW91dCh0aW1lb3V0KTtcbn1cblxucHJvY2Vzcy5uZXh0VGljayA9IGZ1bmN0aW9uIChmdW4pIHtcbiAgICB2YXIgYXJncyA9IG5ldyBBcnJheShhcmd1bWVudHMubGVuZ3RoIC0gMSk7XG4gICAgaWYgKGFyZ3VtZW50cy5sZW5ndGggPiAxKSB7XG4gICAgICAgIGZvciAodmFyIGkgPSAxOyBpIDwgYXJndW1lbnRzLmxlbmd0aDsgaSsrKSB7XG4gICAgICAgICAgICBhcmdzW2kgLSAxXSA9IGFyZ3VtZW50c1tpXTtcbiAgICAgICAgfVxuICAgIH1cbiAgICBxdWV1ZS5wdXNoKG5ldyBJdGVtKGZ1biwgYXJncykpO1xuICAgIGlmIChxdWV1ZS5sZW5ndGggPT09IDEgJiYgIWRyYWluaW5nKSB7XG4gICAgICAgIHJ1blRpbWVvdXQoZHJhaW5RdWV1ZSk7XG4gICAgfVxufTtcblxuLy8gdjggbGlrZXMgcHJlZGljdGlibGUgb2JqZWN0c1xuZnVuY3Rpb24gSXRlbShmdW4sIGFycmF5KSB7XG4gICAgdGhpcy5mdW4gPSBmdW47XG4gICAgdGhpcy5hcnJheSA9IGFycmF5O1xufVxuSXRlbS5wcm90b3R5cGUucnVuID0gZnVuY3Rpb24gKCkge1xuICAgIHRoaXMuZnVuLmFwcGx5KG51bGwsIHRoaXMuYXJyYXkpO1xufTtcbnByb2Nlc3MudGl0bGUgPSAnYnJvd3Nlcic7XG5wcm9jZXNzLmJyb3dzZXIgPSB0cnVlO1xucHJvY2Vzcy5lbnYgPSB7fTtcbnByb2Nlc3MuYXJndiA9IFtdO1xucHJvY2Vzcy52ZXJzaW9uID0gJyc7IC8vIGVtcHR5IHN0cmluZyB0byBhdm9pZCByZWdleHAgaXNzdWVzXG5wcm9jZXNzLnZlcnNpb25zID0ge307XG5cbmZ1bmN0aW9uIG5vb3AoKSB7fVxuXG5wcm9jZXNzLm9uID0gbm9vcDtcbnByb2Nlc3MuYWRkTGlzdGVuZXIgPSBub29wO1xucHJvY2Vzcy5vbmNlID0gbm9vcDtcbnByb2Nlc3Mub2ZmID0gbm9vcDtcbnByb2Nlc3MucmVtb3ZlTGlzdGVuZXIgPSBub29wO1xucHJvY2Vzcy5yZW1vdmVBbGxMaXN0ZW5lcnMgPSBub29wO1xucHJvY2Vzcy5lbWl0ID0gbm9vcDtcbnByb2Nlc3MucHJlcGVuZExpc3RlbmVyID0gbm9vcDtcbnByb2Nlc3MucHJlcGVuZE9uY2VMaXN0ZW5lciA9IG5vb3A7XG5cbnByb2Nlc3MubGlzdGVuZXJzID0gZnVuY3Rpb24gKG5hbWUpIHsgcmV0dXJuIFtdIH1cblxucHJvY2Vzcy5iaW5kaW5nID0gZnVuY3Rpb24gKG5hbWUpIHtcbiAgICB0aHJvdyBuZXcgRXJyb3IoJ3Byb2Nlc3MuYmluZGluZyBpcyBub3Qgc3VwcG9ydGVkJyk7XG59O1xuXG5wcm9jZXNzLmN3ZCA9IGZ1bmN0aW9uICgpIHsgcmV0dXJuICcvJyB9O1xucHJvY2Vzcy5jaGRpciA9IGZ1bmN0aW9uIChkaXIpIHtcbiAgICB0aHJvdyBuZXcgRXJyb3IoJ3Byb2Nlc3MuY2hkaXIgaXMgbm90IHN1cHBvcnRlZCcpO1xufTtcbnByb2Nlc3MudW1hc2sgPSBmdW5jdGlvbigpIHsgcmV0dXJuIDA7IH07XG4iLCIvLyBUaGUgbW9kdWxlIGNhY2hlXG52YXIgX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fID0ge307XG5cbi8vIFRoZSByZXF1aXJlIGZ1bmN0aW9uXG5mdW5jdGlvbiBfX3dlYnBhY2tfcmVxdWlyZV9fKG1vZHVsZUlkKSB7XG5cdC8vIENoZWNrIGlmIG1vZHVsZSBpcyBpbiBjYWNoZVxuXHR2YXIgY2FjaGVkTW9kdWxlID0gX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fW21vZHVsZUlkXTtcblx0aWYgKGNhY2hlZE1vZHVsZSAhPT0gdW5kZWZpbmVkKSB7XG5cdFx0cmV0dXJuIGNhY2hlZE1vZHVsZS5leHBvcnRzO1xuXHR9XG5cdC8vIENyZWF0ZSBhIG5ldyBtb2R1bGUgKGFuZCBwdXQgaXQgaW50byB0aGUgY2FjaGUpXG5cdHZhciBtb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdID0ge1xuXHRcdC8vIG5vIG1vZHVsZS5pZCBuZWVkZWRcblx0XHQvLyBubyBtb2R1bGUubG9hZGVkIG5lZWRlZFxuXHRcdGV4cG9ydHM6IHt9XG5cdH07XG5cblx0Ly8gRXhlY3V0ZSB0aGUgbW9kdWxlIGZ1bmN0aW9uXG5cdF9fd2VicGFja19tb2R1bGVzX19bbW9kdWxlSWRdKG1vZHVsZSwgbW9kdWxlLmV4cG9ydHMsIF9fd2VicGFja19yZXF1aXJlX18pO1xuXG5cdC8vIFJldHVybiB0aGUgZXhwb3J0cyBvZiB0aGUgbW9kdWxlXG5cdHJldHVybiBtb2R1bGUuZXhwb3J0cztcbn1cblxuLy8gZXhwb3NlIHRoZSBtb2R1bGVzIG9iamVjdCAoX193ZWJwYWNrX21vZHVsZXNfXylcbl9fd2VicGFja19yZXF1aXJlX18ubSA9IF9fd2VicGFja19tb2R1bGVzX187XG5cbiIsInZhciBkZWZlcnJlZCA9IFtdO1xuX193ZWJwYWNrX3JlcXVpcmVfXy5PID0gZnVuY3Rpb24ocmVzdWx0LCBjaHVua0lkcywgZm4sIHByaW9yaXR5KSB7XG5cdGlmKGNodW5rSWRzKSB7XG5cdFx0cHJpb3JpdHkgPSBwcmlvcml0eSB8fCAwO1xuXHRcdGZvcih2YXIgaSA9IGRlZmVycmVkLmxlbmd0aDsgaSA+IDAgJiYgZGVmZXJyZWRbaSAtIDFdWzJdID4gcHJpb3JpdHk7IGktLSkgZGVmZXJyZWRbaV0gPSBkZWZlcnJlZFtpIC0gMV07XG5cdFx0ZGVmZXJyZWRbaV0gPSBbY2h1bmtJZHMsIGZuLCBwcmlvcml0eV07XG5cdFx0cmV0dXJuO1xuXHR9XG5cdHZhciBub3RGdWxmaWxsZWQgPSBJbmZpbml0eTtcblx0Zm9yICh2YXIgaSA9IDA7IGkgPCBkZWZlcnJlZC5sZW5ndGg7IGkrKykge1xuXHRcdHZhciBjaHVua0lkcyA9IGRlZmVycmVkW2ldWzBdO1xuXHRcdHZhciBmbiA9IGRlZmVycmVkW2ldWzFdO1xuXHRcdHZhciBwcmlvcml0eSA9IGRlZmVycmVkW2ldWzJdO1xuXHRcdHZhciBmdWxmaWxsZWQgPSB0cnVlO1xuXHRcdGZvciAodmFyIGogPSAwOyBqIDwgY2h1bmtJZHMubGVuZ3RoOyBqKyspIHtcblx0XHRcdGlmICgocHJpb3JpdHkgJiAxID09PSAwIHx8IG5vdEZ1bGZpbGxlZCA+PSBwcmlvcml0eSkgJiYgT2JqZWN0LmtleXMoX193ZWJwYWNrX3JlcXVpcmVfXy5PKS5ldmVyeShmdW5jdGlvbihrZXkpIHsgcmV0dXJuIF9fd2VicGFja19yZXF1aXJlX18uT1trZXldKGNodW5rSWRzW2pdKTsgfSkpIHtcblx0XHRcdFx0Y2h1bmtJZHMuc3BsaWNlKGotLSwgMSk7XG5cdFx0XHR9IGVsc2Uge1xuXHRcdFx0XHRmdWxmaWxsZWQgPSBmYWxzZTtcblx0XHRcdFx0aWYocHJpb3JpdHkgPCBub3RGdWxmaWxsZWQpIG5vdEZ1bGZpbGxlZCA9IHByaW9yaXR5O1xuXHRcdFx0fVxuXHRcdH1cblx0XHRpZihmdWxmaWxsZWQpIHtcblx0XHRcdGRlZmVycmVkLnNwbGljZShpLS0sIDEpXG5cdFx0XHR2YXIgciA9IGZuKCk7XG5cdFx0XHRpZiAociAhPT0gdW5kZWZpbmVkKSByZXN1bHQgPSByO1xuXHRcdH1cblx0fVxuXHRyZXR1cm4gcmVzdWx0O1xufTsiLCIvLyBnZXREZWZhdWx0RXhwb3J0IGZ1bmN0aW9uIGZvciBjb21wYXRpYmlsaXR5IHdpdGggbm9uLWhhcm1vbnkgbW9kdWxlc1xuX193ZWJwYWNrX3JlcXVpcmVfXy5uID0gZnVuY3Rpb24obW9kdWxlKSB7XG5cdHZhciBnZXR0ZXIgPSBtb2R1bGUgJiYgbW9kdWxlLl9fZXNNb2R1bGUgP1xuXHRcdGZ1bmN0aW9uKCkgeyByZXR1cm4gbW9kdWxlWydkZWZhdWx0J107IH0gOlxuXHRcdGZ1bmN0aW9uKCkgeyByZXR1cm4gbW9kdWxlOyB9O1xuXHRfX3dlYnBhY2tfcmVxdWlyZV9fLmQoZ2V0dGVyLCB7IGE6IGdldHRlciB9KTtcblx0cmV0dXJuIGdldHRlcjtcbn07IiwiLy8gZGVmaW5lIGdldHRlciBmdW5jdGlvbnMgZm9yIGhhcm1vbnkgZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5kID0gZnVuY3Rpb24oZXhwb3J0cywgZGVmaW5pdGlvbikge1xuXHRmb3IodmFyIGtleSBpbiBkZWZpbml0aW9uKSB7XG5cdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKGRlZmluaXRpb24sIGtleSkgJiYgIV9fd2VicGFja19yZXF1aXJlX18ubyhleHBvcnRzLCBrZXkpKSB7XG5cdFx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywga2V5LCB7IGVudW1lcmFibGU6IHRydWUsIGdldDogZGVmaW5pdGlvbltrZXldIH0pO1xuXHRcdH1cblx0fVxufTsiLCJfX3dlYnBhY2tfcmVxdWlyZV9fLm8gPSBmdW5jdGlvbihvYmosIHByb3ApIHsgcmV0dXJuIE9iamVjdC5wcm90b3R5cGUuaGFzT3duUHJvcGVydHkuY2FsbChvYmosIHByb3ApOyB9IiwiLy8gZGVmaW5lIF9fZXNNb2R1bGUgb24gZXhwb3J0c1xuX193ZWJwYWNrX3JlcXVpcmVfXy5yID0gZnVuY3Rpb24oZXhwb3J0cykge1xuXHRpZih0eXBlb2YgU3ltYm9sICE9PSAndW5kZWZpbmVkJyAmJiBTeW1ib2wudG9TdHJpbmdUYWcpIHtcblx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgU3ltYm9sLnRvU3RyaW5nVGFnLCB7IHZhbHVlOiAnTW9kdWxlJyB9KTtcblx0fVxuXHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgJ19fZXNNb2R1bGUnLCB7IHZhbHVlOiB0cnVlIH0pO1xufTsiLCIvLyBubyBiYXNlVVJJXG5cbi8vIG9iamVjdCB0byBzdG9yZSBsb2FkZWQgYW5kIGxvYWRpbmcgY2h1bmtzXG4vLyB1bmRlZmluZWQgPSBjaHVuayBub3QgbG9hZGVkLCBudWxsID0gY2h1bmsgcHJlbG9hZGVkL3ByZWZldGNoZWRcbi8vIFtyZXNvbHZlLCByZWplY3QsIFByb21pc2VdID0gY2h1bmsgbG9hZGluZywgMCA9IGNodW5rIGxvYWRlZFxudmFyIGluc3RhbGxlZENodW5rcyA9IHtcblx0XCIvYXNzZXRzL2J1aWxkL2pzL3NjcmlwdHNcIjogMCxcblx0XCJhc3NldHMvYnVpbGQvY3NzL3N0eWxlc1wiOiAwXG59O1xuXG4vLyBubyBjaHVuayBvbiBkZW1hbmQgbG9hZGluZ1xuXG4vLyBubyBwcmVmZXRjaGluZ1xuXG4vLyBubyBwcmVsb2FkZWRcblxuLy8gbm8gSE1SXG5cbi8vIG5vIEhNUiBtYW5pZmVzdFxuXG5fX3dlYnBhY2tfcmVxdWlyZV9fLk8uaiA9IGZ1bmN0aW9uKGNodW5rSWQpIHsgcmV0dXJuIGluc3RhbGxlZENodW5rc1tjaHVua0lkXSA9PT0gMDsgfTtcblxuLy8gaW5zdGFsbCBhIEpTT05QIGNhbGxiYWNrIGZvciBjaHVuayBsb2FkaW5nXG52YXIgd2VicGFja0pzb25wQ2FsbGJhY2sgPSBmdW5jdGlvbihwYXJlbnRDaHVua0xvYWRpbmdGdW5jdGlvbiwgZGF0YSkge1xuXHR2YXIgY2h1bmtJZHMgPSBkYXRhWzBdO1xuXHR2YXIgbW9yZU1vZHVsZXMgPSBkYXRhWzFdO1xuXHR2YXIgcnVudGltZSA9IGRhdGFbMl07XG5cdC8vIGFkZCBcIm1vcmVNb2R1bGVzXCIgdG8gdGhlIG1vZHVsZXMgb2JqZWN0LFxuXHQvLyB0aGVuIGZsYWcgYWxsIFwiY2h1bmtJZHNcIiBhcyBsb2FkZWQgYW5kIGZpcmUgY2FsbGJhY2tcblx0dmFyIG1vZHVsZUlkLCBjaHVua0lkLCBpID0gMDtcblx0aWYoY2h1bmtJZHMuc29tZShmdW5jdGlvbihpZCkgeyByZXR1cm4gaW5zdGFsbGVkQ2h1bmtzW2lkXSAhPT0gMDsgfSkpIHtcblx0XHRmb3IobW9kdWxlSWQgaW4gbW9yZU1vZHVsZXMpIHtcblx0XHRcdGlmKF9fd2VicGFja19yZXF1aXJlX18ubyhtb3JlTW9kdWxlcywgbW9kdWxlSWQpKSB7XG5cdFx0XHRcdF9fd2VicGFja19yZXF1aXJlX18ubVttb2R1bGVJZF0gPSBtb3JlTW9kdWxlc1ttb2R1bGVJZF07XG5cdFx0XHR9XG5cdFx0fVxuXHRcdGlmKHJ1bnRpbWUpIHZhciByZXN1bHQgPSBydW50aW1lKF9fd2VicGFja19yZXF1aXJlX18pO1xuXHR9XG5cdGlmKHBhcmVudENodW5rTG9hZGluZ0Z1bmN0aW9uKSBwYXJlbnRDaHVua0xvYWRpbmdGdW5jdGlvbihkYXRhKTtcblx0Zm9yKDtpIDwgY2h1bmtJZHMubGVuZ3RoOyBpKyspIHtcblx0XHRjaHVua0lkID0gY2h1bmtJZHNbaV07XG5cdFx0aWYoX193ZWJwYWNrX3JlcXVpcmVfXy5vKGluc3RhbGxlZENodW5rcywgY2h1bmtJZCkgJiYgaW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRdKSB7XG5cdFx0XHRpbnN0YWxsZWRDaHVua3NbY2h1bmtJZF1bMF0oKTtcblx0XHR9XG5cdFx0aW5zdGFsbGVkQ2h1bmtzW2NodW5rSWRzW2ldXSA9IDA7XG5cdH1cblx0cmV0dXJuIF9fd2VicGFja19yZXF1aXJlX18uTyhyZXN1bHQpO1xufVxuXG52YXIgY2h1bmtMb2FkaW5nR2xvYmFsID0gc2VsZltcIndlYnBhY2tDaHVua3dlYnBfY29udmVydGVyX2Zvcl9tZWRpYVwiXSA9IHNlbGZbXCJ3ZWJwYWNrQ2h1bmt3ZWJwX2NvbnZlcnRlcl9mb3JfbWVkaWFcIl0gfHwgW107XG5jaHVua0xvYWRpbmdHbG9iYWwuZm9yRWFjaCh3ZWJwYWNrSnNvbnBDYWxsYmFjay5iaW5kKG51bGwsIDApKTtcbmNodW5rTG9hZGluZ0dsb2JhbC5wdXNoID0gd2VicGFja0pzb25wQ2FsbGJhY2suYmluZChudWxsLCBjaHVua0xvYWRpbmdHbG9iYWwucHVzaC5iaW5kKGNodW5rTG9hZGluZ0dsb2JhbCkpOyIsIiIsIi8vIHN0YXJ0dXBcbi8vIExvYWQgZW50cnkgbW9kdWxlIGFuZCByZXR1cm4gZXhwb3J0c1xuLy8gVGhpcyBlbnRyeSBtb2R1bGUgZGVwZW5kcyBvbiBvdGhlciBsb2FkZWQgY2h1bmtzIGFuZCBleGVjdXRpb24gbmVlZCB0byBiZSBkZWxheWVkXG5fX3dlYnBhY2tfcmVxdWlyZV9fLk8odW5kZWZpbmVkLCBbXCJhc3NldHMvYnVpbGQvY3NzL3N0eWxlc1wiXSwgZnVuY3Rpb24oKSB7IHJldHVybiBfX3dlYnBhY2tfcmVxdWlyZV9fKFwiLi9hc3NldHMtc3JjL2pzL0NvcmUuanNcIik7IH0pXG52YXIgX193ZWJwYWNrX2V4cG9ydHNfXyA9IF9fd2VicGFja19yZXF1aXJlX18uTyh1bmRlZmluZWQsIFtcImFzc2V0cy9idWlsZC9jc3Mvc3R5bGVzXCJdLCBmdW5jdGlvbigpIHsgcmV0dXJuIF9fd2VicGFja19yZXF1aXJlX18oXCIuL2Fzc2V0cy1zcmMvc2Nzcy9Db3JlLnNjc3NcIik7IH0pXG5fX3dlYnBhY2tfZXhwb3J0c19fID0gX193ZWJwYWNrX3JlcXVpcmVfXy5PKF9fd2VicGFja19leHBvcnRzX18pO1xuIiwiIl0sIm5hbWVzIjpbIkFkbWluTm90aWNlTWFuYWdlciIsIkNvbnZlcnNpb25TdGF0c01hbmFnZXIiLCJEYXRhVG9nZ2xlVHJpZ2dlciIsIkltYWdlQ29udmVyc2lvbk1hbmFnZXIiLCJJbWFnZXNTdGF0c0ZldGNoZXIiLCJJbWFnZXNUcmVlR2VuZXJhdG9yIiwiUGxhbnNCdXR0b25HZW5lcmF0b3IiLCJDb3JlIiwiY29udmVyc2lvbl9zdGF0c19tYW5hZ2VyIiwiaW1hZ2VzX3RyZWVfZ2VuZXJhdG9yIiwicGxhbnNfYnV0dG9uX2dlbmVyYXRvciIsImF4aW9zIiwiQWRtaW5Ob3RpY2VNYW5hZ2VyQ29yZSIsIm5vdGljZSIsInNldF92YXJzIiwic2V0X2V2ZW50cyIsInNldHRpbmdzIiwiYWpheF9hY3Rpb24iLCJnZXRBdHRyaWJ1dGUiLCJhamF4X3VybCIsImJ1dHRvbl9jbG9zZV9jbGFzcyIsImJ1dHRvbl9oaWRlX2NsYXNzIiwiZXZlbnRzIiwiY2xpY2tfb25fY2xvc2UiLCJiaW5kIiwiYWRkRXZlbnRMaXN0ZW5lciIsImUiLCJ0YXJnZXQiLCJtYXRjaGVzIiwicmVtb3ZlRXZlbnRMaXN0ZW5lciIsImhpZGVfbm90aWNlIiwiaXNfcGVybWFuZW50bHkiLCJzZW5kX3JlcXVlc3QiLCJxdWVyeVNlbGVjdG9yIiwiY2xpY2siLCJtZXRob2QiLCJ1cmwiLCJkYXRhIiwiZ2V0X2RhdGFfZm9yX3JlcXVlc3QiLCJmb3JtX2RhdGEiLCJGb3JtRGF0YSIsImFwcGVuZCIsIm5vdGljZXMiLCJkb2N1bWVudCIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJsZW5ndGgiLCJpIiwic3RhdHVzIiwiY291bnRlcl93ZWJwIiwiY291bnRlcl9hdmlmIiwiY291bnRlcl93ZWJwX3BlcmNlbnQiLCJjb3VudGVyX3dlYnBfaW1hZ2VzIiwiY291bnRlcl93ZWJwX2xvYWRlciIsImNvdW50ZXJfYXZpZl9wZXJjZW50IiwiY291bnRlcl9hdmlmX2ltYWdlcyIsImNvdW50ZXJfYXZpZl9sb2FkZXIiLCJ3ZWJwX2NvbnZlcnRlZCIsIndlYnBfdW5jb252ZXJ0ZWQiLCJ3ZWJwX2FsbCIsImF2aWZfY29udmVydGVkIiwiYXZpZl91bmNvbnZlcnRlZCIsImF2aWZfYWxsIiwiYXR0cyIsImNvdW50ZXJfcGVyY2VudCIsImNvdW50X2NvbnZlcnRlZCIsImNvdW50X2FsbCIsInJlZnJlc2hfc3RhdHMiLCJzZXRBdHRyaWJ1dGUiLCJjb3VudF9zdWNjZXNzZnVsIiwicGVyY2VudF93ZWJwIiwiTWF0aCIsImZsb29yIiwicGVyY2VudF9hdmlmIiwiaW5uZXJUZXh0IiwibWF4IiwidG9TdHJpbmciLCJyZXBsYWNlIiwidHJpZ2dlcnMiLCJvdXRwdXRzIiwidG9nZ2xlX291dHB1dCIsImZpZWxkX25hbWUiLCJjdXJyZW50VGFyZ2V0IiwiZmllbGRfdHlwZSIsImZpZWxkX3ZhbHVlcyIsImdldF9jaGVja2JveF92YWx1ZSIsImdldF9yYWRpb192YWx1ZSIsIm91dHB1dF9hdHRyIiwidmFsaWRhdGVfb3V0cHV0IiwicmVtb3ZlQXR0cmlidXRlIiwidmFsdWVzIiwiY2hlY2tlZCIsInB1c2giLCJvdXRwdXQiLCJhdmFpbGFibGVfdmFsdWVzIiwic3BsaXQiLCJpbmRleE9mIiwic2VjdGlvbiIsIndyYXBwZXJfc3RhdHVzIiwicHJvZ3Jlc3MiLCJwcm9ncmVzc19zaXplIiwicHJvZ3Jlc3Nfc3VjY2VzcyIsInByb2dyZXNzX2ZhaWxlZCIsIndyYXBwZXJfZXJyb3JzIiwiZXJyb3JzX291dHB1dCIsIndyYXBwZXJfc3VjY2VzcyIsIm9wdGlvbl9mb3JjZSIsInN1Ym1pdF9idXR0b24iLCJjb3VudCIsIml0ZW1zIiwic2l6ZSIsImJlZm9yZSIsImFmdGVyIiwiZmlsZXNfY291bnRlciIsImFsbCIsImNvbnZlcnRlZCIsImVycm9ycyIsImlzX2Rpc2FibGVkIiwiYWpheCIsInVybF9wYXRocyIsInVybF9wYXRoc19ub25jZSIsInVybF9yZWdlbmVyYXRlIiwidXJsX3JlZ2VuZXJhdGVfbm9uY2UiLCJlcnJvcl9tZXNzYWdlIiwidW5pdHMiLCJtYXhfZXJyb3JzIiwiY29ubmVjdGlvbl90aW1lb3V0IiwiY2xhc3NlcyIsInByb2dyZXNzX2Vycm9yIiwiYnV0dG9uX2Rpc2FibGVkIiwiaW5pdF9yZWdlbmVyYXRpb24iLCJwcmV2ZW50RGVmYXVsdCIsImNsYXNzTGlzdCIsImFkZCIsInNlbmRfcmVxdWVzdF9mb3JfcGF0aHMiLCJyZWdlbmVyYXRlX2ZvcmNlIiwiaGVhZGVycyIsInRoZW4iLCJyZXNwb25zZSIsInBhdGhzIiwicGFyc2VfZmlsZXNfcGF0aHMiLCJyZXNldF9maWxlc193ZWJwIiwicmVzZXRfZmlsZXNfYXZpZiIsInJlZ2VuZXJhdGVfbmV4dF9pbWFnZXMiLCJjYXRjaCIsImVycm9yIiwiY29uc29sZSIsIndhcm4iLCJjYXRjaF9yZXF1ZXN0X2Vycm9yIiwicmVzcG9uc2VfZGF0YSIsImRpcmVjdG9yeSIsImZpbGVzIiwicGFydF9wYXRocyIsImoiLCJwYXRoIiwiYXR0ZW1wdCIsInVwZGF0ZV9wcm9ncmVzcyIsInNlbmRfcmVxdWVzdF9mb3JfcmVnZW5lcmF0aW9uIiwidGltZW91dCIsImlzX2ZhdGFsX2Vycm9yIiwidXBkYXRlX2Vycm9ycyIsInVwZGF0ZV9zaXplIiwidXBkYXRlX2ZpbGVzX2NvdW50Iiwic2V0VGltZW91dCIsImlubmVySFRNTCIsImN1cnJlbnRfZGF0ZSIsImdldF9kYXRlIiwicHJpbnRfZXJyb3JfbWVzc2FnZSIsInNldF9mYXRhbF9lcnJvciIsIkRhdGUiLCJob3VyIiwiZ2V0SG91cnMiLCJzdWJzdHIiLCJtaW51dGUiLCJnZXRNaW51dGVzIiwic2Vjb25kIiwiZ2V0U2Vjb25kcyIsInBhcmFtcyIsInBhcmFtc192YWx1ZSIsImpvaW4iLCJzdGF0dXNUZXh0IiwiY29uZmlnIiwiaGFzX3ByZV93cmFwcGVyIiwiZWxlbWVudCIsImNyZWF0ZUVsZW1lbnQiLCJkYXRlX3ByZWZpeCIsInByZV9lbGVtZW50IiwiYXBwZW5kQ2hpbGQiLCJieXRlcyIsInBlcmNlbnQiLCJyb3VuZCIsImluZGV4IiwibnVtYmVyIiwidG9GaXhlZCIsInVuaXQiLCJhZGRfZmlsZXNfd2VicCIsIndlYnBfYXZhaWxhYmxlIiwiYWRkX2ZpbGVzX2F2aWYiLCJhdmlmX2F2YWlsYWJsZSIsImVycm9yX291dHB1dCIsImFqYXhfbm9uY2UiLCJzZXRfZXJyb3IiLCJzdGFydF90aW1lIiwidmFsdWVfd2VicF9hbGwiLCJ2YWx1ZV93ZWJwX2NvbnZlcnRlZCIsInZhbHVlX2F2aWZfYWxsIiwidmFsdWVfYXZpZl9jb252ZXJ0ZWQiLCJzZXRfZmlsZXNfd2VicCIsInNldF9maWxlc19hdmlmIiwiZ2VuZXJhdGVfdHJlZSIsInRyZWUiLCJzaG93X2J1dHRvbiIsInNob3dfcmVxdWVzdF9lcnJvciIsInJlcXVlc3RfdGltZSIsInJlcXVlc3Rfc3RhdHVzIiwicmVxdWVzdF9kYXRhIiwiSlNPTiIsInN0cmluZ2lmeSIsImxvYWRlciIsImRyYXdfdHJlZSIsImlucHV0cyIsInBhcmVudE5vZGUiLCJsZXZlbCIsImlkIiwiY29udGVudCIsIml0ZW1faWQiLCJuYW1lIiwiYnV0dG9uIiwiYnV0dG9uX3VybCIsIndlYnBfY291bnQiLCJhdmlmX2NvdW50Il0sInNvdXJjZVJvb3QiOiIifQ==