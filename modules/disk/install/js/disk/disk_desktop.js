BX.namespace("BX.Disk.Desktop.Settings");
BX.Disk.Desktop.Settings = (function ()
{
	var bxim = null;
	var diskEnabled = null;
	var Settings = function (parameters)
	{
		parameters = parameters || {};
		bxim = parameters.bxim;
		diskEnabled = parameters.diskEnabled;

		this.setEvents();
	};

	Settings.prototype.setEvents = function ()
	{
	};

	Settings.prototype.getFolderList = function (type)
	{
		var folders = BXFileStorage.GetFolderList();
		var rootFolders = [];
		for (var i = 0, length = folders.length; i < length; i++)
		{
			var folder = folders[i];
			var path = folder[0];
			var isSymlink = !!folder[1];
			var isSynced = !!folder[2];

			if(path.lastIndexOf('/') > 1)
				continue;


			if(type === 'all')
			{
				rootFolders.push({
					path: path,
					isSymlink: isSymlink,
					isSynced: isSynced
				});
			}
			else if(type === 'own' && !isSymlink)
			{
				rootFolders.push({
					path: path,
					isSymlink: isSymlink,
					isSynced: isSynced
				});
			}
			else if(type === 'shared' && isSymlink)
			{
				rootFolders.push({
					path: path,
					isSymlink: isSymlink,
					isSynced: isSynced
				});
			}
		}
		BX.util.objectSort(folders, 'path', 'asc');

		return rootFolders;
	};

	Settings.prototype.buildFolderNotice = function (type)
	{
		if(type === 'own')
		{
			return '<div id="bx-disk-sync-folder-' + type + '" class="bx-im-notice">' +
					BX.message('DISK_DESKTOP_JS_SETTINGS_SYNC_EMPTY_OWN_FOLDER_NOTICE') +
			    '</div>'
			;
		}
		if(type === 'shared')
		{
			return '<div id="bx-disk-sync-folder-' + type + '" class="bx-im-notice" style="display:none">' +
					BX.message('DISK_DESKTOP_JS_SETTINGS_SYNC_EMPTY_SHARED_FOLDER_NOTICE') +
			    '</div>'
			;
		}
	};

	Settings.prototype.buildFolderList = function (rootFolders, type)
	{
		if(rootFolders.length <=0 )
		{
			return this.buildFolderNotice(type);
		}
		var allToSync = !this.isEnabledSyncCustomFoldersByType(type);
		var html =
			'<ul id="bx-disk-sync-folder-' + type + '" class="bx-im-disk-list-block" ' + (type == 'shared'? 'style="display:none"' : '') + '>' +
				'<li class="bx-im-disk-list-item">' +
					'<input onclick="BitrixDiskSettings.onClickSyncAllFolder(this);" name="sync-all-' + type + '" data-save=1 type="checkbox" ' + (allToSync? 'checked="checked"' : '') + ' class="bx-im-disk-checkbox sync-all-folder" id="bx-disk-sync-all-folder' + type + '"><label class="bx-im-disk-label" for="bx-disk-sync-all-folder' + type + '">' + BX.message('DISK_DESKTOP_JS_SETTINGS_SYNC_ALL') + '</label>' +
				'</li>'
		;
		for (var i = 0, length = rootFolders.length; i < length; i++)
		{
			var folder = rootFolders[i];
			var name = folder.path.split('/').pop();
			html +=
				'<li class="bx-im-disk-list-item">' +
					'<input onclick="BitrixDiskSettings.onClickSyncFolder(this);" name="sync-' + name + '" data-save=1 type="checkbox" ' + (folder.isSynced? 'checked="checked"' : '') + ' class="bx-im-disk-checkbox" id="f-' + name + '"><label class="bx-im-disk-label" for="f-' + name + '">' + name + '</label>' +
				'</li>'
			;
		}

		html += '</ul>';

		return html;
	};

	Settings.prototype.fillFolderList = function ()
	{
		var ownFolderHtml = this.buildFolderList(this.getFolderList('own'), 'own');
		var sharedFolderHtml = this.buildFolderList(this.getFolderList('shared'), 'shared');
		if(BX('bx-im-disk-folders-setting-block-wrap'))
		{
			BX('bx-im-disk-folders-setting-block-wrap').innerHTML =
				'<div class="bx-im-disk-btn-wrap">' +
					'<span id="bx-disk-header-sync-folder-own" onclick="BitrixDiskSettings.onClickTab(\'own\', \'shared\');" class="bx-im-disk-btn bx-im-disk-btn-active"><span class="bx-disk-tab-btn-text">' + BX.message('DISK_DESKTOP_JS_SETTINGS_SYNC_TAB_OWNER') + '</span></span><span id="bx-disk-header-sync-folder-shared"  onclick="BitrixDiskSettings.onClickTab(\'shared\', \'own\');" class="bx-im-disk-btn"><span class="bx-disk-tab-btn-text">' + BX.message('DISK_DESKTOP_JS_SETTINGS_SYNC_TAB_SHARED') + '</span></span>' +
				'</div>' +
				ownFolderHtml +
				sharedFolderHtml +
				'<div id="bx-disk-settings-shared-folder-notice" class="bx-im-notice" style="display: none">' +
					BX.message('DISK_DESKTOP_JS_SETTINGS_SYNC_FOLDER_NOTICE') +
			    '</div>'
			;
		}
	};

	Settings.prototype.prepareSettingsView = function ()
	{
		bxim.settingsView.disk = {
			title: BX.message("DISK_DESKTOP_JS_SETTINGS_TITLE"),
			settings: [
				{
					title: BX.message("DISK_DESKTOP_JS_SETTINGS_LABEL_ENABLE"),
					type: "checkbox",
					name: "diskEnabled",
					checked: diskEnabled,
					callback: function () {}
				},
				{
					title: BX.message("DISK_DESKTOP_JS_SETTINGS_LABEL_FILE_CLICK_ACTION"),
					type: "select",
					value: this.getFileClickActionName(),
					name: "fileClickAction",
					items: [
						{title: BX.message("DISK_DESKTOP_JS_SETTINGS_LABEL_FILE_CLICK_ACTION_OPEN_FOLDER"), value: 'openFolder'},
						{title: BX.message("DISK_DESKTOP_JS_SETTINGS_LABEL_FILE_CLICK_ACTION_OPEN_FILE"), value: 'openFile'}
					],
					callback: function () {}
				}
			]
		};

		if(diskEnabled && BX.desktop.enableInVersion(42))
		{
			bxim.settingsView.disk.settings.push(
				{
					title: BX.message("DISK_DESKTOP_JS_SETTINGS_SYNC_MAKE_CHOICE"),
					type: "checkbox",
					name: "diskCustomFolders",
					callback: function () {
						if(this.checked)
						{
							BX.show(BX('bx-im-disk-folders-setting-block-wrap', 'block'));
							BX.desktop.resize();
						}
						else
						{
							BX.hide(BX('bx-im-disk-folders-setting-block-wrap'));
							BX.desktop.resize();
						}
					},
					checked: this.isEnabledSyncCustomFolders()
				},
				{
					type: "space"
				},
				{
					title: 'Settings',
					type: "html",
					name: "custom_folders",
					value:
						'<div id="bx-im-disk-folders-setting-block-wrap" class="bx-im-disk-setting-block-wrap" ' + (this.isEnabledSyncCustomFolders()? '' : 'style="display: none"') + '>' +
						'</div>',
					callback: function () {}
				}
			);
		}
	};

	Settings.prototype.onClickSyncAllFolder = function (checkbox)
	{
		var parentUl = BX.findParent(checkbox, {tagName: 'ul'}, 4);
		if(!parentUl)
			return false;

		var checkboxes = BX.findChildren(parentUl, {tagName: 'input', className: 'bx-im-disk-checkbox'}, true);
		for(var i in checkboxes)
		{
			if (!checkboxes.hasOwnProperty(i))
				continue;

			checkboxes[i].checked = checkbox.checked;
		}

		return false;
	};

	Settings.prototype.onClickSyncFolder = function (checkbox)
	{
		if(checkbox.checked)
			return false;

		var parentUl = BX.findParent(checkbox, {tagName: 'ul'}, 4);
		if(!parentUl)
			return false;

		var mainCheckbox = BX.findChild(parentUl, {tagName: 'input', className: 'bx-im-disk-checkbox sync-all-folder'}, true);
		if(!mainCheckbox)
			return false;

		mainCheckbox.checked = false;
	};

	Settings.prototype.onClickTab = function (clickType, oppositeType)
	{
		var tabHeader = BX('bx-disk-header-sync-folder-' + clickType);
		var oppositeTabHeader = BX('bx-disk-header-sync-folder-' + oppositeType);
		if(BX.hasClass(tabHeader, 'bx-im-disk-btn-active'))
			return false;

		BX.removeClass(oppositeTabHeader, 'bx-im-disk-btn-active');
		BX.addClass(tabHeader, 'bx-im-disk-btn-active');

		BX.hide(BX('bx-disk-sync-folder-' + oppositeType));
		BX.show(BX('bx-disk-sync-folder-' + clickType), 'block');

		if(clickType === 'shared')
			BX.show(BX('bx-disk-settings-shared-folder-notice'), 'block');
		else
			BX.hide(BX('bx-disk-settings-shared-folder-notice'));

		BX.desktop.resize();

		return false;
	};

	Settings.prototype.saveSettings = function (settings)
	{
		settings = settings || {};
		if(settings.fileClickAction)
		{
			this.setFileClickActionName(settings.fileClickAction);
		}

		this.storeSyncCustomFolders(settings.diskCustomFolders);
		if(!settings.diskCustomFolders)
		{
			this.subscribeByTypeRootFolders('all');
		}
		else
		{
			if(settings['sync-all-own'])
			{
				this.subscribeByTypeRootFolders('own');
			}
			if(settings['sync-all-shared'])
			{
				this.subscribeByTypeRootFolders('shared');
			}

			if(!settings['sync-all-own'] || !settings['sync-all-shared'])
			{
				for (var name in settings)
				{
					if (!settings.hasOwnProperty(name))
						continue;

					if(name.indexOf('sync-') === 0 && name.indexOf('sync-all-') === -1 )
					{
						if(!settings[name])
						{
							console.debug("UnsubscribeFolder", '/' + name.substr(5));
							BXFileStorage.UnsubscribeFolder('/' + name.substr(5));
						}
						else
						{
							console.debug("subscribeFolder", '/' + name.substr(5));
							BXFileStorage.SubscribeFolder('/' + name.substr(5));
						}
					}
				}
			}
		}

	};

	Settings.prototype.subscribeByTypeRootFolders = function (type)
	{
		var rootFolders = this.getFolderList(type);
		for (var i = 0, length = rootFolders.length; i < length; i++)
		{
			var folder = rootFolders[i];
			BXFileStorage.SubscribeFolder(folder.path);
		}
	};

	Settings.prototype.getFileClickActionName = function ()
	{
		var action = bxim.getLocalConfig("fileClickAction", {name: 'openFolder'});
		return action.name;
	};

	Settings.prototype.setFileClickActionName = function (action)
	{
		bxim.setLocalConfig("ww", action);
		switch(action)
		{
			case 'openFolder':
			case 'openFile':
				break;
			default:
				action = 'openFolder';
		}
		bxim.setLocalConfig("fileClickAction", {name: action});
	};

	Settings.prototype.isEnabledSyncCustomFoldersByType = function (type)
	{
		var rootFolders = this.getFolderList(type);
		for (var i = 0, length = rootFolders.length; i < length; i++)
		{
			if(!rootFolders[i].isSynced)
				return true;
		}
		return false;
	};

	Settings.prototype.isEnabledSyncCustomFolders = function ()
	{
		var action = bxim.getLocalConfig("diskCustomFolders", {enabled: false});
		return action.enabled;
	};

	Settings.prototype.storeSyncCustomFolders = function (enabled)
	{
		bxim.setLocalConfig("diskCustomFolders", {enabled: !!enabled});
	};

	return Settings;
})();
