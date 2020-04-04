BX.namespace("BX.Crm");

if(typeof(BX.Crm.RequisiteFormEditor) === "undefined")
{
	BX.Crm.RequisiteFormEditor = function (parameters)
	{
		this.containerId = parameters.containerId;
		this.container = BX(this.containerId);
		this.requisiteEntityTypeId = parameters.requisiteEntityTypeId;
		this.requisiteEntityId = parameters.requisiteEntityId;
		this.presetList = parameters.presetList;
		this.presetLastSelectedId = parameters.presetLastSelectedId;
		this.requisiteDataList = parameters.requisiteDataList;
		this.visible = !!parameters.visible;
		this.messages = parameters.messages || {};
		this.presetSelector = null;
		this.requisitePopupManager = null;
		this.requisitePopupAjaxUrl = parameters.requisitePopupAjaxUrl;
		this.requisiteFormEditorAjaxUrl = parameters.requisiteFormEditorAjaxUrl;
		this.blockArea = null;
	};
	BX.Crm.RequisiteFormEditor.prototype =
	{
		initialize: function()
		{
			if (this.container)
			{
				if (this.visible)
					this.container.style.display = "block";

				this.requisiteEditHandler = BX.delegate(this.onRequisiteEdit, this);

				if (!this.blockArea)
				{
					this.blockArea = new BX.Crm.RequisiteFormEditorBlockArea({
						container: this.container,
						nextNode: null,
						editor: this,
						requisiteDataList: this.requisiteDataList,
						ajaxUrl: this.requisiteFormEditorAjaxUrl,
						requisiteEditHandler: this.requisiteEditHandler
					});
				}

				if (!this.presetSelector)
				{
					this.presetSelector = new BX.Crm.RequisitePresetSelectorClass({
						editor: this,
						container: this.container,
						nextNode: ((this.blockArea) ? this.blockArea.getWrapperNode() : null),
						containerId: this.containerId,
						requisiteEntityTypeId: this.requisiteEntityTypeId,
						requisiteEntityId: this.requisiteEntityId,
						presetList: this.presetList,
						presetLastSelectedId: this.presetLastSelectedId,
						requisiteEditHandler: this.requisiteEditHandler
					});
				}
			}
		},
		getMessage: function(msgId)
		{
			return this.messages[msgId];
		},
		onRequisiteEdit: function(requisiteEntityTypeId, requisiteEntityId, presetId, requisiteId, requisiteData,
                                    requisiteDataSign, blockIndex, copyMode)
		{
			if (BX.type.isNumber(blockIndex) && blockIndex >= 0 || BX.type.isNotEmptyString(blockIndex))
				blockIndex = parseInt(blockIndex);
			else
				blockIndex = -1;

			requisiteData = (BX.type.isNotEmptyString(requisiteData)) ? requisiteData : "";
			requisiteDataSign = (BX.type.isNotEmptyString(requisiteDataSign)) ? requisiteDataSign : "";
			copyMode = !!copyMode;

			if (!this.requisitePopupManager)
			{
				this.requisitePopupManager = new BX.Crm.RequisitePopupFormManagerClass({
					editor: this,
					blockArea: this.blockArea,
					requisiteEntityTypeId: requisiteEntityTypeId,
					requisiteEntityId: requisiteEntityId,
					requisiteId: requisiteId,
					requisiteData: requisiteData,
					requisiteDataSign: requisiteDataSign,
					presetId: presetId,
					requisitePopupAjaxUrl: this.requisitePopupAjaxUrl,
					popupDestroyCallback: BX.delegate(this.onRequisitePopupDestroy, this),
					blockIndex: blockIndex,
					copyMode: copyMode
				});
				this.requisitePopupManager.openPopup();
			}
		},
		onRequisitePopupDestroy: function()
		{
			if (this.requisitePopupManager)
			{
				this.requisitePopupManager.destroy();
				this.requisitePopupManager = null;
			}
		}
	};
	BX.Crm.RequisiteFormEditor.items = {};
	BX.Crm.RequisiteFormEditor.create = function (id, parameters)
	{
		var self = new BX.Crm.RequisiteFormEditor(parameters);
		self.initialize();

		this.items[id] = self;
		return self;
	};
}

