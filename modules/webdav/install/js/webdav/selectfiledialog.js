;(function(window){
if (!!window.WDFileDialog)
	return true;

window.WDFileDialog = function(arParams)
{
	this.dialogName = 'AttachFileDialog';
	this.agent = false;

	this.id = this.getID();
	this.CID = arParams['CID'];

	this.controller = (!! arParams['controller']) ? arParams['controller'] : null;
	this.fileInput = arParams.fileInput;

	this.msg = arParams['msg'];

	arParams.caller = this;
	arParams.classes = {
		'uploaderParent' : 'wduf-uploader',
		'uploader' : 'wduf-fileUploader',
		'tpl_simple' : 'wduf-simple',
		'tpl_extended' : 'wduf-extended',
		'selector' : 'wduf-selector',
		'selector_active' : 'wduf-selector-active'
	};
	arParams.doc_prefix = 'wd-doc';
	this.doc_prefix = arParams.doc_prefix;

	this.values = (!! arParams['values']) ? arParams['values'] : null;

	this.urlSelect = (!! arParams['urlSelect']) ? arParams['urlSelect'] : null;
	this.urlUpload = (!! arParams['urlUpload']) ? arParams['urlUpload'] : null;
	this.urlShow = (!! arParams['urlShow']) ? arParams['urlShow'] : null;
	this.urlGet = (!! arParams['urlGet']) ? arParams['urlGet'] : null;

	this.urlSelect = this._addUrlParam(this.urlSelect, 'dialog2=Y&ACTION=SELECT&MULTI=Y');
	this.urlUpload = this._addUrlParam(this.urlUpload, 'use_hidden_view=Y&random_folder=Y');

	this.uploadDialog = null;

	var arScripts = [];
	if (!BX.FileUploadAgent)
		arScripts.push('/bitrix/js/main/file_upload_agent.js');
	BX.loadScript(arScripts, BX.delegate(function() {
		this.agent = __wfu_FileUploadAgent_extend(new BX.FileUploadAgent(arParams));
		BX.addCustomEvent(this, 'ShowUploadedFile', BX.delegate(this.ShowUploadedFile, this));
		BX.addCustomEvent(this, 'StopUpload', BX.delegate(this.StopUpload, this));
		BX.addCustomEvent(this, 'BindLoadedFileControls', BX.delegate(this.BindLoadedFileControls, this));

		this.Init();
	}, this));
}

window.WDFileDialog.prototype.Init = function()
{
	if (this.agent.controller) {
		this.agent.fileInput = BX.findChild(this.agent.controller, {'className': 'wduf-fileUploader'}, true);
		this.agent.placeholder = BX.findChild(this.agent.controller, {'className': 'wduf-placeholder-tbody'}, true);
	} else {
		this.agent.fileInput = null;
		this.agent.placeholder = null;
	}
	BX.onCustomEvent(BX(this.controller.parentNode), "WDLoadFormControllerInit", [this]);
}

window.WDFileDialog.prototype.getID = function() {
	return ('' + new Date().getTime()).substr(6);
}

window.WDFileDialog.prototype._addUrlParam = function(url, param)
{
	if (!url)
		return null;
	if (url.indexOf(param) == -1)
		url += ((url.indexOf('?') == -1) ? '?' : '&') + param ;
	return url;
}

window.WDFileDialog.prototype.BindLoadedFileControls = function(agent, id)
{
	this.AddUploadedFileActions(id);
}

window.WDFileDialog.prototype.ShowUploadedFile = function(agent)
{
	//var uploadResult = agent.uploadResult;
/*	if(!agent.uploadResultArr){
		agent.uploadResultArr = new Array({'element_id' : agent.uploadResult.element_id, 'place' :agent.place, 'storage' : agent.uploadResult.storage});
	}*/

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
			if (agent.uploadResult.files[ii])
			{
				if (agent.uploadResult.files[ii]["content_type"])
				tparams["element_content_type"] =  agent.uploadResult.files[ii]["content_type"];
				if (agent.uploadResult.files[ii]["width"] && agent.uploadResult.files[ii]["height"])
				{
					tparams["width"] =  agent.uploadResult.files[ii]["width"];
					tparams["height"] =  agent.uploadResult.files[ii]["height"];
				}
			}
			agent.uploadResultArr.push(tparams);
		}
	}

	for (var i0 = 0;i0 < agent.uploadResultArr.length;i0++)
	{
		var uploadResult = agent.uploadResultArr[i0];
		uploadResult['storage'] = (!!uploadResult['storage'] ? uploadResult['storage'] : 'webdav');

		if (uploadResult && (uploadResult.element_id > 0)) {
			if (!!agent.inputName && agent.inputName.length > 0) {
				var hidden = BX.create('INPUT', {
					props: {
						'id': 'wduf-doc'+uploadResult.element_id,
						'type': 'hidden',
						'name': agent.inputName,
						'value': uploadResult.element_id
					}
				});
				this.controller.appendChild(hidden);
			}

			var controller = this.controller,
			__this = this;

			var urlShow = uploadResult.element_url || this.urlShow;
			urlShow = urlShow.replace("#element_id#", uploadResult.element_id).replace("#ELEMENT_ID#", uploadResult.element_id);
			urlShow = this._addUrlParam(urlShow, 'inline=Y&IFRAME=Y');
			var getFunction = function(uploadResultC, context)
			{
				return BX.delegate(
					function(html)
					{
						agentPlace = uploadResultC.place;
						if (BX.type.isElementNode(html)) {
							if (html.parentNode)
								html.parentNode.removeChild(html);
						}
						var D = BX.create('DIV', {
							props: {className: 'content-inner'},
							children: [html || '&nbsp;']
						});
						var TR = BX.findChild(D, {'className':'wd-inline-file'}, true), q=function(ddd){return function(){return ddd;}(ddd);};

						if (BX.style(agentPlace, 'display') == 'none') { // file has to be deleted
							agent.StopUpload(TR);

							if (this.controller && this.controller.parentNode)
								BX.onCustomEvent(this.controller.parentNode, 'OnFileUploadFail');
						} else {
							if (!! TR) {
								var rows = BX.findChildren(agent.placeholder, {'tagName':'TR'}, true);
								if (!!rows) {
									for (var i=0;i<rows.length;i++) {
										if (rows[i] == agentPlace) {
											var newRow = agent.placeholder.insertRow(i);
											newRow.className = TR.className;
											newRow.id = TR.id;
											var cells = BX.findChildren(TR, {'tagName':'TD'}, true);
											if (!!cells) {
												for (var j=0;j<cells.length;j++) {
													var newCell = newRow.insertCell(-1);
													newCell.className = cells[j].className;
													newCell.innerHTML = cells[j].innerHTML;
												}
											}
											BX.cleanNode(agentPlace, true);

											agent._clearPlace();
											agent._mkClose(q(newRow));
											if (controller && controller.parentNode){
												BX.onCustomEvent(controller.parentNode, 'OnFileUploadSuccess', [uploadResultC, __this]);
											}
											setTimeout(BX.delegate(agent.ShowAttachedFiles, agent), 200);
											break;
										}
									}
								}
							} else { // show some error
								var msg = BX.findChild(D, {'className':'errortext'}, true);
								if (!!msg) {
									uploadResult.messages = msg.innerHTML;
									agent.ShowUploadError();
								}
							}
							__this.AddUploadedFileActions(uploadResultC.element_id);
							agent._clearPlace();
						}
					},
					context
				);
			}

			BX.ajax.get(urlShow, getFunction(uploadResult,this));

		} else {
			if (this.controller && this.controller.parentNode)
				BX.onCustomEvent(this.controller.parentNode, 'OnFileUploadFail');
		}
	}
}

