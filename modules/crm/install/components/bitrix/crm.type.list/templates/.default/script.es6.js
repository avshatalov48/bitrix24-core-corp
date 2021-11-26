import { ajax as Ajax, Dom, Loc, Reflection, Text, Type } from "main.core";
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MessageBox, MessageBoxButtons } from "ui.dialogs.messagebox";

const namespace = Reflection.namespace('BX.Crm');

class TypeListComponent
{
	gridId: string;
	grid: BX.Main.grid;
	errorTextContainer: Element;
	welcomeMessageContainer: Element;
	isEmptyList: boolean;

	constructor(params): void
	{
		if (Type.isPlainObject(params))
		{
			if (Type.isString(params.gridId))
			{
				this.gridId = params.gridId;
			}
			if (this.gridId && BX.Main.grid && BX.Main.gridManager)
			{
				this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
			}
			if (Type.isElementNode(params.errorTextContainer))
			{
				this.errorTextContainer = params.errorTextContainer;
			}
			if (Type.isElementNode(params.welcomeMessageContainer))
			{
				this.welcomeMessageContainer = params.welcomeMessageContainer;
			}
			this.isEmptyList = Boolean(params.isEmptyList);
		}
	}

	init(): void
	{
		this.bindEvents();
	}

	bindEvents(): void
	{
		EventEmitter.subscribe('BX.Crm.TypeListComponent:onClickDelete', this.handleTypeDelete.bind(this));

		const toolbarComponent = this.getToolbarComponent();

		if (toolbarComponent)
		{
			/** @see BX.Crm.ToolbarComponent.subscribeTypeUpdatedEvent */
			toolbarComponent.subscribeTypeUpdatedEvent((event: BaseEvent) => {
				const isUrlChanged = Type.isObject(event.getData()) && (event.getData().isUrlChanged === true);
				if (isUrlChanged)
				{
					window.location.reload();
					return;
				}

				if (this.gridId && Reflection.getClass('BX.Main.gridManager.reload'))
				{
					Dom.removeClass(document.getElementById('crm-type-list-container'), 'crm-type-list-grid-empty');
					BX.Main.gridManager.reload(this.gridId);
				}
			});
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
	handleTypeDelete(event: BaseEvent): void
	{
		const id = Text.toInteger(event.data.id);

		if (!id)
		{
			this.showErrors([Loc.getMessage('CRM_TYPE_TYPE_NOT_FOUND')]);
			return;
		}

		MessageBox.show({
			title: Loc.getMessage('CRM_TYPE_TYPE_DELETE_CONFIRMATION_TITLE'),
			message: Loc.getMessage('CRM_TYPE_TYPE_DELETE_CONFIRMATION_MESSAGE'),
			modal: true,
			buttons: MessageBoxButtons.YES_CANCEL,
			onYes: (messageBox) => {
				Ajax.runAction(
					'crm.controller.type.delete', {
						analyticsLabel: 'crmTypeListDeleteType',
						data:
							{
								id,
							}
				}).then((response: {data: {}}) => {
					const isUrlChanged = Type.isObject(response.data) && (response.data.isUrlChanged === true);
					if (isUrlChanged)
					{
						window.location.reload();
						return;
					}

					this.grid.reloadTable();
				}).catch(this.showErrorsFromResponse.bind(this));

				messageBox.close();
			}
		});
	}
	//endregion

	getToolbarComponent(): ?BX.Crm.ToolbarComponent
	{
		if(Reflection.getClass('BX.Crm.ToolbarComponent'))
		{
			return BX.Crm.ToolbarComponent.Instance;
		}

		return null;
	}
}

namespace.TypeListComponent = TypeListComponent;
