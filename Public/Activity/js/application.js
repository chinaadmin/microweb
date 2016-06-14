window.URL = window.URL || window.webkitURL;
var ResizeImage = (function() {
	window.URL = window.URL || window.webkitURL;
	var maxWidth = 640;
	var resizeCanvas = null;
	var File = null;
	var base64Code = "";

	function detectVerticalSquash(img, iw, ih) {
		var canvas = document.createElement('canvas');
		canvas.width = 1;
		canvas.height = ih;
		var ctx = canvas.getContext('2d');
		ctx.drawImage(img, 0, 0);
		var data = ctx.getImageData(0, 0, 1, ih).data;
		// search image edge pixel position in case it is squashed vertically.
		var sy = 0;
		var ey = ih;
		var py = ih;
		while (py > sy) {
			var alpha = data[(py - 1) * 4 + 3];
			if (alpha === 0) {
				ey = py;
			} else {
				sy = py;
			}
			py = (ey + sy) >> 1;
		}
		var ratio = (py / ih);
		return (ratio === 0) ? 1 : ratio;
	}

	function detectSubsampling(img) {
		var iw = img.naturalWidth,
			ih = img.naturalHeight;
		if (iw * ih > 1024 * 1024) { // subsampling may happen over megapixel image
			var canvas = document.createElement('canvas');
			canvas.width = canvas.height = 1;
			var ctx = canvas.getContext('2d');
			ctx.drawImage(img, -iw + 1, 0);
			// subsampled image becomes half smaller in rendering size.
			// check alpha channel value to confirm image is covering edge pixel or not.
			// if alpha value is 0 image is not covering, hence subsampled.
			return ctx.getImageData(0, 0, 1, 1).data[3] === 0;
		} else {
			return false;
		}
	}


	function renderImageToCanvas(img, canvas, options, doSquash) {
		var iw = img.naturalWidth,
			ih = img.naturalHeight;
		if (!(iw + ih)) return;
		var width = options.width,
			height = options.height;
		var ctx = canvas.getContext('2d');
		ctx.save();

		var subsampled = detectSubsampling(img);
		if (subsampled) {
			iw /= 2;
			ih /= 2;
		}
		var d = 1024; // size of tiling canvas
		var tmpCanvas = document.createElement('canvas');
		tmpCanvas.width = tmpCanvas.height = d;
		var tmpCtx = tmpCanvas.getContext('2d');
		var vertSquashRatio = doSquash ? detectVerticalSquash(img, iw, ih) : 1;
		var dw = Math.ceil(d * width / iw);
		var dh = Math.ceil(d * height / ih / vertSquashRatio);
		var sy = 0;
		var dy = 0;
		while (sy < ih) {
			var sx = 0;
			var dx = 0;
			while (sx < iw) {
				tmpCtx.clearRect(0, 0, d, d);
				tmpCtx.drawImage(img, -sx, -sy);
				ctx.drawImage(tmpCanvas, 0, 0, d, d, dx, dy, dw, dh);
				sx += d;
				dx += dw;
			}
			sy += d;
			dy += dh;
		}
		ctx.restore();
		tmpCanvas = tmpCtx = null;
	}

	function canvasResize(img, orientation, pWidth, pHeight, previewElement) {
		var canvas = document.createElement('canvas');
		var height = (img.height * maxWidth) / img.width;
		if (pWidth && pHeight) {
			height = (pHeight * maxWidth) / pWidth;
		};
		// resize the canvas and draw the image data into it		
		var context = canvas.getContext("2d");
		canvas.width = maxWidth;
		canvas.height = height;
		switch (orientation) {
			case 2:
				// horizontal flip
				context.translate(canvas.width, 0);
				context.scale(-1, 1);
				break;
			case 3:
				// 180° rotate left
				context.translate(canvas.width, canvas.height);
				context.rotate(Math.PI);
				break;
			case 4:
				// vertical flip
				context.translate(0, canvas.height);
				context.scale(1, -1);
				break;
			case 5:
				// vertical flip + 90 rotate right
				canvas.width = height;
				canvas.height = maxWidth;
				context.rotate(0.5 * Math.PI);
				context.scale(1, -1);
				break;
			case 6:
				// 90° rotate right

				canvas.width = height;
				canvas.height = maxWidth;


				context.rotate(0.5 * Math.PI);
				context.translate(0, -canvas.width);

				break;
			case 7:
				// horizontal flip + 90 rotate right
				canvas.width = height;
				canvas.height = maxWidth;
				context.rotate(0.5 * Math.PI);
				context.translate(canvas.width, -canvas.height);
				context.scale(1, 1);
				break;
			case 8:
				// 90° rotate left
				canvas.width = height;
				canvas.height = maxWidth;

				context.rotate(-0.5 * Math.PI);
				context.translate(-canvas.height, 0);

				break;
		}
		renderImageToCanvas(img, canvas, {
			width: maxWidth,
			height: height
		}, true);
		window.URL.revokeObjectURL(img.src)
		previewElement.innerHTML = "";
		previewElement.appendChild(canvas); // do the actual resized preview
		resizeCanvas = canvas;
		var imageData=  canvas.toDataURL("image/jpeg", 0.8);
		if (/image\/png/.test(imageData)&&window.JPEGEncoder) {
			try{
			var encoder = new JPEGEncoder();
				imageData = encoder.encode(context.getImageData(0,0,canvas.width,canvas.height), 80);
			}catch(e){
			}
		};
		return imageData;// get the data from canvas as 70% JPG (can be also PNG, etc.)

	}
	return {
		resize: function(file, previewElement, callback) {
			var that = this;

			var blobURL = window.URL.createObjectURL(file); // and get it's URL
			var image = new Image();
			image.src = blobURL;
			image.onload = function() {
				if (image.complete) {

					EXIF.getData(image, function() {
						var orientation = EXIF.getTag(this, 'Orientation');
						var width = EXIF.getTag(this, 'PixelXDimension');
						var height = EXIF.getTag(this, 'PixelYDimension');
						var bs64 = canvasResize(image, orientation, width, height, previewElement);
							base64Code = bs64;
						callback && callback(bs64, that.image());
					});
				};
			};
			previewElement.innerHTML="";
			previewElement.appendChild(image);
			
			File = file;
		},
		canvas: function() {
			return resizeCanvas;
		},
		image: function() {
			if (!base64Code) {
				return false
			};
			var img = new Image();
			img.src = base64Code
			return img;
		},
		file: function() {
			return File;
		}
	}
})();






