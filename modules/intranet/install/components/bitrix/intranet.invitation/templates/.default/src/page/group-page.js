import { Tag, Loc, Type, Event } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';
import DepartmentControl from 'intranet.department-control';
import { InputRowFactory } from '../input-row-factory';
import { Page } from './page';
import { TagSelector } from 'ui.entity-selector';

export class GroupPage extends Page
{
	#container: HTMLElement;
	#inputsFactory: InputRowFactory;
	#tagSelectorGroup: TagSelector;
	#onClickAddInputRow: function;
	#departmentControl: DepartmentControl;

	constructor(options)
	{
		super();
		this.#inputsFactory = options.inputsFactory instanceof InputRowFactory ? options.inputsFactory : null;
		this.#tagSelectorGroup = options.tagSelectorGroup instanceof TagSelector ? options.tagSelectorGroup : null;
		this.#onClickAddInputRow = Type.isFunction(options.onClickAddInputRow) ? options.onClickAddInputRow : null;
		this.#departmentControl = options.departmentControl instanceof DepartmentControl ? options.departmentControl : null;
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = Tag.render`
			<div class="invite-wrap js-intranet-invitation-block" data-role="invite-with-group-dp-block">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-message">
						<div class="ui-icon-set --group"></div>
					</div>
					<div class="invite-title-text">
						${Loc.getMessage('INTRANET_INVITE_DIALOG_GROUP_TITLE')}
					</div>
				</div>
				<div class="invite-content-container">
					<form method="POST" name="INVITE_WITH_GROUP_DP_DIALOG_FORM" class="invite-form-container">
						<div data-role="rows-container"></div>
						<div class="invite-form-buttons --border-bottom --pt-3 --mb-17">
							<span class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round" data-role="invite-more">
								${Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_MORE')}
							</span>
						</div>
						<div class="invite-form-row">
							<div class="invite-form-col">
								<div class="invite-content__field-lable">${Loc.getMessage('INTRANET_INVITE_DIALOG_GROUP_INPUT')}</div>
								<div data-role="entity-selector-container"></div>
							</div>
						</div>
						<div class="invite-form-row" id="intranet-invitation__department-control-palce">
						</div>
					</form>
				</div>
			</div>
		`;
		this.#inputsFactory.renderInputRowsTo(
			this.#container.querySelector('div[data-role="rows-container"]'),
			3,
		);

		this.#tagSelectorGroup.renderTo(this.#container.querySelector('[data-role="entity-selector-container"]'));
		this.#departmentControl.renderTo(this.#container.querySelector('#intranet-invitation__department-control-palce'));

		const moreButton = this.#container.querySelector("[data-role='invite-more']");
		if (Type.isDomNode(moreButton))
		{
			Event.unbindAll(moreButton);
			Event.bind(moreButton, 'click', () => {
				this.#inputsFactory.renderInputRowTo(
					this.#container.querySelector('div[data-role="rows-container"]'),
				);
				if (this.#onClickAddInputRow)
				{
					this.#onClickAddInputRow();
				}
			});
		}

		return this.#container;
	}

	getAnalyticTab(): string
	{
		return Analytics.TAB_DEPARTMENT;
	}

	onSubmit(event: BaseEvent)
	{
		const formNode = this.render().querySelector('form');
		const [items, errorInputData] = this.#inputsFactory.parseEmailAndPhone(formNode);

		const errors = [];
		if (errorInputData.length > 0)
		{
			errors.push(`${Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_VALIDATE_ERROR')}: ${errorInputData.join(', ')}`);
		}

		if (items.length <= 0)
		{
			errors.push(Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_EMPTY_ERROR'));
		}
		const context = event.getData()?.context;
		if (errors.length > 0)
		{
			EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
				errors,
			});

			return;
		}

		const tagSelectorItems = this.#tagSelectorGroup.getDialog().getSelectedItems();
		const projectIds = [];
		tagSelectorItems.forEach((item) => {
			const id = parseInt(item.getId(), 10);
			projectIds.push(id);
		});

		const data = {
			invitations: items,
			departmentIds: this.#departmentControl.getValues(),
			workgroupIds: projectIds,
			tab: 'group',
		};

		const analyticsLabel = {
			INVITATION_TYPE: 'withGroupOrDepartment',
			INVITATION_COUNT: items.length,
		};

		EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
			action: 'inviteWithGroupDp',
			data,
			analyticsLabel,
		});
	}

	onInviteSuccess(event: BaseEvent)
	{
		this.render().querySelector('form')?.reset();
		this.#departmentControl.reset();
		this.#tagSelectorGroup.removeTags();
	}

	getSubmitButtonText(): ?string
	{
		return Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	}
}