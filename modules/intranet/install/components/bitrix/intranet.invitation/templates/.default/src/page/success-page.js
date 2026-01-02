import { Tag, Loc } from 'main.core';
import { Page } from './page';

export class SuccessPage extends Page
{
	#container: HTMLElement;

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = Tag.render`
			<div class="invite-wrap js-intranet-invitation-block" data-role="success-block" style="position: fixed; left: 0; right: 0; top: 0; bottom: 0; background: #fff; z-index: 90;">
				<div style="height: 78vh;" class="invite-send-success-wrap">
					<div class="invite-send-success-text">${Loc.getMessage('INTRANET_INVITE_DIALOG_SUCCESS_SEND')}</div>
					<div class="invite-send-success-decal-1"></div>
					<div class="invite-send-success-decal-2"></div>
					<div class="invite-send-success-decal-3"></div>
					<div class="invite-send-success-decal-4"></div>
					<div class="invite-send-success-decal-5"></div>
				</div>
			</div>
		`;

		return this.#container;
	}

	getAnalyticTab(): ?string
	{
		return null;
	}

	onSubmit(event: BaseEvent)
	{
		event.getData()?.context?.submitButton.enable();
		event.getData()?.context?.submitButton.ready();
		event.getData()?.context?.changeContent(event.getData()?.context?.firstInvitationBlock);
		event.getData()?.context?.activeMenuItem(event.getData()?.context?.firstInvitationBlock);
	}

	getSubmitButtonText(): ?string
	{
		return Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE_MORE');
	}
}