(function() {
    if (!window.postMessage || !window.addEventListener || window.streamResizerInitialized) {
        return;
    }
    window.streamResizerInitialized = true;

    var actionHandlers = {};

    actionHandlers.hello = function(iframe, data, respond) {

        iframe.style.width = '100%';

        var resize = function(event) {
            if (iframe.contentWindow) {
                respond('resize');
            } else {
                window.removeEventListener('resize', resize);
            }
        };
        window.addEventListener('resize', resize, false);

        respond('hello');
    };


    var streamPrepareResize = false;
    actionHandlers.prepareResize = function(iframe, data, respond) {
        if (streamPrepareResize) {
            return;
        }
        streamPrepareResize = true;

        if (iframe.clientHeight !== data.scrollHeight ||
            data.scrollHeight !== data.clientHeight) {

            iframe.style.height = data.clientHeight + 'px';
            respond('resizePrepared');
        }
    };

    var streamLastResize = -1;
    actionHandlers.resize = function(iframe, data, respond) {

        if (streamLastResize == data.scrollHeight) {
            return;
        }
        streamLastResize = data.scrollHeight;

        console.log("resize");
        iframe.style.height = data.scrollHeight + 'px';
    };

    window.addEventListener('message', function receiveMessage(event) {
        if (event.data.context !== 'stream') {
            return;
        }

        var iframe,
            iframes = document.getElementsByTagName('iframe');
        for (var i = 0; i < iframes.length; i++) {
            if (iframes[i].contentWindow === event.source) {
                iframe = iframes[i];
                break;
            }
        }

        if (!iframe) {
            return;
        }

        if (actionHandlers[event.data.action]) {
            actionHandlers[event.data.action](iframe, event.data, function respond(action, data) {
                if (data === undefined) {
                    data = {};
                }
                data.action = action;
                data.context = 'stream';
                event.source.postMessage(data, event.origin);
            });
        }
    }, false);

    var iframes = document.getElementsByTagName('iframe');
    var ready = {
        context: 'stream',
        action: 'ready'
    };
    for (var i = 0; i < iframes.length; i++) {
        if (iframes[i].src.indexOf('stream') !== -1) {
            iframes[i].contentWindow.postMessage(ready, '*');
        }
    }

})();
