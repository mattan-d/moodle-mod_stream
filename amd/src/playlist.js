define(["jquery", "core/ajax", "core/str"], ($, ajax, str) => {
  const ENDED_ACTIONS = {
    ended: true,
    finished: true,
    complete: true,
    videoEnded: true,
    playbackEnded: true,
  }

  /**
   * Load a playlist item into the main player.
   *
   * @param {jQuery} playlistItem
   * @param {boolean} autoplay
   */
  const loadPlaylistItem = (playlistItem, autoplay) => {
    const container = playlistItem.closest(".stream-playlist-container")
    const mainVideoContainer = container.find(".stream-main-video")

    if (playlistItem.hasClass("active") && !autoplay) {
      return // Don't reload if it's already active.
    }

    const identifier = playlistItem.data("identifier")
    const cmid = container.data("cmid")
    const includeaudio = container.data("includeaudio")

    // Set active state
    container.find(".playlist-item").removeClass("active")
    playlistItem.addClass("active")

    // Show loading indicator
    mainVideoContainer.html(
      '<div class="loading-overlay"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>',
    )

    // Mark as viewed
    ajax
      .call([
        {
          methodname: "mod_stream_mark_as_viewed",
          args: {
            cmid: cmid,
            videoid: identifier,
          },
        },
      ])[0]
      .done(() => {
        if (!playlistItem.find(".playlist-viewed-badge").length) {
          str.get_string("viewed", "mod_stream").then((viewedString) => {
            playlistItem
              .find(".playlist-item-content")
              .append('<span class="badge badge-success playlist-viewed-badge">' + viewedString + "</span>")
          })
        }
      })

    // Load player
    ajax
      .call([
        {
          methodname: "mod_stream_get_player",
          args: {
            cmid: cmid,
            identifier: identifier,
            includeaudio: !!includeaudio,
            autoplay: !!autoplay,
          },
        },
      ])[0]
      .done((response) => {
        mainVideoContainer.html(response.html)
        // Notify Stream iframes that the parent is ready (same protocol as mobile resize).
        mainVideoContainer.find("iframe").each(function () {
          try {
            this.contentWindow.postMessage({ context: "stream", action: "ready" }, "*")
          } catch (e) {
            // Ignore cross-origin timing errors before the iframe loads.
          }
        })
      })
      .fail((ex) => {
        mainVideoContainer.html('<div class="alert alert-danger">Failed to load video.</div>')
        console.error(ex)
      })
  }

  /**
   * Play the next playlist item after the current one ends.
   *
   * @param {HTMLElement|null} sourceWindow
   */
  const playNextFromEnded = (sourceWindow) => {
    const containers = $(".stream-playlist-container")
    containers.each(function () {
      const container = $(this)
      if (!Number(container.data("autoplaynext"))) {
        return
      }

      const items = container.find(".playlist-item")
      if (items.length < 2) {
        return
      }

      // Prefer the container that owns the iframe that sent the message.
      if (sourceWindow) {
        let ownsSource = false
        container.find("iframe").each(function () {
          if (this.contentWindow === sourceWindow) {
            ownsSource = true
            return false
          }
        })
        if (!ownsSource) {
          return
        }
      }

      const active = container.find(".playlist-item.active")
      if (!active.length) {
        return
      }

      const next = active.nextAll(".playlist-item").first()
      if (!next.length) {
        return
      }

      loadPlaylistItem(next, true)
    })
  }

  return {
    init: () => {
      $(".playlist-item").on("click", function () {
        loadPlaylistItem($(this), false)
      })

      window.addEventListener("message", (event) => {
        const data = event.data
        if (!data || data.context !== "stream") {
          return
        }
        if (!ENDED_ACTIONS[data.action]) {
          return
        }
        playNextFromEnded(event.source)
      })
    },
  }
})
