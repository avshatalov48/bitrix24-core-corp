import { Tag, Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { TagSelector } from 'ui.entity-selector';
import { Analytics } from '../analytics';
import DepartmentControl from 'intranet.department-control';
import { InputRowFactory } from '../input-row-factory';
import { Page } from './page';

export class RegisterPage extends Page
{
	#container: HTMLElement;
	#inputsFactory: InputRowFactory;
	#isCloud: boolean;
	#withoutConfirm: boolean;
	#tagSelectorGroup: TagSelector;
	#departmentControl: DepartmentControl;

	constructor(options)
	{
		super();
		this.#isCloud = options.isCloud === true;
		this.#withoutConfirm = options.withoutConfirm === true;
		this.#inputsFactory = options.inputsFactory instanceof InputRowFactory ? options.inputsFactory : null;
		this.#tagSelectorGroup = options.tagSelectorGroup instanceof TagSelector ? options.tagSelectorGroup : null;
		this.#departmentControl = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = Tag.render`
			<div class="invite-wrap js-intranet-invitation-block" data-role="add-block">
				<form method="POST" name="ADD_DIALOG_FORM">
					<div class="invite-title-container">
						<div class="invite-title-icon invite-title-icon-registration">
							<div class="ui-icon-set --person-plus"></div>
						</div>
						<div class="invite-title-text">${Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_TITLE')}</div>
					</div>
					<div class="invite-content-container">
						<div class="invite-form-container">
							<div data-role="rows-container">
								${this.#renderFields()}
							</div>
							<div class="invite-form-row">
								<div class="invite-form-col">
									<div class="invite-content__field-lable">${Loc.getMessage('INTRANET_INVITE_DIALOG_GROUP_INPUT')}</div>
									<div data-role="entity-selector-container"></div>
								</div>
							</div>
							<div class="invite-form-row" id="intranet-invitation__department-control-palce"></div>
							<div class="invite-form-row">
								<div class="invite-form-col">
									<div class="invite-dialog-inv-form-checkbox-wrap">
										<input
										type="checkbox"
										name="ADD_SEND_PASSWORD"
										id="ADD_SEND_PASSWORD"
										value="Y"
										class="invite-dialog-inv-form-checkbox"
										${this.#withoutConfirm ? 'checked' : ''}
										>
										<label class="invite-dialog-inv-form-checkbox-label" for="ADD_SEND_PASSWORD">
											${Loc.getMessage(this.#isCloud ? 'BX24_INVITE_DIALOG_ADD_WO_CONFIRMATION_TITLE' : 'BX24_INVITE_DIALOG_ADD_SEND_PASSWORD_TITLE')}
											${this.#isCloud ? '' : '<span id="ADD_SEND_PASSWORD_EMAIL"></span>'}
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		`;
		this.#tagSelectorGroup.renderTo(this.#container.querySelector('[data-role="entity-selector-container"]'));
		this.#departmentControl.renderTo(this.#container.querySelector('#intranet-invitation__department-control-palce'));

		return this.#container;
	}

	#renderFields(): HTMLElement
	{
		const element = Tag.render`
			<div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="invite-content__field-lable">${Loc.getMessage('BX24_INVITE_DIALOG_ADD_NAME_TITLE')}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_NAME" placeholder="${Loc.getMessage('INTRANET_INVITE_DIALOG_PLACEHOLDER_NAME')}" id="ADD_NAME" class="ui-ctl-element">
							<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>
					</div>
				</div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="invite-content__field-lable">${Loc.getMessage('BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE')}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_LAST_NAME" placeholder="${Loc.getMessage('INTRANET_INVITE_DIALOG_PLACEHOLDER_LAST_NAME')}" id="ADD_LAST_NAME" class="ui-ctl-element">
							<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>
					</div>
				</div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="invite-content__field-lable">${Loc.getMessage('BX24_INVITE_DIALOG_ADD_EMAIL_TITLE')}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_EMAIL" placeholder="${Loc.getMessage('INTRANET_INVITE_DIALOG_PLACEHOLDER_EMAIL')}" id="ADD_EMAIL" class="ui-ctl-element" maxlength="50">
							<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>
					</div>
				</div>
				<div class="invite-form-row">
					<div class="invite-form-col">
						<div class="invite-content__field-lable">${Loc.getMessage('BX24_INVITE_DIALOG_ADD_POSITION_TITLE')}</div>
						<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
							<input type="text" name="ADD_POSITION" placeholder="${Loc.getMessage('INTRANET_INVITE_DIALOG_PLACEHOLDER_POSITION')}" id="ADD_POSITION" class="ui-ctl-element">
							<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
						</div>			
					</div>
				</div>
			</div>
		`;

		this.#inputsFactory.bindCloseIcons(element);

		return element;
	}

	getAnalyticTab(): string
	{
		return Analytics.TAB_REGISTRATION;
	}

	onSubmit(event: BaseEvent)
	{
		const addForm = this.render().querySelector('form');

		const tagSelectorItems = this.#tagSelectorGroup.getDialog().getSelectedItems();
		const projectIds = [];
		tagSelectorItems.forEach((item) => {
			const id = parseInt(item.getId(), 10);
			projectIds.push(id);
		});

		const data = {
			ADD_EMAIL: addForm.ADD_EMAIL.value,
			ADD_NAME: addForm.ADD_NAME.value,
			ADD_LAST_NAME: addForm.ADD_LAST_NAME.value,
			ADD_POSITION: addForm.ADD_POSITION.value,
			ADD_SEND_PASSWORD: (
				addForm.ADD_SEND_PASSWORD
				&& Boolean(addForm.ADD_SEND_PASSWORD.checked)
					? addForm.ADD_SEND_PASSWORD.value
					: 'N'
			),
			departmentIds: this.#departmentControl.getValues(),
			SONET_GROUPS_CODE: projectIds,
		};

		const analyticsLabel = {
			INVITATION_TYPE: 'add',
		};

		const context = event.getData()?.context;
		EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
			action: 'add',
			data,
			analyticsLabel,
		});
	}

	getSubmitButtonText(): ?string
	{
		return Loc.getMessage('BX24_INVITE_DIALOG_ACTION_ADD');
	}

	onInviteSuccess(event: BaseEvent)
	{
		this.render().querySelector('form')?.reset();
		this.#departmentControl.reset();
		this.#tagSelectorGroup.removeTags();
	}
}