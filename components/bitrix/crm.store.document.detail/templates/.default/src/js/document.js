import {Loc, Reflection, Tag} from "main.core";
import {BaseCard} from "catalog.entity-card";
import {EventEmitter} from "main.core.events";
import {Button, ButtonColor, ButtonState} from "ui.buttons";
import {DocumentOnboardingManager, OnboardingData} from "./document.onboarding.manager";

export class Document extends BaseCard
{
	static #instance;

	static saveAndDeductAction = 'saveAndDeduct';
	static deductAction = 'deduct';
	static cancelDeductAction = 'cancelDeduct';

	#documentOnboardingManager: DocumentOnboardingManager|null = null;

	constructor(id, settings)
	{
		super(id, settings);
		this.isDocumentDeducted = settings.documentStatus === 'Y';
		this.isDeductLocked = settings.isDeductLocked;
		this.masterSliderUrl = settings.masterSliderUrl;
		this.inventoryManagementSource = settings.inventoryManagementSource;
		this.permissions = settings.permissions;

		this.addCopyLinkPopup();

		EventEmitter.subscribe('BX.Crm.EntityEditor:onFailedValidation', (event) => {
			EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {tabId: 'main'});
		});
		EventEmitter.subscribe('onProductsCheckFailed', (event) => {
			EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {tabId: 'tab_products'});
		});

		EventEmitter.subscribe('BX.Crm.EntityEditor:onSave', (event) => {
			const eventEditor = event.data[0];
			if (eventEditor && eventEditor._ajaxForm)
			{
				let action = eventEditor._ajaxForm?._actionName === 'SAVE' ? 'save' : eventEditor._ajaxForm?._config.data.ACTION;
				if (action === Document.saveAndDeductAction)
				{
					const controllersErrorCollection = this.getControllersIssues(eventEditor.getControllers());
					if (controllersErrorCollection.length > 0)
					{
						event.data[1].cancel = true;
						eventEditor._toolPanel?.setLocked(false);
						eventEditor._toolPanel?.addError(controllersErrorCollection[0]);
						return;
					}
				}

				if (action === 'SAVE')
				{
					// for consistency in analytics tags
					action = 'save';
				}

				let urlParams = {
					isNewDocument: this.entityId <= 0 ? 'Y' : 'N',
					inventoryManagementSource: this.inventoryManagementSource,
				};

				if (action)
				{
					urlParams.action = action;
				}

				eventEditor._ajaxForm.addUrlParams(urlParams);
			}
		});

		EventEmitter.subscribe('BX.Catalog.EntityCard.TabManager:onSelectItem', (event) => {
			const tabId = event.data.tabId;
			if (tabId === 'tab_products' && !this.isTabAnalyticsSent)
			{
				this.sendAnalyticsData({
					tab: 'products',
					isNewDocument: this.entityId <= 0 ? 'Y' : 'N',
					documentType: 'W',
					inventoryManagementSource: this.inventoryManagementSource,
				});
				this.isTabAnalyticsSent = true;
			}
		});

		this.#subscribeToProductRowSummaryEvents();

		Document.#instance = this;

		BX.UI.SidePanel.Wrapper.setParam("closeAfterSave", true);
		this.showNotificationOnClose = false;
	}

	#subscribeToProductRowSummaryEvents()
	{
		EventEmitter.subscribe('BX.UI.EntityEditorProductRowSummary:onDetailProductListLinkClick', () => {
			EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {tabId: 'tab_products'});
		});

		EventEmitter.subscribe('BX.UI.EntityEditorProductRowSummary:onAddNewRowInProductList', () => {
			EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {tabId: 'tab_products'});
			setTimeout(() => {
				EventEmitter.emit('onFocusToProductList');
			}, 500);
		});
	}

	focusOnTab(tabId)
	{
		EventEmitter.emit('BX.Catalog.EntityCard.TabManager:onOpenTab', {tabId: tabId});
	}

	getControllersIssues(controllers)
	{
		let validateErrorCollection = [];
		if (controllers instanceof Array)
		{
			controllers.forEach((controller) => {
				if (controller instanceof BX.Crm.EntityStoreDocumentProductListController)
				{
					validateErrorCollection.push(...controller.getErrorCollection());
				}
			});
		}

		return validateErrorCollection;
	}

	static getInstance()
	{
		return Document.#instance;
	}

	openMasterSlider()
	{
		let card = this;

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
							card.isDeductLocked = false;

							let sliders = BX.SidePanel.Instance.getOpenSliders();
							sliders.forEach((slider) => {
								if (slider.getWindow()?.BX.Catalog?.DocumentGridManager)
								{
									slider.allowChangeHistory = false;
									slider.getWindow().location.reload();
								}
							});
						}
					}
				}
			}
		);
	}

	/**
	 * adds the "deduct" and "save and deduct" buttons to the tool panel
	 * using entity-editor's api to preserve the logic
	 */
	adjustToolPanel()
	{
		const editor = this.getEditorInstance();
		if (!editor)
		{
			return;
		}

		const savePanel = editor._toolPanel;
		const saveButton = editor._toolPanel._editButton;

		this.defaultSaveActionName = editor._ajaxForm._config.data.ACTION;
		this.defaultOnSuccessCallback = editor._ajaxForm._config.onsuccess;

		saveButton.onclick = (event) => {
			this.showNotificationOnClose = false;
			editor._ajaxForm._config.data.ACTION = this.defaultSaveActionName;
			editor._ajaxForm._config.onsuccess = this.defaultOnSuccessCallback;
			savePanel.onSaveButtonClick(event);
		};

		if (this.permissions.conduct && !this.isDocumentDeducted)
		{
			const deductAndSaveButton = Tag.render`<button class="ui-btn ui-btn-light-border">${Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_SAVE_AND_DEDUCT_BUTTON')}</button>`;
			deductAndSaveButton.onclick = (event) => {
				if (this.isDeductLocked)
				{
					this.openMasterSlider();

					return;
				}

				editor._ajaxForm._config.data.ACTION = Document.saveAndDeductAction;
				editor._ajaxForm._config.onsuccess = (result) => {
					this.showNotificationOnClose = true;
					const error = BX.prop.getString(result, 'ERROR', '');
					if (!error)
					{
						this.setViewModeButtons(editor);
					}

					editor.onSaveSuccess(result);
				};

				savePanel.onSaveButtonClick(event);
			};
			saveButton.after(deductAndSaveButton);
			this.deductAndSaveButton = deductAndSaveButton;

			const deductButton = new Button({
				text: Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_DEDUCT_BUTTON'),
				color: ButtonColor.LIGHT_BORDER,
				onclick: (button, event) => {
					if (savePanel.isLocked())
					{
						return;
					}
					if (this.isDeductLocked)
					{
						this.openMasterSlider();

						return;
					}
					button.setState(ButtonState.CLOCKING);
					savePanel.setLocked(true);

					const actionName = Document.deductAction;
					const controllers = editor.getControllers();
					const errorCollection = [];
					controllers.forEach((controller) => {
						if (controller instanceof BX.Crm.EntityStoreDocumentProductListController)
						{
							if (!controller.validateProductList())
							{
								errorCollection.push(...controller.getErrorCollection());
							}
						}
					})

					if (errorCollection.length > 0)
					{
						savePanel.clearErrors();
						savePanel.addError(errorCollection[0]);
						savePanel.setLocked(false);
						button.setActive(true);

						return;
					}

					let formData = {};
					if (window.EntityEditorDocumentOrderShipmentController)
					{
						formData = window.EntityEditorDocumentOrderShipmentController.demandFormData();
					}

					const deductDocumentAjaxForm = editor.createAjaxForm(
						{
							actionName: actionName,
							enableRequiredUserFieldCheck: false,
							formData: formData,
						},
						{
							onSuccess: (result) => {
								if (!this.isDocumentDeducted)
								{
									this.showNotificationOnClose = true;
								}

								button.setState(ButtonState.ACTIVE);
								editor.onSaveSuccess(result);
							},
							onFailure: (result) => {
								button.setState(ButtonState.ACTIVE);
								editor.onSaveFailure(result);
							},
						}
					);

					deductDocumentAjaxForm.addUrlParams({
						action: actionName,
						documentType: 'W',
					});

					deductDocumentAjaxForm.submit();
				},
			}).render();
			saveButton.after(deductButton);
			this.deductButton = deductButton;
		}
		else if (this.permissions.cancel)
		{
			const deductButton = new Button({
				text:  Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_CANCEL_DEDUCT_BUTTON'),
				color: ButtonColor.LIGHT_BORDER,
				onclick: (button, event) => {
					if (savePanel.isLocked())
					{
						return;
					}
					if (this.isDeductLocked)
					{
						this.openMasterSlider();

						return;
					}
					button.setState(ButtonState.CLOCKING);
					savePanel.setLocked(true);

					const actionName = Document.cancelDeductAction;
					let formData = {};
					if (window.EntityEditorDocumentOrderShipmentController)
					{
						formData = window.EntityEditorDocumentOrderShipmentController.demandFormData();
					}

					const deductDocumentAjaxForm = editor.createAjaxForm(
						{
							actionName: actionName,
							enableRequiredUserFieldCheck: false,
							formData: formData,
						},
						{
							onSuccess: (result) => {
								if (!this.isDocumentDeducted)
								{
									this.showNotificationOnClose = true;
								}

								button.setState(ButtonState.ACTIVE);
								editor.onSaveSuccess(result);
							},
							onFailure: (result) => {
								button.setState(ButtonState.ACTIVE);
								editor.onSaveFailure(result);
							},
						}
					);

					deductDocumentAjaxForm.addUrlParams({
						action: actionName,
						documentType: 'W',
					inventoryManagementSource: this.inventoryManagementSource,
					});

					deductDocumentAjaxForm.submit();
				},
			}).render();
			saveButton.after(deductButton);
			this.deductButton = deductButton;
		}

		EventEmitter.subscribe('BX.Crm.EntityEditor:onControlModeChange', (event) => {
			const eventEditor = event.data[0];
			const control = event.data[1].control;
			if(control.getMode() === BX.Crm.EntityEditorMode.edit)
			{
				this.setEditModeButtons(eventEditor);
			}
			else
			{
				this.setViewModeButtons(eventEditor);
			}
		});

		EventEmitter.subscribe('BX.Crm.EntityEditor:onControlChange', (event) => {
			const eventEditor = event.data[0];
			this.setEditModeButtons(eventEditor);
		});

		EventEmitter.subscribe('BX.Crm.EntityEditor:onControllerChange', (event) => {
			const eventEditor = event.data[0];
			this.setEditModeButtons(eventEditor);
		});

		EventEmitter.subscribe('BX.Crm.EntityEditor:onSwitchToViewMode', (event) => {
			const eventEditor = event.data[0];
			this.setViewModeButtons(eventEditor);
		});

		EventEmitter.subscribe('BX.Crm.EntityEditor:onNothingChanged', (event) => {
			const eventEditor = event.data[0];
			this.setViewModeButtons(eventEditor);
		});

		EventEmitter.subscribe('onEntityCreate', (event) => {
			const editor = event?.data[0]?.sender;
			if (editor)
			{
				editor._toolPanel.disableSaveButton();
				editor.hideToolPanel();
			}
		});

		EventEmitter.subscribe('beforeCrmEntityRedirect', (event) => {
			const editor = event?.data[0]?.sender;
			if (editor)
			{
				editor._toolPanel.disableSaveButton();
				editor.hideToolPanel();

				if (this.showNotificationOnClose)
				{
					let url = event.data[0].redirectUrl;
					if (!url)
					{
						return;
					}
					url = BX.Uri.removeParam(url, 'closeOnSave');

					window.top.BX.UI.Notification.Center.notify({
						content: Loc.getMessage('CRM_STORE_DOCUMENT_SAVE_AND_CONDUCT_NOTIFICATION'),
						actions: [
							{
								title: Loc.getMessage('CRM_STORE_DOCUMENT_OPEN_DOCUMENT'),
								href: url,
								events: {
									click: function(event, balloon, action) {
										balloon.close();
									}
								}
							}
						],
					});
				}
			}
		});

		if (editor.isNew())
		{
			this.setEditModeButtons(editor);
		}
		else
		{
			this.setViewModeButtons(editor);
		}
	}

	setViewModeButtons(editor)
	{
		if (editor._toolPanel && editor._toolPanel.hasOwnProperty('_cancelButton'))
		{
			BX.hide(editor._toolPanel._cancelButton);
		}

		if (editor._toolPanel && editor._toolPanel.hasOwnProperty('_editButton'))
		{
			BX.hide(editor._toolPanel._editButton);
		}

		if (this.deductAndSaveButton)
		{
			BX.hide(this.deductAndSaveButton);
		}
		if (this.deductButton)
		{
			BX.show(this.deductButton);
		}
	}

	setEditModeButtons(editor)
	{
		if (editor._toolPanel && editor._toolPanel.hasOwnProperty('_cancelButton'))
		{
			BX.show(editor._toolPanel._cancelButton);
		}

		if (editor._toolPanel && editor._toolPanel.hasOwnProperty('_editButton'))
		{
			BX.show(editor._toolPanel._editButton);
		}

		if (this.deductAndSaveButton && !this.isDocumentDeducted)
		{
			BX.show(this.deductAndSaveButton);
		}
		if (this.deductButton && !this.isDocumentDeducted)
		{
			BX.hide(this.deductButton);
		}
	}

	getEditorInstance()
	{
		if (Reflection.getClass('BX.Crm.EntityEditor'))
		{
			return BX.Crm.EntityEditor.getDefault();
		}

		return null;
	}

	addCopyLinkPopup()
	{
		let copyLinkButton = document.getElementById(this.settings.copyLinkButtonId);
		if (!copyLinkButton)
		{
			return;
		}

		copyLinkButton.onclick = () => {
			this.copyDocumentLinkToClipboard();
		}
	}

	copyDocumentLinkToClipboard()
	{
		let url = BX.util.remove_url_param(window.location.href, ["IFRAME", "IFRAME_TYPE"]);
		if(!BX.clipboard.copy(url))
		{
			return;
		}

		var popup = new BX.PopupWindow(
			'catalog_copy_document_url_to_clipboard',
			document.getElementById(this.settings.copyLinkButtonId),
			{
				content: Loc.getMessage('CRM_STORE_DOCUMENT_DETAIL_LINK_COPIED'),
				darkMode: true,
				autoHide: true,
				zIndex: 1000,
				angle: true,
				bindOptions: { position: "top" }
			}
		);
		popup.show();

		setTimeout(function(){ popup.close(); }, 1500);
	}

	sendAnalyticsData(data)
	{
		BX.ajax.runComponentAction(
			'bitrix:crm.store.document.detail',
			'sendAnalytics',
			{
				mode: 'class',
				analyticsLabel: data,
			}
		);
	}

	enableOnboardingChain(onboardingData: OnboardingData)
	{
		if (this.#documentOnboardingManager === null)
		{
			this.#documentOnboardingManager  = new DocumentOnboardingManager({
				onboardingData: onboardingData,
				documentGuid: this.id,
				productListController: this.getProductListController(),
			});
			this.#documentOnboardingManager.processOnboarding();
		}
	}

	getProductListController(): BX.Crm.EntityStoreDocumentProductListController|null
	{
		const editor = this.getEditorInstance();
		const controllers = editor.getControllers();
		for (const controller of controllers)
		{
			if (controller instanceof BX.Crm.EntityStoreDocumentProductListController)
			{
				return controller;
			}
		}

		return null;
	}
}
