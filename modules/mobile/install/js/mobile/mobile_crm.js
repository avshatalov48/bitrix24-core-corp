BX.namespace("BX.Mobile.Crm");


/**
 * @bxjs_lang_path crm_js_messages.php
 */
BX.Mobile.Crm = {
	loadPageBlank: function(url)
	{
		if (!url)
			return;

		BXMobileApp.PageManager.loadPageBlank({
			url: url,
			bx24ModernStyle:true
		});
	},

	loadPageModal: function(url)
	{
		if (!url)
			return;

		BXMobileApp.PageManager.loadPageModal({
			url: url
		});
	},


	showErrorAlert: function(text)
	{
		if (!text)
			return;

		app.alert({title: BX.message("CRM_JS_ERROR"), text: text});
	},

	showRecursiveActionSheet : function(buttons)
	{
		if (typeof buttons !== "object")
			return;

		var buttonsToShow = [];
		var num = 0;

		for(var item in buttons)
		{
			if (buttons.hasOwnProperty(item))
			{
				if (num >= 5)
				{
					buttonsToShow.push({
						title: BX.message("CRM_JS_MORE"),
						callback: BX.proxy(function()
						{
							var moreButtons = this.buttons.slice(5);
							BX.Mobile.Crm.showRecursiveActionSheet(moreButtons);
						}, {buttons: buttons})
					});
					break;
				}
				else
				{
					buttonsToShow.push(buttons[item]);
				}

				num++;
			}
		}

		new BXMobileApp.UI.ActionSheet({
				buttons: buttonsToShow
			}, 'actionSheetStatus'
		).show();
	},

	deleteItem : function(itemId, ajaxPath, mode, event)
	{
		if (!itemId)
			return;

		if (mode != 'list' && mode != 'detail')
			return;

		app.confirm({
			title : BX.message("CRM_JS_DELETE_CONFIRM_TITLE"),
			text : BX.message("CRM_JS_DELETE_CONFIRM"),

			callback : BX.proxy(function(a) {
				if (a == 2)
					return false;
				else if (a == 1)
				{
					BXMobileApp.UI.Page.LoadingScreen.show();

					BX.ajax({
						url: ajaxPath,
						method: "POST",
						dataType: "json",
						data: {
							itemId: itemId,
							sessid: BX.bitrix_sessid(),
							action: "delete"
						},
						onsuccess: function(json)
						{
							BXMobileApp.UI.Page.LoadingScreen.hide();

							if (!BX.type.isPlainObject(json))
							{
								BX.Mobile.Crm.showErrorAlert(BX.message("CRM_JS_ERROR_DELETE"));
								return;
							}

							if (json.ERROR)
							{
								BX.Mobile.Crm.showErrorAlert(json.ERROR);
							}
							else
							{
								if (event)
								{
									BXMobileApp.onCustomEvent(event, {}, true);
								}

								if (mode == 'list')
								{
									BX.Mobile.Crm.List.onDeleteItemHandler(itemId);
								}
								else if (mode == 'detail')
								{
									BX.Mobile.Crm.Detail.onDeleteItemHandler(itemId);
								}
							}
						},
						onfailure:function(){
							BX.Mobile.Crm.showErrorAlert(BX.message("CRM_JS_ERROR_DELETE"));
							BXMobileApp.UI.Page.LoadingScreen.hide();
						}
					});
				}
			}, this),
			buttons : [BX.message("CRM_JS_BUTTON_OK"), BX.message("CRM_JS_BUTTON_CANCEL")]
		});
	}
};

