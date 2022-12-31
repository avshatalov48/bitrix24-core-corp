/**
 * @module crm/timeline/item/activity/call
 */
jn.define('crm/timeline/item/activity/call', (require, exports, module) => {

	const { TimelineItemBase } = require('crm/timeline/item/base');

    /**
     * @class CallActivity
     */
    class CallActivity extends TimelineItemBase
    {
		get hasPlayer()
		{
			return this.layoutSchema.body.blocks.audio;
		}
    }

    module.exports = { CallActivity };

});