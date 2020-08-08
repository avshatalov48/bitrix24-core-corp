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

		this._requisiteExternalSearchManager = null;

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

			response = BX(this._index.toString() + "_response");
			if (response)
			{
				hiddenRequisiteId = BX.findChild(
					response,
					{"tag": "input", "attr": {"type": "hidden", "name": "REQUISITE_ID"}},
					false, false
				);
				if (hiddenRequisiteId)
				{
					requisiteId = parseInt(hiddenRequisiteId.value);
					requisiteDataNode = BX.findChild(
						response,
						{"tag": "input", "attr": {"type": "hidden", "name": "REQUISITE_DATA"}},
						false, false
					);
					if (requisiteDataNode)
					{
						requisiteDataSignNode = BX.findChild(
							response,
							{"tag": "input", "attr": {"type": "hidden", "name": "REQUISITE_DATA_SIGN"}},
							false, false
						);
						if (requisiteDataSignNode)
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
			{
				window.setTimeout(BX.delegate(this.closePopup, this), 1000);
			}
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
			this.destroyRequisiteExternalSearchManager();
			if (typeof(form) === "object" && form !== null)
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
				var featureName = "";
				switch (countryId)
				{
					case 1:
						typeId = BX.Crm.RequisiteFieldType.itin;
						inputName = "RQ_INN";
						featureName = "detailsSearchByInn";
						break;
					case 14:
						typeId = BX.Crm.RequisiteFieldType.sro;
						inputName = "RQ_EDRPOU";
						featureName = "detailsSearchByEdrpou";
						break;
				}

				var externalRequisiteSearchConfig = form.getSetting("externalRequisiteSearchConfig", null);
				var isExternalRequisiteSearchEnabled = (
					BX.type.isPlainObject(externalRequisiteSearchConfig)
					&& externalRequisiteSearchConfig.hasOwnProperty("enabled")
					&& externalRequisiteSearchConfig["enabled"]
				);
				var defaultFieldControllers = [];

				var controller;
				if (inputName.length > 0)
				{
					var input = this.getFieldControl(inputName);
					if(input)
					{
						var features = form.getSetting("features", null);
						if (!BX.type.isPlainObject(features))
						{
							features = {};
						}
						var scriptIndex = featureName + "InfoScript";
						var popupScript = (features.hasOwnProperty(scriptIndex)) ? features[scriptIndex] : null;
						controller = BX.Crm.RequisiteFieldController.create(
							inputName,
							{
								countryId: countryId,
								typeId: typeId,
								input: input,
								serviceUrl: this.requisiteAjaxUrl,
								callbacks: {onFieldsLoad: BX.delegate(this.setupFields, this)},
								tariffLock: (!features.hasOwnProperty(featureName) || features[featureName] === 'N'),
								tariffLockPopupScript: popupScript
							}
						);

						if (isExternalRequisiteSearchEnabled)
						{
							defaultFieldControllers.push({
								fieldId: "REQUISITE." + externalRequisiteSearchConfig["requisitePseudoId"] +
									"." + inputName,
								controller: controller
							});
						}
					}
				}

				this.destroyRequisiteExternalSearchManager();
				if (isExternalRequisiteSearchEnabled)
				{
					externalRequisiteSearchConfig["containerId"] = "form_" + this.formId;
					externalRequisiteSearchConfig["countryId"] = countryId;
					externalRequisiteSearchConfig["addressOriginatorId"] = "";
					externalRequisiteSearchConfig["defaultFieldControllers"] = defaultFieldControllers;
					this._requisiteExternalSearchManager = BX.Crm.RequisiteExternalSearchManager.create(
						null,
						externalRequisiteSearchConfig
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
		destroyRequisiteExternalSearchManager: function()
		{
			if (this._requisiteExternalSearchManager)
			{
				BX.Crm.RequisiteExternalSearchManager.delete(this._requisiteExternalSearchManager.getId());
				this._requisiteExternalSearchManager = null;
			}
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
			
			this.destroyRequisiteExternalSearchManager();

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
			this.destroyRequisiteExternalSearchManager();
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
	itin: "itin",  // Individual Taxpayer Identification Number
	sro: "sro",    // State Register of organizations
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
		this._inputAutocomplete = "";

		this._serviceUrl = "";
		this._isRequestRunning = false;

		this._isActive = false;

		this._dialog = null;

		this._tariffLock = null;
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

			this.activate();
			this.showLock();
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
			if (!this._isActive)
			{
				return;
			}

			if(this._dialog)
			{
				this._dialog.close();
			}

			this.startSearchRequest();
		},
		openDialog: function(searchResult)
		{
			if (!this._isActive)
			{
				return;
			}

			this.closeDialog();

			var selectParams = BX.type.isPlainObject(searchResult["SELECT_PARAMS"]) ?
				searchResult["SELECT_PARAMS"] : {};
			var items = BX.type.isArray(searchResult["ITEMS"]) ? searchResult["ITEMS"] : [];
			this._dialog = BX.Crm.ExternalRequisiteDialog.create(
				this._id,
				{
					items: items,
					anchor: this._input,
					callbacks: this.getSetting("callbacks"),
					selectParams: selectParams
				}
			);
			this._dialog.open();

			if(items.length === 0)
			{
				window.setTimeout(BX.delegate(this.closeDialog, this), 1000);
			}
		},
		showLoader: function()
		{
			BX.addClass(
				BX.findParent(this._input, { className: "crm-offer-info-data-wrap" }, 1),
				"search-inp-loading"
			);
		},
		hideLoader: function()
		{
			BX.removeClass(
				BX.findParent(this._input, { className: "crm-offer-info-data-wrap" }, 1),
				"search-inp-loading"
			);
		},
		showLock: function()
		{
			var parent, i, found, popupScript;
			if (this.getSetting("tariffLock", false) && BX.type.isDomNode(this._input))
			{
				parent = this._input;
				found = false;
				for (i = 0; i < 10; i++)
				{
					parent = parent.parentNode;
					if (parent && parent.tagName === "TR" && parent.className === "crm-offer-row")
					{
						found = true;
						break;
					}
				}
				if (found)
				{
					parent = parent.querySelector("div.crm-offer-info-label-wrap");
					if (parent)
					{
						this._tariffLock = BX.create("SPAN", { attrs: { "className": "tariff-lock" } });
						if (parent.firstChild)
						{
							parent.insertBefore(this._tariffLock, parent.firstChild);
						}
						else
						{
							parent.appendChild(this._tariffLock);
						}
						popupScript = this.getSetting("tariffLockPopupScript", null);
						if (popupScript)
						{
							this._tariffLock.setAttribute("onclick", popupScript);
							this._tariffLock.style.cursor = "pointer";
						}
					}
				}
			}
		},
		hideLock: function()
		{
			if (this.getSetting("tariffLock", false) && BX.type.isDomNode(this._tariffLock))
			{
				BX.remove(this._tariffLock);
				this._tariffLock = null;
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
			if(!this._isActive || this._isRequestRunning)
			{
				return;
			}

			this._isRequestRunning = true;

			this.showLoader();

			if (this.getSetting("tariffLock", false))
			{
				window.setTimeout(BX.delegate(this.onRequestFailure, this), 1000);
			}
			else
			{
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
			}
		},
		onKeyPress: function(e)
		{
			if (!this._isActive)
			{
				return;
			}

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
			if(!this._isActive || this._timeoutId <= 0)
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
			if (!this._isActive)
			{
				return;
			}

			this._isRequestRunning = false;

			this.hideLoader();

			this.openDialog(BX.type.isPlainObject(response["DATA"]) ? response["DATA"] : {});
		},
		onRequestFailure: function(response)
		{
			if (!this._isActive)
			{
				return;
			}

			this._isRequestRunning = false;

			this.hideLoader();
		},
		isActive: function()
		{
			return this._isActive;
		},
		bindHandlers: function()
		{
			BX.bind(this._input, "keyup", this._keyPressHandler);
		},
		activate: function()
		{
			this._inputAutocomplete = this._input.autocomplete;
			this._input.autocomplete = "off";
			this.bindHandlers();

			this._isActive = true;
		},
		unbindHandlers: function()
		{
			BX.unbind(this._input, "keyup", this._keyPressHandler);
		},
		deactivate: function()
		{
			this._isActive = false;

			if(this._timeoutId > 0)
			{
				window.clearTimeout(this._timeoutId);
				this._timeoutId = 0;
			}

			this.unbindHandlers();
			this._input.autocomplete = this._inputAutocomplete;

			this.closeDialog();
			this._dialog = null;

			if (this._isRequestRunning)
			{
				this.hideLoader();
				this._isRequestRunning = false;
			}
		},
		destroy: function()
		{
			this.deactivate();
			this.hideLock();

			this._id = "";
			this._settings = {};
			this._countryId = 0;
			this._typeId = BX.Crm.RequisiteFieldType.undefined;
			this._input = null;
			this._value = "";
			this._needle = "";
			this._inputAutocomplete = "";
			this._serviceUrl = "";
			this._dialog = null;
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
		this._selectParams = null;
		this._isResultSelected = false;
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

			this._selectParams = this.getSetting("selectParams", null);
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
			var selectParams;

			this._isResultSelected = true;
			if(BX.type.isFunction(this._callbacks["onFieldsLoad"]))
			{
				selectParams = BX.type.isPlainObject(this._selectParams) ? this._selectParams : {};
				selectParams["index"] = item.getIndex();
				this._callbacks["onFieldsLoad"](item.getFields(), selectParams);
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
						style: { display: "block" }
					}
				);

				for(var i = 0; i < qty; i++)
				{
					var item = BX.Crm.ExternalRequisiteDialogItem.create(
						"",
						{ index: i, data: this._itemData[i], container: list, dialog: this }
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
				if(!this._isResultSelected && BX.type.isFunction(this._callbacks["onFieldsLoadCancel"]))
				{
					this._callbacks["onFieldsLoadCancel"](this.getId(), this._selectParams);
				}
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
		getIndex: function()
		{
			var result;

			result = parseInt(this.getSetting("index", -1));
			if (isNaN(result) || result < 0)
			{
				result = -1;
			}

			return result;
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
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_rq_edit_form_manager_" +
				Math.random().toString().substring(2);
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

if(typeof(BX.Crm.RequisiteExternalSearchApplication) === "undefined")
{
	BX.Crm.RequisiteExternalSearchApplication = function()
	{
		this._random = "";
		this._id = "";
		this._lastId = "";
		this._settings = {};

		this._manager = null;

		this._loadCallback = null;
		this._closeCallback = null;

		this._appId = "";
		this._placementId = 0;
		this._placementCode = "";
		this._placementOptions = {};

		this._container = null;
		this._contentContainer = null;

		this._maxTimeout = 0;

		this._subscription = [];

		this._stateMap = {
			"destoyed": 0,
			"initialized": 1,
			"loaded": 2,
			"fieldsChangeRequest": 3,
			"closed": 4
		};

		this._stateArr = [];
		var index;
		for (index in this._stateMap)
		{
			if (this._stateMap.hasOwnProperty(index))
			{
				this._stateArr[this._stateMap[index]] = index;
			}
		}

		this._state = this._stateMap["destoyed"];
	};

	BX.Crm.RequisiteExternalSearchApplication.prototype = {
		initialize: function(id, settings)
		{
			if (this._id !== "")
			{
				this.destroy();
			}

			this._random = Math.random().toString().substring(2);
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_rq_ext_search_app_" + this._random;
			this._lastId = this._id;
			this._settings = settings ? settings : {};

			var handler = this.getSetting("handler", null);
			if (BX.type.isPlainObject(handler))
			{
				if (handler.hasOwnProperty("APP_ID"))
				{
					this._appId = parseInt(handler["APP_ID"]);
				}
				if (handler.hasOwnProperty("ID"))
				{
					this._placementId = parseInt(handler["ID"]);
				}
				if (handler.hasOwnProperty("CODE"))
				{
					this._placementCode = handler["CODE"];
				}

				this._placementOptions = {
					instanceId: this.getId(),
					entityTypeId: this.getSetting("entityTypeId", 0),
					entityId: this.getSetting("entityId", 0),
					presetId: this.getSetting("presetId", 0),
					countryId: this.getSetting("countryId", 0),
					requisiteId: this.getSetting("requisiteId", 0),
					handler: handler
				};
			}

			var manager = this.getSetting("manager", null);
			if (manager !== null && typeof(manager) === "object")
			{
				this._manager = manager;
			}

			var loadCallback = this.getSetting("loadCallback", null);
			if (typeof(loadCallback) === "function")
			{
				this._loadCallback = loadCallback;
			}
			var closeCallback = this.getSetting("closeCallback", null);
			if (typeof(closeCallback) === "function")
			{
				this._closeCallback = closeCallback;
			}

			var container;
			var containerId = this.getSetting("containerId", "");
			if (BX.type.isNotEmptyString(containerId))
			{
				container = BX(containerId);
				if (BX.type.isDomNode(container))
				{
					this._container = container;
				}
			}

			if (this._container)
			{
				this._contentContainer = this._container.appendChild(
					BX.create(
						"div",
						{
							attrs: {
								"id": this.getId(),
								"style": "display: none;"
							}
						}
					)
				);
			}

			this.setState("initialized");
		},
		_clear: function()
		{
			this._random = "";
			this._id = "";
			this._settings = {};

			this._manager = null;

			this._loadCallback = null;
			this._closeCallback = null;

			this._appId = "";
			this._placementId = 0;
			this._placementCode = "";
			this._placementOptions = {};

			if (BX.type.isDomNode(this._contentContainer))
			{
				BX.remove(this._contentContainer);
			}
			this._container = null;
			this._contentContainer = null;

			this._maxTimeout = 0;

			this._subscription = [];
		},
		destroy: function()
		{
			var state = this.getState();
			if (state !== "closed" && state !== "destoyed")
			{
				this.close();
			}
			this._unbind();
			this._clear();

			state = this.getState();
			if (state !== "destoyed")
			{
				this.setState("destoyed");
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return BX.prop.get(this._settings, name, defaultval);
		},
		_bind: function()
		{
		},
		_unbind: function()
		{
		},
		getStateId: function()
		{
			return this._state;
		},
		getState: function()
		{
			return this._stateArr[this._state];
		},
		setState: function(stateName)
		{
			if (!this._stateMap.hasOwnProperty(stateName))
			{
				return false;
			}

			this._state = this._stateMap[stateName];
			
			return true;
		},
		getMaxTimeout: function(maxTimeout)
		{
			return this._maxTimeout;
		},
		setMaxTimeout: function(maxTimeout)
		{
			var timeout = parseInt(maxTimeout);
			if (timeout >= 1000 && timeout <= 30000)
			{
				this._maxTimeout = timeout;
			}
		},
		setReadyState: function()
		{
			var result;

			result = false;

			if (this.getState() === "initialized")
			{
				result = this.onLoad();
			}

			return result;
		},
		setSubscription: function(fields)
		{
			var result, state;

			result = false;

			if (BX.type.isArray(fields))
			{
				if (this._manager)
				{
					state = this.getState();
					if (state === "loaded" || state === "fieldsChangeRequest")
					{
						this._manager.setSubscription(this._id, fields);
						result = true;
					}
				}
			}

			return result;
		},
		getValues: function(fields)
		{
			var result, state;

			result = [];

			if (BX.type.isArray(fields) && fields.length > 0
				&& this._manager)
			{
				state = this.getState();
				if (state === "loaded" || state === "fieldsChangeRequest")
				{
					result = this._manager.getValues(this.getId(), fields);
				}
			}

			return result;
		},
		setResultReady: function(token)
		{
			var result;

			result = false;

			if (this.getState() === "fieldsChangeRequest" && this._manager)
			{
				result = this._manager.setResultReady(this._id, token);
			}

			return result;
		},
		setResult: function(token, resultData)
		{
			var result, state;

			result = false;

			if (this.getState() === "fieldsChangeRequest" && this._manager)
			{
				result = this._manager.setResult(this._id, token, resultData);
			}

			return result;
		},
		getAppId: function()
		{
			return this._appId;
		},
		getPlacementId: function()
		{
			return this._placementId;
		},
		getPlacementCode: function()
		{
			return this._placementCode;
		},
		getPlacementOptions: function()
		{
			return this._placementOptions;
		},
		getContentContainer: function()
		{
			return this._contentContainer;
		},
		isDestroyed: function()
		{
			return(this.getState() === "destroyed");
		},
		load: function()
		{
			var url = BX.message('REST_APPLICATION_URL').replace('#id#', this.getAppId());
			url = BX.util.add_url_param(url, {'_r': Math.random()});

			var params = {
				ID: this.getAppId(),
				PLACEMENT: this.getPlacementCode(),
				PLACEMENT_ID: this.getPlacementId(),
				PLACEMENT_OPTIONS: this.getPlacementOptions(),
				SHOW_LOADER: "N",
				POPUP: 1
			};

			var promise = new BX.Promise();
			promise
				.then(
					function(url)
					{
						var promise = new top.BX.Promise();

						BX.ajax.post(
							url,
							{
								sessid: BX.bitrix_sessid(),
								site: BX.message('SITE_ID'),
								PARAMS: {
									template: '',
									params: params
								}
							},
							function(result)
							{
								promise.fulfill(result);
							}
						);

						return promise;
					}
				)
				.then(
					function(result)
					{
						if (this.isDestroyed())
						{
							return;
						}

						if (BX.type.isNotEmptyString(result))
						{
							this.getContentContainer().innerHTML = result;
						}
					}.bind(this),
					function(reason)
					{
						this.destroy();
						BX.debug("error", reason);
					}
				);
			promise.fulfill(url);

			top.BX.addCustomEvent(top, 'Rest:AppLayout:ApplicationInstall', function(installed, eventResult)
			{
				this.load();
			});
		},
		addAddress: function(token, typeId)
		{
			var result;

			result = false;

			if (this.getState() === "fieldsChangeRequest" && this._manager)
			{
				result = this._manager.addAddress(token, this.getId(), typeId);
			}

			return result;
		},
		removeAddress: function(token, typeId)
		{
			var result;

			result = this.getState() === "fieldsChangeRequest";

			if (result && this._manager)
			{
				result = this._manager.removeAddress(token, this.getId(), typeId);
			}

			return result;
		},
		addBankDetail: function(token)
		{
			var result;

			result = 0;

			if (this.getState() === "fieldsChangeRequest" && this._manager)
			{
				result = this._manager.addBankDetail(token, this.getId());
			}

			return result;
		},
		removeBankDetail: function(token, id)
		{
			var result;

			result = 0;

			if (this.getState() === "fieldsChangeRequest" && this._manager)
			{
				result = this._manager.removeBankDetail(token, this.getId(), id);
			}

			return result;
		},
		close: function()
		{
			BX.onCustomEvent("onCrmRequisiteEditFormApplicationClose", [{instanceId: this._lastId}]);
			this.onClose();
		},
		onLoad: function()
		{
			var result = this.setState("loaded");

			if (result)
			{
				if (this._loadCallback)
				{
					this._loadCallback(this._id);
				}
			}

			return result;
		},
		onFieldsChange: function(token, fields)
		{
			var result;

			result = BX.type.isNotEmptyString(token) && BX.type.isArray(fields) && fields.length > 0;

			if (result)
			{
				if (this.getState() === "loaded")
				{
					result = this.setState("fieldsChangeRequest");
				}
			}

			if (result)
			{
				BX.onCustomEvent("onCrmRequisiteEditFormFieldChange", [{
					instanceId: this.getId(),
					token: token,
					fields: fields
				}]);
			}

			return result;
		},
		onCancelFieldsChange: function()
		{
			var result;

			result = false;

			if (this.getState() === "fieldsChangeRequest")
			{
				result = this.setState("loaded");
			}

			return result;
		},
		onFieldsRemove: function(fields)
		{
			var result, state;

			result = BX.type.isArray(fields) && fields.length > 0;

			if (result)
			{
				state = this.getState();
				if (state !== "loaded" && state !== "fieldsChangeRequest")
				{
					result = false;
				}
			}

			if (result)
			{
				BX.onCustomEvent("onCrmRequisiteEditFormFieldRemove", [{
					instanceId: this.getId(),
					fields: fields
				}]);
			}

			return result;
		},
		onFormAddressAdd: function (typeId, fields)
		{
			var result, state;

			result = typeId > 0 && BX.type.isArray(fields) && fields.length > 0;

			if (result)
			{
				state = this.getState();
				if (state !== "loaded" && state !== "fieldsChangeRequest")
				{
					result = false;
				}
			}

			if (result)
			{
				BX.onCustomEvent("onCrmRequisiteEditFormAddressAdd", [{
					instanceId: this.getId(),
					typeId: typeId,
					fields: fields
				}]);
			}

			return result;
		},
		onFormAddressRemove: function (typeId, fields)
		{
			var result, state;

			result = typeId > 0 && BX.type.isArray(fields) && fields.length > 0;

			if (result)
			{
				state = this.getState();
				if (state !== "loaded" && state !== "fieldsChangeRequest")
				{
					result = false;
				}
			}

			if (result)
			{
				BX.onCustomEvent("onCrmRequisiteEditFormAddressRemove", [{
					instanceId: this.getId(),
					typeId: typeId,
					fields: fields
				}]);
			}

			return result;
		},
		onFormBankDetailAdd: function (id, fields)
		{
			var result, state;

			result = (BX.type.isNotEmptyString(id) || (BX.type.isNumber(id) && id > 0))
				&& BX.type.isArray(fields) && fields.length > 0;
			
			if (result)
			{
				state = this.getState();
				if (state !== "loaded" && state !== "fieldsChangeRequest")
				{
					result = false;
				}
			}

			if (result)
			{
				BX.onCustomEvent("onCrmRequisiteEditFormBankDetailAdd", [{
					instanceId: this.getId(),
					id: id,
					fields: fields
				}]);
			}

			return result;
		},
		onFormBankDetailRemove: function (id, fields)
		{
			var result, state;

			result = (BX.type.isNotEmptyString(id) || (BX.type.isNumber(id) && id > 0))
				&& BX.type.isArray(fields) && fields.length > 0;

			if (result)
			{
				state = this.getState();
				if (state !== "loaded" && state !== "fieldsChangeRequest")
				{
					result = false;
				}
			}

			if (result)
			{
				BX.onCustomEvent("onCrmRequisiteEditFormBankDetailRemove", [{
					instanceId: this.getId(),
					id: id,
					fields: fields
				}]);
			}

			return result;
		},
		onFormResultSelect: function(token, index)
		{
			var result;

			result = false;

			if (this.getState() === "fieldsChangeRequest")
			{
				result = this.setState("loaded");
			}

			if (result)
			{
				BX.onCustomEvent("onCrmRequisiteEditFormResultSelect", [{
					instanceId: this.getId(),
					token: token,
					index: index
				}]);
			}

			return result;
		},
		onFormResultCancel: function(token)
		{
			var result;

			result = false;

			if (this.getState() === "fieldsChangeRequest")
			{
				result = this.setState("loaded");
			}

			if (result)
			{
				BX.onCustomEvent("onCrmRequisiteEditFormResultCancel", [{
					instanceId: this.getId(),
					token: token
				}]);
			}

			return result;
		},
		onClose: function()
		{
			var result, state;

			result = false;

			state = this.getState();
			if (state !== "closed" && state !== "destoyed")
			{
				this.setState("closed");

				if (this._closeCallback)
				{
					this._closeCallback(this._id);
				}

				result = true;
			}

			return result;
		}
	};
	BX.Crm.RequisiteExternalSearchApplication.items = {};
	BX.Crm.RequisiteExternalSearchApplication.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteExternalSearchApplication();
		self.initialize(id, settings);
		BX.Crm.RequisiteExternalSearchApplication.items[self.getId()] = self;
		return self;
	};
	BX.Crm.RequisiteExternalSearchApplication.check = function(id)
	{
		return BX.Crm.RequisiteExternalSearchApplication.items.hasOwnProperty(id);
	};
	BX.Crm.RequisiteExternalSearchApplication.get = function(id)
	{
		if (BX.Crm.RequisiteExternalSearchApplication.items.hasOwnProperty(id))
		{
			return BX.Crm.RequisiteExternalSearchApplication.items[id];
		}

		return null;
	};
	BX.Crm.RequisiteExternalSearchApplication.delete = function(id)
	{
		if (BX.Crm.RequisiteExternalSearchApplication.items.hasOwnProperty(id))
		{
			BX.Crm.RequisiteExternalSearchApplication.items[id].destroy();
			delete BX.Crm.RequisiteExternalSearchApplication.items[id];
		}
	};
}

if(typeof(BX.Crm.RequisiteExternalSearchManager) === "undefined")
{
	BX.Crm.RequisiteExternalSearchManager = function()
	{
		this._clear();
	};

	BX.Crm.RequisiteExternalSearchManager.prototype = {
		_clear: function()
		{
			this._id = "";
			this._settings = {};
			this._placementInterface = null;
			this._formContainerId = "";
			this._formContainer = null;
			this._formSettingManager = null;
			this._addressEditors = [];
			this._apps = [];
			this._subscriptionMap = {};

			if (!this._loadAppHandler)
			{
				this._loadAppHandler = BX.delegate(this.onLoadApplication, this);
			}
			if (!this._closeAppHandler)
			{
				this._closeAppHandler = BX.delegate(this.onCloseApplication, this);
			}
			if (!this._searchResultHandler)
			{
				this._searchResultHandler = BX.delegate(this.onResultSelect, this);
			}
			if (!this._searchResultCancelHandler)
			{
				this._searchResultCancelHandler = BX.delegate(this.onResultCancel, this);
			}
			if (!this._fieldRemoveHandler)
			{
				this._fieldRemoveHandler = BX.delegate(this.onFormFieldRemove, this);
			}
			if (!this._addressAddHandler)
			{
				this._addressAddHandler = BX.delegate(this.onFormAddressAdd, this);
			}
			if (!this._addressRemoveHandler)
			{
				this._addressRemoveHandler = BX.delegate(this.onFormAddressRemove, this);
			}
			if (!this._bankDetailAddHandler)
			{
				this._bankDetailAddHandler = BX.delegate(this.onFormBankDetailAdd, this);
			}
			if (!this._bankDetailRemoveHandler)
			{
				this._bankDetailRemoveHandler = BX.delegate(this.onFormBankDetailRemove, this);
			}

			this._isRequestRunning = false;
			this._clearRequestContext();
		},
		_clearRequestContext: function()
		{
			this._requestContext = {
				token: "",
				fieldId: "",
				needle: "",
				appIdList: [],
				timeout: 5000,
				timeoutId: null,
				onSuccessHandler: null,
				onFailureHandler: null,
				firstResponseAppId: ""
			};
		},
		initialize: function(id, settings)
		{
			var formId, formIdLower, handlers, i;

			if (this._id !== "")
			{
				this.destroy();
			}

			this._id = BX.type.isNotEmptyString(id) ? id : "crm_rq_ext_search_" +
				Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			formId = this.getSetting("formId", "");
			if (BX.type.isNotEmptyString(formId))
			{
				formIdLower = formId.toLowerCase();
				this._formContainerId = "container_" + formIdLower;
				this._formContainer = BX(this._formContainerId);

				if (typeof(BX.CrmFormSettingManager) !== "undefined"
					&& BX.CrmFormSettingManager.items.hasOwnProperty(formIdLower))
				{
					this._formSettingManager = BX.CrmFormSettingManager.items[formIdLower];
				}

				if (typeof(BX.CrmMultipleAddressEditor) !== "undefined")
				{
					this._addressEditors = BX.CrmMultipleAddressEditor.getItemsByFormId(formId);
				}
			}

			this._bind();

			handlers = this.getSetting("handlers", null);
			if (BX.type.isArray(handlers) && handlers.length > 0)
			{
				for (i = 0; i < handlers.length; i++)
				{
					if (BX.type.isPlainObject(handlers[i]))
					{
						if (i === 0)
						{
							this.initializeInterface();
						}
						this.loadApplication(handlers[i]);
					}
				}
			}
		},
		destroy: function()
		{
			var i, appId;

			for (i = 0; i < this._apps.length; i++)
			{
				BX.Crm.RequisiteExternalSearchApplication.delete(this._apps[i].getId());
			}

			this._unbind();
			this._clear();
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return BX.prop.get(this._settings, name, defaultval);
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		_bind: function()
		{
			var i;

			if (this._formSettingManager)
			{
				BX.addCustomEvent(
					this._formSettingManager,
					"CrmFormSettingManagerRemoveField",
					this._fieldRemoveHandler
				);
			}

			if (BX.type.isArray(this._addressEditors))
			{
				for (i = 0; i < this._addressEditors.length; i++)
				{
					BX.addCustomEvent(
						this._addressEditors[i],
						"CrmMultipleAddressItemCreated",
						this._addressAddHandler
					);
					BX.addCustomEvent(
						this._addressEditors[i],
						"CrmMultipleAddressItemMarkAsDeleted",
						this._addressRemoveHandler
					);
				}
			}

			BX.addCustomEvent("CrmFormBankDetailBlockCreate", this._bankDetailAddHandler);
			BX.addCustomEvent("CrmFormBankDetailBlockRemove", this._bankDetailRemoveHandler);
		},
		_unbind: function()
		{
			if (this._formSettingManager)
			{
				BX.removeCustomEvent(
					this._formSettingManager,
					"CrmFormSettingManagerRemoveField",
					this._fieldRemoveHandler
				);
			}

			if (BX.type.isArray(this._addressEditors))
			{
				for (i = 0; i < this._addressEditors.length; i++)
				{
					BX.removeCustomEvent(
						this._addressEditors[i],
						"CrmMultipleAddressItemCreated",
						this._addressAddHandler
					);
					BX.removeCustomEvent(
						this._addressEditors[i],
						"CrmMultipleAddressItemMarkAsDeleted",
						this._addressRemoveHandler
					);
				}
			}

			BX.removeCustomEvent("CrmFormBankDetailBlockCreate", this._bankDetailAddHandler);
			BX.removeCustomEvent("CrmFormBankDetailBlockRemove", this._bankDetailRemoveHandler);
		},
		initializeInterface: function()
		{
			var appManager = this;
			var placementCode = this.getSetting("placementCode", "");

			if(!!BX.rest && !!BX.rest.AppLayout)
			{
				this._placementInterface = BX.rest.AppLayout.initializePlacement(placementCode);
				this._placementInterface.prototype.setReadyState = function(params, cb) {
					var result = false;
					
					if (BX.type.isPlainObject(params) && params.hasOwnProperty("instanceId"))
					{
						var instanceId = params["instanceId"];
						var app = BX.Crm.RequisiteExternalSearchApplication.get(instanceId);
						if (app)
						{
							if (params.hasOwnProperty("maxTimeout"))
							{
								app.setMaxTimeout(params["maxTimeout"]);
							}

							result = app.setReadyState();
						}
					}

					if (typeof(cb) === "function")
					{
						cb(result);
					}
				};
				this._placementInterface.prototype.setSubscription = function (params, cb) {
					var result = false;

					if (BX.type.isPlainObject(params) && params.hasOwnProperty("instanceId")
						&& params.hasOwnProperty("fields") && BX.type.isArray(params["fields"]))
					{
						var app = BX.Crm.RequisiteExternalSearchApplication.get(params["instanceId"]);
						if (app)
						{
							result = app.setSubscription(params["fields"]);
						}
					}

					if (typeof(cb) === "function")
					{
						cb(result);
					}
				};
				this._placementInterface.prototype.getValues = function (params, cb) {
					var result = [];

					if (BX.type.isPlainObject(params) && params.hasOwnProperty("instanceId")
						&& params.hasOwnProperty("fields") && BX.type.isArray(params["fields"]))
					{
						var app = BX.Crm.RequisiteExternalSearchApplication.get(params["instanceId"]);
						if (app)
						{
							result = app.getValues(params["fields"]);
						}
					}

					if (typeof(cb) === "function")
					{
						cb(result);
					}
				};
				this._placementInterface.prototype.setResultReady = function (params, cb) {
					var result = false;

					if (BX.type.isPlainObject(params)
						&& params.hasOwnProperty("instanceId") && BX.type.isNotEmptyString(params["instanceId"])
						&& params.hasOwnProperty("token") && BX.type.isNotEmptyString(params["token"]))
					{
						var app = BX.Crm.RequisiteExternalSearchApplication.get(params["instanceId"]);
						if (app)
						{
							result = app.setResultReady(params["token"]);
						}
					}

					if (typeof(cb) === "function")
					{
						cb(result);
					}
				};
				this._placementInterface.prototype.setResult = function (params, cb) {
					var result = false;

					if (BX.type.isPlainObject(params)
						&& params.hasOwnProperty("instanceId") && BX.type.isNotEmptyString(params["instanceId"])
						&& params.hasOwnProperty("token") && BX.type.isNotEmptyString(params["token"])
						&& params.hasOwnProperty("result") && BX.type.isArray(params["result"]))
					{
						var app = BX.Crm.RequisiteExternalSearchApplication.get(params["instanceId"]);
						if (app)
						{
							result = app.setResult(params["token"], params["result"]);
						}
					}

					if (typeof(cb) === "function")
					{
						cb(result);
					}
				};
				this._placementInterface.prototype.setClosedState = function(params, cb) {
					var result = false;

					if (BX.type.isPlainObject(params) && params.hasOwnProperty("instanceId"))
					{
						var app = BX.Crm.RequisiteExternalSearchApplication.get(params["instanceId"]);
						if (app)
						{
							result = app.onClose();
						}
					}

					if (typeof(cb) === "function")
					{
						cb(result);
					}
				};
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormFieldMapInit');
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormFieldChange');
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormFieldRemove');
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormAddressAdd');
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormAddressRemove');
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormApplicationClose');
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormBankDetailAdd');
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormBankDetailRemove');
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormResultSelect');
				this._placementInterface.prototype.events.push('onCrmRequisiteEditFormResultCancel');
			}
		},
		loadApplication: function(handler)
		{
			var app = BX.Crm.RequisiteExternalSearchApplication.create(
				null,
				{
					containerId: this._formContainerId,
					entityTypeId: this.getSetting("entityTypeId", 0),
					entityId: this.getSetting("entityId", 0),
					presetId: this.getSetting("presetId", 0),
					countryId: this.getSetting("countryId", 0),
					requisiteId: this.getSetting("requisitePseudoId", 0),
					handler: handler,
					manager: this,
					loadCallback: this._loadAppHandler,
					closeCallback: this._closeAppHandler
				}
			);
			this._apps.push(app);
			app.load();
		},
		onLoadApplication: function(appId)
		{
			BX.onCustomEvent("onCrmRequisiteEditFormFieldMapInit", [{
				instanceId: appId,
				fields: this.getSetting("fields", []),
				fieldMap: this.getSetting("fieldMap", [])
			}]);
		},
		onCloseApplication: function(appId)
		{
			var app, index;

			this.clearSubscriptionByApplication(appId);

			app = BX.Crm.RequisiteExternalSearchApplication.get(appId);
			if (app)
			{
				index = this._apps.indexOf(app);
				if (index >= 0)
				{
					this._apps.splice(index, 1);
				}
			}
		},
		clearSubscriptionByApplication: function(appId)
		{
			var fieldId, index, appList;

			for (fieldId in this._subscriptionMap)
			{
				if (this._subscriptionMap.hasOwnProperty(fieldId))
				{
					appList = this._subscriptionMap[fieldId];
					index = appList.indexOf(appId);
					if (index >= 0)
					{
						appList.splice(index, 1);
					}
					if (appList.length <= 0)
					{
						this.deleteFieldController(fieldId);
						this.activateDefaultFieldController(fieldId);
					}
				}
			}
		},
		clearSubscriptionByFields: function(fields)
		{
			var i;

			if (BX.type.isArray(fields))
			{
				for (i = 0; i < fields.length; i++)
				{
					if (this._subscriptionMap.hasOwnProperty(fields[i]))
					{
						delete this._subscriptionMap[fields[i]];
						this.deleteFieldController(fields[i]);
						this.activateDefaultFieldController(fields[i]);
					}
				}
			}
		},
		setSubscription: function(appId, subscription)
		{
			if (BX.type.isArray(subscription) && subscription.length > 0
				&& BX.Crm.RequisiteExternalSearchApplication.check(appId))
			{
				var i, j, fieldId, exist, subscribe, unsubscribe, apps;

				exist = [];
				subscribe = [];
				unsubscribe = [];
				for (fieldId in this._subscriptionMap)
				{
					if (this._subscriptionMap.hasOwnProperty(fieldId))
					{
						if (this._subscriptionMap[fieldId].indexOf(appId) >= 0 && exist.indexOf(fieldId) < 0)
						{
							exist.push(fieldId);
						}
					}
				}

				for (i = 0; i < exist.length; i++)
				{
					if (subscription.indexOf(exist[i]) < 0 && unsubscribe.indexOf(exist[i]) < 0)
					{
						unsubscribe.push(exist[i]);
					}
				}

				for (i = 0; i < subscription.length; i++)
				{
					if (exist.indexOf(subscription[i]) < 0 && subscribe.indexOf(subscription[i]) < 0)
					{
						subscribe.push(subscription[i]);
					}
				}

				var fields = this.getSetting("fields", []);
				if (BX.type.isArray(fields) && fields.length > 0)
				{
					for (i = 0; i < fields.length; i++)
					{
						fieldId = fields[i]["id"];
						if (subscribe.indexOf(fieldId) >= 0)
						{
							if (fields[i]["active"])
							{
								if (this._subscriptionMap.hasOwnProperty(fieldId))
								{
									if (this._subscriptionMap[fieldId].indexOf(appId) < 0)
									{
										this._subscriptionMap[fieldId].push(appId);
									}
								}
								else
								{
									this._subscriptionMap[fieldId] = [appId];
									this.deactivateDefaultFieldController(fieldId);
									this.createFieldController(fields[i]);
								}
							}
						}
						else if (unsubscribe.indexOf(fieldId) >= 0)
						{
							apps = this._subscriptionMap[fieldId];
							if (apps.indexOf(appId) >= 0)
							{
								apps.splice(index, 1);
							}
							if (apps.length <= 0)
							{
								this.deleteFieldController(fieldId);
								this.activateDefaultFieldController(fieldId);
							}
						}
					}
				}
			}
		},
		getValues: function(appId, fields)
		{
			var result, i, j, fieldsSettings;

			result = [];

			if (this._formContainer
				&& BX.type.isArray(fields)
				&& fields.length > 0)
			{
				fieldsSettings = this.getSetting("fields", []);
				if (BX.type.isArray(fieldsSettings) && fieldsSettings.length > 0)
				{
					for (i = 0; i < fieldsSettings.length; i++)
					{
						for (j = 0; j < fields.length; j++)
						{
							if (fieldsSettings[i]["id"] === fields[j])
							{
								result.push({fieldId: fields[j], value: this.getFieldValue(fieldsSettings[i])});
							}
						}
					}
				}
			}
			
			return result;
		},
		checkToken: function(token, appId)
		{
			var result = false;

			if (!BX.type.isString(appId))
			{
				appId = "";
			}

			if (BX.type.isNotEmptyString(token)
				&& this._isRequestRunning
				&& this._requestContext["token"] === token)
			{
				if (this._requestContext["firstResponseAppId"] === "" || appId === "")
				{
					this._requestContext["firstResponseAppId"] = appId;
				}
				if (this._requestContext["firstResponseAppId"] === appId)
				{
					result = true;
				}
			}

			return result;
		},
		setResultReady: function(appId, token)
		{
			var result, context, i;

			result = this.checkToken(token, appId);

			return result;
		},
		setResult: function(appId, token, resultData)
		{
			var result, i;

			result = this.checkToken(token, appId);

			if (result)
			{
				if (this._requestContext["timeoutId"] !== null)
				{
					window.clearTimeout(this._requestContext["timeoutId"]);
					this._requestContext["timeoutId"] = null;
				}

				for (i = 0; i < resultData.length; i++)
				{
					resultData[i]["caption"] = resultData[i]["TITLE"];
					resultData[i]["fields"] = {fields: resultData[i]["FIELDS"]};
					delete resultData[i]["TITLE"];
					delete resultData[i]["FIELDS"];
				}

				this._requestContext["onSuccessHandler"](
					{
						DATA: {
							SELECT_PARAMS: {token: token, appId: appId},
							ITEMS: resultData
						}
					}
				);
			}

			return result;
		},
		getSubscribedAppsByFieldId: function(fieldId)
		{
			var result = [];

			if (this._subscriptionMap.hasOwnProperty(fieldId))
			{
				if (BX.type.isArray(this._subscriptionMap[fieldId]))
				{
					result = this._subscriptionMap[fieldId];
				}
			}

			return result;
		},
		getFieldControl: function(fieldSettings)
		{
			var result, selector, control;

			result = null;

			if (BX.type.isPlainObject(fieldSettings)
				&& fieldSettings.hasOwnProperty("id")
				&& fieldSettings.hasOwnProperty("formId")
				&& fieldSettings.hasOwnProperty("inputType")
				&& this._formContainer)
			{
				// escaping attribute value
				selector = "[name=" + fieldSettings["formId"].replace(/([\["':-=/\\\]])/g, "\\\$1") + "]";

				switch(fieldSettings["inputType"])
				{
					case "text":
					case "checkbox":
						selector = "input[type=" + fieldSettings["inputType"] + "]" + selector;
						break;
					case "textarea":
						selector = "textarea" + selector;
						break;
					default:
						selector = null;
				}

				if (selector)
				{
					control = this._formContainer.querySelector(selector);
					if (control)
					{
						result = control;
					}
				}
			}

			return result;
		},
		activateDefaultFieldController: function(fieldId)
		{
			var i, defaultFieldControllers;

			defaultFieldControllers = this.getSetting("defaultFieldControllers", []);
			if (BX.type.isArray(defaultFieldControllers))
			{
				for (i = 0; i < defaultFieldControllers.length; i++)
				{
					if (defaultFieldControllers[i]["fieldId"] === fieldId)
					{
						if (!defaultFieldControllers[i]["controller"].isActive())
						{
							defaultFieldControllers[i]["controller"].activate();
						}
					}
				}
			}

		},
		deactivateDefaultFieldController: function(fieldId)
		{
			var i, defaultFieldControllers;

			defaultFieldControllers = this.getSetting("defaultFieldControllers", []);
			if (BX.type.isArray(defaultFieldControllers))
			{
				for (i = 0; i < defaultFieldControllers.length; i++)
				{
					if (defaultFieldControllers[i]["fieldId"] === fieldId)
					{
						if (defaultFieldControllers[i]["controller"].isActive())
						{
							defaultFieldControllers[i]["controller"].deactivate();
						}
					}
				}
			}

		},
		createFieldController: function(fieldSettings)
		{
			var result, selector, control;

			result = false;

			control = this.getFieldControl(fieldSettings);
			if (control)
			{
				BX.Crm.RequisiteExternalSearchFieldController.create(
					this.getFieldControllerId(fieldSettings["id"]),
					{
						manager: this,
						fieldId: fieldSettings["id"],
						fieldType: fieldSettings["dataType"],
						input: control,
						callbacks: {
							onFieldsLoad: this._searchResultHandler,
							onFieldsLoadCancel: this._searchResultCancelHandler
						}
					}
				);
				result =  true;
			}

			return result;
		},
		deleteFieldController: function(fieldId)
		{
			var controllerId;

			controllerId = this.getFieldControllerId(fieldId);
			BX.Crm.RequisiteExternalSearchFieldController.delete(controllerId);
		},
		getFieldControllerId: function(fieldId)
		{
			return this._id + "." + fieldId;
		},
		startSearchRequest: function(fieldId, needle, onSuccessHandler, onFailureHandler)
		{
			if (BX.type.isFunction(onFailureHandler))
			{
				var isSuccess, apps, app, appId, i, changedFields, token, timeout, appTimeout;

				isSuccess = false;

				if (!this._isRequestRunning && BX.type.isFunction(onSuccessHandler))
				{
					apps = this.getSubscribedAppsByFieldId(fieldId);
					if (apps.length > 0)
					{
						isSuccess = true;
						token = "bxcrmrqextst" + Math.random().toString().substring(2);
						this._isRequestRunning = true;
						this._requestContext["token"] = token;
						this._requestContext["fieldId"] = fieldId;
						this._requestContext["needle"] = needle;
						this._requestContext["onSuccessHandler"] = onSuccessHandler;
						this._requestContext["onFailureHandler"] = onFailureHandler;

						timeout = 0;
						changedFields = [{fieldId: fieldId, value: needle}];
						for (i = 0; i < apps.length; i++ )
						{
							appId = apps[i];
							this._requestContext["appIdList"].push(appId);
							window.setTimeout(function (appId, token, changedFields) {
								return function () {
									var app = BX.Crm.RequisiteExternalSearchApplication.get(appId);
									if (app)
									{
										app.onFieldsChange(token, changedFields);
									}
								}
							}(appId, token, changedFields));

							app = BX.Crm.RequisiteExternalSearchApplication.get(appId);
							if (app)
							{
								appTimeout = app.getMaxTimeout();
								if (appTimeout > 0 && appTimeout > timeout)
								{
									timeout = appTimeout;
								}
							}
						}
						if (timeout > 0)
						{
							this._requestContext["timeout"] = timeout;
						}
						this._requestContext["timeoutId"] = window.setTimeout(function (manager, token) {
							return function () {
								manager.onRequestTimeout(token);
							}
						}(this, token), this._requestContext["timeout"]);
					}
				}

				if (!isSuccess)
				{
					onFailureHandler();
				}
			}
		},
		onRequestTimeout: function(token)
		{
			var i, appIdList, app;

			if (this._isRequestRunning && this.checkToken(token))
			{
				this._isRequestRunning = false;
				appIdList = this._requestContext["appIdList"];
				for (i = 0; i < appIdList.length; i++)
				{
					app = BX.Crm.RequisiteExternalSearchApplication.get(appIdList[i]);
					if (app)
					{
						app.onCancelFieldsChange();
					}
				}
				this._requestContext["onFailureHandler"]();
				this._clearRequestContext();
			}
		},
		getAddressTypeIndex: function()
		{
			var result, fieldList, regexp, i, matches, addressTypeId;

			result = [];

			fieldList = this.getSetting("fields", []);
			if (BX.type.isArray(fieldList))
			{
				regexp = new RegExp("^REQUISITE\\.n?\\d+\\.RQ_ADDR\\.(\\d+)\\.\\w+$");
				for (i = 0; i < fieldList.length; i++)
				{
					matches = regexp.exec(fieldList[i]["id"]);
					if (matches)
					{
						addressTypeId = parseInt(matches[1]);
						if (!isNaN(addressTypeId) && addressTypeId > 0)
						{
							if (result.indexOf(addressTypeId) < 0)
							{
								result.push(addressTypeId);
							}
						}
					}
				}
			}

			return result;
		},
		getBankDetailIdIndex: function()
		{
			var result, i, fieldsSettings, regexp, matches, bankDetailId;

			result = [];

			fieldsSettings = this.getSetting("fields", []);
			if (BX.type.isArray(fieldsSettings))
			{
				regexp = new RegExp("^REQUISITE\\.n?\\d+\\.BANK_DETAILS\\.(n?\\d+)\\.\\w+$");
				for (i = 0; i < fieldsSettings.length; i++)
				{
					matches = regexp.exec(fieldsSettings[i]["id"]);
					if (matches)
					{
						bankDetailId = parseInt(matches[1]);
						if (isNaN(bankDetailId) || bankDetailId < 0)
						{
							bankDetailId = matches[1];
						}
						if (result.indexOf(bankDetailId) < 0)
						{
							result.push(bankDetailId);
						}
					}
				}
			}

			return result;
		},
		addBlocksAsNeeded: function(token, appId, fieldMap)
		{
			var i, j, k, blockList, blockFieldMap, addressTypeId, addressTypeIndex,
				bankDetailIdIndex, bankDetailId;

			addressTypeIndex = this.getAddressTypeIndex();
			bankDetailIdIndex = this.getBankDetailIdIndex();

			if (BX.type.isArray(fieldMap))
			{
				for (i = 0; i < fieldMap.length; i++)
				{
					if (fieldMap[i].hasOwnProperty("id")
						&& BX.type.isArray(fieldMap[i]["value"]))
					{
						if (fieldMap[i]["id"] === "RQ_ADDR")
						{
							blockList = fieldMap[i]["value"];
							for (j = 0; j < blockList.length; j++)
							{
								blockFieldMap = blockList[j];
								for (k = 0; k < blockFieldMap.length; k++)
								{
									if (blockFieldMap[k].hasOwnProperty("id")
										&& blockFieldMap[k].hasOwnProperty("value")
										&& blockFieldMap[k]["id"] === "TYPE_ID")
									{
										addressTypeId = parseInt(blockFieldMap[k]["value"]);
										if (!isNaN(addressTypeId)
											&& addressTypeId > 0
											&& addressTypeIndex.indexOf(addressTypeId) < 0)
										{
											if (this.addAddress(
												token,
												appId,
												addressTypeId))
											{
												addressTypeIndex.push(addressTypeId);
											}
										}
										break;
									}
								}
							}
						}
						else if (fieldMap[i]["id"] === "BANK_DETAILS")
						{
							blockList = fieldMap[i]["value"];
							if (blockList.length > bankDetailIdIndex.length)
							{
								for (j = bankDetailIdIndex.length; j < blockList.length; j++)
								{
									bankDetailId = this.addBankDetail(token, appId);
									if (BX.type.isNumber(bankDetailId) && bankDetailId > 0
										|| BX.type.isNotEmptyString(bankDetailId))
									{
										bankDetailIdIndex.push(bankDetailId);
									}
								}
							}
						}
					}
				}
			}

			return {
				addressTypeIndex: addressTypeIndex,
				bankDetailIdIndex: bankDetailIdIndex
			};
		},
		onResultSelect: function(result, selectParams)
		{
			var fieldMap, blockIndexInfo, i, j, k, requisiteId, fieldIdIndex, fieldValueIndex, blockList,
				blockFieldMap, addressTypeId, addressTypeIndex, bankDetailIdIndex, bankDetailId, fieldsSettings,
				app;

			if (this._isRequestRunning
				&& BX.type.isPlainObject(result)
				&& result.hasOwnProperty("fields")
				&& BX.type.isArray(result["fields"])
				&& result["fields"].length > 0
				&& BX.type.isPlainObject(selectParams)
				&& selectParams.hasOwnProperty("appId")
				&& selectParams.hasOwnProperty("token")
				&& selectParams.hasOwnProperty("index")
				&& this.checkToken(selectParams["token"], selectParams["appId"]))
			{
				fieldMap = result["fields"];
				blockIndexInfo = this.addBlocksAsNeeded(selectParams["token"], selectParams["appId"], fieldMap);
				if (BX.type.isPlainObject(blockIndexInfo)
					&& blockIndexInfo.hasOwnProperty("addressTypeIndex")
					&& BX.type.isArray(blockIndexInfo["addressTypeIndex"])
					&& blockIndexInfo.hasOwnProperty("bankDetailIdIndex")
					&& BX.type.isArray(blockIndexInfo["bankDetailIdIndex"]))
				{
					addressTypeIndex = blockIndexInfo["addressTypeIndex"];
					bankDetailIdIndex = blockIndexInfo["bankDetailIdIndex"];
					requisiteId = this.getSetting("requisitePseudoId", 0);
					if (BX.type.isNumber(requisiteId) && requisiteId > 0
						|| BX.type.isNotEmptyString(requisiteId))
					{
						fieldIdIndex = [];
						fieldValueIndex = [];
						for (i = 0; i < fieldMap.length; i++)
						{
							if (fieldMap[i].hasOwnProperty("id")
								&& BX.type.isArray(fieldMap[i]["value"]))
							{
								if (fieldMap[i]["id"] === "RQ_ADDR")
								{
									blockList = fieldMap[i]["value"];
									for (j = 0; j < blockList.length; j++)
									{
										addressTypeId = 0;
										blockFieldMap = blockList[j];
										for (k = 0; k < blockFieldMap.length; k++)
										{
											if (blockFieldMap[k].hasOwnProperty("id")
												&& blockFieldMap[k].hasOwnProperty("value"))
											{
												if (blockFieldMap[k]["id"] === "TYPE_ID")
												{
													addressTypeId = parseInt(blockFieldMap[k]["value"]);
													break;
												}
											}
										}
										if (!isNaN(addressTypeId)
											&& addressTypeId > 0
											&& addressTypeIndex.indexOf(addressTypeId) >= 0)
										{
											for (k = 0; k < blockFieldMap.length; k++)
											{
												if (blockFieldMap[k].hasOwnProperty("id")
													&& blockFieldMap[k].hasOwnProperty("value"))
												{
													if (blockFieldMap[k]["id"] !== "TYPE_ID")
													{
														fieldIdIndex.push(
															"REQUISITE." + requisiteId + ".RQ_ADDR." +
															addressTypeId + "." + blockFieldMap[k]["id"]
														);
														fieldValueIndex.push(blockFieldMap[k]["value"]);
													}
												}
											}
										}
									}
								}
								else if (fieldMap[i]["id"] === "BANK_DETAILS")
								{
									blockList = fieldMap[i]["value"];
									for (j = 0; j < blockList.length; j++)
									{
										if (bankDetailIdIndex.hasOwnProperty(j))
										{
											bankDetailId = bankDetailIdIndex[j];
											blockFieldMap = blockList[j];
											for (k = 0; k < blockFieldMap.length; k++)
											{
												if (blockFieldMap[k].hasOwnProperty("id")
													&& blockFieldMap[k].hasOwnProperty("value"))
												{
													fieldIdIndex.push(
														"REQUISITE." + requisiteId + ".BANK_DETAILS." +
														bankDetailId + "." + blockFieldMap[k]["id"]
													);
													fieldValueIndex.push(blockFieldMap[k]["value"]);
												}
											}
										}
									}
								}
							}
							else
							{
								fieldIdIndex.push("REQUISITE." + requisiteId + "." + fieldMap[i]["id"]);
								fieldValueIndex.push(fieldMap[i]["value"]);
							}
						}
					}
				}

				fieldsSettings = this.getSetting("fields", []);
				if (BX.type.isArray(fieldsSettings)
					&& fieldsSettings.length > 0)
				{
					for (i = 0; i < fieldsSettings.length; i++)
					{
						for (j = 0; j < fieldIdIndex.length; j++)
						{
							if (fieldsSettings[i]["id"] === fieldIdIndex[j]
								&& fieldsSettings[i]["changeable"])
							{
								this.setFieldValue(fieldsSettings[i], fieldValueIndex[j]);
							}
						}
					}
				}

				app = BX.Crm.RequisiteExternalSearchApplication.get(selectParams["appId"]);
				if (app)
				{
					app.onFormResultSelect(selectParams["token"], selectParams["index"]);
				}

				this._isRequestRunning = false;
				this._clearRequestContext();
			}
		},
		onResultCancel: function(dialogId, selectParams)
		{
			if (this._isRequestRunning
				&& BX.type.isPlainObject(selectParams)
				&& selectParams.hasOwnProperty("appId")
				&& selectParams.hasOwnProperty("token")
				&& this.checkToken(selectParams["token"], selectParams["appId"]))
			{
				app = BX.Crm.RequisiteExternalSearchApplication.get(selectParams["appId"]);
				if (app)
				{
					app.onFormResultCancel(selectParams["token"]);
				}

				this._isRequestRunning = false;
				this._clearRequestContext();
			}
		},
		getFieldValue: function(fieldSettings)
		{
			var result, control;

			switch (fieldSettings["inputType"])
			{
				case "text":
				case "textarea":
					result = "";
					break;
				case "checkbox":
					result = false;
					break;
				default:
					result = null;
			}

			control = this.getFieldControl(fieldSettings);
			if (control)
			{
				switch (fieldSettings["inputType"])
				{
					case "text":
					case "textarea":
						result = control.value;
						break;
					case "checkbox":
						result = control.checked;
						break;
					default:
				}
			}

			return result;
		},
		setFieldValue: function(fieldSettings, value)
		{
			var control;

			control = this.getFieldControl(fieldSettings);
			if (control)
			{
				switch (fieldSettings["inputType"])
				{
					case "text":
					case "textarea":
						control.value = value;
						break;
					case "checkbox":
						control.checked = !!value;
						break;
					default:
				}
			}
		},
		getFieldSettingsById: function(fieldId)
		{
			var result = null;

			var fieldsSettings, i;

			fieldsSettings = this.getSetting("fields", []);
			if (BX.type.isArray(fieldsSettings) && fieldsSettings.length > 0)
			{
				for (i = 0; i < fieldsSettings.length; i++)
				{
					if (fieldsSettings[i]["id"] === fieldId)
					{
						result = fieldsSettings[i];
						break;
					}
				}
			}

			return result;
		},
		getFieldSettingsByFormId: function(fieldFormId)
		{
			var result = null;

			var fieldsSettings, i;

			fieldsSettings = this.getSetting("fields", []);
			if (BX.type.isArray(fieldsSettings) && fieldsSettings.length > 0)
			{
				for (i = 0; i < fieldsSettings.length; i++)
				{
					if (fieldsSettings[i]["formId"] === fieldFormId)
					{
						result = fieldsSettings[i];
						break;
					}
				}
			}

			return result;
		},
		filterExistingFields: function(fields)
		{
			var result, fieldsIndex, fieldsSettings, i;

			result = [];
			if (BX.type.isArray(fields))
			{
				fieldsIndex = {};
				fieldsSettings = this.getSetting("fields", []);
				if (BX.type.isArray(fieldsSettings))
				{
					for (i = 0; i < fieldsSettings.length; i++)
					{
						if (!fieldsIndex.hasOwnProperty(fieldsSettings[i]["id"]))
						{
							fieldsIndex[fieldsSettings[i]["id"]] = true;
						}
					}
				}

				for (i = 0; i < fields.length; i++)
				{
					if (BX.type.isPlainObject(fields[i])
						&& fields[i].hasOwnProperty("id")
						&& !fieldsIndex.hasOwnProperty(fields[i]["id"]))
					{
						result.push(fields[i]);
					}
				}
			}

			return result;
		},
		getFieldsByIdPrefix: function(prefix)
		{
			var result, fieldsSettings;

			result = [];
			fieldsSettings = this.getSetting("fields", []);
			if (BX.type.isArray(fieldsSettings))
			{
				for (i = 0; i < fieldsSettings.length; i++)
				{
					if (fieldsSettings[i]["id"].length > prefix.length
						&& fieldsSettings[i]["id"].substr(0, prefix.length) === prefix)
					{
						result.push(fieldsSettings[i]["id"]);
					}
				}
			}

			return result;
		},
		appendFields: function(fields)
		{
			var fieldsSettings;

			if (BX.type.isArray(fields) && fields.length > 0)
			{
				fieldsSettings = this.getSetting("fields", []);
				if (BX.type.isArray(fieldsSettings))
				{
					this.setSetting("fields", fieldsSettings.concat(fields));
				}
			}
		},
		removeFields: function(fields)
		{
			var fieldsSettings, i;

			if (BX.type.isArray(fields) && fields.length > 0)
			{
				fieldsSettings = this.getSetting("fields", []);
				if (BX.type.isArray(fieldsSettings))
				{
					for (i = 0; i < fieldsSettings.length; i++)
					{
						if (fields.indexOf(fieldsSettings[i]["id"]) >= 0)
						{
							fieldsSettings.splice(i--, 1);
						}
					}
				}
			}
		},
		addAddress: function(token, appId, typeId)
		{
			var result;

			result = this.checkToken(token, appId);

			if (result)
			{
				if(!BX.type.isNumber(typeId))
				{
					typeId = parseInt(typeId);
				}
				if(!isNaN(typeId) && typeId > 0)
				{
					if (BX.type.isArray(this._addressEditors))
					{
						for (i = 0; i < this._addressEditors.length; i++)
						{
							result = !!this._addressEditors[i].createItem(
								typeId,
								this.getSetting("addressOriginatorId", ""),
								true
							);

							if (!result)
							{
								break;
							}
						}
					}
				}
			}

			return result;
		},
		removeAddress: function(token, appId, typeId)
		{
			var result, item;

			result = this.checkToken(token, appId);

			if (result)
			{
				if(!BX.type.isNumber(typeId))
				{
					typeId = parseInt(typeId);
				}
				if(!isNaN(typeId) && typeId > 0)
				{
					if (BX.type.isArray(this._addressEditors))
					{
						for (i = 0; i < this._addressEditors.length; i++)
						{
							item = this._addressEditors[i].getItemByTypeId(typeId);
							if (item)
							{
								item.markAsDeleted();
								result = this._addressEditors[i].removeItem(item);
							}

							if (!result)
							{
								break;
							}
						}
					}
				}
			}

			return result;
		},
		addBankDetail: function(token, appId)
		{
			var result, bankDetailAreaId, bankDetailArea;

			result = 0;

			if (this.checkToken(token, appId))
			{
				bankDetailAreaId = this.getSetting("bankDetailAreaId", "");
				if (BX.type.isNotEmptyString(bankDetailAreaId)
					&& typeof(BX.Crm.RequisiteBankDetailsArea) !== "undefined"
					&& BX.Crm.RequisiteBankDetailsArea.items
					&& BX.Crm.RequisiteBankDetailsArea.items.hasOwnProperty(bankDetailAreaId))
				{
					bankDetailArea = BX.Crm.RequisiteBankDetailsArea.items[bankDetailAreaId];
					result = bankDetailArea.addBlock();
				}
			}

			return result;
		},
		removeBankDetail: function(token, appId, id)
		{
			var result, bankDetailAreaId, bankDetailArea, bankDetailBlock;

			result = false;

			if (this.checkToken(token, appId) && (BX.type.isNumber(id) && id > 0 || BX.type.isNotEmptyString(id)))
			{
				bankDetailAreaId = this.getSetting("bankDetailAreaId", "");
				if (BX.type.isNotEmptyString(bankDetailAreaId)
					&& typeof(BX.Crm.RequisiteBankDetailsArea) !== "undefined"
					&& BX.Crm.RequisiteBankDetailsArea.items
					&& BX.Crm.RequisiteBankDetailsArea.items.hasOwnProperty(bankDetailAreaId))
				{
					bankDetailArea = BX.Crm.RequisiteBankDetailsArea.items[bankDetailAreaId];
					bankDetailBlock = bankDetailArea.getBlockByPseudoId(id);
					if (bankDetailBlock)
					{
						bankDetailBlock.markAsDeleted();
						result = true;
					}
				}
			}

			return result;
		},
		onFormFieldRemove: function(fieldFormId)
		{
			var fieldSettings, i;

			fieldSettings = this.getFieldSettingsByFormId(fieldFormId);
			if (fieldSettings)
			{
				this.clearSubscriptionByFields([fieldSettings["id"]]);
				for (i = 0; i < this._apps.length; i++)
				{
					this._apps[i].onFieldsRemove([fieldSettings["id"]]);
				}
			}
		},
		onFormAddressAdd: function(addressEditor, addressItem)
		{
			var addressTypeId, scheme, i, fields;

			fields = [];
			if (addressEditor !== null
				&& addressItem !== null
				&& typeof(addressEditor) === "object"
				&& typeof(addressItem) === "object")
			{
				addressTypeId = parseInt(addressItem.getTypeId());
				if (addressTypeId > 0)
				{
					scheme = addressEditor.getScheme();
					if (BX.type.isArray(scheme))
					{
						for (i = 0; i < scheme.length; i++)
						{
							if (scheme[i]["type"] === "text" || scheme[i]["type"] === "multilinetext")
							{
								fields.push(
									{
										id: "REQUISITE." + this.getSetting("requisitePseudoId", "n0") +
											".RQ_ADDR." + addressTypeId + "." + scheme[i]["name"],
										formId: addressEditor.prepareQualifiedName(scheme[i]["name"],
											{typeId: addressTypeId}),
										name: addressEditor.getFieldLabel(scheme[i]["name"]),
										inputType: (scheme[i]["type"] === "multilinetext") ? "textarea" : "text",
										dataType: "string",
										active: true,
										changeable: true
									}
								);
							}
						}

						fields = this.filterExistingFields(fields);
						this.appendFields(fields);

						for (i = 0; i < this._apps.length; i++)
						{
							this._apps[i].onFormAddressAdd(addressTypeId, fields);
						}
					}
				}
			}
		},
		onFormAddressRemove: function(addressEditor, addressItem)
		{
			var addressTypeId, fieldsSettings, i, fields, fieldIdPrefix;

			if (addressEditor !== null
				&& addressItem !== null
				&& typeof(addressEditor) === "object"
				&& typeof(addressItem) === "object")
			{
				addressTypeId = parseInt(addressItem.getTypeId());
				if (addressTypeId > 0)
				{
					fieldIdPrefix = "REQUISITE." + this.getSetting("requisitePseudoId", "n0") + ".RQ_ADDR." +
						addressTypeId + ".";

					fields = this.getFieldsByIdPrefix(fieldIdPrefix);

					this.clearSubscriptionByFields(fields);
					this.removeFields(fields);

					for (i = 0; i < this._apps.length; i++)
					{
						this._apps[i].onFormAddressRemove(addressTypeId, fields);
					}
				}
			}
		},
		onFormBankDetailAdd: function(params)
		{
			var bdBlock, fieldList, fields, i;

			if (BX.type.isPlainObject(params)
				&& params.hasOwnProperty("bankDetailBlock")
				&& params["bankDetailBlock"] !== null
				&& typeof(params["bankDetailBlock"]) === "object"
				&& params.hasOwnProperty("formId")
				&& BX.type.isNotEmptyString(params["formId"])
				&& params.hasOwnProperty("bankDetailPseudoId")
				&& (BX.type.isNotEmptyString(params["bankDetailPseudoId"])
					|| (BX.type.isNumber(params["bankDetailPseudoId"])
						&& params["bankDetailPseudoId"] > 0))
				&& params["formId"] === this.getSetting("formId", ""))
			{
				bdBlock = params["bankDetailBlock"];
				fieldList = params["bankDetailBlock"].getFieldList();
				if (BX.type.isArray(fieldList))
				{
					fields = [];
					for (i = 0; i < fieldList.length; i++)
					{
						if (fieldList[i]["type"] === "text" || fieldList[i]["type"] === "textarea")
						{
							fields.push(
								{
									id: "REQUISITE." + this.getSetting("requisitePseudoId", "n0") +
										".BANK_DETAILS." + params["bankDetailPseudoId"] + "." + fieldList[i]["name"],
									formId: bdBlock.resolveFieldInputName(fieldList[i]["name"]),
									name: fieldList[i]["title"],
									inputType: fieldList[i]["type"],
									dataType: "string",
									active: fieldList[i]["name"] !== "NAME",
									changeable: true
								}
							);
						}
					}

					fields = this.filterExistingFields(fields);
					this.appendFields(fields);

					for (i = 0; i < this._apps.length; i++)
					{
						this._apps[i].onFormBankDetailAdd(params["bankDetailPseudoId"], fields);
					}
				}
			}
		},
		onFormBankDetailRemove: function(params)
		{
			var pseudoId, fieldsSettings, i, fields, fieldIdPrefix;

			if (params !== null && typeof(params) === "object")
			{
				pseudoId = params.getPseudoId();
				if (BX.type.isNotEmptyString(pseudoId) || (BX.type.isNumber(pseudoId) && pseudoId > 0))
				{
					fieldIdPrefix = "REQUISITE." + this.getSetting("requisitePseudoId", "n0") + ".BANK_DETAILS." +
						pseudoId + ".";

					fields = this.getFieldsByIdPrefix(fieldIdPrefix);

					this.clearSubscriptionByFields(fields);
					this.removeFields(fields);

					for (i = 0; i < this._apps.length; i++)
					{
						this._apps[i].onFormBankDetailRemove(pseudoId, fields);
					}
				}
			}
		}
	};
	BX.Crm.RequisiteExternalSearchManager.items = {};
	BX.Crm.RequisiteExternalSearchManager.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteExternalSearchManager();
		self.initialize(id, settings);
		BX.Crm.RequisiteExternalSearchManager.items[self.getId()] = self;
		return self;
	};
	BX.Crm.RequisiteExternalSearchManager.delete = function(id)
	{
		if (BX.Crm.RequisiteExternalSearchManager.items.hasOwnProperty(id))
		{
			BX.Crm.RequisiteExternalSearchManager.items[id].destroy();
			delete BX.Crm.RequisiteExternalSearchManager.items[id];
		}
	};
}

if(typeof(BX.Crm.RequisiteExternalSearchFieldController) === "undefined")
{
	BX.Crm.RequisiteExternalSearchFieldController = function()
	{
		BX.Crm.RequisiteExternalSearchFieldController.superclass.constructor.apply(this);
		this._manager = null;
		this._onSearchSuccessHandler = BX.delegate(this.onSearchRequestSuccess, this);
		this._onSearchFailureHandler = BX.delegate(this.onRequestFailure, this);
		this._onChangeHandler = BX.delegate(this.onChange, this);
	};
	BX.extend(BX.Crm.RequisiteExternalSearchFieldController, BX.Crm.RequisiteFieldController);
	BX.Crm.RequisiteExternalSearchFieldController.prototype.initialize = function(id, settings)
	{
		this._id = (BX.type.isNotEmptyString(id)) ?
			id : "crm_rq_ext_search_fld_cntrlr" + Math.random().toString().substring(2);
		this._settings = settings ? settings : {};
		this._countryId = this.getSetting("countryId", 0);
		this._typeId = BX.Crm.RequisiteFieldType.undefined;

		this._manager = this.getSetting("manager", null);
		if(!(this._manager instanceof BX.Crm.RequisiteExternalSearchManager))
		{
			throw "BX.Crm.RequisiteExternalSearchFieldController: Could not fild 'manager' parameter in settings.";
		}

		this._fieldId = this.getSetting("fieldId", null);
		if(!BX.type.isNotEmptyString(this._fieldId))
		{
			throw "BX.Crm.RequisiteExternalSearchFieldController: Could not fild 'fieldId' parameter in settings.";
		}

		this._fieldType = this.getSetting("fieldType", "string");
		
		this._serviceUrl = "";

		this._input = this.getSetting("input");
		if(!BX.type.isElementNode(this._input))
		{
			throw "BX.Crm.RequisiteExternalSearchFieldController: Could not fild 'input' parameter in settings.";
		}

		this.activate();
	};
	BX.Crm.RequisiteExternalSearchFieldController.prototype.bindHandlers = function()
	{
		BX.Crm.RequisiteExternalSearchFieldController.superclass.bindHandlers.apply(this);
		
		if ((this._fieldType === "datetime" || this._fieldType === "boolean")
			&& BX.type.isFunction(this._onChangeHandler))
		{
			BX.bind(this._input, "change", this._onChangeHandler);
		}
	};
	BX.Crm.RequisiteExternalSearchFieldController.prototype.unbindHandlers = function()
	{
		BX.Crm.RequisiteExternalSearchFieldController.superclass.unbindHandlers.apply(this);

		if ((this._fieldType === "datetime" || this._fieldType === "boolean")
			&& BX.type.isFunction(this._onChangeHandler))
		{
			BX.unbind(this._input, "change", this._onChangeHandler);
		}
	};
	BX.Crm.RequisiteExternalSearchFieldController.prototype.onChange = function(e)
	{
		if (!this._isActive)
		{
			return;
		}

		e = e || window.event;

		if (this._fieldType === 'boolean')
		{
			if(this._value === this._input.checked)
			{
				return;
			}

			this._value = this._input.checked;
		}
		else
		{
			if(this._value === this._input.value)
			{
				return;
			}

			this._value = this._input.value;
		}

		if(this._timeoutId > 0)
		{
			window.clearTimeout(this._timeoutId);
			this._timeoutId = 0;
		}
		this._timeoutId = window.setTimeout(this._timeoutHandler, 1000);
	};
	BX.Crm.RequisiteExternalSearchFieldController.prototype.validate = function()
	{
		var result;

		result = false;

		this._needle = this._value;

		switch (this._fieldType)
		{
			case "boolean":
				result = BX.type.isBoolean(this._needle);
				break;
			default:
				result = BX.type.isNotEmptyString(this._needle);
		}

		return result;
	};
	BX.Crm.RequisiteExternalSearchFieldController.prototype.startSearchRequest = function()
	{
		if(!this._isActive || this._isRequestRunning)
		{
			return;
		}

		this._isRequestRunning = true;

		this.showLoader();

		this._manager.startSearchRequest(this._fieldId, this._needle, this._onSearchSuccessHandler, this._onSearchFailureHandler);
	};
	BX.Crm.RequisiteExternalSearchFieldController.items = {};
	BX.Crm.RequisiteExternalSearchFieldController.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteExternalSearchFieldController();
		self.initialize(id, settings);
		BX.Crm.RequisiteExternalSearchFieldController.items[self.getId()] = self;
		return self;
	};
	BX.Crm.RequisiteExternalSearchFieldController.check = function(id)
	{
		return BX.Crm.RequisiteExternalSearchFieldController.items.hasOwnProperty(id);
	};
	BX.Crm.RequisiteExternalSearchFieldController.get = function(id)
	{
		if (BX.Crm.RequisiteExternalSearchFieldController.check(id))
		{
			return BX.Crm.RequisiteExternalSearchFieldController.items[id];
		}

		return null;
	};
	BX.Crm.RequisiteExternalSearchFieldController.delete = function(id)
	{
		if (BX.Crm.RequisiteExternalSearchFieldController.items.hasOwnProperty(id))
		{
			BX.Crm.RequisiteExternalSearchFieldController.items[id].destroy();
			delete BX.Crm.RequisiteExternalSearchFieldController.items[id];
		}
	};
}
