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
define(['jquery', 'jqueryui', 'core/ajax', 'core/notification', 'core/str', 'core/url'], (
    $,
    jqui,
    ajax,
    notification,
    str,
    url,
) => ({
  normalizeId: function(id) {
    if (id === null || typeof id === 'undefined') {
      return '';
    }
    return String(id).trim();
  },

  normalizeIdList: function(ids) {
    var normalized = [];
    (ids || []).forEach((id) => {
      id = this.normalizeId(id);
      if (id && normalized.indexOf(id) === -1) {
        normalized.push(id);
      }
    });
    return normalized;
  },

  syncVideoOrderWithSelection: function() {
    this.selectedIds = this.normalizeIdList(this.selectedIds);
    this.videoOrder = this.normalizeIdList(this.videoOrder);

    var synced = [];
    this.videoOrder.forEach((id) => {
      if (this.selectedIds.indexOf(id) > -1 && synced.indexOf(id) === -1) {
        synced.push(id);
      }
    });
    this.selectedIds.forEach((id) => {
      if (synced.indexOf(id) === -1) {
        synced.push(id);
      }
    });

    this.videoOrder = synced;
    $('input[name=identifier]').val(this.selectedIds.join(','));
    $('input[name=video_order]').val(JSON.stringify(this.videoOrder));
  },

  cacheInitialVideos: function(videos) {
    var self = this;
    (videos || []).forEach((video) => {
      var id = self.normalizeId(video.id);
      if (!id) {
        return;
      }
      var existing = self.allVideos.find((v) => self.normalizeId(v.id) === id);
      if (!existing) {
        self.allVideos.push(video);
      }
      if (!self.selectedVideoCache.find((v) => self.normalizeId(v.id) === id)) {
        self.selectedVideoCache.push(video);
      }
    });
  },

  isPlaceholderVideoName: function(name, videoId) {
    if (!name) {
      return true;
    }
    name = String(name).trim();
    return name === '' || name === ('Video ' + videoId) || name === 'New Video';
  },

  normalizeVideoNames: function(names) {
    var normalized = {};
    Object.keys(names || {}).forEach((key) => {
      var id = this.normalizeId(key);
      if (id) {
        normalized[id] = names[key];
      }
    });
    return normalized;
  },

  getVideoMetadata: function(videoId) {
    var meta = {title: '', thumbnail: ''};

    var videoElement = $('#video_identifier_' + videoId);
    if (videoElement.length > 0) {
      meta.title = videoElement.find('.title').text().trim();
      meta.thumbnail = videoElement.find('img').attr('src') || '';
      return meta;
    }

    var cachedVideo = this.allVideos.find((v) => this.normalizeId(v.id) === videoId);
    if (!cachedVideo) {
      cachedVideo = this.selectedVideoCache.find((v) => this.normalizeId(v.id) === videoId);
    }
    if (cachedVideo) {
      meta.title = cachedVideo.title || '';
      meta.thumbnail = cachedVideo.thumbnail || '';
    }

    return meta;
  },

  getDisplayName: function(videoId) {
    var customName = this.videoNames[videoId];
    if (!this.isPlaceholderVideoName(customName, videoId)) {
      return customName;
    }

    var meta = this.getVideoMetadata(videoId);
    if (meta.title) {
      return meta.title;
    }

    return customName || ('Video ' + videoId);
  },

  mergeTitlesFromCache: function() {
    var self = this;
    var changed = false;

    this.selectedIds.forEach((videoId) => {
      if (!self.isPlaceholderVideoName(self.videoNames[videoId], videoId)) {
        return;
      }
      var meta = self.getVideoMetadata(videoId);
      if (meta.title) {
        self.videoNames[videoId] = meta.title;
        changed = true;
      }
    });

    if (changed) {
      $('input[name=video_names]').val(JSON.stringify(this.videoNames));
    }
  },

  init: function(config) {
    var self = this;
    config = config || {};

    this.elements = $('#stream-elements');
    this.loadingbars = url.imageUrl('icones/loading-bars', 'stream');
    this.selectedIds = this.normalizeIdList(($('input[name=identifier]').val() || '').split(','));
    this.videoOrder = [];
    this.videoNames = {};
    this.allVideos = [];
    this.selectedVideoCache = (config.initialvideos || []).slice();
    this.cacheInitialVideos(config.initialvideos || []);

    // Initialize video order from existing data
    try {
      var orderData = $('input[name=video_order]').val();
      if (orderData) {
        this.videoOrder = this.normalizeIdList(JSON.parse(orderData));
      }
    } catch (e) {
      this.videoOrder = [];
    }

    this.syncVideoOrderWithSelection();

    try {
      var namesData = $('input[name=video_names]').val();
      if (namesData) {
        this.videoNames = this.normalizeVideoNames(JSON.parse(namesData));
      }
    } catch (e) {
      this.videoNames = {};
    }

    this.mergeTitlesFromCache();

    this.currentPage = 1;
    this.itemsPerPage = 12; // 3 rows of 4 items
    this.totalVideos = 0;

    // Initialize sortable playlist
    this.initSortablePlaylist();

    $('body').on('click', '#stream-elements .list-item-grid', function() {
      var itemid = self.normalizeId($(this).data('itemid'));
      var index = self.selectedIds.indexOf(itemid);

      if (index > -1) {
        self.selectedIds.splice(index, 1);
        $(this).find('.item').removeClass('selected');
        // Remove from video order
        var orderIndex = self.videoOrder.indexOf(itemid);
        if (orderIndex > -1) {
          self.videoOrder.splice(orderIndex, 1);
        }
        delete self.videoNames[itemid];
      } else {
        self.selectedIds.push(itemid);
        $(this).find('.item').addClass('selected');
        // Add to video order if not already there
        if (self.videoOrder.indexOf(itemid) === -1) {
          self.videoOrder.push(itemid);
        }
        if (!self.videoNames[itemid]) {
          var originalTitle = $(this).find('.title').text().trim();
          self.videoNames[itemid] = originalTitle;
        }
      }
      $('input[name=identifier]').val(self.selectedIds.join(','));
      $('input[name=video_order]').val(JSON.stringify(self.videoOrder));
      $('input[name=video_names]').val(JSON.stringify(self.videoNames));
      self.syncVideoOrderWithSelection();
      self.updatePlaylistOrder();
    });

    $('body').on('click', '#stream-load #stream-sort .btn', function(e) {
      e.preventDefault();
      $('#stream-load #stream-sort .btn').removeClass('active');
      $(this).toggleClass('active');
      self.load();
    });

    $('body').on('click', '.btn-upload', (e) => {
      e.preventDefault();
      $('#upload_stream').toggle();
    });

    $('#stream-title-search').keyup(() => {
      self.load();
    });

    this.load();

    window.addEventListener(
        'message',
        (event) => {
          if (event.data.iframeHeight) {
            var iframe = document.getElementById('upload_stream');
            if (iframe) {
              iframe.style.height = event.data.iframeHeight + 'px';
            }
          }
        },
        false,
    );

    // Add event listener to receive messages from iframes
    window.addEventListener(
        'message',
        function(event) {
          this.message(event, self);
        }.bind(this),
        false,
    );

    // Pagination click handler
    $('body').on('click', '#stream-pagination .page-link[data-page]', function(e) {
      e.preventDefault();
      var page = Number.parseInt($(this).data('page'));
      if (page && page !== self.currentPage) {
        self.currentPage = page;
        self.renderCurrentPage();
      }
    });
  },

  initSortablePlaylist: function() {
    // Create playlist container if it doesn't exist
    if ($('#playlist-container').length === 0) {
      // Get localized strings first
      str.get_strings([
        {key: 'selectedvideos', component: 'mod_stream'},
        {key: 'dragtoorder', component: 'mod_stream'},
      ]).then((strings) => {
        var containerHtml =
            '<div id="playlist-container">' +
            '<h4>' +
            strings[0] +
            ' (' +
            strings[1] +
            '):</h4>' +
            '<ul id="sortable-playlist" class="list-group"></ul>' +
            '</div>';

        $('#stream-load').after(containerHtml);

        // Initialize sortable after container is created
        this.initializeSortable();
        this.updatePlaylistOrder();
      }).catch((error) => {
        // Fallback to English if string loading fails
        var containerHtml =
            '<div id="playlist-container">' +
            '<h4>Selected Videos (Drag to reorder):</h4>' +
            '<ul id="sortable-playlist" class="list-group"></ul>' +
            '</div>';

        $('#stream-load').after(containerHtml);

        // Initialize sortable after container is created
        this.initializeSortable();
        this.updatePlaylistOrder();
      });
    } else {
      // Container already exists, just initialize sortable
      this.initializeSortable();
      this.updatePlaylistOrder();
    }
  },

  initializeSortable: function() {
    // Initialize sortable with a delay to ensure DOM is ready
    setTimeout(() => {
      if ($('#sortable-playlist').length > 0) {
        try {
          $('#sortable-playlist').sortable({
            update: (event, ui) => {
              this.updateVideoOrderFromPlaylist();
            },
            placeholder: 'ui-state-highlight list-group-item',
            cursor: 'move',
            tolerance: 'pointer',
            opacity: 0.8,
          });
        } catch (e) {
          console.warn('jQuery UI sortable not available, using fallback drag implementation');
          this.initFallbackDragDrop();
        }
      }
    }, 100);
  },

  initFallbackDragDrop: function() {
    var self = this;
    var draggedElement = null;

    // Add drag and drop event listeners as fallback
    $(document).on('dragstart', '.playlist-item', function(e) {
      draggedElement = this;
      $(this).addClass('dragging');
      e.originalEvent.dataTransfer.effectAllowed = 'move';
      e.originalEvent.dataTransfer.setData('text/html', this.outerHTML);
    });

    $(document).on('dragend', '.playlist-item', function(e) {
      $(this).removeClass('dragging');
      draggedElement = null;
    });

    $(document).on('dragover', '.playlist-item', function(e) {
      e.preventDefault();
      e.originalEvent.dataTransfer.dropEffect = 'move';

      if (draggedElement !== this) {
        var rect = this.getBoundingClientRect();
        var midpoint = rect.top + rect.height / 2;

        if (e.originalEvent.clientY < midpoint) {
          $(this).before(draggedElement);
        } else {
          $(this).after(draggedElement);
        }

        self.updateVideoOrderFromPlaylist();
      }
    });

    // Make items draggable
    $(document).on('mouseenter', '.playlist-item', function() {
      $(this).attr('draggable', 'true');
    });
  },

  updatePlaylistOrder: function() {
    var playlist = $('#sortable-playlist');
    playlist.empty();

    var displayOrder = this.normalizeIdList(this.videoOrder);
    this.selectedIds.forEach((id) => {
      if (displayOrder.indexOf(id) === -1) {
        displayOrder.push(id);
      }
    });

    // Add selected videos to playlist in order, even if not on current page
    displayOrder.forEach((videoId) => {
      if (this.selectedIds.indexOf(videoId) === -1) {
        return;
      }

      var displayName = this.getDisplayName(videoId);
      var meta = this.getVideoMetadata(videoId);
      var thumbnail = meta.thumbnail;
      var title = meta.title || displayName;

      if ($('#video_identifier_' + videoId).length > 0 && this.isPlaceholderVideoName(this.videoNames[videoId], videoId)) {
        this.videoNames[videoId] = title;
      } else if (meta.title && this.isPlaceholderVideoName(this.videoNames[videoId], videoId)) {
        this.videoNames[videoId] = meta.title;
      }

      var playlistItem = $(
          '<li class="list-group-item playlist-item" data-video-id="' +
          videoId +
          '" draggable="true">' +
          '<div class="d-flex align-items-center">' +
          (thumbnail
              ? '<img src="' +
              thumbnail +
              '" class="playlist-thumbnail me-3" style="width: 60px; height: 34px; object-fit: cover;">'
              : '<div class="playlist-thumbnail me-3" style="width: 60px; height: 34px; background: #ccc;"></div>') +
          '<input type="text" class="form-control playlist-title-input flex-grow-1 me-2" value="' +
          displayName.replace(/"/g, '&quot;') +
          '" data-video-id="' +
          videoId +
          '" placeholder="' +
          title.replace(/"/g, '&quot;') +
          '">' +
          '<span class="drag-handle ms-2" style="cursor: move;">⋮⋮</span>' +
          '</div>' +
          '</li>',
      );

      playlist.append(playlistItem);
    });

    $('input[name=video_names]').val(JSON.stringify(this.videoNames));

    var self = this;
    $('.playlist-title-input').on('input', function() {
      var videoId = self.normalizeId($(this).data('video-id'));
      var newName = $(this).val().trim();
      if (newName) {
        self.videoNames[videoId] = newName;
      } else {
        // If empty, reset to original title
        var videoElement = $('#video_identifier_' + videoId);
        if (videoElement.length > 0) {
          var originalTitle = videoElement.find('.title').text().trim();
          self.videoNames[videoId] = originalTitle;
          $(this).val(originalTitle);
        } else {
          // Try cached data
          var cachedVideo = self.allVideos.find((v) => self.normalizeId(v.id) === videoId);
          if (!cachedVideo) {
            cachedVideo = self.selectedVideoCache.find((v) => self.normalizeId(v.id) === videoId);
          }
          if (cachedVideo && cachedVideo.title) {
            self.videoNames[videoId] = cachedVideo.title;
            $(this).val(cachedVideo.title);
          }
        }
      }
      $('input[name=video_names]').val(JSON.stringify(self.videoNames));
    });

    // Show/hide playlist based on selection
    if (this.selectedIds.length > 0) {
      $('#playlist-container').show();
    } else {
      $('#playlist-container').hide();
    }
  },

  updateVideoOrderFromPlaylist: function() {
    var newOrder = [];
    $('#sortable-playlist .playlist-item').each(function() {
      newOrder.push($(this).data('video-id').toString().trim());
    });
    this.videoOrder = this.normalizeIdList(newOrder);
    $('input[name=video_order]').val(JSON.stringify(this.videoOrder));
  },

  message: (event, self) => {
    // Check if the message contains the streamid
    if (event.data && event.data.streamid) {
      var streamid = self.normalizeId(event.data.streamid);
      if (self.selectedIds.indexOf(streamid) === -1) {
        self.selectedIds.push(streamid);
        if (self.videoOrder.indexOf(streamid) === -1) {
          self.videoOrder.push(streamid);
        }
        if (!self.videoNames[streamid]) {
          self.videoNames[streamid] = 'New Video';
        }
      }
      $('input[name=identifier]').val(self.selectedIds.join(','));
      $('input[name=video_order]').val(JSON.stringify(self.videoOrder));
      $('input[name=video_names]').val(JSON.stringify(self.videoNames));
      self.syncVideoOrderWithSelection();
      $('#upload_stream').hide();
      self.load();
    }
  },

  load: function() {
    var sort = $('#stream-load #stream-sort .btn.active').attr('data-name');

    // Reset pagination when loading new data
    this.currentPage = 1;
    this.selectedVideoCache = this.allVideos.filter((video) => {
      return this.selectedIds.indexOf(this.normalizeId(video.id)) > -1;
    });
    this.allVideos = this.selectedVideoCache.slice();
    this.totalVideos = 0;

    this.elements.html('<div style="text-align:center"><img height="80" src="' + this.loadingbars + '" ></div>');

    ajax.call([
      {
        methodname: 'mod_stream_video_list',
        args: {
          term: $('#stream-title-search').val(),
          courseid: $('input[name="course"]').val(),
          sort: sort,
        },
      },
    ])[0].then((response) => this.list(response, this)).catch((error) => this.failed(error, this));
  },

  failed: (error, self) =>
      str.get_string('servererror', 'moodle').
          then((connectionfailed) => self.elements.html('<div class="alert alert-danger">' + connectionfailed + '</div>')),

  list: (response, self) => {
    if (response.status == 'success') {
      if (response.videos.length) {
        // Store all videos and prioritize selected ones
        self.allVideos = response.videos;
        self.selectedVideoCache.forEach((video) => {
          if (!self.allVideos.find((v) => self.normalizeId(v.id) === self.normalizeId(video.id))) {
            self.allVideos.push(video);
          }
        });

        // Sort videos to show selected ones first
        self.allVideos.sort((a, b) => {
          const aSelected = self.selectedIds.indexOf(self.normalizeId(a.id)) > -1;
          const bSelected = self.selectedIds.indexOf(self.normalizeId(b.id)) > -1;

          if (aSelected && !bSelected) {
            return -1;
          }
          if (!aSelected && bSelected) {
            return 1;
          }
          return 0; // Keep original order for videos with same selection status
        });

        self.totalVideos = response.videos.length;

        // Calculate pagination
        const totalPages = Math.ceil(self.totalVideos / self.itemsPerPage);
        const startIndex = (self.currentPage - 1) * self.itemsPerPage;
        const endIndex = startIndex + self.itemsPerPage;
        const videosToShow = self.allVideos.slice(startIndex, endIndex);

        self.elements.html('');

        $.each(videosToShow, (key, video) => {
          str.get_strings([
            {key: 'views', component: 'mod_stream'},
            {key: 'before', component: 'mod_stream'},
          ]).then((string) => {
            var html =
                '<div class="col list-item-grid" data-itemid="' +
                video.id +
                '" id="video_identifier_' +
                video.id +
                '">' +
                '<span class="item"><div class="thumbnail">' +
                '<img src="' +
                video.thumbnail +
                '" class="img-fluid img-rounded">' +
                '<span class="datecreated">' +
                video.datecreated +
                '</span><span class="duration">' +
                video.duration +
                '</span></div><span class="title">' +
                video.title +
                '</span><span class="details">' +
                video.views +
                ' ' +
                string[0] +
                ' <span class="bubble">●</span>' +
                ' ' +
                string[1] +
                ' ' +
                video.elapsed +
                '</span></span></div>';
            self.elements.append(html);

            if (self.selectedIds.indexOf(self.normalizeId(video.id)) > -1) {
              $('#video_identifier_' + video.id).find('.item').addClass('selected');
            }

            if (key === videosToShow.length - 1) {
              self.mergeTitlesFromCache();
              self.updatePlaylistOrder();
            }

            return null;
          }).catch((error) => self.failed(error, self));
        });

        // Update pagination controls
        self.updatePagination(totalPages);
      } else {
        return str.get_string('noresults', 'mod_stream').
            then((noresults) => self.elements.html('<div class="alert alert-info">' + noresults + '</div>'));
      }
    }
    return true;
  },

  updatePagination: function(totalPages) {
    var paginationContainer = $('#stream-pagination');
    var self = this;

    if (totalPages <= 1) {
      paginationContainer.html('');
      return;
    }

    var startItem = (this.currentPage - 1) * this.itemsPerPage + 1;
    var endItem = Math.min(this.currentPage * this.itemsPerPage, this.totalVideos);

    str.get_strings([
      {key: 'paginationprevious', component: 'mod_stream'},
      {key: 'paginationnext', component: 'mod_stream'},
      {
        key: 'paginationsummary',
        component: 'mod_stream',
        param: {
          start: startItem,
          end: endItem,
          total: this.totalVideos,
        },
      },
      {key: 'paginationlabel', component: 'mod_stream'},
    ]).then((strings) => {
      var previousLabel = strings[0];
      var nextLabel = strings[1];
      var summaryLabel = strings[2];
      var ariaLabel = strings[3];
      var paginationHtml = '<nav aria-label="' + ariaLabel + '"><ul class="pagination justify-content-center">';

      if (self.currentPage > 1) {
        paginationHtml +=
            '<li class="page-item"><a class="page-link" href="#" data-page="' +
            (self.currentPage - 1) +
            '">' + previousLabel + '</a></li>';
      } else {
        paginationHtml += '<li class="page-item disabled"><span class="page-link">' + previousLabel + '</span></li>';
      }

      var startPage = Math.max(1, self.currentPage - 2);
      var endPage = Math.min(totalPages, self.currentPage + 2);

      if (startPage > 1) {
        paginationHtml += '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
        if (startPage > 2) {
          paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
      }

      for (var i = startPage; i <= endPage; i++) {
        if (i === self.currentPage) {
          paginationHtml += '<li class="page-item active"><span class="page-link">' + i + '</span></li>';
        } else {
          paginationHtml += '<li class="page-item"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>';
        }
      }

      if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
          paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        paginationHtml +=
            '<li class="page-item"><a class="page-link" href="#" data-page="' + totalPages + '">' + totalPages + '</a></li>';
      }

      if (self.currentPage < totalPages) {
        paginationHtml +=
            '<li class="page-item"><a class="page-link" href="#" data-page="' +
            (self.currentPage + 1) +
            '">' + nextLabel + '</a></li>';
      } else {
        paginationHtml += '<li class="page-item disabled"><span class="page-link">' + nextLabel + '</span></li>';
      }

      paginationHtml += '</ul></nav>';

      var infoHtml =
          '<div class="text-center mb-3"><small class="text-muted">' + summaryLabel + '</small></div>';

      paginationContainer.html(infoHtml + paginationHtml);
    }).catch(() => {
      paginationContainer.html('');
    });
  },

  renderCurrentPage: function() {
    if (this.allVideos.length === 0) {
      return;
    }

    // Re-sort videos to show selected ones first (in case selection changed)
    this.allVideos.sort((a, b) => {
      const aSelected = this.selectedIds.indexOf(this.normalizeId(a.id)) > -1;
      const bSelected = this.selectedIds.indexOf(this.normalizeId(b.id)) > -1;

      if (aSelected && !bSelected) {
        return -1;
      }
      if (!aSelected && bSelected) {
        return 1;
      }
      return 0; // Keep original order for videos with same selection status
    });

    // Calculate pagination
    const totalPages = Math.ceil(this.totalVideos / this.itemsPerPage);
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;
    const videosToShow = this.allVideos.slice(startIndex, endIndex);

    this.elements.html('');

    $.each(videosToShow, (key, video) => {
      str.get_strings([
        {key: 'views', component: 'mod_stream'},
        {key: 'before', component: 'mod_stream'},
      ]).then((string) => {
        var html =
            '<div class="col list-item-grid" data-itemid="' +
            video.id +
            '" id="video_identifier_' +
            video.id +
            '">' +
            '<span class="item"><div class="thumbnail">' +
            '<img src="' +
            video.thumbnail +
            '" class="img-fluid img-rounded">' +
            '<span class="datecreated">' +
            video.datecreated +
            '</span><span class="duration">' +
            video.duration +
            '</span></div><span class="title">' +
            video.title +
            '</span><span class="details">' +
            video.views +
            ' ' +
            string[0] +
            ' <span class="bubble">●</span>' +
            ' ' +
            string[1] +
            ' ' +
            video.elapsed +
            '</span></span></div>';
        this.elements.append(html);

        if (this.selectedIds.indexOf(this.normalizeId(video.id)) > -1) {
          $('#video_identifier_' + video.id).find('.item').addClass('selected');
        }

        if (key === videosToShow.length - 1) {
          this.mergeTitlesFromCache();
          this.updatePlaylistOrder();
        }

        return null;
      }).catch((error) => this.failed(error, this));
    });

    // Update pagination controls
    this.updatePagination(totalPages);
  },
}))
