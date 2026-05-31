/* global window, document, console */
"use strict";

(function () {
  /** @type {number|null} */
  let swcfpc_toolbar_cache_status_interval = null;

  /** @type {string} */
  const spcAjaxURL = window.swcfpcOptions.ajaxUrl;
  let spcCacheEnabled = parseInt(window.swcfpcOptions.cacheEnabled);
  const spcAjaxNonce = window.swcfpcOptions.nonce;

  function swcfpc_lock_screen() {
    if (!document.querySelector('.swcfpc_please_wait')) {
      const inputTypeSubmit = document.querySelectorAll('input[type=submit]')
      const inputTypeBtn = document.querySelectorAll('input[type=submit]')
      const anchorTags = document.querySelectorAll('a')

      inputTypeSubmit.forEach((item) => {
        item.classList.add('swcfpc_hide')
      })

      inputTypeBtn.forEach((item) => {
        item.classList.add('swcfpc_hide')
      })

      anchorTags.forEach((item) => {
        item.classList.add('swcfpc_hide')
      })

      const waitDiv = document.createElement('div')
      waitDiv.classList.add('swcfpc_please_wait')
      document.body.prepend(waitDiv)
    }
  }

  function swcfpc_unlock_screen() {
    const inputTypeSubmit = document.querySelectorAll('input[type=submit]')
    const inputTypeBtn = document.querySelectorAll('input[type=submit]')
    const anchorTags = document.querySelectorAll('a')

    inputTypeSubmit.forEach((item) => {
      item.classList.remove('swcfpc_hide')
    })

    inputTypeBtn.forEach((item) => {
      item.classList.remove('swcfpc_hide')
    })

    anchorTags.forEach((item) => {
      item.classList.remove('swcfpc_hide')
    })

    document.querySelector('.swcfpc_please_wait')?.remove()
  }

  function swcfpc_display_message(title, content) {
    window.alert(`${title}: ${content}`)
  }

  async function swcfpc_run_toolbar_action(action, body) {
    try {
      swcfpc_lock_screen()

      const response = await window.fetch(spcAjaxURL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body,
        credentials: 'same-origin',
        timeout: 10000
      })

      swcfpc_unlock_screen()

      if (!response.ok) {
        console.error(`Error: ${response.status} ${response.statusText}`)
        return
      }

      const data = await response.json()

      if (data.status === 'ok') {
        swcfpc_display_message('Success', data.success_msg)
      } else {
        swcfpc_display_message('Error', data.error)
      }
    } catch (err) {
      window.alert(`Error: ${err.status} ${err.message}`)
      console.error(err)
      swcfpc_unlock_screen()
    }
  }

  function swcfpc_force_purge_everything() {
    return swcfpc_run_toolbar_action(
      'swcfpc_purge_everything',
      `action=swcfpc_purge_everything&security=${spcAjaxNonce}`
    )
  }

  function swcfpc_purge_whole_cache() {
    return swcfpc_run_toolbar_action(
      'swcfpc_purge_whole_cache',
      `action=swcfpc_purge_whole_cache&security=${spcAjaxNonce}`
    )
  }

  function swcfpc_purge_single_post_cache(post_id) {
    const dataJSON = encodeURIComponent(JSON.stringify({
      post_id,
    }))

    return swcfpc_run_toolbar_action(
      'swcfpc_purge_single_post_cache',
      `action=swcfpc_purge_single_post_cache&security=${spcAjaxNonce}&data=${dataJSON}`
    )
  }

  function swcfpc_update_toolbar_cache_status() {
    const toolbarContainer = document.getElementById('wp-admin-bar-wp-cloudflare-super-page-cache-toolbar-container')

    if (toolbarContainer) {
      if (spcCacheEnabled === 0) {
        toolbarContainer.classList.remove('bullet-green')
        toolbarContainer.classList.add('bullet-red')
      } else {
        toolbarContainer.classList.remove('bullet-red')
        toolbarContainer.classList.add('bullet-green')
      }

      window.clearInterval(swcfpc_toolbar_cache_status_interval)
      swcfpc_toolbar_cache_status_interval = null

      return
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    try {
      spcCacheEnabled = typeof spcCacheEnabled === 'undefined' ? 0 : spcCacheEnabled

      document.querySelector('#wp-admin-bar-wp-cloudflare-super-page-cache-toolbar-purge-all a')?.addEventListener('click', (e) => {
        e.preventDefault()

        if (window.confirm(window.swcfpcOptions.purgeConfirmMessage)) {
          swcfpc_purge_whole_cache()
        }
      })

      document.querySelector('#wp-admin-bar-wp-cloudflare-super-page-cache-toolbar-purge-single a')?.addEventListener('click', (e) => {
        e.preventDefault()

        const postId = e.currentTarget.getAttribute('href')?.replace('#', '')

        if (postId) {
          swcfpc_purge_single_post_cache(postId)
        }
      })

      document.querySelector('#wp-admin-bar-wp-cloudflare-super-page-cache-toolbar-force-purge-everything a')?.addEventListener('click', (e) => {
        e.preventDefault()
        swcfpc_force_purge_everything()
      })

      swcfpc_toolbar_cache_status_interval = window.setInterval(swcfpc_update_toolbar_cache_status, 2000)
    } catch (e) {
      console.warn(e)
    }
  })
})()
