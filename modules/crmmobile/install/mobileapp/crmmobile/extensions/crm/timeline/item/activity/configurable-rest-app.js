/**
 * @module crm/timeline/item/activity/configurable-rest-app
 */
jn.define('crm/timeline/item/activity/configurable-rest-app', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class ConfigurableRestAppActivity
	 */
	class ConfigurableRestAppActivity extends TimelineItemBase
	{}

	module.exports = { ConfigurableRestAppActivity };
});
