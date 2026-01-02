import { Dom, Loc, Tag } from 'main.core';
import { Popup, PopupManager } from 'main.popup';
import { Button, CancelButton, ButtonState } from 'ui.buttons';
import { EventEmitter } from 'main.core.events';
import { EmailInvitationInput } from 'email-invitation-input';

export class InviteEmailPopup
{
	#sendButton: Button;
	#popup: Popup = null;
	#input: EmailInvitationInput = null;
	#actionContent: HTMLElement = null;

	constructor(input: EmailInvitationInput)
	{
		this.#input = input;

		this.#input.onReadySaveInputHandler(this.onReadySaveInputHandler.bind(this));
		this.#input.onUnreadySaveInputHandler(this.onUnreadySaveInputHandler.bind(this));

		EventEmitter.subscribe('BX.Intranet.Invitation:InviteSuccess', () => {
			this.#popup?.close();
			this.onReadySaveInputHandler();
			EventEmitter.emit('BX.Intranet.Invitation:onSubmitReady');
			EventEmitter.emit('BX.Intranet.Invitation:onChangeForm');
		});

		EventEmitter.subscribe('BX.Intranet.Invitation:InviteFailed', () => {
			this.onReadySaveInputHandler();
		});
	}

	show(): void
	{
		if (!this.#popup)
		{
			this.#popup = PopupManager.create({
				id: 'email-invitation-email',
				className: 'email-invitation-container',
				closeIcon: true,
				autoHide: false,
				closeByEsc: true,
				width: 515,
				height: 310,
			});
		}

		this.#popup.setContent(this.#input.getContent(this.#getActionContent()));
		this.#input.clearTags();
		this.#popup.show();
	}

	#getActionContent(): HTMLElement
	{
		if (!this.#actionContent)
		{
			this.#actionContent = Tag.render`
				<div class="email-popup-container__action">
					${this.#getSendButton()}
					${this.#getCancelButton()}
				</div>
			`;
		}

		return this.#actionContent;
	}

	#getSendButton(): HTMLElement
	{
		this.#sendButton = new Button({
			className: 'email-popup-container__action-send',
			text: Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_ACTION_SEND'),
			state: ButtonState.DISABLED,
			size: Button.Size.SMALL,
			color: Button.Color.PRIMARY,
			noCaps: true,
			onclick: () => {
				if (this.#sendButton.isDisabled())
				{
					return;
				}

				this.submitData();
			},
		});

		return this.#sendButton.render();
	}

	#getCancelButton(): HTMLElement
	{
		return new CancelButton({
			text: Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_ACTION_CANCEL'),
			size: Button.Size.SMALL,
			onclick: () => this.#popup.close(),
			noCaps: true,
		}).render();
	}

	onReadySaveInputHandler(): void
	{
		this.#sendButton?.setState(null);
	}

	onUnreadySaveInputHandler(): void
	{
		this.#sendButton?.setState(ButtonState.DISABLED);
	}

	submitData(): void
	{
		this.onUnreadySaveInputHandler();
		EventEmitter.emit('BX.Intranet.Invitation.EmailPopup:onSubmit');
	}
}
