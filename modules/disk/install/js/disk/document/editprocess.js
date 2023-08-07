(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Document
	 */
	BX.namespace("BX.Disk.Document");

	/**
	 *
	 * @param {object} parameters
	 * @constructor
	 */
	BX.Disk.Document.EditProcess = function(parameters)
	{
		this.objectId = parameters.objectId;
		this.attachedObjectId = parameters.attachedObjectId;
		this.serviceCode = parameters.serviceCode;
		this.service = null;
		this.popupConfirm = null;
		this.onAfterSave = null;
		this.modalWindow = parameters.modalWindow;
		this.handlerOnSliderMessage = null;

		if(BX.type.isFunction(parameters.onAfterSave))
		{
			this.onAfterSave = parameters.onAfterSave;
		}
	};

	BX.Disk.Document.EditProcess.prototype =
	{
		start: function ()
		{
			var hasPromoPopups = BX.getClass('BX.Disk.OnlyOfficePromo.PromoPopup');
			if (this.serviceCode === 'onlyoffice')
			{
				if (hasPromoPopups)
				{
					if (BX.Disk.OnlyOfficePromo.PromoPopup.shouldBlockViewAndEdit())
					{
						BX.Disk.OnlyOfficePromo.PromoPopup.showCommonPromoForNonPaid();

						return;
					}
					if (BX.Disk.OnlyOfficePromo.PromoPopup.shouldShowEditPromo())
					{
						BX.Disk.OnlyOfficePromo.PromoPopup.showEditPromo();

						return;
					}
				}

				this.openSlider();

				return;
			}
			else if (hasPromoPopups && BX.Disk.OnlyOfficePromo.PromoPopup.shouldShowEndDemo())
			{
				BX.Disk.OnlyOfficePromo.PromoPopup.showEndOfDemo();

				return;
			}

			this.modalWindow = this.buildModalWindow();

			this.loadServiceDescription().then(function (service) {
				this.openEditConfirm();
			}.bind(this));
		},

		getSliderQueryParameters: function()
		{
			return {
				action: 'disk.api.documentService.goToEdit',
				serviceCode: this.serviceCode,
				objectId: this.objectId || 0,
				attachedObjectId: this.attachedObjectId || 0
			}
		},

		getSliderData: function()
		{
			return {
				process: 'edit',
			}
		},

		openSlider: function ()
		{
			var data = this.getSliderData();
			data.documentEditor = true;

			var success = BX.SidePanel.Instance.open(BX.util.add_url_param('/bitrix/services/main/ajax.php', this.getSliderQueryParameters()), {
				width: '100%',
				customLeftBoundary: 30,
				cacheable: false,
				allowChangeHistory: false,
				data: data
			});

			if (success)
			{
				this.handlerOnSliderMessage = this.onSliderMessage.bind(this);
				BX.addCustomEvent('SidePanel.Slider:onMessage', this.handlerOnSliderMessage)
			}
		},

		onSliderMessage: function(event)
		{
			var eventData = event.getData();
			if (event.getEventId() === 'Disk.OnlyOffice:onClosed' && (eventData.process === 'edit' || eventData.process === 'create'))
			{
				var object = eventData.object;
				BX.removeCustomEvent('SidePanel.Slider:onMessage', this.handlerOnSliderMessage);
				var pseudoResponse = {
					status: 'success',
					object: object
				};
				this.onAfterSave.call(this, pseudoResponse);
			}
		},

		buildModalWindow: function ()
		{
			return this.openModal(BX.util.add_url_param('/bitrix/services/main/ajax.php', {
				action: 'disk.api.documentService.goToEdit',
				serviceCode: this.serviceCode,
				objectId: this.objectId || 0,
				attachedObjectId: this.attachedObjectId || 0
			}));
		},

		isActive: function ()
		{
			return this.popupConfirm && this.popupConfirm.isShown();
		},

		openModal: function(link, width, height)
		{
			width = width || 1030;
			height = height || 700;

			if (this.modalWindow)
			{
				this.modalWindow.location = link;
			}

			var modalWindow = this.modalWindow || BX.util.popup(link, width, height);
			window.addEventListener("message", function (event) {
				if (event.origin != window.location.origin)
				{
					return;
				}

				if(event.data.reason === 'disk-work-with-document')
				{
					this.setDataForCommit(event.data);
				}
				else if(event.data.reason === 'disk-work-close-edit-document')
				{
					this.closeEditConfirm();
				}

			}.bind(this), false);

			return modalWindow;
		},

		closeModal: function ()
		{
			if (this.modalWindow)
			{
				try
				{
					this.modalWindow.close();
				}
				catch (e)
				{}
			}
		},

		getDataForCommit: function()
		{
			return this.dataForCommit;
		},

		setDataForCommit: function(data)
		{
			this.dataForCommit = data;
		},

		getService: function ()
		{
			return this.service;
		},

		loadServiceDescription: function()
		{
			var promise = new BX.Promise();
			if (this.service)
			{
				promise.fulfill(this.service);

				return promise;
			}

			BX.ajax.runAction('disk.api.documentService.get', {
				data: {
					serviceCode: this.serviceCode
				}
			}).then(function (response) {
				this.service = response.data.documentService;

				promise.fulfill(this.service);
			}.bind(this));

			return promise;
		},

		buildLinkToCommit: function ()
		{
			var uri = this.objectId? this.service.links.edit : this.service.links.uf.edit;

			uri = uri.replace('FILE_ID', this.objectId);
			uri = uri.replace('ATTACHED_ID', this.attachedObjectId);
			uri = BX.util.remove_url_param(uri, 'document_action');
			uri = BX.util.add_url_param(uri, {'document_action': 'commit'});

			return uri;
		},

		save: function ()
		{
			return this.commit().then(function(response){
				if (response.status !== 'success')
				{
					BX.UI.Viewer.Instance.close();

					BX.Disk.showModalWithStatusAction(response);
					return response;
				}

				if (response.originalIsLocked)
				{
					BX.UI.Viewer.Instance.close();

					BX.Disk.InformationPopups.showWarningLockedDocument({link: BX.Disk.getUrlToShowObjectInGrid(response.forkedObject.id)});
				}
				else
				{
					this.onAfterSave.call(this, response);
				}

				return response;
			}.bind(this));
		},

		commit: function ()
		{
			var fakePromise = new BX.Promise();
			var parameters = this.getDataForCommit() || {};
			var idDoc = parameters.idDoc || parameters.id;
			if (!idDoc)
			{
				console.log('There is no parameters for commit');
				fakePromise.fulfill({status: 'error'});

				return fakePromise;
			}

			return BX.ajax.promise({
				method: 'POST',
				dataType: 'json',
				url: this.buildLinkToCommit(),
				data:  {
					commit: 1,
					editSessionId: parameters.editSessionId,
					id: idDoc,
					sessid: BX.bitrix_sessid()
				}
			});
		},

		buildLinkToDiscard: function ()
		{
			return this.urlHelper().getUrlDiscardFile(
				this.buildLinkToCommit()
			);
		},

		discard: function ()
		{
			var fakePromise = new BX.Promise();
			var parameters = this.getDataForCommit() || {};
			var idDoc = parameters.idDoc || parameters.id;
			if (!idDoc)
			{
				console.log('There is no parameters for commit');
				fakePromise.fulfill({status: 'error'});

				return fakePromise;
			}

			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: this.buildLinkToDiscard(),
				data:  {
					discard: 1,
					editSessionId: parameters.editSessionId,
					id: idDoc,
					sessid: BX.bitrix_sessid()
				},
				onsuccess: function(response){
				}
			});
		},

		closeEditConfirm: function()
		{
			this.popupConfirm && this.popupConfirm.close();
		},

		getConfirmMessages: function ()
		{
			return {
				title: BX.message('JS_DISK_DOC_PROCESS_NOW_EDITING_IN_SERVICE').replace('#SERVICE#', this.service.name),
				text: BX.message('JS_DISK_DOC_PROCESS_IFRAME_DESCR_SAVE_DOC_F').replace('#SAVE_DOC#', BX.message('JS_DISK_DOC_PROCESS_SAVE')),
				saveButton: BX.message('DISK_JS_BTN_SAVE')
			};
		},

		openEditConfirm: function()
		{
			var saveDialog = BX.create('div', {
				props: {
					className: 'bx-disk-document-edit-confirm'
				},
				children: [
					BX.create('div', {
						props: {
							className: 'bx-disk-document-edit-confirm-title'
						},
						text: this.getConfirmMessages().title,
						children: []
					}),
					BX.create('div', {
						props: {
							className: 'bx-disk-document-edit-confirm-text-wrap'
						},
						children: [
							BX.create('span', {
								props: {
									className: 'bx-disk-document-edit-confirm-text-alignment'
								}
							}),
							BX.create('span', {
								props: {
									className: 'bx-disk-document-edit-confirm-text'
								},
								text: this.getConfirmMessages().text
							})
						]
					})
				]
			});

	 		this.popupConfirm = BX.PopupWindowManager.create('document-edit-confirm', null, {
	 			content: saveDialog,
				overlay: true,
				buttons: [
					new BX.PopupWindowCustomButton({
						text : this.getConfirmMessages().saveButton,
						className : "ui-btn ui-btn-success",
						events : {
							click: function () {
								var actionModal = BX.Disk.showActionModal({
									text: BX.message('JS_DISK_DOC_PROCESS_IFRAME_PROCESS_SAVE_DOC'),
									showLoaderIcon: true,
									autoHide: false
								});

								this.popupConfirm.close();
								this.closeModal();
								window.onbeforeunload = null;

								this.save().then(function(){
									actionModal.close();
								});
							}.bind(this)
						}
					}),
					new BX.PopupWindowCustomButton({
						text: BX.message('DISK_JS_BTN_CANCEL'),
						className: 'ui-btn ui-btn-link',
						events: {
							click: function () {
								this.discard();
								this.popupConfirm.close();
								this.closeModal();
							}.bind(this)
						}
					})
				],
	 			autoHide: false,
				closeByEsc: false,
				events: { onPopupClose : function() { this.destroy() }}
			});
			this.popupConfirm.show();
		},

		urlHelper: function ()
		{
			var serviceCode = this.serviceCode;

			return {
				ajaxDocUrl: '/bitrix/tools/disk/document.php',
				ajaxUfDocUrl: '/bitrix/tools/disk/uf.php',

				normalizeServiceName: function(service)
				{
					switch(service.toLowerCase())
					{
						case 'g':
						case 'google':
						case 'gdrive':
							service = 'gdrive';
							break;
						case 's':
						case 'skydrive':
						case 'sky-drive':
						case 'onedrive':
							service = 'onedrive';
							break;
						case 'office365':
							service = 'office365';
							break;
						case 'myoffice':
							service = 'myoffice';
							break;
						case 'l':
						case 'local':
							service = 'l';
							break;
						default:
							service = 'gdrive';
							break;
					}
					return service;
				},

				getUrlViewFile: function(url)
				{
					url = this.addToLinkParam(url, 'service', 'gvdrive');
					url = this.addToLinkParam(url, 'document_action', 'show');
					return url;
				},

				getUrlCheckView: function(url)
				{
					url = this.addToLinkParam(url, 'service', 'gvdrive');
					url = this.addToLinkParam(url, 'document_action', 'checkView');
					return url;
				},

				getUrlStartPublishBlank: function(url, type)
				{
					url = this.addToLinkParam(url, 'service', serviceCode);
					url = this.addToLinkParam(url, 'type', type);
					return url;
				},


				getUrlCommitBlank: function(url, type, targetFolderId)
				{
					url = this.addToLinkParam(url, 'service', serviceCode);
					url = this.addToLinkParam(url, 'document_action', 'saveBlank');
					url = this.addToLinkParam(url, 'type', type);
					if(targetFolderId)
					{
						url = this.addToLinkParam(url, 'targetFolderId', targetFolderId);
					}
					return url;
				},

				getUrlRenameFile: function(url)
				{
					url = this.addToLinkParam(url, 'service', serviceCode);
					url = this.addToLinkParam(url, 'document_action', 'rename');
					return url;
				},

				getUrlCopyToMe: function(url)
				{
					url = this.addToLinkParam(url, 'action', 'copyToMe');
					return url;
				},

				getUrlEditFile: function(url, service)
				{
					url = this.addToLinkParam(url, 'service', serviceCode);
					return url;
				},

				getUrlCommitFile: function(url)
				{
					url = this.addToLinkParam(url, 'service', serviceCode);
					url = this.addToLinkParam(url, 'document_action', 'commit');
					return url;
				},

				getUrlDiscardFile: function(url)
				{
					url = this.addToLinkParam(url, 'service', serviceCode);
					url = this.addToLinkParam(url, 'document_action', 'discard');
					return url;
				},

				getUrlDiscardBlankFile: function(url)
				{
					url = this.addToLinkParam(url, 'service', serviceCode);
					url = this.addToLinkParam(url, 'document_action', 'discardBlank');
					return url;
				},

				addToLinkParam: function(link, name, value)
				{
					if(!link.length)
					{
						return '?' + name + '=' + value;
					}
					link = BX.util.remove_url_param(link, name);
					if(link.indexOf('?') != -1)
					{
						return link + '&' + name + '=' + value;
					}
					return link + '?' + name + '=' + value;
				}
			}
		}
	};
})();