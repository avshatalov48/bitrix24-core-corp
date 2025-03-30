/**
 * @module stafftrack/check-in/pages/cancel-reason
 */
jn.define('stafftrack/check-in/pages/cancel-reason', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { showToast } = require('toast');
	const { outline: { check, cross } } = require('assets/icons');
	const { Haptics } = require('haptics');
	const { BottomSheet } = require('bottom-sheet');
	const { AvaMenu } = require('ava-menu');

	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Button } = require('ui-system/form/buttons/button');
	const { H3 } = require('ui-system/typography/heading');
	const { Link3, LinkDesign, LinkMode } = require('ui-system/blocks/link');
	const { Text3 } = require('ui-system/typography/text');
	const { DialogFooter } = require('ui-system/layout/dialog-footer');

	const { Message } = require('stafftrack/check-in/message');
	const { ShiftManager } = require('stafftrack/data-managers/shift-manager');
	const { CancelReasonMenu } = require('stafftrack/check-in/cancel-reason-menu');
	const { CancelReasonEnum, StatusEnum } = require('stafftrack/model/shift');
	const { Analytics, CheckinSentEnum } = require('stafftrack/analytics');
	const { DateHelper } = require('stafftrack/date-helper');

	const DEFAULT_HEIGHT = 300;

	class CancelReasonPage extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.refs = {
				layoutWidget: this.props.layoutWidget || PageManager,
				textField: null,
				reasonSelector: null,
				message: null,
				buttonContainer: null,
			};

			this.state = {
				keyboardShow: false,
				sendMessage: true,
				selectedReason: this.props.selectedReason || null,
			};

			this.cancelReasonMenu = null;

			this.previousToast = null;

			this.onSwitcherClick = this.onSwitcherClick.bind(this);

			this.onConfirmButtonClick = this.onConfirmButtonClick.bind(this);
			this.onReasonSelectorClick = this.onReasonSelectorClick.bind(this);
			this.onReasonSelected = this.onReasonSelected.bind(this);
		}

		get user()
		{
			return this.props.userInfo ?? {};
		}

		get shift()
		{
			return this.props.shift;
		}

		get cancelType()
		{
			return this.props.cancelType;
		}

		componentDidMount()
		{
			if (this.isCustomReason())
			{
				this.refs.message?.focus();
			}
		}

		componentDidUpdate(prevProps, prevState)
		{
			super.componentDidUpdate(prevProps, prevState);

			if (prevState.selectedReason !== this.state.selectedReason && this.isCustomReason())
			{
				this.refs.message?.focus();
			}
		}

		show(parentLayout = PageManager)
		{
			void new BottomSheet({ component: this })
				.setParentWidget(parentLayout)
				.setMediumPositionHeight(DEFAULT_HEIGHT)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.enableAdoptHeightByKeyboard()
				.disableOnlyMediumPosition()
				.disableContentSwipe()
				.open()
				.then((widget) => {
					this.refs.layoutWidget = widget;
				})
			;
		}

		changeHeight(mediumPositionHeight)
		{
			if (!this.refs.layoutWidget)
			{
				return;
			}

			this.refs.layoutWidget.setBottomSheetParams({ mediumPositionHeight });
			this.refs.layoutWidget.setBottomSheetHeight(mediumPositionHeight);
		}

		getCancelReasonText(value)
		{
			let reason = '';

			switch (value)
			{
				case CancelReasonEnum.ILLNESS.getValue():
					reason = Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON_ILLNESS_MESSAGE');
					break;
				case CancelReasonEnum.SICK_LEAVE.getValue():
					reason = Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON_SICK_LEAVE_MESSAGE');
					break;
				case CancelReasonEnum.TIME_OFF.getValue():
					reason = Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON_TIME_OFF_MESSAGE');
					break;
				case CancelReasonEnum.VACATION.getValue():
					reason = Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON_VACATION_MESSAGE');
					break;
				default:
					return '';
			}

			return this.getCancelReasonEmoji(value) + Loc.getMessage('M_STAFFTRACK_CHECK_IN_NOT_WORKING_MESSAGE', {
				'#REASON#': reason,
			});
		}

		getCancelReasonEmoji(value)
		{
			switch (value)
			{
				case CancelReasonEnum.ILLNESS.getValue():
					return '🤢';
				case CancelReasonEnum.SICK_LEAVE.getValue():
					return '🌡';
				case CancelReasonEnum.TIME_OFF.getValue():
					return '🏕';
				case CancelReasonEnum.VACATION.getValue():
					return '🌊';
				default:
					return '';
			}
		}

		getSelectedCancelReasonText(value)
		{
			switch (value)
			{
				case CancelReasonEnum.ILLNESS.getValue():
					return Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON_ILLNESS_MESSAGE');
				case CancelReasonEnum.SICK_LEAVE.getValue():
					return Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON_SICK_LEAVE_MESSAGE');
				case CancelReasonEnum.TIME_OFF.getValue():
					return Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON_TIME_OFF_MESSAGE');
				case CancelReasonEnum.VACATION.getValue():
					return Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON_VACATION_MESSAGE');
				case CancelReasonEnum.CUSTOM.getValue():
					return Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_CUSTOM_MESSAGE');
				default:
					return '';
			}
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
					resizableByKeyboard: true,
					onClick: () => Keyboard.dismiss(),
					testId: 'stafftrack-check-in-cancel-reason',
				},
				this.renderHeader(),
				this.renderBody(),
				this.renderDialogFooter(),
			);
		}

		renderBody()
		{
			return ScrollView(
				{
					style: {
						flex: 1,
					},
				},
				Box(
					{
						backgroundColor: Color.bgSecondary,
					},
					this.renderContent(),
				),
			);
		}

		renderHeader()
		{
			return Area(
				{
					isFirst: true,
					style: {
						flexDirection: 'row',
					},
				},
				IconView({
					icon: Icon.ARROW_TO_THE_LEFT,
					size: 24,
					color: Color.base4,
					onClick: () => this.refs.layoutWidget.close(),
				}),
				H3({
					numberOfLines: 1,
					ellipsize: 'end',
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_REASON_TITLE'),
					style: {
						flex: 1,
						marginLeft: Indent.XL.toNumber(),
					},
				}),
			);
		}

		renderContent()
		{
			return Area(
				{
					style: {
						paddingVertical: Indent.L.toNumber(),
					},
					isFirst: true,
				},
				this.renderMessage(),
				this.renderReasonSelector(),
			);
		}

		renderMessage()
		{
			return new Message({
				readOnly: false,
				sendMessage: true,
				layoutWidget: this.refs.layoutWidget,
				userInfo: this.user,
				isCancelReason: true,
				defaultValue: this.getCancelReasonText(this.props.selectedReason),
				placeholder: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_CUSTOM_MESSAGE'),
				dialogId: this.props.dialogId || null,
				dialogName: this.props.dialogName || null,
				onSwitcherClick: this.onSwitcherClick,
				ref: (ref) => {
					this.refs.message = ref;
				},
			});
		}

		onSwitcherClick(sendMessage)
		{
			this.setState({ sendMessage });
		}

		renderReasonSelector()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						paddingBottom: Indent.XL3.toNumber() * 2,
					},
				},
				Text3({
					text: `${Loc.getMessage('M_STAFFTRACK_CHECK_IN_REASON')}:`,
					color: Color.base3,
				}),
				View(
					{
						style: {
							marginLeft: Indent.XS.toNumber(),
						},
						ref: (ref) => {
							this.refs.reasonSelector = ref;
						},
					},
					Link3({
						onClick: this.onReasonSelectorClick,
						testId: 'stafftrack-check-in-cancel-reason-selector',
						text: this.getSelectedCancelReasonText(this.state.selectedReason),
						useInAppLink: false,
						mode: LinkMode.PLAIN,
						design: LinkDesign.PRIMARY,
						rightIcon: Icon.CHEVRON_DOWN,
					}),
				),
			);
		}

		renderConfirmButton()
		{
			return View(
				{
					style: {
						opacity: 1,
					},
					ref: (ref) => {
						this.refs.buttonContainer = ref;
					},
				},
				Button({
					testId: 'stafftrack-check-in-cancel-reason-confirm-button',
					text: this.getConfirmButtonText(),
					onClick: this.onConfirmButtonClick,
					backgroundColor: Color.accentMainPrimary,
					color: Color.baseWhiteFixed,
					stretched: true,
				}),
			);
		}

		getConfirmButtonText()
		{
			if (this.cancelType === StatusEnum.NOT_WORKING)
			{
				return this.state.sendMessage
					? Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_REASON_CONFIRM_BUTTON_SEND')
					: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_REASON_CONFIRM_BUTTON_SAVE')
				;
			}

			return Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL');
		}

		renderDialogFooter()
		{
			return DialogFooter(
				{
					safeArea: true,
					keyboardButton: {
						text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CONTINUE'),
						testId: 'stafftrack-checkin-continue-button',
						onClick: () => Keyboard.dismiss(),
					},
				},
				this.renderConfirmButton(),
			);
		}

		onReasonSelectorClick()
		{
			this.refs.message?.blur();

			this.cancelReasonMenu ??= new CancelReasonMenu({
				layoutWidget: this.refs.layoutWidget,
				onItemSelected: this.onReasonSelected,
			});

			this.cancelReasonMenu.show(this.refs.reasonSelector);
		}

		onReasonSelected(selectedReason)
		{
			this.setState({ selectedReason });

			Haptics.impactMedium();

			this.refs.message?.changeMessageContent(this.getCancelReasonText(selectedReason));
		}

		async onConfirmButtonClick()
		{
			if (this.cancelType === StatusEnum.NOT_WORKING)
			{
				void this.startNotWorkingDay();
			}
			else
			{
				void this.cancelWorkingDay();
			}
		}

		// eslint-disable-next-line consistent-return
		startNotWorkingDay()
		{
			this.refs.message?.blur();

			if (this.hasEmptyDialogId())
			{
				return this.refs.message.openChatSelector();
			}

			if (!this.refs.message?.getMessage())
			{
				return this.showToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_REASON_EMPTY_REASON_TOAST'), true);
			}

			const shiftDto = {
				status: this.cancelType.getValue(),
				shiftDate: DateHelper.getCurrentDayCode(),
				timezoneOffset: DateHelper.getTimezoneOffset(),
				dialogId: this.refs.message.getDialogId(),
				cancelReason: this.refs.message.getMessage(),
				skipTm: true,
			};

			void ShiftManager.addShift(shiftDto, this.user.departments);

			AvaMenu.setCounter({ elemId: 'check_in', value: '0' });
			this.showToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_NOT_WORKING_TOAST'));
			this.closeLayout();
			this.sendAnalytics();
		}

		// eslint-disable-next-line consistent-return
		cancelWorkingDay()
		{
			this.refs.message?.blur();

			if (this.hasEmptyDialogId())
			{
				return this.refs.message.openChatSelector();
			}

			if (!this.refs.message?.getMessage())
			{
				return this.showToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_REASON_EMPTY_REASON_TOAST'), true);
			}

			const shiftDto = {
				...this.shift.getDto(),
				status: this.cancelType.getValue(),
				dialogId: this.refs.message.getDialogId(),
				cancelReason: this.refs.message.getMessage(),
				dateCancel: DateHelper.getDateCode(new Date()),
			};

			void ShiftManager.updateShift(shiftDto, this.user.departments);

			this.showToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_TOAST'));
			this.sendAnalytics();
			this.closeLayout();
		}

		hasEmptyDialogId()
		{
			return this.refs.message
				&& this.refs.message.canTypeMessage()
				&& !this.refs.message.getDialogId()
			;
		}

		closeLayout()
		{
			this.refs.layoutWidget?.close(() => {
				if (this.props.onLayoutClose)
				{
					this.props.onLayoutClose();
				}
			});
		}

		showToast(message, alert = false)
		{
			this.previousToast?.close();

			this.previousToast = showToast({
				message,
				svg: {
					content: alert ? cross() : check(),
				},
				backgroundColor: alert
					? Color.accentMainAlert.toHex()
					: Color.bgContentInapp.toHex()
				,
			});

			if (alert)
			{
				Haptics.notifyFailure();
			}
			else
			{
				Haptics.notifySuccess();
			}
		}

		sendAnalytics()
		{
			Analytics.sendCheckIn(CheckinSentEnum.CANCELLED, {
				geoSent: false,
				chatSent: Boolean(this.state.dialogId),
				imageSent: false,
			});
		}

		isCustomReason()
		{
			return this.state.selectedReason === CancelReasonEnum.CUSTOM.getValue();
		}
	}

	module.exports = { CancelReasonPage };
});
