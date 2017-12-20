var express = require('express');
var path    = require('path');
var child_process = require('child_process');
var fs=require('fs');

var app = express();
var runDir = path.resolve(__dirname);

var nodePath = path.join(runDir,'node');
var appFile = path.resolve(runDir,'app.js');
var saveFile = path.resolve(runDir,'savePage.js');
var dataDir = '../data';

function checkClientIp(req) {
    var ip = req.headers['x-forwarded-for'] ||
    req.connection.remoteAddress ||
    req.socket.remoteAddress ||
    req.connection.socket.remoteAddress;

    if (ip.indexOf('127.0.0.1') > -1 || ip.indexOf('110.80.46.138') > -1 || ip.indexOf('211.152.7.212') > -1 || ip.indexOf('211.152.7.213') > -1) {
        return true;
    } else {
        console.log('deny ip: ' + ip);
        return false;
    }
};

// 首页
app.get('/', function(req, res){
    if (!checkClientIp(req)) {
        res.send('');
        return false;
    }
    res.header('Cache-Control', 'no-cache, no-store, must-revalidate');
    res.header('Pragma', 'no-cache');
    res.header('Expires', '0');
    res.sendFile(path.join(runDir, 'index.html'));
});

// 根据url抓取，savePage?url=xxx
app.get('/savePage', function(req, res){
    if (!checkClientIp(req)) {
        res.send('');
        return false;
    }
    res.header('Cache-Control', 'no-cache, no-store, must-revalidate');
    res.header('Pragma', 'no-cache');
    res.header('Expires', '0');

    var _url = req.query.url;
    var _version = req.query.version ? parseInt(req.query.version) : 0;
    var _type = req.query.type ? parseInt(req.query.type) : 0;
    if (!_url || !/^http\:\/\/.+/.test(_url)){
        res.send('url is error!');
    } else {
        var cmd = '"' + nodePath + '" "' + saveFile + '" "' + _url + '"';
        if (_version >= 0) {
            cmd += ' "' + _version + '"';
        }
        if (_type >= 0) {
            cmd += ' "' + _type + '"';
        }
        console.log(cmd);
        child_process.exec(cmd, function(error, stdout, stderr) {

        });
        res.json({"status":"1", "msg":"success!"});
    }
});


// app?id=xxx
app.get('/app', function(req, res){
    if (!checkClientIp(req)) {
        res.send('');
        return false;
    }
    res.header('Cache-Control', 'no-cache, no-store, must-revalidate');
    res.header('Pragma', 'no-cache');
    res.header('Expires', '0');

    var _id = req.query.id;
    var _version = req.query.version ? parseInt(req.query.version) : 0;
    var _type = req.query.type ? parseInt(req.query.type) : 0;
    if (!_id || !/^[0-9]+$/.test(_id) || parseInt(_id) < 1){
        res.send('id is error!');
    } else {
        var cmd = '"' + nodePath + '" "' + appFile + '" "' + _id + '"';
        if (_version >= 0) {
            cmd += ' "' + _version + '"';
        }
        if (_type >= 0) {
            cmd += ' "' + _type + '"';
        }
        console.log(cmd);
        child_process.exec(cmd, function(error, stdout, stderr) {

        });
        res.json({"status":"1", "msg":"success!", "cmd": cmd});
    }
});

app.get('/page', function(req, res){
    if (!checkClientIp(req)) {
        res.send('');
        return false;
    }
    res.header('Cache-Control', 'no-cache, no-store, must-revalidate');
    res.header('Pragma', 'no-cache');
    res.header('Expires', '0');

    var _page = req.query.page ? parseInt(req.query.page) : 1;
    if (_page < 1) {
        _page = 1;
    }
    var _pagesize = 24;
    var _level = fs.readdirSync(dataDir);
    var _total = _level.length;
    var _start = (_page - 1) * _pagesize;
    var _end = _start + _pagesize;
    if (_end > _total) {
        _end = _total;
    }

    var fileArr = [];
    for (var i = _start; i < _end; i++) {
        var _level2 = fs.readdirSync(path.join(dataDir, _level[i]));
        var _max = 0;
        for (var i2 in _level2) {
            var _index = parseInt(_level2[i2].replace('v',''));
            if (_max < _index) {
                _max = _index;
            }
        }
        var _stat = fs.statSync(path.join(dataDir, _level[i], 'v' + _max));
        var _mtime = _stat.mtime;
        fileArr.push({
            'path': _level[i] + '/v' + _max,
            'mtime': _mtime.getTime()
        })
    }
    var pageHtml = '<p style="clear:both;text-align:center;padding:0;">';
    var _totalPage = Math.ceil(_total/_pagesize);
    if (_totalPage > 0) {
        if (_page > 1) {
            pageHtml += '<a href="./?page=' + (_page - 1) + '">上一页</a>';
        }
        pageHtml += '&nbsp第' + _page + '页&nbsp;共' + _totalPage + '页&nbsp;';
        if (_page < _totalPage) {
            pageHtml += '<a href="./?page=' + (_page + 1) + '">下一页</a>';
        }
    }
    pageHtml += '</p>';

    var imgArr = [];
    for (var i in fileArr) {
        imgArr.push('<a href="./' + fileArr[i].path + '/index.html" target="black"><img src="./' + fileArr[i].path + '/index.jpg"/></a>');
    }
    var html = '<!DOCTYPE html>'
             + '<html lang="en">'
             + '<head>'
             + '<meta charset="UTF-8">'
             + '<title>海报列表</title>'
             + '<style type="text/css">html,body{margin:0;padding:10px;}img{width:210px;height:150px;display:block;float:left;margin:5px;}body{padding:10px;}</style>'
             + '</head>'
             + '<body><h3 style="margin:0;padding:0 0 5px 0;0;">海报列表(点击图片查看详情)</h3>'
             + '<div>'
             +  imgArr.join('')
             + '</div>'
             +  pageHtml
             + '</body>'
             + '</html>';
    res.send(html);
});

// 海报静态页面资源
app.use('/page', express.static('../data'));

// 404页面
app.use(function(req, res, next){
    res.status(404);
    res.sendFile(path.join(runDir, '404.html'));
});


// 启动http服务
var server = app.listen(8081, function(){
    var host = server.address().address;
    var port = server.address().port;
    console.log('server listening at http://%s:%s', '127.0.0.1', port);
});
