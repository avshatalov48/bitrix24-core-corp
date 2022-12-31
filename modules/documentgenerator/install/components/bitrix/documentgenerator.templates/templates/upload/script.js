;(function(){

BX.namespace('BX.DocumentGenerator');

BX.DocumentGenerator.UploadTemplate = {
	currentUrl: null,
	fileInputNode: 'upload-template-button',
	fileInputTextNode: 'upload-template-text',
	fileInputDropZone: 'upload-template-dragndrop-zone',
	providerBlockNode: 'add-template-provider-block',
	nameBlockNode: 'add-template-name-block',
	activeBlockNode: 'add-template-active-block',
	userBlockNode: 'add-template-user-block',
	numeratorSelectNode: 'docs-template-num-select',
	productsTableVariantSelectNode: 'docs-template-products-table-variant-select',
	numeratorBlockNode: 'add-template-numerator-block',
	regionBlockNode: 'add-template-region-block',
	fileBlockNode: 'upload-template-file-block',
	uploadBlockNode: 'upload-template-upload-block',
	buttonsBlockNode: 'add-template-buttons-block',
	saveButton: 'add-template-save-button',
	cancelButton: 'add-template-cancel-button',
	progressMessageNode: 'upload-template-progress-message',
	errorMessageNode: 'upload-template-error-message',
	successMessageNode: 'upload-template-success-message',
	fileId: null,
	userSelectorId: 'add-template-users',
	userListContainer: 'add-template-user-list-container',
	providers: {},
	providerSelectorId: 'add-template-providers',
	providerListContainer: 'add-template-providers-container',
	providerListShowContainer: 'container-add-template-providers',
	providerListPopupContainer: 'container-add-template-providers-popup',
	regionSelectorId: 'docs-template-region-select',
	nameInputNode: 'add-template-name-input',
	activeInputNode: 'add-template-active-input',
	stampsInputNode: 'add-template-stamps-input',
	deleteFileNode: 'upload-template-delete-file',
	templateId: null,
	moduleId: '',
	downloadUrl: null,
	defaultCode: null,
	popupConfirm: null,
	loader: null,
	progress: false,
	regions: {},
	addRegionUrl: null,
	addRegionNode: 'docs-template-region-create-btn',
	editRegionNode: 'docs-template-region-edit-btn',
	closeDragZoneNode: 'upload-template-dragndrop-cancel',
};

BX.DocumentGenerator.UploadTemplate.initUploader = function(uploadUrl)
{
	var uploader = BX.Uploader.getInstance({
		id: 'template-add',
		streams: 1,
		uploadFileUrl: uploadUrl,
		uploadMethod: "immediate",
		showImage: false,
		sortItems: false,
		input: BX(this.fileInputNode),
		dropZone: BX(this.fileInputDropZone),
		uploadFormData: "N",
		allowUploadExt: 'docx'
	});

	this.setInputNodeAcceptAttribute();
	BX.addCustomEvent(uploader, "onFileinputIsReinited", this.setInputNodeAcceptAttribute);

	BX.addCustomEvent(uploader, "onFileIsInited", this.initFile);
	BX.addCustomEvent(uploader, "onFileIsUploaded", this.onFileUploadDone);
	BX.addCustomEvent(uploader, "onFileIsUploadedWithError", this.onUploadError);

	return uploader;
};

BX.DocumentGenerator.UploadTemplate.init = function(params)
{
	BX.hide(BX(this.fileInputNode));
	var topSlider = BX.SidePanel.Instance.getTopSlider();
	if(topSlider)
	{
		this.currentUrl = topSlider.getUrl();
	}

	if(params.uploadUrl)
	{
		this.uploader = this.initUploader(params.uploadUrl);
	}

	this.addRegionUrl = params.addRegionUrl || null;

	this.downloadUrl = params.downloadUrl || null;
	this.defaultCode = params.defaultCode || null;
	BX.bind(BX('upload-template-download-file'), 'click', BX.proxy(function(event)
	{
		event.preventDefault();
		if(this.downloadUrl)
		{
			window.open(this.downloadUrl, '_blank');
		}
	}, this));
	BX.bind(BX('upload-template-reinstall'), 'click', BX.proxy(function(event)
	{
		if(this.progress)
		{
			return;
		}
		event.preventDefault();
		if(this.defaultCode)
		{
			if(this.popupConfirm)
			{
				this.popupConfirm.destroy();
			}
			this.popupConfirm = new BX.PopupWindow('crm-document-reinstall-template-popup', null, {
				zIndex: 200,
				autoHide: true,
				closeByEsc: true,
				buttons: [
					new BX.PopupWindowButton({
						text : BX.message('DOCGEN_TEMPLATE_ADD_INSTALL'),
						className : "popup-window-button-accept",
						events :
						{
							click: BX.proxy(this.reinstallTemplate, this)
						}
					}),
					new BX.PopupWindowButtonLink({
						text : BX.message('DOCGEN_TEMPLATE_ADD_CANCEL'),
						className : "popup-window-button-decline",
						events : { click : function() { this.popupWindow.close(); } }
					})
				],
				overlay : true,
				events : { onPopupClose : function() { this.destroy() }, onPopupDestroy : BX.delegate(function() { this.popupConfirm = null }, this)},
				content : BX.message('DOCGEN_TEMPLATE_ADD_REINSTALL_CONFIRM'),
				titleBar: BX.message('DOCGEN_TEMPLATE_ADD_REINSTALL_CONFIRM_TITLE'),
				contentColor: 'white'
			});
			this.popupConfirm.show();
		}
	}, this));

	BX.bind(BX(this.fileInputTextNode), 'click', BX.proxy(function()
	{
		BX.fireEvent(BX(this.fileInputNode), 'click')
	}, this));

	BX.bind(BX(this.cancelButton), 'click', this.onClickCancel);

	BX.addCustomEvent("SidePanel.Slider:onCloseComplete", this.onSliderClose);

	this.initUserSelector();
	this.initProviderSelector();

	BX.bind(BX(this.saveButton), 'click', BX.proxy(this.onClickSave, this));
	var urlNumEdit = BX.util.add_url_param("/bitrix/components/bitrix/main.numerator.edit/slider.php", {NUMERATOR_TYPE: 'DOCUMENT'});
	BX.bind(BX('docs-template-numerator-edit-btn'), 'click', BX.proxy(function (event)
	{
		event.preventDefault();
		var url = BX.util.add_url_param(urlNumEdit, {ID: BX(this.numeratorSelectNode).value});
		if(BX.SidePanel)
		{
			BX.SidePanel.Instance.open(url, {width: 480});
		}
		else
		{
			location.href = url;
		}
	}, this));

	BX.bind(BX('docs-template-numerator-create-btn'), 'click', BX.proxy(function(event)
	{
		event.preventDefault();
		if(BX.SidePanel)
		{
			BX.SidePanel.Instance.open(urlNumEdit, {width: 480, cacheable: false});
		}
		else
		{
			location.href = urlNumEdit;
		}
	}, this));

	if(this.addRegionUrl)
	{
		BX.bind(BX(this.addRegionNode), 'click', BX.proxy(function(event)
		{
			event.preventDefault();
			if(BX.SidePanel)
			{
				BX.SidePanel.Instance.open(this.addRegionUrl, {
					width: 480,
					cacheable: false,
					events: {
						onClose: BX.DocumentGenerator.UploadTemplate.reloadRegions
					}
				});
			}
			else
			{
				location.href = this.addRegionUrl;
			}
		}, this));
	}

	BX.bind(BX(this.deleteFileNode), 'click', BX.proxy(function(){
		BX.show(BX(this.uploadBlockNode));
		BX.hide(BX(this.fileBlockNode));
		if(this.fileId > 0)
		{
			BX.show(BX(this.closeDragZoneNode));
		}
		else
		{
			BX.hide(BX(this.closeDragZoneNode));
		}
	}, this));

	BX.bind(BX(this.closeDragZoneNode), 'click', BX.proxy(function(){
		BX.show(BX(this.fileBlockNode));
		BX.hide(BX(this.uploadBlockNode));
	}, this));

	BX.bind(BX(this.regionSelectorId), 'change', BX.proxy(function()
	{
		if(this.regions[BX(this.regionSelectorId).value] && this.regions[BX(this.regionSelectorId).value].id > 0)
		{
			BX.show(BX(this.editRegionNode));
		}
		else
		{
			BX.hide(BX(this.editRegionNode));
		}
	}, this));

	BX.bind(BX(this.editRegionNode), 'click', BX.proxy(function(event)
	{
		event.preventDefault();
		var editUrl, regionId = false;
		if(this.regions[BX(this.regionSelectorId).value] && this.regions[BX(this.regionSelectorId).value].id > 0)
		{
			regionId = this.regions[BX(this.regionSelectorId).value].id;
		}
		if(regionId)
		{
			editUrl = BX.util.add_url_param(this.addRegionUrl, {id: regionId});
		}
		else
		{
			editUrl = this.addRegionUrl;
		}
		if(BX.SidePanel)
		{
			BX.SidePanel.Instance.open(editUrl, {
				width: 480,
				cacheable: false,
				events: {
					onClose: BX.DocumentGenerator.UploadTemplate.reloadRegions
				}
			});
		}
		else
		{
			location.href = editUrl;
		}
	}, this));

	BX.addCustomEvent('SidePanel.Slider:onMessage', function(message)
	{
		if (message.getEventId() === 'numerator-saved-event')
		{
			var numeratorData = message.getData();
			var numSelect = BX(BX.DocumentGenerator.UploadTemplate.numeratorSelectNode);
			if (numSelect)
			{
				var options = numSelect.options;
				var isNew = true;
				for (var i = 0; i < options.length; i++)
				{
					var option = options[i];
					if (option.value === numeratorData.id)
					{
						isNew = false;
						option.innerText = numeratorData.name;
					}
				}
				if (isNew)
				{
					numSelect.appendChild(BX.create('option', {
						attrs: {value: numeratorData.id},
						text: numeratorData.name
					}, this.iframe.contentDocument));
				}
				numSelect.value = numeratorData.id;
			}
		}
	});
};

BX.DocumentGenerator.UploadTemplate.initFile = function(id, item)
{
	if(!BX.DocumentGenerator.UploadTemplate.templateId)
	{
		BX.DocumentGenerator.UploadTemplate.deletePreviousFile();
	}
	BX.addCustomEvent(item, "onUploadStart", BX.DocumentGenerator.UploadTemplate.onUploadProgress);
	BX.addCustomEvent(item, 'onUploadProgress', BX.DocumentGenerator.UploadTemplate.onUploadProgress);
	BX.hide(BX(BX.DocumentGenerator.UploadTemplate.successMessageNode));
};

BX.DocumentGenerator.UploadTemplate.removeFileEvents = function(item)
{
	BX.removeCustomEvent(item, "onUploadStart", BX.DocumentGenerator.UploadTemplate.onUploadProgress);
	BX.removeCustomEvent(item, 'onUploadProgress', BX.DocumentGenerator.UploadTemplate.onUploadProgress);
};

BX.DocumentGenerator.UploadTemplate.onUploadProgress = function()
{
	BX(BX.DocumentGenerator.UploadTemplate.progressMessageNode).innerText = BX.message('DOCGEN_TEMPLATE_ADD_PROGRESS');
	BX.show(BX(BX.DocumentGenerator.UploadTemplate.progressMessageNode));
	BX.hide(BX(BX.DocumentGenerator.UploadTemplate.errorMessageNode));
};

BX.DocumentGenerator.UploadTemplate.onFileUploadDone = function(id, item, data)
{
	BX(BX.DocumentGenerator.UploadTemplate.progressMessageNode).innerText = BX.message('DOCGEN_TEMPLATE_ADD_COMPLETE');
	BX.show(BX(BX.DocumentGenerator.UploadTemplate.progressMessageNode));
	BX.hide(BX(BX.DocumentGenerator.UploadTemplate.errorMessageNode));
	if(data.status === 'uploaded')
	{
		BX.DocumentGenerator.UploadTemplate.fileId = data.file.FILE_ID;
		if(!BX(BX.DocumentGenerator.UploadTemplate.nameInputNode).value)
		{
			BX(BX.DocumentGenerator.UploadTemplate.nameInputNode).value = data.file.name;
		}
		BX('upload-template-file-name').innerText = item.name || '';
		BX('upload-template-file-size').innerText = item.size || '';
		BX.show(BX(BX.DocumentGenerator.UploadTemplate.providerBlockNode));
		BX.show(BX(BX.DocumentGenerator.UploadTemplate.nameBlockNode));
		BX.show(BX(BX.DocumentGenerator.UploadTemplate.activeBlockNode));
		BX.show(BX(BX.DocumentGenerator.UploadTemplate.userBlockNode));
		BX.show(BX(BX.DocumentGenerator.UploadTemplate.buttonsBlockNode));
		BX.show(BX(BX.DocumentGenerator.UploadTemplate.regionBlockNode));
		BX.show(BX(BX.DocumentGenerator.UploadTemplate.numeratorBlockNode));
		BX.show(BX(BX.DocumentGenerator.UploadTemplate.fileBlockNode));
		BX.hide(BX(BX.DocumentGenerator.UploadTemplate.uploadBlockNode));
	}
};

BX.DocumentGenerator.UploadTemplate.onUploadError = function(id, item, params)
{
	BX.DocumentGenerator.UploadTemplate.removeFileEvents(item);
	BX.DocumentGenerator.UploadTemplate.showUploadError(params.error);
};

BX.DocumentGenerator.UploadTemplate.showUploadError = function(text)
{
	if(text)
	{
		BX(BX.DocumentGenerator.UploadTemplate.errorMessageNode).innerHTML = text;
	}
	BX.show(BX(BX.DocumentGenerator.UploadTemplate.errorMessageNode));
	BX.hide(BX(BX.DocumentGenerator.UploadTemplate.progressMessageNode));
};

BX.DocumentGenerator.UploadTemplate.setInputNodeAcceptAttribute = function()
{
	BX(BX.DocumentGenerator.UploadTemplate.fileInputNode).setAttribute('accept', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
};

BX.DocumentGenerator.UploadTemplate.onClickCancel = function(event)
{
	event.preventDefault();
	var slider = BX.SidePanel.Instance.getTopSlider();
	if(slider)
	{
		slider.close();
	}
};

BX.DocumentGenerator.UploadTemplate.onClickSave = function(event)
{
	event.preventDefault();
	var errors = [];
	if(!this.fileId)
	{
		errors.push(BX.message('DOCGEN_TEMPLATE_ADD_ERROR_FILE'));
	}
	var providers = this.providerSelector.getValue();
	if(!providers || providers.length === 0)
	{
		errors.push(BX.message('DOCGEN_TEMPLATE_ADD_ERROR_PROVIDER'));
	}
	var active = 'N', stamps = 'N';
	if(BX(this.activeInputNode).checked)
	{
		active = 'Y';
	}
	if(BX(this.stampsInputNode).checked)
	{
		stamps = 'Y';
	}
	var name = BX(this.nameInputNode).value;
	if(!name || name.length < 1)
	{
		errors.push(BX.message('DOCGEN_TEMPLATE_ADD_ERROR_NAME'));
	}
	var users = this.userSelector.getValue();
	if(errors.length > 0)
	{
		this.showUploadError(errors.join("<br />"));
	}
	else
	{
		BX.hide(BX(this.errorMessageNode));
		this.startProgress();
		var analyticsLabel, method, data;
		data = {
			fields: {
				fileId: this.fileId,
				providers: providers,
				name: name,
				numeratorId: BX(this.numeratorSelectNode).value,
				productsTableVariant: BX(this.productsTableVariantSelectNode).value,
				users: users,
				active: active,
				withStamps: stamps,
				moduleId: this.moduleId,
				region: BX(this.regionSelectorId).value
			}
		};
		if(this.templateId > 0)
		{
			analyticsLabel = 'editTemplate';
			method = 'documentgenerator.api.template.update';
			data.id = this.templateId;
		}
		else
		{
			analyticsLabel = 'addTemplate';
			method = 'documentgenerator.api.template.add';
		}
		BX.ajax.runAction(method, {
			analyticsLabel: analyticsLabel,
			data: data
		}).then(function(response)
		{
			BX.DocumentGenerator.UploadTemplate.stopProgress();
			BX.DocumentGenerator.UploadTemplate.reInitForm();
			var slider = BX.SidePanel.Instance.getTopSlider();
			if(slider)
			{
				BX.SidePanel.Instance.postMessage(slider, 'documentgenerator-add-template', {templateId: response.data.template.id});
				slider.close();
			}
			else
			{
				BX.show(BX(BX.DocumentGenerator.UploadTemplate.successMessageNode));
			}
		}, function(response)
		{
			BX.DocumentGenerator.UploadTemplate.stopProgress();
			BX.DocumentGenerator.UploadTemplate.showUploadError(response.errors.pop().message);
		});
	}
};

BX.DocumentGenerator.UploadTemplate.deletePreviousFile = function(callback)
{
	if(!BX.type.isFunction(callback))
	{
		callback = BX.DoNothing;
	}
	if(BX.DocumentGenerator.UploadTemplate.fileId)
	{
		BX.ajax.runAction('documentgenerator.api.file.delete', {
			data: {
				fileId: BX.DocumentGenerator.UploadTemplate.fileId
			}
		}).then(callback);
	}
	else
	{
		callback();
	}
};

BX.DocumentGenerator.UploadTemplate.onSliderClose = function(event)
{
	var slider = event.getSlider();
	if(slider.getUrl() === BX.DocumentGenerator.UploadTemplate.currentUrl)
	{
		BX.DocumentGenerator.UploadTemplate.reInitForm();
	}
};

BX.DocumentGenerator.UploadTemplate.reInitForm = function()
{
	if(!BX.DocumentGenerator.UploadTemplate.templateId)
	{
		BX.DocumentGenerator.UploadTemplate.deletePreviousFile();
		BX.DocumentGenerator.UploadTemplate.fileId = null;
		BX(BX.DocumentGenerator.UploadTemplate.nameInputNode).value = '';
		BX.DocumentGenerator.UploadTemplate.providerSelector.purge();
		BX.DocumentGenerator.UploadTemplate.userSelector.purge();
		try
		{
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.errorMessageNode));
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.progressMessageNode));
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.providerBlockNode));
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.nameBlockNode));
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.activeBlockNode));
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.userBlockNode));
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.numeratorBlockNode));
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.buttonsBlockNode));
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.regionBlockNode));
			BX.hide(BX(BX.DocumentGenerator.UploadTemplate.fileBlockNode));
			BX.show(BX(BX.DocumentGenerator.UploadTemplate.uploadBlockNode));
			if (this.providerPopup)
			{
				this.providerPopup.close();
			}
		}
		catch(e){}
	}
};

