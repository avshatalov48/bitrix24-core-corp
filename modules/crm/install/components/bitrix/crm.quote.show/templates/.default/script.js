if (typeof BX.CrmQuoteShowInitScript === "undefined")
{
	BX.CrmQuoteShowInitScript = function(settings)
	{
		var enableInstantEdit = !!settings["enableInstantEdit"];

		if (enableInstantEdit)
		{
			var messages = settings["messages"];

			BX.CrmInstantEditorMessages =
			{
				editButtonTitle: messages["editButtonTitle"],
				lockButtonTitle: messages["lockButtonTitle"]
			};

			var instantEditor = BX.CrmInstantEditor.create(
				settings["instantEditorId"],
				{
					containerID: [settings["summaryContainerId"]],
					ownerType: settings["ownerType"],
					ownerID: parseInt(settings["ownerId"]),
					url: settings["url"],
					callToFormat: parseInt(settings["callToFormat"])
				}
			);

			instantEditor.setFieldReadOnly('SUM_PAID', true);

			var prodEditor = typeof(BX.CrmProductEditor) !== 'undefined' ? BX.CrmProductEditor.getDefault() : null;

			function handleProductRowChange()
			{
				if(prodEditor)
				{
					var haveProducts = prodEditor.getProductCount() > 0;
					instantEditor.setFieldReadOnly('OPPORTUNITY', haveProducts);
					instantEditor.setFieldReadOnly('CURRENCY_ID', haveProducts);
				}
			}

			function handleSelectProductEditorTab(objForm, objFormName, tabID, tabElement)
			{
				var productRowsTabId = settings["productRowsTabId"];
				if (typeof(productRowsTabId) === "string" && productRowsTabId.length > 0 && tabID === productRowsTabId)
					BX.onCustomEvent("CrmHandleShowProductEditor", [prodEditor]);
			}

			if(prodEditor)
			{
				BX.addCustomEvent(
					prodEditor,
					'sumTotalChange',
					function(ttl)
					{
						instantEditor.setFieldValue('OPPORTUNITY', ttl);
						if(prodEditor.isViewMode())
						{
							//emulate save field event to refresh controls
							instantEditor.riseSaveFieldValueEvent('OPPORTUNITY', ttl);
						}
					}
				);

				handleProductRowChange();

				BX.addCustomEvent(
					prodEditor,
					'productAdd',
					handleProductRowChange
				);

				BX.addCustomEvent(
					prodEditor,
					'productRemove',
					handleProductRowChange
				);

				BX.addCustomEvent(
					'BX_CRM_INTERFACE_FORM_TAB_SELECTED',
					handleSelectProductEditorTab
				);
			}
		}

		// files field
		settings["filesFieldSettings"]["formId"] = settings["formId"];
		var filesFieldContainer = new BX.FilesFieldContainer(settings["filesFieldSettings"]);
	}
}

if(typeof(BX.FilesFieldContainer) === "undefined")
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
			var files = this.getSetting("diskfiles", []);
			if (this.controlMode !== BX.CrmQuoteMode.view || files.length > 0)
			{
				this.prepareDiskUploader(
					uploaderName,
					this.controlMode,
					files
				);
			}
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
							'diskAttachedFiles' : this.getMessage('diskAttachedFiles')
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