BX.namespace("BX.Mobile.Crm.List");
BX.Mobile.Crm.List = {
	init: function(params)
	{
		this.ajaxPath = "";
		this.sortPath = "";
		this.fieldsPath = "";
		this.filterPath = "";
		this.filterAjaxPath = "";
		this.contextMenuTitle = "";

		if (typeof params === "object" && params)
		{
			this.ajaxPath = params.ajaxPath;
			this.sortPath = params.sortPath;
			this.fieldsPath = params.fieldsPath;
			this.filterPath = params.filterPath;
			this.filterAjaxPath = params.filterAjaxPath;
			this.contextMenuTitle = params.contextMenuTitle;
		}
	},

	showContextMenu : function(customItems)
	{
		var items = [];

		if (typeof customItems == "object")
		{
			for(var i=0, l=customItems.length; i<l; i++)
			{
				items.push(customItems[i]);
			}
		}

		items.push({
			name: BX.message("CRM_JS_GRID_FILTER"),
			image: "/bitrix/js/mobile/images/settings.png",
			action: BX.proxy(function()
			{
				BX.Mobile.Crm.loadPageModal(this.filterPath);
			}, this)
		});

		items.push({
			name: BX.message("CRM_JS_GRID_FIELDS"),
			image: "/bitrix/js/mobile/images/fields.png",
			action: BX.proxy(function()
			{
				BX.Mobile.Crm.loadPageModal(this.fieldsPath);
			}, this)
		});

		items.push({
			name: BX.message("CRM_JS_GRID_SORT"),
			image: "/bitrix/js/mobile/images/sort.png",
			action: BX.proxy(function()
			{
				BX.Mobile.Crm.loadPageModal(this.sortPath);
			}, this)
		});

		var menu = new BXMobileApp.UI.Menu({
			items: items
		}, "crmMobileMenu");
		BXMobileApp.UI.Page.TopBar.title.setText(this.contextMenuTitle);
		BXMobileApp.UI.Page.TopBar.title.show();
		BXMobileApp.UI.Page.TopBar.title.setCallback(function (){
			menu.show();
		});
	},

	showStatusList : function(itemId, statusList, onAfterUpdateEventName)
	{
		if (typeof statusList !== "object")
			return;

		var buttons = [];

		for(var item in statusList)
		{
			if (statusList.hasOwnProperty(item))
			{
				buttons.push({
					title: statusList[item].NAME,
					callback:BX.proxy(function()
					{
						params = {STATUS_ID: this.status.STATUS_ID, NAME: this.status.NAME, COLOR: this.status.COLOR};
						BX.Mobile.Crm.List.changeStatus(itemId, params);

						if (onAfterUpdateEventName){
							BXMobileApp.addCustomEvent(onAfterUpdateEventName, {});}

					}, {status:statusList[item]})
				});
			}
		}

		BX.Mobile.Crm.showRecursiveActionSheet(buttons);
	},

	applyListFilter: function(filterCode, gridId)
	{
		BXMobileApp.UI.Page.LoadingScreen.show();
		BX.ajax.post(
			this.filterAjaxPath,
			{
				sessid: BX.bitrix_sessid(),
				filterCode: filterCode,
				action: "applyFilter",
				gridId: gridId
			},
			function()
			{
				BXMobileApp.UI.Page.reload();
				BXMobileApp.UI.Page.LoadingScreen.hide();
			}
		);
	},

	changeStatus : function(itemId, params)
	{
		if (isNaN(itemId) || !(typeof params === "object"))
			return;

		BXMobileApp.UI.Page.PopupLoader.show();

		BX.ajax({
			url: this.ajaxPath,
			method: "POST",
			dataType: "json",
			data: {
				itemId: itemId,
				sessid: BX.bitrix_sessid(),
				action: "changeStatus",
				statusId: params.STATUS_ID
			},
			onsuccess: function (json)
			{
				BXMobileApp.UI.Page.PopupLoader.hide();

				if (!BX.type.isPlainObject(json))
					return;

				if (json.ERROR)
				{
					BX.Mobile.Crm.showErrorAlert(json.ERROR);
				}
				else
				{
					var statusNode = document.querySelector("[data-role='mobile-crm-status-entity-" + itemId + "']");
					if (statusNode)
					{
						var statusNameNode = document.querySelector("[data-role='mobile-crm-status-name-" + itemId + "']");
						var statusBlocks = BX.findChildren(statusNode, {tagName: "span"}, true);

						var stopColor = false;
						if (statusBlocks)
						{
							for (var i = 0; i < statusBlocks.length; i++)
							{
								if (stopColor)
									statusBlocks[i].style.background = "";
								else
									statusBlocks[i].style.background = params.COLOR;

								if (statusBlocks[i].getAttribute("data-role") == "mobile-crm-status-block-" + params.STATUS_ID)
									stopColor = true;
							}
						}
						if (statusNameNode)
						{
							statusNameNode.innerHTML = BX.util.htmlspecialchars(params.NAME);
						}
					}
					else
					{
						var curItem = document.querySelector("[data-id='mobile-grid-item-" + itemId + "']");
						if (curItem)
						{
							var statusIcon = BX.findChild(curItem, {className: "mobile-grid-field-title-icon"}, true, false);

							if (statusIcon)
							{
								statusIcon.style.background = params.COLOR;
							}
						}
					}
				}
			},
			onfailure: function(){
				BXMobileApp.UI.Page.LoadingScreen.hide();
			}
		});
	},

	onDeleteItemHandler : function(itemId)
	{
		if (!itemId)
			return;

		var itemNode = document.querySelector("[data-id='mobile-grid-item-"+itemId+"']");
		if (itemNode)
		{
			if (
				BX.hasClass(BX.previousSibling(itemNode), "mobile-grid-change")
				&&
				(
					BX.hasClass(BX.nextSibling(itemNode), "mobile-grid-change")
					|| !BX.nextSibling(itemNode)
				)
			)
				BX.remove(BX.previousSibling(itemNode));

			BX.remove(itemNode);
		}
	}
};

