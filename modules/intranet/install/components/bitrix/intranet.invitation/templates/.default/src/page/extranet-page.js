import { Tag, Loc, Event, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Analytics } from '../analytics';
import { InputRowFactory } from '../input-row-factory';
import { Page } from './page';
import { TagSelector } from 'ui.entity-selector';

export class ExtranetPage extends Page
{
	#container: HTMLElement;
	#inputsFactory: InputRowFactory;
	#tagSelectorGroup: TagSelector;
	#onClickAddInputRow: function;

	constructor(options)
	{
		super();
		this.#inputsFactory = options.inputsFactory instanceof InputRowFactory ? options.inputsFactory : null;
		this.#tagSelectorGroup = options.tagSelectorGroup instanceof TagSelector ? options.tagSelectorGroup : null;
		this.#onClickAddInputRow = Type.isFunction(options.onClickAddInputRow) ? options.onClickAddInputRow : null;
	}

	render(): HTMLElement
	{
		if (this.#container)
		{
			return this.#container;
		}

		this.#container = Tag.render`
			<div class="invite-wrap js-intranet-invitation-block" data-role="extranet-block">
				<div class="invite-title-container">
					<div class="invite-title-icon invite-title-icon-message">
						<div class="ui-icon-set --earth"></div>
					</div>
					<div class="invite-title-text">${Loc.getMessage('INTRANET_INVITE_DIALOG_EXTRANET_TITLE')}</div>
				</div>
				<div class="invite-content-container">
					<form method="POST" name="EXTRANET_DIALOG_FORM" class="invite-form-container">
						<div class="invite-form-row" style="margin-bottom: 15px;">
							<div class="invite-form-col">
								<div class="ui-ctl-label-text">${Loc.getMessage('INTRANET_INVITE_DIALOG_EXTRANET_GROUP')}</div>
								<div data-role="entity-selector-container"></div>
							</div>
						</div>
						<div data-role="rows-container"></div>
						<div class="invite-form-buttons">
							<span class="ui-btn ui-btn-sm ui-btn-light-border ui-btn-icon-add ui-btn-round" data-role="invite-more">
								${Loc.getMessage('INTRANET_INVITE_DIALOG_ADD_MORE')}
							</span>
						</div>
					</form>
				</div>
			</div>
		`;

		this.#tagSelectorGroup?.renderTo(this.#container.querySelector('[data-role="entity-selector-container"]'));

		this.#inputsFactory.renderInputRowsTo(
			this.#container.querySelector('div[data-role="rows-container"]'),
			3,
		);

		const moreButton = this.#container.querySelector("[data-role='invite-more']");
		if (Type.isDomNode(moreButton))
		{
			Event.unbindAll(moreButton);
			Event.bind(moreButton, 'click', () => {
				this.#inputsFactory.renderInputRowTo(this.#container.querySelector('div[data-role="rows-container"]'));
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
		return Analytics.TAB_EXTRANET;
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
			workgroupIds: projectIds,
			tab: 'email',
		};

		const analyticsLabel = {
			INVITATION_TYPE: 'extranet',
			INVITATION_COUNT: items.length,
		};

		EventEmitter.emit(context, 'BX.Intranet.Invitation:onSendData', {
			action: 'extranet',
			data,
			analyticsLabel,
		});
	}

	onInviteSuccess(event: BaseEvent)
	{
		this.render().querySelector('form')?.reset();
	}

	getSubmitButtonText(): ?string
	{
		return Loc.getMessage('BX24_INVITE_DIALOG_ACTION_INVITE');
	}
}