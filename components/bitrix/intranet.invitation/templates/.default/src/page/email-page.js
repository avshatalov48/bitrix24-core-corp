import { Loc, Tag, Type, Event } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';
import DepartmentControl from 'intranet.department-control';
import { InputRowFactory } from '../input-row-factory';
import { Page } from './page';

export class EmailPage extends Page
{
	#container: HTMLElement;
	#inputsFactory: InputRowFactory;
	#smsAvailable: boolean;
	#useOnlyPhone: boolean;
	#onClickSwitchMode: function;
	#onClickAddInputRow: function;
	#departmentControl: DepartmentControl;

	constructor(options)
	{
		super();
		this.#inputsFactory = options.inputsFactory instanceof InputRowFactory ? options.inputsFactory : null;
		this.#smsAvailable = options.smsAvailable === true;
		this.#useOnlyPhone = options.useLocalEmailProgram === true;
		this.#onClickSwitchMode = Type.isFunction(options.onClickSwitchMode) ? options.onClickSwitchMode : null;
		this.#onClickAddInputRow = Type.isFunction(options.onClickAddInputRow) ? options.onClickAddInputRow : null;
		this.#departmentControl = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		const title = this.#titleRender();
		const form = this.#renderForm();
		this.#inputsFactory.renderInputRowsTo(form.querySelector('div[data-role="rows-container"]'), 5);
		this.#departmentControl.renderTo(form.querySelector('form'));

		this.#container = Tag.render`
			<div class="invite-wrap js-intranet-invitation-block" data-role="invite-block">
				${title}
				${form}
			</div>
		`;

		return this.#container;
	}

	#titleRender(): HTMLElement
	{
		const suffix = this.#useOnlyPhone ? 'PHONE_MSGVER_1' : (this.#smsAvailable ? 'EMAIL_AND_PHONE' : 'EMAIL');
		const code = this.#useOnlyPhone || this.#smsAvailable ? '21141922' : '25375678';

		return Tag.render`
			<div class="invite-title-container">
				<div class="invite-title-icon invite-title-icon-message">
					<div class="ui-icon-set --person-letter"></div>
				</div>
				<div class="invite-title-text">
					${Loc.getMessage(`INTRANET_INVITE_DIALOG_TITLE_${suffix}`)}
				</div>
				<div class="invite-title-helper" onclick="top.BX.Helper.show('redirect=detail&code=${code}');"></div>
			</div>
		`;
	}

	#renderForm(): HTMLElement
	{
		const form = Tag.render`
			<div class="invite-content-container">
				<form method="POST" name="INVITE_DIALOG_FORM" class="invite-form-container">
					<div data-role="rows-container"></div>
					<div class="invite-form-buttons --border-bottom --pt-3">
							<span class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round"
								  data-role="invite-more"
							>
								${Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_MORE')}
							</span>
						<span style="padding: 0 10px;">${Loc.getMessage('INTRANET_INVITE_DIALOG_OR')}</span>
						<span
							class="invite-content__switch_button swith-email-invitation-persons-mode"
						>
								${Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_MASSIVE')}
							</span>
					</div>
				</form>
			</div>
		`;

		const moreButton = form.querySelector("[data-role='invite-more']");
		if (Type.isDomNode(moreButton))
		{
			Event.unbindAll(moreButton);
			Event.bind(moreButton, 'click', () => {
				this.#inputsFactory.renderInputRowTo(form.querySelector('div[data-role="rows-container"]'));
				if (this.#onClickAddInputRow)
				{
					this.#onClickAddInputRow();
				}
			});
		}

		const switchBtn = form.querySelector('.swith-email-invitation-persons-mode');
		if (Type.isDomNode(switchBtn))
		{
			Event.unbindAll(switchBtn);
			Event.bind(switchBtn, 'click', this.#onClickSwitchMode);
		}

		return form;
	}

	getAnalyticTab(): string
	{
		return this.#useOnlyPhone ? Analytics.TAB_PHONE : Analytics.TAB_EMAIL;
	}

	onSubmit(event: BaseEvent)
	{
		const formNode = this.render().querySelector('form');
		const [items, errorInputData] = this.#inputsFactory.parseEmailAndPhone(formNode);

		const errors = [];
		if (errorInputData.length > 0)
		{
			errors.push(`${this.#getValidateError()}: ${errorInputData.join(', ')}`);
		}

		if (items.length <= 0)
		{
			errors.push(this.#getEmptyError());
		}
		const context = event.getData()?.context;
		if (errors.length > 0)
		{
			EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
				errors,
			});

			return;
		}

		const data = {
			invitations: items,
			departmentIds: this.#departmentControl.getValues(),
			tab: 'email',
		};

		const analyticsLabel = {
			INVITATION_TYPE: 'invite',
			INVITATION_COUNT: items.length,
		};

		EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
			action: 'invite',
			data,
			analyticsLabel,
		});
	}

	onInviteSuccess(event: BaseEvent)
	{
		this.render().querySelector('form')?.reset();
		this.#departmentControl.reset();
	}

	#getValidateError()
	{
		if (this.#useOnlyPhone)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_PHONE_VALIDATE_ERROR');
		}
		else if (this.#smsAvailable)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR');
		}

		return Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_VALIDATE_ERROR');
	}

	#getEmptyError()
	{
		if (this.#useOnlyPhone)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_PHONE_EMPTY_ERROR');
		}
		else if (this.#smsAvailable)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR');
		}

		return Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_EMPTY_ERROR');
	}

	getSubmitButtonText(): ?string
	{
		return Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	}
}