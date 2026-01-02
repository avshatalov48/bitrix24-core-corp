import { Tag, Loc, Event, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';
import DepartmentControl from 'intranet.department-control';
import MassInvitationField from '../mass-invitation-field';
import { Page } from './page';

export class MassPage extends Page
{
	#container: HTMLElement;
	#smsAvailable: boolean;
	#useOnlyPhone: boolean;
	#onClickSwitchMode: function;
	#massInvitationField: MassInvitationField;
	#departmentControl: DepartmentControl;

	constructor(options)
	{
		super();
		this.#smsAvailable = options.smsAvailable === true;
		this.#useOnlyPhone = options.useOnlyPhone === true;
		this.#onClickSwitchMode = Type.isFunction(options.onClickSwitchMode) ? options.onClickSwitchMode : null;
		this.#massInvitationField = new MassInvitationField({
			placeholder: '',
			useOnlyPhone: this.#useOnlyPhone,
			smsAvailable: this.#smsAvailable,
		});
		this.#departmentControl = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		const helper = Tag.render`
			<div class="invite-title-helper"
				 data-hint="${Loc.getMessage('INTRANET_INVITE_DIALOG_MASS_INVITE_HINT')}"
				 data-hint-no-icon
			>
			</div>
		`;

		const sufix = this.#useOnlyPhone ? 'PHONE' : (this.#smsAvailable ? 'EMAIL_AND_PHONE' : 'EMAIL');
		this.#container = Tag.render`
			<div class="invite-wrap js-intranet-invitation-block" data-role="mass-invite-block">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-mass">
						<div class="ui-icon-set --person-letter"></div>
					</div>
					<div class="invite-title-text">${Loc.getMessage('INTRANET_INVITE_DIALOG_MASS_TITLE')}</div>
					${this.#smsAvailable ? helper : ''}
				</div>
				<div class="invite-content-container">
					<div class="invite-form-container">
						<form method="POST" name="MASS_INVITE_DIALOG_FORM" class="invite-form-container">
							<div class="invite-content__field-lable">
								${Loc.getMessage(`INTRANET_INVITE_DIALOG_MASS_TITLE_${sufix}`)}
							</div>
							<div id="invite-content__mass-field">
							</div>
							<div class="invite-form-ctl-description --border-bottom">
								<span href="javascript:void(0)"
								   class="invite-content__switch_button swith-email-invitation-mass-mode"
								>
									${Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_PERSONAL')}
								</span>
							</div>
							<div class="invite-form-row" id="intranet-invitation__department-control-palce"></div>
						</form>
					</div>
				</div>
			</div>
		`;

		this.#massInvitationField?.renderTo(this.#container.querySelector('#invite-content__mass-field'));
		this.#departmentControl.renderTo(this.#container.querySelector('#intranet-invitation__department-control-palce'));

		Event.bind(
			this.#container.querySelector('.swith-email-invitation-mass-mode'),
			'click',
			this.#onClickSwitchMode,
		);

		return this.#container;
	}

	getAnalyticTab(): string
	{
		return Analytics.TAB_MASS;
	}

	onSubmit(event: BaseEvent): void
	{
		const { values, errorElements } = this.#massInvitationField.getValue();
		const errors = [];

		if (values.length <= 0)
		{
			errors.push(this.#getEmptyError());
		}

		if (errorElements.length > 0)
		{
			errors.push(this.#getValidateError());
		}

		if (errors.length > 0)
		{
			EventEmitter.emit(window.invitationForm, 'BX.Intranet.Invitation:onSendData', {
				errors,
			});

			return;
		}

		const data = {
			invitationText: values.join(' '),
			departmentIds: this.#departmentControl.getValues(),
			tab: 'mass',
		};

		const analyticsLabel = {
			INVITATION_TYPE: 'mass',
		};

		const context = event.getData()?.context;
		EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
			action: 'massInvite',
			data,
			analyticsLabel,
		});
	}

	onInviteSuccess(event: BaseEvent)
	{
		this.#massInvitationField.reset();
		this.#departmentControl.reset();
	}

	#getEmptyError(): string
	{
		if (this.#useOnlyPhone)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_EMPTY_ERROR_PHONE');
		}
		else if (this.#smsAvailable)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_EMPTY_ERROR_EMAIL_AND_PHONE');
		}

		return Loc.getMessage('INTRANET_INVITE_DIALOG_EMPTY_ERROR_EMAIL');
	}

	#getValidateError(): string
	{
		if (this.#useOnlyPhone)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_PHONE');
		}
		else if (this.#smsAvailable)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_EMAIL_AND_PHONE');
		}

		return Loc.getMessage('INTRANET_INVITE_DIALOG_VALIDATE_ERROR_EMAIL');
	}

	getSubmitButtonText(): ?string
	{
		return Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	}
}