BX.DocumentGenerator.UploadTemplate.initProviderPopup = function()
{
	var checked, providers = this.providerSelector.getValue();
	var self = BX.DocumentGenerator.UploadTemplate;
	var content = '<div class="menu-popup"><div class="menu-popup-items">';
	for(var key in self.providers)
	{
		checked = '';
		if(self.providers.hasOwnProperty(key))
		{
			if(providers.indexOf(key) !== -1)
			{
				checked = ' checked';
			}
			content += '<span class="docs-template-load-popup-item">' +
				'<input class="docs-template-load-popup-input" type="checkbox"' + checked + ' id="provider-check-' + key + '" value="' + self.providers[key].CLASS + '" onchange="BX.DocumentGenerator.UploadTemplate.onChangeProvider(this);">' +
				'<label class="docs-template-load-popup-label" for="provider-check-' + key + '">' + BX.Text.encode(self.providers[key].NAME) + '</label>' +
				'</span>'
		}
	}
	content += '</div></div>';

	self.providerPopup = new BX.PopupWindow(self.providerListPopupContainer, BX(self.providerBlockNode), {
		content: content,
		className: 'docs-template-load-providers-popup',
		autoHide: true,
		closeByEsc: true,
		offsetLeft: 0,
		offsetTop: 5
	});

	BX.bind(BX(self.providerListShowContainer), 'click', BX.proxy(function()
	{
		this.providerPopup.show();
	}, self));
};

