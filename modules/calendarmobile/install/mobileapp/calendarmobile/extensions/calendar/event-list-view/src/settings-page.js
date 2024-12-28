/**
 * @module calendar/event-list-view/settings-page
 */
jn.define('calendar/event-list-view/settings-page', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');

	const { Box } = require('ui-system/layout/box');
	const { SettingSelector } = require('ui-system/blocks/setting-selector');
	const { Card } = require('ui-system/layout/card');

	const { SettingsManager } = require('calendar/data-managers/settings-manager');
	const { State, observeState } = require('calendar/event-list-view/state');

	class SettingsPage extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.layoutWidget = null;

			this.handleShowDeclinedSwitcherClick = this.handleShowDeclinedSwitcherClick.bind(this);
			this.handleShowWeekNumbersSwitcherClick = this.handleShowWeekNumbersSwitcherClick.bind(this);
			this.handleDenyBusyInvitationSwitcherClick = this.handleDenyBusyInvitationSwitcherClick.bind(this);
		}

		show(parentLayout = PageManager)
		{
			void new BottomSheet({
				component: this,
				titleParams: {
					text: Loc.getMessage('M_CALENDAR_EVENT_LIST_MORE_MENU_SETTINGS'),
					type: 'dialog',
				},
			})
				.setParentWidget(parentLayout)
				.setMediumPositionPercent(60)
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.setBackgroundColor(Color.bgSecondary.toHex())
				.disableContentSwipe()
				.open()
				.then((widget) => {
					this.layoutWidget = widget;
				})
			;
		}

		render()
		{
			return Box(
				{
					withPaddingHorizontal: true,
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						paddingVertical: Indent.M.toNumber(),
					},
				},
				this.renderShowDeclinedSection(),
				this.renderShowWeekNumbersSection(),
				this.renderDenyBusyInvitationSection(),
			);
		}

		renderShowDeclinedSection()
		{
			return Card(
				{
					border: true,
					testId: 'calendar-settings-show-declined',
				},
				SettingSelector({
					numberOfLinesTitle: 3,
					title: Loc.getMessage('M_CALENDAR_EVENT_LIST_MORE_MENU_SETTINGS_SHOW_DECLINED'),
					testId: 'calendar-settings-show-declined-switcher',
					checked: this.props.showDeclined,
					onClick: this.handleShowDeclinedSwitcherClick,
				}),
			);
		}

		renderShowWeekNumbersSection()
		{
			return Card(
				{
					style: {
						marginTop: Indent.XL.toNumber(),
					},
					border: true,
					testId: 'calendar-settings-show-week-numbers',
				},
				SettingSelector({
					numberOfLinesTitle: 3,
					title: Loc.getMessage('M_CALENDAR_EVENT_LIST_MORE_MENU_SETTINGS_SHOW_WEEK_NUMBERS'),
					testId: 'calendar-settings-show-week-numbers-switcher',
					checked: this.props.showWeekNumbers,
					onClick: this.handleShowWeekNumbersSwitcherClick,
				}),
			);
		}

		renderDenyBusyInvitationSection()
		{
			return Card(
				{
					style: {
						marginTop: Indent.XL.toNumber(),
					},
					border: true,
					testId: 'calendar-settings-deny-busy-invitation',
				},
				SettingSelector({
					numberOfLinesTitle: 3,
					title: Loc.getMessage('M_CALENDAR_EVENT_LIST_MORE_MENU_SETTINGS_DENY_BUSY_INVITATION'),
					testId: 'calendar-settings-deny-busy-invitation-switcher',
					checked: this.props.denyBusyInvitation,
					onClick: this.handleDenyBusyInvitationSwitcherClick,
				}),
			);
		}

		handleShowDeclinedSwitcherClick()
		{
			const showDeclined = !this.props.showDeclined;
			SettingsManager.switchShowDeclined(showDeclined);
			Haptics.notifySuccess();

			State.setShowDeclined(showDeclined);
		}

		handleShowWeekNumbersSwitcherClick()
		{
			const showWeekNumbers = !this.props.showWeekNumbers;
			SettingsManager.switchShowWeekNumbers(showWeekNumbers);
			Haptics.notifySuccess();

			State.setShowWeekNumbers(showWeekNumbers);
		}

		handleDenyBusyInvitationSwitcherClick()
		{
			const denyBusyInvitation = !this.props.denyBusyInvitation;
			SettingsManager.switchDenyBusyInvitation(denyBusyInvitation);
			Haptics.notifySuccess();

			State.setDenyBusyInvitation(denyBusyInvitation);
		}
	}

	const mapStateToProps = (state) => ({
		showDeclined: state.showDeclined,
		showWeekNumbers: state.showWeekNumbers,
		denyBusyInvitation: state.denyBusyInvitation,
	});

	module.exports = { SettingsPage: observeState(SettingsPage, mapStateToProps) };
});
