/**
 * @module stafftrack/month-picker
 */
jn.define('stafftrack/month-picker', (require, exports, module) => {
	const { Loc } = require('loc');
	const { DateHelper } = require('stafftrack/date-helper');

	class MonthPicker
	{
		constructor(props = {})
		{
			this.props = props;
		}

		showPicker()
		{
			dialogs.showPicker({
				title: Loc.getMessage('M_STAFFTRACK_MONTH_PICKER_SELECT_MONTH'),
				items: this.getMonthsPickerItems(),
				defaultValue: this.props.monthCode,
			}, (event, item) => {
				if (event === 'onPick')
				{
					this.props.onPick?.(item);
				}
			});
		}

		getMonthsPickerItems()
		{
			const date = new Date();
			const windowFrom = -6;
			const windowTo = 0;

			const items = [];
			for (let i = windowFrom; i <= windowTo; i++)
			{
				const monthDate = new Date(date.getFullYear(), date.getMonth() + i, 1);
				items.push(this.getMonthPickerItem(monthDate));
			}

			const selectedMonthDate = DateHelper.getDateFromMonthCode(this.props.monthCode);
			const minDate = new Date(date.getFullYear(), date.getMonth() + windowFrom, 1);
			const maxDate = new Date(date.getFullYear(), date.getMonth() + windowTo, 1);
			if (selectedMonthDate.getTime() < minDate.getTime())
			{
				items.shift(this.getMonthPickerItem(selectedMonthDate));
			}

			if (selectedMonthDate.getTime() > maxDate.getTime())
			{
				items.push(this.getMonthPickerItem(selectedMonthDate));
			}

			return items;
		}

		getMonthPickerItem(date)
		{
			return {
				value: String(DateHelper.getMonthCode(date)),
				name: String(DateHelper.formatMonthYear(date)),
			};
		}
	}

	module.exports = { MonthPicker };
});
