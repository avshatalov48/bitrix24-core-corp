/**
 * @module crm/timeline/ui/senders-selector
 */
jn.define('crm/timeline/ui/senders-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { get } = require('utils/object');
	const { ProviderSelector } = require('crm/timeline/ui/senders-selector/provider-selector');
	const { NumberSelector } = require('crm/timeline/ui/senders-selector/number-selector');

	/**
	 * @class SendersSelector
	 */
	class SendersSelector
	{
		constructor({
			senders,
			currentSender,
			contactCenterUrl,
			currentPhoneId,
			onChangeSenderCallback,
			onChangePhoneCallback,
		})
		{
			this.providerSelector = null;
			this.numberSelector = null;

			this.senders = senders;
			this.currentSender = currentSender;
			this.currentPhoneId = currentPhoneId;
			this.contactCenterUrl = contactCenterUrl;
			this.name = currentSender.shortName;

			// eslint-disable-next-line no-shadow
			this.onChangeSenderCallback = ({ sender, phoneId }) => {
				this.update({ sender, phoneId });
				onChangeSenderCallback({ sender, phoneId });
			};

			// eslint-disable-next-line no-shadow
			this.onChangePhoneCallback = ({ phoneId }) => {
				this.update({ phoneId });
				onChangePhoneCallback({ phoneId });
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
					phoneId: this.currentPhoneId,
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
					title: Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_TITLE'),
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
				title: Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_PROVIDER'),
				subtitle: this.name,
				onClickCallback: () => {
					this.showProviderSelector();
					return Promise.resolve({ closeMenu: false });
				},
			};
		}

		getPhoneAction()
		{
			const phone = this.getCurrentPhone();

			return {
				id: 'sms-settings-phone',
				title: Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_PHONE'),
				subtitle: get(phone, 'name', ''),
				onClickCallback: () => {
					this.showNumberSelector();
					return Promise.resolve({ closeMenu: false });
				},
			};
		}

		getCurrentPhone()
		{
			return this.currentSender.fromList.find((phone) => phone.id === this.currentPhoneId);
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

		update({ sender, phoneId })
		{
			let needRerender = false;

			if (sender)
			{
				this.currentSender = sender;
				const name = sender.shortName;

				if (Type.isStringFilled(name))
				{
					needRerender = true;
					this.name = name;
					this.numberSelector = null;
				}
			}

			if (Type.isStringFilled(phoneId))
			{
				needRerender = true;
				this.currentPhoneId = phoneId;
			}

			if (needRerender)
			{
				this.settingsMenu.rerender(this.getMenuConfig());
			}
		}
	}

	module.exports = { SendersSelector };
});
