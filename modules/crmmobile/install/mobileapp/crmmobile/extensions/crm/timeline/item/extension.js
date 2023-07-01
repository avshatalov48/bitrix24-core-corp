/**
 * @module crm/timeline/item
 */
jn.define('crm/timeline/item', (require, exports, module) => {
	const { TimelineItemFactory } = require('crm/timeline/item/factory');
	const { TimelineItemModel } = require('crm/timeline/item/model');

	module.exports = {
		TimelineItemFactory,
		TimelineItemModel,
	};
});
