/**
 * @module crm/timeline/item/compatible
 */
jn.define('crm/timeline/item/compatible', (require, exports, module) => {
	const { Loc } = require('loc');
	const { TimelineItemBase } = require('crm/timeline/item/base');
	const { Banner } = require('crm/timeline/ui/banner');

	/**
	 * @class TimelineItemCompatible
	 */
	class TimelineItemCompatible extends TimelineItemBase
	{
		render()
		{
			return Banner({
				title: Loc.getMessage('CRM_TIMELINE_ITEM_NOT_SUPPORTED_TITLE'),
				description: Loc.getMessage('CRM_TIMELINE_ITEM_NOT_SUPPORTED_DESCRIPTION'),
				style: {
					backgroundColor: this.backgroundColor,
					marginBottom: 16,
					opacity: 0.6,
				},
			});
		}
	}

	module.exports = { TimelineItemCompatible };
});
