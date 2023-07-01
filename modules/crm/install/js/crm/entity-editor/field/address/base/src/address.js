import {Text, Tag, Dom, Type, Loc} from "main.core";
import {EventEmitter} from "main.core.events";
import "./address.css"
import {MenuManager, PopupManager} from "main.popup";

export class EntityEditorBaseAddressField
{
	initialize(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
		this._isMultiple = false;
		this._settings = settings ? settings : {};
		this._typesList = [];
		this._availableTypesIds = [];
		this._allowedTypeIds = [];
		this._addressList = [];
		this._wrapper = null;
		this._isEditMode = true;
		this._showFirstItemOnly = BX.prop.getBoolean(settings, 'showFirstItemOnly', false);
		this._enableAutocomplete = BX.prop.getBoolean(settings, 'enableAutocomplete', true);
		this._hideDefaultAddressType = BX.prop.getBoolean(this._settings, 'hideDefaultAddressType', false);
		this._showAddressTypeInViewMode = BX.prop.getBoolean(this._settings, 'showAddressTypeInViewMode', false);
		this._addrZoneConfig = BX.prop.getObject(this._settings, 'addressZoneConfig', {});
		this._countryId = BX.prop.getInteger(
			this._settings,
			'countryId',
			BX.prop.getInteger(this._addrZoneConfig, "countryId", 0)
		);
		this._defaultAddressType = BX.prop.getInteger(this._settings, 'defaultAddressTypeByCategory', 0);
		if (this._defaultAddressType <= 0)
		{
			this._defaultAddressType = BX.prop.getInteger(this._addrZoneConfig, 'defaultAddressType', 0)
		}

		this.updateAllowedTypes();
	}

	setMultiple(isMultiple)
	{
		this._isMultiple = !!isMultiple;
	}

	setValue(value)
	{
		if (this._isMultiple)
		{
			let items = Type.isPlainObject(value) ? value : {};
			let types = Object.keys(items);

			let isSame = (this._addressList.length > 0 && types.length == this._addressList.length);
			if (isSame)
			{
				for (let addressItem of this._addressList)
				{
					let type = addressItem.getType();
					if (!items.hasOwnProperty(type) || items[type] !== addressItem.getValue())
					{
						isSame = false;
						break;
					}
				}
			}
			if ( // if new value is empty and old value has only one empty element
				!isSame &&
				!types.length &&
				this._addressList.length === 1 &&
				!this._addressList[0].getValue().length
			)
			{
				isSame = true;
			}

			if (isSame)
			{
				return false; // update is not required
			}

			this.removeAllAddresses();
			for (let type of types)
			{
				this.addAddress(type, items[type]);
			}
			if (!types.length)
			{
				this.addAddress(this.getDefaultType(), null);
			}
		}
		else
		{
			this.removeAllAddresses();
			let address = Type.isStringFilled(value) ? value : null;
			this.addAddress(null, address);
		}

		return true;
	}

	getValue()
	{
		if (this._isMultiple)
		{
			let result = [];
			for (let addressItem of this._addressList)
			{
				let value = addressItem.getValue();
				if (Type.isString(value))
				{
					result.push({type: addressItem.getType(), value});
				}
			}
			return result;
		}
		else
		{
			if (this._addressList && this._addressList[0] && Type.isString(this._addressList[0].getValue()))
			{
				return this._addressList[0].getValue();
			}
			return null;
		}
	}

	setTypesList(list)
	{
		this._typesList = [];
		if (Type.isPlainObject(list))
		{
			for (let id of Object.keys(list))
			{
				this._typesList.push(list[id]);
			}
		}
		this.initAvailableTypes();
	}

	getTypesList()
	{
		let types = [];
		for (let item of this._typesList)
		{
			let value = BX.prop.getString(item, "ID", "");
			let name = BX.prop.getString(item, "DESCRIPTION", "");

			types.push(
				{
					name: name,
					value: value
				}
			);
		}
		return types;
	}

	setAllowedTypes(typeIds)
	{
		this._allowedTypeIds = [];
		if (Type.isArray(typeIds))
		{
			this._allowedTypeIds = typeIds;
		}
	}

	getAllowedTypes()
	{
		return this._allowedTypeIds;
	}

	setCountryId(countryId)
	{
		let needUpdateAllowedTypes = false;

		countryId = parseInt(countryId);
		if (this._countryId !== countryId)
		{
			needUpdateAllowedTypes = true;
		}
		this._countryId = countryId;

		if (needUpdateAllowedTypes)
		{
			this.updateAllowedTypes();
		}
	}