BX.namespace("BX.Mobile.Crm.Detail");
BX.Mobile.Crm.Detail = {
	onDeleteItemHandler : function()
	{
		BXMobileApp.UI.Page.close({drop:true});
	},

	collectInterfaceFormData: function(form, dataFormValues)
	{
		var multivalueCounter = {};
		for (var i = 0; i < form.elements.length; i++)
		{
			if (form[i].tagName === "SELECT")
			{
				dataFormValues[form[i].name] = '';
				var options = form[i].options;
				for (var j = 0; j < options.length; j++){
					if (options[j].selected && options[j].value){
						var optionName = (form[i].name).replace('[]','['+j+']');
						dataFormValues[optionName] = options[j].value;
					}
				}
			}
			else if (form[i].tagName == "INPUT" && form[i].type == "checkbox")
			{
				if (form[i].checked)
					dataFormValues[form[i].name] = form[i].value;
				else
					dataFormValues[form[i].name] = "";
			}
			else
			{
				var fieldName = form[i].name;
				if (fieldName.substr(-2) === '[]')
				{
					fieldName = fieldName.substr(0, fieldName.length - 2);
					if (multivalueCounter.hasOwnProperty(fieldName))
					{
						multivalueCounter[fieldName]++;
					}
					else
					{
						multivalueCounter[fieldName] = 0;
					}
					var realFieldName = fieldName + '[' + multivalueCounter[fieldName] + ']';
					while (dataFormValues.hasOwnProperty(realFieldName))
					{
						multivalueCounter[fieldName]++;
						realFieldName = fieldName + '[' + multivalueCounter[fieldName] + ']';
					}
					fieldName = realFieldName;
				}
				dataFormValues[fieldName] = form[i].value;
			}
		}

		return dataFormValues;
	}
};

BX.namespace("BX.Mobile.Crm.EntityEditor");

