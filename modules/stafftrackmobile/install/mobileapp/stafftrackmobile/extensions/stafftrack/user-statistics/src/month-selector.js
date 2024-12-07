/**
 * @module stafftrack/user-statistics/month-selector
 */
jn.define('stafftrack/user-statistics/month-selector', (require, exports, module) => {
	const { DateHelper } = require('stafftrack/date-helper');
	const { MonthPicker } = require('stafftrack/month-picker');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');

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
			return ChipButton({
				design: ChipButtonDesign.GREY,
				mode: ChipButtonMode.OUTLINE,
				dropdown: true,
				compact: true,
				text: DateHelper.formatMonthCode(this.state.monthCode),
				onClick: this.openMonthSelector,
				testId: 'stafftrack-user-statistics-month-selector',
			});
		}

		openMonthSelector()
		{
			const { monthCode } = this.state;

			new MonthPicker({
				monthCode,
				onPick: (item) => {
					const monthCode = item.value;

					this.props.onPick(monthCode);

					this.setState({ monthCode });
				},
			}).showPicker();
		}
	}

	module.exports = { MonthSelector };
});