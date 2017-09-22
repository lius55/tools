each = function(arr, callback) {
	var element;
	for (element in arr) {
		var val = arr[element];
		if (callback.call(val, element, val) == false) {
			break;
		}
	}
	return arr;
};

// updateTime指定
var updateTime = '2000-11-11 00:00:00';

// 旧DB(データ移行元)
var orgDbConfig = {
	host: 		'prdrds02.cu72a2lknk9v.ap-northeast-1.rds.amazonaws.com',
	port: 		'3306',
	user: 		'inde',
	password: 	'infodeliver',
	database: 	'healthpf_private' 
};

// 新DB(データ移行先)
var targetDbConfig = {
	// host: 		'prdrds02.cu72a2lknk9v.ap-northeast-1.rds.amazonaws.com',
	host: 		'prdrds01.cu72a2lknk9v.ap-northeast-1.rds.amazonaws.com',
	port: 		'3306',
	user: 		'inde',
	password: 	'infodeliver',
	database: 	'healthpf'
};

// DB接続
var mysql = require('mysql');
var conn = mysql.createConnection(orgDbConfig);
conn.connect();
var tgtConn = mysql.createConnection(targetDbConfig);
tgtConn.connect();

// テーブル一覧
var tblObjArr = [
	// { 
	// 	name: 'm_control',
	// 	key: 'userID',
	// 	getSql: 'select m.userID as userID,sendTime,receiveTime from m_control as m,s_walker_daily as s where m.userID=s.userID and updateTime>?',
	// 	columns: 'userID,sendTime,receiveTime'
	// },  
	// { 
	// 	name: 'm_health_cond',
	// 	key: 'userID', 
	// 	getSql: 'select * from m_health_cond where updateTime > ?',
	// 	columns: 'userID,goalStep,standardSpeed,standardStride,healthCondClass,healthCondLabel,speedLevelIcon,speedLevelLabel,updateTime,speedCmpMessage' 
	// },
	// { 
	// 	name: 'm_user',
	// 	key: 'userID',
	// 	getSql: 'select * from m_user where updateTime > ?',
	// 	columns: 'userID,deviceID,deviceType,chatID,givenNameKanji,attribute,userClass,iconID,useWalkingMeas,purchaseStatus,userSalt,actualAge,sex,chatPW,insertTime,updateTime'
	// },
	// { 
	// 	name: 'm_user_control', 
	// 	key: 'userID',
	// 	getSql: 'select * from m_user_control where lastReceiveTime > ?',
	// 	columns: 'userID,purchaseStatus,purchaseStatusYmd,referralStatus,referralStatusYmd,contractStatus,contractStatusYmd,tryRelateCount,tryRelateTime,lastReceiveTime,noCommHours,noWalkingHours'
	// }, // m_user_control は、　lastReceiveTime によって、判断します。
	// { 
	// 	name: 'm_user_relation',
	// 	key: 'familyID,insuredID',
	// 	getSql: 'select * from m_user_relation where updateTime > ?',
	// 	columns: 'familyID,insuredID,relationship,isHousemate,useAlarmClass,lastAlarmHoldTime,chatDialogID,updateTime,observeBeginTime'
	// },
	// { 
	// 	name: 't_chat_order',
	// 	key: 'insuredID,familyID',
	// 	getSql: 'select insuredID,familyID,chatOrderType,chatDialogID,insertTime from t_chat_order as t,s_walker_daily as s where s.userID=t.insuredID and updateTime>?',
	// 	columns: 'insuredID,familyID,chatOrderType,chatDialogID,insertTime'
	// },  // s_walker_daily のupdate_time 
	// { 
	// 	name: 't_engine_token',
	// 	key: 'userID',
	// 	getSql: 'select * from t_engine_token where insertTime > ?',
	// 	columns: 'userID,authToken,insertTime'
	// }, // insert_time
	// { 
	// 	name: 't_old_data_order',
	// 	key: 'familyID,insuredID',
	// 	getSql: 'select insuredID,insuredID,observeBeginTime from t_old_data_order as t,s_walker_daily as s where s.userID = t.familyID and updateTime > ?',
	// 	columns: 'familyID,insuredID,observeBeginTime'
	// }, // s_walker_daily のupdate_time 
	// { 
	// 	name: 't_user_session',
	// 	key: 'userID,sessionKey',
	// 	getSql: 'select * from t_user_session where insertTime > ?',
	// 	columns: 'userID,sessionKey,expirationTime,insertTime'
	// } // insert_time
	{ 
		name: 'm_privacy',
		key: 'userID',
		getSql: 'select m.userID as userID,sendTime,receiveTime from m_control as m,s_walker_daily as s where m.userID=s.userID and updateTime>?',
		columns: 'userID,sendTime,receiveTime'
	}
];

// var sql = "show full columns from m_privacy";

each (tblObjArr, function(i, table) {

	// var sql = table.getSql + " limit 1";
	// sql = mysql.format(sql, [updateTime]);
	var sql = "show full columns from ??";
	sql = mysql.format(sql, [table.name]);
	console.log(sql);
	var query = conn.query(sql);

	var fs = require('fs');
	var fName = table.name + '.json';
	fs.writeFile(fName, '', 'utf-8', function(err) { console.log(err); });

	query
		.on('result', function(rows) {

			fs.appendFile(fName, JSON.stringify(rows) + "\n", function(err) { console.log(err); });
			// var searchSql = "select count(*) as cnt from ?? where ??=?";
			// searchSql = mysql.format(searchSql, [table.name, table.key, rows[table.key]]);
			// console.log(searchSql);
			// var searchQuery = tgtConn.query(searchSql);
			// searchQuery.on('result', function(rs) {
			// 	// 存在すれば削除します
			// 	if (rs['cnt'] > 0) {
			// 		var delSql = "delete from ?? where ??=?";
			// 		delSql = mysql.format(delSql, [table.name, table.key, rows[table.key]]);
			// 		console.log(delSql);
			// 		tgtConn.query(delSql);
			// 	}
			// 	var uptSql = "insert into " + table.name;
			// 	var values = new Array();
			// 	var formatAry = new Array();
			// 	each (table.columns.split(","), function(i, e) {
			// 		values.push(rows[e]);
			// 		formatAry.push('?');
			// 	});
			// 	var valueSql = formatAry.join(',');
			// 	valueSql = mysql.format(valueSql, values);
			// 	uptSql += " values(" + valueSql + ")";
			// 	console.log(uptSql);
			// 	tgtConn.query(uptSql);
			// });
		})
		.on('error', function(err) {
			console.log(err);
		});

});