BX.Mobile.Crm.EntityEditor = (function()
{
	var EntityEditor = function(params)
	{
		this.isRestrictedMode = false;
		this.isMultiEntity = false;
		this.entityInfo = {};
		this.entityContainerNode = "";
		this.curEntityId = [];
		this.onSelectEventName = "";
		this.onDeleteEventName = "";

		this.init(params);
	};

	EntityEditor.prototype.init = function(params)
	{
		if (typeof params === "object")
		{
			this.entityContainerNode = params.entityContainerNode || "";
			this.entityInfo = params.entityInfo || "";
			this.isRestrictedMode = params.isRestrictedMode || false;
			this.isMultiEntity = params.isMultiEntity || false;
			this.onSelectEventName = params.onSelectEventName || "";
			this.onDeleteEventName = params.onDeleteEventName || "";
		}

		//generate current entity info
		if (this.isMultiEntity)
		{
			if (typeof this.entityInfo === 'object')
			{
				for (var i = 0; i < this.entityInfo.length; i++)
				{
					this.curEntityId.push(this.entityInfo[i].id);
					this.generateEntityHtml(this.entityInfo[i]);
				}
			}
		}
		else
		{
			if (typeof this.entityInfo === 'object')
			{
				this.curEntityId = this.entityInfo.id;
				this.generateEntityHtml(this.entityInfo);
			}
		}

		if (!this.isRestrictedMode)
		{
			BXMobileApp.addCustomEvent(this.onSelectEventName, BX.proxy(function(data) {
				this.changeEntity(data);
			}, this));
		}
	};

	EntityEditor.prototype.delEntity = function(elementNode, id)
	{
		if (this.isMultiEntity)
		{
			for (var i = 0; i < this.curEntityId.length; i++)
			{
				if (this.curEntityId[i] == id)
				{
					this.curEntityId.splice(i, 1);
					break;
				}
			}
		}
		else
		{
			this.curEntityId = "";
		}

		BX.remove(elementNode);

		if (this.onDeleteEventName)
		{
			BX.onCustomEvent(this.onDeleteEventName, []);
		}
	};

	EntityEditor.prototype.generateEntityHtml = function(data)
	{
		var entityContainer = BX.create("div");

		var self = this;

		var imageNode = "";
		if (data.image)
		{
			imageNode = BX.create("span", {
				attrs: {
					className: "avatar",
					style: data.image ? "background-image:url("+data.image+")" : ""
				}
			});
		}
		else if (data.entityType)
		{
			var imagePath = "";

			if (data.entityType == "contact")
				imagePath = "/bitrix/js/mobile/images/icon-contact.png";
			else if (data.entityType == "company")
				imagePath = "/bitrix/js/mobile/images/icon-company.png";
			else if (data.entityType == "lead")
				imagePath = "/bitrix/js/mobile/images/icon-lead.png";
			else if (data.entityType == "quote")
				imagePath = "/bitrix/js/mobile/images/icon-quote.png";
			else if (data.entityType == "deal")
				imagePath = "/bitrix/js/mobile/images/icon-deal.png";

			if (imagePath)
			{
				imageNode = BX.create("span", {
					attrs: {
						className: "avatar",
						style: "background:#717e8a url("+imagePath+") center no-repeat; background-size: 39px;"
					}
				});
			}
		}

		var entityInfo = BX.create("div", {
			attrs: {className: "mobile-grid-field-select-user-item"},
			children: [
				imageNode,
				( this.isRestrictedMode ? "" :
						BX.create("del", {
							events: {
								"click": function () {
									self.delEntity(this.parentNode.parentNode, data.id);
								}
							}
						})
				),
				BX.create("span", {
					html: data.name + (data.addTitle ? "<span>" + data.addTitle + "</span>" : ""),
					attrs: {className: "mobile-grid-field-user-name"},
					events: {
						"click": function () {
							BX.Mobile.Crm.loadPageBlank(data.url);
						}
					}
				})
			]
		});

		if (!this.isMultiEntity)
			this.entityContainerNode.innerHTML = "";

		entityContainer.appendChild(entityInfo);

		if (data.multi)
		{
			var entityMultiContainer = BX.create("div", {
				attrs: {style: "padding-top: 8px;"}
			});

			var multiFields = data.multi;
			for(var i in multiFields)
			{
				if (
					multiFields.hasOwnProperty(i)
					&& multiFields[i].hasOwnProperty('type')
					&& multiFields[i].hasOwnProperty('value')
				)
				{
					var activeNode = "";

					if (multiFields[i].type == "PHONE")
					{
						activeNode = BX.create("div", {
							attrs: {className: "mobile-grid-block mobile-grid-data-phone-container"},
							children: [
								BX.create("img", {attrs:{
									className: "mobile-grid-field-icon mobile-grid-field-icon-phone",
									src: "/bitrix/js/mobile/images/" + "phone2x.png",
									srcset: "/bitrix/js/mobile/images/" + "phone2x.png" + " 2x"
								}}),
								BX.create("span", {attrs:{className: "mobile-grid-field-contact"}, html: BX.util.htmlspecialchars(multiFields[i].value)})
							],
							events: {
								"click" : BX.proxy(function(){
									BX.MobileTools.phoneTo(this.phone);
								}, {phone: multiFields[i].value})
							}
						});
					}
					else if (multiFields[i].type == "EMAIL")
					{
						activeNode = BX.create("div", {
							attrs: {className: "mobile-grid-block mobile-grid-data-mail-container"},
							children: [
								BX.create("a", {
									attrs: {href: "mailto:" + (multiFields[i].value ? multiFields[i].value : ""), style: "display: block;text-decoration: none"},
									children: [
										BX.create("img", {attrs:{
											className: "mobile-grid-field-icon",
											src: "/bitrix/js/mobile/images/" + "email2x.png",
											srcset: "/bitrix/js/mobile/images/" + "email2x.png" + " 2x"
										}}),
										BX.create("span", {
											attrs:{className: "mobile-grid-field-contact"},
											html: (multiFields[i].value ? multiFields[i].value : "")
										})
									]
								})

							]
						});
					}

					var multiNode = BX.create("div", {
						attrs: {className: "mobile-grid-section-child mobile-grid-field-phone", style: this.isRestrictedMode ? "padding-right: 16px;" : ""},
						children: [
							BX.create("span", {attrs: {className: "mobile-grid-title"}, html: (multiFields[i].name ? multiFields[i].name : "")}),
							activeNode
						]
					});

					entityMultiContainer.appendChild(multiNode);
				}
			}
			entityContainer.appendChild(entityMultiContainer);
		}

		if (this.entityContainerNode)
			this.entityContainerNode.appendChild(entityContainer);
	};

	EntityEditor.prototype.changeEntity = function(data)
	{
		if (this.isMultiEntity)
		{
			for (var i=0; i<this.curEntityId.length; i++)
			{
				if (this.curEntityId[i] == data.id)
					return;
			}

			this.curEntityId.push(data.id);
		}
		else
			this.curEntityId = data.id;

		this.generateEntityHtml(data);
	};

	return EntityEditor;
})();

//=============== converter
BX.namespace("BX.Mobile.Crm.EntityConverterMode");
BX.Mobile.Crm.EntityConverterMode =
{
	intermediate: 0,
	schemeSetup: 1,
	syncSetup: 2,
	request: 3
};

