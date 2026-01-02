import { Loc, Type, Event } from 'main.core';
import { InvitationInput, InvitationInputType } from 'intranet.invitation-input';

export type MassInvitationFieldType = {
	placeholder: string,
	useOnlyPhone: boolean,
	smsAvailable: boolean,
}

export default class MassInvitationField
{
	#input: InvitationInput;
	#invitationType: InvitationInputType;
	#isPhoneEnabled: boolean;
	#isEmailEnabled: boolean;

	constructor(options: MassInvitationFieldType)
	{
		this.#invitationType = options.useOnlyPhone
			? InvitationInputType.PHONE
			: (options.smsAvailable ? InvitationInputType.ALL : InvitationInputType.EMAIL);
		this.#isPhoneEnabled = [InvitationInputType.ALL, InvitationInputType.PHONE].includes(this.#invitationType);
		this.#isEmailEnabled = [InvitationInputType.ALL, InvitationInputType.EMAIL].includes(this.#invitationType);

		this.#input = new InvitationInput({ inputType: this.#invitationType });
		this.#input.getTagSelector().setPlaceholder(
			Type.isStringFilled(options.placeholder)
				? options.placeholder
				: this.#getDialogInputMessage(),
		);
		BX.Dom.style(this.#input.getTagSelector().getContainer(), 'height', '103px');
		BX.Dom.style(this.#input.getTagSelector().getContainer(), 'cursor', 'text');
		Event.bind(this.#input.getTagSelector().getContainer(), 'click', () => {
			this.#input.getTagSelector().focusTextBox();
		});
	}

	#getDialogInputMessage(): string
	{
		if (this.#isPhoneEnabled && this.#isEmailEnabled)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT');
		}
		else if (this.#isPhoneEnabled)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_PHONE_INPUT');
		}
		else
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_INPUT');
		}
	}

	reset(): void
	{
		this.#input.getTagSelector().removeTags();
	}

	renderTo(node: HTMLElement)
	{
		this.#input.renderTo(node);
	}

	getValue(): Object
	{
		const selector = this.#input.getTagSelector();
		const tags = selector.getTags();
		const values = [];
		const errorElements = [];

		tags.forEach((tag) => {
			if (this.#isPhoneEnabled && tag.getEntityType() === 'phone')
			{
				values.push(tag.getTitle());
			}
			else if (this.#isEmailEnabled && tag.getEntityType() === 'email')
			{
				values.push(tag.getTitle());
			}
			else
			{
				errorElements.push(tag.getTitle());
			}
		});

		return { values, errorElements };
	}
}
