/**
 * @module calendar/event-list-view/layout
 */
jn.define('calendar/event-list-view/layout', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');

	const { CalendarHeader } = require('calendar/event-list-view/layout/calendar-header');
	const { CalendarGrid } = require('calendar/event-list-view/layout/calendar-grid');
	const { SearchHeader } = require('calendar/event-list-view/layout/search-header');
	const { InvitesBanner } = require('calendar/event-list-view/layout/invites-banner');
	const { EventList } = require('calendar/event-list-view/layout/event-list');
	const { State } = require('calendar/event-list-view/state');

	/**
	 * @class Layout
	 */
	class Layout extends LayoutComponent
	{
		render()
		{
			return Box(
				{
					style: {
						flex: 1,
					},
					safeArea: {
						bottom: State.isBaseViewMode,
					},
				},
				this.renderSearchHeader(),
				this.renderCalendarHeader(),
				this.renderCalendarView(),
				this.renderInvitesBanner(),
				this.renderEventList(),
			);
		}

		renderCalendarHeader()
		{
			return new CalendarHeader();
		}

		renderCalendarView()
		{
			return new CalendarGrid({
				layout: this.props.layout,
			});
		}

		renderInvitesBanner()
		{
			return new InvitesBanner();
		}

		renderSearchHeader()
		{
			return new SearchHeader();
		}

		renderEventList()
		{
			return new EventList({
				layout: this.props.layout,
				floatingActionButtonRef: this.props.floatingActionButtonRef,
			});
		}
	}

	module.exports = { Layout };
});