	getCountryId()
	{
		return this._countryId;
	}

	getAddressZoneConfig()
	{
		return this._addrZoneConfig
	}

	getValueTypes()
	{
		let result = [];

		for (let addressItem of this._addressList)
		{
			let addressType = parseInt(addressItem.getType());
			if (result.indexOf(addressType) < 0)
			{
				result.push(addressType);
			}
		}

		return result;
	}

	updateAllowedTypes()
	{
		let allowedTypeList = [];
		let typeValues = this.getValueTypes();
		let countryId = this.getCountryId();
		let config = this.getAddressZoneConfig();

		if (Type.isPlainObject(config))
		{
			if (config.hasOwnProperty("currentZoneAddressTypes")
				&& Type.isArray(config["currentZoneAddressTypes"]))
			{
				let i;
				let typeId;
				let curZoneAddrTypes = config["currentZoneAddressTypes"];
				for (i = 0; i < curZoneAddrTypes.length; i++)
				{
					typeId = parseInt(curZoneAddrTypes[i]);
					if (allowedTypeList.indexOf(typeId) < 0)
					{
						allowedTypeList.push(typeId);
					}
				}
				if (countryId > 0
					&& config.hasOwnProperty("countryAddressTypeMap")
					&& Type.isPlainObject(config["countryAddressTypeMap"])
					&& config["countryAddressTypeMap"].hasOwnProperty(countryId)
					&& Type.isArray(config["countryAddressTypeMap"][countryId]))
				{
					let addrTypeMap = config["countryAddressTypeMap"][countryId];
					for (i = 0; i < addrTypeMap.length; i++)
					{
						typeId = parseInt(addrTypeMap[i]);
						if (allowedTypeList.indexOf(typeId) < 0)
						{
							allowedTypeList.push(typeId);
						}
					}
				}
				for (i = 0; i < typeValues.length; i++)
				{
					typeId = parseInt(typeValues[i]);
					if (allowedTypeList.indexOf(typeId) < 0)
					{
						allowedTypeList.push(typeId);
					}
				}
			}
		}
		this._allowedTypeIds = allowedTypeList;

		for (let addressItem of this._addressList)
		{
			addressItem.setAllowedTypesIds([...this._allowedTypeIds]);
		}
	}

	getDefaultType()
	{
		let defAddrType = this._defaultAddressType.toString();
		if (defAddrType > 0
			&& this._availableTypesIds.indexOf(defAddrType) >= 0
			&& this._allowedTypeIds.indexOf(parseInt(defAddrType)) >= 0)
		{
			return  defAddrType;
		}

		for (let item of this._typesList)
		{
			let value = BX.prop.getString(item, "ID", "");
			let isDefault = BX.prop.getString(item, "IS_DEFAULT", false);

			if (isDefault
				&& this._availableTypesIds.indexOf(value) >= 0
				&& this._allowedTypeIds.indexOf(parseInt(value)) >= 0)
			{
				return value;
			}
		}
		for (let item of this._typesList)
		{
			let value = BX.prop.getString(item, "ID", "");
			if (this._availableTypesIds.indexOf(value) >= 0
				&& this._allowedTypeIds.indexOf(parseInt(value)) >= 0)
			{
				return value;
			}
		}

		return null;
	}

	layout(isEditMode)
	{
		this._isEditMode = isEditMode;

		this._wrapper = Tag.render`<div class="crm-address-control-wrap ${this._isEditMode ? 'crm-address-control-wrap-edit' : ''}"></div>`;
		this.refreshLayout();

		return this._wrapper;
	}

	refreshLayout()
	{
		Dom.clean(this._wrapper);
		let addrCounter = true;
		for (let addressItem of this._addressList)
		{
			addressItem.setEditMode(this._isEditMode);
			if (!this._isEditMode && this._showFirstItemOnly && addrCounter > 1)
			{
				let showMore = Tag.render`
					<span class="ui-link ui-link-secondary ui-link-dotted" onmouseup="${this.onShowMoreMouseUp.bind(this)}"
						>
						${Loc.getMessage('CRM_ADDRESS_SHOW_ALL')}
					</span>`;
				Dom.append(showMore, this._wrapper);
				break;
			}
			else
			{
				Dom.append(addressItem.layout(), this._wrapper);
			}
			addrCounter++;
		}
		if (this._isEditMode && this._isMultiple && !Type.isNull(this.getDefaultType()))
		{
			let crmCompatibilityMode = BX.prop.getBoolean(this._settings, 'crmCompatibilityMode', false);
			let addButtonWrapClass = crmCompatibilityMode ?
				'crm-entity-widget-content-block-add-field' : 'ui-entity-widget-content-block-add-field';
			let addButtonClass = crmCompatibilityMode ?
				'crm-entity-widget-content-add-field' : 'ui-entity-editor-content-add-lnk';
			Dom.append(Tag.render`
				<div class="${addButtonWrapClass}"><span class="${addButtonClass}" onclick="${this.onAddNewAddress.bind(this)}">${Loc.getMessage('CRM_ADDRESS_ADD')}</span></div>
			`, this._wrapper);
		}
	}

