/**
 * @module crm/timeline/item/custom-types/openline-chat
 */
jn.define('crm/timeline/item/custom-types/openline-chat', (require, exports, module) => {
	const { TimelineItemBase } = require('crm/timeline/item/base');
	const { Loc } = require('loc');

	/**
   * @class OpenlineChat
   */
	class OpenlineChat extends TimelineItemBase
	{
		getActivityConfirmationParams()
		{
			return {
				...super.getActivityConfirmationParams(),
				description: Loc.getMessage('M_CRM_TIMELINE_ITEM_ACTIVITY_OPENLINE_COMPLETE_CONF_DESCRIPTION'),
			};
		}
	}

	module.exports = { OpenlineChat };
});
