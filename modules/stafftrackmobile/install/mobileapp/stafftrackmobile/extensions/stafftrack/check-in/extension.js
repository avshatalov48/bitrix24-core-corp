/**
 * @module stafftrack/check-in
 */
jn.define('stafftrack/check-in', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Indent } = require('tokens');
	const { H3 } = require('ui-system/typography/heading');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');

	const { Skeleton } = require('stafftrack/check-in/skeleton');
	const { CheckInPage } = require('stafftrack/check-in/pages/check-in');
	const { DisabledCheckInPage } = require('stafftrack/check-in/pages/disabled-check-in');
	const { HeightManager } = require('stafftrack/check-in/height-manager');
	const { MoreMenu } = require('stafftrack/check-in/more-menu');
	const { StatisticsMenu } = require('stafftrack/check-in/statistics-menu');
	const { ContinueButton } = require('stafftrack/check-in/continue-button');
	const { DateHelper } = require('stafftrack/date-helper');
	const { ShiftManager } = require('stafftrack/data-managers/shift-manager');
	const { SettingsManager } = require('stafftrack/data-managers/settings-manager');
	const { OptionManager, OptionEnum } = require('stafftrack/data-managers/option-manager');
	const { ShiftModel } = require('stafftrack/model/shift');
	const { MuteEnum } = require('stafftrack/model/counter');
	const { Analytics, CheckinOpenEnum, HelpdeskEnum } = require('stafftrack/analytics');
	const { SettingsPage } = require('stafftrack/check-in/pages/settings');

	const { PureComponent } = require('layout/pure-component');

	class CheckIn extends PureComponent
	{
		/**
		 * @param props {{dialogId: string, dialogName: string, openSettings: boolean, layoutWidget: LayoutWidget}}
		 */
		constructor(props)
		{
			super(props);

			this.layoutWidget = this.props.layoutWidget || PageManager;
			this.heightManager = new HeightManager(this.layoutWidget);

			this.state = {
				loading: true,
				config: null,
				options: null,
				shift: null,
				dialogInfo: {
					dialogName: this.props.dialogName,
					dialogId: this.props.dialogId,
				},
				userInfo: null,
				counter: null,
				enabledBySettings: true,
			};

			this.onKeyboardWillShowHandler = this.onKeyboardWillShowHandler.bind(this);
			this.onKeyboardWillHideHandler = this.onKeyboardWillHideHandler.bind(this);

			this.closeLayoutWidget = this.closeLayoutWidget.bind(this);
			this.showMoreMenu = this.showMoreMenu.bind(this);
			this.showStatisticsMenu = this.showStatisticsMenu.bind(this);
			this.openHelp = this.openHelp.bind(this);
			this.load = this.load.bind(this);

			this.settingsOpened = false;
			this.statisticsMenu = null;
			this.moreMenu = null;

			this.refs = {
				checkIn: null,
				disabledCheckIn: null,
				statisticsMenu: null,
				moreMenu: null,
				continueButton: null,
			};

			Analytics.sendCheckinOpen(props.dialogId ? CheckinOpenEnum.CHAT : CheckinOpenEnum.AVA_MENU);
		}

		get user()
		{
			return this.state.userInfo ?? {};
		}

		get dialogInfo()
		{
			return this.state.dialogInfo ?? {};
		}

		get shift()
		{
			return this.state.shift ?? {};
		}

		get config()
		{
			return this.state.config ?? {};
		}

		isCounterMuted()
		{
			return this.state.counter?.muteStatus !== MuteEnum.DISABLED.toNumber();
		}

		componentDidMount()
		{
			this.bindEvents();

			void this.load();
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		bindEvents()
		{
			Keyboard.on(Keyboard.Event.WillShow, this.onKeyboardWillShowHandler);
			Keyboard.on(Keyboard.Event.WillHide, this.onKeyboardWillHideHandler);

			SettingsManager.on('updated', this.load);
		}

		unbindEvents()
		{
			Keyboard.off(Keyboard.Event.WillShow, this.onKeyboardWillShowHandler);
			Keyboard.off(Keyboard.Event.WillHide, this.onKeyboardWillHideHandler);

			SettingsManager.off('updated', this.load);
		}

		async load()
		{
			const data = await ShiftManager.getMain(DateHelper.getCurrentDayCode());

			const currentShift = BX.prop.getObject(data, 'currentShift', {});

			const state = {
				loading: false,
				enabledBySettings: SettingsManager.isEnabledBySettings(),
				config: BX.prop.getObject(data, 'config', {}),
				options: BX.prop.getObject(data, 'options', {}),
				userInfo: BX.prop.getObject(data, 'userInfo', {}),
				counter: BX.prop.getObject(data, 'counter', {}),
				shift: new ShiftModel(currentShift),
			};

			this.heightManager.setStatus(state.shift.getStatus());
			this.heightManager.setEnabledBySettings(state.config.enabledBySettings);
			this.heightManager.updateSheetHeight();

			if (Type.isNil(this.dialogInfo.dialogName) && Type.isNil(this.dialogInfo.dialogId))
			{
				state.dialogInfo = state.config.dialogInfo;
			}

			this.setState(state, () => {
				this.handleFirstHelpView();
				this.handleSettingsPage();
			});
		}

		onKeyboardWillShowHandler()
		{
			this.refs.continueButton?.show();
		}

		onKeyboardWillHideHandler()
		{
			this.refs.continueButton?.hide();
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
				},
				this.renderContent(),
				this.renderContinueButton(),
			);
		}

		renderContent()
		{
			return Box(
				{
					backgroundColor: Color.bgSecondary,
					style: {
						flex: 1,
					},
				},
				this.state.loading && Skeleton(),
				!this.state.loading && this.renderHeader(),
				this.renderCheckIn(),
				this.renderDisabledCheckIn(),
			);
		}

		renderCheckIn()
		{
			if (!this.state.loading && this.state.enabledBySettings)
			{
				return new CheckInPage({
					...this.state,
					layoutWidget: this.layoutWidget,
					heightManager: this.heightManager,
					ref: (ref) => {
						this.refs.checkIn = ref;
					},
				});
			}

			return null;
		}

		renderDisabledCheckIn()
		{
			if (!this.state.loading && !this.state.enabledBySettings)
			{
				return new DisabledCheckInPage({
					layoutWidget: this.layoutWidget,
					isAdmin: this.user.isAdmin,
					onHelpClick: this.openHelp,
					onDepartmentHeadChatButtonClick: this.closeLayoutWidget,
					userInfo: this.state.userInfo,
					ref: (ref) => {
						this.refs.disabledCheckIn = ref;
					},
				});
			}

			return null;
		}

		renderHeader()
		{
			return Area(
				{
					isFirst: true,
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
					},
				},
				H3({
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_TITLE'),
				}),
				this.renderHeaderRightIcons(),
			);
		}

		renderHeaderRightIcons()
		{
			if (this.state.enabledBySettings)
			{
				return View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					this.renderStatisticsChip(),
					this.renderMoreMenuIcon(),
				);
			}

			return null;
		}

		renderStatisticsChip()
		{
			return View(
				{
					ref: (ref) => {
						this.refs.statisticsMenu = ref;
					},
				},
				ChipButton({
					testId: 'stafftrack-check-in-statistics',
					design: ChipButtonDesign.GREY,
					mode: ChipButtonMode.OUTLINE,
					dropdown: true,
					compact: true,
					color: Color.base3,
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_STATISTICS'),
					onClick: this.showStatisticsMenu,
				}),
			);
		}

		renderMoreMenuIcon()
		{
			return View(
				{
					style: {
						marginLeft: Indent.S.toNumber(),
					},
					ref: (ref) => {
						this.refs.moreMenu = ref;
					},
				},
				IconView({
					size: 24,
					icon: Icon.MORE,
					color: Color.base4,
					onClick: this.showMoreMenu,
				}),
			);
		}

		renderContinueButton()
		{
			return new ContinueButton({
				ref: (ref) => {
					this.refs.continueButton = ref;
				},
			});
		}

		showStatisticsMenu()
		{
			this.statisticsMenu ??= new StatisticsMenu({
				layoutWidget: this.layoutWidget,
				user: this.user,
			});

			this.statisticsMenu.show(this.refs.statisticsMenu);
		}

		showMoreMenu()
		{
			this.moreMenu ??= new MoreMenu({
				layoutWidget: this.layoutWidget,
				isMuted: this.isCounterMuted(),
				onHelpClick: this.openHelp,
				isAdmin: this.user.isAdmin,
				hasShift: this.hasCurrentShift(),
				timemanAvailable: this.config.timemanAvailable,
			});

			this.moreMenu.show(this.refs.moreMenu);
		}

		handleFirstHelpView()
		{
			if (OptionManager.getOption(OptionEnum.IS_FIRST_HELP_VIEWED) === false)
			{
				this.openHelp();
				OptionManager.handleFirstHelpView();
			}
		}

		handleSettingsPage()
		{
			if (this.props.openSettings && !this.settingsOpened)
			{
				new SettingsPage({ isAdmin: this.user.isAdmin }).show(this.layoutWidget);
				this.settingsOpened = true;
			}
		}

		hasCurrentShift()
		{
			return Type.isNumber(this.shift.id) && this.shift.id !== 0;
		}

		openHelp()
		{
			helpdesk.openHelpArticle('20922794');

			Analytics.sendHelpdeskOpen(this.props.dialogId ? HelpdeskEnum.CHAT : HelpdeskEnum.AVA_MENU);
		}

		closeLayoutWidget()
		{
			this.layoutWidget.close();
		}
	}

	module.exports = { CheckIn };
});