window.WDFileDialog.prototype.ShowMovedFile = function(element_id, arProp, section_path)
{
	if (isNaN(parseInt(element_id)) || (element_id <= 0))
		return false;
	var MAX_PATH_LENGTH = 65; // chars

	var id = element_id;
	var parent = BX('wd-doc'+id);
	var filemove = BX.findChild(parent, {'className' : 'files-path'}, true);

	BX.cleanNode(filemove);
	var pathLength = 0;
	section_path = section_path.split('/').join(' / ');
	pathLength = section_path.length+1;

	filemove.innerHTML = section_path;
	var w = parseInt(filemove.offsetWidth);
	var l = parseInt(filemove.parentNode.offsetWidth)-150;
	MAX_PATH_LENGTH = l / (w / section_path.length) ;
	if (w > l) {
		midName = Math.floor(MAX_PATH_LENGTH / 2) + 1;
		section_path = section_path.substr(0, midName) + ' ... ' + section_path.substr(section_path.length - midName);
		filemove.innerHTML = section_path;
	}

	var fileInput = BX('wduf-doc'+id);
	if (!!fileInput) {
		var arFile = this._fileUnserialize(fileInput.value);
		arFile.section = arProp['sectionID'];
		arFile.iblock = arProp['iblockID'];
		fileInput.value = this._fileSerialize(arFile);
	}
}

