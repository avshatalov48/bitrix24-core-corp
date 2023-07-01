import {Dom, Loc, Tag, Type} from "main.core"
import {EventEmitter} from "main.core.events"
import {RequisiteList, RequisiteListItem} from "./requisite-list";
import {EntityEditorRequisiteEditor} from "./requisite-editor"
import {MessageBox} from 'ui.dialogs.messagebox';

export class EntityEditorRequisiteController extends BX.Crm.EntityEditorController
{
	constructor()
	{
		super();
		this._requisiteList = null;
		this._requisiteEditor = null;

		this._requisiteFieldId = null;
		this._requisiteField = null;
		this._requisiteInitData = null;

		this._addressFieldId = null;
		this._addressField = null;
		this._isLoading = false;

		this._formInputsWrapper = null;
		this._enableRequisiteSelection = false;
	}

	doInitialize()
	{
		super.doInitialize();
		this._requisiteFieldId = this.getConfigStringParam("requisiteFieldId", "");
		this._addressFieldId = this.getConfigStringParam("addressFieldId", "");

		this.saveRequisiteInitData();

		EventEmitter.subscribe(this._editor, 'onFieldInit', this.onFieldInit.bind(this));

		this.initRequisiteEditor();
		this.initRequisiteList();

		let selectedItem = BX.prop.getObject(this.getConfig(), "requisiteBinding", {});
		if (!Type.isUndefined(selectedItem.REQUISITE_ID) && !Type.isUndefined(selectedItem.BANK_DETAIL_ID))
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

		this._enableRequisiteSelection = BX.prop.getString(this._config, 'enableRequisiteSelection', false);
	}

	initRequisiteList()
	{
		this._requisiteList = RequisiteList.create(this._requisiteInitData);
		this._requisiteList.subscribe(this._requisiteList.CHANGE_EVENT, this.onChangeRequisites.bind(this));

		this._requisiteEditor.setRequisiteList(this._requisiteList);
	}

	initRequisiteEditor()
	{
		this._requisiteEditor = EntityEditorRequisiteEditor.create(this._id + '_rq_editor', {
			entityTypeId: this._editor.getEntityTypeId(),
			entityId: this._editor.getEntityId(),
			contextId: this._editor.getContextId(),
			requisiteEditUrl: this._editor.getRequisiteEditUrl('#requisite_id#'),
			permissionToken: this.getConfigStringParam('permissionToken', null),
			entityCategoryId: BX.prop.getString(this.getConfig(), 'entityCategoryId', 0)
		});
		EventEmitter.subscribe(this._requisiteEditor, 'onAfterEditRequisite', this.onRequisiteEditorAfterEdit.bind(this));
		EventEmitter.subscribe(this._requisiteEditor, 'onAfterDeleteRequisite', this.onRequisiteEditorAfterDelete.bind(this));
	}

	initRequisiteField()
	{
		if (this._requisiteField)
		{
			this._requisiteField.setRequisites(this._requisiteList);

			this._requisiteField.setSelectModeEnabled(this._enableRequisiteSelection);
		}
	}

	initAddressField()
	{
		if (this._addressField)
		{
			let countryId = 0;
			let addressList = {};
			let selectedRequisite = this._requisiteList ? this._requisiteList.getSelected() : null;
			if (selectedRequisite)
			{
				countryId = selectedRequisite.getPresetCountryId();
				let requisiteAddressList = selectedRequisite.getAddressList();
				for (let type in requisiteAddressList)
				{
					if (
						requisiteAddressList.hasOwnProperty(type) &&
						BX.prop.getString(requisiteAddressList[type], 'DELETED', 'N') !== 'Y'
					)
					{
						addressList[type] = requisiteAddressList[type];
					}
				}
			}
			this._addressField.setCountryId(countryId);
			this._addressField.setAddressList(addressList);
		}
	}

	setSelectModeEnabled(enableRequisiteSelection: boolean): void
	{
		if (this._enableRequisiteSelection !== enableRequisiteSelection)
		{
			this._enableRequisiteSelection = enableRequisiteSelection;
			if (this._requisiteField)
			{
				this._requisiteField.setSelectModeEnabled(this._enableRequisiteSelection);
			}
		}
	}

	saveRequisiteInitData()
	{
		this._requisiteInitData = this.getRequisiteFieldValue();
	}

