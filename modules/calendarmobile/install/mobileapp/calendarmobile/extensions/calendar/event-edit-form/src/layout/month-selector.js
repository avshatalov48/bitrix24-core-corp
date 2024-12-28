/**
 * @module calendar/event-edit-form/layout/month-selector
 */
jn.define('calendar/event-edit-form/layout/month-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Link4, LinkDesign, Icon } = require('ui-system/blocks/link');

	const { DateHelper } = require('calendar/date-helper');

	/**
	 * @class MonthSelector
	 */
	class MonthSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const todayMonthCode = DateHelper.getMonthCode(new Date());

			this.state = {
				monthCode: props.monthCode ?? todayMonthCode,
			};

			this.openMonthSelector = this.openMonthSelector.bind(this);
		}

		setMonthCode(monthCode)
		{
			this.setState({ monthCode });
		}

		render()
		{
			return Link4({
				testId: 'calendar-event-edit-form-month-selector-link',
				design: LinkDesign.BLACK,
				rightIcon: Icon.CHEVRON_DOWN,
				text: DateHelper.formatMonthCode(this.state.monthCode),
				onClick: this.openMonthSelector,
			});
		}

		openMonthSelector()
		{
			dialogs.showPicker({
				title: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_MONTH_PICKER_TITLE'),
				items: this.getMonthsPickerItems(),
				defaultValue: this.state.monthCode,
			}, (event, item) => {
				if (event === 'onPick')
				{
					const monthCode = item.value;

					this.props.onPick?.(monthCode);

					this.setState({ monthCode });
				}
			});
		}

		getMonthsPickerItems()
		{
			const date = new Date();
			const windowFrom = 0;
			const windowTo = 12;

			const items = [];
			for (let i = windowFrom; i <= windowTo; i++)
			{
				const monthDate = new Date(date.getFullYear(), date.getMonth() + i, 1);
				items.push(this.getMonthPickerItem(monthDate));
			}

			const selectedMonthDate = DateHelper.getDateFromMonthCode(this.state.monthCode);
			const minDate = new Date(date.getFullYear(), date.getMonth() + windowFrom);
			const maxDate = new Date(date.getFullYear(), date.getMonth() + windowTo);
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

	module.exports = { MonthSelector };
});
