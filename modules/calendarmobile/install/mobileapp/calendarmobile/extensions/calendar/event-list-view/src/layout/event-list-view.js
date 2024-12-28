/**
 * @module calendar/event-list-view/layout/event-list-view
 */
jn.define('calendar/event-list-view/layout/event-list-view', (require, exports, module) => {
	const { Color, Indent } = require('tokens');

	const { DateHelper } = require('calendar/date-helper');
	const { DayLabel } = require('calendar/event-list-view/layout/day-label');
	const { Event } = require('calendar/event-list-view/layout/event');
	const { EventModel } = require('calendar/model/event');
	const { State } = require('calendar/event-list-view/state');

	const EventListItemType = {
		TYPE_EVENT: 'event',
		TYPE_DAY_LABEL: 'day-label',
		TYPE_BOTTOM_SAFE_SPACE: 'bottom-safe-space',
	};

	/**
	 * @class EventListView
	 */
	class EventListView
	{
		constructor(props)
		{
			this.props = props;

			this.listRef = null;
		}

		get items()
		{
			const addedDates = {};
			const items = [];

			if (State.isListMode)
			{
				const selectedDayCode = DateHelper.getDayCode(this.props.selectedDate);

				items.push({
					key: `${EventListItemType.TYPE_DAY_LABEL}.${selectedDayCode}`,
					type: EventListItemType.TYPE_DAY_LABEL,
					date: this.props.selectedDate.getTime(),
				});

				for (const event of this.props.events)
				{
					const dayCode = DateHelper.getDayCode(new Date(event.dateFromTs));

					items.push({
						key: `${EventListItemType.TYPE_EVENT}.${event.id}.${dayCode}`,
						type: EventListItemType.TYPE_EVENT,
						dayCode: selectedDayCode,
						...event,
					});
				}
			}
			else
			{
				for (const event of this.props.events)
				{
					const dayCode = DateHelper.getDayCode(new Date(event.dateFromTs));
					if (!addedDates[dayCode])
					{
						addedDates[dayCode] = dayCode;
						items.push({
							key: `${EventListItemType.TYPE_DAY_LABEL}.${dayCode}`,
							type: EventListItemType.TYPE_DAY_LABEL,
							date: event.dateFromTs,
						});
					}

					items.push({
						key: `${EventListItemType.TYPE_EVENT}.${event.id}.${dayCode}`,
						type: EventListItemType.TYPE_EVENT,
						dayCode,
						...event,
					});
				}
			}

			items.push({
				key: `${EventListItemType.TYPE_BOTTOM_SAFE_SPACE}.${Date.now()}`,
				type: EventListItemType.TYPE_BOTTOM_SAFE_SPACE,
			});

			return items;
		}

		render()
		{
			return ListView({
				data: [{ items: this.items }],
				onScrollBeginDrag: this.props.onScroll,
				isScrollBarEnabled: false,
				renderItem: this.renderItemContent,
				style: {
					flex: 1,
					backgroundColor: State.isSearchMode ? Color.bgContentPrimary.toHex() : Color.bgContentSecondary.toHex(),
				},
				ref: this.#bindListRef,
			});
		}

		#bindListRef = (ref) => {
			this.listRef = ref;
		};

		scrollToBegin(animation)
		{
			this.listRef?.scrollToBegin(animation);
		}

		renderItemContent = (props) => {
			switch (props.type)
			{
				case EventListItemType.TYPE_DAY_LABEL:
					return DayLabel(props);
				case EventListItemType.TYPE_EVENT:
					return this.renderEvent(props);
				case EventListItemType.TYPE_BOTTOM_SAFE_SPACE:
					return this.renderBottomSafeSpace();
				default:
					return null;
			}
		};

		renderEvent(props)
		{
			const event = EventModel.fromReduxModel(props);

			if (!event)
			{
				return null;
			}

			return Event({
				event,
				dayCode: props.dayCode,
				layout: this.props.layout,
			});
		}

		renderBottomSafeSpace()
		{
			return View(
				{
					style: {
						paddingBottom: 58 + Indent.L.toNumber() * 2,
						backgroundColor: State.isSearchMode ? Color.bgContentPrimary.toHex() : Color.bgContentSecondary.toHex(),
					},
				},
			);
		}
	}

	module.exports = { EventListView: (props) => new EventListView(props).render() };
});
