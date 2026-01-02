/**
 * @module calendar/event-list-view
 */
jn.define('calendar/event-list-view', (require, exports, module) => {
	const { Type } = require('type');
	const { FloatingActionButton } = require('ui-system/form/buttons/floating-action-button');
	const { tariffPlanRestrictionsReady } = require('tariff-plan-restriction');
	const { Tourist } = require('tourist');

	const { Sharing, SharingContext } = require('calendar/sharing');
	const { EventManager } = require('calendar/data-managers/event-manager');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { LocationManager } = require('calendar/data-managers/location-manager');
	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { SyncManager } = require('calendar/data-managers/sync-manager');
	const { CollabManager } = require('calendar/data-managers/collab-manager');
	const { UserManager } = require('calendar/data-managers/user-manager');
	const { MoreMenu } = require('calendar/event-list-view/more-menu');
	const { SearchLayout } = require('calendar/event-list-view/search/layout');
	const { SharingButton } = require('calendar/event-list-view/sharing-button');
	const { EventEditForm } = require('calendar/event-edit-form');
	const { Layout } = require('calendar/event-list-view/layout');
	const { State } = require('calendar/event-list-view/state');
	const { NewMenu, SyncError } = require('calendar/aha-moments-manager');
	const {
		CalendarType,
		EventMeetingStatus,
		PullCommand,
		AhaMomentEvent,
	} = require('calendar/enums');

	/**
	 * @class CalendarEventListView
	 */
	class CalendarEventListView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.floatingActionButtonRef = null;

			this.syncErrorAhaShowed = false;

			this.search = null;
			this.sharingButton = null;
			this.moreMenu = null;

			this.initManagers();
			this.initState();
			this.initRightMenuButtons();
			this.initFloatingButton();
		}

		get readOnly()
		{
			return this.props.readOnly;
		}

		get layout()
		{
			return this.props.layout;
		}

		initManagers()
		{
			SectionManager.setSections(this.props.sectionInfo);
			SectionManager.setCollabSections(this.props.collabSectionInfo);
			SectionManager.setTrackingUserList(this.props.additionalSectionInfo.trackingUserList);
			SectionManager.setHiddenSections(this.props.additionalSectionInfo.hiddenSections);
			LocationManager.setLocations(this.props.locationInfo);
			LocationManager.setCategories(this.props.categoryInfo);
			SettingsManager.setSettings(this.props.settings);
			CollabManager.setCollabs(this.props.collabInfo);
			UserManager.addUsersToRedux(this.props.user);

			if (
				this.props.calType === CalendarType.USER
				&& Number(this.props.ownerId) === Number(env.userId)
				&& !env.extranet
			)
			{
				SyncManager.setSyncInfo(this.props.syncInfo);
			}
		}

		initState()
		{
			State.init({
				calType: this.props.calType,
				ownerId: this.props.ownerId,
				viewMode: this.props.viewMode,
				counters: this.props.counters,
				hiddenSections: SectionManager.getHiddenSections(),
				showDeclined: SettingsManager.isShowDeclinedEnabled(),
				showWeekNumbers: SettingsManager.isShowWeekNumbersEnabled(),
				denyBusyInvitation: SettingsManager.isDenyBusyInvitationEnabled(),
			});
		}

		initRightMenuButtons()
		{
			if (this.shouldShowSharingButton())
			{
				const sharing = new Sharing({
					sharingInfo: this.props.sharingInfo,
					type: SharingContext.CALENDAR,
				});

				this.sharingButton = new SharingButton({
					sharing,
					layout: this.layout,
					onSharing: () => this.initRightMenu(),
				});
			}

			if (this.shouldShowSearchButton())
			{
				this.search = new SearchLayout({
					layout: this.props.layout,
					filterPresets: this.props.filterPresets,
				});
			}

			this.moreMenu = new MoreMenu({
				targetElementRef: null,
				layoutWidget: this.layout,
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
				.then(() => {
					this.initRightMenu();
					this.handleAhaMoments();
				})
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
			BX.addCustomEvent('Calendar.EventEditForm::onAfterEventSave', this.onAfterEventSave);
		}

		unbindEvents()
		{
			BX.removeCustomEvent('Calendar.EventListView::onSearch', this.onSearch);
			BX.removeCustomEvent('Calendar.SyncPage::onSetSectionStatus', this.onSetSectionStatus);
			BX.removeCustomEvent('Calendar.Sync::onSyncStatusChanged', this.onSyncStatusChanged);
			BX.removeCustomEvent('Calendar.EventEditForm::onAfterEventSave', this.onAfterEventSave);
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
							void this.onEventEdit(data.params.fields);
							break;
						case PullCommand.DELETE_EVENT:
							EventManager.handlePullEventDelete(data.params.fields);
							break;
						case PullCommand.EDIT_SECTION:
						case PullCommand.DELETE_SECTION:
							SectionManager.handlePull(data);
							break;
						case PullCommand.HIDDEN_SECTIONS_UPDATED:
							this.hiddenSectionsUpdated(data.params.hiddenSections);
							break;
						case PullCommand.REFRESH_SYNC_STATUS:
						case PullCommand.DELETE_SYNC_CONNECTION:
							SyncManager.handlePull(data);
							break;
						case PullCommand.HANDLE_SUCCESSFUL_CONNECTION:
							void this.onHandleSuccessfulConnection();
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

		handleAhaMoments()
		{
			if (!env.isCollaber && Tourist.firstTime(AhaMomentEvent.NEW_MENU))
			{
				setTimeout(() => {
					NewMenu.show();

					void Tourist.remember(AhaMomentEvent.NEW_MENU);
				}, 500);
			}
			else if (!env.isCollaber && this.moreMenu.hasCountersValue() && Tourist.firstTime(AhaMomentEvent.SYNC_ERROR))
			{
				setTimeout(() => {
					SyncError.show(this.onHideSyncErrorAha);

					void Tourist.remember(AhaMomentEvent.SYNC_ERROR);
				}, 500);
			}
		}

		onHideSyncErrorAha = () => {
			if (!this.syncErrorAhaShowed)
			{
				SyncManager.openSyncPage();
				this.syncErrorAhaShowed = true;
			}
		};

		initRightMenu()
		{
			const buttons = this.getMenuButtons();

			this.layout.setRightButtons(buttons);
		}

		getMenuButtons()
		{
			const buttons = [];

			buttons.push(
				this.search?.getButton(),
				this.sharingButton?.getButton(),
				this.moreMenu.getMenuButton(),
			);

			return buttons.filter(Boolean);
		}

		initFloatingButton()
		{
			if (!this.readOnly)
			{
				this.floatingActionButtonRef = FloatingActionButton({
					testId: 'calendar_event_list_ADD_BTN',
					parentLayout: this.layout,
					onClick: this.handleFloatingButtonClick,
				});
			}
		}

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

		async onHandleSuccessfulConnection()
		{
			await SectionManager.refresh(State.ownerId, State.calType, true);
			void EventManager.refresh(true);
		}

		hiddenSectionsUpdated(hiddenSections)
		{
			SectionManager.setHiddenSections(hiddenSections);
			State.setHiddenSections(SectionManager.getHiddenSections());
		}

		onSyncStatusChanged = (params) => {
			this.initRightMenu();
		};

		onAfterEventSave = (params) => {
			const { section } = params.fields;
			const currentHidden = SectionManager.getHiddenSections();

			const isHidden = currentHidden.find((sectionId) => sectionId === section);

			if (isHidden)
			{
				const hiddenSections = currentHidden.filter((sectionId) => sectionId !== section);
				SectionManager.saveHiddenSections(State.ownerId, State.calType, hiddenSections);
				State.setHiddenSections(SectionManager.getHiddenSections());
			}
		};

		updateUserCounter(data)
		{
			if (
				State.calType !== CalendarType.USER
				&& State.ownerId !== Number(env.userId)
			)
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
				this.renderLayout(),
			);
		}

		renderLayout()
		{
			return new Layout({
				layout: this.layout,
				floatingActionButtonRef: this.floatingActionButtonRef,
			});
		}

		handleFloatingButtonClick = () => {
			const participantsEntityList = [Number(env.userId)];

			if (State.calType === CalendarType.USER && State.ownerId !== Number(env.userId))
			{
				participantsEntityList.push(State.ownerId);
			}

			void EventEditForm.open({
				participantsEntityList,
				ownerId: State.ownerId,
				calType: State.calType,
				selectedDayTs: State.selectedDate.getTime(),
				parentLayout: this.layout,
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

		shouldShowSharingButton()
		{
			return (
				State.calType === CalendarType.USER
				&& State.ownerId === Number(env.userId)
				&& !env.extranet
			);
		}

		shouldShowSearchButton()
		{
			return (State.calType === CalendarType.USER && State.ownerId === Number(env.userId))
				|| State.calType === CalendarType.GROUP
				|| State.calType === CalendarType.COMPANY_CALENDAR
			;
		}
	}

	module.exports = { CalendarEventListView };
});
