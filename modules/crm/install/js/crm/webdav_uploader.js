BX.CrmWebDavUploader = function()
{
	this._id = "";
	this._settings = {};
	this._hasLayout = false;
	this._mode = BX.CrmInterfaceMode.edit;
	this._values = [];
	this._canceled = [];
	this._enabled = true; // for future use
	this._wrapper = null;
	this._container = null;
	this._switchContainer = null;
	this._webDavElementSelectHandler = BX.delegate(this._onSelectElementBtnClick, this);
	this._showUploadedFileHandler = BX.delegate(this._onShowUploadedFile, this);
	this._stopUploadHandler = BX.delegate(this._onStopUpload, this);
	this._bindLoadedControlsHandler = BX.delegate(this._onBindLoadedControls, this);
	this._webDavDialogSectionOpenHandler = BX.delegate(this._openSection, this);
	this._webDavDialogElementSelectHandler = BX.delegate(this._onElementSelect, this);
	this._webDavDialogCheckFileNameHandler = BX.delegate(this._checkFileName, this);
	this._webDavDialogSelectElementHandler = BX.delegate(this._selectElement, this);
	this._webDavDialogUnselectElementHandler = BX.delegate(this._unselectElement, this);
	this._webDavDialogSelectFolderHandler = BX.delegate(this._selectFolder, this);
	this._onUploaderInitHandler = BX.delegate(this._onUploaderInit, this);
	this._onUploadStartHandler = BX.delegate(this._onUploadStart, this);

	this._agent = null;
	this._dialogName = "AttachFileDialog";
	this._uploadDialogID = 0;
	this._uploadDialog = null;
	this._noticeId = null;
	this._arAgent = {};
};

