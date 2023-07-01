import { ajax as Ajax, Dom, Loc, Reflection, Runtime, Tag, Text, Type, Uri } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { StageFlow } from 'ui.stageflow';
import type { StageModelData } from 'crm.stage-model';
import { StageModel } from 'crm.stage-model';
import { Loader } from 'main.loader';
import { PopupMenu } from 'main.popup';
import { ReceiverRepository } from 'crm.messagesender';

export type ItemDetailsComponentParams = {
	entityTypeId: number,
	entityTypeName: string,
	serviceUrl: string,
	id: number,
	categoryId?: number,
	categories?: Category[],
	errorTextContainer: HTMLElement,
	stages: StageModelData[],
	currentStageId: number,
	messages: Object,
	signedParameters: ?string,
	documentButtonParameters: ?Object,
	isPageTitleEditable: boolean,
	userFieldCreateUrl: ?string,
	editorGuid: ?string,
	isStageFlowActive: ?boolean,
	pullTag: ?string,
	bizprocStarterConfig: ?Object,
	automationCheckAutomationTourGuideData: ?Object,
	receiversJSONString: string,
};

declare type Category = {
	id: string,
	categoryId: number,
	text: string,
	href: string,
};

const BACKGROUND_COLOR = 'd3d7dc';

export class ItemDetailsComponent
{
	entityTypeId: number;
	entityTypeName: string;
	id: number;
	categoryId: ?number = null;
	categories: ?Category[];
	errorTextContainer: HTMLElement;
	stages: StageModel[];
	stageflowChart: StageFlow.Chart;
	currentStageId: number;
	isProgress: boolean;
	container: HTMLElement;
	messages: {[code: string]: string};
	signedParameters: ?string;
	documentButtonParameters: ?Object;
	isPageTitleEditable: boolean;
	partialEntityEditor: ?BX.Crm.PartialEditorDialog;
	partialEditorId: ?string;
	editorContext: Object;
	userFieldCreateUrl: ?string;
	editorGuid: ?string;
	isStageFlowActive: ?boolean;
	pullTag: ?string;
	bizprocStarterConfig: ?Object;
	automationCheckAutomationTourGuideData: ?Object;
	receiversJSONString: string = '';

	constructor(params: ItemDetailsComponentParams): void
	{
		if(Type.isPlainObject(params))
		{
			this.entityTypeId = Text.toInteger(params.entityTypeId);
			this.entityTypeName = params.entityTypeName;
			this.id = Text.toInteger(params.id);
			if (BX.Crm.PartialEditorDialog && params.serviceUrl)
			{
				this.partialEditorId = 'partial_editor_' + this.entityTypeId + '_' + this.id;
				BX.Crm.PartialEditorDialog.registerEntityEditorUrl(this.entityTypeId, params.serviceUrl);
			}
			if (params.hasOwnProperty('editorContext'))
			{
				this.editorContext = params.editorContext;
			}
			if (params.hasOwnProperty('categoryId'))
			{
				this.categoryId = Text.toInteger(params.categoryId);
				this.categories = params.categories;
			}
			if (Type.isElementNode(params.errorTextContainer))
			{
				this.errorTextContainer = params.errorTextContainer;
			}
			if(Type.isArray(params.stages))
			{
				this.stages = [];
				params.stages.forEach((data) => {
					this.stages.push(new StageModel(data));
				});
			}
			this.currentStageId = params.currentStageId;
			this.messages = params.messages;
			this.signedParameters = params.signedParameters;
			this.documentButtonParameters = params.documentButtonParameters;
			this.userFieldCreateUrl = params.userFieldCreateUrl;
			this.editorGuid = params.editorGuid;
			this.isStageFlowActive = params.isStageFlowActive;
			this.pullTag = params.pullTag;
			this.bizprocStarterConfig = params.bizprocStarterConfig;
			this.automationCheckAutomationTourGuideData =
				Type.isPlainObject(params.automationCheckAutomationTourGuideData)
					? params.automationCheckAutomationTourGuideData
					: null
			;
			if (Type.isString(params.receiversJSONString))
			{
				this.receiversJSONString = params.receiversJSONString;
			}

			this.isPageTitleEditable = Boolean(params.isPageTitleEditable);
		}

		this.container = document.querySelector('[data-role="crm-item-detail-container"]');
		this.handleClosePartialEntityEditor = this.handleClosePartialEntityEditor.bind(this);
		this.handleErrorPartialEntityEditor = this.handleErrorPartialEntityEditor.bind(this);
	}

