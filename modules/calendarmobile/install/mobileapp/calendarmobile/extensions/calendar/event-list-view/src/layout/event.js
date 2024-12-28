/**
 * @module calendar/event-list-view/layout/event
 */
jn.define('calendar/event-list-view/layout/event', (require, exports, module) => {
	const { Loc } = require('loc');
	const { shortTime } = require('utils/date/formats');
	const { Indent, Color, Corner } = require('tokens');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { BadgeStatus, BadgeStatusMode, BadgeStatusSize } = require('ui-system/blocks/badges/status');
	const { Text2, Text5, Text6 } = require('ui-system/typography/text');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	const { State } = require('calendar/event-list-view/state');
	const { DateHelper } = require('calendar/date-helper');
	const { EventView } = require('calendar/event-view-form');
	const { LocationManager } = require('calendar/data-managers/location-manager');
	const { CollabManager } = require('calendar/data-managers/collab-manager');
	const { SectionManager } = require('calendar/data-managers/section-manager');
	const { EventMeetingStatus, CalendarType, Counters } = require('calendar/enums');
	const { RecursionModeMenu } = require('calendar/layout/menu/recursion-mode');

	const { dispatch } = require('statemanager/redux/store');
	const { setMeetingStatus } = require('calendar/statemanager/redux/slices/events');

	const isAndroid = Application.getPlatform() === 'android';

	class Event
	{
		constructor(props)
		{
			this.props = props;

			this.refs = {
				declineButton: null,
			};
		}

		/** @return {EventModel} */
		get event()
		{
			return this.props.event;
		}

		/** @return {string} */
		get dayCode()
		{
			return this.props.dayCode;
		}

		get isFirst()
		{
			return this.props.isFirst;
		}

		/** @return {boolean} */
		get hasPassed()
		{
			return State.isSearchMode || State.isInvitationMode ? false : this.event.hasPassed(this.dayCode);
		}

		/**
		 * @public
		 * @returns {BaseMethods}
		 */
		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						paddingVertical: Indent.XL.toNumber(),
						borderTopWidth: 1,
						borderTopColor: Color.bgSeparatorSecondary.toHex(),
					},
					clickable: true,
					onClick: this.openEventViewForm,
					testId: `event_${this.event.getId()}`,
				},
				this.renderColor(),
				this.renderInfo(),
				this.renderTime(),
			);
		}

		renderColor()
		{
			return View(
				{
					style: {
						width: 6,
						height: '100%',
						borderRadius: Corner.S.toNumber(),
						backgroundColor: this.getEventColor(),
						opacity: this.isDeclinedOrHasPassed() ? 0.4 : 1,
					},
					testId: `event_${this.event.getId()}_color`,
				},
			);
		}

		getEventColor()
		{
			if (this.event.getCollabId())
			{
				return this.event.getColor() || Color.collabAccentPrimary.toHex();
			}

			return this.event.getColor() || SectionManager.getSectionColor(this.event.getSectionId());
		}

		renderInfo()
		{
			return View(
				{
					style: {
						flex: 1,
						paddingHorizontal: Indent.XL.toNumber(),
						flexDirection: 'column',
					},
				},
				this.renderName(),
				this.event.isInvited() ? this.renderAttendButtons() : this.renderDescription(),
			);
		}

		renderName()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				this.renderNameIcon(),
				this.renderNameText(),
			);
		}

		renderNameText()
		{
			return Text2({
				testId: `event_${this.event.getId()}_name`,
				text: this.event.getName(),
				color: this.getNameColor(),
				numberOfLines: 1,
				ellipsize: 'end',
				style: {
					flex: 1,
					opacity: (this.event.isSharingEvent() && this.hasPassed) ? 0.7 : 1,
				},
			});
		}

		getNameColor()
		{
			if (this.event.isSharingEvent())
			{
				return Color.accentMainWarning;
			}

			return this.isDeclinedOrHasPassed() ? Color.base3 : Color.base1;
		}

		getLocationColor()
		{
			return this.isDeclinedOrHasPassed() ? Color.base4 : Color.base2;
		}

		renderNameIcon()
		{
			if (this.event.isSharingEvent())
			{
				return View(
					{
						style: {
							paddingRight: Indent.XS.toNumber(),
						},
					},
					IconView({
						icon: Icon.PERSON,
						size: 18,
						color: Color.accentMainWarning,
					}),
				);
			}

			if (this.event.isInvited())
			{
				return View(
					{
						style: {
							paddingRight: Indent.S.toNumber(),
						},
					},
					BadgeCounter({
						testId: 'event_icon_invited',
						value: 1,
						design: BadgeCounterDesign.ALERT,
					}),
				);
			}

			if (this.event.isRecurrence())
			{
				return View(
					{
						style: {
							paddingRight: Indent.XS.toNumber(),
						},
					},
					IconView({
						icon: Icon.REPEAT,
						size: 18,
						color: this.getNameColor(),
					}),
				);
			}

			return null;
		}

		renderDescription()
		{
			if (!this.event.getLocation() && !this.event.isDeclined() && !this.event.getCollabId())
			{
				return null;
			}

			return View(
				{
					style: {
						paddingTop: Indent.XS2.toNumber(),
						paddingBottom: Indent.XS.toNumber(),
						flexDirection: 'column',
					},
				},
				!this.event.isDeclined() && this.event.getLocation() && this.renderLocation(),
				this.event.isDeclined() && this.renderNotParticipatingStatus(),
				this.shouldRenderCollabName() && this.renderCollabName(),
			);
		}

		shouldRenderCollabName()
		{
			return this.event.getCollabId()
				&& CollabManager.getCollabName(this.event.getCollabId())
				&& !this.event.isDeclined()
				&& !this.event.isGroupCalendar()
			;
		}

		renderLocation()
		{
			return Text5({
				testId: `event_${this.event.getId()}_location`,
				text: LocationManager.getTextLocation(this.event.getLocation()),
				color: this.getLocationColor(),
				numberOfLines: 1,
				ellipsize: 'end',
				style: {
					opacity: 0.7,
				},
			});
		}

		renderNotParticipatingStatus()
		{
			return View(
				{
					testId: `event_${this.event.getId()}_not_participating`,
					style: {
						alignItems: 'center',
						flexDirection: 'row',
					},
				},
				BadgeStatus({
					testId: `event_${this.event.getId()}_not_participating_badge`,
					mode: BadgeStatusMode.WARNING_GREY,
					size: BadgeStatusSize.SMALL,
				}),
				Text6({
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_NOT_ATTENDED'),
					color: Color.base4,
					style: {
						marginLeft: Indent.XS.toNumber(),
					},
				}),
			);
		}

		renderCollabName()
		{
			return Text6({
				testId: `event_${this.event.getId()}_collab_name`,
				color: Color.collabAccentPrimaryAlt,
				text: Loc.getMessage('M_CALENDAR_EVENT_LIST_COLLAB_NAME', {
					'#NAME#': CollabManager.getCollabName(this.event.getCollabId()),
				}),
				numberOfLines: 1,
				ellipsize: 'end',
				style: {
					opacity: this.hasPassed ? 0.7 : 1,
					paddingTop: this.event.getLocation() ? Indent.XS2.toNumber() : 0,
				},
			});
		}

		renderAttendButtons()
		{
			return View(
				{
					style: {
						paddingTop: Indent.L.toNumber(),
						paddingBottom: Indent.XS.toNumber(),
						flexDirection: 'row',
					},
				},
				Button({
					testId: `event_${this.event.getId()}_confirm_button`,
					size: ButtonSize.S,
					design: ButtonDesign.FILLED,
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_INVITATION_BUTTON_CONFIRM'),
					onClick: this.onConfirmButtonClick,
				}),
				Button({
					testId: `event_${this.event.getId()}_decline_button`,
					size: ButtonSize.S,
					design: ButtonDesign.OUTLINE,
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_INVITATION_BUTTON_DECLINE'),
					style: {
						marginLeft: Indent.S.toNumber(),
					},
					onClick: this.onDeclineButtonClick,
					forwardRef: this.#bindDeclineButton,
				}),
			);
		}

		#bindDeclineButton = (ref) => {
			this.refs.declineButton = ref;
		};

		renderTime()
		{
			return View(
				{
					style: {
						paddingVertical: Indent.XS.toNumber(),
						alignItems: 'flex-end',
						paddingRight: isAndroid ? 0 : 40, // TODO: solve the mystery
						flexDirection: 'column',
					},
				},
				Text5({
					testId: `event_${this.event.getId()}_time_from`,
					text: this.getTimeFromText(),
					color: this.isDeclinedOrHasPassed() ? Color.base2 : Color.base0,
					style: {
						opacity: this.isDeclinedOrHasPassed() ? 0.7 : 0.9,
					},
				}),
				Text5({
					testId: `event_${this.event.getId()}_time_to`,
					text: this.getTimeToText(),
					color: this.isDeclinedOrHasPassed() ? Color.base4 : Color.base2,
					style: {
						opacity: this.isDeclinedOrHasPassed() ? 0.8 : 0.7,
						marginTop: Indent.XS.toNumber(),
					},
				}),
			);
		}

		getTimeFromText()
		{
			const dateFrom = this.event.getMomentDateFrom();
			const dateTo = this.event.getMomentDateTo();

			if (this.event.isFullDay())
			{
				return Loc.getMessage('M_CALENDAR_EVENT_LIST_ALL_DAY');
			}

			if (this.event.isLongWithTime())
			{
				if (this.dayCode === DateHelper.getDayCode(dateFrom.date))
				{
					return dateFrom.format(shortTime()).toLocaleUpperCase(env.languageId);
				}

				if (this.dayCode === DateHelper.getDayCode(dateTo.date))
				{
					return Loc.getMessage('M_CALENDAR_EVENT_LIST_TILL_TIME').replace(
						'#TIME#',
						dateTo.format(shortTime()).toLocaleUpperCase(env.languageId),
					);
				}

				return Loc.getMessage('M_CALENDAR_EVENT_LIST_ALL_DAY');
			}

			return dateFrom.format(shortTime()).toLocaleUpperCase(env.languageId);
		}

		getTimeToText()
		{
			const dateTo = this.event.getMomentDateTo();

			if (this.event.isFullDay() || this.event.isLongWithTime())
			{
				return ' ';
			}

			return dateTo.format(shortTime()).toLocaleUpperCase(env.languageId);
		}

		openEventViewForm = () => {
			EventView.open({
				ownerId: State.ownerId,
				calType: State.calType,
				parentLayout: this.props.layout,
				eventId: this.event.getId(),
				dateFromTs: this.getEventDateFrom().getTime(),
			});
		};

		onConfirmButtonClick = () => {
			this.setMeetingStatus(EventMeetingStatus.ATTENDED);
		};

		onDeclineButtonClick = () => {
			this.setMeetingStatus(EventMeetingStatus.DECLINED);
		};

		setMeetingStatus(status, recursionMode = false)
		{
			if (
				this.event.isRecurrence()
				&& status === EventMeetingStatus.DECLINED
				&& !State.isInvitationMode
				&& !recursionMode
			)
			{
				this.showRecursionMenu();

				return;
			}

			dispatch(
				setMeetingStatus({
					data: {
						status,
						recursionMode,
						eventId: this.event.getId(),
						parentId: this.event.getParentId(),
						currentDateFrom: DateHelper.formatDate(this.getEventDateFrom()),
					},
				}),
			);

			if (State.isInvitationMode)
			{
				const hasChanges = State.filterResultIds.find((id) => id === this.event.getId());
				if (hasChanges)
				{
					const filterResultIds = State.filterResultIds.filter((id) => id !== this.event.getId());
					State.setFilterResultIds(filterResultIds);

					if (State.calType === CalendarType.USER && State.counters[Counters.INVITES] > 0)
					{
						State.setUserInvitesCounter(State.counters[Counters.INVITES] - 1);
					}
					else if (State.calType === CalendarType.GROUP && State.counters[Counters.GROUP_INVITES] > 0)
					{
						State.setGroupCounter(State.counters[Counters.GROUP_INVITES] - 1);
					}
				}
			}
		}

		showRecursionMenu()
		{
			const onRecursionMenuItemSelected = (item) => {
				const recursionMode = item.id;
				this.setMeetingStatus(EventMeetingStatus.DECLINED, recursionMode);
			};

			this.recursionMenu = new RecursionModeMenu({
				layoutWidget: this.props.layout,
				targetElementRef: this.refs.declineButton,
				onItemSelected: onRecursionMenuItemSelected,
			});

			this.recursionMenu.show();
		}

		isDeclinedOrHasPassed()
		{
			return this.event.isDeclined() || this.hasPassed;
		}

		getEventDateFrom()
		{
			if (this.event.isRecurrence())
			{
				const eventFrom = DateHelper.getDateFromDayCode(this.dayCode);
				eventFrom.setHours(this.event.getDateFrom().getHours(), this.event.getDateFrom().getMinutes());

				return eventFrom;
			}

			return this.event.getDateFrom();
		}
	}

	module.exports = {
		Event: (props) => new Event(props).render(),
	};
});
