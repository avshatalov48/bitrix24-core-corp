import {Loc, Tag, Dom, Type, Event} from "main.core";
import {RequisiteAutocompleteField} from "crm.entity-editor.field.requisite.autocomplete";
import {EventEmitter} from "main.core.events";
import {EntityEditorRequisiteTooltip} from './requisite-tooltip';
import {PresetMenu} from './preset-menu';

export class EntityEditorRequisiteField extends BX.Crm.EntityEditorField
{
	constructor()
	{
		super();
		this._domNodes = {};
		this._requisiteList = null;

		this.presetMenu = null;
		this._autocomplete = null;
		this._tooltip = null;
		this.isSearchMode = true;
		this.requisitesDropdown = null;
		this.bankDetailsDropdown = null;
		this.selectModeEnabled = false;

		this._changeRequisistesHandler = this.onChangeRequisites.bind(this);
	}

	static create(id, settings)
	{
		let self = new this(id, settings);
		self.initialize(id, settings);
		return self;
	}

	doInitialize()
	{
		this._autocomplete = RequisiteAutocompleteField.create(this.getName(), {
			searchAction: 'crm.requisite.entity.search',
			canAddRequisite: true,
			feedbackFormParams: BX.prop.getObject(this._schemeElement.getData(), "feedback_form", {}),
			enabled: true,
			showFeedbackLink: false
		});
		this._autocomplete.subscribe('onSelectValue', this.onSelectAutocompleteValue.bind(this));
		this._autocomplete.subscribe('onCreateNewItem', this.onAddRequisiteFromAutocomplete.bind(this));
		this._autocomplete.subscribe('onClear', this.onClearAutocompleteValue.bind(this));
		this._autocomplete.subscribe('onInstallDefaultApp', this.onInstallDefaultApp.bind(this));
		EventEmitter.subscribe(
			"BX.Crm.RequisiteAutocomplete:onAfterInstallDefaultApp",
			this.onInstallDefaultAppGlobal.bind(this)
		);

		this.presetMenu = new PresetMenu(this.getName() + '_requisite_preset_menu', this.getPresetList());
		this.presetMenu.subscribe('onSelect', this.onAddRequisiteFromMenu.bind(this));

		const isReadonly = this.getEditor().isReadOnly();
		this._tooltip = EntityEditorRequisiteTooltip.create(this.getName() + '_requisite_details', {
			readonly: isReadonly,
			canChangeDefaultRequisite: !isReadonly,
			presets: this.getPresetList()
		});

		EventEmitter.subscribe(this._tooltip, 'onAddRequisite', this.onAddRequisiteFromTooltip.bind(this));
		EventEmitter.subscribe(this._tooltip, 'onEditRequisite', this.onEditRequisite.bind(this));
		EventEmitter.subscribe(this._tooltip, 'onDeleteRequisite', this.onDeleteRequisite.bind(this));
		EventEmitter.subscribe(this._tooltip, 'onAddBankDetails', this.onAddBankDetails.bind(this));
		EventEmitter.subscribe(this._tooltip, 'onSetSelectedRequisite', this.onSetSelectedRequisite.bind(this));

		this.updateAutocompletePlaceholder();
		this.updateAutocompeteClientResolverPlacementParams();

		EventEmitter.emit(this.getEditor(), 'onFieldInit', {field: this});
	}

	setSelectModeEnabled(selectModeEnabled: boolean): void
	{
		if (this.selectModeEnabled !== selectModeEnabled)
		{
			this.selectModeEnabled = selectModeEnabled;
			if (this.isSelectModeEnabled())
			{
				this.isSearchMode = false;
			}
			this.refreshLayoutParts();
		}
	}

	isSelectModeEnabled(): boolean
	{
		return this.selectModeEnabled && this.hasRequisites() && this.getRequisites().getList().length > 0;
	}

	setRequisites(requisiteList)
	{
		const hasRequisites = this.hasRequisites();
		const vasEmpty = hasRequisites && this.getRequisites().isEmpty();
		this._requisiteList = requisiteList;
		requisiteList.unsubscribe(requisiteList.CHANGE_EVENT, this._changeRequisistesHandler);
		requisiteList.subscribe(requisiteList.CHANGE_EVENT, this._changeRequisistesHandler);
		this._tooltip.setRequisites(requisiteList);
		if (hasRequisites && !vasEmpty && !this.getRequisites().isEmpty())
		{
			this.refreshLayoutParts();
		}
		else
		{
			this.refreshLayout();
		}
	}

