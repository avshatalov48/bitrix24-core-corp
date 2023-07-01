BX.namespace("BX.Disk");
BX.Disk.FolderListClass = (function (){

	var FolderListClass = function (parameters)
	{
		this.layout = parameters.layout || {};
		this.errors = parameters.errors || [];
		this.showSearchNotice = parameters.showSearchNotice;
		this.information = parameters.information || '';

		this.currentFolder = parameters.currentFolder || {};
		this.gridId = parameters.gridId;
		this.isTrashMode = parameters.isTrashMode;
		this.filterValueToSkipSearchUnderLinks = parameters.filterValueToSkipSearchUnderLinks || {};
		this.filterId = parameters.filterId;

		if (BX.Main.gridManager)
		{
			this.commonGrid = new BX.Disk.Model.FolderList.CommonGrid({
				instance: BX.Main.gridManager.getById(this.gridId).instance
			});
		}
		else
		{
			this.commonGrid = new BX.Disk.Model.FolderList.CommonGrid({
				instance: BX.Main.tileGridManager.getById(this.gridId).instance
			});
		}

		this.filter = BX.Main.filterManager.getById(this.filterId);

		this.relativePath = parameters.relativePath;
		this.gridShowTreeButton = BX(parameters.gridShowTreeButton);
		this.rootObject = parameters.rootObject || {};
		this.storage = parameters.storage || {};
		this.storage.manage = this.storage.manage || {};
		this.enabledModZip = parameters.enabledModZip || false;
		this.enabledExternalLink = parameters.enabledExternalLink;
		this.enabledObjectLock = parameters.enabledObjectLock;
		this.getFilesCountAndSize = parameters.getFilesCountAndSize || {};
		this.cacheExternalLinks = {};
		this.createBlankFileUrl = parameters.createBlankFileUrl;
		this.renameBlankFileUrl = parameters.renameBlankFileUrl;
		this.defaultService = parameters.defaultService;
		this.defaultServiceLabel = parameters.defaultServiceLabel;

		this.sortFields = parameters.sortFields;
		this.sort = parameters.sort;

		this.actionGroupButton = parameters.actionGroupButton || 'move';
		this.isBitrix24 = parameters.isBitrix24 || false;

		this.destFormName = parameters.destFormName || 'folder-list-destFormName';

		this.ajaxUrl = '/bitrix/components/bitrix/disk.folder.list/ajax.php';
		this.baseGridPageUrl = document.location.toString();

		BX.Disk.Page.changeFolder({
			id: this.currentFolder.id,
			name: this.currentFolder.name
		});

		BX.Disk.Page.changeStorage(this.storage);

		this.setEvents();

		if (this.shouldUseHistory())
		{
			window.history.replaceState(
				{
					disk: true,
					folder: {
						id: this.currentFolder.id
					}
				},
				null,
				this.baseGridPageUrl
			);
		}

		this.workWithLocationHash();
		this.processCommand();

		if(this.errors.length)
			this.showErrors();
		if(this.information.length)
			this.showInformation();

		if (!this.currentFolder.canAdd)
		{
			this.blockCreateItemsButton();
		}

		if (this.needRunFilterUnderLinks())
		{
			this.rerunFilter();
		}
	};

	FolderListClass.prototype.rerunFilter = function()
	{
		var fakePromise = new BX.Promise();
		this.onFilterApply(this.filter.getParam('FILTER_ID'), {}, this.filter, fakePromise);
		fakePromise.fulfill();
	};

	FolderListClass.prototype.showInformation = function()
	{
		BX.Disk.showModalWithStatusAction({status: 'success', message: this.information});
	};

	FolderListClass.prototype.showErrors = function()
	{
		BX.Disk.showModalWithStatusAction({status: 'error', errors: this.errors});
	};

	FolderListClass.prototype.onHashChange = function()
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
					if (this.commonGrid.getItemById(number[1]))
					{
						this.commonGrid.scrollTo(number[1]);
						this.commonGrid.selectItemById(number[1]);
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

	FolderListClass.prototype.processCommand = function()
	{
		if (BX.Disk.getUrlParameter('cmd') === 'openSliderBp')
		{
			if (this.storage.bpListLink)
			{
				this.openSlider(this.storage.bpListLink);
			}
		}
	};

	FolderListClass.prototype.shouldUseHistory = function()
	{
		return top === window;
	};

	FolderListClass.prototype.workWithLocationHash = function()
	{
		setTimeout(BX.delegate(function(){
			this.onHashChange();
		}, this), 350);
	};

	FolderListClass.prototype.setEvents = function()
	{
		BX.bind(this.getFilesCountAndSize.button, 'click', BX.proxy(this.onClickGetFilesCountAndSizeButtonButton, this));
		BX.bind(window, 'hashchange', BX.proxy(this.onHashChange, this));
		BX.bindDelegate(this.commonGrid.getContainer(), 'click', {className: 'js-disk-grid-open-folder'}, this.openGridFolder.bind(this));
		BX.bind(this.sort.layout.label, 'click', this.showGridSortingMenu.bind(this));

		for (var i = 0; i < this.layout.changeViewButtons.length; ++i)
		{
			BX.bind(BX(this.layout.changeViewButtons[i]), 'click', this.changeView.bind(this));
		}

		BX.SidePanel.Instance.bindAnchors({
			rules: [
				{
					condition: [
						this.storage.link,
						this.storage.trashLink
					],
					handler: function (event, link) {

						if (BX.hasClass(link.anchor, 'main-ui-pagination-page') || BX.hasClass(link.anchor, 'main-ui-pagination-arrow'))
						{
							//skip link which used in grid navigation.
							return;
						}

						if (this.onOpenFolderByAnchor(link.anchor))
						{
							event.preventDefault();
						}

					}.bind(this)
				},
				{
					condition: [
						this.storage.fileLinkPrefix,
						this.storage.trashFileLinkPrefix
					],
					handler: function(event, link) {
						if (!link.anchor.dataset.bxViewer && !link.anchor.dataset.viewer)
						{
							BX.SidePanel.Instance.open(link.url);
							event.preventDefault();
						}
						//else we have BX.Viewer which bind in disk.folder.list/templates/.default/template.php
					}
				}
			]
		});

		BX.addCustomEvent('SidePanel.Slider:onMessage', this.onSliderMessage.bind(this));
		BX.addCustomEvent('Disk.OnlyOffice:onSaved', this.handleDocumentSaved.bind(this));

		BX.bind(window, 'popstate', this.onPopState.bind(this));

		BX.addCustomEvent("onIframeElementLoadDataToView", BX.proxy(this.onIframeElementLoadDataToView, this));
		BX.addCustomEvent("onBeforeElementShow", BX.proxy(this.onBeforeElementShow, this));

		BX.addCustomEvent("onCreateExtendedFolder", BX.proxy(this.onCreateExtendedFolder, this));

		BX.addCustomEvent("Grid::beforeRequest", this.onBeforeGridRequest.bind(this));
		BX.addCustomEvent("BX.TileGrid.Grid:beforeReload", this.onBeforeGridRequest.bind(this));
		if (this.commonGrid.isGrid())
		{
			BX.addCustomEvent(this.commonGrid.getContainer(), "Grid::optionsChanged", this.onGridOptionsChanged.bind(this));
		}
		BX.addCustomEvent("Grid::updated", this.onGridUpdated.bind(this));
		BX.addCustomEvent("BX.Main.Filter:beforeApply", this.onBeforeFilterApply.bind(this));
		BX.addCustomEvent("BX.Main.Filter:apply", this.onFilterApply.bind(this));

		BX.addCustomEvent("onPopupFileUploadClose", this.onPopupFileUploadClose.bind(this));

		if (this.isTrashMode)
		{
			BX.addCustomEvent("onStepperProgress", this.onStepperHasBeenFinished.bind(this));
			BX.addCustomEvent("onStepperHasBeenFinished", this.onStepperHasBeenFinished.bind(this));
		}

		BX.addCustomEvent("Disk.Page:onChangeFolder", this.onChangeFolder.bind(this));
		BX.addCustomEvent("Disk.TileItem.Item:onItemDblClick", this.onItemDblClick.bind(this));
		BX.addCustomEvent("Disk.TileItem.Item:onItemEnter", this.onItemDblClick.bind(this));
		BX.addCustomEvent(this.commonGrid.instance, "TileGrid.Grid:onItemRemove", this.handleItemRemoveByTileGrid.bind(this));
		BX.addCustomEvent(this.commonGrid.instance, "TileGrid.Grid:onItemMove", this.onItemMove.bind(this));

		BX.addCustomEvent("Disk:onChangeDocumentService", this.onChangeDocumentService.bind(this));
	};

	FolderListClass.prototype.onBeforeFilterApply = function (filterId, data, filter, promise) {
		if (filterId !== this.filterId)
		{
			return;
		}

		this.isFiltetedFolderList = true;
		promise.then(function () {
			if (filter.getSearch().getSearchString() || filter.getSearch().getSquares().length)
			{
				this.blockSorting();
			}
			else
			{
				this.unblockSorting();
			}
		}.bind(this));
	};

	FolderListClass.prototype.blockSorting = function ()
	{
		if (this.commonGrid.isGrid())
		{
			this.commonGrid.instance.blockSorting();
		}
		this.sort.layout.label.style.pointerEvents = 'none';
	};

	FolderListClass.prototype.unblockSorting = function ()
	{
		if (this.commonGrid.isGrid())
		{
			this.commonGrid.instance.unblockSorting();
		}
		this.sort.layout.label.style.pointerEvents = null;
	};

	var _getSymlinksUnderObjectId = [];
	FolderListClass.prototype.getSymlinksUnderObjectId = function (object)
	{
		var objectId = object.id;
		if(_getSymlinksUnderObjectId[objectId] !== undefined)
		{
			var result = new BX.Promise();
			result.fulfill(_getSymlinksUnderObjectId[objectId]);

			return result;
		}
		var promise = new BX.Promise();

		BX.Disk.ajax({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSymlinks'),
			data: object,
			onsuccess: function (data) {
				if (!data || !data.status || data.status !== 'success')
				{
					return;
				}

				_getSymlinksUnderObjectId[objectId] = data.items;
				promise.fulfill(_getSymlinksUnderObjectId[objectId]);
			}.bind(this)
		});

		return promise;
	};

	FolderListClass.prototype.needRunFilterUnderLinks = function ()
	{
		if (!this.filter.getSearch().getSearchString() && !this.filter.getSearch().getSquares().length)
		{
			return false;
		}

		for(var field in this.filterValueToSkipSearchUnderLinks)
		{
			if(!this.filterValueToSkipSearchUnderLinks.hasOwnProperty(field))
			{
				continue;
			}

			var value = this.filterValueToSkipSearchUnderLinks[field];
			var filterFieldsValues = this.filter.getFilterFieldsValues();
			if(filterFieldsValues[field] == value)
			{
				return false;
			}
		}

		return true;
	};

	FolderListClass.prototype.showPopupWindowInfo = function(target) {

		this.popupWindowInfo = new BX.PopupWindow('disk-folder-list-popup-info', target, {
			className: 'disk-folder-list-popup-info',
			autoHide: true,
			zIndex: 200,
			closeByEsc: true,
			offsetTop: 5,
			width: target.offsetWidth - 20,
			content: '<span class="disk-folder-list-popup-info-content">' + BX.message('DISK_FOLDER_LIST_SEARCH_INDEX_NOTICE_1') + '</span>'
		});

		this.popupWindowInfo.show();
	};

	FolderListClass.prototype.onFilterApply = function (filterId, data, filter, promise, params)
	{
		if (this.showSearchNotice && filter.getSearch().getSearchString())
		{
			setTimeout(function() {
				this.showPopupWindowInfo(filter.search.container);
			}.bind(this), 200);
		}

		if (filterId !== this.filterId)
		{
			return;
		}

		if (this.commonGrid.isTile())
		{
			promise = promise.then(function () {
				return this.commonGrid.reload();
			}.bind(this));
		}

		if (!this.needRunFilterUnderLinks())
		{
			return;
		}

		this.layout.fileListContainer.classList.add('disk-running-filter');

		this.runAfterFilterOpenFolder = this.resetFilter.bind(this);

		var folder = BX.Disk.Page.getFolder();
		folder.link = window.location.pathname.toString();

		var isTimeToStopSearch = function() {
			var currentFolderInGrid = BX.Disk.Page.getFolder();
			return currentFolderInGrid && currentFolderInGrid.id != folder.id;
		};

		promise.then(function () {

				if (!this.commonGrid.countItems())
				{
					if (this.commonGrid.isTile())
					{
						this.commonGrid.instance.removeEmptyBlock();
						this.commonGrid.instance.setMinHeightContainer();
					}
					this.commonGrid.fade();
				}

				this.getSymlinksUnderObjectId(folder).then(function (items) {

					var promise = new BX.Promise();
					var firstPromise = promise;

					items.forEach(function (symlink, index) {

						promise = promise.then(function () {
							if (isTimeToStopSearch())
							{
								return;
							}

							var promise = new BX.Promise();

							var grid = this.commonGrid.instance;
							var data = {
								viewGridStorageId: BX.Disk.Page.getStorage().id
							};

							if (items.length === index + 1)
							{
								this.removeSearchProcessInConnectedFolders();
							}
							else if (this.commonGrid.countItems() > 0)
							{
								this.showSearchProcessInConnectedFolders();
							}

							if (this.commonGrid.isGrid())
							{
								grid.getData().request(symlink.link, 'POST', data, null, function () {
									if (isTimeToStopSearch())
									{
										return;
									}

									var bodyRows = BX.Grid.Utils.getByClass(this.getResponse(), grid.settings.get('classBodyRow'));

									if (
										BX.type.isArray(bodyRows) && bodyRows.length === 1 &&
										BX.hasClass(bodyRows[0], grid.settings.get('classEmptyRows'))
									)
									{
									}
									else if (BX.type.isArray(bodyRows) && bodyRows.length === 0 || !BX.type.isArray(bodyRows))
									{
									}
									else
									{
										BX.remove(BX.Grid.Utils.getByClass(grid.getContainer(), grid.settings.get('classEmptyRows'), true));
										grid.adjustEmptyTable(bodyRows);
										grid.getUpdater().appendBodyRows(bodyRows);
										grid.getRows().reset();
										grid.bindOnRowEvents();

										grid.updateCounterDisplayed();
										grid.updateCounterSelected();

										grid.tableUnfade();
									}

									promise.fulfill();
								});
							}
							else
							{
								var ajaxPromise = BX.ajax.promise({
									url: BX.util.add_url_param(symlink.link, {
										grid_id: this.commonGrid.getId(),
										internal: true
									}),
									method: 'POST',
									dataType: 'json',
									data: data
								});

								ajaxPromise.then(function (response) {
									if (this.commonGrid.countItems())
									{
										this.commonGrid.unFade();
									}

									if (isTimeToStopSearch())
									{
										return;
									}

									response.data.tileGrid.items.forEach(function (item) {
										this.commonGrid.instance.appendItem(item);
									}, this);

									promise.fulfill();

								}.bind(this));
							}

							return promise;

						}.bind(this));

					}, this);

					promise.then(function () {
						this.layout.fileListContainer.classList.remove('disk-running-filter');
						this.commonGrid.unFade();

						if (!this.commonGrid.countItems() && this.commonGrid.isTile())
						{
							this.commonGrid.instance.setMinHeightContainer();
							this.commonGrid.instance.appendEmptyBlock();
						}
					}.bind(this));

					firstPromise.fulfill();

				}.bind(this));
			}.bind(this)
		);
	};

	FolderListClass.prototype.resetFilter = function ()
	{
		this.isFiltetedFolderList = false;
		this.filter.getApi().setFields({});
		this.filter.getSearch().clearForm();
		this.filter.getSearch().adjustPlaceholder();
	};

	FolderListClass.prototype.onChangeDocumentService = function (service)
	{
		var shouldHide = service !== 'l';

		this.commonGrid.getIds().forEach(function(objectId){
			var actionEdit = this.commonGrid.getActionById(objectId, 'edit');
			if (!actionEdit)
			{
				return;
			}
			actionEdit.hide = shouldHide;
			var menu = this.commonGrid.getActionsMenu(objectId);
			menu.getMenuItem('edit').hide = shouldHide;

			if (shouldHide)
			{
				BX.addClass(menu.getMenuItem('edit').layout.item, 'disk-popup-menu-hidden-item');
			}
			else
			{
				BX.removeClass(menu.getMenuItem('edit').layout.item, 'disk-popup-menu-hidden-item');
			}
		}.bind(this));
	};

	FolderListClass.prototype.onItemMove = function (sourceItem, destinationItem)
	{
		if (this.isTrashMode)
		{

		}
		else
		{
			BX.ajax.runAction('disk.api.commonActions.move', {
				analyticsLabel: 'folder.list.dd',
				data: {
					objectId: sourceItem.getId(),
					toFolderId: destinationItem.getId()
				}
			});
		}
	};

	FolderListClass.prototype.handleItemRemoveByTileGrid = function (item)
	{
		if (!this.isTrashMode)
		{
			BX.ajax.runAction('disk.api.commonActions.markDeleted', {
				analyticsLabel: 'folder.list.dd',
				data: {
					objectId: item.getId()
				}
			});
		}
	};

	FolderListClass.prototype.onItemDblClick = function (item)
	{
		if (item.isFolder)
		{
			this.onOpenFolder({
				id: item.getId(),
				name: item.name,
				link: item.item.titleLink.href
			});
		}
		else
		{
			//BX.SidePanel.Instance.open(item.link);
			BX.fireEvent(item.item.titleLink, 'click');
		}
	};

	FolderListClass.prototype.onChangeFolder = function (folder, newFolder)
	{
		if (BX.type.isFunction(this.runAfterFilterOpenFolder))
		{
			this.runAfterFilterOpenFolder();
			this.runAfterFilterOpenFolder = null;

			var folderState = history.state.folder;
			folderState.link = window.location.pathname.toString();

			BX.onCustomEvent("Disk.FolderListClass:openFolderAfterFilter", [folderState]);
		}
	};

	FolderListClass.prototype.onGridUpdated = function ()
	{
	};

	FolderListClass.prototype.onGridOptionsChanged = function (grid)
	{
		var options = grid.getUserOptions().getCurrentOptions();
		this.sort.sortBy = options.last_sort_by;
		this.sort.direction = options.last_sort_order;

		var sortByItem = this.sortFields.find(function(item) {
			return item.field === this.sort.sortBy;
		}, this);

		if (sortByItem)
		{
			BX.adjust(this.sort.layout.label, {
				text: sortByItem.label
			});
		}
	};

	FolderListClass.prototype.onBeforeGridRequest = function (ctx, requestParams)
	{
		if (this.gridId !== requestParams.gridId)
		{
			return;
		}

		if (!requestParams.url)
		{
			requestParams.url = this.baseGridPageUrl;
		}

		if (requestParams.data.controls)
		{
			var obj = {};
			requestParams.data.rows.forEach(function(e){
				obj[e] = {ID: e};
			});

			requestParams.data.rows = obj;
		}

		if (requestParams.data.FIELDS || requestParams.data.ID)
		{
			if(requestParams.data.FIELDS)
			{
				requestParams.data.rows = requestParams.data.FIELDS;
			}
			else if(requestParams.data.ID)
			{
				var objId = {};
				requestParams.data.ID.forEach(function(id){
					objId[id] = {ID: id};
				});

				requestParams.data.rows = objId;
			}

			requestParams.data.FIELDS = null;
			requestParams.data.ID = null;
			requestParams.data.controls = requestParams.data.controls || {};

			if(this.commonGrid.isGrid())
			{
				requestParams.data.controls = BX.mergeEx(requestParams.data.controls, BX.clone(requestParams.data));
			}
		}
	};

	FolderListClass.prototype.runCommandOnObjectId = function(command, objectId)
	{
		if(!command)
		{
			return;
		}

		switch (command.toLowerCase())
		{
			case '!disconnect':
			case '!detach':
				var menuItem = this.commonGrid.getActionsMenu(objectId).getMenuItem(command.toLowerCase().substr(1));
				if(!menuItem || !menuItem.onclick)
				{
					return;
				}

				eval(menuItem.onclick);
				break;
			case '!share':
				var menuItem = this.commonGrid.getActionsMenu(objectId).getMenuItem('share-section');
				if(!menuItem || !menuItem.hasSubMenu())
				{
					return;
				}
				menuItem.addSubMenu(menuItem._items);
				menuItem = menuItem.getSubMenu().getMenuItem(command.toLowerCase().substr(1));
				if(!menuItem || !menuItem.onclick)
				{
					return;
				}

				eval(menuItem.onclick);
				break;
			case '!show':
				var linkWithObject = BX('disk_obj_' + objectId);
				if(!!linkWithObject) {
					BX.fireEvent(linkWithObject, 'click');
				}
				break;
			default:
				break;
		}
	};

	//todo create object which will describe folder/file.
	function getObjectDataId(objectId)
	{
		var row = this.getRow(objectId);
		return {
			row: this.getRow(objectId),
			title: BX.findChild(row.node, {
				tagName: 'a',
				className: 'bx-disk-folder-title'
			}, true),
			icon: BX.findChild(row.node, function(node){
				return BX.type.isElementNode(node) && (BX.hasClass(node, 'bx-disk-file-icon') || BX.hasClass(node, 'bx-disk-folder-icon'));
			}, true)
		}
	}

	function getIconElementByObjectId(objectId)
	{
		var row = this.getRow(objectId);
		return BX.findChild(row.node, function(node){
			return BX.type.isElementNode(node) && (BX.hasClass(node, 'bx-disk-file-icon') || BX.hasClass(node, 'bx-disk-folder-icon'));
		}, true);
	}

	FolderListClass.prototype.scrollToObject = function (objectId)
	{
		var row = this.getRow(objectId);
		this.scrollToRow(row);
	};

	FolderListClass.prototype.scrollToRow = function (row)
	{
		var rowNode = row.node;

		(new BX.easing({
			duration : 500,
			start : { scroll : window.pageYOffset || document.documentElement.scrollTop },
			finish : { scroll : BX.pos(rowNode).top },
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step : function(state){
				window.scrollTo(0, state.scroll);
			}
		})).animate();
	};

	FolderListClass.prototype.openMenuWithServices = function (targetElement) {
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

	FolderListClass.prototype.blockFeatures = function () {
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

	FolderListClass.prototype.runCreatingFile = function (documentType, service) {

		if (BX.message('disk_restriction'))
		{
			this.blockFeatures();
			return;
		}

		if (service === 'l' && BX.Disk.Document.Local.Instance.isEnabled())
		{
			BX.Disk.Document.Local.Instance.createFile({
				type: documentType,
				targetFolderId: BX.Disk.Page.getFolder().id
			}).then(function (response) {
				this.commonGrid.reload(BX.Disk.getUrlToShowObjectInGrid(response.object.id))
			}.bind(this));

			return;
		}

		var createProcess = new BX.Disk.Document.CreateProcess({
			typeFile: documentType,
			targetFolderId: BX.Disk.Page.getFolder().id,
			serviceCode: service,
			onAfterSave: function(response) {
				if (response.status === 'success')
				{
					this.commonGrid.reload(BX.Disk.getUrlToShowObjectInGrid(response.object.id))
				}
			}.bind(this)
		});

		createProcess.start();
	};

	FolderListClass.prototype.changeView = function (event)
	{
		var link = BX.getEventTarget(event);
		if (link && this.commonGrid.isTile())
		{
			event.preventDefault();

			this.commonGrid.instance.changeTileSize(link.dataset.viewTileSize);

			for (var i = 0; i < this.layout.changeViewButtons.length; ++i)
			{
				this.layout.changeViewButtons[i].classList.remove('disk-folder-list-view-item-active');
			}

			link.classList.toggle('disk-folder-list-view-item-active');

			if (this.shouldUseHistory())
			{
				window.history.replaceState(null, null, BX.util.remove_url_param(document.location.toString(), 'viewSize'));
			}

			BX.ajax.runComponentAction('bitrix:disk.folder.list', 'saveViewOptions', {
				analyticsLabel: 'tile.' + link.dataset.viewTileSize,
				mode: 'class',
				data: {
					storageId: BX.Disk.Page.getStorage().id,
					viewMode: 'tile',
					viewSize: link.dataset.viewTileSize
				}
			});
		}
	};

	FolderListClass.prototype.createFolder = function () {
		var self = this;

		var modal = BX.Disk.modalWindow({
			modalId: 'bx-disk-create-folder',
			title: BX.message('DISK_FOLDER_TITLE_CREATE_FOLDER'),
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
						BX.message('DISK_FOLDER_LABEL_NAME_CREATE_FOLDER')
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
				new BX.PopupWindowCustomButton({
					text: BX.message('DISK_FOLDER_BTN_CREATE_FOLDER'),
					className: "ui-btn ui-btn-success",
					events: {
						click: function () {
							var input = BX('disk-new-create-filename');
							var newName = input.value;
							if (!newName || !newName.replace(/\s+/g, '')) {
								BX.addClass(input, 'disk-animated disk-animate-shake');
								input.addEventListener('animationend', function(){
									BX.removeClass(input, 'disk-animated disk-animate-shake');
								});

								BX.focus(input);
								return;
							}

							this.addClassName('ui-btn-clock');
							var button = this;

							BX.Disk.ajax({
								method: 'POST',
								dataType: 'json',
								url: BX.Disk.addToLinkParam(self.ajaxUrl, 'action', 'addFolder'),
								data: {
									targetFolderId: BX.Disk.Page.getFolder().id,
									name: newName
								},
								onsuccess: function (data) {
									if (!data) {
										return;
									}
									if (data.status && data.status == 'success') {
										modal.close();

										this.commonGrid.reload(
											BX.Disk.getUrlToShowObjectInGrid(data.folder.id, {resetFilter: 1}),
											{}
										).then(function () {
											this.resetFilter();
											this.commonGrid.selectItemById(data.folder.id);

											if (this.commonGrid.isGrid())
											{
												var row = this.getRow(data.folder.id);
												this.scrollToRow(row);
											}
										}.bind(this));
									}
									else
									{
										BX.Disk.showModalWithStatusAction(data);
										button.removeClassName('ui-btn-clock');
									}
								}.bind(self)
							});
						}
					}
				}),
				new BX.PopupWindowCustomButton({
					text: BX.message('DISK_JS_BTN_CLOSE'),
					className: 'ui-btn ui-btn-link',
					events: {
						click: function () {
							BX.PopupWindowManager.getCurrentPopup().close();
						}
					}
				})
			]
		});
	};

	/**
	 *
	 * @param {BX.UI.Button} button
	 * @param e
	 */
	FolderListClass.prototype.onClickManageConnectButton = function (button, e)
	{
		if(button.getIcon() === BX.UI.Button.Icon.DISK)
		{
			BX.Disk.ajax({
				method: 'POST',
				dataType: 'json',
				url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'connectToUserStorage'),
				data: {
					objectId: this.storage.rootObject.id
				},
				onsuccess: BX.delegate(function (response)
				{
					BX.Disk.showModalWithStatusAction(response);
					if(response.status != 'success')
					{
						return;
					}
					button.setIcon(BX.UI.Button.Icon.DONE);
					button.setText(BX.message('DISK_FOLDER_LIST_LABEL_ALREADY_CONNECT_DISK'));

					if(!!response.manage.link)
					{
						this.storage.manage.link = BX.clone(response.manage.link, true);
					}
				}, this)
			});
		}
		else if(button.getIcon() === BX.UI.Button.Icon.DONE)
		{
			this.openConfirmDetach({
				object: {
					id: this.storage.manage.link.object.id,
					name: this.storage.name,
					isFolder: true
				},
				onSuccess: function(response){
					if(response && response.status == 'success')
					{
						response.message = BX.message('DISK_FOLDER_LIST_LABEL_DISCONNECTED_DISK')
					}
					BX.Disk.showModalWithStatusAction(response);

					button.setIcon(BX.UI.Button.Icon.DISK);
					button.setText(BX.message('DISK_FOLDER_LIST_LABEL_CONNECT_DISK'));
				}
			});
		}
	};

	FolderListClass.prototype.showTree = function (params)
	{
		var bindElement = params.bindElement || null;
		var textElement = params.textElement || null;
		var valueElement = params.valueElement || null;
		var onSelect = params.onSelect || null;

		var targetObjectId = null;
		var targetObjectNode = null;

		var modalTree = new BX.Disk.Tree.Modal(this.rootObject, {
			enableKeyboardNavigation: false,
			events: {
				onSelectFolder: function(node, objectId) {
					if(!node.getAttribute('data-can-add'))
					{
						BX.removeClass(node, 'selected');
						return;
					}

					targetObjectId = objectId;
					if(targetObjectNode)
					{
						BX.removeClass(targetObjectNode, 'selected');
					}
					targetObjectNode = node;
					if(targetObjectId && valueElement)
					{
						valueElement.value = targetObjectId;
					}
					if (targetObjectId && BX.type.isFunction(onSelect))
					{
						onSelect(targetObjectId);
					}
				},
				onUnSelectFolder: function(node){
					targetObjectId = null;
					targetObjectNode = null;
					var pos = BX('grid_group_action_target_object');
					pos && BX.remove(pos);
				}
			},
			modalParameters: {
				bindElement: bindElement,
				title: params.title,
				buttons: params.buttons
			}
		});
		modalTree.show();
	};

	FolderListClass.prototype.onClickGetFilesCountAndSizeButtonButton = function (e)
	{
		BX.Disk.ajax({
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'calculateFileSizeAndCount'),
			method: 'POST',
			dataType: 'json',
			data: {
				folderId: this.currentFolder.id
			},
			onsuccess: BX.delegate(function(response) {
				if(!response || response.status != 'success')
				{
					BX.Disk.showModalWithStatusAction(response);
					return;
				}

				BX.adjust(this.getFilesCountAndSize.sizeContainer, {text: response.size});
				BX.adjust(this.getFilesCountAndSize.countContainer, {text: response.count});

			}, this)
		});
	};

	FolderListClass.prototype.handleDocumentSaved = function(object, documentSession)
	{
		var item = this.commonGrid.getItemById(object.id);
		if (!item || this.commonGrid.isTile())
		{
			return;
		}

		this.commonGrid.instance.updateRow(object.id, null, null, function () {
			var rowNode = this.commonGrid.instance.getRows().getById(object.id).getNode();
			if (!rowNode)
			{
				return;
			}

			BX.addClass(rowNode, 'main-grid-row-checked');
			setInterval(function () {
				BX.removeClass(rowNode, 'main-grid-row-checked');
			}, 8000);
		}.bind(this));
	}

	FolderListClass.prototype.onSliderMessage = function(event) {
		var eventData = event.getData();
		if (event.getEventId() === 'Disk.File:onMarkDeleted')
		{
			if (eventData.objectId)
			{
				setTimeout(function () {
					this.removeRow(eventData.objectId);
				}.bind(this), 500);
			}
		}

		if (event.getEventId() === 'Disk.File:onDelete')
		{
			if (eventData.objectId)
			{
				setTimeout(function () {
					this.removeRow(eventData.objectId);
				}.bind(this), 500);
			}
		}

		if (event.getEventId() === 'Disk.File:onRestore')
		{
			if (eventData.objectId)
			{
				setTimeout(function () {
					this.removeRow(eventData.objectId);
				}.bind(this), 500);
			}
		}

		if (event.getEventId() === 'Disk.File:onNewVersionUploaded')
		{
			this.commonGrid.reload(BX.Disk.getUrlToShowObjectInGrid(eventData.object.id));
		}

		if (event.getEventId() === 'Disk.OnlyOffice:onSaved')
		{
			if (!eventData.object)
			{
				this.commonGrid.reload();
			}
			else
			{
				this.commonGrid.reload(BX.Disk.getUrlToShowObjectInGrid(eventData.object.id))
			}
		}
		if (event.getEventId() === 'Disk.OnlyOffice:onClosed' && eventData.object && eventData.process === 'create')
		{
			this.commonGrid.reload(BX.Disk.getUrlToShowObjectInGrid(eventData.object.id))
		}

		if (event.getEventId() === 'Disk.File:onAddSharing')
		{
			this.showSharingIcon(eventData.objectId);
		}
	};

	FolderListClass.prototype.onPopState = function(e)
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

		BX.onCustomEvent("Disk.FolderListClass:onPopState", [state.folder]);

		this.openFolder(state.folder.id, state.folder);
	};

	FolderListClass.prototype.openGridFolder = function(event)
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

	FolderListClass.prototype.onOpenFolderByAnchor = function(anchor)
	{
		var folder = {
			id: anchor.dataset.objectId,
			canAdd: anchor.dataset.canAdd,
			link: anchor.href,
			name: anchor.textContent.trim(),
			node: anchor
		};

		return this.onOpenFolder(folder);
	};

	/**
	 * @param {Object} folder
	 * @param {number} [folder.id]
	 * @param {string} [folder.link]
	 * @param {string} [folder.name]
	 * @param {Node} [folder.node]
	 * @returns {boolean}
	 */
	FolderListClass.prototype.onOpenFolder = function(folder)
	{
		if (!folder.id)
		{
			return false;
		}

		BX.onCustomEvent("Disk.FolderListClass:onFolderBeforeOpen", [folder]);

		var state = {
			disk: true,
			folder: {
				id: folder.id,
				name: folder.name
			}
		};

		if (this.shouldUseHistory())
		{
			window.history.pushState(
				state,
				null,
				folder.link
			);
		}
		BX.onCustomEvent("Window:onPushState", [state, null, folder.link]);
		this.baseGridPageUrl = folder.link;

		this.openFolder(folder.id, folder);

		return true;
	};

	FolderListClass.prototype.openFolderContextMenu = function(anchor, event, folderId, folder)
	{
		event.preventDefault();

		folder.link = anchor.href;
		folder.id = folderId;

		this.onOpenFolder(folder)
	};

	/**
	 * @param {number} folderId
	 * @param {Object} folder
	 * @param {number} [folder.id]
	 * @param {string} [folder.link]
	 * @param {string} [folder.name]
	 * @param {Node} [folder.node]
	 * @returns {boolean}
	 */
	FolderListClass.prototype.openFolder = function(folderId, folder)
	{
		this.commonGrid.reload(folder.link, {
			resetFilter: 1
		}).then(function(){
			BX.onCustomEvent("Disk.FolderListClass:onFolderOpen", [folder, this.isFiltetedFolderList]);

			this.removeSearchProcessInConnectedFolders();

			BX.Disk.Page.changeFolder({
				id: folder.id,
				name: folder.name
			});

			if (folder.canAdd === undefined)
			{
				BX.ajax.runAction('disk.api.folder.getAllowedOperationsRights', {
					analyticsLabel: 'folder.list',
					data: {
						folderId: folder.id
					}
				}).then(function (response) {

					var operations = response.data.operations;
					operations.disk_add? this.unblockCreateItemsButton() : this.blockCreateItemsButton();

					folder.canAdd = !!operations.disk_add;

				}.bind(this));
			}
			else
			{
				folder.canAdd? this.unblockCreateItemsButton() : this.blockCreateItemsButton();
			}

			window.scroll(0, 0);
		}.bind(this));
	};

	FolderListClass.prototype.onClickDeleteGroup = function(e)
	{
		if(!this.commonGrid.instance.IsActionEnabled())
			return false;
		var allRows = document.getElementById('actallrows_' + this.commonGrid.instance.table_id);

		this.openConfirmDeleteGroup({
			attemptDeleteAll: allRows && allRows.checked
		});
		BX.PreventDefault(e);
		return false;
	};

	FolderListClass.prototype.removeRow = function(objectId)
	{
		this.commonGrid.removeItemById(objectId);

		BX.onCustomEvent('onRemoveRowFromDiskList', [objectId]);
	};

	FolderListClass.prototype.getRow = function(objectId)
	{
		return this.commonGrid.instance.getRows().getById(objectId);
	};

	FolderListClass.prototype.blockCreateItemsButton = function()
	{
		if (this.layout.createItemsButton)
		{
			this.layout.createItemsButton.classList.add('ui-btn-disabled');
		}
		if (this.layout.emptyBlockUploadFileButtonId)
		{
			BX.addClass(this.layout.emptyBlockUploadFileButtonId, 'disk-folder-list-no-data-disabled')
		}
		if (this.layout.emptyBlockCreateFolderButtonId)
		{
			BX.addClass(this.layout.emptyBlockCreateFolderButtonId, 'disk-folder-list-no-data-disabled')
		}
	};

	FolderListClass.prototype.unblockCreateItemsButton = function()
	{
		if (this.layout.createItemsButton)
		{
			this.layout.createItemsButton.classList.remove('ui-btn-disabled');
		}
		if (this.layout.emptyBlockUploadFileButtonId)
		{
			BX.removeClass(this.layout.emptyBlockUploadFileButtonId, 'disk-folder-list-no-data-disabled')
		}
		if (this.layout.emptyBlockCreateFolderButtonId)
		{
			BX.removeClass(this.layout.emptyBlockCreateFolderButtonId, 'disk-folder-list-no-data-disabled')
		}
	};

	FolderListClass.prototype.renameInline = function(objectId)
	{
		if (this.commonGrid.isTile())
		{
			var item = this.commonGrid.instance.getItem(objectId);
			if (item)
			{
				item.onRename();
			}

			return;
		}

		this.commonGrid.instance.getRows().unselectAll();
		var row = this.commonGrid.instance.getRows().getById(objectId);
		row.select();
		this.commonGrid.instance.editSelected();

		var editorContainer = BX.Grid.Utils.getByClass(row.getNode(), 'main-grid-editor-container', true);
		var input = BX.findChild(editorContainer, {
			tag: 'input'
		}, true);

		if(input)
		{
			BX.bind(input, 'keydown', function(event){

				if(event.key === 'Enter')
				{
					event.stopPropagation();
					event.preventDefault();

					this.commonGrid.instance.editSelectedSave();
				}

				if(event.key === 'Escape')
				{
					this.commonGrid.instance.editSelectedCancel();
				}

			}.bind(this));
			BX.bind(input, 'blur', function(event){
				event.stopPropagation();
				event.preventDefault();
				this.commonGrid.instance.editSelectedSave();
			}.bind(this));

			BX.focus(input);
		}
	};

	FolderListClass.prototype.processGridGroupActionRestore = function ()
	{
		var selectedRows = this.commonGrid.getSelectedIds();
		if (!selectedRows.length)
		{
			return;
		}

		var self = this;
		var messageDescription = BX.message('DISK_TRASHCAN_TRASH_RESTORE_DESCR_MULTIPLE');
		var buttons = [
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_TRASHCAN_ACT_RESTORE'),
				className: "ui-btn ui-btn-success",
				events: {
					click: function (e) {
						this.addClassName('ui-btn-clock');

						BX.ajax.runAction('disk.api.commonActions.restoreCollection', {
							analyticsLabel: 'folder.list',
							data: {
								objectCollection: selectedRows
							}
						}).then(function (response) {

							if (response.status === 'success')
							{
								if (response.data.restoredObjectIds.length > 1)
								{
									BX.Disk.showModalWithStatusAction({
										status: 'success',
										message: BX.message('DISK_TRASHCAN_TRASH_RESTORE_SUCCESS')
									});
									this.commonGrid.reload();
								}
								else
								{
									var firstObjectId = response.data.restoredObjectIds.pop();
									window.document.location = BX.Disk.getUrlToShowObjectInGrid(firstObjectId);
								}
							}

							BX.PopupWindowManager.getCurrentPopup().close();
						}.bind(self));
					}
				}
			}),
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_JS_BTN_CANCEL'),
				className: 'ui-btn ui-btn-link',
				events: {
					click: function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
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

	FolderListClass.prototype.openConfirmRestore = function (parameters)
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

		var self = this;
		var buttons = [
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_TRASHCAN_ACT_RESTORE'),
				className: "ui-btn ui-btn-success",
				events: {
					click: function (e) {
						this.addClassName('ui-btn-clock');

						BX.ajax.runAction('disk.api.commonActions.restore', {
							analyticsLabel: 'folder.list',
							data: {
								objectId: objectId
							}
						}).then(function (response) {
							BX.PopupWindowManager.getCurrentPopup().close();
							this.commonGrid.selectItemById(objectId);

							window.document.location = BX.Disk.getUrlToShowObjectInGrid(response.data.object.id);
						}.bind(self));
					}
				}
			}),
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_JS_BTN_CANCEL'),
				className: 'ui-btn ui-btn-link',
				events: {
					click: function (e)
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
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

	FolderListClass.prototype.copyLinkInternalLink = function (link, target)
	{
		target.classList.add('menu-popup-item-accept', 'disk-folder-list-context-menu-item-accept-animate');
		target.style.minWidth = (target.offsetWidth) + 'px';
		var textNode = target.querySelector('.menu-popup-item-text');
		if (textNode)
		{
			textNode.textContent = BX.message('DISK_FOLDER_LIST_ACT_COPIED_INTERNAL_LINK');
		}

		BX.clipboard.copy(link);
	};

	FolderListClass.prototype.openConfirmDelete = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var isFolder = parameters.object.isFolder;
		var isDeleted = parameters.object.isDeleted;

		var canDelete = parameters.canDelete;
		var messageDescription = '';

		if (isFolder)
		{
			messageDescription = BX.message(canDelete? 'DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_FOLDER_CONFIRM' : 'DISK_FOLDER_LIST_TRASH_DELETE_FOLDER_CONFIRM');
		}
		else
		{
			messageDescription = BX.message(canDelete? 'DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_FILE_CONFIRM' : 'DISK_FOLDER_LIST_TRASH_DELETE_FILE_CONFIRM');
		}

		if (isDeleted)
		{
			messageDescription = isFolder?
				BX.message('DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_DELETED_FOLDER_CONFIRM') :
				BX.message('DISK_FOLDER_LIST_TRASH_DELETE_DESTROY_DELETED_FILE_CONFIRM');
		}

		var self = this;
		var buttons = [];
		if (!isDeleted)
		{
			buttons.push(new BX.PopupWindowCustomButton({
				text: BX.message("DISK_FOLDER_LIST_TRASH_DELETE_BUTTON"),
				className: "ui-btn ui-btn-success",
				events: {
					click: function (e) {
						this.addClassName('ui-btn-clock');

						BX.ajax.runAction('disk.api.commonActions.markDeleted', {
							analyticsLabel: 'folder.list',
							data: {
								objectId: objectId
							}
						}).then(function (response) {
							BX.PopupWindowManager.getCurrentPopup().close();
							self.removeRow(objectId);
						});
					}
				}
			}));
		}
		if (canDelete)
		{
			buttons.push(new BX.PopupWindowCustomButton({
					text: BX.message("DISK_FOLDER_LIST_TRASH_DESTROY_BUTTON"),
					className: 'ui-btn ui-btn-light-border',
					events: {
						click: function (e)
						{
							this.addClassName('ui-btn-clock');

							BX.ajax.runAction('disk.api.commonActions.delete', {
								analyticsLabel: 'folder.list',
								data: {
									objectId: objectId
								}
							}).then(function (response) {
								BX.PopupWindowManager.getCurrentPopup().close();
								self.removeRow(objectId);
							});
						}
					}
				}));
		}
		buttons.push(
			new BX.PopupWindowCustomButton({
				text: BX.message("DISK_FOLDER_LIST_TRASH_CANCEL_DELETE_BUTTON"),
				className: 'ui-btn ui-btn-link',
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
					}
				}
			})
		);

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FOLDER_LIST_TRASH_DELETE_TITLE'),
			contentClassName: 'disk-popup-text-content',
			content: messageDescription.replace('#NAME#', name),
			buttons: buttons
		});
	};

	FolderListClass.prototype.onPopupFileUploadClose = function (diskUpload, fileId)
	{
		this.commonGrid.reload(BX.Disk.getUrlToShowObjectInGrid(fileId, {resetFilter: 1})).then(function () {

			this.resetFilter();
			this.commonGrid.selectItemById(fileId);

			if (this.commonGrid.isGrid())
			{
				var row = this.getRow(fileId);
				this.scrollToRow(row);
			}

		}.bind(this));
	};

	FolderListClass.prototype.onStepperHasBeenFinished = function (stepper)
	{
		//we don't want to analyze: is it our stepper or not. Because there is strange and not useful structure.
		this.commonGrid.reload();
	};

	var alreadyRunEmptyTrash = false;
	FolderListClass.prototype.openConfirmEmptyTrash = function ()
	{
		var storageId = this.storage.id;
		var buttons = [
			new BX.PopupWindowCustomButton({
				text: BX.message("DISK_FOLDER_LIST_TITLE_EMPTY_TRASH"),
				className: "ui-btn ui-btn-success",
				events: {
					click: function (e) {
						this.addClassName('ui-btn-clock');

						if (alreadyRunEmptyTrash)
						{
							BX.PopupWindowManager.getCurrentPopup().close();
							return;
						}

						alreadyRunEmptyTrash = true;
						BX.ajax.runAction('disk.api.trashcan.empty', {
							analyticsLabel: 'folder.list',
							data: {
								storageId: storageId
							}
						}).then(function (response) {

							BX.ajax.runComponentAction('bitrix:disk.folder.list', 'getSteppers', {
								mode: 'class'
							}).then(function(response) {
								if (response.data.html)
								{
									var place = BX('disk-folder-list-place-for-stepper');
									if (place)
									{
										BX.html(place, response.data.html);
									}
								}
							});

							BX.PopupWindowManager.getCurrentPopup().close();
						}, function(response) {
							BX.Disk.showModalWithStatusAction(response);
							BX.PopupWindowManager.getCurrentPopup().close();
						});
					}
				}
			}),
			new BX.PopupWindowCustomButton({
				text: BX.message("DISK_JS_BTN_CANCEL"),
				className: 'ui-btn ui-btn-link',
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-empty-trash-confirm',
			title: BX.message('DISK_FOLDER_LIST_TITLE_EMPTY_TRASH_TITLE'),
			contentClassName: 'disk-popup-text-content',
			content: BX.message('DISK_FOLDER_LIST_TRASH_EMPTY_TRASH_DESCRIPTION'),
			buttons: buttons
		});
	};

	FolderListClass.prototype.openConfirmDetach = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var isFolder = parameters.object.isFolder;
		var onSuccess = parameters.onSuccess;
		var messageDescription = '';
		if (isFolder) {
			messageDescription = BX.message('DISK_FOLDER_LIST_DETACH_FOLDER_CONFIRM');
		} else {
			messageDescription = BX.message('DISK_FOLDER_LIST_DETACH_FILE_CONFIRM');
		}
		var buttons = [
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_FOLDER_LIST_DETACH_BUTTON'),
				className: "ui-btn ui-btn-success",
				events: {
					click: BX.delegate(function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
						BX.PreventDefault(e);

						BX.Disk.ajax({
							method: 'POST',
							dataType: 'json',
							url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'detach'),
							data: {
								objectId: objectId
							},
							onsuccess: BX.delegate(function (data) {
								if (!data) {
									return;
								}
								if(data.status == 'success')
								{
									if(BX.type.isFunction(onSuccess))
									{
										BX.delegate(onSuccess, this)(data);
									}
									else
									{
										this.removeRow(objectId);
									}
									return;
								}
								BX.Disk.showModalWithStatusAction(data);
							}, this)
						});

						return false;
					}, this)
				}
			}),
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_FOLDER_LIST_TRASH_CANCEL_DELETE_BUTTON'),
				className: 'ui-btn ui-btn-link',
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: isFolder? BX.message('DISK_FOLDER_LIST_DETACH_FOLDER_TITLE') : BX.message('DISK_FOLDER_LIST_DETACH_FILE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription.replace('#NAME#', BX.util.htmlspecialchars(name)),
			buttons: buttons
		});
	};

	FolderListClass.prototype.openConfirmDeleteGroup = function ()
	{
		var messageDescription = BX.message('DISK_FOLDER_LIST_TRASH_DELETE_GROUP_CONFIRM');
		var buttons = [
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_FOLDER_LIST_TRASH_DELETE_BUTTON'),
				className: "ui-btn ui-btn-success",
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();

						var values = {};
						values[this.commonGrid.getActionKey()] = 'delete';

						var data = {
							rows: this.commonGrid.getSelectedIds(),
							controls: values
						};

						this.commonGrid.reload(null, data).then(function(){
							BX.PopupWindowManager.getCurrentPopup().destroy();
						});
					}.bind(this)
				}
			})
		];

		var canWeDestroyAll = false;
		// this.commonGrid.instance.getRows().getSelected().forEach(function(row) {
		// 	if (!row.node.dataset.canDestroy)
		// 	{
		// 		canWeDestroyAll = false;
		// 	}
		// });

		if (canWeDestroyAll)
		{
			buttons.push(
				new BX.PopupWindowCustomButton({
					text: BX.message("DISK_FOLDER_LIST_TRASH_DESTROY_BUTTON"),
					events: {
						click: function (e) {
							BX.PopupWindowManager.getCurrentPopup().destroy();

							var values = {};
							values[this.commonGrid.getActionKey()] = 'destroy';

							var data = {
								rows: this.commonGrid.getSelectedIds(),
								controls: values
							};

							this.commonGrid.reload(null, data).then(function(){
								BX.PopupWindowManager.getCurrentPopup().destroy();
							});
						}.bind(this)
					}
				}));
		}

		buttons.push(new BX.PopupWindowCustomButton({
			className: 'ui-btn ui-btn-link',
			text: BX.message('DISK_FOLDER_LIST_TRASH_CANCEL_DELETE_BUTTON'),
			events: {
				click: function (e) {
					BX.PopupWindowManager.getCurrentPopup().destroy();
				}
			}
		}));

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FOLDER_LIST_TRASH_DELETE_TITLE'),
			contentClassName: 'tac',
			contentStyle: {
				paddingTop: '70px',
				paddingBottom: '70px'
			},
			content: messageDescription,
			buttons: buttons
		});
	};

	FolderListClass.prototype.openConfirmDestroyGroup = function ()
	{
		var messageDescription = BX.message('DISK_FOLDER_LIST_TRASH_DESTROY_GROUP_CONFIRM');
		var buttons = [
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_FOLDER_LIST_TRASH_DESTROY_BUTTON'),
				className: "ui-btn ui-btn-success",
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();

						var values = {};
						values[this.commonGrid.getActionKey()] = 'destroy';

						var data = {
							rows: this.commonGrid.getSelectedIds(),
							controls: values
						};

						this.commonGrid.reload(null, data).then(function(){
							BX.PopupWindowManager.getCurrentPopup().destroy();
						});
					}.bind(this)
				}
			}),
			new BX.PopupWindowCustomButton({
				className: 'ui-btn ui-btn-link',
				text: BX.message('DISK_JS_BTN_CANCEL'),
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
					}
				}
			})
		];

		BX.Disk.modalWindow({
			modalId: 'bx-link-unlink-confirm',
			title: BX.message('DISK_FOLDER_LIST_TRASH_DELETE_TITLE'),
			contentClassName: 'disk-popup-text-content',
			content: messageDescription,
			buttons: buttons
		});
	};

	FolderListClass.prototype.downloadGroup = function ()
	{
		var selectedRows = this.commonGrid.getSelectedIds();
		if (!selectedRows.length)
		{
			return;
		}

		BX.ajax.runAction('disk.api.commonActions.getArchiveLink', {
			analyticsLabel: 'folder.list',
			data: {
				objectCollection: selectedRows
			}
		}).then(function(response){
			document.location = response.data.downloadArchiveUri;
		}).catch(function(response){
			BX.Disk.showModalWithStatusAction(response);
		}.bind(this));
	};

	FolderListClass.prototype.openConfirmCopyGroup = function ()
	{
		var destinationFolderId;
		var self = this;
		var buttons = [
			new BX.PopupWindowCustomButton({
				text: BX.message("DISK_FOLDER_LIST_TITLE_GRID_TOOLBAR_COPY_BUTTON"),
				className: "ui-btn ui-btn-success",
				events: {
					click: function (e) {
						if(!destinationFolderId)
						{
							return;
						}

						this.addClassName('ui-btn-clock');

						var values = {};
						values[self.commonGrid.getActionKey()] = 'copy';
						values['destinationFolderId'] = destinationFolderId;

						var data = {
							rows: self.commonGrid.getSelectedIds(),
							controls: values
						};

						self.commonGrid.reload(null, data).then(function(){
							BX.PopupWindowManager.getCurrentPopup().destroy();
						});
					}
				}
			}),
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_JS_BTN_CANCEL'),
				className: 'ui-btn ui-btn-link',
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
					}
				}
			})
		];

		this.showTree({
			title: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_MANY_COPY_TO'),
			buttons: buttons,
			onSelect: function (targetObjectId) {
				destinationFolderId = targetObjectId;
			}
		});
	};

	FolderListClass.prototype.openConfirmMoveGroup = function ()
	{
		var destinationFolderId;
		var self = this;
		var buttons = [
			new BX.PopupWindowCustomButton({
				text: BX.message("DISK_FOLDER_LIST_TITLE_GRID_TOOLBAR_MOVE_BUTTON"),
				className: "ui-btn ui-btn-success",
				events: {
					click: function (e) {
						if(!destinationFolderId)
						{
							return;
						}

						this.addClassName('ui-btn-clock');

						var values = {};
						values[self.commonGrid.getActionKey()] = 'move';
						values['destinationFolderId'] = destinationFolderId;

						var data = {
							rows: self.commonGrid.getSelectedIds(),
							controls: values
						};

						self.commonGrid.reload(null, data).then(function(){
							BX.PopupWindowManager.getCurrentPopup().destroy();
						});
					}
				}
			}),
			new BX.PopupWindowCustomButton({
				text: BX.message('DISK_JS_BTN_CANCEL'),
				className: 'ui-btn ui-btn-link',
				events: {
					click: function (e) {
						BX.PopupWindowManager.getCurrentPopup().destroy();
					}
				}
			})
		];

		this.showTree({
			title: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_MANY_MOVE_TO'),
			buttons: buttons,
			onSelect: function (targetObjectId) {
				destinationFolderId = targetObjectId;
			}
		});
	};

	FolderListClass.prototype.connectObjectToDisk = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;
		var isFolder = parameters.object.isFolder;

		BX.Disk.ajaxPromise({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'connectToUserStorage'),
			data: {
				objectId: objectId
			}
		}).then(function (response) {
			if (!response)
			{
				return;
			}
			if (response.status == 'success')
			{
				this.commonGrid.getActionById(objectId, 'connect').hide = true;

				var menu = this.commonGrid.getActionsMenu(objectId);
				menu.getMenuItem('connect').hide = true;
				BX.addClass(menu.getMenuItem('connect').layout.item, 'disk-popup-menu-hidden-item');

				BX.Disk.showModalWithStatusAction({
					status: 'success',
					message: isFolder ?
						BX.message('DISK_FOLDER_LIST_SUCCESS_CONNECT_TO_DISK_FOLDER').replace('#NAME#', name) :
						BX.message('DISK_FOLDER_LIST_SUCCESS_CONNECT_TO_DISK_FILE').replace('#NAME#', name)
				})
			}
			else
			{
				response.errors = response.errors || [{}];
				BX.Disk.showModalWithStatusAction({
					status: 'error',
					message: response.errors.pop().message
				})
			}
		}.bind(this));

	};

	FolderListClass.prototype.unlockFile = function (parameters)
	{
		var name = parameters.object.name;
		var objectId = parameters.object.id;


		BX.Disk.ajaxPromise({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'unlock'),
			data: {
				objectId: objectId
			}
		}).then(function (response) {
			if (!response)
			{
				return;
			}
			if (response.status == 'success')
			{
				this.commonGrid.getActionById(objectId, 'lock').hide = false;
				this.commonGrid.getActionById(objectId, 'unlock').hide = true;

				var menu = this.commonGrid.getActionsMenu(objectId);
				menu.getMenuItem('lock').hide = false;
				menu.getMenuItem('unlock').hide = true;
				BX.removeClass(menu.getMenuItem('lock').layout.item, 'disk-popup-menu-hidden-item');
				BX.addClass(menu.getMenuItem('unlock').layout.item, 'disk-popup-menu-hidden-item');

				this.hideLockIcon(objectId);
			}
			else
			{
				response.errors = response.errors || [{}];
				BX.Disk.showModalWithStatusAction({
					status: 'error',
					message: response.errors.pop().message
				})
			}
		}.bind(this));
	};

	FolderListClass.prototype.lockFile = function (parameters) {
		var name = parameters.object.name;
		var objectId = parameters.object.id;

		BX.Disk.ajaxPromise({
			method: 'POST',
			dataType: 'json',
			url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'lock'),
			data: {
				objectId: objectId
			}
		}).then(function (response) {
			if (!response)
			{
				return;
			}
			if (response.status == 'success')
			{
				this.commonGrid.getActionById(objectId, 'unlock').hide = false;
				this.commonGrid.getActionById(objectId, 'lock').hide = true;

				var menu = this.commonGrid.getActionsMenu(objectId);
				menu.getMenuItem('unlock').hide = false;
				menu.getMenuItem('lock').hide = true;
				BX.removeClass(menu.getMenuItem('unlock').layout.item, 'disk-popup-menu-hidden-item');
				BX.addClass(menu.getMenuItem('lock').layout.item, 'disk-popup-menu-hidden-item');

				this.showLockIcon(objectId);
			}
			else
			{
				response.errors = response.errors || [{}];
				BX.Disk.showModalWithStatusAction({
					status: 'error',
					message: response.errors.pop().message
				})
			}
		}.bind(this));
	};

	FolderListClass.prototype.showSharingIcon = function(objectId)
	{
		if (this.commonGrid.isTile())
		{
			var item = this.commonGrid.instance.getItem(objectId);
			item && item.markAsShared();
		}
		else
		{
			var row = this.getRow(objectId);
			var icon = BX.findChildByClassName(row.node, 'bx-disk-file-icon', true) || BX.findChildByClassName(row.node, 'bx-disk-folder-icon', true);
			if(icon)
			{
				BX.addClass(icon, 'icon-shared shared icon-shared_1 icon-shared_2');
			}
		}
	};

	FolderListClass.prototype.hideSharingIcon = function(objectId)
	{
		if (this.commonGrid.isTile())
		{
			var item = this.commonGrid.instance.getItem(objectId);
			item && item.unmarkAsShared();
		}
		else
		{
			var row = this.getRow(objectId);
			var icon = BX.findChildByClassName(row.node, 'bx-disk-file-icon', true) || BX.findChildByClassName(row.node, 'bx-disk-folder-icon', true);
			if(icon)
			{
				BX.removeClass(icon, 'icon-shared shared icon-shared_1 icon-shared_2');
			}
		}
	};

	FolderListClass.prototype.showLockIcon = function(objectId)
	{
		if (this.commonGrid.isGrid())
		{
			var row = this.getRow(objectId);
			var lockIcon = BX.findChildByClassName(row.node, 'js-lock-icon', true);
			if (lockIcon)
			{
				BX.show(lockIcon, 'block');
			}
		}
		else
		{
			var item = this.commonGrid.instance.getItem(objectId);
			item.lock();
		}
	};

	FolderListClass.prototype.hideLockIcon = function(objectId)
	{
		if (this.commonGrid.isGrid())
		{
			var row = this.getRow(objectId);
			var lockIcon = BX.findChildByClassName(row.node, 'js-lock-icon', true);
			if(lockIcon)
			{
				BX.hide(lockIcon, 'block');
			}
		}
		else
		{
			var item = this.commonGrid.instance.getItem(objectId);
			item.unlock();
		}
	};

	FolderListClass.prototype.sortByColumn = function(sortBy, direction)
	{
		direction = direction || 'desc';

		this.sort.sortBy = sortBy;
		this.sort.direction = direction.toLowerCase();

		this.commonGrid.sortByColumn({
			sort_by: this.sort.sortBy,
			sort_order: this.sort.direction
		});
	};

	FolderListClass.prototype.showGridSortingMenu = function(event)
	{
		var bindElement = BX.getEventTarget(event);
		var updateLabel = function(item) {
			if (bindElement)
			{
				BX.adjust(bindElement, {
					text: item.text
				});
			}
		};
		var toggleActiveMark = function(item) {
			item.layout.item.classList.toggle('menu-popup-item-accept');
			item.layout.item.classList.toggle('menu-popup-no-icon');
		};

		var items = [];
		this.sortFields.forEach(function(item) {
			items.push({
				title: item.label,
				text: item.label,
				field: item.field,
				onclick: function(event, item) {
					this.sortByColumn(item.field, 'desc');
					updateLabel(item);
					item.menuWindow.close();
				}.bind(this)
			});
		}, this);

		items.push(
			{
				delimiter: true
			},
			{
				title: BX.message('DISK_FOLDER_LIST_LABEL_SORT_INVERSE_DIRECTION'),
				text: BX.message('DISK_FOLDER_LIST_LABEL_SORT_INVERSE_DIRECTION'),
				className: this.sort.direction === 'desc'? 'menu-popup-item menu-popup-item-accept' : '',
				onclick: function(event, item) {
					this.inverseSortByColumn();
					toggleActiveMark(item);
					item.menuWindow.close();
				}.bind(this)
			},
			{
				delimiter: true
			},
			{
				title: BX.message('DISK_FOLDER_LIST_LABEL_SORT_MIX_MODE'),
				text: BX.message('DISK_FOLDER_LIST_LABEL_SORT_MIX_MODE'),
				className: this.sort.mix? 'menu-popup-item menu-popup-item-accept' : '',
				onclick: function(event, item) {
					this.toggleMixSort();
					toggleActiveMark(item);
					item.menuWindow.close();
				}.bind(this)
			}
		);

		BX.PopupMenu.create(
			'disk-folder-list-sorting-menu',
			this.sort.layout.label,
			items,
			{
				autoHide: true,
				className: 'disk-folder-list-sorting-menu',
				offsetTop: 0,
				offsetLeft: 35,
				angle: {
					offset: 45
				},
				events: {
					onPopupClose: function ()
					{
						BX.PopupMenu.destroy('disk-folder-list-sorting-menu');
						this.destroy();
					}
				}
			}
		).show();
	};

	FolderListClass.prototype.inverseSortByColumn = function()
	{
		var inverseDirection = this.sort.direction === 'desc'? 'asc' : 'desc';

		this.sortByColumn(this.sort.sortBy, inverseDirection);
	};

	FolderListClass.prototype.toggleMixSort = function()
	{
		if(this.sort.mix)
		{
			this.disableMixSort();
		}
		else
		{
			this.enableMixSort();
		}
	};

	FolderListClass.prototype.enableMixSort = function()
	{
		this.sort.mix = true;
		this.commonGrid.reload('', {
			sortMode: 'mix'
		});
	};

	FolderListClass.prototype.disableMixSort = function()
	{
		this.sort.mix = false;
		this.commonGrid.reload('', {
			sortMode: 'ord'
		});
	};

	FolderListClass.prototype.openExternalLinkDetailSettingsWithEditing = function (objectId)
	{
		BX.Disk.modalWindowActionLoader('disk.api.commonActions.generateExternalLink', {
			analyticsLabel: 'folder.list',
			id: 'bx-disk-external-link-loader',
			postData: {
				objectId: objectId
			},
			afterSuccessLoad: function(response) {
				if (!response || response.status != 'success')
				{
					BX.Disk.showModalWithStatusAction(response);
					return;
				}

				var externalLink = new BX.Disk.Model.ExternalLink.Input({
					state: response.data.externalLink,
					data: {
						objectId: objectId
					},
					models: {
						externalLinkSettings: new BX.Disk.Model.ExternalLink.Settings({
							state: response.data.externalLink
						}),
						externalLinkDescription: new BX.Disk.Model.ExternalLink.Description({
							state: response.data.externalLink
						})
					}
				});
				externalLink.render();

				BX.Disk.modalWindow({
					modalId: 'bx-disk-external-link',
					title: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_GET_EXT_LINK'),
					contentClassName: 'disk-popup-external-link-config',
					contentStyle: {},
					events: {
						onPopupClose: function () {
							this.destroy();
						}
					},
					content: [
						externalLink.getContainer()
					],
					buttons: [
						new BX.PopupWindowCustomButton({
							text: BX.message('DISK_FOLDER_LIST_BTN_SAVE'),
							className: "ui-btn ui-btn-success",
							events: {
								click: function () {
									externalLink.externalLinkSettings.save();
									BX.PopupWindowManager.getCurrentPopup().close();
									setTimeout(function(){
										BX.Disk.showModalWithStatusAction({status: 'success'});
									}, 300);
								}
							}
						}),
						new BX.PopupWindowCustomButton({
							className: 'ui-btn ui-btn-link',
							text: BX.message('DISK_JS_BTN_CLOSE'),
							events: {
								click: function () {
									BX.PopupWindowManager.getCurrentPopup().close();
								}
							}
						})
					]
				});
			}
		});
	};

	FolderListClass.prototype.onBeforeElementShow = function(viewer, element, status)
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

	FolderListClass.prototype.onIframeElementLoadDataToView = function(element, responseData)
	{
		if(responseData && responseData.status === "restriction" && BX('bx-bitrix24-business-tools-info'))
		{
			if(BX.CViewer && BX.CViewer.objNowInShow)
			{
				if(element.currentModalWindow)
				{
					element.currentModalWindow.close();
				}

				BX.CViewer.objNowInShow.close();
			}
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

	FolderListClass.prototype.getExternalLink = function (objectId)
	{
		var objectData = BX.delegate(getObjectDataId, this)(objectId);
		var isFolder = BX.hasClass(objectData.icon, 'bx-disk-folder-icon');
		var queryUrl = this.ajaxUrl;

		queryUrl = BX.Disk.addToLinkParam(queryUrl, 'action', 'generateExternalLink');
		queryUrl = BX.Disk.addToLinkParam(queryUrl, 'isFolder', isFolder);

		BX.Disk.modalWindowLoader(queryUrl, {
			id: 'bx-disk-external-link-loader',
			responseType: 'json',
			postData: {
				objectId: objectId
			},
			afterSuccessLoad: BX.delegate(function(response){

				if(!response || response.status != 'success')
				{
					BX.Disk.showModalWithStatusAction(response);
					return;
				}
				this.cacheExternalLinks[objectId] = response.link;

				BX.Disk.modalWindow({
					modalId: 'bx-disk-external-link',
					title: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_GET_EXT_LINK'),
					contentClassName: 'tac',
					contentStyle: {
					},
					events: {
						onAfterPopupShow: function () {
							var inputExtLink = BX('disk-get-external-link');
							BX.focus(inputExtLink);
							inputExtLink.setSelectionRange(0, inputExtLink.value.length)
						},
						onPopupClose: function () {
							this.destroy();
						}
					},
					content: [
						BX.create('label', {
							props: {
								className: 'bx-disk-popup-label',
								"for": 'disk-get-external-link'
							}
						}),
						BX.create('input', {
							style: {
								marginTop: '10px'
							},
							props: {
								id: 'disk-get-external-link',
								className: 'bx-viewer-inp',
								type: 'text',
								value: response.link
							}
						})
					],
					buttons: [
						new BX.PopupWindowCustomButton({
							text: BX.message('DISK_JS_BTN_CLOSE'),
							className: 'ui-btn ui-btn-link',
							events: {
								click: function () {
									BX.PopupWindowManager.getCurrentPopup().close();
								}
							}
						})
					]

				});
			}, this)
		});

		return false;
	};

	FolderListClass.prototype.getInternalLink = function (internalLink)
	{
		BX.Disk.modalWindow({
			modalId: 'bx-disk-internal-link',
			title: BX.message('DISK_FOLDER_LIST_ACT_COPY_INTERNAL_LINK'),
			contentClassName: 'tac',
			contentStyle: {
			},
			events: {
				onAfterPopupShow: function () {
					var inputLink = BX('disk-get-internal-link');
					BX.focus(inputLink);
					inputLink.setSelectionRange(0, inputLink.value.length)
				},
				onPopupClose: function () {
					this.destroy();
				}
			},
			content: [
				BX.create('label', {
					props: {
						className: 'bx-disk-popup-label',
						"for": 'disk-get-internal-link'
					}
				}),
				BX.create('input', {
					style: {
						marginTop: '10px'
					},
					props: {
						id: 'disk-get-internal-link',
						className: 'bx-viewer-inp',
						type: 'text',
						value: internalLink
					}
				})
			],
			buttons: [
				new BX.PopupWindowCustomButton({
					text: BX.message("DISK_JS_BTN_CLOSE"),
					className: 'ui-btn ui-btn-link',
					events: {
						click: function() {
							BX.PopupWindowManager.getCurrentPopup().close();
						}
					}
				})
			]
		});
	};

	FolderListClass.prototype.openCopyModalWindow = function(rootObject, objectToMove)
	{
		var targetObjectId = null;
		var targetObjectNode = null;

		var modalTree = new BX.Disk.Tree.Modal(this.rootObject, {
			events: {
				onSelectFolder: function(node, objectId){
					if(!node.getAttribute('data-can-add'))
					{
						BX.removeClass(node, 'selected');
						return;
					}

					if(targetObjectNode)
					{
						BX.removeClass(targetObjectNode, 'selected');
					}
					targetObjectId = objectId;
					targetObjectNode = node;
				},
				onUnSelectFolder: function(){
					targetObjectId = null;
					targetObjectNode = null;
				}
			},
			modalParameters: {
				title: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_TREE'),
				contentTitle: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_COPY_TO').replace('#NAME#', objectToMove.name),
				buttons: [
				new BX.PopupWindowCustomButton({
					text: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_COPY_TO_BUTTON'),
					className: "ui-btn ui-btn-success",
					events: {
						click: BX.delegate(function (e) {
							if(!targetObjectId)
							{
								BX.PreventDefault(e);
								return false;
							}

							BX.PopupWindowManager.getCurrentPopup().close();
							BX.PreventDefault(e);

							BX.Disk.ajax({
								method: 'POST',
								dataType: 'json',
								url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'copyTo'),
								data: {
									objectId: objectToMove.id,
									targetObjectId: targetObjectId
								},
								onsuccess: function (data) {
									if (!data) {
										return;
									}
									if (data.status == 'success') {
										if(data.isFolder)
										{
											data.message = BX.message('DISK_FOLDER_LIST_OK_FOLDER_COPIED').replace('#FOLDER#', data.name);
										}
										else
										{
											data.message = BX.message('DISK_FOLDER_LIST_OK_FILE_COPIED').replace('#FILE#', data.name);
										}
										data.message = data.message.replace('#TARGET_FOLDER#', data.destination.name);

										BX.Disk.showModalWithStatusAction(data);
										return;
									}
									BX.Disk.showModalWithStatusAction(data);
								}
							});

							return false;
						}, this)
					}
				}),
				new BX.PopupWindowCustomButton({
					text: BX.message('DISK_JS_BTN_CANCEL'),
					className: 'ui-btn ui-btn-link',
					events: {
						click: function (e)
						{
							BX.PopupWindowManager.getCurrentPopup().destroy();
						}
					}
				})

			]
			}
		});
		modalTree.show();
	};

	FolderListClass.prototype.openMoveModalWindow = function(rootObject, objectToMove)
	{
		var targetObjectId = null;
		var targetObjectNode = null;

		var modalTree = new BX.Disk.Tree.Modal(this.rootObject, {
			events: {
				onSelectFolder: function (node, objectId) {
					if(!node.getAttribute('data-can-add'))
					{
						BX.removeClass(node, 'selected');
						return;
					}

					if (targetObjectNode)
					{
						BX.removeClass(targetObjectNode, 'selected');
					}

					targetObjectId = objectId;
					targetObjectNode = node;
				},
				onUnSelectFolder: function(){
					targetObjectId = null;
					targetObjectNode = null;
				}
			},
			modalParameters: {
				title: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_TREE'),
				contentTitle: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_MOVE_TO').replace('#NAME#', objectToMove.name),
				buttons: [
					new BX.PopupWindowCustomButton({
						text: BX.message('DISK_FOLDER_LIST_TITLE_MODAL_MOVE_TO_BUTTON'),
						className: "ui-btn ui-btn-success",
						events: {
							click: BX.delegate(function (e) {
								if(!targetObjectId)
								{
									BX.PreventDefault(e);
									return false;
								}

								BX.PopupWindowManager.getCurrentPopup().close();
								BX.PreventDefault(e);

								BX.Disk.ajaxPromise({
									method: 'POST',
									dataType: 'json',
									url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'moveTo'),
									data: {
										objectId: objectToMove.id,
										targetObjectId: targetObjectId
									}
								}).then(function (response) {

									if (this.commonGrid.isGrid())
									{
										this.commonGrid.instance.getRows().unselectAll();
									}
									this.commonGrid.selectItemById(objectToMove.id);
									this.commonGrid.reload();

									if(response.isFolder)
									{
										response.message = BX.message('DISK_FOLDER_LIST_OK_FOLDER_MOVED').replace('#FOLDER#', response.name);
									}
									else
									{
										response.message = BX.message('DISK_FOLDER_LIST_OK_FILE_MOVED').replace('#FILE#', response.name);
									}
									response.message = response.message.replace('#TARGET_FOLDER#', response.destination.name);


									BX.Disk.showModalWithStatusAction(response);
								}.bind(this));

								return false;
							}, this)
						}
					}),
					new BX.PopupWindowCustomButton({
						text: BX.message('DISK_JS_BTN_CANCEL'),
						className: 'ui-btn ui-btn-link',
						events: {
							click: function (e)
							{
								BX.PopupWindowManager.getCurrentPopup().destroy();
							}
						}
					})
				]
			}
		});
		modalTree.show();
	};


	var isChangedRights = false;
	var storageNewRights = {};
	var originalRights = {};
	var detachedRights = {};
	var moduleTasks = {};

	var entityToNewShared = {};
	var loadedReadOnlyEntityToNewShared = {};
	var entityToNewSharedMaxTaskName = '';

	FolderListClass.prototype.showSharingDetailWithChangeRights = function (params) {

		entityToNewShared = {};
		loadedReadOnlyEntityToNewShared = {};

		params = params || {};
		var objectId = params.object.id;

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSharingDetailChangeRights'),
			{
				id: 'folder_list_sharing_detail_object_' + objectId,
				responseType: 'json',
				postData: {
					objectId: objectId
				},
				afterSuccessLoad: BX.delegate(function(response)
				{
					if(response.status != 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message
						})
					}

					var objectOwner = {
						name: response.owner.name,
						avatar: response.owner.avatar,
						link: response.owner.link
					};

					BX.Disk.modalWindow({
						modalId: 'bx-disk-detail-sharing-folder-change-right',
						title: BX.message('DISK_FOLDER_LIST_SHARING_TITLE_MODAL_3'),
						contentClassName: '',
						contentStyle: {},
						events: {
							onAfterPopupShow: BX.delegate(function () {

								BX.addCustomEvent('onChangeRightOfSharing', BX.proxy(this.onChangeRightOfSharing, this));

								for (var i in response.members) {
									if (!response.members.hasOwnProperty(i)) {
										continue;
									}

									entityToNewShared[response.members[i].entityId] = {
										item: {
											id: response.members[i].entityId,
											name: response.members[i].name,
											avatar: response.members[i].avatar
										},
										type: response.members[i].type,
										right: response.members[i].right
									};
								}

								BX.SocNetLogDestination.init({
									name : this.destFormName,
									searchInput : BX('feed-add-post-destination-input'),
									bindMainPopup : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
									bindSearchPopup : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
									callback : {
										select : BX.proxy(this.onSelectDestination, this),
										unSelect : BX.proxy(this.onUnSelectDestination, this),
										openDialog : BX.proxy(this.onOpenDialogDestination, this),
										closeDialog : BX.proxy(this.onCloseDialogDestination, this),
										openSearch : BX.proxy(this.onOpenSearchDestination, this),
										closeSearch : BX.proxy(this.onCloseSearchDestination, this)
									},
									items: response.destination.items,
									itemsLast: response.destination.itemsLast,
									itemsSelected : response.destination.itemsSelected
								});

								var BXSocNetLogDestinationFormName = this.destFormName;
								BX.bind(BX('feed-add-post-destination-container'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);BX.PreventDefault(e); });
								BX.bind(BX('feed-add-post-destination-input'), 'keyup', BX.proxy(this.onKeyUpDestination, this));
								BX.bind(BX('feed-add-post-destination-input'), 'keydown', BX.proxy(this.onKeyDownDestination, this));

							}, this),
							onPopupClose: BX.delegate(function () {
								if(BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog())
								{
									BX.SocNetLogDestination.closeDialog()
								}
								BX.removeCustomEvent('onChangeRightOfSharing', BX.proxy(this.onChangeRightOfSharing, this));
								BX.proxy_context.destroy();
							}, this)
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content'
								},
								children: [
									BX.create('table', {
										props: {
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_OWNER') + '</td>' +
												'</tr>'
											}),
											BX.create('tr', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="' + objectOwner.link + '"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url(\'' + encodeURI(objectOwner.avatar) + '\');"></span>' + BX.util.htmlspecialchars(objectOwner.name) + '</a></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS_USER') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('div', {
										props: {
											id: 'feed-add-post-destination-container',
											className: 'feed-add-post-destination-wrap'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'feed-add-post-destination-item'
												}
											}),
											BX.create('span', {
												props: {
													id: 'feed-add-post-destination-input-box',
													className: 'feed-add-destination-input-box'
												},
												style: {
													background: 'transparent'
												},
												children: [
													BX.create('input', {
														props: {
															type: 'text',
															value: '',
															id: 'feed-add-post-destination-input',
															className: 'feed-add-destination-inp'
														}
													})
												]
											}),
											BX.create('a', {
												props: {
													href: '#',
													id: 'bx-destination-tag',
													className: 'feed-add-destination-link'
												},
												style: {
													background: 'transparent'
												},
												text: BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_ADD_RIGHTS_USER'),
												events: {
													click: BX.delegate(function () {
													}, this)
												}
											})
										]
									})
								]
							})
						],
						buttons: [
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_FOLDER_LIST_BTN_SAVE'),
								className: "ui-btn ui-btn-success",
								events: {
									click: BX.delegate(function () {

										BX.Disk.ajax({
											method: 'POST',
											dataType: 'json',
											url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'changeSharingAndRights'),
											data: {
												objectId: objectId,
												entityToNewShared: entityToNewShared
											},
											onsuccess: BX.delegate(function (response) {
												if (!response) {
													return;
												}
												if(params.object.isFolder)
												{
													response.message = BX.message('DISK_FOLDER_LIST_OK_FOLDER_SHARE_MODIFIED').replace('#FOLDER#', params.object.name);
												}
												else
												{
													response.message = BX.message('DISK_FOLDER_LIST_OK_FILE_SHARE_MODIFIED').replace('#FILE#', params.object.name);
												}
												BX.Disk.showModalWithStatusAction(response);
												this.showSharingIcon(objectId);

												// var icon = BX.delegate(getIconElementByObjectId, this)(objectId);
												// if(icon)
												// {
												// 	if(!entityToNewShared || BX.Disk.isEmptyObject(entityToNewShared))
												// 	{
												// 		BX.removeClass(icon, 'icon-shared icon-shared_2 shared');
												// 		BX.removeClass(icon, 'icon-shared_1');
												// 	}
												// 	else
												// 	{
												// 		BX.addClass(icon, 'icon-shared icon-shared_2 shared');
												// 	}
												// }
											}, this)
										});

										BX.PopupWindowManager.getCurrentPopup().close();
									}, this)
								}
							}),
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_JS_BTN_CANCEL'),
								className: 'ui-btn ui-btn-link',
								events: {
									click: function (e)
									{
										BX.PopupWindowManager.getCurrentPopup().destroy();
									}
								}
							})
						]
					});
				}, this)
			}
		);
	};

	function showAccessCodeFullName(item)
	{
		item = item || {};

		return (item.provider? item.provider + ': ' : '') + item.name;
	}

	FolderListClass.prototype.showRights = function (params)
	{
		params = params || {};
		var objectId = params.object.id;
		var rights = {};

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showRightsDetail'),
			{
				id: 'folder_list_sharing_detail_object_' + objectId,
				responseType: 'json',
				postData: {
					objectId: objectId
				},
				afterSuccessLoad: BX.delegate(function(response)
				{
					if(response.status != 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message
						})
					}

					for (var i in response.rights) {
						if (!response.rights.hasOwnProperty(i)) {
							continue;
						}
						var rightsByAccessCode = response.rights[i];
						for (var j in rightsByAccessCode) {
							if (!rightsByAccessCode.hasOwnProperty(j)) {
								continue;
							}

							rights[i] = {
								readOnly: true,
								item: {
									id: i,
									name: showAccessCodeFullName(response.accessCodeNames[i]),
									avatar: null
								},
								type: 'group',
								right: {
									title: rightsByAccessCode[j].TASK.TITLE
								}
							};
						}
					}

					BX.Disk.modalWindow({
						modalId: 'bx-disk-detail-sharing-folder-change-right',
						title: BX.message('DISK_FOLDER_LIST_SHARING_TITLE_MODAL_3'),
						contentClassName: '',
						contentStyle: {
							//paddingTop: '30px',
							//paddingBottom: '70px'
						},
						events: {
							onAfterPopupShow: BX.delegate(function () {
								for (var i in rights) {
									if (!rights.hasOwnProperty(i)) {
										continue;
									}
									BX.Disk.appendRight(rights[i]);

								}

							}, this),
							onPopupClose: BX.delegate(function () {
								BX.proxy_context.destroy();
							}, this)
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content'
								},
								children: [
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS_USER') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('a', {
										text: BX.message('DISK_FOLDER_TOOLBAR_BTN_CREATE_FOLDER'),
										props: {
											id: 'bx-disk-destination-object-modal',
											className: 'bx-disk-btn bx-disk-btn-big bx-disk-btn-transparent border'
										},
										events: {
											click: BX.delegate(function () {
											}, this)
										},
										children: [
											BX.create('span', {
												props: {
													className: 'bx-disk-btn-icon bx-disk-btn-icon-plus'
												}
											}),
											BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_ADD_RIGHTS_USER')
										]
									}),
									BX.create('div', {
										html:
												'<span class="feed-add-destination-input-box" id="feed-add-post-destination-input-box">' +
													'<input autocomplete="nope" type="text" value="" class="feed-add-destination-inp" id="feed-add-post-destination-input"/>' +
												'</span>'
									})
								]
							})
						],
						buttons: []
					});
				}, this)
			}
		);

	};

	FolderListClass.prototype.showRightsOnStorage = function ()
	{
		storageNewRights = {};
		var storageId = this.storage.id;
		var rights = {};

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showRightsOnStorageDetail'),
			{
				id: 'folder_list_rights_detail_storage_' + storageId,
				responseType: 'json',
				postData: {
					storageId: storageId
				},
				afterSuccessLoad: BX.delegate(function(response, windowLoader)
				{
					windowLoader && windowLoader.close();

					if(response.status !== 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message
						});

						return;
					}

					if(BX.Disk.isEmptyObject(moduleTasks))
					{
						moduleTasks = BX.clone(response.tasks, true);
						BX.Disk.setModuleTasks(moduleTasks);
					}

					for (var i in response.rights) {
						if (!response.rights.hasOwnProperty(i)) {
							continue;
						}
						var rightsByAccessCode = response.rights[i];
						for (var j in rightsByAccessCode) {
							if (!rightsByAccessCode.hasOwnProperty(j)) {
								continue;
							}

							rights[i] = {
								readOnly: !!rightsByAccessCode[j].READ_ONLY,
								item: {
									id: i,
									name: showAccessCodeFullName(response.accessCodeNames[i]),
									avatar: null
								},
								type: 'group',
								right: {
									title: rightsByAccessCode[j].TASK.TITLE,
									id: rightsByAccessCode[j].TASK.ID
								}
							};
						}
					}
					var showExtendedRights = !!response.showExtendedRights;
					var showSystemFolderCheckbox = response.systemFolders.show;
					var modalWindow = BX.Disk.modalWindow({
						modalId: 'bx-disk-detail-sharing-folder-change-right',
						title: BX.message('DISK_FOLDER_LIST_RIGHTS_TITLE_MODAL_WITH_NAME'). replace('#OBJECT#', response.storage.name),
						withoutWindowManager: true,
						contentClassName: '',
						contentStyle: {
							//paddingTop: '30px',
							//paddingBottom: '70px'
						},
						events: {
							onAfterPopupShow: BX.delegate(function () {
								storageNewRights = BX.clone(rights, true);
								isChangedRights = false;

								BX.Access.Init({
									groups: { disabled: this.isBitrix24 }
								});
								var startValue = {};
								for (var key in storageNewRights) {
									if(!storageNewRights.hasOwnProperty(key))
										continue;

									storageNewRights[key].isBitrix24 = this.isBitrix24;
									BX.Disk.appendSystemRight(storageNewRights[key]);
								}

								BX.addCustomEvent('onChangeSystemRight', BX.proxy(this.onChangeSystemRight, this));
								BX.addCustomEvent('onDetachSystemRight', BX.proxy(this.onDetachSystemRight, this));

								BX.bind(BX('feed-add-post-destination-container'), 'click', BX.delegate(function(e){
									var startValue = {};
									for (var key in storageNewRights) {
										if(!storageNewRights.hasOwnProperty(key))
											continue;
										startValue[key] = true;
									}
									BX.Access.SetSelected(startValue);


									BX.Access.ShowForm({
										showSelected: true,
										callback: BX.delegate(function (arRights){
											var res = [];
											for (var provider in arRights) {
												for (var id in arRights[provider]) {
													res.push(arRights[provider][id]);
													this.onSelectSystemRight(arRights[provider][id], provider);
												}
											}
										}, this)
									});

									return BX.PreventDefault(e);
								}, this));


							}, this),
							onPopupClose: BX.delegate(function () {

								BX.removeCustomEvent('onChangeSystemRight', BX.proxy(this.onChangeRight, this));

							}, this)
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content'
								},
								children: [
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS_USER') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('div', {
										props: {
											id: 'feed-add-post-destination-container',
											className: 'feed-add-post-destination-wrap'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'feed-add-post-destination-item'
												}
											}),
											BX.create('span', {
												props: {
													id: 'feed-add-post-destination-input-box',
													className: 'feed-add-destination-input-box'
												},
												style: {
													background: 'transparent'
												},
												children: [
													BX.create('input', {
														props: {
															type: 'text',
															value: '',
															id: 'feed-add-post-destination-input',
															className: 'feed-add-destination-inp'
														}
													})
												]
											}),
											BX.create('a', {
												props: {
													href: '#',
													id: 'bx-destination-tag',
													className: 'feed-add-destination-link'
												},
												style: {
													background: 'transparent'
												},
												text: BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_ADD_RIGHTS_USER'),
												events: {
													click: BX.delegate(function () {
													}, this)
												}
											})
										]
									}),
									BX.create('div', {
										style: {
											marginTop: '27px',
											marginBottom: '20px'
										},
										html:
											'<div><input type="checkbox" ' + (showExtendedRights? 'checked="checked"' : '') + ' id="showExtendedRights"/><label for="showExtendedRights">' + BX.message("DISK_FOLDER_LIST_LABEL_SHOW_EXTENDED_RIGHTS") + '</label></div>' +
											(showSystemFolderCheckbox ? '<div><input type="checkbox" id="setRightsOnPseudoSystemFolders"/><label for="setRightsOnPseudoSystemFolders">' + BX.message("DISK_FOLDER_LIST_LABEL_CHANGE_SYSTEM_FOLDERS").replace('#FOLDERS#', response.systemFolders.names.join(', ')) + '</label></div>' : '')
									})
								]
							})
						],
						buttons: [
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_FOLDER_LIST_BTN_SAVE'),
								className: "ui-btn ui-btn-success",
								events: {
									click: BX.delegate(function () {

										BX.Disk.ajax({
											method: 'POST',
											dataType: 'json',
											url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'saveRightsOnStorage'),
											data: {
												isChangedRights: isChangedRights? 1 : 0,
												showExtendedRights: BX('showExtendedRights').checked? 1 : 0,
												storageId: storageId,
												storageNewRights: storageNewRights
											},
											onsuccess: BX.delegate(function (response) {
												if (!response) {
													return;
												}
												BX.Disk.showModalWithStatusAction(response);
												document.location.reload();
											}, this)
										});

										if(!!modalWindow)
										{
											modalWindow.close();
										}
									}, this)
								}
							}),
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_JS_BTN_CANCEL'),
								className: 'ui-btn ui-btn-link',
								events: {
									click: function (e)
									{
										if(!!modalWindow)
										{
											modalWindow.close();
										}
									}
								}
							})
						]
					});
				}, this)
			}
		);

	};

	FolderListClass.prototype.showRightsOnObjectDetail = function (params)
	{
		storageNewRights = {};
		var storageId = this.storage.id;
		var rights = {};

		params = params || {};
		var objectId = params.object.id;

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showRightsOnObjectDetail'),
			{
				id: 'folder_list_rights_detail_object_' + objectId,
				responseType: 'json',
				postData: {
					objectId: objectId,
					storageId: storageId
				},
				afterSuccessLoad: BX.delegate(function(response, windowLoader)
				{
					windowLoader && windowLoader.close();

					if(response.status !== 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message
						});

						return;
					}

					if(BX.Disk.isEmptyObject(moduleTasks))
					{
						moduleTasks = BX.clone(response.tasks, true);
						BX.Disk.setModuleTasks(moduleTasks);
					}

					for (var i in response.rights) {
						if (!response.rights.hasOwnProperty(i)) {
							continue;
						}
						var rightsByAccessCode = response.rights[i];
						for (var j in rightsByAccessCode) {
							if (!rightsByAccessCode.hasOwnProperty(j)) {
								continue;
							}

							rights[i] = {
								item: {
									id: i,
									name: showAccessCodeFullName(response.accessCodeNames[i]),
									avatar: null
								},
								type: 'group',
								right: {
									title: rightsByAccessCode[j].TASK.TITLE,
									id: rightsByAccessCode[j].TASK.ID
								}
							};
						}
					}
					var modalWindow = BX.Disk.modalWindow({
						modalId: 'bx-disk-detail-sharing-folder-change-right',
						title: BX.message('DISK_FOLDER_LIST_RIGHTS_TITLE_MODAL_WITH_NAME'). replace('#OBJECT#', response.object.name),
						withoutWindowManager: true,
						contentClassName: '',
						contentStyle: {
							//paddingTop: '30px',
							//paddingBottom: '70px'
						},
						events: {
							onAfterPopupShow: BX.delegate(function () {
								storageNewRights = BX.clone(rights, true);
								originalRights = BX.clone(rights, true);
								detachedRights = {};

								BX.Access.Init({
									groups: { disabled: this.isBitrix24 }
								});
								for (var key in storageNewRights) {
									if(!storageNewRights.hasOwnProperty(key))
										continue;

									storageNewRights[key].isBitrix24 = this.isBitrix24;
									BX.Disk.appendSystemRight(storageNewRights[key]);
								}

								BX.addCustomEvent('onChangeSystemRight', BX.proxy(this.onChangeSystemRight, this));
								BX.addCustomEvent('onDetachSystemRight', BX.proxy(this.onDetachSystemRight, this));

								BX.bind(BX('feed-add-post-destination-container'), 'click', BX.delegate(function(e){
									var startValue = {};
									for (var key in storageNewRights) {
										if(!storageNewRights.hasOwnProperty(key))
											continue;
										startValue[key] = true;
									}
									BX.Access.SetSelected(startValue);


									BX.Access.ShowForm({
										showSelected: true,
										callback: BX.delegate(function (arRights){
											var res = [];
											for (var provider in arRights) {
												for (var id in arRights[provider]) {
													res.push(arRights[provider][id]);
													this.onSelectSystemRight(arRights[provider][id], provider);
												}
											}
										}, this)
									});

									return BX.PreventDefault(e);
								}, this));


							}, this),
							onPopupClose: BX.delegate(function () {

								BX.removeCustomEvent('onChangeSystemRight', BX.proxy(this.onChangeRight, this));

							}, this)
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content'
								},
								children: [
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS_USER') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('div', {
										props: {
											id: 'feed-add-post-destination-container',
											className: 'feed-add-post-destination-wrap'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'feed-add-post-destination-item'
												}
											}),
											BX.create('span', {
												props: {
													id: 'feed-add-post-destination-input-box',
													className: 'feed-add-destination-input-box'
												},
												style: {
													background: 'transparent'
												},
												children: [
													BX.create('input', {
														props: {
															type: 'text',
															value: '',
															id: 'feed-add-post-destination-input',
															className: 'feed-add-destination-inp'
														}
													})
												]
											}),
											BX.create('a', {
												props: {
													href: '#',
													id: 'bx-destination-tag',
													className: 'feed-add-destination-link'
												},
												style: {
													background: 'transparent'
												},
												text: BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_ADD_RIGHTS_USER'),
												events: {
													click: BX.delegate(function () {
													}, this)
												}
											})
										]
									})
								]
							})
						],
						buttons: [
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_FOLDER_LIST_BTN_SAVE'),
								className: "ui-btn ui-btn-success",
								events: {
									click: BX.delegate(function () {

										BX.Disk.ajax({
											method: 'POST',
											dataType: 'json',
											url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'saveRightsOnObject'),
											data: {
												objectId: objectId,
												objectNewRights: storageNewRights,
												detachedRights: detachedRights
											},
											onsuccess: BX.delegate(function (response) {
												if (!response) {
													return;
												}
												if (params.object.isFolder)
												{
													response.message = BX.message('DISK_FOLDER_LIST_OK_FOLDER_RIGHTS_MODIFIED').replace('#FOLDER#', params.object.name);
												}
												else
												{
													response.message = BX.message('DISK_FOLDER_LIST_OK_FILE_RIGHTS_MODIFIED').replace('#FILE#', params.object.name);
												}

												BX.Disk.showModalWithStatusAction(response);

											}, this)
										});

										if(!!modalWindow)
										{
											modalWindow.close();
										}
									}, this)
								}
							}),
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_JS_BTN_CANCEL'),
								className: 'ui-btn ui-btn-link',
								events: {
									click: function (e)
									{
										if(!!modalWindow)
										{
											modalWindow.close();
										}
									}
								}
							})
						]
					});
				}, this)
			}
		);

	};

	FolderListClass.prototype.openSlider = function (url)
	{
		BX.SidePanel.Instance.open(url, {
			allowChangeHistory: false
		});
	};

	FolderListClass.prototype.showSettingsOnBizproc = function ()
	{
		var storageId = this.storage.id;
		var activationBizProc = '';

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSettingsOnBizproc'),
			{
				responseType: 'json',
				postData: {
					storageId: storageId
				},
				afterSuccessLoad: BX.delegate(function(response)
				{
					if(response.status != 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message
						})
					}

					if(response.statusBizProc)
					{
						activationBizProc = 'checked';
					}

					BX.Disk.modalWindow({
						modalId: 'bx-disk-settings-bizproc',
						title: BX.message('DISK_FOLDER_LIST_BIZPROC_TITLE_MODAL'),
						contentClassName: '',
						events: {
						},
						content: [
							BX.create('table', {
								html: '<tr><td><label for="activationBizProc">'+BX.message("DISK_FOLDER_LIST_BIZPROC_LABEL")+'</label></td>' +
								'<td><input type="checkbox" id="activationBizProc" '+activationBizProc+' /></td>' +
								'</tr>'
							})
						],
						buttons: [
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_FOLDER_LIST_BTN_SAVE'),
								className: "ui-btn ui-btn-success",
								events: {
									click: BX.delegate(function () {

										BX.Disk.ajax({
											method: 'POST',
											dataType: 'json',
											url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'saveSettingsOnBizproc'),
											data: {
												storageId: storageId,
												activationBizproc: BX('activationBizProc').checked ? 1 : 0
											},
											onsuccess: BX.delegate(function (response) {
												if (!response) {
													return;
												}
												if(response.status != 'success')
												{
													response.errors = response.errors || [{}];
													BX.Disk.showModalWithStatusAction({
														status: 'error',
														message: response.errors.pop().message
													})
												}
												else
												{
													BX.Disk.showModalWithStatusAction(response);
												}
												location.reload();
											}, this)
										});
									}, this)
								}
							}),
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_JS_BTN_CANCEL'),
								className: 'ui-btn ui-btn-link',
								events: {
									click: function (e)
									{
										BX.PopupWindowManager.getCurrentPopup().destroy();
									}
								}
							})
						]

					});
				}, this)
			}
		);

	};

	FolderListClass.prototype.openWindowForSelectDocumentService = function ()
	{
		BX.Disk.InformationPopups.openWindowForSelectDocumentService({});
	};

	FolderListClass.prototype.showHiddenContent = function (el)
	{
		el.style.display = (el.style.display == 'none') ? 'block' : 'none';
	};

	FolderListClass.prototype.hide = function(el)
	{
		if (!el.getAttribute('displayOld'))
		{
			el.setAttribute("displayOld", el.style.display)
		}
		el.style.display = "none"
	};

	FolderListClass.prototype.showNetworkDriveConnect = function (params)
	{
		params = params || {};
		var link = params.link,
			showHiddenContent = this.showHiddenContent,
			hide = this.hide;
		showHiddenContent(BX('bx-disk-network-drive-full'));

		BX.Disk.modalWindow({
			modalId: 'bx-disk-show-network-drive-connect',
			title: BX.message('DISK_FOLDER_LIST_PAGE_TITLE_NETWORK_DRIVE'),
			contentClassName: 'tac',
			contentStyle: {
			},
			events: {
				onAfterPopupShow: function () {
					var inputLink = BX('disk-get-network-drive-link');
					BX.focus(inputLink);
					inputLink.setSelectionRange(0, inputLink.value.length)
				},
				onPopupClose: function () {
					hide(BX('bx-disk-network-drive'));
					hide(BX('bx-disk-network-drive-full'));
					document.body.appendChild(BX('bx-disk-network-drive-full'));
					this.destroy();
				}
			},
			content: [
				BX.create('label', {
					text: BX.message('DISK_FOLDER_LIST_PAGE_TITLE_NETWORK_DRIVE_DESCR_MODAL') + ' :',
					props: {
						className: 'bx-disk-popup-label',
						"for": 'disk-get-network-drive-link'
					}
				}),
				BX.create('input', {
					style: {
						marginTop: '10px'
					},
					props: {
						id: 'disk-get-network-drive-link',
						className: 'bx-disk-popup-input',
						type: 'text',
						value: link
					}
				}),
				BX('bx-disk-network-drive-full')
			],
			buttons: [
				new BX.PopupWindowCustomButton({
					text: BX.message('DISK_JS_BTN_CLOSE'),
					className: 'ui-btn ui-btn-link',
					events: {
						click: function () {
							BX.PopupWindowManager.getCurrentPopup().close();
						}
					}
				})
			]
		});
		if(BX('bx-disk-network-drive-secure-label'))
		{
			hide(BX.findChildByClassName(BX('bx-disk-show-network-drive-connect'), 'bx-disk-popup-label'));
			hide(BX.findChildByClassName(BX('bx-disk-show-network-drive-connect'), 'bx-disk-popup-input'));
		}
	};


	FolderListClass.prototype.showSharingDetailWithSharing = function (params) {

		entityToNewShared = {};
		loadedReadOnlyEntityToNewShared = {};

		params = params || {};
		var objectId = params.object.id;

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showSharingDetailAppendSharing'),
			{
				id: 'folder_list_sharing_detail_object_' + objectId,
				responseType: 'json',
				postData: {
					objectId: objectId
				},
				afterSuccessLoad: BX.delegate(function(response)
				{
					if(response.status != 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message
						})
					}

					var objectOwner = {
						name: response.owner.name,
						avatar: response.owner.avatar,
						link: response.owner.link
					};
					entityToNewSharedMaxTaskName = response.owner.maxTaskName;

					BX.Disk.modalWindow({
						modalId: 'bx-disk-detail-sharing-folder-change-right',
						title: BX.message('DISK_FOLDER_LIST_SHARING_TITLE_MODAL_3'),
						contentClassName: '',
						contentStyle: {
							//paddingTop: '30px',
							//paddingBottom: '70px'
						},
						events: {
							onAfterPopupShow: BX.delegate(function () {

								BX.addCustomEvent('onChangeRightOfSharing', BX.proxy(this.onChangeRightOfSharing, this));

								for (var i in response.members) {
									if (!response.members.hasOwnProperty(i)) {
										continue;
									}

									entityToNewShared[response.members[i].entityId] = {
										item: {
											id: response.members[i].entityId,
											name: response.members[i].name,
											avatar: response.members[i].avatar
										},
										type: response.members[i].type,
										right: response.members[i].right
									};
								}
								loadedReadOnlyEntityToNewShared = BX.clone(entityToNewShared, true);

								BX.SocNetLogDestination.init({
									name : this.destFormName,
									searchInput : BX('feed-add-post-destination-input'),
									bindMainPopup : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
									bindSearchPopup : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
									callback : {
										select : BX.proxy(this.onSelectDestination, this),
										unSelect : BX.proxy(this.onUnSelectDestination, this),
										openDialog : BX.proxy(this.onOpenDialogDestination, this),
										closeDialog : BX.proxy(this.onCloseDialogDestination, this),
										openSearch : BX.proxy(this.onOpenSearchDestination, this),
										closeSearch : BX.proxy(this.onCloseSearchDestination, this)
									},
									items: response.destination.items,
									itemsLast: response.destination.itemsLast,
									itemsSelected : response.destination.itemsSelected
								});

								var BXSocNetLogDestinationFormName = this.destFormName;
								BX.bind(BX('feed-add-post-destination-container'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);BX.PreventDefault(e); });
								BX.bind(BX('feed-add-post-destination-input'), 'keyup', BX.proxy(this.onKeyUpDestination, this));
								BX.bind(BX('feed-add-post-destination-input'), 'keydown', BX.proxy(this.onKeyDownDestination, this));
							}, this),
							onPopupClose: BX.delegate(function () {
								if(BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog())
								{
									BX.SocNetLogDestination.closeDialog()
								}

								BX.removeCustomEvent('onChangeRightOfSharing', BX.proxy(this.onChangeRightOfSharing, this));
								BX.proxy_context.destroy();
							}, this)
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content'
								},
								children: [
									BX.create('table', {
										props: {
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_OWNER') + '</td>' +
												'</tr>'
											}),
											BX.create('tr', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="' + objectOwner.link + '"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url(\'' + encodeURI(objectOwner.avatar) + '\');"></span>' + BX.util.htmlspecialchars(objectOwner.name) + '</a></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS_USER') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('div', {
										props: {
											id: 'feed-add-post-destination-container',
											className: 'feed-add-post-destination-wrap'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'feed-add-post-destination-item'
												}
											}),
											BX.create('span', {
												props: {
													id: 'feed-add-post-destination-input-box',
													className: 'feed-add-destination-input-box'
												},
												style: {
													background: 'transparent'
												},
												children: [
													BX.create('input', {
														props: {
															type: 'text',
															value: '',
															id: 'feed-add-post-destination-input',
															className: 'feed-add-destination-inp'
														}
													})
												]
											}),
											BX.create('a', {
												props: {
													href: '#',
													id: 'bx-destination-tag',
													className: 'feed-add-destination-link'
												},
												style: {
													background: 'transparent'
												},
												text: BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_ADD_RIGHTS_USER'),
												events: {
													click: BX.delegate(function () {
													}, this)
												}
											})
										]
									})
								]
							})
						],
						buttons: [
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_FOLDER_LIST_BTN_SAVE'),
								className: "ui-btn ui-btn-success",
								events: {
									click: BX.delegate(function () {

										BX.Disk.ajax({
											method: 'POST',
											dataType: 'json',
											url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'appendSharing'),
											data: {
												objectId: objectId,
												entityToNewShared: entityToNewShared
											},
											onsuccess: BX.delegate(function (response) {
												if (!response) {
													return;
												}
												BX.Disk.showModalWithStatusAction(response);
												var icon = BX.delegate(getIconElementByObjectId, this)(objectId);
												if(icon)
												{
													if(!entityToNewShared || BX.Disk.isEmptyObject(entityToNewShared))
													{
														BX.removeClass(icon, 'icon-shared icon-shared_2 shared');
														BX.removeClass(icon, 'icon-shared_1');
													}
													else
													{
														BX.addClass(icon, 'icon-shared icon-shared_2 shared');
													}
												}

											}, this)
										});

										BX.PopupWindowManager.getCurrentPopup().close();
									}, this)
								}
							}),
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_JS_BTN_CANCEL'),
								className: 'ui-btn ui-btn-link',
								events: {
									click: function (e)
									{
										BX.PopupWindowManager.getCurrentPopup().destroy();
									}
								}
							})
						]
					});
				}, this)
			}
		);
	};

	FolderListClass.prototype.onCreateExtendedFolder = function () {
		this.showCreateFolderWithSharing({

		});
	};

	FolderListClass.prototype.showCreateFolderWithSharing = function ()
	{
		entityToNewShared = {};
		storageNewRights = {};
		var storageId = this.storage.id;
		var rights = {};

		BX.Disk.modalWindowLoader(
			BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'showCreateFolderWithSharingInCommon'),
			{
				id: 'folder_list_rights_detail_storage_' + storageId,
				responseType: 'json',
				postData: {
					storageId: storageId
				},
				afterSuccessLoad: BX.delegate(function(response)
				{
					if(response.status != 'success')
					{
						response.errors = response.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: response.errors.pop().message
						})
					}

					if(BX.Disk.isEmptyObject(moduleTasks))
					{
						moduleTasks = BX.clone(response.tasks, true);
						BX.Disk.setModuleTasks(moduleTasks);
					}

					for (var i in response.rights) {
						if (!response.rights.hasOwnProperty(i)) {
							continue;
						}
						var rightsByAccessCode = response.rights[i];
						for (var j in rightsByAccessCode) {
							if (!rightsByAccessCode.hasOwnProperty(j)) {
								continue;
							}

							rights[i] = {
								detachOnly: true,
								item: {
									id: i,
									name: response.accessCodeNames[i].name,
									avatar: null
								},
								type: 'group',
								right: {
									title: rightsByAccessCode[j].TASK.TITLE,
									id: rightsByAccessCode[j].TASK.ID
								}
							};
						}
					}

					BX.Disk.modalWindow({
						modalId: 'bx-disk-detail-sharing-create-folder',
						title: BX.message('DISK_FOLDER_LIST_CREATE_FOLDER_MODAL'),
						contentClassName: '',
						contentStyle: {},
						events: {
							onAfterPopupShow: BX.delegate(function () {
								BX.focus(BX('disk-new-create-filename'));
								storageNewRights = BX.clone(rights, true);

								for (var i in rights) {
									if (!rights.hasOwnProperty(i)) {
										continue;
									}
									BX.Disk.appendRight(rights[i]);

								}


								BX.addCustomEvent('onChangeRightOfSharing', BX.proxy(this.onChangeRightOfSharing, this));
								BX.addCustomEvent('onChangeRight', BX.proxy(this.onChangeRight, this));
								BX.addCustomEvent('onDetachRight', BX.proxy(this.onDetachRight, this));

								BX.SocNetLogDestination.init({
									name : this.destFormName,
									searchInput : BX('feed-add-post-destination-input'),
									bindMainPopup : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
									bindSearchPopup : { 'node' : BX('feed-add-post-destination-container'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
									callback : {
										select : BX.proxy(this.onSelectDestination, this),
										unSelect : BX.proxy(this.onUnSelectDestination, this),
										openDialog : BX.proxy(this.onOpenDialogDestination, this),
										closeDialog : BX.proxy(this.onCloseDialogDestination, this),
										openSearch : BX.proxy(this.onOpenSearchDestination, this),
										closeSearch : BX.proxy(this.onCloseSearchDestination, this)
									},
									items: response.destination.items,
									itemsLast: response.destination.itemsLast,
									itemsSelected : response.destination.itemsSelected
								});

								var BXSocNetLogDestinationFormName = this.destFormName;
								BX.bind(BX('feed-add-post-destination-container'), 'click', function(e){BX.SocNetLogDestination.openDialog(BXSocNetLogDestinationFormName);BX.PreventDefault(e); });
								BX.bind(BX('feed-add-post-destination-input'), 'keyup', BX.proxy(this.onKeyUpDestination, this));
								BX.bind(BX('feed-add-post-destination-input'), 'keydown', BX.proxy(this.onKeyDownDestination, this));



							}, this),
							onPopupClose: BX.delegate(function () {
								if(BX.SocNetLogDestination && BX.SocNetLogDestination.isOpenDialog())
								{
									BX.SocNetLogDestination.closeDialog()
								}

								BX.removeCustomEvent('onChangeRight', BX.proxy(this.onChangeRight, this));
								BX.proxy_context.destroy();
							}, this)
						},
						content: [
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content-small'
								},
								children: [
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
											BX.message('DISK_FOLDER_LIST_LABEL_NAME_CREATE_FOLDER')
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
								]
							}),
							BX.create('div', {
								props: {
									className: 'bx-disk-popup-content'
								},
								children: [
									BX.create('table', {
										props: {
											id: 'bx-disk-popup-shared-people-list',
											className: 'bx-disk-popup-shared-people-list'
										},
										children: [
											BX.create('thead', {
												html: '<tr>' +
													'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS_USER') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_RIGHTS') + '</td>' +
													'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
												'</tr>'
											})
										]
									}),
									BX.create('div', {
										props: {
											id: 'feed-add-post-destination-container',
											className: 'feed-add-post-destination-wrap'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'feed-add-post-destination-item'
												}
											}),
											BX.create('span', {
												props: {
													id: 'feed-add-post-destination-input-box',
													className: 'feed-add-destination-input-box'
												},
												style: {
													background: 'transparent'
												},
												children: [
													BX.create('input', {
														props: {
															type: 'text',
															value: '',
															id: 'feed-add-post-destination-input',
															className: 'feed-add-destination-inp'
														}
													})
												]
											}),
											BX.create('a', {
												props: {
													href: '#',
													id: 'bx-destination-tag',
													className: 'feed-add-destination-link'
												},
												style: {
													background: 'transparent'
												},
												text: BX.message('DISK_FOLDER_LIST_SHARING_LABEL_NAME_ADD_RIGHTS_USER'),
												events: {
													click: BX.delegate(function () {
													}, this)
												}
											})
										]
									})
								]
							})
						],
						buttons: [
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_FOLDER_LIST_BTN_SAVE'),
								className: "ui-btn ui-btn-success",
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
											url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', 'createFolderWithSharing'),
											data: {
												name: newName,
												storageId: storageId,
												storageNewRights: storageNewRights || {},
												entityToNewShared: entityToNewShared || {}
											},
											onsuccess: BX.delegate(function (response) {
												if (!response) {
													return;
												}
												BX.Disk.showModalWithStatusAction(response);
												if (response.status && response.status == 'success') {
													window.document.location = BX.Disk.getUrlToShowObjectInGrid(response.folder.id);
												}
											}, this)
										});

										BX.PopupWindowManager.getCurrentPopup().close();
									}, this)
								}
							}),
							new BX.PopupWindowCustomButton({
								text: BX.message('DISK_JS_BTN_CANCEL'),
								className: 'ui-btn ui-btn-link',
								events: {
									click: function (e)
									{
										BX.PopupWindowManager.getCurrentPopup().destroy();
									}
								}
							})
						]
					});
				}, this)
			}
		);

	};

	FolderListClass.prototype.onSelectSystemRight = function(item, type)
	{
		storageNewRights[item.id] = storageNewRights[item.id] || {};
		isChangedRights = true;

		var providerPrefix = BX.Access.GetProviderPrefix(type, item.id);
		storageNewRights[item.id] = {
			item: {
				avatar: null,
				id: item.id,
				name: (providerPrefix? providerPrefix + ': ': '') + item.name
			},
			type: 'user', //todo fix nd actualize this. May be groups, users, departments, etc.
			right: 'read'
		};

		storageNewRights[item.id].isBitrix24 = this.isBitrix24;
		BX.Disk.appendSystemRight(storageNewRights[item.id]);
	};

	FolderListClass.prototype.onSelectRightDestination = function(item, type, search)
	{
		storageNewRights[item.id] = storageNewRights[item.id] || {};

		storageNewRights[item.id] = {
			item: item,
			type: type,
			right: storageNewRights[item.id].right || {}
		};

		BX.Disk.appendRight({
			destFormName: this.destFormName,
			item: item,
			type: type,
			right: storageNewRights[item.id].right
		});
	};

	FolderListClass.prototype.onUnSelectRightDestination = function (item, type, search)
	{
		var entityId = item.id;

		delete storageNewRights[entityId];

		var child = BX.findChild(BX('bx-disk-popup-shared-people-list'), {attribute: {'data-dest-id': '' + entityId + ''}}, true);
		if (child) {
			BX.remove(child);
		}
	};

	FolderListClass.prototype.onChangeSystemRight = function(entityId, task)
	{
		if(storageNewRights[entityId])
		{
			isChangedRights = true;
			storageNewRights[entityId].right = {
				id: task.ID,
				title: task.TITLE
			};
		}
	};

	FolderListClass.prototype.onDetachSystemRight = function(entityId)
	{
		if(storageNewRights[entityId])
		{
			isChangedRights = true;
			BX.Access.DeleteSelected(entityId);
			detachedRights[entityId] = storageNewRights[entityId];

			delete storageNewRights[entityId];
		}
	};

	FolderListClass.prototype.onChangeRight = function(entityId, task)
	{
		if(storageNewRights[entityId])
		{
			storageNewRights[entityId].right = {
				id: task.ID,
				title: task.TITLE
			};
		}
	};

	FolderListClass.prototype.onDetachRight = function(entityId)
	{
		if(storageNewRights[entityId])
		{
			delete storageNewRights[entityId];
		}
	};

	FolderListClass.prototype.onSelectDestination = function(item, type, search)
	{
		entityToNewShared[item.id] = entityToNewShared[item.id] || {};
		BX.Disk.appendNewShared({
			maxTaskName: entityToNewSharedMaxTaskName,
			readOnly: !!loadedReadOnlyEntityToNewShared[item.id],
			destFormName: this.destFormName,
			item: item,
			type: type,
			right: entityToNewShared[item.id].right
		});

		entityToNewShared[item.id] = {
			item: item,
			type: type,
			right: entityToNewShared[item.id].right || 'disk_access_read'
		};
	};

	FolderListClass.prototype.onUnSelectDestination = function (item, type, search)
	{
		var entityId = item.id;

		if(!!loadedReadOnlyEntityToNewShared[entityId])
		{
			return false;
		}

		delete entityToNewShared[entityId];

		var child = BX.findChild(BX('bx-disk-popup-shared-people-list'), {attribute: {'data-dest-id': '' + entityId + ''}}, true);
		if (child) {
			BX.remove(child);
		}
	};

	FolderListClass.prototype.onChangeRightOfSharing = function(entityId, taskName)
	{
		if(entityToNewShared[entityId])
		{
			entityToNewShared[entityId].right = taskName;
		}
	};

	FolderListClass.prototype.onOpenDialogDestination = function()
	{
		BX.style(BX('feed-add-post-destination-input-box'), 'display', 'inline-block');
		BX.style(BX('bx-destination-tag'), 'display', 'none');
		BX.focus(BX('feed-add-post-destination-input'));
		if(BX.SocNetLogDestination.popupWindow)
			BX.SocNetLogDestination.popupWindow.adjustPosition({ forceTop: true });
	};

	FolderListClass.prototype.onCloseDialogDestination = function()
	{
		var input = BX('feed-add-post-destination-input');
		if (!BX.SocNetLogDestination.isOpenSearch() && input && input.value.length <= 0)
		{
			BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
			BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
		}
	};

	FolderListClass.prototype.onOpenSearchDestination = function()
	{
		if(BX.SocNetLogDestination.popupSearchWindow)
			BX.SocNetLogDestination.popupSearchWindow.adjustPosition({ forceTop: true });
	};

	FolderListClass.prototype.onCloseSearchDestination = function()
	{
		var input = BX('feed-add-post-destination-input');
		if (!BX.SocNetLogDestination.isOpenSearch() && input && input.value.length > 0)
		{
			BX.style(BX('feed-add-post-destination-input-box'), 'display', 'none');
			BX.style(BX('bx-destination-tag'), 'display', 'inline-block');
			BX('feed-add-post-destination-input').value = '';
		}
	};

	FolderListClass.prototype.onKeyDownDestination = function (event)
	{
		var BXSocNetLogDestinationFormName = this.destFormName;
		if (event.keyCode == 8 && BX('feed-add-post-destination-input').value.length <= 0) {
			BX.SocNetLogDestination.sendEvent = false;
			BX.SocNetLogDestination.deleteLastItem(BXSocNetLogDestinationFormName);
		}

		return true;
	};

	FolderListClass.prototype.onKeyUpDestination = function (event)
	{
		var BXSocNetLogDestinationFormName = this.destFormName;
		if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
			return false;

		if (event.keyCode == 13) {
			BX.SocNetLogDestination.selectFirstSearchItem(BXSocNetLogDestinationFormName);
			return BX.PreventDefault(event);
		}
		if (event.keyCode == 27) {
			BX('feed-add-post-destination-input').value = '';
		}
		else {
			BX.SocNetLogDestination.search(BX('feed-add-post-destination-input').value, true, BXSocNetLogDestinationFormName);
		}

		if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
			BX.SocNetLogDestination.closeDialog();

		if (event.keyCode == 8) {
			BX.SocNetLogDestination.sendEvent = true;
		}
		return BX.PreventDefault(event);
	};

	FolderListClass.prototype.showSearchProcessInConnectedFolders = function()
	{
		if (this.layout.loader)
		{
			return;
		}

		this.layout.loader = BX.create("div", {
			props: {
				className: "bx-disk-interface-filelist-loader"
			},
			children: [
				 BX.create("div", {
					props: {
						className: "bx-disk-interface-filelist-loader-wrapper"
					},
					children: [
						this.layout.loaderWrapper = BX.create("div", {
							props: {
								className: "bx-disk-interface-filelist-loader-container"
							}
						}),
						BX.create("div", {
							props: {
								className: "bx-disk-interface-filelist-loader-text"
							},
							text: BX.message('DISK_FOLDER_LIST_SEARCH_PROGRESS_LABEL')
						})
					]
				})
			]
		});

		var loader = new BX.Loader({size: 170});

		loader.show(this.layout.loaderWrapper);
		if (this.commonGrid.isGrid())
		{
			document.querySelector('.main-grid-wrapper').appendChild(this.layout.loader);
		}
		else if (this.commonGrid.isTile())
		{
			this.commonGrid.getContainer().parentNode.appendChild(this.layout.loader);
		}
	};

	FolderListClass.prototype.removeSearchProcessInConnectedFolders = function()
	{
		if(!this.layout.loader)
			return;

		this.layout.loader.parentNode.removeChild(this.layout.loader);
		this.layout.loader = null;
	};

	return FolderListClass;
})();

