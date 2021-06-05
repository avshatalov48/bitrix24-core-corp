import { ajax as Ajax, Loc, Reflection, Text, Type } from "main.core";
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MessageBox, MessageBoxButtons } from "ui.dialogs.messagebox";
import { Router } from "crm.router";

const namespace = Reflection.namespace('BX.Crm');

let instance;

class ItemListComponent
{
	entityTypeId: number;
	categoryId: number;
	gridId: string;
	grid: BX.Main.grid;
	errorTextContainer: Element;
	itemCreateUrl: ?string;

	constructor(params): void
	{
		if(Type.isPlainObject(params))
		{
			this.entityTypeId = Text.toInteger(params.entityTypeId);
			this.categoryId = Text.toInteger(params.categoryId);

			if (Type.isString(params.gridId))
			{
				this.gridId = params.gridId;
			}
			if(this.gridId && BX.Main.grid && BX.Main.gridManager)
			{
				this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
			}
			if (Type.isElementNode(params.errorTextContainer))
			{
				this.errorTextContainer = params.errorTextContainer;
			}
			this.itemCreateUrl = params.itemCreateUrl;
		}

		instance = this;
	}

	init(): void
	{
		this.bindEvents();
	}

	bindEvents(): void
	{
		EventEmitter.subscribe('BX.Crm.ItemListComponent:onClickDelete', this.handleItemDelete.bind(this));

		const toolbarComponent = Reflection.getClass('BX.Crm.ToolbarComponent')
			? Reflection.getClass('BX.Crm.ToolbarComponent').Instance
			: null;

		if (toolbarComponent)
		{
			toolbarComponent.subscribeTypeUpdatedEvent(() => {

				const newUrl = Router.Instance.getItemListUrl(this.entityTypeId, this.categoryId);
				if (newUrl)
				{
					window.location.href = newUrl;
					return;
				}

				window.location.reload();
			});
			if (this.grid)
			{
				toolbarComponent.subscribeCategoriesUpdatedEvent(() => {
					this.grid.reload();
				});
			}
		}
	}

	showErrors(errors: []): void
	{
		let text = '';
		errors.forEach((message) => {
			text = text + message + ' ';
		});

		if (Type.isElementNode(this.errorTextContainer))
		{
			this.errorTextContainer.innerText = text;
			this.errorTextContainer.parentElement.style.display = 'block';
		}
		else
		{
			console.error(text);
		}
	}

	hideErrors(): void
	{
		if (Type.isElementNode(this.errorTextContainer))
		{
			this.errorTextContainer.innerText = '';
			this.errorTextContainer.parentElement.style.display = 'none';
		}
	}

	showErrorsFromResponse({errors}): void
	{
		const messages = [];
		errors.forEach(({message}) => messages.push(message));
		this.showErrors(messages);
	}

	// region EventHandlers
	handleItemDelete(event: BaseEvent): void
	{
		const entityTypeId = Text.toInteger(event.data.entityTypeId);
		const id = Text.toInteger(event.data.id);

		if (!entityTypeId)
		{
			this.showErrors([Loc.getMessage('CRM_TYPE_TYPE_NOT_FOUND')]);
			return;
		}
		if (!id)
		{
			this.showErrors([Loc.getMessage('CRM_TYPE_ITEM_NOT_FOUND')]);
			return;
		}

		MessageBox.show({
			title: Loc.getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_TITLE'),
			message: Loc.getMessage('CRM_TYPE_ITEM_DELETE_CONFIRMATION_MESSAGE'),
			modal: true,
			buttons: MessageBoxButtons.YES_CANCEL,
			onYes: (messageBox) => {
				Ajax.runAction(
					'crm.controller.item.delete', {
						analyticsLabel: 'crmItemListDeleteItem',
						data:
							{
								entityTypeId,
								id,
							}
				}).then(() => {
					this.grid.reloadTable();
				}).catch(this.showErrorsFromResponse.bind(this));

				messageBox.close();
			}
		});
	}
	//endregion

	static handleAddButtonClick()
	{
		if (instance && instance.itemCreateUrl)
		{
			Router.openSlider(instance.itemCreateUrl).then(() => {
				if (instance.grid)
				{
					instance.grid.reload();
				}
			});
		}
	}
}

namespace.ItemListComponent = ItemListComponent;