	getRequisites()
	{
		return this._requisiteList;
	}

	hasRequisites()
	{
		return Type.isObject(this._requisiteList);
	}

	isSingleMode()
	{
		if (!this.hasRequisites())
		{
			return true;
		}
		return (this.getRequisites().getList().length <= 1);
	}

	getTitle()
	{
		let title = super.getTitle();
		if (this.hasRequisites() && !this.isSingleMode())
		{
			let selectedRequisite = this.hasRequisites() ? this.getRequisites().getSelected() : null;
			let selectedPresetId = selectedRequisite ? selectedRequisite.getPresetId() : null;
			if (selectedRequisite && selectedPresetId)
			{
				let selectedPresetName = this.getPresetList().reduce(
					(name, item) => ((item.value === selectedPresetId) ? item.name : name), '');

				if (selectedPresetName.length)
				{
					title += ' (' + selectedPresetName + ')';
				}
			}
		}
		return title;
	}

	createTitleActionControls()
	{
		const actions = [];

		if (this._mode !== BX.UI.EntityEditorMode.edit)
		{
			return actions;
		}

		if (this.isAutocompleteEnabled() && this.selectModeEnabled)
		{
			if (this.isSearchMode)
			{
				actions.push(Tag.render`
					<span class="ui-link ui-link-secondary ui-entity-editor-block-title-link"
						onclick="${this.toggleSearchMode.bind(this)}">${Loc.getMessage('REQUISITE_LABEL_DETAILS_SELECT')}</span>`
				);
			}
			else
			{
				const title = this.getClientResolverTitle();
				actions.push(Tag.render`
					<span class="ui-link ui-link-secondary ui-entity-editor-block-title-link"
						onclick="${this.toggleSearchMode.bind(this)}">${title}</span>`
				);
			}
		}

		if (this.hasRequisites())
		{
			actions.push(Tag.render`
				<span class="ui-link ui-link-secondary ui-entity-editor-block-title-link"
				 	onclick="${this.editDefaultRequisite.bind(this)}">${Loc.getMessage('REQUISITE_LABEL_DETAILS_TEXT')}</span>`
			);
		}

		return actions;
	}

	toggleSearchMode()
	{
		this.isSearchMode = !this.isSearchMode;

		this.refreshLayoutParts();
	}

	isNeedToDisplay(options)
	{
		return this.hasRequisites() && super.isNeedToDisplay(options);
	}

	layout(options)
	{
		if (this._hasLayout)
		{
			return;
		}

		this._domNodes = {};
		this.ensureWrapperCreated({classNames: ["crm-entity-widget-content-block-field-requisites"]});
		this.adjustWrapper();
		this.bindWrapperEvents();

		if (!this.isNeedToDisplay())
		{
			this.registerLayout(options);
			this._hasLayout = true;
			return;
		}

		if (this.isDragEnabled())
		{
			Dom.append(this.createDragButton(), this._wrapper);
		}

		Dom.append(this.createTitleNode(this.getTitle()), this._wrapper);

		if (this._mode === BX.UI.EntityEditorMode.edit)
		{
			this._domNodes.addButton = this.renderAddButton();
			this._domNodes.autocompleteForm = this.renderAutocompleteForm();
			this._domNodes.requisiteSelectForm = this.renderRequisiteSelectForm();
			this._domNodes.bankDetailSelectForm = this.renderBankDetailSelectForm();
			Dom.append(this._domNodes.autocompleteForm, this._wrapper);
			Dom.append(this._domNodes.requisiteSelectForm, this._wrapper);
			Dom.append(this._domNodes.bankDetailSelectForm, this._wrapper);
			Dom.append(this._domNodes.addButton, this._wrapper);

			this.adjustNodesVisibility();
			this.updateRequisitesDropdown();
			this.updateBankDetailsDropdown();
			this.updateRequisiteSelectorValue();
			this.updateBankDetailsSelectorValue();
		}
		else // if(this._mode === BX.UI.EntityEditorMode.view)
		{
			Dom.append(this.renderSelectedRequisite(), this._wrapper);
		}

		if (this.isContextMenuEnabled())
		{
			this._wrapper.appendChild(this.createContextMenuButton());
		}

		if (this.isDragEnabled())
		{
			this.initializeDragDropAbilities();
		}

		this.registerLayout(options);
		this._hasLayout = true;
	}