window.WDFileDialog.prototype._fileUnserialize = function(index)
{
	if (!BX.type.isString(index))
		return false;

	var arIndex = index.split('|');
	arFile = {
		'id' : (arIndex[0] || 0),
		'section' : (arIndex[1] || 0),
		'iblock' : (arIndex[2] || 0)
	};

	return arFile;
}

window.WDFileDialog.prototype._fileSerialize = function(arFile)
{
	var arIndex = [ arFile.id, arFile.section, arFile.iblock ];
	return arIndex.join('|');
}

window.WDFileDialog.prototype.AddUploadedFileActions = function(element_id)
{
	if (isNaN(parseInt(element_id)) || (element_id <= 0))
		return false;

	var id = element_id;
	var parent = BX('wd-doc'+id);
	var filemove = BX.findChild(parent, {'className' : 'files-path'}, true);
	var attrName = 'file-action-control';
	var filename = BX.findChild(parent, {'className' : 'f-wrap'}, true);

	if (!!filemove && !!filename && !filemove.getAttribute(attrName)) {
		filemove.setAttribute(attrName, 'enabled');
		BX.bind(filemove, 'click', BX.delegate(function(ev) {
			ev = ev || window.event;
			target = ev.target || ev.srcElement;
			this.place = parent;
			this.MoveUploadedFile(id, filename.innerHTML);
		}, this));
	}
	var filepreview = BX.findChild(parent, {'className' : 'files-preview-wrap', 'tagName' : 'SPAN'}, true);
	if (!!filepreview) {
		BX.bind(filepreview, "mouseover", BX.delegate(function(){new __wfu_preview(this);}, filepreview));
	}
}

window.WDFileDialog.prototype.WDFD_SelectFolder = function(tab, path, selected, folderByPath)
{
	var id = false;
	if ((BX.WebDavFileDialog.arParams) &&
		(BX.WebDavFileDialog.arParams[this.dialogName]) &&
		(BX.WebDavFileDialog.arParams[this.dialogName]['element_id']))
			id = BX.WebDavFileDialog.arParams[this.dialogName]['element_id'];

	var moved = false;
	for (i in selected) {
		if (i.substr(0,1) == 'S') {
			var secPath = tab.name+selected[i].path;
			var arProp = {
				'sectionID' : i.substr(1),
				'iblockID' : tab.iblock_id
			};
			this.ShowMovedFile(id, arProp, secPath);
			moved = true;
		}
	}
	if (!moved) {
		var secPath = tab.name;
		var arProp = {
			'sectionID' : tab.section_id,
			'iblockID' : tab.iblock_id
		};
		if (!!folderByPath && !!folderByPath.path && folderByPath.path != '/') {
			secPath += folderByPath.path;
			arProp.sectionID = folderByPath.id.substr(1);
		}
		this.ShowMovedFile(id, arProp, secPath);
	}
	BX.removeCustomEvent(BX.WebDavFileDialog, 'loadItems', this.h_WDFD_OpenSection);
	BX.removeCustomEvent(BX.WebDavFileDialog, 'loadItemsDone', this.h_WDFD_CheckFileName);
	BX.removeCustomEvent(BX.WebDavFileDialog, 'selectItem', this.h_WDFD_selectItem);
	BX.removeCustomEvent(BX.WebDavFileDialog, 'unSelectItem', this.h_WDFD_unSelectItem);
}

