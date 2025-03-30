import { Builder, Dictionary } from 'crm.integration.analytics';
import { Router } from 'crm.router';
import { ToolbarComponent } from 'crm.toolbar-component';
import { CustomSection, TypeModel, TypeModelData } from 'crm.type-model';
import { Dom, Event, Loc, Reflection, Tag, Text, Type, Uri, Runtime } from 'main.core';
import { type BaseEvent, EventEmitter } from 'main.core.events';
import { Loader } from 'main.loader';
import { sendData as sendAnalyticsData } from 'ui.analytics';
import { MessageBox } from 'ui.dialogs.messagebox';
import { TagSelector } from 'ui.entity-selector';
import { Alert, AlertSize, AlertColor } from 'ui.alerts';

declare type Preset = {
	fields: {
		id: string,
		title: string,
		category: string,
		description: string,
		icon: string,
	},
	data: TypeModelData
}

declare type Relation = {
	title: string,
	isChecked: boolean,
	entityTypeId: number,
	isChildrenListEnabled: boolean,
}

const namespace = Reflection.namespace('BX.Crm.Component');

let instance: TypeDetail = null;

/**
 * @memberOf BX.Crm.Component
 */
class TypeDetail
{
	form;
	type: TypeModel;
	isProgress: boolean = false;
	container: Element;
	errorsContainer: Element;
	isNew: boolean;
	tabs: Map<string, Element> = new Map();
	presets: Array;
	selectedPresetId: ?string;
	relations: {
		parent: Relation[],
		child: Relation[],
	};

	parentRelationsController: RelationsController;
	childRelationsController: RelationsController;
	customSectionController: ?CustomSectionsController;
	isRestricted: boolean = false;
	restrictionErrorMessage: string;
	restrictionSliderCode: ?string;
	isExternal: boolean = false;
	isSaveFromTypeDetail: boolean = true;
	isCreateSectionsViaAutomatedSolutionDetails: boolean = false;
	canEditAutomatedSolution: boolean = false;
	permissionsUrl: ?string = null;

	#isCancelEventRegistered: boolean = false;

	constructor(params: {
		form: Element,
		type: TypeModel,
		container: Element,
		errorsContainer: Element,
		presets?: Array,
		relations: {
			parent: Relation[],
			child: Relation[],
		},
		isCreateSectionsViaAutomatedSolutionDetails: boolean,
		isExternal: boolean,
		isRestricted: boolean,
		restrictionErrorMessage: string,
		restrictionSliderCode: ?string,
		canEditAutomatedSolution: boolean,
		permissionsUrl: ?string,
    })
	{
		if (Type.isPlainObject(params))
		{
			this.type = params.type;
			this.isNew = !this.type.isSaved();
			this.form = params.form;
			this.container = params.container;
			this.errorsContainer = params.errorsContainer;
			this.presets = params.presets;
			this.relations = params.relations;
			this.isRestricted = Boolean(params.isRestricted);
			this.restrictionErrorMessage = Type.isStringFilled(params.restrictionErrorMessage) ? params.restrictionErrorMessage : '';
			this.restrictionSliderCode = Type.isStringFilled(params.restrictionSliderCode) && this.isRestricted
				? params.restrictionSliderCode
				: null
			;
			this.isExternal = Boolean(params.isExternal);
			this.isCreateSectionsViaAutomatedSolutionDetails = Boolean(params.isCreateSectionsViaAutomatedSolutionDetails);
			this.canEditAutomatedSolution = Boolean(params.canEditAutomatedSolution);
			if (Type.isStringFilled(params.permissionsUrl))
			{
				this.permissionsUrl = params.permissionsUrl;
			}
		}

		this.buttonsPanel = document.getElementById('ui-button-panel');
		this.saveButton = document.getElementById('ui-button-panel-save');
		this.cancelButton = document.getElementById('ui-button-panel-cancel');
		this.deleteButton = document.getElementById('ui-button-panel-remove');

		// eslint-disable-next-line unicorn/no-this-assignment
		instance = this;
	}

	init()
	{
		this.bindEvents();

		this.fillTabs();

		if (this.type.getId())
		{
			// const customPreset = this.getPresetById('bitrix:empty');
			// const presetSelector = document.querySelector('[data-role="crm-type-preset-selector"]');
			// if (customPreset && presetSelector)
			// {
			// 	presetSelector.textContent = customPreset.fields.title;
			// }
			this.disablePresetsView();
			const presetSelectorContainer = document.querySelector('[data-role="preset-selector-container"]');
			if (presetSelectorContainer)
			{
				Dom.addClass(presetSelectorContainer, 'crm-type-hidden');
			}
		}
		else
		{
			this.enablePresetsView();
		}

		Dom.removeClass(document.querySelector('body'), 'crm-type-hidden');

		this.initRelations();

		this.initCustomSections();
	}

	bindEvents()
	{
		Event.bind(this.saveButton, 'click', (event) => {
			this.save(event);
		}, {
			passive: false,
		});

		if (this.deleteButton)
		{
			Event.bind(this.deleteButton, 'click', (event) => {
				this.delete(event);
			});
		}

		const userFieldOption = this.getBooleanFieldNodeByName('isUseInUserfieldEnabled');
		if (userFieldOption)
		{
			Event.bind(userFieldOption, 'click', this.disableLinkedUserFieldsIfNotAvailable.bind(this));
		}

		this.form.querySelectorAll('[data-name*="linkedUserFields"]').forEach((linkedUserFieldNode) => {
			Event.bind(linkedUserFieldNode, 'click', this.enableUserFieldIfAnyLinkedChecked.bind(this));
		});

		EventEmitter.subscribe('SidePanel.Slider:onCloseByEsc', (event: BaseEvent) => {
			const [sliderEvent] = event.getData();
			const slider: BX.SidePanel.Slider = sliderEvent.getSlider();

			if (slider === this.getSlider())
			{
				this.#registerCancelEvent(Dictionary.ELEMENT_ESC_BUTTON);
			}
		});
		EventEmitter.subscribe('SidePanel.Slider:onClose', (event: BaseEvent) => {
			const [sliderEvent] = event.getData();
			const slider: BX.SidePanel.Slider = sliderEvent.getSlider();

			if (slider === this.getSlider())
			{
				this.#registerCancelEvent(null);
			}
		});

		this.handleSliderDestroy = this.handleSliderDestroy.bind(this);
		top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onDestroy', this.handleSliderDestroy);
	}

