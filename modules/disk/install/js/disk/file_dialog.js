(function() {
var BX = window.BX;
if(BX.DiskFileDialog)
	return;

BX.DiskFileDialog =
{
	popupWindow: null,
	popupWaitWindow: null,
	timeout: null,
	sendRequest: false,

	obCallback: {},
	obLocalize: {},

	obType: {},
	obTypeItems: {},

	sortMode: 'ord',
	searchContext: {},

	obItems: {},
	obItemsDisabled: {},
	obItemsSelected: {},
	obItemsSelectEnabled: {},
	obItemsSelectMulti: {},

	obFolderByPath: {},
	obCurrentPath: {},
	obCurrentTab: {},

	obGridColumn: {},
	obGridOrder: {},

	obElementBindPopup: {},
	obButtonSaveDisabled: {},

	obInitItems: {},
	obInitItemsDisabled: {},
	obInitItemsSelected: {}
}

BX.DiskFileDialog.init = function(arParams)
{
	if(!arParams.name)
		arParams.name = 'fd';

	BX.DiskFileDialog.searchContext = {};

	BX.DiskFileDialog.obCallback[arParams.name] = arParams.callback;

	BX.DiskFileDialog.obType[arParams.name] = arParams.type;
	BX.DiskFileDialog.obTypeItems[arParams.name] = arParams.typeItems;
	BX.DiskFileDialog.obLocalize[arParams.name] = arParams.localize;

	BX.DiskFileDialog.obCurrentPath[arParams.name] =  arParams.currentPath? arParams.currentPath : '/';
	BX.DiskFileDialog.obCurrentTab[arParams.name] = arParams.currentTabId? BX.DiskFileDialog.obTypeItems[arParams.name][arParams.currentTabId] : null;
	BX.DiskFileDialog.obFolderByPath[arParams.name] = arParams.folderByPath? arParams.folderByPath : {};
	if (BX.DiskFileDialog.obCurrentTab[arParams.name] !== null)
		BX.DiskFileDialog.obFolderByPath[arParams.name]['/'] = {'id' : 'root', 'type': 'folder', 'extra': '', 'name': BX.DiskFileDialog.obTypeItems[arParams.name][arParams.currentTabId].name, 'path': '/', 'size': '', 'sizeInt': '0', 'modifyBy': '', 'modifyDate': '', 'modifyDateInt': '0'};

	BX.DiskFileDialog.obItems[arParams.name] = arParams.items;

	BX.DiskFileDialog.obItemsDisabled[arParams.name] = arParams.itemsDisabled;
	BX.DiskFileDialog.obItemsSelected[arParams.name] = arParams.itemsSelected;
	BX.DiskFileDialog.obItemsSelectEnabled[arParams.name] = arParams.itemsSelectEnabled;
	BX.DiskFileDialog.obItemsSelectMulti[arParams.name] = arParams.itemsSelectMulti;

	BX.DiskFileDialog.obElementBindPopup[arParams.name] = arParams.bindPopup;

	BX.DiskFileDialog.obGridColumn[arParams.name] = arParams.gridColumn;
	BX.DiskFileDialog.obGridOrder[arParams.name] = arParams.gridOrder;

	var arTypeItemsSort = BX.util.objectSort(BX.DiskFileDialog.obTypeItems[arParams.name], 'name', 'asc');
	for (var i = 0, c = arTypeItemsSort.length; i < c; i++)
	{
		var item = arTypeItemsSort[i];

		if (BX.DiskFileDialog.obType[arParams.name][item.type])
		{
			if (BX.DiskFileDialog.obType[arParams.name][item.type].items)
				BX.DiskFileDialog.obType[arParams.name][item.type].items.push(item.id);
			else
				BX.DiskFileDialog.obType[arParams.name][item.type].items = [item.id];
		}
	}

	if (BX.DiskFileDialog.obCurrentTab[arParams.name] == null)
	{
		var arTypeSort = BX.util.objectSort(BX.DiskFileDialog.obType[arParams.name], 'order', 'asc');
		for (var i = 0, c = arTypeItemsSort.length; i < c; i++)
		{
			var item = arTypeItemsSort[i];
			if (item.type == arTypeSort[0].id)
			{
				BX.DiskFileDialog.obCurrentPath[arParams.name] = '/';
				BX.DiskFileDialog.obCurrentTab[arParams.name] = BX.DiskFileDialog.obTypeItems[arParams.name][item.id];
				BX.DiskFileDialog.obFolderByPath[arParams.name]['/'] = {'id' : 'root', 'type': 'folder', 'extra': '', 'name': BX.DiskFileDialog.obTypeItems[arParams.name][item.id].name, 'path': '/', 'size': '', 'sizeInt': '0', 'modifyBy': '', 'modifyDate': '', 'modifyDateInt': '0'};
				break;
			}
		}
	}

	for (var i in BX.DiskFileDialog.obItemsSelected[arParams.name])
	{
		if (BX.DiskFileDialog.obItems[arParams.name][i])
			BX.DiskFileDialog.obItemsSelected[arParams.name][i] = BX.DiskFileDialog.obItems[arParams.name][i].type;
		else
			delete BX.DiskFileDialog.obItemsSelected[arParams.name][i];
	}

	BX.DiskFileDialog.obInitItems[arParams.name] = BX.clone(BX.DiskFileDialog.obItems[arParams.name]);
	BX.DiskFileDialog.obInitItemsSelected[arParams.name] = BX.clone(BX.DiskFileDialog.obItemsSelected[arParams.name]);
	BX.DiskFileDialog.obInitItemsDisabled[arParams.name] = BX.clone(BX.DiskFileDialog.obItemsDisabled[arParams.name]);

	BX.DiskFileDialog.obButtonSaveDisabled[arParams.name] = false;

	BX.onCustomEvent(BX.DiskFileDialog, 'inited', [arParams.name]);

	var firstLoadItems = true;
	for (var i in arParams.items)
	{
		firstLoadItems = false;
		break;
	}
	if (firstLoadItems)
		BX.DiskFileDialog.loadItems(BX.DiskFileDialog.obCurrentTab[arParams.name], arParams.name);
}

BX.DiskFileDialog.openDialog = function(name)
{
	if(!name)
		name = 'fd';

	if (BX.DiskFileDialog.popupWindow != null)
	{
		BX.DiskFileDialog.popupWindow.close();
		return false;
	}

	BX.DiskFileDialog.popupWindow = new BX.PopupWindow('DiskFileDialog', BX.DiskFileDialog.obElementBindPopup[name].node, {
		/*autoHide: true,*/
		offsetLeft: parseInt(BX.DiskFileDialog.obElementBindPopup[name].offsetLeft),
		offsetTop: parseInt(BX.DiskFileDialog.obElementBindPopup[name].offsetTop),
		bindOptions: {forceBindPosition: true},
		zIndex: 100,
		closeByEsc: true,
		closeIcon : true,
		draggable: BX.DiskFileDialog.obElementBindPopup[name].node == null? {restrict: true}: false,
		titleBar: BX.DiskFileDialog.obLocalize[name].title,
		contentColor : 'white',
		contentNoPaddings : true,
		events : {
			onPopupClose : function() {
				if (BX.DiskFileDialog.popupWaitWindow !== null)
					BX.DiskFileDialog.popupWaitWindow.close();

				var searchForm = BX('bx-file-list-dialog-search-form');
				if(searchForm)
				{
					BX.unbind(searchForm, 'submit', BX.proxy(BX.DiskFileDialog.onSubmitSearchForm, true));
					BX.unbind(BX('bx-file-title-button-search-input'), "keyup", BX.proxy(BX.DiskFileDialog.onFileSearch, this));
					BX('bx-file-title-button-search-input').setAttribute('data-prev-search', '');
				}

				this.destroy();
			},
			onPopupDestroy : function() {
				BX.DiskFileDialog.popupWindow = null;
				if(BX.DiskFileDialog.obCallback[name] && BX.DiskFileDialog.obCallback[name].popupDestroy)
				{
					BX.DiskFileDialog.obCallback[name].popupDestroy();
				}
			},
			onPopupShow : BX.delegate(function() {
				if(BX.DiskFileDialog.obCallback[name] && BX.DiskFileDialog.obCallback[name].popupShow)
				{
					BX.DiskFileDialog.obCallback[name].popupShow();
				}
				var searchForm = BX('bx-file-list-dialog-search-form');
				if(searchForm)
				{
					BX.bind(searchForm, 'submit', BX.proxy(BX.DiskFileDialog.onSubmitSearchForm, this));
					BX.bind(BX('bx-file-title-button-search-input'), "keyup", BX.proxy(BX.DiskFileDialog.onFileSearch, this));
				}

			}, this)
		},
		content:	'<div class="bx-file-dialog-container">'+
						'<div class="bx-file-dialog-tab">'+
							'<div class="bx-file-dialog-tab-wrap" id="bx-file-dialog-tab-'+name+'">'
								+BX.DiskFileDialog.getTabsHtml(name)+
							'</div>'+
						'</div>'+
						'<div id="bx-file-list-dialog-search-cont" class="bx-file-list-dialog-search" style="">' +
							'<form data-name="' + name + '" id="bx-file-list-dialog-search-form" onsubmit="" action="" method="GET" name="bx-file-filter-title-form" style="display: block;">' +
								'<input data-name="' + name + '" class="bx-file-list-dialog-search-input" id="bx-file-title-button-search-input" name="title_value" autocomplete="off" type="text" style="width: 610px;margin-bottom: 6px;margin-right: 8px;">' +
								'<span onclick="BX.fireEvent(BX(\'bx-file-list-dialog-search-form\'), \'submit\')" class="bx-file-list-dialog-search-icon" id="bx-file-title-button-search-icon"></span>' +
							'</form>' +
						'</div>' +
						'<div class="bx-file-dialog-content" style="margin-left: 199px;height: 254px;" id="bx-file-dialog-content-'+name+'">'
							+BX.DiskFileDialog.getItemsHtml(name)+
						'</div>'+
					'</div>'+
					'<div class="bx-file-dialog-notice" id="bx-file-dialog-notice-'+name+'">'+
						'<div class="popup-window-hr popup-window-buttons-hr"><i></i></div>'+
						'<div class="bx-file-dialog-notice-wrap"></div>'+
					'</div>',
		buttons: [
			new BX.PopupWindowButton({
				text : BX.DiskFileDialog.obLocalize[name].saveButton,
				className : "popup-window-button-disabled",
				events : { click : function()
				{
					if (BX.DiskFileDialog.obButtonSaveDisabled[name])
						return false;

					BX.DiskFileDialog.obInitItems[name] = BX.clone(BX.DiskFileDialog.obItems[name]);
					BX.DiskFileDialog.obInitItemsSelected[name] = BX.clone(BX.DiskFileDialog.obItemsSelected[name]);
					BX.DiskFileDialog.obInitItemsDisabled[name] = BX.clone(BX.DiskFileDialog.obItemsDisabled[name]);

					if(BX.DiskFileDialog.obCallback[name] && BX.DiskFileDialog.obCallback[name].saveButton)
					{
						var selected = {};
						for(var i in BX.DiskFileDialog.obItemsSelected[name])
							selected[i] = BX.DiskFileDialog.obItems[name][i];

						BX.DiskFileDialog.obCallback[name].saveButton(BX.DiskFileDialog.obCurrentTab[name], BX.DiskFileDialog.obCurrentPath[name], selected, BX.DiskFileDialog.obFolderByPath[name][BX.DiskFileDialog.obCurrentPath[name]]);
					}

					this.popupWindow.close();
				}}
			}),

			new BX.PopupWindowButtonLink({
				text: BX.DiskFileDialog.obLocalize[name].cancelButton,
				className: "popup-window-button-link-cancel",
				events: { click : function()
				{

					BX.DiskFileDialog.obItems[name] = BX.clone(BX.DiskFileDialog.obInitItems[name]);
					BX.DiskFileDialog.obItemsSelected[name] = BX.clone(BX.DiskFileDialog.obInitItemsSelected[name]);
					BX.DiskFileDialog.obItemsDisabled[name] = BX.clone(BX.DiskFileDialog.obInitItemsDisabled[name]);

					if(BX.DiskFileDialog.obCallback[name] && BX.DiskFileDialog.obCallback[name].cancelButton)
					{
						var selected = {};
						for(var i in BX.DiskFileDialog.obItemsSelected[name])
							selected[i] = BX.DiskFileDialog.obItems[name][i];

						BX.DiskFileDialog.obCallback[name].cancelButton(BX.DiskFileDialog.obCurrentTab[name], BX.DiskFileDialog.obCurrentPath[name], selected, BX.DiskFileDialog.obFolderByPath[name][BX.DiskFileDialog.obCurrentPath[name]]);
					}

					this.popupWindow.close();
				}}
			})
		]
	});
	BX.DiskFileDialog.popupWindow.show();
	BX.DiskFileDialog.slidePath(name);
};

var fix;
BX.DiskFileDialog.onFileSearch = function(event)
{
	if (event.keyCode == 16 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 91)
		return false;

	var searchInput = event.target;
	var name = event.target.getAttribute('data-name');
	if(event.keyCode == 27 && searchInput.getAttribute('data-prev-search') != '')
	{
		searchInput.value = '';
		searchInput.setAttribute('data-prev-search', '');
		BX.DiskFileDialog.loadItems(BX.DiskFileDialog.obCurrentTab[name], name);
		return BX.PreventDefault(event);
	}
	if(searchInput.value == '' && searchInput.getAttribute('data-prev-search') != '')
	{
		searchInput.setAttribute('data-prev-search', '');
		BX.DiskFileDialog.loadItems(BX.DiskFileDialog.obCurrentTab[name], name);
		return BX.PreventDefault(event);
	}

	if(!BX.type.isFunction(fix))
	{
		fix = BX.debounce(function(searchInput){

			if(searchInput.getAttribute('data-prev-search') === searchInput.value)
				return BX.PreventDefault(event);

			if(BX.DiskFileDialog.searchFileByName(searchInput.value, name))
			{
				searchInput.setAttribute('data-prev-search', searchInput.value);
			}

			return BX.PreventDefault(event);

		}, 500, this);
	}
	fix(searchInput);
};

BX.DiskFileDialog.onSubmitSearchForm = function(e)
{
	return BX.PreventDefault(e);
};

BX.DiskFileDialog.searchFileByName = function(searchQuery, name)
{
	var entityType = null;
	var entityId = null;

	if(searchQuery.length < 3)
		return false;

	if(BX.DiskFileDialog.obCurrentTab[name].type === "recently_used")
		entityType = BX.DiskFileDialog.obCurrentTab[name].type;
	else if(!BX.DiskFileDialog.obCurrentPath[name] || BX.DiskFileDialog.obCurrentPath[name] === '/')
	{
		entityType = 'storage';
		entityId = BX.DiskFileDialog.obCurrentPath[name];
	}

	BX.Disk.ajax({
		url: BX.Disk.addToLinkParam(BX.DiskFileDialog.target[name], 'action', 'searchFile'),
		method: 'POST',
		dataType: 'json',
		data: {
			FORM_NAME: name,
			storageId: BX.DiskFileDialog.obCurrentTab[name].id,
			storageType: BX.DiskFileDialog.obCurrentTab[name].type,
			entityType: entityType,
			entityId: entityId || 0,
			searchQuery: searchQuery,
			searchContext: BX.DiskFileDialog.searchContext
		},
		onsuccess: BX.delegate(function (data)
		{
			BX.DiskFileDialog.obItems[name] = {};
			BX.DiskFileDialog.obItemsSelected[name] = {};
			BX.DiskFileDialog.obItemsDisabled[name] = {};

			for (var i in data.items)
			{
				if(!data.items.hasOwnProperty(i))
					continue;
				BX.DiskFileDialog.obItems[name][i] = data.items[i];
			}
			var container = BX('bx-file-dialog-content-'+name);
			if (container)
				container.innerHTML = BX.DiskFileDialog.getItemsHtml(name, data.sortMode);
		}, this)
	});

	return true;
};

BX.DiskFileDialog.selectTab = function(element, tabId, name)
{
	if(!name)
		name = 'fd';

	if (
		BX.DiskFileDialog.obCurrentTab[name] &&
		BX.DiskFileDialog.obCurrentTab[name].id == tabId &&
		BX.DiskFileDialog.obCurrentPath[name] == '/'
	)
	{
		return false;
	}

	BX.DiskFileDialog.obCurrentPath[name] = '/';
	BX.DiskFileDialog.obCurrentTab[name] = BX.DiskFileDialog.obTypeItems[name][tabId];

	BX.DiskFileDialog.obFolderByPath[name] = {};
	BX.DiskFileDialog.obFolderByPath[name][BX.DiskFileDialog.obCurrentPath[name]] = {'id' : 'root', 'type': 'folder', 'extra': '', 'name': BX.DiskFileDialog.obTypeItems[name][tabId].name, 'path': '/', 'size': '', 'sizeInt': '0', 'modifyBy': '', 'modifyDate': '', 'modifyDateInt': '0'};

	if (element !== null)
	{
		var elements = BX.findChildren(BX('bx-file-dialog-tab-'+name), {className : "bx-file-dialog-tab-item-active"}, true);
		if (elements != null)
		{
			for (var j = 0; j < elements.length; j++)
				elements[j].className = 'bx-file-dialog-tab-item-link';
		}
		element.className = 'bx-file-dialog-tab-item-link bx-file-dialog-tab-item-active';
	}

	var searchInput = BX('bx-file-title-button-search-input');
	if(searchInput.value != '')
	{
		BX.DiskFileDialog.searchFileByName(searchInput.value, name)
	}
	else
	{
		BX.DiskFileDialog.loadItems(BX.DiskFileDialog.obCurrentTab[name], name);
	}

	return false;
}

BX.DiskFileDialog.loadItems = function(oTarget, name)
{
	if (BX.DiskFileDialog.sendRequest)
		return false;

	if(!name)
		name = 'fd';

	if (!BX.DiskFileDialog.target)
		BX.DiskFileDialog.target = {};
	if (!!oTarget.link)
		BX.DiskFileDialog.target[name] = oTarget.link;

	BX.onCustomEvent(BX.DiskFileDialog, 'loadItems', [oTarget.link, name]);

	BX.Disk.ajax({
		url: BX.DiskFileDialog.target[name],
		method: 'POST',
		dataType: 'json',
		data: {'WD_LOAD_ITEMS' : 'Y',
			'FORM_NAME' : name,
			'FORM_TAB_ID' : BX.DiskFileDialog.obCurrentTab[name].id,
			'FORM_TAB_TYPE' : BX.DiskFileDialog.obCurrentTab[name].type,
			'FORM_PATH' : BX.DiskFileDialog.obCurrentPath[name].split('//').join('/'),

			searchContext: BX.DiskFileDialog.searchContext
		},

		onsuccess: BX.delegate(function(data)
		{
			var search = BX('bx-file-list-dialog-search-cont');
			if(data.authUrl)
			{
				BX.DiskFileDialog.closeWait(name);
				BX.DiskFileDialog.sendRequest = false;
				var containerData = BX('bx-file-dialog-content-'+name);
				if (!containerData)
					return;
				if(search)
					BX.hide(search);

				containerData.innerHTML = '<div class="bx-file-dialog-content" style="margin-left: 0;">'+
						'<div class="bx-file-dialog-content-wrap-inner">' +
							'<div class="bx-file-dialog-content-wrap-title">' +
								BX.message('DISK_JS_FILE_DIALOG_OAUTH_NOTICE').replace('#SERVICE#', oTarget.name) +
							'</div>' +
							'<div class="bx-file-dialog-content-wrap-text">' +
								BX.message('DISK_JS_FILE_DIALOG_OAUTH_NOTICE_DETAIL').replace('#HELP_URL#', BX.message('DISK_JS_FILE_DIALOG_OAUTH_NOTICE_DETAIL_HELP_URL')) +
							'</div>' +
						'</div>' +
					'</div>';


				BX.bind(BX('bx-js-disk-run-oauth-modal'), 'click', function(e){
					BX.util.popup(data.authUrl, 1030, 700);
					BX.PreventDefault(e);
					return false;
				});
				BX.bind(window, 'hashchange', BX.proxy(function ()
				{
					var matches = document.location.hash.match(/external-auth-(\w+)/);
					if (!matches)
						return;
					BX.DiskFileDialog.sendRequest = false;
					this.loadItems(oTarget, name);
				}, this));
				return;
			}
			if(data.status && data.status === 'error')
			{
				data.errors = data.errors || [{}];
				BX.Disk.showModalWithStatusAction({
					status: 'error',
					message: data.errors.pop().message
				});
				BX.DiskFileDialog.closeWait(name);
				BX.DiskFileDialog.sendRequest = false;
				BX.cleanNode(BX('bx-file-dialog-content-'+name));
				return;
			}

			if(BX.DiskFileDialog.obType[name][BX.DiskFileDialog.obCurrentTab[name].type].searchable)
			{
				if(search)
				{
					BX.show(search, 'block');
				}

				var containerDialog = BX('bx-file-dialog-content-'+name);
				if (containerDialog)
				{
					containerDialog.style.height = '254px';
					var wrapContent = BX.findChild(containerDialog, {tagName: 'div', className: 'bx-file-dialog-content-wrap'}, true);
					if(wrapContent)
					{
						wrapContent.style.cssText = '';
					}
				}
			}
			else
			{
				if(search)
				{
					BX.hide(search);
				}

				var containerDialog = BX('bx-file-dialog-content-'+name);
				if (containerDialog)
				{
					containerDialog.style.height = '288px';
					var wrapContent = BX.findChild(containerDialog, {tagName: 'div', className: 'bx-file-dialog-content-wrap'}, true);
					if(wrapContent)
					{
						wrapContent.style.height = '257px';
					}
				}
			}


			BX.DiskFileDialog.obItems[data.FORM_NAME] = {};
			BX.DiskFileDialog.obItemsSelected[data.FORM_NAME] = {};
			BX.DiskFileDialog.obItemsDisabled[data.FORM_NAME] = {};

			for (var i in data.FORM_ITEMS)
				BX.DiskFileDialog.obItems[data.FORM_NAME][i] = data.FORM_ITEMS[i];

			for (var i in data.FORM_ITEMS_DISABLED)
				BX.DiskFileDialog.obItemsDisabled[data.FORM_NAME][i] = data.FORM_ITEMS_DISABLED[i];

//			BX.DiskFileDialog.obCurrentTab[data.FORM_NAME].iblock_id = data.FORM_IBLOCK_ID;

			//BX.DiskFileDialog.obCurrentPath[data.FORM_NAME] = data.FORM_PATH;

			var container = BX('bx-file-dialog-content-'+name);
			if (container)
				container.innerHTML = BX.DiskFileDialog.getItemsHtml(name, data.sortMode);
			BX.DiskFileDialog.slidePath(name);

			BX.DiskFileDialog.closeWait(name);
			BX.DiskFileDialog.sendRequest = false;

			BX.onCustomEvent(BX.DiskFileDialog, 'loadItemsDone', [name]);
		}, this),
		onfailure: function(data) { BX.DiskFileDialog.sendRequest = false; }
	});
	BX.DiskFileDialog.showWait(500, name);
	BX.DiskFileDialog.sendRequest = true;

	return false;
}

BX.DiskFileDialog.selectColumn = function(column, order, name)
{
	if(!column)
		column = 'name';

	BX.DiskFileDialog.obGridOrder[name].column = !column? 'name': column;
	BX.DiskFileDialog.obGridOrder[name].order = !order? 'desc': order;

	var container = BX('bx-file-dialog-content-'+name);
	if (container)
		container.innerHTML = BX.DiskFileDialog.getItemsHtml(name);
	BX.DiskFileDialog.slidePath(name);

	return false;
}

BX.DiskFileDialog.selectItem = function(element, itemId, name)
{
	if(!name)
		name = 'fd';

	if (BX.DiskFileDialog.obItemsSelected[name][itemId])
	{
		BX.DiskFileDialog.unSelectItem(element, itemId, name);
	}
	else
	{
		if (!BX.DiskFileDialog.obItemsSelectMulti[name])
		{
			var elements = BX.findChildren(BX('bx-file-dialog-content-'+name), {className : "bx-file-dialog-row-selected"}, true);
			if (elements != null)
			{
				for (var i = 0, c = elements.length; i < c; i++)
					BX.DiskFileDialog.unSelectItem(elements[i], elements[i].getAttribute('data-id'), name);
			}
			else
			{
				for (var i in BX.DiskFileDialog.obItemsSelected[name])
					delete BX.DiskFileDialog.obItemsSelected[name][i];
			}
		}

		if (element !== null)
		{
			BX.removeClass(element, 'bx-file-dialog-row-normal');
			BX.addClass(element, 'bx-file-dialog-row-selected');
			BX.onCustomEvent(BX.DiskFileDialog, 'selectItem', [element, itemId, name]);
		}

		BX.DiskFileDialog.obItemsSelected[name][itemId] = BX.DiskFileDialog.obItems[name][itemId].type;
	}
	if(BX.Disk.isEmptyObject(BX.DiskFileDialog.obItemsSelected[name]))
	{
		BX.DiskFileDialog.disableSave(name);
	}
	else
	{
		BX.DiskFileDialog.enableSave(name);
	}


	return false;
}

BX.DiskFileDialog.unSelectItem = function(element, itemId, name)
{
	if(!name)
		name = 'fd';

	BX.removeClass(element, 'bx-file-dialog-row-selected');
	BX.addClass(element, 'bx-file-dialog-row-normal');

	delete BX.DiskFileDialog.obItemsSelected[name][itemId];

	if(BX.Disk.isEmptyObject(BX.DiskFileDialog.obItemsSelected[name]))
	{
		BX.DiskFileDialog.disableSave(name);
	}

	BX.onCustomEvent(BX.DiskFileDialog, 'unSelectItem', [element, itemId, name]);

	return false;
}

BX.DiskFileDialog._rtrimFolder = function(path)
{
	if (path.length > 1) {
		var arPath = path.split('/');
		if (arPath[arPath.length - 1] == '')
			arPath.pop();
		arPath.pop();
		path = arPath.join('/');
		if (path.length < 1)
			path = '/';
	} else {
		path = false;
	}
	return path;
}

BX.DiskFileDialog.openSpecifiedFolder = function(url, name)
{
	if(!name)
		name = 'fd';

	BX.DiskFileDialog.obCurrentPath[name] = url;

	//url = BX.DiskFileDialog.obCurrentTab[name].link + url;

	if(url !== '/')
	{
		var normalizedUrl = url;
		if(normalizedUrl.slice(-1) === '/')
		{
			normalizedUrl = normalizedUrl.substring(0, normalizedUrl.length-1);
		}
		if(BX.DiskFileDialog.obFolderByPath[name][normalizedUrl] && BX.DiskFileDialog.obFolderByPath[name][normalizedUrl].provider)
		{
			BX.DiskFileDialog.loadItems({link: BX.DiskFileDialog.obFolderByPath[name][normalizedUrl].link}, name);
			return false;
		}
	}


	var link = BX.DiskFileDialog.obCurrentTab[name].link;
	var indexPos =link.indexOf('index.php');
	if( indexPos >= 0)
	{
		if(url == '/')
		{
			url = '';
		}
		else
		{
			link = link.substring(0, indexPos);
		}
	}
	url = link + url;
	url = url.split('//').join('/');

	BX.DiskFileDialog.loadItems({'link':link}, name);

	return false;
};

BX.DiskFileDialog.openParentFolder = function(name)
{
	if(!name)
		name = 'fd';

	if (!!BX.DiskFileDialog.obCurrentTab[name]) {
		var url = BX.DiskFileDialog._rtrimFolder(BX.DiskFileDialog.obCurrentPath[name]);

		BX.DiskFileDialog.openSpecifiedFolder(url, name);
		return false;
	}
	return false;
};

BX.DiskFileDialog.openFolder = function(element, itemId, name)
{
	if(!name)
		name = 'fd';

	if(!BX.DiskFileDialog.obItems[name][itemId].path)
	{
		BX.DiskFileDialog.obCurrentPath[name] =
			(BX.DiskFileDialog.obCurrentPath[name] === '/'? '/' : BX.DiskFileDialog.obCurrentPath[name] + '/')
				+ BX.DiskFileDialog.obItems[name][itemId].name;
	}
	else
	{
		BX.DiskFileDialog.obCurrentPath[name] = BX.DiskFileDialog.obItems[name][itemId].path;
	}

	//BX.DiskFileDialog.obCurrentPath[name] = BX.DiskFileDialog.obItems[name][itemId].path;
	BX.DiskFileDialog.obFolderByPath[name][BX.DiskFileDialog.obCurrentPath[name]] = BX.DiskFileDialog.obItems[name][itemId];

	BX.removeClass(element, 'bx-file-dialog-row-selected');
	BX.addClass(element, 'bx-file-dialog-row-normal');

	BX.DiskFileDialog.loadItems(BX.DiskFileDialog.obItems[name][itemId], name);

	return false;
}

BX.DiskFileDialog.getTabsHtml = function(name)
{
	if(!name)
		name = 'fd';

	var html = '';
	var arTypeSort = BX.util.objectSort(BX.DiskFileDialog.obType[name], 'order', 'asc');
	for (var i = 0, c = arTypeSort.length; i < c; i++)
	{
		var groupHtml = '';
		var type = arTypeSort[i];
		groupHtml = '<div class="bx-file-dialog-tab-group">';
		if (type.name)
			groupHtml += '<div class="bx-file-dialog-tab-group-title">'+type.name+'</div>';
		if (type.items)
		{
			for (var j = 0; j < type.items.length; j++)
			{
				var item = BX.DiskFileDialog.obTypeItems[name][type.items[j]];
				var active = BX.DiskFileDialog.obCurrentTab[name].id == item.id? ' bx-file-dialog-tab-item-active': '';
				groupHtml += '<div class="bx-file-dialog-tab-item">'+
							'<a href="#" class="bx-file-dialog-tab-item-link'+active+'" data-id="'+item.id+'" data-type="'+item.type+'" onclick="return BX.DiskFileDialog.selectTab(this, \''+item.id+'\', \''+name+'\')">'+
								'<span class="bx-file-dialog-tab-item-link-text" title="'+BX.util.htmlspecialchars(item.name)+'">'+BX.util.htmlspecialchars(item.name)+'</span>'+
								'<span class="bx-file-dialog-tab-item-link-arrow"></span>'+
							'</a>'+
						'</div>';
			}
		}
		groupHtml += '</div>';

		if(type.items)
		{
			html += groupHtml;
		}
	}

	return html;
}

BX.DiskFileDialog.getItemsHtml = function(name, sortMode)
{
	if(!name)
		name = 'fd';
	if(!sortMode)
		sortMode = BX.DiskFileDialog.sortMode;

	var html = '';
	if (BX.DiskFileDialog.obCurrentPath[name] == '/')
		html = '<div class="bx-file-dialog-content-root">';
		// header
		html += '<div class="bx-file-dialog-header">';
		for (var i in BX.DiskFileDialog.obGridColumn[name])
		{

			var column = BX.DiskFileDialog.obGridColumn[name][i];
			var sortByColumn = BX.DiskFileDialog.obGridOrder[name].column == column.sort? true: false;
			html += '<span class="bx-file-dialog-column-header bx-file-dialog-column-'+column.id+'" style="'+column.style+'">'+
						'<a href="#sort" class="bx-file-dialog-sort-link'+(sortByColumn? ' bx-file-dialog-sort-active': '')+'"  onclick="return BX.DiskFileDialog.selectColumn(\''+column.sort+'\', \''+(sortByColumn? (BX.DiskFileDialog.obGridOrder[name].order == 'desc'? 'asc':'desc'): 'desc')+'\', \''+name+'\')">'
							+BX.util.htmlspecialchars(column.name)+'<span class="bx-file-dialog-sort-icon bx-file-dialog-sort-'+(sortByColumn? BX.DiskFileDialog.obGridOrder[name].order: 'desc')+'"></span>'+
						'</a>'+
					'</span>';
		}
		html += '</div>';


		// path
		var arPath = BX.DiskFileDialog.obCurrentPath[name].split('/');
		html +=	'<div class="bx-file-dialog-content-path">'+
					'<a href="#up" class="bx-file-dialog-content-path-up bx-file-dialog-icon bx-file-dialog-icon-up" onclick="return BX.DiskFileDialog.openParentFolder(\''+name+'\');"></a>'+
					'<span class="bx-file-dialog-content-path-items"><span class="bx-file-dialog-content-path-items-wrap" id="bx-file-dialog-content-path-items-wrap-'+name+'"> '+
					'<a href="#library" onclick="return BX.DiskFileDialog.openSpecifiedFolder(\'/\', \''+name+'\')" class="bx-file-dialog-content-path-link">'+BX.util.htmlspecialchars(BX.DiskFileDialog.obCurrentTab[name].name)+'</a>';
					var path = '/';
					if (arPath[arPath.length-1] == '')
						arPath.pop();
					for (var i = 0, c = arPath.length; i < c; i++)
					{
						if (i == 0 || i == c)
							continue;
						path += arPath[i]+'/';
						html += '<span class="bx-file-dialog-content-path-seporator bx-file-dialog-icon bx-file-dialog-icon-seporator"></span>';
						html +=	'<a href="#'+arPath[i]+'" onclick="return BX.DiskFileDialog.openSpecifiedFolder(\''+path+'\', \''+name+'\')" class="bx-file-dialog-content-path-link '+(i == c-1? 'bx-file-dialog-content-path-link-active':'')+'">'+arPath[i]+'</a>';
					};
					html += '</span></span>';
		html +=	'</div>';
		// items
		html +=	'<div class="bx-file-dialog-content-wrap">';

		var htmlFolder = ""; var htmlFile = "";	var htmlElement = "";
		var arTypeSort = BX.util.objectSort(BX.DiskFileDialog.obItems[name], BX.DiskFileDialog.obGridOrder[name].column, BX.DiskFileDialog.obGridOrder[name].order);
		for (var i = 0, c = arTypeSort.length; i < c; i++)
		{
			var item = arTypeSort[i];
			var extraClass = BX.DiskFileDialog.obItemsSelected[name][item.id]? ' bx-file-dialog-row-selected': ' bx-file-dialog-row-normal';

			var selectDisable = false;
			var elementDisable = false;
			if (BX.DiskFileDialog.obItemsDisabled[name][item.id])
				elementDisable = true;

			if (elementDisable && item.type == 'folder')
				selectDisable = true;

			if (!elementDisable)
			{
				elementDisable = true;
				var enableTypeCount = 0;
				for (var enableType in BX.DiskFileDialog.obItemsSelectEnabled[name])
				{
					enableTypeCount++;
					if (enableType == 'all')
					{
						elementDisable = false;
						break;
					}
					else if (enableType == 'onlyFiles')
					{
						elementDisable = false;
						if (item.type == 'folder')
							selectDisable = true;
						break;
					}
					else if (enableType == item.type)
					{
						elementDisable = false;
					}
				}
				if (enableTypeCount <= 0)
					elementDisable = false;
			}
			var hintNotice = '';
			if (elementDisable)
			{
				extraClass = ' bx-file-dialog-row-disabled';
				if(BX.DiskFileDialog.obItemsDisabled[name] && BX.DiskFileDialog.obItemsDisabled[name][item.id] && BX.DiskFileDialog.obItemsDisabled[name][item.id]['hint'])
				{
					hintNotice = '. ' + BX.DiskFileDialog.obItemsDisabled[name][item.id]['hint'];
				}
			}

			var clickfunc = '';
			var dblclickfunc = '';
			if (!elementDisable)
			{
				var clickfunc = 'onclick="return BX.DiskFileDialog.selectItem(this, \''+item.id+'\', \''+name+'\')"';
				var dblclickfunc = '';
				if (item.type == 'folder')
				{
					clickfunc = selectDisable? 'onclick="return BX.DiskFileDialog.openFolder(this, \''+item.id+'\', \''+name+'\')"': clickfunc;
					dblclickfunc = selectDisable? '': 'ondblclick="return BX.DiskFileDialog.openFolder(this, \''+item.id+'\', \''+name+'\')"';
				}
			}

			htmlElement = '<div '+ clickfunc+' '+dblclickfunc +' class="bx-file-dialog-row '+extraClass+'" data-id="'+item.id+'">'; // bx-file-dialog-row-selected bx-file-dialog-row-disabled
			var firstColumn = true;
			for (var j in BX.DiskFileDialog.obGridColumn[name])
			{
				var column = BX.DiskFileDialog.obGridColumn[name][j];
				var extra = item.extra && item.extra != ''? ' bx-file-dialog-extra-'+item.extra: '';

				if (firstColumn)
				{
					htmlElement += '<span class="bx-file-dialog-column-row bx-file-dialog-column-'+column.id+''+extra+'" style="'+column.style+'"  title="'+column.name+': '+ BX.util.htmlspecialchars(item[column.id]) + hintNotice +'">';
					if (elementDisable)
					{
						htmlElement += '<span class="bx-file-dialog-content-link bx-file-dialog-icon bx-file-dialog-icon-'+item.type+'">'+ BX.util.htmlspecialchars(item[column.id]) +'</span>';
					}
					else
					{
						htmlElement += '<a href="#" class="bx-file-dialog-content-link bx-file-dialog-icon bx-file-dialog-icon-'+item.type+'" onclick="javascript:void(0);">'+ BX.util.htmlspecialchars(item[column.id]) +'</a>';
					}

					htmlElement += '</span>';
					firstColumn = false;
				}
				else
				{
					htmlElement += '<span class="bx-file-dialog-column-row bx-file-dialog-column-'+column.id+'" style="'+column.style+'" title="'+column.name+': '+ BX.util.htmlspecialchars(item[column.id]) +'">'+ BX.util.htmlspecialchars(item[column.id]) +'</span>';
				}
			}
			htmlElement += '</div>';

			if(sortMode === 'ord')
			{
				if (item.type == 'folder')
					htmlFolder += htmlElement;
				else
					htmlFile += htmlElement;
			}
			else if(sortMode === 'mix')
			{
				htmlFile += htmlElement;
			}
		}
		html += htmlFolder+htmlFile;
		html += '</div>';
	if (BX.DiskFileDialog.obCurrentPath[name] == '/')
		html += '</div>';
	return html;
}


BX.DiskFileDialog.showWait = function(timeout, name)
{
	if(!name)
		name = 'fd';

	if (BX.DiskFileDialog.popupWaitWindow !== null)
		BX.DiskFileDialog.closeWait(name);

	if (timeout > 0)
	{
		clearTimeout(BX.DiskFileDialog.timeout);
		BX.DiskFileDialog.timeout = setTimeout(function(){
			BX.DiskFileDialog.showWait(0, name);
		}, timeout);
		return false;
	}

	var content = BX('bx-file-dialog-content-'+name);
	BX.DiskFileDialog.popupWaitWindow = new BX.PopupWindow('DiskFileDialogWait', content, {
		autoHide: false,
		lightShadow: true,
		zIndex: 100,
		content: BX.create('DIV', {props: {className: 'bx-file-dialog-wait'}}),
		events : {
			onPopupClose : function() {
				this.destroy();
			},
			onPopupDestroy : function() {
				BX.DiskFileDialog.popupWaitWindow = null;
			}
		}
	});
	if (content)
	{
		var height = content.offsetHeight, width = content.offsetWidth;
		if (height > 0 && width > 0)
		{
			BX.DiskFileDialog.popupWaitWindow.setOffset({
				offsetTop: -parseInt(height/2-2),
				offsetLeft: parseInt(width/2-15)
			});
			BX.DiskFileDialog.popupWaitWindow.show();
		}
	}

	return false;
}

BX.DiskFileDialog.closeWait = function(name)
{
	if(!name)
		name = 'fd';

	if (BX.DiskFileDialog.popupWaitWindow !== null)
		BX.DiskFileDialog.popupWaitWindow.close();

	clearTimeout(BX.DiskFileDialog.timeout);

	return false;
}

BX.DiskFileDialog.enableSave = function(name)
{
	if(!name)
		name = 'fd';

	BX.DiskFileDialog.obButtonSaveDisabled[name] = false;
	if(BX.DiskFileDialog.popupWindow && BX.DiskFileDialog.popupWindow.buttons && BX.DiskFileDialog.popupWindow.buttons[0])
		BX.DiskFileDialog.popupWindow.buttons[0].setClassName('popup-window-button-accept');
}

BX.DiskFileDialog.disableSave = function(name)
{
	if(!name)
		name = 'fd';

	BX.DiskFileDialog.obButtonSaveDisabled[name] = true;
	if(BX.DiskFileDialog.popupWindow && BX.DiskFileDialog.popupWindow.buttons && BX.DiskFileDialog.popupWindow.buttons[0])
		BX.DiskFileDialog.popupWindow.buttons[0].setClassName('popup-window-button-disabled');
}

BX.DiskFileDialog.showNotice = function(text, name)
{
	if(!name)
		name = 'fd';

	var container = BX('bx-file-dialog-notice-'+name);
	if (container == null)
		return false;

	BX.show(container);

	var element = BX.findChild(container, {className : "bx-file-dialog-notice-wrap"}, true);
	element.innerHTML = text;
}

BX.DiskFileDialog.closeNotice = function(name)
{
	if(!name)
		name = 'fd';

	var container = BX('bx-file-dialog-notice-'+name);
	if (container == null)
		return false;

	BX.hide(container);
}

BX.DiskFileDialog.slidePath = function(name)
{
	var pathWrap = BX('bx-file-dialog-content-path-items-wrap-'+name);
	if (!pathWrap) return;
	var path = pathWrap.parentNode;
	if (pathWrap.offsetWidth > path.offsetWidth)
		BX.style(pathWrap, 'margin-left', -(pathWrap.offsetWidth-path.offsetWidth)+'px');

	BX.unbind(pathWrap, 'mousewheel', function(event){BX.DiskFileDialog.slidePathScroll(event, name)});
	BX.bind(pathWrap, 'mousewheel', function(event){BX.DiskFileDialog.slidePathScroll(event, name)});
}

BX.DiskFileDialog.slidePathScroll = function(event, name)
{
	var pathWrap = BX('bx-file-dialog-content-path-items-wrap-'+name);
	if (!pathWrap) return;
	if (pathWrap.offsetWidth < pathWrap.parentNode.offsetWidth)
		return false;

	var maxMargin = pathWrap.offsetWidth-pathWrap.parentNode.offsetWidth;
	var scrollMargin = (parseInt(BX.style(pathWrap, 'margin-left'))-Math.ceil(30 * BX.getWheelData(event)/3))*-1;

	if (scrollMargin >= 0 && scrollMargin <= maxMargin)
		BX.style(pathWrap, 'margin-left', -scrollMargin+'px');
	else if (scrollMargin > maxMargin)
		BX.style(pathWrap, 'margin-left', -maxMargin+'px');
	else if (scrollMargin < 0)
		BX.style(pathWrap, 'margin-left', '0px');
	return	BX.PreventDefault(event);
}

})();