window.WDFileDialog.prototype.WDFD_CheckFileName = function(name)
{
	if (this.noticeTimeout) {
		clearTimeout(this.noticeTimeout);
		this.noticeTimeout = null;
	}
	if (!!name && (name != this.dialogName))
		return;

	var fname = BX.WebDavFileDialog.arParams[this.dialogName]['element_name'];
	var exist = false;
	for (i in BX.WebDavFileDialog.obItems[this.dialogName]) {
		if (BX.WebDavFileDialog.obItems[this.dialogName][i]['name'] == fname)
			exist = true;
		if (exist)
			break;
	}

	if (exist)
		BX.WebDavFileDialog.showNotice(this.msg.file_exists, this.dialogName);
	else
		BX.WebDavFileDialog.closeNotice(this.dialogName);
}

window.WDFileDialog.prototype.WDFD_selectItem = function(element, itemID, name)
{
	if (name != this.dialogName)
		return;

	var targetID = itemID.substr(1);
	var libLink = BX.WebDavFileDialog.obCurrentTab[name].link;
	libLink = libLink.replace("/index.php","") + '/element/upload/' + targetID + 
		'/?use_light_view=Y&AJAX_CALL=Y&SIMPLE_UPLOAD=Y&IFRAME=Y&sessid='+BX.bitrix_sessid()+
		'&SECTION_ID='+targetID+
		'&CHECK_NAME='+BX.WebDavFileDialog.arParams[this.dialogName]['element_name'];
	libLink = libLink.replace('/files/lib/', '/files/');

	BX.ajax.loadJSON(libLink, BX.delegate(function(result) {
		var documentExists = (result.permission == true && result.okmsg != "");

		if (this.noticeTimeout) {
			clearTimeout(this.noticeTimeout);
			this.noticeTimeout = null;
		}
			
		this.noticeTimeout = setTimeout(
			BX.delegate(
				function() {
					if (documentExists) {
						BX.WebDavFileDialog.showNotice(this.msg.file_exists, this.dialogName);
					} else {
						BX.WebDavFileDialog.closeNotice(this.dialogName);
					}
				},
				this),
			200);
	}, this));
}

window.WDFileDialog.prototype.WDFD_unSelectItem = function(name)
{
	if (this.noticeTimeout) {
		clearTimeout(this.noticeTimeout);
		this.noticeTimeout = null;
	}
	this.noticeTimeout = setTimeout(
		BX.delegate(
			function() {
				this.WDFD_CheckFileName();
			},
			this),
		200);
}

window.WDFileDialog.prototype.WDFD_OpenSection = function(link, name)
{
	if (name == this.dialogName) {
		BX.WebDavFileDialog.target[name] = this._addUrlParam(link, 'dialog2=Y');
	}
}

window.WDFileDialog.prototype.WDFD_SelectFile = function(tab, path, selected)
{
	for (i in selected) {
		if ((i.substr(0,1) == 'E') && (!BX('wduf-doc'+i.substr(1)))) {
			var fileID = i.substr(1);
			this.agent._mkPlace(selected[i].name, fileID);
			var ar = {'element_id':fileID, 'element_url':selected[i].link, 'element_name' : selected[i].name};
			this.agent.values.push(ar);
		}
	}
	if (this.agent.values.length > 0) {
		BX.onCustomEvent(this.controller.parentNode, 'OnFileFromDialogSelected', [this.agent.values, this]);
		this.agent.ShowAttachedFiles();
	}
	BX.removeCustomEvent(BX.WebDavFileDialog, 'loadItems', this.h_WDFD_OpenSection);
}

