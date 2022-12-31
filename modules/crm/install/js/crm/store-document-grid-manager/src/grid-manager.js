import {ajax, Loc} from 'main.core';
import {Popup} from 'main.popup';
import {Button, ButtonColor} from 'ui.buttons';
import {BaseEvent, EventEmitter} from "main.core.events";
import {DocumentManager} from "./document-manager"

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

		window.top.BX.addCustomEvent('onEntityEditorDocumentOrderShipmentControllerDocumentSave', this.reloadGrid.bind(this));
	}

	getSelectedIds()
	{
		return this.grid.getRows().getSelectedIds()
	}

	deleteDocument(documentId)
	{
		let popup = new Popup({
			id: 'crm_delete_document_popup',
			titleBar: Loc.getMessage('DOCUMENT_GRID_DOCUMENT_DELETE_TITLE'),
			content: Loc.getMessage('DOCUMENT_GRID_DOCUMENT_DELETE_CONTENT'),
			buttons: [
				new Button({
					text:  Loc.getMessage('DOCUMENT_GRID_CONTINUE'),
					color: ButtonColor.SUCCESS,
					onclick: (button, event) => {
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
							}
						).then((response) => {
							popup.destroy();
							this.reloadGrid();
						}).catch((response) => {
							if (response.errors)
							{
								BX.UI.Notification.Center.notify({
									content: BX.util.htmlspecialchars(response.errors[0].message),
								});
							}
							popup.destroy();
						});
					},
				}),
				new Button({
					text: Loc.getMessage('DOCUMENT_GRID_CANCEL'),
					color: ButtonColor.DANGER,
					onclick: (button, event) => {
						popup.destroy();
					}
				}),
			],
		});
		popup.show();
	}

	conductDocument(documentId)
	{
		if (this.isConductDisabled)
		{
			this.openStoreMasterSlider();
			return;
		}
		let popup = new Popup({
			id: 'crm_delete_document_popup',
			titleBar: Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CONDUCT_TITLE'),
			content: Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CONDUCT_CONTENT'),
			buttons: [
				new Button({
					text:  Loc.getMessage('DOCUMENT_GRID_CONTINUE'),
					color: ButtonColor.SUCCESS,
					onclick: (button, event) => {
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
							}
						).then((response) => {
							popup.destroy();
							this.reloadGrid();
						}).catch((response) => {
							if (response.errors)
							{
								BX.UI.Notification.Center.notify({
									content: BX.util.htmlspecialchars(response.errors[0].message),
								});
							}
							popup.destroy();
						});
					},
				}),
				new Button({
					text: Loc.getMessage('DOCUMENT_GRID_CANCEL'),
					color: ButtonColor.DANGER,
					onclick: (button, event) => {
						popup.destroy();
					}
				}),
			],
		});
		popup.show();
	}

	cancelDocument(documentId)
	{
		if (this.isConductDisabled)
		{
			this.openStoreMasterSlider();
			return;
		}
		let popup = new Popup({
			id: 'crm_delete_document_popup',
			titleBar: Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CANCEL_TITLE'),
			content: Loc.getMessage('DOCUMENT_GRID_DOCUMENT_CANCEL_CONTENT'),
			buttons: [
				new Button({
					text:  Loc.getMessage('DOCUMENT_GRID_CONTINUE'),
					color: ButtonColor.SUCCESS,
					onclick: (button, event) => {
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
							}
						).then((response) => {
							popup.destroy();
							this.reloadGrid();
						}).catch((response) => {
							if (response.errors)
							{
								BX.UI.Notification.Center.notify({
									content: BX.util.htmlspecialchars(response.errors[0].message),
								});
							}
							popup.destroy();
						});
					},
				}),
				new Button({
					text: Loc.getMessage('DOCUMENT_GRID_CANCEL'),
					color: ButtonColor.DANGER,
					onclick: (button, event) => {
						popup.destroy();
					}
				}),
			],
		});
		popup.show();
	}

	deleteSelectedDocuments()
	{
		let documentIds = this.getSelectedIds();
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
			}
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
		if (this.isConductDisabled)
		{
			this.openStoreMasterSlider();
			return;
		}
		let documentIds = this.getSelectedIds();
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
			}
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
		if (this.isConductDisabled)
		{
			this.openStoreMasterSlider();
			return;
		}
		let documentIds = this.getSelectedIds();
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
			}
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
		let filterManager = BX.Main.filterManager.getById(this.filterId);
		if (!filterManager)
		{
			return;
		}

		filterManager.getApi().extendFilter(options);
	}

	processApplyButtonClick()
	{
		let actionValues = this.grid.getActionsPanel().getValues();
		let selectedAction = actionValues['action_button_' + this.gridId];

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
		if(top.BX.Helper)
		{
			top.BX.Helper.show("redirect=detail&code=14640548");
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
						let slider = event.getSlider();
						if (!slider)
						{
							return;
						}

						if (slider.getData().get('isInventoryManagementEnabled'))
						{
							document.location.reload();
						}
					}
				}
			}
		);
	}

	reloadGrid()
	{
		this.grid.reload();
	}
}
