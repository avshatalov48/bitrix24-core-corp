import {Event, Type, Dom, Text, Loc} from "main.core";

export class SelfRegister
{
	constructor(parent)
	{
		this.parent = parent;

		if (Type.isDomNode(this.parent.contentBlocks["self"]))
		{
			this.selfBlock = this.parent.contentBlocks["self"];
			this.bindActions();
		}
	}

	bindActions()
	{
		const regenerateButton = this.selfBlock.querySelector("[data-role='selfRegenerateSecretButton']");
		if (Type.isDomNode(regenerateButton))
		{
			Event.bind(regenerateButton, 'click',() => {
				this.parent.activateButton();
				this.regenerateSecret(this.parent.regenerateUrlBase);
			});
		}

		const copyRegisterUrlButton = this.selfBlock.querySelector("[data-role='copyRegisterUrlButton']");
		if (Type.isDomNode(copyRegisterUrlButton))
		{
			Event.bind(copyRegisterUrlButton, 'click', () => {
				this.copyRegisterUrl();
			});
		}

		const selfToggleSettingsButton = this.selfBlock.querySelector("[data-role='selfToggleSettingsButton']");
		if (Type.isDomNode(selfToggleSettingsButton))
		{
			Event.bind(selfToggleSettingsButton, 'change', () => {
				this.parent.activateButton();
				this.toggleSettings(selfToggleSettingsButton);
			});
		}

		const allowRegisterConfirm = this.selfBlock.querySelector("[data-role='allowRegisterConfirm']");
		if (Type.isDomNode(allowRegisterConfirm))
		{
			Event.bind(allowRegisterConfirm, 'change', () => {
				this.parent.activateButton();
				this.toggleWhiteList(allowRegisterConfirm);
			});
		}

		const selfWhiteList = this.selfBlock.querySelector("[data-role='selfWhiteList']");
		if (Type.isDomNode(selfWhiteList))
		{
			Event.bind(selfWhiteList, 'input', () => {
				this.parent.activateButton();
			});
		}
	}

	regenerateSecret(registerUrl)
	{
		const value = Text.getRandom(8);

		const allowRegisterSecretNode = this.selfBlock.querySelector("[data-role='allowRegisterSecret']");
		if (Type.isDomNode(allowRegisterSecretNode))
		{
			allowRegisterSecretNode.value = value || '';
		}

		const allowRegisterUrlNode = this.selfBlock.querySelector("[data-role='allowRegisterUrl']");
		if (Type.isDomNode(allowRegisterUrlNode) && registerUrl)
		{
			allowRegisterUrlNode.value = registerUrl + (value || 'yes');
		}
	}

	copyRegisterUrl()
	{
		const allowRegisterUrlNode = this.selfBlock.querySelector("[data-role='allowRegisterUrl']");
		if (Type.isDomNode(allowRegisterUrlNode))
		{
			BX.clipboard.copy(allowRegisterUrlNode.value);
			this.showHintPopup(Loc.getMessage("BX24_INVITE_DIALOG_COPY_URL"), allowRegisterUrlNode);

			BX.ajax.runAction('intranet.controller.invite.copyregisterurl', {
				data: {}
			}).then(function (response) {}, function (response) {});
		}
	}

	showHintPopup(message, bindNode)
	{
		if (!Type.isDomNode(bindNode) || !message)
		{
			return;
		}

		new BX.PopupWindow('inviteHint' + Text.getRandom(8), bindNode, {
			content: message,
			zIndex: 15000,
			angle: true,
			offsetTop: 0,
			offsetLeft: 50,
			closeIcon: false,
			autoHide: true,
			darkMode: true,
			overlay: false,
			maxWidth: 400,
			events: {
				onAfterPopupShow: function () {
					setTimeout(function () {
						this.close();
					}.bind(this), 4000);
				}
			}
		}).show();
	}

	toggleSettings(inputElement)
	{
		const controlBlock = this.selfBlock.querySelector(".js-invite-dialog-fast-reg-control-container");
		if (Type.isDomNode(controlBlock))
		{
			if (!Dom.hasClass(controlBlock, 'disallow-registration'))
			{
				const switcher = controlBlock.querySelector("[data-role='self-switcher']");
				this.showHintPopup(Loc.getMessage("INTRANET_INVITE_DIALOG_SELF_OFF_HINT"), switcher);
			}
			Dom.toggleClass(controlBlock, 'disallow-registration');
		}

		const settingsBlock = this.selfBlock.querySelector("[data-role='selfSettingsBlock']");
		if (Type.isDomNode(settingsBlock))
		{
			Dom.style(settingsBlock, 'display', inputElement.checked ? 'block' : 'none');
		}
	}

	toggleWhiteList(inputElement)
	{
		const selfWhiteList = this.selfBlock.querySelector("[data-role='selfWhiteList']");
		if (Type.isDomNode(selfWhiteList))
		{
			Dom.style(selfWhiteList, 'display', inputElement.checked ? 'block' : 'none');
		}
	}
}