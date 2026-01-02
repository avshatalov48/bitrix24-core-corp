import { EventEmitter, BaseEvent } from 'main.core.events';
import { SubmitButton } from '../submit-button';

export class Page
{
	constructor()
	{
		EventEmitter.subscribe(this, 'BX.Intranet.Invitation:submit', this.onSubmit.bind(this));
		EventEmitter.subscribe('BX.Intranet.Invitation:onInviteRequestSuccess', this.onInviteSuccess.bind(this));
	}

	render(): HTMLElement
	{
		return new HTMLElement();
	}

	onSubmit(event: BaseEvent)
	{}

	onInviteSuccess(event: BaseEvent)
	{}

	getSubmitButtonText(): ?string
	{
		return null;
	}

	getButtonState(): ?string
	{
		return SubmitButton.ENABLED_STATE;
	}
}