BX.DocumentGenerator.UploadTemplate.onChangeProvider = function(item)
{
	if(item.checked)
	{
		BX.DocumentGenerator.UploadTemplate.providerSelector.add(item.value, item.nextSibling.innerText);
	}
	else
	{
		BX.DocumentGenerator.UploadTemplate.providerSelector.delete(item.value);
	}
};

BX.DocumentGenerator.UploadTemplate.initProviderSelector = function()
{
	BX.DocumentGenerator.UploadTemplate.providerSelector = new BX.DocumentGenerator.UploadTemplate.Selector({
		id: BX.DocumentGenerator.UploadTemplate.providerSelectorId,
		multiple: true,
		onAfterDelete: BX.DocumentGenerator.UploadTemplate.unsetProviderCheckbox
	});
};

BX.DocumentGenerator.UploadTemplate.initUserSelector = function()
{
	BX.DocumentGenerator.UploadTemplate.userSelector = new BX.DocumentGenerator.UploadTemplate.Selector({
		id: BX.DocumentGenerator.UploadTemplate.userSelectorId,
		multiple: true,
		onAfterDelete: BX.DocumentGenerator.UploadTemplate.deleteFromSocNetLogDest
	});

	BX.onCustomEvent(window, 'BX.DocumentGenerator.UploadTemplate:init', [{
		id: BX.DocumentGenerator.UploadTemplate.userSelectorId,
		openDialogWhenInit: false,
		single: false
	}]);
};

