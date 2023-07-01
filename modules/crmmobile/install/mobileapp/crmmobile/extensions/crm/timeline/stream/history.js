/**
 * @module crm/timeline/stream/history
 */
jn.define('crm/timeline/stream/history', (require, exports, module) => {
	const { TimelineStreamBase } = require('crm/timeline/stream/base');

	/**
	 * @class TimelineStreamHistory
	 */
	class TimelineStreamHistory extends TimelineStreamBase
	{
		getId()
		{
			return 'history';
		}

		getItemSortDirection()
		{
			return 'desc';
		}

		exportToListView()
		{
			let result = [];

			const groupedItems = {};

			this.items.forEach((item) => {
				const deadline = item.deadline;
				if (deadline)
				{
					const key = deadline.date.toDateString();

					if (groupedItems[key])
					{
						groupedItems[key].push(item);
					}
					else
					{
						groupedItems[key] = [item];
					}
				}
				else
				{
					console.warn('Timeline item has no date', item);
				}
			});

			result.push({
				type: 'StickyDateBegin',
				key: 'StickyDateBegin',
				props: {},
			});

			Object.keys(groupedItems).forEach((groupDate) => {
				result.push({
					type: 'DateDivider',
					key: `DateDivider_${groupDate}`,
					props: {
						date: groupDate,
					},
				});

				result = [
					...result,
					...this.exportCollapsibleGroup(groupedItems[groupDate]),
				];
			});

			return result;
		}
	}

	module.exports = { TimelineStreamHistory };
});