window.WDFileDialog.prototype.MoveUploadedFile = function(element_id, name)
{
	var urlMove = this._addUrlParam(this.urlSelect, 'ID=E'+element_id+'&NAME='+name);
	urlMove = urlMove.replace("ACTION=SELECT", "").replace("MULTI=Y","");
	urlMove = this._addUrlParam(urlMove, 'ACTION=FAKEMOVE&IFRAME=Y');

	this.h_WDFD_CheckFileName = BX.proxy(this.WDFD_CheckFileName, this);
	this.h_WDFD_SelectFolder = BX.proxy(this.WDFD_SelectFolder, this);
	this.h_WDFD_OpenSection = BX.proxy(this.WDFD_OpenSection, this);
	this.h_WDFD_selectItem = BX.proxy(this.WDFD_selectItem, this);
	this.h_WDFD_unSelectItem = BX.proxy(this.WDFD_unSelectItem, this);
	BX.WebDavFileDialog.arParams = {};
	BX.WebDavFileDialog.arParams[this.dialogName] = {};
	BX.WebDavFileDialog.arParams[this.dialogName]['element_id'] = element_id;
	
	var nodeFileTR = BX('wd-doc'+element_id);
	var nodeFileName = BX.findChild(nodeFileTR, {'className':'f-wrap'}, true);
	BX.WebDavFileDialog.arParams[this.dialogName]['element_name'] = nodeFileName.innerHTML;

	BX.addCustomEvent(BX.WebDavFileDialog, 'loadItems', this.h_WDFD_OpenSection);
	BX.addCustomEvent(BX.WebDavFileDialog, 'loadItemsDone', this.h_WDFD_CheckFileName);
	BX.addCustomEvent(BX.WebDavFileDialog, 'selectItem', this.h_WDFD_selectItem);
	BX.addCustomEvent(BX.WebDavFileDialog, 'unSelectItem', this.h_WDFD_unSelectItem);
	BX.ajax.get(urlMove, 'dialogName='+this.dialogName,
		BX.delegate(function(result) {
			setTimeout(BX.delegate(function() {
				BX.WebDavFileDialog.obCallback[this.dialogName] = {'saveButton' : this.h_WDFD_SelectFolder};
				BX.WebDavFileDialog.openDialog(this.dialogName);
				this.WDFD_CheckFileName();
			}, this), 100);
		}, this)
	);
}

window.WDFileDialog.prototype.StopUpload = function(agent, p)
{
	var parent = p;
	var editLink = BX.findChild(parent, {'className' : 'file-edit'}, true);

	var fileName = false;
	var fileNameNode = BX.findChild(parent, {'className' : 'f-wrap'}, true);
	if (!!fileNameNode)
		fileName = fileNameNode.innerHTML;
	if (!!editLink) {
		var href = editLink.href;
		href = href.replace('EDIT','DELETE_DROPPED');
		href += ((href.indexOf('?') > 0)?'&':'?') + "&sessid="+BX.bitrix_sessid()+'&EDIT=Y&AJAX_MODE=Y&IFRAME=Y';
		if (!!fileName && (fileName in window.wduf_places))
			window.wduf_places[fileName] = null;
		BX.hide(parent);
		BX.ajax.get(href);
	} else {
		BX.hide(parent);
	}

	sID = p.id;
	mID = sID.match(/wd-doc(\d+)/);
	if (!!mID) {
		id = mID[1];
		var fileInput = BX('wduf-doc'+id);
		if (!!fileInput)
			BX.remove(fileInput);
		BX.onCustomEvent(this.controller.parentNode, 'OnFileUploadRemove', [id, this]);
	}
}

