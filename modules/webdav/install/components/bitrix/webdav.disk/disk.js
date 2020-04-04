var disk_token = "";
var disk_path = "";
var isInstalledPull = true;
var urlToDiskAjax = "";
var alreadyDiskInstall = false;
BX.ready(function(){
	BX.addCustomEvent(window, 'onImInit', function (BXIM) {
		if(!window.BXFileStorage)
			return;

		ReloadWindow();

		BXIM.desktop.addCustomEvent("BXProtocolUrl", function(command, params) {
			command = command.toLowerCase();
			switch(command)
			{
				case 'opendisktab':
					console.debug('openDisktab Start', params);
					BXDesktopSystem.GetMainWindow().ExecuteCommand("show");
					BX.desktop.setActiveWindow(TAB_CP);
					BX.desktop.onCustomEvent("main", "BXChangeTab", ['disk']);
					break;
				case 'openfolder':
					console.debug('openFolder Start', params);
					if(params && params.path)
					{
						BXFileStorage.Refresh(function(){
							params.path = decodeURIComponent(params.path);
							BXFileStorage.FolderExist('/' + params.path, function(status){
								if(!status)
								{
									return;
								}

								BXFileStorage.ObjectOpen('/' + params.path, function(status){
									if(status.Error)
									{
										return;
									}
								});
							});
						});
					}
					break;
				case 'openfile':
					console.debug('openFile Start', params);
					if(!params || !params.externalId)
					{
						return false;
					}
					if(!('GetObjectDataById' in BXFileStorage))
					{
						console.debug('Implement please GetObjectDataById!');
						break;
					}

					var fileInfo = BXFileStorage.GetObjectDataById(decodeURIComponent(params.externalId));
					console.debug('Object Data', fileInfo, decodeURIComponent(params.externalId));
					if(fileInfo.path)
					{
						BXFileStorage.FileExist(fileInfo.path, function(exist){
							if(exist)
							{
								BXFileStorage.ObjectOpen(fileInfo.path, function(){});
							}
							else
							{
								console.debug('Object not exists', fileInfo);
							}
						});
					}

					break;
				case 'createfile':
					console.debug('CreateFile Start', params);
					var urlDownload = '';
					var urlUpload = '';
					if(params && params.url)
					{
						urlDownload = decodeURIComponent(params.url);
						urlUpload = urlDownload = addToLinkParam(urlDownload, 'editIn', 'local');

						urlDownload = addToLinkParam(urlDownload, 'action', 'start');
						urlUpload = addToLinkParam(urlDownload, 'action', 'commit');
						urlUpload = addToLinkParam(urlDownload, 'primaryAction', 'commit');
					}
					else
					{
						console.debug('Mistake in params!');
					}
					if('EditFile' in BXFileStorage)
					{
						console.debug(params, params.name, decodeURIComponent(params.name));
						//fake!!!
						BitrixDisk.onStartEditingFile(decodeURIComponent(params.name), {}, null, true, true, true);
						BXFileStorage.EditFile(urlDownload, urlUpload, decodeURIComponent(params.name));
					}
					else
					{
						console.debug('Implement please EditFile!');
					}
					break;
				case 'editfile':
					console.debug('EditFile Start', params);
					var urlDownload = '';
					var urlUpload = '';
					if(params && params.url)
					{
						urlDownload = decodeURIComponent(params.url);
						urlUpload = urlDownload = addToLinkParam(urlDownload, 'editIn', 'local');

						urlDownload = addToLinkParam(urlDownload, 'action', 'start');
						urlUpload = addToLinkParam(urlDownload, 'action', 'commit');
						urlUpload = addToLinkParam(urlDownload, 'primaryAction', 'commit');
					}
					else
					{
						console.debug('Mistake in params!');
					}

					if(BX.type.isString(params.externalId) && params.externalId.length > 2)
					{
						if(!('GetObjectDataById' in BXFileStorage))
						{
							console.debug('Implement please GetObjectDataById!');
						}
						else
						{
							var fileInfo = BXFileStorage.GetObjectDataById(decodeURIComponent(params.externalId));
							console.debug('Object Data', fileInfo, decodeURIComponent(params.externalId));
							if(fileInfo.path)
							{
								BXFileStorage.ObjectOpen(fileInfo.path, function(){});
								break;
							}
						}
					}
					if(BX.type.isString(params.objectId) && params.objectId !== '0')
					{
						if(!('FindPathByPartOfId' in BXFileStorage))
						{
							console.debug('Implement please FindPathByPartOfId!');
						}
						else
						{
							var filePath = BXFileStorage.FindPathByPartOfId(decodeURIComponent(params.objectId));
							console.debug('Object path', filePath, decodeURIComponent(params.objectId));
							if(!!filePath)
							{
								BXFileStorage.ObjectOpen(filePath, function(){});
								break;
							}
						}
					}

					if('EditFile' in BXFileStorage)
					{
						console.debug(params, params.name, decodeURIComponent(params.name));
						//fake!!!
						BitrixDisk.onStartEditingFile(decodeURIComponent(params.name), {}, null, true, true);
						BXFileStorage.EditFile(urlDownload, urlUpload, decodeURIComponent(params.name));
					}
					else
					{
						console.debug('Implement please EditFile!');
					}
					break;
				case 'viewfile':
					console.debug('ShowFile Start', params);
					var urlDownload = '';
					if(params && params.url)
					{
						urlDownload = decodeURIComponent(params.url);
						//urlDownload = addToLinkParam(urlDownload, 'editIn', 'local');
						//urlDownload = addToLinkParam(urlDownload, 'action', 'start');
					}
					else
					{
						console.debug('Mistake in params!');
					}

					if(BX.type.isString(params.externalId) && params.externalId.length > 2)
					{
						if(!('GetObjectDataById' in BXFileStorage))
						{
							console.debug('Implement please GetObjectDataById!');
						}
						else
						{
							var fileInfo = BXFileStorage.GetObjectDataById(decodeURIComponent(params.externalId));
							console.debug('Object Data', fileInfo, decodeURIComponent(params.externalId));
							if(fileInfo.path)
							{
								BXFileStorage.ObjectOpen(fileInfo.path, function(){});
								break;
							}
						}
					}
					if(BX.type.isString(params.objectId) && params.objectId !== '0')
					{
						if(!('FindPathByPartOfId' in BXFileStorage))
						{
							console.debug('Implement please FindPathByPartOfId!');
						}
						else
						{
							var filePath = BXFileStorage.FindPathByPartOfId(decodeURIComponent(params.objectId));
							console.debug('Object path', filePath, decodeURIComponent(params.objectId));
							if(!!filePath)
							{
								BXFileStorage.ObjectOpen(filePath, function(){});
								break;
							}
						}
					}


					if('ViewFile' in BXFileStorage)
					{
						console.debug(params, params.name, decodeURIComponent(params.name));
						//fake!!!
						BitrixDisk.onStartViewingFile(decodeURIComponent(params.name), {}, null, true, true);
						BXFileStorage.ViewFile(urlDownload, decodeURIComponent(params.name));
					}
					else
					{
						console.debug('Implement please ViewFile!');
					}
					break;

			}
		});
	});
});

function addToLinkParam(link, name, value)
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

function setInstalledDisk()
{
	if(alreadyDiskInstall || !urlToDiskAjax)
	{
		return;
	}
	BX.ajax.post(urlToDiskAjax, {
		installDisk: true,
		sessid: BX.bitrix_sessid()
	}, function(res){
		if(res && res.status == 'success')
		{
			alreadyDiskInstall = true;
		}
	});
}
function setUninstalledDisk()
{
	if(!urlToDiskAjax)
	{
		return;
	}
	alreadyDiskInstall = false;

	BX.ajax.post(urlToDiskAjax, {
		uninstallDisk: true,
		sessid: BX.bitrix_sessid()
	}, function(res){});
}

function ReloadWindow()
{
	if(!isInstalledPull)
	{
		NotInstalledPushAndPull();
		return;
	}

	var statusObject = BXFileStorage.GetStatus();
	logDisk(statusObject.status);
	switch(statusObject.status)
	{
		case 'disposed':
		case 'disposing':
		case 'inactive':
			UnshowLoading('disk_connectbutton');
			UnshowLoading('disk_disconnectbutton');
			ShowConnectCont();
			break;
		case 'activating':
			ShowLoading('disk_connectbutton');
			break;
		case 'active':
			UnshowLoading('disk_connectbutton');
			ShowAlreadyConnectCont();
			logDisk('another user?');
			break;
		case 'deactivating':
		case 'connecting':
			ShowLoading('disk_disconnectbutton');
			break;
		case 'online':
			UnshowLoading('disk_connectbutton');
			UnshowLoading('disk_disconnectbutton');
			ShowAlreadyConnectCont();
			break;
		case 'disconnecting':
			ShowLoading('disk_disconnectbutton');
			break;
	}

	if(statusObject && statusObject.error)
	{
		ShowErrorDisk(statusObject.error);
	}
	else
	{
		HideErrorDisk();
	}
}

