angular.module('app.wechat', ['app.services'])
.factory('ngWechat', function($rootScope,httpRequest) {
	var share = {};
	var wechat = null;
	var wxJs = null
	return {
		enabled:false,
		init: function(config,callback) {
			var self = this;
			httpRequest.post(interfaceURL.activitys.activityHelpCode,{token:$rootScope.shareToken}).success(function (data) {
				$rootScope.shareCode = data.code;				
			}).error(function(e) {
//				console.log(e);
			});
			if (window.wx) {
				config.jsApiList=[
				"scanQRCode",
				"onMenuShareTimeline",
				"onMenuShareAppMessage",
				"onMenuShareQQ",
				"onMenuShareWeibo",
				"onMenuShareQZone",
				"previewImage",
				"checkJsApi",
		        'hideMenuItems',
		        'showMenuItems',
		        'hideAllNonBaseMenuItem',
		        'showAllNonBaseMenuItem',
		        'translateVoice',
		        'startRecord',
		        'stopRecord',
		        'onRecordEnd',
		        'playVoice',
		        'pauseVoice',
		        'stopVoice',
		        'uploadVoice',
		        'downloadVoice',
		        'chooseImage',
		        'previewImage',
		        'uploadImage',
		        'downloadImage',
		        'getNetworkType',
		        'openLocation',
		        'getLocation',
		        'hideOptionMenu',
		        'showOptionMenu',
		        'closeWindow',
		        'scanQRCode',
		        'chooseWXPay',
		        'openProductSpecificView',
		        'addCard',
		        'chooseCard',
		        'openCard',
				"getLocation",
				"uploadImage"
				]
				window.wx.config(config);
				window.wx.ready(function() {
					wechat = window.wx;
					self.enabled = true;
					callback&&callback({
						success:true,
						msg:"window.wx.ready"
					});
//					token = $rootScope.shareToken;
					share.title ="必好货商城";
					share.desc ="必好货商城-提供专属服务的O2O健康超市，您的生活尊享！主营土特产、营养保健品、休闲食品及生活必需品等，是一家通过社区O2O模式为用户创造完美体验与服务的连锁经营机构";
					share.link = window.location.host;
//					share.link = window.location.host+"/html/activitys/addmi.html?code="+$rootScope.shareCode;
					share.imgUrl = "http://admin.tp-bihaohuo.cn/Public/Image/activity/logoShare.png";
//					if(window.location.toString().indexOf("anniversary")>=0){
//						share.title ="同心十周年";
//						share.desc ="活动描述";
//						share.link = window.location.host+"/html/anniversary/index.html";
//						share.imgUrl = window.location.host+"/images/wecharActivily/denglong.png";
//					}
					//或用setShare方法 setshare({"title":"采花"})
					
//					if(islogin){
						
//					}else{
//						share.link = location.href;
//					}
					
					
					//第二种用法ngWechat.ShareTimeline()等方法
					wechat.onMenuShareTimeline({
						title: share.title,
						link:share.link,
						imgUrl:share.imgUrl,
						success: function(e) {
							success && success(e);
						},
						cancel: function(err) {
							fail&&fail(err)
						}
					});
					wechat.onMenuShareQQ({
						title: share.title,
						link:share.link,
						imgUrl:share.imgUrl,
						success: function(e) {
							success && success(e);
						},
						cancel: function(err) {
							fail&&fail(err)
						}
					});
					wechat.onMenuShareAppMessage({
						title: share.title,
						desc: share.desc, // 分享描述
						type: 'link', // 分享类型,music、video或link，不填默认为link
						link:share.link,
						imgUrl: share.imgUrl,
						dataUrl: '', 
						success: function(e) {
							success && success(e);
						},
						cancel: function(err) {
							fail&&fail(err)
						}
					});
					wx.onMenuShareWeibo({
					    title: share.title,
						desc: share.desc, // 分享描述
						imgUrl: share.imgUrl,
						link:share.link,
					    success: function (e) { 
					    	success && success(e);
					       // 用户确认分享后执行的回调函数
					    },
					    cancel: function (err) { 
					        // 用户取消分享后执行的回调函数
					        fail&&fail(err)
					    }
					});
					wx.onMenuShareQZone({
					    title: share.title,
						desc: share.desc, // 分享描述
						imgUrl: share.imgUrl,
						link:share.link,
					    success: function (e) { 
					    	success && success(e);
					       // 用户确认分享后执行的回调函数
					    },
					    cancel: function (err) { 
					        fail&&fail(err)
					        // 用户取消分享后执行的回调函数
					    }
					});
				})
				window.wx.error(function(res) {					
					callback&&callback({
						success:false,
						msg:"window.wx.error"
					})
				})
			};
			if (window.WeixinJSBridge) {
				wxJs = window.WeixinJSBridge;
				window.document.addEventListener('WeixinJSBridgeReady', function(){
					

				}, false);				
			};		
		},
		preview: function(urls) {
			var res=[];
			if (angular.isString(urls)) {
				res =[urls]
			};
			if (angular.isArray(urls)) {
				res=urls;
			};
			//图片预览
			res.length && wechat && wechat.previewImage({
				current: res[0], 
				urls: res
			});
		},
		pay: function(config, callback,fail) {
			//微信支付	wait-
			if (wechat) {
				wechat.chooseWXPay({
					timestamp: config.timeStamp, // 支付签名时间戳，注意微信jssdk中的所有使用timestamp字段均为小写。但最新版的支付后台生成签名使用的timeStamp字段名需大写其中的S字符
					nonceStr: config.nonceStr, // 支付签名随机串，不长于 32 位
					package: config.package, // 统一支付接口返回的prepay_id参数值，提交格式如:prepay_id=***）
					signType: config.signType, // 签名方式，默认为'SHA1'，使用新版支付需传入'MD5'
					paySign: config.paySign, // 支付签名
					success: function(res) {
						// 支付成功后的回调函数
						callback && callback({
							success: true,
							msg: res.err_msg
						})
					},
					fail: function(res) {
						fail && fail({
							success: false,
							msg: res.err_msg
						})
					},
					cancel: function(res) {
						fail && fail({
							success: false,
							msg: res.err_msg
						})
					}

				});
			} else if (window.WeixinJSBridge) {
				window.WeixinJSBridge.invoke(
					'getBrandWCPayRequest', config,
					function(res) {
						if (res.err_msg == "get_brand_wcpay_request:ok") {
							// 使用以上方式判断前端返回,微信团队郑重提示:res.err_msg将在用户支付成功后返回    ok，但并不保证它绝对可靠。 
							callback && callback({
								success: true,
								msg: res
							})
						} else {
							callback && callback({
								success: false,
								msg: res.err_msg
							});
							fail && fail({
								success: false,
								msg: res.err_msg
							})
						}
					}
				);
			} else {
				callback && callback({
					success: false,
					msg: config
				})
			};


		},
		scan: function(callback) {
			// 二维码扫描
			wechat && wechat.scanQRCode({
				needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
				scanType: ["qrCode", "barCode"], // 可以指定扫二维码还是一维码，默认二者都有
				success: function(res) {
					callback&&callback(res.resultStr)
				}
			});
		},
		maps: function(lat,lon,addressName,url) {
			// 地图
			wechat && wechat.openLocation({
				latitude: lat||0, // 纬度，浮点数，范围为90 ~ -90
				longitude: lon||0, // 经度，浮点数，范围为180 ~ -180。
				name: addressName||'', // 位置名
				address: '', // 地址详情说明
				//scale: 16, // 地图缩放级别,整形值,范围从1~28。默认为最大
				infoUrl: url||window.location.href // 在查看位置界面底部显示的超链接,可点击跳转
			});
		},
		gps:function () {
			// body...
		},
		address:function (callback) {
			!wxJs&&callback&&callback();
			wxJs && wxJs.invoke('editAddress', {
					"appId": getAppId(),
					"scope": "jsapi_address",
					"signType": "sha1",
					"addrSign": "xxxxx",
					"timeStamp": "12345",
					"nonceStr": "10000"
				}, function(res) {
					//若res 中所带的返回值不为空，则表示用户选择该返回值作为收货地址。
					//否则若返回空，则表示用户取消了这一次编辑收货地址。
					var address = null
					if (res) {
						address={};
						address.provice.value = res.proviceFirstStageName;
						address.city.value = res.addressCitySecondStageName;
						address.county.value = res.addressCountiesThirdStageName;
						address.detail.value = res.addressDetailInfo;
						address.phone.value = res.telNumber;
					}
					callback&&callback(address)
				});
		
		},
		setShare: function(config) {
			share.title  = config.title||"";
			share.desc   = config.desc||"";
			share.link   = config.link||"";
			share.imgUrl = config.imgUrl||"";
		},		
		shareTimeline: function(success,fail) {
			// 分享到朋友圈
			if (wechat) {

			wechat.onMenuShareTimeline({
				title: share.title,
				link: share.link,
				imgUrl: share.imgUrl,
				success: function(e) {
					alert(1);
					success && success(e);
				},
				cancel: function(err) {
					fail&&fail(err)
				}
			});
			}else{
				fail&&fail({
					"msg":"wechat isnt has"
				})
			}
		},
		shareMessage: function() {
			// 分享给朋友
			
			wechat && wx.onMenuShareAppMessage({
				title: share.title,
				desc: share.desc,
				link: share.link,
				imgUrl: share.imgUrl,
				type: 'link', // 分享类型,music、video或link，不填默认为link
				dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
				success: function() {
				},
				cancel: function() {
				}
			});
			
		},
		shareqq: function() {
			// 分享到QQ
			wechat && wechat.onMenuShareQQ({
				title: share.title,
				desc: share.desc,
				link: share.link,
				imgUrl: share.imgUrl,
				success: function() {

				},
				cancel: function() {

				}
			});
		},
		shareweibo: function() {
			// 分享到微博
			wechat && wechat.onMenuShareWeibo({
				title: share.title,
				desc: share.desc,
				link: share.link,
				imgUrl: share.imgUrl,
				success: function() {

				},
				cancel: function() {

				}
			});
		},
		shareqzone: function() {
			//分享到QQ空间
			wechat && wechat.onMenuShareQZone({
				title: share.title,
				desc: share.desc,
				link: share.link,
				imgUrl: share.imgUrl,
				success: function() {

				},
				cancel: function() {

				}
			});
		}
		
	}
})