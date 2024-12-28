/**
 * @module calendar/event-edit-form/layout/attendees-selector
 */
jn.define('calendar/event-edit-form/layout/attendees-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Indent } = require('tokens');
	const { withPressed } = require('utils/color');
	const { Button, Icon, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { Text2, Text5 } = require('ui-system/typography/text');
	const { UserName } = require('layout/ui/user/user-name');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { EntitySelectorFactory } = require('selector/widget/factory');

	const { dispatch } = require('statemanager/redux/store');
	const { usersAddedFromEntitySelector } = require('statemanager/redux/slices/users');

	const { State, observeState } = require('calendar/event-edit-form/state');

	/**
	 * @class AttendeesSelector
	 */
	class AttendeesSelector extends LayoutComponent
	{
		render()
		{
			return View(
				{},
				this.renderAddAttendeesButton(),
				...this.props.attendees.map((attendee) => this.renderAttendee(attendee)),
			);
		}

		renderAddAttendeesButton()
		{
			return View(
				{
					testId: 'calendar-event-edit-form-add-attendee',
					style: {
						marginTop: Indent.XL.toNumber(),
						flexDirection: 'row',
						alignItems: 'center',
						backgroundColor: withPressed(Color.bgSecondary.toHex()),
					},
					onClick: this.openUserSelector,
				},
				Button({
					testId: 'calendar-event-edit-form-add-attendee-button',
					rounded: true,
					backgroundColor: Color.accentMainPrimary,
					leftIcon: Icon.ADD_PERSON,
					size: ButtonSize.L,
				}),
				View(
					{
						style: {
							marginLeft: Indent.XL.toNumber(),
							borderBottomWidth: 1,
							borderBottomColor: Color.bgSeparatorSecondary.toHex(),
							height: 70,
							justifyContent: 'center',
							flex: 1,
						},
					},
					Text2({
						text: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_ADD_ATTENDEES'),
						color: Color.accentMainLink,
					}),
				),
			);
		}

		openUserSelector = () => {
			void EntitySelectorFactory.createByType(EntitySelectorFactory.Type.USER, {
				provider: {
					context: 'CALENDAR_EVENT',
				},
				canUseRecent: true,
				createOptions: {
					enableCreation: false,
				},
				selectOptions: {
					canUnselectLast: false,
				},
				initSelectedIds: this.props.attendees.map((user) => ['user', Number(user.id)]),
				undeselectableIds: this.getUndeselectableIds(),
				allowMultipleSelection: true,
				events: {
					onClose: this.onUserSelectorCloseHandler,
				},
				widgetParams: {
					title: Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EVENT_ATTENDEES'),
					backdrop: {
						mediumPositionPercent: 80,
						horizontalSwipeAllowed: false,
					},
				},
			}).show({}, this.props.layout);
		};

		onUserSelectorCloseHandler = (items) => {
			if (items.length > 0)
			{
				dispatch(usersAddedFromEntitySelector(items));

				const attendees = items.map((item) => ({
					id: item.id,
					name: item.title,
					isCollaber: item.entityType === 'collaber',
					isExtranet: item.entityType === 'extranet',
					workPosition: item.customData?.position,
				}));

				State.setAttendees(attendees);
			}
		};

		renderAttendee(userInfo)
		{
			return View(
				{
					testId: `calendar-event-edit-form-attendee-${userInfo.id}`,
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				Avatar({
					id: userInfo.id,
					withRedux: true,
					size: ButtonSize.L.getHeight() + 2,
					testId: `calendar-event-edit-form-attendee-${userInfo.id}-avatar`,
				}),
				View(
					{
						style: {
							marginLeft: Indent.XL.toNumber(),
							borderBottomWidth: 1,
							borderBottomColor: Color.bgSeparatorSecondary.toHex(),
							height: 70,
							flexDirection: 'row',
							alignItems: 'center',
							justifyContent: 'space-between',
							flex: 1,
						},
					},
					this.renderAttendeeInfo(userInfo),
					this.canRemoveAttendee(userInfo) && this.renderAttendeeRemoveButton(userInfo),
				),
			);
		}

		renderAttendeeInfo(userInfo)
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.renderAttendeeName(userInfo),
				this.renderAttendeeWorkPosition(userInfo),
			);
		}

		renderAttendeeName(userInfo)
		{
			return UserName({
				textElement: Text2,
				id: userInfo.id,
				testId: `calendar-event-edit-form-attendee-${userInfo.id}-name`,
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}

		renderAttendeeWorkPosition(userInfo)
		{
			if (!Type.isStringFilled(userInfo.workPosition) && !userInfo.isCollaber && !userInfo.isExtranet)
			{
				return null;
			}

			return Text5({
				testId: `calendar-event-edit-form-attendee-${userInfo.id}-work-position`,
				color: Color.base3,
				numberOfLines: 1,
				ellipsize: 'end',
				style: {
					marginTop: Indent.XS2.toNumber(),
				},
				text: this.getUserWorkPosition(userInfo),
			});
		}

		renderAttendeeRemoveButton(userInfo)
		{
			return Button({
				testId: `calendar-event-edit-form-attendee-${userInfo.id}-remove-button`,
				size: ButtonSize.L,
				design: ButtonDesign.PLAIN_NO_ACCENT,
				leftIcon: Icon.CROSS,
				leftIconColor: Color.base4,
				onClick: () => this.removeAttendee(userInfo.id),
			});
		}

		removeAttendee(userId)
		{
			const attendees = this.props.attendees.filter((user) => user.id !== userId);

			State.setAttendees(attendees);
		}

		getUserWorkPosition(userInfo)
		{
			if (userInfo.isCollaber)
			{
				return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_GUEST');
			}

			if (userInfo.isExtranet)
			{
				return Loc.getMessage('M_CALENDAR_EVENT_EDIT_FORM_EXTRANET');
			}

			return userInfo.workPosition;
		}

		getUndeselectableIds()
		{
			const result = [['user', Number(env.userId)]];

			if (this.props.meetingHost && Number(env.userId) !== this.props.meetingHost)
			{
				result.push(['user', this.props.meetingHost]);
			}

			return result;
		}

		canRemoveAttendee(userInfo)
		{
			return Number(userInfo.id) !== Number(env.userId)
				&& Number(userInfo.id) !== this.props.meetingHost
			;
		}
	}

	const mapStateToProps = (state) => ({
		meetingHost: state.meetingHost,
		attendees: state.attendees,
	});

	module.exports = { AttendeesSelector: observeState(AttendeesSelector, mapStateToProps) };
});