function NotInstalledPushAndPull()
{
	isInstalledPull = false;
//	BX('disk_main_cont').style.display = 'none';
	HideConnectCont();
	HideAlreadyConnectCont();
	ShowErrorDisk('not_installed_pull');
}

function SetDefaultTargetFolder()
{
	BXFileStorage.SetDefaultTargetFolder(function(data){
		if (!data)
		{
			return;
		}
		if (!data.Error)
		{
//			BX('attach_disk_path').innerHTML = data.Path;
			disk_path = data.Path;
			disk_token = data.Token;
		}
		else
		{
			ShowErrorDisk(data.Kind);
			logDisk(data.Kind);
			if(data.Message) logDisk(data.Message);
		}
	});

}

function HideAlreadyConnectCont()
{
	SetDefaultTargetFolder();
//	BX('disk_already_connect').style.display  = 'none';
}

function HideConnectCont()
{
//	BX('disk_connect_cont').style.display  = 'none';
//	BX('disk_change_path').style.display  = 'none';
}

function ShowConnectCont()
{
	SetDefaultTargetFolder();
	HideAlreadyConnectCont();
//	BX('disk_connect_cont').style.display  = 'block';
//	BX('disk_change_path').style.display  = 'block';
//	BX('disk_add_info').style.display  = 'block';

	return false;
}

function ShowAlreadyConnectCont()
{
//	BX('disk_already_connect').style.display  = 'block';
	HideConnectCont();
//	BX('disk_add_info').style.display  = 'none';

	return false;
}


function OpenFolder()
{
	BXFileStorage.OpenFolder();

	return false;
}

function SelectDisk(success)
{
	success = success || function(){};
	BXFileStorage.SelectTargetFolder(function(data)	{
		if (!data)
		{
			return;
		}
		if (!data.Error)
		{
			BX('attach_disk_path').innerHTML = data.Path;
			disk_path = data.Path;
			disk_token = data.Token;
			success();
		}
		else
		{
			ShowErrorDisk(data.Kind);
			logDisk(data.Kind);
			if(data.Message) logDisk(data.Message);
		}
	});
}

function ReAttachDisk(fb)
{
	BXFileStorage.Detach();
	setTimeout(function(){
		BXFileStorage.Attach(disk_token);

		BX.ajax.post(urlToDiskAjax, {
			reInstallDisk: true,
			sessid: BX.bitrix_sessid()
		}, function(res){
			if(res && res.status == 'success')
			{
				alreadyDiskInstall = true;
			}
			if(BX.type.isFunction(fb))
			{
				fb(res);
			}
		});
	}, 1500);
}

function AttachDisk()
{
	//ShowLoading('disk_connectbutton');
	if(disk_path)
	{
		BXFileStorage.SetTargetFolder(disk_path);
	}
	BXFileStorage.Attach(disk_token);
	setInstalledDisk();
}

function DetachDisk()
{
	//ShowLoading('disk_disconnectbutton');
	BXFileStorage.Detach();
	setUninstalledDisk();
}

function HideErrorDisk()
{
//	var errorCont = BX("disk_error_container");
//	errorCont.style.display  = 'none';
}

function ShowErrorDisk(kind, message)
{
	logDisk(kind);
	if(kind == 'user_cancelled')
	{
		//skip. This is not error.
		return false;
	}

	message = message || BX.message('disk_default');

	//kind
	var errorCont = BX("disk_error_container");
	var errorText = BX("disk_error_text");
	var langMessage = BX.message('disk_' + kind);
	if(langMessage != undefined)
	{
		message = langMessage;
	}

	if(kind == 'not_empty' || kind == 'attach_directory_is_not_empty')
	{
		logDisk(message);
		message = message.replace('#PATH#', disk_path) + ' ' + '<a onclick="SelectDisk(function(){HideErrorDisk(); AttachDisk();}); return false;" href="">' + BX.message('disk_change_dir') + '</a>';
	}
//	errorText.innerHTML = message;
//	errorCont.style.display  = 'block';
}

function ShowLoading(id)
{
	BX.addClass(id, 'wait');
}

function UnshowLoading(id)
{
	BX.removeClass(id, 'wait');
}

function logDisk(a)
{
	console.log('BXDISK: ' + a);
}

//todo remove this. And use BX.Notify.prototype.createNotify
function DiskFormatDate(timestamp)
{
	if (!BX.isAmPmMode())
		var format = [
			["tommorow", "tommorow, H:i"],
			["today", "today, H:i"],
			["yesterday", "yesterday, H:i"],
			["", BX.date.convertBitrixFormat(BX.message("FORMAT_DATETIME"))]
		];
	else
		var format = [
			["tommorow", "tommorow, g:i a"],
			["today", "today, g:i a"],
			["yesterday", "yesterday, g:i a"],
			["", BX.date.convertBitrixFormat(BX.message("FORMAT_DATETIME"))]
		];
	return BX.date.format(format, parseInt(timestamp));
}


function formatNotifyString(action, countFiles, countFolders)
{
	switch(action)
	{
		case 'add':
		case 'update':
		case 'delete':
			break;
		default:
			return '';
	}

	var s = '';
	if(countFiles && countFolders)
	{
		s = BX.message('disk_notify_action_' + action + '_f_d');
	}
	else if(countFiles)
	{
		s = BX.message('disk_notify_action_' + action + '_f');
	}
	else if(countFolders)
	{
		s = BX.message('disk_notify_action_' + action + '_d');
	}


	if(countFiles)
	{
		s = s.replace('#FILE#',
			GetNumericCase(
				countFiles,
				BX.message('disk_notify_file_numeral_1'),
				BX.message('disk_notify_file_numeral_21'),
				BX.message('disk_notify_file_numeral_2_4'),
				BX.message('disk_notify_file_numeral_5_20')
			)
		).replace('#COUNT#', countFiles);
	}

	if(countFolders)
	{
		s = s.replace('#DIR#',
			GetNumericCase(
				countFolders,
				BX.message('disk_notify_dir_numeral_1'),
				BX.message('disk_notify_dir_numeral_21'),
				BX.message('disk_notify_dir_numeral_2_4'),
				BX.message('disk_notify_dir_numeral_5_20')
			)
		).replace('#COUNT#', countFolders);
	}

	return s;
}