(function() {

	"use strict";

	/**
	 * @namespace BX.Disk.Model.FolderList
	 */
	BX.namespace("BX.Disk.Model.FolderList");

	/**
	 *
	 * @param {object} parameters
	 * @extends {BX.Disk.Model.Item}
	 * @constructor
	 */
	BX.Disk.Model.FolderList.SearchProgress = function(parameters)
	{
		BX.Disk.Model.Item.apply(this, arguments);

		this.templateId = 'search-progress';
	};

	BX.Disk.Model.FolderList.SearchProgress.prototype =
	{
		__proto__: BX.Disk.Model.Item.prototype,
		constructor: BX.Disk.Model.FolderList.SearchProgress,

		isTimeToShow: function ()
		{
			return this.state.total > 0 && this.state.total !== this.state.current;
		},

		getDefaultStateValues: function ()
		{
			return {
				isTimeToShow: this.isTimeToShow.bind(this)
			};
		}
	};

	BX.Disk.Model.FolderList.CommonGrid = function(parameters)
	{
		this.instance = parameters.instance;
	};

	BX.Disk.Model.FolderList.CommonGrid.prototype =
	{
		constructor: BX.Disk.Model.FolderList.CommonGrid,

		getId: function ()
		{
			return this.instance.getId();
		},

		isGrid: function ()
		{
			return !this.isTile();
		},

		isTile: function ()
		{
			return BX.TileGrid.Grid && (this.instance instanceof BX.TileGrid.Grid);
		},

		getContainer: function ()
		{
			return this.instance.getContainer();
		},

		fade: function ()
		{
			if (this.isGrid())
			{
				this.instance.tableFade();
			}
			else
			{
				this.instance.setFadeContainer();
				this.instance.getLoader();
				this.instance.showLoader();
			}
		},

		unFade: function ()
		{
			if (this.isGrid())
			{
				this.instance.tableUnfade();
			}
			else
			{
				this.instance.getLoader().hide();
				this.instance.unSetFadeContainer();
			}
		},

		getActionKey: function()
		{
			return ('action_button_' + this.instance.getId());
		},

		getSelectedIds: function ()
		{
			if (this.isGrid())
			{
				return this.instance.getRows().getSelectedIds();
			}
			else
			{
				return this.instance.getSelectedItems().map(function(item){
					return item.getId();
				});
			}
		},

		getIds: function ()
		{
			if (this.isGrid())
			{
				return this.instance.getRows().getBodyChild().map(function (row) {
					return row.getId();
				});
			}
			else
			{
				return this.instance.items.map(function(item){
					return item.id;
				});
			}
		},

		countItems: function ()
		{
			if (this.isGrid())
			{
				return this.instance.getRows().getBodyChild().length;
			}
			else
			{
				return this.instance.countItems();
			}
		},

		reload: function (url, data)
		{
			data = data || {};

			if (this.isGrid())
			{
				var promise = new BX.Promise();
				this.instance.reloadTable(
					"POST",
					data,
					function() {
						promise.fulfill();
					},
					url
				);

				return promise;
			}
			else
			{
				return this.instance.reload(url, data);
			}
		},

		getActionsMenu: function (itemId)
		{
			if (this.isGrid())
			{
				return this.instance.getRows().getById(itemId).getActionsMenu();
			}
			else
			{
				var item = this.instance.getItem(itemId);
				if (item)
				{
					return item.getActionsMenu();
				}
			}
		},

		getItemById: function (id)
		{
			if (this.isGrid())
			{
				return this.instance.getRows().getById(id);
			}
			else
			{
				return this.instance.getItem(id);
			}
		},

		scrollTo: function (id)
		{
			var contentNode;
			if (this.isGrid())
			{
				var row = this.instance.getRows().getById(id);
				if (row && row.node)
				{
					contentNode = row.node;
				}
			}
			else
			{
				var item = this.instance.getItem(id);
				if (row && row.node)
				{
					contentNode = row.getContainer();
				}
			}

			if(contentNode)
			{
				(new BX.easing({
					duration : 500,
					start : { scroll : window.pageYOffset || document.documentElement.scrollTop },
					finish : { scroll : BX.pos(contentNode).top },
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step : function(state){
						window.scrollTo(0, state.scroll);
					}
				})).animate();
			}
		},

		getActionById: function (id, menuItemId)
		{
			var item = this.getItemById(id);
			if (!item)
			{
				return null;
			}

			var actions = item.getActions() || [];
			for (var i = 0; i < actions.length; i++)
			{
				if (actions[i].id && actions[i].id === menuItemId)
				{
					return actions[i];
				}
			}

			return null;
		},

		removeItemById: function (itemId)
		{
			BX.fireEvent(document, 'click');

			if (this.isGrid())
			{
				this.instance.removeRow(itemId);
			}
			else
			{
				var item = this.instance.getItem(itemId);
				if (item)
				{
					this.instance.removeItem(item);
				}
			}
		},

		selectItemById: function (itemId)
		{
			var item;
			if (this.isGrid())
			{
				item = this.instance.getRows().getById(itemId);
				if(item)
				{
					item.select();
				}
			}
			else
			{
				item = this.instance.getItem(itemId);
				if (item)
				{
					this.instance.selectItem(item);
				}
			}
		},

		removeSelected: function ()
		{
			if (this.isGrid())
			{
				this.instance.removeSelected();
			}
			else
			{
				//todo here we have to remove items from server
			}
		},

		sortByColumn: function (column)
		{
			this.instance.sortByColumn(column);
		}
	};

	BX.namespace("BX.Disk.TileGrid");

	/**
	 *
	 * @param options
	 * @extends {BX.TileGrid.Item}
	 * @constructor
	 */
	BX.Disk.TileGrid.Item = function(options)
	{
		BX.TileGrid.Item.apply(this, arguments);

		this.isDraggable = options.isDraggable;
		this.isDroppable = options.isDroppable;
		this.dblClickDelay = 0;
		this.title = options.name;
		this.isFolder = options.isFolder;
		this.isFile = options.isFile;
		this.canAdd = options.canAdd;
		this.isLocked = options.isLocked;
		this.isSymlink = options.isSymlink;
		this.image = options.image;
		this.actions = options.actions;
		this.link = options.link;
		this.attributes = options.attributes;
		this.item = {
			container: null,
			action: null,
			title: null,
			titleWrapper: null,
			titleLink: null,
			titleInput: null,
			lock: null,
			symlink: null,
			imageBlock: null,
			picture: null,
			fileType: null,
			icons: null
		};
		this.actionsMenu = null;
		this.imageItemHandler = null;

		BX.addCustomEvent(window, 'TileGrid.Grid:onItemDragStart', function() {
			if(this.actionsMenu)
				this.actionsMenu.popupWindow.close();
		}.bind(this));
	};

	BX.Disk.TileGrid.Item.prototype =
	{
		__proto__: BX.TileGrid.Item.prototype,
		constructor: BX.TileGrid.Item,

		handleDblClick: function()
		{
			BX.onCustomEvent("Disk.TileItem.Item:onItemDblClick", [this]);
		},

		handleEnter: function()
		{
			BX.onCustomEvent("Disk.TileItem.Item:onItemEnter", [this]);
		},

		/**
		 *
		 * @returns {Element}
		 */
		getContent: function()
		{
			this.item.container = BX.create('div', {
				attrs: {
					className: this.isFile ? 'disk-folder-list-item' : 'disk-folder-list-item disk-folder-list-item-folder'
				},
				children: [
					this.getImage(),
					this.getActionBlock(),
					BX.create('div', {
						props: {
							className: (!this.getLocked() && !this.getSymlink()) ? 'disk-folder-list-item-bottom disk-folder-list-item-bottom-without-icons' : 'disk-folder-list-item-bottom'
						},
						children: [
							this.getTitle(),
							this.getIconsContainer()
						]
					})
				],
				events: {
					contextmenu: function(event) {
						if (event.ctrlKey)
						{
							return;
						}

						this.showActionsMenu(event);
						this.gridTile.resetSelection();
						this.gridTile.selectItem(this);
						event.preventDefault();
					}.bind(this)
				}
			});

			if(this.image)
			{
				this.imageItemHandler = BX.throttle(this.appendImageItem, 20, this);
				BX.bind(window, 'resize', this.imageItemHandler);
				BX.bind(window, 'scroll', this.imageItemHandler);
			}

			return this.item.container
		},

		appendImageItem: function()
		{
			if(this.isVisibleOnFolderList())
			{
				this.item.picture.setAttribute('src', this.image);
				BX.bind(this.item.container, 'animationend', BX.proxy(this.appendImageItem, this));
				BX.unbind(this.item.container, 'animationend', BX.proxy(this.appendImageItem, this));
				BX.unbind(window, 'resize', this.imageItemHandler);
				BX.unbind(window, 'scroll', this.imageItemHandler);
			}
		},

		lock: function()
		{
			this.item.lock.style.display = null;
		},

		unlock: function()
		{
			this.item.lock.style.display = 'none';
		},

		getIconsContainer: function()
		{
			this.item.icons = BX.create("div", {
				props: {
					className: "disk-folder-list-item-icons"
				},
				children: [
					this.getLocked(),
					this.getSymlink()
				]
			});

			return this.item.icons
		},

		getLocked: function()
		{
			this.item.lock = BX.create('div', {
				attrs: {
					className: 'disk-folder-list-item-locked'
				},
				style: {
					display: this.isLocked? null : 'none'
				}
			});

			return this.item.lock
		},

		getSymlink: function()
		{
			this.item.symlink = BX.create('div', {
				attrs: {
					className: 'disk-folder-list-item-shared'
				},
				style: {
					display: this.isSymlink? null : 'none'
				}
			});

			return this.item.symlink
		},

		/**
		 *
		 * @returns {Element}
		 */
		getTitle: function()
		{
			return this.item.title = BX.create('div', {
				props: {
					className: 'disk-folder-list-item-title'
				},
				children: [
					this.item.titleWrapper = BX.create("div", {
						props: {
							className: 'disk-folder-list-item-title-wrapper'
						},
						 children: [
						 	this.getTitleInput(),
							this.item.titleLink = BX.create('a', {
								attrs: {
						 			className: 'disk-folder-list-item-title-link',
						 			href: this.link,
						 			title: this.title,
									id: 'disk_obj_' + this.id
						 		},
						 		text: this.title,
						 		dataset: BX.mergeEx({
						 			objectId: this.id,
						 			canAdd: this.canAdd
						 		}, this.attributes)
						 	})
						 ]
					})
				]
			})
		},

		getTitleInput: function()
		{
			this.item.titleInput = BX.create('input', {
				attrs: {
					className: 'disk-folder-list-item-title-input',
					type: 'text',
					value: this.title
				}
			});

			BX.bind(this.item.titleInput, 'click', function(event) {
				event.stopPropagation();
			});

			BX.addCustomEvent(window, 'BX.TileGrid.Grid:resetSelectAllItems', this.cancelRenaming.bind(this));
			BX.addCustomEvent(window, 'BX.TileGrid.Grid:selectItem', this.cancelRenaming.bind(this));

			BX.bind(this.item.titleInput, 'keydown', function(event) {
				if(event.key === 'Escape')
				{
					this.cancelRenaming();

					event.preventDefault();
				}

				if(event.key === 'Enter')
				{
					this.cancelRenaming();
					this.runRename();

					event.preventDefault();
				}

				event.stopPropagation();
			}.bind(this));

			BX.bind(this.item.titleInput, 'blur', function(event){
				this.cancelRenaming();
				this.runRename();
			}.bind(this));

			return this.item.titleInput
		},

		onRename: function()
		{
			this.gridTile.resetSelection();
			jsDD.Disable();

			this.item.titleInput.value = this.title;
			BX.addClass(this.item.title, 'disk-folder-list-item-title-rename');
			this.item.titleInput.focus();
			if (this.isFile)
			{
				this.item.titleInput.setSelectionRange(0, this.title.lastIndexOf("."));
			}
			else
			{
				this.item.titleInput.select();
			}
		},

		cancelRenaming: function()
		{
			BX.removeClass(this.item.title, 'disk-folder-list-item-title-rename');
			this.item.titleInput.blur();

			jsDD.Enable();
		},

		rename: function(newName)
		{
			BX.addClass(this.item.titleLink, 'disk-folder-list-item-title-link-renamed');

			this.item.titleLink.addEventListener('animationend', function(){
				BX.removeClass(this.item.titleLink, 'disk-folder-list-item-title-link-renamed');
			}.bind(this));

			this.item.titleLink.textContent = newName;
			this.item.titleLink.setAttribute('title', newName);
			this.title = newName;
			this.rebuildLinkAfterRename(newName);

			jsDD.Enable();
		},

		rebuildLinkAfterRename: function(name)
		{
			if (this.isFile)
			{
				this.link = this.link.substring(0, this.link.lastIndexOf('/') + 1) + encodeURIComponent(name);
			}
			else
			{
				this.link = this.link.substring(0, this.link.lastIndexOf('/', this.link.length-2) + 1) + encodeURIComponent(name) + '/';
			}

			this.item.titleLink.href = this.link;
			this.actions.forEach(function(action){
				if (action.id === 'open' && action.href)
				{
					action.href = this.link;
				}
			}, this);

			this.destroyActionsMenu();
		},

		runRename: function()
		{
			if (this.item.titleInput.value === this.title)
			{
				return;
			}

			var oldTitle = this.title;
			this.rename(this.item.titleInput.value);

			BX.ajax.runAction('disk.api.commonActions.rename', {
				analyticsLabel: 'folder.list',
				data: {
					objectId: this.getId(),
					newName: this.title,
					autoCorrect: true
				}
			}).then(function (response) {
				if(response.data.object.name !== this.title)
				{
					this.rename(response.data.object.name);
				}
			}.bind(this)).catch(function (response) {
				BX.Disk.showModalWithStatusAction(response);
				this.rename(oldTitle);
			}.bind(this));
		},

		afterRender: function()
		{
			if(!this.item.picture)
				return;

			if(this.isVisibleOnFolderList())
			{
				this.appendImageItem();
			}

			BX.bind(this.item.container, 'animationend', BX.proxy(this.appendImageItem, this));

			this.item.picture.onload = function()
			{
				BX.show(this.item.picture);
				BX.hide(this.item.fileType);
			}.bind(this);
		},

		isVisibleOnFolderList: function()
		{
			var rect = this.layout.container.getBoundingClientRect();
			var rectBody = document.body.getBoundingClientRect();
			var itemHeight = this.layout.container.offsetHeight * 2;

			if (rect.top < 0 || rect.bottom < 0)
				return false;

			return rectBody.height > (rect.top - itemHeight) && rectBody.height >= (rect.bottom - itemHeight);
		},

		getImage: function()
		{
			var fileExtension = this.getFileExtension(this.title);

			this.item.imageBlock = BX.create('div', {
				attrs: {
					className: 'disk-folder-list-item-image'
				},
				children: [
					this.item.fileType = BX.create('div', {
						attrs: {
							className: 'ui-icon ui-icon-file ui-icon-file-' + fileExtension
						},
						style: {
							width: this.isFolder ? '85%' : '70%'
						},
						html: '<i></i>'
					}),
					this.item.picture = (this.image? BX.create('img', {
						attrs: {
							className: 'disk-folder-list-item-image-img'
							// src: this.image
						},
						style: {
							display: 'none'
						}
					}) : null)
				]
			});

			return this.item.imageBlock
		},

		markAsShared: function ()
		{
			this.isSymlink = true;

			if (this.isFolder)
			{
				this.item.fileType.classList.add('ui-icon-file-folder-shared');
			}
			else if (this.item.symlink)
			{
				this.item.symlink.style.display = null;
			}
		},

		unmarkAsShared: function ()
		{
			this.isSymlink = false;

			if (this.isFolder)
			{
				this.item.fileType.classList.remove('ui-icon-file-folder-shared');
			}
			else if (this.item.symlink)
			{
				this.item.symlink.style.display = 'none';
			}
		},

		/**
		 *
		 * @returns {string}
		 */
		getFileExtension: function(fileName)
		{
			var fileExtension = fileName.substring(fileName.lastIndexOf('.') + 1);

			switch(fileExtension)
			{
				case 'mp4':
				case 'mkv':
				case 'mpeg':
				case 'avi':
				case '3gp':
				case 'flv':
				case 'm4v':
				case 'ogg':
				case 'swf':
				case 'wmv':
					fileExtension = 'mov';
					break;

				case 'txt':
					fileExtension = 'txt';
					break;

				case 'doc':
				case 'docx':
					fileExtension = 'doc';
					break;

				case 'xls':
				case 'xlsx':
					fileExtension = 'xls';
					break;

				case 'php':
					fileExtension = 'php';
					break;

				case 'pdf':
					fileExtension = 'pdf';
					break;

				case 'ppt':
				case 'pptx':
					fileExtension = 'ppt';
					break;

				case 'rar':
					fileExtension = 'rar';
					break;

				case 'zip':
					fileExtension = 'zip';
					break;

				case 'set':
					fileExtension = 'set';
					break;

				case 'mov':
					fileExtension = 'mov';
					break;

				case 'img':
				case 'jpg':
				case 'jpeg':
				case 'gif':
					fileExtension = 'img';
					break;

				default:
					fileExtension = 'empty'
			}

			this.isFolder ? fileExtension = 'folder' : null;
			this.isSymlink && this.isFolder ? fileExtension = 'folder-shared' : null;

			return fileExtension;

		},

		getActionBlock: function()
		{
			if (!this.item.action)
			{
				this.item.action = BX.create('div', {
					attrs: {
						className: 'disk-folder-list-item-action'
					},
					events: {
						click: function(event) {
							this.showActionsMenu(event, BX.getEventTarget(event));
						}.bind(this)
					}
				});
			}

			return this.item.action;
		},

		getActions: function ()
		{
			return this.actions;
		},

		destroyActionsMenu: function ()
		{
			if (this.actionsMenu)
			{
				this.actionsMenu.destroy();
				this.actionsMenu = null;
			}
		},

		getActionsMenu: function(target)
		{
			if (this.actionsMenu)
			{
				return this.actionsMenu;
			}

			this.actionsMenu = BX.PopupMenu.create('-disk-folder-list-item-action-menu' + this.getId(), target, this.actions, {
				autoHide: true,
				offsetLeft: 20,
				angle: true
			});

			BX.bind(this.actionsMenu.popupWindow.popupContainer, 'click', function(event) {
				var actionsMenu = this.getActionsMenu();
				if (actionsMenu)
				{
					var target = BX.getEventTarget(event);
					var item = BX.findParent(target, {
						className: 'menu-popup-item'
					}, 10);

					if (!item || !item.dataset.preventCloseContextMenu)
					{
						actionsMenu.close();
					}
				}
			}.bind(this));

			return this.actionsMenu;
		},

		showActionsMenu: function(event, bindElement)
		{
 			BX.fireEvent(document.body, 'click');

			var actionsMenu = this.getActionsMenu(bindElement);
			actionsMenu.show();

			if(!bindElement)
			{
				actionsMenu.popupWindow.popupContainer.style.top = event.pageY + "px";
				actionsMenu.popupWindow.popupContainer.style.left = (event.pageX - 35) + "px";
			}
			else if (bindElement)
			{
				var pos = BX.pos(bindElement);
				pos.forceBindPosition = true;
				actionsMenu.popupWindow.setBindElement(bindElement);
				actionsMenu.popupWindow.adjustPosition(pos);
			}
		}
	}
})();