	release()
	{
		Dom.clean(this._wrapper);
		this.removeAllAddresses();
	}

	removeAllAddresses()
	{
		let ids = this._addressList.map((item) => item.getId());
		for (let id of ids)
		{
			this.removeAddress(id);
		}
	}

	addAddress(type, value = null)
	{
		let addressItem = new AddressItem(Text.getRandom(8), {
			typesList: this.getTypesList(),
			availableTypesIds: [...this._availableTypesIds],
			allowedTypesIds: [...this._allowedTypeIds],
			canChangeType: this._isMultiple,
			enableAutocomplete: this._enableAutocomplete,
			showAddressTypeInViewMode: this._isMultiple && this._showAddressTypeInViewMode,
			type,
			value
		});
		addressItem.subscribe('onUpdateAddress', this.onUpdateAddress.bind(this));
		addressItem.subscribe('onUpdateAddressType', this.onUpdateAddressType.bind(this));
		addressItem.subscribe('onDelete', this.onDeleteAddress.bind(this));
		addressItem.subscribe('onStartLoadAddress', this.onStartLoadAddress.bind(this));
		addressItem.subscribe('onAddressLoaded', this.onAddressLoaded.bind(this));
		addressItem.subscribe('onAddressDataInputting', this.onAddressDataInputting.bind(this));
		addressItem.subscribe('onError', this.onError.bind(this));
		addressItem.subscribe('onCopyAddress', this.onCopyAddress.bind(this));
		this.updateAvailableTypes(type, null);
		this._addressList.push(addressItem);
		this.updateAllowedTypes();
		this.updateTypeSelectorVisibility(this._addressList.length > 1);
		return addressItem;
	}

	removeAddress(id)
	{
		let addressItem = this.getAddressById(id);
		if (addressItem)
		{
			let type = addressItem.getType();
			this._addressList.splice(this._addressList.indexOf(addressItem), 1);
			this.updateAvailableTypes(null, type);
			this.updateAllowedTypes();
			this.updateTypeSelectorVisibility(this._addressList.length > 1);
			addressItem.destroy();
		}
	}

	getAddressById(id)
	{
		return this._addressList
			.filter((item) => item.getId() === id)
			.reduce((prev, item) => prev ? prev : item, null);
	}

	initAvailableTypes()
	{
		this._availableTypesIds = [];
		for (let type of this._typesList)
		{
			this._availableTypesIds.push(BX.prop.getString(type, "ID", ""));
		}
	}

	updateAvailableTypes(removedType, addedType)
	{
		if (!Type.isNull(addedType) && this._availableTypesIds.indexOf(addedType) < 0)
		{
			this._availableTypesIds.push(addedType);
		}
		if (!Type.isNull(removedType) && this._availableTypesIds.indexOf(removedType) >= 0)
		{
			this._availableTypesIds.splice(this._availableTypesIds.indexOf(removedType), 1);
		}
		for (let addressItem of this._addressList)
		{
			addressItem.setAvailableTypesIds([...this._availableTypesIds]);
		}
	}

	updateTypeSelectorVisibility(showTypeSelector)
	{
		if (!this._hideDefaultAddressType)
		{
			return;
		}
		for (let addressItem of this._addressList)
		{
			addressItem.setTypeSelectorVisibility(showTypeSelector);
		}
	}

	emitUpdateEvent()
	{
		EventEmitter.emit(this, 'onUpdate', {value: this.getValue()});
	}

	onAddNewAddress()
	{
		this.addAddress(this.getDefaultType());
		this.refreshLayout();
	}

	onUpdateAddress(event)
	{
		this.emitUpdateEvent();
	}

	onDeleteAddress(event)
	{
		let data = event.getData();
		let id = data.id;
		if (this._addressList.length <= 1)
		{
			// should be at least one address, so just clear it
			let addressItem = this.getAddressById(id);
			if (addressItem)
			{
				addressItem.clearValue();
			}
			return;
		}
		this.removeAddress(id);
		this.refreshLayout();
	}

