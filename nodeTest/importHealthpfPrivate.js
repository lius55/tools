var utils = require('./utils.js');
var fs = require('fs');

// 新DB(データ移行先)
var targetDbConfig = {
	host: 		'prdrds02.cu72a2lknk9v.ap-northeast-1.rds.amazonaws.com',
	port: 		'3306',
	user: 		'inde',
	password: 	'infodeliver',
	database: 	'healthpf_private'
};

// DB接続
var conn = utils.mysql.createConnection(targetDbConfig);
conn.connect();

var count = 0;
var targetCount = 0;

utils.each(utils.prvTblObjArr, function(i, table) {

	// 読み込みjsonファイル名(テーブルごとに読み込みます)
	var fileName = "data/" + table.name + '.json';

	fs.readFile(fileName, 'utf8', function(err, text) {

		if (text == undefined || text.length < 1) { return; }

		utils.each(text.split('\n'), function(index, row) {

			if (row.length < 1) { return; }

			// JSONオブジェクトに変換
			var row = JSON.parse(row);

			// ----------------------------
			//   主キーで該当のレコード抽出
			// ----------------------------
			var searchSql = "select count(*) as cnt from ?? where ";
			searchSql = utils.mysql.format(searchSql, [table.name]);
			searchSql += utils.getKeyClause(utils.mysql, table.key, row);
			var searchQuery = conn.query(searchSql);
			// console.log("searchSql=" + searchSql);

			searchQuery.on('result', function(rs) {
				targetCount++;
				// 存在すれば更新します
				// console.log("cnt=" + rs['cnt']);
				if (rs['cnt'] > 0) {
					var updateSql = utils.getUpdateClause(utils.mysql, table, row);
					conn.query(updateSql).on('result', function(res) {
						utils.log('update', ++count, targetCount, table.name);
					});
				} else {
					var insertSql = utils.getInsertClause(utils.mysql, table, row);
					conn.query(insertSql).on('result', function(result) { 
						utils.log('insert', ++count, targetCount, table.name);
					});
				}
			});
		});
	})
});