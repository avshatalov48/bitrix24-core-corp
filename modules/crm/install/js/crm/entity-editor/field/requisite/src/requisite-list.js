import {Type} from "main.core";
import {EventEmitter} from "main.core.events";

export class RequisiteList extends EventEmitter
{
	constructor()
	{
		super();
		this.setEventNamespace('BX.Crm.EntityEditorRequisiteField.RequisiteList');
		this._items = [];

		this.CHANGE_EVENT = 'onChange';
	}

	initialize(value, settings)
	{
		if (!Type.isArray(value))
		{
			value = [];
		}
		for (let item of value)
		{
			let listItem = RequisiteListItem.create(item);
			this._items.push(listItem);
		}
	}

	getList()
	{
		return this._items.filter((item) => !item.isDeleted());
	}

	getListWithDeleted()
	{
		return this._items;
	}

	isEmpty()
	{
		return !this.getList().length;
	}

	getSelected()
	{
		let selectedId = this.getSelectedId();
		return this.getById(selectedId);
	}

	getSelectedId()
	{
		let list = this.getList();
		if (!list.length)
		{
			return null;
		}
		for (let index = 0; index < list.length; index++)
		{
			let requisite = list[index];
			if (requisite.isSelected())
			{
				return index;
			}
		}
		return 0; // first element by default
	}

	getById(id)
	{
		let list = this.getList();

		if (null === id)
		{
			return null;
		}
		if (id >= 0 && id < list.length)
		{
			return list[id];
		}
		return null;
	}

	getByRequisiteId(requisiteId)
	{
		let list = this.getList();
		return list
			.filter((item) => item.getRequisiteId() == requisiteId)
			.reduce((prev, current) => current, null);
	}

	setSelected(requisiteId, bankDetailsId)
	{
		let requisite = this.getById(requisiteId);
		if (requisite)
		{
			for (let item of this.getList())
			{
				let selected = (item === requisite);
				item.setSelected(selected);

				if (selected)
				{
					item.setSelectedBankDetails(
						Type.isNull(bankDetailsId) ? requisite.getSelectedBankDetailId() : bankDetailsId);
				}
			}
			this.notifyListChanged();
		}
	}

	getNewRequisiteId()
	{
		let maxExistedId = this.getList().reduce((prevId, item) =>
		{
			let requisiteId = item.getRequisiteIdAsString();
			let match = requisiteId ? requisiteId.match(RequisiteListItem.newRequisitePattern) : false;
			let currentId = match && match[1] ? match[1] : -1;
			return Math.max(prevId, currentId);
		}, -1);

		return 'n' + (parseInt(maxExistedId) + 1);
	}

	indexOf(item)
	{
		return this._items.indexOf(item);
	}

	add(item)
	{
		this._items.push(item);
		if (!item.isAddressOnly())
		{
			this.setSelected(this._items.indexOf(item));
		}
		else
		{
			this.notifyListChanged();
		}
	}

	remove(item)
	{
		let index = this._items.indexOf(item);
		if (index >= 0)
		{
			this._items.splice(index, 1);
		}
		this.notifyListChanged();
	}

	removePostponed(item)
	{
		let index = this._items.indexOf(item);
		if (index >= 0)
		{
			item.setDeleted(true);
		}
		this.notifyListChanged();
	}

	hide(item)
	{
		let index = this._items.indexOf(item);
		if (index >= 0)
		{
			item.setAddressOnly(true);
			item.setChanged(true);
		}
		this.notifyListChanged();
	}

	unhide(item)
	{
		let index = this._items.indexOf(item);
		if (index >= 0)
		{
			item.setAddressOnly(false);
			item.setChanged(true);
		}
		this.notifyListChanged();
	}

	notifyListChanged()
	{
		this.emit(this.CHANGE_EVENT);
	}

	exportToModel()
	{
		let result = [];
		for (let item of this._items)
		{
			let exportedItem = item.exportToModel();
			result.push({...exportedItem});
		}
		return result;
	}