	validate(result)
	{
		let promises = [];
		for (let requisite of this._requisiteList.getList())
		{
			if (!requisite.isChanged())
			{
				continue;
			}
			if (requisite.isEmptyFormData() && requisite.isEmptyAddressData())
			{
				continue;
			}

			let signPromise = this.signRequisiteFields(requisite);
			signPromise.then(
				(data) =>
				{
					let error = BX.prop.getString(data, 'ERROR', '');
					let entityDataObj = BX.prop.getObject(data, 'ENTITY_DATA', {});
					let entityData = BX.prop.getString(entityDataObj, 'REQUISITE_DATA', "");
					let entityDataSign = BX.prop.getString(entityDataObj, 'REQUISITE_DATA_SIGN', "");
					if (Type.isStringFilled(error))
					{
						result.addError(BX.Crm.EntityValidationError.create({field: this.getFirstEditModeField()}));
						this.showError(error);
					}
					else if (Type.isStringFilled(entityData) && Type.isStringFilled(entityDataSign))
					{
						requisite.setRequisiteData(entityData, entityDataSign);
						requisite.clearFormData();
						requisite.clearAddressData();
					}
					else
					{
						result.addError(BX.Crm.EntityValidationError.create({field: this.getFirstEditModeField()}));
						this.showError(Loc.getMessage('CRM_EDITOR_SAVE_ERROR_CONTENT'));
					}
					return true;
				},
				() =>
				{
					result.addError(BX.Crm.EntityValidationError.create({field: this.getFirstEditModeField()}));
					this.showError(Loc.getMessage('CRM_EDITOR_SAVE_ERROR_CONTENT'));
					return new Promise((resolve, reject) =>
					{
						resolve();
					});
				}
			);
			promises.push(signPromise);
		}

		return (promises.length > 0) ? Promise.all(promises) : null;
	}

	setSelectedRequisite(requisiteId, bankDetailId)
	{
		let entityId = this._editor.getEntityId();
		if (!entityId)
		{
			// impossible situation, but...
			return;
		}
		let newSelectedRequisite = this._requisiteList.getById(requisiteId);
		if (!newSelectedRequisite || newSelectedRequisite.isNew())
		{
			// impossible situation too
			return;
		}
		this._requisiteList.setSelected(requisiteId, bankDetailId);

		let selectedBankDetail = newSelectedRequisite.getBankDetailById(newSelectedRequisite.getSelectedBankDetailId());
		let selectedBankDetailId = Type.isNull(selectedBankDetail) ? null : selectedBankDetail.id;

		this.startLoading();
		BX.ajax.runAction(
			'crm.requisite.settings.setSelectedEntityRequisite',
			{
				data: {
					entityTypeId: this._editor.getEntityTypeId(),
					entityId: entityId,
					requisiteId: newSelectedRequisite.getRequisiteId(),
					bankDetailId: selectedBankDetailId,
				}
			}
		).then(() =>
		{
			this.stopLoading();
		}, () =>
		{
			this.stopLoading();
		});

	}

	openEditor(requisite, options = {})
	{
		this.setRequisiteInitAddrData(requisite);
		this._requisiteEditor.setMode(this._editor.getMode());
		this._requisiteEditor.open(requisite, options);
	}

	isViewMode()
	{
		return this._editor.getMode() === BX.Crm.EntityEditorMode.view;
	}

	setRequisiteInitAddrData(requisite)
	{
		let requisiteId = requisite.getRequisiteId();
		let rawRequisite = this._requisiteInitData
			.filter((item) => item.requisiteId === requisiteId)
			.reduce((prev, current) => current, null);

		if (Type.isPlainObject(rawRequisite))
		{
			try
			{
				let requisiteData = JSON.parse(rawRequisite.requisiteData);
				let requisiteFields = BX.prop.getObject(requisiteData, 'fields', {});
				let addressData = BX.prop.getObject(requisiteFields, 'RQ_ADDR', null);
				requisite.setInitialAddressData(addressData);
			}
			catch (e)
			{
				requisite.setInitAddrData(null);
			}
		}
	}

	getFirstEditModeField()
	{
		if (this._requisiteField && this._requisiteField._mode === BX.Crm.EntityEditorMode.edit)
		{
			return this._requisiteField;
		}
		if (this._addressField && this._addressField._mode === BX.Crm.EntityEditorMode.edit)
		{
			return this._addressField;
		}
		return null;
	}

