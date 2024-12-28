/**
 * @module calendar/event-list-view
 */
jn.define('calendar/event-list-view', (require, exports, module) => {
	const { Type } = require('type');
	const { FloatingActionButton } = require('ui-system/form/buttons/floating-action-button');
	const { tariffPlanRestrictionsReady } = require('tariff-plan-restriction');
	const { Icon } = require('ui-system/blocks/icon');

	const { Sharing, SharingContext } = require('calendar/sharing');
	const { EventManager } = require('calendar/data-managers/event-manager');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { LocationManager } = require('calendar/data-managers/location-manager');
	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { SyncManager } = require('calendar/data-managers/sync-manager');
	const { CollabManager } = require('calendar/data-managers/collab-manager');
	const { UserManager } = require('calendar/data-managers/user-manager');
	const { MoreMenu } = require('calendar/event-list-view/more-menu');
	const { SharingButton } = require('calendar/event-list-view/sharing-button');
	const { EventEditForm } = require('calendar/event-edit-form');
	const { Layout } = require('calendar/event-list-view/layout');
	const { State } = require('calendar/event-list-view/state');
	const { CalendarType, EventMeetingStatus, PullCommand } = require('calendar/enums');
	const { DateHelper } = require('calendar/date-helper');

	const isAndroid = Application.getPlatform() === 'android';

	/**
	 * @class CalendarEventListView
	 */
	class CalendarEventListView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.timezoneOffset = isAndroid ? 0 : DateHelper.timezoneOffset;
			this.floatingActionButtonRef = null;

			this.initManagers();
			this.initState();
			this.initRightMenuButtons();
			this.initFloatingButton();
		}

		get readOnly()
		{
			return this.props.readOnly;
		}

		initManagers()
		{
			SectionManager.setSections(this.props.sectionInfo);
			SectionManager.setCollabSections(this.props.collabSectionInfo);
			LocationManager.setLocations(this.props.locationInfo);
			LocationManager.setCategories(this.props.categoryInfo);
			SettingsManager.setSettings(this.props.settings);
			CollabManager.setCollabs(this.props.collabInfo);
			UserManager.addUsersToRedux(this.props.user);

			if (this.props.calType === CalendarType.USER && !env.extranet)
			{
				SyncManager.setSyncInfo(this.props.syncInfo);
			}
		}

		initState()
		{
			State.init({
				calType: this.props.calType,
				ownerId: this.props.ownerId,
				counters: this.props.counters,
				showDeclined: SettingsManager.isShowDeclinedEnabled(),
				showWeekNumbers: SettingsManager.isShowWeekNumbersEnabled(),
				denyBusyInvitation: SettingsManager.isDenyBusyInvitationEnabled(),
			});
		}

		initRightMenuButtons()
		{
			this.sharingButton = null;
			if (State.calType === CalendarType.USER && !env.extranet)
			{
				const sharing = new Sharing({
					sharingInfo: this.props.sharingInfo,
					type: SharingContext.CALENDAR,
				});

				this.sharingButton = new SharingButton({
					sharing,
					layout: this.props.layout,
					onSharing: () => this.initRightMenu(),
				});
			}

			this.moreMenu = new MoreMenu({
				layout: this.props.layout,
				counters: this.props.counters,
			});
		}

		componentDidMount()
		{
			this.bindEvents();
			this.pullSubscribe();

			const preloads = [
				tariffPlanRestrictionsReady(),
			];

			Promise.allSettled(preloads)
				.then(() => this.initRightMenu())
				.catch((errors) => console.error(errors))
			;

			// eslint-disable-next-line promise/catch-or-return
			EventManager.init().then(() => State.setIsLoading(false));
		}

		componentWillUnmount()
		{
			this.pullUnsubscribe?.();

			this.unbindEvents();
		}

		bindEvents()
		{
			BX.addCustomEvent('Calendar.EventListView::onSearch', this.onSearch);
			BX.addCustomEvent('Calendar.SyncPage::onSetSectionStatus', this.onSetSectionStatus);
			BX.addCustomEvent('Calendar.Sync::onSyncStatusChanged', this.onSyncStatusChanged);
		}

		unbindEvents()
		{
			BX.removeCustomEvent('Calendar.EventListView::onSearch', this.onSearch);
			BX.removeCustomEvent('Calendar.SyncPage::onSetSectionStatus', this.onSetSectionStatus);
			BX.removeCustomEvent('Calendar.Sync::onSyncStatusChanged', this.onSyncStatusChanged);
		}

		pullSubscribe()
		{
			this.pullUnsubscribe = BX.PULL.subscribe({
				moduleId: 'calendar',
				callback: (data) => {
					const command = BX.prop.getString(data, 'command', '');

					switch (command)
					{
						case PullCommand.SET_MEETING_STATUS:
							this.onMeetingStatusChange(data.params.fields);
							break;
						case PullCommand.EDIT_EVENT:
							this.onEventEdit(data.params.fields);
							break;
						case PullCommand.DELETE_EVENT:
							EventManager.handlePullEventDelete(data.params.fields);
							break;
						case PullCommand.EDIT_SECTION:
							SectionManager.handlePull(data);
							break;
						case PullCommand.DELETE_SECTION:
							SectionManager.handlePull(data);
							void EventManager.refresh();
							break;
						case PullCommand.REFRESH_SYNC_STATUS:
						case PullCommand.DELETE_SYNC_CONNECTION:
							SyncManager.handlePull(data);
							break;
						case PullCommand.HANDLE_SUCCESSFUL_CONNECTION:
							SectionManager.refresh(State.ownerId, State.calType, true);
							break;
						case PullCommand.UPDATE_USER_COUNTERS:
							this.updateUserCounter(data);
							break;
						case PullCommand.UPDATE_GROUP_COUNTERS:
							this.updateGroupCounter(data);
							break;
						default:
							break;
					}
				},
			});
		}

		initRightMenu()
		{
			const buttons = this.getMenuButtons();

			this.props.layout.setRightButtons(buttons);
		}

		getMenuButtons()
		{
			const buttons = [];

			buttons.push(
				this.getSearchButton(),
				this.sharingButton?.getButton(),
				this.moreMenu.getMenuButton(),
			);

			return buttons.filter(Boolean);
		}

		getSearchButton()
		{
			return {
				type: Icon.SEARCH.getIconName(),
				id: 'calendar-search',
				testId: 'calendar-search',
				accent: State.isSearchMode,
				callback: this.showSearch,
			};
		}

		initFloatingButton()
		{
			if (!this.readOnly)
			{
				this.floatingActionButtonRef = FloatingActionButton({
					testId: 'calendar_event_list_ADD_BTN',
					parentLayout: this.props.layout,
					onClick: this.handleFloatingButtonClick,
				});
			}
		}

		showSearch = () => {
			State.setIsSearchVisible(true);
		};

		onSearch = (params) => {
			this.initRightMenu();
		};

		onSetSectionStatus = (params) => {
			const sectionId = parseInt(params.sectionId, 10);
			if (!sectionId)
			{
				return;
			}

			const section = SectionManager.getSection(sectionId);

			if (section)
			{
				section.setSectionStatus(params.status);
				void EventManager.refresh();
			}
		};

		onMeetingStatusChange(fields)
		{
			const event = EventManager.handlePullMeetingStatusChanges(fields);

			if (!event)
			{
				return;
			}

			const eventId = Number(event.id);
			if (State.isInvitationMode)
			{
				const filterResultIds = State.filterResultIds.filter((id) => id !== eventId);
				State.setFilterResultIds(filterResultIds);
			}
		}

		async onEventEdit(fields)
		{
			const event = await EventManager.handlePullEventChanges(fields, State.ownerId, State.calType);

			if (!event)
			{
				return;
			}

			if (State.isInvitationMode && event.meetingStatus === EventMeetingStatus.QUESTIONED)
			{
				const filterResultIds = State.filterResultIds;
				filterResultIds.push(event.id);
				State.setFilterResultIds(filterResultIds);
			}
		}

		onSyncStatusChanged = (params) => {
			this.initRightMenu();
		};

		updateUserCounter(data)
		{
			if (State.calType !== CalendarType.USER)
			{
				return;
			}

			const params = BX.prop.getObject(data, 'params', {});

			if (Type.isObject(params.counters))
			{
				const { counters } = params;
				State.setCounters(counters);
				this.moreMenu.setCounters(counters);
				this.initRightMenu();
			}
		}

		updateGroupCounter(data)
		{
			if (State.calType !== CalendarType.GROUP)
			{
				return;
			}

			const params = BX.prop.getObject(data, 'params', {});
			if (params?.groupId !== State.ownerId)
			{
				return;
			}

			if (Type.isObject(params.counters))
			{
				const { counters } = params;
				State.setCounters(counters);
			}
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				new Layout({
					layout: this.props.layout,
					floatingActionButtonRef: this.floatingActionButtonRef,
				}),
			);
		}

		handleFloatingButtonClick = () => {
			void EventEditForm.open({
				ownerId: State.ownerId,
				calType: State.calType,
				selectedDayTs: State.selectedDate.getTime(),
				parentLayout: this.props.layout,
				sectionId: SettingsManager.getMeetSectionId(),
				firstWeekday: SettingsManager.getFirstWeekday(),
				user: this.getUserForEditForm(),
			});
		};

		getUserForEditForm()
		{
			const { id, fullName, workPosition, isCollaber, isExtranet } = UserManager.getById(env.userId);

			return {
				id,
				workPosition,
				isCollaber,
				isExtranet,
				name: fullName,
			};
		}
	}

	module.exports = { CalendarEventListView };
});