BX.namespace("BX.Mobile.Crm.EntityConverter");
BX.Mobile.Crm.EntityConverter = function()
{
	this._id = "";
	this._entityType = "";
	this._settings = {};
	this._config = {};
	this._contextData = null;
	this._mode = BX.Mobile.Crm.EntityConverterMode.intermediate;
	this._entityId = 0;
	this._syncEditor = null;
	this._syncEditorClosingListener = BX.delegate(this.onSyncEditorClose, this);
	this._enableSync = false;
	this._requestIsRunning = false;
};
BX.Mobile.Crm.EntityConverter.prototype =
{
	initialize: function(id, settings, entityType)
	{
		this._id = id;
		this._entityType = entityType;
		this._settings = settings ? settings : {};

		this._config = this.getSetting("config", {});
		this._serviceUrl = this.getSetting("serviceUrl", "");
	},
	getSetting: function(name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	setSetting: function(name, val)
	{
		this._settings[name] = val;
	},
	getMessage: function(name)
	{
		return name;
	},
	getConfig: function()
	{
		return this._config;
	},
	setupSynchronization: function(fieldNames)
	{
		this._mode = BX.Mobile.Crm.EntityConverterMode.syncSetup;
		var convertId = "convert_" + this._entityType + this._id + Math.random();

		BXMobileApp.addCustomEvent("onCrmConvertSynchSettings", BX.proxy(function (data) {
			if (data.convertId == convertId)
			{
				this._enableSync = true;
				this._config = data.config;
				this._contextData = null;
				this.startRequest();
			}
		}, this));

		BXMobileApp.PageManager.loadPageBlank({ //should be modal page, but can't send data yet
			url: "/mobile/crm/convert_sync/?sync_id=" + convertId,
			bx24ModernStyle:true,
			data: {
				config: this._config,
				fields: fieldNames,
				convertId: convertId,
				entityType: this._entityType
			}
		});
	},
	convert: function(entityId, config, contextData)
	{
		if(!BX.type.isPlainObject(config))
		{
			return;
		}

		this._entityId = entityId;
		this._config = config;
		this._contextData = BX.type.isPlainObject(contextData) ? contextData : null;
		this.startRequest();
	},
	startRequest: function()
	{
		if(this._requestIsRunning)
		{
			return;
		}
		this._requestIsRunning = true;

		BXMobileApp.UI.Page.LoadingScreen.show();

		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data: {
					action: "convert",
					sessid:BX.bitrix_sessid(),
					"MODE": "CONVERT",
					"ENTITY_ID": this._entityId,
					"ENABLE_SYNCHRONIZATION": this._enableSync ? "Y" : "N",
					"CONFIG": this._config,
					"CONTEXT": this._contextData
					//"ORIGIN_URL": this._originUrl
				},
				onsuccess: BX.delegate(this.onRequestSuccsess, this),
				onfailure: BX.delegate(this.onRequestFailure, this)
			}
		);
		this._mode = BX.Mobile.Crm.EntityConverterMode.request;
	},
	onRequestSuccsess: function(result)
	{
		BXMobileApp.UI.Page.LoadingScreen.hide();

		if (!BX.type.isPlainObject(result))
			return;

		this._requestIsRunning = false;
		this._mode = BX.Mobile.Crm.EntityConverterMode.intermediate;

		if(result.hasOwnProperty("ERROR"))
		{
			this.showError(result["ERROR"]);
			return;
		}

		var data;
		if(BX.type.isPlainObject(result["REQUIRED_ACTION"]))
		{
			var action = result["REQUIRED_ACTION"];
			var name = BX.type.isNotEmptyString(action["NAME"]) ? action["NAME"] : "";
			data = BX.type.isPlainObject(action["DATA"]) ? action["DATA"] : {};
			if(name === "SYNCHRONIZE")
			{
				if(BX.type.isPlainObject(data["CONFIG"]))
				{
					this._config = data["CONFIG"];
				}

				this.setupSynchronization(BX.type.isArray(data["FIELD_NAMES"]) ? data["FIELD_NAMES"] : []);
			}
			return;
		}

		if(BX.type.isPlainObject(result["DATA"]))
		{
			data = result["DATA"];
			if(BX.type.isNotEmptyString(data["URL"]))
			{
				if (data["MODAL_SCREEN"])
				{
					BX.Mobile.Crm.loadPageModal(data["URL"]);
				}
				else
				{
					BX.Mobile.Crm.loadPageBlank(data["URL"]);
				}
			}
			else
			{
				BXMobileApp.UI.Page.reload();
			}
		}
	},
	onRequestFailure: function()
	{
		BXMobileApp.UI.Page.LoadingScreen.hide();

		this._requestIsRunning = false;
		this._mode = BX.Mobile.Crm.EntityConverterMode.intermediate;
	},
	showError: function(error)
	{
		if (!error)
			return;

		BX.Mobile.Crm.showErrorAlert(BX.type.isNotEmptyString(error) ? error : this.getMessage("generalError"));
	}
};
BX.Mobile.Crm.EntityConverter.create = function(id, settings)
{
	var self = new BX.CrmEntityConverter();
	self.initialize(id, settings);
	return self;
};


