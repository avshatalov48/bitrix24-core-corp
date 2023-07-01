/**
 * @module crm/timeline/item/activity/creation
 */
jn.define('crm/timeline/item/activity/creation', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class CreationActivity
	 */
	class CreationActivity extends TimelineItemBase
	{}

	module.exports = { CreationActivity };
});