function GetNumericCase(number, once, multi_21, multi_2_4, multi_5_20)
{
	if(number == 1) {
		return once;
	}

	if(number < 0) {
		number = -number;
	}

	number %= 100;
	if (number >= 5 && number <= 20) {
		return multi_5_20;
	}

	number %= 10;
	if (number == 1) {
		return multi_21;
	}

	if (number >= 2 && number <= 4) {
		return multi_2_4;
	}

	return multi_5_20;
}
var BitrixDisk = {
	revision: -1,
	needToReAttach: false,

	enabled : false,
	chart : null,
	pathToImages : "",

	mySpace : 0,
	diskSpace : 0,
	freeSpace : 0,

	storageCmdPath : "",

	bxim : null,

	mySlice : null,
	companySlice : null,
	freeSlice : null,

	layout : {
		status : null,
		history : null,
		chart : null,
		freeSpace : null,
		companySpace : null,
		mySpace : null,
		usedSpace : null,
		fullSpace : null,
		spaceStatus : null,
		diskStatus : null,
		connectButton : null,
		lastSync : null,
		lastSyncDate: null,
		diskLoading: null,
		historyContainer: null,
		historyHelp: null,
		historyEmpty: null,
		changeTargetDir: null
	},

	chartData : null,
	chartLoaded : false,

	lastSyncTimestamp : null,

	historyItems : [],

	notifyData: {
		firstInitializeSnapshot: false,
		numberOfFilesLastPackage: null,
		singleFileData: null
	},
	currentUserId: null,

	localEditFiles: {},

	init : function(settings)
	{
		this.revision = parseInt(settings.revision, 10);
		this.needToReAttach = !! parseInt(settings.needToReAttach, 10);

		this.layout.wrap = BX("disk-wrap");

		if (typeof(BX.desktop) == 'undefined' || !BX.desktop.ready())
			return false;

		this.layout.status = BX("disk-status");
		this.layout.history = BX("disk-history");
		this.layout.chart = BX("disk-chart");
		this.layout.chartDefault = BX("disk-chart-default");
		this.layout.diskWorkarea = BX("disk-workarea");

		this.layout.freeSpace = BX("disk-free-space");
		this.layout.companySpace = BX("disk-company-space");
		this.layout.mySpace = BX("disk-my-space");
		this.layout.usedSpace = BX("disk-used-space");
		this.layout.fullSpace = BX("disk-full-space");

		this.layout.spaceStatus = BX("disk-space-status");
		this.layout.diskStatus = BX("disk-status");
		this.layout.connectButton = BX("disk-connect-button");
		this.layout.lastSync = BX("disk-last-sync");
		this.layout.lastSyncDate = BX("disk-last-sync-date");
		this.layout.diskLoading = BX("disk-loading");

		this.layout.historyContainer = BX("disk-history-container");
		this.layout.historyHelp = BX("disk-history-help");
		this.layout.historyEmpty = BX("disk-history-empty");

		this.layout.syncFilesText = BX("disk-number-of-files-text");
		this.layout.syncProgress = BX("disk-progress-bar");
		this.layout.syncCurrentFile = BX("disk-current-file-num");
		this.layout.syncNumberOfFiles = BX("disk-number-of-files");
		this.layout.syncSpeed = BX("disk-progress-speed");

		this.layout.changeTargetDir = BX("disk-change-target-folder");

		this.pathToImages = settings.pathToImages;
		this.storageCmdPath = settings.storageCmdPath || "";

		this.bxim = settings.bxim;
		this.bxim.desktopDisk = this;

		this.currentUserId = settings.currentUserId;

		if (BX.type.isArray(settings.historyItems))
		{
			this.historyItems = settings.historyItems;
			for (var i = 0, length = this.historyItems.length; i < length; i++)
			{
				this.addHistoryItem(this.historyItems[i]);
			}
		}

		this.setLastSync(settings.lastSyncTimestamp);

		this.mySpace = settings.mySpace && settings.mySpace > 0 ? settings.mySpace : 0;
		this.diskSpace = settings.diskSpace && settings.diskSpace > 0 ? settings.diskSpace : 0;
		this.freeSpace = settings.freeSpace && settings.freeSpace > 0 ? settings.freeSpace : 0;

		if (this.diskSpace > 0)
		{
			this.showChart(true);
		}
		else
		{
			this.updateChart();
			this.hideChart();
		}

		this.enabled = BX.type.isBoolean(settings.enabled) ? settings.enabled : this.enabled;
		if (this.enabled)
		{
			this.switchOn();
		}
		else
		{
			this.switchOff();
		}

		BX.desktop.addTab({
			id: "disk",
			title: BX.message("disk_name"),
			order: 130,
			events: {
				open: BX.proxy(function(){
					if (!this.chartLoaded && this.diskSpace > 0)
					{
						this.showChart();
					}
				}, this),
				init: BX.proxy(function() { BX.desktop.setTabContent("disk", this.layout.wrap) }, this),
				close: function() { }
			}
		});

		this.setEvents();

		this.bxim.settingsView.disk = {
			title: BX.message("disk_settings_title"),
			settings: [
				{
					title: BX.message("disk_settings_label_enable"),
					type: "checkbox",
					name: "diskEnabled",
					checked: this.enabled,
					callback: function () {}
				},
				{
					title: BX.message("disk_settings_label_file_click_action"),
					type: "select",
					value: this.getFileClickActionName(),
					name: "fileClickAction",
					items: [
						{title: BX.message("disk_settings_label_file_click_action_open_folder"), value: 'openFolder'},
						{title: BX.message("disk_settings_label_file_click_action_open_file"), value: 'openFile'}
					],
					callback: function () {}
				}
			]
		};

		if(this.needToReAttach)
		{
			this.reAttach();
		}

		BXFileStorage.StartSync();
	},

	isEmptyObject: function(obj)
	{
		if (obj == null) return true;
		if (obj.length && obj.length > 0)
			return false;
		if (obj.length === 0)
			return true;

		for (var key in obj) {
		    if (hasOwnProperty.call(obj, key))
				return false;
		}

		return true;
	},

	reAttach: function()
	{
		ReAttachDisk(BX.delegate(function(){
			BX.desktop.windowReload();
		}, this))
	},

	checkRevision: function(revision)
	{
		revision = parseInt(revision);
		if (typeof(revision) == "number" && this.revision < revision)
		{
			console.debug('NOTICE: Window reload, because REVISION UP ('+this.revision+' -> '+revision+')');
			switch(revision)
			{
				case 2:
					this.revision = revision;
					BX.desktop.windowReload();
					break;
				default:
					this.revision = revision;
					BX.desktop.windowReload();
					break;
			}

			return false;
		}
		return true;
	},

	setEvents : function()
	{
		window.addEventListener("BXFileStorageStatusChanged", function() {
			var statusObject = BXFileStorage.GetStatus();
			switch(statusObject.status)
			{
				case "disposed":
				case "disposing":
				case "inactive":
					BitrixDisk.switchOff();
					break;
				case "activating":
				case "active":
				case "deactivating":
				case "connecting":
					break;
				case "online":
					BitrixDisk.switchOn();
					break;
				case "disconnecting":
					break;
			}
		});

		this.bxim.desktop.addCustomEvent("BXFileStorageStatusSync", BX.proxy(this.onChangeStatusSync, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusStartPackage", BX.proxy(this.onStartPackage, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusStartFile", BX.proxy(this.onStartFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusProgressFile", BX.proxy(this.onProgressFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusFinalFile", BX.proxy(this.onFinalFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusFinalPackage", BX.proxy(this.onFinalPackage, this));

		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusErrorFile", BX.proxy(this.onErrorFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusDeleteFile", BX.proxy(this.onDeleteFile, this));

		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusStartPackage", BX.proxy(this.onStartPackageNotify, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusFinalFile", BX.proxy(this.onFinalFileNotify, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusFinalPackage", BX.proxy(this.onFinalPackageNotify, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusDeleteFile", BX.proxy(this.onDeleteFileNotify, this));

		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusStartFile", BX.proxy(this.onStartEditingFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusFinalFile", BX.proxy(this.onFinalEditingFile, this));

		this.bxim.desktop.addCustomEvent("BXFileStoragePublicLinkStart", BX.proxy(this.onStartExternalLink, this));
		this.bxim.desktop.addCustomEvent("BXFileStoragePublicLinkFinal", BX.proxy(this.onFinalExternalLink, this));

		this.bxim.desktop.addCustomEvent("BXFileStorageLaunchApp", BX.proxy(this.onLaunchApp, this));

		this.bxim.desktop.addCustomEvent("bxSaveSettings", BX.proxy(this.onSaveSettings, this));

		BX.addCustomEvent(this.bxim, "prepareSettingsView", BX.proxy(this.prepareSettingsView, this));
	},

	onStartPackage : function(numberOfFiles, packageSize)
	{
		this.bxim.setLocalConfig("currentSyncFile", 0);
		this.bxim.setLocalConfig("numberOfSyncFiles", numberOfFiles);
		this.bxim.setLocalConfig("syncPackageSize", packageSize);

		this.bxim.setLocalConfig("startPackageTime", (new Date()).getTime());

		this.setProgress(0, numberOfFiles, null, null);

		console.debug(
			this.getSyncTime(),
			"StartPackage:  ",
			"numberOfFiles: ", numberOfFiles + "  ",
			"packageSize: ", BitrixDisk.formatSize(packageSize) + " (" + packageSize + ")"
		);
	},

	onStartFile : function(path, snapshot, fileSize, isDownloaded)
	{
		if(this.isEmptyObject(snapshot))
		{
			return;
		}
		var currentFileNumber = this.bxim.getLocalConfig("currentSyncFile", 0);
		currentFileNumber = currentFileNumber + 1;

		this.bxim.setLocalConfig("currentSyncFile", currentFileNumber);

		var numberOfFiles = this.bxim.getLocalConfig("numberOfSyncFiles", null);
		var packageSize = this.bxim.getLocalConfig("syncPackageSize", null);

		this.setProgress(
			currentFileNumber,
			numberOfFiles,
			this.getProgressPercent(currentFileNumber, numberOfFiles, 0, packageSize),
			null
		);

		console.debug(
			this.getSyncTime(),
			"StartFile:  ",
			path + "  ",
			snapshot,
			BitrixDisk.formatSize(fileSize) + " (" + fileSize + ")" + "  ",
			"isDownloaded: " + isDownloaded
		);
	},

	onProgressFile : function(path, bytes, speed, fileSize)
	{
		BX.desktop.onCustomEvent("sub-BXFileStorageSyncStatusProgressFile", [path, bytes, speed, fileSize]);

		if(this.localEditFiles.hasOwnProperty(path))
		{
			return;
		}

		var currentFileNumber = this.bxim.getLocalConfig("currentSyncFile", null);
		var numberOfFiles = this.bxim.getLocalConfig("numberOfSyncFiles", null);
		var packageSize = this.bxim.getLocalConfig("syncPackageSize", null);

		this.setProgress(
			currentFileNumber,
			numberOfFiles,
			this.getProgressPercent(currentFileNumber, numberOfFiles, bytes, packageSize),
			speed
		);

		console.debug(
			this.getSyncTime(),
			"ProgressFile:  ",
			path + "  ",
			BitrixDisk.formatSize(bytes) + " (" + bytes + ")  ",
			BitrixDisk.formatSize(speed) + "/" + BX.message("disk_speed_seconds") + " (" + speed + ")"
		);
	},

	onFinalFile : function(path, fileData)
	{
		if(this.isEmptyObject(fileData))
		{
			return;
		}

		this.addHistoryItem(fileData);

		console.debug(
			this.getSyncTime(),
			"FinalFile:  ",
			fileData
		);
	},

	onFinalPackage : function()
	{
		this.setLastSync((new Date()).getTime() / 1000);
		this.updateSpaces();

		console.debug(
			this.getSyncTime(),
			"FinalPackage"
		);
	},

	onErrorFile : function()
	{
		console.debug(
			BX.date.format("H:i:s") + "   ",
			"ErrorFile:  ",
			arguments
		);
	},

	onDeleteFile : function(filePath, fileData)
	{
		console.debug("DeleteFile:", filePath, fileData);

		fileData.isDeleted = true;
		fileData.version = (new Date().getTime());
		this.addHistoryItem(fileData);

		this.setLastSync((new Date()).getTime() / 1000);
	},

	onChangeStatusSync : function(changedFiles, changedFolders)
	{
		console.debug('onChangeStatusSync', changedFiles, changedFolders);
		console.debug('firstInitializeSnapshot', this.notifyData.firstInitializeSnapshot);

		if(this.notifyData.firstInitializeSnapshot)
		{
			this.notifyData.firstInitializeSnapshot = false;
			//If run first initialize, then we shown all uploaded files.
			this.showNotifyChangedObjects({
				add: changedFiles.add,
				update: changedFiles.update,
				delete: changedFiles.delete
			}, {
				add: changedFolders.add,
				update: changedFolders.update,
				delete: changedFolders.delete
			});
			return;
		}

		//don't disturb
		if(!this.canShowNotify())
		{
			return;
		}

		//new api BDisk. If run first initialize, then we shown all uploaded files.
		if(changedFiles.hasOwnProperty('nonSelfAdd'))
		{
			if(
				!changedFiles.nonSelfAdd &&
				!changedFiles.nonSelfUpdate &&
				!changedFiles.nonSelfDelete
			)
			{
				//changed only by current user
				return;
			}
			if(changedFiles.nonSelfAdd == 1 || changedFiles.nonSelfUpdate == 1)
			{
				//already shown notify by single file (showNotifySingleFile)
				return;
			}
			this.showNotifyChangedObjects({
				add: changedFiles.nonSelfAdd,
				update: changedFiles.nonSelfUpdate,
				delete: changedFiles.nonSelfDelete
			}, {
				add: changedFolders.nonSelfAdd,
				update: changedFolders.nonSelfUpdate,
				delete: changedFolders.nonSelfDelete
			});
			return;
		}

		this.showNotifyChangedObjects(changedFiles, changedFolders);
	},

	onStartPackageNotify : function(numberOfFiles, packageSize)
	{
		this.notifyData.numberOfFilesLastPackage = numberOfFiles;
		console.debug(
			"onStartPackageNotify"
		);
	},

	onFinalFileNotify: function(path, fileData)
	{
		if(this.isEmptyObject(fileData))
		{
			return;
		}

		if (this.notifyData.numberOfFilesLastPackage == 1)
		{
			this.notifyData.singleFileData = fileData;
		}

		console.debug(
			"onFinalFileNotify "
		);
	},

	onFinalPackageNotify: function()
	{
		if (this.notifyData.numberOfFilesLastPackage == 1 && this.notifyData.singleFileData)
		{
			if(!this.canShowNotify())
			{
				return;
			}
			this.showNotifySingleFile(this.notifyData.singleFileData);
		}

		this.notifyData.numberOfFilesLastPackage = null;
		this.notifyData.singleFileData = null;

		console.debug(
			"FinalPackageNotify"
		);
	},

	onDeleteFileNotify : function()
	{
		console.debug(
			"onDeleteFileNotify"
		);
	},

	onSaveSettings : function(settings)
	{
		settings = settings || {};
		if (settings.diskEnabled)
		{
			if(!this.enabled)
			{
				this.switchOn(true);
			}
		}
		else
		{
			this.switchOff(true);
		}
		if(settings.fileClickAction)
		{
			this.setFileClickActionName(settings.fileClickAction);
		}
	},

	createChart : function()
	{
		if (this.chart)
		{
			return;
		}

		this.chart = new AmCharts.AmPieChart();

		this.chart.radius = 100;
		this.chart.valueField = "value";
		this.chart.titleField = "title";
		this.chart.pulledField = "pulled";
		this.chart.patternField = "pattern";
		this.chart.labelsEnabled = false;
		this.chart.startDuration = 0.3;
		this.chart.startEffect = "easeOutSine";
		this.chart.balloonText = "";

		this.chart.depth3D = 5;

		this.chart.outlineColor = "#000000";
		this.chart.outlineThickness = 1;
		this.chart.outlineAlpha = 0.2;

		this.chart.colors = ["#4a9be8", "#98ca00", "#FFFFFF"];

		this.mySlice = {
			value: this.mySpace,
			pattern : {
				"url" : this.pathToImages + "/texture-blue.png",
				"width": 250,
				"height": 250
			}
		};

		var companySpace = this.diskSpace - this.freeSpace - this.mySpace;
		companySpace = companySpace < 0 ? 0 : companySpace;
		this.companySlice = {
			value: companySpace,
			pattern : {
				"url" : this.pathToImages + "/texture-green.png",
				"width": 250,
				"height": 250
			}
		};

		this.freeSlice = {
			value: this.freeSpace,
			pattern : {
				"url" : this.pathToImages + "/texture.png",
				"width": 250,
				"height": 250
			}
		};

		this.chartData = [this.mySlice, this.companySlice, this.freeSlice];
		this.chart.dataProvider = this.chartData;
	},

	showChart : function(skipWriting)
	{
		BX.removeClass(this.layout.diskWorkarea, "unlimited");

		if (!this.chart)
		{
			this.createChart();
			this.updateChart();
		}

		if (this.chartLoaded === false && this.chart && skipWriting !== true)
		{
			this.chart.write(this.layout.chart);
			this.chartLoaded = true;
		}
	},

	hideChart : function()
	{
		BX.addClass(this.layout.diskWorkarea, "unlimited");
	},

	updateChart : function()
	{
		BXFileStorage.GetStorageSize(BX.proxy(function(data){

			if (data.Error)
			{
				return;
			}

			this.mySpace = data.size;
			this.layout.mySpace.innerHTML = BitrixDisk.formatSize(this.mySpace);

			if (this.chart)
			{
				var companySpace = this.diskSpace - this.freeSpace - this.mySpace;
				companySpace = companySpace < 0 ? 0 : companySpace;

				var usedSpace = this.diskSpace - this.freeSpace;
				usedSpace = usedSpace < 0 ? 0 : usedSpace;

				this.companySlice.value = companySpace;
				this.mySlice.value = this.mySpace;
				this.freeSlice.value = this.freeSpace;

				this.layout.freeSpace.innerHTML = BitrixDisk.formatSize(this.freeSpace);
				this.layout.companySpace.innerHTML = BitrixDisk.formatSize(companySpace);
				this.layout.usedSpace.innerHTML = BitrixDisk.formatSize(usedSpace);
				this.layout.fullSpace.innerHTML = BitrixDisk.formatSize(this.diskSpace);

				this.chart.validateData();
			}

		}, this));
	},

	updateSpaces : function()
	{
		if (this.storageCmdPath.length < 1)
		{
			return;
		}

		BX.ajax({
			method : "POST",
			dataType : "json",
			url : this.storageCmdPath,
			data :  BX.ajax.prepareData({ action : "GetDiskSpace" }),
			onsuccess: BX.proxy(function(result) {
				if (result)
				{
					console.debug(
						"Updated spaces: diskSpace: ",
						BitrixDisk.formatSize(result.diskSpace) + " (" + result.diskSpace + ")  ",
						"freeSpace: ", BitrixDisk.formatSize(result.freeSpace) + " (" + result.freeSpace + ")"
					);

					this.diskSpace = result.diskSpace > 0 ? result.diskSpace : 0;
					this.freeSpace = result.freeSpace > 0 ? result.freeSpace : 0;

					if (this.diskSpace > 0)
					{
						this.showChart();
					}
					else
					{
						this.hideChart();
					}

					this.updateChart();
				}
			}, this)
		});
	},

	getTargetFolder : function()
	{
		var path = BXFileStorage.GetTargetFolder();
		path = path || {};
		return path.Path;
	},

	changeTargetFolder : function()
	{
		SelectDisk();
	},

	switchOn : function(attachDisk)
	{
		this.layout.connectButton.style.display = "none";
		this.layout.historyHelp.style.display = "none";

		if (this.historyItems.length > 0)
		{
			this.layout.historyContainer.style.display = "block";
			this.layout.historyEmpty.style.display = "none";
		}
		else
		{
			this.layout.historyContainer.style.display = "none";
			this.layout.historyEmpty.style.display = "block";
		}

		this.layout.lastSync.style.display = "block";
		this.layout.spaceStatus.style.display = "block";

		this.layout.changeTargetDir.style.display = "none";

		this.layout.diskStatus.innerHTML = BX.message("disk_status_enabled");
		BX.addClass(this.layout.diskStatus.parentNode, "good");

		this.enabled = true;

		if (attachDisk === true)
		{
			this.notifyData.firstInitializeSnapshot = true;
			AttachDisk();
		}
	},

	switchOff : function(detachDisk)
	{
		this.layout.connectButton.style.display = "block";
		this.layout.historyHelp.style.display = "block";

		this.layout.historyContainer.style.display = "none";
		this.layout.historyEmpty.style.display = "none";
		this.layout.lastSync.style.display = "none";
		this.layout.spaceStatus.style.display = "none";
		this.layout.diskLoading.style.display = "none";

		this.layout.changeTargetDir.style.display = "block";

		this.layout.diskStatus.innerHTML = BX.message("disk_status_disabled");
		BX.removeClass(this.layout.diskStatus.parentNode, "good");

		this.enabled = false;
		BX('attach_disk_path').innerHTML = this.getTargetFolder();

		if (detachDisk === true)
		{
			DetachDisk();
		}
	},

	setProgress : function(currentFileNumber, numberOfFiles, percent, speed)
	{
		if (!this.enabled)
		{
			return;
		}

		if (BX.type.isNumber(currentFileNumber))
		{
			this.layout.syncCurrentFile.innerHTML = currentFileNumber;
		}

		if (BX.type.isNumber(numberOfFiles))
		{
			this.layout.syncFilesText.innerHTML = GetNumericCase(
				numberOfFiles,
				BX.message("disk_notify_file_numeral_1"),
				BX.message("disk_notify_file_numeral_21"),
				BX.message("disk_notify_file_numeral_2_4"),
				BX.message("disk_notify_file_numeral_5_20")
			).replace("#COUNT#", numberOfFiles);

			this.layout.syncNumberOfFiles.innerHTML = numberOfFiles;
		}

		if (BX.type.isNumber(percent))
		{
			this.layout.syncProgress.style.width = percent + "%";
		}

		if (speed)
		{
			this.layout.syncSpeed.innerHTML = " " + BitrixDisk.formatSize(speed) + "/" + BX.message("disk_speed_seconds");
		}

		this.layout.lastSync.style.display = "none";
		this.layout.diskLoading.style.display = "block";
	},

	getProgressPercent : function(currentFileNumber, numberOfFiles, fileSize, packageSize)
	{
		var percent = 0;
		if (numberOfFiles == 1)
		{
			percent = Math.floor((fileSize / packageSize) * 100);
		}
		else
		{
			percent = Math.floor((currentFileNumber / numberOfFiles) * 100);
		}

		return Math.min(Math.max(percent, 0), 100);
	},

	setLastSync : function(timestamp)
	{
		if (BX.type.isNumber(timestamp))
		{
			this.lastSyncTimestamp = timestamp;
			this.layout.lastSyncDate.innerHTML = DiskFormatDate(this.lastSyncTimestamp);
		}
		else
		{
			this.layout.lastSyncDate.innerHTML = BX.message("disk_sync_no_date");
		}

		this.layout.lastSync.style.display = "block";
		this.layout.diskLoading.style.display = "none";

		this.bxim.setLocalConfig("lastSyncTimestamp", (new Date()).getTime() / 1000);
	},

	getFileId : function(diskFileId)
	{
		return diskFileId.replace(/\|/g, "_");
	},

	addHistoryItem : function(fileData, animate)
	{
		if (!fileData || !fileData.name || !fileData.path)
		{
			return;
		}

		var extension = fileData.name.split(".").pop().replace(/[^a-z0-9]/ig, "");
		extension = extension.length > 0 && extension.length < 5 ? " history-file-icon-" + extension : "";

		var id = this.getFileId(fileData.id);
		var user = this.bxim.messenger.users[fileData.modifiedBy];

		var avatar = null;
		if (user && BX.type.isNotEmptyString(user.avatar) && user.avatar.indexOf("blank.gif") == -1)
		{
			avatar = BX.create("span", {
				props : { className: "history-author-avatar" },
				style : { backgroundImage : "url('" + user.avatar + "')"}
			});
		}

		var status = "";
		var statusClass = "";
		if (fileData.isDeleted)
		{
			status = BX.message("disk_file_deleted");
			statusClass = "status-deleted";
		}
		else if (fileData.isNew)
		{
			status = BX.message("disk_file_created");
			statusClass = "status-created";
		}
		else
		{
			status = BX.message("disk_file_updated");
			statusClass = "status-updated";
		}

		var authorName = null;
		if (user && BX.type.isNotEmptyString(user.name))
		{
			authorName = BX.create("span", {
				props : { className: "history-author-name" },
				html : user.name
			});
		}
		var itemDate = fileData.version ? fileData.version / 1000 : (new Date()).getTime() / 1000;
		var item = BX.create("li", { props : {  id : id },  children: [
			BX.create("div", {
				props : { className: "history-file" + " " + statusClass},
				children: [
					BX.create("div", {
						props : { className: "history-file-icon" + extension, id : id + "_icon" }
					}),
					BX.create("div", {
						props : { className: "history-file-name" },
						children: [
							(
								fileData.isDeleted
								?
								BX.create("span", {
									props : { className: "history-file-title" },
									html : fileData.name
								})
								:
								BX.create("a", {
									props : { className: "history-file-title", href : fileData.path },
									html : fileData.name,
									events: {
										click: BX.delegate(function(e){
											var action = this.getFileClickAction();
											action(fileData);

											return BX.PreventDefault(e);
										}, this)
									}
								})
							),
							BX.create("span", {
								props : { className: "history-file-status" },
								html : "(" + status + ")"
							})
						]
					})
				]
			}),
			BX.create("div", {
				props : { className: "history-info" },
				children: [
					avatar,
					authorName,
					BX.create("span", {
						props : { className: "history-file-size" },
						html :  BitrixDisk.formatSize(fileData.size)
					}),
					BX.create("span", {
						props : { className: "history-file-date" },
						html : DiskFormatDate(itemDate)
					})
				]
			})
		]});

		this.layout.historyContainer.style.display = "block";
		this.layout.historyEmpty.style.display = "none";

		if (this.layout.history.firstChild)
		{
			this.layout.history.insertBefore(item, this.layout.history.firstChild);
		}
		else
		{
			this.layout.history.appendChild(item);
		}
	},

	formatSize : function(size)
	{
		var sizes = ["b", "Kb", "Mb", "Gb", "Tb"];
		var pos = 0;
		size = parseInt(size, 10);
		while (size >= 1000 && pos < 4)
		{
			size /= 1024;
			pos++;
		}

		return (pos == 0 ? size : size.toFixed(1)) + " " + BX.message("FILE_SIZE_" + sizes[pos]);
	},

	prepareSettingsView : function()
	{
		this.bxim.settingsView.disk.settings[0].checked = this.enabled;
		this.bxim.settingsView.disk.settings[1].value = this.getFileClickActionName();
	},
	openSettings : function()
	{
		this.bxim.settingsView.disk.settings[0].checked = this.enabled;
		this.bxim.settingsView.disk.settings[1].value = this.getFileClickActionName();
		this.bxim.openSettings({
			onlyPanel: "disk",
			minSettingsHeight: 141
		});
	},

	getSyncTime : function()
	{
		var now = (new Date()).getTime();
		var start = this.bxim.getLocalConfig("startPackageTime", null);
		if (start)
		{
			return BX.date.format("H:i:s", now / 1000) + " (" + BX.date.format("i:s", (now - start) / 1000) + ")" + "   ";
		}
		else
		{
			return BX.date.format("H:i:s", now / 1000) + "   ";
		}
	},

	getFileClickAction : function()
	{
		var action = this.getFileClickActionName();

		switch(action)
		{
			case 'openFolder':
				return function(fileData){
					BXFileStorage.FileExist(fileData.path, function(exist){
						if(exist)
						{
							BXFileStorage.OpenFileFolder(fileData.path, function(){});
						}
					});
				};
			case 'openFile':
				return function(fileData){
					BXFileStorage.FileExist(fileData.path, function(exist){
						if(exist)
						{
							BXFileStorage.FileOpen(fileData.path, function(){});
						}
					});
				};
		}
		return function(){};
	},

	getFileClickActionStringJs : function(fileData)
	{
		var action = this.getFileClickActionName();

		switch(action)
		{
			case 'openFolder':
				return 'BXFileStorage.FileExist("' + fileData.path + '", function(exist){' +
					'if(exist)' +
					'BXFileStorage.OpenFileFolder("' + fileData.path + '", function(){});' +
					'})';
			case 'openFile':
				return 'BXFileStorage.FileExist("' + fileData.path + '", function(exist){' +
					'if(exist)' +
					'BXFileStorage.FileOpen("' + fileData.path + '", function(){});' +
					'})';
		}
		return '';
	},

	getFileClickActionName : function()
	{
		var action = this.bxim.getLocalConfig("fileClickAction", {name: 'openFolder'});
		return action.name;
	},

	setFileClickActionName : function(action)
	{
		this.bxim.setLocalConfig("ww", action);
		switch(action)
		{
			case 'openFolder':
			case 'openFile':
				break;
			default:
				action = 'openFolder';
		}
		this.bxim.setLocalConfig("fileClickAction", {name: action});
	},

	canShowNotify: function()
	{
		return this.bxim.settings.status != 'dnd';
	},

	showNotifySingleFile : function(fileData)
	{
		//not shown operation by current user.
		if(fileData.isDirectory || fileData.modifiedBy == this.currentUserId)
		{
			return;
		}

		var user = this.bxim.messenger.users[fileData.modifiedBy];
		var avatar = null;
		if (user && BX.type.isNotEmptyString(user.avatar) && user.avatar.indexOf("blank.gif") == -1)
		{
			avatar = user.avatar;
		}

		var status = BX.message('disk_notify_single_file').replace('#FILENAME#', fileData.name);
		var eventOnClick = 'BX.desktop.windowCommand("close");';
		if (fileData.isDeleted)
		{
			status = status.replace('#OPERATION#', BX.message('disk_notify_single_file_operation_deleted'));

			eventOnClick =
				'BX.desktop.windowCommand("main", "show");' +
				'BX.desktop.onCustomEvent("main", "BXChangeTab", ["disk"]);' +
				'BX.desktop.windowCommand("close");';
		}
		else if (fileData.isNew)
		{
			status = status.replace('#OPERATION#', BX.message('disk_notify_single_file_operation_created'));
			eventOnClick = this.getFileClickActionStringJs(fileData) + '; BX.desktop.windowCommand("close");';
		}
		else
		{
			status = status.replace('#OPERATION#', BX.message('disk_notify_single_file_operation_updated'));
			eventOnClick = this.getFileClickActionStringJs(fileData) + '; BX.desktop.windowCommand("close");';
		}

		var notify = {
			date: (new Date().getTime() / 1000),
			id: "bd-file-" + this.getFileId(fileData.id),
			text: status,
			userLink: user.profile,
			userAvatar: user.avatar || '/bitrix/components/bitrix/webdav.disk/templates/.default/images/disk_34x34.png',
			userName: user.name,
			type: "2"
		};

		var notifyHtml = BX.create("div", {attrs : {'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item"}, children : [
			BX.create('span', {props : { className : "bx-notifier-item-content" }, children : [
				BX.create('span', {props : { className : "bx-notifier-item-avatar" }, children : [
					BX.create('img', {props : { className : "bx-notifier-item-avatar-img" },attrs : {src : notify.userAvatar}})
				]}),
				BX.create("a", {
					attrs: {href: '#', 'data-notifyId': notify.id, 'data-notifyType': notify.type, onclick: 'BX.desktop.windowCommand("close"); return BX.PreventDefault(event);'},
					props: {className: "bx-notifier-item-delete"}
				}),
				BX.create('span', {props : { className : "bx-notifier-item-date" }, html: DiskFormatDate(notify.date)}),
				BX.create('span', {props : { className : "bx-notifier-item-name" }, html: '<a href="'+notify.userLink+'">'+ (typeof(BX.MessengerCommon) != 'undefined'? BX.MessengerCommon.prepareText(notify.userName) : BX.IM.prepareText(notify.userName))+'</a>'}),
				BX.create('span', {props : { className : "bx-notifier-item-text" }, html: notify.text})
			]})
		]});

		var messsageJs =
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'+
			'BX.bind(notify, "click", function(event){ ' + eventOnClick + ' });'+
			'BX.bind(notify, "contextmenu", function(){ ' + eventOnClick + ' });';

		BXDesktopSystem.ExecuteCommand('notification.show.html', this.bxim.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	},

	notifyProgressEdit : function(params)
	{
		params = params || {};
		var isUpload = !!params.isUpload;
		var isCreate = !!params.isCreate;
		var eventOnClick = 'BX.desktop.windowCommand("close");';

		var msg = '';
		if(isCreate)
		{
			msg = BX.message('disk_progress_start_create');
		}
		else
		{
			msg = BX.message(isUpload? 'disk_progress_start_edit_upload' : 'disk_progress_start_edit');
			this.localEditFiles[params.name] = {};
		}

		var notify = {
			date: (new Date().getTime() / 1000),
			id: "bd-file-edit-" + (new Date().getTime() / 1000),
			text: msg,
			userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/webdav.disk/templates/.default/images/disk_34x34.png',
			userName: BX.message('disk_name'),
			type: "2"
		};

		var notifyHtml = BX.create("div", {attrs : {'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item"}, children : [
			BX.create('span', {props : { className : "bx-notifier-item-content" }, children : [
				BX.create('span', {props : { className : "bx-notifier-item-avatar" }, children : [
					BX.create('img', {props : { className : "bx-notifier-item-avatar-img" },attrs : {src : notify.userAvatar}})
				]}),
				BX.create("a", {attrs : {href : '#', 'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item-delete"}}),
				BX.create('span', {props : { className : "bx-notifier-item-text" }, text: params.name}),
				BX.create('div', {style: {marginLeft: '-7px'}, html:
					'<div class="bx-disk-loader-popup-progress-continer">' +
						'<div class="bx-disk-loader-popup-progress-track">' +
							'<div id="bx-disk-loader-popup-progress-line" class="bx-disk-loader-popup-progress-line" style="width:1%"></div>' +
						'</div>' +
						'<div class="bx-disk-loader-popup-speed"><span id="disk-speed"></span></div>' +
					'</div>'
				}),
				BX.create('div', {props : { id: "bx-notifier-item-text", className : "bx-notifier-item-text" }, html: msg})
			]})
		]});

		var messsageJs =
			'BX.desktop.windowCommand("freeze");' +
			'window.name = "notifyProgressEdit";' +
			'window.filePath = "' + BX.util.jsencode(params.name) + '";' +
			'window.disk_speed_seconds = "' + BX.message("disk_speed_seconds") + '";' +
			'window.lastPortion = 0;' +
			'window.anim = null;' +
			'setTimeout(function(){ if(window.lastPortion)return; ' +
				'window.lastPortion = (Math.floor(Math.random() * (12 - 4)) + 4);' +
				'BX.adjust(BX("disk-speed"), {text: window.lastPortion + "%"});' +

				'window.anim = new BX.easing({' +
					'duration : 400,' +
					'start : { width : 0},' +
					'finish : { width : window.lastPortion},' +
					'transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),' +
					'step : function(state) {' +
						'window.lastPortion = state.width;' +
						'BX("bx-disk-loader-popup-progress-line").style.width = state.width + "%";' +
					'},' +
					'complete : BX.delegate(function() {' +
					'}, this)' +
				'});' +
				'window.anim.animate();' +
			' }, 230);' +

			'BX.desktop.addCustomEvent("sub-BXFileStorageSyncStatusProgressFile", function(path, bytes, speed, fileSize){' +
				'if(path !== window.filePath)' +
					'return;' +
				'var portion = Math.floor((bytes/fileSize)*100);' +
				'if(window.lastPortion > portion) { return; };' +

				'window.anim.stop();' +
				'window.anim = new BX.easing({' +
					'duration : 400,' +
					'start : { width : window.lastPortion},' +
					'finish : { width : portion},' +
					'transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),' +
					'step : function(state) {' +
						'window.lastPortion = state.width;' +
						'BX("bx-disk-loader-popup-progress-line").style.width = state.width + "%";' +
					'},' +
					'complete : BX.delegate(function() {' +
					'}, this)' +
				'});' +
				'window.anim.animate();' +

				'window.lastPortion = portion;' +

				'BX.adjust(BX("disk-speed"), {text: portion + "%" + " " + (!!speed? "(" + BitrixDisk.formatSize(speed) + "/" + disk_speed_seconds + ")" : "")});' +
				'BX.style(BX("bx-disk-loader-popup-progress-line"), "width", portion + "%");' +
			'});' +

			'BX.desktop.addCustomEvent("onLaunchApp", function(){BX.adjust(BX("bx-notifier-item-text"), {text: "' + BX.message('disk_progress_start_launch_app') + '"})});' +
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'+
			'BX.bind(notify, "click", function(event){ ' + eventOnClick + ' });' +
			'BX.bind(notify, "contextmenu", function(){ ' + eventOnClick + ' });' +
			'';

		this.currentNotifyWindow = BXDesktopSystem.ExecuteCommand('notification.show.html', this.bxim.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	},

	notifyProgressView : function(params)
	{
		params = params || {};
		var eventOnClick = 'BX.desktop.windowCommand("close");';

		var notify = {
			date: (new Date().getTime() / 1000),
			id: "bd-file-view-" + (new Date().getTime() / 1000),
			text: BX.message('disk_progress_start_view'),
			userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/webdav.disk/templates/.default/images/disk_34x34.png',
			userName: BX.message('disk_name'),
			type: "2"
		};

		var notifyHtml = BX.create("div", {attrs : {'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item"}, children : [
			BX.create('span', {props : { className : "bx-notifier-item-content" }, children : [
				BX.create('span', {props : { className : "bx-notifier-item-avatar" }, children : [
					BX.create('img', {props : { className : "bx-notifier-item-avatar-img" },attrs : {src : notify.userAvatar}})
				]}),
				BX.create("a", {attrs : {href : '#', 'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item-delete"}}),
				BX.create('span', {props : { className : "bx-notifier-item-text" }, text: params.name}),
				BX.create('div', {style: {marginLeft: '-7px'}, html:
					'<div class="bx-disk-loader-popup-progress-continer">' +
						'<div class="bx-disk-loader-popup-progress-track">' +
							'<div id="bx-disk-loader-popup-progress-line" class="bx-disk-loader-popup-progress-line" style="width:1%"></div>' +
						'</div>' +
						'<div class="bx-disk-loader-popup-speed"><span id="disk-speed"></span></div>' +
					'</div>'
				}),
				BX.create('div', {props : { id: "bx-notifier-item-text", className : "bx-notifier-item-text" }, html: BX.message('disk_progress_start_view')})
			]})
		]});

		var messsageJs =
			'BX.desktop.windowCommand("freeze");' +
			'window.name = "notifyProgressEdit";' +
			'window.filePath = "' + BX.util.jsencode(params.name) + '";' +
			'window.disk_speed_seconds = "' + BX.message("disk_speed_seconds") + '";' +
			'window.lastPortion = 0;' +
			'window.anim = null;' +
			'setTimeout(function(){ if(window.lastPortion)return; ' +
				'window.lastPortion = (Math.floor(Math.random() * (12 - 4)) + 4);' +
				'BX.adjust(BX("disk-speed"), {text: window.lastPortion + "%"});' +

				'window.anim = new BX.easing({' +
					'duration : 400,' +
					'start : { width : 0},' +
					'finish : { width : window.lastPortion},' +
					'transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),' +
					'step : function(state) {' +
						'window.lastPortion = state.width;' +
						'BX("bx-disk-loader-popup-progress-line").style.width = state.width + "%";' +
					'},' +
					'complete : BX.delegate(function() {' +
					'}, this)' +
				'});' +
				'window.anim.animate();' +
			' }, 230);' +

			'BX.desktop.addCustomEvent("sub-BXFileStorageSyncStatusProgressFile", function(path, bytes, speed, fileSize){' +
				'if(path !== window.filePath)' +
					'return;' +
				'var portion = Math.floor((bytes/fileSize)*100);' +
				'if(window.lastPortion > portion) { return; };' +

				'window.anim.stop();' +
				'window.anim = new BX.easing({' +
					'duration : 400,' +
					'start : { width : window.lastPortion},' +
					'finish : { width : portion},' +
					'transition : BX.easing.makeEaseOut(BX.easing.transitions.quad),' +
					'step : function(state) {' +
						'window.lastPortion = state.width;' +
						'BX("bx-disk-loader-popup-progress-line").style.width = state.width + "%";' +
					'},' +
					'complete : BX.delegate(function() {' +
					'}, this)' +
				'});' +
				'window.anim.animate();' +

				'window.lastPortion = portion;' +

				'BX.adjust(BX("disk-speed"), {text: portion + "%" + " " + (!!speed? "(" + BitrixDisk.formatSize(speed) + "/" + disk_speed_seconds + ")" : "")});' +
				'BX.style(BX("bx-disk-loader-popup-progress-line"), "width", portion + "%");' +
			'});' +

			'BX.desktop.addCustomEvent("onLaunchApp", function(){BX.adjust(BX("bx-notifier-item-text"), {text: "' + BX.message('disk_progress_start_launch_app') + '"})});' +
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'+
			'BX.bind(notify, "click", function(event){ ' + eventOnClick + ' });' +
			'BX.bind(notify, "contextmenu", function(){ ' + eventOnClick + ' });' +
			'';

		this.currentNotifyWindow = BXDesktopSystem.ExecuteCommand('notification.show.html', this.bxim.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	},

	notifyProgressLaunchApp : function(params)
	{
		params = params || {};
		var eventOnClick = 'BX.desktop.windowCommand("close");';

		var notify = {
			date: (new Date().getTime() / 1000),
			id: "bd-file-view-" + (new Date().getTime() / 1000),
			text: BX.message('disk_progress_start_launch_app'),
			userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/webdav.disk/templates/.default/images/disk_34x34.png',
			userName: BX.message('disk_name'),
			type: "2"
		};

		var notifyHtml = BX.create("div", {attrs : {'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item"}, children : [
			BX.create('span', {props : { className : "bx-notifier-item-content" }, children : [
				BX.create('span', {props : { className : "bx-notifier-item-avatar" }, children : [
					BX.create('img', {props : { className : "bx-notifier-item-avatar-img" },attrs : {src : notify.userAvatar}})
				]}),
				BX.create("a", {attrs : {href : '#', 'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item-delete"}}),
				BX.create('span', {props : { className : "bx-notifier-item-text" }, text: params.name}),
				BX.create('div', {style: {marginLeft: '50px'}, children: [BX.create('img', {props: {src: '/bitrix/components/bitrix/webdav.disk/templates/.default/images/popups-alert-mov.gif'}})]}),
				BX.create('div', {props : { className : "bx-notifier-item-text" }, html: BX.message('disk_progress_start_launch_app')})
			]})
		]});

		var messsageJs =
			'window.name = "notifyProgressLaunchApp";' +
			'setTimeout(function(){BX.desktop.windowCommand("close");}, 1000);' +
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'+
			'BX.bind(notify, "click", function(event){ ' + eventOnClick + ' });'+
			'BX.bind(notify, "contextmenu", function(){ ' + eventOnClick + ' });';

		this.currentNotifyWindow = BXDesktopSystem.ExecuteCommand('notification.show.html', this.bxim.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	},

	notifyProgressExtLink : function(params)
	{
		params = params || {};
		var isFinish = !!params.isFinish;
		var eventOnClick = 'BX.desktop.windowCommand("close");';

		var notify = {
			date: (new Date().getTime() / 1000),
			id: "bd-file-edit-" + (new Date().getTime() / 1000),
			text: BX.message(isFinish? 'disk_progress_finish_extlink' : 'disk_progress_start_extlink'),
			userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/webdav.disk/templates/.default/images/disk_34x34.png',
			userName: BX.message('disk_name'),
			type: "2"
		};

		var notifyHtml = BX.create("div", {attrs : {'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item"}, children : [
			BX.create('span', {props : { className : "bx-notifier-item-content" }, children : [
				BX.create('span', {props : { className : "bx-notifier-item-avatar" }, children : [
					BX.create('img', {props : { className : "bx-notifier-item-avatar-img" },attrs : {src : notify.userAvatar}})
				]}),
				BX.create("a", {attrs : {href : '#', 'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item-delete"}}),
				(isFinish? null : BX.create('div', {style: {marginLeft: '50px'}, children: [BX.create('img', {props: {src: '/bitrix/components/bitrix/webdav.disk/templates/.default/images/popups-alert-mov.gif'}})]})),

				(!isFinish? null : BX.create('span', {props : { className : "bx-notifier-item-name" }, html: '<a href="'+notify.userLink+'">'+notify.userName+'</a>'})),
				BX.create('span', {props : { className : "bx-notifier-item-text" }, html: notify.text})
			]})
		]});

		var messsageJs =
			'window.name = "notifyProgressExtLink";' +
			(isFinish? 'setTimeout(function(){BX.desktop.windowCommand("close");}, 2500);' : 'BX.desktop.windowCommand("freeze");') +
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'+
			'BX.bind(notify, "click", function(event){ ' + eventOnClick + ' });'+
			'BX.bind(notify, "contextmenu", function(){ ' + eventOnClick + ' });';

		this.currentNotifyWindow = BXDesktopSystem.ExecuteCommand('notification.show.html', this.bxim.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	},

	onStartViewingFile : function(path, snapshot, fileSize, isDownloaded, fake)
	{
		console.debug(arguments);
		fake = fake || false;
		console.debug(
			this.getSyncTime(),
			"StartViewingFile:  ",
			path + "  "
		);

		//we skip process download (EditFile). And we skip real bdisk progress with exist file
		if(!this.isEmptyObject(snapshot))
		{
			return;
		}
		var lastWindow = this.getLastWindow('notifyProgressView');
		if(lastWindow)
		{
			BX.desktop.windowCommand(lastWindow, 'close');
		}

		this.notifyProgressView({
			name: path
		});
	},

	getLastWindow: function(name)
	{
		try
		{
			var lastWindow = BXWindows.slice(-1);
			if(!!lastWindow)
			{
				if(!!lastWindow[0])
				{
					if(lastWindow[0].name == name)
					{
						return lastWindow[0];
					}

					for (var i in BXWindows) {
						if (!BXWindows.hasOwnProperty(i)) {
							continue;
						}
						if(BXWindows[i].name == name)
						{
							return BXWindows[i];
						}
					}
				}
			}
		}
		catch (e)
		{}

		return false;
	},

	getLastWindowByPrefix: function(prefix)
	{
		try
		{
			var lastWindow = BXWindows.slice(-1);
			if(!!lastWindow)
			{
				if(!!lastWindow[0])
				{
					if(lastWindow[0].name.search(prefix) != -1)
					{
						return lastWindow[0];
					}

					for (var i in BXWindows) {
						if (!BXWindows.hasOwnProperty(i)) {
							continue;
						}
						if(BXWindows[i].name.search(prefix) != -1)
						{
							return BXWindows[i];
						}
					}

				}
			}
		}
		catch (e)
		{}

		return false;
	},

	onStartEditingFile : function(path, snapshot, fileSize, isDownloaded, fake, isCreated)
	{
		console.debug(arguments);
		fake = fake || false;
		console.debug(
			this.getSyncTime(),
			"StartEditingFile:  ",
			path + "  "
		);

		//we skip process download (EditFile). And we skip real bdisk progress with exist file
		if(!this.isEmptyObject(snapshot))
		{
			return;
		}

		if(!!isCreated)
		{
			var lastWindow = this.getLastWindow('notifyProgressEdit');
			if(lastWindow)
			{
				BX.desktop.windowCommand(lastWindow, 'close');
			}

			this.notifyProgressEdit({
				name: path,
				isCreate: true
			});
			return;
		}

		if(!fake && !isDownloaded)
		{
			var lastWindow = this.getLastWindow('notifyProgressEdit');
			if(lastWindow)
			{
				BX.desktop.windowCommand(lastWindow, 'close');
			}

			this.notifyProgressEdit({
				name: path,
				isUpload: !isDownloaded
			});
		}
		if(fake && isDownloaded)
		{
			var lastWindow = this.getLastWindow('notifyProgressEdit');
			if(lastWindow)
			{
				BX.desktop.windowCommand(lastWindow, 'close');
			}

			this.notifyProgressEdit({
				name: path,
				isUpload: !isDownloaded
			});
		}
	},

	onLaunchApp : function()
	{
		console.debug(
			this.getSyncTime(),
			"LaunchApp:  "
		);
		BX.desktop.onCustomEvent("onLaunchApp", []);
	},

	onFinalEditingFile : function(path, fileData)
	{
		if(this.isEmptyObject(fileData))
		{
			delete this.localEditFiles[path];
		}

		//fake final size for drawing progress bar in notify
		BX.desktop.onCustomEvent("sub-BXFileStorageSyncStatusProgressFile", [path, 100, 0, 100]);
		setTimeout(BX.delegate(function(){
			var lastWindow = this.getLastWindow('notifyProgressEdit');
			if(lastWindow)
			{
				BX.desktop.windowCommand(lastWindow, 'close');
			}
			lastWindow = this.getLastWindow('notifyProgressView');
			if(lastWindow)
			{
				BX.desktop.windowCommand(lastWindow, 'close');
			}
		}, this), 1000);

		console.debug(
			this.getSyncTime(),
			"FinalEditingFile:  ",
			path + "  "
		);
	},

	onStartExternalLink : function()
	{
		var lastWindow = this.getLastWindow('notifyProgressExtLink');
		if(lastWindow)
		{
			BX.desktop.windowCommand(lastWindow, 'close');
		}

		this.notifyProgressExtLink();
		console.debug(
			this.getSyncTime(),
			"StartExternalLink:  "
		);
	},

	onFinalExternalLink : function()
	{
		setTimeout(BX.delegate(function(){
		var lastWindow = this.getLastWindow('notifyProgressExtLink');
		if(lastWindow)
		{
			BX.desktop.windowCommand(lastWindow, 'close');
		}

		this.notifyProgressExtLink({
			isFinish: true
		});
		console.debug(
			this.getSyncTime(),
			"FinalExternalLink:  "
		);

		}, this), 300)
	},

	showNotifyChangedObjects : function(changedFiles, changedFolders)
	{
		var text = '';
		if(changedFiles.add && changedFiles.add > 0 || changedFolders.add && changedFolders.add > 0)
		{
			text += formatNotifyString('add', changedFiles.add || 0, changedFolders.add || 0)  + '<br>';
		}
		if(changedFiles.update && changedFiles.update > 0 || changedFolders.update && changedFolders.update > 0)
		{
			text += formatNotifyString('update', changedFiles.update || 0, changedFolders.update || 0)  + '<br>';
		}
		if(changedFiles.delete && changedFiles.delete > 0 || changedFolders.delete && changedFolders.delete > 0)
		{
			text += formatNotifyString('delete', changedFiles.delete || 0, changedFolders.delete || 0)  + '<br>';
		}

		var notify = {
			date: (new Date().getTime() / 1000),
			id: "bd" + (new Date().getTime() / 1000),
			text: text,
			userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/webdav.disk/templates/.default/images/disk_34x34.png',
			userName: BX.message('disk_name'),
			type: "2"
		};

		var notifyHtml = BX.create("div", {attrs : {'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item"}, children : [
			BX.create('span', {props : { className : "bx-notifier-item-content" }, children : [
				BX.create('span', {props : { className : "bx-notifier-item-avatar" }, children : [
					BX.create('img', {props : { className : "bx-notifier-item-avatar-img" },attrs : {src : notify.userAvatar}})
				]}),
				BX.create("a", {
					attrs: {href: '#', 'data-notifyId': notify.id, 'data-notifyType': notify.type, onclick: 'BX.desktop.windowCommand("close"); return BX.PreventDefault(event);'},
					props: {className: "bx-notifier-item-delete"}
				}),
				BX.create('span', {props : { className : "bx-notifier-item-date" }, html: DiskFormatDate(notify.date)}),
				BX.create('span', {props : { className : "bx-notifier-item-name" }, html: '<a href="'+notify.userLink+'">'+notify.userName+'</a>'}),
				BX.create('span', {props : { className : "bx-notifier-item-text" }, html: notify.text})
			]})
		]});

		var messsageJs =
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'+
			'BX.bind(notify, "click", function(event){ BXDesktopSystem.GetMainWindow().ExecuteCommand("show"); BX.desktop.windowCommand("close"); document.location.href = "bx://openDiskTab" });'+
			'BX.bind(notify, "contextmenu", function(){ BXDesktopSystem.GetMainWindow().ExecuteCommand("show"); BX.desktop.windowCommand("close"); });';

		BXDesktopSystem.ExecuteCommand('notification.show.html', BXIM.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	}
};
