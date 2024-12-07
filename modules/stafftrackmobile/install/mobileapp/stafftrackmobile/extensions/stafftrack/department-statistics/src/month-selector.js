/**
 * @module stafftrack/department-statistics/month-selector
 */
jn.define('stafftrack/department-statistics/month-selector', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { H4, H5 } = require('ui-system/typography/heading');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { DateHelper } = require('stafftrack/date-helper');
	const { capitalize } = require('utils/string');
	const { MonthPicker } = require('stafftrack/month-picker');

	class MonthSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const todayMonthCode = DateHelper.getMonthCode(new Date());

			this.state = {
				monthCode: props.monthCode ?? todayMonthCode,
			};
		}

		render()
		{
			const date = DateHelper.getDateFromMonthCode(this.state.monthCode);

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					onClick: () => {
						new MonthPicker({
							monthCode: this.state.monthCode,
							onPick: (item) => {
								const monthCode = item.value;

								this.props.onPick(monthCode);

								this.setState({ monthCode });
							},
						}).showPicker();
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'baseline',
						},
					},
					H4({
						testId: 'stafftrack-department-statistics-month',
						text: capitalize(DateHelper.getMonthName(date)),
						color: Color.base1,
						style: {
							marginRight: Indent.XS.toNumber(),
						},
					}),
					H5({
						testId: 'stafftrack-department-statistics-year',
						text: date.getFullYear().toString(),
						color: Color.base3,
					}),
				),
				IconView({
					size: 20,
					icon: Icon.CHEVRON_DOWN,
					color: Color.base4,
				}),
			);
		}
	}

	module.exports = { MonthSelector };
});
