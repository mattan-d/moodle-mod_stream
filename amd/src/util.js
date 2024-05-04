window.onload = function() {
    var videoBoxWidth = 0;

    var videoBox = document.getElementById('stream-workaround');
    if (videoBox.offsetWidth) {
        videoBoxWidth = videoBox.offsetWidth;
    } else if (videoBox.clientWidth) {
        videoBoxWidth = videoBox.clientWidth;
    }

    var streamPlayer = document.getElementById('stream-player');
    if (streamPlayer) {
        var videoBoxHeight = videoBoxWidth * 3 / 4;

        streamPlayer.style.width = videoBoxWidth + "px";
        streamPlayer.style.height = videoBoxHeight + "px";
    }
};