	bindWrapperEvents()
	{
		if (!this.wrapperMouseEnterHandler)
		{
			this.wrapperMouseEnterHandler = this.onFieldMouseEnter.bind(this);
		}
		if (!this.wrapperMouseLeaveHandler)
		{
			this.wrapperMouseLeaveHandler = this.onFieldMouseLeave.bind(this);
		}

		Event.unbind(this._wrapper, 'mouseenter', this.wrapperMouseEnterHandler);
		Event.unbind(this._wrapper, 'mouseleave', this.wrapperMouseLeaveHandler);

		Event.bind(this._wrapper, 'mouseenter', this.wrapperMouseEnterHandler);
		Event.bind(this._wrapper, 'mouseleave', this.wrapperMouseLeaveHandler);
	}

	refreshLayoutParts()
	{
		this.updateSelectedRequisiteText();
		this.refreshTitleLayout();
		this.updateAutocompleteState();
		this.adjustNodesVisibility();
		this.updateRequisitesDropdown();
		this.updateBankDetailsDropdown();
		this.updateRequisiteSelectorValue();
		this.updateBankDetailsSelectorValue();
	}

	hasContentToDisplay()
	{
		return this.hasValue();
	}

	hasValue()
	{
		if (!this.hasRequisites())
		{
			return  false;
		}
		let list = this.getRequisites().getList();
		if (list.length > 1)
		{
			return true;
		}
		// if list contains only one item, it shouldn't be hidden:
		return (list.length === 1 && !list[0].isAddressOnly());
	}

	isAutocompleteEnabled()
	{
		if (
			!this.hasRequisites()
			|| this.getRequisites().isEmpty()
			|| this.getRequisites().getSelected().isAddressOnly()
		)
		{
			return !!this.getClientResolverPropForPreset(this.getSelectedPresetId());
		}

		return true;
	}

	renderSelectedRequisite()
	{
		this._domNodes.selectedRequisiteView = Tag.render`<span></span>`;
		this.updateSelectedRequisiteText();
		this.updateAutocompleteState();
		let container = Tag.render`
			<div class="ui-entity-editor-content-block" 
				onclick="${this.onViewStringClick.bind(this)}"
				onmouseenter="${this.onViewStringMouseEnter.bind(this)}">
					${this._domNodes.selectedRequisiteView}
			</div>`;
		this._tooltip.setBindElement(container, this.getEditor().getFormElement());

		return container;
	}

	updateSelectedRequisiteText()
	{
		if (!this._domNodes.selectedRequisiteView)
		{
			return;
		}
		let selectedRequisite = this.hasRequisites() && this.getRequisites().getSelected();
		if (this.hasValue() && selectedRequisite && selectedRequisite.getTitle().length)
		{
			this._domNodes.selectedRequisiteView.classList.add('ui-link', 'ui-link-dark', 'ui-link-dotted');
			this._domNodes.selectedRequisiteView.textContent = selectedRequisite.getTitle();
		}
		else
		{
			this._domNodes.selectedRequisiteView.classList.remove('ui-link', 'ui-link-dark', 'ui-link-dotted');
			this._domNodes.selectedRequisiteView.textContent = BX.UI.EntityEditorField.messages.isEmpty;
		}
	}

	renderAddButton()
	{
		return Tag.render`
			<div class="ui-entity-editor-content-block crm-entity-widget-content-block-requisites">
				<span class="crm-entity-widget-client-requisites-add-btn" onclick="${this.toggleNewRequisitePresetMenu.bind(this)}">${Loc.getMessage('CRM_EDITOR_ADD')}</span>
			</div>`;
	}

