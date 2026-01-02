/**
 * @module im/messenger-v2/controller/recent/const
 */
jn.define('im/messenger-v2/controller/recent/const', (require, exports, module) => {
	const { RecentEventType } = require('im/messenger-v2/controller/recent/const/event-type');
	const { RecentServiceName } = require('im/messenger-v2/controller/recent/const/service');

	module.exports = {
		RecentEventType,
		RecentServiceName,
	};
});