	getCurrentCategory(): ?Category
	{
		let currentCategory = null
		if(this.categories && this.categoryId)
		{
			this.categories.forEach((category) => {
				if(category.categoryId === this.categoryId)
				{
					currentCategory = category;
				}
			});
		}

		return currentCategory;
	}

	getLoader()
	{
		if(!this.loader)
		{
			this.loader = new Loader({
				size: 200,
				offset: {
					left: '-100px',
					top: '-200px',
				}
			});
			this.loader.layout.style.zIndex = 300;
		}

		return this.loader;
	}

	startProgress()
	{
		this.isProgress = true;
		if(!this.getLoader().isShown() && this.container)
		{
			this.getLoader().show(this.container);
		}
		this.hideErrors();
	}

	stopProgress()
	{
		this.isProgress = false;
		this.getLoader().hide();
	}

	getStageById(id: number): ?StageModel
	{
		let result = null;
		let key = 0;
		while(true)
		{
			if(!this.stages[key])
			{
				break;
			}
			const stage = this.stages[key];
			if(stage.getId() === id)
			{
				result = stage;
				break;
			}
			key++;
		}

		return result;
	}

	getStageByStatusId(statusId: string): ?StageModel
	{
		let result = null;
		let key = 0;
		while(true)
		{
			if(!this.stages[key])
			{
				break;
			}
			const stage = this.stages[key];
			if(stage.getStatusId() === statusId)
			{
				result = stage;
				break;
			}
			key++;
		}

		return result;
	}

	init(): void
	{
		this.initStageFlow();
		this.bindEvents();
		this.initDocumentButton();
		this.initReceiversRepository();
		if (this.id > 0)
		{
			this.initPageTitleButtons();
			this.initPull();
			this.initTours();
		}
	}

	initDocumentButton(): void
	{
		if(
			Type.isPlainObject(this.documentButtonParameters)
			&& this.documentButtonParameters.buttonId
			&& BX.DocumentGenerator
			&& BX.DocumentGenerator.Button
		)
		{
			this.documentButton = new BX.DocumentGenerator.Button(this.documentButtonParameters.buttonId, this.documentButtonParameters);
			this.documentButton.init();
		}
	}

	initReceiversRepository(): void
	{
		ReceiverRepository.onDetailsLoad(this.entityTypeId, this.id, this.receiversJSONString);
	}

	initPageTitleButtons(): void
	{
		const pageTitleButtons = Tag.render`
			<span id="pagetitle_btn_wrapper" class="pagetitile-button-container">
				<span id="page_url_copy_btn" class="crm-page-link-btn"></span>
			</span>
		`;

		if (this.isPageTitleEditable)
		{
			const editButton = Tag.render`
				<span id="pagetitle_edit" class="pagetitle-edit-button"></span>
			`;
			Dom.prepend(editButton, pageTitleButtons);
		}

		const pageTitle = document.getElementById('pagetitle');
		Dom.insertAfter(pageTitleButtons, pageTitle);

		if(Type.isArray(this.categories) && this.categories.length > 0)
		{
			const currentCategory = this.getCurrentCategory();
			if(currentCategory)
			{
				const categoriesSelector = Tag.render`
					<div id="pagetitle_sub" class="pagetitle-sub">
						<a href="#" onclick="${this.onCategorySelectorClick.bind(this)}">${currentCategory.text}</a>
					</div>
				`;

				Dom.insertAfter(categoriesSelector, pageTitleButtons);
			}
		}
	}

	onCategorySelectorClick(event)
	{
		if(!this.categoryId || !this.categories)
		{
			return;
		}

		const notCurrentCategories = this.categories.filter((category) => {
			return category.categoryId !== this.categoryId;
		});
		notCurrentCategories.forEach((category) => {
			delete category.href;
			category.onclick = () => {
				this.onCategorySelect(category.categoryId);
			}
		});

		PopupMenu.show({
			id: 'item-detail-' + this.entityTypeId + '-' + this.id,
			bindElement: event.target,
			items: notCurrentCategories
		});
	}

