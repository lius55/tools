exports.mysql = require('mysql');

exports.each = function(arr, callback) {
	var element;
	for (element in arr) {
		var val = arr[element];
		if (callback.call(val, element, val) == false) {
			break;
		}
	}
	return arr;
};

// healthpfテーブル一覧
exports.tblObjArr = [
	{ 
		name: 'm_control',
		key: 'userID',
		getSql: 'select m.userID as userID,sendTime,receiveTime from m_control as m,s_walker_daily as s where m.userID=s.userID and updateTime>?',
		columns: 'userID,sendTime,receiveTime'
	},  
	{ 
		name: 'm_health_cond',
		key: 'userID', 
		getSql: 'select * from m_health_cond where updateTime > ?',
		columns: 'userID,goalStep,standardSpeed,standardStride,healthCondClass,healthCondLabel,speedLevelIcon,speedLevelLabel,updateTime,speedCmpMessage' 
	},
	{ 
		name: 'm_user',
		key: 'userID',
		getSql: 'select * from m_user where updateTime > ?',
		columns: 'userID,deviceID,deviceType,chatID,givenNameKanji,attribute,userClass,iconID,useWalkingMeas,purchaseStatus,userSalt,actualAge,sex,chatPW,insertTime,updateTime'
	},
	{ 
		name: 'm_user_control', 
		key: 'userID',
		getSql: 'select * from m_user_control where lastReceiveTime > ?',
		columns: 'userID,purchaseStatus,purchaseStatusYmd,referralStatus,referralStatusYmd,contractStatus,contractStatusYmd,tryRelateCount,tryRelateTime,lastReceiveTime,noCommHours,noWalkingHours'
	}, // m_user_control は、　lastReceiveTime によって、判断します。
	{ 
		name: 'm_user_relation',
		key: 'familyID,insuredID',
		getSql: 'select * from m_user_relation where updateTime > ?',
		columns: 'familyID,insuredID,relationship,isHousemate,useAlarmClass,lastAlarmHoldTime,chatDialogID,updateTime,observeBeginTime'
	},
	{ 
		name: 't_chat_order',
		key: 'insuredID,familyID',
		getSql: 'select insuredID,familyID,chatOrderType,chatDialogID,insertTime from t_chat_order as t,s_walker_daily as s where s.userID=t.insuredID and updateTime>?',
		columns: 'insuredID,familyID,chatOrderType,chatDialogID,insertTime'
	},  // s_walker_daily のupdate_time 
	{ 
		name: 't_engine_token',
		key: 'userID',
		getSql: 'select * from t_engine_token where insertTime > ?',
		columns: 'userID,authToken,insertTime'
	}, // insert_time
	{ 
		name: 't_old_data_order',
		key: 'familyID,insuredID',
		getSql: 'select insuredID,insuredID,observeBeginTime from t_old_data_order as t,s_walker_daily as s where s.userID = t.familyID and updateTime > ?',
		columns: 'familyID,insuredID,observeBeginTime'
	}, // s_walker_daily のupdate_time 
	{ 
		name: 't_user_session',
		key: 'userID,sessionKey',
		getSql: 'select * from t_user_session where insertTime > ?',
		columns: 'userID,sessionKey,expirationTime,insertTime'
	} // insert_time
];

// healthpf_privateテーブル一覧
exports.prvTblObjArr = [
	{ 
		name: 'm_privacy',
		key: 'userID',
		getSql: 'select * from m_privacy where  updateTime>?',
		columns: 'userID,familyNameKanji,givenNameKanji,familyNameKana,givenNameKana,sex,birthday,height,weight,postageCode,loginPw,updateTime'
	}
];

exports.getKeyClause = function(mysql, key, row) {
	var whereSection = '';
	this.each(key.split(","), function(index, element){
		if (index > 0) { whereSection += " and "; }
		whereSection += " ?? = ? ";
		whereSection = mysql.format(whereSection, [element,row[element]]);
	});
	return whereSection;
}

exports.getUpdateClause = function(mysql, table, row) {
	var updateSection = 'update ?? set ';
	updateSection = mysql.format(updateSection, [table.name]);
	this.each(table.columns.split(","), function(index, element) {
		if (index > 0) { updateSection += ", ";}
		updateSection += " ?? = ? ";
		updateSection = mysql.format(updateSection, [element, row[element]]);
	});
	updateSection += "where " + this.getKeyClause(mysql, table.key, row);
	console.log(updateSection);
	return updateSection;
}

exports.getInsertClause = function(mysql, table, row) {

	var insertSection = "insert into " + table.name;
	// values
	var values = new Array();
	// カラム名
	var formatAry = new Array();
	
	utils.each (table.columns.split(","), function(i, e) {
		values.push(row[e]);
		formatAry.push('?');
	});

	// カラム名取得
	var valueSql = formatAry.join(',');
	valueSql = utils.mysql.format(valueSql, values);

	// 各カラムの値を指定する
	insertSection += " values(" + valueSql + ")";
	return insertSection;
}

exports.log = function(oper, count, targetCount, table) {
	console.log('処理件数[' + oper + ']=' + ++count); 
	if (count > targetCount) {
		console.log("=======[" + table + "]処理完了=======");
	}
}
