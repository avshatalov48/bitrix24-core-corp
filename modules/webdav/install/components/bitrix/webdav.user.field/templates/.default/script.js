;(function(window){
if (!!window["WDCreateDocument"])
	return;
var wdufPopup = false;
var wdufCurrentIdDocument = false;
var wdufCurrentFileDialog = false;
var wdufMenuNumber = 0;
/**
 * @return {boolean}
 */
window.WDCreateDocument = function (documentType)
{
	if(!wdufCurrentFileDialog)
	{
		return false;
	}
	else if(!documentType)
	{
		return false;
	}
	var obElementViewer = new BX.CViewer({
		createDoc: true
	});
	var blankDocument = obElementViewer.createBlankElementByParams({
		docType: documentType
	});
	obElementViewer.setCurrent(blankDocument);
	blankDocument.afterSuccessCreate = function(response){
		var data = {};
		data['E' + response.elementId] = {name: response.name, link: response.link, sizeInt: response.sizeInt, type: response.type};
		wdufCurrentFileDialog.WDFD_SelectFile({}, {}, data);
	};

	obElementViewer.runActionByCurrentElement('create', {obElementViewer: obElementViewer});

	try{BX.PreventDefault()}catch(e){}
	return false;
};
window.WDOpenMenuCreateService = function (targetElement)
{
	var obElementViewer = new BX.CViewer({});
	obElementViewer.openMenu('bx-viewer-popup-create', BX(targetElement), [
		{text: obElementViewer.getNameEditService('google'), className: "bx-viewer-popup-item item-gdocs", href: "#", onclick: BX.delegate(function(e){
			this.setEditService('google');
			BX.adjust(targetElement, {text: this.getNameEditService('google')});
			this.closeMenu();

			return BX.PreventDefault(e);}, obElementViewer)},
		{text: obElementViewer.getNameEditService('skydrive'), className: "bx-viewer-popup-item item-office", href: "#", onclick: BX.delegate(function(e){
			this.setEditService('skydrive');
			BX.adjust(targetElement, {text: this.getNameEditService('skydrive')});
			this.closeMenu();

			return BX.PreventDefault(e);}, obElementViewer)}
	], {
		offsetTop: 0,
		offsetLeft: 25
	});
};

/**
 * @return {boolean}
 */
window.WDActionFileMenu = function(id, bindElement, buttons)
{
	wdufMenuNumber++;
	BX.PopupMenu.show('bx-viewer-wd-popup' + wdufMenuNumber + '_' + id, BX(bindElement), buttons,
		{
			angle: {
				position: 'top',
				offset: 25
			},
			autoHide: true
		}
	);
	return false;
};
/**
 * Forward click event from inline element to main element (with additional properties)
 * @param element
 * @param realElementId main element (in attached block)
 * @returns {boolean}
 * @constructor
 */
window.WDInlineElementClickDispatcher = function(element, realElementId)
{
	var realElement = BX(realElementId);
	if(realElement)
	{
		BX.fireEvent(realElement, 'click');
	}
	return false;
};

window.showWebdavHistoryPopup = function(historyUrl, docId, bindElement)
{
	bindElement = bindElement || null;
	if(wdufPopup)
	{
		wdufPopup.show();
		return;
	}
	if(wdufCurrentIdDocument == docId)
	{
		return;
	}
	wdufCurrentIdDocument = docId;
	wdufPopup = new BX.PopupWindow(
		'bx_webdav_history_popup',
		bindElement,
		{
			closeIcon : true,
			offsetTop: 5,
			autoHide: true,
			zIndex : -100,
			content:
				BX.create('div', {
					children: [
				BX.create('div', {
					style: {
						display: 'table',
						width: '665px',
						height: '225px'
					},
					children: [
						BX.create('div', {
							style: {
								display: 'table-cell',
								verticalAlign: 'middle',
								textAlign: 'center'
							},
							children: [
								BX.create('div', {
									props: {
										className: 'bx-viewer-wrap-loading-modal'
									}
								}),
								BX.create('span', {
									text: ''
								})
							]
						})
					]
				}
			)
					]
				}),
			closeByEsc: true,
			draggable: true,
			titleBar: BX.message('WDUF_FILE_TITLE_REV_HISTORY'),
			events : {
				'onPopupClose': function()
				{
					wdufPopup.destroy();
					wdufPopup = wdufCurrentIdDocument = false;
				}
			}
		}
	);
	wdufPopup.show();
	BX.ajax.get(historyUrl, function(data)
	{
		wdufPopup.setContent(BX.create('DIV', {html: data}));
	});
};

var __wfu_preview = function(node)
{
	if (!node)
		return false;
	BX.unbindAll(node);
	this.img = BX.findChild(node, {'className' : 'files-preview', 'tagName' : 'IMG'}, true);
	if (!this.img)
		return false;
	this.node = node;
	this.id = 'wufdp_' + Math.floor((Math.random() * 1000) + 1);
	BX.bind(node, "mouseover", BX.delegate(function(){this.turnOn(0);}, this));
	BX.bind(node, "mouseout", BX.delegate(function(){this.turnOff(0);}, this));
	this.turnOn(0);
	return true;
};
__wfu_preview.prototype = {
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
};

BX.WebdavUpload = function(params)
{
	this.dialogName = "AttachFileDialog";
	BX.addCustomEvent(window, "onUploaderIsAlmostInited", BX.proxy(this.onUploaderIsAlmostInited, this));
	this.params = params;
	this.CID = params['UID'];
	this.controller = params.controller;

	this.values = (params.values || []);
	this.agent = BX.Uploader.getInstance({
		id : params["UID"],
		streams : 3,
		allowUpload : "A",
		uploadFormData : "N",
		uploadMethod : "immediate",
		uploadFileUrl : this._addUrlParam(params["urlUpload"], 'use_hidden_view=Y&random_folder=Y'),
		deleteFileOnServer : false,
		showImage : false,
		input : params["input"],
		dropZone : params["dropZone"],
		placeHolder : params["placeHolder"],
		queueFields : {
			thumb : {
				tagName : "TR",
				className : "bxu-item"
			}
		},
		fields : {
			thumb : {
				tagName : "",
				template : BX.message('WD_TMPLT_THUMB')
			}
		}
	});
	this.agent.ShowAttachedFiles = BX.proxy(function() { // backward compatibility
		var val = null, data = {}, i;
		if (! this.agent.values || typeof this.agent.values != "object")
			return;
		for (i in this.agent.values)
		{
			if (this.agent.values.hasOwnProperty(i))
			{
				val = this.agent.values[i];
				data['E' + val.element_id] = { name: val.element_name, link: val.element_url, sizeInt: 0, type: val.element_content_type }
			}
		}
		this.WDFD_SelectFile({}, {}, data);

	}, this);
	this.urlSelect = (!! params['urlSelect']) ? params['urlSelect'] : null;
	this.urlUpload = (!! params['urlUpload']) ? params['urlUpload'] : null;
	this.urlShow = (!! params['urlShow']) ? params['urlShow'] : null;
	this.urlGet = (!! params['urlGet']) ? params['urlGet'] : null;
	this.params.controlName = (this.params.controlName || 'FILES[]');

	this.urlSelect = this._addUrlParam(this.urlSelect, 'dialog2=Y&ACTION=SELECT&MULTI=Y');
	this.urlUpload = this._addUrlParam(this.urlUpload, 'use_hidden_view=Y&random_folder=Y');

	this.init();
	return this;
};
BX.WebdavUpload.prototype = {
	_addUrlParam : function(url, param)
	{
		if (!url)
			return null;
		if (url.indexOf(param) == -1)
			url += ((url.indexOf('?') == -1) ? '?' : '&') + param ;
		return url;
	},
	onUploaderIsAlmostInited : function(objName, params)
	{

		var s = BX.findChild(this.controller, {className : "wduf-simple"}, true),
			e = BX.findChild(this.controller, {className : "wduf-extended"}, true);
		if (objName == "BX.UploaderSimple")
		{
			BX.remove(e);
			BX.show(s);
		}
		else
		{
			BX.remove(s);
			BX.show(e);
		}
		params.input = BX.findChild(this.controller, { className : 'wduf-fileUploader' }, true);
		params.dropZone = BX.findChild(this.controller, { className : 'wduf-selector' }, true);
		this.params.placeHolder = params["placeHolder"] = BX.findChild(this.controller, {'className': 'wduf-placeholder-tbody'}, true);
	},
	init : function()
	{
		this._onItemIsAdded = BX.delegate(this.onItemIsAdded, this);
		this._onFileIsAppended = BX.delegate(this.onFileIsAppended, this);
		this._onFileIsAttached = BX.delegate(this.onFileIsAttached, this);
		this._onFileIsBound = BX.delegate(this.onFileIsBound, this);
		this._onFileIsInited = BX.delegate(this.onFileIsInited, this);
		this._onError = BX.delegate(this.onError, this);

		BX.addCustomEvent(this.agent, "onItemIsAdded", this._onItemIsAdded);
		BX.addCustomEvent(this.agent, "onFileIsInited", this._onFileIsInited);
		BX.addCustomEvent(this.agent, "onError", this._onError);

		this._onUploadProgress = BX.delegate(this.onUploadProgress, this);
		this._onUploadDone = BX.delegate(this.onUploadDone, this);
		this._onUploadError = BX.delegate(this.onUploadError, this);
		this._onUploadRestore = BX.delegate(this.onUploadRestore, this);

		BX.onCustomEvent(BX(this.controller.parentNode), "WDLoadFormControllerInit", [this]);

		var ar1 = [], ar2 = [], name;
		for (var ii = 0; ii < this.values.length; ii++)
		{
			name = BX.findChild(this.values[ii], {'className' : 'f-wrap'}, true);
			if (!!name)
			{
				ar1.push({ name : name.innerHTML});
				ar2.push(this.values[ii]);
			}
		}
		this.values = [];
		this.agent.onAttach(ar1, ar2);

		var controller = BX.findChild(this.controller, { 'className': 'wduf-selector-link'}, true);
		if (!!controller)
		{
			this.hShowSelectDialog = BX.proxy(this.ShowSelectDialog, this);
			BX.bind(controller, 'click', this.hShowSelectDialog);
		}

		return false;
	},
	onItemIsAdded : function()
	{
		BX.removeCustomEvent(this.agent, "onItemIsAdded", this._onItemIsAdded);
		BX.show(BX.findParent(this.params.placeHolder, {className:'wduf-files-block'}));
	},
	onFileIsInited : function(id, file)
	{
		BX.addCustomEvent(file, 'onFileIsAttached', this._onFileIsAttached);
		BX.addCustomEvent(file, 'onFileIsAppended', this._onFileIsAppended);
		BX.addCustomEvent(file, 'onFileIsBound', this._onFileIsBound);
		BX.addCustomEvent(file, 'onUploadProgress', this._onUploadProgress);
		BX.addCustomEvent(file, 'onUploadDone', this._onUploadDone);
		BX.addCustomEvent(file, 'onUploadError', this._onUploadError);
	},
	onFileIs : function(TR, item, file)
	{
		this.bindEventsHandlers(TR, item, file);
		var res = {
			element_id : file.element_id,
			element_name : (file.element_name || item.name),
			place : TR,
			storage : 'webdav'
		};
		this.values.push(TR);
		BX.onCustomEvent(this.params.controller.parentNode, 'OnFileUploadSuccess', [res, this]);
		BX.onCustomEvent(item, 'OnFileUploadSuccess', [res, this]);
	},
	onFileIsAppended : function(id, item)
	{
		var node = this.agent.getItem(id), TR = node.node;
		this.bindEventsHandlers(TR, item, {});
	},
	onFileIsBound : function(id, item)
	{
		var node = this.agent.getItem(id), TR = node.node, element_id = TR.getAttribute("id").replace("wd-doc", "");
		this.onFileIs(TR, item, { element_id : element_id });
	},
	onFileIsAttached : function(id, item, agent, being)
	{
		this.onUploadDone(item, { file : being } );
	},
	onUploadProgress : function(item, progress)
	{
		var id = item.id;
		if (!item.__progressBarWidth)
			item.__progressBarWidth = 5;
		if (progress > item.__progressBarWidth)
		{
			item.__progressBarWidth = Math.ceil(progress);
			item.__progressBarWidth = (item.__progressBarWidth > 100 ? 100 : item.__progressBarWidth);
			if (BX('wdu' + id + 'Progressbar'))
				BX.adjust(BX('wdu' + id + 'Progressbar'), { style : { width : item.__progressBarWidth + '%' } } );
			if (BX('wdu' + id + 'ProgressbarText'))
				BX.adjust(BX('wdu' + id + 'ProgressbarText'), { text : item.__progressBarWidth + '%' } );
		}
	},
	onUploadDone : function(item, result)
	{
		var node = this.agent.getItem(item.id).node, file = result["file"],
			urlShow = this._addUrlParam((file.url_show || this.params.url_show || '').replace("#element_id#", file.element_id).replace("#ELEMENT_ID#", file.element_id), 'size=' + this.params.THUMB_SIZE + '&inline=Y&IFRAME=Y');
		this.controller.appendChild(BX.create('INPUT',
			{
				props: {
					'id': 'wduf-doc' + file.element_id,
					'type': 'hidden',
					'name': this.params.controlName,
					'value': file.element_id
				}
			}
		));
		if (!!node)
		{
			BX.ajax.get(urlShow, BX.proxy(function(html)
			{
				if (!BX(node) || node.style.display == 'none')
					return false;
				else if (BX.type.isElementNode(html))
					BX.remove(html);

				var D = BX.create('DIV', { props: { className: 'content-inner'}, children: [html || '&nbsp;'] }),
					TR = BX.findChild(D, { className : 'wd-inline-file'}, true);
				if (TR)
				{
					node.parentNode.replaceChild(TR, node);
					this.onFileIs(TR, item, file);
				}
				else
				{
					this.onUploadError(item, result, this.agent);
				}
				return true;
			}, this));

		}
	},
	onUploadError : function(item, params, agent)
	{
		var node = agent.getItem(item.id), errorText;
		if (!!node)
		{
			BX.adjust(node.node, { props : { className : "error-load" } } );
			errorText = (params && params["error"] ? params["error"] : BX.message("WD_FILE_UPLOAD_ERROR"));
			node.node.cells[2].innerHTML = '<span class="info-icon"></span><span class="error-text">' + errorText + '</span>';
			BX.onCustomEvent(item, 'OnFileUploadFailed', [node, this]);
		}
	},
	onError : function(stream, pIndex, data)
	{
		var item, id;
		stream.files = (stream.files || {});
		for (id in stream.files)
		{
			if (stream.files.hasOwnProperty(id))
			{
				item = this.agent.queue.items.getItem(id);
				this.onUploadError(item, data, this.agent);
			}
		}
	},
	bindEventsHandlers : function(row, item, file)
	{
		var id = file.element_id,
			attrName = 'file-action-control',
			filemove = BX.findChild(row, {'className' : 'files-path'}, true),
			filename = BX.findChild(row, {'className' : 'f-wrap'}, true);
		if (!!filemove && !!filename && !filemove.getAttribute(attrName))
		{
			filemove.setAttribute(attrName, 'enabled');
			BX.bind(filemove, 'click', BX.delegate(function() { this.move(id, filename.innerHTML, row); }, this));
		}
		var filepreview = BX.findChild(row, {'className' : 'files-preview-wrap', 'tagName' : 'SPAN'}, true);
		if (!!filepreview)
			BX.bind(filepreview, "mouseover", BX.delegate(function(){new __wfu_preview(this);}, filepreview));
		var divLoading = BX.findChild(row, {'className':'feed-add-post-loading'}, true),
			delFunc = BX.delegate(function() { this.deleteFile(row, item); }, this),
			closeBtn = BX.create('SPAN', {
				'props' : {
					'className' : 'del-but'
				},
				events : {
					click : delFunc
				}
			});
		if (!!divLoading)
		{
			var t = BX.findChild(divLoading, {'className':'del-but'}, true);
			if (!!t)
				BX.bind(t, 'click', delFunc);
			else
				divLoading.appendChild(closeBtn.cloneNode(true));
		}
		if (!BX.findChild(row, {'className':'files-info'}, true))
		{
			row.appendChild(
				BX.create('TD',
					{
						'props' : {
							'className' : 'files-info'
						}
					}
				)
			);
		}
		if (!BX.findChild(row, {'className':'files-del-btn'}, true))
		{
			row.appendChild(
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
		if (!closeBtn.hasAttribute("bx-bound"))
		{
			closeBtn.setAttribute("bx-bound", "Y");
			BX.bind(closeBtn, 'click', delFunc);
			BX.onCustomEvent(row, 'OnMkClose', [row]);
		}
	},
	deleteFile : function(row, item)
	{
		BX.hide(row);
		item.deleteFile('deleteFile');
		var editLink = BX.findChild(row, {'className' : 'file-edit'}, true), href;
		if (!!editLink)
		{
			href = this._addUrlParam(editLink.href.replace('EDIT','DELETE_DROPPED'), '&sessid=' + BX.bitrix_sessid() + '&EDIT=Y&AJAX_MODE=Y&IFRAME=Y');
			BX.ajax.get(href);
		}
		var tmp = row.id.match(/wd-doc(\d+)/), id = (!!tmp && !!tmp[1] ? tmp[1] : 0);

		BX.removeCustomEvent(item, 'onUploadProgress', this._onUploadProgress);
		BX.removeCustomEvent(item, 'onUploadDone', this._onUploadDone);
		BX.removeCustomEvent(item, 'onUploadError', this._onUploadError);

		if (!!id)
		{
			var fileInput = BX('wduf-doc' + id);
			if (!!fileInput)
				BX.remove(fileInput);
			BX.onCustomEvent(this.controller.parentNode, 'OnFileUploadRemove', [id, this]);
		}
	},
	move : function(element_id, name, row)
	{
		var urlMove = this._addUrlParam(
			this.urlSelect.replace("ACTION=SELECT", "").replace("MULTI=Y", ""),
			'ID=E' + element_id + '&NAME=' + name + '&ACTION=FAKEMOVE&IFRAME=Y');
		while(urlMove.indexOf('&&') >= 0)
			urlMove = urlMove.replace('&&', '&');

		if (!this.h_WDFD_CheckFileName)
		{
			this.h_WDFD_CheckFileName = BX.proxy(this.WDFD_CheckFileName, this);
			this.h_WDFD_SelectFolder = BX.proxy(this.WDFD_SelectFolder, this);
			this.h_WDFD_OpenSection = BX.proxy(this.WDFD_OpenSection, this);
			this.h_WDFD_selectItem = BX.proxy(this.WDFD_selectItem, this);
			this.h_WDFD_unSelectItem = BX.proxy(this.WDFD_unSelectItem, this);
		}

		var nodeFileTR = (row || BX('wd-doc'+element_id)),
			nodeFileName = BX.findChild(nodeFileTR, {'className':'f-wrap'}, true);

		BX.WebDavFileDialog.arParams = {};
		BX.WebDavFileDialog.arParams[this.dialogName] = { element_id : element_id, element_name : nodeFileName.innerHTML };

		BX.addCustomEvent(BX.WebDavFileDialog, 'loadItems', this.h_WDFD_OpenSection);
		BX.addCustomEvent(BX.WebDavFileDialog, 'loadItemsDone', this.h_WDFD_CheckFileName);
		BX.addCustomEvent(BX.WebDavFileDialog, 'selectItem', this.h_WDFD_selectItem);
		BX.addCustomEvent(BX.WebDavFileDialog, 'unSelectItem', this.h_WDFD_unSelectItem);

		return BX.ajax.get(urlMove, 'dialogName='+this.dialogName,
			BX.delegate(function() {
				setTimeout(BX.delegate(function() {
					BX.WebDavFileDialog.obCallback[this.dialogName] = {'saveButton' : this.h_WDFD_SelectFolder};
					BX.WebDavFileDialog.openDialog(this.dialogName);
					this.WDFD_CheckFileName();
				}, this), 100);
			}, this)
		);
	},
	showMovedFile : function(element_id, arProp, section_path)
	{
		if (isNaN(parseInt(element_id)) || (element_id <= 0))
			return false;

		var MAX_PATH_LENGTH,
			id = parseInt(element_id),
			parent = BX('wd-doc'+id),
			filemove = BX.findChild(parent, {'className' : 'files-path'}, true);

		BX.cleanNode(filemove);
		section_path = section_path.split('/').join(' / ');

		filemove.innerHTML = section_path;
		var w = parseInt(filemove.offsetWidth);
		var l = parseInt(filemove.parentNode.offsetWidth)-150, midName;
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
		return true;
	},
	_fileUnserialize : function(index)
	{
		if (!BX.type.isString(index))
			return false;

		var arIndex = index.split('|');
		return {id : (arIndex[0] || 0), section : (arIndex[1] || 0), iblock : (arIndex[2] || 0)};
	},
	_fileSerialize : function(arFile)
	{
		var arIndex = [ arFile.id, arFile.section, arFile.iblock ];
		return arIndex.join('|');
	},
	WDFD_SelectFolder : function(tab, path, selected, folderByPath)
	{
		var id = false, moved = false, i, secPath, arProp;
		if ((BX.WebDavFileDialog.arParams) &&
			(BX.WebDavFileDialog.arParams[this.dialogName]) &&
			(BX.WebDavFileDialog.arParams[this.dialogName]['element_id']))
				id = BX.WebDavFileDialog.arParams[this.dialogName]['element_id'];
		for (i in selected)
		{
			if (selected.hasOwnProperty(i) && i.substr(0,1) == 'S')
			{
				secPath = tab.name + selected[i].path;
				arProp = { sectionID : i.substr(1), iblockID : tab.iblock_id };
				this.showMovedFile(id, arProp, secPath);
				moved = true;
			}
		}
		if (!moved)
		{
			secPath = tab.name;
			arProp = { sectionID : tab.section_id, iblockID : tab.iblock_id };
			if (!!folderByPath && !!folderByPath.path && folderByPath.path != '/')
			{
				secPath += folderByPath.path;
				arProp.sectionID = folderByPath.id.substr(1);
			}
			this.showMovedFile(id, arProp, secPath);
		}
		BX.removeCustomEvent(BX.WebDavFileDialog, 'loadItems', this.h_WDFD_OpenSection);
		BX.removeCustomEvent(BX.WebDavFileDialog, 'loadItemsDone', this.h_WDFD_CheckFileName);
		BX.removeCustomEvent(BX.WebDavFileDialog, 'selectItem', this.h_WDFD_selectItem);
		BX.removeCustomEvent(BX.WebDavFileDialog, 'unSelectItem', this.h_WDFD_unSelectItem);
	},
	WDFD_CheckFileName : function(name)
	{
		if (this.noticeTimeout)
		{
			clearTimeout(this.noticeTimeout);
			this.noticeTimeout = null;
		}
		if (!!name && (name != this.dialogName))
			return;

		var fname = BX.WebDavFileDialog.arParams[this.dialogName]['element_name'];
		var exist = false, i;
		for (i in BX.WebDavFileDialog.obItems[this.dialogName])
		{
			if (BX.WebDavFileDialog.obItems[this.dialogName].hasOwnProperty(i))
			{
				if (BX.WebDavFileDialog.obItems[this.dialogName][i]['name'] == fname)
					exist = true;
				if (exist)
					break;
			}
		}

		if (exist)
			BX.WebDavFileDialog.showNotice(BX.message('WD_FILE_EXISTS'), this.dialogName);
		else
			BX.WebDavFileDialog.closeNotice(this.dialogName);
	},
	WDFD_selectItem : function(element, itemID, name)
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
			var documentExists = (result.permission === true && result["okmsg"] != "");

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
	},
	WDFD_unSelectItem : function()
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
	},
	WDFD_OpenSection : function(link, name)
	{
		if (name == this.dialogName) {
			BX.WebDavFileDialog.target[name] = this._addUrlParam(link, 'dialog2=Y');
		}
	},
	WDFD_SelectFile : function(tab, path, selected)
	{
		var ar = [], ar2 = [];
		for (var i in selected)
		{
			if (selected.hasOwnProperty(i))
			{
				if (i.substr(0,1) == 'E' && (!BX('wduf-doc'+i.substr(1))))
				{
					ar.push( { name : selected[i].name, size : parseInt(selected[i]["sizeInt"]), type : selected[i].type } );
					ar2.push( { element_id : i.substr(1), element_name : selected[i].name, url_show : selected[i].link } );
				}
			}
		}
		this.agent.onAttach(ar, ar2);
		BX.removeCustomEvent(BX.WebDavFileDialog, 'loadItems', this.h_WDFD_OpenSection);
	},
	ShowSelectDialog : function()
	{
		this.h_WDFD_OpenSection = BX.proxy(this.WDFD_OpenSection, this);
		this.h_WDFD_SelectFile = BX.proxy(this.WDFD_SelectFile, this);
		BX.addCustomEvent(BX.WebDavFileDialog, 'loadItems', this.h_WDFD_OpenSection);
		BX.ajax.get(this.urlSelect, 'dialogName='+this.dialogName,
			BX.delegate(function() {
				setTimeout(BX.delegate(function() {
					BX.WebDavFileDialog.obCallback[this.dialogName] = {'saveButton' : this.h_WDFD_SelectFile};
					BX.WebDavFileDialog.openDialog(this.dialogName);
				}, this), 10);
			}, this)
		);
	}
};
BX.WebdavUpload.toLoadJSON = [];
BX.WebdavUpload.add = function(params)
{
	if (!!params["JSON"] && params["JSON"].length > 0)
	{
		BX.WebdavUpload.toLoadJSON = (BX.WebdavUpload.toLoadJSON || []);
		for (var ij = 0; ij < params["JSON"].length; ij++)
		{
			if (!BX.util.in_array(params["JSON"][ij], BX.WebdavUpload.toLoadJSON))
			{
				BX.WebdavUpload.toLoadJSON.push(params["JSON"][ij]);
				BX.ajax({method: 'GET', dataType: 'json', url: params["JSON"][ij], skipAuthCheck : true, onsuccess : BX.DoNothing});
			}
		}
	}
	params["controller"] = BX('wduf-selectdialog-' + params['UID']);
	BX.addCustomEvent(params['controller'].parentNode, "WDLoadFormController", function(status){ BX.WebdavUpload.initialize(status, params); });
	BX.onCustomEvent(params['controller'], "WDLoadFormControllerWasBound", [params]);

	if (!!BX.DD)
	{
		var controller = params["controller"];


		if (BX.type.isElementNode(controller) && controller.parentNode && controller.parentNode.parentNode)
		{
			var dropbox = new BX.DD["dropFiles"](controller.parentNode.parentNode);
			if (dropbox && dropbox.supported() && BX.ajax.FormData.isSupported()) {
				params["__expandFunction"] = function(){
					if (dropbox.__bound === true)
					{
						BX.WebdavUpload.initialize('show', params);
						BX.removeCustomEvent(dropbox, 'dragEnter', params["__expandFunction"]);
						dropbox.__bound = false;
					}
				};
				params["__bind2ExpandFunction"] = function(){
					if (dropbox.__bound !== true)
					{
						BX.addCustomEvent(dropbox, 'dragEnter', params["__expandFunction"]);
						dropbox.__bound = true;
					}
				};
				params["__bind2ExpandFunction"]();
				BX.addCustomEvent(params['controller'], "onControllerHasBeenHidden", params["__bind2ExpandFunction"]);
			}
		}
	}

	if (!!params['values'] && params['values'].length > 0)
		BX.WebdavUpload.initialize('show', params);
};
BX.WebdavUpload.initialize = function(status, params)
{
	status = (status === 'show' ? 'show' : (status === 'hide' ? 'hide' : 'switch'));
	if (status == 'switch')
		status = (params['controller'].style.display != 'none' ? 'hide' : 'show');

	if (! params['controller'].loaded)
	{
		params['controller'].loaded = true;
		params['controller'].parentNode.cName = 'wdFD' + params['UID'];

		wdufCurrentFileDialog = top['wdFD' + params['UID']] = new BX.WebdavUpload(params);
		if (!params['values'] || params['values'].length <= 0)
		{
			if (status == 'show')
			{
				BX.fx.show(params['controller'], 'fade', {time:0.2});
				if (params['switcher'] && params['switcher'].style.display != 'none')
					BX.fx.hide(params['switcher'], 'fade', {time:0.1});
			}
		}
		else
		{
			BX.show(params['controller']);
			if (params['switcher'] && params['switcher'].style.display != 'none')
				BX.hide(params['switcher'], 'fade');
		}
	}
	else if (status == 'show')
	{
		wdufCurrentFileDialog = top['wdFD' + params['UID']];
		BX.fx.show(params['controller'], 'fade', {time:0.2});
		if (params['switcher'] && params['switcher'].style.display != 'none')
			BX.fx.hide(params['switcher'], 'fade', {time:0.1});
	}
	else
	{
		wdufCurrentFileDialog = false;
		BX.fx.hide(params['controller'], 'fade', {time:0.2});
		BX.onCustomEvent(params['controller'], "onControllerHasBeenHidden", [params['controller']]);
	}
};

})(window);