if(typeof(BX.Crm.RequisiteFormEditorBlockArea) === "undefined")
{
	BX.Crm.RequisiteFormEditorBlockArea = function (parameters)
	{
		this.editor = parameters.editor;
		this.requisiteDataList = parameters.requisiteDataList;
		this.ajaxUrl = parameters.ajaxUrl;
		this.container = parameters.container;
		this.nextNode = parameters.nextNode;
		this.requisiteEditHandler = parameters.requisiteEditHandler;
		this.wrapper = null;

		this.blockList = [];

		if (this.container)
		{
			this.wrapper = BX.create("DIV", {"attrs": {"class": "crm-offer-requisite-blocks"}});
			if (this.wrapper)
			{
				if (this.nextNode)
					this.container.insertBefore(this.wrapper, this.nextNode);
				else
					this.container.appendChild(this.wrapper);
			}
		}

		if (this.requisiteDataList && this.requisiteDataList instanceof Array)
		{
			var requisiteData;
			for (var i = 0; i < this.requisiteDataList.length; i++)
			{
				if (this.requisiteDataList[i]["requisiteId"] && this.requisiteDataList[i]["requisiteData"]
					&& this.requisiteDataList[i]["requisiteDataSign"])
				{
					this.addBlock(
						this.requisiteDataList[i]["requisiteId"],
						this.requisiteDataList[i]["requisiteData"],
						this.requisiteDataList[i]["requisiteDataSign"]
					);
				}
			}
		}
	};
	BX.Crm.RequisiteFormEditorBlockArea.prototype =
	{
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		addBlock: function(requisiteId, requisiteData, requisiteDataSign)
		{
			var blockIndex = this.blockList.length;
			var block = new BX.Crm.RequisiteFormEditorBlock({
				editor: this.editor,
				blockArea: this,
				blockIndex: blockIndex,
				ajaxUrl: this.ajaxUrl,
				container: this.wrapper,
				nextNode: null,
				requisiteEditHandler: this.requisiteEditHandler,
				requisiteId: requisiteId,
				requisiteData: requisiteData,
				requisiteDataSign: requisiteDataSign
			});

			if (block)
				this.blockList[blockIndex] = block;
		},
		updateBlock: function(blockIndex, requisiteId, requisiteData, requisiteDataSign)
		{
			blockIndex = (BX.type.isNumber(blockIndex) && blockIndex >= 0 || BX.type.isNotEmptyString(blockIndex)) ?
				parseInt(blockIndex) : -1;

			if (blockIndex >= 0)
			{
				var block = this.blockList[blockIndex];
				if (block)
				{
					block.update({
						requisiteId: requisiteId,
						requisiteData: requisiteData,
						requisiteDataSign: requisiteDataSign
					});
				}
			}
		},
		onBlockDestroy: function(blockIndex)
		{
			if (blockIndex >= 0 && this.blockList && this.blockList.length > blockIndex)
			{
				this.blockList.splice(blockIndex, 1);
				this.reindexBlocks(blockIndex);
			}
		},
		reindexBlocks: function(indexFrom)
		{
			for (var i = indexFrom; i < this.blockList.length; i++)
				this.blockList[i].setIndex(i);
		}
	};
}

