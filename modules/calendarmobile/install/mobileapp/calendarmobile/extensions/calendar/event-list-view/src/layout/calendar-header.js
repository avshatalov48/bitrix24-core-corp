/**
 * @module calendar/event-list-view/layout/calendar-header
 */
jn.define('calendar/event-list-view/layout/calendar-header', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { H4 } = require('ui-system/typography/heading');

	const { DateHelper } = require('calendar/date-helper');

	const { State, observeState } = require('calendar/event-list-view/state');

	class CalendarHeader extends LayoutComponent
	{
		get selectedDate()
		{
			return this.props.selectedDate;
		}

		get month()
		{
			const monthName = DateHelper.getMonthName(this.selectedDate);

			return monthName.charAt(0).toUpperCase() + monthName.slice(1);
		}

		get year()
		{
			return this.selectedDate.getFullYear().toString();
		}

		render()
		{
			return View(
				{},
				State.isTabsViewMode && !this.props.isHidden && this.renderContent(),
			);
		}

		renderContent()
		{
			return View(
				{
					testId: 'calendar_header',
					style: {
						paddingTop: Indent.M.toNumber(),
						paddingBottom: Indent.S.toNumber(),
						paddingHorizontal: Indent.XL3.toNumber(),
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
						},
					},
					H4({
						text: this.month,
						color: Color.base1,
						style: {
							paddingRight: Indent.S.toNumber(),
						},
					}),
					H4({
						text: this.year,
						color: Color.base4,
					}),
				),
			);
		}
	}

	const mapStateToProps = (state) => ({
		isHidden: state.isSearchMode && !state.invitesSelected,
		selectedDate: state.selectedDate,
	});

	module.exports = { CalendarHeader: observeState(CalendarHeader, mapStateToProps) };
});