	static create(value, settings)
	{
		let self = new RequisiteList();
		self.initialize(value, settings);
		return self;
	}
}

export class RequisiteListItem
{
	constructor()
	{

		this._data = null;
	}

	initialize(value, settings)
	{
		if (Type.isPlainObject(value))
		{
			this._data = {...value};
			this._data.isNew = false;
			this._data.isChanged = false;
			this._data.isDeleted = false;
			this._data.isAddressOnly = false;
			this._data.formData = {};
			this._data.addressData = {};
			if (!Type.isPlainObject(this._data.autocompleteState))
			{
				this._data.autocompleteState = {};
			}
		}
		else
		{ // new empty requisite
			this._data = {
				isNew: true,
				isChanged: false,
				isDeleted: false,
				isAddressOnly: false,
				selected: false,
				presetId: null,
				requisiteId: BX.prop.getString(settings, 'newRequisiteId', 'n0'),
				requisiteData: '',
				requisiteDataSign: '',
				bankDetails: [],
				bankDetailIdSelected: 0,
				addressList: {},
				value: {},
				title: '',
				subtitle: '',
				autocompleteState: {},
				formData: {},
				addressData: {}
			};
			let extraData = BX.prop.getObject(settings, 'newRequisiteExtraFields', {});
			this._data = {...this._data, ...extraData};
		}
		this._data.initialAddressDdta = null;
		this.prepareViewData(this._data);
	}

	prepareViewData()
	{
		try
		{
			this._data.value = this._data.requisiteData ? JSON.parse(this._data.requisiteData) : {};
		}
		catch (e)
		{
			this._data.value = {};
		}
		if (Type.isPlainObject(this._data.value) && Type.isPlainObject(this._data.value.viewData))
		{
			this._data.title = this._data.value.viewData.title;
			this._data.subtitle = this._data.value.viewData.subtitle;
		}

		if (this.getRequisiteIdAsString().match(RequisiteListItem.newRequisitePattern)) // was new requisite
		{
			let newRequisiteId = BX.prop.getNumber(this.getFields(), 'ID', 0);
			if (newRequisiteId > 0) // if new requisite was saved
			{
				this.setRequisiteId(newRequisiteId);
			}
		}
		this.setAddressOnly(BX.prop.getString(this.getFields(), 'ADDRESS_ONLY', 'N') === 'Y');

		this._data.bankDetails = [];
		if (Type.isPlainObject(this._data.value) && Type.isArray(this._data.value.bankDetailViewDataList))
		{
			this._data.bankDetails = this.prepareBankDetailsList(this._data.value.bankDetailViewDataList);
		}

		this._data.addressList = {...BX.prop.getObject(this.getFields(), 'RQ_ADDR', {})};
	}

	prepareBankDetailsList(bankDetails)
	{
		let result = [];
		for (let bankDetailsItem of bankDetails)
		{
			if (bankDetailsItem.deleted)
			{
				continue; // Deleted items should not be shown
			}
			if (Type.isPlainObject(bankDetailsItem.viewData))
			{
				let item = {
					'title': bankDetailsItem.viewData.title,
					'id': bankDetailsItem.pseudoId,
					'value': '',
					'selected': !!bankDetailsItem.selected
				};
				if (Type.isArray(bankDetailsItem.viewData.fields) && bankDetailsItem.viewData.fields.length)
				{
					item.value = bankDetailsItem.viewData.fields
						.filter((item) => Type.isStringFilled(item.textValue))
						.map((item) => item.title + ': ' + item.textValue)
						.join(', ');
				}
				if (!item.value.length)
				{
					item.value = item.title;
				}
				result.push(item);
			}
		}
		return result;
	}

	isSelected()
	{
		if (!this._data.hasOwnProperty('justSelected'))
		{
			return BX.prop.getBoolean(this._data, 'selected', false);
		}
		return BX.prop.getBoolean(this._data, 'justSelected', false);
	}

