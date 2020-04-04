if(typeof(BX.FilesFieldContainer) == "undefined")
{
	BX.CrmQuoteStorageType =
	{
		undefined: 0,
		file: 1,
		webdav: 2,
		diskfiles: 3
	};

	BX.CrmQuoteMode =
	{
		edit: 1,
		view: 2
	};

	BX.FilesFieldContainer = function(settings)
	{
		this.random = Math.random().toString().substring(2);
		this.settings = settings;
		this.controlMode = (settings["controlMode"] === "edit") ? BX.CrmQuoteMode.edit : BX.CrmQuoteMode.view;
		this.container = BX(this.settings["containerId"]);

		var storageTypeId = parseInt(this.getSetting("storageTypeId", BX.CrmQuoteStorageType.undefined));
		if(isNaN(storageTypeId) || storageTypeId === BX.CrmQuoteStorageType.undefined)
		{
			storageTypeId = this.getDefaultStorageTypeId();
		}
		this.storageTypeId = storageTypeId;

		var uploaderName = this.getSetting("uploaderName", "files_field_uploader_" + this.random);
		this.setSetting("uploaderName", uploaderName);
		if(this.storageTypeId === BX.CrmQuoteStorageType.webdav)
		{
			this.prepareWebDavUploader(
				uploaderName,
				this.controlMode,
				this.getSetting("webdavelements", [])
			);
		}
		else if(this.storageTypeId === BX.CrmQuoteStorageType.diskfiles)
		{
			this.prepareDiskUploader(
				uploaderName,
				this.controlMode,
				this.getSetting("diskfiles", [])
			);
		}
		else
		{
			this.prepareFileUploader(
				uploaderName,
				this.getSetting("files", [])
			);
		}
	};

	BX.FilesFieldContainer.prototype = {
		"getSetting": function(name, defaultval)
		{
			return typeof(this.settings[name]) != "undefined" ? this.settings[name] : defaultval;
		},
		"setSetting": function (name, val)
		{
			this.settings[name] = val;
		},
		"getMessage": function(name, defaultval)
		{
			return typeof(this.settings["messages"]) !== "undefined" && this.settings["messages"][name] ? this.settings["messages"][name] : defaultval;
		},
		"getDialogElements": function(name)
		{
			var form = document.forms["form_" + this.settings["formId"]];
			if(!form || !form.elements[name])
			{
				return [];
			}

			return form.elements[name];
		},
		"prepareWebDavUploader": function(name, mode, vals)
		{
			name = BX.type.isNotEmptyString(name) ? name : "files_field_uploader_" + this.random;

			var uploader = typeof(BX.CrmWebDavUploader.items[name]) !== "undefined"
				? BX.CrmWebDavUploader.items[name] : null;

			if(uploader)
			{
				uploader.cleanLayout();
			}
			else
			{
				uploader = BX.CrmWebDavUploader.create(
					name,
					{
						"urlSelect": this.getSetting("webDavSelectUrl", ""),
						"urlUpload": this.getSetting("webDavUploadUrl", ""),
						"urlShow": this.getSetting("webDavShowUrl", ""),
						"elementInfoLoader": BX.delegate(this.getWebDavElementInfo, this),
						"msg" :
						{
							"loading" : this.getMessage("webdavFileLoading", "Loading..."),
							"file_exists": this.getMessage("webdavFileAlreadyExists", "File already exists!"),
							"access_denied":"<p style=\"margin-top:0;\">" + this.getMessage("webdavFileAccessDenied", "Access denied!") + "</p>",
							"title": this.getMessage("webdavTitle", "Files"),
							"attachFile": this.getMessage("webdavAttachFile", "Attach file"),
							"dragFile": this.getMessage("webdavDragFile", "Drag a files to this area"),
							"selectFile": this.getMessage("webdavSelectFile", "or select a file in your computer"),
							"selectFromLib": this.getMessage("webdavSelectFromLib", "Select from library"),
							"loadFiles": this.getMessage("webdavLoadFiles", "Load files")
						}
					}
				)
			}

			uploader.setMode(mode);
			uploader.setValues(vals);

			if (this.container)
				uploader.layout(this.container);

			return this.container;
		},
		"prepareDiskUploader": function(name, mode, vals)
		{
			name = BX.type.isNotEmptyString(name) ? name : 'files_field_uploader_';

			var uploader = typeof(BX.CrmDiskUploader.items[name]) !== 'undefined'
				? BX.CrmDiskUploader.items[name] : null;

			if(uploader)
			{
				uploader.cleanLayout();
			}
			else
			{
				uploader = BX.CrmDiskUploader.create(
					name,
					{
						msg :
						{
							'diskAttachFiles' : this.getMessage('diskAttachFiles'),
							'diskAttachedFiles' : this.getMessage('diskAttachedFiles'),
							'diskSelectFile' : this.getMessage('diskSelectFile'),
							'diskSelectFileLegend' : this.getMessage('diskSelectFileLegend'),
							'diskUploadFile' : this.getMessage('diskUploadFile'),
							'diskUploadFileLegend' : this.getMessage('diskUploadFileLegend')
						}
					}
				)
			}

			uploader.setMode(mode);
			uploader.setValues(vals);

			if (this.container)
				uploader.layout(this.container);

			return this.container;
		},
		"prepareFileUploader": function(controlId, vals)
		{
			if(BX.CFileInput.Items[controlId])
			{
				BX.CFileInput.Items[controlId].setFiles(vals);
			}

			if(this.container)
				this.container.style.display = "";

			return this.container;
		},
		"getDefaultStorageTypeId": function()
		{
			return parseInt(this.getSetting("defaultStorageTypeId", BX.CrmQuoteStorageType.file));
		},
		"getWebDavElementInfo": function(elementId, callback)
		{
			BX.ajax(
				{
					"url": this.getSetting("serviceUrl", ""),
					"method": "POST",
					"dataType": "json",
					"data":
					{
						"ACTION" : "GET_WEBDAV_ELEMENT_INFO",
						"ELEMENT_ID": elementId
					},
					onsuccess: function(data)
					{
						var innerData = data["DATA"] ? data["DATA"] : {};
						if(BX.type.isFunction(callback))
						{
							try
							{
								callback(innerData["INFO"] ? innerData["INFO"] : {});
							}
							catch(e)
							{
							}
						}
					},
					onfailure: function(data)
					{
					}
				}
			);
		},
		"getWebDavUploaderValues": function(name)
		{
			var result = [];

			var uploader = BX.CrmWebDavUploader.items[name];
			var elements = uploader ? uploader.getValues() : [];
			for(var i = 0; i < elements.length; i++)
			{
				result.push(elements[i]["ID"]);
			}

			return result;
		},
		"getDiskUploaderValues": function(name)
		{
			var uploader = BX.CrmDiskUploader.items[name];
			return uploader ? uploader.getFileIds() : [];
		},
		"getFileUploaderValues": function(files)
		{
			var result = [];
			if(BX.type.isElementNode(files))
			{
				result.push(files.value);
			}
			else if(BX.type.isArray(files) || typeof(files.length) !== 'undefined')
			{
				for(var i = 0; i < files.length; i++)
				{
					result.push(files[i].value);
				}
			}

			return result;
		},
		"onSaveHandler": function(inputContainer)
		{
			var data = {};
			if(this.storageTypeId === BX.CrmQuoteStorageType.webdav)
			{
				data["webdavelements"] = this.getWebDavUploaderValues(this.getSetting("uploaderName", ""));
			}
			else if(this.storageTypeId === BX.CrmQuoteStorageType.diskfiles)
			{
				data["diskfiles"] = this.getDiskUploaderValues(this.getSetting("uploaderName", ""));
			}
			else
			{
				data["files"] = this.getFileUploaderValues(this.getDialogElements(this.getSetting("uploadInputID", "") + "[]"));
				var controlId = this.getSetting("uploadControlID", "");
				if(typeof(BX.CFileInput) !== "undefined"
					&& typeof(BX.CFileInput.Items[controlId]) !== "undefined")
				{
					data["uploadControlCID"] = BX.CFileInput.Items[controlId].CID;
				}
			}

			if (this.container)
			{
				// clear
				var arr, name;
				arr = BX.findChildren(this.container, {"tag": "input", "attr": {"type": "hidden"}});
				if (arr instanceof Array && arr.length > 0)
				{
					for (var i in arr)
					{
						name = arr[i].name;
						if (name.match(/^(webdavelements|diskfiles|files|uploadcontrolcid|storagetypeid)(\[\d*\])*/i))
							BX.remove(arr[i]);
					}
				}

				// add hiddens
				if(this.storageTypeId === BX.CrmQuoteStorageType.webdav)
				{
					arr = data["webdavelements"];
					if (arr instanceof Array && arr.length > 0)
					{
						for (var i=0; i<arr.length; i++)
						{
							this.container.appendChild(
								BX.create(
									"INPUT",
									{
										"attrs": {
											"type": "hidden",
											"name": "webdavelements[]",
											"value": arr[i]
										}
									}
								)
							);
						}
					}
				}
				else if(this.storageTypeId === BX.CrmQuoteStorageType.diskfiles)
				{
					arr = data["diskfiles"];
					if (arr instanceof Array && arr.length > 0)
					{
						for (var i=0; i<arr.length; i++)
						{
							this.container.appendChild(
								BX.create(
									"INPUT",
									{
										"attrs": {
											"type": "hidden",
											"name": "diskfiles[]",
											"value": arr[i]
										}
									}
								)
							);
						}
					}
				}
				else
				{
					arr = data["files"];
					if (arr instanceof Array && arr.length > 0)
					{
						for (var i=0; i<arr.length; i++)
						{
							this.container.appendChild(
								BX.create(
									"INPUT",
									{
										"attrs": {
											"type": "hidden",
											"name": "files[]",
											"value": arr[i]
										}
									}
								)
							);
						}
					}
					if (data["uploadControlCID"])
					{
						this.container.appendChild(
							BX.create(
								"INPUT",
								{
									"attrs": {
										"type": "hidden",
										"name": "uploadControlCID",
										"value": data["uploadControlCID"]
									}
								}
							)
						);
					}
				}
				this.container.appendChild(
					BX.create(
						"INPUT",
						{
							"attrs": {
								"type": "hidden",
								"name": "storageTypeId",
								"value": this.storageTypeId
							}
						}
					)
				);
			}
		}
	}
}