BX.DocumentGenerator.UploadTemplate.openUserSelector = function(bindNode)
{
	BX.onCustomEvent(window, 'BX.DocumentGenerator.UploadTemplate:open', [{
		id: BX.DocumentGenerator.UploadTemplate.userSelectorId,
		contId: bindNode,
		bindId: bindNode,
		tagId: BX.DocumentGenerator.UploadTemplate.userSelectorId,
		bindNode: bindNode
	}]);
};

BX.DocumentGenerator.UploadTemplate.onSelectUser = function(user)
{
	var userId = user.id;
	BX.DocumentGenerator.UploadTemplate.userSelector.add(userId, BX.util.htmlspecialcharsback(user.name));
};

BX.DocumentGenerator.UploadTemplate.onUnSelectUser = function(user)
{
	var userId = user.id;
	BX.DocumentGenerator.UploadTemplate.userSelector.delete(userId);
};

BX.DocumentGenerator.UploadTemplate.unsetProviderCheckbox = function ()
{
	var templateProviders = BX.DocumentGenerator.UploadTemplate.providerSelector.getValue();
	for(var id in BX.DocumentGenerator.UploadTemplate.providers)
	{
		if(BX.DocumentGenerator.UploadTemplate.providers.hasOwnProperty(id))
		{
			if(BX('provider-check-' + id).checked && templateProviders.indexOf(id) === -1)
			{
				BX('provider-check-' + id).checked = false;
			}
		}
	}
};

