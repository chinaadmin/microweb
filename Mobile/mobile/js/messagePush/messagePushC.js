angular.module('app.controllers', [])
    .directive('errSrc', function () {
        return {
            link: function (scope, element, attrs) {
                element.bind('error', function () {
                    if (attrs.src != attrs.errSrc) {
                        attrs.$set('src', attrs.errSrc);
                    }
                });
                if (attrs.$attr['ngSrc'] && !attrs.ngSrc) {
                    attrs.$set('src', attrs.errSrc);
                }
                ;

            }
        }
    })

    .directive('ngImagescale', function () {
        return {
            link: function (scope, element, attrs) {
                if (element[0].tagName == "IMG") {
                    element.bind('load', function () {
                        if (attrs.ngImagescale && (attrs.ngImagescale.split(":")).length == 2) {
                            var size = attrs.ngImagescale.split(":");
                            var w = parseInt(size[0]) || 0,
                                h = parseInt(size[1]) || 0;
                            if (w && h) {
                                element[0].height = element[0].width * h / w;
                            } else {
                                element[0].height = element[0].width;
                            }
                        } else {
                            element[0].height = element[0].width;
                        }
                    });
                }
                ;
            }
        }
    })
    .directive('ngBack', function (storage) {
        return {
            link: function (scope, element, attrs) {
                storage.init();
                element.bind('click', function () {
                    window.history.go(-1);
                });
            }
        }
    })

    .directive('ngEnter', function () {
        return function (scope, element, attrs) {
            element.bind("keydown keypress", function (event) {
                if (event.which === 13) {
                    scope.$apply(function () {
                        scope.$eval(attrs.ngEnter);
                    });
                    event.preventDefault();
                }
            });
        };
    })

    .controller("indexC", function ($rootScope, $element, $scope, $state, messagepushS, storage, ngMessage, ngWechat) {
        storage.init();
        var token = storage.get('token');
        $scope.Title = new Array();
        $scope.Title[0] = '活动促销';
        $scope.Title[1] = '新品上架';
        $scope.Title[2] = '通知消息';
        $scope.Title[3] = '物流助手';
        $scope.Title[4] = '交易信息';
        $scope.Title[5] = '我的专员';


        //name_id     0:只查消息，不返回消息数据    1：促销   2：新品
        if (!token) {
            storage.toPage('login')
        } else {
            messagepushS.getMsg({
                "token": token,
                "name_id": 0
            }, function (res) {
                $scope.count = res.data;
            })
        }

      $scope.ban=function(){
      	   ngMessage.showTip('暂无任何数据');
      }


    })
    .controller('getInfoC', function ($scope, messagepushS, storage, ngMessage, ngWechat) {
        storage.init();
        var token = storage.get('token');
        var rec_id = storage.getUrlParam('rec_id');
        if (!token) {
            storage.toPage('login')
        }
        messagepushS.getInfo({
            "token": token,
            "rec_id": rec_id
        }, function (res) {
            $scope.data = res;
        })
    })
    .directive('logistics', function (ngMessage, $rootScope) {
        var TEMP = ""
            + "<div class=\"list\" ng-repeat=\"(key,value) in data\" >"
            + "<p class=\"text-center\" ng-bind=\"value.time\"></p>"
            + "<a class=\"list-group-item\" href=\"check.html?rec_id={{value.rec_id}}\">"
            + "<div class=\"media\" ng-class=\"{2:'read'}[value.state]\">"
            + "<div class=\"media-top\">该订单<span class=\"orderst\" ng-class=\"{2:'read'}[value.state]\">{{value.logistics_state}}</span>您购买的[{{value.name}}]</div>"
            + "<div class=\"media-left\">"
            + "<img class=\"media-object\" ng-src=\"{{value.pic}}\" />"
            + "</div>"
            + "<div class=\"media-body\">"
            + "<p>您购买的[{{value.name}}]订单已经签收!</p>"
            + "</div>"
            + "<div class=\"media-footer\">"
            + "<p>查看详情<span class=\"right\">></span></p>"
            + "</div>"
            + "</div>"
            + "</a>"
            + "</div>"
        return {
            restrict: 'E',
            template: TEMP,
            controller: function ($scope, messagepushS, storage, $element, $rootScope) {
                storage.init();
                $scope.Title = new Array();
                $scope.Title[4] = '物流助手';

                var token = storage.get('token');

                var name_id = storage.getUrlParam('name_id');
                $scope.listStatus = false;
                $scope.emptyStatus = false;

                var counter = 0;
                // 每页展示4个
                var num = 5;
                var pageStart = 0, pageEnd = 10;
                $scope.data = [];
                $('.myListBg').dropload({
                    scrollArea: window,
                    domDown: {
                        domClass: 'dropload-down',
                        domRefresh: '<div class="dropload-refresh">↑上拉加载更多</div>',
                        domLoad: '<div class="dropload-load"><span class="loading"></span>加载中...</div>',
                        domNoData: '<div class="dropload-noData">已经没有数据</div>'
                    },

                    loadDownFn: function (me) {
                        if (name_id) {
                            $scope.listStatus = true;
                            messagepushS.getMsg({
                                "token": token,
                                "name_id": name_id
                            }, function (res) {
                                counter++
                                $scope.name_id = name_id;
                                maxLen = res.data.length
                                
                                
                                if(!res.data){
							    	 	 $scope.emptyStatus = true;
							    	 	 $('.dropload-down').html('')
							    	 	 return false;
								}
                                
                                
                                pageEnd = num * counter;
                                pageStart = pageEnd - num;


                                if (pageEnd > maxLen) {
                                    pageEnd = maxLen
                                }
                                for (var i = pageStart; i < pageEnd; i++) {
                                    $scope.data.push(res.data[i])
                                    if ((i + 1) >= res.data.length) {
                                        // 无数据
                                        me.noData();
                                        break;
                                    }
                                }
                                setTimeout(function () {
                                    // 每次数据加载完，必须重置
                                    me.resetload();
                                }, 1000);
                            })
                        } else {
                            $scope.emptyStatus = true
                        }
                    },
                    threshold: 50
                });

            }
        }
    })