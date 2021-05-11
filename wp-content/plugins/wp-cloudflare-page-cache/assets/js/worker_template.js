// Worker version: 2.6.1
// Default cookie prefixes for cache bypassing
const DEFAULT_BYPASS_COOKIES = [
  'wp-',
  'wordpress_logged_in_',
  'comment_',
  'woocommerce_',
  'wordpressuser_',
  'wordpresspass_',
  'wordpress_sec_',
  'yith_wcwl_products',
  'edd_items_in_cart',
  'it_exchange_session_',
  'comment_author',
  'dshack_level',
  'auth',
  'noaffiliate_',
  'mp_session',
  'mp_globalcart_'
]

// Third party query parameter that we need to ignore in a URL
const THIRD_PARTY_QUERY_PARAMETERS = [
  'fbclid',
  'fb_action_ids',
  'fb_action_types',
  'fb_source',
  '_ga',
  'age-verified',
  'ao_noptimize',
  'usqp',
  'cn-reloaded',
  'klaviyo',
  'amp',
  'gclid',
  'utm_source',
  'utm_medium',
  'utm_campaign',
  'utm_content',
  'utm_term'
]

/**
 * Function to normalize the URL by removing promotional query parameters from the URL and cache the original URL
 * @param {Object} event 
 */
function url_normalize(event) {
  try {
    // Fetch the Request URL from the event
    // Parse the URL for better handling
    const reqURL = new URL(event.request.url)

    // Loop through the promo queries (THIRD_PARTY_QUERY_PARAMETERS) and see if we have any of these queries present in the URL, if so remove them
    THIRD_PARTY_QUERY_PARAMETERS.forEach( (queryParam) => {

      // Create the REGEX to text the URL with our desired parameters
      const promoUrlQuery = new RegExp( '(&?)(' + queryParam + '=\\w+)', 'g' )

      // Check if the reqURL.search has these search query parameters
      if(promoUrlQuery.test( reqURL.search )) {

        // The URL has promo query parameters that we need to remove
        const urlSearchParams = reqURL.searchParams

        urlSearchParams.delete(queryParam)
      }
    } )

    return reqURL

  } catch (e) {
    return event.respondWith(new Response(`Error thrown ${e.message}`));
  }
}

/**
 * Function to check if the current request should be BYPASSed or Cached based on exclusion cookies
 * entered by the user in the plugin settings
 * @param {String} cookieHeader - The cookie header of the current request
 * @param {Array} cookies_list - List of cookies which should not be cached
 */
function are_blacklisted_cookies(cookieHeader, cookies_list) {
  let blackListedCookieExists = false

  // Make sure both cookieHeader & cookies_list are defined & the length of both cookieHeader & cookies_list > 0
  if (
    cookieHeader &&
    cookieHeader.length > 0 &&
    cookies_list &&
    cookies_list.length > 0
  ) {
    // Split the received request cookie header by semicolon to an Array
    const cookies = cookieHeader.split(';');

    // Loop through the cookies in the request header and check if there is any cookie present there
    // which is also mentioned in our bypassed cookies array
    // if there is then set blackListedCookieExists as true and break out of the loops
    cookies.every((cookie) => {

      cookies_list.every((single_black_list_cookie) => {
        if (cookie.trim().includes(single_black_list_cookie.trim())) {
          blackListedCookieExists = true
          // Found item. Break out from the loop
          return false
        }

        // Otherwise continue the loop
        return true
      })

      // Check if blackListedCookieExists is true then break out of this loop. Else continue the loop
      return blackListedCookieExists ? false : true
    })
  }

  return blackListedCookieExists // value -> FALSE
}

/**
 * Function to add extra response headers for BYPASSed Requests
 * @param {Response} res - The response object
 * @param {String} reason - The string that hold the bypass reason
 */
function add_bypass_custom_headers(res, reason) {
  if (res && (reason.length > 0)) {
    // BYPASS the request and add our custom headers
    res.headers.set('x-wp-cf-super-cache-worker-status', 'bypass')
    res.headers.set('x-wp-cf-super-cache-worker-bypass-reason', reason)
    res.headers.set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
  }
}

/**
 * The function that handles the Request
 * @param {Object} event
 */
