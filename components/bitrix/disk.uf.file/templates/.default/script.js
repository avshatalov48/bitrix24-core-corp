;(function(window) {
	var diskufPopup = false,
		diskufCurrentIdDocument = false,
		diskufMenuNumber = 0,
		currentDialog = null,
		currentService = null,
		repo = {},
		showRepo = {};

	BX.namespace("BX.Disk");
	if (BX.Disk.UF)
		return;
	BX.Disk.UF = (function ()
	{
		var UF = function (params)
		{
			this.dialogName = "DiskFileDialog";
			this.params = params;
			this.CID = params['UID'];
			this.controller = params.controller;
			this.values = (params.values || []);
			this.prefix = 'diskuf-doc';
			this.onUploaderIsAlmostInited = BX.delegate(this.onUploaderIsAlmostInited, this);

			this._checkFileName = this.checkFileName.bind(this);
			this._selectFolder = this.selectFolder.bind(this);
			this._openSection = this.openSection.bind(this);
			this._selectItem = this.selectItem.bind(this);
			this._unSelectItem = this.unSelectItem.bind(this);

			BX.addCustomEvent(window, "onUploaderIsAlmostInited", this.onUploaderIsAlmostInited);

			this.agent = BX.Uploader.getInstance({
				id : params["UID"],
				streams : 3,
				allowUpload : "A",
				uploadFormData : "N",
				uploadMethod : "immediate",
				uploadFileUrl : params["urlUpload"],
				showImage : false,
				sortItems : false,
				input : params["input"],
				dropZone : params["dropZone"],
				placeHolder : params["placeHolder"],
				queueFields : {
					thumb : {
						tagName : "TR",
						className : "wd-inline-file"
					}
				},
				fields : {
					thumb : {
						tagName : "",
						template : BX.message('DISK_TMPLT_THUMB')
					}
				}
			});

			this.urlSelect = (!! params['urlSelect']) ? params['urlSelect'] : null;
			this.urlRenameFile = (!! params['urlRenameFile']) ? params['urlRenameFile'] : null;
			this.urlDeleteFile = (!! params['urlDeleteFile']) ? params['urlDeleteFile'] : null;
			this.urlSelect = this._addUrlParam(this.urlSelect, 'dialog2=Y&ACTION=SELECT&MULTI=Y');
			this.urlUpload = (!! params['urlUpload']) ? params['urlUpload'] : null;
			this.urlShow = (!! params['urlShow']) ? params['urlShow'] : null;

			this.params.controlName = (this.params.controlName || 'FILES[]');

			this.init();
			return this;
		};

		UF.prototype = {
			_addUrlParam : function(url, param)
			{
				if (!url)
					return null;
				if (url.indexOf(param) == -1)
					url += ((url.indexOf('?') == -1) ? '?' : '&') + param ;
				return url;
			},
			_camelToSNAKE : function(obj)
			{
				var o = {}, i, k;
				for (i in obj)
				{
					k = i.replace(/(.)([A-Z])/g, "$1_$2").toUpperCase();
					o[k] = obj[i];
					o[i] = obj[i];
				}

				return o;
			},
			onUploaderIsAlmostInited : function(objName, params)
			{
				if (this.CID != params["id"])
					return;
				BX.removeCustomEvent(window, "onUploaderIsAlmostInited", this.onUploaderIsAlmostInited);
				var s = BX.findChild(this.controller, {className : "diskuf-simple"}, true),
					e = BX.findChild(this.controller, {className : "diskuf-extended"}, true);
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
				params.input = BX.findChild(this.controller, { className : 'diskuf-fileUploader' }, true);
				params.dropZone = BX.findChild(this.controller, { className : 'diskuf-extended' }, false);
				this.params.placeHolder = params["placeHolder"] = BX.findChild(this.controller, {'className': 'diskuf-placeholder-tbody'}, true);
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

				BX.onCustomEvent(BX(this.controller.parentNode), "DiskDLoadFormControllerInit", [this]);

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

				var controller = BX.findChild(this.controller, { 'className': 'diskuf-selector-link'}, true);
				if (!!controller)
				{
					BX.bind(controller, 'click', BX.proxy(this.showSelectDialog, this));
				}

				var controllerClouds = BX.findChildren(this.controller, { 'className': 'diskuf-selector-link-cloud'}, true);
				if (!!controllerClouds)
				{
					for(var i in controllerClouds)
					{
						if(!controllerClouds.hasOwnProperty(i))
							continue;

						BX.bind(controllerClouds[i], 'click', BX.proxy(this.showSelectDialogCloudImport, this));
					}
				}

				return false;
			},
			addFile : function(file)
			{
				return (this.agent && this.agent.onChange([file]));
			},
			onItemIsAdded : function()
			{
				BX.removeCustomEvent(this.agent, "onItemIsAdded", this._onItemIsAdded);
				BX.show(BX.findParent(this.params.placeHolder, {className:'diskuf-files-block'}));
			},
			onFileIsInited : function(id, file)
			{
				BX.addCustomEvent(file, 'onFileIsAttached', this._onFileIsAttached);
				BX.addCustomEvent(file, 'onFileIsAfterCreated', function(res, being, itemStatus, uploader){
					if(being && !being.sizeInt)
					{
						//we won't to show 0 bytes
						res.size = ' ';
					}
				});
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
					element_url: (file.element_name || file.previewUrl || file.preview_url),
					place : TR,
					storage : 'disk'
				};
				this.values.push(TR);
				BX.onCustomEvent(this.params.controller.parentNode, 'OnFileUploadSuccess', [res, this, item.file, item]);
				item.__disk_element_id = file.element_id;
				BX.onCustomEvent(item, 'OnFileUploadSuccess', [res, this]);
			},
			onFileIsAppended : function(id, item)
			{
				var node = this.agent.getItem(id), TR = node.node;
				this.bindEventsHandlers(TR, item, {});
				BX.onCustomEvent(this.params.controller.parentNode, 'onFileIsAppended', [id, item]);
			},
			onFileIsBound : function(id, item)
			{
				var node = this.agent.getItem(id), TR = node.node, element_id = TR.getAttribute("id").replace("disk-edit-attach", "");
				this.onFileIs(TR, item, { element_id : element_id });
			},
			onFileIsAttached : function(id, item, agent, being)
			{
				if (!!being["sizeFormatted"])
					being["size"] = being["sizeFormatted"];

				if(!being.hasOwnProperty('service'))
				{
					this.onUploadDone(item, { file : being} );
					return;
				}
				BX.Disk.ExternalLoader.startLoad({
					file: {
						id: being.id,
						name: being.name,
						service: being.service
					},

					onFinish: BX.delegate(function(newData){

						var itemNode = this.agent.getItem(item.id).node;
						BX.hide(itemNode);

						var file = {
							id: newData.ufId,
							name: newData.name,
							storage: newData.storage,
							ext: item.ext,
							size: newData.sizeFormatted,
							previewUrl: newData.previewUrl,
							preview_url: newData.previewUrl,
							element_url: newData.previewUrl,
							size_int: parseInt(newData.size, 10)
						};

						var value;
						var html = BX.message('DISK_TMPLT_THUMB2').replace("#control_name#", this.params.controlName).replace("#CONTROL_NAME#", this.params.controlName);
						for (var ii in file)
						{
							value = file[ii];
							if (file.hasOwnProperty(ii))
							{
								if(ii.toLowerCase() == 'size')
								{
									if(file.hasOwnProperty('size_int'))
									{
										if(parseInt(file['size_int'], 10) == 0 || isNaN(file['size_int']))
										{
											value = '';
										}
									}
									if(file.hasOwnProperty('SIZE_INT') || isNaN(file['SIZE_INT']))
									{
										if(parseInt(file['SIZE_INT'], 10) == 0)
										{
											value = '';
										}
									}
								}

								html = html.replace(new RegExp("\#" + ii.toLowerCase() + "\#", "gi"), value).
									replace(new RegExp("\#" + ii.toUpperCase() + "\#", "gi"), value);
							}
						}
						var attrs = { id : 'disk-edit-attach' + file.id, 'bx-agentFileId': item.id}, TR;
						TR = BX.create('TR', {
							attrs : attrs,
							props: { className: 'wd-inline-file'}, html: html } );

						var editNameBtn = BX.findChild(TR, {className : 'files-name-edit-btn'}, true);
						if(editNameBtn)
						{
							BX.remove(editNameBtn);
						}

						itemNode.parentNode.insertBefore(TR, itemNode);
						file.element_id = file.id;
						this.agent.onAttach([file], [TR]);

						item.deleteFile();
					}, this),
					onProgress: BX.delegate(function(progress){
						this.onUploadProgress(item, progress);
					}, this)
				});
			},
			onUploadProgress : function(item, progress)
			{
				progress = Math.min(progress, 98);
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
				if (result["file"] && result["file"]["attachId"] && result["file"]["attachId"] != result["file"]["id"])
				{
					result["file"]["id"] = result["file"]["attachId"];
					delete result["file"]["attachId"];
				}
				var node = this.agent.getItem(item.id).node, file = this._camelToSNAKE(result["file"]);
				if (BX(node))
				{
					var html = BX.message('DISK_TMPLT_THUMB2').replace("#control_name#", this.params.controlName).replace("#CONTROL_NAME#", this.params.controlName), value;
					for (var ii in file)
					{
						if (file.hasOwnProperty(ii))
						{
							value = file[ii];
							if(ii.toLowerCase() == 'size')
							{
								if(file.hasOwnProperty('size_int'))
								{
									if(parseInt(file['size_int'], 10) == 0)
									{
										value = '';
									}
								}
								if(file.hasOwnProperty('SIZE_INT'))
								{
									if(parseInt(file['SIZE_INT'], 10) == 0)
									{
										value = '';
									}
								}
							}

							//it's fix for editing comment without ajax. There is no information about file path.
							//and we have to send request to calculate it.
							if(ii === 'STORAGE' && value === 'disk')
							{
								getBreadcrumbsByAttachedObject(file.id).then(function(response){
									if(response.crumbs)
									{
										var path = response.crumbs.join(' / ');
										var parent = BX('disk-edit-attach'+file.id);
										if(parent)
										{
											var placement = BX.findChild(parent, {className : 'files-placement'}, true);
											if(placement)
											{
												BX.adjust(placement, {text: path});
											}
										}
									}
								});
							}

							html = html.replace(new RegExp("\#" + ii.toLowerCase() + "\#", "gi"), value).
								replace(new RegExp("\#" + ii.toUpperCase() + "\#", "gi"), value);
						}
					}
					html = html.replace(new RegExp("\#(width|height)\#", "gi"), "0").
						replace(new RegExp("\#preview_url\#", "gi"), "data:image/png;base64,");
					var attrs = { id : 'disk-edit-attach' + file.id, 'bx-agentFileId': item.id}, TR;
					if (file["XML_ID"])
						attrs["bx-attach-xml-id"] = file["XML_ID"];
					if (file["FILE_ID"])
						attrs["bx-attach-file-id"] = 'n' + file["FILE_ID"];
					if (file['FILE_TYPE'])
						attrs["bx-attach-file-type"] = file["FILE_TYPE"];
					TR = BX.create('TR', {
						attrs : attrs,
						props: { className: 'wd-inline-file'}, html: html } );
					if(!result.file.canChangeName)
					{
						var editNameBtn = BX.findChild(TR, {className : 'files-name-edit-btn'}, true);
						if(editNameBtn)
						{
							BX.remove(editNameBtn);
						}
					}

					node.parentNode.replaceChild(TR, node);
					file.element_id = file.id;
					this.onFileIs(TR, item, file);
				}
				else
				{
					this.onUploadError(item, result, this.agent);
				}
			},
			onUploadError : function(item, params, specify)
			{
				var node = this.agent.getItem(item.id);
				if (!!node && (node = node.node) && BX(node) &&
					(specify == true || !BX(node).hasAttribute("bx-disk-error")))
				{
					BX(node).setAttribute("bx-disk-error", "Y");
					BX.adjust(node, { props : { className : "error-load" } } );
					var errorText = (params && params["error"] ? params["error"] : 'Uploading error');
					node.cells[1].innerHTML = '';
					node.cells[2].innerHTML = '<span class="info-icon"></span><span class="error-text">' + errorText + '</span>';
					BX.onCustomEvent(item, 'OnFileUploadFailed', [node, this]);
					BX.onCustomEvent(this.params.controller.parentNode, 'OnFileUploadFailed', [node, this, item.file, item]);
				}
			},
			onError : function(stream, pIndex, data)
			{
				var defaultErrorText = 'Uploading error.',
					errorText = defaultErrorText, item, id;
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
							errorText.push('Uploading error.');
						errorText = errorText.join(' ');
					}
				}
				stream.files = (stream.files || {});
				for (id in stream.files)
				{
					if (stream.files.hasOwnProperty(id))
					{
						item = this.agent.queue.items.getItem(id);
						this.onUploadError(item, {error : errorText}, (errorText != defaultErrorText));
					}
				}
			},

			onBlurRenameInput : function(e)
			{
				var targetElement = e.target || e.srcElement;
				var fileEditName = BX.findChild(targetElement.parentNode, {'className' : 'files-name-edit-btn'}, true);
				if(!!fileEditName)
					BX.fireEvent(fileEditName, 'click');
			},

			bindEventsHandlers : function(row, item, file)
			{
				var id = file.element_id,
					attrName = 'file-action-control',
					filemove = BX.findChild(row, {'className' : 'files-path'}, true),
					fileEditName = BX.findChild(row, {'className' : 'files-name-edit-btn'}, true),
					filename = BX.findChild(row, {'className' : 'f-wrap'}, true);
				if (!!fileEditName && !!filemove && !!filename && !filemove.getAttribute(attrName))
				{
					filemove.setAttribute(attrName, 'enabled');
					BX.show(filemove, 'inline');
					BX.hide(BX.findChild(row, {'className' : 'files-placement'}, true));
					BX.bind(filemove, 'click', BX.delegate(function() { this.move(id, filename.innerHTML, row); }, this));
				}
				if(!!fileEditName)
				{
					BX.bind(fileEditName, 'click', BX.delegate(function() {
						BX.toggleClass(fileEditName.parentNode, 'files-name-editable');
						this.rename(id, row);
					}, this));

					var inputName = BX.findChild(row, {
						className: 'files-name-edit-inp'
					}, true);
					if(inputName)
					{
						BX.bind(inputName, 'keydown', BX.delegate(function(e) {
							var key = (e || window.event).keyCode || (e || window.event).charCode;
							if(key == 13)
							{
								BX.unbind(inputName, 'blur', BX.proxy(this.onBlurRenameInput, this));
								BX.toggleClass(fileEditName.parentNode, 'files-name-editable');
								this.rename(id, row);
								return BX.PreventDefault(e);
							}
							if(key == 27)
							{
								BX.unbind(inputName, 'blur', BX.proxy(this.onBlurRenameInput, this));
								BX.toggleClass(fileEditName.parentNode, 'files-name-editable');
								this.revertRename(id, row);
								return BX.PreventDefault(e);
							}

						}, this));
					}
				}
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
				var upgradeButton = BX.findChild(row, {'className':'transformer-upgrade-popup'}, true);
				if(upgradeButton)
				{
					BX.show(upgradeButton.parentNode);
					BX.bind(upgradeButton, 'click', BX.proxy(BX.Disk.UF.showTransformationUpgradePopup, this));
				}
			},
			deleteFile : function(row, item)
			{
				if (!!item.__disk_element_id)
				{
					BX.Disk.ajax({
						url: this.urlDeleteFile,
						method: 'POST',
						dataType: 'json',
						data: {
							attachedId: item.__disk_element_id
						},
						onsuccess: function (data)
						{}
					});

					BX.onCustomEvent(this.controller.parentNode, 'OnFileUploadRemove', [item.__disk_element_id, this]);
				}

				BX.removeCustomEvent(item, 'onFileIsAttached', this._onFileIsAttached);
				BX.removeCustomEvent(item, 'onFileIsAppended', this._onFileIsAppended);
				BX.removeCustomEvent(item, 'onFileIsBound', this._onFileIsBound);
				BX.removeCustomEvent(item, 'onUploadProgress', this._onUploadProgress);
				BX.removeCustomEvent(item, 'onUploadDone', this._onUploadDone);
				BX.removeCustomEvent(item, 'onUploadError', this._onUploadError);

				delete item.hash;
				item.deleteFile('deleteFile');

				BX.remove(row);

				this.setHiddenUfValue();
			},
			setHiddenUfValue: function()
			{
				var extendedBlock = BX.findChild(this.controller, {className : "diskuf-extended"}, true);
				BX.append(BX.create("input",{
					props : {
						name  : this.params.controlName,
						type  : "hidden",
						value : ""
					}
				}), extendedBlock);
			},
			move : function(element_id, name, row)
			{
				var urlMove = this._addUrlParam(
					this.urlSelect.replace("ACTION=SELECT", "").replace("MULTI=Y", ""),
					'ID=E' + element_id + '&NAME=' + name + '&wish=fakemove');
				while(urlMove.indexOf('&&') >= 0)
					urlMove = urlMove.replace('&&', '&');

				var nodeFileTR = (row || BX('disk-edit-attach'+element_id)),
					nodeFileName = BX.findChild(nodeFileTR, {'className':'f-wrap'}, true);

				BX.DiskFileDialog.arParams = {};
				BX.DiskFileDialog.arParams[this.dialogName] = { element_id : element_id, element_name : nodeFileName.innerHTML };

				BX.addCustomEvent(BX.DiskFileDialog, 'loadItems', this._openSection);
				//BX.addCustomEvent(BX.DiskFileDialog, 'loadItemsDone', this._checkFileName);
				BX.addCustomEvent(BX.DiskFileDialog, 'selectItem', this._selectItem);
				BX.addCustomEvent(BX.DiskFileDialog, 'unSelectItem', this._unSelectItem);

				return BX.ajax.get(urlMove, 'dialogName='+this.dialogName,
					BX.delegate(function() {
						setTimeout(BX.delegate(function() {
							BX.DiskFileDialog.obCallback[this.dialogName] = {'saveButton' : this._selectFolder};
							BX.DiskFileDialog.openDialog(this.dialogName);
							//this._checkFileName();
						}, this), 100);
					}, this)
				);
			},

			rename: function(id, row)
			{
				if(!this.urlRenameFile)
				{
					return;
				}
				var editNow = BX.findChild(row, {
					className: 'files-name-editable'
				}, true);
				var input = BX.findChild(row, {
					className: 'files-name-edit-inp'
				}, true);
				var name = BX.findChild(row, {
					className: 'f-wrap'
				}, true);
				var filename = name.textContent || name.innerText;
				var ext = filename.split('.').pop();
				var newName = input.value + '.' + ext;
				if(!!editNow)
				{
					BX.focus(input);
					BX.bind(input, 'blur', BX.proxy(this.onBlurRenameInput, this));
				}
				else if(input.value && newName !== filename)
				{
					BX.adjust(name, {text: newName});

					var agentFileId = row.getAttribute('bx-agentFileId');
					var fileObject = agentFileId? this.agent.getItem(agentFileId) : null;
					if(!!fileObject)
					{
						fileObject.item.file.name = newName;
						fileObject.item.name = newName;
					}

					var managerFileObject = this.manager? this.manager.checkFile('disk_file' + id) : null;
					if(!!managerFileObject)
					{
						managerFileObject.name = newName;
					}

					BX.Disk.ajax({
						url: this.urlRenameFile,
						method: 'POST',
						dataType: 'json',
						data: {
							newName: newName,
							attachedId: id
						},
						onsuccess: function (data)
						{}
					});
				}
			},
			revertRename: function(id, row)
			{
				if(!this.urlRenameFile)
				{
					return;
				}
				var editNow = BX.findChild(row, {
					className: 'files-name-editable'
				}, true);
				var input = BX.findChild(row, {
					className: 'files-name-edit-inp'
				}, true);
				var name = BX.findChild(row, {
					className: 'f-wrap'
				}, true);
				var filename = name.textContent || name.innerText;
				var parts = filename.split('.');
				var ext = parts.pop();

				input.value  = parts.join('.');
			},
			showMovedFile : function(element_id, arProp, section_path)
			{
				if (!element_id)
					return false;

				var MAX_PATH_LENGTH,
					id = element_id,
					parent = BX('disk-edit-attach'+id),
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
				var fileInput = BX('diskuf-doc'+id);
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
			selectFolder : function(tab, path, selected, folderByPath)
			{
				var id = false, moved = false, i, secPath, arProp;
				if ((BX.DiskFileDialog.arParams) &&
					(BX.DiskFileDialog.arParams[this.dialogName]) &&
					(BX.DiskFileDialog.arParams[this.dialogName]['element_id']))
						id = BX.DiskFileDialog.arParams[this.dialogName]['element_id'];

				var moveQuery = BX.delegate(function(id, targetFolderId, arProp, secPath){
					BX.Disk.ajax({
						method: 'POST',
						dataType: 'json',
						url: BX.Disk.addToLinkParam('/bitrix/tools/disk/uf.php', 'action', 'moveUploadedFile'),
						data: {
							attachedId: id,
							targetFolderId: targetFolderId
						},
						onsuccess: BX.delegate(function (response) {
							if(!response || response.status != 'success')
							{
								BX.Disk.showModalWithStatusAction(response);
								return;
							}

							this.showMovedFile(id, arProp, secPath);
						}, this)
					})
				}, this);

				for (i in selected)
				{
					if (selected.hasOwnProperty(i) && selected[i].type === 'folder')
					{
						secPath = tab.name + selected[i].path;
						arProp = { sectionID : i, iblockID : tab.iblock_id };

						moveQuery(id, selected[i].id, arProp, secPath);
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
						arProp.sectionID = folderByPath.id;
						if(!!folderByPath)
						{
							moveQuery(id, folderByPath.id, arProp, secPath);
						}
					}
				}
				BX.removeCustomEvent(BX.DiskFileDialog, 'loadItems', this._openSection);
				BX.removeCustomEvent(BX.DiskFileDialog, 'loadItemsDone', this._checkFileName);
				BX.removeCustomEvent(BX.DiskFileDialog, 'selectItem', this._selectItem);
				BX.removeCustomEvent(BX.DiskFileDialog, 'unSelectItem', this._unSelectItem);
			},
			checkFileName : function(name)
			{
				if (this.noticeTimeout)
				{
					clearTimeout(this.noticeTimeout);
					this.noticeTimeout = null;
				}
				if (!!name && (name != this.dialogName))
					return;

				var fname = BX.DiskFileDialog.arParams[this.dialogName]['element_name'];
				var exist = false, i;
				for (i in BX.DiskFileDialog.obItems[this.dialogName])
				{
					if (BX.DiskFileDialog.obItems[this.dialogName].hasOwnProperty(i))
					{
						if (BX.DiskFileDialog.obItems[this.dialogName][i]['name'] == fname)
							exist = true;
						if (exist)
							break;
					}
				}

				if (exist)
					BX.DiskFileDialog.showNotice(BX.message('DISK_FILE_EXISTS'), this.dialogName);
				else
					BX.DiskFileDialog.closeNotice(this.dialogName);
			},
			selectItem : function(element, itemID, name)
			{
				if (name != this.dialogName)
					return;

				var targetID = itemID.substr(1);
				var libLink = BX.DiskFileDialog.obCurrentTab[name].link;
				libLink = libLink.replace("/index.php","") + '/element/upload/' + targetID +
					'/?use_light_view=Y&AJAX_CALL=Y&SIMPLE_UPLOAD=Y&IFRAME=Y&sessid='+BX.bitrix_sessid()+
					'&SECTION_ID='+targetID+
					'&CHECK_NAME='+BX.DiskFileDialog.arParams[this.dialogName]['element_name'];
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
									BX.DiskFileDialog.showNotice(this.msg.file_exists, this.dialogName);
								} else {
									BX.DiskFileDialog.closeNotice(this.dialogName);
								}
							},
							this),
						200);
				}, this));
			},
			unSelectItem : function()
			{
				if (this.noticeTimeout) {
					clearTimeout(this.noticeTimeout);
					this.noticeTimeout = null;
				}
				this.noticeTimeout = setTimeout(
					BX.delegate(
						function() {
							this.checkFileName();
						},
						this),
					200);
			},
			openSection : function(link, name)
			{
				if (name == this.dialogName) {
					BX.DiskFileDialog.target[name] = this._addUrlParam(link, 'dialog2=Y');
				}
			},
			openSectionCloud : function(link, name)
			{
				if (name == this.dialogName) {
					BX.DiskFileDialog.target[name] = this._addUrlParam(link, 'dialog2=Y');
					BX.DiskFileDialog.target[name] = this._addUrlParam(BX.DiskFileDialog.target[name], 'cloudImport=1');
				}
			},
			selectFile : function(tab, path, selected)
			{
				var ar = [], i;
				for (i in selected)
				{
					if (selected.hasOwnProperty(i))
					{
						if (selected[i].type == 'file' && !BX(this.prefix+i))
						{
							selected[i].sizeFormatted = selected[i]["size"];
							selected[i].size = selected[i]["sizeInt"];
							if (!selected[i]["ext"])
								selected[i]["ext"] = selected[i]["name"].split('.').pop();
							if (!selected[i]["storage"])
								selected[i]["storage"] = '';
							ar.push(selected[i]);
						}
					}
				}
				this.agent.onAttach(ar, ar);

				BX.removeCustomEvent(BX.DiskFileDialog, 'loadItems', this._openSection);
			},

			selectCloudFile : function(tab, path, selected)
			{
				var ar = [], i;
				for (i in selected)
				{
					if (selected.hasOwnProperty(i))
					{
						if (selected[i].type == 'file' && !BX(this.prefix+i))
						{
							if(selected[i].hasOwnProperty('provider'))
							{
								selected[i].service = selected[i].provider;
							}
							else
							{
								selected[i].service = currentService;
							}

							selected[i].sizeFormatted = selected[i]["size"];
							selected[i].size = selected[i]["sizeInt"];
							if (!selected[i]["ext"])
								selected[i]["ext"] = selected[i]["name"].split('.').pop();
							if (!selected[i]["storage"])
								selected[i]["storage"] = '';
							ar.push(selected[i]);
						}
					}
				}
				this.agent.onAttach(ar, ar);
				BX.removeCustomEvent(BX.DiskFileDialog, 'loadItems', this._openSection);
			},

			showSelectDialog : function()
			{
				this._openSection = BX.proxy(this.openSection, this);
				this._selectFile = BX.proxy(this.selectFile, this);
				BX.addCustomEvent(BX.DiskFileDialog, 'loadItems', this._openSection);

				BX.ajax.get(this.urlSelect, 'dialogName='+this.dialogName,
					BX.delegate(function() {
						setTimeout(BX.delegate(function() {
							BX.DiskFileDialog.obCallback[this.dialogName] = {'saveButton' :this._selectFile};
							BX.DiskFileDialog.openDialog(this.dialogName);
						}, this), 10);
					}, this)
				);
			},
			showSelectDialogCloudImport : function(e)
			{
				var targetElement = e.target || e.srcElement;
				if(!BX.hasClass(targetElement, 'diskuf-selector-link-cloud')) {
					targetElement = BX.findParent(targetElement, {className: 'diskuf-selector-link-cloud'});
				}
				if(!targetElement || !targetElement.getAttribute('data-bx-doc-handler'))
					return;

				currentService = targetElement.getAttribute('data-bx-doc-handler');

				this._openSection = BX.proxy(this.openSectionCloud, this);
				this._selectFile = BX.proxy(this.selectFile, this);

				this._selectCloudFile = BX.proxy(this.selectCloudFile, this);

				BX.addCustomEvent(BX.DiskFileDialog, 'loadItems', this._openSection);

				BX.ajax.get(this.urlSelect, '&cloudImport=1&service=' + currentService + '&dialogName='+this.dialogName,
					BX.delegate(function() {
						setTimeout(BX.delegate(function() {
							BX.DiskFileDialog.obCallback[this.dialogName] = {'saveButton' :this._selectCloudFile};
							BX.DiskFileDialog.openDialog(this.dialogName);
						}, this), 10);
					}, this)
				);
			}
		};
		return UF;
	})();
	BX.Disk.UF.dndCatcher = {};
	BX.Disk.UF.add = function(params)
	{
		params["controller"] = BX('diskuf-selectdialog-' + params['UID']);
		if (params["controller"] && BX.isNodeInDom(params["controller"]))
		{
			params['values'] = BX.findChildren(params["controller"], {"className": "wd-inline-file"}, true);
			var node = BX(params['controller']).parentNode;
			BX.onCustomEvent(params['controller'], "DiskLoadFormControllerWasBound", [params, 'DiskLoadFormControllerWasBound']);
			if (!BX(params['controller']).hasAttribute("bx-disk-load-is-bound"))
			{
				BX(params['controller']).setAttribute("bx-disk-load-is-bound", "Y");
				BX.addCustomEvent(node, "DiskLoadFormController", function (status)
				{
					BX.Disk.UF.initialize(status, params);
				});
			}
			if (!!params['values'] && params['values'].length > 0 && !params['hideSelectDialog'])
				BX.onCustomEvent(params['controller'].parentNode, 'DiskLoadFormController', ['show']);
		}
	};
	BX.Disk.UF.initialize = function(status, params)
	{
		status = (status === 'show' || status === 'hide' ? status : (params['controller'].style.display != 'none' ? 'hide' : 'show'));

		if (! params['controller'].loaded)
		{
			params['controller'].loaded = true;
			repo[params['UID']] = new BX.Disk.UF(params);
		}
		if (status == 'show')
		{
			if (params['controller'].style.display != 'block')
			{
				BX.fx.show(params['controller'], 'fade', {time:0.2});
				if (params['switcher'] && params['switcher'].style.display != 'none')
					BX.fx.hide(params['switcher'], 'fade', {time:0.1});
				BX.onCustomEvent(params['controller'], "onControllerIsShown", [params['controller'], repo[params['UID']]]);
				currentDialog = repo[params['UID']];
			}
		}
		else if (params['controller'].style.display != 'none')
		{
			currentDialog = null;
			BX.fx.hide(params['controller'], 'fade', {time:0.2});
			BX.onCustomEvent(params['controller'], "onControllerIsHidden", [params['controller'], repo[params['UID']]]);
		}
		return repo[params['UID']];
	};

	var getBreadcrumbsByAttachedObject = function(attachedId) {
		return BX.Disk.ajaxPromise({
			url: BX.Disk.addToLinkParam('/bitrix/tools/disk/uf.php', 'action', 'getBreadcrumbs'),
			method: 'POST',
			dataType: 'json',
			data: {
				attachedId: attachedId
			}
		});
	};

	var __preview = function(img)
	{
		if (!BX(img) || img.hasAttribute("bx-is-bound"))
			return;
		img.setAttribute("bx-is-bound", "Y");

		this.img = img;
		this.node = img.parentNode.parentNode.parentNode;

		BX.unbindAll(img);
		BX.unbindAll(this.node);

		BX.show(this.node);
		BX.remove(this.node.nextSibling);
		this.id = 'wufdp_' + Math.random();
		// BX.bind(this.node, "mouseover", BX.delegate(function(){this.turnOn();}, this));
		// BX.bind(this.node, "mouseout", BX.delegate(function(){this.turnOff();}, this));
	};
	__preview.prototype =
	{
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
				var props = {
						width : this.img.naturalWidth,
						height : this.img.naturalHeight
					};
				if (BX["UploaderUtils"])
				{
					var res2 = BX.UploaderUtils.scaleImage(props, {
							width : parseInt(BX.message("DISK_THUMB_WIDTH")),
							height : parseInt(BX.message("DISK_THUMB_HEIGHT"))
						});
					props = res2.destin;
				}
				this.popup = new BX.PopupWindow('bx-wufd-preview-img-' + this.id, this.img.parentNode,
					{
						lightShadow : true,
						offsetTop: -7,
						offsetLeft: (51-28)/2 + 14,
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
								props: props,
								children : [
									BX.create(
										"IMG",
										{
											props : props,
											attrs: {
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
	BX.addCustomEvent('onDiskPreviewIsReady', function(img) { new __preview(img); });

BX.Disk.UF.runImport = function(params)
{
	BX.Disk.showActionModal({text: BX.message('DISK_UF_FILE_STATUS_PROCESS_LOADING'), showLoaderIcon: true, autoHide: false});

	BX.Disk.ExternalLoader.reloadLoadAttachedObject({
		attachedObject: {
			id: params.id,
			name: params.name,
			service: params.service
		},

		onFinish: BX.delegate(function(newData){
			if(newData.hasOwnProperty('hasNewVersion') && !newData.hasNewVersion)
			{
				BX.Disk.showActionModal({text: BX.message('DISK_UF_FILE_STATUS_HAS_LAST_VERSION'), showSuccessIcon: true, autoHide: true});
			}
			else if(newData.status === 'success')
			{
				BX.Disk.showActionModal({text: BX.message('DISK_UF_FILE_STATUS_SUCCESS_LOADING'), showSuccessIcon: true, autoHide: true});
			}
			else
			{
				BX.Disk.showActionModal({text: BX.message('DISK_UF_FILE_STATUS_FAIL_LOADING'), autoHide: true});
			}
		}, this),
		onProgress: BX.delegate(function(progress){

		}, this)
	}).start();

};

BX.Disk.UF.setDocumentHandlers = function(handlers)
{
	this.documentHandlers = handlers;
};

BX.Disk.UF.getDocumentHandler = function(code)
{
	if (!this.documentHandlers)
	{
		return '';
	}

	var handler = this.documentHandlers.find(function (handler) {
		return handler.code === code;
	});

	return handler || {};
};

BX.Disk.UF.getDocumentHandlers = function(handlers)
{
	return this.documentHandlers;
};

BX.Disk.UF.disableAutoCommentToAttachedObject = function(params)
{
	var attachedId = params.attachedId;
	BX.Disk.ajax({
		method: 'POST',
		dataType: 'json',
		url: BX.Disk.addToLinkParam('/bitrix/tools/disk/uf.php', 'action', 'disableAutoCommentToAttachedObject'),
		data: {
			attachedId: attachedId
		},
		onsuccess: BX.delegate(function (response) {
			//BX.Disk.showModalWithStatusAction(response);
		}, this)
	});

};
BX.Disk.UF.enableAutoCommentToAttachedObject = function(params)
{
	var attachedId = params.attachedId;
	BX.Disk.ajax({
		method: 'POST',
		dataType: 'json',
		url: BX.Disk.addToLinkParam('/bitrix/tools/disk/uf.php', 'action', 'enableAutoCommentToAttachedObject'),
		data: {
			attachedId: attachedId
		},
		onsuccess: BX.delegate(function (response) {
			//BX.Disk.showModalWithStatusAction(response);
		}, this)
	});

};

BX.Disk.UF.showTransformationUpgradePopup = function(event)
{
	B24.licenseInfoPopup.show(
		'disk_transformation_video_limit',
		BX.message('DISK_UF_CONTROLLER_TRANSFORMATION_UPGRADE_POPUP_TITLE'),
		BX.message('DISK_UF_CONTROLLER_TRANSFORMATION_UPGRADE_POPUP_CONTENT'),
		false
	);
};

BX.Disk.UFShowController = function(params) {
	if (!BX.type.isPlainObject(params))
	{
		params = {};
	}

	this.entityType = (BX.type.isNotEmptyString(params.entityType) ? params.entityType : '');
	this.entityId = (parseInt(params.entityId) > 0 ? params.entityId : '');
	this.signedParameters = (BX.type.isNotEmptyString(params.signedParameters) ? params.signedParameters : '');
	this.loader = null;

	this.container = (
		BX.type.isNotEmptyString(params.nodeId)
			? document.getElementById(params.nodeId)
			: null
	);

	if (this.container)
	{
		var
			toggleViewlink = this.container.querySelector('.disk-uf-file-switch-control');

		if (toggleViewlink)
		{
			BX.Event.bind(toggleViewlink, 'click', BX.Disk.UFShowController.onToggleView);
		}
	}

	if (BX.type.isNotEmptyString(params.nodeId))
	{
		showRepo[params.nodeId] = this;
	}
};

BX.Disk.UFShowController.getInstance = function(nodeId)
{
	return (
		BX.type.isNotEmptyString(nodeId)
		&& showRepo[nodeId]
			? showRepo[nodeId]
			: null
	);
};

BX.Disk.UFShowController.onToggleView = function(event)
{
	var
		container = event.currentTarget.closest('.diskuf-files-toggle-container'),
		viewType = event.currentTarget.getAttribute('data-bx-view-type');

	if (
		!BX.type.isDomNode(container)
		|| !BX.type.isNotEmptyString(container.id)
	)
	{
		return;
	}

	var
		controller = BX.Disk.UFShowController.getInstance(container.id);

	if (controller)
	{
		controller.toggleViewType({
			viewType: viewType
		});
	}

	event.preventDefault();
};

BX.Disk.UFShowController.prototype.toggleViewType = function(params)
{
	this.showToggleViewLoader();

	BX.ajax.runComponentAction('bitrix:disk.uf.file', 'toggleViewType', {
		mode: 'class',
		signedParameters: this.signedParameters,
		data: {
			params: {
				viewType: params.viewType
			}
		}
	}).then(function(response) {
		this.hideToggleViewLoader();
		BX.clean(this.container);
		BX.html(this.container, response.data.html);

	}.bind(this), function(response) {

		this.hideToggleViewLoader();

	});
};

BX.Disk.UFShowController.prototype.showToggleViewLoader = function(params)
{
	this.container.classList.add('diskuf-files-toggle-container-active');

	this.loader = new BX.Loader({
		target: this.container
	});
	this.loader.show();
};

BX.Disk.UFShowController.prototype.hideToggleViewLoader = function(params)
{
	this.container.classList.remove('diskuf-files-toggle-container-active');

	if (this.loader)
	{
		this.loader.destroy();
	}
};

	window.DiskCreateDocument = function(documentType, event)
	{
		event.preventDefault();

		if (!BX.Disk.getDocumentService())
		{
			BX.Disk.saveDocumentService('l');
		}

		if (!currentDialog)
		{
			return;
		}
		if (!documentType)
		{
			return;
		}

		var insertDocumentIntoUf = function (extendedFileData) {
			var parts = extendedFileData.object.name.split('.');
			parts.pop();

			var data = {};
			data['E' + extendedFileData.object.id] = {
				type: 'file',
				id: 'n' + extendedFileData.object.id,
				name: extendedFileData.object.name,
				label: parts.join('.'),
				storage: extendedFileData.folderName,
				size: extendedFileData.object.size,
				sizeInt: extendedFileData.object.sizeInt,
				ext: extendedFileData.object.extension,
				canChangeName: true,
				link: extendedFileData.link
			};
			setTimeout(function(){
				currentDialog.selectFile({}, {}, data);
			}, 200);
		};

		if (BX.Disk.Document.Local.Instance.isSetWorkWithLocalBDisk())
		{
			BX.Disk.Document.Local.Instance.createFile({
				type: documentType
			}).then(function (response) {
				console.log('local createFile', response);

				insertDocumentIntoUf(response);
			}.bind(this));

			return;
		}

		var createProcess = new BX.Disk.Document.CreateProcess({
			typeFile: documentType,
			serviceCode: BX.Disk.getDocumentService(),
			onAfterSave: function(response, extendedFileData) {
				if (response.status !== 'success')
				{
					return;
				}

				insertDocumentIntoUf(extendedFileData);
			}
		});

		createProcess.start();
	};

	window.DiskOpenMenuCreateService = function(targetElement)
	{
		var items = [
			(BX.Disk.Document.Local.Instance.isEnabled()? {
				text: BX.message('DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT'),
				className: "bx-viewer-popup-item item-b24",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('l');

					BX.adjust(targetElement, {text: BX.message('DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT')});
				}
			}: null),
			{
				text: BX.Disk.UF.getDocumentHandler('gdrive').name,
				className: "bx-viewer-popup-item item-gdocs",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('gdrive');

					BX.adjust(targetElement, {text: BX.Disk.UF.getDocumentHandler('gdrive').name});
				}
			},
			{
				text: BX.Disk.UF.getDocumentHandler('office365').name,
				className: "bx-viewer-popup-item item-office365",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('office365');

					BX.adjust(targetElement, {text: BX.Disk.UF.getDocumentHandler('office365').name});
				}
			},
			{
				text: BX.Disk.UF.getDocumentHandler('onedrive').name,
				className: "bx-viewer-popup-item item-office",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('onedrive');

					BX.adjust(targetElement, {text: BX.Disk.UF.getDocumentHandler('onedrive').name});
				}
			}
		];

		BX.PopupMenu.show('disk_open_menu_with_services', BX(targetElement), items,
			{
				offsetTop: 0,
				offsetLeft: 25,
				angle: {
					position: 'top',
					offset: 45
				},
				autoHide: true,
				zIndex: 10000,
				overlay: {
					opacity: 0.01
				},
				events : {}
			}
		);
	};

	window.DiskOpenMenuImportService = function(targetElement, listCloudStorages)
	{
		var list = [];
		for(var i in listCloudStorages)
		{
			if(!listCloudStorages.hasOwnProperty(i))
				continue;

			list.push({
				text: listCloudStorages[i].name,
				code: listCloudStorages[i].id,
				href: "#",
				onclick: function (e, item)
				{
					var helpItem = item.layout.item;
					BX.addClass(helpItem, 'diskuf-selector-link-cloud');
					helpItem.setAttribute('data-bx-doc-handler', item.code);
					BX.onCustomEvent('onManualChooseCloudImport', [{
						target: helpItem
					}]);
					BX.removeClass(helpItem, 'diskuf-selector-link-cloud');
					helpItem.removeAttribute('data-bx-doc-handler');

					BX.PopupMenu.destroy('disk_open_menu_with_import_services');

					return BX.PreventDefault(e);
				}
			});
		}

		var obElementViewer = new BX.CViewer({});
		obElementViewer.openMenu('disk_open_menu_with_import_services', BX(targetElement), list, {
			offsetTop: 0,
			offsetLeft: 25
		});

		return BX.PreventDefault();
	};

	window.DiskActionFileMenu = function(id, bindElement, buttons)
	{
		diskufMenuNumber++;
		BX.PopupMenu.show('bx-viewer-wd-popup' + diskufMenuNumber + '_' + id, BX(bindElement), buttons,
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
})(window);
