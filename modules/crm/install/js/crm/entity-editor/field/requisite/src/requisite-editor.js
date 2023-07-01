import {RequisiteListItem} from "./requisite-list";
import {EventEmitter} from "main.core.events";
import {Loc, Type} from "main.core";
import {MessageBox} from "ui.dialogs.messagebox";

export class EntityEditorRequisiteEditor
{
	constructor()
	{
		this._requisiteList = null;
		this._entityTypeId = null;
		this._entityId = null;
		this._entityCategoryId = null;
		this._permissionToken = null;
		this._contextId = null;
		this._mode = BX.UI.EntityEditorMode.view;

		this.currentSliderRequisiste = null;
	}

	initialize(id, settings)
	{
		this._entityTypeId = BX.prop.getInteger(settings, 'entityTypeId', 0);
		this._entityId = BX.prop.getInteger(settings, 'entityId', 0);
		this._entityCategoryId = BX.prop.getInteger(settings, 'entityCategoryId', null);
		this._contextId = BX.prop.getString(settings, 'contextId', "");
		this._requisiteEditUrl = BX.prop.getString(settings, 'requisiteEditUrl', "");
		this._permissionToken = BX.prop.getString(settings, 'permissionToken', null);

		this._onExternalEventListener = this.onExternalEvent.bind(this);
		EventEmitter.subscribe('onLocalStorageSet', this._onExternalEventListener);
	}

	setRequisiteList(requisiteList)
	{
		this._requisiteList = requisiteList;
	}

	setMode(mode)
	{
		this._mode = mode;
	}

	open(requisite, options = {})
	{
		if (!(requisite instanceof RequisiteListItem))
		{
			return;
		}
		this.currentSliderRequisiste = requisite;

		let sliderOptions = {
			width: 950,
			cacheable: false,
			allowChangeHistory: false,
			requestMethod: 'post',
			requestParams: this.prepareSliderRequestParams(requisite, options)
		};
		BX.Crm.Page.openSlider(this.getSliderUrl(requisite), sliderOptions);
	}

	deleteRequisite(id)
	{
		let requisite = this._requisiteList.getById(id);
		if (requisite)
		{
			let postData = {...this.prepareSliderRequestParams(requisite)};
			postData.sessid = BX.bitrix_sessid();
			postData.mode = 'delete';
			postData.ACTION = 'SAVE';

			BX.ajax.post(this.getSliderUrl(requisite), postData, (data) =>
			{
				try
				{
					let json = JSON.parse(data);
					if (Type.isStringFilled(json.ERROR))
					{
						this.showError(json.ERROR);
					}
					else
					{
						let selectedRequisite = this._requisiteList.getSelected();
						let selectedRemoved = (selectedRequisite === requisite);
						this._requisiteList.remove(requisite);

						EventEmitter.emit(this, 'onAfterDeleteRequisite', {selectedRemoved});
					}
				}
				catch (e)
				{
				}
			});
		}
	}

	showError(errorMessage)
	{
		MessageBox.alert(errorMessage, Loc.getMessage('REQUISITE_LIST_ITEM_ERROR_CAPTION'));
	}

	getSliderUrl(requisite)
	{
		let requisiteId = requisite.getRequisiteId();

		let urlParams =
			{
				etype: this._entityTypeId,
				eid: this._entityId,
				external_context_id: this._contextId
			};

		let presetId = requisite.getPresetId();
		if (presetId > 0)
		{
			urlParams["pid"] = presetId;
		}
		if (!Type.isNull(this._entityCategoryId))
		{
			urlParams.cid = this._entityCategoryId;
		}

		return BX.util.add_url_param(
			this.getRequisiteEditUrl(requisiteId),
			urlParams
		);
	}