BX.DocumentGenerator.UploadTemplate.deleteFromSocNetLogDest = function()
{
	var templateUsers = BX.DocumentGenerator.UploadTemplate.userSelector.getValue();
	var selectorUsers = BX.SocNetLogDestination.obItemsSelected[BX.DocumentGenerator.UploadTemplate.userSelectorId];

	for(var id in selectorUsers)
	{
		if(selectorUsers.hasOwnProperty(id))
		{
			if(templateUsers.indexOf(id) === -1)
			{
				delete selectorUsers[id];
			}
		}
	}
};

BX.DocumentGenerator.UploadTemplate.setTemplateData = function(data)
{
	this.templateId = data.ID;
	this.fileId = data.FILE_ID;
	if(BX.type.isArray(data['PROVIDERS']))
	{
		for(var i = 0; i < data['PROVIDERS'].length; i++)
		{
			this.providerSelector.add(data['PROVIDERS'][i]['CLASS'], data['PROVIDERS'][i]['NAME']);
		}
	}
};

BX.DocumentGenerator.UploadTemplate.reinstallTemplate = function()
{
	this.popupConfirm.destroy();
	this.startProgress();
	BX.ajax.runAction('documentgenerator.api.template.installDefault', {
		data: {
			code: this.defaultCode
		}
	}).then(function(response)
	{
		BX.DocumentGenerator.UploadTemplate.fileId = response.data.template.fileId;
		BX.DocumentGenerator.UploadTemplate.stopProgress();
		BX.hide(BX(BX.DocumentGenerator.UploadTemplate.errorMessageNode));
		var popup = new BX.PopupWindow('crm-document-reinstall-template-popup-success', null, {
			zIndex: 200,
			closeIcon: false,
			autoHide: true,
			closeByEsc: true,
			overlay : true,
			events : { onPopupClose : function() { this.destroy() }},
			titleBar : BX.message('DOCGEN_TEMPLATE_ADD_REINSTALL_SUCCESS'),
			contentColor: 'white',
		});
		popup.show();
	}, function(response)
	{
		BX.DocumentGenerator.UploadTemplate.stopProgress();
		BX.DocumentGenerator.UploadTemplate.showUploadError(response.errors.pop().message);
	});
};

