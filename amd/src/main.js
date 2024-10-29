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

define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url'], function ($, ajax, notification, str, url) {
    'use strict';

    return {
        init: function () {
            var self = this;

            this.elements = $('#stream-elements');
            this.loadingbars = url.imageUrl('icones/loading-bars', 'stream');

            $('body').on('click', '#stream-elements .list-item-grid', function () {
                var itemid = $(this).data('itemid');
                var topic = $(this).find('.title').text();

                self.selected(itemid, topic);
            });

            $('body').on('click', '.btn-upload', function (e) {
                e.preventDefault();
                $('#upload_stream').toggle();
            });

            $('#stream-title-search')
                .val($('#id_topic').val())
                .keyup(function () {
                    self.load();
                });

            this.load();

            // Add event listener to receive messages from iframes
            window.addEventListener('message', function (event) {
                this.message(event, self);
            }.bind(this), false);
        }, message: function (event) {
            // Check if the message contains the streamid
            if (event.data && event.data.streamid) {
                $('#id_identifier').val(event.data.streamid);
                $('#id_topic').val(event.data.topic);
                $('#stream-title-search').val(event.data.topic);
                $('#upload_stream').hide();

                self.elements.html("");
                var html = '<div class="col list-item-grid" data-itemid="' + event.data.streamid + '" ' + 'id="video_identifier_' + event.data.streamid + '"><span class="item selected"><div class="thumbnail">' + '<img src="' + event.data.thumbnail + '" class="img-fluid img-rounded">' + '</div><span class="title">' + event.data.topic + '</span></span></div>';
                self.elements.append(html);
            }
        }, load: function () {
            var self = this;
            self.elements.html('<div style="text-align:center"><img height="80" src="' + self.loadingbars + '" ></div>');
            $('html, body').animate({
                scrollTop: self.elements.offset().top - 100
            }, 800);

            ajax.call([{
                methodname: 'mod_stream_video_list', args: {
                    term: $('#stream-title-search').val(), courseid: $('input[name="course"]').val()
                }
            }])[0]
                .then(function (response) {
                    return self.list(response, self);
                })
                .catch(function (error) {
                    return self.failed(error, self);
                });
        }, selected: function (identifier, topic) {
            setTimeout(function () {
                $('#id_identifier').val(identifier);
                $('#id_topic').val(topic);
                $('.list-item-grid').find('.item').removeClass('selected');
                $('#video_identifier_' + identifier).find('.item').addClass('selected');
            }, 100);
        }, failed: function (error, self) {
            return str.get_string('servererror', 'moodle')
                .then(function (connectionfailed) {
                    return self.elements.html('<div class="alert alert-danger">' + connectionfailed + '</div>');
                });
        }, list: function (response, self) {
            self.selected($('#id_identifier').val(), $('#id_topic').val());
            if (response.status == 'success') {
                if (response.videos.length) {
                    self.elements.html("");


                    $.each(response.videos, function (key, video) {

                        str.get_strings([
                            {'key': 'views', component: 'mod_stream'},
                            {'key': 'before', component: 'mod_stream'},
                        ])
                            .then(function (string) {
                                var html = '<div class="col list-item-grid" data-itemid="' + video.id + '" ' + 'id="video_identifier_' + video.id + '">' + '<span class="item" ><div class="thumbnail">' + '<img src="' + video.thumbnail + '" class="img-fluid img-rounded">' + '<span class="datecreated">' + video.datecreated + '</span><span class="duration">' + video.duration + '</span></div><span class="title">' + video.title + '</span><span class="details">' + video.views + ' ' + string[0] + ' <span class="bubble">●</span>' + ' ' + string[1] + ' ' + video.elapsed + '</span></span></div>';
                                self.elements.append(html);
                            });
                    });
                } else {
                    return str.get_string('noresults', 'mod_stream')
                        .then(function (noresults) {
                            return self.elements.html('<div class="alert alert-info">' + noresults + '</div>');
                        });
                }
            }
            return true;
        },
    };
});