	prepareSliderRequestParams(requisite, options = {})
	{
		let requestParams = {};
		let requisiteData = requisite.getRequisiteData();
		if (requisite.isChanged() && Type.isString(requisiteData) && requisiteData.length)
		{
			requestParams['externalData'] = {
				'data': requisiteData,
				'sign': requisite.getRequisiteDataSign()
			};
		}
		if (requisite.isSelected())
		{
			let autocompleteState = requisite.getAutocompleteState();
			if (Object.keys(autocompleteState).length)
			{
				requestParams['AUTOCOMPLETE'] = JSON.stringify(autocompleteState);
				requestParams['useFormData'] = 'Y';
			}
		}
		if (!requisite.isEmptyFormData())
		{
			requestParams = {...requestParams, ...requisite.getFormData()};
		}
		if (!requisite.isEmptyAddressData())
		{
			requestParams = {...requestParams, ...{RQ_ADDR: requisite.getAddressesForSave()}};
		}
		requestParams['mode'] = requisite.isNew() ? 'create' : 'edit';
		if (this.isViewMode())
		{
			requestParams['doSave'] = 'Y';
		}
		if (BX.prop.getBoolean(options, 'addBankDetailsItem', false))
		{
			requestParams['addBankDetailsItem'] = 'Y';
		}
		let overriddenPresetId = BX.prop.getInteger(options, 'overriddenPresetId', 0);
		if (overriddenPresetId > 0)
		{
			requestParams['PRESET_ID'] = overriddenPresetId;
			requestParams['useFormData'] = 'Y';
		}
		if (!Type.isNull(this._permissionToken))
		{
			requestParams['permissionToken'] = this._permissionToken;
		}

		return requestParams;
	}

	getRequisiteEditUrl(requisiteId)
	{
		return this._requisiteEditUrl.replace(/#requisite_id#/gi, requisiteId);
	}

	getSignRequisitePromise(requisite)
	{
		let postData = this.prepareSliderRequestParams(requisite);
		postData.sessid = BX.bitrix_sessid();
		postData.PRESET_ID = requisite.getPresetId();
		postData.useFormData = 'Y';
		postData.ACTION = 'SAVE';

		return BX.ajax.promise({
			method: 'post',
			dataType: 'json',
			url: this.getSliderUrl(requisite),
			data: postData
		});
	}

	isViewMode()
	{
		return this._mode === BX.UI.EntityEditorMode.view;
	}

	release()
	{
		EventEmitter.unsubscribe('onLocalStorageSet', this._onExternalEventListener);
	}

	onExternalEvent(event)
	{
		let dataArray = event.getData();
		if (!Type.isArray(dataArray))
		{
			return;
		}
		let data = dataArray[0];

		let eventName = BX.prop.getString(data, "key", "");

		if (eventName !== "BX.Crm.RequisiteSliderDetails:onCancelEdit" && eventName !== "BX.Crm.RequisiteSliderDetails:onSave")
		{
			return;
		}
		let value = BX.prop.getObject(data, "value", {});

		let contextId = BX.prop.getString(value, "contextId", "");
		if (contextId !== this._contextId)
		{
			return;
		}

		if (eventName === "BX.Crm.RequisiteSliderDetails:onCancelEdit")
		{
			this.currentSliderRequisiste = null;
		}
		if (eventName === "BX.Crm.RequisiteSliderDetails:onSave")
		{
			let requisite = this.currentSliderRequisiste;
			if (Type.isObject(requisite))
			{
				if (Type.isString(value.requisiteData))
				{
					requisite.setRequisiteData(value.requisiteData, value.requisiteDataSign);
					requisite.setAutocompleteState({});
					requisite.clearFormData();
					requisite.clearAddressData();
				}

				if (Type.isString(value.presetId))
				{
					requisite.setPresetId(value.presetId);
				}
				if (Type.isString(value.presetCountryId))
				{
					requisite.setPresetCountryId(value.presetCountryId);
				}
				if (this.isViewMode())
				{
					requisite.setNew(false);
				}
				else
				{
					requisite.setChanged(true);
				}
				requisite.setDeleted(false);

				if (this._requisiteList.indexOf(requisite) < 0)
				{
					this._requisiteList.add(requisite);
				}
				else
				{
					this._requisiteList.notifyListChanged();
				}

				this.currentSliderRequisiste = null;

				EventEmitter.emit(this, 'onAfterEditRequisite');
			}
		}
	}

	static create(id, settings)
	{
		let self = new EntityEditorRequisiteEditor();
		self.initialize(id, settings);
		return self;
	}

}