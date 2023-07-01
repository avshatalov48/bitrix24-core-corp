/**
 * @module crm/timeline/item/log/rest-log
 */
jn.define('crm/timeline/item/log/rest-log', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class RestLog
	 */
	class RestLog extends TimelineItemBase
	{}

	module.exports = { RestLog };
});
