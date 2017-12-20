var fs   = require('fs');
var path = require('path');
var child_process = require('child_process');
var mysql  = require('mysql');

var App = {
    config: {},
    dataDir: '',
    type: '0',
    init: function (config, argv) {
        // 获取数据储存目录
        var dataDir = typeof(config['dataDir']) == 'undefined' ? '' : path.resolve(config['dataDir']);
        if (!dataDir) {
            process.stdout.write('error: please enter dataDir.\n');
            return false;
        }
        if (!fs.existsSync(dataDir)) {
            process.stdout.write('error: dataDir "' + dataDir + '" not found.\n');
            return false;
        }

        this.dataDir = dataDir;
        this.config = config;

        this.type = argv[4] ? argv[4] : 0;

        var runDir = path.dirname(process.argv[1]);
        this.nodePath = path.join(runDir, 'node.exe');
        this.savePageFile = path.join(runDir, 'savePage.js');

        this.errorNum = 0; // 失败次数
        this.run(argv[2], argv[3]);
    },
    run: function (id, version) {
        var that = this;
        // 开始处理
        process.stdout.write('start running ..\n');
        process.stdout.write('dataDir: ' + that.dataDir + '\n');
        // 数据库获取要抓取的url列表
        if (id && id > 0) {
            var version = version ? parseInt(version) : 0;
            that.processById(id, version);
        } else {
            that.getAdUrlList(0);
        }
    },
    getAdUrlList: function (startId) {
        var startId = startId ? startId : 0;
        var that = this;
        var sql = "SELECT * FROM other_url WHERE oa_state=0 and oa_runstate in(0,3) and oa_id>" + startId + " ORDER BY oa_id ASC LIMIT 0,10" ;
        that.executeSql(sql, function (result) {
            if (result.status == 1 && result.data.length) {
                var data = result.data;
                var idArr = [];
                var idMax = 0;
                for (var i in data) {
                    idArr.push(data[i].oa_id);
                    idMax = data[i].oa_id;
                }
                // 更新状态
                var sql = "update other_url set oa_runstate=1 where oa_id in(" + idArr.join(",") + ")";
                that.executeSql(sql, function (result2) {
                    if (result2.status == 1) {
                        // 执行
                        that.processQueue(data, 1, function(result3) {
                            that.getAdUrlList(idMax);
                        });
                    }
                });
            }
        });
        return false;
    },
    // 处理队列
    processQueue: function(queue, num, callback){
        callback = callback || function(){};
        var that = this;
        var num = num ? num : 1; // 执行个数
        for (var i = 0; i < num; i++) {
            var reqItem = queue.shift();
            // 检测是否需要处理
            if (!reqItem) {
                // 结束
                callback(1);
                return false;
            }
            that.processOne(reqItem,function(result) {
                that.processQueue(queue, 1, callback);
            });
        }
    },
    processById: function(id, version) {
        var that = this;
        var sql = "SELECT * FROM other_url WHERE oa_id=" + id;
        that.executeSql(sql, function (result) {
            if (result.status == 1 && result.data.length) {
                var data = result.data;
                if (version > 0) {
                    // 有版本号
                    console.log('ID:' + id + ' URL:' + data[0].oa_url + ' VERSION:' + version + '\n');
                    var cmd = '"' + that.nodePath + '" "' + that.savePageFile + '" "'   + data[0].oa_url + '" "' + version + '"';
                    console.log(cmd);
                    child_process.exec(cmd, {timeout: 60000, maxBuffer: 100000*1024}, function(error, stdout, stderr) {
                        if (error) {
                            console.log(error);
                        }
                    });
                } else {
                    // 更新状态
                    var sql = "update other_url set oa_runstate=1 where oa_id=" + id;
                    that.executeSql(sql, function (result2) {
                        if (result2.status == 1) {
                            // 执行
                            that.processOne(data[0]);
                        }
                    });
                }
            } else {
                console.log("ID:" + id + " Not Found")
            }
        });
        return false;
    },
    // 处理一个
    processOne: function(reqItem, callback) {
        callback = callback || function(){};
        var that = this;
        console.log('ID:' + reqItem.oa_id + ' URL:' + reqItem.oa_url + ' VERSION:' + reqItem.oa_version + '  MD5:' + reqItem.oa_contentmd5 + '\n');
        var newVersion = parseInt(reqItem.oa_version) + 1;
        var cmd = '"' + that.nodePath + '" "' + that.savePageFile + '" "'   + reqItem.oa_url + '" "' + newVersion + '" "' + reqItem.oa_contentmd5 + '"';
        console.log(cmd);
        child_process.exec(cmd, {timeout: 60000, maxBuffer: 100000*1024}, function(error, stdout, stderr) {
            var flag =true;
            if (error) {
                console.log(error);
                flag = false;
            } else {
                // 截取内容
                var content = stdout.trim().split('<--PageContent-->');
                var contentArr = content[1] ? JSON.parse(content[1]) : '';
                var status = contentArr && contentArr.status ? contentArr.status : 0;
                if (!contentArr || (status != 1 && status != 2) || !contentArr.contentMd5 || !contentArr.version || !contentArr.dirPath) {
                    flag = false;
                }
            }
            var nowTime = parseInt((new Date()).getTime() / 1000);
            if (!flag) {
                // 获取失败，执行下一条(runstate:3)
                that.errorNum ++;
                var sql_fail = "update other_url set oa_runstate=3,oa_mod_date=" + nowTime + " where oa_id=" + reqItem.oa_id;
                that.executeSql(sql_fail);
                if (that.errorNum >= 30) {
                    // 失败次数大于30，退出
                    var sql = "update other_url set oa_runstate=3,oa_mod_date=" + nowTime + " where oa_runstate=1";
                    that.executeSql(sql, function (result) {
                        console.log("失败次数大于30，退出执行。");
                        process.exit(0);
                    });
                    return false;
                }
            } else {
                that.errorNum = 0;
                // 成功，更新版本信息(runstate:2)
                var sql_update = "UPDATE other_url SET";
                if (status == 1) {
                    sql_update += " oa_version=" + contentArr.version + ",oa_contentmd5='" + contentArr.contentMd5 + "',oa_runstate=2,oa_dirpath='" + contentArr.dirPath + "',oa_mod_date=" + nowTime;
                    sql_update += " WHERE oa_id=" + reqItem.oa_id + " AND oa_version<" + contentArr.version;
                } else {
                    sql_update += " oa_runstate=2,oa_mod_date=" + nowTime;
                    sql_update += " WHERE oa_id=" + reqItem.oa_id;
                }
                that.executeSql(sql_update);
                if (status == 1) {
                    // 增加新版本记录
                    var sql_insert = "INSERT INTO other_url_version(oav_oaid,oav_version,oav_contentmd5,oav_add_date,oav_mod_date) VALUES"
                    sql_insert += " ('" + reqItem.oa_id + "','" + contentArr.version + "','" + contentArr.contentMd5 + "','" + nowTime + "','" + nowTime + "')"
                    sql_insert += " ON DUPLICATE KEY UPDATE `oav_contentmd5`=values(`oav_contentmd5`),`oav_mod_date`=values(`oav_mod_date`)";
                    that.executeSql(sql_insert);
                }
            }
            // 执行结束
            callback(1);
        });
        return false;
    },
    executeSql: function(sql, callback) {
        var _type = this.type;

        callback = callback || function(){};
        var returnArr = {status: 0, msg: 'fail.'};
        var config = this.config;
        //创建连接
        if (_type == 1) {
            var client = mysql.createConnection({
                host     : config.dbhost_gameapp,
                user     : config.dbuser_gameapp,
                password : config.dbpassword_gameapp,
                port     : config.dbport_gameapp,
                database : config.dbname_gameapp
            });
        } else {
            var client = mysql.createConnection({
                host     : config.dbhost,
                user     : config.dbuser,
                password : config.dbpassword,
                port     : config.dbport,
                database : config.dbname
            });
        }
        
        client.connect();
        console.log(sql);
        client.query(sql, [], function(err, result){
            if (err) {
                console.log('[EXECUTE ERROR] - ', err.message);
                callback(returnArr);
                return false;
            }
            returnArr.status = 1;
            returnArr.data = result;
            callback(returnArr);
        });
        client.end();
        return false;
    }
}


// 脚本启动
var configFile = path.join(__dirname, 'app.json');
if (fs.existsSync(configFile)) {
    var configBuff = fs.readFileSync(configFile, 'utf-8');
    // 去掉bom
    if (configBuff[0].toString(16).toLowerCase() == "ef" && configBuff[1].toString(16).toLowerCase() == "bb" && configBuff[2].toString(16).toLowerCase() == "bf") {
        //EF BB BF 239 187 191
        configBuff = configBuff.slice(3);
    }
    var config = JSON.parse(configBuff);
    App.init(config, process.argv);
} else {
    process.stdout.write('error: ' + path.basename(configFile) + ' not found.\n');
}