if(typeof BX.CrmQuoteEditor === "undefined")
{
	BX.CrmQuoteEditor = function()
	{
		this._id = "";
		this._settings = {};

		this._formId = "";
		this._formManager = null;
		this._productRowEditorId = "";

		this._productEditor = null;
		this._currencyElement = null;
		this._opportunityElement = null;
		this._filesFieldContainer = null;
	};
	BX.CrmQuoteEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(8);
			this._settings = settings ? settings : {};
			this._formId = this.getSetting("formId", "");

			var form = BX("form_" + this._formId);

			this._currencyElement = BX.findChild(form, { "tag":"select", "attr":{ "name": "CURRENCY_ID" } }, true, false);
			this._opportunityElement = BX.findChild(form, { "tag":"input", "attr":{ "name": "OPPORTUNITY" } }, true, false);

			this._productEditor = BX.CrmProductEditor.getDefault();

			if(this._opportunityElement)
			{
				this._opportunityElement.disabled = this._productEditor.getProductCount() > 0;

				BX.addCustomEvent(
					this._productEditor,
					"productAdd",
					BX.delegate(this._handleProductAddRemove, this)
				);

				BX.addCustomEvent(
					this._productEditor,
					"productRemove",
					BX.delegate(this._handleProductAddRemove, this)
				);

				BX.addCustomEvent(
					this._productEditor,
					"sumTotalChange",
					BX.delegate(this._handleSumTotalChange, this)
				);

				if(this._currencyElement)
				{
					BX.bind(
						this._currencyElement,
						"change",
						BX.delegate(this._handleCurrencyChange, this)
					);
				}
			}

			var el = BX("LOC_CITY_val");
			if (el)
			{
				if (BX.hasClass(el, "search-suggest"))
				{
					BX.removeClass(el, "search-suggest");
				}
				BX.addClass(el, "crm-offer-item-inp");
				el.setAttribute("size", "255");
			}

			settings["filesFieldSettings"]["formId"] = this._formId;
			this._filesFieldContainer = new BX.FilesFieldContainer(settings["filesFieldSettings"]);

			var btns = [ "saveAndView", "saveAndAdd", "apply", "save", "continue" ];
			for (var i = 0; i < btns.length; i++)
			{
				var bid = this._formId + "_" + btns[i];
				BX.bind(BX(bid), "click", BX.delegate(this._onSaveHandler, this._filesFieldContainer));
			}

			if(typeof(BX.CrmEditFormManager) !== "undefined")
			{
				this._formManager = BX.CrmEditFormManager.items[(BX.type.isNotEmptyString(this._formId) ? this._formId : id).toLowerCase()];
			}

			this._productRowEditorId = this.getSetting("productRowEditorId", "");
			if(BX.type.isNotEmptyString(this._productRowEditorId))
			{
				BX.addCustomEvent("onProductEditorFocusChange", BX.delegate(this._onProductEditorFocusChange, this));
			}
		},
		_handleProductAddRemove: function(data)
		{
			if (data && typeof(data) === "object" && data.hasOwnProperty("product") && this._opportunityElement)
			{
				var product = data["product"];
				if (product && typeof(product) === "object" && product.hasOwnProperty("_editor"))
				{
					var prodEditor = product._editor;
					if (prodEditor && typeof(prodEditor) === "object")
						this._opportunityElement.disabled = (prodEditor.getProductCount() > 0);
				}
			}
		},
		_handleSumTotalChange: function(ttl)
		{
			if (this._opportunityElement)
			{
				this._opportunityElement.value = ttl;
			}
		},
		_handleCurrencyChange: function()
		{
			if (this._currencyElement && this._productEditor)
			{
				var currencyId = this._currencyElement.value;
				var prevCurrencyId = this._productEditor.getCurrencyId();

				this._productEditor.setCurrencyId(currencyId);

				var oportunity = this._opportunityElement.value.length > 0 ? parseFloat(this._opportunityElement.value) : 0;
				if(isNaN(oportunity))
				{
					oportunity = 0;
				}

				if(this._productEditor.getProductCount() == 0 && oportunity !== 0)
				{
					this._productEditor.convertMoney(
						parseFloat(this._opportunityElement.value),
						prevCurrencyId,
						currencyId,
						BX.delegate(this._callbackAfterProductEditorConvertMoney, this)
					);
				}
			}
		},
		_callbackAfterProductEditorConvertMoney: function(sum)
		{
			if (this._opportunityElement)
				this._opportunityElement.value = sum;
		},
		_onSaveHandler: function(e)
		{
			this.onSaveHandler();
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		_onProductEditorFocusChange: function(sender, focused)
		{
			if(sender.getId() !== this._productRowEditorId)
			{
				return;
			}
		}
	};

	BX.CrmQuoteEditor.items = {};
	BX.CrmQuoteEditor.create = function(id, settings)
	{
		var self = new BX.CrmQuoteEditor();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}