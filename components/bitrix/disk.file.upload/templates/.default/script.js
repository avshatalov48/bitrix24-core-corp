;(function() {
	if (BX.DiskUpload)
		return;

	var repo = [];
	BX.DiskUpload = function(params)
	{
		this.bp = params['bp'];
		this.bpParameters = params['bpParameters'];
		this.bpParametersRequired = params['bpParametersRequired'];
		this.storageId = params["storageId"];
		this.ajaxUrl = '/bitrix/components/bitrix/disk.folder.list/ajax.php';
		this.CID = params["CID"];
		this.nodeContent = BX.create('DIV', {
			style : { display : "none"},
			attrs : { id : params["CID"] + 'ContentNode'},
			html : BX.message('DFU_TEMPLATE').replace(/#id#/gi, this.CID) + '<div class="wduf-uploader area">' + BX.message('DFU_DND_TEMPLATE') + '</div>'});
		document.body.appendChild(this.nodeContent);
		this.counter = {
			all : [],
			uploaded : [],
			uploading : []
		};
		this.init(params);
		return this;
	};
	BX.DiskUpload.prototype = {
		_camelToSNAKE : function(obj)
		{
			var o = {}, i, k;
			for (i in obj)
			{
				if (obj.hasOwnProperty(i))
				{
					k = i.replace(/(.)([A-Z])/g, "$1_$2").toUpperCase();
					o[k] = obj[i];
					o[i] = obj[i];
				}
			}

			return o;
		},
		initAttempt : 0,
		init : function(params)
		{
			this.initAttempt++;
			if (this.initAttempt > 10 || this.agent)
				return;
			this.placeHolder = BX(this.CID + 'PlaceHolder');
			if (this.placeHolder)
			{
				this.templates = {
					'new' : BX.message('DFU_NODE_TEMPLATE'),
					done : BX.message('DFU_NODE_TEMPLATE_DONE'),
					canceled : BX.message('DFU_NODE_TEMPLATE_CANCELED'),
					error : BX.message('DFU_NODE_TEMPLATE_ERROR'),
					error_double : BX.message('DFU_NODE_TEMPLATE_ERROR_DOUBLE')
				};
				var i, j, match, attrs, ii, match1;
				for (i in this.templates)
				{
					attrs = {};
					if (this.templates.hasOwnProperty(i))
					{
						j = this.templates[i];
						if (/^<tr(.*?)>/ig.test(j))
						{
							match = /^<tr(.*?)>/ig.exec(j);
							match = match[1].split(" ");
							if (match && match.length > 0)
							{
								for (ii = 0; ii < match.length; ii++)
								{
									match1 = match[ii].split("=");
									if (match1 && match1.length == 2)
									{
										match1[0] = match1[0].replace(/['"]/gi, "");
										match1[1] = match1[1].replace(/['"]/gi, "");
										attrs[match1[0]] = match1[1];
									}
								}
							}
						}
						this.templates[i] = {
							node : "TR",
							attrs : attrs,
							text : j.replace(/^<tr(.*?)>/i, "").replace(/<\/tr>$/i, "")
						}
					}
				}

				this.agent = BX.Uploader.getInstance({
					id : this.CID,
					streams : 1,
					allowUpload : "A",
					uploadFormData : "Y",
					uploadMethod : (this.bp ? ((this.bpParameters) ? "deferred" : "immediate") : "immediate"),
					uploadFileUrl : params["urlUpload"],
					showImage : false,
					sortItems : false,
					input : params["input"],
					dropZone : params["dropZone"],
					placeHolder : this.placeHolder,
					phpMaxFileUploads : 1,
					queueFields : {
						thumb : {
							tagName : "TR",
							className : "bxu-item"
						}
					},
					fields : {
						thumb : {
							tagName : "",
							template : this.templates["new"].text
						}
					}
				});

				this._onItemIsAdded = BX.delegate(this.onItemIsAdded, this);
				this._onFileIsInited = BX.delegate(this.onFileIsInited, this);
				this._onStart = BX.delegate(this.onStart, this);
				this._onError = BX.delegate(this.onError, this);

				if (BX(this.agent.fileInput) && !this.agent.fileInput.hasAttribute("id"))
					this.agent.fileInput.setAttribute("id", "diskUbloadFileInput" + this.CID);
				BX.addCustomEvent(this.agent, "onItemIsAdded", this._onItemIsAdded);
				BX.addCustomEvent(this.agent, "onFileIsInited", this._onFileIsInited);
				BX.addCustomEvent(this.agent, "onStart", this._onStart);
				BX.addCustomEvent(this.agent, "onError", this._onError);

				BX.addCustomEvent("Disk.Page:onChangeFolder", function(folder, newFolder){
					this.changeTargetFolderId(newFolder.id);
				}.bind(this));

				this._onFileIsAppended = BX.delegate(this.onFileIsAppended, this);
				this._onUploadStart = BX.delegate(this.onUploadStart, this);
				this._onUploadProgress = BX.delegate(this.onUploadProgress, this);
				this._onUploadDone = BX.delegate(this.onUploadDone, this);
				this._onUploadError = BX.delegate(this.onUploadError, this);

				this._onUploadWindowClose = BX.delegate(this.onUploadWindowClose, this);
				this._onUploadWindowFirstShow = BX.delegate(this.onUploadWindowFirstShow, this);
				this._onStartBizproc = BX.delegate(this.onStartBizproc, this);

				if (BX(params["dropZone"]))
				{
					var
						divDrop = BX.create('DIV', {attrs : {className : "wduf-uploader area"}, html : BX.message('DFU_DND_TEMPLATE')}),
						pos = BX.pos(params["dropZone"]);
					BX.adjust(params["dropZone"], {style : {position : "relative"}, children: [divDrop]});
					BX.addCustomEvent(this.agent.dropZone, 'dragEnter', function() {
						var windowSize =  BX.GetWindowInnerSize(),
							windowScroll = BX.GetWindowScrollPos();
						pos["height"] = windowSize["innerHeight"] - (pos["top"] - windowScroll["scrollTop"]);
						divDrop.style.height = pos["height"] + 'px';
					});
				}
			}
			else
			{
				setTimeout(BX.delegate(function(){this.init(params);}, this), 500);
			}
		},
		changeTargetFolderId: function(folderId)
		{
			if(this.agent.form && this.agent.form.targetFolderId)
			{
				this.agent.form.targetFolderId.value = folderId;

				var items = this.agent.getItems().items;
				for(var i in items)
				{
					if(!items.hasOwnProperty(i))
					{
						continue;
					}

					var item = items[i];
					var nodeAndItem = this.agent.getItem(item.id);
					if (nodeAndItem.node)
					{
						BX.remove(nodeAndItem.node);
					}
				}
			}
		},
		onItemIsAdded : function()
		{
			if (this.popup)
				this.popup.show();
			else if(this.bp && this.bpParameters)
			{
				var content = BX.findChildren(this.nodeContent);
				content.unshift(BX('parametersFormBp'));
				this.popup = BX.Disk.modalWindow({
					modalId: 'bx-dfu-upload-' + this.CID,
					closeIcon: false,
					title: BX.message('DFU_UPLOAD_TITLE1'),
					contentClassName: ' tac bx-disk-upload-file',
					withoutWindowManager: true,
					content: content,
					events : {
						onPopupFirstShow : this._onUploadWindowFirstShow,
						onPopupClose: function () {
							BX.reload();
						}
					},
					zIndex: -200,
					htmlButtons: [
						BX.create('A', {
							text: BX.message("DFU_SAVE_BP"),
							props: {
								className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green mb0'
							},
							events : {
								click : this._onStartBizproc
							},
							attrs: {
								id: 'bx-disk-savebp-button'
							}
						}),
						BX.create('LABEL', {
							text: BX.message("DFU_UPLOAD"),
							props: {
								className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green mb0 bx-hide-button'
							},
							attrs : {
								id: 'bx-disk-upload-button',
								"for" : this.agent.fileInput.getAttribute("id")
							}
						}),
						BX.create('A', {
							text: BX.message("DFU_CLOSE"),
							props: {
								className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-gray mb0'
							},
							attrs : {
								id : this.CID + 'ButtonClose',
								disabled : true
							},
							events : {
								click : this._onUploadWindowClose
							}
						})
					]
				});
			}
			else
			{
				this.popup = BX.Disk.modalWindow({
					modalId: 'bx-dfu-upload-' + this.CID,
					closeIcon: false,
					title: BX.message('DFU_UPLOAD_TITLE1'),
					contentClassName: ' tac bx-disk-upload-file',
					content: BX.findChildren(this.nodeContent),
					events : {
						onPopupFirstShow : this._onUploadWindowFirstShow,
						onPopupClose : BX.delegate(function(){
							BX.addCustomEvent(this.agent, "onItemIsAdded", this._onItemIsAdded);
						}, this)
					},
					htmlButtons: [
						BX.create('LABEL', {
							text: BX.message("DFU_UPLOAD"),
							props: {
								className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-green mb0'
							},
							attrs : {
								"for" : this.agent.fileInput.getAttribute("id")
							}
						}),
						BX.create('A', {
							text: BX.message("DFU_CLOSE"),
							props: {
								className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-gray mb0'
							},
							attrs : {
								id : this.CID + 'ButtonClose',
								disabled : true
							},
							events : {
								click : this._onUploadWindowClose
							}
						})
					]
				});
			}

			this.popup.destroy = BX.DoNothing;

			BX.removeCustomEvent(this.agent, "onItemIsAdded", this._onItemIsAdded);
		},
		onStartBizproc : function()
		{
			if(BX('formPostAjax').getElementsByTagName('div').length)
			{
				BX('formPostAjax').removeChild(BX('divStartBizProc'));
			}
			var newForm = BX('divStartBizProc').cloneNode(true),
				textarea = BX('parametersFormBp').querySelectorAll('textarea'),
				select = BX('parametersFormBp').querySelectorAll('select'),
				textareaNew = newForm.querySelectorAll('textarea'),
				selectNew = newForm.querySelectorAll('select'),
				k;
			if(textarea.length)
			{
				for(k in textarea)
				{
					if (textarea.hasOwnProperty(k))
					{
						textareaNew[k].value = textarea[k].value;
					}
				}
			}
			if (select.length)
			{
				for (k in select)
				{
					if (select.hasOwnProperty(k))
					{
						selectNew[k].value = select[k].value;
					}
				}
			}
			BX('formPostAjax').appendChild(newForm);
			var data = BX.ajax.prepareForm(BX('formPostAjax')),
				agent = this.agent;
			BX.Disk.ajax({
				method: 'POST',
				dataType: 'json',
				url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'validateParameterAutoloadBizProc'),
				data: {
					required: this.bpParametersRequired,
					data: data,
					storageId: this.storageId
				},
				onsuccess: function (result) {
					if(result.status == 'success')
					{
						agent.submit();
						BX('formPostAjax').removeChild(BX('divStartBizProc'));
						BX('errorTd').innerHTML = '';
						if(!BX.hasClass(BX('bx-disk-savebp-button'), 'bx-hide-button'))
						{
							BX.toggleClass(BX('bx-disk-upload-button'), 'bx-hide-button');
							BX.toggleClass(BX('bx-disk-savebp-button'), 'bx-hide-button');
						}
					}
					else
					{
						if(BX('formPostAjax').getElementsByTagName('div').length)
						{
							BX('formPostAjax').removeChild(BX('divStartBizProc'));
						}
						for(k in result["errors"])
						{
							if (result["errors"].hasOwnProperty(k))
							{
								BX('errorTd').innerHTML = result["errors"][k].message;
							}
						}
						if(BX.hasClass(BX('bx-disk-savebp-button'), 'bx-hide-button'))
						{
							BX.toggleClass(BX('bx-disk-upload-button'), 'bx-hide-button');
							BX.toggleClass(BX('bx-disk-savebp-button'), 'bx-hide-button');
						}
					}
				}
			});
		},
		onFileIsInited : function(id, file)
		{
			this.counter.all.push(id);
			BX.addCustomEvent(file, 'onFileIsAppended', this._onFileIsAppended);
			BX.addCustomEvent(file, 'onUploadStart', this._onUploadStart);
			BX.addCustomEvent(file, 'onUploadProgress', this._onUploadProgress);
			BX.addCustomEvent(file, 'onUploadDone', this._onUploadDone);
			BX.addCustomEvent(file, 'onUploadError', this._onUploadError);

			this.__checkButton();
			if(this.bp && this.bpParameters && this.popup)
			{
				this.onStartBizproc();
			}
		},
		onFileIsAppended : function(id, item)
		{
			var node = this.agent.getItem(id);
			this.__bindEventsToNode(node.node, item);
		},
		onUploadStart : function(item)
		{
			this.counter.uploading.push(item.id);
			item.__progressBar = BX(item.id + 'Progress');
			item.__progressBarWidth = 5;
			if (item.__progressBar)
				item.__progressBar.style.width = item.__progressBarWidth + '%';
		},
		onUploadProgress : function(item, progress)
		{
			progress = Math.min(progress, 96);
			if (progress > item.__progressBarWidth)
			{
				item.__progressBarWidth = Math.ceil(progress);
				item.__progressBarWidth = (item.__progressBarWidth > 100 ? 100 : item.__progressBarWidth);
				item.__progressBar = BX(item.id + 'Progress');
				if (item.__progressBar)
					item.__progressBar.style.width = item.__progressBarWidth + '%';
			}
		},
		onUploadDone : function(item, result)
		{
			if (!this.agent.getItem(item.id))
			{
				return;	
			}
			item.__progressBar = BX(item.id + 'Progress');
			if (item.__progressBar)
				item.__progressBar.style.width = '100%';
			this.counter.uploading = BX.util.deleteFromArray(this.counter.uploading, BX.util.array_search(item.id, this.counter.uploading));
			this.counter.uploaded.push(item.id);
			item.fileId = result["file"]["fileId"];
			var node = this.agent.getItem(item.id).node, file = this._camelToSNAKE(result["file"]);
			if (BX(node))
			{
				var html = this.templates.done.text;
				for (var ii in file)
				{
					if (file.hasOwnProperty(ii))
					{
						html = html.replace(new RegExp("#" + ii.toLowerCase() + "#", "gi"), BX.util.htmlspecialchars(file[ii])).
							replace(new RegExp("#" + ii.toUpperCase() + "#", "gi"), BX.util.htmlspecialchars(file[ii]));
					}
				}
				var TR;
				if (BX.browser.IsIE8())
				{
					TR = node;
					while (TR.cells.length > 0)
					{
						TR.deleteCell(0);
					}
					var cellIndex = 0;
					html.replace(/<\/td>/gi, "\002").replace(/<td([^>]+)>([^\002]+)\002/gi, function(str, attrs, innerH)
					{
						var TD = TR.insertCell(cellIndex);
						TD.innerHTML = innerH;
						attrs.replace(/class=["']([a-z\-\s]+)['"]/, function(str, className) { TD.className = className; });
						cellIndex++;
						return '';
					});
				}
				else
				{
					TR = BX.create('TR', { attrs: this.templates.done.attrs, html: html } );
					TR.setAttribute("id", node.getAttribute("id"));
					node.parentNode.replaceChild(TR, node);
				}
			}
			else
			{
				this.onUploadError(item, result);
			}
			this.__bindEventsToNode(node, item);
			this.__checkButton();
		},
		onUploadError : function(item, result, specify)
		{
			this.counter.uploading = BX.util.deleteFromArray(this.counter.uploading, BX.util.array_search(item.id, this.counter.uploading));
			result = (typeof result == "object" && result ? result : {});
			var node = this.agent.getItem(item.id).node, file = this._camelToSNAKE(result);
			if (BX(node) && (specify == true || !BX(node).hasAttribute("bx-disk-error")))
			{
				file = (!!file && typeof file == "object" ? file : {});
				var
					template = (file["file"] && file["file"]["isNotUnique"] ? this.templates.error_double : this.templates.error),
					html = template.text, ii;
				for (ii in item)
				{
					if (item.hasOwnProperty(ii) && typeof item[ii] == "string")
					{
						html = html.replace(new RegExp("#" + ii.toLowerCase() + "#", "gi"), BX.util.htmlspecialchars(item[ii])).
							replace(new RegExp("#" + ii.toUpperCase() + "#", "gi"), BX.util.htmlspecialchars(item[ii]));
					}
				}
				if (file)
				{
					for (ii in file)
					{
						if (file.hasOwnProperty(ii))
						{
							html = html.replace(new RegExp("#" + ii.toLowerCase() + "#", "gi"), BX.util.htmlspecialchars(file[ii])).
								replace(new RegExp("#" + ii.toUpperCase() + "#", "gi"), BX.util.htmlspecialchars(file[ii]));
						}
					}
					if (file['file'])
					{
						for (ii in file['file'])
						{
							if (file['file'].hasOwnProperty(ii))
							{
								html = html.replace(new RegExp("#" + ii.toLowerCase() + "#", "gi"), BX.util.htmlspecialchars(file['file'][ii])).
									replace(new RegExp("#" + ii.toUpperCase() + "#", "gi"), BX.util.htmlspecialchars(file['file'][ii]));
							}
						}
					}
				}
				var TR = BX.create('TR', { attrs: template.attrs, html: html } );
				TR.setAttribute("id", node.getAttribute("id"));
				TR.setAttribute("bx-disk-error", "Y");
				node.parentNode.replaceChild(TR, node);
				this.__bindEventsToNode(TR, item);
				this.__checkButton();

				if (this.uploadParams.errored <= 0)
				{
					var pos = BX.pos(TR),
						pos2 = BX.pos(this.placeHolder);
					this.placeHolder.parentNode.scrollTop = (pos.top - pos2.top);
				}
				this.uploadParams.errored++;
			}
			else
			{
				BX.debug('BX.Disk.Upload: node for error does not exist.');
			}
		},
		uploadParams : {errored : 0},
		onStart : function()
		{
			this.uploadParams.errored = 0;
		},
		onError : function(stream, pIndex, data)
		{
			if(data && data.status === "restriction" && BX('bx-bitrix24-business-tools-info'))
			{
				BX.PopupWindowManager.create('bx-disk-business-tools-info', null, {
					content: BX('bx-bitrix24-business-tools-info'),
					closeIcon: true,
					onPopupClose: function ()
					{
						this.destroy();
					},
					autoHide: true
				}).show();
			}

			var defaultErrorText = 'Uploading error.',
				errorText = defaultErrorText,
				item, id;

			if (data)
			{
				if (data["error"] && typeof data["error"] == "string")
					errorText = data["error"];
				else if (data["message"] && typeof data["message"] == "string")
					errorText = data["message"];
				else if (BX.type.isArray(data["errors"]) && data["errors"].length > 0)
				{
					errorText = [];
					for (var ii = 0; ii < data["errors"].length; ii++)
					{
						if (typeof data["errors"][ii] == "object" && data["errors"][ii]["message"])
							errorText.push(data["errors"][ii]["message"]);
					}
					if (errorText.length <= 0)
						errorText.push(defaultErrorText);
					errorText = errorText.join(' ');
				}
			}
			stream["files"] = (stream.files || {});

			for (id in stream["files"])
			{
				if (stream["files"].hasOwnProperty(id))
				{
					item = this.agent.queue.items.getItem(id);
					this.onUploadError(item, {error : errorText}, (errorText != defaultErrorText));
				}
			}
		},
		deleteFile : function(row, item)
		{
			BX.addClass(row, "bx-disk-popup-upload-file-delete-event");
			var
				editLink = BX.findChild(row, {'className' : 'file-delete'}, true),
				key = BX.util.array_search(item.id, this.counter.uploaded);
			if (key >= 0)
				this.counter.uploaded = BX.util.deleteFromArray(this.counter.uploaded, key);
			key = BX.util.array_search(item.id, this.counter.uploading);
			if (key >= 0)
				this.counter.uploading = BX.util.deleteFromArray(this.counter.uploading, key);
			this.counter.all = BX.util.deleteFromArray(this.counter.all, BX.util.array_search(item.id, this.counter.all));
			delete item.hash;
			setTimeout(function(){item.deleteFile('deleteFile');}, 400);
			if (!!editLink && editLink.getAttribute("href").length > 0)
				BX.ajax.post(editLink.href, {sessid : BX.bitrix_sessid()});
			this.__checkButton();
		},
		replaceFile : function(row, item)
		{
			if (BX(row))
			{
				var html = this.templates["new"].text;
				for (var ii in item)
				{
					if (item.hasOwnProperty(ii))
					{
						html = html.replace(new RegExp("#" + ii.toLowerCase() + "#", "gi"), BX.util.htmlspecialchars(item[ii])).
							replace(new RegExp("#" + ii.toUpperCase() + "#", "gi"), BX.util.htmlspecialchars(item[ii]));
					}
				}
				var TR = BX.create('TR', { attrs: this.templates["new"].attrs, html: html } );
				TR.setAttribute("id", row.getAttribute("id"));
				row.parentNode.replaceChild(TR, row);
				this.__bindEventsToNode(row, item);
				this.__checkButton();
				var status = item.file.uploadStatus;
				this.agent.queue.restoreFiles(new BX.UploaderUtils.Hash(item.id, item), true);
				item.file.uploadStatus = status;
				if (this.agent.post)
				{
					this.agent.post.data["REPLACE_FILE"] = (this.agent.post.data["REPLACE_FILE"] || []);
					this.agent.post.data["REPLACE_FILE"].push(item.id);
				}
				var node;
				if (this.agent.form)
				{
					node = BX.create("INPUT", {
						attrs : { id : 'input' + item.id},
						props : { name : 'REPLACE_FILE[]', value : item.id}
					});
					this.agent.form.appendChild(node);
				}

				this.agent.submit();

				if (this.agent.post)
				{
					this.agent.post.data["REPLACE_FILE"].pop();
				}
				if (this.agent.form)
				{
					BX.remove(node);
				}
			}
		},
		restoreFile : function(row, item)
		{
			if (!BX(row))
			{
				return;
			}
			var html = this.templates["new"].text;
			for (var ii in item)
			{
				if (item.hasOwnProperty(ii))
				{
					html = html.replace(new RegExp("#" + ii.toLowerCase() + "#", "gi"), BX.util.htmlspecialchars(item[ii])).
					replace(new RegExp("#" + ii.toUpperCase() + "#", "gi"), BX.util.htmlspecialchars(item[ii]));
				}
			}
			var TR = BX.create('TR', { attrs: this.templates["new"].attrs, html: html } );
			TR.setAttribute("id", row.getAttribute("id"));
			row.parentNode.replaceChild(TR, row);
			this.__bindEventsToNode(row, item);
			this.__checkButton();
			var status = item.file.uploadStatus;
			this.agent.queue.restoreFiles(new BX.UploaderUtils.Hash(item.id, item), true, false);
			item.file.uploadStatus = status;
			item.__progressBar = BX(item.id + 'Progress');
			item.__progressBarWidth = 5;
			this.agent.submit();
		},
		__checkButton : function()
		{
			var button = BX(this.CID + 'ButtonClose'),
				number = BX(this.CID + 'Number'),
				count = BX(this.CID + 'Count');

			if (button)
				BX.adjust(button, {props : {disabled : (this.counter.uploading.length > 0)}});
			if (number)
				number.innerHTML = this.counter.uploaded.length;
			if (count)
				count.innerHTML = this.counter.all.length;
		},
		__bindEventsToNode : function(row, item)
		{
			var
				delFunc = function() { this.deleteFile(row, item); }.bind(this),
				replaceFunc = function(e) { BX.PreventDefault(e); this.replaceFile(row, item); return false;}.bind(this),
				restoreFunc = function(e) { BX.PreventDefault(e); this.restoreFile(row, item); return false;}.bind(this),
				closeBtn = BX.findChild(row, {attribute: { id : item.id + 'Cancel'} }, true),
				replaceBtn = BX.findChild(row, {attribute: { id : item.id + 'Replace'} }, true),
				restoreBtn = BX.findChild(row, {attribute: { id : item.id + 'Restore'} }, true);
			if (closeBtn && !closeBtn.hasAttribute("bx-bound"))
			{
				closeBtn.setAttribute("bx-bound", "Y");
				BX.bind(closeBtn, 'click', delFunc);
			}
			if (replaceBtn && !replaceBtn.hasAttribute("bx-bound"))
			{
				replaceBtn.setAttribute("bx-bound", "Y");
				BX.bind(replaceBtn, 'click', replaceFunc);
			}
			if (restoreBtn && !restoreBtn.hasAttribute("bx-bound"))
			{
				restoreBtn.setAttribute("bx-bound", "Y");
				BX.bind(restoreBtn, 'click', restoreFunc);
			}
		},
		onUploadWindowFirstShow : function(popup)
		{
			this.agent.initDropZone(popup.contentContainer);
			if(this.bp && this.bpParameters)
			{
				var htmlEditor = BX.findChildrenByClassName(BX('parametersFormBp'), 'bx-html-editor'),
					editorId;
				for(var k in htmlEditor)
				{
					if (htmlEditor.hasOwnProperty(k))
					{
						editorId = htmlEditor[k].getAttribute('id').replace("bx-html-editor-", "");
						window["BXHtmlEditor"].editors[editorId].CheckAndReInit();
					}
				}
			}
		},
		onUploadWindowClose : function()
		{
			if (this.popup && this.counter.uploading.length <= 0)
			{
				this.popup.close();
				if (this.counter.uploaded.length > 0)
				{
					var url, item;
					var countOfUploadedFiles = this.counter.uploaded.length;
					while ((item = this.agent.getItem(this.counter.uploaded.pop())) && item && item["item"] && (item = item["item"]))
					{
						if (item["fileId"])
						{
							url = BX.Disk.getUrlToShowObjectInGrid(item["fileId"]);
							break;
						}
					}

					if (countOfUploadedFiles === 1 && BX.SidePanel.Instance.getSliderByWindow(window))
					{
						BX.SidePanel.Instance.postMessageAll(window, 'Disk.File:onNewVersionUploaded', {
							object: {
								id: item["fileId"]
							}
						});
					}

					BX.onCustomEvent("onPopupFileUploadClose", [this, item["fileId"]]);
				}
			}
		}
	};
	BX.DiskUpload.initialize = function(params)
	{
		params['CID'] = (params['CID'] ? params['CID'] : 'DiskUpload');

		var input = BX.create('INPUT', {
			attrs: {id: ('inputContainer' + params['CID'])},
			props: {type: 'file', multiple: true, title: BX.message("DFU_UPLOAD_TITLE1")}
		});
		var form = BX.create('FORM', {
			attrs: {
				id: 'formPostAjax'
			},
			style: {
				display: 'none'
			},
			children: [
				input,
				(params['targetFileId'] ?
						BX.create('INPUT', {
							props: {
								type: "hidden",
								name: "targetFileId",
								value: params['targetFileId']
							}
						}) :
						BX.create('INPUT', {
							props: {
								type: "hidden",
								name: "targetFolderId",
								value: params['targetFolderId']
							}
						})
				)
			]
		});
		var label = BX.create('LABEL', {
			attrs: {
				id: ('inputContainerLabel' + params['CID']),
				title: BX.message("DFU_UPLOAD_TITLE1"),
				"for": 'inputContainer' + params['CID']
			},
			children: []
		});
		var hiddenDiv = BX.create('DIV', {
			style: {display: "none"}
		});
		document.body.appendChild(hiddenDiv);

		if (params["inputContainer"])
		{
			var inputContainer = BX(params["inputContainer"]);
			BX.adjust(label, {
				attrs: {
					className: inputContainer.className,
					id: inputContainer.id,
					title: BX.message("DFU_UPLOAD_TITLE1"),
					"for": 'inputContainer' + params['CID']
				},
				html: inputContainer.innerHTML
			});
			label.appendChild(form);
			inputContainer.parentNode.replaceChild(label, inputContainer);
		}
		else
		{
			label.appendChild(form);
			hiddenDiv.appendChild(label);

			BX.addCustomEvent(window, "onDiskUploadPopupShow", function(node){ node.appendChild(label); });
			BX.addCustomEvent(window, "onDiskUploadPopupClose", function(node){ hiddenDiv.appendChild(label); });
		}
		params['input'] = input;

		if(!repo[params['CID']])
		{
			repo[params['CID']] = new BX.DiskUpload(params);
		}
		return repo[params['CID']];
	};
	BX.DiskUpload.getObj = function(cid)
	{
		return repo[cid]
	};
})();