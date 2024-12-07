/**
 * @module calendar/event-list-view/layout/event-list
 */
jn.define('calendar/event-list-view/layout/event-list', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
	const { DateHelper } = require('calendar/date-helper');
	const { Icons } = require('calendar/layout/icons');
	const { DayLabel } = require('calendar/event-list-view/layout/day-label');
	const { Event } = require('calendar/event-list-view/layout/event');
	const { clone } = require('utils/object');
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { Color } = require('tokens');

	const EventListItemType = {
		TYPE_EVENT: 'event',
		TYPE_DAY_LABEL: 'day-label',
		TYPE_EMPTY_SPACE: 'empty-space',
	};

	/**
	 * @class EventListComponent
	 */
	class EventListComponent extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.eventManager = props.eventManager;

			this.state = {
				refreshing: true,
				events: [],
				search: false,
				invitations: false,
				searchByPreset: false,
			};

			this.listRef = null;
		}

		get isSearchMode()
		{
			return this.props.isSearchMode;
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				this.isFilledList() ? this.renderList() : this.renderEmptyState(),
			);
		}

		renderList()
		{
			const items = this.prepareItemsForList(this.state.events);

			return ListView({
				ref: (ref) => {
					this.listRef = ref;
				},
				data: [{ items }],
				style: {
					flexDirection: 'column',
					flexGrow: 1,
				},
				onScrollBeginDrag: () => this.props.onScroll(),
				renderItem: (props) => {
					return View(
						{},
						this.renderItemContent(props),
					);
				},
			});
		}

		renderItemContent(props)
		{
			switch (props.type)
			{
				case EventListItemType.TYPE_DAY_LABEL:
					return DayLabel(props);
				case EventListItemType.TYPE_EVENT:
					return this.renderEvent(props);
				case EventListItemType.TYPE_EMPTY_SPACE:
					return this.renderEmptySpace();
				default:
					return null;
			}
		}

		renderEvent(props)
		{
			const event = this.eventManager.getByUniqueId(props.eventUniqueId);

			if (!event)
			{
				return null;
			}

			return Event({
				event,
				isSearch: this.isSearchMode,
				dayCode: props.dayCode,
				isLongWithTime: props.isLongWithTime,
				isFullDay: props.isFullDay,
				isUntil: props.isUntil,
				onClick: this.props.onItemSelected,
			});
		}

		renderEmptySpace()
		{
			return View(
				{
					style: {
						marginTop: 12,
						marginBottom: 13,
					},
				},
				Text(
					{
						text: ' ',
					},
				),
			);
		}

		renderEmptyState()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						flex: 1,
						paddingHorizontal: 20,
						marginBottom: 15,
						opacity: this.state.searchByPreset ? 0 : 1,
					},
					onClick: () => {
						this.props.onEmptyStateClick();
					},
					ref: (ref) => {
						if (this.state.searchByPreset)
						{
							ref?.animate({ duration: 200, opacity: 1, delay: 500 });
						}
					},
					testId: 'calendar_event_list_empty_state',
				},
				!this.state.search && !this.state.searchByPreset && this.renderEmptyEventsState(),
				this.state.search && this.renderEmptySearchState(),
				this.state.searchByPreset && this.renderEmptyPresetState(),
			);
		}

		renderEmptyEventsState()
		{
			return View(
				{},
				Text(
					{
						text: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_TITLE'),
						style: {
							fontSize: 20,
							fontWeight: '600',
						},
						testId: 'calendar_event_list_empty_state_events',
					},
				),
			);
		}

		renderEmptySearchState()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
					},
					testId: 'calendar_event_list_empty_state_search',
				},
				Text({
					style: {
						fontSize: 20,
						fontWeight: '400',
						color: AppTheme.colors.base2,
						textAlign: 'center',
					},
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TITLE'),
				}),
				Text({
					style: {
						fontSize: 16,
						fontWeight: '400',
						marginTop: 20,
						color: AppTheme.colors.base2,
						textAlign: 'center',
					},
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TEXT'),
				}),
			);
		}

		renderEmptyPresetState()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
					},
					testId: 'calendar_event_list_empty_state_preset',
				},
				Image({
					style: {
						width: 158,
						height: 129,
					},
					svg: {
						content: Icons.calendarEmpty,
					},
				}),
				Text({
					style: {
						fontSize: 18,
						fontWeight: '400',
						marginTop: 20,
						color: AppTheme.colors.base2,
						textAlign: 'center',
					},
					text: this.state.invitations
						? Loc.getMessage('M_CALENDAR_EVENT_LIST_NO_INVITATIONS_TITLE')
						: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TITLE')
					,
				}),
			);
		}

		setFilterResult(events, searchByPreset, invitations)
		{
			// eslint-disable-next-line no-param-reassign
			events = this.prepareItemsForList(events, false);

			this.setState({
				events,
				refreshing: false,
				search: !searchByPreset,
				searchByPreset,
				invitations,
			}, () => {
				this.listRef?.scrollToBegin(true);
			});
		}

		getEventsForDay(day)
		{
			const dayCode = DateHelper.getDayCode(day);

			const events = this.prepareItemsForList(this.eventManager.getEventsByDay(dayCode));

			this.setState({
				events,
				refreshing: false,
				search: false,
				invitations: false,
			}, () => {
				this.listRef?.scrollToBegin(true);
			});
		}

		prepareItemsForList(items, addEmpty = true)
		{
			const result = clone(items);

			if (addEmpty && Type.isArrayFilled(result))
			{
				result.push({
					type: EventListItemType.TYPE_EMPTY_SPACE,
					// eslint-disable-next-line no-undef
					key: Random.getString(3),
				});
			}

			return result.map((item) => {
				return {
					...item,
					// eslint-disable-next-line no-undef
					key: Random.getString(3),
				};
			});
		}

		isFilledList()
		{
			const { events } = this.state;

			return Type.isArrayFilled(events);
		}
	}

	module.exports = { EventListComponent, EventListItemType };
});
