/**
 * @module stafftrack/check-in/pages/check-in
 */
jn.define('stafftrack/check-in/pages/check-in', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent, Component } = require('tokens');
	const { showToast } = require('toast');
	const { outline: { check, cross } } = require('assets/icons');
	const { Haptics } = require('haptics');
	const { confirmDefaultAction } = require('alert');

	const { Button, Icon } = require('ui-system/form/buttons/button');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Area } = require('ui-system/layout/area');
	const { Link3, LinkDesign, LinkMode } = require('ui-system/blocks/link');
	const { Text3, Text2 } = require('ui-system/typography/text');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { DialogFooter } = require('ui-system/layout/dialog-footer');

	const { CancelReasonPage } = require('stafftrack/check-in/pages/cancel-reason');
	const { Message } = require('stafftrack/check-in/message');
	const { CancelReasonView } = require('stafftrack/check-in/cancel-reason-view');
	const { CancelReasonMenu } = require('stafftrack/check-in/cancel-reason-menu');
	const { MapView } = require('stafftrack/map');
	const { DateHelper } = require('stafftrack/date-helper');
	const { ShiftManager } = require('stafftrack/data-managers/shift-manager');
	const { ShiftModel, StatusEnum } = require('stafftrack/model/shift');
	const { MuteEnum } = require('stafftrack/model/counter');
	const { Analytics, CheckinSentEnum } = require('stafftrack/analytics');
	const { AvaMenu } = require('ava-menu');
	const { OptionManager, OptionEnum } = require('stafftrack/data-managers/option-manager');
	const { SettingsManager } = require('stafftrack/data-managers/settings-manager');
	const { Entry } = require('stafftrack/entry');

	const { PureComponent } = require('layout/pure-component');

	class CheckInPage extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				shift: props.shift,
			};

			this.refs = {
				layoutWidget: this.props.layoutWidget || PageManager,
				cancelDay: null,
				message: null,
				location: null,
				buttonContainer: null,
			};

			this.previousToast = null;
			this.confirmStartWorkDayResult = null;
			this.confirmExpiredWorkDayResult = null;

			this.startWorkingDay = this.startWorkingDay.bind(this);
			this.startNotWorkingDay = this.startNotWorkingDay.bind(this);
			this.cancelWorkingDay = this.cancelWorkingDay.bind(this);
			this.closeLayout = this.closeLayout.bind(this);
		}

		/**
		 * @return {HeightManager}
		 */
		get heightManager()
		{
			return this.props.heightManager;
		}

		/**
		 * @returns {Object}
		 */
		get user()
		{
			return this.props.userInfo ?? {};
		}

		/**
		 * @return {ShiftModel}
		 */
		get shift()
		{
			return this.state.shift;
		}

		/**
		 * @returns {Object}
		 */
		get dialogInfo()
		{
			return this.props.dialogInfo;
		}

		/**
		 *
		 * @returns {Object}
		 */
		get config()
		{
			return this.props.config || {};
		}

		/**
		 *
		 * @returns {*}
		 */
		get diskFolderId()
		{
			return this.config.diskFolderId;
		}

		/**
		 *
		 * @returns {Boolean}
		 */
		get timemanAvailable()
		{
			return this.config.timemanAvailable ?? false;
		}

		isCounterMuted()
		{
			return this.props.counter?.muteStatus !== MuteEnum.DISABLED.toNumber();
		}

		componentDidMount()
		{
			this.bindEvents();
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		bindEvents()
		{
			ShiftManager.on('updated', this.updateState);
		}

		unbindEvents()
		{
			ShiftManager.off('updated', this.updateState);
		}

		updateState = async () => {
			const { currentShift } = await ShiftManager.getMain(DateHelper.getCurrentDayCode());

			const shift = new ShiftModel(currentShift);

			this.heightManager.setStatus(shift.getStatus());

			this.setState({ shift }, () => this.heightManager.updateSheetHeight());
		};

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
					safeArea: {
						bottom: true,
					},
				},
				this.renderContent(),
				this.shift.isEmptyStatus() && this.renderDialogFooter(),
				this.shift.isWorkingStatus() && this.renderCancelButton(),
			);
		}

		renderContent()
		{
			return ScrollView(
				{
					testId: 'stafftrack-shift',
					style: {
						flex: 1,
					},
				},
				Area(
					{
						isFirst: true,
						excludePaddingSide: {
							horizontal: true,
						},
					},
					this.shift.getStatus() && this.renderShiftStatus(),
					this.shift.isCancelOrNotWorkingStatus() && this.renderCancelReason(),
					!this.shift.isCancelOrNotWorkingStatus() && this.renderMessage(),
					!this.shift.isCancelOrNotWorkingStatus() && this.renderLocation(),
				),
			);
		}

		renderShiftStatus()
		{
			return View(
				{
					style: {
						paddingBottom: Indent.XL3.toNumber(),
						paddingHorizontal: Component.paddingLr.toNumber(),
					},
				},
				Card(
					{
						testId: 'stafftrack-shift-status-card',
						design: this.shift.isCancelOrNotWorkingStatus()
							? CardDesign.WARNING
							: CardDesign.ACCENT
						,
					},
					this.renderShiftStatusContent(),
				),
			);
		}

		renderShiftStatusContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
					},
				},
				Text2({
					text: this.getShiftStatusTitle(),
					color: Color.base1,
				}),
				Text3({
					text: this.shift.isCancelOrNotWorkingStatus()
						? DateHelper.formatTime(this.shift.getDateCancel())
						: DateHelper.formatTime(this.shift.getDateCreate()),
					color: this.shift.isCancelOrNotWorkingStatus()
						? Color.accentMainWarning
						: Color.accentMainPrimary
					,
				}),
			);
		}

		getShiftStatusTitle()
		{
			if (this.shift.isWorkingStatus())
			{
				return Loc.getMessage('M_STAFFTRACK_CHECK_IN_WORKING_STATUS');
			}

			if (this.shift.isNotWorkingStatus())
			{
				return Loc.getMessage('M_STAFFTRACK_CHECK_IN_NOT_WORKING_STATUS');
			}

			return Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL_STATUS');
		}

		renderCancelReason()
		{
			return View(
				{
					style: {
						paddingHorizontal: Component.paddingLr.toNumber(),
					},
				},
				CancelReasonView({
					userInfo: this.user,
					cancelReason: this.shift.getCancelReason(),
				}),
			);
		}

		renderMessage()
		{
			return new Message({
				layoutWidget: this.refs.layoutWidget,
				readOnly: this.shift.isWorkingStatus() || this.shift.isCancelOrNotWorkingStatus(),
				sendMessage: OptionManager.getOption(OptionEnum.SEND_MESSAGE),
				userInfo: this.user,
				isCancelReason: false,
				defaultValue: OptionManager.getOption(OptionEnum.DEFAULT_MESSAGE) || Loc.getMessage('M_STAFFTRACK_CHECK_IN_DEFAULT_MESSAGE'),
				placeholder: Loc.getMessage('M_STAFFTRACK_CHECK_IN_MESSAGE_PLACEHOLDER'),
				dialogId: this.dialogInfo.dialogId,
				dialogName: this.dialogInfo.dialogName,
				diskFolderId: this.diskFolderId,
				ref: (ref) => {
					this.refs.message = ref;
				},
			});
		}

		renderLocation()
		{
			return Area(
				{},
				new MapView({
					layoutWidget: this.refs.layoutWidget,
					sendGeo: SettingsManager.isGeoEnabled() ? OptionManager.getOption(OptionEnum.SEND_GEO) : false,
					isFirstHelpViewed: OptionManager.getOption(OptionEnum.IS_FIRST_HELP_VIEWED),
					readOnly: this.shift.isWorkingStatus() || this.shift.isCancelOrNotWorkingStatus(),
					location: this.shift.getLocation() || OptionManager.getOption(OptionEnum.DEFAULT_LOCATION),
					customLocation: OptionManager.getOption(OptionEnum.DEFAULT_CUSTOM_LOCATION),
					geoImageUrl: this.shift.getGeoImageUrl(),
					address: this.shift.getAddress(),
					userInfo: this.user,
					ref: (ref) => {
						this.refs.location = ref;
					},
				}),
			);
		}

		renderDialogFooter()
		{
			return DialogFooter(
				{
					keyboardButton: {
						text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CONTINUE'),
						testId: 'stafftrack-checkin-continue-button',
						onClick: () => Keyboard.dismiss(),
					},
				},
				this.renderStartDayButton(),
				this.renderNotWorkingButton(),
			);
		}

		renderStartDayButton()
		{
			return Button({
				leftIcon: Icon.PLAY,
				text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_START_WORKDAY'),
				onClick: this.startWorkingDay,
				color: Color.baseWhiteFixed,
				backgroundColor: Color.accentMainPrimary,
				stretched: true,
				testId: 'stafftrack-start-day-button',
				badge: this.isCounterMuted()
					? null
					: BadgeCounter({
						testId: 'stafftrack-start-day-button-badge',
						value: 1,
						design: BadgeCounterDesign.ALERT,
					})
				,
			});
		}

		renderNotWorkingButton()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						height: 50,
						marginTop: Indent.M.toNumber(),
					},
					ref: (ref) => {
						this.refs.cancelDay = ref;
					},
					onClick: this.showStartNotWorkingDayMenu,
				},
				Link3({
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_NOT_WORKING'),
					design: LinkDesign.GREY,
					mode: LinkMode.DASH,
					size: 3,
					useInAppLink: false,
					testId: 'stafftrack-not-working-button',
					onClick: this.showStartNotWorkingDayMenu,
				}),
			);
		}

		renderCancelButton()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						height: 50,
						marginBottom: Indent.XL2.toNumber(),
					},
					ref: (ref) => {
						this.refs.cancelDay = ref;
					},
					onClick: this.showCancelWorkingDayMenu,
				},
				Link3({
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_CANCEL'),
					design: LinkDesign.GREY,
					mode: LinkMode.DASH,
					useInAppLink: false,
					testId: 'stafftrack-cancel-day-button',
					onClick: this.showCancelWorkingDayMenu,
				}),
			);
		}

		// eslint-disable-next-line consistent-return
		async startWorkingDay()
		{
			this.refs.message?.blur();

			if (this.hasEmptyDialogId())
			{
				return this.refs.message.openChatSelector();
			}

			if (this.hasEmptyCustomLocation())
			{
				return this.showCheckInToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_EMPTY_LOCATION_TOAST'), true);
			}

			if (this.isTimemanDayExpired())
			{
				return this.showConfirmExpiredWorkDay();
			}

			if (this.isNotWorkingDay() && !this.isTimemanDayOpened())
			{
				return this.showConfirmStartWorkDay();
			}

			const shiftDto = {
				status: StatusEnum.WORKING.getValue(),
				shiftDate: DateHelper.getCurrentDayCode(),
				timezoneOffset: DateHelper.getTimezoneOffset(),
				location: this.refs.location?.getLocation(),
				geoImageUrl: this.refs.location?.getGeoImage(),
				address: this.refs.location?.getAddress(),
				dialogId: this.refs.message?.getDialogId(),
				message: this.refs.message?.getMessage(),
				imageFileId: this.refs.message?.getFileId(),
				skipTm: this.hasToSkipTimeman(),
			};

			this.showCheckInToast();
			this.removeCounterFromAvaMenu();
			this.closeLayout();

			void ShiftManager.addShift(shiftDto, this.user.departments);

			Analytics.sendCheckIn(CheckinSentEnum.DONE, {
				geoSent: Boolean(shiftDto.address),
				chatSent: Boolean(shiftDto.dialogId),
				imageSent: Boolean(shiftDto.imageFileId),
			});
		}

		showStartNotWorkingDayMenu = () => {
			this.showCancelReasonMenu(this.startNotWorkingDay);
		};

		showCancelWorkingDayMenu = () => {
			this.showCancelReasonMenu(this.cancelWorkingDay);
		};

		// eslint-disable-next-line consistent-return
		startNotWorkingDay(cancelReason)
		{
			this.openCancelReasonPage(cancelReason, StatusEnum.NOT_WORKING);
		}

		cancelWorkingDay(cancelReason)
		{
			if (!this.shift.getId())
			{
				return;
			}

			this.openCancelReasonPage(cancelReason, StatusEnum.CANCEL_WORKING);
		}

		hasEmptyDialogId()
		{
			return this.refs.message
				&& this.refs.message.canTypeMessage()
				&& !this.refs.message.getDialogId()
			;
		}

		hasEmptyCustomLocation()
		{
			return this.refs.location
				&& this.refs.location.isCustomLocationSelected()
				&& !this.refs.location.getLocation()
			;
		}

		showCheckInToast(message = Loc.getMessage('M_STAFFTRACK_CHECK_IN_SENT_TOAST'), alert = false)
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

		removeCounterFromAvaMenu()
		{
			AvaMenu.setCounter({ elemId: 'check_in', value: '0' });
		}

		closeLayout()
		{
			this.refs.layoutWidget?.close();
		}

		showCancelReasonMenu(callback)
		{
			this.cancelReasonMenu ??= new CancelReasonMenu({
				layoutWidget: this.refs.layoutWidget,
				onItemSelected: callback,
			});

			this.cancelReasonMenu.show(this.refs.cancelDay);
		}

		openCancelReasonPage(selectedReason, cancelType)
		{
			Haptics.impactMedium();

			const cancelReason = new CancelReasonPage({
				userInfo: this.user,
				shift: this.shift,
				selectedReason,
				cancelType,
				dialogId: this.refs.message?.getCurrentDialogId(),
				dialogName: this.refs.message?.getCurrentDialogName(),
				onLayoutClose: this.closeLayout,
			});

			cancelReason.show(this.refs.layoutWidget);
		}

		showConfirmStartWorkDay()
		{
			Haptics.notifyWarning();

			confirmDefaultAction({
				title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_START_WORKDAY_CONFIRM_TITLE'),
				description: Loc.getMessage('M_STAFFTRACK_CHECK_IN_START_WORKDAY_CONFIRM_DESCRIPTION'),
				actionButtonText: Loc.getMessage('M_STAFFTRACK_CHECK_IN_START_WORKDAY_CONFIRM_ACTION'),
				cancelButtonText: Loc.getMessage('M_STAFFTRACK_CHECK_IN_START_WORKDAY_CONFIRM_CANCEL'),
				onAction: () => {
					this.confirmStartWorkDayResult = false;
					void this.startWorkingDay();
				},
				onCancel: () => {
					this.confirmStartWorkDayResult = true;
					void this.startWorkingDay();
				},
			});
		}

		showConfirmExpiredWorkDay()
		{
			Haptics.notifyWarning();

			confirmDefaultAction({
				title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_EXPIRED_WORKDAY_CONFIRM_TITLE'),
				description: Loc.getMessage('M_STAFFTRACK_CHECK_IN_EXPIRED_WORKDAY_CONFIRM_DESCRIPTION'),
				actionButtonText: Loc.getMessage('M_STAFFTRACK_CHECK_IN_EXPIRED_WORKDAY_CONFIRM_CANCEL'),
				cancelButtonText: Loc.getMessage('M_STAFFTRACK_CHECK_IN_EXPIRED_WORKDAY_CONFIRM_ACTION'),
				onAction: () => {
					this.confirmExpiredWorkDayResult = true;
					void this.startWorkingDay();
				},
				onCancel: () => {
					this.confirmExpiredWorkDayResult = false;
					Entry.openTimemanPage();
				},
			});
		}

		isNotWorkingDay()
		{
			return this.timemanAvailable
				&& this.config.isNotWorkingDay
				&& OptionManager.getOption(OptionEnum.TIMEMAN_INTEGRATION_ENABLED)
				&& this.confirmStartWorkDayResult === null
			;
		}

		isTimemanDayExpired()
		{
			return this.timemanAvailable
				&& this.config.isTimemanDayExpired
				&& OptionManager.getOption(OptionEnum.TIMEMAN_INTEGRATION_ENABLED)
				&& this.confirmExpiredWorkDayResult === null
			;
		}

		isTimemanDayOpened()
		{
			return this.config.isTimemanDayOpened;
		}

		hasToSkipTimeman()
		{
			if (this.confirmExpiredWorkDayResult !== null || this.confirmStartWorkDayResult !== null)
			{
				return this.confirmExpiredWorkDayResult || this.confirmStartWorkDayResult;
			}

			return OptionManager.getOption(OptionEnum.TIMEMAN_INTEGRATION_ENABLED) === false;
		}
	}

	module.exports = { CheckInPage };
});
