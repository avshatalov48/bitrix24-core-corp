/**
 * @module crm/timeline/scheduler/providers/sms/settings-menu
 */
jn.define('crm/timeline/scheduler/providers/sms/settings-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { ProviderSelector } = require('crm/timeline/scheduler/providers/sms/provider-selector');
	const { NumberSelector } = require('crm/timeline/scheduler/providers/sms/number-selector');

	/**
	 * @class SettingsMenu
	 */
	class SettingsMenu
	{
		constructor({
			senders,
			currentSender,
			contactCenterUrl,
			phone,
			onChangeSenderCallback,
			onChangePhoneCallback,
		})
		{
			this.providerSelector = null;
			this.numberSelector = null;

			this.senders = senders;
			this.currentSender = currentSender;
			this.contactCenterUrl = contactCenterUrl;
			this.phone = phone;
			this.name = currentSender.shortName;

			this.onChangeSenderCallback = ({ sender, phone }) => {
				this.update({
					phone,
					name: sender.shortName,
				});

				onChangeSenderCallback({ sender, phone });
			};

			this.onChangePhoneCallback = ({ phone }) => {
				this.update({ phone });

				onChangePhoneCallback({ phone });
			};

			this.settingsMenu = new ContextMenu(this.getMenuConfig());
		}

		showProviderSelector()
		{
			if (!this.providerSelector)
			{
				this.providerSelector = new ProviderSelector({
					senders: this.senders,
					contactCenterUrl: this.contactCenterUrl,
					currentSender: this.currentSender,
					onChangeSenderCallback: this.onChangeSenderCallback,
				});
			}

			this.providerSelector.show(this.layout);
		}

		showNumberSelector()
		{
			if (!this.numberSelector)
			{
				this.numberSelector = new NumberSelector({
					sender: this.currentSender,
					currentPhone: this.phone,
					onChangePhoneCallback: this.onChangePhoneCallback,
				});
			}

			this.numberSelector.show(this.layout);
		}

		getMenuConfig()
		{
			return {
				testId: 'SMS_SETTINGS_MENU',
				actions: this.getSettingsMenuActions(),
				params: {
					shouldResizeContent: true,
					showCancelButton: true,
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_TITLE'),
				},
			};
		}

		getSettingsMenuActions()
		{
			return [
				this.getProviderAction(),
				this.getPhoneAction(),
			];
		}

		getProviderAction()
		{
			return {
				id: 'sms-settings-provider',
				title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_PROVIDER'),
				subtitle: this.name,
				onClickCallback: () => {
					this.showProviderSelector();
					return Promise.resolve({
						closeMenu: false,
					});
				},
			};
		}

		getPhoneAction()
		{
			return {
				id: 'sms-settings-phone',
				title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_PHONE'),
				subtitle: this.phone,
				onClickCallback: () => {
					this.showNumberSelector();

					return Promise.resolve({ closeMenu: false });
				},
			};
		}

		show(parentWidget = PageManager)
		{
			void this.settingsMenu.show(parentWidget);
		}

		get layout()
		{
			return this.settingsMenu.layoutWidget;
		}

		get actionsBySections()
		{
			return this.settingsMenu.actionsBySections;
		}

		update({ name, phone })
		{
			let needRerender = false;

			if (Type.isStringFilled(name))
			{
				needRerender = true;
				this.name = name;
				this.numberSelector = null;
			}

			if (Type.isStringFilled(phone))
			{
				needRerender = true;
				this.phone = phone;
			}

			if (needRerender)
			{
				this.settingsMenu.rerender(this.getMenuConfig());
			}
		}
	}

	module.exports = { SettingsMenu };
});