BX.DocumentGenerator.UploadTemplate.Selector = function(params)
{
	this.multiple = params.multiple === true;
	this.values = [];
	this.id = params.id || BX.util.hashCode(Math.random().toString());
	this.itemsContainerId = 'container-' + this.id;
	this.itemContainerClass = 'docs-template-load-block-section-inner';
	this.itemNameClass = 'docs-template-load-block-section-name';
	this.itemDeleteClass = 'docs-template-load-block-delete';
	this.onAfterDelete = BX.type.isFunction(params.onAfterDelete) ? params.onAfterDelete : BX.DoNothing;
};

BX.DocumentGenerator.UploadTemplate.Selector.prototype.add = function(id, name)
{
	if(!id)
	{
		return;
	}
	var index = this.values.indexOf(id);
	if (index !== -1) {
		return;
	}
	if(!this.multiple)
	{
		for(var i = 0; i < this.values.length; i++)
		{
			this.delete(this.values[i]);
		}
	}
	this.values.push(id);
	name = name || id;
	BX.append(BX.create('span', {
		props: { className: this.itemContainerClass},
		attrs: {id: 'item-' + this.id + id},
		children: [
			BX.create('span', {
				props: {className: this.itemNameClass},
				text: name
			}),
			BX.create('span', {
				props: {className: this.itemDeleteClass},
				events: {
					click: BX.proxy(function()
					{
						this.delete(id);
					}, this)
				}
			})
		],
		events: {
			click: function(event)
			{
				event.stopPropagation();
			}
		}
	}), BX(this.itemsContainerId));
};

