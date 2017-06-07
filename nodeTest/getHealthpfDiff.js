// updateTime指定
var updateTime = '2010-11-11 00:00:00';

var utils = require('./utils.js');
var fs = require('fs');

// 旧DB(データ移行元)
var orgDbConfig = {
	host: 		'prdrds01.cu72a2lknk9v.ap-northeast-1.rds.amazonaws.com',
	port: 		'3306',
	user: 		'inde',
	password: 	'infodeliver',
	database: 	'healthpf' 
};

// DB接続
var mysql = utils.mysql;
var conn = mysql.createConnection(orgDbConfig);
conn.connect();

fs.existsSync("data") || fs.mkdirSync("data");

utils.each (utils.tblObjArr, function(i, table) {

	var sql = table.getSql;
	sql = mysql.format(sql, [updateTime]);
	// console.log(sql);
	var query = conn.query(sql);

	var fName = "data/" + table.name + '.json';
	fs.writeFile(fName, '', 'utf-8', function(res) { console.log(table.name + "抽出完了."); });

	query
		.on('result', function(rows) {
			fs.appendFile(fName, JSON.stringify(rows) + "\n", function(res) { });
		})
});



