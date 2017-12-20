var fs       = require('fs');
var path     = require('path');
var qs       = require('querystring');
var images   = require('images');
var webdriver = require('selenium-webdriver'),
    chrome    = require('selenium-webdriver/chrome'),
    firefox   = require('selenium-webdriver/firefox'),
    proxy     = require('selenium-webdriver/proxy'),
    By        = webdriver.By,
    until     = webdriver.until;

// 拷贝浏览器驱动
var driverExePath = 'c:/Windows/chromedriver.exe'
if (!fs.existsSync(driverExePath)) {
    var source = fs.readFileSync(path.join(__dirname, 'driver', 'chromedriver.exe'));
    fs.writeFileSync(driverExePath, source);
}
var driverExePath = 'c:/Windows/geckodriver.exe'
if (!fs.existsSync(driverExePath)) {
    var source = fs.readFileSync(path.join(__dirname, 'driver', 'geckodriver.exe'));
    fs.writeFileSync(driverExePath, source);
}
var driverExePath = 'c:/Windows/IEDriverServer.exe'
if (!fs.existsSync(driverExePath)) {
    var source = fs.readFileSync(path.join(__dirname, 'driver', 'IEDriverServer.exe'));
    fs.writeFileSync(driverExePath, source);
}

// 异常处理
process.on('uncaughtException', function(err) {
    console.log(err);
    if (GetPage && GetPage.driver) {
        GetPage.driver.quit();
    }
});


//getPage.js [url] [isRender]
var GetPage = {
    config: {},
    driver: null,
    init: function(argv){
        var url        = argv[1];
        var isRender   = parseInt(argv[2] || 0);
        if (!/^https?:\/\/.+/.test(url)) {
            console.log('error: param[url]');
            process.exit(1);
            return false;
        }

        this.getContent(url, isRender, function(result){
            console.log('<--PageContent-->'+JSON.stringify(result)+'<--PageContent-->');
            if (GetPage.driver) {
                GetPage.driver.quit().then(function(){
                    process.exit(0);
                });
                setTimeout(function(){process.exit(0);}, 5000);
            } else {
                process.exit(0);
            }
        });
    },
    getContent: function(url, isRender, callback){
        callback = callback || function(){};

        // 打开浏览器处理
        // ie
        //var driver = new webdriver.Builder().withCapabilities(webdriver.Capabilities.ie()).build();

        // 火狐
        //var options = new firefox.Options().setBinary('C:\\Program Files (x86)\\Mozilla Firefox\\firefox.exe');
        //var driver = new webdriver.Builder().withCapabilities(webdriver.Capabilities.firefox()).setFirefoxOptions(options).build();

        // 谷歌
        var driver = new webdriver.Builder().withCapabilities(webdriver.Capabilities.chrome()).build();
        //.setProxy(proxy.manual({http:'131.72.69.89:8082', https:'131.72.69.89:8082'}))

        // 储存用于结束时清理
        GetPage.driver = driver;

        // 浏览器最大化
        driver.manage().window().maximize();
        //driver.manage().window().setSize(1440, 900);

        var processFn = function(callback){

            var returnArr = {};

            // 获取源代码
            driver.getPageSource().then(function(source){
                var pageHeadArr = source.match(/\s*(\<!DOCTYPE [^>]+\>[\w\W]*?)\<head[\w\W]+?(\<body[^>]*\>)?/i);
                var docTypeHtml = pageHeadArr && pageHeadArr[1] ? pageHeadArr[1] : '<html>';

                // 获取当前页面url
                driver.executeScript(function(){
                    return window.location + '';
                }).then(function(rurl){
                    // 获取不包含script源代码
                    driver.executeScript(function(){
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
                    }).then(function(htmlStr){
                        if (!htmlStr) {
                            callback(null);
                            return false;
                        }

                        // 删除js
                        htmlStr = htmlStr.replace(/\<script[^>]*\>[\w\W]*?\<\/script\>/gi, '');
                        htmlStr = docTypeHtml + htmlStr + '</html>';

                        // 返回值处理
                        returnArr.url     = url;
                        returnArr.rurl    = rurl;
                        returnArr.content = htmlStr;
                        returnArr.source  = source;
                        if (isRender) {
                            setTimeout(function(){
                                driver.takeScreenshot().then(function(renderStr){
                                    // 转换图片格式为jpg
                                    var renderStrBuff = images(new Buffer(renderStr, 'base64')).encode('jpg', {operation:80});
                                    // 图片可能有问题，需要重新保存
                                    //console.log(renderStrBuff.length);
                                    //process.exit(1);
                                    if (renderStrBuff.length <= 1024*75) {
                                        setTimeout(function(){
                                            driver.takeScreenshot().then(function(renderStr){
                                                renderStr = images(new Buffer(renderStr, 'base64')).encode('jpg', {operation:80}).toString('base64');
                                                returnArr.renderBase64 = renderStr;
                                                callback(returnArr);
                                            });
                                        }, 6000);
                                    } else {
                                        var renderStr = renderStrBuff.toString('base64');
                                        returnArr.renderBase64 = renderStr;
                                        callback(returnArr);
                                    }
                                });
                            }, 3000);
                        } else {
                            callback(returnArr);
                        }

                    });
                });

            });

        };

        // 初始化页面窗口
        driver.get(url);
        driver.wait(function(){
            return driver.getTitle().then(function(title){
                setTimeout(function(){
                    processFn(callback);
                }, 2000);
                return true;
            });
        }, 5000);

    }

};

// 脚本启动
if (process.argv.length <= 2) {
    console.log('Usage: getPage.selenium.js [url] [isRender]');
    process.exit(1);
} else {
    var argv = process.argv;
    argv.shift();
    GetPage.init(argv);
}

