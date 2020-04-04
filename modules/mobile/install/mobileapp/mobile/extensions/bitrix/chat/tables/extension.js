/**
 * Use deps 'chat/tables'
 *
 * @requires module:db
 * @module chat/tables
 */

var ChatDatabaseName = "im";
var ChatTables = {
	recent : {
		name : "recent",
		fields : [{name : "id", unique : true}, "value"]
	},
	recentLines : {
		name : "recentLines",
		fields : [{name : "id", unique : true}, "value"]
	},
	lastSearch : {
		name : "lastSearch",
		fields : [{name : "id", unique : true}, "value"]
	},
	colleaguesList : {
		name : "colleaguesList",
		fields : [{name : "id", unique : true}, "value"]
	},
	businessUsersList : {
		name : "businessUsersList",
		fields : [{name : "id", unique : true}, "value"]
	},
	dialogConfig : {
		name : "dialogConfig",
		fields : [{name : "id", unique : true}, "value"]
	},
	diskConfig : {
		name : "diskConfig",
		fields : [{name : "dialogId", unique : true}, "value"]
	},
	diskFileQueue : {
		name : "diskFileQueue",
		fields : [{name : "id", unique : true}, "value"]
	},
	notifyConfig : {
		name : "notifyConfig",
		fields : [{name : "id", unique : true}, "value"]
	},
	dialogOptions : {
		name : "dialogOptions",
		fields : [
			{name : "id", unique : true, primary: true},
			{name : "options"},
			{name : "lastModified", type: 'integer'},
			{name : "lastModifiedAtom"}
		]
	},
	dialogMessages : {
		name : "dialogMessages",
		fields : [
			{name : "id", unique : true},
			{name : "dialogId"},
			"value"
		]
	},
};