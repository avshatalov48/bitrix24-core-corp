(function() {
var BX = window.BX;
if(BX.WebDavFileDialog)
	return;

BX.WebDavFileDialog =
{
	popupWindow: null,
	popupWaitWindow: null,
	timeout: null,
	sendRequest: false,

	obCallback: {},
	obLocalize: {},

	obType: {},
	obTypeItems: {},

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

BX.WebDavFileDialog.init = function(arParams)
{
	if(!arParams.name)
		arParams.name = 'fd';

	BX.WebDavFileDialog.obCallback[arParams.name] = arParams.callback;

	BX.WebDavFileDialog.obType[arParams.name] = arParams.type;
	BX.WebDavFileDialog.obTypeItems[arParams.name] = arParams.typeItems;
	BX.WebDavFileDialog.obLocalize[arParams.name] = arParams.localize;

	BX.WebDavFileDialog.obCurrentPath[arParams.name] =  arParams.currentPath? arParams.currentPath : '/';
	BX.WebDavFileDialog.obCurrentTab[arParams.name] = arParams.currentTabId? BX.WebDavFileDialog.obTypeItems[arParams.name][arParams.currentTabId] : null;
	BX.WebDavFileDialog.obFolderByPath[arParams.name] = arParams.folderByPath? arParams.folderByPath : {};
	if (BX.WebDavFileDialog.obCurrentTab[arParams.name] !== null)
		BX.WebDavFileDialog.obFolderByPath[arParams.name]['/'] = {'id' : 'root', 'type': 'folder', 'extra': '', 'name': BX.WebDavFileDialog.obTypeItems[arParams.name][arParams.currentTabId].name, 'path': '/', 'size': '', 'sizeInt': '0', 'modifyBy': '', 'modifyDate': '', 'modifyDateInt': '0'};

	BX.WebDavFileDialog.obItems[arParams.name] = arParams.items;

	BX.WebDavFileDialog.obItemsDisabled[arParams.name] = arParams.itemsDisabled;
	BX.WebDavFileDialog.obItemsSelected[arParams.name] = arParams.itemsSelected;
	BX.WebDavFileDialog.obItemsSelectEnabled[arParams.name] = arParams.itemsSelectEnabled;
	BX.WebDavFileDialog.obItemsSelectMulti[arParams.name] = arParams.itemsSelectMulti;

	BX.WebDavFileDialog.obElementBindPopup[arParams.name] = arParams.bindPopup;

	BX.WebDavFileDialog.obGridColumn[arParams.name] = arParams.gridColumn;
	BX.WebDavFileDialog.obGridOrder[arParams.name] = arParams.gridOrder;

	var arTypeItemsSort = BX.util.objectSort(BX.WebDavFileDialog.obTypeItems[arParams.name], 'name', 'asc');
	for (var i = 0, c = arTypeItemsSort.length; i < c; i++)
	{
		var item = arTypeItemsSort[i];

		if (BX.WebDavFileDialog.obType[arParams.name][item.type])
		{
			if (BX.WebDavFileDialog.obType[arParams.name][item.type].items)
				BX.WebDavFileDialog.obType[arParams.name][item.type].items.push(item.id)
			else
				BX.WebDavFileDialog.obType[arParams.name][item.type].items = [item.id];
		}
	}

	if (BX.WebDavFileDialog.obCurrentTab[arParams.name] == null)
	{
		var arTypeSort = BX.util.objectSort(BX.WebDavFileDialog.obType[arParams.name], 'order', 'asc');
		for (var i = 0, c = arTypeItemsSort.length; i < c; i++)
		{
			var item = arTypeItemsSort[i];
			if (item.type == arTypeSort[0].id)
			{
				BX.WebDavFileDialog.obCurrentPath[arParams.name] = '/';
				BX.WebDavFileDialog.obCurrentTab[arParams.name] = BX.WebDavFileDialog.obTypeItems[arParams.name][item.id];
				BX.WebDavFileDialog.obFolderByPath[arParams.name]['/'] = {'id' : 'root', 'type': 'folder', 'extra': '', 'name': BX.WebDavFileDialog.obTypeItems[arParams.name][item.id].name, 'path': '/', 'size': '', 'sizeInt': '0', 'modifyBy': '', 'modifyDate': '', 'modifyDateInt': '0'};
				break;
			}
		}
	}

	for (var i in BX.WebDavFileDialog.obItemsSelected[arParams.name])
	{
		if (BX.WebDavFileDialog.obItems[arParams.name][i])
			BX.WebDavFileDialog.obItemsSelected[arParams.name][i] = BX.WebDavFileDialog.obItems[arParams.name][i].type;
		else
			delete BX.WebDavFileDialog.obItemsSelected[arParams.name][i];
	}

	BX.WebDavFileDialog.obInitItems[arParams.name] = BX.clone(BX.WebDavFileDialog.obItems[arParams.name]);
	BX.WebDavFileDialog.obInitItemsSelected[arParams.name] = BX.clone(BX.WebDavFileDialog.obItemsSelected[arParams.name]);
	BX.WebDavFileDialog.obInitItemsDisabled[arParams.name] = BX.clone(BX.WebDavFileDialog.obItemsDisabled[arParams.name]);

	BX.WebDavFileDialog.obButtonSaveDisabled[arParams.name] = false;

	var firstLoadItems = true;
	for (var i in arParams.items)
	{
		firstLoadItems = false;
		break;
	}
	if (firstLoadItems)
		BX.WebDavFileDialog.loadItems(BX.WebDavFileDialog.obCurrentTab[arParams.name], arParams.name);
}

BX.WebDavFileDialog.openDialog = function(name)
{
	if(!name)
		name = 'fd';

	if (BX.WebDavFileDialog.popupWindow != null)
	{
		BX.WebDavFileDialog.popupWindow.close();
		return false;
	}

	BX.WebDavFileDialog.popupWindow = new BX.PopupWindow('WebDavFileDialog', BX.WebDavFileDialog.obElementBindPopup[name].node, {
		/*autoHide: true,*/
		offsetLeft: parseInt(BX.WebDavFileDialog.obElementBindPopup[name].offsetLeft),
		offsetTop: parseInt(BX.WebDavFileDialog.obElementBindPopup[name].offsetTop),
		bindOptions: {forceBindPosition: true},
		closeByEsc: true,
		closeIcon : true,
		draggable: BX.WebDavFileDialog.obElementBindPopup[name].node == null? {restrict: true}: false,
		titleBar: BX.WebDavFileDialog.obLocalize[name].title,
		contentColor : 'white',
		contentNoPaddings : true,
		events : {
			onPopupClose : function() {
				if (BX.WebDavFileDialog.popupWaitWindow !== null)
					BX.WebDavFileDialog.popupWaitWindow.close();
				this.destroy();
			},
			onPopupDestroy : function() {
				BX.WebDavFileDialog.popupWindow = null;
			}
		},
		content:	'<div class="bx-file-dialog-container">'+
						'<span class="bx-file-dialog-tab">'+
							'<div class="bx-file-dialog-tab-wrap" id="bx-file-dialog-tab-'+name+'">'
								+BX.WebDavFileDialog.getTabsHtml(name)+
							'</div>'+
						'</span>'+
						'<span class="bx-file-dialog-content" id="bx-file-dialog-content-'+name+'">'
							+BX.WebDavFileDialog.getItemsHtml(name)+
						'</span>'+
					'</div>'+
					'<div class="bx-file-dialog-notice" id="bx-file-dialog-notice-'+name+'">'+
						'<div class="popup-window-hr popup-window-buttons-hr"><i></i></div>'+
						'<div class="bx-file-dialog-notice-wrap"></div>'+
					'</div>',
		buttons: [
			new BX.PopupWindowButton({
				text : BX.WebDavFileDialog.obLocalize[name].saveButton,
				className : "popup-window-button-accept",
				events : { click : function()
				{
					if (BX.WebDavFileDialog.obButtonSaveDisabled[name])
						return false;

					BX.WebDavFileDialog.obInitItems[name] = BX.clone(BX.WebDavFileDialog.obItems[name]);
					BX.WebDavFileDialog.obInitItemsSelected[name] = BX.clone(BX.WebDavFileDialog.obItemsSelected[name]);
					BX.WebDavFileDialog.obInitItemsDisabled[name] = BX.clone(BX.WebDavFileDialog.obItemsDisabled[name]);

					if(BX.WebDavFileDialog.obCallback[name] && BX.WebDavFileDialog.obCallback[name].saveButton)
					{
						var selected = {};
						for(var i in BX.WebDavFileDialog.obItemsSelected[name])
							selected[i] = BX.WebDavFileDialog.obItems[name][i];

						BX.WebDavFileDialog.obCallback[name].saveButton(BX.WebDavFileDialog.obCurrentTab[name], BX.WebDavFileDialog.obCurrentPath[name], selected, BX.WebDavFileDialog.obFolderByPath[name][BX.WebDavFileDialog.obCurrentPath[name]]);
					}

					this.popupWindow.close();
				}}
			}),

			new BX.PopupWindowButtonLink({
				text: BX.WebDavFileDialog.obLocalize[name].cancelButton,
				className: "popup-window-button-link-cancel",
				events: { click : function()
				{

					BX.WebDavFileDialog.obItems[name] = BX.clone(BX.WebDavFileDialog.obInitItems[name]);
					BX.WebDavFileDialog.obItemsSelected[name] = BX.clone(BX.WebDavFileDialog.obInitItemsSelected[name]);
					BX.WebDavFileDialog.obItemsDisabled[name] = BX.clone(BX.WebDavFileDialog.obInitItemsDisabled[name]);

					if(BX.WebDavFileDialog.obCallback[name] && BX.WebDavFileDialog.obCallback[name].cancelButton)
					{
						var selected = {};
						for(var i in BX.WebDavFileDialog.obItemsSelected[name])
							selected[i] = BX.WebDavFileDialog.obItems[name][i];

						BX.WebDavFileDialog.obCallback[name].cancelButton(BX.WebDavFileDialog.obCurrentTab[name], BX.WebDavFileDialog.obCurrentPath[name], selected, BX.WebDavFileDialog.obFolderByPath[name][BX.WebDavFileDialog.obCurrentPath[name]]);
					}

					this.popupWindow.close();
				}}
			})
		]
	});
	BX.WebDavFileDialog.popupWindow.show();
	BX.WebDavFileDialog.slidePath(name);
}

BX.WebDavFileDialog.selectTab = function(element, tabId, name)
{
	if(!name)
		name = 'fd';

	if (BX.WebDavFileDialog.obCurrentTab[name] && BX.WebDavFileDialog.obCurrentTab[name].id == tabId)
		return false;

	BX.WebDavFileDialog.obCurrentPath[name] = '/';
	BX.WebDavFileDialog.obCurrentTab[name] = BX.WebDavFileDialog.obTypeItems[name][tabId];

	BX.WebDavFileDialog.obFolderByPath[name] = {};
	BX.WebDavFileDialog.obFolderByPath[name][BX.WebDavFileDialog.obCurrentPath[name]] = {'id' : 'root', 'type': 'folder', 'extra': '', 'name': BX.WebDavFileDialog.obTypeItems[name][tabId].name, 'path': '/', 'size': '', 'sizeInt': '0', 'modifyBy': '', 'modifyDate': '', 'modifyDateInt': '0'};

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

	BX.WebDavFileDialog.loadItems(BX.WebDavFileDialog.obCurrentTab[name], name);

	return false;
}

BX.WebDavFileDialog.loadItems = function(oTarget, name)
{
	if (BX.WebDavFileDialog.sendRequest)
		return false;

	if(!name)
		name = 'fd';

	if (!BX.WebDavFileDialog.target)
		BX.WebDavFileDialog.target = {};
	if (!!oTarget.link)
		BX.WebDavFileDialog.target[name] = oTarget.link;

	BX.onCustomEvent(BX.WebDavFileDialog, 'loadItems', [oTarget.link, name]);

	BX.ajax({
		url: BX.WebDavFileDialog.target[name],
		method: 'POST',
		dataType: 'json',
		data: {'WD_LOAD_ITEMS' : 'Y',
			'FORM_NAME' : name,
			'FORM_TAB_ID' : BX.WebDavFileDialog.obCurrentTab[name].id,
			'FORM_TAB_TYPE' : BX.WebDavFileDialog.obCurrentTab[name].type,
			'FORM_PATH' : BX.WebDavFileDialog.obCurrentPath[name],
			'sessid': BX.bitrix_sessid()
		},

		onsuccess: function(data)
		{
			BX.WebDavFileDialog.obItems[data.FORM_NAME] = {};
			BX.WebDavFileDialog.obItemsSelected[data.FORM_NAME] = {};
			BX.WebDavFileDialog.obItemsDisabled[data.FORM_NAME] = {};

			for (var i in data.FORM_ITEMS)
				BX.WebDavFileDialog.obItems[data.FORM_NAME][i] = data.FORM_ITEMS[i];

			for (var i in data.FORM_ITEMS_DISABLED)
				BX.WebDavFileDialog.obItemsDisabled[data.FORM_NAME][i] = data.FORM_ITEMS_DISABLED[i];

			BX.WebDavFileDialog.obCurrentTab[data.FORM_NAME].iblock_id = data.FORM_IBLOCK_ID;

			//BX.WebDavFileDialog.obCurrentPath[data.FORM_NAME] = data.FORM_PATH;

			var container = BX('bx-file-dialog-content-'+name);
			if (container)
				container.innerHTML = BX.WebDavFileDialog.getItemsHtml(name);
			BX.WebDavFileDialog.slidePath(name);

			BX.WebDavFileDialog.closeWait(name);
			BX.WebDavFileDialog.sendRequest = false;

			BX.onCustomEvent(BX.WebDavFileDialog, 'loadItemsDone', [name]);
		},
		onfailure: function(data) { BX.WebDavFileDialog.sendRequest = false; }
	});
	BX.WebDavFileDialog.showWait(500, name);
	BX.WebDavFileDialog.sendRequest = true;

	return false;
}

BX.WebDavFileDialog.selectColumn = function(column, order, name)
{
	if(!column)
		column = 'name';

	BX.WebDavFileDialog.obGridOrder[name].column = !column? 'name': column;
	BX.WebDavFileDialog.obGridOrder[name].order = !order? 'desc': order;

	var container = BX('bx-file-dialog-content-'+name);
	if (container)
		container.innerHTML = BX.WebDavFileDialog.getItemsHtml(name);
	BX.WebDavFileDialog.slidePath(name);

	return false;
}

BX.WebDavFileDialog.selectItem = function(element, itemId, name)
{
	if(!name)
		name = 'fd';

	if (BX.WebDavFileDialog.obItemsSelected[name][itemId])
	{
		BX.WebDavFileDialog.unSelectItem(element, itemId, name);
	}
	else
	{
		if (!BX.WebDavFileDialog.obItemsSelectMulti[name])
		{
			var elements = BX.findChildren(BX('bx-file-dialog-content-'+name), {className : "bx-file-dialog-row-selected"}, true);
			if (elements != null)
			{
				for (var i = 0, c = elements.length; i < c; i++)
					BX.WebDavFileDialog.unSelectItem(elements[i], elements[i].getAttribute('data-id'), name);
			}
			else
			{
				for (var i in BX.WebDavFileDialog.obItemsSelected[name])
					delete BX.WebDavFileDialog.obItemsSelected[name][i];
			}
		}

		if (element !== null)
		{
			BX.removeClass(element, 'bx-file-dialog-row-normal');
			BX.addClass(element, 'bx-file-dialog-row-selected');
			BX.onCustomEvent(BX.WebDavFileDialog, 'selectItem', [element, itemId, name]);
		}

		BX.WebDavFileDialog.obItemsSelected[name][itemId] = BX.WebDavFileDialog.obItems[name][itemId].type;
	}

	return false;
}

BX.WebDavFileDialog.unSelectItem = function(element, itemId, name)
{
	if(!name)
		name = 'fd';

	BX.removeClass(element, 'bx-file-dialog-row-selected');
	BX.addClass(element, 'bx-file-dialog-row-normal');

	delete BX.WebDavFileDialog.obItemsSelected[name][itemId];

	BX.onCustomEvent(BX.WebDavFileDialog, 'unSelectItem', [element, itemId, name]);

	return false;
}

BX.WebDavFileDialog._rtrimFolder = function(path)
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

BX.WebDavFileDialog.openSpecifiedFolder = function(url, name)
{
	if(!name)
		name = 'fd';

	BX.WebDavFileDialog.obCurrentPath[name] = url;

	//url = BX.WebDavFileDialog.obCurrentTab[name].link + url;

	link = BX.WebDavFileDialog.obCurrentTab[name].link;
	indexPos =link.indexOf('index.php');
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

	BX.WebDavFileDialog.loadItems({'link':url}, name);

	return false;
}

BX.WebDavFileDialog.openParentFolder = function(name)
{
	if(!name)
		name = 'fd';

	if (!!BX.WebDavFileDialog.obCurrentTab[name]) {
		var url = BX.WebDavFileDialog._rtrimFolder(BX.WebDavFileDialog.obCurrentPath[name]);
		BX.WebDavFileDialog.obCurrentPath[name] = url;
		url = BX.WebDavFileDialog.obCurrentTab[name].link + url;
		BX.WebDavFileDialog.loadItems({'link':url}, name);
	}

	return false;
}

BX.WebDavFileDialog.openFolder = function(element, itemId, name)
{
	if(!name)
		name = 'fd';

	BX.WebDavFileDialog.obCurrentPath[name] = BX.WebDavFileDialog.obItems[name][itemId].path;
	BX.WebDavFileDialog.obFolderByPath[name][BX.WebDavFileDialog.obCurrentPath[name]] = BX.WebDavFileDialog.obItems[name][itemId];

	BX.removeClass(element, 'bx-file-dialog-row-selected');
	BX.addClass(element, 'bx-file-dialog-row-normal');

	BX.WebDavFileDialog.loadItems(BX.WebDavFileDialog.obItems[name][itemId], name);

	return false;
}

BX.WebDavFileDialog.getTabsHtml = function(name)
{
	if(!name)
		name = 'fd';

	var html = '';
	var arTypeSort = BX.util.objectSort(BX.WebDavFileDialog.obType[name], 'order', 'asc');
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
				var item = BX.WebDavFileDialog.obTypeItems[name][type.items[j]];
				var active = BX.WebDavFileDialog.obCurrentTab[name].id == item.id? ' bx-file-dialog-tab-item-active': '';
				groupHtml += '<div class="bx-file-dialog-tab-item">'+
							'<a href="#" class="bx-file-dialog-tab-item-link'+active+'" data-id="'+item.id+'" data-type="'+item.type+'" onclick="return BX.WebDavFileDialog.selectTab(this, \''+item.id+'\', \''+name+'\')">'+
								'<span class="bx-file-dialog-tab-item-link-text" title="'+item.name+'">'+item.name+'</span>'+
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

BX.WebDavFileDialog.getItemsHtml = function(name)
{
	if(!name)
		name = 'fd';
	var html = '';
	if (BX.WebDavFileDialog.obCurrentPath[name] == '/')
		html = '<div class="bx-file-dialog-content-root">';
		// header
		html += '<div class="bx-file-dialog-header">';
		for (var i in BX.WebDavFileDialog.obGridColumn[name])
		{

			var column = BX.WebDavFileDialog.obGridColumn[name][i];
			var sortByColumn = BX.WebDavFileDialog.obGridOrder[name].column == column.sort? true: false;
			html += '<span class="bx-file-dialog-column-header bx-file-dialog-column-'+column.id+'" style="'+column.style+'">'+
						'<a href="#sort" class="bx-file-dialog-sort-link'+(sortByColumn? ' bx-file-dialog-sort-active': '')+'"  onclick="return BX.WebDavFileDialog.selectColumn(\''+column.sort+'\', \''+(sortByColumn? (BX.WebDavFileDialog.obGridOrder[name].order == 'desc'? 'asc':'desc'): 'desc')+'\', \''+name+'\')">'
							+column.name+'<span class="bx-file-dialog-sort-icon bx-file-dialog-sort-'+(sortByColumn? BX.WebDavFileDialog.obGridOrder[name].order: 'desc')+'"></span>'+
						'</a>'+
					'</span>';
		}
		html += '</div>';


		// path
		var arPath = BX.WebDavFileDialog.obCurrentPath[name].split('/');
		html +=	'<div class="bx-file-dialog-content-path">'+
					'<a href="#up" class="bx-file-dialog-content-path-up bx-file-dialog-icon bx-file-dialog-icon-up" onclick="return BX.WebDavFileDialog.openParentFolder(\''+name+'\');"></a>'+
					'<span class="bx-file-dialog-content-path-items"><span class="bx-file-dialog-content-path-items-wrap" id="bx-file-dialog-content-path-items-wrap-'+name+'"> '+
					'<a href="#library" onclick="return BX.WebDavFileDialog.openSpecifiedFolder(\'/\', \''+name+'\')" class="bx-file-dialog-content-path-link">'+BX.WebDavFileDialog.obCurrentTab[name].name+'</a>';
					var path = '/';
					if (arPath[arPath.length-1] == '')
						arPath.pop();
					for (var i = 0, c = arPath.length; i < c; i++)
					{
						if (i == 0 || i == c)
							continue;
						path += arPath[i]+'/';
						html += '<span class="bx-file-dialog-content-path-seporator bx-file-dialog-icon bx-file-dialog-icon-seporator"></span>';
						html +=	'<a href="#'+arPath[i]+'" onclick="return BX.WebDavFileDialog.openSpecifiedFolder(\''+path+'\', \''+name+'\')" class="bx-file-dialog-content-path-link '+(i == c-1? 'bx-file-dialog-content-path-link-active':'')+'">'+arPath[i]+'</a>';
					};
					html += '</span></span>';
		html +=	'</div>';
		// items
		html +=	'<div class="bx-file-dialog-content-wrap">';

		var htmlFolder = ""; var htmlFile = "";	var htmlElement = "";
		var arTypeSort = BX.util.objectSort(BX.WebDavFileDialog.obItems[name], BX.WebDavFileDialog.obGridOrder[name].column, BX.WebDavFileDialog.obGridOrder[name].order);
		for (var i = 0, c = arTypeSort.length; i < c; i++)
		{
			var item = arTypeSort[i];
			var extraClass = BX.WebDavFileDialog.obItemsSelected[name][item.id]? ' bx-file-dialog-row-selected': ' bx-file-dialog-row-normal';

			var selectDisable = false;
			var elementDisable = false;
			if (BX.WebDavFileDialog.obItemsDisabled[name][item.id])
				elementDisable = true;

			if (elementDisable && item.type == 'folder')
				selectDisable = true;

			if (!elementDisable)
			{
				elementDisable = true;
				var enableTypeCount = 0;
				for (var enableType in BX.WebDavFileDialog.obItemsSelectEnabled[name])
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
				if(BX.WebDavFileDialog.obItemsDisabled[name] && BX.WebDavFileDialog.obItemsDisabled[name][item.id] && BX.WebDavFileDialog.obItemsDisabled[name][item.id]['hint'])
				{
					hintNotice = '. ' + BX.WebDavFileDialog.obItemsDisabled[name][item.id]['hint'];
				}
			}

			var clickfunc = '';
			var dblclickfunc = '';
			if (!elementDisable)
			{
				var clickfunc = 'onclick="return BX.WebDavFileDialog.selectItem(this, \''+item.id+'\', \''+name+'\')"';
				var dblclickfunc = '';
				if (item.type == 'folder')
				{
					clickfunc = selectDisable? 'onclick="return BX.WebDavFileDialog.openFolder(this, \''+item.id+'\', \''+name+'\')"': clickfunc;
					dblclickfunc = selectDisable? '': 'ondblclick="return BX.WebDavFileDialog.openFolder(this, \''+item.id+'\', \''+name+'\')"';
				}
			}

			htmlElement = '<div '+ clickfunc+' '+dblclickfunc +' class="bx-file-dialog-row '+extraClass+'" data-id="'+item.id+'">'; // bx-file-dialog-row-selected bx-file-dialog-row-disabled
			var firstColumn = true;
			for (var j in BX.WebDavFileDialog.obGridColumn[name])
			{
				var column = BX.WebDavFileDialog.obGridColumn[name][j];
				var extra = item.extra && item.extra != ''? ' bx-file-dialog-extra-'+item.extra: '';

				if (firstColumn)
				{
					htmlElement += '<span class="bx-file-dialog-column-row bx-file-dialog-column-'+column.id+''+extra+'" style="'+column.style+'"  title="'+column.name+': '+item[column.id]+ hintNotice +'">';
					if (elementDisable)
					{
						htmlElement += '<span class="bx-file-dialog-content-link bx-file-dialog-icon bx-file-dialog-icon-'+item.type+'">'+item[column.id]+'</span>';
					}
					else
					{
						htmlElement += '<a href="#" class="bx-file-dialog-content-link bx-file-dialog-icon bx-file-dialog-icon-'+item.type+'" onclick="javascript:void(0);">'+item[column.id]+'</a>';
					}

					htmlElement += '</span>';
					firstColumn = false;
				}
				else
				{
					htmlElement += '<span class="bx-file-dialog-column-row bx-file-dialog-column-'+column.id+'" style="'+column.style+'" title="'+column.name+': '+item[column.id]+'">'+item[column.id]+'</span>';
				}
			}
			htmlElement += '</div>';

			if (item.type == 'folder')
				htmlFolder += htmlElement;
			else
				htmlFile += htmlElement;
		}
		html += htmlFolder+htmlFile;
		html += '</div>';
	if (BX.WebDavFileDialog.obCurrentPath[name] == '/')
		html += '</div>';
	return html;
}


BX.WebDavFileDialog.showWait = function(timeout, name)
{
	if(!name)
		name = 'fd';

	if (BX.WebDavFileDialog.popupWaitWindow !== null)
		BX.WebDavFileDialog.closeWait(name);

	if (timeout > 0)
	{
		clearTimeout(BX.WebDavFileDialog.timeout);
		BX.WebDavFileDialog.timeout = setTimeout(function(){
			BX.WebDavFileDialog.showWait(0, name);
		}, timeout);
		return false;
	}

	var content = BX('bx-file-dialog-content-'+name);
	BX.WebDavFileDialog.popupWaitWindow = new BX.PopupWindow('WebDavFileDialogWait', content, {
		autoHide: false,
		lightShadow: true,
		content: BX.create('DIV', {props: {className: 'bx-file-dialog-wait'}}),
		events : {
			onPopupClose : function() {
				this.destroy();
			},
			onPopupDestroy : function() {
				BX.WebDavFileDialog.popupWaitWindow = null;
			}
		}
	});

	var height = content.offsetHeight, width = content.offsetWidth;
	if (height > 0 && width > 0)
	{
		BX.WebDavFileDialog.popupWaitWindow.setOffset({
			offsetTop: -parseInt(height/2-2),
			offsetLeft: parseInt(width/2-15)
		});
		BX.WebDavFileDialog.popupWaitWindow.show();
	}

	return false;
}

BX.WebDavFileDialog.closeWait = function(name)
{
	if(!name)
		name = 'fd';

	if (BX.WebDavFileDialog.popupWaitWindow !== null)
		BX.WebDavFileDialog.popupWaitWindow.close();

	clearTimeout(BX.WebDavFileDialog.timeout);

	return false;
}

BX.WebDavFileDialog.enableSave = function(name)
{
	if(!name)
		name = 'fd';

	BX.WebDavFileDialog.obButtonSaveDisabled[name] = false;
}

BX.WebDavFileDialog.disableSave = function(name)
{
	if(!name)
		name = 'fd';

	BX.WebDavFileDialog.obButtonSaveDisabled[name] = true;
}

BX.WebDavFileDialog.showNotice = function(text, name)
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

BX.WebDavFileDialog.closeNotice = function(name)
{
	if(!name)
		name = 'fd';

	var container = BX('bx-file-dialog-notice-'+name);
	if (container == null)
		return false;

	BX.hide(container);
}

BX.WebDavFileDialog.slidePath = function(name)
{
	var pathWrap = BX('bx-file-dialog-content-path-items-wrap-'+name);
	var path = pathWrap.parentNode;
	if (pathWrap.offsetWidth > path.offsetWidth)
		BX.style(pathWrap, 'margin-left', -(pathWrap.offsetWidth-path.offsetWidth)+'px');

	BX.unbind(pathWrap, 'mousewheel', function(event){BX.WebDavFileDialog.slidePathScroll(event, name)});
	BX.bind(pathWrap, 'mousewheel', function(event){BX.WebDavFileDialog.slidePathScroll(event, name)});
}

BX.WebDavFileDialog.slidePathScroll = function(event, name)
{
	var pathWrap = BX('bx-file-dialog-content-path-items-wrap-'+name);
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