	isChanged()
	{
		return BX.prop.getBoolean(this._data, 'isChanged', false);
	}

	setChanged(changed)
	{
		this._data.isChanged = !!changed;
	}

	isNew()
	{
		return BX.prop.getBoolean(this._data, 'isNew', false);
	}

	setNew(isNew)
	{
		this._data.isNew = !!isNew;
	}

	getSelectedBankDetailId()
	{
		let selectedBankDetailId = BX.prop.getInteger(this._data, 'selectedBankDetailId', -1);
		if (selectedBankDetailId !== -1)
		{
			return selectedBankDetailId;
		}
		selectedBankDetailId = this.getBankDetails().reduce(
			(selected, item, index) =>
			{
				return item.selected ? index : selected
			}, -1);
		this._data.selectedBankDetailId = selectedBankDetailId;

		return selectedBankDetailId > -1 ? selectedBankDetailId : 0;
	}

	getBankDetailById(bankDetailId)
	{
		let list = this.getBankDetails();
		if (null === bankDetailId)
		{
			return null;
		}
		if (bankDetailId >= 0 && bankDetailId < list.length)
		{
			return list[bankDetailId];
		}
		return null;
	}

	getBankDetailByBankDetailId(bankDetailId)
	{
		let list = this.getBankDetails();
		if (null === bankDetailId)
		{
			return null;
		}
		return list
			.filter((item) => item.id == bankDetailId)
			.reduce((prev, current) => current, null);
	}

	getTitle()
	{
		return BX.prop.getString(this._data, 'title', "");
	}

	getSubtitle()
	{
		return BX.prop.getString(this._data, 'subtitle', "");
	}

	getPresetId()
	{
		return BX.prop.getString(this._data, 'presetId', "0");
	}

	getPresetCountryId()
	{
		return BX.prop.getString(this._data, 'presetCountryId', "0");
	}

	getBankDetails()
	{
		return BX.prop.getArray(this._data, 'bankDetails', []);
	}

	getRequisiteId()
	{
		return this._data.requisiteId;
	}

	getRequisiteIdAsString()
	{
		let requisiteId = this.getRequisiteId();
		requisiteId = Type.isNumber(requisiteId) ? String(requisiteId) : requisiteId;
		return Type.isStringFilled(requisiteId) ? requisiteId : '';
	}

	getRequisiteData()
	{
		return this._data.requisiteData;
	}

	getRequisiteDataSign()
	{
		return this._data.requisiteDataSign;
	}

	getFields()
	{
		if (Type.isPlainObject(this._data.value) && Type.isPlainObject(this._data.value.fields))
		{
			return {...this._data.value.fields};
		}
		return {};
	}

	getAutocompleteData()
	{
		let result = null;

		let autocompleteState = this.getAutocompleteState();
		let selectedAutocompleteItem = BX.prop.getObject(autocompleteState, 'currentItem', null);
		if (Type.isPlainObject(selectedAutocompleteItem))
		{
			result = {
				title: BX.prop.getString(selectedAutocompleteItem, 'title', ''),
				subTitle: BX.prop.getString(selectedAutocompleteItem, 'subTitle', '')
			}
		}
		else if (!Type.isUndefined(this._data.value.viewData)  && Type.isArray(this._data.value.viewData.fields))
		{
			let fields =  this._data.value.viewData.fields;
			result = {
				title: Type.isStringFilled(this._data.title) ? this._data.title : '',
				subTitle: fields
					.filter((item) => item.name === 'RQ_INN' && item.textValue.length)
					.map((item) => item.title + ' ' + item.textValue)
					.join('')
			};
		}
		return result;
	}

	setAutocompleteState(state)
	{
		this._data.autocompleteState = Type.isPlainObject(state) ? state : {};
	}

	getAutocompleteState()
	{
		return this._data.autocompleteState;
	}

	getAddressList()
	{
		return this._data.addressList;
	}

