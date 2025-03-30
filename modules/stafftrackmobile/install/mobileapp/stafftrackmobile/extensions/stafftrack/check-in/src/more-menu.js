/**
 * @module stafftrack/check-in/more-menu
 */
jn.define('stafftrack/check-in/more-menu', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { showToast } = require('toast');
	const { outline: { alert } } = require('assets/icons');
	const { Haptics } = require('haptics');
	const { AvaMenu } = require('ava-menu');

	const { Icon } = require('ui-system/blocks/icon');

	const { ShiftAjax } = require('stafftrack/ajax');
	const { MuteEnum } = require('stafftrack/model/counter');
	const { BaseMenu, baseSectionType } = require('stafftrack/base-menu');
	const { SettingsPage } = require('stafftrack/check-in/pages/settings');

	const helpSectionType = 'help';

	/**
	 * @class MoreMenu
	 */
	class MoreMenu extends BaseMenu
	{
		getItems()
		{
			return [
				this.getMutedItem(),
				this.getSettingsItem(),
				this.getHelpItem(),
			];
		}

		getMutedItem()
		{
			return {
				id: itemTypes.remind,
				sectionCode: baseSectionType,
				checked: this.props.isMuted === false,
				testId: 'stafftrack-settings-remind-menu',
				title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_REMIND'),
				iconName: Icon.SOUND_ON.getIconName(),
				styles: {
					icon: {
						color: Color.base3.toHex(),
					},
				},
			};
		}

		getSettingsItem()
		{
			return {
				id: itemTypes.settings,
				sectionCode: baseSectionType,
				testId: 'stafftrack-settings-settings-menu',
				title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_SETTINGS_MSGVER_1'),
				iconName: Icon.SETTINGS.getIconName(),
				styles: {
					icon: {
						color: Color.base3.toHex(),
					},
				},
			};
		}

		getHelpItem()
		{
			return {
				id: itemTypes.help,
				sectionCode: helpSectionType,
				testId: 'stafftrack-settings-show-help-menu',
				title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_HELP_MSGVER_1'),
				iconName: Icon.QUESTION.getIconName(),
				styles: {
					icon: {
						color: Color.base3.toHex(),
					},
				},
			};
		}

		getSections()
		{
			return [
				{
					id: baseSectionType,
					title: '',
				},
				{
					id: helpSectionType,
					title: '',
				},
			];
		}

		onItemSelected(item)
		{
			switch (item.id)
			{
				case itemTypes.help:
					this.showHelp();
					break;
				case itemTypes.remind:
					this.changeRemindStatus(item);
					break;
				case itemTypes.settings:
					this.openSettings();
					break;
				default:
					break;
			}
		}

		changeRemindStatus(item)
		{
			const remindStatus = !item.checked;

			if (remindStatus)
			{
				this.unmute();
			}
			else
			{
				this.mute();
			}
		}

		mute()
		{
			this.props.isMuted = true;
			ShiftAjax.muteCounter(MuteEnum.PERMANENT.toNumber());
			this.showInfoToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_DO_NOT_REMIND_TOAST'));

			AvaMenu.setCounter({ elemId: 'check_in', value: '0' });
		}

		unmute()
		{
			this.props.isMuted = false;
			ShiftAjax.muteCounter(MuteEnum.DISABLED.toNumber());
			this.showInfoToast(Loc.getMessage('M_STAFFTRACK_CHECK_IN_REMIND_TOAST'));

			if (this.props.hasShift === false)
			{
				AvaMenu.setCounter({ elemId: 'check_in', value: '1' });
			}
		}

		showInfoToast(message)
		{
			showToast({
				message,
				svg: {
					content: alert(),
				},
				backgroundColor: Color.bgContentInapp.toHex(),
			});

			Haptics.notifySuccess();
		}

		showHelp()
		{
			if (this.props.onHelpClick)
			{
				this.props.onHelpClick();
			}
		}

		openSettings()
		{
			const { isAdmin } = this.props;

			SettingsPage.show({
				isAdmin,
				parentLayout: this.layoutWidget,
			});
		}
	}

	const itemTypes = {
		help: 'help',
		settings: 'settings',
		remind: 'remind',
	};

	module.exports = { MoreMenu };
});
