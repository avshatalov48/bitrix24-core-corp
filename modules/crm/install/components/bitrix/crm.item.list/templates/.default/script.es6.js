import { Router } from 'crm.router';
import { SettingsButtonExtender } from 'crm.settings-button-extender';
import { ajax as Ajax, Event, Loc, Reflection, Runtime, Text, Type, Uri } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import 'ui.notification';

const namespace = Reflection.namespace('BX.Crm');

class ItemListComponent
{
	entityTypeId: number;
	categoryId: number;
	gridId: string;
	grid: BX.Main.grid;
	errorTextContainer: Element;
	entityTypeName: string;
	reloadGridTimeoutId: number;
	exportPopups: Object;
	#isIframe: boolean = false;
	#smartActivityNotificationSupported: boolean = false;
	// is the list is embedded in another entity detail tab
	#isEmbedded: boolean = false;
	#pingSettings: Object;
	#aiAutostartSettings: ?string;

	constructor(params): void
	{
		this.exportPopups = {};
		if(Type.isPlainObject(params))
		{
			this.entityTypeId = Text.toInteger(params.entityTypeId);
			this.entityTypeName = params.entityTypeName;
			this.categoryId = Text.toInteger(params.categoryId);
			this.#smartActivityNotificationSupported = Text.toBoolean(params.smartActivityNotificationSupported);

			if (Type.isString(params.gridId))
			{
				this.gridId = params.gridId;
			}
			if(this.gridId && BX.Main.grid && BX.Main.gridManager)
			{
				this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
				if (this.grid && params.backendUrl)
				{
					BX.addCustomEvent(window, "Grid::beforeRequest", (gridData, requestParams) => {
						if (!gridData.parent || gridData.parent !== this.grid)
						{
							return;
						}

						const currentUrl = new Uri(requestParams.url);
						const backendUrl = new Uri(params.backendUrl);

						if (currentUrl.getPath() !== backendUrl.getPath())
						{
							currentUrl.setPath(backendUrl.getPath());
							currentUrl.setQueryParams({...currentUrl.getQueryParams(), ...backendUrl.getQueryParams()});
						}

						requestParams.url = currentUrl.toString();
					});
				}
			}
			if (Type.isElementNode(params.errorTextContainer))
			{
				this.errorTextContainer = params.errorTextContainer;
			}
			if (Type.isBoolean(params.isIframe))
			{
				this.#isIframe = params.isIframe;
			}
			if (Type.isBoolean(params.isEmbedded))
			{
				this.#isEmbedded = params.isEmbedded;
			}
			if (Type.isPlainObject(params.pingSettings))
			{
				this.#pingSettings = params.pingSettings;
			}
			this.#aiAutostartSettings = Type.isString(params.aiAutostartSettings) ? params.aiAutostartSettings : null;
		}

		this.reloadGridTimeoutId = 0;
	}

	init(): void
	{
		this.bindEvents();

		this.#initSettingsButtonExtender();
	}