if(typeof(BX.Crm.RequisiteFormEditorBlock) === "undefined")
{
	BX.Crm.RequisiteFormEditorBlock = function (parameters)
	{
		this.editor = parameters.editor;
		this.blockArea = parameters.blockArea;
		this.blockIndex = parameters.blockIndex;
		this.ajaxUrl = parameters.ajaxUrl;
		this.container = parameters.container;
		this.nextNode = parameters.nextNode;
		this.requisiteId = parseInt(parameters.requisiteId);
		this.requisiteDataJson = parameters.requisiteData;
		this.requisiteDataSign = parameters.requisiteDataSign;
		this.requisiteEditHandler = parameters.requisiteEditHandler;

		this.requisiteData = null;
		this.entityTypeId = 0;
		this.entityId = 0;
		this.presetId = 0;

		this.viewData = null;
		this.isRequestRunning = false;

		this.wrapper = null;
		this.closeButtonNode = null;
		this.closeButtonClickHandler = null;
		this.requisiteDataInputNode = null;
		this.requisiteDataSignInputNode = null;

		this.initialize();
	};
	BX.Crm.RequisiteFormEditorBlock.prototype =
	{
		update: function(parameters)
		{
			this.requisiteId = parseInt(parameters.requisiteId);
			this.requisiteDataJson = parameters.requisiteData;
			this.requisiteDataSign = parameters.requisiteDataSign;

			this.requisiteData = null;
			this.entityTypeId = 0;
			this.entityId = 0;
			this.presetId = 0;

			this.viewData = null;
			this.isRequestRunning = false;

			this.initialize();
		},
		initialize: function()
		{
			this.requisiteData = BX.parseJSON(this.requisiteDataJson, this);

			if (this.requisiteData && this.requisiteData["fields"])
			{
				this.entityTypeId = parseInt(this.requisiteData["fields"]["ENTITY_TYPE_ID"]);
				this.entityId = parseInt(this.requisiteData["fields"]["ENTITY_ID"]);
				this.presetId = parseInt(this.requisiteData["fields"]["PRESET_ID"]);
			}

			if (this.requisiteData && this.requisiteData["viewData"])
			{
				this.viewData = this.requisiteData["viewData"];
			}

			if (this.container)
			{
				this.clean();

				if (!this.wrapper)
				{
					this.wrapper = BX.create(
						"DIV",
						{
							"attrs": {"class": "crm-offer-requisite", "data-tab-block": "tabBlock"}
						}
					);
					if (this.wrapper)
					{
						if (this.nextNode)
							this.container.insertBefore(this.wrapper, this.nextNode);
						else
							this.container.appendChild(this.wrapper);
					}
				}
			}

			if (this.wrapper)
			{
				if (this.viewData && this.viewData['title'])
				{
					var titleNode = BX.create(
						"DIV",
						{
							"attrs": {"class": "crm-offer-requisite-title", "style": "cursor: pointer;"},
							"html": BX.util.htmlspecialchars(this.viewData['title'])
						}
					);
					this.wrapper.appendChild(titleNode);

					var closeButton =
						BX.create("SPAN", {"attrs": {"class": "crm-offer-tab-close-btn", "data-tab-block": "closeBtn"}});
					this.wrapper.appendChild(closeButton);

					if (this.viewData['fields'])
					{
						var table, row, cell, i;
						var fields = this.viewData['fields'];
						if (fields instanceof Array && fields.length > 0)
						{
							table = BX.create("TABLE", {"attrs": {"class": "crm-offer-tab-table"}});
							for (i = 0; i < fields.length; i++)
							{
								row = table.insertRow(-1);
								cell = row.insertCell(-1);
								cell.className = "crm-offer-tab-cell";
								cell.innerHTML =
									((fields[i]["title"]) ? BX.util.htmlspecialchars(fields[i]["title"]) : "") + ":";
								cell = row.insertCell(-1);
								cell.className = "crm-offer-tab-cell";
								cell.innerHTML =
									(fields[i]["textValue"]) ?
										BX.util.nl2br(BX.util.htmlspecialchars(fields[i]["textValue"])) : "";
							}
							this.wrapper.appendChild(table);
						}
					}
					if (closeButton)
					{
						this.closeButtonNode = closeButton;
						this.closeButtonClickHandler = BX.delegate(this.onCloseButtonClick, this);
						BX.bind(this.closeButtonNode, "click", this.closeButtonClickHandler);
					}
					if (titleNode)
					{
						this.titleNode = titleNode;
						this.titleClickHandler = BX.delegate(this.onBlockTitleClick, this);
						BX.bind(this.titleNode, "click", this.titleClickHandler);
					}
				}
				if (this.requisiteId === 0 && this.requisiteDataJson && this.requisiteDataSign)
				{
					this.requisiteDataInputNode = BX.create(
						"INPUT",
						{
							"attrs": {
								"type": "hidden",
								"name": "REQUISITE_DATA[" + this.blockIndex + "]",
								"value": this.requisiteDataJson
							}
						}
					);
					this.wrapper.appendChild(this.requisiteDataInputNode);
					this.requisiteDataSignInputNode = BX.create(
						"INPUT",
						{
							"attrs": {
								"type": "hidden",
								"name": "REQUISITE_DATA_SIGN[" + this.blockIndex + "]",
								"value": this.requisiteDataSign
							}
						}
					);
					this.wrapper.appendChild(this.requisiteDataSignInputNode);
				}
			}
		},
		getWrapperNode: function()
		{
			return this.wrapper;
		},
		onCloseButtonClick: function()
		{
			if (this.requisiteId > 0)
				this.startRequisiteDeleteRequest(this.requisiteId);
			else
				this.destroy();
		},
		onBlockTitleClick: function()
		{
			if (this.requisiteEditHandler)
				this.requisiteEditHandler(
					this.entityTypeId,
					this.entityId,
					this.presetId,
					this.requisiteId,
					this.requisiteDataJson,
					this.requisiteDataSign,
					this.blockIndex
				);
		},
		startRequisiteDeleteRequest: function(requisiteId)
		{
			requisiteId = parseInt(requisiteId);
			if(this.isRequestRunning)
				return;

			this.isRequestRunning = true;
			BX.ajax(
				{
					url: this.ajaxUrl,
					method: "POST",
					dataType: "json",
					data: {
						"action": "deleteRequisite",
						"requisite_id": requisiteId
					},
					onsuccess: BX.delegate(this.onRequisiteDeleteRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		setIndex: function(index)
		{
			this.blockIndex = index;
			if (this.requisiteDataInputNode)
				this.requisiteDataInputNode.setAttribute("name", "REQUISITE_DATA[" + this.blockIndex + "]");
			if (this.requisiteDataSignInputNode)
				this.requisiteDataSignInputNode.setAttribute("name", "REQUISITE_DATA_SIGN[" + this.blockIndex + "]");
		},
		onRequisiteDeleteRequestSuccess: function(data)
		{
			var destroy = false;
			var errRequisiteNotFound = 4;
			if (data && data["status"])
			{
				if (data["status"] === "success" && data["response"] && data["response"]["id"]
					&& parseInt(data["response"]["id"]) == this.requisiteId)
				{
					destroy = true;
				}
				else if (data["status"] === "error" && data["errors"] && data["errors"][0]
					&& data["errors"][0]["code"]
					&& parseInt(data["errors"][0]["code"]) === errRequisiteNotFound)
				{
					destroy = true;
				}
			}
			this.isRequestRunning = false;
			if (destroy)
				this.destroy();
		},
		onRequestFailure: function(data)
		{
			this.isRequestRunning = false;
		},
		clean: function(cleanAll)
		{
			cleanAll = !!cleanAll;
			if (this.wrapper)
			{
				if(this.requisiteDataInputNode)
				{
					BX.cleanNode(this.requisiteDataInputNode, true);
					this.requisiteDataInputNode = null;
				}
				if(this.requisiteDataSignInputNode)
				{
					BX.cleanNode(this.requisiteDataSignInputNode, true);
					this.requisiteDataSignInputNode = null;
				}
				if (this.titleNode)
				{
					BX.unbind(this.titleNode, "click", this.titleClickHandler);
					this.titleClickHandler = null;
					this.titleNode = null;
				}
				if (this.closeButtonNode)
				{
					BX.unbind(this.closeButtonNode, "click", this.closeButtonClickHandler);
					this.closeButtonClickHandler = null;
					this.closeButtonNode = null;
				}

				BX.cleanNode(this.wrapper, cleanAll);
			}
		},
		destroy: function()
		{
			this.clean(true);

			if (this.blockArea)
				this.blockArea.onBlockDestroy(this.blockIndex);
		}
	};
}

if(typeof(BX.setTextContent) === "undefined")
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

if(typeof(BX.Crm.RequisiteFormManager) === "undefined")
{
	BX.Crm.RequisiteFormManager = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeId = 0;
		this._entityId = 0;
		this._countryId = 0;
		this._container = null;
		this._isVisible = true;
		this._presetList = null;
		this._presetSelector = null;
		this._presetLastSelectedId = 0;
		this._presetSelectHandler = BX.delegate(this.onPresetSelect, this);
		this._pseudoIdSequence = 0;
		this._fieldNameTemplate = "";
		this._enableFieldMasquerading = false;
		this._formCreateHandler = BX.delegate(this.onFormCreate, this);
		this._forms = {};
		this._isRequestRunning = false;
		this._requestForm = null;
		this._formLoaderUrl = "";
		this._serviceUrl = "";
	};
	BX.Crm.RequisiteFormManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_requisite_form_manager";
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting("containerId", ""));
			if(!this._container)
			{
				throw "BX.Crm.RequisiteFormManager: Could not find container.";
			}

			this._formLoaderUrl = this.getSetting("formLoaderUrl", "");
			if(!BX.type.isNotEmptyString(this._formLoaderUrl))
			{
				throw "BX.Crm.RequisiteFormManager: Could not find parameter 'formLoaderUrl' in settings.";
			}

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.Crm.RequisiteFormManager: Could not find parameter 'serviceUrl' in settings.";
			}

			this._fieldNameTemplate = this.getSetting("fieldNameTemplate", "");
			this._enableFieldMasquerading = this._fieldNameTemplate !== "";

			this._entityTypeId = parseInt(this.getSetting("entityTypeId", 0));
			this._entityId = parseInt(this.getSetting("entityId", 0));
			this._presetList = this.getSetting("presetList", null);
			if(!BX.type.isArray(this._presetList))
			{
				this._presetList = [];
			}

			this._countryId = parseInt(this.getSetting("countryId", 0));
			if(isNaN(this._countryId) || this._countryId < 0)
			{
				this._countryId = 0;
			}

			this._presetLastSelectedId = parseInt(this.getSetting("presetLastSelectedId", 0));

			this._isVisible = !!this.getSetting("isVisible", true);
			this._container.style.display = this._isVisible ? "" : "none";

			this._presetSelector = new BX.Crm.RequisitePresetSelectorClass(
				{
					editor: this,
					id: this._id,
					container: this._container,
					position: "top",
					requisiteEntityTypeId: this._entityTypeId,
					requisiteEntityId: this._entityId,
					presetList: this._presetList,
					presetLastSelectedId: this._presetLastSelectedId,
					requisiteEditHandler: this._presetSelectHandler
				}
			);

			BX.addCustomEvent(window, "CrmRequisiteEditFormCreate", this._formCreateHandler);
			//... and wait for CrmRequisiteEditFormCreate event for to get forms.
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(messageId)
		{
			var messages = BX.Crm.RequisiteFormManager.messages;
			return messages.hasOwnProperty(messageId) ? messages[messageId] : messageId;
		},
		getEntityTypeId: function()
		{
			return this._entityTypeId;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		onPresetSelect: function(entityTypeId, entityId, presetId)
		{
			this.startLoadRequest(
				{
					presetId: parseInt(presetId),
					requisiteId: 0,
					requisitePseudoId: "n" + (this._pseudoIdSequence++).toString()
				}
			);
		},
		onFormCreate: function(eventArgs)
		{
			var formId = eventArgs["formId"];

			if(this._forms[formId])
			{
				delete this._forms[formId];
			}

			var elementId = BX.type.isNumber(eventArgs["elementId"])
				? eventArgs["elementId"] : 0;

			var enableFieldMasquerading = BX.type.isBoolean(eventArgs["enableFieldMasquerading"])
				? eventArgs["enableFieldMasquerading"] : this._enableFieldMasquerading;
			var fieldNameTemplate = BX.type.isNotEmptyString(eventArgs["fieldNameTemplate"])
				? eventArgs["fieldNameTemplate"] : this._fieldNameTemplate;

			var containerId = BX.type.isNotEmptyString(eventArgs["containerId"])
				? eventArgs["containerId"] : ("container_" + formId);

			var countryId = BX.type.isNumber(eventArgs["countryId"])
				? eventArgs["countryId"] : this._countryId;

			var enableClientResolution = BX.type.isBoolean(eventArgs["enableClientResolution"])
				? eventArgs["enableClientResolution"] : false;

			var form = BX.Crm.RequisiteInnerForm.create(
				formId,
				{
					manager: this,
					settingManagerId: formId.toLowerCase(),
					countryId: countryId,
					enableClientResolution: enableClientResolution,
					containerId: containerId,
					elementId: elementId,
					enableFieldMasquerading: enableFieldMasquerading,
					fieldNameTemplate: fieldNameTemplate,
					serviceUrl: this._serviceUrl
				}
			);

			//Move new element to up.
			if(elementId <= 0)
			{
				var topForm = this.getTopmostForm();
				if(topForm)
				{
					this._container.insertBefore(form.getWrapper(), topForm.getWrapper());
					var sort = topForm.getSort();
					if(sort > 0)
					{
						sort--;
					}

					form.setSort(sort);
				}
			}

			this._forms[formId] = form;
		},
		getTopmostForm: function()
		{
			var result = null;
			for(var formId in this._forms)
			{
				if(!this._forms.hasOwnProperty(formId))
				{
					continue;
				}

				var current = this._forms[formId];
				if(result === null || result.getSort() > current.getSort())
				{
					result = current;
				}
			}
			return result;
		},
		reloadForm: function(form)
		{
			this.startLoadRequest(
				{
					presetId: form.getPresetId(),
					requisiteId: form.getElementId(),
					requisitePseudoId: form.getElementPseudoId(),
					form: form
				}
			);
		},
		startLoadRequest: function(params)
		{
			if(this._isRequestRunning)
			{
				return;
			}

			this._isRequestRunning = true;

			if(typeof(params["form"]) !== "undefined")
			{
				this._requestForm = params["form"];
			}

			var urlParams = { entityTypeId: this._entityTypeId, entityId: this._entityId };

			var presetId = BX.type.isNumber(params["presetId"]) ? params["presetId"] : 0;
			if(presetId > 0)
			{
				urlParams["presetId"] = presetId;
			}

			var requisiteId = BX.type.isNumber(params["requisiteId"]) ? params["requisiteId"] : 0;
			if(requisiteId > 0)
			{
				urlParams["requisiteId"] = requisiteId;
			}

			var requisitePseudoId = BX.type.isNotEmptyString(params["requisitePseudoId"]) ? params["requisitePseudoId"] : "";
			if(requisitePseudoId !== "")
			{
				urlParams["requisitePseudoId"] = params["requisitePseudoId"];
			}

			if(this._enableFieldMasquerading)
			{
				urlParams["fieldNameTemplate"] = BX.util.urlencode(this._fieldNameTemplate);
			}

			BX.ajax(
				{
					url: BX.util.add_url_param(this._formLoaderUrl, urlParams),
					method: "GET",
					dataType: "html",
					data: {},
					onsuccess: BX.delegate(this.onLoadRequestSuccess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		onLoadRequestSuccess: function(response)
		{
			var achor = null;

			this._isRequestRunning = false;
			if(this._requestForm)
			{
				achor = this._requestForm.getNextSiblingWrapper();
				this._requestForm.release(true);
				this._requestForm = null;
			}

			var node = BX.create("DIV", { html: response });
			while(node.childNodes.length > 0)
			{
				var childNode = node.childNodes[0];
				if(achor)
				{
					this._container.insertBefore(node.removeChild(childNode), achor);
				}
				else
				{
					this._container.appendChild(node.removeChild(childNode));
				}
			}

			//...and wait for CrmRequisiteEditFormCreate event
		},
		onRequestFailure: function(response)
		{
			this._isRequestRunning = false;
			this._requestForm = null;
		}
	};
	if(typeof(BX.Crm.RequisiteFormManager.messages) === "undefined")
	{
		BX.Crm.RequisiteFormManager.messages =
		{
		};
	}
	BX.Crm.RequisiteFormManager.items = {};
	BX.Crm.RequisiteFormManager.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteFormManager();
		self.initialize(id, settings);
		return (this.items[self.getId()] = self);
	}
}

if(typeof(BX.Crm.RequisiteInnerForm) === "undefined")
{
	BX.Crm.RequisiteInnerForm = function()
	{
		this._id = "";
		this._settings = {};
		this._manager = null;
		this._serviceUrl = "";
		this._containerId = "";
		this._elementId = 0;
		this._pseudoId = null;
		this._sort = -1;
		this._presetId = -1;
		this._countryId = 0;
		this._enableClientResolution = false;
		this._enableFieldMasquerading = false;
		this._multiAddressEditor = null;
		this._fieldNameTemplate = "";
		this._settingManagerId = "";
		this._settingManager = "";
		this._settingManagerCreateHandler = BX.delegate(this.onFormSettingManagerCreate, this);
		this._settingManagerSaveHandler = BX.delegate(this.onFormSettingManagerSave, this);
		this._settingManagerSectionEditHandler = BX.delegate(this.onFormSettingManagerSectionEdit, this);
		this._settingManagerSectionRemoveHandler = BX.delegate(this.onFormSettingManagerSectionRemove, this);
		this._settingManagerFormReloadHandler = BX.delegate(this.onFormSettingManagerFormReload, this);
		this._settingManagerFormReloadHandler = BX.delegate(this.onFormSettingManagerFormReload, this);
		this._addressCreateHandler = BX.delegate(this.onAddressCreate, this);
		this._isMarkedAsDeleted = false;
	};
	BX.Crm.RequisiteInnerForm.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_requisite_form_manager";
			this._settings = settings ? settings : {};

			this._countryId = this.getSetting("countryId", 0);
			this._enableClientResolution = this.getSetting("enableClientResolution", false);

			this._manager = this.getSetting("manager", null);
			if(!this._manager)
			{
				throw "BX.Crm.RequisiteInnerForm: Could not find parameter 'manager' in settings.";
			}

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.Crm.RequisiteInnerForm: Could not find parameter 'serviceUrl' in settings.";
			}

			this._containerId = this.getSetting("containerId", "");
			this._elementId = parseInt(this.getSetting("elementId", 0));
			this._enableFieldMasquerading = !!this.getSetting("enableFieldMasquerading", false);
			this._fieldNameTemplate = this.getSetting("fieldNameTemplate", "");

			this._settingManagerId = this.getSetting("settingManagerId", "");
			if(this._settingManagerId === "")
			{
				this._settingManagerId = this._id;
			}

			this._settingManager = BX.CrmFormSettingManager.getItemById(this._settingManagerId, true);
			if(this._settingManager)
			{
				this.bind();
			}
			else
			{
				BX.addCustomEvent(window, "CrmFormSettingManagerCreate", this._settingManagerCreateHandler);
			}

			var typeId = "";
			var inputName = "";
			switch (this._countryId)
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
				var input =  this.getFieldControl(inputName);
				if(input && this._enableClientResolution)
				{
					BX.Crm.RequisiteFieldController.create(
						inputName,
						{
							countryId: this._countryId,
							typeId: typeId,
							input: input,
							serviceUrl: this._serviceUrl,
							callbacks: { onFieldsLoad: BX.delegate(this.setupFields, this) }
						}
					);
				}
			}

			var editors = BX.CrmMultipleAddressEditor.getItemsByFormId(this._id);
			if(editors.length > 0)
			{
				this._multiAddressEditor = editors[0];
				BX.addCustomEvent(this._multiAddressEditor, "CrmMultipleAddressItemCreated", this._addressCreateHandler);
			}
		},
		release: function(removeNode)
		{
			removeNode = !!removeNode;

			this.unbind();
			if(this._settingManager)
			{
				var formManager = this._settingManager.getManager();
				formManager.release(removeNode);
				delete BX.CrmEditFormManager.items[formManager.getId()];
				this._settingManager = null;
			}

			if(removeNode)
			{
				var container = this.getContainer();
				if(container)
				{
					BX.remove(container.parentNode);
				}
			}
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getContainer: function()
		{
			return BX(this._containerId);
		},
		getWrapper: function()
		{
			var container = this.getContainer();
			return BX.type.isElementNode(container) ? container.parentNode : null;
		},
		getNextSiblingWrapper: function()
		{
			var wrapper = this.getWrapper();
			return BX.type.isElementNode(wrapper) ? BX.findNextSibling(wrapper, { className: "crm-offer-requisite-form-wrap" }) : null;
		},
		bind: function()
		{
			if(this._settingManager)
			{
				BX.addCustomEvent(
					this._settingManager,
					"CrmFormSettingManagerSave",
					this._settingManagerSaveHandler
				);

				BX.addCustomEvent(
					this._settingManager,
					"CrmFormSettingManagerSectionEditEnd",
					this._settingManagerSectionEditHandler
				);

				BX.addCustomEvent(
					this._settingManager,
					"CrmFormSettingManagerSectionRemove",
					this._settingManagerSectionRemoveHandler
				);

				BX.addCustomEvent(
					this._settingManager,
					"CrmFormSettingManagerReloadForm",
					this._settingManagerFormReloadHandler
				);
			}
		},
		unbind: function()
		{
			if(this._settingManager)
			{
				BX.removeCustomEvent(
					this._settingManager,
					"CrmFormSettingManagerSave",
					this._settingManagerSaveHandler
				);

				BX.removeCustomEvent(
					this._settingManager,
					"CrmFormSettingManagerSectionEditEnd",
					this._settingManagerSectionEditHandler
				);

				BX.removeCustomEvent(
					this._settingManager,
					"CrmFormSettingManagerSectionRemove",
					this._settingManagerSectionRemoveHandler
				);

				BX.removeCustomEvent(
					this._settingManager,
					"CrmFormSettingManagerReloadForm",
					this._settingManagerFormReloadHandler
				);
			}
		},
		getElementId: function()
		{
			return this._elementId;
		},
		getFieldControl: function(fieldName)
		{
			var ctrls = document.getElementsByName(this.resolveFieldInputName(fieldName));
			return ctrls.length > 0 ? ctrls[0] : null;
		},
		getFieldValue: function(fieldName)
		{
			var ctrl = this.getFieldControl(fieldName);
			return ctrl !== null ? ctrl.value : "";
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
			var inputs = this.getContainer().querySelectorAll('input[type="text"][data-requisite="field"],textarea[data-requisite="field"]');
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
				else if(this._multiAddressEditor)
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
						var addressEditor = this._multiAddressEditor.getItemByTypeId(addressTypeId);
						if(addressEditor === null)
						{
							addressEditor = this._multiAddressEditor.createItem(addressTypeId, this._id);
						}

						addressEditor.setup(address);
					}
				}
			}
		},
		getElementPseudoId: function()
		{
			if(this._pseudoId === null)
			{
				this._pseudoId = this.getFieldValue("PSEUDO_ID", "");
			}

			return this._pseudoId;
		},
		getPresetId: function()
		{
			if(this._presetId < 0)
			{
				this._presetId = parseInt(this.getFieldValue("PRESET_ID"));
				if(isNaN(this._presetId))
				{
					this._presetId = 0;
				}
			}

			return this._presetId;
		},
		getSort: function()
		{
			if(this._sort < 0)
			{
				this._sort = parseInt(this.getFieldValue("SORT"));
				if(isNaN(this._sort))
				{
					this._sort = 0;
				}
			}

			return this._sort;
		},
		setSort: function(sort)
		{
			if(!BX.type.isNumber(sort))
			{
				sort = parseInt(sort);
				if(isNaN(sort))
				{
					sort = 0;
				}
			}

			this._sort = sort;
			this.setFieldValue("SORT", this._sort);
		},
		resolveFieldInputName: function(fieldName)
		{
			if(this._enableFieldMasquerading && this._fieldNameTemplate !== "")
			{
				return this._fieldNameTemplate.replace(/#FIELD_NAME#/g, fieldName);
			}
			return fieldName;
		},
		markAsDeleted: function()
		{
			if(this._isMarkedAsDeleted)
			{
				return;
			}

			this._isMarkedAsDeleted = true;

			var container = this.getContainer();
			if(container)
			{
				container.appendChild(
					BX.create("INPUT",
						{
							props:
							{
								"name": this.resolveFieldInputName("DELETED"),
								"type": "hidden",
								"value": "Y"
							}
						}
					)
				);
				var wrapper = container.parentNode;
				wrapper.style.display = "none";
			}
		},
		isMarkedAsDeleted: function()
		{
			return this._isMarkedAsDeleted;
		},
		onFormSettingManagerCreate: function(manager)
		{
			if(manager.getId().toLowerCase() === this._settingManagerId.toLowerCase())
			{
				this._settingManager = manager;
				this.bind();

				BX.removeCustomEvent(window, "CrmFormSettingManagerCreate", this._settingManagerCreateHandler);
			}
		},
		onFormSettingManagerSave: function(manager, eventArgs)
		{
			if(this._settingManager === manager && this._enableFieldMasquerading)
			{
				BX.CrmFormSettingManager.replaceIdentity(eventArgs["data"], "rawId");
			}
		},
		onFormSettingManagerSectionEdit: function(manager, eventArgs)
		{
			if(this._settingManager === manager)
			{
				eventArgs["cancel"] = true;

				var section = eventArgs["section"];
				var field = section.getAssociatedField();
				if(field)
				{
					var input = BX(field['id']);
					if(input)
					{
						input.value = section.getName();
					}
				}
			}
		},
		onFormSettingManagerSectionRemove: function(manager, eventArgs)
		{
			if(this._settingManager === manager)
			{
				eventArgs["cancel"] = true;
				this.markAsDeleted();
			}
		},
		onFormSettingManagerFormReload: function(manager, eventArgs)
		{
			eventArgs["cancel"] = true;
			this._manager.reloadForm(this);
		},
		onAddressCreate: function(sender, address)
		{
			if(address.getOriginatorId() === this._id)
			{
				return;
			}

			var entityTypeId = this._manager.getEntityTypeId();
			var entityId = this._manager.getEntityId();
			if(entityTypeId > 0 && entityId > 0)
			{
				address.setupByEntity(entityTypeId, entityId);
			}
		}
	};
	BX.Crm.RequisiteInnerForm.create = function(id, settings)
	{
		var self = new BX.Crm.RequisiteInnerForm();
		self.initialize(id, settings);
		return self;
	};
}