BX.namespace("BX.Mobile.Crm.LeadConversionScheme");
BX.Mobile.Crm.LeadConversionScheme = (function()
{
	var LeadConversionScheme = function(params)
	{
		this.dealcontactcompany = "DEAL_CONTACT_COMPANY";
		this.dealcontact = "DEAL_CONTACT";
		this.dealcompany = "DEAL_COMPANY";
		this.deal = "DEAL";
		this.contactcompany = "CONTACT_COMPANY";
		this.contact = "CONTACT";
		this.company = "COMPANY";

		this.entityId = "";
		this.ajaxPath = "";
		this.permissions = {};
		this.messages = {};
		this.contactSelectUrl = "";
		this.companySelectUrl = "";
		this.buttons = [];

		this.init(params);
	};

	LeadConversionScheme.prototype.getListItems = function(ids)
	{
		var results = [];
		for(var i = 0; i < ids.length; i++)
		{
			var id = ids[i];
			results.push({ value: id, text: this.getDescription(id) });
		}

		return results;
	};

	LeadConversionScheme.prototype.getDescription = function(id)
	{
		var mess = this.messages;
		return mess.hasOwnProperty(id) ? mess[id] : id;
	};

	LeadConversionScheme.prototype.toConfig = function(scheme, config)
	{
		this.markEntityAsActive(
			config,
			"deal",
			scheme === this.dealcontactcompany || scheme === this.dealcontact || scheme === this.dealcompany || scheme === this.deal
		);

		this.markEntityAsActive(
			config,
			"contact",
			scheme === this.dealcontactcompany || scheme === this.dealcontact || scheme === this.contactcompany || scheme === this.contact
		);

		this.markEntityAsActive(
			config,
			"company",
			scheme === this.dealcontactcompany || scheme === this.dealcompany || scheme === this.contactcompany || scheme === this.company
		);
	};

	LeadConversionScheme.prototype.createConfig = function(scheme)
	{
		var config = {};
		this.toConfig(scheme, config);
		return config;
	};

	LeadConversionScheme.prototype.markEntityAsActive = function(config, entityTypeName, active)
	{
		if(typeof(config[entityTypeName]) === "undefined")
		{
			config[entityTypeName] = {};
		}
		config[entityTypeName]["active"] = active ? "Y" : "N";
	};

	LeadConversionScheme.prototype.showActionSheet = function()
	{
		if (this.buttons)
		{
			BX.Mobile.Crm.showRecursiveActionSheet(this.buttons);
		}
	};

	LeadConversionScheme.prototype.initConverter = function(scheme, context)
	{
		if (!scheme)
			return;

		if (!context)
			context = null;

		var converter = new BX.Mobile.Crm.EntityConverter();
		var params = {
			serviceUrl : this.ajaxPath,
			config : this.createConfig(scheme)
		};

		converter.initialize("crm_lead", params, "lead");
		converter.convert(this.entityId, this.createConfig(scheme), context);

		BXMobileApp.addCustomEvent("onCrmContactLoadPageBlank", BX.proxy(function(data){
			if (!data.type || data.type !== 'convert')
				return;

			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmContactListUpdate", {}, true);
		}, this));

		BXMobileApp.addCustomEvent("onCrmCompanyLoadPageBlank", BX.proxy(function(data){
			if (!data.type || data.type !== 'convert')
				return;

			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmCompanyListUpdate", {}, true);
		}, this));

		BXMobileApp.addCustomEvent("onCrmDealLoadPageBlank", BX.proxy(function(data){
			if (!data.type || data.type !== 'convert')
				return;

			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmDealListUpdate", {}, true);
		}, this));
	};

	LeadConversionScheme.prototype.init = function(params)
	{
		if (typeof params === "object" && params)
		{
			this.entityId = params.entityId;
			this.ajaxPath = params.ajaxPath;
			this.permissions = params.permissions || {};
			this.messages = params.messages || {};
			this.contactSelectUrl = params.contactSelectUrl || "";
			this.companySelectUrl = params.companySelectUrl || "";
		}

		var isDealPermitted = params.permissions["deal"];
		var isContactPermitted = params.permissions["contact"];
		var isCompanyPermitted = params.permissions["company"];

		var schemes = [];
		if(isDealPermitted)
		{
			if(isContactPermitted && isCompanyPermitted)
			{
				schemes.push(this.dealcontactcompany);
			}
			if(isContactPermitted)
			{
				schemes.push(this.dealcontact);
			}
			if(isCompanyPermitted)
			{
				schemes.push(this.dealcompany);
			}

			schemes.push(this.deal);
		}
		if(isContactPermitted && isCompanyPermitted)
		{
			schemes.push(this.contactcompany);
		}
		if(isContactPermitted)
		{
			schemes.push(this.contact);
		}
		if(isCompanyPermitted)
		{
			schemes.push(this.company);
		}

		var items = this.getListItems(schemes);

		buttons = [];
		if (items)
		{
			for (var i=0; i<items.length; i++)
			{
				buttons.push(
					{
						title: items[i].text,
						callback: BX.proxy(function()
						{
							this.self.initConverter(this.scheme);
						}, {scheme: items[i].value, self: this})
					}
				);
			}
		}

		buttons.push(
			{
				title: BX.message("M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_CONTACT"),
				callback: BX.proxy(function()
				{
					BX.Mobile.Crm.loadPageModal(this.contactSelectUrl);
				}, this)
			}
		);

		buttons.push(
			{
				title: BX.message("M_CRM_LEAD_CONV_OPEN_ENTITY_SEL_COMPANY"),
				callback: BX.proxy(function()
				{
					BX.Mobile.Crm.loadPageModal(this.companySelectUrl);
				}, this)
			}
		);

		this.buttons = buttons;
		this.showActionSheet();

		//merge with existing contact
		BX.addCustomEvent("onLeadConvertSelectContact", BX.proxy(function(data)
		{
			if (data.id)
			{
				this.initConverter(this.contact, {contact: data.id});
			}
		}, this));

		//merge with existing company
		BX.addCustomEvent("onLeadConvertSelectCompany", BX.proxy(function(data)
		{
			if (data.id)
			{
				this.initConverter(this.company, {company: data.id});
			}
		}, this));
	};

	return LeadConversionScheme;
})();