var camera = function () {

		var vElement = function(dom) {
			var el = dom;
			return {
				on: function(event, fn, index,size) {
					var that = this;
					el&&el.addEventListener(event, function(e) {
						fn && typeof(fn) == "function" && fn.apply(that, [e,index,size])
					})
					return this;
				},
				remove: function() {
					el&&el.parentNode.removeChild(el);
				},
				removeClass: function(sclassName) {
					if (!el) {return this};
					var c = el.className.replace(sclassName, '');
					el.className = c;
					return this;
				},
				hasClass: function(className) {
					if (!el) {return false};
					return new RegExp(className).test(el.className);
				},
				addClass: function(class_name) {
					if (!el) {return this};
					this.removeClass(class_name);				
					el.className += " " + class_name;
					return this;
				},
				html: function(HTML) {				
					if (HTML && el) {
						if (/html\w+Element/ig.test(Object.prototype.toString.apply(HTML,[]))) {
							el.innerHTML = "";
							el.appendChild(HTML)
						}else{
							el.innerHTML = HTML.toString();
						}					
						return this;
					} else {
						return el?el.innerHTML:"";
					}
				},
				value: function(v) {
					if (el&&(el.tagName == "INPUT" || el.tagName == "TEXTAREA")) {
						if (v != undefined) {
							el.value = v.toString();
						} else {
							return el.value;
						}
					};
				},
				find: function(selectors) {
					return el?vElement(el.querySelector(selectors)):null;
				},
				parent: function() {
					return el?vElement(el.parentNode):null;
				},
				get: function() {
					return el;
				},
				width:function (w) {
					var width = 0;
					if (el) {
						if (!isNaN(w)) {
							el.style.width =w+"px";
							return this;
						}else{
							width = el.getBoundingClientRect().width
						}					
					};
					return width;
				},
				height:function (h) {
					var height = 0;
					if (el) {
						if (!isNaN(w)) {
							el.style.height = h + "px";
							return this;
						} else {
							height = el.getBoundingClientRect().height
						}
					};
					return height;
				}
			}
		}

		var vElements = function (doms) {
			var vEls = [];
			for (var i = 0; i < doms.length; i++) {
				vEls.push(vElement(doms[i]));
			};
			var len = vEls.length;
			return{
				on:function (type,fn) {
					for (var i = 0; i < len; i++) {
						vEls[i].on.apply(vEls[i],[type,fn,i,len]);
					};
				},
				eq:function (index) {
					return vEls[index];
				}
			}
		}


		var $ = function (selectors) {
			return vElement(document.querySelector(selectors));
		}
		var $$ = function (selectors) {
			return vElements(document.querySelectorAll(selectors))
		}
		var isCapture = false;
		var bgi = $(".imageView .cameraBgi");//背景图
		var imageWidth = bgi.width();
		var preview = $(".preview");//预览框
		var captureBtn = $(".captureBtn");
		var baseInput = $("#base64");
		var vCamera = $("#camera");
			vCamera.on("change",function (e) {
				if (window.URL) {
					window.setTimeout(function() {
						ResizeImage.resize(e.target.files[0], preview.get(), function(bs64Data, image) {
							baseInput.value(bs64Data);
							image.width = imageWidth;
							preview.get().appendChild(image)
						});
					}, 36)						
					if (!isCapture) {
						preview.removeClass("hide");
						bgi.addClass("hide");
						isCapture = true;
					};
					
				};
			})
		



		var onepix =  $("#onepix");
		if (onepix.get()) {
			var style = document.createElement("style");
			var height = onepix.width();//*4/3;
			style.innerHTML = ".view .listBox .item img.u{height:"+height+"px;}";
			onepix.parent().get().appendChild(style);
		};



		var loginBox = $(".loginBox.onebox");
		var proxyFun = null;
		var isetInterval = null;
		var time = 60;
		if (loginBox.get()) {
			var mobile = loginBox.find(".phone");
			var loginBoxBtn = loginBox.find('a.btn');
			var ifc = false
			loginBoxBtn.on("click",function () {
				if (ifc) {
					return false
				};
				ifc = true;
				time = 60;
				loginBoxBtn.addClass("grey");
				proxyFun&&proxyFun($(".loginBox.onebox .phone").value());	
				$("p.msg").removeClass("hide");			
				isetInterval= setInterval(function() {
					time--;
					if (0 >= time) {
						ifc = false;
						loginBoxBtn.html("获取验证码");
						loginBoxBtn.removeClass("grey");
						window.clearInterval(isetInterval);
					} else {
						loginBoxBtn.html("还剩余" + time + "秒");
					}
				}, 1000)

			})


		};



		var swipe = function(class_name,b) {
			if (b) {
				preview.removeClass(class_name);
				bgi.addClass(class_name);
			}else{
				preview.addClass(class_name);
				bgi.removeClass(class_name);
			}
		}

		return {
			send:function ($http,url,uid,title,callback) {	

				var fd = new FormData();
				fd.append("base",baseInput.value())
				fd.append("title",title||"");
				fd.append("uid",uid||"");
				$http.post(url, fd, {
						transformRequest: angular.identity,
						headers: {
							'Content-Type': undefined
						}
					})
					.success(function(data) {						
						callback&&callback(true,data)
					})
					.error(function(e) {
						callback&&callback(false)
					});					
			},
			capture:function (b) {
				swipe("hide",b);
			},
			preview:function (imgSrc) {
				if (/^http.+/i) {
					var  image = new Image();
					image.src = imgSrc;
					image.width = imageWidth;
					image.onerror=function(){
						if (image.src != defaultImage) {
							image.src = defaultImage;
						};
					}
					preview.html(image);
					swipe("hide",true);
					$(".captureBtn span").get().style.backgroundImage= "url("+plusChangeImage+")";
					$("#upload .logo span").html("更换照片")
				};
			},
			mobileBoxValue:function () {
				return{
					"mobile":$(".loginBox.onebox .phone").value(),
					"code":$(".loginBox.onebox .code").value()
				}
			},
			mobileBoxCallback:function (callback) {
				if (callback&&typeof(callback)=="function") {
					proxyFun = callback;
				};
			},
			stopMobileBox:function(){
				time=0;
			},
			regBackButton:function (callback) {
				$(".pc-back").on("click",function () {
					callback&&callback()
				})
			}
		}
		
	};







//-------------------------------------------------------
var app = angular.module('tryEat', ["ui.router","tryEat.controllers","tryEat.services"])
	.run(function () {		
		window.previewCamera = camera();
	})

var host = "http://"+location.host+"/";//jtshop  t-bihaohuo
var mockHost = "http://192.168.1.189:8680/";// http://act.t-bihaohuo.cn/
var appURLRES = {
	"sendsms":host+"Connect/sendSMS",
	"adduser":host+"Connect/addUser",
	"uploadImage":host+"Photos/addUpload",
	"photoShow":host+"Photos/photoShow",
	"photoinfo":host+"Photos/info",
	"myPhtoInfo":host+"Photos/myPhtoInfo",
	"images":host+"Photos/photoList",
	"vote": host+"Vote/vote"
};

var staticPages={
	"login":host+"Photos/login.html",
	"list":host+"Photos/lists.html",
	"upload":host+"Photos/upload.html",
	"detail":host+"Photos/detail.html"
}	