	bindEvents(): void
	{
		EventEmitter.subscribe('BX.Crm.ItemListComponent:onClickDelete', this.handleItemDelete.bind(this));

		EventEmitter.subscribe('BX.Crm.ItemListComponent:onStartExportCsv', (event) => {
			this.handleStartExport(event, 'csv');
		});
		EventEmitter.subscribe('BX.Crm.ItemListComponent:onStartExportExcel', (event) => {
			this.handleStartExport(event, 'excel');
		});

		const toolbarComponent = this.#getToolbarComponent();

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
					this.reloadGridAfterTimeout();
				});
			}
		}

		EventEmitter.subscribe('Crm.EntityConverter.Converted', (event) => {
			const parameters = event.data;
			if (!Type.isArray(parameters) || !parameters[1])
			{
				return;
			}
			const eventData = parameters[1];
			if (!this.entityTypeName || !eventData.entityTypeName)
			{
				return;
			}

			this.reloadGridAfterTimeout();
		});

		EventEmitter.subscribe("onLocalStorageSet", (event) => {
			const parameters = event.data;
			if (!Type.isArray(parameters) || !parameters[0])
			{
				return;
			}
			const params = parameters[0];
			const key = params.key || '';
			if (key !== "onCrmEntityCreate" && key !== "onCrmEntityUpdate" && key !== "onCrmEntityDelete" && key !== "onCrmEntityConvert")
			{
				return;
			}
			const eventData = params.value;
			if (!Type.isPlainObject(eventData))
			{
				return;
			}
			if (!this.entityTypeName || !eventData.entityTypeName)
			{
				return;
			}

			this.reloadGridAfterTimeout();
		});

		const addItemButton = document.querySelector('[data-role="add-new-item-button-' + this.gridId + '"]');
		if (addItemButton)
		{
			const detailUrl = addItemButton.href;
			addItemButton.href = "javascript: void(0);";
			Event.bind(addItemButton, 'click', (event) => {
				event.preventDefault();
				event.stopPropagation();

				EventEmitter.emit("BX.Crm.ItemListComponent:onAddNewItemButtonClick", {
					detailUrl,
					entityTypeId: this.entityTypeId,
				});
			});
		}
	}

	#getToolbarComponent(): ?BX.Crm.ToolbarComponent
	{
		const component = Reflection.getClass('BX.Crm.ToolbarComponent');

		return component ? component.Instance : null;
	}

	#initSettingsButtonExtender(): void
	{
		if (this.#isIframe || this.#isEmbedded)
		{
			return;
		}

		const toolbar = this.#getToolbarComponent();
		if (!toolbar)
		{
			console.error('BX.Crm.ToolbarComponent not found');

			return;
		}

		const settingsMenu = toolbar.getSettingsButton()?.getMenuWindow();
		if (settingsMenu)
		{
			/** @see BX.Crm.SettingsButtonExtender */
			new SettingsButtonExtender({
				smartActivityNotificationSupported: this.#smartActivityNotificationSupported,
				entityTypeId: this.entityTypeId,
				categoryId: this.categoryId,
				aiAutostartSettings: this.#aiAutostartSettings,
				pingSettings: this.#pingSettings,
				rootMenu: settingsMenu,
				grid: this.grid,
			});
		}
	}

	reloadGridAfterTimeout()
	{
		if (!this.grid)
		{
			return;
		}
		if (this.reloadGridTimeoutId > 0)
		{
			clearTimeout(this.reloadGridTimeoutId);
			this.reloadGridTimeoutId = 0;
		}

		this.reloadGridTimeoutId = setTimeout(() => {
			this.grid.reload();
		}, 1000);
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
					BX.UI.Notification.Center.notify({
						content: Loc.getMessage('CRM_TYPE_ITEM_DELETE_NOTIFICATION')
					});

					this.reloadGridAfterTimeout();
				}).catch(this.showErrorsFromResponse.bind(this));

				messageBox.close();
			}
		});
	}

	handleStartExport(event: BaseEvent, exportType: string): void
	{
		this.getExportPopup(exportType).then((process) => process.showDialog());
	}
	//endregion

	getExportPopup(exportType: string): Promise
	{
		if (this.exportPopups[exportType])
		{
			return Promise.resolve(this.exportPopups[exportType]);
		}

		return Runtime.loadExtension('ui.stepprocessing').then((exports) => {
			this.exportPopups[exportType] = exports.ProcessManager.create({
				id: 'crm.item.list.export.' + exportType,
				controller: 'bitrix:crm.api.itemExport',
				queue: [{action: 'dispatcher'}],
				params: {
					SITE_ID: Loc.getMessage('SITE_ID'),
					entityTypeId: this.entityTypeId,
					categoryId: this.categoryId,
					EXPORT_TYPE: exportType,
					COMPONENT_NAME: 'bitrix:crm.item.list',
				},
				messages: {
					DialogTitle: Loc.getMessage('CRM_ITEM_EXPORT_' + exportType.toUpperCase() + '_TITLE'),
					DialogSummary: Loc.getMessage('CRM_ITEM_EXPORT_' + exportType.toUpperCase() + '_SUMMARY'),
				},
				dialogMaxWidth: '650',
			});
			this.exportPopups[exportType].setHandler(BX.UI.StepProcessing.ProcessCallback.StepCompleted, ((formatInner) => {
				return () => {
					delete this.exportPopups[formatInner];
				}
			})(exportType));

			return this.exportPopups[exportType];
		});

	}
}

namespace.ItemListComponent = ItemListComponent;
