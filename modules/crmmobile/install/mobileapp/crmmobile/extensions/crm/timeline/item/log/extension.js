/**
 * @module crm/timeline/item/log
 */
jn.define('crm/timeline/item/log', (require, exports, module) => {

    const { Creation } = require('crm/timeline/item/log/creation');
	const { Modification } = require('crm/timeline/item/log/modification');
	const { Link } = require('crm/timeline/item/log/link');
	const { Unlink } = require('crm/timeline/item/log/unlink');
	const { TodoCreated } = require('crm/timeline/item/log/todo-created');
	const { CallIncoming } = require('crm/timeline/item/log/call-incoming');
	const { Ping } = require('crm/timeline/item/log/ping');
	const { DocumentViewed } = require('crm/timeline/item/log/document-viewed');

    module.exports = {
		Creation,
		Modification,
		Link,
		Unlink,
		TodoCreated,
		CallIncoming,
		Ping,
		DocumentViewed,
	};

});