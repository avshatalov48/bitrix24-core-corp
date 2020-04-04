BX.namespace("BX.Disk");
BX.Disk.TrashCanClass = (function (){

	var TrashCanClass = function (parameters)
	{
		this.gridId = parameters.gridId;
		this.filterId = parameters.filterId;
		this.trashcan = parameters.trashcan;
		this.grid = BX.Main.gridManager.getById(this.gridId);
		this.filter = BX.Main.filterManager.getById(this.filterId);

		this.rootObject = parameters.rootObject || {};

		this.ajaxUrl = '/bitrix/components/bitrix/disk.trashcan/ajax.php';

		this.setEvents();
		this.workWithLocationHash();
	};

	TrashCanClass.prototype.setEvents = function()
	{
		BX.bind(BX('delete_button_' + this.grid.table_id), "click", BX.proxy(this.onClickDeleteGroup, this));
		BX.addCustomEvent("onEmptyTrashCan", BX.proxy(this.openConfirmEmpty, this));
		BX.bind(window, 'hashchange', BX.proxy(this.onHashChange, this));
		BX.bindDelegate(this.grid.instance.container, 'click', {className: 'js-disk-grid-open-folder'}, this.openGridFolder.bind(this));
		BX.bind(window, 'popstate', this.onPopState.bind(this));
		BX.addCustomEvent("BX.Main.Filter:apply", this.onFilterApply.bind(this));
		BX.addCustomEvent("Disk.Page:onChangeFolder", this.onChangeFolder.bind(this));

		if (typeof BX.Bitrix24 !== "undefined" && typeof BX.Bitrix24.PageSlider !== "undefined")
		{
			BX.Bitrix24.PageSlider.bindAnchors({
				rules: [{
					condition: [
						this.trashcan.link
					],
					handler: function (event, link) {

						if (BX.hasClass(link.anchor, 'main-ui-pagination-page') || BX.hasClass(link.anchor, 'main-ui-pagination-arrow'))
						{
							//skip link which used in grid navigration.
							return;
						}

						if (this.onOpenFolder(link.anchor))
						{
							event.preventDefault();
						}

					}.bind(this)
				}]
			});
		}
	};

	TrashCanClass.prototype.workWithLocationHash = function()
	{
		setTimeout(BX.delegate(function(){
			this.onHashChange();
		}, this), 350);
	};

	TrashCanClass.prototype.onHashChange = function()
	{
		var matches = document.location.hash.match(/hl-([0-9]+)/g);
		if(matches)
		{
			var command = (document.location.hash.match(/!([a-zA-Z]+)/g) || []).pop();
			for (var i in matches) {
				if (!matches.hasOwnProperty(i)) {
					continue;
				}
				var hl = matches[i];
				var number = hl.match(/hl-([0-9]+)/);
				if(number && number[1])
				{
					var row = this.getRow(number[1]);

					if(row)
					{
						this.scrollToRow(row);
						row.select();
						this.runCommandOnObjectId(command, number[1]);
					}
					else if(command)
					{
						//we didn't find object on current page. May be it will be shown after reload :)
						if(window.BXIM && BXIM.isOpenNotify())
						{
							document.location.reload();
							BXIM.closeMessenger();
						}
					}
				}
			}

			if(window.BXIM && BXIM.isOpenNotify())
			{
				BXIM.closeMessenger();
			}
		}
	};

	TrashCanClass.prototype.onFilterApply = function (filterId, data, filter, promise)
	{
		if (filterId !== this.filterId)
		{
			return;
		}

		this.runAfterFilterOpenFolder = this.resetFilter.bind(this);
	};

	TrashCanClass.prototype.resetFilter = function ()
	{
		this.isFiltetedFolderList = false;
		this.filter.getApi().setFields({});
		this.filter.getSearch().clearForm();
		this.filter.getSearch().adjustPlaceholder();
	};

	TrashCanClass.prototype.onOpenFolder = function(link)
	{
		var folder = {
			id: link.dataset.objectId,
			link: link.getAttribute('href'),
			name: link.textContent.trim(),
			node: link
		};

		if (!folder.id)
		{
			return false;
		}

		BX.onCustomEvent("Disk.TrashCanClass:onFolderBeforeOpen", [folder]);

		window.history.pushState(
			{
				disk: true,
				folder: {
					id: folder.id,
					name: folder.name
				}
			},
			null,
			folder.link
		);

		this.openFolder(folder.id, folder);

		return true;
	};

	TrashCanClass.prototype.openFolder = function(folderId, folder)
	{
		this.grid.instance.reloadTable('POST', {
			resetFilter: 1
		}, function(){
			BX.onCustomEvent("Disk.TrashCanClass:onFolderOpen", [folder, this.isFiltetedFolderList]);
			BX.Disk.Page.changeFolder({
				id: folder.id,
				name: folder.name
			});
		}.bind(this), folder.link);
	};

	TrashCanClass.prototype.openGridFolder = function(event)
	{
		if(event.shiftKey || event.ctrlKey)
		{
			return;
		}

		var element = event.target || event.srcElement;
		if(!element.dataset.objectId)
		{
			return;
		}

		var row = this.getRow(element.dataset.objectId);
		var a = BX.findChildByClassName(row.node, 'js-disk-grid-folder');
		BX.fireEvent(a, 'click');
	};

	TrashCanClass.prototype.onPopState = function(e)
	{
		var state = e.state;
		if(!state || !state.disk || !state.folder)
		{
			window.location.reload();
			return;
		}

		if(!state.folder.link)
		{
			state.folder.link = window.location.pathname.toString();
		}

		BX.onCustomEvent("Disk.TrashCanClass:onPopState", [state.folder, true]);

		this.openFolder(state.folder.id, state.folder);
	};

	TrashCanClass.prototype.onChangeFolder = function (folder, newFolder)
	{
		if (BX.type.isFunction(this.runAfterFilterOpenFolder))
		{
			this.runAfterFilterOpenFolder();
			this.runAfterFilterOpenFolder = null;

			var folderState = history.state.folder;
			folderState.link = window.location.pathname.toString();

			BX.onCustomEvent("Disk.TrashCanClass:openFolderAfterFilter", [folderState, true]);
		}
	};

	TrashCanClass.prototype.removeRow = function(objectId)
	{
		this.grid.instance.removeRow(objectId);
	};

	TrashCanClass.prototype.getRow = function(objectId)
	{
		return this.grid.instance.getRows().getById(objectId);
	};

	TrashCanClass.prototype.openConfirmDelete = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var isFolder = parameters.object.isFolder;
		var messageDescription = '';
		if (isFolder) {
			messageDescription = BX.message('DISK_TRASHCAN_TRASH_DELETE_DESTROY_FOLDER_CONFIRM');
		} else {
			messageDescription = BX.message('DISK_TRASHCAN_TRASH_DELETE_DESTROY_FILE_CONFIRM');
		}
		var buttons = [
			new BX.PopupWindowButton({
				text: BX.message('DISK_TRASHCAN_TRASH_DESTROY_BUTTON'),
				className: "popup-window-button-accept",
				events: {
					click: BX.delegate(function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						BX.Disk.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'destroy'),
							data: {
								objectId: objectId
							},
							onsuccess: BX.delegate(function (data)
							{
								if (!data) {
									return;
								}
								if (data.status == 'success') {
									this.removeRow(objectId);

									BX.Disk.showModalWithStatusAction(data);
									return;
								}
								BX.Disk.showModalWithStatusAction(data);
							}, this)
						});

						return false;
					}, this)
				}
			}),
			new BX.PopupWindowButton({
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				events: {
					click: function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_TRASHCAN_TRASH_DELETE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	TrashCanClass.prototype.openConfirmRestore = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var isFolder = parameters.object.isFolder;
		var messageDescription = '';
		if (isFolder) {
			messageDescription = BX.message('DISK_TRASHCAN_TRASH_RESTORE_FOLDER_CONFIRM');
		} else {
			messageDescription = BX.message('DISK_TRASHCAN_TRASH_RESTORE_FILE_CONFIRM');
		}
		var buttons = [
			new BX.PopupWindowButton({
				text: BX.message('DISK_TRASHCAN_TRASH_RESTORE_BUTTON'),
				className: "popup-window-button-accept",
				events: {
					click: BX.delegate(function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						BX.Disk.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'restore'),
							data: {
								objectId: objectId
							},
							onsuccess: BX.delegate(function (data)
							{
								if (!data) {
									return;
								}
								if (data.status == 'success') {
									this.removeRow(objectId);

									BX.Disk.showModalWithStatusAction(data);
									return;
								}
								BX.Disk.showModalWithStatusAction(data);
							}, this)
						});

						return false;
					}, this)
				}
			}),
			new BX.PopupWindowButton({
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				events: {
					click: function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_TRASHCAN_TRASH_RESTORE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	var stopEmptyTrashCan = false;
	var countItemsToDestroy = 0;
	function destroyPortion()
	{
		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'destroyPortion'),
			data: {
				objectId: this.rootObject.id
			},
			onsuccess: BX.delegate(function (data)
			{
				if (!data || data.status != 'success')
					BX.Disk.showModalWithStatusAction(data);

				countItemsToDestroy -= data.countItems;
				if(countItemsToDestroy < 0)
				{
					countItemsToDestroy = 0;
				}
				var container = BX('bx-elements-to-destroy');
				BX.adjust(container, {
					text: countItemsToDestroy
				});

				if(countItemsToDestroy && !stopEmptyTrashCan)
				{
					BX.delegate(destroyPortion, this)();
				}
				else
				{
					BX.reload();
				}

			}, this)
		});
	}

	TrashCanClass.prototype.openConfirmEmpty = function()
	{
		var messageDescription = BX.message('DISK_TRASHCAN_TRASH_EMPTY_FOLDER_CONFIRM');

		var buttons = [
			new BX.PopupWindowButton({
				text: BX.message('DISK_TRASHCAN_TRASH_EMPTY_BUTTON'),
				id: 'bx-disk-btn-start-trashcan',
				className: "popup-window-button-accept",
				events: {
					click: BX.delegate(function (e)
					{
						BX.PreventDefault(e);

						BX.remove(BX('bx-disk-btn-start-trashcan'));
						BX.remove(BX('bx-disk-btn-cancel-trashcan'));
						BX.show(BX('bx-disk-btn-stop-empty-trashcan'), 'inline-block');

						BX.Disk.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'calculate'),
							data: {
								objectId: this.rootObject.id
							},
							onsuccess: BX.delegate(function (data)
							{
								if(!data || data.status != 'success')
									BX.Disk.showModalWithStatusAction(data);

								var container = BX('bx-empty-trashcan-container');
								BX.cleanNode(container);

								countItemsToDestroy = data.countItems;
								BX.adjust(container, {
									children: [
										BX.create('span', {
											text: BX.message('DISK_TRASHCAN_TRASH_COUNT_ELEMENTS')
										}),
										BX.create('span', {
											props: {
												id: 'bx-elements-to-destroy'
											},
											text: data.countItems
										}),
										BX.create('span', {
											style: {
												margin: '0 auto',
												backgroundColor: 'transparent',
												border: 'none',
												position: 'relative'
											},
											props: {
												id: 'wd_progress',
												className: 'bx-core-waitwindow'
											}
										})
									]
								});
								BX.delegate(destroyPortion, this)();

							}, this)
						});
					}, this)
				}
			}),
			new BX.PopupWindowButton({
				id: 'bx-disk-btn-cancel-trashcan',
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				events: {
					click: function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			}),
			new BX.PopupWindowButton({
				id: 'bx-disk-btn-stop-empty-trashcan',
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_STOP_BUTTON'),
				events: {
					click: function (e)
					{
						stopEmptyTrashCan = true;
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-empty-trashcan-modal',
			title: BX.message('DISK_TRASHCAN_TRASH_EMPTY_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			events: {
				onPopupShow: function(){
					BX.hide(BX('bx-disk-btn-stop-empty-trashcan'));
				}
			},
			content: [
				BX.create('div', {
					props: {
						id: 'bx-empty-trashcan-container'
					},
					text: messageDescription
				})
			],
			buttons: buttons
		});
	};

	TrashCanClass.prototype.openConfirmDeleteGroup = function (parameters)
	{
		var messageDescription = BX.message('DISK_TRASHCAN_TRASH_DELETE_GROUP_CONFIRM');
		var buttons = [
			new BX.PopupWindowButton({
				text: BX.message('DISK_TRASHCAN_TRASH_DESTROY_BUTTON'),
				className: "popup-window-button-accept",
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						this.grid.ActionDelete();
						return false;
					}, this)
				}
			}),

			new BX.PopupWindowButton({
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_TRASHCAN_TRASH_DELETE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription,
			buttons: buttons
		});
	};

	TrashCanClass.prototype.openConfirmRestoreGroup = function (parameters)
	{
		var messageDescription = BX.message('DISK_TRASHCAN_TRASH_RESTORE_GROUP_CONFIRM');
		var buttons = [
			new BX.PopupWindowButton({
				text: BX.message('DISK_TRASHCAN_TRASH_RESTORE_BUTTON'),
				className: "popup-window-button-accept",
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						this.grid.SetActionName('restore');
						this.actionGroupButton = 'restore';
						this.grid.SetActionName('restore');

						BX.submit(this.grid.GetForm());

						return false;
					}, this)
				}
			}),

			new BX.PopupWindowButton({
				text: BX.message('DISK_TRASHCAN_TRASH_CANCEL_DELETE_BUTTON'),
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);
						return false;
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_TRASHCAN_TRASH_RESTORE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription,
			buttons: buttons
		});
	};

	return TrashCanClass;
})();
