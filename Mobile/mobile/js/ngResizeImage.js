// need exif.js

angular.module('ngResizeImage', [])

.directive('ngResize', function(ngResizeImage,userS,storage) {
  storage.init();
  return {
    link: function(scope, element, attrs) {
    	if (element[0].tagName=="INPUT"&&element[0].type=="file" && scope.hasOwnProperty(attrs.ngResize)) {
    		element.bind('change', function(event) {					
				ngResizeImage.resize(event.target.files[0], event.target, function(src) {					
					scope[attrs.ngResize] = src;
					scope.$applyAsync();
				})					    			
    		});
    	};	      
    }
  }
})
.factory('ngResizeImage', function(){
	var document = window.document;
	var maxWidth = 640;
	var EXIF = window.EXIF||null;


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
		canvas = null;
		ctx = null;
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

	function canvasResize(img, orientation, pWidth, pHeight, box) {
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

		box.appendChild(canvas); // do the actual resized preview
		var imageData=  canvas.toDataURL("image/jpeg", 0.8);
		if (/image\/png/.test(imageData)&&window.JPEGEncoder) {
			try{
			var encoder = new JPEGEncoder();//JPEG编码器 //解决安卓无法转为jpg的问题，android无法转为JPEG，只提供png支持
				imageData = encoder.encode(context.getImageData(0,0,canvas.width,canvas.height), 80);
			}catch(e){
			}
		};
		return imageData;// get the data from canvas as 70% JPG (can be also PNG, etc.)

	}

	function tmpbox(file,element,callback) {
		var blobURL = window.URL.createObjectURL(file); // and get it's URL
		var parent = element.parentNode;
		var image = new Image();
		var box = document.createElement("div");
			box.style.cssText = "width:0;height:0;overflow:hidden;position:absolute;";		
		image.src = blobURL;
		image.onload = function() {
			if (image.complete) {
				EXIF&&EXIF.getData(image, function() {
					var orientation = EXIF.getTag(this, 'Orientation');
					var width = EXIF.getTag(this, 'PixelXDimension');
					var height = EXIF.getTag(this, 'PixelYDimension');
					var base64Code = canvasResize(image, orientation, width, height, box);					
						window.URL.revokeObjectURL(image.src); //吊销blobURL
						box.removeChild(image);//将image从box的dom中移除
						image = null;
						callback && callback(base64Code);
				});
			};
		};
		box.appendChild(image);
		parent.appendChild(box);
	}
	return {
			resize:function (file,element,callback) {
				tmpbox(file,element,callback);
			}
	}
})