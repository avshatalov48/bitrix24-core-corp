BX.namespace("BX.Disk");
BX.Disk.FolderToolbarClass = (function () {

	var FolderToolbarClass = function (parameters) {
		this.id = parameters.id;
		this.destFormName = parameters.destFormName;
		this.toolbarContainer = parameters.toolbarContainer || null;
		this.createBlankFileUrl = parameters.createBlankFileUrl;
		this.renameBlankFileUrl = parameters.renameBlankFileUrl;
		this.targetFolderId = parameters.targetFolderId;
		this.defaultService = parameters.defaultService;
		this.defaultServiceLabel = parameters.defaultServiceLabel;

		this.ajaxUrl = '/bitrix/components/bitrix/disk.folder.toolbar/ajax.php';

		this.setEvents();
	};

	FolderToolbarClass.prototype.setEvents = function () {
	};

	FolderToolbarClass.prototype.emptyTrashCan = function () {
		BX.onCustomEvent("onEmptyTrashCan", []);
	};

	FolderToolbarClass.prototype.createFolder = function () {
		BX.Disk.modalWindow({
			modalId: 'bx-disk-create-folder',
			title: BX.message('DISK_FOLDER_TOOLBAR_TITLE_CREATE_FOLDER'),
			contentClassName: '',
			contentStyle: {
				paddingTop: '30px',
				paddingBottom: '70px'
			},
			events: {
				onAfterPopupShow: function () {
					BX.focus(BX('disk-new-create-filename'));
				},
				onPopupClose: function () {
					this.destroy();
				}
			},
			content: [
				BX.create('label', {
					props: {
						className: 'bx-disk-popup-label',
						"for": 'disk-new-create-filename'
					},
					children: [
						BX.create('span', {
							props: {
								className: 'req'
							},
							text: '*'
						}),
						BX.message('DISK_FOLDER_TOOLBAR_LABEL_NAME_CREATE_FOLDER')
					]
				}),
				BX.create('input', {
					props: {
						id: 'disk-new-create-filename',
						className: 'bx-disk-popup-input',
						type: 'text',
						value: ''
					},
					style: {
						fontSize: '16px',
						marginTop: '10px'
					}
				})
			],
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('DISK_FOLDER_TOOLBAR_BTN_CREATE_FOLDER'),
					className: "popup-window-button-accept",
					events: {
						click: BX.delegate(function () {
							var newName = BX('disk-new-create-filename').value;
							if (!newName) {
								BX.focus(BX('disk-new-create-filename'));
								return;
							}

							BX.Disk.ajax({
								method: 'POST',
								dataType: 'json',
								url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'addFolder'),
								data: {
									targetFolderId: this.targetFolderId,
									name: newName
								},
								onsuccess: function (data) {
									if (!data) {
										return;
									}
									if (data.status && data.status == 'success') {
										document.location = BX.Disk.getUrlToShowObjectInGrid(data.folder.id);
									}
									else
									{
										BX.Disk.showModalWithStatusAction(data);
									}
								}
							});
						}, this)
					}
				}),
				new BX.PopupWindowButton({
					text: BX.message('DISK_FOLDER_TOOLBAR_BTN_CLOSE'),
					events: {
						click: function () {
							BX.PopupWindowManager.getCurrentPopup().close();
						}
					}
				})
			]
		});
	};

	FolderToolbarClass.prototype.removeShared = function (params) {
		var entityId = params.item.id;
		var entityName = params.item.name;
		var entityAvatar = params.item.avatar;
		var type = params.type;

		delete entityToNewShared[entityId];

		var child = BX.findChild(BX('bx-disk-popup-shared-people-list'), {attribute: {'data-dest-id': ''+entityId+''}}, true);
		if(child)
		{
			BX.remove(child);
		}
	};

	var entityToNewShared = {};
	var canForwardNewShared = 0;

	FolderToolbarClass.prototype.appendNewShared = function (params) {
		entityToNewShared[params.item.id] = {
			destFormName: this.destFormName,
			item: params.item,
			type: params.type,
			right: 'disk_access_read'
		};
		BX.Disk.appendNewShared(params);
	};

	FolderToolbarClass.prototype.createExtendedFolder = function () {

		entityToNewShared = {};

		BX.onCustomEvent('onCreateExtendedFolder', []);
	};

	FolderToolbarClass.prototype.createFile = function () {
		BX.Disk.modalWindow({
			modalId: 'bx-disk-create-file',
			title: BX.message('DISK_FOLDER_TOOLBAR_MW_CREATE_FILE_TITLE'),
			contentClassName: 'tac bx-disk-create-file-big',
			contentStyle: {
				paddingTop: '30px',
				paddingBottom: '70px'
			},
			events: {
				onPopupClose: function () {
					BX.PopupMenu.destroy('disk_open_menu_with_services');
				}
			},
			content: [
				BX.create('div', {
					props: {
						className: 'bx-disk-create-file-section'
					},
					children: [
						BX.message('DISK_FOLDER_TOOLBAR_MW_CREATE_FILE_TEXT') + ' ',
						BX.create('span', {
							style: {
								cursor: 'pointer'
							},
							children: [
								BX.create('span', {
									text: this.defaultServiceLabel,
									props: {
										className: 'bx-disk-create-file-bdbdotted',
										id: 'bx-disk-default-service-label'
									}
								}),
								BX.create('SPAN', {html: '&nbsp;'}),
								BX.create('span', {
									props: {
										className: 'bx-disk-create-file-big-icon'
									}
								})
							],
							events: {
								click: BX.delegate(function (e) {
									this.openMenuWithServices(BX('bx-disk-default-service-label'));
									BX.PreventDefault(e);
								}, this)
							}
						})
					]
				}),
				BX.create('div', {
					props: {
						className: 'bx-disk-create-file-section'
					},
					children: [
						BX.create('a', {
							text: BX.message('DISK_FOLDER_TOOLBAR_MW_CREATE_TYPE_DOC'),
							props: {
								className: 'bx-disk-create-file-big-icon bx-disk-create-file-big-doc'
							},
							style: {
								cursor: 'pointer'
							},
							events: {
								click: BX.delegate(function (e) {
									this.runCreatingFile('docx');
									BX.PreventDefault(e);
								}, this)
							}
						}),
						BX.create('a', {
							text: BX.message('DISK_FOLDER_TOOLBAR_MW_CREATE_TYPE_XLS'),
							props: {
								className: 'bx-disk-create-file-big-icon bx-disk-create-file-big-table'
							},
							style: {
								cursor: 'pointer'
							},
							events: {
								click: BX.delegate(function (e) {
									this.runCreatingFile('xlsx');
									BX.PreventDefault(e);
								}, this)
							}
						}),
						BX.create('a', {
							text: BX.message('DISK_FOLDER_TOOLBAR_MW_CREATE_TYPE_PPT'),
							props: {
								className: 'bx-disk-create-file-big-icon bx-disk-create-file-big-pres'
							},
							style: {
								cursor: 'pointer'
							},
							events: {
								click: BX.delegate(function (e) {
									this.runCreatingFile('pptx');
									BX.PreventDefault(e);
								}, this)
							}
						})
					]
				})
			]
		});
	};

	FolderToolbarClass.prototype.openMenuWithServices = function (targetElement) {
		var obElementViewer = new BX.CViewer({});
		BX.PopupMenu.show('disk_open_menu_with_services', BX(targetElement), [
				{
					text: BX.message('DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT'),
					className: "bx-viewer-popup-item item-b24",
					href: "#",
					onclick: BX.delegate(function (e) {

						if(BX.CViewer.isEnableLocalEditInDesktop())
						{
							this.setEditService('l');
							BX.adjust(BX('bx-disk-default-service-label'), {text: BX.message('DISK_FOLDER_TOOLBAR_LABEL_LOCAL_BDISK_EDIT')});
							BX.PopupMenu.destroy('disk_open_menu_with_services');
						}
						else
						{
							this.helpDiskDialog();
						}

						return BX.PreventDefault(e);
					}, obElementViewer)
				},
				{
					text: obElementViewer.getNameEditService('google'),
					className: "bx-viewer-popup-item item-gdocs",
					href: "#",
					onclick: BX.delegate(function (e) {
						this.setEditService('google');
						BX.adjust(BX('bx-disk-default-service-label'), {text: this.getNameEditService('google')});
						BX.PopupMenu.destroy('disk_open_menu_with_services');

						return BX.PreventDefault(e);
					}, obElementViewer)
				},
				{
					text: obElementViewer.getNameEditService('office365'),
					className: "bx-viewer-popup-item item-office365",
					href: "#",
					onclick: BX.delegate(function (e) {
						this.setEditService('office365');
						BX.adjust(BX('bx-disk-default-service-label'), {text: this.getNameEditService('office365')});
						BX.PopupMenu.destroy('disk_open_menu_with_services');

						return BX.PreventDefault(e);
					}, obElementViewer)
				},
				{
					text: obElementViewer.getNameEditService('skydrive'),
					className: "bx-viewer-popup-item item-office",
					href: "#",
					onclick: BX.delegate(function (e) {
						this.setEditService('skydrive');
						BX.adjust(BX('bx-disk-default-service-label'), {text: this.getNameEditService('skydrive')});
						BX.PopupMenu.destroy('disk_open_menu_with_services');

						return BX.PreventDefault(e);
					}, obElementViewer)
				}
			],
			{
				angle: {
					position: 'top',
					offset: 45
				},
				autoHide: true,
				overlay: {
					opacity: 0.01
				}
			}
		);
	};

	FolderToolbarClass.prototype.blockFeatures = function () {
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

	FolderToolbarClass.prototype.runCreatingFile = function (documentType) {

		if(BX.message('disk_restriction'))
		{
			this.blockFeatures();
			return;
		}

		var obElementViewer = new BX.CViewer({
			createDoc: true
		});
		var blankDocument = obElementViewer.createBlankElementByParams({
			targetFolderId: this.targetFolderId,
			docType: documentType,
			editUrl: this.createBlankFileUrl,
			renameUrl: this.renameBlankFileUrl
		});
		obElementViewer.setCurrent(blankDocument);
		blankDocument.afterSuccessCreate = function (response) {
			if (response && response.status == 'success') {
				if(!BX.CViewer.isLocalEditService(obElementViewer.initEditService()))
				{
					window.document.location = BX.Disk.getUrlToShowObjectInGrid(response.objectId);
				}
				else
				{
					window.onbeforeunload = function(){
						if(!window.diskUnloadCreateDoc) {
							window.diskUnloadCreateDoc = true;

							setTimeout(function () {
								window.document.location = BX.Disk.getUrlToShowObjectInGrid(response.objectId);
							}, 250);
						}
					}
				}
			}
		};

		obElementViewer.runActionByCurrentElement('create', {obElementViewer: obElementViewer});

		try {
			BX.PreventDefault()
		} catch (e) {
		}
		return false;
	};
	return FolderToolbarClass;
})();
