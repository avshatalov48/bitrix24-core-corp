/**
 * @module calendar/event-list-view/layout/event-list
 */
jn.define('calendar/event-list-view/layout/event-list', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { Color, Component } = require('tokens');
	const { StatusBlock } = require('ui-system/blocks/status-block');

	const { EventListView } = require('calendar/event-list-view/layout/event-list-view');
	const { ListSkeleton } = require('calendar/event-list-view/list-skeleton');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { EventManager } = require('calendar/data-managers/event-manager');
	const { Icons } = require('calendar/layout/icons');

	const { observeState } = require('calendar/event-list-view/state');

	const store = require('statemanager/redux/store');
	const {
		selectByIds,
		selectByDate,
	} = require('calendar/statemanager/redux/slices/events');

	/**
	 * @class EventList
	 */
	class EventList extends LayoutComponent
	{
		get isSearchMode()
		{
			return this.props.isSearchMode;
		}

		get isInvitationPresetEnabled()
		{
			return this.props.isInvitationPresetEnabled;
		}

		get layout()
		{
			return this.props.layout;
		}

		get floatingActionButtonRef()
		{
			return this.props.floatingActionButtonRef;
		}

		get events()
		{
			return this.props.events;
		}

		get selectedDate()
		{
			return this.props.selectedDate;
		}

		componentDidUpdate(prevProps, prevState)
		{
			super.componentDidUpdate(prevProps, prevState);

			if (!this.events || !this.floatingActionButtonRef)
			{
				return;
			}

			if (this.isSearchMode || this.isInvitationPresetEnabled)
			{
				this.floatingActionButtonRef?.hide();
			}
			else
			{
				this.floatingActionButtonRef?.setFloatingButton({
					hide: false,
					accentByDefault: !Type.isArrayFilled(this.events),
				});
			}
		}

		render()
		{
			return View(
				{
					testId: 'calendar-event-list',
					style: {
						flex: 1,
						paddingHorizontal: Component.areaPaddingLr.toNumber(),
						backgroundColor: this.isSearchMode ? Color.bgContentPrimary.toHex() : Color.bgContentSecondary.toHex(),
					},
				},
				this.props.isLoading && ListSkeleton(),
				!this.props.isLoading && Type.isArrayFilled(this.events) && this.renderList(),
				!this.props.isLoading && !Type.isArrayFilled(this.events) && this.renderEmptyState(),
			);
		}

		renderList()
		{
			return EventListView({
				events: this.events,
				onScroll: this.closeSearch,
				selectedDate: this.selectedDate,
				isSearchMode: this.isSearchMode,
				layout: this.layout,
			});
		}

		renderEmptyState()
		{
			return View(
				{
					style: {
						flex: 1,
						opacity: 0,
						justifyContent: 'center',
						alignItems: 'center',
					},
					onClick: this.closeSearch,
					testId: 'calendar-event-list-empty_state',
					ref: (ref) => ref?.animate({ duration: 200, opacity: 1 }),
				},
				StatusBlock({
					testId: 'calendar-event-list-empty_state-status',
					image: this.isSearchMode ? this.getEmptySearchImage() : this.getEmptyStateImage(),
					title: this.getEmptyStateTitle(),
					titleColor: this.isSearchMode ? Color.base1 : Color.base3,
					description: this.isSearchMode ? this.getEmptyStateDescription() : null,
					descriptionColor: this.isSearchMode ? Color.base2 : Color.base4,
				}),
			);
		}

		getEmptyStateImage()
		{
			return Image({
				style: {
					width: 108,
					height: 108,
				},
				svg: {
					content: Icons.eventListEmptyState,
				},
			});
		}

		getEmptySearchImage()
		{
			return Image({
				style: {
					width: 126,
					height: 105,
				},
				svg: {
					content: Icons.eventListEmptySearch,
				},
			});
		}

		getEmptyStateTitle()
		{
			let title = Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_TITLE');

			if (this.isSearchMode)
			{
				title = this.isInvitationPresetEnabled
					? Loc.getMessage('M_CALENDAR_EVENT_LIST_NO_INVITATIONS_TITLE')
					: Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TITLE')
				;
			}

			return title;
		}

		getEmptyStateDescription()
		{
			return Loc.getMessage('M_CALENDAR_EVENT_LIST_EMPTY_SEARCH_RESULT_TEXT');
		}

		closeSearch = () => this.layout?.search?.close();
	}

	const mapStateToProps = (state) => {
		const isSearchMode = state.isSearchMode || Type.isArrayFilled(state.filterResultIds);
		const sectionIds = SectionManager.getActiveSectionsIds().filter((sectionId) => {
			return !state.hiddenSections.includes(sectionId);
		});

		let events = isSearchMode
			? selectByIds(store.getState(), {
				ids: state.filterResultIds,
				parseRecursion: false,
			})
			: selectByDate(store.getState(), {
				sectionIds,
				date: state.selectedDate,
				showDeclined: state.showDeclined,
			})
		;

		if (!isSearchMode)
		{
			events = EventManager.prepareEventsForListView(events, state.calType, state.ownerId);
		}

		return {
			events: EventManager.sortEvents(events),
			selectedDate: state.selectedDate,
			isLoading: state.isLoading,
			isInvitationPresetEnabled: state.isInvitationPresetEnabled,
			isSearchMode: state.isSearchMode,
			showDeclined: state.showDeclined,
			hiddenSections: state.hiddenSections,
		};
	};

	module.exports = { EventList: observeState(EventList, mapStateToProps) };
});
