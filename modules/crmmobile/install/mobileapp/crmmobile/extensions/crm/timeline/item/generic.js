/**
 * @module crm/timeline/item/generic
 */
jn.define('crm/timeline/item/generic', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class GenericTimelineItem
	 */
	class GenericTimelineItem extends TimelineItemBase
	{}

	module.exports = { GenericTimelineItem };
});
