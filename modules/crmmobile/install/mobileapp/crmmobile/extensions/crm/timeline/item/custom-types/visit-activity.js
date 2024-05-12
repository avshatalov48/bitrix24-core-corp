/**
 * @module crm/timeline/item/custom-types/visit-activity
 */
jn.define('crm/timeline/item/custom-types/visit-activity', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class VisitActivity
	 */
	class VisitActivity extends TimelineItemBase
	{
		get hasPlayer()
		{
			return BX.prop.getBoolean(this.layoutSchema.body.blocks, 'audio', false);
		}
	}

	module.exports = { VisitActivity };
});
