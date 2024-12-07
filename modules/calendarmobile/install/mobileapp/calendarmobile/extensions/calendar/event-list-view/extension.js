/**
 * @module calendar/event-list-view
 */
jn.define('calendar/event-list-view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { isEqual } = require('utils/object');
	const { PureComponent } = require('layout/pure-component');
	const { DateHelper } = require('calendar/date-helper');
	const { BottomSheet } = require('bottom-sheet');
	const { DialogSharing } = require('calendar/layout/dialog/dialog-sharing');
	const { Loc } = require('loc');
	const { Sharing, SharingContext } = require('calendar/sharing');
	const { Filter } = require('calendar/event-list-view/filter');
	const { DayHeader } = require('calendar/event-list-view/day-header');
	const { SyncButton } = require('calendar/event-list-view/sync-button');
	const { EventManager } = require('calendar/event-manager');
	const { SectionManager } = require('calendar/section-manager');
	const { LocationManager } = require('calendar/location-manager');
	const { EventListComponent } = require('calendar/event-list-view/layout/event-list');
	const { getAhaMoment } = require('calendar/aha-moments-manager');
	const { magnifierWithMenuAndDot } = require('assets/common');
	const { Type } = require('type');
	const { CalendarGrid } = require('calendar/calendar-grid');
	const isAndroid = Application.getPlatform() === 'android';
	const { FloatingButtonComponent } = require('layout/ui/floating-button');
	const { FloatingActionButton, FloatingActionButtonSupportNative } = require('ui-system/form/buttons/floating-action-button');
	const { Color } = require('tokens');
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');

	/**
	 * @class CalendarEventListView
	 */
	class CalendarEventListView extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.layout = props.layout;
			this.layoutRightButtons = null;

			/** @type {Filter} */
			this.searchFilter = new Filter();

			this.state = {
				forceReload: false,
				lastSelectedDate: new Date(),
				isFilledEventList: true,
			};

			this.ahaMoments = props.ahaMoments;
			this.settings = props.settings;

			this.pullUnsubscribe = null;
			this.pullConfig = {
				commands: [
					'edit_event',
					'delete_event',
					'set_meeting_status',
					'edit_section',
					'delete_section',
					'refresh_sync_status',
					'delete_sync_connection',
					'handle_successful_connection',
				],
			};

			this.nowTime = new Date();
			this.timezoneOffset = isAndroid ? 0 : this.nowTime.getTimezoneOffset() * 60000;

			this.sharing = new Sharing({
				sharingInfo: props.sharingInfo,
				type: SharingContext.CALENDAR,
			});

			this.sectionManager = new SectionManager({
				sectionInfo: props.sectionInfo,
				onSectionsForceRefresh: () => {
					this.refreshEventManager(true);
				},
			});

			this.locationManager = new LocationManager({
				locationInfo: props.locationInfo,
			});

			this.eventManager = new EventManager({
				sectionManager: this.sectionManager,
				locationManager: this.locationManager,
				showDeclined: this.settings.showDeclined,
				onEventLoaded: () => {
					this.getEventsForDay();
					this.calendarRef?.setEvents(this.eventManager.getEventsMap());
				},
				onRefresh: () => {
					this.reload();
					this.calendarRef?.setEvents(this.eventManager.getEventsMap());
				},
			});

			this.syncButton = new SyncButton({
				syncInfo: props.syncInfo,
				onSyncStatusChanged: () => {
					this.initRightMenu();
				},
			});

			this.viewFormWidget = null;

			this.calendarRef = null;
			this.dayHeaderRef = null;
			this.eventListRef = null;
			this.floatingButtonRef = null;
			this.ahaSyncCalendarRef = null;
			this.ahaSyncErrorRef = null;

			this.handleFloatingButtonClick = this.handleFloatingButtonClick.bind(this);
			this.handleClose = this.handleClose.bind(this);
			this.onEventChanged = this.onEventChanged.bind(this);
			this.onEventDeleted = this.onEventDeleted.bind(this);
			this.onSearch = this.onSearch.bind(this);
			this.onSetSectionStatus = this.onSetSectionStatus.bind(this);
			this.showSearch = this.showSearch.bind(this);
			this.openSharingDialog = this.openSharingDialog.bind(this);
			this.openEventViewForm = this.openEventViewForm.bind(this);
			this.closeSearch = this.closeSearch.bind(this);

			this.initLayout();
		}

		componentDidMount()
		{
			this.showFloatingButton();
			this.handleAhaMoments();
			this.pullSubscribe();
			this.bindEvents();

			this.eventManager.init();
		}

		componentWillUnmount()
		{
			if (this.pullUnsubscribe)
			{
				this.pullUnsubscribe();
			}

			this.unbindEvents();
		}

		bindEvents()
		{
			BX.addCustomEvent('onCalendarEventChanged', this.onEventChanged);
			BX.addCustomEvent('onCalendarEventRemoved', this.onEventDeleted);
			BX.addCustomEvent('Calendar.EventListView::onSearch', this.onSearch);
			BX.addCustomEvent('Calendar.SyncPage::onSetSectionStatus', this.onSetSectionStatus);
		}

		unbindEvents()
		{
			BX.removeCustomEvent('onCalendarEventChanged', this.onEventChanged);
			BX.removeCustomEvent('onCalendarEventRemoved', this.onEventDeleted);
			BX.removeCustomEvent('Calendar.EventListView::onSearch', this.onSearch);
			BX.removeCustomEvent('Calendar.SyncPage::onSetSectionStatus', this.onSetSectionStatus);
		}

		pullSubscribe()
		{
			this.pullUnsubscribe = BX.PULL.subscribe({
				moduleId: 'calendar',
				callback: (data) => {
					const command = BX.prop.getString(data, 'command', '');

					if (!this.pullConfig.commands.includes(command))
					{
						return;
					}

					switch (command)
					{
						case 'edit_event':
						case 'delete_event':
						case 'set_meeting_status':
							this.refreshEventManager();
							break;
						case 'edit_section':
							this.sectionManager.handlePull(data);
							break;
						case 'delete_section':
							this.sectionManager.handlePull(data);
							this.refreshEventManager();
							break;
						case 'refresh_sync_status':
						case 'delete_sync_connection':
							this.syncButton.handlePull(data);
							break;
						case 'handle_successful_connection':
							this.sectionManager.refresh(true);
							break;
						default:
							break;
					}
				},
			});
		}

		initLayout()
		{
			this.setMonthTitle(this.nowTime);
			this.initRightMenu();
			this.initFloatingButton();
		}

		async initRightMenu()
		{
			const buttons = await this.getMenuButtons();

			if (!isEqual(buttons, this.layoutRightButtons))
			{
				this.layoutRightButtons = buttons;
				this.layout.setRightButtons(buttons);
			}
		}

		initFloatingButton()
		{
			if (!FloatingActionButtonSupportNative(this.layout))
			{
				return;
			}

			FloatingActionButton({
				testId: this.getFloatingButtonTestId(),
				parentLayout: this.layout,
				onClick: this.handleFloatingButtonClick,
			});
		}

		async getMenuButtons()
		{
			const buttons = [];
			const sharingButton = await this.getSharingButton();

			buttons.push(
				this.getSearchButton(),
				sharingButton,
				this.syncButton.getContent(),
			);

			return buttons;
		}

		getSearchButton()
		{
			return {
				type: 'search',
				svg: {
					content: magnifierWithMenuAndDot(
						AppTheme.colors.base4,
						this.searchFilter.isEmpty() ? null : AppTheme.colors.accentBrandBlue,
					),
				},
				callback: this.showSearch,
			};
		}

		async getSharingButton()
		{
			await tariffPlanRestrictionsReady();
			const { isRestricted } = getFeatureRestriction(this.sharing.getFeatureCode());

			return {
				svg: {
					content: this.sharing.isOn() && !isRestricted()
						? icons.menuCalendarColor
						: icons.menuCalendarGray,
				},
				type: 'options',
				badgeCode: 'sharing_categories_selector',
				callback: this.openSharingDialog,
			};
		}

		handleClose()
		{
			if (this.layout)
			{
				this.layout.close();
			}
		}

		refreshEventManager(force = false)
		{
			if (this.eventManager)
			{
				this.eventManager.refresh(force);
			}
		}

		onEventChanged(event)
		{
			this.closeViewForm();

			if (event && event.app_calendar_action)
			{
				this.refreshEventManager();
			}
		}

		onEventDeleted()
		{
			this.closeViewForm();
			this.refreshEventManager();
		}

		onSearch(params)
		{
			const { preset, text } = params;
			const presetId = preset ? preset.id : '';

			this.searchFilter.set({
				preset,
				presetId,
				search: text,
			});

			this.reload();
		}

		onSetSectionStatus(params)
		{
			const sectionId = parseInt(params.sectionId, 10);

			if (sectionId)
			{
				const section = this.sectionManager.getSection(sectionId);

				if (section)
				{
					section.setSectionStatus(params.status);
					this.refreshEventManager();
				}
			}
		}

		closeViewForm()
		{
			if (this.viewFormWidget)
			{
				this.viewFormWidget.close();
				this.viewFormWidget = null;
			}
		}

		showFloatingButton()
		{
			if (this.floatingButtonRef)
			{
				this.floatingButtonRef.show();
			}
		}

		handleAhaMoments()
		{
			if (this.ahaMoments && this.ahaMoments.syncCalendar && this.ahaSyncCalendarRef)
			{
				this.ahaSyncCalendarRef.actualize();
			}
			else if (this.ahaMoments && this.ahaMoments.syncError && this.ahaSyncErrorRef)
			{
				this.ahaSyncErrorRef.actualize();
			}
		}

		reload()
		{
			this.initRightMenu();

			if (this.searchFilter.isEmpty())
			{
				this.eventManager.setFilteredMode(false);
				this.setMonthTitle(this.state.lastSelectedDate);
				this.setState({
					isFilledEventList: true,
					forceReload: !this.state.forceReload,
				}, () => {
					this.getEventsForDay();
					this.showFloatingButton();
				});
			}
			else
			{
				this.eventManager.setFilteredMode(true);
				this.setSearchTitle();
				// eslint-disable-next-line promise/catch-or-return
				this.getEventsByFilter().then((events) => {
					this.setState({
						isFilledEventList: Type.isArrayFilled(events),
						forceReload: !this.state.forceReload,
					}, () => {
						if (this.eventListRef)
						{
							const searchByPreset = this.searchFilter.isSearchByPreset();
							const invitations = this.searchFilter.isInvitationPresetEnabled();
							this.eventListRef.setFilterResult(events, searchByPreset, invitations);
						}
					});
				});
			}
		}

		showSearch()
		{
			const { presetId, search } = this.searchFilter;
			this.searchFilter.setWasShown();

			BX.postComponentEvent('Calendar.EventListView::onSearchShow', [
				{
					presetId,
					search,
				},
			]);
		}

		async openSharingDialog()
		{
			await tariffPlanRestrictionsReady();
			const { isRestricted, showRestriction } = getFeatureRestriction(this.sharing.getFeatureCode());

			if (isRestricted())
			{
				showRestriction();
			}
			else
			{
				const component = (layoutWidget) => new DialogSharing({
					layoutWidget,
					sharing: this.sharing,
					onSharing: (fields) => {
						this.sharing.getModel().setFields(fields);
						this.initRightMenu();
					},
				});

				void new BottomSheet({ component })
					.setBackgroundColor(Color.bgNavigation.toHex())
					.setMediumPositionPercent(70)
					.disableContentSwipe()
					.open()
				;
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
				this.searchFilter.isEmpty() ? this.renderCalendarView() : this.renderSearchHeader(),
				this.searchFilter.isEmpty() && this.renderDayHeader(),
				this.renderContent(),
				this.renderFloatingButton(),
				this.renderSyncCalendarAhaMoment(),
				this.renderSyncErrorAhaMoment(),
			);
		}

		renderCalendarView()
		{
			return new CalendarGrid({
				initialDate: this.getInitialDate(),
				showWeekNumbers: this.settings.showWeekNumbers || false,
				firstWeekday: this.settings.firstWeekday || 2,
				onMonthSwitched: (timestamp) => {
					setTimeout(() => {
						const date = new Date(timestamp * 1000 + this.timezoneOffset);
						this.handleMountSwitch(date);
					}, 1);
				},
				onDateSelected: (timestamp) => {
					const date = new Date(timestamp * 1000 + this.timezoneOffset);
					this.handleDateSelected(date);
				},
				ref: (ref) => {
					this.calendarRef = ref;
					ref?.setEvents(this.eventManager.getEventsMap());
				},
			});
		}

		renderSearchHeader()
		{
			if (!this.state.isFilledEventList)
			{
				return null;
			}

			return View(
				{
					style: {
						marginTop: 10,
						marginHorizontal: 20,
						paddingBottom: 11,
						alignItems: 'flex-start',
						justifyContent: 'flex-start',
						borderBottomWidth: 0.5,
						borderBottomColor: Color.bgSeparatorPrimary.toHex(),
					},
				},
				Text({
					style: {
						fontSize: 18,
						fontWeight: '400',
						color: AppTheme.colors.base2,
					},
					ellipsize: 'end',
					numberOfLines: 1,
					text: this.getSearchTitle(),
				}),
			);
		}

		getSearchTitle()
		{
			if (this.searchFilter.isInvitationPresetEnabled())
			{
				return Loc.getMessage('M_CALENDAR_EVENT_LIST_INVITATION');
			}

			if (this.searchFilter.search !== '')
			{
				return Loc.getMessage('M_CALENDAR_EVENT_LIST_SEARCH_RESULT_BY_QUERY', {
					'#QUERY#': this.searchFilter.search,
				});
			}

			return Loc.getMessage('M_CALENDAR_EVENT_LIST_SEARCH_RESULT');
		}

		renderDayHeader()
		{
			return new DayHeader({
				testId: 'calendar_event_list_day_header',
				date: this.state.lastSelectedDate,
				ref: (ref) => {
					this.dayHeaderRef = ref;
				},
			});
		}

		renderContent()
		{
			return new EventListComponent({
				testId: 'calendar_event_list_component',
				isSearchMode: !this.searchFilter.isEmpty(),
				eventManager: this.eventManager,
				onItemSelected: this.openEventViewForm,
				onScroll: this.closeSearch,
				onEmptyStateClick: this.closeSearch,
				ref: (ref) => {
					this.eventListRef = ref;
				},
			});
		}

		renderFloatingButton()
		{
			return new FloatingButtonComponent({
				parentLayout: this.layout,
				testId: this.getFloatingButtonTestId(),
				position: { bottom: -100 },
				onClick: this.handleFloatingButtonClick,
				ref: (ref) => {
					this.floatingButtonRef = ref;
				},
			});
		}

		renderSyncCalendarAhaMoment()
		{
			if (getAhaMoment)
			{
				const SyncCalendar = getAhaMoment('syncCalendar');

				return new SyncCalendar({
					ref: (ref) => {
						this.ahaSyncCalendarRef = ref;
					},
					testId: 'calendar_event_list_sync_calendar_aha',
				});
			}

			return null;
		}

		renderSyncErrorAhaMoment()
		{
			if (getAhaMoment)
			{
				const SyncError = getAhaMoment('syncError');

				return new SyncError({
					ref: (ref) => {
						this.ahaSyncErrorRef = ref;
					},
					testId: 'calendar_event_list_sync_error_aha',
				});
			}

			return null;
		}

		handleFloatingButtonClick()
		{
			PageManager.openPage({
				url: '/mobile/calendar/edit_event.php',
				modal: true,
				data: {
					modal: 'Y',
				},
			});
		}

		openEventViewForm(eventData)
		{
			const id = BX.prop.getNumber(eventData, 'id', 0);

			if (!id)
			{
				return;
			}

			// eslint-disable-next-line promise/catch-or-return
			PageManager.openWidget('web', {
				backdrop: {
					shouldResizeContent: true,
					showOnTop: true,
					topPosition: 100,
				},
				page: {
					url: `/mobile/calendar/view_event.php?event_id=${id}`,
				},
			}).then((widget) => {
				this.viewFormWidget = widget;
			});
		}

		getInitialDate()
		{
			return Math.round((this.state.lastSelectedDate.getTime() - this.timezoneOffset) / 1000);
		}

		handleMountSwitch(date)
		{
			const startDate = new Date(date.getTime());
			const endDate = new Date(startDate.getFullYear(), startDate.getMonth() + 1, 1);

			if (!this.eventManager.doesDateRangeLoaded(startDate, endDate))
			{
				this.eventManager.loadList(startDate, endDate);
			}

			this.setMonthTitle(date);
			this.selectDateAfterMonthSwitch(date);
		}

		selectDateAfterMonthSwitch(date)
		{
			if (this.state.lastSelectedDate.getMonth() === date.getMonth())
			{
				return;
			}

			if (
				date.getMonth() === this.nowTime.getMonth()
				&& date.getFullYear() === this.nowTime.getFullYear()
			)
			{
				date.setDate(this.nowTime.getDate());
			}

			if (this.calendarRef)
			{
				this.calendarRef.setDate(Math.round((date.getTime() - this.timezoneOffset) / 1000), false);

				this.handleDateSelected(date);
			}
		}

		handleDateSelected(date)
		{
			if (date.getTime() < 0)
			{
				return;
			}

			this.state.lastSelectedDate = date;

			this.getEventsForDay();

			if (this.dayHeaderRef)
			{
				this.dayHeaderRef.updateDate(this.state.lastSelectedDate);
			}
		}

		getEventsForDay()
		{
			if (this.eventListRef)
			{
				this.eventListRef.getEventsForDay(this.state.lastSelectedDate);
			}
		}

		getEventsByFilter()
		{
			return new Promise((resolve) => {
				// eslint-disable-next-line promise/catch-or-return
				this.eventManager.getEventsByFilter(this.searchFilter.getData()).then((events) => {
					resolve(events);
				});
			});
		}

		setMonthTitle(date)
		{
			const text = DateHelper.getMonthName(date);
			const detailText = date.getFullYear().toString();

			this.layout.setTitle({ text, detailText, useLargeTitleMode: true });
		}

		setSearchTitle()
		{
			const text = Loc.getMessage('M_CALENDAR_EVENT_LIST_TITLE_SEARCH');

			this.layout.setTitle({ text, useLargeTitleMode: true });
		}

		closeSearch()
		{
			this.layout.search.close();
		}

		getFloatingButtonTestId()
		{
			return 'calendar_event_list_ADD_BTN';
		}
	}

	const icons = {
		menuCalendarColor: `<svg width="32" height="33" viewBox="0 0 32 33" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="1" y="1" width="31" height="32" rx="15.5" fill="${AppTheme.colors.accentMainSuccess}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.98621 21.56H13.2116L12.2396 23.6428H8.98731C8.43592 23.6428 7.98842 23.1762 7.98842 22.6012V12.1849C7.98442 12.1308 7.98242 12.0776 7.98242 12.0245C7.98442 10.96 8.8135 10.0996 9.83437 10.1017H10.9851V10.6225C10.9851 11.4849 11.6554 12.1849 12.4834 12.1849C13.3115 12.1849 13.9818 11.4849 13.9818 10.6225V10.1017H17.0351V10.6225C17.0351 11.4849 17.7064 12.1849 18.5334 12.1849C19.3605 12.1849 20.0318 11.4849 20.0318 10.6225V10.1017H21.2853C22.3361 10.1683 23.1502 11.086 23.1313 12.1849V15.9391L21.1335 14.2743V13.286H9.98621V21.56ZM13.2176 10.4166V9.27081C13.2196 8.84791 12.894 8.50314 12.4884 8.50001C12.0829 8.49793 11.7513 8.83854 11.7493 9.2604V9.27081V10.4166C11.7493 10.8395 12.0779 11.1822 12.4834 11.1822C12.889 11.1822 13.2176 10.8395 13.2176 10.4166ZM19.2266 10.3845V9.29809C19.2266 8.8981 18.9159 8.5752 18.5333 8.5752C18.1507 8.5752 17.8401 8.8981 17.8401 9.29809V10.3835C17.8401 10.7824 18.1487 11.1064 18.5323 11.1074C18.9159 11.1074 19.2266 10.7834 19.2266 10.3845ZM12.2783 15.1141C12.0021 15.1141 11.7783 15.338 11.7783 15.6141V16.5038C11.7783 16.78 12.0021 17.0038 12.2783 17.0038H13.168C13.4442 17.0038 13.668 16.78 13.668 16.5038V15.6141C13.668 15.338 13.4442 15.1141 13.168 15.1141H12.2783ZM14.6129 15.6514C14.6129 15.3752 14.8368 15.1514 15.1129 15.1514H16.0027C16.2788 15.1514 16.5027 15.3752 16.5027 15.6514V16.5411C16.5027 16.8173 16.2788 17.0411 16.0027 17.0411H15.1129C14.8368 17.0411 14.6129 16.8173 14.6129 16.5411V15.6514ZM20.4787 16.1589C20.4787 15.9794 20.7088 15.8847 20.8531 16.0048L25.9009 20.2052C26.0328 20.315 26.0328 20.5063 25.9009 20.6161L20.8531 24.8165C20.7088 24.9366 20.4787 24.8419 20.4787 24.6624V21.9554C20.4513 21.9656 20.4214 21.9712 20.3903 21.9712C17.4663 21.9715 14.9309 23.896 13.8896 24.8121C13.73 24.9525 13.4676 24.8301 13.5157 24.6314C13.9226 22.9505 15.4512 18.6046 20.3959 18.4748C20.4249 18.474 20.4529 18.4786 20.4787 18.4875V16.1589Z" fill="white"/><rect width="31" height="32" rx="15.5" fill="white" fill-opacity="0.01"/></svg>`,
		menuCalendarGray: '<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.98621 17.56H9.21181L8.23962 19.6432H4.98731C4.43592 19.6432 3.98842 19.1766 3.98842 18.6016V8.18532C3.98442 8.13116 3.98242 8.07803 3.98242 8.02491C3.98442 6.96037 4.8135 6.09998 5.83437 6.10207H6.9851V6.62288C6.9851 7.48535 7.65536 8.18532 8.48344 8.18532C9.31152 8.18532 9.98178 7.48535 9.98178 6.62288V6.10207H13.0351V6.62288C13.0351 7.48535 13.7064 8.18532 14.5334 8.18532C15.3605 8.18532 16.0318 7.48535 16.0318 6.62288V6.10207H17.2853C18.3361 6.16873 19.1502 7.0864 19.1313 8.18532V11.9395L17.1335 10.2747V9.28601H5.98621V17.56ZM9.21763 6.41661V5.27081C9.21963 4.84791 8.89399 4.50314 8.48844 4.50001C8.08288 4.49793 7.75125 4.83854 7.74925 5.2604V5.27081V6.41661C7.74925 6.83951 8.07789 7.1822 8.48344 7.1822C8.88899 7.1822 9.21763 6.83951 9.21763 6.41661ZM15.2266 6.38497V5.29855C15.2266 4.89857 14.9159 4.57566 14.5334 4.57566C14.1508 4.57566 13.8401 4.89857 13.8401 5.29855V6.38393C13.8401 6.78287 14.1488 7.10682 14.5324 7.10786C14.9159 7.10786 15.2266 6.78392 15.2266 6.38497ZM8.27829 11.1141C8.00215 11.1141 7.77829 11.338 7.77829 11.6141V12.5038C7.77829 12.78 8.00215 13.0038 8.27829 13.0038H9.16803C9.44418 13.0038 9.66803 12.78 9.66803 12.5038V11.6141C9.66803 11.338 9.44418 11.1141 9.16803 11.1141H8.27829ZM10.6129 11.651C10.6129 11.3749 10.8368 11.151 11.1129 11.151H12.0026C12.2788 11.151 12.5027 11.3749 12.5027 11.651V12.5408C12.5027 12.8169 12.2788 13.0408 12.0027 13.0408H11.1129C10.8368 13.0408 10.6129 12.8169 10.6129 12.5408V11.651ZM16.4786 12.1586C16.4786 11.9791 16.7087 11.8844 16.8531 12.0045L21.9008 16.2049C22.0327 16.3147 22.0327 16.5059 21.9008 16.6157L16.8531 20.8161C16.7087 20.9363 16.4786 20.8416 16.4786 20.6621V17.9551C16.4512 17.9653 16.4214 17.9709 16.3902 17.9709C13.4663 17.9712 10.9309 19.8957 9.88954 20.8118C9.72999 20.9522 9.46753 20.8298 9.51562 20.6311C9.92256 18.9501 11.4511 14.6043 16.3958 14.4745C16.4249 14.4737 16.4528 14.4783 16.4786 14.4872V12.1586Z" fill="#a8adb4"/></svg>',
	};

	module.exports = { CalendarEventListView };
});