	setAddressList(addressList)
	{
		this._data.addressList = addressList;
	}

	setInitialAddressData(addressData)
	{
		this._data.initialAddressDdta = addressData;
	}

	getAddressesForSave()
	{
		let oldAddressTypes = Type.isPlainObject(this._data.initialAddressDdta) ?
			Object.keys(this._data.initialAddressDdta) : [];

		let addresses = {};
		for (let type of oldAddressTypes)
		{
			addresses[type] = "";
		}
		let addressData = this.getAddressData();
		for (let type in addressData)
		{
			if (addressData.hasOwnProperty(type) && addressData[type].length)
			{
				addresses[type] = addressData[type];
			}
		}
		for (let type in addresses)
		{
			if (Type.isString(addresses[type]) && addresses[type] === "")
			{
				addresses[type] = {DELETED: 'Y'};
			}
		}

		return addresses;
	}

	setRequisiteId(requisiteId)
	{
		this._data.requisiteId = requisiteId;
	}

	setPresetId(presetId)
	{
		this._data.presetId = presetId;
	}

	setPresetCountryId(presetCountryId)
	{
		this._data.presetCountryId = presetCountryId;
	}

	setSelected(selected)
	{
		this._data.selected = !!selected;
		this._data.justSelected = !!selected;
	}

	setRequisiteData(requisiteData, requisiteDataSign)
	{
		this._data.requisiteData = requisiteData;
		if (Type.isStringFilled(requisiteDataSign))
		{
			this._data.requisiteDataSign = requisiteDataSign;
		}
		this.prepareViewData();
	}

	setDeleted(isDeleted)
	{
		this._data.isDeleted = !!isDeleted;
	}

	isDeleted()
	{
		return this._data.isDeleted;
	}

	setAddressOnly(isAddressOnly)
	{
		this._data.isAddressOnly = !!isAddressOnly;
		this.setFormData({...this.getFormData(), 'ADDRESS_ONLY': isAddressOnly ? 'Y':'N'})
	}

	isAddressOnly()
	{
		return this._data.isAddressOnly;
	}

	isEmptyFormData()
	{
		return Object.keys(this._data.formData).length <= 0;
	}

	getFormData()
	{
		return this._data.formData;
	}

	setFormData(formData)
	{
		this._data.formData = formData;
	}

	clearFormData()
	{
		this.setFormData({});
	}

	isEmptyAddressData()
	{
		return Object.keys(this._data.addressData).length <= 0;
	}

	getAddressData()
	{
		return this._data.addressData;
	}

	setAddressData(addressData)
	{
		this._data.addressData = addressData;
	}

	clearAddressData()
	{
		this.setAddressData({});
	}
	setSelectedBankDetails(bankDetailsId)
	{
		if (!Type.isArray(this._data.bankDetails))
		{
			return;
		}
		if (Type.isNull(bankDetailsId))
		{
			bankDetailsId = 0; // first item by default
		}
		for (let index = 0; index < this._data.bankDetails.length; index++)
		{
			this._data.bankDetails[index].selected = (index === bankDetailsId);
		}

		this._data.selectedBankDetailId = bankDetailsId;
	}

	clearSelectedBankDetails()
	{
		if (!Type.isArray(this._data.bankDetails))
		{
			return;
		}
		for (let index = 0; index < this._data.bankDetails.length; index++)
		{
			this._data.bankDetails[index].selected = false;
		}
	}

	exportToModel()
	{
		let exportedItem = {...this._data};
		delete (exportedItem.value);
		delete (exportedItem.addressList);
		delete (exportedItem.bankDetails);
		delete (exportedItem.initialAddressDdta);
		delete (exportedItem.isAddressOnly);
		return exportedItem;
	}

	static create(value, settings)
	{
		let self = new RequisiteListItem();
		self.initialize(value, settings);
		return self;
	}
}

RequisiteListItem.newRequisitePattern = /n([0-9]+)/;