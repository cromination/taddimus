/* global window, document, console */
"use strict"

/** @type {string} */
const spcAjaxURL = window.swcfpcOptions.ajaxUrl;
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

async function swcfpc_purge_single_post_cache(post_id) {
  try {
    const dataJSON = encodeURIComponent(JSON.stringify({
      "post_id": post_id
    }));

    swcfpc_lock_screen();

    const response = await window.fetch(spcAjaxURL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
      },
      body: `action=swcfpc_purge_single_post_cache&security=${spcAjaxNonce}&data=${dataJSON}`,
      credentials: 'same-origin',
      timeout: 10000
    });

    if (response.ok) {
      const data = await response.json();
      swcfpc_unlock_screen();

      if (data.status === 'ok') {
        swcfpc_display_message("Success", `${data.success_msg}`);
      } else {
        swcfpc_display_message("Error", `${data.error}`);
      }
    } else {
      console.error(`Error: ${response.status} ${response.statusText}`);
      swcfpc_unlock_screen();
    }
  } catch (err) {
    window.alert(`Error: ${err.status} ${err.message}`);
    console.error(err);
    swcfpc_unlock_screen();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  try {
    /**
     * Post list page: Purge single post cache on the post list page.
     */
    document.querySelectorAll('.swcfpc_action_row_single_post_cache_purge').forEach((item) => {
      item.addEventListener('click', (e) => {
        e.preventDefault();
        swcfpc_purge_single_post_cache(e.currentTarget.dataset.post_id);
      });
    });
  } catch (e) {
    console.warn(e);
  }
});