	onUpdateAddressType(event)
	{
		let data = event.getData();
		let prevType = data.prevType;
		let type = data.type;
		this.updateAvailableTypes(type, prevType);
		this.updateAllowedTypes();
		this.emitUpdateEvent();
	}

	onShowMoreMouseUp(event)
	{
		event.stopPropagation(); // cancel switching client to edit mode
		this._showFirstItemOnly = false;
		this.refreshLayout();
		return false;
	}

	onStartLoadAddress(event)
	{
		EventEmitter.emit(this, 'onStartLoadAddress');
	}

	onAddressLoaded(event)
	{
		EventEmitter.emit(this, 'onAddressLoaded');
	}

	onAddressDataInputting(event)
	{
		EventEmitter.emit(this, 'onAddressDataInputting');
	}

	onError(event)
	{
		EventEmitter.emit(this, 'onError', event);
	}

	onCopyAddress(event)
	{
		const data = event.getData();
		let sourceAddress = this.getAddressById(data.sourceId);
		if (!sourceAddress)
		{
			return;
		}
		const sourceAddressData = sourceAddress.getValue();
		if (!(sourceAddressData).length)
		{
			return;
		}

		for (let i=0; i < data.destinationTypes.length; i++)
		{
			const type = data.destinationTypes[i];
			let destinationAddress = this._addressList
				.filter((item) => item.getType() == type)
				.reduce((prev, cur) => prev ? prev : cur, null);
			if (destinationAddress)
			{
				destinationAddress.setValue(sourceAddressData);
			}
			else
			{
				destinationAddress = this.addAddress(type, sourceAddressData);
			}
			destinationAddress.markAsNew();

			this.refreshLayout();
		}
		this.emitUpdateEvent();
	}

	resetView(): void
	{
		for (const addressItem of this._addressList)
		{
			addressItem.resetView();
		}
	}

	static create(id, settings)
	{
		let self = new EntityEditorBaseAddressField();
		self.initialize(id, settings);
		return self;
	}
}

class AddressItem extends EventEmitter
{
	constructor(id, settings)
	{
		super();
		this.setEventNamespace('BX.Crm.AddressItem');

		this._id = id;
		this._value = BX.prop.getString(settings, 'value', "");
		this._isTypesMenuOpened = false;
		this._typesList = BX.prop.getArray(settings, 'typesList', []);
		this._availableTypesIds = BX.prop.getArray(settings, 'availableTypesIds', []);
		this._allowedTypesIds = BX.prop.getArray(settings, 'allowedTypesIds', []);
		this._canChangeType = BX.prop.getBoolean(settings, 'canChangeType', false);
		this.typesMenuId = 'address_type_menu_' + this._id;
		this._type = BX.prop.getString(settings, 'type', "");
		this._isEditMode = true;
		this._isTypeSelectorVisible = BX.prop.getBoolean(settings, 'isTypeSelectorVisible', true);
		this._isAutocompleteEnabled = BX.prop.getBoolean(settings, 'enableAutocomplete', true);
		this._showAddressTypeInViewMode = BX.prop.getBoolean(settings, 'showAddressTypeInViewMode', true);
		this._showDetails = !this._isAutocompleteEnabled || BX.prop.getBoolean(settings, 'showDetails', false);
		this._isLoading = false;
		this._icon = null;
		this._isDropdownLoading = false;
		this._addressWidget = null;
		this._wrapper = null;
		this._domNodes = {};
		this._selectedCopyDestinations = [];

		this._isLocationModuleInstalled =
			!Type.isUndefined(BX.Location) &&
			!Type.isUndefined(BX.Location.Core)&&
			!Type.isUndefined(BX.Location.Widget);

		this.initializeAddressWidget();
	}

	initializeAddressWidget()
	{
		if (!this._isLocationModuleInstalled)
		{
			return;
		}

		let value = this.getValue();

		let address = null;
		if (Type.isStringFilled(value))
		{
			try
			{
				address = new BX.Location.Core.Address(JSON.parse(value));
			}
			catch (e)
			{
			}
		}

		let widgetFactory = new BX.Location.Widget.Factory();

		this._addressWidget = widgetFactory.createAddressWidget({
			address: address,
			mode: this._isEditMode ? BX.Location.Core.ControlMode.edit : BX.Location.Core.ControlMode.view,
			popupBindOptions: {position: 'right'}
		});

		this._addressWidget.subscribeOnStateChangedEvent(this.onAddressWidgetChangedState.bind(this));
		this._addressWidget.subscribeOnAddressChangedEvent(this.onAddressChanged.bind(this));
		this._addressWidget.subscribeOnFeatureEvent(this.onFeatureEvent.bind(this));
		this._addressWidget.subscribeOnErrorEvent(this.onError.bind(this));
	}