if(typeof(BX.CrmQuoteViewForm) === "undefined")
{
	BX.CrmQuoteViewForm = function()
	{
		this._id = "";
		this._settings = {};
		this._messages = BX.CrmQuoteViewForm.messages;

		this._printUrl = "";
		this._downloadPdfUrl = "";
		this._createPdfFileUrl = "";
		this._printTemplates = [];

		this._printDlg = null;
		this._isCreatePdfRequestRunning = false;
	};
	BX.CrmQuoteViewForm.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			var printUrl = this.getSetting("printUrl");
			if(BX.type.isNotEmptyString(printUrl))
			{
				this._printUrl = printUrl;
			}

			var downloadPdfUrl = this.getSetting("downloadPdfUrl");
			if(BX.type.isNotEmptyString(downloadPdfUrl))
			{
				this._downloadPdfUrl = downloadPdfUrl;
			}

			var createPdfFileUrl = this.getSetting("createPdfFileUrl");
			if(BX.type.isNotEmptyString(createPdfFileUrl))
			{
				this._createPdfFileUrl = createPdfFileUrl;
			}

			var printTemplates = this.getSetting("printTemplates");
			if(BX.type.isArray(printTemplates))
			{
				this._printTemplates =  printTemplates;
			}

			BX.addCustomEvent(window, "CrmQuotePrint", BX.delegate(this._onPrint, this));
			BX.addCustomEvent(window, "CrmQuoteDownloadPdf", BX.delegate(this._onDownloadPdf, this));
			BX.addCustomEvent(window, "CrmQuoteSendByEmail", BX.delegate(this._onSendByEmail, this));
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return this._messages.hasOwnProperty(name) ? this._messages[name] : "";
		},
		_onPrint: function(sender, params)
		{
			if(this._printUrl === "")
			{
				alert(this.getMessage("noPrintUrlError"));
				return;
			}

			if(this._printTemplates.length === 0)
			{
				alert(this.getMessage("noPrintTemplatesError"));
				return;
			}

			if(!BX.type.isPlainObject(params))
			{
				params = {};
			}
			var blank = BX.type.isBoolean(params["blank"]) ? params["blank"] : false;
			var handler = BX.CrmQuotePrintHandler.create({ urlTemplate: this._printUrl, blank: blank, openNewWindow: true });
			if(this._printTemplates.length > 1)
			{
				this._openPrintDialog(this._printTemplates, handler);
				return;
			}

			handler.print(this._printTemplates[0]['ID']);
		},
		_onDownloadPdf: function(sender, params)
		{
			if(this._downloadPdfUrl === "")
			{
				alert(this.getMessage("noPrintUrlError"));
				return;
			}

			if(this._printTemplates.length === 0)
			{
				alert(this.getMessage("noPrintTemplatesError"));
				return;
			}

			if(!BX.type.isPlainObject(params))
			{
				params = {};
			}

			var blank = BX.type.isBoolean(params["blank"]) ? params["blank"] : false;
			var handler = BX.CrmQuotePrintHandler.create({ urlTemplate: this._downloadPdfUrl, blank: blank, openNewWindow: false });
			if(this._printTemplates.length > 1)
			{
				this._openPrintDialog(this._printTemplates, handler);
				return;
			}

			handler.print(this._printTemplates[0]['ID']);
		},
		_onSendByEmail: function(sender)
		{
			if(this._createPdfFileUrl === "")
			{
				alert(this.getMessage("noPrintUrlError"));
				return;
			}

			if(this._printTemplates.length === 0)
			{
				alert(this.getMessage("noPrintTemplatesError"));
				return;
			}

			var handler = BX.CrmQuoteSendByEmailHandler.create(
				{
					entityId: this.getSetting("entityId"),
					activityEditorId: this.getSetting("activityEditorId"),
					communications: this.getSetting("emailCommunications"),
					title: this.getSetting("emailTitle"),
					createPdfFileUrl: this._createPdfFileUrl
				}
			);
			if(this._printTemplates.length > 1)
			{
				this._openPrintDialog(this._printTemplates, handler);
				return;
			}

			handler.createEmail(this._printTemplates[0]['ID']);
		},
		_openPrintDialog: function(templates, handler)
		{
			if(!this._printDlg)
			{
				this._printDlg = BX.CrmQuotePrintDialog.create(
					this._id,
					{
						templates: templates,
						handler: handler,
						onClose: BX.delegate(this._onPrintDialogClose, this)
					}
				);
			}
			else
			{
				this._printDlg.setSetting("templates", templates);
				this._printDlg.setSetting("handler", handler);
			}

			this._printDlg.open();
		},
		_onPrintDialogClose: function(dlg, bid)
		{
			if(bid !== BX.CrmQuotePrintDialog.buttons.print)
			{
				return;
			}

			var handler = dlg.getSetting("handler");
			if(!handler)
			{
				return;
			}

			var data = dlg.getData();
			var templateId = BX.type.isNotEmptyString(data["templateId"]) ? data["templateId"] : "";
			if(templateId !== "")
			{
				handler.process(templateId);
			}
		}
	};
	BX.CrmQuoteViewForm.addUrlParams = function(url, params)
	{
		if (!BX.type.isNotEmptyString(url))
		{
			return "";
		}

		if(BX.type.isString(params))
		{
			params = [ params ];
		}

		for(var i = 0; i < params.length; i++)
		{
			var param = params[i];
			if(url.indexOf(param) == -1)
			{
				url += ((url.indexOf("?") == -1) ? "?" : "&") + param;
			}
		}
		return url;
	};
	if(typeof(BX.CrmQuoteViewForm.messages) === "undefined")
	{
		BX.CrmQuoteViewForm.messages = {};
	}
	BX.CrmQuoteViewForm.create = function(id, settings)
	{
		var self = new BX.CrmQuoteViewForm();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmQuotePrintHandler) === "undefined")
{
	BX.CrmQuotePrintHandler = function()
	{
		this._settings = {};
		this._urlTemplate = "";
	};
	BX.CrmQuotePrintHandler.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};

			this._urlTemplate = this.getSetting("urlTemplate");
			if(!BX.type.isNotEmptyString(this._urlTemplate))
			{
				throw "CrmQuotePrintHandler. Could not find 'urlTemplate' setting.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		print: function(templateId)
		{
			var openNewWindow = this.getSetting("openNewWindow", true);
			if(openNewWindow)
			{
				jsUtils.OpenWindow(
					BX.CrmQuoteViewForm.addUrlParams(
						this._urlTemplate,
							[
								"PAY_SYSTEM_ID=" + templateId,
								"BLANK=" + (this.getSetting("blank", false) ? 'Y' : 'N')
							]
					),
					900,
					600
				);
			}
			else
			{
				jsUtils.Redirect([],
					BX.CrmQuoteViewForm.addUrlParams(
						this._urlTemplate,
							[
								"PAY_SYSTEM_ID=" + templateId,
								"BLANK=" + (this.getSetting("blank", false) ? 'Y' : 'N')
							]
					),
					900,
					600
				);
			}
		},
		process: function(templateId)
		{
			this.print(templateId);
		}
	};
	BX.CrmQuotePrintHandler.create = function(settings)
	{
		var self = new BX.CrmQuotePrintHandler();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmQuoteSendByEmailHandler) === "undefined")
{
	BX.CrmQuoteSendByEmailHandler = function()
	{
		this._settings = {};
		this._entityId = "";
		this._activityEditorId = "";
		this._communications = [];
		this._title = "";
		this._createPdfFileUrl = "";
		this._isRequestRunning = false;
	};
	BX.CrmQuoteSendByEmailHandler.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};

			this._entityId = this.getSetting("entityId");
			if(!BX.type.isNotEmptyString(this._entityId))
			{
				throw "CrmQuoteSendByEmailHandler. Could not find 'entityId' setting.";
			}

			this._activityEditorId = this.getSetting("activityEditorId");
			if(!BX.type.isNotEmptyString(this._activityEditorId))
			{
				throw "CrmQuoteSendByEmailHandler. Could not find 'activityEditorId' setting.";
			}

			var communications = this.getSetting("communications");
			if(BX.type.isArray(communications))
			{
				this._communications = communications;
			}

			this._title = this.getSetting("title");

			this._createPdfFileUrl = this.getSetting("createPdfFileUrl");
			if(!BX.type.isNotEmptyString(this._createPdfFileUrl))
			{
				throw "CrmQuoteSendByEmailHandler. Could not find 'createPdfFileUrl' setting.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		createEmail: function(templateId)
		{
			this._beginCreatePdfFileRequest(templateId);
		},
		_beginCreatePdfFileRequest: function(paySystemId)
		{
			if (top.BX.Bitrix24 && top.BX.Bitrix24.Slider)
			{
				this._onCreatePdfFileRequestSuccess.apply(this, [{__psid: paySystemId}]);
				return;
			}

			if(this._createPdfFileUrl === "")
			{
				return;
			}

			if(this._isRequestRunning)
			{
				return;
			}

			var data = {
				"QUOTE_ID": this._entityId,
				"PAY_SYSTEM_ID": paySystemId,
				"MODE": "SAVE_PDF",
				"GET_CONTENT": "Y",
				"pdf": 1,
				"sessid": BX.bitrix_sessid()
			};

			BX.showWait();

			BX.ajax(
				{
					data: data,
					method: "POST",
					dataType: "json",
					url: this._createPdfFileUrl,
				onsuccess: BX.delegate(this._onCreatePdfFileRequestSuccess, this),
				onfailure: BX.delegate(this._onCreatePdfFileRequestFailure, this)
			});

			this._isRequestRunning = true;
		},
		_onCreatePdfFileRequestSuccess: function(result)
		{
			this._isRequestRunning = false;
			BX.closeWait();

			if(!result)
			{
				BX.debug("CrmQuoteSendByEmailHandler. Could not create PDF. Unknown error.");
				return;
			}

			if(BX.type.isNotEmptyString(result["ERROR"]))
			{
				BX.debug("CrmQuoteSendByEmailHandler. Could not create PDF. " + result["ERROR"]);
				return;
			}

			var emailSettings = {};
			if(result["webdavelement"])
			{
				emailSettings["storageTypeID"] = BX.CrmActivityStorageType.webdav;
				emailSettings["webdavelements"] = [ result["webdavelement"] ];
			}
			else if(result["diskfile"])
			{
				emailSettings["storageTypeID"] = BX.CrmActivityStorageType.disk;
				emailSettings["diskfiles"] = [ result["diskfile"] ];
			}
			else if(result["file"])
			{
				emailSettings["storageTypeID"] = BX.CrmActivityStorageType.file;
				result["file"]["fileURL"] = result["file"]["src"];
				emailSettings["files"] = [ result["file"] ];
			}

			var comms = [];
			for(var i = 0; i < this._communications.length; i++)
			{
				var commItem = this._communications[i];
				comms.push(
					{
						entityType: commItem['ENTITY_TYPE'],
						entityId: commItem['ENTITY_ID'],
						entityTitle: commItem['TITLE'],
						type: commItem['TYPE'],
						value: commItem['VALUE']

					}
				);
			}

			if(comms.length > 0)
			{
				emailSettings["communications"] = comms;
			}

			emailSettings['ownerType'] = 'QUOTE';
			emailSettings['ownerID'] = this._entityId;
			if (result.__psid > 0)
				emailSettings['ownerPSID'] = result.__psid;

			emailSettings["subject"] = this._title;
			BX.CrmActivityEditor.items[this._activityEditorId].addEmail(emailSettings);
		},
		_onCreatePdfFileRequestFailure: function(result)
		{
			this._isRequestRunning = false;
			BX.closeWait();
			BX.debug("CrmQuoteSendByEmailHandler. Could not create PDF. Unknown error.");
		},
		process: function(templateId)
		{
			this.createEmail(templateId);
		}
	};
	BX.CrmQuoteSendByEmailHandler.create = function(settings)
	{
		var self = new BX.CrmQuoteSendByEmailHandler();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmQuotePrintDialog) === "undefined")
{
	BX.CrmQuotePrintDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._messages = BX.CrmQuotePrintDialog.messages;
		this._data = {};

		this._isOpened = false;
		this._popup = null;

		this._templateSelect = null;
		this._onCloseCallback = null;
	};
	BX.CrmQuotePrintDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			var callback = this.getSetting("onClose");
			this._onCloseCallback = BX.type.isFunction(callback) ? callback : null;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, value)
		{
			return this._settings[name] = value;
		},
		getId: function()
		{
			return this._id;
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		getMessage: function(name)
		{
			return this._messages.hasOwnProperty(name) ? this._messages[name] : "";
		},
		getData: function()
		{
			return this._data;
		},
		open: function()
		{
			if(this._isOpened)
			{
				return;
			}

			this._popup = new BX.PopupWindow(
				this._id,
				null,
				{
					autoHide: false,
					draggable: true,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: true },
					closeByEsc: true,
					titleBar: this.getMessage("title"),
					content: this._prepareContent(),
					events:
					{
						onPopupShow: BX.delegate(this._onPopupShow, this),
						onPopupClose: BX.delegate(this._onPopupClose, this),
						onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
					},
					buttons: this._prepareButtons()
				}
			);
			this._popup.show();
		},
		_prepareContent: function()
		{
			var tab = BX.create(
				'TABLE',
				{
					attrs: { className: 'bx-crm-dialog-quote-print-table' }
				}
			);
			tab.cellSpacing = '0';
			tab.cellPadding = '0';
			tab.border = '0';

			var select = this._templateSelect = BX.create(
				"SELECT",
				{
					attrs: { className: "bx-crm-dialog-quick-create-field-select" }
				}
			);

			var selectItems = this.getSetting("templates");
			if(!BX.type.isArray(selectItems))
			{
				selectItems = [];
			}

			for(var i = 0; i < selectItems.length; i++)
			{
				var selectItem = selectItems[i];
				var option = BX.create("OPTION", { props: { value: selectItem['ID'] }, text: selectItem['NAME'] });

				if(!BX.browser.isIE)
				{
					select.add(option, null);
				}
				else
				{
					try
					{
						// for IE earlier than version 8
						select.add(option, select.options[null]);
					}
					catch (e)
					{
						select.add(option,null);
					}
				}
			}

			var row = tab.insertRow(-1);
			var cell = row.insertCell(-1);
			cell.className = "bx-crm-dialog-quote-print-table-left";
			cell.appendChild(BX.create("SPAN", { text: this.getMessage("templateField") + ":" }));

			cell = row.insertCell(-1);
			cell.className = "bx-crm-dialog-quote-print-table-right";
			cell.appendChild(select);

			return BX.create(
				"DIV",
				{
					attrs: { className: "bx-crm-dialog-quote-print-popup" },
					children: [ tab ]
				}
			);
		},
		_prepareButtons: function()
		{
			var printButton = new BX.PopupWindowButton(
				{
					text: this.getMessage("printButton"),
					className: "popup-window-button-accept",
					events: { click: BX.delegate(this._onPrintButtonClick, this) }
				}
			);
			var cancelButton = new BX.PopupWindowButtonLink(
				{
					text: this.getMessage("cancelButton"),
					className: "popup-window-button-link-cancel",
					events: { click: BX.delegate(this._onCancelButtonClick, this) }
				}
			);
			return [ printButton, cancelButton ];
		},
		_onPrintButtonClick: function()
		{
			this._data = { templateId: this._templateSelect.value };
			if(this._onCloseCallback)
			{
				this._onCloseCallback(this, BX.CrmQuotePrintDialog.buttons.print);
			}

			if(this._popup)
			{
				this._popup.close();
			}
		},
		_onCancelButtonClick: function()
		{
			if(this._onCloseCallback)
			{
				this._onCloseCallback(this, BX.CrmQuotePrintDialog.buttons.cancel);
			}

			if(this._popup)
			{
				this._popup.close();
			}
		},
		_onPopupShow: function()
		{
			this._isOpened = true;
		},
		_onPopupClose: function()
		{
			this._isOpened = false;
			if(this._popup)
			{
				this._popup.destroy();
			}
			this._templateSelect = null;
		},
		_onPopupDestroy: function()
		{
			this._popup = null;
		}
	};
	BX.CrmQuotePrintDialog.buttons = { cancel: 0, print: 1 };
	if(typeof(BX.CrmQuotePrintDialog.messages) === "undefined")
	{
		BX.CrmQuotePrintDialog.messages = {};
	}

	BX.CrmQuotePrintDialog.create = function(id, settings)
	{
		var self = new BX.CrmQuotePrintDialog();
		self.initialize(id, settings);
		return self;
	};
}
