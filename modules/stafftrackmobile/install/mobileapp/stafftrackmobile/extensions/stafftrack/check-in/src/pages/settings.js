/**
 * @module stafftrack/check-in/pages/settings
 */
jn.define('stafftrack/check-in/pages/settings', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
	const { Color, Indent } = require('tokens');
	const { showToast } = require('toast');
	const { confirmDestructiveAction } = require('alert');
	const { outline: { alert, cross } } = require('assets/icons');
	const { PureComponent } = require('layout/pure-component');
	const { Haptics } = require('haptics');
	const { CheckIn } = require('ava-menu');

	const { Area } = require('ui-system/layout/area');
	const { H3 } = require('ui-system/typography/heading');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Text2, Text4 } = require('ui-system/typography/text');
	const { Link4, LinkMode, LinkDesign } = require('ui-system/blocks/link');
	const { Card } = require('ui-system/layout/card');
	const { Switcher, SwitcherMode, SwitcherSize } = require('ui-system/blocks/switcher');

	const { HeightManager } = require('stafftrack/check-in/height-manager');
	const { SettingsManager } = require('stafftrack/data-managers/settings-manager');
	const { OptionManager, OptionEnum } = require('stafftrack/data-managers/option-manager');

	class SettingsPage extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				timemanEnabled: OptionManager.getOption(OptionEnum.TIMEMAN_INTEGRATION_ENABLED),
				geoEnabled: SettingsManager.isGeoEnabled(),
				checkInEnabled: SettingsManager.isEnabledBySettings(),
			};

			this.previousToast = null;

			this.handleTimemanSwitcherClick = this.handleTimemanSwitcherClick.bind(this);
			this.handleGeoSwitcherClick = this.handleGeoSwitcherClick.bind(this);
			this.onTurnOnButtonClick = this.onTurnOnButtonClick.bind(this);
			this.onTurnOffButtonClick = this.onTurnOffButtonClick.bind(this);
		}

		get layout()
		{
			return this.props.layout;
		}

		get isAdmin()
		{
			return this.props.isAdmin;
		}

		get userId()
		{
			return this.props.userInfo?.id || 0;
		}

		static show({ isAdmin, parentLayout = PageManager })
		{
			const component = (layout) => new this({ layout, isAdmin });

			void new BottomSheet({ component })
				.setParentWidget(parentLayout)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.disableContentSwipe()
				.setMediumPositionHeight(HeightManager.getDefaultHeight())
				.open()
			;
		}

		render()
		{
			return View(
				{
					testId: 'stafftrack-settings',
				},
				this.renderHeader(),
				this.renderContent(),
			);
		}

		renderHeader()
		{
			return Area(
				{
					isFirst: true,
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				IconView({
					icon: Icon.ARROW_TO_THE_LEFT,
					size: 24,
					color: Color.base4,
					onClick: this.closeLayout,
				}),
				H3({
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_HEADER'),
					style: {
						marginLeft: Indent.XL.toNumber(),
					},
				}),
			);
		}

		renderContent()
		{
			return View(
				{},
				SettingsManager.isTimemanAvailable() && this.renderTimemanSection(),
				this.renderGeoSection(),
				this.renderMainSection(),
			);
		}

		renderTimemanSection()
		{
			return Area(
				{
					title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_PERSONAL_SECTION_TITLE'),
					isFirst: true,
					excludePaddingSide: {
						bottom: true,
					},
				},
				this.renderTimeman(),
			);
		}

		renderGeoSection()
		{
			return Area(
				{
					title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_ADMIN_SECTION_TITLE'),
					isFirst: true,
				},
				this.renderGeo(),
			);
		}

		renderMainSection()
		{
			return Area(
				{
					isFirst: true,
				},
				this.renderMain(),
			);
		}

		renderTimeman()
		{
			return Card(
				{
					border: true,
					testId: 'stafftrack-settings-timeman',
				},
				this.renderTimemanTitle(),
				this.renderCardDescription(Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TIMEMAN_DESCRIPTION')),
			);
		}

		renderTimemanTitle()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						flexDirection: 'row',
						justifyContent: 'space-between',
						paddingBottom: Indent.L.toNumber(),
					},
				},
				Text2({
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TIMEMAN_TITLE'),
					color: Color.base1,
					style: {
						paddingRight: Indent.XL3.toNumber(),
						flex: 1,
					},
				}),
				View(
					{
						clickable: true,
						onClick: this.handleTimemanSwitcherClick,
					},
					Switcher({
						onClick: this.handleTimemanSwitcherClick,
						useState: false,
						mode: SwitcherMode.SOLID,
						size: SwitcherSize.L,
						checked: this.state.timemanEnabled,
						testId: 'stafftrack-settings-timeman-switcher',
					}),
				),
			);
		}

		renderCardDescription(text)
		{
			return Text4({
				text,
				color: Color.base3,
			});
		}

		renderGeo()
		{
			return Card(
				{
					border: true,
					testId: 'stafftrack-settings-geo',
					style: {
						opacity: this.isAdmin ? 1 : 0.5,
					},
				},
				this.renderGeoTitle(),
				this.renderCardDescription(Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_GEO_DESCRIPTION')),
			);
		}

		renderGeoTitle()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						flexDirection: 'row',
						justifyContent: 'space-between',
						paddingBottom: Indent.L.toNumber(),
					},
				},
				Text2({
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_GEO_TITLE'),
					color: Color.base1,
					style: {
						paddingRight: Indent.XL3.toNumber(),
						flex: 1,
					},
				}),
				View(
					{
						clickable: true,
						onClick: this.handleGeoSwitcherClick,
					},
					Switcher({
						onClick: this.handleGeoSwitcherClick,
						disabled: !this.isAdmin,
						useState: false,
						mode: SwitcherMode.SOLID,
						size: SwitcherSize.L,
						checked: this.state.geoEnabled,
						testId: 'stafftrack-settings-geo-switcher',
					}),
				),
			);
		}

		renderMain()
		{
			return Card(
				{
					border: true,
					testId: 'stafftrack-settings-main',
					style: {
						opacity: this.isAdmin ? 1 : 0.5,
					},
				},
				this.renderMainContent(),
			);
		}

		renderMainContent()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
					},
				},
				Text2({
					text: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TITLE'),
					color: Color.base1,
					style: {
						paddingRight: Indent.XL3.toNumber(),
					},
				}),
				Link4({
					testId: 'stafftrack-settings-main-link',
					onClick: this.state.checkInEnabled ? this.onTurnOffButtonClick : this.onTurnOnButtonClick,
					design: this.state.checkInEnabled ? LinkDesign.ALERT : LinkDesign.BLACK,
					mode: LinkMode.PLAIN,
					useInAppLink: false,
					text: this.state.checkInEnabled
						? Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TURN_OFF')
						: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TURN_ON')
					,
				}),
			);
		}

		handleTimemanSwitcherClick()
		{
			const timemanEnabled = !this.state.timemanEnabled;

			OptionManager.saveTimemanIntegrationEnabled(timemanEnabled);

			const message = timemanEnabled
				? Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TIMEMAN_TURNED_ON_TOAST')
				: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TIMEMAN_TURNED_OFF_TOAST')
			;

			this.showInfoToast(message);

			this.setState({ timemanEnabled });
		}

		handleGeoSwitcherClick()
		{
			if (!this.isAdmin)
			{
				this.showNotAdminToast();

				return;
			}

			const geoEnabled = !this.state.geoEnabled;

			if (geoEnabled)
			{
				this.enableGeo();
			}
			else
			{
				this.disableGeo();
			}

			this.setState({ geoEnabled });
		}

		enableGeo()
		{
			SettingsManager.turnCheckInGeoOn();

			this.showInfoToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_GEO_TURNED_ON_TOAST'));
		}

		disableGeo()
		{
			SettingsManager.turnCheckInGeoOff();

			this.showInfoToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_GEO_TURNED_OFF_TOAST'));
		}

		onTurnOnButtonClick()
		{
			if (!this.isAdmin)
			{
				this.showNotAdminToast();

				return;
			}

			this.turnCheckInSettingOn();
		}

		turnCheckInSettingOn()
		{
			SettingsManager.turnCheckInSettingOn();
			this.showInfoToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TURNED_ON_TOAST'));

			this.closeLayout();

			CheckIn.updateItemColor(true);
		}

		onTurnOffButtonClick()
		{
			if (!this.isAdmin)
			{
				this.showNotAdminToast();

				return;
			}

			confirmDestructiveAction({
				title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TURN_OFF_TITLE'),
				description: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TURN_OFF_DESCRIPTION'),
				destructionText: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TURN_OFF_CONFIRM'),
				onDestruct: () => this.turnCheckInSettingOff(),
			});
		}

		turnCheckInSettingOff()
		{
			SettingsManager.turnCheckInSettingOff();
			this.showInfoToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_TURNED_OFF_TOAST'));

			this.closeLayout();

			CheckIn.updateItemColor(false);
		}

		showInfoToast(message)
		{
			this.previousToast?.close();

			this.previousToast = showToast({
				message,
				svg: {
					content: alert(),
				},
				backgroundColor: Color.bgContentInapp.toHex(),
			});

			Haptics.notifySuccess();
		}

		showNotAdminToast()
		{
			this.previousToast?.close();

			this.previousToast = showToast({
				message: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_NOT_ADMIN_TOAST'),
				svg: {
					content: cross(),
				},
				backgroundColor: Color.bgContentInapp.toHex(),
			});

			Haptics.notifyWarning();
		}

		closeLayout = () => this.layout?.close();
	}

	module.exports = { SettingsPage };
});