	getId()
	{
		return this._id;
	}

	getType()
	{
		return this._type;
	}

	getValue()
	{
		return this._value;
	}

	setValue(value)
	{
		this.destroy();
		this._value = value;
		this.initializeAddressWidget();
	}

	markAsNew()
	{
		let address = this.getAddress();
		if (address)
		{
			address.id = 0;
			address.clearLinks();
		}
		this._value = address ? address.toJson() : '';
	}

	setEditMode(isEditMode)
	{
		this._isEditMode = !!isEditMode;
		if (!Type.isNull(this._addressWidget))
		{
			this._addressWidget.mode = isEditMode ? BX.Location.Core.ControlMode.edit : BX.Location.Core.ControlMode.view;
		}
	}

	setAvailableTypesIds(ids)
	{
		this._availableTypesIds = ids;
	}

	setAllowedTypesIds(ids)
	{
		this._allowedTypesIds = ids;
	}

	getTypeListByIds(ids)
	{
		let result = [];

		if (Type.isArray(ids) && ids.length > 0)
		{
			let typeMap = {};
			for (let item of this._typesList)
			{
				typeMap["a" + item.value] = item;
			}
			for (let typeId of ids)
			{
				let index = "a" + typeId;
				if (typeMap.hasOwnProperty(index))
				{
					result.push(typeMap[index]);
				}
			}
		}

		return result;
	}

	layout()
	{
		if (Type.isNull(this._addressWidget))
		{
			this._wrapper = Tag.render`<div>Location module is not installed</div>`;
			return this._wrapper;
		}
		let addressWidgetParams = {};
		const addressString = this.convertAddressToString(this.getAddress());
		if (this._isEditMode)
		{
			this._wrapper = this.getEditHtml(addressString);
			addressWidgetParams.mode = BX.Location.Core.ControlMode.edit;
			addressWidgetParams.inputNode = this._domNodes.searchInput;
			addressWidgetParams.mapBindElement = this._domNodes.searchInput;
			addressWidgetParams.fieldsContainer = this._domNodes.detailsContainer;
			addressWidgetParams.controlWrapper = this._domNodes.addressContainer;
		}
		else
		{
			this._wrapper = this.getViewHtml(addressString);
			addressWidgetParams.mode = BX.Location.Core.ControlMode.view;
			addressWidgetParams.mapBindElement = this._wrapper;
		}
		addressWidgetParams.controlWrapper = this._domNodes.addressContainer;

		this._addressWidget.render(addressWidgetParams);
		return this._wrapper;
	}

	openTypesMenu(bindElement)
	{
		if (this._isTypesMenuOpened)
		{
			return;
		}

		let menu = [];
		let allowedTypes = [...this._allowedTypesIds];
		let selectedTypeId = parseInt(this._type);
		if (allowedTypes.indexOf(selectedTypeId) < 0)
		{
			allowedTypes.push(selectedTypeId);
		}
		for (let item of this.getTypeListByIds(allowedTypes))
		{
			let selected = (selectedTypeId === parseInt(item.value));
			if (this._availableTypesIds.indexOf(item.value) < 0 && !selected)
			{
				continue;
			}
			menu.push(
				{
					text: item.name,
					value: item.value,
					//className: selected ? "menu-popup-item-accept" : "menu-popup-item-none",
					onclick: this.onChangeType.bind(this)
				}
			);
		}

		MenuManager.show(
			this.typesMenuId,
			bindElement,
			menu,
			{
				angle: false,
				cacheable: false,
				events:
					{
						onPopupShow: () =>
						{
							this._isTypesMenuOpened = true;
						},
						onPopupClose: () =>
						{
							this._isTypesMenuOpened = false;
						}
					}
			}
		);
		let createdMenu = MenuManager.getMenuById(this.typesMenuId);
		if (createdMenu && Type.isDomNode(this._domNodes.addressTypeSelector)
			&& this._domNodes.addressTypeSelector.offsetWidth > 200)
		{
			createdMenu.getPopupWindow().setWidth(this._domNodes.addressTypeSelector.offsetWidth);
		}
	};

