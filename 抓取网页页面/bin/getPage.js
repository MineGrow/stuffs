var system   = require('system');
var fs       = require('fs');
var webPage  = require('webpage');

//getPage.js [url] [isRender]
var GetPage = {
    config: {},
    resourceArr: [],
    init: function(argv){
        var url        = argv[1];
        var isRender   = parseInt(argv[2] || 0);
        if (!/^https?:\/\/.+/.test(url)) {
            console.log('error: param[url]');
            phantom.exit(1);
            return false;
        }

        this.getContent(url, isRender, function(result){
            console.log('<--PageContent-->'+JSON.stringify(result)+'<--PageContent-->');
            phantom.exit(0);
        });
    },
    getContent: function(url, isRender, callback){
        callback = callback || function(){};
        // 初始化页面窗口
        var page = webPage.create();
        page.settings.resourceTimeout = 14000;  //10秒
        page.settings.userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0';
        page.viewportSize = {width: 1440, height: 900};
        page.onConsoleMessage = function(msg){
            //console.log(msg);
            return false;
        };

        var processFn = function(callback){
            var pageHeadArr = page.content.match(/\s*(\<!DOCTYPE [^>]+\>[\w\W]*?)\<head[\w\W]+?(\<body[^>]*\>)?/i);
            var docTypeHtml = pageHeadArr && pageHeadArr[1] ? pageHeadArr[1] : '<html>';

            //page.evaluate(function(){var bg=window.getComputedStyle(document.body,null)['backgroundColor'];bg = bg || '#FFFFFF';document.body.style.backgroundColor=bg;});
            //console.log(page.content);
            //page.title = page.evaluate(function(){return document.title;});
            var returnArr = {};
            var rurl = page.evaluate(function(){
                return window.location + '';
            });
            var htmlStr = page.evaluate(function(){
                // 删除script节点
                var scriptArr = document.getElementsByTagName("script");
                if (scriptArr && scriptArr.length) {
                    for (var i=scriptArr.length-1; i>=0; i--) {
                        scriptArr[i].parentNode.removeChild(scriptArr[i]);
                    }
                }

                // 获取dom文档html
                var htmlObj = document.getElementsByTagName('html');
                htmlObj = htmlObj[0] || '';
                if (!htmlObj) {
                    htmlObj = document.getElementsByTagName('body');
                    htmlObj = htmlObj[0] || '';
                    var htmlStr = htmlObj ? htmlObj.innerHTML : '';
                    if (htmlStr) {
                        htmlStr = '<body>' + htmlStr + '</body>';
                    }
                } else {
                    var htmlStr = htmlObj.innerHTML;
                }
                return htmlStr;
            });
            if (!htmlStr) {
                callback(null);
                return false;
            }

            // 删除js
            htmlStr = htmlStr.replace(/\<script[^>]*\>[\w\W]*?\<\/script\>/gi, '');
            htmlStr = docTypeHtml + htmlStr + '</html>';

            returnArr.url     = url;
            returnArr.rurl    = rurl;
            returnArr.content = htmlStr;
            returnArr.source  = page.content;
            returnArr.resource = GetPage.resourceArr;
            if (isRender) {
                var renderStr = page.renderBase64({format:"jpg", quality:80});
                if (renderStr.length*0.75 <= 1024*75) {
                    setTimeout(function(){
                        renderStr = page.renderBase64({format:"jpg", quality:80});
                        if (renderStr.length*0.75 <= 1024*75) {
                            setTimeout(function(){
                                returnArr.renderBase64 = renderStr;
                                setTimeout(function(){callback(returnArr);}, 0);
                            }, 3000);
                        } else {
                            returnArr.renderBase64 = renderStr;
                            setTimeout(function(){callback(returnArr);}, 0);
                        }
                    }, 6000);
                } else {
                    returnArr.renderBase64 = renderStr;
                    setTimeout(function(){callback(returnArr);}, 0);
                }
            } else {
                callback(returnArr);
            }
        };
        var onLoadFinishedHanle = null;
        // 定义页面加载完成动作
        page.onLoadFinished = function(status) {
            if (status != 'success') {
                callback(null);
                return false;
            }
            if (onLoadFinishedHanle) {
                clearTimeout(onLoadFinishedHanle);
                onLoadFinishedHanle = null;
            }

            onLoadFinishedHanle = setTimeout(function(){
                processFn(callback);
            }, 3000);
        };
        // 请求监控
        page.onResourceRequested = function(req) {
            if (req && req.url && /\.flv(\?|$)/i.test(req.url||'')) {
                GetPage.resourceArr.push(req.url);
            }
        };

        // 启动页面 var url = 'http://tg.game2.cn/oifra3894.htm';
        page.open(url);
    }

};

// 脚本启动
if (system.args.length <= 1) {
    console.log('Usage: getPage.js [url] [isRender]');
    phantom.exit(1);
} else {
    GetPage.init(system.args);
}