BX.namespace("BX.Mobile.Crm.DealConversionScheme");
BX.Mobile.Crm.DealConversionScheme = (function()
{
	var DealConversionScheme = function(params)
	{
		this.invoice = "INVOICE";
		this.quote = "QUOTE";

		this.entityId = "";
		this.ajaxPath = "";
		this.permissions = {};
		this.messages = {};
		this.buttons = [];

		this.init(params);
	};

	DealConversionScheme.prototype.getListItems = function(ids)
	{
		var results = [];
		for(var i = 0; i < ids.length; i++)
		{
			var id = ids[i];
			results.push({ value: id, text: this.getDescription(id) });
		}

		return results;
	};

	DealConversionScheme.prototype.getDescription = function(id)
	{
		var mess = this.messages;
		return mess.hasOwnProperty(id) ? mess[id] : id;
	};

	DealConversionScheme.prototype.toConfig = function(scheme, config)
	{
		this.markEntityAsActive(config, "invoice", scheme === this.invoice);
		this.markEntityAsActive(config, "quote", scheme === this.quote);
	};

	DealConversionScheme.prototype.createConfig = function(scheme)
	{
		var config = {};
		this.toConfig(scheme, config);
		return config;
	};

	DealConversionScheme.prototype.markEntityAsActive = function(config, entityTypeName, active)
	{
		if(typeof(config[entityTypeName]) === "undefined")
		{
			config[entityTypeName] = {};
		}
		config[entityTypeName]["active"] = active ? "Y" : "N";
	};

	DealConversionScheme.prototype.showActionSheet = function()
	{
		if (this.buttons)
		{
			BX.Mobile.Crm.showRecursiveActionSheet(this.buttons);
		}
	};

	DealConversionScheme.prototype.initConverter = function(scheme)
	{
		if (!scheme)
			return;

		var converter = new BX.Mobile.Crm.EntityConverter();
		var params = {
			serviceUrl : this.ajaxPath,
			config : this.createConfig(scheme)
		};

		converter.initialize("crm_deal", params, "deal");
		converter.convert(this.entityId, this.createConfig(scheme));

		BXMobileApp.addCustomEvent("onCrmInvoiceLoadPageBlank", BX.proxy(function(data){
			if (!data.type || data.type !== 'convert')
				return;

			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmInvoiceListUpdate", {}, true);
		}, this));

		BXMobileApp.addCustomEvent("onCrmQuoteLoadPageBlank", BX.proxy(function(data){
			if (!data.type || data.type !== 'convert')
				return;

			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmQuoteListUpdate", {}, true);
		}, this));
	};

	DealConversionScheme.prototype.init = function(params)
	{
		if (typeof params === "object" && params)
		{
			this.entityId = params.entityId;
			this.ajaxPath = params.ajaxPath;
			this.permissions = params.permissions || {};
			this.messages = params.messages || {};
		}

		var isInvoicePermitted = params.permissions["invoice"];
		var isQuotePermitted = params.permissions["quote"];

		var schemes = [];
		if(isInvoicePermitted)
		{
			schemes.push(this.invoice);
		}

		if(isQuotePermitted)
		{
			schemes.push(this.quote);
		}

		var items = this.getListItems(schemes);

		buttons = [];
		if (items)
		{
			for (var i=0; i<items.length; i++)
			{
				buttons.push(
					{
						title: items[i].text,
						callback: BX.proxy(function()
						{
							this.self.initConverter(this.scheme);
						}, {scheme: items[i].value, self: this})
					}
				);
			}
		}

		this.buttons = buttons;
		this.showActionSheet();
	};

	return DealConversionScheme;
})();

