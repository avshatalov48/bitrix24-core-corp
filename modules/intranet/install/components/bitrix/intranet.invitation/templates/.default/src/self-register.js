import { Event, Type, Dom, Text, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Switcher, SwitcherSize } from 'ui.switcher';
import { Analytics } from './analytics';
import DepartmentControl from 'intranet.department-control';
import type { LinkPage } from './page/link-page';
import { SubmitButton } from './submit-button';
import type { Transport } from './transport';

export class SelfRegister
{
	#container: HTMLElement;
	#isEnabled: boolean;
	#analytics: Analytics;
	#transport: Transport;
	#departmentControl: DepartmentControl;
	#page: LinkPage;

	constructor(options)
	{
		this.#container = options.container;
		this.#isEnabled = options.isSelfRegisterEnabled;
		this.#analytics = options.analytics;
		this.#transport = options.transport;
		this.#departmentControl = options.departmentControl;
		this.#page = options.page;

		if (Type.isDomNode(this.#container))
		{
			this.bindActions();

			const switcherNode = this.#container.querySelector('.invite-dialog-fast-reg-control-switcher');
			this.switcher = new Switcher({
				inputName: 'allow_register',
				id: 'allow_register',
				checked: this.#isEnabled,
				node: switcherNode,
				size: SwitcherSize.small,
				handlers: {
					toggled: this.toggleSettings.bind(this),
				},
			});
		}

		const generateLinkBtnNode = this.#container.querySelector("[data-role='selfRegenerateSecretButton']");
		this.generateLinkHint = null;
		Event.bind(generateLinkBtnNode, 'mouseover', (event) => {
			this.generateLinkHint = this.showHintPopup(
				Loc.getMessage('INTRANET_INVITE_DIALOG_LINK_UPDATE_WARNING_MSGVER_1'),
				generateLinkBtnNode,
			);
		});

		Event.bind(generateLinkBtnNode, 'mouseout', () => {
			this.generateLinkHint?.destroy();
			this.generateLinkHint = null;
		});

		EventEmitter.subscribe('BX.Intranet.Invitation:confirmShutdown', (event: BaseEvent) => {
			const data = event.getData();
			this.showNotificationPopup(data);
		});
	}

	bindActions()
	{
		const regenerateButton = this.#container.querySelector("[data-role='selfRegenerateSecretButton']");
		if (Type.isDomNode(regenerateButton))
		{
			Event.bind(regenerateButton, 'click', () => {
				this.regenerateSecret();
			});
		}

		const copyRegisterUrlButton = this.#container.querySelector("[data-role='copyRegisterUrlButton']");
		if (Type.isDomNode(copyRegisterUrlButton))
		{
			Event.bind(copyRegisterUrlButton, 'click', () => {
				this.copyRegisterUrl();
			});
		}

		const allowRegisterConfirm = this.#container.querySelector("[data-role='allowRegisterConfirm']");
		if (Type.isDomNode(allowRegisterConfirm))
		{
			Event.bind(allowRegisterConfirm, 'change', () => {
				EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
				this.#page.setButtonState(SubmitButton.ENABLED_STATE);
				this.toggleWhiteList(allowRegisterConfirm);
			});
		}

		const selfWhiteList = this.#container.querySelector("[data-role='selfWhiteList']");
		if (Type.isDomNode(selfWhiteList))
		{
			Event.bind(selfWhiteList, 'input', () => {
				this.#page.setButtonState(SubmitButton.ENABLED_STATE);
				EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
			});
		}
	}

	regenerateSecret()
	{
		this.#transport.send({
			action: 'self',
			data: { allow_register_secret: Text.getRandom(8) },
		}).then((response) => {
			top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('INTRANET_INVITE_DIALOG_LINK_UPDATE_SUCCESS'),
				autoHideDelay: 2500,
			});
		}, (response) => {
			top.BX.UI.Notification.Center.notify({
				content: Loc.getMessage('INTRANET_INVITE_DIALOG_LINK_UPDATE_ERROR'),
				autoHideDelay: 2500,
			});
		});
	}

	copyRegisterUrl()
	{
		const copyBtnNode = this.#container.querySelector("[data-role='copyRegisterUrlButton']");
		if (Dom.hasClass(copyBtnNode, 'ui-btn-wait'))
		{
			return;
		}
		Dom.addClass(copyBtnNode, 'ui-btn-wait');
		this.#transport.send({
			action: 'getInviteLink',
			data: {
				departmentsId: this.#departmentControl.getValues(),
			},
		}).then((response) => {
			Dom.removeClass(copyBtnNode, 'ui-btn-wait');
			const invitationUrl = response.data?.invitationLink;
			if (Type.isStringFilled(invitationUrl))
			{
				this.copyToClipboard(invitationUrl)
					.then(r => {
						top.BX.UI.Notification.Center.notify({
							content: Loc.getMessage('BX24_INVITE_DIALOG_COPY_URL'),
							autoHideDelay: 2500,
						});
					})
					.catch((e) => {
						console.log(e);
					});

				this.#analytics.send();
			}
		}, (response) => {
			Dom.removeClass(copyBtnNode, 'ui-btn-wait');
		});
	}

	async copyToClipboard(textToCopy: string): Promise<void>
	{
		if (!Type.isString(textToCopy))
		{
			return Promise.reject();
		}

		// navigator.clipboard defined only if window.isSecureContext === true
		// so or https should be activated, or localhost address
		if (window.isSecureContext && navigator.clipboard)
		{
			// safari not allowed clipboard manipulation as result of ajax request
			// so timeout is hack for this, to prevent "not have permission"
			return new Promise((resolve, reject) => {
				setTimeout(() => (
					navigator.clipboard
						.writeText(textToCopy)
						.then(() => resolve())
						.catch((e) => reject(e))
				), 0);
			});
		}

		return BX.clipboard?.copy(textToCopy) ? Promise.resolve() : Promise.reject();
	}

	showHintPopup(message, bindNode)
	{
		if (!Type.isDomNode(bindNode) || !message)
		{
			return;
		}

		const popup = new BX.PopupWindow('inviteHint' + Text.getRandom(8), bindNode, {
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
				onAfterPopupShow() {
					setTimeout(() => {
						this.close();
					}, 4000);
				},
			},
		});

		popup.show();

		return popup;
	}

	showNotificationPopup(data: Object)
	{
		const popup = new BX.PopupWindow({
			className: 'confirm-self-register',
			titleBar: Loc.getMessage('INTRANET_INVITE_DIALOG_SELF_CONFIRM_POPUP_TITLE'),
			content: Loc.getMessage('INTRANET_INVITE_DIALOG_SELF_CONFIRM_POPUP_TEXT'),
			width: 364,
			height: 188,
			closeIcon: true,
			buttons: [
				new BX.UI.Button({
					text: Loc.getMessage('INTRANET_INVITE_DIALOG_SELF_CONFIRM_POPUP_OK'),
					color: BX.UI.Button.Color.PRIMARY,
					round: true,
					events: {
						click() {
							popup.close();
							EventEmitter.emit(data.page, 'BX.Intranet.Invitation:submit', {
								context: data.context,
								isConfirm: true,
							});
						},
					},
				}),
				new BX.UI.ApplyButton({
					text: Loc.getMessage('INTRANET_INVITE_DIALOG_SELF_CONFIRM_POPUP_CANCEL'),
					round: true,
					events: {
						click() {
							popup.close();
						},
					},
				}),
			],
			events: {
				onClose: () => {
					EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
				},
			},
		});

		popup.show();
	}

	toggleSettings()
	{
		EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
		this.#page.setButtonState(SubmitButton.ENABLED_STATE);
		const controlBlock = this.#container.querySelector(".js-invite-dialog-fast-reg-control-container");
		if (Type.isDomNode(controlBlock))
		{
			Dom.toggleClass(controlBlock, 'disallow-registration');
		}

		const settingsBlock = this.#container.querySelector("[data-role='selfSettingsBlock']");
		if (Type.isDomNode(settingsBlock))
		{
			Dom.style(settingsBlock, 'display', this.switcher.checked ? 'block' : 'none');
		}
	}

	toggleWhiteList(inputElement)
	{
		const selfWhiteList = this.#container.querySelector("[data-role='selfWhiteList']");
		if (Type.isDomNode(selfWhiteList))
		{
			Dom.style(selfWhiteList, 'display', inputElement.checked ? 'block' : 'none');
		}
	}
}
