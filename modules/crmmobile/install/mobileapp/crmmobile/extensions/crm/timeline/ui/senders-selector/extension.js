/**
 * @module crm/timeline/ui/senders-selector
 */
jn.define('crm/timeline/ui/senders-selector', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { get } = require('utils/object');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { ProviderSelector } = require('crm/timeline/ui/senders-selector/provider-selector');
	const { FromSelector } = require('crm/timeline/ui/senders-selector/from-selector');

	const SENDER_TYPE_EMAIL = 'EMAIL';

	/**
	 * @class SendersSelector
	 */
	class SendersSelector
	{
		constructor({
			senders,
			currentSender,
			contactCenterUrl,
			currentFromId,
			onChangeSenderCallback,
			onDisabledSenderClickCallback,
			onChangeFromCallback,
			smsAndMailSenders,
		})
		{
			this.providerSelector = null;
			this.fromSelector = null;

			this.senders = senders;
			this.currentSender = currentSender;
			this.currentFromId = currentFromId;
			this.contactCenterUrl = contactCenterUrl;
			this.name = currentSender.shortName;
			this.smsAndMailSenders = smsAndMailSenders || false;

			// eslint-disable-next-line no-shadow
			this.onChangeSenderCallback = ({ sender, fromId }) => {
				this.update({ sender, fromId });
				onChangeSenderCallback({ sender, fromId });
			};

			this.onDisabledSenderClickCallback = onDisabledSenderClickCallback;

			// eslint-disable-next-line no-shadow
			this.onChangeFromCallback = ({ fromId }) => {
				this.update({ fromId });
				onChangeFromCallback({ fromId });
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
					onDisabledSenderClickCallback: this.onDisabledSenderClickCallback,
				});
			}

			this.providerSelector.show(this.layout);
		}

		showFromSelector()
		{
			if (!this.fromSelector)
			{
				this.fromSelector = new FromSelector({
					sender: this.currentSender,
					fromId: this.currentFromId,
					onChangeFromCallback: this.onChangeFromCallback,
				});
			}

			this.fromSelector.show(this.layout);
		}

		getMenuConfig()
		{
			let title = Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_TITLE');
			if (this.smsAndMailSenders)
			{
				title = Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_TITLE_CHANNEL');
			}

			return {
				testId: 'crmmobile-senders-selector-menu',
				actions: this.getSettingsMenuActions(),
				params: {
					shouldResizeContent: true,
					showCancelButton: true,
					title,
				},
			};
		}

		getSettingsMenuActions()
		{
			return [
				this.getProviderAction(),
				this.getFromAction(),
			];
		}

		getProviderAction()
		{
			let title = Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_PROVIDER');
			if (this.smsAndMailSenders)
			{
				title = Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_SENDER_SERVICE');
			}

			return {
				id: 'crmmobile-senders-selector-provider',
				title,
				subtitle: this.name,
				onClickCallback: () => {
					this.showProviderSelector();

					return Promise.resolve({ closeMenu: false });
				},
			};
		}

		getFromAction()
		{
			const from = this.getCurrentFrom();

			let title = Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_PHONE');
			if (this.currentSender?.typeId === SENDER_TYPE_EMAIL)
			{
				title = Loc.getMessage('M_CRM_TIMELINE_SENDERS_SELECTOR_EMAIL');
			}

			return {
				id: 'crmmobile-senders-selector-from',
				title,
				subtitle: get(from, 'name', ''),
				onClickCallback: () => {
					this.showFromSelector();

					return Promise.resolve({ closeMenu: false });
				},
			};
		}

		getCurrentFrom()
		{
			return this.currentSender.fromList.find((from) => from.id === this.currentFromId);
		}

		show(parentWidget = PageManager)
		{
			void this.settingsMenu.show(parentWidget);
		}

		close(callback = () => {})
		{
			if (this.providerSelector)
			{
				this.providerSelector.close(() => this.settingsMenu.close(callback));
			}
			else
			{
				this.settingsMenu.close(callback);
			}
		}

		get layout()
		{
			return this.settingsMenu.layoutWidget;
		}

		get actionsBySections()
		{
			return this.settingsMenu.actionsBySections;
		}

		update({ sender, fromId })
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
					this.fromSelector = null;
				}
			}

			if (Type.isStringFilled(fromId) || Type.isNumber(fromId))
			{
				needRerender = true;
				this.currentFromId = fromId;
			}

			if (needRerender)
			{
				this.settingsMenu.rerender(this.getMenuConfig());
			}
		}
	}

	module.exports = { SendersSelector };
});
