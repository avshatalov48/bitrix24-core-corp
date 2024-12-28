/**
 * @module calendar/event-edit-form
 */
jn.define('calendar/event-edit-form', (require, exports, module) => {
	const { BottomSheet } = require('bottom-sheet');
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { Haptics } = require('haptics');
	const { Icon } = require('assets/icons');
	const { confirmClosing } = require('alert');
	const { tariffPlanRestrictionsReady } = require('tariff-plan-restriction');
	const { NotifyManager } = require('notify-manager');

	const { UserManager } = require('calendar/data-managers/user-manager');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { LocationManager } = require('calendar/data-managers/location-manager');
	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { AboutPage } = require('calendar/event-edit-form/pages/about-page');
	const { State } = require('calendar/event-edit-form/state');
	const { CalendarType } = require('calendar/enums');
	const { EventAjax } = require('calendar/ajax');

	const store = require('statemanager/redux/store');
	const { selectByIdAndDate } = require('calendar/statemanager/redux/slices/events');

	/**
	 * @class EventEditForm
	 */
	class EventEditForm extends LayoutComponent
	{
		get layout()
		{
			return this.props.layout;
		}

		get editAttendeesMode()
		{
			return this.props.editAttendeesMode;
		}

		componentDidMount()
		{
			tariffPlanRestrictionsReady();
			this.bindKeyboardHandlers();
			this.initLayout();
		}

		componentWillUnmount()
		{
			this.unbindKeyboardHandlers();
		}

		initLayout()
		{
			this.layout.setRightButtons([
				{
					type: Icon.CROSS.getIconName(),
					callback: () => this.layout.close(),
				},
			]);

			this.layout.preventBottomSheetDismiss(true);
			this.layout.on('preventDismiss', this.onPreventDismiss);
		}

		bindKeyboardHandlers()
		{
			Keyboard.on(Keyboard.Event.WillHide, this.#onKeyboardWillHide);
			Keyboard.on(Keyboard.Event.WillShow, this.#onKeyboardWillShow);
		}

		unbindKeyboardHandlers()
		{
			Keyboard.off(Keyboard.Event.WillHide, this.#onKeyboardWillHide);
			Keyboard.off(Keyboard.Event.WillShow, this.#onKeyboardWillShow);
		}

		#onKeyboardWillShow = () => {
			State.setKeyboardShown(true);
		};

		#onKeyboardWillHide = () => {
			State.setKeyboardShown(false);
		};

		onPreventDismiss = () => {
			if (!State.hasChanges())
			{
				this.layout.close();

				return;
			}

			if (this.preventDismissShown)
			{
				return;
			}

			Haptics.impactLight();

			const description = State.isEditForm()
				? Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_PREVENT_DISMISS_DESCRIPTION_EDIT')
				: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_PREVENT_DISMISS_DESCRIPTION')
			;

			confirmClosing({
				hasSaveAndClose: false,
				title: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_PREVENT_DISMISS_TITLE'),
				description,
				onCancel: () => {
					this.preventDismissShown = false;
				},
				onClose: () => {
					this.preventDismissShown = false;
					this.layout.close();
				},
			});

			this.preventDismissShown = true;
		};

		render()
		{
			return new AboutPage(this.props);
		}

		/**
		 * @public
		 * @param {object} params
		 * @param {PageManager} [params.parentLayout]
		 * @param {number} [params.sectionId]
		 * @param {number} [params.firstWeekday = 2]
		 * @param {User} [params.user = {}]
		 * @param {?number} [params.eventId = null]
		 * @param {?number} [params.dateFromTs = null]
		 * @param {?number} [params.createChatId = null]
		 * @param {number} [params.ownerId]
		 * @param {string} [params.calType]
		 * @param {number} [params.selectedDayTs]
		 * @param {boolean} [params.editAttendeesMode]
		 * @return void
		 */
		static async open({
			parentLayout,
			sectionId,
			firstWeekday = 2,
			user = {},
			eventId = null,
			dateFromTs = null,
			ownerId = env.userId,
			calType = CalendarType.USER,
			selectedDayTs = null,
			recursionMode = null,
			createChatId = null,
			editAttendeesMode = false,
		})
		{
			const event = await this.getEvent(eventId, dateFromTs);

			State.initNewForm({
				event,
				user,
				ownerId,
				calType,
				createChatId,
				sectionId,
				firstWeekday,
				recursionMode,
				selectedDayTs,
				editAttendeesMode,
			});

			this.openPage(parentLayout, editAttendeesMode);
		}

		static openPage(parentLayout, editAttendeesMode = false)
		{
			const component = (layout) => new this({ layout, editAttendeesMode });

			void new BottomSheet({ component })
				.setParentWidget(parentLayout)
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setTitleParams({
					text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_TITLE'),
					type: 'wizard',
				})
				.disableSwipe()
				.showOnTop()
				.open()
			;
		}

		static async getEvent(eventId, dateFromTs)
		{
			if (!eventId)
			{
				return null;
			}

			const reduxEvent = selectByIdAndDate(store.getState(), { eventId, dateFromTs });
			if (!reduxEvent)
			{
				return null;
			}

			return reduxEvent;
		}

		/**
		 * @param ownerId {number}
		 * @param calType {string}
		 * @param participantsEntityList {array}
		 * @param description {string}
		 * @param createChatId {number}
		 * @param uuid {string}
		 * @param showLoading {boolean}
		 * @returns {Promise<void>}
		 */
		static async initEditForm({
			ownerId,
			calType,
			participantsEntityList = [],
			description = '',
			createChatId = null,
			uuid = null,
			showLoading = false,
		})
		{
			if (showLoading)
			{
				void NotifyManager.showLoadingIndicator();
			}

			try
			{
				if (!participantsEntityList.includes(Number(env.userId)))
				{
					participantsEntityList.push(Number(env.userId));
				}

				const { data } = await EventAjax.getEditFormConfig({
					ownerId,
					calType,
					userIds: UserManager.getNotRequestedUserIds(participantsEntityList),
				});

				SectionManager.setSections(data.sections);
				LocationManager.setLocations(data.locationList);
				LocationManager.setCategories(data.categoryList);
				SettingsManager.setCalendarSettings(data.settings);

				if (data.users && data.users.length > 0)
				{
					UserManager.addUsersToRedux(data.users);
				}

				const { id, fullName, workPosition, isCollaber, isExtranet } = UserManager.getById(env.userId);
				const user = {
					id,
					workPosition,
					isCollaber,
					isExtranet,
					name: fullName,
				};

				const sectionId = SettingsManager.getMeetSectionId() ?? data.meetSection;
				const firstWeekday = SettingsManager.getFirstWeekday() ?? data.firstWeekday;

				State.initNewForm({
					uuid,
					user,
					ownerId,
					calType,
					createChatId,
					sectionId,
					firstWeekday,
					description,
					participantsEntityList,
				});

				if (showLoading)
				{
					void NotifyManager.hideLoadingIndicatorWithoutFallback();
				}
			}
			catch (errorResponse)
			{
				console.error(errorResponse);

				if (showLoading)
				{
					void NotifyManager.hideLoadingIndicator(false);
				}
			}
		}
	}

	module.exports = { EventEditForm };
});
