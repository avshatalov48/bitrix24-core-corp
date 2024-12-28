/**
 * @module calendar/event-list-view/layout
 */
jn.define('calendar/event-list-view/layout', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Box } = require('ui-system/layout/box');

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
					backgroundColor: State.isSearchMode ? Color.bgContentPrimary : Color.bgContentSecondary,
					style: {
						flex: 1,
					},
					safeArea: {
						bottom: true,
					},
				},
				this.renderSearchHeader(),
				this.renderCalendarView(),
				this.renderInvitesBanner(),
				this.renderEventList(),
			);
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