	renderAutocompleteForm()
	{
		const autocompleteContainer = Tag.render`
			<div class="crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-requisites"></div>`;
		const hasResolvers = !!this.getClientResolverPropForPreset(this.getSelectedPresetId());
		this._autocomplete.setEnabled(hasResolvers);
		this._autocomplete.layout(autocompleteContainer);
		this.updateAutocompleteState();

		return Tag.render`
			<div class="ui-entity-editor-content-block">
				${autocompleteContainer}
				<div class="crm-entity-widget-content-block-add-field">
					<span class="crm-entity-widget-content-add-field" onclick="${this.toggleNewRequisitePresetMenu.bind(this)}">${Loc.getMessage('CRM_EDITOR_ADD')}</span>
				</div>
			</div>`;
	}

	renderRequisiteSelectForm()
	{
		let isOpen = false;
		const toggleDropdown = () => {
			if (this.requisitesDropdown)
			{
				if (!isOpen)
				{
					this.requisitesDropdown.showPopupWindow();
				}
				else
				{
					this.requisitesDropdown.destroyPopupWindow();
				}
				isOpen = !isOpen;
			}
		};

		const selectInput = Tag.render`<div class="ui-ctl-element" onclick="${toggleDropdown}"></div>`;

		const selectContainer = Tag.render`
			<div class="crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-requisites">
				<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon">
					<button class="ui-ctl-after ui-ctl-icon-angle" onclick="${toggleDropdown}"></button>
					${selectInput}
				</div>			
			</div>`;

		this.requisitesDropdown = new BX.UI.Dropdown(
			{
				targetElement: selectInput,
				items: [],
				isDisabled: true,
				events: {
					onSelect: this.onRequisiteSelect.bind(this)
				}
			}
		);

		return Tag.render`
			<div class="ui-entity-editor-content-block">
				${selectContainer}
				<div class="crm-entity-widget-content-block-add-field">
					<span class="crm-entity-widget-content-add-field" onclick="${this.toggleNewRequisitePresetMenu.bind(this)}">${Loc.getMessage('CRM_EDITOR_ADD_REQUISITE')}</span>
				</div>
			</div>`;
	}

	renderBankDetailSelectForm()
	{
		let isOpen = false;
		const toggleDropdown = () => {
			if (this.bankDetailsDropdown)
			{
				if (!isOpen)
				{
					this.bankDetailsDropdown.showPopupWindow();
				}
				else
				{
					this.bankDetailsDropdown.destroyPopupWindow();
				}
				isOpen = !isOpen;
			}
		};

		const selectInput = Tag.render`<div class="ui-ctl-element" onclick="${toggleDropdown}"></div>`;

		this._domNodes.bankDetailsSelectContainer = Tag.render`
			<div class="crm-entity-widget-content-block-field-container crm-entity-widget-content-block-field-requisites">
				<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon">
					<button class="ui-ctl-after ui-ctl-icon-angle" onclick="${toggleDropdown}"></button>
					${selectInput}
				</div>			
			</div>`;

		this.bankDetailsDropdown = new BX.UI.Dropdown(
			{
				targetElement: selectInput,
				items: [],
				isDisabled: true,
				events: {
					onSelect: this.onBankDetailSelect.bind(this)
				}
			}
		);

		return Tag.render`
			<div class="ui-entity-editor-content-block">
				${this._domNodes.bankDetailsSelectContainer}
				<div class="crm-entity-widget-content-block-add-field">
					<span class="crm-entity-widget-content-add-field" onclick="${this.onAddBankDetailsClick.bind(this)}">${Loc.getMessage('CRM_EDITOR_ADD_BANK_DETAILS')}</span>
				</div>
			</div>`;
	}

	updateAutocompleteState()
	{
		let autocompleteValue = null;
		const selectedRequisite = this.hasRequisites() ? this.getRequisites().getSelected() : null;
		if (selectedRequisite && !selectedRequisite.isAddressOnly())
		{
			autocompleteValue = selectedRequisite.getAutocompleteData();
		}
		this._autocomplete.setCurrentItem(autocompleteValue);
		this._autocomplete.setContext(this.getAutocompleteContext());
	}