	onCategorySelect(categoryId)
	{
		if(this.isProgress)
		{
			return;
		}
		this.startProgress();
		Ajax.runAction('crm.controller.item.update', {
			analyticsLabel: 'crmItemDetailsChangeCategory',
			data: {
				entityTypeId: this.entityTypeId,
				id: this.id,
				fields: {
					categoryId
				}
			}
		}).then( () => {
			setTimeout(() => {
				//todo what if editor is changed ?
				window.location.reload();
			}, 500);
		}).catch(this.showErrorsFromResponse.bind(this))
	}

	initStageFlow()
	{
		if(this.stages)
		{
			const flowStagesData = this.prepareStageFlowStagesData();
			const stageFlowContainer = document.querySelector('[data-role="stageflow-wrap"]');
			if(stageFlowContainer)
			{
				this.stageflowChart = new StageFlow.Chart({
					backgroundColor: BACKGROUND_COLOR,
					currentStage: this.currentStageId,
					isActive: this.isStageFlowActive === true,
					onStageChange: this.onStageChange.bind(this),
					labels: {
						finalStageName: Loc.getMessage('CRM_ITEM_DETAIL_STAGEFLOW_FINAL_STAGE_NAME'),
						finalStagePopupTitle: Loc.getMessage('CRM_ITEM_DETAIL_STAGEFLOW_FINAL_STAGE_POPUP'),
						finalStagePopupFail: Loc.getMessage('CRM_ITEM_DETAIL_STAGEFLOW_FINAL_STAGE_POPUP_FAIL'),
						finalStageSelectorTitle: Loc.getMessage('CRM_ITEM_DETAIL_STAGEFLOW_FINAL_STAGE_SELECTOR'),
					},
				}, flowStagesData);
				stageFlowContainer.appendChild(this.stageflowChart.render());
			}
		}
	}

	prepareStageFlowStagesData(): Array
	{
		const flowStagesData = [];
		const isNew = (this.id <= 0);
		this.stages.forEach((stage: StageModel) => {
			const data = stage.getData();
			let color = (stage.getColor().indexOf('#') === 0) ? stage.getColor().substr(1) : stage.getColor();
			if(isNew)
			{
				color = BACKGROUND_COLOR;
			}
			data.isSuccess = stage.isSuccess();
			data.isFail = stage.isFailure();
			data.color = color;
			flowStagesData.push(data);
		});

		return flowStagesData;
	}

	bindEvents(): void
	{
		EventEmitter.subscribe('BX.Crm.ItemDetailsComponent:onClickDelete', this.handleItemDelete.bind(this));
		if (this.bizprocStarterConfig)
		{
			EventEmitter.subscribe(
				'BX.Crm.ItemDetailsComponent:onClickBizprocTemplates',
				this.handleBPTemplatesShow.bind(this),
			);
		}
		if (this.editorGuid && this.userFieldCreateUrl && BX.SidePanel && BX.Crm.EntityEditor)
		{
			EventEmitter.subscribe(
				'BX.UI.EntityConfigurationManager:onCreateClick',
				this.handleUserFieldCreationUrlClick.bind(this)
			);
		}
	}

	initPull()
	{
		const Pull = BX.PULL;
		if (!Pull)
		{
			console.error('pull is not initialized');
			return;
		}
		if (!this.pullTag)
		{
			return;
		}
		Pull.subscribe({
			moduleId: 'crm',
			command: this.pullTag,
			callback: (params) => {
				if (params && params.item && params.item.data)
				{
					const columnId = params.item.data.columnId;
					if (this.stageflowChart && this.stageflowChart.isActive)
					{
						const currentStage = this.getStageById(this.stageflowChart.currentStage);
						if (currentStage && currentStage.statusId !== columnId)
						{
							const newStage = this.getStageByStatusId(columnId);
							if (newStage)
							{
								this.updateStage(newStage);
							}
						}
					}
				}
			},
		});
		Pull.extendWatch(this.pullTag);
	}

	getEditor(): ?BX.Crm.EntityEditor
	{
		if (BX.Crm.EntityEditor)
		{
			if (this.editorGuid)
			{
				return BX.Crm.EntityEditor.get(this.editorGuid);
			}

			return BX.Crm.EntityEditor.getDefault();
		}

		return null;
	}

