/**
 * @module crm/timeline/item/activity/email
 */
jn.define('crm/timeline/item/activity/email', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class EmailActivity
	 */
	class EmailActivity extends TimelineItemBase
	{}

	module.exports = { EmailActivity };
});