BX.DocumentGenerator.UploadTemplate.Selector.prototype.delete = function(id)
{
	if(!id)
	{
		return;
	}
	var index = this.values.indexOf(id);
	if (index === -1) {
		return;
	}
	this.values.splice(index, 1);
	BX.remove(BX('item-' + this.id + id));
	this.onAfterDelete();
};

BX.DocumentGenerator.UploadTemplate.Selector.prototype.getValue = function()
{
	this.values = BX.util.array_unique(this.values);
	if(!this.multiple)
	{
		return this.values[0];
	}
	return this.values.slice();
};

BX.DocumentGenerator.UploadTemplate.Selector.prototype.purge = function()
{
	var value = this.getValue();
	if(this.multiple)
	{
		for(var i = 0; i < value.length; i++)
		{
			this.delete(value[i]);
		}
	}
	else
	{
		this.delete(value);
	}
};

BX.DocumentGenerator.UploadTemplate.getLoader = function()
{
	if(!this.loader)
	{
		this.loader = new BX.Loader({size: 150});
	}

	return this.loader;
};

BX.DocumentGenerator.UploadTemplate.startProgress = function()
{
	if(!this.getLoader().isShown())
	{
		this.getLoader().show(BX(BX.DocumentGenerator.UploadTemplate.nameBlockNode).parentNode);
	}
	BX(this.saveButton).disabled = true;
	this.progress = true;
};

