/**
 * @module crm/timeline/item/log/call-incoming
 */
jn.define('crm/timeline/item/log/call-incoming', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class CallIncoming
	 */
	class CallIncoming extends TimelineItemBase
	{}

	module.exports = { CallIncoming };
});
