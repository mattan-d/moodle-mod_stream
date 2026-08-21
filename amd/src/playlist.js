define(['jquery', 'core/ajax', 'core/str'], function($, ajax, str) {
    'use strict';

    /**
     * Moodle-only playlist autoplay.
     *
     * Does not depend on VideoTube/Stream postMessage events. Advances by the
     * known video duration from the playlist metadata after the iframe loads.
     */

    var timerState = {
        timeoutId: null,
        token: 0,
        remainingMs: 0,
        deadline: 0,
        paused: false,
        playlistItem: null,
        container: null
    };

    /**
     * @param {jQuery} container
     * @return {boolean}
     */
    var isAutoplayNextEnabled = function(container) {
        var attr = container.attr('data-autoplaynext');
        if (typeof attr !== 'undefined' && attr !== null && attr !== '') {
            return attr === '1' || attr === 'true';
        }
        return !!Number(container.data('autoplaynext'));
    };

    /**
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
        if (/^\d+(\.\d+)?$/.test(text)) {
            return Math.max(0, Math.floor(parseFloat(text)));
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
     * @param {jQuery} playlistItem
     * @return {number}
     */
    var getItemDurationSeconds = function(playlistItem) {
        var seconds = parseDurationSeconds(playlistItem.attr('data-duration-seconds'));
        if (seconds > 0) {
            return seconds;
        }
        seconds = parseDurationSeconds(playlistItem.data('duration-seconds'));
        if (seconds > 0) {
            return seconds;
        }
        return parseDurationSeconds(playlistItem.find('.playlist-item-duration').first().text());
    };

    var clearAutoplayTimer = function() {
        if (timerState.timeoutId) {
            window.clearTimeout(timerState.timeoutId);
            timerState.timeoutId = null;
        }
        timerState.token += 1;
        timerState.remainingMs = 0;
        timerState.deadline = 0;
        timerState.paused = false;
        timerState.playlistItem = null;
        timerState.container = null;
    };

    /**
     * @param {jQuery} playlistItem
     * @param {jQuery} container
     */
    var armAutoplayTimer = function(playlistItem, container) {
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

        // +1s buffer so the last second is not cut off.
        startCountdown(playlistItem, container, (durationSeconds + 1) * 1000);
    };

    /**
     * @param {jQuery} playlistItem
     * @param {jQuery} container
     * @param {number} delayMs
     */
    var startCountdown = function(playlistItem, container, delayMs) {
        if (timerState.timeoutId) {
            window.clearTimeout(timerState.timeoutId);
            timerState.timeoutId = null;
        }

        timerState.token += 1;
        var token = timerState.token;
        timerState.playlistItem = playlistItem;
        timerState.container = container;
        timerState.remainingMs = delayMs;
        timerState.deadline = Date.now() + delayMs;
        timerState.paused = false;

        timerState.timeoutId = window.setTimeout(function() {
            if (token !== timerState.token) {
                return;
            }
            if (!playlistItem.hasClass('active')) {
                return;
            }
            var next = playlistItem.nextAll('.playlist-item').first();
            if (!next.length) {
                return;
            }
            loadPlaylistItem(next, true);
        }, delayMs);
    };

    var pauseCountdown = function() {
        if (!timerState.timeoutId || timerState.paused) {
            return;
        }
        window.clearTimeout(timerState.timeoutId);
        timerState.timeoutId = null;
        timerState.remainingMs = Math.max(0, timerState.deadline - Date.now());
        timerState.paused = true;
    };

    var resumeCountdown = function() {
        if (!timerState.paused || !timerState.playlistItem || !timerState.container) {
            return;
        }
        if (timerState.remainingMs <= 0) {
            return;
        }
        startCountdown(timerState.playlistItem, timerState.container, timerState.remainingMs);
    };

    /**
     * @param {jQuery} mainVideoContainer
     * @param {jQuery} playlistItem
     * @param {jQuery} container
     */
    var bindIframeLoadThenArm = function(mainVideoContainer, playlistItem, container) {
        var iframe = mainVideoContainer.find('iframe.stream-video-iframe').first();
        if (!iframe.length) {
            iframe = mainVideoContainer.find('iframe').filter(function() {
                var src = this.getAttribute('src') || '';
                return src.indexOf('/embed-audio/') === -1;
            }).first();
        }

        var armed = false;
        var arm = function() {
            if (armed) {
                return;
            }
            armed = true;
            armAutoplayTimer(playlistItem, container);
        };

        if (!iframe.length) {
            arm();
            return;
        }

        iframe.off('load.streamAutoplay').on('load.streamAutoplay', function() {
            arm();
        });

        // Fallback if load already fired or never fires.
        window.setTimeout(arm, 1500);
    };

    /**
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
            bindIframeLoadThenArm(mainVideoContainer, playlistItem, container);
        }).fail(function() {
            mainVideoContainer.html('<div class="alert alert-danger">Failed to load video.</div>');
        });
    };

    return {
        init: function() {
            $('.playlist-item').on('click', function() {
                loadPlaylistItem($(this), false);
            });

            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    pauseCountdown();
                } else {
                    resumeCountdown();
                }
            });

            $('.stream-playlist-container').each(function() {
                var container = $(this);
                if (!isAutoplayNextEnabled(container)) {
                    return;
                }
                var active = container.find('.playlist-item.active').first();
                if (!active.length) {
                    return;
                }
                var mainVideoContainer = container.find('.stream-main-video');
                bindIframeLoadThenArm(mainVideoContainer, active, container);
            });
        }
    };
});
