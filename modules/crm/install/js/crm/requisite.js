BX.namespace("BX.Crm");

BX.Crm.RequisitePresetSelectorClass = (function ()
{
	var RequisitePresetSelectorClass = function (parameters)
	{
		this.id = BX.type.isNotEmptyString(parameters["id"]) ? parameters["id"] : "";
		if(this.id === "")
		{
			//For compatibility only
			this.id = BX.type.isNotEmptyString(parameters["containerId"])
				? parameters["containerId"] : "crm_requisite_preset_sel";
		}

		this.editor = parameters.editor;
		this.container = parameters.container;
		this.nextNode = BX.type.isElementNode(parameters.nextNode) ? parameters.nextNode : null;
		this.position = BX.type.isNotEmptyString(parameters.position) ? parameters.position : "";
		this.content = null;
		this.requisiteEntityTypeId = parameters.requisiteEntityTypeId;
		this.requisiteEntityId = parameters.requisiteEntityId;
		this.presetList = parameters.presetList;
		this.presetLastSelectedId = parseInt(parameters.presetLastSelectedId);
		this.ajaxUrl = "/bitrix/components/bitrix/crm.requisite.edit/settings.php";
		this.requisiteEditHandler = parameters.requisiteEditHandler;
		this.curPreset = {
			"id": 0,
			"title": ""
		};
		this.curPresetName = "";
		this.labelElement = null;
		this.buttonElement = null;

		this._menuId = "ps_menu_" + this.id;
		this._isMenuShown = false;
		this._buttonClickHandler = BX.delegate(this.onButtonClick, this);
		this._menuIiemClickHandler = BX.delegate(this.onMenuItemClick, this);
		this._menuCloseHandler = BX.delegate(this.onMenuClose, this);

		if (this.presetList.length > 0)
		{
			if (this.presetLastSelectedId <= 0)
				this.curPreset = this.presetList[0];
			else
			{
				for (var i = 0; i < this.presetList.length; i++)
				{
					if (this.presetList[i]["id"] && this.presetList[i]["id"] == this.presetLastSelectedId)
					{
						this.curPreset = this.presetList[i];
						break;
					}
				}
				if (this.curPreset["id"] === 0)
				{
					this.curPreset = this.presetList[0];
					setTimeout(BX.delegate(this.saveLastSelectedPresetId, this), 100);
				}
			}
		}

		this.buildContent();
	};

	RequisitePresetSelectorClass.prototype = {
		buildContent: function()
		{
			if (this.container)
			{
				this.labelElement = BX.create("SPAN", {"text": this.curPreset["title"]});
				this.buttonElement = BX.create("SPAN", { attrs: { className: "crm-offer-requisite-option-arrow" } });

				this.content = BX.create("SPAN",
					{
						attrs: { className: "crm-offer-requisite-option"},
						children:
						[
							BX.create("SPAN",
								{
									attrs: { className: "crm-offer-requisite-option-caption" },
									text: this.getMessage("presetSelectorText") + ":"
								}
							),
							BX.create("SPAN",
								{
									attrs:
									{
										className: "crm-offer-requisite-option-text",
										title: this.getMessage("presetSelectorTitle")
									},
									events: { click: BX.delegate(this.onSelectorClick, this) },
									children: [ BX.create("SPAN", { children: [ this.labelElement ] }) ]
								}
							),
							this.buttonElement
						]
					}
				);

				this.ajust();

				if (this.buttonElement)
				{
					if (this.presetList.length > 1)
					{
						BX.bind(this.buttonElement, "click", this._buttonClickHandler);
					}
					else
					{
						this.buttonElement.style.display = "none";
					}
				}
			}
		},
		selectPreset: function(item)
		{
			if (this.labelElement)
			{
				if (this.curPreset["id"] != item["id"])
				{
					this.curPreset = item;
					this.presetLastSelectedId = item["id"];
					BX.setTextContent(this.labelElement, item["title"]);
					setTimeout(BX.delegate(this.saveLastSelectedPresetId, this), 100);
				}
			}
		},
		getMessage: function(msgId)
		{
			return this.editor ? this.editor.getMessage(msgId) : msgId;
		},
		getNextNode: function()
		{
			return this.nextNode;
		},
		setNextNode: function(node)
		{
			node = BX.type.isElementNode(node) ? node : null;
			if(this.nextNode !== node)
			{
				this.nextNode = node;
				this.ajust();
			}
		},
		ajust: function()
		{
			if (!this.container)
			{
				return;
			}

			if(this.container === this.content.parentNode)
			{
				this.container.removeChild(this.content);
			}

			if(this.position === "top")
			{
				if(this.container.firstChild)
				{
					this.container.insertBefore(this.content, this.container.firstChild);
				}
				else
				{
					this.container.appendChild(this.content);
				}
			}
			else
			{
				if (this.container && this.nextNode)
				{
					this.container.insertBefore(this.content, this.nextNode);
				}
				else
				{
					this.container.appendChild(this.content);
				}
			}
		},
		showError: function(msg)
		{
			alert(msg);
		},
		onButtonClick: function(e)
		{
			this.showMenu();
		},
		showMenu: function()
		{
			if(this._isMenuShown)
			{
				return;
			}

			var menuItems = [];
			for(var i = 0; i < this.presetList.length; i++)
			{
				var item = this.presetList[i];

				menuItems.push(
					{
						text: BX.util.htmlspecialchars(item["title"]),
						value: item["id"],
						href : "#",
						className: "crm-convert-item",
						onclick: this._menuIiemClickHandler
					}
				);
			}

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._menuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._menuId];
			}

			var anchor = this.buttonElement;
			var anchorPos = BX.pos(anchor);

			BX.PopupMenu.show(
				this._menuId,
				anchor,
				menuItems,
				{
					autoHide: true,
					offsetLeft: (anchorPos["width"] / 2),
					angle: { position: "top", offset: 0 },
					events: { onPopupClose : this._menuCloseHandler }
				}
			);

			this._isMenuShown = true;
		},
		closeMenu: function()
		{
			if(!this._isMenuShown)
			{
				return;
			}

			BX.PopupMenu.destroy(this._menuId);
			this._isMenuShown = false;
		},
		onMenuClose: function()
		{
			this._isMenuShown = false;
		},
		onMenuItemClick: function(e, item)
		{
			var curPreset = {
				"id": 0,
				"title": ""
			};
			for (var i = 0; i < this.presetList.length; i++)
			{
				if (this.presetList[i]["id"] && this.presetList[i]["id"] == item["value"])
				{
					curPreset = this.presetList[i];
					break;
				}
			}
			this.selectPreset(curPreset);
			this.closeMenu();
			return BX.PreventDefault(e);
		},
		onSelectorClick: function(e)
		{
			this.closeMenu();
			if (this.requisiteEditHandler)
			{
				if (parseInt(this.curPreset["id"]) <= 0)
				{
					this.showError(this.getMessage("errPresetNotSelected"));
				}
				else
				{
					this.requisiteEditHandler(
						this.requisiteEntityTypeId,
						this.requisiteEntityId,
						this.curPreset["id"],
						0
					);
				}
			}
			return BX.PreventDefault(e);
		},
		saveLastSelectedPresetId: function()
		{
			var url = BX.util.add_url_param(this.ajaxUrl, {sessid: BX.bitrix_sessid()});
			var data = {
				"action": "savelastselectedpreset",
				"requisiteEntityTypeId": this.requisiteEntityTypeId,
				"presetId": this.presetLastSelectedId
			};
			BX.ajax.post(url, data);
		}
	};

	return RequisitePresetSelectorClass;
})();