async function handleRequest(event) {

  const request = event.request
  const requestURL = url_normalize(event)
  let response = false
  let bypassCache = false
  const bypassReason = {
    'req_method': false,
    'admin_req': false,
    'file_path_ext': false,
    'page_excluded': false,
    'file_excluded': false,
    'cookie': false
  }
  let bypassReasonDetails = ''
  const cookieHeader = request.headers.get('cookie')
  const reqDetails = {
    'contentTypeHTML': false
  }

  // ---------------------------------------------------------
  // Check - Bypass Request ? - Only Based on Request Headers
  // ---------------------------------------------------------

  // 1. BYPASS any requests whose request method is not GET or HEAD
  const allowedReqMethods = ['GET', 'HEAD']
  if (!bypassCache && request) {
    if (!allowedReqMethods.includes(request.method)) {
      bypassCache = true
      bypassReason.req_method = true
      bypassReasonDetails = `Caching not possible for req method ${request.method}`
    }
  }

  // 2. BYPASS the cache for WP Admin HTML Requests & Any File That has /wp-admin/ in it & API endpoints
  // Get the Accept header of the request being received by the CF Worker
  const accept = request.headers.get('Accept')

  if (!bypassCache && accept) {

    // List of path regex that we will BYPASS caching
    // Path includes - WP Admin Paths, WP REST API, WooCommerce API, EDD API Endpoints
    const bypass_admin_path = new RegExp(/(\/(wp-admin)(\/?))/g)
    const bypass_cache_paths = new RegExp(/(\/((wp-admin)|(wc-api)|(edd-api))(\/?))/g)

    // List of file extensions to be BYPASSed
    const bypass_file_ext = new RegExp(/\.(xsl|xml)$/)

    // Check if the request is for WP Admin endpoint & accept type includes text/html i.e. the main HTML request
    if ( accept.includes('text/html') ) {
      reqDetails.contentTypeHTML = true 
    }

    // Check if the request URL is an admin URL for HTML type requests
    if ( reqDetails.contentTypeHTML && bypass_admin_path.test(requestURL.pathname) ) {
      bypassCache = true
      bypassReason.admin_req = true
      bypassReasonDetails = 'WP Admin HTML request'

    } else if ( bypass_cache_paths.test(requestURL.pathname) || bypass_file_ext.test(requestURL.pathname) ) {
      // This is for files which starts with /wp-admin/ but not supposed to be cached
      // E.g. /wp-admin/load-styles.php || /wp-admin/admin-ajax.php
      // Also API endpoints and xml/xsl files to ensure sitemap isn't cached

      bypassCache = true
      bypassReason.file_path_ext = true
      bypassReasonDetails = 'Dynamic File'
    }
  }

  // 3. BYPASS the cache if DEFAULT_BYPASS_COOKIES is present in the request
  // AND also only for the HTML type requests
  if (
    !bypassCache &&
    reqDetails.contentTypeHTML &&
    cookieHeader &&
    cookieHeader.length > 0 &&
    DEFAULT_BYPASS_COOKIES.length > 0
  ) {

    // Separate the request cookies by semicolon and create an Array
    const cookies = cookieHeader.split(';')

    // Loop through the cookies Array to see if there is any cookies present that is present in DEFAULT_BYPASS_COOKIES
    let foundDefaultBypassCookie = false

    cookies.every((cookie) => {

      DEFAULT_BYPASS_COOKIES.every((cookie_prefix) => {

        if (cookie.trim().startsWith(cookie_prefix.trim())) {
          bypassCache = true
          bypassReason.cookie = true
          bypassReasonDetails = 'Default Bypass Cookie Present'
          foundDefaultBypassCookie = true

          // Stop the loop
          return false
        }

        // Otherwise continue the loop
        return true
      })

      // Stop the loop if foundDefaultBypassCookie is TRUE else continue
      return foundDefaultBypassCookie ? false : true
    })
  }

  /**
   * Check if the Request has been Bypassed so far.
   * If not, then check if the request exists in CF Edge Cache & if it does, send it
   * If it does not exists in CF Edge Cache, then check if the request needs to be Bypassed based on the headers
   * present in the Response.
   */
  if (!bypassCache) { // bypassCache is still FALSE

    // Check if the Request present in the CF Edge Cache
    const cacheKey = new Request(requestURL, request)
    const cache = caches.default // Get global CF cache object for this zone

    // Try to Get this request from this zone's cache
    response = await cache.match(cacheKey)

    if (response) { // Cache is present for this request in the CF Edge. Nothing special needs to be done.

      // This request is already cached in the CF Edge. So, simply create a response and set custom headers
      response = new Response(response.body, response)
      response.headers.set('x-wp-cf-super-cache-worker-status', 'hit')

    } else { // Cache not present in CF Edge. Check if Req needs to be Bypassed or Cached based on Response header data

      // Fetch the response of this given request normally without any special parameters
      // so that we can use the response headers set by the plugin at the server level
      const fetchedResponse = await fetch(request)
      response = new Response(fetchedResponse.body, fetchedResponse)

      // ---------------------------------------------------------
      // Check - Bypass Request ? - Based on RESPONSE Headers
      // ---------------------------------------------------------

      // 4. BYPASS the HTML page requests which are excluded from caching (via WP Admin plugin settings or page level settings)
      if (
        !bypassCache &&
        response.headers.get('content-type').includes('text/html') &&
        !response.headers.has('x-wp-cf-super-cache-active')
      ) {
        bypassCache = true
        bypassReason.page_excluded = true
        bypassReasonDetails = 'This page is excluded from caching'
      }

      // 5. BYPASS the static files (non HTML) which has x-wp-cf-super-cache response header set to no-cache
      if (!bypassCache &&
        !response.headers.get('content-type').includes('text/html') &&
        (response.headers.get('x-wp-cf-super-cache') === 'no-cache')
      ) {
        bypassCache = true
        bypassReason.file_excluded = true
        bypassReasonDetails = 'This file is excluded from caching'
      }

      // 6. BYPASS cache if any custom cookie mentioned by the user in the plugin settings is present in the request
      // Check only for HTML type requests
      if (
        !bypassCache &&
        cookieHeader &&
        cookieHeader.length > 0 &&
        response &&
        response.headers.get('content-type').includes('text/html') &&
        response.headers.has('x-wp-cf-super-cache-cookies-bypass')
      ) {
        // Make sure the feature is enabled first
        if (response.headers.get('x-wp-cf-super-cache-cookies-bypass') !== 'swfpc-feature-not-enabled') {

          // Get the list of cookie names entered by the user in the plugin settings
          let cookies_blacklist = response.headers.get('x-wp-cf-super-cache-cookies-bypass');

          if (cookies_blacklist && cookies_blacklist.length > 0) {

            // Split the received cookie list with | separated and make an Array
            cookies_blacklist = cookies_blacklist.split('|')

            if (are_blacklisted_cookies(cookieHeader, cookies_blacklist)) {
              bypassCache = true
              bypassReason.cookie = true
              bypassReasonDetails = 'User provided excluded cookies present in request'
            }
          }
        }
      }

      //-----------------------------------------------------
      // Check if the request needs to be BYPASSed or Cached
      //-----------------------------------------------------
      if (!bypassCache) { // bypassCache is still FALSE. Cache the item in the CF Edge

        // Check if the response status code is not 206 to cache using cache.put(), as any request with status code === 206
        // cache.put() will not work. More info: https://developers.cloudflare.com/workers/runtime-apis/cache#put
        if (response.status !== 206) {

          // If the response header has x-wp-cf-super-cache-active overwrite the cache-control header provided by the server value with x-wp-cf-super-cache-active value just to be safe
          if (response.headers.has('x-wp-cf-super-cache-active')) {
            response.headers.set('Cache-Control', response.headers.get('x-wp-cf-super-cache-cache-control'))
          }

          // Set the worker status as miss and put the item in CF cache
          response.headers.set('x-wp-cf-super-cache-worker-status', 'miss')

          // Add page in cache using cache.put()
          event.waitUntil(cache.put(cacheKey, response.clone()))

        } else {

          // Try to fetch this request again with cacheEverything set to TRUE as that is the only way to cache it
          // More info: https://developers.cloudflare.com/workers/runtime-apis/request#requestinitcfproperties
          response = await fetch(request, { cf: { cacheEverything: true } })
          response = new Response(response.body, response)

          // Set the worker status as miss and put the item in CF cache
          response.headers.set('x-wp-cf-super-cache-worker-status', 'miss')

        }
      } else { // bypassCache -> TRUE || Bypass the Request

        // BYPASS the request and add our custom headers
        add_bypass_custom_headers(response, bypassReasonDetails)
      }

    }

  } else { // bypassCache -> TRUE

    // Fetch the request from the origin server and send it by adding our custom bypass headers
    const bypassedResponse = await fetch(request)
    response = new Response(bypassedResponse.body, bypassedResponse)

    // BYPASS the request and add our custom headers
    add_bypass_custom_headers(response, bypassReasonDetails)
  }

  return response
}

/**
 * Adding event lister to the fetch event to catch the requests and manage them accordingly
 * @param {Object} event 
 */
addEventListener('fetch', event => {
  try {
    return event.respondWith(handleRequest(event));
  } catch (e) {
    return event.respondWith(new Response(`Error thrown ${e.message}`));
  }
})