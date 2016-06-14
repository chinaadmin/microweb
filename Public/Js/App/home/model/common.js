define(function(require , exports ,module) {
    var $ = require('jquery');
    ;(function (global, document, undefined) {
        var
            rblock = /\{([^\}]*)\}/g,
            ds = global.ds = {
                noop: function () {
                },
                //Object
                mix: function (target, source, cover) {
                    if (typeof source !== 'object') {
                        cover = source;
                        source = target;
                        target = this;
                    }
                    for (var k in source) {
                        if (cover || target[k] === undefined) {
                            target[k] = source[k];
                        }
                    }
                    return target;
                },
                //String
                mixStr: function (sStr) {
                    var args = Array.prototype.slice.call(arguments, 1);
                    return String(sStr).replace(rblock, function (a, i) {
                        return args[i] != null ? args[i] : a;
                    });
                },
                trim: function (str) {
                    return String(str).replace(/^\s*/, '').replace(/\s*$/, '');
                },
                //Number
                getRndNum: function (max, min) {
                    min = isFinite(min) ? min : 0;
                    return Math.random() * ((isFinite(max) ? max : 1000) - min) + min;
                },
                //BOM
                scrollTo: (function () {
                    var
                        duration = 480,
                        view = $(global),
                        setTop = function (top) {
                            global.scrollTo(0, top);
                        },
                        fxEase = function (t) {
                            return (t *= 2) < 1 ? .5 * t * t : .5 * (1 - (--t) * (t - 2));
                        };
                    return function (top, callback) {
                        top = Math.max(0, ~~top);
                        var
                            tMark = new Date(),
                            currTop = view.scrollTop(),
                            height = top - currTop,
                            fx = function () {
                                var tMap = new Date() - tMark;
                                if (tMap >= duration) {
                                    setTop(top);
                                    return (callback || ds.noop).call(ds, top);
                                }
                                setTop(currTop + height * fxEase(tMap / duration));
                                setTimeout(fx, 16);
                            };
                        fx();
                    };
                })(),
                //DOM
                loadScriptCache: {},
                loadScript: function (url, callback, args) {
                    var cache = this.loadScriptCache[url];
                    if (!cache) {
                        cache = this.loadScriptCache[url] = {
                            callbacks: [],
                            url: url
                        };

                        var
                            firstScript = document.getElementsByTagName('script')[0],
                            script = document.createElement('script');
                        if (typeof args === 'object') {
                            for (var k in args) {
                                script[k] = args[k];
                            }
                        }
                        script.src = url;
                        script.onload = script.onreadystatechange =
                            script.onerror = function () {
                                if (/undefined|loaded|complete/.test(this.readyState)) {
                                    script = script.onreadystatechange =
                                        script.onload = script.onerror = null;
                                    cache.loaded = true;

                                    for (var i = 0, len = cache.callbacks.length; i < len; i++) {
                                        cache.callbacks[i].call(null, url);
                                    }
                                    cache.callbacks = [];
                                }
                            };
                        firstScript.parentNode.insertBefore(script, firstScript);
                    }

                    if (!cache.loaded) {
                        if (typeof callback === 'function') {
                            cache.callbacks.push(callback);
                        }
                    }
                    else {
                        (callback || ds.noop).call(null, url);
                    }
                    return this;
                },
                requestAnimationFrame: (function () {
                    var handler = global.requestAnimationFrame || global.webkitRequestAnimationFrame ||
                        global.mozRequestAnimationFrame || global.msRequestAnimationFrame ||
                        global.oRequestAnimationFrame || function (callback) {
                            return global.setTimeout(callback, 1000 / 60);
                        };
                    return function (callback) {
                        return handler(callback);
                    };
                })(),
                animate: (function () {
                    var
                        easeOut = function (pos) {
                            return (Math.pow((pos - 1), 3) + 1);
                        };
                    getCurrCSS = global.getComputedStyle ? function (elem, name) {
                        return global.getComputedStyle(elem, null)[name];
                    } : function (elem, name) {
                        return elem.currentStyle[name];
                    };
                    return function (elem, name, to, duration, callback, easing) {
                        var
                            from = parseFloat(getCurrCSS(elem, name)) || 0,
                            style = elem.style,
                            tMark = new Date(),
                            size = 0;

                        function fx() {
                            var elapsed = +new Date() - tMark;
                            if (elapsed >= duration) {
                                style[name] = to + 'px';
                                return (callback || ds.noop).call(elem);
                            }
                            style[name] = (from + size * easing(elapsed / duration)) + 'px';
                            ds.requestAnimationFrame(fx);
                        };
                        to = parseFloat(to) || 0;
                        duration = ~~duration || 200;
                        easing = easing || easeOut;
                        size = to - from;
                        fx();
                        return this;
                    };
                })(),
                //Cookies
                getCookie: function (name) {
                    var ret = new RegExp('(?:^|[^;])' + name + '=([^;]+)').exec(document.cookie);
                    return ret ? decodeURIComponent(ret[1]) : '';
                },
                setCookie: function (name, value, expir) {
                    var cookie = name + '=' + encodeURIComponent(value);
                    if (expir !== void 0) {
                        var now = new Date();
                        now.setDate(now.getDate() + ~~expir);
                        cookie += '; expires=' + now.toGMTString();
                    }
                    document.cookie = cookie;
                },
                //Hacker
                transitionSupport: (function () {
                    var
                        name = '',
                        prefixs = ['', 'webkit', 'moz', 'ms', 'o'],
                        docStyle = (document.documentElement || document.body).style;
                    for (var i = 0, len = prefixs.length; i < len; i++) {
                        name = prefixs[i] + (prefixs[i] !== '' ? 'Transition' : 'transition');
                        if (name in docStyle) {
                            return {
                                propName: name,
                                prefix: prefixs[i]
                            };
                        }
                    }
                    return null;
                })(),
                isIE6: !-[1,] && !global.XMLHttpRequest
            };
    	//回到顶部
    	var $backToTopEle = $('.ui-tools-bottom').click(function() {
	         $("html, body").animate({ scrollTop: 0 }, 120);
	    });
        var beWidth = $backToTopEle.width();
	    $backToTopFun = function() {
	        var st = $(document).scrollTop(), winh = $(window).height();
	        (st > 0)? $('.ui-tools-bottom').fadeIn(): $('.ui-tools-bottom').hide();    
	        //IE6下的定位
	        if (!window.XMLHttpRequest) {
	            $backToTopEle.css("top", st + winh - 166);    
	        }
	    };
	    
        var calculatePositionFn=function () {
            var contentWidth = $("#content").width();
            var bodyWidth = $("body").width();            
            if (contentWidth+beWidth>bodyWidth) {
            	 $backToTopEle.css('right', "0px");
            }else{
                var p = (bodyWidth- contentWidth)/2- (beWidth);
                $backToTopEle.css('right', p+"px");
            }
        }
        $(window).bind({
	        	'scroll':$backToTopFun,
	        	'resize':function(event) {
	                calculatePositionFn()//动态改变位置
	            }
        });
        calculatePositionFn();
        $backToTopFun();	
        
        $(".logo_index.qr img").attr('title', '');


    })(this, document);

    var main = ds;
    module.exports = main;
});