/**
 * @module crm/timeline/item/custom-types/call-activity
 */
jn.define('crm/timeline/item/custom-types/call-activity', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');

	/**
	 * @class CallActivity
	 */
	class CallActivity extends TimelineItemBase
	{
		get hasPlayer()
		{
			return BX.prop.getBoolean(this.layoutSchema.body.blocks, 'audio', false);
		}
	}

	module.exports = { CallActivity };
});