window.WDFileDialog.prototype.UploadDialogEvents = function(dialog)
{
	if (dialog.parentID != this.id)
		return;

	this.uploadDialog = dialog;
	dialog.agent = this.agent;
	dialog.parentID = this.agent.id;

	this.agent.BindUploadEvents(dialog);

	BX.removeCustomEvent('WDFileHiddenUploadInit', this.hUploadDialogEvents);
}

window.WDFileDialog.prototype.ShowSelectDialog = function()
{
	this.h_WDFD_OpenSection = BX.proxy(this.WDFD_OpenSection, this);
	this.h_WDFD_SelectFile = BX.proxy(this.WDFD_SelectFile, this);
	BX.addCustomEvent(BX.WebDavFileDialog, 'loadItems', this.h_WDFD_OpenSection);
	BX.ajax.get(this.urlSelect, 'dialogName='+this.dialogName,
		BX.delegate(function(result) {
			setTimeout(BX.delegate(function() {
				BX.WebDavFileDialog.obCallback[this.dialogName] = {'saveButton' : this.h_WDFD_SelectFile};
				BX.WebDavFileDialog.openDialog(this.dialogName);
			}, this), 10);
		}, this)
	);
}

window.WDFileDialog.prototype.Disable = function()
{
	BX.cleanNode(this.controller);
	this.controller.innerHTML = this.msg.access_denied;
}

window.WDFileDialog.prototype.GetUploadDialog = function(agent)
{
	this.agent = agent;
	this.LoadUploadDialog();
}

window.WDFileDialog.prototype.LoadUploadDialog = function()
{
	this.hUploadDialogEvents = BX.proxy(this.UploadDialogEvents, this);
	BX.addCustomEvent('WDFileHiddenUploadInit', this.hUploadDialogEvents);

	if (this.urlUpload) {
		var D = BX.create('DIV');
		this.DIV = D;
		var url = this.urlUpload+'&IFRAME=Y&parentID='+this.id;
		BX.ajax.get(url, BX.delegate( function(html) {
			if (html.indexOf('errortext') >= 0)
			{
				this.Disable();
				return false;
			}

			if (BX.type.isElementNode(html)) {
				if (html.parentNode)
					html.parentNode.removeChild(html);
			}

			BX.adjust(D, {
				children: [
					BX.create('DIV', {
						props: {className: 'content-inner'},
						children: [html || '&nbsp;']
					})
				],
				style: {
					'visibility':'hidden',
					'position':'absolute',
					'top':'0',
					'left':'-999px'
				}
			});

			document.body.appendChild(D);
		}, this));
	}
}

window.WDFileDialog.prototype.LoadSelectDialog = function()
{
	var controller = BX.findChild(this.controller, { 'className': 'wduf-selector-link'}, true);
	if (!controller)
		return false;

	this.hShowSelectDialog = BX.proxy(this.ShowSelectDialog, this);
	BX.bind(controller, 'click', this.hShowSelectDialog);
}

window.WDFileDialog.prototype.LoadDialogs = function(dialogs)
{
	if (!!this.agent)
	{
		this.LoadSelectDialog();
		this.agent.LoadDialogs(dialogs);
	}
	else
	{
		var dlgs = dialogs;
		setTimeout(BX.delegate(function() {this.LoadDialogs(dlgs);}, this), 100);
	}
}


/*************************************************
                    DISPATCHER
**************************************************/
window.WDFileDialogDispatcher = function(controller, id)
{
	this.id = BX.type.isNotEmptyString(id) ? id : 'WD_FILE_DIALOG_DISPATCHER_' + Math.random();
	this.controller = controller;

	WDFileDialogDispatcher.items[id] = this;

	BX.loadScript((!!BX.DD ? [] : ['/bitrix/js/main/core/core_dd.js']), BX.delegate(function() {
		if (BX.type.isElementNode(this.controller) && this.controller.parentNode && this.controller.parentNode.parentNode)
		{
			var target = controller.parentNode.parentNode;
			this.dropbox = new BX.DD.dropFiles(target);
			if (this.dropbox && this.dropbox.supported() && BX.ajax.FormData.isSupported()) {
				this.hExpandUploader = BX.proxy(this.ExpandUploader, this);
				BX.addCustomEvent(this.dropbox, 'dragEnter', this.hExpandUploader);
				BX.addCustomEvent(target, "UnbindDndDispatcher", BX.delegate(this.Unbind, this));
			}
		}
	}, this));
}