	handleSliderDestroy(event: BaseEvent): void
	{
		const [sliderEvent] = event.getData();
		const slider: BX.SidePanel.Slider = sliderEvent.getSlider();

		if (slider.getFrameWindow() === window)
		{
			// if we add event handler from iframe to the main page, they will live forever, even after slider destroys
			// sometimes it causes errors, like in this case
			this.destroy();
			top.BX.Event.EventEmitter.unsubscribe('SidePanel.Slider:onDestroy', this.handleSliderDestroy);
		}
	}

	#registerCancelEvent(element: ?string): void
	{
		if (this.#isCancelEventRegistered)
		{
			return;
		}

		this.#isCancelEventRegistered = true;

		sendAnalyticsData(
			this.#getAnalyticsBuilder()
				.setElement(element)
				.setStatus(Dictionary.STATUS_CANCEL)
				.buildData()
			,
		);
	}

	destroy(): void
	{
		this.customSectionController?.destroy();
	}

	enablePresetsView()
	{
		Dom.addClass(document.querySelector('body'), 'crm-type-settings-presets');
		const activeTab = this.container.querySelector('.crm-type-tab-current');
		if (activeTab)
		{
			Dom.removeClass(activeTab, 'crm-type-tab-current');
		}
		const presetsTab = this.container.querySelector('[data-tab="presets"]');
		if (presetsTab)
		{
			Dom.addClass(presetsTab, 'crm-type-tab-current');
		}
		const presetSelectorContainer = document.querySelector('[data-role="preset-selector-container"]');
		if (presetSelectorContainer)
		{
			Dom.addClass(presetSelectorContainer, 'crm-type-hidden');
		}
		Dom.removeClass(document.getElementById('pagetitle'), 'crm-type-hidden');
		Dom.addClass(this.buttonsPanel, 'crm-type-hidden');
		this.hideErrors();
	}

	disablePresetsView()
	{
		Dom.removeClass(document.querySelector('body'), 'crm-type-settings-presets');

		(this.#findActiveTabButton() ?? this.#findFirstTabButton()).click();

		const presetSelectorContainer = document.querySelector('[data-role="preset-selector-container"]');
		if (presetSelectorContainer)
		{
			Dom.removeClass(presetSelectorContainer, 'crm-type-hidden');
		}
		Dom.addClass(document.getElementById('pagetitle'), 'crm-type-hidden');
		Dom.removeClass(this.buttonsPanel, 'crm-type-hidden');
	}

	#findActiveTabButton(): ?HTMLElement
	{
		return document.querySelector('.ui-sidepanel-menu-item.ui-sidepanel-menu-active > [data-role^=tab-]');
	}

	#findFirstTabButton(): HTMLElement
	{
		return document.querySelector('.ui-sidepanel-menu-item > [data-role^=tab-]');
	}

	disableLinkedUserFieldsIfNotAvailable()
	{
		const userFieldOption = this.getBooleanFieldNodeByName('isUseInUserfieldEnabled');
		if (!this.isBooleanFieldChecked(userFieldOption))
		{
			this.form.querySelectorAll('[data-name*="linkedUserFields"]').forEach((linkedUserFieldNode) => {
				this.setBooleanFieldCheckedState(linkedUserFieldNode, false);
			});
		}
	}

	enableUserFieldIfAnyLinkedChecked()
	{
		const userFieldOption = this.getBooleanFieldNodeByName('isUseInUserfieldEnabled');
		if (!this.isBooleanFieldChecked(userFieldOption))
		{
			this.form.querySelectorAll('[data-name*="linkedUserFields"]').forEach((linkedUserFieldNode) => {
				if (this.isBooleanFieldChecked(linkedUserFieldNode))
				{
					this.setBooleanFieldCheckedState(userFieldOption, true);
				}
			});
		}
	}

	getLoader(): Loader
	{
		if (!this.loader)
		{
			this.loader = new Loader({ size: 150 });
		}

		return this.loader;
	}

	startProgress(): void
	{
		this.isProgress = true;
		if (!this.getLoader().isShown())
		{
			this.getLoader().show(this.form);
		}
		this.hideErrors();
	}

	stopProgress()
	{
		this.isProgress = false;
		this.getLoader().hide();
		setTimeout(() => {
			Dom.removeClass(this.saveButton, 'ui-btn-wait');
			Dom.removeClass(this.cancelButton, 'ui-btn-wait');
			if (this.deleteButton)
			{
				Dom.removeClass(this.deleteButton, 'ui-btn-wait');
			}
		}, 200);
	}

	save(event)
	{
		if (this.isRestricted)
		{
			if (
				Type.isStringFilled(this.restrictionSliderCode)
				&& Reflection.getClass('BX.UI.InfoHelper.show')
			)
			{
				BX.UI.InfoHelper.show(this.restrictionSliderCode);
			}
			else
			{
				this.showErrors([this.restrictionErrorMessage]);
			}

			this.stopProgress();

			return;
		}
		event.preventDefault();
		if (!this.form)
		{
			return;
		}

		if (this.isProgress)
		{
			return;
		}

		if (!this.type)
		{
			return;
		}
		this.startProgress();

		this.type.setTitle(this.form.querySelector('[name="title"]').value);
		TypeModel.getBooleanFieldNames().forEach((fieldName) => {
			const fieldNode = this.getBooleanFieldNodeByName(fieldName);
			if (fieldNode)
			{
				this.type.data[fieldName] = this.isBooleanFieldChecked(fieldNode);
			}
		});
		// this.type.setConversionMap({
		// 	sourceTypes: this.collectEntityTypeIds('conversion-source'),
		// 	destinationTypes: this.collectEntityTypeIds('conversion-destination'),
		// });
		const linkedUserFields = {};
		this.form.querySelectorAll('[data-name*="linkedUserFields"]').forEach((linkedUserFieldNode) => {
			const name = linkedUserFieldNode.dataset.name.slice('linkedUserFields['.length).replace(']', '');
			linkedUserFields[name] = this.isBooleanFieldChecked(linkedUserFieldNode);
		});
		this.type.setLinkedUserFields(linkedUserFields);
		this.type.setRelations({
			parent: this.parentRelationsController.getData(),
			child: this.childRelationsController.getData(),
		});
		if (this.customSectionController)
		{
			const customSectionData = this.customSectionController.getData();
			this.type.setCustomSectionId(customSectionData.customSectionId);
			this.type.setCustomSections(customSectionData.customSections);
			this.type.setIsExternalDynamicalType(this.isExternal);
			this.type.setIsSaveFromTypeDetail(this.isSaveFromTypeDetail);
		}

		const analyticsBuilder = this.#getAnalyticsBuilder()
			.setElement(Dictionary.ELEMENT_CREATE_BUTTON)
		;

		sendAnalyticsData(
			analyticsBuilder
				.setStatus(Dictionary.STATUS_ATTEMPT)
				.buildData()
			,
		);

		this.type.save().then((response) => {
			sendAnalyticsData(
				analyticsBuilder
					.setStatus(Dictionary.STATUS_SUCCESS)
					.setId(this.type.getId())
					.buildData()
				,
			);

			this.#isCancelEventRegistered = true;

			this.stopProgress();
			this.afterSave(response);
			this.isNew = false;
		}).catch((errors) => {
			sendAnalyticsData(
				analyticsBuilder
					.setStatus(Dictionary.STATUS_ERROR)
					.setId(this.type.getId())
					.buildData()
				,
			);

			this.showErrors(errors);
			this.stopProgress();
		});
	}

	#getAnalyticsBuilder(): Builder.Automation.Type.CreateEvent | Builder.Automation.Type.EditEvent
	{
		const builder = this.isNew
			? new Builder.Automation.Type.CreateEvent()
			: new Builder.Automation.Type.EditEvent()
		;

		builder.setIsExternal(this.isExternal);

		if (Type.isStringFilled(this.selectedPresetId))
		{
			builder.setPreset(this.selectedPresetId);
		}

		if (this.type.getId() > 0)
		{
			builder.setId(this.type.getId());
		}

		const currentUrl = new Uri(decodeURI(window.location.href));
		if (currentUrl.getQueryParam('c_sub_section') && builder instanceof Builder.Automation.Type.EditEvent)
		{
			builder.setSubSection(currentUrl.getQueryParam('c_sub_section'));
		}

		return builder;
	}

	collectEntityTypeIds(role: string): []
	{
		const entityTypeIds = [];

		const checkboxes = this.container.querySelectorAll(`[data-role="${role}"]`);
		[...checkboxes].forEach((checkbox: HTMLInputElement) => {
			if (checkbox.checked)
			{
				entityTypeIds.push(checkbox.dataset.entityTypeId);
			}
		});

		return entityTypeIds;
	}

	afterSave(response: {data: {}})
	{
		this.addDataToSlider('response', response);

		if (Object.hasOwn(response.data, 'urlTemplates'))
		{
			Router.Instance.setUrlTemplates(response.data.urlTemplates);
		}

		this.getSlider()?.close();

		this.emitTypeUpdatedEvent({
			isUrlChanged: (response.data.isUrlChanged === true),
		});
	}

	getSlider(): ?BX.SidePanel.Slider
	{
		return BX.SidePanel?.Instance?.getSliderByWindow(window);
	}

	emitTypeUpdatedEvent(data): void
	{
		ToolbarComponent.Instance.emitTypeUpdatedEvent(data);
	}

	addDataToSlider(key, data)
	{
		if (Type.isString(key) && Type.isPlainObject(data))
		{
			const slider = this.getSlider();
			if (slider)
			{
				slider.data.set(key, data);
			}
		}
	}

	showErrors(errors: string[])
	{
		let text = '';
		errors.forEach((message) => {
			text += message;
		});
		if (Type.isDomNode(this.errorsContainer))
		{
			this.errorsContainer.innerText = text;
			Dom.style(this.errorsContainer.parentNode, 'display', 'block');
		}
		else
		{
			console.error(text);
		}
	}

	hideErrors()
	{
		if (Type.isDomNode(this.errorsContainer))
		{
			Dom.style(this.errorsContainer.parentNode, 'display', 'none');
			this.errorsContainer.innerText = '';
		}
	}

	delete(event)
	{
		event.preventDefault();
		if (!this.form)
		{
			return;
		}

		if (this.isProgress)
		{
			return;
		}

		if (!this.type)
		{
			return;
		}

		const currentUrl = new Uri(decodeURI(window.location.href));

		const analyticsBuilder = (new Builder.Automation.Type.DeleteEvent())
			.setElement(Dictionary.ELEMENT_DELETE_BUTTON)
			.setIsExternal(this.isExternal)
			.setSubSection(currentUrl.getQueryParam('c_sub_section'))
			.setId(this.type.getId())
		;

		MessageBox.confirm(
			Loc.getMessage('CRM_TYPE_DETAIL_DELETE_CONFIRM'),
			() => {
				return new Promise((resolve) => {
					sendAnalyticsData(
						analyticsBuilder
							.setStatus(Dictionary.STATUS_ATTEMPT)
							.buildData()
						,
					);

					this.startProgress();
					this.type.delete().then((response) => {
						sendAnalyticsData(
							analyticsBuilder
								.setStatus(Dictionary.STATUS_SUCCESS)
								.buildData()
							,
						);

						this.#isCancelEventRegistered = true;

						this.stopProgress();

						const isUrlChanged = (Type.isObject(response.data) && (response.data.isUrlChanged === true));
						this.emitTypeUpdatedEvent({ isUrlChanged });

						Router.Instance.closeSliderOrRedirect(Router.Instance.getTypeListUrl());
					}).catch((errors: string[]) => {
						sendAnalyticsData(
							analyticsBuilder
								.setStatus(Dictionary.STATUS_ERROR)
								.buildData()
							,
						);

						this.showErrors(errors);
						this.stopProgress();
						resolve();
					});
				});
			},
			null,
			(box) => {
				sendAnalyticsData(
					analyticsBuilder
						.setStatus(Dictionary.STATUS_CANCEL)
						.buildData()
					,
				);

				this.stopProgress();
				box.close();
			},
		);
	}

	fillTabs()
	{
		if (this.container)
		{
			this.container.querySelectorAll('.crm-type-tab').forEach((tabNode: HTMLDivElement) => {
				if (tabNode.dataset.tab)
				{
					this.tabs.set(tabNode.dataset.tab, tabNode);
				}
			});
		}
	}

	showTab(tabNameToShow: string)
	{
		[...this.tabs.keys()].forEach((tabName: string) => {
			if (tabName === tabNameToShow)
			{
				Dom.addClass(this.tabs.get(tabName), 'crm-type-tab-current');
			}
			else
			{
				Dom.removeClass(this.tabs.get(tabName), 'crm-type-tab-current');
			}
		});
	}

	applyPreset(presetId: string)
	{
		this.disablePresetsView();
		const presetSelector = document.querySelector('[data-role="crm-type-preset-selector"]');
		const currentPresetNode = this.container.querySelector('[data-role="preset"].crm-type-preset-active');
		if (currentPresetNode)
		{
			const currentPreset = this.getPresetById(currentPresetNode.dataset.presetId);
			if (
				currentPreset
				&& currentPreset.data.title
				&& this.form.querySelector('[name="title"]').value === currentPreset.data.title)
			{
				this.form.querySelector('[name="title"]').value = '';
			}
		}
		const presets = this.container.querySelectorAll('[data-role="preset"]');
		presets.forEach((presetNode: HTMLDivElement) => {
			Dom.removeClass(presetNode, 'crm-type-preset-active');
			if (presetNode.dataset.presetId === presetId)
			{
				Dom.addClass(presetNode, 'crm-type-preset-active');
				const preset = this.getPresetById(presetId);
				if (preset)
				{
					this.updateInputs(preset.data);
					if (presetSelector)
					{
						presetSelector.textContent = Text.encode(preset.fields.title);
					}
				}
			}
		});

		this.selectedPresetId = presetId;
	}

	getPresetById(presetId: string)
	{
		for (const preset: Preset of this.presets)
		{
			if (preset.fields.id === presetId)
			{
				return preset;
			}
		}

		return null;
	}

	updateInputs(data: TypeModelData)
	{
		if (this.form.querySelector('[name="title"]').value.length <= 0)
		{
			this.form.querySelector('[name="title"]').value = data.title || '';
		}
		TypeModel.getBooleanFieldNames().forEach((fieldName) => {
			const node = this.getBooleanFieldNodeByName(fieldName);
			if (node)
			{
				this.setBooleanFieldCheckedState(node, data[fieldName]);
			}
		});
		this.disableLinkedUserFieldsIfNotAvailable();
	}

	toggleBooleanField(fieldName: string): void
	{
		const node = this.getBooleanFieldNodeByName(fieldName);
		if (!node)
		{
			return;
		}

		if (node.nodeName === 'INPUT')
		{
			node.checked = !node.checked;
		}
		else
		{
			Dom.toggleClass(node, 'crm-type-field-button-item-active');
		}
	}

	getBooleanFieldNodeByName(fieldName: string): ?HTMLDivElement | HTMLInputElement
	{
		return this.container.querySelector(`[data-name="${fieldName}"]`);
	}

	isBooleanFieldChecked(node: HTMLDivElement | HTMLInputElement): boolean
	{
		if (node.nodeName === 'INPUT')
		{
			return node.checked;
		}

		return Dom.hasClass(node, 'crm-type-field-button-item-active');
	}

	setBooleanFieldCheckedState(node: HTMLDivElement | HTMLInputElement, isChecked: boolean): void
	{
		if (node.nodeName === 'INPUT')
		{
			// eslint-disable-next-line no-param-reassign
			node.checked = isChecked;

			return;
		}

		if (isChecked)
		{
			Dom.addClass(node, 'crm-type-field-button-item-active');
		}
		else
		{
			Dom.removeClass(node, 'crm-type-field-button-item-active');
		}
	}

	initRelations(): void
	{
		this.parentRelationsController = new RelationsController({
			switcher: BX.UI.Switcher.getById('crm-type-relation-parent-switcher'),
			container: this.container.querySelector('[data-role="crm-type-relation-parent-items"]'),
			typeSelectorContainer: this.container.querySelector('[data-role="crm-type-relation-parent-items-selector"]'),
			tabsContainer: this.container.querySelector('[data-role="crm-type-relation-parent-items-tabs"]'),
			tabsCheckbox: this.container.querySelector('[data-name="isRelationParentShowChildrenEnabled"]'),
			tabsSelectorContainer: this.container.querySelector('[data-role="crm-type-relation-parent-items-tabs-selector"]'),
			relations: this.relations.parent,
		});

		this.childRelationsController = new RelationsController({
			switcher: BX.UI.Switcher.getById('crm-type-relation-child-switcher'),
			container: this.container.querySelector('[data-role="crm-type-relation-child-items"]'),
			typeSelectorContainer: this.container.querySelector('[data-role="crm-type-relation-child-items-selector"]'),
			tabsContainer: this.container.querySelector('[data-role="crm-type-relation-child-items-tabs"]'),
			tabsCheckbox: this.container.querySelector('[data-name="isRelationChildShowChildrenEnabled"]'),
			tabsSelectorContainer: this.container.querySelector('[data-role="crm-type-relation-child-items-tabs-selector"]'),
			relations: this.relations.child,
		});
	}

	initCustomSections()
	{
		this.customSectionController = new CustomSectionsController({
			switcher: BX.UI.Switcher.getById('crm-type-custom-section-switcher'),
			container: this.container.querySelector('[data-role="crm-type-custom-section-container"]'),
			selectorContainer: this.container.querySelector('[data-role="crm-type-custom-section-selector"]'),
			customSections: this.type.getCustomSections() || [],
			isCreateSectionsViaAutomatedSolutionDetails: this.isCreateSectionsViaAutomatedSolutionDetails,
			canEditAutomatedSolution: this.canEditAutomatedSolution,
			isNew: this.isNew,
			permissionsUrl: this.permissionsUrl,
		});
	}

	static handleLeftMenuClick(tabName: string)
	{
		if (instance)
		{
			instance.showTab(tabName);
		}
	}

	static handlePresetClick(presetId: string)
	{
		if (instance)
		{
			instance.applyPreset(presetId);
		}
	}

	static handleHideDescriptionClick(target: HTMLDivElement)
	{
		Dom.style(target.parentNode, 'display', 'none');
	}

	static handleBooleanFieldClick(fieldName: string)
	{
		instance?.toggleBooleanField(fieldName);
	}

	static handlePresetSelectorClick()
	{
		instance?.enablePresetsView();
	}

	static handleCancelButtonClick()
	{
		// if we just add click event handler to cancel button node, that handler will be called after slider close
		// to capture click before that, we need to add handler directly to markup
		instance?.#registerCancelEvent(Dictionary.ELEMENT_CANCEL_BUTTON);
	}
}

namespace.TypeDetail = TypeDetail;

class RelationsController
{
	switcher: BX.UI.Switcher;
	container: HTMLDivElement;
	typeSelectorContainer: HTMLDivElement;
	tabsContainer: HTMLDivElement;
	tabsCheckbox: HTMLInputElement;
	tabsSelectorContainer: HTMLDivElement;
	relations: Relation[];
	typeSelector: TagSelector;
	tabsSelector: TagSelector;

	constructor(options)
	{
		this.switcher = options.switcher;
		this.container = options.container;
		this.typeSelectorContainer = options.typeSelectorContainer;
		this.tabsContainer = options.tabsContainer;
		this.tabsCheckbox = options.tabsCheckbox;
		this.tabsSelectorContainer = options.tabsSelectorContainer;
		this.relations = options.relations;

		this.initSelectors();

		this.adjustInitialState();

		this.bindEvents();

		this.adjust();
	}

	initSelectors()
	{
		const unselectedTypes = [];
		const selectedTypes = [];
		const unselectedTabs = [];
		const selectedTabs = [];
		this.relations.forEach((relation: Relation) => {
			const item = {
				id: relation.entityTypeId,
				entityId: 'crmType',
				title: relation.title,
				tabs: 'recents',
			};
			if (relation.isChecked)
			{
				selectedTypes.push(item);

				if (relation.isChildrenListEnabled)
				{
					selectedTabs.push(item);
				}
				else
				{
					unselectedTabs.push(item);
				}
			}
			else
			{
				unselectedTypes.push(item);
			}
		});

		this.typeSelector = new TagSelector({
			dialogOptions: {
				enableSearch: false,
				multiple: false,
				items: unselectedTypes,
				selectedItems: selectedTypes,
				dropdownMode: true,
				height: 200,
				showAvatars: false,
			},
			events: {
				onAfterTagAdd: this.adjust.bind(this),
				onAfterTagRemove: this.adjust.bind(this),
			},
		});
		this.typeSelector.renderTo(this.typeSelectorContainer);

		this.tabsSelector = new TagSelector({
			dialogOptions: {
				enableSearch: false,
				multiple: false,
				items: unselectedTabs,
				selectedItems: selectedTabs,
				dropdownMode: true,
				height: 200,
				showAvatars: false,
			},
		});
		this.tabsSelector.renderTo(this.tabsSelectorContainer);
	}

	adjustInitialState()
	{
		const selectedTypes = this.typeSelector.getDialog().getSelectedItems();
		if (selectedTypes.length > 0)
		{
			this.switcher.check(true);
		}
		this.tabsCheckbox.checked = this.tabsSelector.getDialog().getSelectedItems().length > 0;
	}

	bindEvents()
	{
		EventEmitter.subscribe(this.switcher, 'toggled', this.adjust.bind(this));
		Event.bind(this.tabsCheckbox, 'click', this.adjust.bind(this));
	}

	adjust()
	{
		if (this.switcher.isChecked())
		{
			Dom.removeClass(this.container, 'crm-type-hidden');
		}
		else
		{
			Dom.addClass(this.container, 'crm-type-hidden');
		}
		const selectedTypes = this.typeSelector.getDialog().getSelectedItems();
		if (selectedTypes.length > 0)
		{
			Dom.removeClass(this.tabsContainer, 'crm-type-hidden');
		}
		else
		{
			Dom.addClass(this.tabsContainer, 'crm-type-hidden');
		}

		if (this.tabsCheckbox.checked)
		{
			Dom.removeClass(this.tabsSelectorContainer, 'crm-type-hidden');
		}
		else
		{
			Dom.addClass(this.tabsSelectorContainer, 'crm-type-hidden');
		}

		this.tabsSelector.getDialog().getItems().forEach((item) => {
			if (!this.isItemSelected(item, selectedTypes))
			{
				item.deselect();
				this.tabsSelector.getDialog().removeItem(item);
				this.tabsSelector.removeTag({
					id: item.getId(),
					entityId: item.getEntityId(),
				});
			}
		});
		selectedTypes.forEach((item) => {
			const itemData = {
				id: item.getId(),
				entityId: item.getEntityId(),
				title: item.getTitle(),
				tabs: 'recents',
			};
			const tabItem = this.tabsSelector.getDialog().getItem(itemData);
			if (!tabItem)
			{
				const newItem = this.tabsSelector.getDialog().addItem(itemData);
				newItem.select();
			}
		});
	}

	isItemSelected(item, selectedItems: Array): boolean
	{
		return selectedItems.some((selectedItem) => {
			return item.id === selectedItem.id;
		});
	}

	getData(): Array
	{
		const data = [];
		if (!this.switcher.isChecked())
		{
			return [];
		}
		const isTabsCheckboxChecked = this.tabsCheckbox.checked;
		const selectedTypes = this.typeSelector.getDialog().getSelectedItems();
		selectedTypes.forEach((selectedType) => {
			const type = {
				entityTypeId: selectedType.getId(),
				isChildrenListEnabled: false,
			};
			if (
				isTabsCheckboxChecked
				&& this.isItemSelected(selectedType, this.tabsSelector.getDialog().getSelectedItems())
			)
			{
				type.isChildrenListEnabled = true;
			}
			data.push(type);
		});

		return data;
	}
}

class CustomSectionsController
{
	switcher: BX.UI.Switcher;
	container: HTMLDivElement;
	selectorContainer: HTMLDivElement;
	customSections: CustomSection[];
	originallySelectedCustomSection: ?CustomSection;
	selector: TagSelector;
	settingsContainer: HTMLDivElement;
	sectionsListContainer: HTMLDivElement;
	saveButton: Element;
	cancelButton: Element;
	addSectionItemButton: Element;
	isNew: boolean;
	isCreateSectionsViaAutomatedSolutionDetails: false;
	canEditAutomatedSolution: false;
	permissionsResetAlert: ?Alert = null;
	isPermissionsResetAlertShown: boolean = false;
	permissionsUrl: ?string = null;

	constructor(options: {
		switcher: {},
		container: Element,
		selectorContainer: Element,
		customSections?: CustomSection[],
		isCreateSectionsViaAutomatedSolutionDetails: boolean,
		canEditAutomatedSolution: boolean,
		isNew: boolean,
		permissionsUrl: ?string,
	})
	{
		this.switcher = options.switcher;
		this.container = options.container;
		this.selectorContainer = options.selectorContainer;
		if (Type.isArray(options.customSections))
		{
			this.customSections = options.customSections;
		}
		else
		{
			this.customSections = [];
		}
		this.originallySelectedCustomSection = this.customSections.find((section: CustomSection) => section.isSelected)
			?? null
		;

		this.isNew = Type.isBoolean(options.isNew) ? options.isNew : false;
		if (Type.isBoolean(options.isCreateSectionsViaAutomatedSolutionDetails))
		{
			this.isCreateSectionsViaAutomatedSolutionDetails = options.isCreateSectionsViaAutomatedSolutionDetails;
		}

		if (Type.isBoolean(options.canEditAutomatedSolution))
		{
			this.canEditAutomatedSolution = options.canEditAutomatedSolution;
		}

		if (Type.isStringFilled(options.permissionsUrl))
		{
			this.permissionsUrl = options.permissionsUrl;
		}

		this.initSelector();

		this.settingsContainer = Tag.render`
			<div class="crm-type-hidden crm-type-custom-sections-settings-container">
				<div class="crm-type-relation-subtitle">${Loc.getMessage('CRM_TYPE_DETAIL_CUSTOM_SECTION_LIST_MSGVER_1')}</div>
			</div>
		`;
		this.container.append(this.settingsContainer);

		this.adjustInitialState();

		this.bindEvents();

		this.adjust();
	}

	destroy()
	{
		this.unbindEvents();
		this.selector?.unsubscribeAll();
	}

	initSelector()
	{
		const items = [];
		const selectedItems = [];

		this.customSections.forEach((section) => {
			const item = {
				id: section.id,
				entityId: 'custom-section',
				title: section.title,
				tabs: 'recents',
			};

			items.push(item);

			if (section.isSelected)
			{
				selectedItems.push(item);
			}
		});

		const adjustResetPermissionsAlertDebounce = Runtime.debounce(this.adjustResetPermissionsAlert.bind(this), 200);
		const tagSelectorOptions = {
			multiple: false,
			dialogOptions: {
				items,
				selectedItems,
				dropdownMode: true,
				height: 200,
				showAvatars: false,
			},
			events: {
				onAfterTagRemove: adjustResetPermissionsAlertDebounce,
				onAfterTagAdd: adjustResetPermissionsAlertDebounce,
			},
		};

		if (this.isCreateSectionsViaAutomatedSolutionDetails)
		{
			tagSelectorOptions.showCreateButton = false;
			tagSelectorOptions.dialogOptions.footer = Tag.render`
				<a
					onclick="${this.onOpenAutomatedSolutionCreationSliderClick.bind(this)}"
					class="ui-selector-footer-link ui-selector-footer-link-add"
				>${Loc.getMessage('CRM_COMMON_ACTION_CREATE')}</a>
			`;
		}
		else
		{
			tagSelectorOptions.showCreateButton = true;
			tagSelectorOptions.createButtonCaption = Loc.getMessage('CRM_COMMON_ACTION_CONFIG');
			tagSelectorOptions.events = {
				onCreateButtonClick: this.onCreateButtonClick.bind(this),
			};
		}

		if (!this.canEditAutomatedSolution)
		{
			tagSelectorOptions.locked = true;
		}

		this.selector = new TagSelector(tagSelectorOptions);
		this.selector.renderTo(this.selectorContainer);
	}

	adjustResetPermissionsAlert(): void
	{
		if (
			!FeatureManager.getInstance().isPermissionsLayoutV2Enabled()
			|| this.permissionsUrl === null
			|| this.isNew
		)
		{
			return;
		}

		const selectedItems = this.selector.getDialog().getSelectedItems();
		const item = selectedItems[0] ?? null;
		const alert = this.getPermissionsResetAlert();

		const isItemChanged = item?.getId() !== this.originallySelectedCustomSection?.id && this.switcher.isChecked();
		const isDetachCustomSection = this.originallySelectedCustomSection && !this.switcher.isChecked();

		const isShow = isItemChanged || isDetachCustomSection;
		if (isShow)
		{
			if (!this.isPermissionsResetAlertShown)
			{
				this.isPermissionsResetAlertShown = true;
				alert.renderTo(this.container.parentNode);
				alert.show();
			}

			return;
		}

		if (this.isPermissionsResetAlertShown)
		{
			this.isPermissionsResetAlertShown = false;
			alert.hide();
		}
	}

	getPermissionsResetAlert(): Alert
	{
		if (this.permissionsResetAlert === null)
		{
			const text = Loc.getMessage('CRM_TYPE_DETAIL_PERMISSIONS_WILL_BE_RESET_ALERT')
				.replace('#LINK#', this.permissionsUrl)
			;

			this.permissionsResetAlert = new Alert({
				size: AlertSize.MD,
				color: AlertColor.WARNING,
				customClass: 'crm-type-permissions-reset-alert',
				text,
			});
		}

		return this.permissionsResetAlert;
	}

	reInitSelector()
	{
		this.selector.getDialog().destroy();
		this.selector.unsubscribeAll();
		this.selector = null;
		Dom.clean(this.selectorContainer);
		this.initSelector();
	}

	showSelector()
	{
		Dom.removeClass(this.selectorContainer, 'crm-type-hidden');
	}

	hideSelector()
	{
		Dom.addClass(this.selectorContainer, 'crm-type-hidden');
	}

	adjustInitialState()
	{
		const selectedSection = this.selector.getDialog().getSelectedItems();
		if (selectedSection.length > 0)
		{
			this.switcher.check(true);
		}
	}

	bindEvents()
	{
		this.adjust = this.adjust.bind(this);
		this.onAutomatedSolutionUpdate = this.onAutomatedSolutionUpdate.bind(this);

		EventEmitter.subscribe(this.switcher, 'toggled', this.adjust);
		EventEmitter.subscribe(this.switcher, 'toggled', this.adjustResetPermissionsAlert.bind(this));
		if (this.isCreateSectionsViaAutomatedSolutionDetails)
		{
			ToolbarComponent.Instance.subscribeAutomatedSolutionUpdatedEvent(
				this.onAutomatedSolutionUpdate,
			);
		}
	}

	unbindEvents()
	{
		EventEmitter.unsubscribe(this.switcher, 'toggled', this.adjust);
		ToolbarComponent.Instance.unsubscribeAutomatedSolutionUpdatedEvent(this.onAutomatedSolutionUpdate);
	}

	onAutomatedSolutionUpdate(event: BaseEvent): void
	{
		const id = Text.toInteger(event.getData().intranetCustomSectionId);
		const title = String(event.getData().title);

		if (id <= 0 || !Type.isStringFilled(title))
		{
			return;
		}

		const currentCustomSection = this.customSections.find(
			(section) => Text.toInteger(section.id) === id,
		);

		if (currentCustomSection)
		{
			currentCustomSection.title = title;
		}
		else
		{
			this.customSections.push({
				id,
				title,
			});
		}

		this.reInitSelector();

		this.selectCustomSectionById(id);
	}

	selectCustomSectionById(id: number): void
	{
		const dialog = this.selector.getDialog();

		dialog.deselectAll();
		dialog.getItem({
			entityId: 'custom-section',
			id,
		})?.select();
	}

	onOpenAutomatedSolutionCreationSliderClick(): void
	{
		void Router.Instance.openAutomatedSolutionDetail();
	}

	onCreateButtonClick()
	{
		this.hideSelector();
		this.showSectionsList();
	}

	renderSectionsConfig(): HTMLDivElement
	{
		if (!this.sectionsListContainer)
		{
			this.sectionsListContainer = Tag.render`<div class="crm-type-custom-sections-list-container"></div>`;
			this.settingsContainer.append(this.sectionsListContainer);
		}

		this.renderSectionsList(this.sectionsListContainer);

		if (!this.addSectionItemButton)
		{
			this.addSectionItemButton = Tag.render`
				<div class="crm-type-custom-section-add-item-container">
					<span
						class="crm-type-custom-section-add-item-button"
						onclick="${() => this.sectionsListContainer.append(this.renderSectionItem())}"
					>
						${Loc.getMessage('CRM_COMMON_ACTION_CREATE')}
					</span>
				</div>
			`;
			this.settingsContainer.append(this.addSectionItemButton);
		}

		if (!this.buttonsContainer)
		{
			this.settingsContainer.append(Tag.render`<hr class="crm-type-custom-sections-line">`);
			this.buttonsContainer = Tag.render`<div class="crm-type-custom-sections-buttons-container"></div>`;
			this.settingsContainer.append(this.buttonsContainer);
		}

		if (!this.saveButton)
		{
			this.saveButton = Tag.render`<span class="ui-btn ui-btn-primary" onclick="${this.onSaveConfigHandler.bind(this)}">${Loc.getMessage('CRM_COMMON_ACTION_SAVE')}</span>`;
			this.buttonsContainer.append(this.saveButton);
		}

		if (!this.cancelButton)
		{
			this.cancelButton = Tag.render`<span class="ui-btn ui-btn-light-border" onclick="${this.onCancelConfigHandler.bind(this)}">${Loc.getMessage('CRM_COMMON_ACTION_CANCEL')}</span>`;
			this.buttonsContainer.append(this.cancelButton);
		}
	}

	onSaveConfigHandler(event: MouseEvent)
	{
		event.preventDefault();

		const selectedSection = this.getSelectedSection();
		const newCustomSections = [];
		[...this.sectionsListContainer.children].forEach((node) => {
			const idInput = node.querySelector('[name="id"]');
			const valueInput = node.querySelector('[name="value"]');
			if (!idInput || !valueInput)
			{
				return;
			}
			const id = idInput.value;
			const title = valueInput.value;
			let isSelected = false;
			if (selectedSection && selectedSection.id === id)
			{
				isSelected = true;
			}

			if (title)
			{
				newCustomSections.push({
					id,
					title,
					isSelected,
				});
			}
		});

		this.customSections = newCustomSections;

		this.reInitSelector();
		this.showSelector();

		this.hideSectionsList();
	}

	onCancelConfigHandler(event: MouseEvent)
	{
		event.preventDefault();
		this.showSelector();
		this.hideSectionsList();
	}

	renderSectionsList(listContainer: HTMLDivElement)
	{
		Dom.clean(listContainer);
		this.customSections.forEach((section) => {
			listContainer.append(this.renderSectionItem(section));
		});

		listContainer.append(this.renderSectionItem());
	}

	renderSectionItem(section: ?CustomSection): HTMLDivElement
	{
		const item = new CustomSectionItem(section);
		const node = Tag.render`
			<div style="margin-bottom: 10px;" class="ui-ctl ui-ctl-textbox ui-ctl-w100 ui-ctl-row">
				<input type="hidden" name="id" value="${item.getId()}" />
				<input class="ui-ctl-element" name="value" type="text" value="${Text.encode(item.getValue())}">
				<div
					class="crm-type-custom-section-remove-item"
					onclick="${(event) => {
						event.preventDefault();
						this.sectionsListContainer.removeChild(item.getNode());
					}}">
				</div>
			</div>
		`;

		item.setNode(node);

		return node;
	}

	showSectionsList()
	{
		this.renderSectionsConfig();
		Dom.removeClass(this.settingsContainer, 'crm-type-hidden');
	}

	hideSectionsList()
	{
		Dom.clean(this.sectionsListContainer);
		Dom.addClass(this.settingsContainer, 'crm-type-hidden');
	}

	adjust()
	{
		if (this.switcher.isChecked())
		{
			Dom.removeClass(this.container, 'crm-type-hidden');
		}
		else
		{
			Dom.addClass(this.container, 'crm-type-hidden');
		}
	}

	getSelectedSection(): ?CustomSection
	{
		const selectedItems = this.selector.getDialog().getSelectedItems();
		if (selectedItems.length > 0)
		{
			return {
				id: selectedItems[0].getId(),
				title: selectedItems[0].getTitle(),
			};
		}

		return null;
	}

	getData()
	{
		const data = {
			customSectionId: 0,
			customSections: this.customSections,
		};

		if (this.switcher.isChecked())
		{
			const selectedSection = this.getSelectedSection();
			if (selectedSection)
			{
				data.customSectionId = selectedSection.id;
			}
		}

		return data;
	}
}

class CustomSectionItem
{
	constructor(customSection: CustomSection = null)
	{
		this.id = customSection ? customSection.id : `new_${Text.getRandom()}`;
		this.value = customSection ? customSection.title : '';
	}

	setNode(node: Element)
	{
		this.node = node;
	}

	getId(): ?number
	{
		return this.id;
	}

	getNode(): ?Element
	{
		return this.node;
	}

	getInput(): ?Element
	{
		const node = this.getNode();
		if (!node)
		{
			return null;
		}

		if (node instanceof HTMLInputElement)
		{
			return node;
		}

		return node.querySelector('input');
	}

	getValue(): string
	{
		const input = this.getInput();
		if (input && input.value)
		{
			return input.value;
		}

		return this.value || '';
	}
}

class FeatureManager
{
	static #instance: ?FeatureManager = null;

	#isPermissionsLayoutV2Enabled: boolean = false;

	static getInstance(): FeatureManager
	{
		if (this.#instance === null)
		{
			this.#instance = new FeatureManager();
		}

		return this.#instance;
	}

	setPermissionsLayoutV2Enabled(isEnabled: boolean): FeatureManager
	{
		this.#isPermissionsLayoutV2Enabled = isEnabled;

		return this;
	}

	isPermissionsLayoutV2Enabled(): boolean
	{
		return this.#isPermissionsLayoutV2Enabled;
	}
}

namespace.FeatureManager = FeatureManager;