BX.CrmWebDavUploader.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};
		this._uploadDialogID = parseInt(new Date().getTime().toString().substr(6));

		this._mode = parseInt(this.getSetting("mode", BX.CrmInterfaceMode.edit));
		this._values = this.getSetting("values", []);
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != "undefined" ? this._settings[name] : defaultval;
	},
	getMessages: function()
	{
		return this.getSetting("msg", {});
	},
	getMessage: function(name, defaultval)
	{
		if(typeof(defaultval) === "undefined")
		{
			defaultval = "";
		}

		var m = this.getMessages();
		return typeof(m[name]) !== "undefined" ? m[name] : defaultval;
	},
	getMode: function()
	{
		return this._mode;
	},
	setMode: function(mode)
	{
		if(this._mode === mode)
		{
			return;
		}

		if(this._hasLayout)
		{
			throw "Could not set mode while control has layout.";
		}

		this._mode = mode;
	},
	getValues: function()
	{
		return this._values;
	},
	setValues: function(vals)
	{
		if(this._hasLayout)
		{
			throw "Could not set value while control has layout.";
		}

		this._values = [];

		if(BX.type.isArray(vals) && vals.length > 0)
		{
			for(var i = 0; i < vals.length; i++)
			{
				var src = vals[i];
				var dst =
				{
					"ID": typeof(src["ID"]) !== "undefined" ? parseInt(src["ID"]) : 0
				};

				for (var attr in src)
				{
					if (attr !== "ID" && src.hasOwnProperty(attr))
					{
						dst[attr] = src[attr];
					}
				}

				this._values.push(dst);
			}
		}
	},
	hasValues: function()
	{
		return this._values.length > 0;
	},
	addValue: function(elem)
	{
		var elemId =  elem["ID"] = parseInt(elem["ID"]);
		if(!this.getValueById(elemId))
		{
			this._values.push(elem);
		}

	},
	getValueById: function(id)
	{
		for(var i = 0; i < this._values.length; i++)
		{
			var curElem = this._values[i];
			if(curElem["ID"] === id)
			{
				return curElem;
			}
		}

		return null;
	},
	removeValue: function(elemId)
	{
		elemId = parseInt(elemId);
		for(var i = 0; i < this._values.length; i++)
		{
			var curElem = this._values[i];
			if(curElem["ID"] === elemId)
			{
				this._values.splice(i, 1);
				return;
			}
		}
	},
	_addCanceled: function(name)
	{
		for(var i = 0; i < this._canceled.length; i++)
		{
			if(this._canceled[i] === name)
			{
				return;
			}
		}

		this._canceled.push(name);
	},
	_removeCanceled: function(name)
	{
		for(var i = 0; i < this._canceled.length; i++)
		{
			if(this._canceled[i] === name)
			{
				this._canceled.splice(i, 1);
				return;
			}
		}
	},
	_cancelNotice: function()
	{
		if (this._noticeId)
		{
			window.clearTimeout(this._noticeId);
			this._noticeId = null;
		}
	},
	_getFileInputName: function()
	{
		return this.getSetting("inputFileName", "SourceFile_1");
	},
	_getPlaceHolder: function()
	{
		return BX.findChild(this._container, { "className": "files-list" }, true);
	},
	_findElement: function(params)
	{
		return BX.findChild(this._container, params, true);
	},
	showLabel: function(show)
	{
		this._findElement({ "className": "wduf-label" }).style.display = !!show ? "" : "none";
	},
	_openSection: function(link, dialogName)
	{
		if (dialogName == this._dialogName)
		{
			BX.WebDavFileDialog.target[dialogName] =
				BX.CrmWebDavUploader.addSessionParam(
					BX.CrmWebDavUploader.addUrlParam(link, "dialog2=Y")
				);
		}
	},
	_onElementSelect: function(tab, path, selected)
	{
		var agent = this._agent;
		if(!agent)
		{
			return;
		}

		for (var i in selected) {
			if ((i.substr(0,1) == 'E') && (!BX('wduf-doc'+i.substr(1)))) {
				var fileID = i.substr(1);
				agent._mkPlace(selected[i].name, fileID);
				var ar = {'element_id':fileID, 'element_url':selected[i].link};
				agent.values.push(ar);
			}
		}
		if (agent.values.length > 0)
		{
			agent.ShowAttachedFiles();
		}
		BX.removeCustomEvent(BX.WebDavFileDialog, "loadItems", this._webDavDialogSectionOpenHandler);
	},
	_onElementLoad: function(result)
	{
		var self = this;
		window.setTimeout(
			function()
			{
				BX.WebDavFileDialog.obCallback[self._dialogName] = { "saveButton" : self._webDavDialogElementSelectHandler };
				BX.WebDavFileDialog.openDialog(self._dialogName);
			},
			0
		);
	},
	_onSelectElementBtnClick: function(e)
	{
		var selectUrl = BX.CrmWebDavUploader.addSessionParam(
			BX.CrmWebDavUploader.addUrlParam(
				this.getSetting("urlSelect", ""), "dialog2=Y&ACTION=SELECT&MULTI=Y")
		);

		if(selectUrl === "")
		{
			return BX.PreventDefault(e);
		}

		BX.addCustomEvent(BX.WebDavFileDialog, "loadItems", this._webDavDialogSectionOpenHandler);
		BX.ajax.get(
			selectUrl,
			"dialogName=" + this._dialogName,
			BX.delegate(this._onElementLoad, this)
		);

		return BX.PreventDefault(e);
	},
	_onUploadedFileHtmlLoad: function(html)
	{
		var agent = this._agent;
		if(!agent)
		{
			return;
		}

		if (BX.type.isElementNode(html)) {
			if (html.parentNode)
				html.parentNode.removeChild(html);
		}

		var wrapper = BX.create(
			"DIV",
			{
				children: [ html || "&nbsp;" ]
			}
		);

		var rows = BX.findChildren(agent.placeholder, { "tag": "TR" }, true);
		var elementRow = BX.findChild(wrapper, { "className": "wd-inline-file" }, true);
		if(!elementRow)
		{
			var msg = BX.findChild(wrapper, { "className": "errortext" }, true);
			if (BX.type.isNotEmptyString(msg))
			{
				agent.uploadResult.messages = msg.innerHTML;
				agent.ShowUploadError();
			}
		}
		else if (BX.style(agent.place, "display") == "none")
		{
			agent.StopUpload(elementRow);
		}
		else if(rows)
		{
			for (var i = 0; i < rows.length; i++)
			{
				if (rows[i] !== agent.place)
				{
					continue;
				}

				var newRow = agent.placeholder.insertRow(i);
				newRow.className = elementRow.className;
				newRow.id = elementRow.id;
				var cells = BX.findChildren(elementRow, { "tag":"TD" }, true);
				if (cells)
				{
					for (var j = 0; j < cells.length; j++)
					{
						var newCell = newRow.insertCell(-1);
						newCell.className = cells[j].className;
						newCell.innerHTML = cells[j].innerHTML;
					}
				}
				BX.cleanNode(agent.place, true);
				agent._clearPlace();
				agent._mkClose(newRow);
				setTimeout(BX.delegate(agent.ShowAttachedFiles, agent), 100);
				break;
			}
		}

		this._addUploadedFileActions(agent.uploadResult.element_id);
		agent._clearPlace();

	},
	_addElementRow: function(placeholder, row, index)
	{
		if(!BX.type.isElementNode(row) || !BX.type.isElementNode(placeholder))
		{
			return;
		}

		index = parseInt(index);
		if(isNaN(index))
		{
			index = -1;
		}

		var newRow = placeholder.insertRow(index);
		newRow.className = row.className;
		newRow.id = row.id;
		var cells = BX.findChildren(row, { "tag":"TD" }, true);
		if (cells)
		{
			for (var j = 0; j < cells.length; j++)
			{
				var newCell = newRow.insertCell(-1);
				newCell.className = cells[j].className;
				newCell.innerHTML = cells[j].innerHTML;
			}
		}

		if(this._agent)
		{
			this._agent._mkClose(newRow);
		}
	},
	_showElement: function(data, mode)
	{
		if(typeof(data["ID"]) === "undefined")
		{
			return;
		}

		if(typeof(mode) === "undefined")
		{
			mode = this._mode;
		}

		var placeholder = this._getPlaceHolder();
		var row = placeholder.insertRow(-1);
		row.id = "wd-doc" + data["ID"];
		row.className = "wd-inline-file";

		// Name
		var c1 = row.insertCell(-1);
		c1.className = "files-name";

		var name = BX.create(
			"SPAN",
			{
				"attrs": { "class": "files-text" }
			}
		);

		if(typeof(data["VIEW_URL"]) !== "undefined")
		{
			name.appendChild(
				BX.create(
					"A",
					{
						"props":
						{
							"target": "_blank",
							"href": data["VIEW_URL"]
						},
						"text": typeof(data["NAME"]) !== "undefined" ? data["NAME"] : data["ID"]
					}
				)
			);
		}
		else
		{
			name.appendChild(
				BX.create(
					"SPAN",
					{
						"attrs": { "class": "f-wrap" },
						"text": typeof(data["NAME"]) !== "undefined" ? data["NAME"] : data["ID"]
					}
				)
			);
		}

		c1.appendChild(name);

		// Size
		var c2 = row.insertCell(-1);
		c2.className = "files-size";

		if(typeof(data["SIZE"]) !== "undefined")
		{
			c2.innerHTML = BX.util.htmlspecialchars(data["SIZE"]);
		}

		// Service
		var c3 = row.insertCell(-1);
		c3.className = "files-storage";
		c3.appendChild(
			BX.create(
				"DIV",
				{
					"attrs": { "class": "files-storage-block" }
				}
			)
		);

		if(mode === BX.CrmInterfaceMode.edit)
		{
			var agent = data["agent"] !== undefined ? data["agent"] : this._agent;
			if(agent)
			{
				agent._mkClose(row);
			}
			this._addUploadedFileActions(parseInt(data["ID"]));
		}
	},
	_getElementIdByNode: function(node)
	{
		var ary = node.id.match(/wd-doc(\d+)/);
		return BX.type.isArray(ary) ? parseInt(ary[1]) : 0;
	},
	_onElementHtmlLoad: function(html)
	{
		if (BX.type.isElementNode(html)) {
			if (html.parentNode)
				html.parentNode.removeChild(html);
		}

		var wrapper = BX.create(
			"DIV",
			{
				children: [ html || "&nbsp;" ]
			}
		);

		var row = BX.findChild(wrapper, { "className": "wd-inline-file" }, true);
		if(!row)
		{
			return;
		}

		this._addElementRow(this._getPlaceHolder(), row);
		this._addUploadedFileActions(this._getElementIdByNode(row));
	},
	_onElementInfoLoad: function(info)
	{
		var elementId = typeof(info["ID"]) !== "undefined" ? parseInt(info["ID"]) : 0;
		if(elementId <= 0)
		{
			return;
		}

		info["ID"] = elementId; //type convert

		//var agent = this._agent;
		var agent = this._arAgent[elementId] !== undefined ? this._arAgent[elementId] : null;
		if(agent)
		{
			BX.cleanNode(agent.place, true);
			agent._clearPlace();
		}

		for(var i = 0; i < this._canceled.length; i++)
		{
			if(this._canceled[i] === info["NAME"])
			{
				this.removeValue(elementId);
				this._removeCanceled(info["NAME"]);
				this.showLabel(this.hasValues());
				if(BX.type.isNotEmptyString(info["DELETE_URL"]))
				{
					BX.ajax.get(
						BX.CrmWebDavUploader.addSessionParam(
							BX.CrmWebDavUploader.addUrlParam(
								info["DELETE_URL"],
								"&EDIT=Y&AJAX_MODE=Y&IFRAME=Y"
							)
						)
					);
				}
				return;
			}
		}

		for(var j = 0; j < this._values.length; j++)
		{
			if(this._values[i]["ID"] === elementId)
			{
				this._values[i] = info;
				break;
			}
		}

		info['agent'] = agent;
		this._showElement(info);
		this._addUploadedFileActions(elementId);
	},
	_onShowUploadedFile: function(agent)
	{
		if (!!agent.uploadResult.files)
		{
			agent.uploadResultArr = new Array();
			for (var ii in agent.uploadResult.files) {
				var tparams = {
					'element_id' : agent.uploadResult.element_id,
					'element_name' : ii,
					'place' :agent.place,
					'storage' : agent.uploadResult.storage
				};
				agent.uploadResultArr.push(tparams);
			}
		}

		var getFunction = function(uploadResultC, context)
		{
			var res = BX.delegate(
				function()
				{
					var elementId = uploadResultC && typeof(uploadResultC.element_id) != "undefined" ? parseInt(uploadResultC.element_id) : 0;
					if(elementId <= 0)
					{
						return;
					}

					this._arAgent[elementId] = agent;

					this.addValue(
						{
							"ID": elementId,
							"EDIT_URL": uploadResultC.element_url
						}
					);

					this.showLabel(true);
					var loader = this.getSetting("elementInfoLoader", null);
					if(loader && BX.type.isFunction(loader))
					{
						loader(elementId, BX.delegate(function(info){
							this._onElementInfoLoad(info);
							BX.cleanNode(uploadResultC.place, true);
							agent._clearPlace();
						}, this));
					}
				},
				context
			);
			res();
		};

		for (var i0=0;i0<agent.uploadResultArr.length;i0++)
		{
			var uploadResult = agent.uploadResultArr[i0];
			getFunction(uploadResult, this);
		}
	},
	_prepareElementShowUrl: function(elementId, url)
	{
		if(BX.type.isNotEmptyString(url))
		{
			return BX.CrmWebDavUploader.addSessionParam(BX.CrmWebDavUploader.addUrlParam(url, "inline=Y&IFRAME=Y"));
		}

		return BX.CrmWebDavUploader.addSessionParam(
			BX.CrmWebDavUploader.addUrlParam(
				this.getSetting("urlShow", "").replace("#element_id#", elementId).replace("#ELEMENT_ID#", elementId),
				"inline=Y&IFRAME=Y"
			)
		);
	},
	_onStopUpload: function(agent, row)
	{
		var elementId = this._getElementIdByNode(row);

		var fileName = "";
		var fileNameNode = BX.findChild(row, { "className": "f-wrap" }, true);
		if (fileNameNode)
		{
			fileName = fileNameNode.innerHTML;
		}

		var elementInfo = this.getValueById(elementId);
		if(!elementInfo)
		{
			if(fileName !== "")
			{
				this._addCanceled(fileName);
			}
		}
		else
		{
			if (fileName !== ""
				&& window.wduf_places
				&& (fileName in window.wduf_places))
			{
				delete window.wduf_places[fileName];
			}

			if(BX.type.isNotEmptyString(elementInfo["DELETE_URL"]))
			{
				BX.ajax.get(
					BX.CrmWebDavUploader.addSessionParam(
						BX.CrmWebDavUploader.addUrlParam(
							elementInfo["DELETE_URL"],
							"&EDIT=Y&AJAX_MODE=Y&IFRAME=Y"
						)
					)
				);
			}
		}

		BX.hide(row);
		this.removeValue(elementId);
		this.showLabel(this.hasValues());
	},
	_onBindLoadedControls: function(agent, id)
	{
		this._addUploadedFileActions(id);
	},
	_addUploadedFileActions: function(elementId)
	{
		if (isNaN(parseInt(elementId)) || (elementId <= 0))
			return false;

		var parent = BX("wd-doc" + elementId);
		var fileMove = BX.findChild(parent, {'className' : 'files-path'}, true);
		if (fileMove)
		{
			fileMove.setAttribute('file-action-control', 'enabled');

			var fileName = BX.findChild(parent, {'className' : 'f-wrap'}, true);
			if (fileMove)
			{
				var self = this;
				BX.bind(
					fileMove,
					'click',
					BX.delegate(
						function(e)
						{
							self._moveUploadedFile(elementId, fileName.innerHTML);
						},
						this
					)
				);
			}
		}

		return true;
	},
	_checkFileName: function(dialogName)
	{
		this._cancelNotice();

		if (BX.type.isNotEmptyString(dialogName) && dialogName !== this._dialogName)
		{
			return;
		}

		var fileName = BX.WebDavFileDialog.arParams[dialogName]["element_name"];
		for (var i in BX.WebDavFileDialog.obItems[dialogName])
		{
			if(BX.WebDavFileDialog.obItems[dialogName][i]["name"] === fileName)
			{
				BX.WebDavFileDialog.showNotice(this.getMessage("file_exists"), dialogName);
				return;
			}
		}

		BX.WebDavFileDialog.closeNotice(dialogName);
	},
	_selectElement: function(element, itemId, dialogName)
	{
		if (dialogName != this._dialogName)
		{
			return;
		}

		var targetId = itemId.substr(1);
		var url = BX.WebDavFileDialog.obCurrentTab[dialogName].link.replace("/files/lib/", "/files/") + "/element/upload/" + targetId +
			"/?use_light_view=Y&AJAX_CALL=Y&SIMPLE_UPLOAD=Y&IFRAME=Y&sessid=" + BX.bitrix_sessid() +
			"&SECTION_ID=" + targetId +
			"&CHECK_NAME=" + BX.WebDavFileDialog.arParams[dialogName]["element_name"];

		var self = this;
		BX.ajax.loadJSON(
			url,
			function(result)
			{
				var exists = (result.permission == true && result.okmsg != "");
				self._cancelNotice();

				self._noticeId = window.setTimeout(
					function()
					{
						if (exists)
						{
							BX.WebDavFileDialog.showNotice(self.getMessage("file_exists"), dialogName);
						}
						else
						{
							BX.WebDavFileDialog.closeNotice(dialogName);
						}
					},
					0
				);
			});
	},
	_unselectElement: function(dialogName)
	{
		this._cancelNotice();
		var self = this;
		this._noticeId = window.setTimeout(
			function()
			{
				self._checkFileName(dialogName);
			},
			0
		);
	},
	_selectFolder: function(tab, path, selected, folderByPath)
	{
		var id = false;
		if ((BX.WebDavFileDialog.arParams) &&
			(BX.WebDavFileDialog.arParams[this._dialogName]) &&
			(BX.WebDavFileDialog.arParams[this._dialogName]["element_id"]))
		{
			id = BX.WebDavFileDialog.arParams[this._dialogName]["element_id"];
		}

		var moved = false;
		for (var i in selected)
		{
			if (i.substr(0,1) == "S")
			{
				var secPath = tab.name+selected[i].path;
				var arProp = {
					"sectionID" : i.substr(1),
					"iblockID" : tab.iblock_id
				};
				this._showMovedFile(id, arProp, secPath);
				moved = true;
			}
		}

		if (!moved)
		{
			var secPath = tab.name;
			var arProp =
			{
				"sectionID" : tab.section_id,
				"iblockID" : tab.iblock_id
			};

			if (folderByPath && folderByPath.path && folderByPath.path !== '/')
			{
				secPath += folderByPath.path;
				arProp.sectionID = folderByPath.id.substr(1);
			}

			this._showMovedFile(id, arProp, secPath);
		}

		BX.removeCustomEvent(BX.WebDavFileDialog, 'loadItems', this._webDavDialogSectionOpenHandler);
		BX.removeCustomEvent(BX.WebDavFileDialog, 'loadItemsDone', this._webDavDialogCheckFileNameHandler);
		BX.removeCustomEvent(BX.WebDavFileDialog, 'selectItem', this._webDavDialogSelectElementHandler);
		BX.removeCustomEvent(BX.WebDavFileDialog, 'unSelectItem', this._webDavDialogUnselectElementHandler);
	},
	_showMovedFile: function(elementId, arProp, section_path)
	{
		if (isNaN(parseInt(elementId)) || (elementId <= 0))
		{
			return false;
		}

		var parent = BX("wd-doc" + elementId);
		var filemove = BX.findChild(parent, {"className": "files-path"}, true);

		BX.cleanNode(filemove);
		var pathLength = 0;
		section_path = section_path.split("/").join(" / ");
		pathLength = section_path.length+1;

		filemove.innerHTML = section_path;
		var w = parseInt(filemove.offsetWidth);
		var l = parseInt(filemove.parentNode.offsetWidth)-150;
		var MAX_PATH_LENGTH = l / (w / section_path.length) ;
		if (w > l) {
			var midName = Math.floor(MAX_PATH_LENGTH / 2) + 1;
			section_path = section_path.substr(0, midName) + " ... " + section_path.substr(section_path.length - midName);
			filemove.innerHTML = section_path;
		}

		var fileInput = BX("wduf-doc" + elementId);
		if (fileInput)
		{
			var arFile = this._fileUnserialize(fileInput.value);
			arFile.section = arProp["sectionID"];
			arFile.iblock = arProp["iblockID"];
			fileInput.value = this._fileSerialize(arFile);
		}
	},
	_moveUploadedFile: function(elementId, name)
	{
		var urlMove = BX.CrmWebDavUploader.addSessionParam(
			BX.CrmWebDavUploader.addUrlParam(
				this.getSetting("urlSelect", ""),
				"dialog2=Y&ID=E" + elementId + "&NAME=" + name + "&ACTION=FAKEMOVE&IFRAME=Y"
			)
		);

		BX.WebDavFileDialog.arParams = {};
		BX.WebDavFileDialog.arParams[this._dialogName] = {};
		BX.WebDavFileDialog.arParams[this._dialogName]["element_id"] = elementId;

		var row = BX("wd-doc" + elementId);
		var fileName = BX.findChild(row, { "className": "f-wrap" }, true);
		BX.WebDavFileDialog.arParams[this._dialogName]["element_name"] = fileName.innerHTML;

		BX.addCustomEvent(BX.WebDavFileDialog, "loadItems", this._webDavDialogSectionOpenHandler);
		BX.addCustomEvent(BX.WebDavFileDialog, "loadItemsDone", this._webDavDialogCheckFileNameHandler);
		BX.addCustomEvent(BX.WebDavFileDialog, "selectItem", this._webDavDialogSelectElementHandler);
		BX.addCustomEvent(BX.WebDavFileDialog, "unSelectItem", this._webDavDialogUnselectElementHandler);

		var self = this;
		BX.ajax.get(
			urlMove,
			"dialogName=" + this._dialogName,
			function(result)
			{
				window.setTimeout(
					function()
					{
						BX.WebDavFileDialog.obCallback[self._dialogName] = {"saveButton" : self._webDavDialogSelectFolderHandler};
						BX.WebDavFileDialog.openDialog(self._dialogName);
						self._checkFileName(self._dialogName);
					},
					0
				);
			}
		);
	},
	layout: function(parent)
	{
		var mode = this._mode;
		if(mode === BX.CrmInterfaceMode.edit)
		{
			this._prepareEditLayout(parent);

			var agent = this._agent = new BX.FileUploadAgent(
				{
					"caller": this,
					"controller": this._wrapper,
					"fileInputName": this._getFileInputName(), // Is required for IE
					"doc_prefix": "wd-doc",
					"values": [],
					"urlUpload" : this.getSetting("urlUpload", ""),
					"urlShow": this.getSetting("urlShow", ""),
					"classes":
					{
						"uploaderParent" : "wduf-uploader",
						"uploader" : "wduf-fileUploader",
						"tpl_simple" : "wduf-simple",
						"tpl_extended" : "wduf-extended",
						"selector" : "wduf-selector",
						"selector_active" : "wduf-selector-active"
					},
					"msg": this.getSetting("msg", {})
				});

			agent.fileInput = this._findElement({ "className": "wduf-fileUploader" });
			agent.placeholder = this._getPlaceHolder();

			BX.addCustomEvent(this, "ShowUploadedFile", this._showUploadedFileHandler);
			BX.addCustomEvent(this, "StopUpload", this._stopUploadHandler);
			BX.addCustomEvent(this, "BindLoadedFileControls", this._bindLoadedControlsHandler);

			agent.LoadDialogs("DropInterface");

			var hasValues = this._values.length > 0;
			if(hasValues)
			{
				this.show();
			}

			this.showLabel(hasValues);
		}
		else if(mode === BX.CrmInterfaceMode.view)
		{
			this._prepareViewLayout(parent);
		}

		// HACK: for create tbody
		var placeholder = this._getPlaceHolder();
		placeholder.insertRow(-1);
		placeholder.deleteRow(0);

		for(var i = 0; i < this._values.length; i++)
		{
			this._showElement(this._values[i], mode);
		}

		this._hasLayout = true;
	},
	_prepareEditLayout: function(parent)
	{
		if(!BX.type.isElementNode(parent))
		{
			return;
		}

		var s = this._switchContainer = BX.create(
			"DIV",
			{
				"attrs": { "class": "wduf-switch-container" },
				"children":
					[
						BX.create(
							"A",
							{
								"attrs":
								{
									"class": "wduf-switch",
									"href": "#"
								},
								"text": this.getMessage("attachFile", "Attach file"),
								"events":
								{
									"click": BX.delegate(this._onSwitchButtonClick, this)
								}
							}
						)
					]
			}
		);

		var c = this._container = BX.create(
			"DIV",
			{
				"attrs": { "class": BX.browser.IsIE() ? "wduf-simple" : "wduf-extended" }
			}
		);

		var w = this._wrapper = BX.create(
			"DIV",
			{
				"attrs": { "class": "wduf-selectdialog" },
				"children":[ s, c ]
			}
		);
		parent.appendChild(w);

		c.appendChild(
			BX.create(
				"SPAN",
				{
					"text": this.getMessage("title", "Files") + ":",
					"attrs": { "class": "wduf-label" }
				}
			)
		);

		c.appendChild(
			BX.create(
				"DIV",
				{
					"attrs": { "class": "wduf-placeholder" },
					"children":
						[
							BX.create(
								"TABLE",
								{
									"attrs": { "class": "files-list", "cellspacing": "0", "cellpadding": "0", "border": "0" }
								}
							)
						]
				}
			)
		);

		if (BX.browser.IsIE())
		{
			c.appendChild(
				BX.create(
					"DIV",
					{
						"attrs": { "class": "wduf-selector" },
						"children":
							[
								BX.create(
									"SPAN",
									{
										"attrs": { "class": "wduf-uploader" },
										"children":
										[
											BX.create(
												"SPAN",
												{
													"attrs": { "class": "wduf-uploader-left" }
												}
											),
											BX.create(
												"SPAN",
												{
													"attrs": { "class": "wduf-but-text" },
													"text": this.getMessage("loadFiles")
												}
											),
											BX.create(
												"SPAN",
												{
													"attrs": { "class": "wduf-uploader-right" }
												}
											),
											BX.create(
												"INPUT",
												{
													"attrs": { "class": "wduf-fileUploader" },
													"props":
													{
														"name": this._getFileInputName(),
														"type": "file",
														"size": 1,
														"multiple": "multiple"
													}
												}
											)
										]
									}
								)
							]
					}
				)
			);
		}
		else
		{
			c.appendChild(
				BX.create(
					"DIV",
					{
						"attrs": { "class": "wduf-selector" },
						"children":
							[
								BX.create(
									"SPAN",
									{
										"text": this.getMessage("dragFile", "Drag a files to this area")
									}
								),
								BX.create("BR"),
								BX.create(
									"SPAN",
									{
										"attrs": { "class": "wduf-uploader" },
										"children":
											[
												BX.create(
													"SPAN",
													{
														"text": this.getMessage("selectFile", "or select a file in your computer"),
														"attrs": { "class": "wduf-but-text" }
													}
												),
												BX.create(
													"INPUT",
													{
														"attrs": { "class": "wduf-fileUploader" },
														"props":
														{
															"name": this._getFileInputName(),
															"type": "file",
															"size": 1,
															"multiple": "multiple"
														}
													}
												)
											]
									}
								),
								BX.create(
									"DIV",
									{
										"attrs": { "class": "wduf-load-img" }
									}
								)
							]
					}
				)
			);
		}

		c.appendChild(
			BX.create(
				"DIV",
				{
					"attrs": { "class": "wduf-label2" },
					"children":
						[
							BX.create(
								"A",
								{
									"text": this.getMessage("selectFromLib", "Select from library"),
									"attrs": { "class": "wduf-selector-link" },
									"props": { "href": "#" },
									"events": { "click": this._webDavElementSelectHandler }
								}
							)
						]
				}
			)
		);
	},
	_prepareViewLayout: function(parent)
	{
		if(!BX.type.isElementNode(parent))
		{
			return;
		}

		var c = this._container = BX.create(
			"DIV",
			{
				"attrs": { "class": "wduf-extended" },
				"style": { "display": "block" }
			}
		);

		var w = this._wrapper = BX.create(
			"DIV",
			{
				"attrs": { "class": "wduf-selectdialog" },
				"children":[ c ]
			}
		);
		parent.appendChild(w);

		c.appendChild(
			BX.create(
				"SPAN",
				{
					"text": this.getMessage("title", "Files") + ":",
					"attrs": { "class": "wduf-label" }
				}
			)
		);

		c.appendChild(
			BX.create(
				"DIV",
				{
					"attrs": { "class": "wduf-placeholder" },
					"children":
						[
							BX.create(
								"TABLE",
								{
									"attrs": { "class": "files-list", "cellspacing": "0", "cellpadding": "0", "border": "0" }
								}
							)
						]
				}
			)
		);
	},
	cleanLayout: function()
	{
		BX.unbind(
			BX.findChild(this._container, { "tag": "A", "class": "wduf-selector-link" }, true, false),
			"click",
			this._webDavElementSelectHandler
		);

		if(this._agent)
		{
			BX.removeCustomEvent(this, "ShowUploadedFile", this._showUploadedFileHandler);
			BX.removeCustomEvent(this, "StopUpload", this._stopUploadHandler);
			BX.removeCustomEvent(this, "BindLoadedFileControls", this._bindLoadedControlsHandler);
		}
		BX.removeCustomEvent(BX.WebDavFileDialog, "loadItems", this._webDavDialogSectionOpenHandler);

		if(this._wrapper)
		{
			BX.cleanNode(this._wrapper, true);
		}

		this._hasLayout = false;
	},
	isEnabled: function()
	{
		return this._enabled;
	},
	enable: function(enable)
	{
		this._enabled = enable;
	},
	_onUploaderLoad: function(html)
	{
		if (BX.type.isNotEmptyString(html)
			&& html.indexOf('errortext') >= 0)
		{
			this.enable(false);
			return;
		}

		if (BX.type.isElementNode(html)
			&& html.parentNode)
		{
			html.parentNode.removeChild(html);
		}

		document.body.appendChild(
			BX.create(
				"DIV",
				{
					"children":
						[
							BX.create("DIV", {
								"props": { "className" : "content-inner"},
								"children": [ html || '&nbsp;' ]
							})
						],
					//"style":
					//{
					//	"display": "none"
					//}
					"style": {
						'visibility':'hidden',
						'position':'absolute',
						'top':'0',
						'left':'-999px'
					}
				}
			)
		);
	},
	_onUploaderInit: function(dialog)
	{
		if (dialog.parentID != this._uploadDialogID)
		{
			return;
		}

		this._uploadDialog = dialog;
		BX.addCustomEvent(dialog, "uploadStart", this._onUploadStartHandler);

		var agent = this._agent;
		if(agent)
		{
			dialog.agent = agent;
			dialog.parentID = agent.id;
			agent.BindUploadEvents(dialog);
		}

		BX.removeCustomEvent("WDFileHiddenUploadInit", this._onUploaderInitHandler);
	},
	_onSwitchButtonClick: function(e)
	{
		this.show();
		return BX.PreventDefault(e);
	},
	_onUploadStart: function(dialog)
	{
		if (!this._uploadDialog || dialog.id != this._uploadDialog.id)
		{
			return false;
		}

		this.showLabel(true);
	},
	show: function()
	{
		this._container.style.display = "block";
		this._switchContainer.style.display = "none";
	},
	hide: function()
	{
		this._container.style.display = "none";
		this._switchContainer.style.display = "block";
	},
	GetUploadDialog: function(agent)
	{
		this._agent = agent;

		var uploadUrl = BX.CrmWebDavUploader.addSessionParam(
			BX.CrmWebDavUploader.addUrlParam(
				this.getSetting("urlUpload", ""),
				"use_hidden_view=Y&random_folder=Y&IFRAME=Y&parentID=" + this._uploadDialogID
			)
		);
		if(!BX.type.isNotEmptyString(uploadUrl))
		{
			return;
		}

		BX.addCustomEvent("WDFileHiddenUploadInit", this._onUploaderInitHandler);

		BX.ajax.get(
			uploadUrl,
			BX.delegate(this._onUploaderLoad, this)
		);
	}
};

BX.CrmWebDavUploader.items = {};
BX.CrmWebDavUploader.create = function(id, settings)
{
	if(!BX.type.isNotEmptyString(id))
	{
		id = 'BX_CRM_FILEUPLOADER_' + Math.random();
	}

	var self = new BX.CrmWebDavUploader();
	self.initialize(id, settings);
	this.items[id] = self;
	return self;
};

BX.CrmWebDavUploader.addUrlParam = function(url, params)
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

BX.CrmWebDavUploader.addSessionParam = function(url)
{
	return this.addUrlParam(url, "sessid=" + BX.bitrix_sessid());
};