window.WDFileDialogDispatcher.prototype.ExpandUploader = function()
{
	BX.onCustomEvent(BX(this.controller.parentNode), "WDLoadFormController", ['show']);
	//BX.removeCustomEvent(this.dropbox, 'dragEnter', this.hExpandUploader);
}

window.WDFileDialogDispatcher.prototype.Unbind = function()
{
	BX.removeCustomEvent(this.dropbox, 'dragEnter', this.hExpandUploader);
}

window.WDFileDialogDispatcher.items = {};

window.__wfu_preview = function(node)
{
	if (!node)
		return false;
	BX.unbindAll(node);
	this.img = BX.findChild(node, {'className' : 'files-preview', 'tagName' : 'IMG'}, true);;
	if (!this.img)
		return false;
	this.node = node;
	this.id = 'wufdp_' + Math.random(1000);
	BX.bind(node, "mouseover", BX.delegate(function(){this.turnOn(0);}, this));
	BX.bind(node, "mouseout", BX.delegate(function(){this.turnOff(0);}, this));
	this.turnOn(0);
}
window.__wfu_preview.prototype = {
	turnOn : function()
	{
		this.timeout = setTimeout(BX.delegate(function(){this.show();}, this), 500);
	},
	turnOff : function()
	{
		clearTimeout(this.timeout);
		this.timeout = null;
		this.hide();
	},
	show : function()
	{
		if (this.popup != null)
			this.popup.close();
		if (this.popup == null)
		{
			this.popup = new BX.PopupWindow('bx-wufd-preview-img-' + this.id, this.img,
				{
					lightShadow : true,
					offsetTop: -7,
					offsetLeft: 7,
					autoHide: true,
					closeByEsc: true,
					bindOptions: {position: "top"},
					events : {
						onPopupClose : function() { this.destroy() },
						onPopupDestroy : BX.proxy(function() { this.popup = null; }, this)
					},
					content : BX.create(
						"DIV",
						{
							attrs: {
								width : this.img.getAttribute("data-bx-width"),
								height : this.img.getAttribute("data-bx-height")
							},
							children : [
								BX.create(
									"IMG",
									{
										attrs: {
											width : this.img.getAttribute("data-bx-width"),
											height : this.img.getAttribute("data-bx-height"),
											src: this.img.src
										}
									}
								)
							]
						}
					)
				}
			);
			this.popup.show();
		}
		this.popup.setAngle({position:'bottom'});
		this.popup.bindOptions.forceBindPosition = true;
		this.popup.adjustPosition();
		this.popup.bindOptions.forceBindPosition = false;
	},
	hide : function()
	{
		if (this.popup != null)
			this.popup.close();
	}
}

