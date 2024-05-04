// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main.
 *
 * @package
 * @category    admin
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url'], function($, ajax, notification, str, url) {
    'use strict';

    return {
        init: function() {
            var self = this;

            this.elements = $('#stream-elements');
            this.loadingbars = url.imageUrl('icones/loading-bars', 'stream');

            $('body').on('click', '#stream-elements .list-item-grid', function() {
                var itemid = $(this).data('itemid'),
                    topic = $(this).find('.title').text();

                self.selected(itemid, topic);
            });

            $('#stream-title-search')
                .val($('#id_topic').val())
                .keyup(function() {
                    self.load();
                });

            this.load();
        },
        load: function() {
            var self = this;
            self.elements.html('<div style="text-align:center"><img height="80" src="' + self.loadingbars + '" ></div>');
            $('html, body').animate({
                scrollTop: self.elements.offset().top - 100
            }, 800);

            ajax.call([{
                methodname: 'mod_stream_video_list',
                args: {
                    term: $('#stream-title-search').val()
                },
                done: function(response) {
                    self.list(response, self);
                },
                fail: function(error) {
                    self.failed(error, self);
                },
            }]);
        },
        selected: function(identifier, topic) {
            setTimeout(function() {
                $('#id_identifier').val(identifier);
                $('#id_topic').val(topic);
                $('.list-item-grid').find('.item').removeClass('selected');
                $('#video_identifier_' + identifier).find('.item').addClass('selected');
            }, 100);
        },
        failed: function(error, self) {
            self.elements.html('<div class="alert alert-danger">' + error + '</div>');
        },
        list: function(response, self) {
            self.selected($('#id_identifier').val(), $('#id_topic').val());
            if (response.status == 'success') {
                if (response.videos.length) {
                    self.elements.html("");
                    $.each(response.videos, function(key, video) {
                        var html =
                            '<div class="list-item-grid" data-itemid="' + video.id + '" id="video_identifier_' + video.id + '">' +
                            '    <span class="item" >' +
                            '        <img src="' + video.thumbnail + '" height="133" width="236"><br>' +
                            '        <span class="title">' + video.title + '</span>' +
                            '    </span>' +
                            '</div>';
                        self.elements.append(html);
                    });
                } else {
                    self.elements.html('<div class="alert alert-info">' + str.get_string('noresults', 'mod_stream') + '</div>');
                }
            } else {
                this.failed(response.error);
            }
        },
    };
});