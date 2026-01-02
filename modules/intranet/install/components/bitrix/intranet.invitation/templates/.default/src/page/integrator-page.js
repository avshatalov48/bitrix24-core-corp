import { Tag, Loc, Event, Dom } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';
import { Page } from './page';
import { CreateButton, CancelButton } from 'ui.buttons';
import { Popup } from 'main.popup';

export class IntegratorPage extends Page
{
	#container: HTMLElement;

	constructor(options)
	{
		super();
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = Tag.render`
			<div class="invite-wrap js-intranet-invitation-block" data-role="integrator-block">
				<form method="POST" name="INTEGRATOR_DIALOG_FORM">
					<div class="invite-title-container">
						<div class="invite-title-icon invite-title-icon-mass">
							<div class="ui-icon-set --persons-3"></div>
						</div>
					<div class="invite-title-text">${Loc.getMessage('BX24_INVITE_DIALOG_TAB_INTEGRATOR_TITLE')}</div>
					<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=7725333');"></div>
					</div>
					<div class="invite-content-container">
						<div class="invite-form-container">
							<div data-role="rows-container">
								<div class="invite-form-row">
									<div class="invite-form-col">
										<div class="invite-content__field-lable">${Loc.getMessage('INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL')}</div>
										<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
											<input 
												type="text" 
												class="ui-ctl-element" 
												value="" 
												maxlength="50"
												name="integrator_email" 
												id="integrator_email" 
												placeholder="${Loc.getMessage('INTRANET_INVITE_DIALOG_INTEGRATOR_EMAIL')}"
											/>
											<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		`;

		const input = this.#container.querySelector('input');

		const closeIcon = input.nextElementSibling;
		Event.bind(input, 'input', () => {
			Dom.style(closeIcon, 'display', input.value === '' ? 'none' : 'block');
		});
		Event.bind(closeIcon, 'click', (event) => {
			event.preventDefault();
			Dom.style(closeIcon, 'display', 'none');
		});

		return this.#container;
	}

	getAnalyticTab(): string
	{
		return Analytics.TAB_INTEGRATOR;
	}

	onSubmit(event: BaseEvent)
	{
		const formNode = this.render().querySelector('form');

		const data = {
			integrator_email: formNode.integrator_email.value,
		};
		const analyticsLabel = {
			INVITATION_TYPE: 'integrator',
		};

		const message = Tag.render`
			<div class="invite-integrator-confirm-message">
				${Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_DESCRIPTION')}
			</div>
		`;
		const moreLink = message.querySelector('span');
		Event.bind(moreLink, 'click', () => {
			top.BX.Helper.show('redirect=detail&code=20682986');
		});

		const request = {
			action: 'inviteIntegrator',
			data,
			analyticsLabel,
		};

		const popup = new Popup({
			id: 'integrator-confirm-invitation-popup',
			maxWidth: 500,
			closeIcon: false,
			overlay: true,
			contentPadding: 10,
			titleBar: Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_TITLE'),
			content: message,
			offsetLeft: 100,
			buttons: [
				new CreateButton({
					text: Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_BUTTON_YES'),
					onclick: () => {
						const context = event.getData()?.context;
						EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', request);

						popup.close();
					},
				}),
				new CancelButton({
					text: Loc.getMessage('INTRANET_INVITE_DIALOG_CONFIRM_INTEGRATOR_BUTTON_NO'),
					onclick: () => {
						popup.close();
					},
				}),
			],
			events: {
				onClose: () => {
					popup.destroy();
				},
			},
		});

		popup.show();
	}

	getSubmitButtonText(): ?string
	{
		return Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	}

	onInviteSuccess(event: BaseEvent)
	{
		this.render().querySelector('form')?.reset();
	}
}