window.__wfu_FileUploadAgent_extend = function(obj)
{
	obj._mkClose = function(parent)
	{
		if (!parent)
			return false;
		var target = null;

		var closeBtn = BX.create('SPAN', {
				'props' : {
					'className' : 'del-but'
				}
			}
		);
		var divLoading = BX.findChild(parent, {'className':'loading'}, true);
		var divLoaded = BX.findChild(parent, {'className':'files-storage-block'}, true);
		var p = parent;
		if (!!divLoading)
		{
			var p = parent;
			BX.bind(closeBtn, 'click', BX.delegate(function() {this.StopUpload(p); }, this));
			divLoading.appendChild(closeBtn);
		}
		else if (!!divLoaded)
		{
			if (!BX.findChild(parent, {'className':'files-info'}, true))
			{
				parent.appendChild(
					BX.create('TD',
						{
							'props' : {
								'className' : 'files-info'
							}
						}
					)
				);
			}
			if (!BX.findChild(parent, {'className':'files-del-btn'}, true))
			{
				BX.bind(closeBtn, 'click', BX.delegate(function() {this.StopUpload(p); }, this));
				parent.appendChild(
					BX.create('TD',
						{
							'props' : {
								'className' : 'files-del-btn'
							},
							'children' : [
								closeBtn
							]
						}
					)
				);
			}
			else
			{
				closeBtn = BX.findChild(parent, {'className':'del-but'}, true);
				BX.bind(closeBtn, 'click', BX.delegate(function() {this.StopUpload(p); }, this));
			}
		}
		BX.onCustomEvent(parent, 'OnMkClose', [parent]);
	}

	obj._mkPlace = function(name, cacheID)
	{
		if (!cacheID)
			cacheID = name;

		if ((cacheID in window.wduf_places) && !!window.wduf_places[cacheID]) {
			this.place = window.wduf_places[cacheID];
			this.progress = BX.findChild(this.place, {'className' : 'load-indicator'}, true);
		} else {
			this.progress = BX.create('SPAN', {
				'props' : {
					'className' : 'load-indicator'
				},
				'style' : {
					'width' : '5%'
				},
				'children' : [
					BX.create('SPAN', {
							'props' : {
								'className' : 'load-number'
							},
							'text' : '5%'
						}
					)
				]
			});

			var progressHolder = BX.create('SPAN', {
				'props' : {
					'className' : 'loading-wrap'
				},
				'children' : [
					BX.create('SPAN', {
							'props' : {
								'className' : 'loading'
							}
						}
					),
					this.progress
				]
			});

			this.place = BX.create('TR', {
				'children' : [
					BX.create('TD', {
							'props' : {
								'className' : 'files-name'
							},
							'children' : [
								BX.create('SPAN', {
									'props' : {
										'className' : 'files-text'
									},
									'children' : [
										BX.create('SPAN', {
												'props' : {
													'className' : 'f-wrap'
												},
												'text': name
											}
										),
										BX.create('SPAN', {
											'props' : {
												'className' : 'wd-files-icon feed-file-icon-' +
													(name.indexOf(".") > 0 ? name.substr((name.lastIndexOf(".")+1)) : "")
												}
											}
										)
									]
								})
							]
						}
					),
					BX.create('TD', {
							'props' : {
								'className' : 'files-storage'
							},
							'attrs' : {
								'colspan' : '4'
							},
							'children' : [
								BX.create('SPAN', {
										'text': this.msg['loading']+':'
									}
								),
								progressHolder
							]
						}
					)
				]
			});
			this._mkClose(this.place);
			BX.show(BX.findParent(this.placeholder, {className:'wduf-files-block'}));
			this.placeholder.appendChild(this.place);
			window.wduf_places[cacheID] = this.place;
		}
	}
	obj.ShowUploadError = function(messages)
	{
		if (!!messages)
		{
			if (BX.type.isArray(messages))
				messages = messages.join("\n");
			messages = messages.replace("<br>","");

			BX.remove(this.progress.parentNode); // .progressHolder

			if (!! messages) {
				BX.addClass(this.place, 'error-load');
				while(this.place.cells.length > 1)
					this.place.deleteCell(1); // size
				var newCell = this.place.insertCell(-1);
				newCell.setAttribute("colspan", 4);
				newCell.appendChild(BX.create('SPAN', {props: {className: 'info-icon'}}));
				newCell.appendChild(BX.create('SPAN', {props: {className: 'error-text'}, text: messages}));
				this._mkClose(this.place);
			}
		}
	}
	obj.GetNewObject = function(parent)
	{
		return window.__wfu_FileUploadAgent_extend(new BX.FileUploadAgent((!!parent ? parent : this)));
	}
	return obj;
}
})(window);