	updateAutocompletePlaceholder()
	{
		const clientResolverPropTitle = this.getClientResolverTitle();
		this._autocomplete.setEnabled(!!clientResolverPropTitle);
		this._autocomplete.setPlaceholderText(clientResolverPropTitle);
	}

	updateAutocompeteClientResolverPlacementParams()
	{
		this._autocomplete.setClientResolverPlacementParams(this.getClientResolverPlacementParams());
	}

	getClientResolverPlacementParams()
	{
		const clientResolverProp = this.getClientResolverPropForPreset(this.getSelectedPresetId());

		return (
			clientResolverProp
				? {
					"isPlacement": BX.prop.getString(clientResolverProp, "IS_PLACEMENT", "N") === "Y",
					"numberOfPlacements": BX.prop.getArray(clientResolverProp, "PLACEMENTS", []).length,
					"countryId": BX.prop.getInteger(clientResolverProp, "COUNTRY_ID", 0),
					"defaultAppInfo": BX.prop.getObject(clientResolverProp, "DEFAULT_APP_INFO", {})
				}
				: {}
		);
	}

	getClientResolverTitle()
	{
		let title = "";

		const clientResolverProp = this.getClientResolverPropForPreset(this.getSelectedPresetId());
		title = BX.prop.getString(clientResolverProp, "TITLE", "");
		const isPlacement = (BX.prop.getString(clientResolverProp, 'IS_PLACEMENT', 'N') === 'Y');
		if (!isPlacement && Type.isStringFilled(title))
		{
			title = Loc.getMessage('REQUISITE_AUTOCOMPLETE_FILL_IN').toLowerCase().replace('#field_name#', title);
		}

		return title;
	}

	adjustNodesVisibility()
	{
		if (
			!this._domNodes.autocompleteForm
			|| !this._domNodes.requisiteSelectForm
			|| !this._domNodes.bankDetailSelectForm
			|| !this._domNodes.addButton
		)
		{
			return;
		}

		if (this.isSearchMode && this.isAutocompleteEnabled())
		{
			this._domNodes.autocompleteForm.style.display = '';
			this._domNodes.requisiteSelectForm.style.display = 'none';
			this._domNodes.addButton.style.display = 'none';
			this._domNodes.bankDetailSelectForm.style.display = 'none';
		}
		else if (
			this.isSelectModeEnabled()
			&& this.hasRequisites()
			&&
			(
				this.getRequisites().getList().length > 1
			 	|| (
					this.getRequisites().getList().length > 0)
					&& !this.getRequisites().getSelected().isAddressOnly()
				)
		)
		{
			this._domNodes.autocompleteForm.style.display = 'none';
			this._domNodes.requisiteSelectForm.style.display = '';
			this._domNodes.bankDetailSelectForm.style.display = '';
			this._domNodes.addButton.style.display = 'none';

			if (this._domNodes.bankDetailsSelectContainer)
			{
				const bankDetails = this.getRequisites().getSelected().getBankDetails();
				if (!bankDetails.length)
				{
					this._domNodes.bankDetailsSelectContainer.style.display = 'none';
				}
				else
				{
					this._domNodes.bankDetailsSelectContainer.style.display = '';
				}
			}
		}
		else
		{
			this._domNodes.autocompleteForm.style.display = 'none';
			this._domNodes.requisiteSelectForm.style.display = 'none';
			this._domNodes.addButton.style.display = '';
			this._domNodes.bankDetailSelectForm.style.display = 'none';
		}
	}

	getSelectedRequisiteTitle()
	{
		let title = '';
		if (!this.hasRequisites())
		{
			return title;
		}

		const selectedRequisite = this.getRequisites().getSelected();
		if (selectedRequisite)
		{
			title = selectedRequisite.getTitle();
		}

		return title;
	}

	updateRequisiteSelectorValue()
	{
		if (!this.requisitesDropdown || !this.requisitesDropdown.targetElement)
		{
			return;
		}

		this.requisitesDropdown.targetElement.innerText = this.getSelectedRequisiteTitle();
	}

	updateBankDetailsSelectorValue()
	{
		if (!this.bankDetailsDropdown || !this.bankDetailsDropdown.targetElement)
		{
			return;
		}

		this.bankDetailsDropdown.targetElement.innerText = this.getSelectedBankDetailTitle();
	}