	updateRequisiteFieldModel()
	{
		let modelValue = this._requisiteList.exportToModel();
		if (this._requisiteField)
		{
			this._model.setField(this._requisiteFieldId, modelValue);
		}
		this.saveRequisiteInitData([...modelValue]);
	}

	getRequisiteFieldValue()
	{
		if (!this._requisiteFieldId)
		{
			return [];
		}
		return this._model.getField(this._requisiteFieldId, []);
	}

	addEditorFormInputs()
	{
		this._formInputsWrapper = Tag.render`<div></div>`;
		Dom.append(this._formInputsWrapper, this._editor.getFormElement());

		for (let requisite of this._requisiteList.getListWithDeleted())
		{
			if (requisite.isDeleted())
			{
				Dom.append(Tag.render`<input type="hidden" name="REQUISITES[${requisite.getRequisiteId()}][DELETED]" value="Y" >`, this._formInputsWrapper);
				continue;
			}
			if (!requisite.isChanged())
			{
				continue;
			}
			let dataInput = Tag.render`<input type="hidden" name="REQUISITES[${requisite.getRequisiteId()}][DATA]" value="${Tag.safe`${requisite.getRequisiteData()}`}" >`;
			let signInput = Tag.render`<input type="hidden" name="REQUISITES[${requisite.getRequisiteId()}][SIGN]" value="${requisite.getRequisiteDataSign()}" >`;
			Dom.append(dataInput, this._formInputsWrapper);
			Dom.append(signInput, this._formInputsWrapper);
		}
	}

	removeEditorFormInputs()
	{
		if (Type.isDomNode(this._formInputsWrapper))
		{
			Dom.remove(this._formInputsWrapper);
			this._formInputsWrapper = null;
		}
	}

	markFieldsAsChanged()
	{
		if (this._requisiteField)
		{
			this._requisiteField.markAsChanged();
		}
		if (this._addressField)
		{
			this._addressField.markAsChanged();
		}
	}

	rollback()
	{
		this.initRequisiteList();
		this.initRequisiteField();
		this.initAddressField();
		this.updateRequisiteFieldModel();
	}

	isLoading()
	{
		return !!this._isLoading;
	}

	startLoading()
	{
		this._isLoading = true;
	}

	stopLoading()
	{
		this._isLoading = false;
	}

	showError(errorMessage)
	{
		MessageBox.alert(errorMessage, Loc.getMessage('REQUISITE_LIST_ITEM_ERROR_CAPTION'));
	}

	prepareRequisiteByEventData(eventData)
	{
		let requisite = this._requisiteList.getById(eventData.id);
		if (!requisite)
		{
			const extraFields = {
				selected: this._requisiteList.isEmpty(),
				presetId: eventData.defaultPresetId,
			};

			if (Type.isPlainObject(eventData.data))
			{
				if (eventData.data.title)
				{
					extraFields.title = eventData.data.title;
				}
				if (eventData.data.subtitle)
				{
					extraFields.subtitle = eventData.data.subtitle;
				}
			}

			if (eventData.hasOwnProperty('autocompleteState'))
			{
				extraFields.autocompleteState = eventData.autocompleteState;
			}

			requisite = RequisiteListItem.create(null, {
				'newRequisiteId': this._requisiteList.getNewRequisiteId(),
				'newRequisiteExtraFields': extraFields,
			});
		}
		else
		{
			if (eventData.hasOwnProperty('autocompleteState'))
			{
				requisite.setAutocompleteState(eventData.autocompleteState);
			}
			if (Type.isPlainObject(eventData.data))
			{
				if (eventData.data.title)
				{
					requisite._data.title = eventData.data.title;
				}
				if (eventData.data.subtitle)
				{
					requisite._data.subtitle = eventData.data.subtitle;
				}
			}
		}
		return requisite;
	}

	getDefaultPresetId()
	{
		if (this._requisiteField)
		{
			return this._requisiteField.getDefaultPresetId();
		}
		else // if requisiteField is hidden
		{
			let schemeElement = this._editor.getScheme().getAvailableElements()
				.filter((item) => (item.getName() === this._requisiteFieldId))
				.reduce((prev, current) => current, null);

			if (!schemeElement)
			{
				return null;
			}

			for (let preset of BX.prop.getArray(schemeElement.getData(), "presets", []))
			{
				if (preset.IS_DEFAULT)
				{
					return preset.VALUE;
				}
			}
		}
		return null;
	}

