/**
 * @module crm/timeline/stream/scheduled
 */
jn.define('crm/timeline/stream/scheduled', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { TimelineStreamBase } = require('crm/timeline/stream/base');

	/**
	 * @class TimelineStreamScheduled
	 */
	class TimelineStreamScheduled extends TimelineStreamBase
	{
		getId()
		{
			return 'scheduled';
		}

		getItemSortDirection()
		{
			return 'asc';
		}

		makeItemModel(props)
		{
			const model = super.makeItemModel(props);
			model.isScheduled = true;

			return model;
		}

		/**
		 * @public
		 * @return {TimelineItemModel[]}
		 */
		getAttentionableItems()
		{
			return this.items.filter((item) => item.needsAttention || item.isIncomingChannel);
		}

		/**
		 * @public
		 * @return {TimelineItemModel[]}
		 */
		getNeedsAttentionItems()
		{
			return this.items.filter((item) => item.needsAttention);
		}

		/**
		 * @public
		 * @return {TimelineItemModel[]}
		 */
		getIncomingChannelItems()
		{
			return this.items.filter((item) => item.isIncomingChannel);
		}

		exportToListView()
		{
			if (this.items.length === 0 && !this.isEditable)
			{
				return [];
			}

			const result = [];

			result.push({
				type: 'Divider',
				key: 'Divider_scheduled',
				props: {
					color: AppTheme.colors.accentMainSuccess,
					text: Loc.getMessage('CRM_TIMELINE_SCHEDULED_TITLE2'),
				},
			});

			if (this.items.length === 0)
			{
				result.push({
					type: 'CreateReminder',
					key: 'CreateReminder',
					props: {},
				});
			}

			return [
				...result,
				...this.exportCollapsibleGroup(this.items),
			];
		}
	}

	module.exports = { TimelineStreamScheduled };
});
