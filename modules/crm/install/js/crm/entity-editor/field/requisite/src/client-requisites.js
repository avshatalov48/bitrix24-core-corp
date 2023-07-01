import {Loc, Dom, Type, Tag} from "main.core";
import {Loader} from "main.loader";
import {EntityEditorBaseAddressField} from "crm.entity-editor.field.address.base";
import {RequisiteList, RequisiteListItem} from "./requisite-list";
import {EntityEditorRequisiteTooltip} from "./requisite-tooltip";
import {EventEmitter} from "main.core.events";
import {EntityEditorRequisiteEditor} from "./requisite-editor";

export class EntityEditorClientRequisites
{
	constructor()
	{
		this._id = "";
		this._entityInfo = null;
		this._fieldsParams = null;
		this._loaderConfig = null;
		this._addressConfig = null;
		this._requisiteList = null;
		this._requisiteEditor = null;
		this._permissionToken = null;

		this._readonly = true;
		this._requisiteEditUrl = null;
		this._addressContainer = null;
		this._formElement = null;
		this._showTooltipOnEntityLoad = false;
	}

	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this.setEntity(BX.prop.get(settings, "entityInfo", null), false);
		if (!this._entityInfo)
		{
			throw "EntityEditorClientRequisites: EntityInfo must be instance of BX.CrmEntityInfo";
		}
		this._fieldsParams = BX.prop.get(settings, "fieldsParams", null);
		if (!this._fieldsParams)
		{
			throw "EntityEditorClientRequisites: Fields params are undefined";
		}
		this._addressConfig = BX.prop.getObject(this._fieldsParams, 'ADDRESS', null);

		this._requisitesConfig = BX.prop.getObject(this._fieldsParams, 'REQUISITES', null);

		if (this._requisiteList)
		{
			let selectedItem = BX.prop.getObject(settings, "requisiteBinding", null);
			if (selectedItem
				&& !Type.isUndefined(selectedItem.REQUISITE_ID) && !Type.isUndefined(selectedItem.BANK_DETAIL_ID))
			{
				let requisite = this._requisiteList.getByRequisiteId(selectedItem.REQUISITE_ID);
				if (requisite)
				{
					let bankDetail = selectedItem.BANK_DETAIL_ID > 0 ?
						requisite.getBankDetailByBankDetailId(selectedItem.BANK_DETAIL_ID) : null;
					this._requisiteList.setSelected(
						this._requisiteList.indexOf(requisite),
						bankDetail ? requisite.getBankDetails().indexOf(bankDetail) : null
					);
				}
			}
		}
		this._loaderConfig = BX.prop.getObject(settings, 'loaderConfig', null);

		this._readonly = BX.prop.getBoolean(settings, "readonly", true);
		this._canChangeDefaultRequisite = BX.prop.getBoolean(settings, "canChangeDefaultRequisite", true);
		this._requisiteEditUrl = BX.prop.getString(settings, "requisiteEditUrl", null);
		this._formElement = BX.prop.get(settings, "formElement", null);
		this._permissionToken = BX.prop.getString(settings, "permissionToken", null);

		if (BX.prop.getBoolean(settings, "enableTooltip", true) && !Type.isNull(this._requisitesConfig))
		{
			this._tooltip = EntityEditorRequisiteTooltip.create(this._id + '_client_requisite_details', {
				readonly: this._readonly,
				canChangeDefaultRequisite: this._canChangeDefaultRequisite,
				presets: this.getRequisitesPresetList()
			});
		}

		this._requisiteEditor = EntityEditorRequisiteEditor.create(this._id + '_rq_editor', {
			entityTypeId: this._entityInfo.getTypeId(),
			entityId: this._entityInfo.getId(),
			contextId: BX.prop.getString(settings, "contextId", ""),
			requisiteEditUrl: this._requisiteEditUrl,
			permissionToken: this._permissionToken
		});
		this._requisiteEditor.setRequisiteList(this._requisiteList);
		EventEmitter.subscribe(this._requisiteEditor, 'onAfterEditRequisite', this.onRequisiteEditorAfterEdit.bind(this));
		EventEmitter.subscribe(this._requisiteEditor, 'onAfterDeleteRequisite', this.onRequisiteEditorAfterDelete.bind(this));