	bindPartialEntityEditorEvents()
	{
		EventEmitter.subscribe('Crm.PartialEditorDialog.Close', this.handleClosePartialEntityEditor);
		EventEmitter.subscribe('Crm.PartialEditorDialog.Error', this.handleErrorPartialEntityEditor);
	}

	unBindPartialEntityEditorEvents()
	{
		EventEmitter.unsubscribe('Crm.PartialEditorDialog.Close', this.handleClosePartialEntityEditor);
		EventEmitter.unsubscribe('Crm.PartialEditorDialog.Error', this.handleErrorPartialEntityEditor);
	}

	onStageChange(stageFlowStage: StageFlow.Stage)
	{
		if(this.isProgress)
		{
			return;
		}
		const stage = this.getStageById(stageFlowStage.getId());
		if(!stage)
		{
			console.error('Wrong stage');
			return;
		}
		this.startProgress();
		Ajax.runAction('crm.controller.item.update', {
			analyticsLabel: 'crmItemDetailsMoveItem',
			data: {
				entityTypeId: this.entityTypeId,
				id: this.id,
				fields: {
					stageId: stage.getStatusId()
				}
			}
		}).then( () => {
			this.stopProgress();

			let currentSlider: ?BX.SidePanel.Slider = null;
			if (Reflection.getClass('BX.SidePanel.Instance.getTopSlider'))
			{
				currentSlider = BX.SidePanel.Instance.getTopSlider();
			}
			if (currentSlider !== null)
			{
				if (Reflection.getClass('BX.Crm.EntityEvent'))
				{
					let eventParams = null;
					if(currentSlider)
					{
						eventParams = { "sliderUrl": currentSlider.getUrl() };
					}
					BX.Crm.EntityEvent.fireUpdate(this.entityTypeId, this.id, '', eventParams);
				}
			}

			this.updateStage(stage);
		}).catch((response) => {
			this.stopProgress();

			if (!this.partialEditorId)
			{
				this.showErrorsFromResponse(response);
				return;
			}

			const requiredFields = [];
			response.errors.forEach(({code, customData}) => {
				if (code === 'CRM_FIELD_ERROR_REQUIRED' && customData.fieldName)
				{
					requiredFields.push(customData.fieldName);
				}
			});

			if(requiredFields.length > 0)
			{
				BX.Crm.PartialEditorDialog.close(this.partialEditorId);

				this.partialEntityEditor = BX.Crm.PartialEditorDialog.create(
					this.partialEditorId,
					{
						title: BX.prop.getString(this.messages, "partialEditorTitle", "Please fill in all required fields"),
						entityTypeName: this.entityTypeName,
						entityTypeId: this.entityTypeId,
						entityId: this.id,
						fieldNames: requiredFields,
						helpData: null,
						context: this.editorContext || null,
						isController: true,
						stageId: stage.getStatusId(),
					}
				);

				this.bindPartialEntityEditorEvents();
				this.partialEntityEditor.open();
			}
			else
			{
				this.showErrorsFromResponse(response);
			}
		})
	}

	updateStage(stage: StageModel)
	{
		const currentStage = this.getStageById(this.stageflowChart.currentStage);
		this.stageflowChart.setCurrentStageId(stage.getId());
		EventEmitter.emit(
			'BX.Crm.ItemDetailsComponent:onStageChange',
			{
				entityTypeId: this.entityTypeId,
				id: this.id,
				stageId: stage.getStatusId(),
				previousStageId: currentStage ? currentStage.getStatusId() : null,
			}
		);
	}

	showError(error: string): void
	{
		if (Type.isElementNode(this.errorTextContainer))
		{
			this.errorTextContainer.innerText = error;
			this.errorTextContainer.parentElement.style.display = 'block';
		}
		else
		{
			console.error(error);
		}
	}

	showErrors(errors: string[]): void
	{
		let severalErrorsText = '';
		errors.forEach((message) => {
			severalErrorsText = severalErrorsText + message + ' ';
		});

		this.showError(severalErrorsText);
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
		this.stopProgress();
		const messages = [];
		errors.forEach(({message}) => messages.push(message));
		this.showErrors(messages);
	}

	normalizeUrl(url: Uri): Uri
	{
		// Allow redirects only in the current domain
		return url.setHost('');
	}

