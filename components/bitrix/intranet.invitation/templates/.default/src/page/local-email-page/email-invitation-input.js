import { Loc, Tag } from 'main.core';
import { InvitationInput, InvitationInputType } from 'intranet.invitation-input';
import { EventEmitter } from 'main.core.events';

export class EmailInvitationInput
{
	#input: InvitationInput = null;

	constructor()
	{
		this.#input = new InvitationInput({ inputType: InvitationInputType.EMAIL });
		this.#input.getTagSelector().setPlaceholder(Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_PLACEHOLDER'));

		Event.bind(this.#input.getTagSelector().getContainer(), 'click', () => {
			this.#input.getTagSelector().focusTextBox();
		});

		EventEmitter.subscribe('BX.Intranet.Invitation:InviteSuccess', () => {
			this.clearTags();
		});
	}

	onReadySaveInputHandler(callback: function): void
	{
		this.#input.unsubscribe('onReadySave', callback);
		this.#input.subscribe('onReadySave', callback);
	}

	onUnreadySaveInputHandler(callback: function): void
	{
		this.#input.unsubscribe('onUnreadySave', callback);
		this.#input.subscribe('onUnreadySave', callback);
	}

	getContent(actionBlock: HTMLElement = null): HTMLElement
	{
		this.clearTags();

		return Tag.render`
			<div class="email-popup-container">
				<div class="email-popup-container__title">
					${Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_TITLE')}
				</div>
				<div class="email-popup-container__description">
					${Loc.getMessage('INTRANET_INVITE_DIALOG_LOCAL_POPUP_EMAIL_DESCRIPTION')}
				</div>
				<div class="email-popup-container__input">
					${this.#input.render()}
				</div>
				${actionBlock ?? ''}
			</div>
		`;
	}

	clearTags(): void
	{
		this.#input.getTagSelector().removeTags();
	}

	getValues(): Object
	{
		const selector = this.#input.getTagSelector();
		const tags = selector.getTags();
		const emails = [];
		const errorElements = [];

		tags.forEach((tag) => {
			if (tag.getEntityType() === 'email')
			{
				emails.push({
					EMAIL: tag.getTitle(),
				});
			}
			else
			{
				errorElements.push(tag.getTitle());
			}
		});

		return { emails, errorElements };
	}
}