	getSelectedBankDetailTitle()
	{
		let title = '';
		if (!this.hasRequisites())
		{
			return title;
		}

		const selectedRequisite = this.getRequisites().getSelected();
		if (selectedRequisite)
		{
			const bankDetail = selectedRequisite.getBankDetailById(selectedRequisite.getSelectedBankDetailId());
			if (bankDetail && bankDetail.title)
			{
				title = bankDetail.title;
			}
		}

		return title;
	}

	getDefaultPresetId()
	{
		for (let preset of BX.prop.getArray(this._schemeElement.getData(), "presets", []))
		{
			if (preset.IS_DEFAULT)
			{
				return preset.VALUE;
			}
		}
		return null;
	}

	getSelectedPresetId()
	{
		let selectedRequisite = this.hasRequisites() ? this.getRequisites().getSelected() : null;
		if (selectedRequisite)
		{
			return selectedRequisite.getPresetId();
		}
		return this.getDefaultPresetId();
	}

	getClientResolverPropForPreset(presetId)
	{
		for (let preset of BX.prop.getArray(this._schemeElement.getData(), "presets", []))
		{
			if (preset.VALUE === presetId)
			{
				return BX.prop.get(preset, 'CLIENT_RESOLVER_PROP', null);
			}
		}
		return null;
	}

	getAutocompleteContext()
	{
		return {
			presetId: this.getSelectedPresetId()
		};
	}

	toggleNewRequisitePresetMenu(e)
	{
		this.presetMenu.toggle(e.target);
	};

	getPresetList()
	{
		let presets = [];
		for (let item of BX.prop.getArray(this._schemeElement.getData(), "presets"))
		{
			let value = BX.prop.getString(item, "VALUE", 0);
			let name = BX.prop.getString(item, "NAME", value);
			presets.push(
				{
					name: name,
					value: value
				}
			);
		}
		return presets;
	}

	addRequisite(params)
	{
		EventEmitter.emit(this, 'onEditNew', params);
	}

	editRequisite(id, options)
	{
		EventEmitter.emit(this, 'onEditExisted', {id, options});
	}

	deleteRequisite(id)
	{
		this._tooltip.removeDebouncedEvents();
		this._tooltip.close();
		EventEmitter.emit(this, 'onDelete', {id, postponed: this._mode === BX.UI.EntityEditorMode.edit});
	}

	hideRequisite(id)
	{
		this.markAsChanged();
		this._autocomplete.setCurrentItem(null);
		EventEmitter.emit(this, 'onHide', {id});
	}

	showDeleteConfirmation(requisiteId)
	{
		BX.Crm.EditorAuxiliaryDialog.create(
			"delete_requisite_confirmation",
			{
				title: Loc.getMessage('REQUISITE_LIST_ITEM_DELETE_CONFIRMATION_TITLE'),
				content: Loc.getMessage('REQUISITE_LIST_ITEM_DELETE_CONFIRMATION_CONTENT'),
				buttons:
					[
						{
							id: "yes",
							type: BX.Crm.DialogButtonType.accept,
							text: Loc.getMessage("CRM_EDITOR_YES"),
							callback: (button) =>
							{
								button.getDialog().close();
								this.markAsChanged();
								this.deleteRequisite(requisiteId);
							}
						},
						{
							id: "no",
							type: BX.Crm.DialogButtonType.cancel,
							text: Loc.getMessage("CRM_EDITOR_NO"),
							callback: (button) =>
							{
								button.getDialog().close();
							}
						}
					]
			}
		).open();
	}

	showClearConfirmation(requisiteId)
	{
		BX.Crm.EditorAuxiliaryDialog.create(
			"hide_requisite_confirmation",
			{
				title: Loc.getMessage('REQUISITE_LIST_ITEM_HIDE_CONFIRMATION_TITLE'),
				content: Loc.getMessage('REQUISITE_LIST_ITEM_HIDE_CONFIRMATION_CONTENT'),
				buttons:
					[
						{
							id: "yes",
							type: BX.Crm.DialogButtonType.accept,
							text: Loc.getMessage("CRM_EDITOR_YES"),
							callback: (button) =>
							{
								button.getDialog().close();
								this.hideRequisite(requisiteId);
							}
						},
						{
							id: "no",
							type: BX.Crm.DialogButtonType.cancel,
							text: Loc.getMessage("CRM_EDITOR_NO"),
							callback: (button) =>
							{
								button.getDialog().close();
							}
						}
					]
			}
		).open();
	}

