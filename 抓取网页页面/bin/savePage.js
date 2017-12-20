var fs       = require('fs');
var path     = require('path');
var crypto   = require('crypto');
var http     = require('http');
var https    = require('https');
var url      = require('url');
var child_process = require('child_process');

// 程序路径
var runDir = path.dirname(process.argv[1]);

var nodeJsFile = path.join(runDir, 'node.exe');

// slimerjs程序路径
var slimerjsFile = path.join(runDir, 'slimerjs-0.10.2', 'slimerjs.bat');
if (!fs.existsSync(slimerjsFile)) {
    process.stdout.write('error: slimerjs-0.10.2\\slimerjs.bat not found.\n');
    process.exit(1);
}

// getPage.js脚本文件
var getPageJsFile = runDir + path.sep + 'getPage.js';
//var getPageJsFile = runDir + path.sep + 'getPage.selenium.js';
if (!fs.existsSync(getPageJsFile)) {
    process.stdout.write('error: getPage.js not found.\n');
    process.exit(1);
}


//savePage.js [url] [version] [oldmd5] [dataDir]
var SavePage = {
    dataDir: '',
    mainUrl: '',
    version: 1,
    oldContentMd5: '',
    newContentMd5: '',
    mainDir:'',
    dirPath:'',
    reqAllExist: {},
    reqAll: [],    // 储存所有的请求，用于给请求编号
    reqQueue: [],  // 储存待请求队列
    resource: [],  // 储存所有资源url
    init: function(argv){
        // 获取数据存放目录
        var dataDir = argv[5] || '..\\data';
        if (!dataDir) {
            process.stdout.write('error: please enter dataDir.\n');
            return false;
        }
        if (!fs.existsSync(dataDir)) {
            process.stdout.write('error: dataDir "' + dataDir + '" not found.\n');
            return false;
        }
        this.dataDir = dataDir;
        this.mainUrl = argv[2];
        this.version = parseInt(argv[3] || 1);
        this.oldContentMd5 = argv[4] || '';
        this.mainDir = this.getMainDir(this.mainUrl, this.version);
        this.dirPath = this.md5(this.mainUrl);

        // 主页面入队列  processid tagName url urlIdx saveName
        this.pushNewReq([0, 'index', this.mainUrl]);

        // 处理队列
        this.processReqQueue();
    },
    md5: function(str){
        var md5sum = crypto.createHash('md5');
        md5sum.update(str);
        str = md5sum.digest('hex');
        return str;
    },
    // 创建目录
    createDir: function(dir){
        if (fs.existsSync(dir)) return true;

        // 非盘符或根路径
        if (path.dirname(dir) == dir) return true;

        // 先创建父级
        if (!this.createDir(path.dirname(dir))) {
            return false;
        }

        // 创建本目录
        fs.mkdirSync(dir, 0777);
        if (!fs.existsSync(dir)) {
            return false;
        }
        return true;
    },
    saveFile: function(file, content){
        if (!this.createDir(path.dirname(file))) {
            process.stdout.write('error: create dir[' + path.dirname(file) + '] fail.\n');
            return false;
        }

        return fs.writeFileSync(file, content);
    },
    urlPathResolve: function(urlDir, relativeUrl){
        var urlArr = url.parse(urlDir);
        var host = urlArr.protocol ? urlArr.protocol + '//' + urlArr.hostname + (urlArr.port ? ':'+urlArr.port : '') : '';

        var lastChar = relativeUrl && relativeUrl.substr(relativeUrl.length-1) == '/' ? '/' : '';
        var urlStr   = path.resolve(urlArr.pathname, relativeUrl).split(':')[1].replace(/\\/gi, '\/') + lastChar;
        return host + urlStr;
    },
    matchArr: function(reg, str, callback){
        var returnVal = 0;
        var item = null;
        do {
            if (item = reg.exec(str)) {
                returnVal += 1;
                callback(item);
            } else {
                break;
            }
        } while (true);

        return returnVal;
    },
    // 获取主页面储存目录
    getMainDir: function(mainUrl, version){
        var version  = parseInt(version) || 1;
        var pageDir  = path.join(this.dataDir, this.md5(mainUrl), 'v'+version);
        return pageDir;
    },
    // 获取页面资源储存相对路径
    getFilePath: function(tagName, extType, idx, tagName2){
        var returnStr   = '';
        var parentDir   = '/';
        var idx         = parseInt(idx) || 1;

        var fileName    = tagName + (idx <= '1' ? '' : '_' + idx) + (extType ? '.' + extType : '');
        var resourceDir = 'resource';

        tagName = tagName2 || tagName;
        if (tagName == 'index' || tagName == 'iframe' || tagName == 'link' || tagName == 'url' || tagName == 'resource' || tagName == 'root') {
            // 主页面
            returnStr = path.join(parentDir, fileName);
        } else {
            // 页面资源
            returnStr = path.join(parentDir, resourceDir, fileName);
        }

        return returnStr;
    },
    getFileExt: function(urlStr){
        urlArr = url.parse(urlStr);
        var extArr = urlArr.pathname.split('.');
        var ext = extArr && extArr.length >= 2 ? extArr[extArr.length-1] : '';
        return ext;
    },
    getFileName: function(urlStr){
        urlArr = url.parse(urlStr);
        return path.basename(urlArr.pathname||'');
    },
    replaceAll: function(content, reg, replace){
        do {
            if (content.indexOf(reg) > -1) {
                content = content.replace(reg, replace);
            } else {
                break;
            }
        } while (true);
        return content;
    },
    pushNewReq: function(item){
        if (item[2].match(/^\s*about:/i)) {
            return 0;
        } else if (typeof(this.reqAllExist[item[2]]) == 'undefined') {
            var idx = this.reqAll.length;
            this.reqAll[idx] = item;

            item[0] = idx+1;
            this.reqQueue.push(item);

            this.reqAllExist[item[2]] = item[0];

            // 游戏网flash特殊处理
            var urlArr = item[2].match(/^\s*(http:\/\/static\.xyimg\.net\/adver\/[^\/]+\/)main\.swf(.*)/i);
            if (urlArr && urlArr[1]) {
                this.reqQueue.push([0, 'root', urlArr[1]+'bg.swf'+urlArr[2], item[3]||0, 'bg.swf']);
                this.reqQueue.push([0, 'root', urlArr[1]+'f.swf'+urlArr[2], item[3]||0, 'f.swf']);
                this.reqQueue.push([0, 'root', urlArr[1]+'mask.swf'+urlArr[2], item[3]||0, 'mask.swf']);
                this.reqQueue.push([0, 'root', urlArr[1]+'reg.swf'+urlArr[2], item[3]||0, 'reg.swf']);
                this.reqQueue.push([0, 'root', urlArr[1]+'audio.swf'+urlArr[2], item[3]||0, 'audio.swf']);

                this.reqQueue.push([0, 'swf_sub', urlArr[1]+'bg.swf'+urlArr[2], item[3]||0, 'swf_' + item[0] + '.swbg.swf']);
                this.reqQueue.push([0, 'swf_sub', urlArr[1]+'f.swf'+urlArr[2], item[3]||0, 'swf_' + item[0] + '.swf.swf']);
                this.reqQueue.push([0, 'swf_sub', urlArr[1]+'mask.swf'+urlArr[2], item[3]||0, 'swf_' + item[0] + '.swmask.swf']);
                this.reqQueue.push([0, 'swf_sub', urlArr[1]+'reg.swf'+urlArr[2], item[3]||0, 'swf_' + item[0] + '.swreg.swf']);
                this.reqQueue.push([0, 'swf_sub', urlArr[1]+'audio.swf'+urlArr[2], item[3]||0, 'swf_' + item[0] + '.swaudio.swf']);
            }
        } else {
            // 已存在
        }
        return this.reqAllExist[item[2]];
    },
    getHttp: function(urlStr, callback){
        var callback = callback || function(){};
        var httpModule = urlStr.indexOf('https') === 0 ? https : http;

        // 获取后缀
        urlArr = url.parse(urlStr);
        var extArr = urlArr.pathname.split('.');
        var ext = extArr && extArr.length ? extArr[extArr.length-1] : '';

        var req = httpModule.request({
            'hostname': urlArr.hostname,
            'path'    : urlArr.path,
            'method'  : 'GET',
            'port'    : urlArr.port ? urlArr.port : (urlArr.protocol == 'https:' ? '443' : '80'),
            'headers' :{
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }, function(httpRes){
            if (httpRes.statusCode != '200') {
                callback(null);
                return false;
            }

            // 资源类型
            //if (httpRes.headers['content-type']) {
            //    var ctypeArr = httpRes.headers['content-type'].match(/^\s*[0-9a-zA-Z]+\s*\/\s*([^;\s]+)/i);
            //    if (ctypeArr) {
            //        ext = ctypeArr[1]
            //    }
            //}

            var buffers = [];
            httpRes.on('data', function(chunk) {
                buffers.push(chunk);
            });

            httpRes.on('end', function(chunk) {
                callback(Buffer.concat(buffers), ext);
            });
        }).on('error', function(err) {
            console.log('error ' + err);
            callback(null);
        });

        // 发放请求
        req.end();
    },
    getPage: function(urlStr, isRender, callback) {
        callback = callback || function(){};

        if (getPageJsFile.indexOf('selenium') > -1) {
            var cmd = '"' + nodeJsFile + '" "'   + getPageJsFile + '" "' + urlStr + '" ' + (isRender ? '1' : '');
        } else {
            var cmd = '"' + slimerjsFile + '" "' + getPageJsFile + '" "' + urlStr + '" ' + (isRender ? '1' : '');
        }
        child_process.exec(cmd, {timeout: 60000, maxBuffer: 10000*1024}, function(error, stdout, stderr){
            if (error) {
                console.log(error);
                callback(null);
                return false;
            }

            // 截取内容
            var content = stdout.trim().split('<--PageContent-->');
            var contentArr = content[1] ? JSON.parse(content[1]) : '';
            if ((!contentArr) || !contentArr.content) {
                callback(null);
                return false;
            }

            // 数据返回
            callback(contentArr);
        });
    },
    parseHtml: function(urlIdx, urlStr, html){
        var that = this;
        var urlArr     = url.parse(urlStr);
        var host       = urlArr.protocol + '//' + urlArr.hostname + (urlArr.port ? ':'+urlArr.port : '');
        var urlDir     = urlArr.pathname.substr(urlArr.pathname.length-1) == '/' ? urlArr.pathname : path.dirname(urlArr.pathname);
        var urlDirPath = host + urlDir;

        var resourceRs = {'iframe':[], 'swf':[], 'img':[], 'link':[], 'css_img':[]};
        // 解析iframe
        html = this.parseIframe(html, urlDirPath, resourceRs);
        // 解析swf
        html = this.parseSwf(html, urlDirPath, resourceRs);
        html = this.parseEmbed(html, urlDirPath, resourceRs);
        html = this.parseObject(html, urlDirPath, resourceRs);
        // 解析图片
        html = this.parseImg(html, urlDirPath, resourceRs);
        // 解析行内样式
        html = this.parseInlineStyle(html, urlDirPath, resourceRs);
        // 解析样式文件
        html = this.parseLink(html, urlDirPath, resourceRs);

        // 资源加入队列
        var newUrlIdx = 0;
        var ext    = '';
        var urlItemPath = '';
        for (var tagName in resourceRs) {
            if (resourceRs[tagName]) {
                for (var urlItem in resourceRs[tagName]) {
                    // 推到队列
                    newUrlIdx = this.pushNewReq([0, tagName, urlItem, urlIdx]);
                    if (newUrlIdx) {
                        // 更新页面资料内容为本地相对url
                        ext = tagName == 'index' || tagName == 'iframe' ? 'html' : this.getFileExt(urlItem);
                        urlItemPath = '.' + this.getFilePath(tagName, ext, newUrlIdx).replace(/\\/gi, '\/');
                    } else {
                        urlItemPath = '';
                    }
                    html = this.replaceAll(html, urlItem, urlItemPath);
                }
            }
        }
        return html;
    },
    parseIframe: function(html, urlDir, resourceRs){
        var reg = /(\<iframe\s+[^>]*?src=['"\s])([^'"\s]+)/gi;
        var urlDirArr = url.parse(urlDir);

        var that  = this;
        this.matchArr(reg, html, function(rs){
            var imgSrc = rs[2].trim();
            if (imgSrc) {
                var urlArr = url.parse(imgSrc);
                if (!urlArr) return false;

                if (urlArr.protocol) {
                    // 绝对地址
                } else if (imgSrc.substr(0, 2) == '\/\/') {
                    // 绝对地址，少了http开头
                    imgSrc = (urlDirArr.protocol||'http:') + imgSrc;
                } else {
                    // 相对址址
                    imgSrc = that.urlPathResolve(urlDir, imgSrc);
                }
                if (rs[0] != rs[1]+imgSrc) {
                    html = html.replace(rs[0], rs[1]+imgSrc);
                    //html = that.replaceAll(html, rs[0], rs[1]+imgSrc);
                }
                resourceRs['iframe'][imgSrc] = imgSrc;
            }
        });

        return html;
    },
    parseSwf: function(html, urlDir, resourceRs){
        var reg = /(\<param\s+[^>]*?value=['"\s])([^'"\s]+)/gi;
        var urlDirArr = url.parse(urlDir);

        var that  = this;
        this.matchArr(reg, html, function(rs){
            var imgSrc = rs[2].trim();
            if (imgSrc && rs[0].toLowerCase().indexOf('movie') > -1) {
                var urlArr = url.parse(imgSrc);
                if (!urlArr) return false;

                if (urlArr.protocol) {
                    // 绝对地址
                } else if (imgSrc.substr(0, 2) == '\/\/') {
                    // 绝对地址，少了http开头
                    imgSrc = (urlDirArr.protocol||'http:') + imgSrc;
                } else {
                    // 相对址址
                    imgSrc = that.urlPathResolve(urlDir, imgSrc);
                }
                html = html.replace(rs[0], rs[1]+imgSrc);
                //html = that.replaceAll(html, rs[0], rs[1]+imgSrc);
                resourceRs['swf'][imgSrc] = imgSrc;
            }
        });

        return html;
    },
    parseEmbed: function(html, urlDir, resourceRs){
        var reg = /(\<embed\s+[^>]*?src=['"\s])([^'"\s]+)/gi;
        var urlDirArr = url.parse(urlDir);

        var that  = this;
        this.matchArr(reg, html, function(rs){
            var imgSrc = rs[2].trim();
            if (imgSrc) {
                var urlArr = url.parse(imgSrc);
                if (!urlArr) return false;

                if (urlArr.protocol) {
                    // 绝对地址
                } else if (imgSrc.substr(0, 2) == '\/\/') {
                    // 绝对地址，少了http开头
                    imgSrc = (urlDirArr.protocol||'http:') + imgSrc;
                } else {
                    // 相对址址
                    imgSrc = that.urlPathResolve(urlDir, imgSrc);
                }
                html = html.replace(rs[0], rs[1]+imgSrc);
                //html = that.replaceAll(html, rs[0], rs[1]+imgSrc);
                resourceRs['swf'][imgSrc] = imgSrc;
            }
        });

        return html;
    },
    parseObject: function(html, urlDir, resourceRs){
        var reg = /(\<object\s+[^>]*?data=['"\s])([^'"\s]+)/gi;
        var urlDirArr = url.parse(urlDir);

        var that  = this;
        this.matchArr(reg, html, function(rs){
            var imgSrc = rs[2].trim();
            if (imgSrc) {
                var urlArr = url.parse(imgSrc);
                if (!urlArr) return false;

                if (urlArr.protocol) {
                    // 绝对地址
                } else if (imgSrc.substr(0, 2) == '\/\/') {
                    // 绝对地址，少了http开头
                    imgSrc = (urlDirArr.protocol||'http:') + imgSrc;
                } else {
                    // 相对址址
                    imgSrc = that.urlPathResolve(urlDir, imgSrc);
                }
                html = html.replace(rs[0], rs[1]+imgSrc);
                //html = that.replaceAll(html, rs[0], rs[1]+imgSrc);
                resourceRs['swf'][imgSrc] = imgSrc;
            }
        });

        return html;
    },
    parseImg: function(html, urlDir, resourceRs){
        var reg = /(\<img\s+[^>]*?src=['"\s])([^'"\s]+)/gi;
        var urlDirArr = url.parse(urlDir);

        var that  = this;
        this.matchArr(reg, html, function(rs){
            var imgSrc = rs[2].trim();
            if (imgSrc) {
                var urlArr = url.parse(imgSrc);
                if (!urlArr) return false;

                if (urlArr.protocol) {
                    // 绝对地址
                } else if (imgSrc.substr(0, 2) == '\/\/') {
                    // 绝对地址，少了http开头
                    imgSrc = (urlDirArr.protocol||'http:') + imgSrc;
                } else {
                    // 相对址址
                    imgSrc = that.urlPathResolve(urlDir, imgSrc);
                }
                html = html.replace(rs[0], rs[1]+imgSrc);
                //html = that.replaceAll(html, rs[0], rs[1]+imgSrc);
                resourceRs['img'][imgSrc] = imgSrc;
            }
        });

        return html;
    },
    parseLink: function(html, urlDir, resourceRs){
        var reg = /(\<link\s+[^>]*?href=['"\s])([^'"\s]+)([^>]*\>)/gi;
        var urlDirArr = url.parse(urlDir);

        var that  = this;
        this.matchArr(reg, html, function(rs){
            var imgSrc = rs[2].trim();
            if (imgSrc) {
                if (rs[0].toLowerCase().indexOf("text\/css") > -1 || rs[0].toLowerCase().indexOf("stylesheet") > -1) {
                    var urlArr = url.parse(imgSrc);
                    if (!urlArr) return false;

                    if (urlArr.protocol) {
                        // 绝对地址
                    } else if (imgSrc.substr(0, 2) == '\/\/') {
                        // 绝对地址，少了http开头
                        imgSrc = (urlDirArr.protocol||'http:') + imgSrc;
                    } else {
                        // 相对址址
                        imgSrc = that.urlPathResolve(urlDir, imgSrc);
                    }
                    html = html.replace(rs[0], rs[1]+imgSrc+rs[3]);
                    //html = that.replaceAll(html, rs[0], rs[1]+imgSrc+rs[3]);
                    resourceRs['link'][imgSrc] = imgSrc;
                } else {
                    html = html.replace(rs[0], rs[1]+rs[3]);
                }
            }
        });

        return html;
    },
    parseInlineStyle: function(html, urlDir, resourceRs){
        var reg = /(\<style[^>]+\>)([\w\W]+?)(\<\/style\>)/gi;
        var urlDirArr = url.parse(urlDir);

        var that  = this;
        this.matchArr(reg, html, function(rs){
            var imgSrc = rs[2].trim();
            if (imgSrc) {
                imgSrc = that.parseStyle(imgSrc, urlDir, resourceRs);
                html = html.replace(rs[0], rs[1]+imgSrc+rs[3]);
            }
        });

        return html;
    },
    parseFileStyle: function(urlIdx, urlStr, html){
        var that = this;
        var urlArr     = url.parse(urlStr);
        var host       = urlArr.protocol + '//' + urlArr.hostname + (urlArr.port ? ':'+urlArr.port : '');
        var urlDir     = urlArr.pathname.substr(urlArr.pathname.length-1) == '/' ? urlArr.pathname : path.dirname(urlArr.pathname);
        var urlDirPath = host + urlDir;

        var resourceRs = {'iframe':[], 'swf':[], 'img':[], 'link':[], 'css_img':[]};
        html = this.parseStyle(html, urlDirPath, resourceRs);

        // 资源加入队列
        var newUrlIdx = 0;
        var ext    = '';
        var urlItemPath = '';
        for (var tagName in resourceRs) {
            if (resourceRs[tagName]) {
                for (var urlItem in resourceRs[tagName]) {
                    // 推到队列
                    newUrlIdx = this.pushNewReq([0, tagName, urlItem, urlIdx]);
                    if (newUrlIdx) {
                        // 更新页面资料内容为本地相对url
                        ext = tagName == 'index' || tagName == 'iframe' ? 'html' : this.getFileExt(urlItem);
                        urlItemPath = '.' + this.getFilePath(tagName, ext, newUrlIdx).replace(/\\/gi, '\/');
                    } else {
                        urlItemPath = '';
                    }
                    html = this.replaceAll(html, urlItem, urlItemPath);
                }
            }
        }
        return html;
    },
    parseStyle: function(html, urlDir, resourceRs){
        var reg = /(\burl\s*\(\s*['"\s]?)([^'"\s\);]{5,}?)(['"\s\)]?\))/gi;
        var urlDirArr = url.parse(urlDir);

        var that  = this;
        this.matchArr(reg, html, function(rs){
            var imgSrc = rs[2].trim();
            if (imgSrc) {
                var urlArr = url.parse(imgSrc);
                if (!urlArr) return false;

                if (urlArr.protocol) {
                    // 绝对地址
                } else if (imgSrc.substr(0, 2) == '\/\/') {
                    // 绝对地址，少了http开头
                    imgSrc = (urlDirArr.protocol||'http:') + imgSrc;
                } else {
                    // 相对址址
                    imgSrc = that.urlPathResolve(urlDir, imgSrc);
                }
                html = html.replace(rs[0], rs[1]+imgSrc+rs[3]);
                //html = that.replaceAll(html, rs[0], rs[1]+imgSrc);
                resourceRs['css_img'][imgSrc] = imgSrc;
            }
        });

        var reg = /(\bsrc\s*=\s*['"\s]?)([^'"\s\);]{5,}?)(['"\s\)]?\))/gi;
        this.matchArr(reg, html, function(rs){
            var imgSrc = rs[2].trim();
            if (imgSrc) {
                var urlArr = url.parse(imgSrc);
                if (!urlArr) return false;

                if (urlArr.protocol) {
                    // 绝对地址
                } else if (imgSrc.substr(0, 2) == '\/\/') {
                    // 绝对地址，少了http开头
                    imgSrc = (urlDirArr.protocol||'http:') + imgSrc;
                } else {
                    // 相对址址
                    imgSrc = that.urlPathResolve(urlDir, imgSrc);
                }
                html = html.replace(rs[0], rs[1]+imgSrc+rs[3]);
                //html = that.replaceAll(html, rs[0], rs[1]+imgSrc);
                resourceRs['css_img'][imgSrc] = imgSrc;
            }
        });

        return html;
    },


    // 处理队列
    processReqQueue: function(){
        var that = this;

        // 检测是否需要处理
        var reqItem = this.reqQueue.pop();
        if (!reqItem) {
            // 资源url写文件
            if (that.resource && that.resource.length) {
                var resourceFile = path.join(that.mainDir, that.getFilePath('resource', 'txt'));
                that.saveFile(resourceFile, that.resource.join("\r\n"));
            }

            // 结束了
            var result = {
                'status': 1,
                'url': that.mainUrl,
                'contentMd5': that.newContentMd5,
                'dirPath': that.dirPath,
                'version': that.version
            };
            console.log('<--PageContent-->'+JSON.stringify(result)+'<--PageContent-->');
            process.exit(0);
            return false;
        }

        var urlIdx    = reqItem[0];
        var tagName   = reqItem[1];
        var urlStr    = reqItem[2];
        var parentIdx = reqItem[3] || 0;
        var saveName  = reqItem[4] || '';

        // 处理请求
        if (reqItem[0] == '1' || reqItem[1] == 'iframe') {
            var isRender  = reqItem[0] == '1' ? 1 : '';
            console.log((reqItem[0] == '1' ? 'main ':'sub ') + 'process url['+urlStr+'] ..');
            // 主页面
            this.getPage(urlStr, isRender, function(contentRs){
                // 请求失败
                if (!contentRs) {
                    // 如果是主页面，则退出
                    if (reqItem[0] == '1') {
                        reqItem[4] = reqItem[4] || 0;
                        if (reqItem[4] >= 1) {
                            console.log('mainUrl[' + urlStr + '] request error');
                            process.exit(1);
                        } else {
                            reqItem[4] += 1;
                            that.reqQueue.push(reqItem);
                        }
                    }
                    return false;
                }

                // 检测是否储存过
                if (that.oldContentMd5) {
                    if (that.md5(contentRs.source) == that.oldContentMd5) {
                        // 结束了
                        var result = {
                            'status': 2,
                            'url': that.mainUrl,
                            'contentMd5': that.oldContentMd5,
                            'dirPath': that.dirPath,
                            'version': that.version - 1
                        };
                        console.log('<--PageContent-->'+JSON.stringify(result)+'<--PageContent-->');
                        process.exit(0);
                        return false;
                    }
                }

                // 首页内容md5
                if (reqItem[0] == '1') {
                    that.newContentMd5 = that.md5(contentRs.source);

                    // 储存url
                    var urlFile = path.join(that.mainDir, that.getFilePath('url', 'txt'));
                    that.saveFile(urlFile, that.mainUrl);

                    // 额外资源url入队(swf,flv)
                    if (contentRs.resource && contentRs.resource.length) {
                        // 推到队列
                        for (var i=0,len=contentRs.resource.length; i<len; i++) {
                            that.reqQueue.push([0, 'swf_sub', contentRs.resource[i], 0, that.getFileName(contentRs.resource[i])]);
                        }
                    }
                }

                // 解析页面资源，把资料加入队列
                contentRs.content = that.parseHtml(urlIdx, contentRs.rurl, contentRs.content);

                // 储存html内容 contentRs.content(不包含js)
                var pageName = that.getFilePath(tagName, 'html', urlIdx);
                var pageFile = path.join(that.mainDir, pageName);
                that.resource.push(urlStr+'\t'+pageName.replace(/\\/g, '\/'));
                that.saveFile(pageFile, contentRs.content);

                // 储存截图
                if (contentRs.renderBase64) {
                    var renderFile = path.join(that.mainDir, that.getFilePath(tagName, 'jpg'));
                    that.saveFile(renderFile, new Buffer(contentRs.renderBase64, 'base64'));
                }


                // 处理下一个请求
                that.processReqQueue();

            });
        } else if (reqItem[1] == 'link') {
            // 获取资源
            this.getHttp(urlStr, function(content, type){
                // 解析页面资源，把资料加入队列
                content = that.parseFileStyle(urlIdx, urlStr, content.toString('utf-8'));

                // 储存html内容 contentRs.content(不包含js)
                var pageName = that.getFilePath(tagName, type, urlIdx);
                var pageFile = path.join(that.mainDir, pageName);
                that.resource.push(urlStr+'\t'+pageName.replace(/\\/g, '\/'));
                that.saveFile(pageFile, content);

                // 处理下一个请求
                that.processReqQueue();
            });
        } else {
            // 获取资源
            this.getHttp(urlStr, function(content, type){
                // 储存资源
                if (saveName) {
                    var pageName = that.getFilePath(saveName, '', 0, tagName);
                    var pageFile = path.join(that.mainDir, pageName);
                } else {
                    var pageName = that.getFilePath(tagName, type, urlIdx);
                    var pageFile = path.join(that.mainDir, pageName);
                }
                that.resource.push(urlStr+'\t'+pageName.replace(/\\/g, '\/'));
                that.saveFile(pageFile, content);

                // 处理下一个请求
                that.processReqQueue();
            });
        }

    }


};

// 脚本启动
if (process.argv.length <= 2) {
    console.log('Usage: savePage.js [url] [version] [oldContentMd5] [dataDir]');
    process.exit(1);
} else {
    SavePage.init(process.argv);
}