		if (this._tooltip)
		{
			EventEmitter.subscribe(this._tooltip, 'onAddRequisite', this.onEditNewRequisite.bind(this));
			EventEmitter.subscribe(this._tooltip, 'onEditRequisite', this.onEditExistedRequisite.bind(this));
			EventEmitter.subscribe(this._tooltip, 'onDeleteRequisite', this.onDeleteRequisite.bind(this));
			EventEmitter.subscribe(this._tooltip, 'onAddBankDetails', this.onAddBankDetails.bind(this));
			EventEmitter.subscribe(this._tooltip, 'onSetSelectedRequisite', this.onSetSelectedRequisite.bind(this));
			EventEmitter.subscribe(this._tooltip, 'onShow', this.onShowTooltip.bind(this));
		}
	}

	setEntity(entityInfo, emitNotification)
	{
		this._entityInfo = entityInfo;

		if (this._entityInfo.hasEditRequisiteData())
		{
			this._requisiteList = RequisiteList.create(this._entityInfo.getRequisites());
		}
		if (emitNotification)
		{
			this.onChangeRequisiteList();
		}
	}

	addressLayout(container)
	{
		if (!Type.isDomNode(container))
		{
			return;
		}
		this._addressContainer = Tag.render`<div class="crm-entity-widget-client-address"></div>`;
		Dom.append(this._addressContainer, container);
		this.doAddressLayout();
	}

	doAddressLayout()
	{
		if (!Type.isDomNode(this._addressContainer))
		{
			return;
		}

		Dom.clean(this._addressContainer);

		if (!Type.isNull(this._addressConfig))
		{
			if (this._entityInfo.hasEditRequisiteData())
			{
				let defaultRequisite = this._requisiteList.getSelected();
				let addressValue = defaultRequisite ? defaultRequisite.getAddressList() : null;

				if (!Type.isNull(addressValue) && Object.keys(addressValue).length)
				{
					let countryId = 0;
					if (defaultRequisite)
					{
						countryId = parseInt(defaultRequisite.getPresetCountryId());
					}
					this._addressField = EntityEditorBaseAddressField.create(this._entityInfo.getId(), {
						showFirstItemOnly: true,
						showAddressTypeInViewMode: true,
						addressZoneConfig: BX.prop.getObject(this._addressConfig, "addressZoneConfig", {}),
						countryId: countryId,
						defaultAddressTypeByCategory: BX.prop.getInteger(
							this._addressConfig,
							"defaultAddressTypeByCategory",
							0
						)
					});
					this._addressField.setMultiple(true);
					this._addressField.setTypesList(BX.prop.getObject(this._addressConfig, "types", {}));
					this._addressField.setValue(addressValue);
					Dom.append(this._addressField.layout(false), this._addressContainer);
				}
			}
			else
			{
				let showAddressLink = Tag.render`
				<span class="ui-link ui-link-secondary ui-link-dotted" onmouseup="${this.onLoadAddressMouseUp.bind(this)}">
					${Loc.getMessage('CLIENT_REQUISITES_ADDRESS_SHOW_ADDRESS')}
				</span>`;

				Dom.append(showAddressLink, this._addressContainer);
			}
		}
	}

	showTooltip(bindElement)
	{
		if (!this._tooltip)
		{
			return;
		}
		if (this._entityInfo.hasEditRequisiteData())
		{
			this._tooltip.setRequisites(this._requisiteList);
			if (this.isRequisiteAddressOnly())
			{
				return;
			}
		}
		this._tooltip.setBindElement(bindElement, this._formElement);
		this._tooltip.showDebounced();
	}

	closeTooltip()
	{
		if (!this._tooltip)
		{
			return;
		}
		this._tooltip.closeDebounced();
		this._tooltip.cancelShowDebounced();
		this._showTooltipOnEntityLoad = false;
	}

	release()
	{
		if (this._addressField)
		{
			this._addressField.release();
		}
		if (this._tooltip)
		{
			this._tooltip.close();
			this._tooltip.removeDebouncedEvents();
		}
		if (this._requisiteEditor)
		{
			this._requisiteEditor.release();
		}
	}

	loadEntity()
	{
		if (!this._loaderConfig)
		{
			return;
		}

		if (Type.isDomNode(this._addressContainer))
		{
			let loader = new Loader({size: 19, mode: 'inline'});
			Dom.clean(this._addressContainer);
			loader.show(this._addressContainer);
		}

		BX.CrmDataLoader.create(
			this._id,
			{
				serviceUrl: this._loaderConfig["url"],
				action: this._loaderConfig["action"],
				params: {
					"ENTITY_TYPE_NAME": this._entityInfo.getTypeName(),
					"ENTITY_ID": this._entityInfo.getId(),
					"NORMALIZE_MULTIFIELDS": "Y"
				}
			}
		).load(this.onEntityInfoLoad.bind(this));
	}

	getRequisitesPresetList()
	{
		if (Type.isNull(this._requisitesConfig))
		{
			return [];
		}
		let presets = [];
		for (let item of BX.prop.getArray(this._requisitesConfig, "presets"))
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

	deleteRequisite(id)
	{
		if (this._tooltip)
		{
			this._tooltip.removeDebouncedEvents();
			this._tooltip.close();
		}
		this._requisiteEditor.deleteRequisite(id);
	}

	isRequisiteAddressOnly()
	{
		let list = this._requisiteList.getList();
		return (list.length === 1 && list[0].isAddressOnly());
	}

	onLoadAddressMouseUp(event)
	{
		event.stopPropagation(); // cancel switching client to edit mode
		this.loadEntity();
	}

	onEntityInfoLoad(sender, result)
	{
		var entityData = BX.prop.getObject(result, "DATA", null);
		if (entityData)
		{
			this.setEntity(BX.CrmEntityInfo.create(entityData), true);
			if (this._tooltip && this._showTooltipOnEntityLoad)
			{
				if (!this.isRequisiteAddressOnly())
				{
					this._tooltip.show();
				}
				this._showTooltipOnEntityLoad = false;
			}
		}
	}

	onEditNewRequisite(event)
	{
		let params = event.getData();

		params.selected = this._requisiteList.isEmpty();
		let requisite = RequisiteListItem.create(null, {
			'newRequisiteId': this._requisiteList.getNewRequisiteId(),
			'newRequisiteExtraFields': params
		});
		requisite.setRequisiteId(this._requisiteList.getNewRequisiteId());
		this._requisiteEditor.open(requisite);
	}

	onEditExistedRequisite(event)
	{
		let params = event.getData();
		let requisite = this._requisiteList.getById(params.id);
		if (requisite)
		{
			this._requisiteEditor.open(requisite, {});
		}
	}

	onDeleteRequisite(event)
	{
		let eventData = event.getData();
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
								this.deleteRequisite(eventData.id);
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

	onAddBankDetails(event)
	{
		let eventData = event.getData();
		let requisite = this._requisiteList.getById(eventData.requisiteId);
		if (requisite)
		{
			this._requisiteEditor.open(requisite, {
				addBankDetailsItem: true
			});
		}
	}

	onSetSelectedRequisite(event)
	{
		let eventData = event.getData();
		this._requisiteList.setSelected(eventData.id, eventData.bankDetailId);

		this.doAddressLayout();

		let newSelectedRequisite = this._requisiteList.getSelected();
		if (newSelectedRequisite)
		{
			let selectedBankDetail = newSelectedRequisite.getBankDetailById(newSelectedRequisite.getSelectedBankDetailId());
			let selectedBankDetailId = Type.isNull(selectedBankDetail) ? 0 : selectedBankDetail.id;

			EventEmitter.emit(this, 'onSetSelectedRequisite', {
				requisiteId: newSelectedRequisite.getRequisiteId(),
				bankDetailId: selectedBankDetailId
			});
		}
	}

	onRequisiteEditorAfterEdit(event)
	{
		this.onChangeRequisiteList();
	}

	onRequisiteEditorAfterDelete(event)
	{
		this.onChangeRequisiteList();
	}

	onChangeRequisiteList()
	{
		this.doAddressLayout();
		if (this._tooltip)
		{
			this._tooltip.setRequisites(this._requisiteList);
			this._tooltip.setLoading(false);
		}

		this._requisiteEditor.setRequisiteList(this._requisiteList);

		let requisiteList = this._requisiteList ? this._requisiteList.exportToModel() : null;

		this._entityInfo.setRequisites(requisiteList);
		EventEmitter.emit(this, 'onChangeRequisiteList', {
			entityTypeName: this._entityInfo.getTypeName(),
			entityId: this._entityInfo.getId(),
			requisites: this._requisiteList.exportToModel()
		});
	}

	onShowTooltip()
	{
		if (!this._entityInfo.hasEditRequisiteData())
		{
			if (this._tooltip)
			{
				this._tooltip.close();
				this._showTooltipOnEntityLoad = true;
			}
			this.loadEntity();
		}
		else if (this.isRequisiteAddressOnly())
		{
			this._tooltip.close();
		}
	}

	static create(id, settings)
	{
		let self = new EntityEditorClientRequisites();
		self.initialize(id, settings);
		return self;
	}
}