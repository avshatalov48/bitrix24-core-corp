/**
 * @module calendar/event-view-form/fields/decision-buttons
 */
jn.define('calendar/event-view-form/fields/decision-buttons', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { Button, ButtonSize, ButtonDesign, Icon } = require('ui-system/form/buttons');

	const { DateHelper } = require('calendar/date-helper');
	const { ChangeDecisionMenu } = require('calendar/event-view-form/change-decision-menu');
	const { EventMeetingStatus } = require('calendar/enums');
	const { RecursionModeMenu } = require('calendar/layout/menu/recursion-mode');

	const { dispatch } = require('statemanager/redux/store');
	const { setMeetingStatus } = require('calendar/statemanager/redux/slices/events');

	/**
	 * @class DecisionButtonsField
	 */
	class DecisionButtonsField extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.decisionMenu = null;
			this.recursionMenu = null;

			this.refs = {
				declineButton: null,
				changeDecisionButton: null,
			};

			this.setMeetingStatus = this.setMeetingStatus.bind(this);
		}

		getId()
		{
			return this.props.id;
		}

		isReadOnly()
		{
			return this.props.readOnly;
		}

		isRequired()
		{
			return false;
		}

		isEmpty()
		{
			return this.props.meetingStatus === EventMeetingStatus.HOST;
		}

		isHidden()
		{
			return this.isEmpty();
		}

		render()
		{
			const hasDecision = [EventMeetingStatus.ATTENDED, EventMeetingStatus.DECLINED].includes(this.props.meetingStatus);

			return View(
				{
					style: {
						marginTop: Indent.XL3.toNumber(),
						flexDirection: 'row',
					},
				},
				!hasDecision && this.renderAcceptButton(),
				!hasDecision && this.renderDeclineButton(),
				hasDecision && this.renderChangeDecisionButton(),
			);
		}

		renderAcceptButton()
		{
			return Button({
				testId: 'calendar-event-view-form-button-accept',
				color: Color.accentMainPrimary,
				borderColor: Color.accentSoftBorderBlue,
				leftIcon: Icon.CHECK,
				text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_ACCEPT'),
				size: ButtonSize.M,
				design: ButtonDesign.OUTLINE_ACCENT_2,
				rounded: true,
				style: {
					marginRight: Indent.M.toNumber(),
				},
				onClick: this.onAcceptButtonClick,
			});
		}

		renderDeclineButton()
		{
			return Button({
				testId: 'calendar-event-view-form-button-decline',
				color: Color.accentMainAlert,
				borderColor: Color.accentSoftRed1,
				leftIcon: Icon.CROSS,
				text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_DECLINE'),
				size: ButtonSize.M,
				design: ButtonDesign.OUTLINE_ACCENT_2,
				rounded: true,
				forwardRef: (ref) => {
					this.refs.declineButton = ref;
				},
				onClick: this.onDeclineButtonClick,
			});
		}

		renderChangeDecisionButton()
		{
			return Button({
				testId: 'calendar-event-view-form-change-decision-button',
				...(this.props.meetingStatus === EventMeetingStatus.ATTENDED ? acceptedProps : {}),
				...(this.props.meetingStatus === EventMeetingStatus.DECLINED ? declinedProps : {}),
				rightIcon: Icon.CHEVRON_DOWN_SIZE_S,
				size: ButtonSize.M,
				design: ButtonDesign.OUTLINE_ACCENT_2,
				rounded: true,
				forwardRef: (ref) => {
					this.refs.changeDecisionButton = ref;
				},
				onClick: this.showChangeDecisionMenu,
			});
		}

		onAcceptButtonClick = () => {
			this.setMeetingStatus(EventMeetingStatus.ATTENDED);
		};

		onDeclineButtonClick = () => {
			this.setMeetingStatus(EventMeetingStatus.DECLINED);
		};

		setMeetingStatus(status, recursionMode = false)
		{
			if (
				this.props.isRecurrent
				&& status === EventMeetingStatus.DECLINED
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
						eventId: this.props.eventId,
						parentId: this.props.parentEventId,
						currentDateFrom: DateHelper.formatDate(new Date(this.props.dateFromTs)),
					},
				}),
			);
		}

		showRecursionMenu()
		{
			this.recursionMenu = new RecursionModeMenu({
				layoutWidget: this.props.layout,
				targetElementRef: this.refs.declineButton || this.refs.changeDecisionButton,
				onItemSelected: this.onRecursionMenuItemSelected,
			});

			this.recursionMenu.show();
		}

		onRecursionMenuItemSelected = (item) => {
			const recursionMode = item.id;
			this.setMeetingStatus(EventMeetingStatus.DECLINED, recursionMode);
		};

		showChangeDecisionMenu = () => {
			this.decisionMenu = new ChangeDecisionMenu({
				meetingStatus: this.props.meetingStatus,
				targetElementRef: this.refs.changeDecisionButton,
				layoutWidget: this.props.layout,
				onItemSelected: this.onChangeDecisionItemSelected,
			});

			this.decisionMenu.show();
		};

		onChangeDecisionItemSelected = (item) => {
			const newStatus = this.props.meetingStatus === EventMeetingStatus.ATTENDED
				? EventMeetingStatus.DECLINED
				: EventMeetingStatus.ATTENDED
			;

			this.setMeetingStatus(newStatus);
		};
	}

	const acceptedProps = {
		color: Color.accentMainSuccess,
		borderColor: Color.accentMainSuccess,
		leftIcon: Icon.CHECK,
		text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_YOU_ACCEPTED'),
	};

	const declinedProps = {
		color: Color.accentMainAlert,
		borderColor: Color.accentSoftRed1,
		leftIcon: Icon.CROSS,
		text: Loc.getMessage('M_CALENDAR_EVENT_VIEW_FORM_YOU_DECLINED'),
	};

	module.exports = {
		DecisionButtonsField: (props) => new DecisionButtonsField(props),
	};
});