BX.DocumentGenerator.UploadTemplate.stopProgress = function()
{
	this.getLoader().hide();
	BX(this.saveButton).disabled = false;
	this.progress = false;
};

BX.DocumentGenerator.UploadTemplate.reloadRegions = function()
{
	BX.DocumentGenerator.UploadTemplate.startProgress();
	BX.ajax.runAction('documentgenerator.region.list').then(function(response)
	{
		BX.DocumentGenerator.UploadTemplate.stopProgress();
		var selectedRegion, regions = [], code, i;
		selectedRegion = BX(BX.DocumentGenerator.UploadTemplate.regionSelectorId).value;
		if(BX.type.isNotEmptyObject(response.data.regions))
		{
			BX.DocumentGenerator.UploadTemplate.regions = response.data.regions;
			var defaultRegions = []; var customRegions = [];
			for(code in response.data.regions)
			{
				if(response.data.regions.hasOwnProperty(code))
				{
					if(parseInt(code) > 0)
					{
						customRegions.push(response.data.regions[code]);
					}
					else
					{
						defaultRegions.push(response.data.regions[code]);
					}
				}
			}
			for(i = 0; i < defaultRegions.length; i++)
			{
				regions.push(defaultRegions[i]);
			}
			for(i = 0; i < customRegions.length; i++)
			{
				regions.push(customRegions[i]);
			}
		}
		BX.cleanNode(BX(BX.DocumentGenerator.UploadTemplate.regionSelectorId), false);
		for(i = 0; i < regions.length; i++)
		{
			var attrs = {
				value: regions[i]['code'],
			};
			if(selectedRegion === regions[i]['code'])
			{
				attrs['selected'] = 'selected';
			}
			BX(BX.DocumentGenerator.UploadTemplate.regionSelectorId).appendChild(BX.create('option', {
				attrs: attrs,
				text: regions[i]['title']
			}));
		}
	}, function(response)
	{
		BX.DocumentGenerator.UploadTemplate.stopProgress();
		BX.DocumentGenerator.UploadTemplate.showUploadError(response.errors.pop().message);
	});
};

})(window);