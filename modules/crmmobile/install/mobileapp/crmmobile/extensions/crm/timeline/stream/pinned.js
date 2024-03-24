/**
 * @module crm/timeline/stream/pinned
 */
jn.define('crm/timeline/stream/pinned', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { TimelineStreamBase } = require('crm/timeline/stream/base');

	/**
	 * @class TimelineStreamPinned
	 */
	class TimelineStreamPinned extends TimelineStreamBase
	{
		getId()
		{
			return 'pinned';
		}

		getItemSortDirection()
		{
			return 'desc';
		}

		makeItemModel(props)
		{
			const model = super.makeItemModel(props);
			model.isPinned = true;

			return model;
		}

		exportToListView()
		{
			const result = [];

			if (this.items.length > 0)
			{
				result.push({
					type: 'Divider',
					key: 'Divider_pinned',
					props: {
						color: AppTheme.colors.bgSeparatorPrimary,
						text: Loc.getMessage('CRM_TIMELINE_PINNED_TITLE'),
						textColor: AppTheme.colors.base3,
					},
				});
			}

			return [
				...result,
				...this.exportCollapsibleGroup(this.items),
			];
		}
	}

	module.exports = { TimelineStreamPinned };
});
