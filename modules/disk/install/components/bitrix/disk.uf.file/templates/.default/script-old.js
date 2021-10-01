;(function(window){

	if (window.BX.Disk && window.BX.Disk.UFShowController)
		return;

	var BX = window.BX;
	var diskufMenuNumber = 0;
	var showRepo = {};

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

	window.DiskOpenMenuCreateService = function(targetElement)
	{
		var items = [
			(BX.Disk.UF.getDocumentHandler('onlyoffice')? {
				text: BX.Disk.UF.getDocumentHandler('onlyoffice').name,
				className: "bx-viewer-popup-item item-b24-docs",
				onclick: function (event, popupItem)
				{
					popupItem.getMenuWindow().close();

					BX.Disk.saveDocumentService('onlyoffice');

					BX.adjust(targetElement, {text: BX.Disk.UF.getDocumentHandler('onlyoffice').name});
				}
			}: null),
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
