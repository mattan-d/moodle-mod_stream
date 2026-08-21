define(['jquery', 'core/ajax', 'core/str'], function($, ajax, str) {
    'use strict';

    /**
     * Playlist autoplay using only Moodle-side duration timing.
     * Uses the original Stream iframe embed (no postMessage, no native video).
     *
     * Timer starts when the learner engages the player (click into iframe),
     * or immediately after an auto-advanced load with autoplay=1.
     */

    var config = {
        autoplaynext: false,
        durations: {}
    };

    var timerState = {
        timeoutId: null,
        intervalId: null,
        token: 0,
        remainingMs: 0,
        deadline: 0,
        paused: false,
        armedForId: null,
        playlistItem: null,
        container: null
    };

    /**
     * @param {jQuery} container
     * @return {boolean}
     */
    var isAutoplayNextEnabled = function(container) {
        if (config.autoplaynext) {
            return true;
        }
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
     * @return {string}
     */
    var getItemId = function(playlistItem) {
        var id = playlistItem.attr('data-identifier');
        if (typeof id === 'undefined' || id === null || id === '') {
            id = playlistItem.data('identifier');
        }
        return String(id);
    };

    /**
     * @param {jQuery} playlistItem
     * @return {number}
     */
    var getItemDurationSeconds = function(playlistItem) {
        var id = getItemId(playlistItem);
        if (id && Object.prototype.hasOwnProperty.call(config.durations, id)) {
            var fromConfig = parseDurationSeconds(config.durations[id]);
            if (fromConfig > 0) {
                return fromConfig;
            }
        }

        var seconds = parseDurationSeconds(playlistItem.attr('data-duration-seconds'));
        if (seconds > 0) {
            return seconds;
        }
        seconds = parseDurationSeconds(playlistItem.data('durationSeconds'));
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
        if (timerState.intervalId) {
            window.clearInterval(timerState.intervalId);
            timerState.intervalId = null;
        }
        timerState.token += 1;
        timerState.remainingMs = 0;
        timerState.deadline = 0;
        timerState.paused = false;
        timerState.armedForId = null;
        timerState.playlistItem = null;
        timerState.container = null;
    };

    /**
     * @param {jQuery} playlistItem
     * @param {jQuery} container
     */
    var playNextItem = function(playlistItem, container) {
        if (!playlistItem || !container || !container.length) {
            return;
        }
        if (!isAutoplayNextEnabled(container)) {
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
    };

    /**
     * @param {jQuery} playlistItem
     * @param {jQuery} container
     * @param {number} delayMs
     */
    var startCountdown = function(playlistItem, container, delayMs) {
        clearAutoplayTimer();

        timerState.token += 1;
        var token = timerState.token;
        timerState.playlistItem = playlistItem;
        timerState.container = container;
        timerState.armedForId = getItemId(playlistItem);
        timerState.remainingMs = delayMs;
        timerState.deadline = Date.now() + delayMs;
        timerState.paused = false;

        // Poll deadline so background timer throttling is less likely to skip the switch.
        timerState.intervalId = window.setInterval(function() {
            if (token !== timerState.token || timerState.paused) {
                return;
            }
            if (Date.now() < timerState.deadline) {
                return;
            }
            window.clearInterval(timerState.intervalId);
            timerState.intervalId = null;
            playNextItem(playlistItem, container);
        }, 500);
    };

    /**
     * @param {jQuery} playlistItem
     * @param {jQuery} container
     */
    var armAutoplayTimer = function(playlistItem, container) {
        if (!isAutoplayNextEnabled(container)) {
            return;
        }
        if (!playlistItem || !playlistItem.length || !playlistItem.hasClass('active')) {
            return;
        }

        var itemId = getItemId(playlistItem);
        if (timerState.armedForId === itemId && (timerState.intervalId || timerState.paused)) {
            // Already counting for this item.
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

        // Small buffer so the last second is not cut off.
        startCountdown(playlistItem, container, (durationSeconds + 1) * 1000);
    };

    var pauseCountdown = function() {
        if (timerState.paused || !timerState.deadline) {
            return;
        }
        if (timerState.intervalId) {
            window.clearInterval(timerState.intervalId);
            timerState.intervalId = null;
        }
        timerState.remainingMs = Math.max(0, timerState.deadline - Date.now());
        timerState.paused = true;
    };

    var resumeCountdown = function() {
        if (!timerState.paused || !timerState.playlistItem || !timerState.container) {
            return;
        }
        if (timerState.remainingMs <= 0) {
            playNextItem(timerState.playlistItem, timerState.container);
            return;
        }
        startCountdown(timerState.playlistItem, timerState.container, timerState.remainingMs);
    };

    /**
     * Start timing after the learner clicks into the embed iframe.
     */
    var bindIframeEngagement = function(mainVideoContainer, playlistItem, container) {
        var iframe = mainVideoContainer.find('iframe.stream-video-iframe').first();
        if (!iframe.length) {
            iframe = mainVideoContainer.find('iframe').filter(function() {
                var src = this.getAttribute('src') || '';
                return src.indexOf('/embed-audio/') === -1;
            }).first();
        }
        if (!iframe.length) {
            return;
        }

        var tryArmFromEngagement = function() {
            window.setTimeout(function() {
                var active = document.activeElement;
                if (active === iframe.get(0)) {
                    armAutoplayTimer(playlistItem, container);
                }
            }, 0);
        };

        $(window).off('blur.streamAutoplay').on('blur.streamAutoplay', tryArmFromEngagement);
        // focusin bubbles when the iframe receives focus (more reliable than blur alone).
        mainVideoContainer.off('focusin.streamAutoplay').on('focusin.streamAutoplay', function() {
            armAutoplayTimer(playlistItem, container);
        });
    };

    /**
     * @param {jQuery} mainVideoContainer
     * @param {jQuery} playlistItem
     * @param {jQuery} container
     * @param {boolean} autoplay
     */
    var setupPlayerTiming = function(mainVideoContainer, playlistItem, container, autoplay) {
        if (!isAutoplayNextEnabled(container)) {
            return;
        }

        if (autoplay) {
            // Next item was auto-advanced; playback should start via ?autoplay=1.
            var armed = false;
            var arm = function() {
                if (armed) {
                    return;
                }
                armed = true;
                armAutoplayTimer(playlistItem, container);
            };
            var iframe = mainVideoContainer.find('iframe').first();
            if (iframe.length) {
                iframe.off('load.streamAutoplay').on('load.streamAutoplay', arm);
            }
            window.setTimeout(arm, 1200);
            return;
        }

        // First / manually selected video: wait until the user engages the player.
        bindIframeEngagement(mainVideoContainer, playlistItem, container);
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
        $(window).off('blur.streamAutoplay');

        var identifier = getItemId(playlistItem);
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
            setupPlayerTiming(mainVideoContainer, playlistItem, container, !!autoplay);
        }).fail(function() {
            mainVideoContainer.html('<div class="alert alert-danger">Failed to load video.</div>');
        });
    };

    return {
        /**
         * @param {Object} [opts]
         * @param {boolean} [opts.autoplaynext]
         * @param {Object} [opts.durations] Map of video id → seconds
         */
        init: function(opts) {
            opts = opts || {};
            config.autoplaynext = !!opts.autoplaynext;
            config.durations = opts.durations || {};

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
                    // Single-video layout: no playlist items in DOM.
                    return;
                }
                var mainVideoContainer = container.find('.stream-main-video');
                setupPlayerTiming(mainVideoContainer, active, container, false);
            });
        }
    };
});