	closeTypesMenu()
	{
		let menu = MenuManager.getMenuById(this.typesMenuId);
		if (menu)
		{
			menu.close();
		}
	}

	setTypeSelectorVisibility(visible)
	{
		this._isTypeSelectorVisible = !!visible;
	}

	getEditHtml(addressString)
	{
		this._domNodes.typeName = Tag.render`<div class="ui-ctl-element"></div>`;
		this._domNodes.searchInput = Tag.render`
			<input type="text" class="ui-ctl-element ui-ctl-textbox" value="${addressString}" ${this._isAutocompleteEnabled ? '' : 'readonly'}>`;
		this._domNodes.icon = Tag.render`<span></span>`;
		this._domNodes.addressContainer = Tag.render`
		<div class="crm-address-search-control-block">
			<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon">
				${this._domNodes.icon}
				${this._domNodes.searchInput}
			</div>
		</div>`;

		this._domNodes.detailsContainer = Tag.render`
			<div class="location-fields-control-block"></div>`;

		if (this._canChangeType)
		{
			if (this._isTypeSelectorVisible)
			{
				this._domNodes.addressTypeSelector = Tag.render`
					<div class="ui-ctl ui-ctl-inline ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w25" onclick="${this.onToggleTypesMenu.bind(this)}">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						${this._domNodes.typeName}
					</div>`;
				this._domNodes.addressTypeContainer = null;

				Dom.addClass(this._domNodes.addressContainer, ['ui-ctl-inline', 'ui-ctl-w75']);
				this._domNodes.addressContainer = Tag.render`
					<div class="ui-ctl-inline ui-ctl-w100">
						${this._domNodes.addressTypeSelector}
						${this._domNodes.addressContainer}
					</div>
				`;
			}
			else
			{
				this._domNodes.addressTypeSelector = Tag.render`
					<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown" onclick="${this.onToggleTypesMenu.bind(this)}">
						<div class="ui-ctl-after ui-ctl-icon-angle"></div>
						${this._domNodes.typeName}
					</div>`;

				this._domNodes.addressTypeContainer = Tag.render`
					<div class="location-fields-control-block crm-address-type-block">
						<div class="ui-entity-editor-content-block ui-entity-editor-field-text">
							<div class="ui-entity-editor-block-title">
								<label class="ui-entity-editor-block-title-text">${Loc.getMessage('CRM_ADDRESS_TYPE')}</label>
							</div>
							${this._domNodes.addressTypeSelector}
						</div>
					</div>`;
			}
			this.refreshTypeName();
		}

		this.refreshIcon();

		this._domNodes.detailsToggler =
			Tag.render`<span class="ui-link ui-link-secondary ui-entity-editor-block-title-link" onclick="${this.onToggleDetailsVisibility.bind(this)}"></span>`;

		if (this._canChangeType)
		{
			this._domNodes.copyButton =
				Tag.render`<span class="ui-link ui-link-secondary ui-entity-editor-block-title-link" onclick="${this.onCopyButtonClick.bind(this)}">${Loc.getMessage('CRM_ADDRESS_COPY1')}</span>`;
		}

		this.refreshCopyButtonVisibility();
		this.setDetailsVisibility(this._showDetails);

		let result = Tag.render`
			<div class="crm-address-control-item">
				<div class="crm-address-control-mode-switch">
					${this._domNodes.copyButton ? this._domNodes.copyButton : ''}
					${this._domNodes.detailsToggler}
				</div>
				${this._domNodes.addressContainer}
				${this._domNodes.detailsContainer}
			</div>`;

		if (this._canChangeType && Type.isDomNode(this._domNodes.addressTypeContainer))
		{
			Dom.append(this._domNodes.addressTypeContainer, result);
		}

		return result;
	}

	getViewHtml(addressString)
	{
		this._domNodes.addressContainer = Tag.render`
			<div class="ui-entity-editor-content-block-text">
				<span class="ui-link ui-link-dark ui-link-dotted">${addressString}</span>
			</div>`;

		let addressType = '';
		if (this._showAddressTypeInViewMode)
		{
			let typeName = this._typesList
				.filter((item) => item.value == this._type)
				.map((item) => item.name)
				.join('')

			addressType = Tag.render`<span class="ui-link ui-link-secondary">${Text.encode(typeName)}:</span>`
		}
		return Tag.render`
			<div class="crm-address-control-item">
				${addressType}
				${this._domNodes.addressContainer}
			</div>`;
	}