	editDefaultRequisite()
	{
		let selectedRequisiteId = this.hasRequisites() ? this.getRequisites().getSelectedId() : null;
		if (null !== selectedRequisiteId)
		{
			this.editRequisite(selectedRequisiteId, {
				autocompleteState: this._autocomplete.getState()
			});
		}
		else
		{
			this.addRequisite({
				presetId: this.getDefaultPresetId(),
				autocompleteState: this._autocomplete.getState()
			});
		}
	}

	onChangeRequisites()
	{
		if (
			(this._domNodes && Type.isDomNode(this._domNodes.addButton) && this._domNodes.addButton.style.display !== 'none') ||
			(this.hasRequisites() && this.getRequisites().isEmpty()) ||
			(this.hasRequisites() && this.getRequisites().getSelected().isAddressOnly())
		)
		{
			// this.refreshLayout();
			this.refreshLayoutParts();
		}
		else
		{
			this.refreshLayoutParts();
		}
		this.updateAutocompletePlaceholder();
		this.updateAutocompeteClientResolverPlacementParams();
	}

	updateRequisitesDropdown()
	{
		if (!this.requisitesDropdown)
		{
			return;
		}

		const items = [];

		if (this.hasRequisites())
		{
			this.getRequisites().getList().forEach((requisiteItem, index) => {
				items.push({
					id: requisiteItem.getRequisiteId(),
					title: requisiteItem.getTitle(),
					subTitle: requisiteItem.getSubtitle(),
					index
				});
			});
		}

		this.requisitesDropdown.setItems(items);
	}

	updateBankDetailsDropdown()
	{
		if (!this.bankDetailsDropdown)
		{
			return;
		}

		const items = [];

		if (this.hasRequisites() && this.getRequisites().getSelected())
		{
			const bankDetails = this.getRequisites().getSelected().getBankDetails();
			if (bankDetails.length)
			{
				bankDetails.forEach((bankDetail, index) => {
					items.push({
						id: bankDetail.id,
						title: bankDetail.title,
						subTitle: bankDetail.value,
						index
					});
				});
			}
		}

		this.bankDetailsDropdown.setItems(items);
	}

	onRequisiteSelect(sender, {index})
	{
		if (!this.hasRequisites())
		{
			return;
		}
		if (index !== undefined)
		{
			const selectedRequisiteId = Number(this.getRequisites().getSelectedId());
			if (selectedRequisiteId !== index)
			{
				this.getRequisites().setSelected(index);
				this.markAsChanged();
			}
		}
		if (this.requisitesDropdown)
		{
			this.requisitesDropdown.getPopupWindow().close();
		}
	}

	onBankDetailSelect(sender, {index})
	{
		if (!this.hasRequisites() || !this.bankDetailsDropdown)
		{
			return;
		}
		if (index !== undefined)
		{
			const selectedRequisiteId = Number(this.getRequisites().getSelectedId())
			const selectedBankDetailId = Number(this.getRequisites().getSelected().getSelectedBankDetailId());
			if (selectedBankDetailId !== index)
			{
				this.getRequisites().setSelected(selectedRequisiteId, index);
				this.markAsChanged();
			}
		}

		this.bankDetailsDropdown.getPopupWindow().close();
	}

	onAddRequisiteFromMenu(event)
	{
		let data = event.getData();
		const selectedRequisite = this.hasRequisites() ? this.getRequisites().getSelected() : null;
		// if hidden requisite is selected, it will be used instead of new:
		if (null !== selectedRequisite && selectedRequisite.isAddressOnly())
		{
			this.editRequisite(this.getRequisites().getSelectedId(), {
				editorOptions: {
					overriddenPresetId: data.value
				}
			});
		}
		else
		{
			this.addRequisite({
				presetId: data.value
			});
		}
	}

	onAddRequisiteFromTooltip(event)
	{
		this.addRequisite(event.getData());
	}

