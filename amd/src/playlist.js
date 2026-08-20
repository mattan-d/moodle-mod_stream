define(['jquery', 'core/ajax', 'core/str'], function($, ajax, str) {
    'use strict';

    var ENDED_ACTIONS = {
        ended: true,
        finished: true,
        complete: true,
        videoEnded: true,
        playbackEnded: true
    };

    var autoplayTimer = null;
    var autoplayToken = 0;

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
        var attr = container.attr('data-autoplaynext');
        if (typeof attr !== 'undefined' && attr !== null && attr !== '') {
            return attr === '1' || attr === 'true' || attr === true;
        }
        return !!Number(container.data('autoplaynext'));
    };

    /**
     * Parse a clock duration (MM:SS / HH:MM:SS) or seconds into seconds.
     *
     * @param {*} value
     * @return {number}
     */
    var parseDurationSeconds = function(value) {
        if (value === null || typeof value === 'undefined' || value === '') {
            return 0;
        }
        if (typeof value === 'number' && !isNaN(value)) {
            return Math.max(0, Math.floor(value));
        }
        var text = String(value).trim();
        if (/^\d+$/.test(text)) {
            return Math.max(0, parseInt(text, 10));
        }
        var parts = text.split(':');
        if (!parts.length || parts.length > 3) {
            return 0;
        }
        var seconds = 0;
        var multiplier = 1;
        for (var i = parts.length - 1; i >= 0; i--) {
            var part = parseInt(parts[i], 10);
            if (isNaN(part)) {
                return 0;
            }
            seconds += part * multiplier;
            multiplier *= 60;
        }
        return Math.max(0, seconds);
    };

    /**
     * Resolve duration in seconds for a playlist item.
     *
     * @param {jQuery} playlistItem
     * @return {number}
     */
    var getItemDurationSeconds = function(playlistItem) {
        var fromAttr = playlistItem.attr('data-duration-seconds');
        var seconds = parseDurationSeconds(fromAttr);
        if (seconds > 0) {
            return seconds;
        }
        return parseDurationSeconds(playlistItem.find('.playlist-item-duration').first().text());
    };

    /**
     * Cancel any pending autoplay-next timer.
     */
    var clearAutoplayTimer = function() {
        if (autoplayTimer) {
            window.clearTimeout(autoplayTimer);
            autoplayTimer = null;
        }
        autoplayToken += 1;
    };

    /**
     * Schedule advancing to the next item after the current video duration.
     * Used when the Stream embed cannot notify Moodle that playback ended.
     *
     * @param {jQuery} playlistItem
     * @param {jQuery} container
     */
    var scheduleAutoplayNext = function(playlistItem, container) {
        clearAutoplayTimer();

        if (!isAutoplayNextEnabled(container)) {
            return;
        }

        var durationSeconds = getItemDurationSeconds(playlistItem);
        if (durationSeconds <= 0) {
            return;
        }

        var next = playlistItem.nextAll('.playlist-item').first();
        if (!next.length) {
            return;
        }

        // Small buffer so we don't cut off the last second of playback.
        var delayMs = (durationSeconds + 1) * 1000;
        var token = autoplayToken;

        autoplayTimer = window.setTimeout(function() {
            if (token !== autoplayToken) {
                return;
            }
            if (!playlistItem.hasClass('active')) {
                return;
            }
            loadPlaylistItem(next, true);
        }, delayMs);
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

        clearAutoplayTimer();

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
            scheduleAutoplayNext(playlistItem, container);
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
                    if (src.indexOf('/embed-audio/') !== -1) {
                        return;
                    }
                    hasVideoIframe = true;
                    if (this.contentWindow === sourceWindow) {
                        ownsSource = true;
                        return false;
                    }
                });
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

            clearAutoplayTimer();
            loadPlaylistItem(next, true);
        });
    };

    return {
        init: function() {
            $('.playlist-item').on('click', function() {
                loadPlaylistItem($(this), false);
            });

            // Duration-based fallback (works without Stream player updates).
            $('.stream-playlist-container').each(function() {
                var container = $(this);
                if (!isAutoplayNextEnabled(container)) {
                    return;
                }
                var active = container.find('.playlist-item.active').first();
                if (active.length) {
                    scheduleAutoplayNext(active, container);
                }
            });

            // Optional fast-path if the Stream embed later starts sending ended events.
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