	refreshTypeName()
	{
		if (Type.isDomNode(this._domNodes.typeName))
		{
			let typeName =
				this._typesList
					.filter((item) => item.value === this._type)
					.map((item) => item.name)
					.join('');

			this._domNodes.typeName.textContent = typeName;
			this._domNodes.typeName.title = typeName;
		}
	}

	refreshIcon()
	{
		let newIcon = this.getNewIcon();
		if (this._icon !== newIcon)
		{
			let node = this._domNodes.icon;
			if (Type.isDomNode(node))
			{
				let newNode;
				if (newIcon === 'loading')
				{
					newNode = Tag.render`<span class="ui-ctl-after ui-ctl-icon-loader"></span>`;
				}
				else
				{
					if (newIcon === 'clear')
					{
						newNode = Tag.render`<button type="button" class="ui-ctl-after ui-ctl-icon-clear" onclick="${this.onDelete.bind(this)}"></button>`;
					}
					else if (newIcon === 'search')
					{
						newNode = Tag.render`<span class="ui-ctl-after ${this._isAutocompleteEnabled ? 'ui-ctl-icon-search' : ''}"></span>`;
					}
				}

				Dom.replace(node, newNode);
				this._domNodes.icon = newNode;
			}

			this._icon = newIcon;
		}
	}

	getNewIcon()
	{
		if (this._isLoading)
		{
			return 'loading';
		}
		else
		{
			return this.getAddress() ? 'clear' : 'search';
		}
	}

	refreshCopyButtonVisibility()
	{
		let node = this._domNodes.copyButton;
		if (Type.isDomNode(node))
		{
			let isVisible = !!this.getAddress();
			Dom.style(node, 'display', isVisible ? '' : 'none');
		}
	}

	convertAddressToString(address)
	{
		if (!address)
		{
			return '';
		}

		return address.toString(this.getAddressFormat());
	}

	getAddress()
	{
		return Type.isNull(this._addressWidget) ? null : this._addressWidget.address;
	}

	getAddressFormat()
	{
		return Type.isNull(this._addressWidget) ? null : this._addressWidget.addressFormat;
	}

	clearValue()
	{
		if (!Type.isNull(this._addressWidget))
		{
			this._addressWidget.resetView();
			this._addressWidget.address = null;
		}
		if (Type.isDomNode(this._domNodes.searchInput))
		{
			this._domNodes.searchInput.value = '';
		}
		this._value = "";
		this._isLoading = false;
		this.refreshIcon();
		this.refreshCopyButtonVisibility();
	}

	setDetailsVisibility(visible)
	{
		this._showDetails = !!visible;
		if (this._showDetails)
		{
			Dom.addClass(this._domNodes.detailsContainer, 'visible');
			if (Type.isDomNode(this._domNodes.detailsToggler))
			{
				this._domNodes.detailsToggler.textContent = Loc.getMessage('CRM_ADDRESS_MODE_SHORT');
			}
			if (this._canChangeType)
			{
				Dom.addClass(this._domNodes.addressTypeContainer, 'visible');
			}
		}
		else
		{
			Dom.removeClass(this._domNodes.detailsContainer, 'visible');
			if (Type.isDomNode(this._domNodes.detailsToggler))
			{
				this._domNodes.detailsToggler.textContent = Loc.getMessage('CRM_ADDRESS_MODE_DETAILED');
			}
			if (this._canChangeType)
			{
				Dom.removeClass(this._domNodes.addressTypeContainer, 'visible');
			}
		}
	}

	showCopyDestinationPopup()
	{
		let popup = PopupManager.create({
			id: this._id + '_copy_dst_popup',
			cacheable: false,
			autoHide: true,
			titleBar: Loc.getMessage('CRM_ADDRESS_COPY_TITLE'),
			content: this.getCopyDestinationLayout(),
			closeIcon: true,
			closeByEsc: true,
			buttons: [
				new BX.UI.Button({
					id: 'copy',
					text: Loc.getMessage('CRM_ADDRESS_COPY2'),
					color: BX.UI.Button.Color.PRIMARY,
					state: BX.UI.ButtonState.DISABLED,
					onclick:(button) =>
					{
						button.getContext().close();
						this.emit('onCopyAddress', {
							sourceId: this.getId(),
							destinationTypes: this._selectedCopyDestinations});
					}
				})
			]
		});
		popup.show();
	}