	onAddRequisiteFromAutocomplete()
	{
		this.editDefaultRequisite();
	}

	onEditRequisite(event)
	{
		let eventData = event.getData();
		this.editRequisite(eventData.id, {
			autocompleteState: this._autocomplete.getState()
		});
	}

	onDeleteRequisite(event)
	{
		let eventData = event.getData();
		this.showDeleteConfirmation(eventData.id);
	}

	onAddBankDetails(event)
	{
		let eventData = event.getData();
		this.editRequisite(eventData.requisiteId, {
			editorOptions: {
				addBankDetailsItem: true
			},
			autocompleteState: this._autocomplete.getState()
		});
	}

	onSelectAutocompleteValue(event)
	{
		let data = event.getData();
		this.markAsChanged();
		this._autocomplete.setLoading(true);
		if (this.selectModeEnabled)
		{
			this.isSearchMode = false;
		}
		const selectedRequisiteId = this.hasRequisites() ? this.getRequisites().getSelectedId() : null;

		EventEmitter.emit(this, 'onFinishAutocomplete', {
			id: selectedRequisiteId,
			defaultPresetId: this.getDefaultPresetId(),
			autocompleteState: this._autocomplete.getState(),
			data
		});
	}

	onClearAutocompleteValue()
	{
		let selectedRequisiteId = this.hasRequisites() ? this.getRequisites().getSelectedId() : null;

		if (null !== selectedRequisiteId)
		{
			let selectedRequisite = this.getRequisites().getSelected();
			let hasAddresses = false;
			let addresses = selectedRequisite.getAddressList();
			for (let addressType in addresses)
			{
				if (
					addresses.hasOwnProperty(addressType) &&
					Type.isStringFilled(addresses[addressType])
				)
				{
					hasAddresses = true;
					break;
				}
			}
			if (hasAddresses)
			{
				this.showClearConfirmation(selectedRequisiteId);
			}
			else
			{
				this.showDeleteConfirmation(selectedRequisiteId);
			}
		}
	}

	onInstallDefaultApp()
	{
		BX.onGlobalCustomEvent("BX.Crm.RequisiteAutocomplete:onAfterInstallDefaultApp");
	}

	onInstallDefaultAppGlobal()
	{
		BX.ajax.runAction(
			'crm.requisite.schemedata.getRequisitesSchemeData',
			{ data: { entityTypeId: this.getEditor().getEntityTypeId() } }
		).then(
			(data) => {
				if (
					Type.isPlainObject(data)
					&& data.hasOwnProperty("data")
					&& Type.isPlainObject(data["data"])
				)
				{
					this._schemeElement.setData(data["data"]);
					this.updateAutocompletePlaceholder();
					this.updateAutocompeteClientResolverPlacementParams();
				}
			}
		);
	}

	onViewStringClick()
	{
		if (!this.getEditor().isReadOnly())
		{
			this._tooltip.removeDebouncedEvents();
			this._tooltip.close();
			this.switchToSingleEditMode();
		}
	}

	onViewStringMouseEnter()
	{
		if (this._mode === BX.UI.EntityEditorMode.view && this.hasValue())
		{
			this._tooltip.showDebounced();
		}
	}

	onSetSelectedRequisite(event)
	{
		let eventData = event.getData();
		if (!this.getEditor().isReadOnly())
		{
			EventEmitter.emit(this, 'onSetDefault', eventData);
		}
		return false;
	}

	onFieldMouseEnter()
	{
		if (this._mode === BX.UI.EntityEditorMode.view && this.hasValue())
		{
			this._tooltip.showDebounced(5);
		}
	}

	onFieldMouseLeave()
	{
		this._tooltip.closeDebounced();
		this._tooltip.cancelShowDebounced();
	}

	onAddBankDetailsClick(event)
	{
		event.preventDefault();
		let selectedRequisiteId = this.hasRequisites() ? this.getRequisites().getSelectedId() : null;
		if (null !== selectedRequisiteId)
		{
			this.editRequisite(selectedRequisiteId, {
				autocompleteState: this._autocomplete.getState(),
				editorOptions: {
					addBankDetailsItem: true
				},
			});
		}
	}
}