BX.namespace("BX.Mobile.Crm.QuoteConversionScheme");
BX.Mobile.Crm.QuoteConversionScheme = (function()
{
	var QuoteConversionScheme = function(params)
	{
		this.invoice = "INVOICE";
		this.deal = "DEAL";

		this.entityId = "";
		this.ajaxPath = "";
		this.permissions = {};
		this.messages = {};
		this.buttons = [];

		this.init(params);
	};

	QuoteConversionScheme.prototype.getListItems = function(ids)
	{
		var results = [];
		for(var i = 0; i < ids.length; i++)
		{
			var id = ids[i];
			results.push({ value: id, text: this.getDescription(id) });
		}

		return results;
	};

	QuoteConversionScheme.prototype.getDescription = function(id)
	{
		var mess = this.messages;
		return mess.hasOwnProperty(id) ? mess[id] : id;
	};

	QuoteConversionScheme.prototype.toConfig = function(scheme, config)
	{
		this.markEntityAsActive(config, "invoice", scheme === this.invoice);
		this.markEntityAsActive(config, "deal", scheme === this.deal);
	};

	QuoteConversionScheme.prototype.createConfig = function(scheme)
	{
		var config = {};
		this.toConfig(scheme, config);
		return config;
	};

	QuoteConversionScheme.prototype.markEntityAsActive = function(config, entityTypeName, active)
	{
		if(typeof(config[entityTypeName]) === "undefined")
		{
			config[entityTypeName] = {};
		}
		config[entityTypeName]["active"] = active ? "Y" : "N";
	};

	QuoteConversionScheme.prototype.showActionSheet = function()
	{
		if (this.buttons)
		{
			BX.Mobile.Crm.showRecursiveActionSheet(this.buttons);
		}
	};

	QuoteConversionScheme.prototype.initConverter = function(scheme)
	{
		if (!scheme)
			return;

		var converter = new BX.Mobile.Crm.EntityConverter();
		var params = {
			serviceUrl : this.ajaxPath,
			config : this.createConfig(scheme)
		};

		converter.initialize("crm_quote", params, "quote");
		converter.convert(this.entityId, this.createConfig(scheme));

		BXMobileApp.addCustomEvent("onCrmDealLoadPageBlank", BX.proxy(function(data){
			if (!data.type || data.type !== 'convert')
				return;

			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmDealListUpdate", {}, true);
		}, this));

		BXMobileApp.addCustomEvent("onCrmInvoiceLoadPageBlank", BX.proxy(function(data){
			if (!data.type || data.type !== 'convert')
				return;

			if (data.path)
				BX.Mobile.Crm.loadPageBlank(data.path);

			BXMobileApp.onCustomEvent("onCrmInvoiceListUpdate", {}, true);
		}, this));
	};

	QuoteConversionScheme.prototype.init = function(params)
	{
		if (typeof params === "object" && params)
		{
			this.entityId = params.entityId;
			this.ajaxPath = params.ajaxPath;
			this.permissions = params.permissions || {};
			this.messages = params.messages || {};
		}

		var isInvoicePermitted = params.permissions["invoice"];
		var isDealPermitted = params.permissions["deal"];

		var schemes = [];
		if(isInvoicePermitted)
		{
			schemes.push(this.invoice);
		}

		if(isDealPermitted)
		{
			schemes.push(this.deal);
		}

		var items = this.getListItems(schemes);

		buttons = [];
		if (items)
		{
			for (var i=0; i<items.length; i++)
			{
				buttons.push(
					{
						title: items[i].text,
						callback: BX.proxy(function()
						{
							this.self.initConverter(this.scheme);
						}, {scheme: items[i].value, self: this})
					}
				);
			}
		}

		this.buttons = buttons;
		this.showActionSheet();
	};

	return QuoteConversionScheme;
})();
//=================== converter