	getCopyDestinationLayout()
	{
		let types = this.getTypeListByIds(this._allowedTypesIds).filter((item) => item.value !== this._type);
		return Tag.render`
			<div>
				<div class="ui-title-7">${Loc.getMessage('CRM_ADDRESS_COPY_TO')}</div>
				<div>
					${types.map((item) => Tag.render`
					<div class="ui-ctl ui-ctl-w100 ui-ctl-checkbox ui-ctl-xs">
					<label>
					<input onclick="${this.onChangeCopyDestination.bind(this)}" type="checkbox" value="${item.value}">
						<span class="ui-ctl-label-text">${Text.encode(item.name)}</span>
					</label>
					</div>
					`)}
				</div>
			</div>
		`;
	}

	destroy()
	{
		if (!Type.isNull(this._addressWidget))
		{
			this._addressWidget.destroy();
		}
	}

	onToggleDetailsVisibility()
	{
		this.setDetailsVisibility(!this._showDetails);
	}

	onDelete()
	{
		this.clearValue();
		this.emit('onUpdateAddress', {id: this.getId(), value: this.getValue()});
	}

	onCopyButtonClick()
	{
		this.showCopyDestinationPopup();
	}

	onChangeCopyDestination(e)
	{
		let input = e.target;
		let value = input ? input.value : null;
		let isChecked = input ? input.checked : false;
		if (isChecked && this._selectedCopyDestinations.indexOf(value) < 0)
		{
			this._selectedCopyDestinations.push(value);
		}
		if (!isChecked && this._selectedCopyDestinations.indexOf(value) >= 0)
		{
			this._selectedCopyDestinations.splice(this._selectedCopyDestinations.indexOf(value), 1);
		}
		let popup = PopupManager.getPopupById(this._id + '_copy_dst_popup');
		if (popup)
		{
			let button = popup.getButton('copy');
			if (button)
			{
				button.setDisabled(!this._selectedCopyDestinations.length);
			}
		}
	}

	onToggleTypesMenu(event)
	{
		if (this._isTypesMenuOpened)
		{
			this.closeTypesMenu();
		}
		else
		{
			this.openTypesMenu(event.target);
		}
	}

	onChangeType(e, item)
	{
		this.closeTypesMenu();
		if (this._type !== item.value)
		{
			let prevType = this._type;
			this._type = item.value;
			this.refreshTypeName();
			this.emit('onUpdateAddressType', {id: this.getId(), type: this.getType(), prevType});
		}
	}

	onAddressWidgetChangedState(event)
	{
		let data = event.getData(),
			state = data.state;

		let wasLoading = this._isLoading;

		this.computeIsLoading();

		if (wasLoading !== this._isLoading)
		{
			this.refreshIcon();
			this.refreshCopyButtonVisibility();
		}
		if (state === BX.Location.Widget.State.DATA_LOADING)
		{
			this.emit('onStartLoadAddress', {id: this.getId()});
		}
		else if (state === BX.Location.Widget.State.DATA_LOADED)
		{
			this.emit('onAddressLoaded', {id: this.getId()});
		}
		else if (state === BX.Location.Widget.State.DATA_INPUTTING)
		{
			this.emit('onAddressDataInputting', {id: this.getId()});
		}
	}

	onAddressChanged(event)
	{
		this._isLoading = false;

		let data = event.getData();
		this._value = Type.isObject(data.address) ? data.address.toJson() : '';

		this.refreshIcon();
		this.refreshCopyButtonVisibility();
		this.emit('onUpdateAddress', {id: this.getId(), value: this.getValue()});
	}

	onFeatureEvent(event)
	{
		let data = event.getData();

		if (data.feature instanceof BX.Location.Widget.AutocompleteFeature)
		{
			this._isDropdownLoading = (data.eventCode === BX.Location.Widget.AutocompleteFeature.searchStartedEvent);

			let wasLoading = this._isLoading;

			this.computeIsLoading();

			if (wasLoading !== this._isLoading)
			{
				this.refreshIcon();
				this.refreshCopyButtonVisibility();
			}
		}
	}

	onError(event)
	{
		const data = event.getData();
		const errors = data.errors;
		let errorMessage = errors
			.map((error) => error.message + (error.code.length ? `${error.code}` : ''))
			.join(', ');

		this._isLoading = false;
		this.refreshIcon();
		this.refreshCopyButtonVisibility();

		this.emit('onError', {id: this.getId(), error: errorMessage});
	}

	computeIsLoading()
	{
		this._isLoading = (this._addressWidget.state === BX.Location.Widget.State.DATA_LOADING
			|| this._isDropdownLoading
		);
	}

	resetView(): void
	{
		this._addressWidget.resetView();
	}
}