	// region EventHandlers
	handleItemDelete(): void
	{
		if(this.isProgress)
		{
			return;
		}
		MessageBox.show({
			title: this.messages.deleteItemTitle,
			message: this.messages.deleteItemMessage,
			modal: true,
			buttons: MessageBoxButtons.YES_CANCEL,
			onYes: (messageBox) => {
				this.startProgress();
				Ajax.runAction(
					'crm.controller.item.delete', {
						analyticsLabel: 'crmItemDetailsDeleteItem',
						data:
							{
								entityTypeId: this.entityTypeId,
								id: this.id,
							}
					}).then( ({data}) => {
					this.stopProgress();

					let currentSlider: ?BX.SidePanel.Slider = null;
					if (Reflection.getClass('BX.SidePanel.Instance.getTopSlider'))
					{
						currentSlider = BX.SidePanel.Instance.getTopSlider();
					}

					if (currentSlider !== null)
					{
						if (Reflection.getClass('BX.Crm.EntityEvent'))
						{
							let eventParams = null;
							if(currentSlider)
							{
								eventParams = { "sliderUrl": currentSlider.getUrl() };
							}
							BX.Crm.EntityEvent.fireDelete(this.entityTypeId, this.id, '', eventParams);
						}
						currentSlider.close();
					}
					else
					{
						const link = data.redirectUrl;
						if (Type.isStringFilled(link))
						{
							const url = this.normalizeUrl(new Uri(link));
							location.href = url.toString();
						}
					}
				}).catch(this.showErrorsFromResponse.bind(this));

				messageBox.close();
			}
		});
	}

	handleBPTemplatesShow(event)
	{
		const starter = new BX.Bizproc.Starter(this.bizprocStarterConfig);
		starter.showTemplatesMenu(event.data.button.button);
	}

	handleClosePartialEntityEditor(event: BaseEvent)
	{
		this.unBindPartialEntityEditorEvents();
		this.stopProgress();

		const data = event.getData();
		if(Type.isArray(data) && data.length === 2)
		{
			const parameters = data[1];

			if(parameters.isCancelled)
			{
				return;
			}

			const stage = this.getStageByStatusId(parameters.stageId);
			if(!stage)
			{
				return;
			}
			this.updateStage(stage);
		}
	}

	handleErrorPartialEntityEditor(event: BaseEvent)
	{
		this.unBindPartialEntityEditorEvents();

		this.stopProgress();

		const data = event.getData();
		if(Type.isArray(data) && data[1] && Type.isArray(data[1].errors))
		{
			this.showErrorsFromResponse({errors: data[1].errors});
		}
	}

	handleUserFieldCreationUrlClick(event: BaseEvent)
	{
		const data = event.getData();
		if (data.hasOwnProperty('isCanceled'))
		{
			event.setData({ ...data, ...{isCanceled: true}});
			BX.SidePanel.Instance.open(this.userFieldCreateUrl, {
				allowChangeHistory: false,
				cacheable: false,
				events: {
					onClose: this.onCreateUserFieldSliderClose.bind(this)
				}
			});
		}
	}

	onCreateUserFieldSliderClose(event: BX.SidePanel.Event)
	{
		const slider = event.getSlider();
		const sliderData = slider.getData();
		const userFieldData = sliderData.get('userFieldData');
		if (userFieldData && Type.isString(userFieldData))
		{
			this.reloadPageIfNotChanged();
		}
	}
	//endregion

	reloadPageIfNotChanged()
	{
		const editor = this.getEditor();
		if (editor)
		{
			if(editor.isChanged())
			{
				MessageBox.alert(this.messages.onCreateUserFieldAddMessage);
			}
			else
			{
				window.location.reload();
			}
		}
	}

	initTours()
	{
		if (this.automationCheckAutomationTourGuideData)
		{
			Runtime.loadExtension('bizproc.automation.guide')
				.then((exports) => {
					const {CrmCheckAutomationGuide} = exports;
					if (CrmCheckAutomationGuide)
					{
						CrmCheckAutomationGuide.showCheckAutomation(
							this.entityTypeName,
							this.categoryId ?? 0,
							this.automationCheckAutomationTourGuideData['options']
						);
					}
				})
			;
		}
	}
}
