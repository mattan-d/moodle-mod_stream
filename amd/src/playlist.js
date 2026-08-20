define(['jquery', 'core/ajax', 'core/str'], function($, ajax, str) {
    'use strict';

    var ENDED_ACTIONS = {
        ended: true,
        finished: true,
        complete: true,
        videoEnded: true,
        playbackEnded: true
    };

    /**
     * Normalize postMessage payloads (object or JSON string).
     *
     * @param {*} raw
     * @return {Object|null}
     */
    var normalizeMessageData = function(raw) {
        if (!raw) {
            return null;
        }
        if (typeof raw === 'string') {
            try {
                raw = JSON.parse(raw);
            } catch (e) {
                return null;
            }
        }
        if (typeof raw !== 'object') {
            return null;
        }
        return raw;
    };

    /**
     * Whether playlist autoplay-next is enabled on a container.
     *
     * @param {jQuery} container
     * @return {boolean}
     */
    var isAutoplayNextEnabled = function(container) {
        // Prefer attribute over jQuery.data() cache to avoid stale/typed values.
        var attr = container.attr('data-autoplaynext');
        if (typeof attr !== 'undefined' && attr !== null && attr !== '') {
            return attr === '1' || attr === 'true' || attr === true;
        }
        return !!Number(container.data('autoplaynext'));
    };

    /**
     * Load a playlist item into the main player.
     *
     * @param {jQuery} playlistItem
     * @param {boolean} autoplay
     */
    var loadPlaylistItem = function(playlistItem, autoplay) {
        var container = playlistItem.closest('.stream-playlist-container');
        var mainVideoContainer = container.find('.stream-main-video');

        if (playlistItem.hasClass('active') && !autoplay) {
            return;
        }

        var identifier = playlistItem.data('identifier');
        var cmid = container.data('cmid');
        var includeaudio = container.data('includeaudio');

        container.find('.playlist-item').removeClass('active');
        playlistItem.addClass('active');

        mainVideoContainer.html(
            '<div class="loading-overlay"><div class="spinner-border" role="status">' +
            '<span class="sr-only">Loading...</span></div></div>'
        );

        ajax.call([{
            methodname: 'mod_stream_mark_as_viewed',
            args: {
                cmid: cmid,
                videoid: identifier
            }
        }])[0].done(function() {
            if (!playlistItem.find('.playlist-viewed-badge').length) {
                str.get_string('viewed', 'mod_stream').then(function(viewedString) {
                    playlistItem.find('.playlist-item-content').append(
                        '<span class="badge badge-success playlist-viewed-badge">' + viewedString + '</span>'
                    );
                });
            }
        });

        ajax.call([{
            methodname: 'mod_stream_get_player',
            args: {
                cmid: cmid,
                identifier: identifier,
                includeaudio: !!includeaudio,
                autoplay: !!autoplay
            }
        }])[0].done(function(response) {
            mainVideoContainer.html(response.html);
            mainVideoContainer.find('iframe').each(function() {
                try {
                    this.contentWindow.postMessage({context: 'stream', action: 'ready'}, '*');
                } catch (e) {
                    // Ignore cross-origin timing errors before the iframe loads.
                }
            });
        }).fail(function() {
            mainVideoContainer.html('<div class="alert alert-danger">Failed to load video.</div>');
        });
    };

    /**
     * Play the next playlist item after the current one ends.
     *
     * @param {Window|null} sourceWindow
     */
    var playNextFromEnded = function(sourceWindow) {
        $('.stream-playlist-container').each(function() {
            var container = $(this);
            if (!isAutoplayNextEnabled(container)) {
                return;
            }

            var items = container.find('.playlist-item');
            if (items.length < 2) {
                return;
            }

            if (sourceWindow) {
                var ownsSource = false;
                var hasVideoIframe = false;
                container.find('iframe').each(function() {
                    var src = this.getAttribute('src') || '';
                    // Ignore the optional audio iframe; only the video embed advances the playlist.
                    if (src.indexOf('/embed-audio/') !== -1) {
                        return;
                    }
                    hasVideoIframe = true;
                    if (this.contentWindow === sourceWindow) {
                        ownsSource = true;
                        return false;
                    }
                });
                // If we have iframes but none matched, skip this container.
                // If the page has a single playlist container, still advance as a fallback
                // (some browsers may not preserve a stable source Window reference).
                if (hasVideoIframe && !ownsSource && $('.stream-playlist-container').length > 1) {
                    return;
                }
            }

            var active = container.find('.playlist-item.active');
            if (!active.length) {
                return;
            }

            var next = active.nextAll('.playlist-item').first();
            if (!next.length) {
                return;
            }

            loadPlaylistItem(next, true);
        });
    };

    return {
        init: function() {
            $('.playlist-item').on('click', function() {
                loadPlaylistItem($(this), false);
            });

            window.addEventListener('message', function(event) {
                var data = normalizeMessageData(event.data);
                if (!data || data.context !== 'stream') {
                    return;
                }
                if (!ENDED_ACTIONS[data.action]) {
                    return;
                }
                playNextFromEnded(event.source || null);
            });
        }
    };
});
