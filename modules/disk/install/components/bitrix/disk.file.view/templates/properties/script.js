BX.namespace("BX.Disk");
BX.Disk.FileViewClass = (function ()
{
	var historyTabsIsLoaded = false;
	var bpTabsIsLoaded = false;

	var FileViewClass = function (parameters)
	{
		this.componentName = parameters.componentName;
		this.signedParameters = parameters.signedParameters;

		this.selectorId = parameters.selectorId;
		//this.grid = parameters.grid;
		this.object = parameters.object || {};
		this.uf = parameters.uf || {};
		this.canDelete = !!parameters.canDelete;
		if (parameters.externalLinkInfo)
		{
			this.buildExternalLinkByState(parameters.externalLinkInfo);
		}

		this.layout = parameters.layout;
		this.urls = parameters.urls;

		this.gridId = parameters.gridId;
		this.grid = this.gridId? BX.Main.gridManager.getById(this.gridId) : null;

		this.ajaxUrl = '/bitrix/components/bitrix/disk.file.view/ajax.php';
		this.ajaxCurrentPageUrl = document.location.href;

		this.popupOuterLink = null;
		this.selectorInitialized = false;

		if(!parameters.withoutEventBinding)
			this.setEvents();

		this.render();

		BX.ajax.runAction('disk.api.file.showSharingEntities', {
			data: {
				fileId: this.object.id
			}
		}).then(function (response) {

			if (BX.type.isArray(response.data))
			{
				response.data.forEach(function (item) {
					var sharingItem = new BX.Disk.Model.SharingItem({
						state: {
							entity: item.entity,
							sharing: item.sharing,
							object: {
								id: this.object.id
							}
						}
					});

					sharingItem.render();
					document.querySelector('.disk-detail-sidebar-user-access-section').appendChild(sharingItem.getContainer());

					var selectorInstance = BX.UI.SelectorManager.instances[this.selectorId];
					if (selectorInstance)
					{
						selectorInstance.itemsSelected[item.entity.id] = BX.UI.SelectorManager.convertEntityType(item.entity.type);
					}

					BX.onCustomEvent('Disk.FileView:onShowSharingEntities', [{
						id: this.selectorId,
						openDialogWhenInit: false
					}]);
					this.selectorInitialized = true;
				}.bind(this));
			}
		}.bind(this));

	};

	FileViewClass.prototype.buildExternalLinkByState = function (state)
	{
		this.externalLink = new BX.Disk.Model.ExternalLink.Input({
			templateId: 'external-link-setting-input-prop',
			state: state,
			data: {
				objectId: this.object.id
			},
			models: {
				externalLinkSettings: new BX.Disk.Model.ExternalLink.Settings({
					templateId: 'external-link-setting-popup-prop',
					state: state
				}),
				externalLinkDescription: new BX.Disk.Model.ExternalLink.Description({
					templateId: 'external-link-setting-info-prop',
					state: state
				})
			}
		});
	};

	FileViewClass.prototype.setUrlToAjaxCurrentPage = function (url)
	{
		this.ajaxCurrentPageUrl = url;
	};

	FileViewClass.prototype.getUrlToAjaxCurrentPage = function ()
	{
		return this.ajaxCurrentPageUrl.replace(document.location.hash, '');
	};

	FileViewClass.prototype.render = function ()
	{
		if (this.externalLink && this.layout.externalLink)
		{
			this.externalLink.render();
			this.layout.externalLink.parentNode.replaceChild(this.externalLink.getContainer(), this.layout.externalLink);
			this.adjustExternalLinkBlockHeight();
		}
	};

	FileViewClass.prototype.setEvents = function ()
	{
		BX.bind(BX('disk-detail-sidebar-public-link-copy-link'), 'click', this.copyInternalLink.bind(this));
		if (this.layout.deleteButton)
		{
			BX.bind(this.layout.deleteButton, 'click', this.openDeleteConfirm.bind(this));
		}
		if (this.layout.restoreButton)
		{
			BX.bind(this.layout.restoreButton, 'click', this.openConfirmRestoreFromTrash.bind(this));
		}
		if (this.layout.addSharingButton)
		{
			BX.bind(this.layout.addSharingButton, 'click', this.openSelector.bind(this));
		}
		if (this.layout.editUf)
		{
			BX.bind(this.layout.editUf, 'click', this.editUf.bind(this));
		}
		BX.bind(this.layout.moreActionsButton, 'click', this.openFileActions.bind(this));
		BX.bind(window, 'popstate', this.onPopState.bind(this));

		BX.addCustomEvent("onIframeElementLoadDataToView", BX.proxy(this.onIframeElementLoadDataToView, this));
		BX.addCustomEvent("onIframeElementConverted", BX.proxy(this.onIframeElementConverted, this));
		BX.addCustomEvent("onBeforeElementShow", BX.proxy(this.onBeforeElementShow, this));
		BX.addCustomEvent(this.externalLink.externalLinkDescription, "Disk.Model.Item:afterRender", this.handleAfterRenderExternalLinkSettings.bind(this));
		BX.addCustomEvent('SidePanel.Slider:onMessage', this.onSliderMessage.bind(this));

		if(!!this.uf)
		{
			BX.bind(this.uf.editButton, 'click', BX.delegate(this.onClickEditUfButton, this));
		}
	};

	FileViewClass.prototype.onSliderMessage = function(event)
	{
		var eventData = event.getData();
		if (event.getEventId() === 'Disk.File:onRestoredFromVersion' || event.getEventId() === 'Disk.Version:onDeleted')
		{
			window.location.reload();
		}
		if (event.getEventId() === 'Disk.File.Uf:onUpdated')
		{
			this.reloadUf();
		}
	};

	FileViewClass.prototype.onBeforeElementShow = function(viewer, element, status)
	{
		if(element.hasOwnProperty('image') || !BX.message('disk_restriction'))
		{
			return;
		}
		status.prevent = true;

		BX.PopupWindowManager.create('bx-disk-business-tools-info', null, {
			content: BX('bx-bitrix24-business-tools-info'),
			closeIcon: true,
			onPopupClose: function ()
			{
				this.destroy();
			},
			autoHide: true,
			zIndex: 11000
		}).show();
	};

	FileViewClass.prototype.handleAfterRenderExternalLinkSettings = function()
	{
		this.adjustExternalLinkBlockHeight();
	};

	FileViewClass.prototype.onIframeElementConverted = function(element, newName, oldName)
	{
		this.setUrlToAjaxCurrentPage(
			this.ajaxCurrentPageUrl.replace(encodeURIComponent(oldName), encodeURIComponent(newName))
		);
	};

	FileViewClass.prototype.onIframeElementLoadDataToView = function(element, responseData)
	{
		if(responseData && responseData.status === "restriction" && BX('bx-bitrix24-business-tools-info'))
		{
			setTimeout(function ()
			{
				if (BX.CViewer && BX.CViewer.objNowInShow) {
					if(element.currentModalWindow)
					{
						element.currentModalWindow.close();
					}
					BX.CViewer.objNowInShow.close();
				}

			}, 100);
			BX.PopupWindowManager.create('bx-disk-business-tools-info', null, {
				content: BX('bx-bitrix24-business-tools-info'),
				closeIcon: true,
				onPopupClose: function ()
				{
					this.destroy();
				},
				autoHide: true,
				zIndex: 11000
			}).show();
		}

	};

	FileViewClass.prototype.onClickEditUfButton = function(e)
	{
		BX.PreventDefault(e);

		BX.Disk.ajax({
			url: BX.Disk.addToLinkParam(document.location.href.replace(document.location.hash, ''), 'action', 'editUserField'),
			method: 'POST',
			dataType: 'html',
			data: {},
			onsuccess: BX.delegate(function (data)
			{
				var showContentNode = BX.findChild(this.uf.contentContainer, {className: 'bx-disk-uf-show-content'});
				var editContentNode = BX.findChild(this.uf.contentContainer, {className: 'bx-disk-uf-edit-content'});
				if(showContentNode)
				{
					BX.hide(showContentNode);
				}
				BX.adjust(editContentNode, {html: data});
				//BX.remove(loader);
			}, this)
		});
	};

	FileViewClass.prototype.openConfirmRestoreFromTrash = function ()
	{
		var name = this.object.name;
		var objectId = this.object.id;
		var messageDescription = BX.message('DISK_FILE_TRASH_RESTORE_FILE_CONFIRM');

		var buttons = [
			new BX.PopupWindowButton({
				text: BX.message('DISK_FILE_VIEW_FILE_RESTORE'),
				className: "popup-window-button-accept",
				events: {
					click: function (e) {
						this.addClassName('popup-window-button-wait');

						BX.ajax.runAction('disk.api.file.restore', {
							data: {
								fileId: objectId
							}
						}).then(function (response) {
							BX.PopupWindowManager.getCurrentPopup().close();

							var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
							if (sliderByWindow)
							{
								sliderByWindow.close();
								BX.SidePanel.Instance.postMessageAll(window, 'Disk.File:onRestore', {
									objectId: objectId
								});
							}
							else if(response.data.file.id)
							{
								document.location.href = BX.Disk.getUrlToShowObjectInGrid(response.data.ID);
							}
						});
					}
				}
			}),
			new BX.PopupWindowButton({
				text: BX.message('DISK_FILE_VIEW_VERSION_CANCEL_BUTTON'),
				events: {
					click: function (e){
						BX.PopupWindowManager.getCurrentPopup().close();
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FILE_TRASHCAN_TRASH_RESTORE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	FileViewClass.prototype.openConfirmRestore = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var versionId = parameters.version.id;
		var messageDescription = BX.message('DISK_FILE_VIEW_VERSION_RESTORE_CONFIRM');
		var buttons = [
			new BX.PopupWindowButton({
				text: BX.message('DISK_FILE_VIEW_VERSION_RESTORE_BUTTON'),
				className: "popup-window-button-accept",
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().close();
						BX.PreventDefault(e);

						BX.Disk.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'restoreFromVersion'),
							data: {
								objectId: objectId,
								versionId: versionId
							},
							onsuccess: BX.delegate(function (data) {
								if (!data) {
									return;
								}
								this.grid.instance.reload();
								BX.Disk.showModalWithStatusAction(data);
							}, this)
						});

						return false;
					}, this)
				}
			}),
			new BX.PopupWindowButton({
				text: BX.message('DISK_FILE_VIEW_VERSION_CANCEL_BUTTON'),
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().close();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FILE_VIEW_VERSION_RESTORE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	FileViewClass.prototype.openDeleteConfirm = function ()
	{
		var name = this.object.name;
		var objectId = this.object.id;

		var messageDescription = this.canDelete ?
			BX.message('DISK_FILE_VIEW_TRASH_DELETE_DESTROY_FILE_CONFIRM') :
			BX.message('DISK_FILE_VIEW_TRASH_DELETE_FILE_CONFIRM')
		;

		if(this.object.isDeleted)
		{
			messageDescription = BX.message('DISK_FILE_VIEW_TRASH_DELETE_DESTROY_DELETED_FILE_CONFIRM');
		}

		var buttons = [];

		if (!this.object.isDeleted)
		{
			buttons.push(new BX.PopupWindowCustomButton({
					text: BX.message('DISK_FILE_VIEW_TRASH_DELETE_BUTTON'),
					className: "ui-btn ui-btn-success",
					events: {
						click: function (e) {
							this.addClassName('ui-btn-clock');

							BX.ajax.runAction('disk.api.file.markDeleted', {
								data: {
									fileId: objectId
								}
							}).then(function (response) {
								BX.PopupWindowManager.getCurrentPopup().close();

								var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
								if (sliderByWindow)
								{
									sliderByWindow.close();
									BX.SidePanel.Instance.postMessageAll(window, 'Disk.File:onMarkDeleted', {
										objectId: objectId
									});
								}
							});
						}
					}
				})
			);
		}

		if (this.canDelete)
		{
			var self = this;
			buttons.push(new BX.PopupWindowCustomButton({
					text: BX.message('DISK_FILE_VIEW_TRASH_DESTROY_BUTTON'),
					className: 'ui-btn ui-btn-light-border',
					events: {
						click: function (e) {
							this.addClassName('ui-btn-clock');

							BX.ajax.runAction('disk.api.file.delete', {
								data: {
									fileId: objectId
								}
							}).then(function (response) {
								BX.PopupWindowManager.getCurrentPopup().close();

								var sliderByWindow = BX.SidePanel.Instance.getSliderByWindow(window);
								if (sliderByWindow)
								{
									sliderByWindow.close();
									BX.SidePanel.Instance.postMessageAll(window, 'Disk.File:onDelete', {
										objectId: objectId
									});
								}
								else
								{
									document.location.href = self.urls.trashcanList;
								}
							});
						}
					}
				})
			)
		}

		buttons.push(new BX.PopupWindowCustomButton({
			text: BX.message('DISK_FILE_VIEW_VERSION_CANCEL_BUTTON'),
			className: 'ui-btn ui-btn-link',
			events: {
				click: function (e) {
					BX.PopupWindowManager.getCurrentPopup().close();
				}
			}})
		);

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FILE_VIEW_VERSION_DELETE_TITLE'),
			contentClassName: 'disk-popup-text-content',
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	FileViewClass.prototype.openConfirmDeleteVersion = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var versionId = parameters.version.id;
		var messageDescription = BX.message('DISK_FILE_VIEW_VERSION_DELETE_VERSION_CONFIRM');
		var buttons = [
			new BX.PopupWindowButton({
				text: BX.message('DISK_FILE_VIEW_VERSION_DELETE_VERSION_BUTTON'),
				className: "popup-window-button-accept",
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().close();
						BX.PreventDefault(e);

						BX.Disk.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'deleteVersion'),
							data: {
								objectId: objectId,
								versionId: versionId
							},
							onsuccess: BX.delegate(function (data) {
								if (!data) {
									return;
								}
								this.grid.instance.removeRow(versionId);

								BX.Disk.showModalWithStatusAction(data);
							}, this)
						});

						return false;
					}, this)
				}
			}),
			new BX.PopupWindowButton({
				text: BX.message('DISK_FILE_VIEW_VERSION_CANCEL_BUTTON'),
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().close();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FILE_VIEW_VERSION_DELETE_VERSION_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	FileViewClass.prototype.deleteBizProc = function (idBizProc)
	{
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'deleteBizProc'),
			data: {
				idBizProc: idBizProc,
				fileId: this.object.id
			},
			onsuccess: BX.delegate(function (response) {
				if(response)
				{
					BX.remove(BX(idBizProc));
				}
			}, this)
		});
	};

	FileViewClass.prototype.stopBizProc = function (idBizProc)
	{
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'stopBizProc'),
			data: {
				idBizProc: idBizProc,
				fileId: this.object.id
			},
			onsuccess: BX.delegate(function (response) {
				if(response.status == 'success')
				{
					var url = document.location.href.replace("#tab-bp", '');
					url += "#tab-bp";
					document.location.href = url;
					location.reload();
				}
				else
				{
					response.errors = response.errors || [{}];
					BX.Disk.showModalWithStatusAction({
						status: 'error',
						message: response.errors.pop().message
					})
				}
			}, this)
		});
	};

	FileViewClass.prototype.openSlider = function (url)
	{
		BX.SidePanel.Instance.open(url, {
			allowChangeHistory: false
		});
	};

	FileViewClass.prototype.showLogBizProc = function (key)
	{
		this.openSlider(BX.Disk.addToLinkParam(this.urls.fileShowBp, 'log_workflow', key));
	};

	FileViewClass.prototype.sortBizProcLog = function ()
	{
		var newOnclickStr = BX.findChildByClassName(BX('workarea-content'), 'bx-sortable').getAttribute('onclick').replace('log_sort=1&', '');
		newOnclickStr = newOnclickStr.replace('?log_workflow', '?log_sort=1&log_workflow');
		newOnclickStr = newOnclickStr.replace('&action=showBp', '');
		BX.findChildByClassName(BX('workarea-content'), 'bx-sortable').setAttribute('onclick', newOnclickStr);
	}

	FileViewClass.prototype.fixUrlForSort = function ()
	{
		var url = document.location.href.replace('&log_sort=1', '');
		url += "#tab-bp";
		document.location.href = url;
	}

	FileViewClass.prototype.copyInternalLink = function() {
		var element = BX('disk-detail-sidebar-public-link-copy-link');
		var elementAttr = element.getAttribute('for');
		var copyInput = document.getElementById(elementAttr);
		copyInput.select();
		document.execCommand('copy');
		this.popupOuterLink ? null : this.showCopyLinkPopup(element, BX.message('DISK_FILE_VIEW_INTERNAL_LINK_COPIED'));
	};

	FileViewClass.prototype.showCopyLinkPopup = function(node, message) {
		this.popupOuterLink = new BX.PopupWindow('disk-popup-copy-link', node, {
			className: 'disk-popup-copy-link',
			bindPosition: {
				position: 'top'
			},
			offsetLeft: -10,
			darkMode: true,
			angle: true,
			content: message
		});

		this.popupOuterLink.show();

		setTimeout(function() {
			BX.addClass(BX(this.popupOuterLink.uniquePopupId), 'disk-popup-copy-link-hide');
		}.bind(this), 2000);

		setTimeout(function() {
			this.popupOuterLink.destroy();
			this.popupOuterLink = null;
		}.bind(this), 2200)
	};

	FileViewClass.prototype.adjustExternalLinkBlockHeight = function()
	{
		this.externalLink.adjustHeight();
	};

	FileViewClass.prototype.onPopState = function (e)
	{
		var state = e.state;
		if(state && state.disk)
		{
			window.location.reload();
		}
	};

	FileViewClass.prototype.openFileActions = function (event)
	{
		var target = BX.getEventTarget(event);
		var self = this;

		var menuItems = [
			{
				text: BX.message('DISK_FILE_VIEW_FILE_GO_TO_HISTORY'),
				onclick: function() {
					BX.SidePanel.Instance.open(self.urls.fileHistory);
					this.close();
				}
			}
		];
		if (this.object.hasUf)
		{
			menuItems.push({
					text: BX.message('DISK_FILE_VIEW_FILE_GO_TO_SHOW_USERFIELDS'),
					onclick: function () {
						BX.SidePanel.Instance.open(self.urls.fileShowUf, {
							allowChangeHistory: false
						});
						this.close();
					}
				}
			)
		}
		if (this.object.hasBp)
		{
			menuItems.push({
					text: BX.message('DISK_FILE_VIEW_FILE_GO_TO_SHOW_BP'),
					onclick: function () {
						BX.SidePanel.Instance.open(self.urls.fileShowBp, {
							allowChangeHistory: false
						});
						this.close();
					}
				}
			)
		}
		BX.PopupMenu.show('disk-file-view-actions', BX(target), menuItems,
			{
				className: 'disk-file-view-actions-popup',
				angle: true,
				autoHide: true,
				offsetLeft: 20,
				overlay: {
					opacity: 0.01
				},
				events: {
					onPopupClose: function () {
						BX.PopupMenu.destroy('disk-file-view-actions');
					}
				}
			}
		);
	};

	FileViewClass.prototype.editUf = function ()
	{
		BX.SidePanel.Instance.open(this.urls.fileEditUf, {
			allowChangeHistory: false
		});
	};

	FileViewClass.prototype.reloadUf = function ()
	{
		BX.ajax.runComponentAction('bitrix:disk.file.view', 'showUfSidebar', {
			mode: 'class',
			data: {
				fileId: this.object.id
			}
		}).then(function (response) {
			BX.cleanNode(this.layout.sidebarUfValuesSection);
			this.layout.sidebarUfValuesSection.innerHTML = response.data.html;
		}.bind(this));
	};

	FileViewClass.prototype.onOpenSelector = function ()
	{
		//fix zIndex to show above sidepanel
		var selectorInstance = BX.UI.SelectorManager.instances[this.selectorId];
		if (
			selectorInstance
			&& selectorInstance.popups.container
			&& BX.SidePanel.Instance.isOpen()
		)
		{
			selectorInstance.popups.container.adjustPosition();
		}
	};

	FileViewClass.prototype.openSelector = function ()
	{
		if (!this.selectorInitialized)
		{
			BX.onCustomEvent('Disk.FileView:onShowSharingEntities', [{
				id: this.selectorId,
				openDialogWhenInit: true
			}]);
			this.selectorInitialized = true;
		}
		else
		{
			BX.onCustomEvent('Disk.FileView:openSelector', [{
				id: this.selectorId
			}]);
		}
	};

	FileViewClass.prototype.onUnSelectSelectorItem = function (data)
	{
		BX.onCustomEvent("Disk.FileView:onUnSelectSelectorItem", [data]);
	};

	FileViewClass.prototype.onSelectSelectorItem = function (data)
	{
		if (data.state !== 'select')
			return;

		BX.Main.selectorManagerV2.getById(this.selectorId).closeDialog();

		var sharingItem = new BX.Disk.Model.DraftSharingItem({
			state: {
				entity: {
					id: data.item.id,
					name: data.item.name,
					link: data.item.link,
					avatar: data.item.avatar
				},
				object: {
					id: this.object.id
				}
			}
		});

		sharingItem.render();
		sharingItem.save();

		document.querySelector('.disk-detail-sidebar-user-access-section').appendChild(sharingItem.getContainer());
	};

	return FileViewClass;
})();