	signRequisiteFields(requisite)
	{
		this.setRequisiteInitAddrData(requisite);
		this._requisiteEditor.setMode(this._editor.getMode());
		return this._requisiteEditor.getSignRequisitePromise(requisite);
	}

	release()
	{
		if (this._requisiteEditor)
		{
			this._requisiteEditor.release();
		}
	}

	onFieldInit(event)
	{
		let eventData = event.getData();
		let field = eventData.field;
		if (field)
		{
			let fieldId = field.getId();
			if (Type.isStringFilled(this._requisiteFieldId) && fieldId === this._requisiteFieldId)
			{
				this._requisiteField = field;
				this.initRequisiteField();

				EventEmitter.subscribe(this._requisiteField, 'onEditNew', this.onEditNewRequisite.bind(this));
				EventEmitter.subscribe(this._requisiteField, 'onEditExisted', this.onEditExistedRequisite.bind(this));
				EventEmitter.subscribe(this._requisiteField, 'onFinishAutocomplete', this.onFinishRequisiteAutocomplete.bind(this));
				EventEmitter.subscribe(this._requisiteField, 'onClearAutocomplete', this.onClearRequisiteAutocomplete.bind(this));
				EventEmitter.subscribe(this._requisiteField, 'onSetDefault', this.onSetDefaultRequisite.bind(this));
				EventEmitter.subscribe(this._requisiteField, 'onDelete', this.onDeleteRequisite.bind(this));
				EventEmitter.subscribe(this._requisiteField, 'onHide', this.onHideRequisite.bind(this));
			}

			if (Type.isStringFilled(this._addressFieldId) && fieldId === this._addressFieldId)
			{
				this._addressField = field;
				this.initAddressField();

				EventEmitter.subscribe(this._addressField, 'onAddressListUpdate', this.onAddressListUpdate.bind(this));
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
		this.openEditor(requisite);
	}

	onEditExistedRequisite(event)
	{
		let params = event.getData();
		let requisite = this._requisiteList.getById(params.id);
		if (requisite)
		{
			let options = BX.prop.getObject(params, 'options', {});
			if (Type.isPlainObject(options.autocompleteState))
			{
				requisite.setAutocompleteState(options.autocompleteState);
			}
			let editorOptions = {};
			if (Type.isPlainObject(options.editorOptions))
			{
				editorOptions = options.editorOptions;
			}
			this.openEditor(requisite, editorOptions);
		}
	}

	onClearRequisiteAutocomplete(event)
	{
		let params = event.getData();
		let requisite = this._requisiteList.getById(params.id);
		if (requisite)
		{
			requisite.clearFormData();
		}
	}

	onFinishRequisiteAutocomplete(event)
	{
		const eventData = event.getData();
		const requisite = this.prepareRequisiteByEventData(eventData);
		const formData = BX.prop.getObject(BX.prop.getObject(eventData, 'data', {}), 'fields', {});
		if (formData.hasOwnProperty('RQ_ADDR'))
		{
			if (Type.isPlainObject(formData.RQ_ADDR))
			{
				let oldAddr = requisite.getAddressList();
				oldAddr = Type.isPlainObject(oldAddr) ? oldAddr : {};

				const addr = {...oldAddr, ...formData.RQ_ADDR};
				requisite.setAddressData(addr);
				requisite.setAddressList(addr);
			}
			delete (formData.RQ_ADDR);
		}
		requisite.setFormData(formData);
		requisite.setChanged(true);
		requisite.setDeleted(false);
		const presetId = BX.prop.getInteger(formData, 'PRESET_ID', 0);
		if (presetId > 0)
		{
			requisite.setPresetId(presetId);
		}
		const presetCountryId = BX.prop.getInteger(formData, 'PRESET_COUNTRY_ID', 0);
		if (presetCountryId > 0)
		{
			requisite.setPresetCountryId(presetCountryId);
		}
		if (this._requisiteList.indexOf(requisite) < 0)
		{
			this._requisiteList.add(requisite);
		}
		else
		{
			this._requisiteList.notifyListChanged();
		}
		if (requisite.isAddressOnly())
		{
			this._requisiteList.unhide(requisite);
		}
	}

	onSetDefaultRequisite(event)
	{
		const eventData = event.getData();
		const id = eventData.id;
		const bankDetailId = eventData.bankDetailId;

		if (this._addressField && this._addressField.isChanged())
		{
			this._editor.cancel();
			return false; // have to save address first
		}

		this.setSelectedRequisite(id, bankDetailId);
		this.updateRequisiteFieldModel();

		return true;
	}

	onDeleteRequisite(event)
	{
		let params = event.getData();
		let requisite = this._requisiteList.getById(params.id);
		if (requisite)
		{
			this.setRequisiteInitAddrData(requisite);
		}
		if (params.postponed)
		{
			this._requisiteList.removePostponed(requisite);
		}
		else
		{
			this._requisiteEditor.setMode(this._editor.getMode());
			this._requisiteEditor.deleteRequisite(params.id);
		}
	}

	onHideRequisite(event)
	{
		let params = event.getData();
		let requisite = this._requisiteList.getById(params.id);
		if (requisite)
		{
			this._requisiteList.hide(requisite);
		}
	}

	onChangeRequisites()
	{
		this.initAddressField();
	}

	onAddressListUpdate(event)
	{
		let eventData = event.getData();
		eventData.id = this._requisiteList.getSelectedId();

		eventData.defaultPresetId = this.getDefaultPresetId();

		let requisite = this.prepareRequisiteByEventData(eventData);

		let addresses = {};
		let isEmptyAddress = true;
		for (let address of eventData.value)
		{
			addresses[address.type] = address.value;
			if (Type.isStringFilled(address.value))
			{
				isEmptyAddress = false;
			}
		}

		requisite.setAddressData(addresses);
		requisite.setAddressList(addresses);

		requisite.setChanged(true);
		if (!isEmptyAddress)
		{
			requisite.setDeleted(false);
		}

		if (this._requisiteList.indexOf(requisite) < 0)
		{
			if (!isEmptyAddress)
			{
				requisite.setAddressOnly(true);
				this._requisiteList.add(requisite);
			}
		}
		else
		{
			//  remove requisite if address is empty and requisite contain only address
			if (isEmptyAddress && requisite.isAddressOnly())
			{
				this._requisiteList.removePostponed(requisite);
			}
			this._requisiteList.notifyListChanged();
		}
	}

	onBeforeSubmit()
	{
		super.onBeforeSubmit();
		this.addEditorFormInputs();
	}

	onBeforesSaveControl(data)
	{
		if (!data.hasOwnProperty('REQUISITES'))
		{
			data['REQUISITES'] = {};
		}
		for (let requisite of this._requisiteList.getListWithDeleted())
		{
			if (!requisite.isChanged() && !requisite.isDeleted())
			{
				continue;
			}
			let requisiteData = {};
			if (requisite.isDeleted())
			{
				requisiteData['DELETED'] = 'Y';
			}
			else
			{
				requisiteData['DATA'] = requisite.getRequisiteData();
				requisiteData['SIGN'] = requisite.getRequisiteDataSign();
			}
			data['REQUISITES'][requisite.getRequisiteId()] = requisiteData;
		}

		if (this._enableRequisiteSelection)
		{
			const selectedRequisite = this._requisiteList.getSelected();
			let selectedRequisiteId = null;
			let selectedBankDetailId = null;
			if (selectedRequisite)
			{
				selectedRequisiteId = selectedRequisite.getRequisiteId();
				const selectedBankDetail = selectedRequisite.getBankDetailById(selectedRequisite.getSelectedBankDetailId());
				selectedBankDetailId = Type.isNull(selectedBankDetail) ? null : selectedBankDetail.id;
			}

			data['REQUISITES']['BINDING'] = {
				requisiteId: selectedRequisiteId,
				bankDetailId: selectedBankDetailId,
			};
		}

		return data;
	}

	onAfterSave()
	{
		super.onAfterSave();
		this.saveRequisiteInitData(this.getRequisiteFieldValue());

		this.initRequisiteList();
		this.initRequisiteField();
		this.initAddressField();

		this.removeEditorFormInputs();
	}

	onRequisiteEditorAfterEdit()
	{
		if (this.isViewMode())
		{
			this.updateRequisiteFieldModel();
		}
		this.markFieldsAsChanged();
	}

	onRequisiteEditorAfterDelete(event)
	{
		let data = event.getData();
		let isEmptyRequisitesList = this._requisiteList.isEmpty();
		if (!isEmptyRequisitesList && data.selectedRemoved)
		{
			// set new default requisite
			this.setSelectedRequisite(0, 0);
		}
		this.updateRequisiteFieldModel();
	}

	static create(id, settings)
	{
		let self = new EntityEditorRequisiteController();
		self.initialize(id, settings);
		return self;
	}
}