BX.Crm.RequisitePopupFormManagerClass = (function ()
{
	var RequisitePopupFormManagerClass = function (parameters)
	{
		this._random = Math.random().toString().substring(2);
		this._index = "RequisitePopupFormManager_" + this._random;
		this.editor = parameters.editor;
		this.blockArea = parameters.blockArea;
		this.requisiteEntityTypeId = parameters.requisiteEntityTypeId;
		this.requisiteEntityId = parameters.requisiteEntityId;
		this.requisiteId = parameters.requisiteId;
		this.requisiteData = parameters.requisiteData;
		this.requisiteDataSign = parameters.requisiteDataSign;
		this.presetId = parameters.presetId;
		this.multiAddressEditor = null;
		this.requisiteAjaxUrl =  BX.type.isNotEmptyString(parameters.requisiteAjaxUrl) ? parameters.requisiteAjaxUrl : "";
		this.requisitePopupAjaxUrl = parameters.requisitePopupAjaxUrl;
		this.isRequestRunning = false;
		this.wrapper = null;
		this.popup = null;
		this.saveButton = null;
		this.cancelButton = null;
		this.formId = "";
		this.formSettingManager = null;
		this.formCreateHandler = BX.delegate(this.onFormCreate, this);
		this.editorPopupDestroyCallback = parameters.popupDestroyCallback;
		this.blockIndex =
			(BX.type.isNumber(parameters.blockIndex) && parameters.blockIndex >= 0
			|| BX.type.isNotEmptyString(parameters.blockIndex)) ? parseInt(parameters.blockIndex) : -1;
		this.afterRequisiteEditCallback = parameters.afterRequisiteEditCallback;
		this.copyMode = !!parameters.copyMode;
		this.readOnlyMode = !!parameters.readOnlyMode;
		this.saveBtnClickLockObject = null;
		this.doSaveHandler = BX.delegate(this.onDoSave, this);

		this.register();
	};

	RequisitePopupFormManagerClass.prototype = {
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		getMessage: function(msgId)
		{
			return this.editor ? this.editor.getMessage(msgId) : msgId;
		},
		getFieldControl: function(fieldName)
		{
			var ctrls = document.getElementsByName(fieldName);
			return ctrls.length > 0 ? ctrls[0] : null;
		},
		setFormId: function(formId)
		{
			this.formId = BX.type.isNotEmptyString(formId) ? formId : "";
		},
		getFormId: function()
		{
			return this.formId;
		},
		setFieldValue: function(fieldName, val)
		{
			var ctrl = this.getFieldControl(fieldName);
			if(ctrl !== null)
			{
				ctrl.value = val;
			}
		},
		setupFields: function(fields)
		{
			var inputs = document.querySelectorAll('input[type="text"][data-requisite="field"],textarea[data-requisite="field"]');
			for(var n = 0; n < inputs.length; n++)
			{
				inputs[n].value = "";
			}

			for(var i in fields)
			{
				if(!fields.hasOwnProperty(i))
				{
					continue;
				}

				if(i !== "RQ_ADDR")
				{
					this.setFieldValue(i, fields[i]);
				}
				else if(this.multiAddressEditor)
				{
					var addressData = fields[i];
					for(var j in addressData)
					{
						if(!addressData.hasOwnProperty(j))
						{
							continue;
						}

						var address = addressData[j];
						var addressTypeId = parseInt(j);
						var addressEditor = this.multiAddressEditor.getItemByTypeId(addressTypeId);
						if(addressEditor === null)
						{
							addressEditor = this.multiAddressEditor.createItem(addressTypeId, this.formId);
						}

						addressEditor.setup(address);
					}
				}
			}
		},
		openPopup: function()
		{
			if(!this.popup)
			{
				this.startLoadRequest();
			}
		},
		closePopup: function()
		{
			if(this.popup)
			{
				this.popup.close();
			}
		},
		reloadPopup: function()
		{
			if(this.popup)
			{
				this.startReloadRequest();
			}
		},
		startLoadRequest: function()
		{
			if(this.isRequestRunning)
			{
				return;
			}
			this.isRequestRunning = true;
			var urlParams = "";
			if (this.requisiteId > 0)
			{
				urlParams += "&etype=" +
					BX.util.urlencode((this.requisiteEntityTypeId > 0) ? this.requisiteEntityTypeId : 0) +
					"&eid=" + BX.util.urlencode((this.requisiteEntityId > 0) ? this.requisiteEntityId : 0) +
					"&requisite_id=" + BX.util.urlencode(this.requisiteId);
				if (this.copyMode)
					urlParams += "&copy=1";
			}
			else
			{
				urlParams += "&etype=" +
					BX.util.urlencode((this.requisiteEntityTypeId > 0) ? this.requisiteEntityTypeId : 0) +
					"&eid=" + BX.util.urlencode((this.requisiteEntityId > 0) ? this.requisiteEntityId : 0) +
					"&pid=" + BX.util.urlencode((this.presetId > 0) ? this.presetId : 0) +
					"&requisite_data=" +
					BX.util.urlencode((BX.type.isNotEmptyString(this.requisiteData)) ? this.requisiteData : "") +
					"&requisite_data_sign=" +
					BX.util.urlencode((BX.type.isNotEmptyString(this.requisiteDataSign)) ? this.requisiteDataSign : "");
			}
			if (BX.type.isNotEmptyString(this._index))
				urlParams += "&popup_manager_id=" + this._index;

			BX.ajax(
				{
					url: this.requisitePopupAjaxUrl + urlParams,
					method: "POST",
					dataType: "html",
					data: {},
					prepareData: true,
					onsuccess: BX.delegate(this.onLoadRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		startReloadRequest: function()
		{
			if(this.isRequestRunning)
			{
				return;
			}
			this.isRequestRunning = true;

			var form = this.formSettingManager.getForm();
			form.appendChild(
				BX.create("INPUT",
					{
						props: { type: "hidden", name: "reload", value: "Y" }
					}
				)
			);

			var urlParams = "";
			if (this.requisiteId > 0)
			{
				urlParams += "&requisite_id=" + BX.util.urlencode(this.requisiteId);
				if (this.copyMode)
					urlParams += "&copy=1";
			}
			else
			{
				urlParams += "&etype=" +
					BX.util.urlencode((this.requisiteEntityTypeId > 0) ? this.requisiteEntityTypeId : 0) +
					"&eid=" + BX.util.urlencode((this.requisiteEntityId > 0) ? this.requisiteEntityId : 0) +
					"&pid=" + BX.util.urlencode((this.presetId > 0) ? this.presetId : 0);
			}
			if (BX.type.isNotEmptyString(this._index))
				urlParams += "&popup_manager_id=" + this._index;

			BX.ajax.submitAjax(
				form,
				{
					url: this.requisitePopupAjaxUrl + urlParams,
					method: "POST",
					data: {},
					onsuccess: BX.delegate(this.onReloadRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		startFormSubmitRequest: function(e)
		{
			if(this.isRequestRunning)
			{
				return;
			}
			this.isRequestRunning = true;

			var form = this.formSettingManager.getForm();
			form["save"] = form.appendChild(
				BX.create("INPUT",
					{
						props: { type: "hidden", name: "save", value: "Y" }
					}
				)
			);

			var urlParams = "";
			if (this.requisiteId > 0)
			{
				urlParams += "&requisite_id=" + BX.util.urlencode(this.requisiteId);
				if (this.copyMode)
					urlParams += "&copy=1";
			}
			else
			{
				urlParams += "&etype=" +
					BX.util.urlencode((this.requisiteEntityTypeId > 0) ? this.requisiteEntityTypeId : 0) +
					"&eid=" + BX.util.urlencode((this.requisiteEntityId > 0) ? this.requisiteEntityId : 0) +
					"&pid=" + BX.util.urlencode((this.presetId > 0) ? this.presetId : 0);
			}
			if (BX.type.isNotEmptyString(this._index))
				urlParams += "&popup_manager_id=" + this._index;

			BX.ajax.submitAjax(
				form,
				{
					url: this.requisitePopupAjaxUrl + urlParams,
					method: "POST",
					onsuccess: BX.delegate(this.onFormSubmitRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		onLoadRequestSuccess: function(data)
		{
			this.isRequestRunning = false;

			BX.addCustomEvent(window, "CrmRequisiteEditFormCreate", this.formCreateHandler);
			BX.addCustomEvent(window, "CrmFormSettingManagerCreate", BX.delegate(this.onFormManagerCreate, this));

			this.popup = new BX.PopupWindow(
				"test_form_popup",
				null,
				{
					overlay: {opacity: 82},
					autoHide: false,
					draggable: true,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: false },
					closeByEsc: false,
					closeIcon: { top: "10px", right: "15px" },
					zIndex: 996 - 1100,
					titleBar: this.getMessage("popupTitle"),
					events:
					{
						onPopupShow: BX.delegate(this.opPopupShow, this),
						onPopupClose: BX.delegate(this.opPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					content: this.preparePopupContent(data),
					buttons: this.prepareButtons()
				}
			);

			this.popup.show();
		},
		onReloadRequestSuccess: function(data)
		{
			this.isRequestRunning = false;
			if(this.wrapper)
			{
				this.wrapper.innerHTML = data;
			}
		},
		onFormSubmitRequestSuccess: function(data)
		{
			BX.onCustomEvent("CrmRequisitePopupFormManagerClosePopup", [this]);
			this.setFormId("");
			BX.addCustomEvent(window, "CrmRequisiteEditFormCreate", this.formCreateHandler);

			this.isRequestRunning = false;

			if(!this.wrapper)
				return;

			this.wrapper.innerHTML = data;

			var response = null, hiddenRequisiteId = null, requisiteId = 0,
				requisiteDataNode = null, requisiteDataSignNode = null, needClosePopup = false;

			if (response = BX(this._index.toString() + "_response"))
			{
				if (hiddenRequisiteId = BX.findChild(
						response,
						{"tag": "input", "attr": {"type": "hidden", "name": "REQUISITE_ID"}},
						false, false))
				{
					requisiteId = parseInt(hiddenRequisiteId.value);
					if (requisiteDataNode = BX.findChild(
							response,
							{"tag": "input", "attr": {"type": "hidden", "name": "REQUISITE_DATA"}},
							false, false))
					{
						if (requisiteDataSignNode = BX.findChild(
								response,
								{"tag": "input", "attr": {"type": "hidden", "name": "REQUISITE_DATA_SIGN"}},
								false, false))
						{
							if (requisiteDataNode.value && requisiteDataSignNode.value)
							{
								var requisiteData = requisiteDataNode.value;
								var requisiteDataSign = requisiteDataSignNode.value;
								if (this.blockArea)
								{
									if (requisiteData && requisiteDataSign)
									{
										if (this.blockIndex >= 0)
											this.blockArea.updateBlock(this.blockIndex, requisiteId, requisiteData, requisiteDataSign);
										else
											this.blockArea.addBlock(requisiteId, requisiteData, requisiteDataSign);
									}
								}
								if (typeof(this.afterRequisiteEditCallback) === "function")
								{
									this.afterRequisiteEditCallback(requisiteId, requisiteData, requisiteDataSign);
								}
								needClosePopup = true;
							}
						}
					}
				}
			}

			if (needClosePopup)
				window.setTimeout(BX.delegate(this.closePopup, this), 1000);
		},
		onRequestFailure: function(data)
		{
			BX.onCustomEvent("CrmRequisitePopupFormManagerClosePopup", [this]);
			this.isRequestRunning = false;
		},
		onFormCreate: function(eventArgs)
		{
			BX.removeCustomEvent(window, "CrmRequisiteEditFormCreate", this.formCreateHandler);
			this.bindToForm(eventArgs["form"]);
		},
		bindToForm: function(form)
		{
			this.formId = form.getFormId();
			if (this.saveButton && BX.type.isNotEmptyString(this.formId))
			{
				var saveButtonNode = this.saveButton.render();
				if (BX.type.isElementNode(saveButtonNode))
					saveButtonNode.setAttribute("id", this.formId + "_save");
			}
			if (this.cancelButton && BX.type.isNotEmptyString(this.formId))
			{
				var cancelButtonNode = this.cancelButton.render();
				if (BX.type.isElementNode(cancelButtonNode))
					cancelButtonNode.setAttribute("id", this.formId + "_cancel");
			}

			if(this.requisiteAjaxUrl === "")
			{
				return;
			}

			if(!form.isClientResolutionEnabled())
			{
				return;
			}

			var countryId = form.getCountryId();
			var typeId = "";
			var inputName = "";
			switch (countryId)
			{
				case 1:
					typeId = BX.Crm.RequisiteFieldType.itin;
					inputName = "RQ_INN";
					break;
				case 14:
					typeId = BX.Crm.RequisiteFieldType.sro;
					inputName = "RQ_EDRPOU";
					break;
			}

			if (inputName.length > 0)
			{
				var input = this.getFieldControl(inputName);
				if(input)
				{
					BX.Crm.RequisiteFieldController.create(
						inputName,
						{
							countryId: countryId,
							typeId: typeId,
							input: input,
							serviceUrl: this.requisiteAjaxUrl,
							callbacks: {onFieldsLoad: BX.delegate(this.setupFields, this)}
						}
					);
				}
			}

			var editors = BX.CrmMultipleAddressEditor.getItemsByFormId(this.formId);
			if(editors.length > 0)
			{
				this.multiAddressEditor = editors[0];
				BX.addCustomEvent(
					this.multiAddressEditor,
					"CrmMultipleAddressItemCreated",
					BX.delegate(this.onAddressCreate, this)
				);
			}
		},
		preparePopupContent: function(data)
		{
			this.wrapper = BX.create("DIV", { html: data });
			return this.wrapper;
		},
		prepareButtons: function()
		{
			var result = [];

			if (!this.readOnlyMode)
			{
				result.push(
					this.saveButton = new BX.PopupWindowButton(
						{
							text: this.getMessage("popupSaveBtnTitle"),
							className: 'popup-window-button-accept',
							events: { click: BX.delegate(this.onSaveBtnClick, this) }
						}
					)
				);
			}
			result.push(
				this.cancelButton = new BX.PopupWindowButtonLink(
					{
						text: this.getMessage("popupCancelBtnTitle"),
						className: 'popup-window-button-link-cancel',
						events: { click: BX.delegate(this.onCloseBtnClick, this) }
					}
				)
			);

			return result;
		},
		opPopupShow: function()
		{
		},
		opPopupClose: function()
		{
			BX.onCustomEvent("CrmRequisitePopupFormManagerClosePopup", [this]);
			if(this.popup)
			{
				this.wrapper = BX.remove(this.wrapper);
				this.popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			this.popup = null;
			if (typeof(this.editorPopupDestroyCallback) === 'function')
				this.editorPopupDestroyCallback();
		},
		onSaveBtnClick: function(e)
		{
			var result = [];
			BX.onCustomEvent("CrmDupControllerRequisiteFind", [this, result]);
			if (result.length <= 0)
				this.saveBtnClickLockObject = this;
			else
				this.saveBtnClickLockObject = result[0];
			BX.addCustomEvent("CrmRequisitePopupFormManagerDoSave", this.doSaveHandler);

			if (this.saveBtnClickLockObject === this)
			{
				BX.onCustomEvent("CrmRequisitePopupFormManagerDoSave", [this, true]);
			}
			else
			{
				BX.onCustomEvent(this.saveBtnClickLockObject, "CrmRequisitePopupFormManagerSaveLock");
			}
		},
		onDoSave: function(lockObject, doSave)
		{
			if (lockObject !== null && typeof(lockObject) === "object"
				&& lockObject === this.saveBtnClickLockObject && BX.type.isBoolean(doSave))
			{
				BX.removeCustomEvent("CrmRequisitePopupFormManagerDoSave", this.doSaveHandler);
				this.saveBtnClickLockObject = null;

				if (doSave)
				{
					this.startFormSubmitRequest();
				}
			}
		},
		onCloseBtnClick: function(e)
		{
			this.closePopup();
		},
		onFormManagerCreate: function(sender)
		{
			this.formSettingManager = sender;
			BX.addCustomEvent(this.formSettingManager, "CrmFormSettingManagerReloadForm", BX.delegate(this.onFormReload, this));
		},
		onFormReload: function(sender, eventArgs)
		{
			if(this.formSettingManager !== sender)
			{
				return;
			}

			eventArgs["cancel"] = true;
			this.reloadPopup();
		},
		onAddressCreate: function(sender, address)
		{
		},
		register: function()
		{
			BX.Crm[this._index] = this;
		},
		unregister: function()
		{
			delete BX.Crm[this._index];
		},
		destroy: function()
		{
			BX.removeCustomEvent("CrmRequisitePopupFormManagerDoSave", this.doSaveHandler);
			this.unregister();
		}
	};

	return RequisitePopupFormManagerClass;
})();

if (typeof(BX.setTextContent) === "undefined")
{
	BX.setTextContent = function(element, value)
	{
		if (element)
		{
			if (element.textContent !== undefined)
				element.textContent = value;
			else
				element.innerText = value;
		}
	}
}

BX.Crm.RequisiteFieldType =
{
	undefined: "",
	itin: "itin",   //Individual Taxpayer Identification Number
	sro: "sro"    // State Register of organizations
};

if(typeof(BX.Crm.RequisiteFieldController) === "undefined")
{
	BX.Crm.RequisiteFieldController = function()
	{
		this._id = "";
		this._settings = {};
		this._countryId = 0;
		this._typeId = BX.Crm.RequisiteFieldType.undefined;
		this._input = null;
		this._value = "";
		this._needle = "";
		this._timeoutId = 0;
		this._keyPressHandler = BX.delegate(this.onKeyPress, this);
		this._timeoutHandler = BX.delegate(this.onTimeout, this);

		this._serviceUrl = "";
		this._isRequestRunning = false;

		this._dialog = null;
	};
	BX.Crm.RequisiteFieldController.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_requisite_form_manager";
			this._settings = settings ? settings : {};
			this._countryId = this.getSetting("countryId", 0);
			this._typeId = this.getSetting("typeId", BX.Crm.RequisiteFieldType.undefined);

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.Crm.RequisiteFieldController: Could not find 'serviceUrl' parameter in settings.";
			}

			this._input = this.getSetting("input");
			if(!BX.type.isElementNode(this._input))
			{
				throw "BX.Crm.RequisiteFieldController: Could not fild 'input' parameter in settings.";
			}

			this._input.autocomplete = "off";
			BX.bind(this._input, "keyup", this._keyPressHandler);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		validate: function()
		{
			var result = false;
			if(this._typeId === BX.Crm.RequisiteFieldType.itin)
			{
				this._needle = this._value.replace(/[^0-9]/g, "");
				//Russia
				if(this._countryId === 1)
				{
					return (this._needle.length === 10 || this._needle.length === 12);
				}
				return this._needle.length === 9;
			}
			else if(this._typeId === BX.Crm.RequisiteFieldType.sro)
			{
				this._needle = this._value.replace(/[^0-9]/g, "");
				//Ukraine
				if(this._countryId === 14)
				{
					return (this._needle.length === 8);
				}
			}
			return result;
		},
		search: function()
		{
			if(this._dialog)
			{
				this._dialog.close();
			}

			this.startSearchRequest();
		},
		openDialog: function(searchResult)
		{
			this.closeDialog();

			var items = BX.type.isArray(searchResult["ITEMS"]) ? searchResult["ITEMS"] : [];
			this._dialog = BX.Crm.ExternalRequisiteDialog.create(
				this._id,
				{ items: items, anchor: this._input, callbacks: this.getSetting("callbacks") }
			);
			this._dialog.open();

			if(items.length === 0)
			{
				window.setTimeout(BX.delegate(this.closeDialog, this), 1000);
			}
		},
		closeDialog: function()
		{
			if(this._dialog)
			{
				this._dialog.close();
			}
		},
		startSearchRequest: function()
		{
			if(this._isRequestRunning)
			{
				return;
			}

			this._isRequestRunning = true;

			BX.addClass(
				BX.findParent(this._input, { className: "crm-offer-info-data-wrap" }, 1),
				"search-inp-loading"
			);

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION": "RESOLVE_EXTERNAL_CLIENT",
						"PROPERTY_TYPE_ID": this._typeId,
						"PROPERTY_VALUE": this._needle,
						"COUNTRY_ID": this._countryId
					},
					onsuccess: BX.delegate(this.onSearchRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		onKeyPress: function(e)
		{
			e = e || window.event;
			var c = e.keyCode;

			if(c === 13 || c === 27 || (c >=37 && c <= 40) || (c >=112 && c <= 123))
			{
				return;
			}

			if(this._value === this._input.value)
			{
				return;
			}

			this._value = this._input.value;

			if(this._timeoutId > 0)
			{
				window.clearTimeout(this._timeoutId);
				this._timeoutId = 0;
			}
			this._timeoutId = window.setTimeout(this._timeoutHandler, 1000);
		},
		onTimeout: function()
		{
			if(this._timeoutId <= 0)
			{
				return;
			}

			this._timeoutId = 0;
			if(this.validate())
			{
				this._value = "";
				this.search();
			}
		},
		onSearchRequestSuccess: function(response)
		{
			this._isRequestRunning = false;

			BX.removeClass(
				BX.findParent(this._input, { className: "crm-offer-info-data-wrap" }, 1),
				"search-inp-loading"
			);

			this.openDialog(BX.type.isPlainObject(response["DATA"]) ? response["DATA"] : {});
		},
		onRequestFailure: function(response)
		{
			this._isRequestRunning = false;

			BX.removeClass(
				BX.findParent(this._input, { className: "crm-offer-info-data-wrap" }, 1),
				"search-inp-loading"
			);
		}
	};
	BX.Crm.RequisiteFieldController.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteFieldController();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.ExternalRequisiteDialog) === "undefined")
{
	BX.Crm.ExternalRequisiteDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._callbacks = {};
		this._anchor = null;
		this._dialog = null;
		this._itemData = null;
		this._items = [];
	};
	BX.Crm.ExternalRequisiteDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_ext_requisite_dlg";
			this._settings = settings ? settings : {};

			this._itemData = this.getSetting("items");
			if(!BX.type.isArray(this._itemData))
			{
				throw "BX.Crm.ExternalRequisiteDialog: Could not fild 'items' parameter in settings.";
			}

			var cb = this.getSetting("callbacks");
			if(BX.type.isPlainObject(cb))
			{
				this._callbacks = cb;
			}

			this._anchor = this.getSetting("anchor");
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(msgId)
		{
			var messages = BX.Crm.ExternalRequisiteDialog.messages;
			return messages.hasOwnProperty(msgId) ? messages[msgId] : msgId;
		},
		open: function()
		{
			var id = this.getId();
			if(BX.Crm.ExternalRequisiteDialog.windows[id])
			{
				BX.Crm.ExternalRequisiteDialog.windows[id].destroy();
			}

			this._dialog = new BX.PopupWindow(
				this._id,
				this._anchor,
				{
					autoHide: true,
					draggable: false,
					bindOptions: { forceBindPosition: true },
					closeByEsc: true,
					zIndex: 0,
					content: this.prepareContent(),
					events:
					{
						onPopupShow: BX.delegate(this.onDialogShow, this),
						onPopupClose: BX.delegate(this.onDialogClose, this),
						onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
					}
				}
			);

			BX.Crm.ExternalRequisiteDialog.windows[id] = this._dialog;
			this._dialog.show();
		},
		close: function()
		{
			if(this._dialog)
			{
				this._dialog.close();
			}
		},
		processItemSelection: function(item)
		{
			if(BX.type.isFunction(this._callbacks["onFieldsLoad"]))
			{
				this._callbacks["onFieldsLoad"](item.getFields());
			}
			this.close();
		},
		prepareContent: function()
		{
			var width = BX.pos(this._anchor)["width"];
			var qty = this._itemData.length;
			if(qty > 0)
			{
				var list = BX.create(
					"UL",
					{
						attrs: { className: "popup-search-result" },
						style: { width: (width.toString() + "px"), display: "block" }
					}
				);

				for(var i = 0; i < qty; i++)
				{
					var item = BX.Crm.ExternalRequisiteDialogItem.create(
						"",
						{ data: this._itemData[i], container: list, dialog: this }
					);
					item.layout();
					this._items.push(item);
				}

				return list;
			}
			else
			{
				return (
					BX.create(
						"DIV",
						{
							attrs: { className: "popup-search-result-empty" },
							style: { width: (width.toString() + "px") },
							text: this.getMessage("searchResultNotFound")
						}
					)
				);
			}
		},
		onDialogShow: function()
		{
		},
		onDialogClose: function()
		{
			if(this._dialog)
			{
				this._dialog.destroy();
			}
		},
		onDialogDestroy: function()
		{
			if(this._dialog)
			{
				this._dialog = null;
			}
		}
	};
	if(typeof(BX.Crm.ExternalRequisiteDialog.messages) === "undefined")
	{
		BX.Crm.ExternalRequisiteDialog.messages =
		{
		};
	}
	BX.Crm.ExternalRequisiteDialog.items = {};
	BX.Crm.ExternalRequisiteDialog.windows = {};
	BX.Crm.ExternalRequisiteDialog.create = function(id, settings)
	{
		var self = new BX.Crm.ExternalRequisiteDialog();
		self.initialize(id, settings);
		BX.Crm.ExternalRequisiteDialog.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.Crm.ExternalRequisiteDialogItem) === "undefined")
{
	BX.Crm.ExternalRequisiteDialogItem = function()
	{
		this._id = "";
		this._settings = {};
		this._dialog = null;
		this._data = null;
		this._container = null;
		this._element = null;
		this._onClickHandler = BX.delegate(this.onClick, this);

		this._hasLayout = false;
	};

	BX.Crm.ExternalRequisiteDialogItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_ext_requisite_dlg_item";
			this._settings = settings ? settings : {};

			this._dialog = this.getSetting("dialog");
			if(!this._dialog)
			{
				throw "BX.Crm.ExternalRequisiteDialogItem: Could not fild 'dialog' parameter in settings.";
			}

			this._container = this.getSetting("container");
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.Crm.ExternalRequisiteDialogItem: Could not fild 'container' parameter in settings.";
			}

			this._data = this.getSetting("data");
			if(!BX.type.isPlainObject(this._data))
			{
				throw "BX.Crm.ExternalRequisiteDialogItem: Could not fild 'data' parameter in settings.";
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getCaption: function()
		{
			return BX.type.isNotEmptyString(this._data["caption"]) ? this._data["caption"] : "";
		},
		getFields: function()
		{
			return BX.type.isPlainObject(this._data["fields"]) ? this._data["fields"] : {};
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._element = BX.create(
				"LI",
				{
					attrs: { className: "popup-search-result-item" },
					events: { click: this._onClickHandler },
					children: [ BX.create("SPAN", { text: this.getCaption() }) ]
				}
			);
			this._container.appendChild(this._element);

			this._hasLayout = true;
		},
		clearLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			BX.remove(this._element);
			this._element = null;

			this._hasLayout = false;
		},
		onClick: function(e)
		{
			this._dialog.processItemSelection(this);
			return BX.PreventDefault(e);
		}
	};

	BX.Crm.ExternalRequisiteDialogItem.create = function(id, settings)
	{
		var self = new BX.Crm.ExternalRequisiteDialogItem();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.RequisiteEditFormManager) === "undefined")
{
	BX.Crm.RequisiteEditFormManager = function()
	{
		this._id = "";
		this._settings = {};
		this._crmRequisiteEditFormGetParamsHandler = BX.delegate(this.onCrmRequisiteEditFormGetParams, this);
		this._requisitePopupCloseHandler = BX.delegate(this.onRequisitePopupClose, this);
	};

	BX.Crm.RequisiteEditFormManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_rq_edit_form_manager_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};
			this._bind();

			var eventArgs = BX.clone(this._settings);
			eventArgs["form"] = this;
			BX.onCustomEvent("CrmRequisiteEditFormCreate", [eventArgs]);
		},
		destroy: function()
		{
			this._unbind();
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return BX.prop.get(this._settings, name, defaultval);
		},
		getFormId: function()
		{
			return BX.prop.getString(this._settings, "formId", "");
		},
		getCountryId: function()
		{
			return BX.prop.getInteger(this._settings, "countryId", 0);
		},
		isClientResolutionEnabled: function()
		{
			return BX.prop.getBoolean(this._settings, "enableClientResolution", false);
		},
		_bind: function()
		{
			BX.addCustomEvent("CrmRequisiteEditFormGetParams", this._crmRequisiteEditFormGetParamsHandler);
			BX.addCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
		},
		_unbind: function()
		{
			BX.removeCustomEvent("CrmRequisiteEditFormGetParams", this._crmRequisiteEditFormGetParamsHandler);
			BX.removeCustomEvent("CrmRequisitePopupFormManagerClosePopup", this._requisitePopupCloseHandler);
		},
		onCrmRequisiteEditFormGetParams: function(callback)
		{
			if (BX.type.isFunction(callback))
				callback(this._settings);
		},
		onRequisitePopupClose: function(requisitePopupFormManager)
		{
			var formId = "";

			if (requisitePopupFormManager instanceof BX.Crm.RequisitePopupFormManagerClass)
			{
				formId = requisitePopupFormManager.getFormId();
				if (BX.type.isNotEmptyString(formId))
				{
					formId = formId.replace(/[^a-z0-9_]/ig, "");
					if (formId === this._id)
						BX.Crm.RequisiteEditFormManager.delete(this._id);
				}
			}
		}
	};
	BX.Crm.RequisiteEditFormManager.items = {};
	BX.Crm.RequisiteEditFormManager.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteEditFormManager();
		self.initialize(id, settings);
		BX.Crm.RequisiteEditFormManager.items[id] = self;
		return self;
	};
	BX.Crm.RequisiteEditFormManager.delete = function(id)
	{
		if (BX.Crm.RequisiteEditFormManager.items.hasOwnProperty(id))
		{
			BX.Crm.RequisiteEditFormManager.items[id].destroy();
			delete BX.Crm.RequisiteEditFormManager.items[id];
		}
	};
}