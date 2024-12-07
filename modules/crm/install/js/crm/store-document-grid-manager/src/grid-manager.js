import { ajax, Dom, Event, Extension, Loc, Tag } from 'main.core';
import { Popup } from 'main.popup';
import { Button, ButtonColor } from 'ui.buttons';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { DocumentManager } from './document-manager';
import { MessageBox } from 'ui.dialogs.messagebox';

export class GridManager
{
	constructor(options)
	{
		this.gridId = options.gridId;
		this.filterId = options.filterId;
		this.grid = BX.Main.gridManager.getInstanceById(this.gridId);
		this.isConductDisabled = options.isConductDisabled;
		this.masterSliderUrl = options.masterSliderUrl;
		this.inventoryManagementSource = options.inventoryManagementSource;
		this.isInventoryManagementDisabled = options.isInventoryManagementDisabled;
		this.inventoryManagementFeatureCode = options.inventoryManagementFeatureCode;

		window.top.BX.addCustomEvent('onEntityEditorDocumentOrderShipmentControllerDocumentSave', this.reloadGrid.bind(this));
	}

	getSelectedIds()
	{
		return this.grid.getRows().getSelectedIds();
	}

	deleteDocument(documentId)
	{
		if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode)
		{
			top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);

			return;
		}

		MessageBox.confirm(
			Loc.getMessage('DOCUMENT_GRID_DOCUMENT_DELETE_CONTENT_2'),
			(messageBox, button) => {
				button.setWaiting();
				ajax.runAction(
					'crm.api.realizationdocument.setRealization',
					{
						data: {
							id: documentId,
							value: 'N',
						},
						analyticsLabel: {
							action: 'delete',
							inventoryManagementSource: this.inventoryManagementSource,
						},
					},
				).then(() => {
					messageBox.close();
					this.reloadGrid();
				}).catch((response) => {
					if (response.errors)
					{
						BX.UI.Notification.Center.notify({
							content: BX.util.htmlspecialchars(response.errors[0].message),
						});
					}
					messageBox.close();
				});
			},
			Loc.getMessage('DOCUMENT_GRID_DOCUMENT_DELETE_BUTTON_CONFIRM'),
			(messageBox) => messageBox.close(),
			Loc.getMessage('DOCUMENT_GRID_BUTTON_BACK'),
		);
	}

	conductDocument(documentId)
	{
		if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode)
		{
			top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);

			return;
		}

		if (this.isConductDisabled)
		{
			this.openStoreMasterSlider();

			return;
		}

		MessageBox.confirm(
			Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CONDUCT_CONTENT_2'),
			(messageBox, button) => {
				button.setWaiting();
				ajax.runAction(
					'crm.api.realizationdocument.setShipped',
					{
						data: {
							id: documentId,
							value: 'Y',
						},
						analyticsLabel: {
							action: 'deduct',
							inventoryManagementSource: this.inventoryManagementSource,
						},
					},
				).then(() => {
					messageBox.close();
					this.reloadGrid();
				}).catch((response) => {
					if (response.errors)
					{
						BX.UI.Notification.Center.notify({
							content: BX.util.htmlspecialchars(response.errors[0].message),
						});
					}
					messageBox.close();
				});
			},
			Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CONDUCT_BUTTON_CONFIRM'),
			(messageBox) => messageBox.close(),
			Loc.getMessage('DOCUMENT_GRID_BUTTON_BACK'),
		);
	}

	cancelDocument(documentId)
	{
		if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode)
		{
			top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);

			return;
		}

		if (this.isConductDisabled)
		{
			this.openStoreMasterSlider();

			return;
		}

		MessageBox.confirm(
			Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CANCEL_CONTENT_2'),
			(messageBox, button) => {
				button.setWaiting();
				ajax.runAction(
					'crm.api.realizationdocument.setShipped',
					{
						data: {
							id: documentId,
							value: 'N',
						},
						analyticsLabel: {
							action: 'cancelDeduct',
							inventoryManagementSource: this.inventoryManagementSource,
						},
					},
				).then(() => {
					messageBox.close();
					this.reloadGrid();
				}).catch((response) => {
					if (response.errors)
					{
						BX.UI.Notification.Center.notify({
							content: BX.util.htmlspecialchars(response.errors[0].message),
						});
					}
					messageBox.close();
				});
			},
			Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CANCEL_BUTTON_CONFIRM'),
			(messageBox) => messageBox.close(),
			Loc.getMessage('DOCUMENT_GRID_BUTTON_BACK'),
		);
	}

	deleteSelectedDocuments()
	{
		const documentIds = this.getSelectedIds();
		ajax.runAction(
			'crm.api.realizationdocument.setRealizationList',
			{
				data: {
					ids: documentIds,
					value: 'N',
				},
				analyticsLabel: {
					action: 'delete',
					inventoryManagementSource: this.inventoryManagementSource,
				},
			},
		).then((response) => {
			this.reloadGrid();
		}).catch((response) => {
			if (response.errors)
			{
				response.errors.forEach((error) => {
					if (error.message)
					{
						BX.UI.Notification.Center.notify({
							content: BX.util.htmlspecialchars(error.message),
						});
					}
				});
			}
			this.reloadGrid();
		});
	}

	conductSelectedDocuments()
	{
		if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode)
		{
			top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);

			return;
		}

		if (this.isConductDisabled)
		{
			this.openStoreMasterSlider();

			return;
		}
		const documentIds = this.getSelectedIds();
		ajax.runAction(
			'crm.api.realizationdocument.setShippedList',
			{
				data: {
					ids: documentIds,
					value: 'Y',
				},
				analyticsLabel: {
					inventoryManagementSource: this.inventoryManagementSource,
					action: 'deduct',
				},
			},
		).then((response) => {
			this.reloadGrid();
		}).catch((response) => {
			if (response.errors)
			{
				response.errors.forEach((error) => {
					if (error.message)
					{
						BX.UI.Notification.Center.notify({
							content: BX.util.htmlspecialchars(error.message),
						});
					}
				});
			}
			this.reloadGrid();
		});
	}

	cancelSelectedDocuments()
	{
		if (this.isInventoryManagementDisabled && this.inventoryManagementFeatureCode)
		{
			top.BX.UI.InfoHelper.show(this.inventoryManagementFeatureCode);

			return;
		}

		if (this.isConductDisabled)
		{
			this.openStoreMasterSlider();

			return;
		}
		const documentIds = this.getSelectedIds();
		ajax.runAction(
			'crm.api.realizationdocument.setShippedList',
			{
				data: {
					ids: documentIds,
					value: 'N',
				},
				analyticsLabel: {
					inventoryManagementSource: this.inventoryManagementSource,
					action: 'cancelDeduct',
				},
			},
		).then((response) => {
			this.reloadGrid();
		}).catch((response) => {
			if (response.errors)
			{
				response.errors.forEach((error) => {
					if (error.message)
					{
						BX.UI.Notification.Center.notify({
							content: BX.util.htmlspecialchars(error.message),
						});
					}
				});
			}
			this.reloadGrid();
		});
	}

	applyFilter(options)
	{
		const filterManager = BX.Main.filterManager.getById(this.filterId);
		if (!filterManager)
		{
			return;
		}

		filterManager.getApi().extendFilter(options);
	}

	processApplyButtonClick()
	{
		const actionValues = this.grid.getActionsPanel().getValues();
		const selectedAction = actionValues[`action_button_${this.gridId}`];

		if (selectedAction === 'conduct')
		{
			this.conductSelectedDocuments();
		}

		if (selectedAction === 'cancel')
		{
			this.cancelSelectedDocuments();
		}
	}

	openHowToShipProducts()
	{
		if (top.BX.Helper)
		{
			top.BX.Helper.show('redirect=detail&code=14640548');
			event.preventDefault();
		}
	}

	openStoreMasterSlider()
	{
		BX.SidePanel.Instance.open(
			this.masterSliderUrl,
			{
				cacheable: false,
				data: {
					openGridOnDone: false,
				},
				events: {
					onCloseComplete: function(event) {
						const slider = event.getSlider();
						if (!slider)
						{
							return;
						}

						if (slider.getData().get('isInventoryManagementEnabled'))
						{
							document.location.reload();
						}
					},
				},
			},
		);
	}

	reloadGrid()
	{
		this.grid.reload();
	}
}
