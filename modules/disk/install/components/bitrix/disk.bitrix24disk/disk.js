BX.ready(() => {
	if (BX.Reflection.getClass('BX.Messenger.v2.Lib.DesktopApi'))
	{
		const loadDiskIntegration = () => {
			BX.loadExt('disk.bitrix24disk-integration').then(() => {
				BX.Disk.Bitrix24Disk.BxLinkHandler.init();
			});
		};

		if (BX.Messenger.v2.Lib.DesktopApi.isDesktop())
		{
			loadDiskIntegration();
		}
		else
		{
			BX.Messenger.v2.Lib.DesktopApi.subscribe('onDesktopInit', () => {
				loadDiskIntegration();
			});
		}
	}
});

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
		updateDiskUsage()

		BXIM.desktop.addCustomEvent("BXProtocolUrl", function(command, params) {

			params = params || {};
			command = command.toLowerCase();
			var filePath, urlDownload, urlUpload;

			var checkBDiskIsConnected = function (uidRequest) {
				console.debug('checkBDiskIsConnected', BXFileStorage.GetStatus().status);
				if (BXFileStorage.GetStatus().status === "inactive")
				{
					BitrixDisk.showSwitchOnBDisk();
				}

				if (BX.getClass('BX.PULL.isPublishingEnabled') && BX.PULL.isPublishingEnabled() && BX.PULL.isConnected())
				{
					BX.PULL.sendMessage([BX.message('USER_ID')], 'disk', 'bdisk', {
						status: BXFileStorage.GetStatus().status,
						uidRequest: uidRequest
					});
				}
				else
				{
					BX.ajax.runAction('disk.documentService.setStatusWorkWithLocalDocument', {
						data: {
							status: BXFileStorage.GetStatus().status,
							uidRequest: uidRequest
						}
					});
				}
			};

			switch(command)
			{
				case 'v2openfile':

					checkBDiskIsConnected(params.uidRequest);
					console.debug('v2openfile', params);

					if (params.objectId)
					{
						filePath = BXFileStorage.FindPathByPartOfId('|f' + decodeURIComponent(params.objectId));
						console.debug('Object path', filePath, decodeURIComponent(params.objectId));
					}

					BXFileStorage.FileExist(filePath, function(exist) {
						if (exist && filePath)
						{
							BXFileStorage.ObjectOpen(filePath, function () {});
						}
						else if(params.url)
						{
							urlDownload = decodeURIComponent(params.url);
							urlUpload = urlDownload = addToLinkParam(urlDownload, 'editIn', 'l');

							urlDownload = addToLinkParam(urlDownload, 'action', 'start');
							urlUpload = addToLinkParam(urlDownload, 'action', 'commit');
							urlUpload = addToLinkParam(urlDownload, 'primaryAction', 'commit');

							console.debug(params, params.name, decodeURIComponent(params.name));
							//fake!!!
							BitrixDisk.onStartEditingFile(decodeURIComponent(params.name), {}, null, true, true);
							BXFileStorage.EditFile(urlDownload, urlUpload, decodeURIComponent(params.name));
						}
					});

					break;

				case 'v2viewfile':

					checkBDiskIsConnected();
					console.debug('v2viewFile', params);

					if (params.objectId)
					{
						filePath = BXFileStorage.FindPathByPartOfId('|f' + decodeURIComponent(params.objectId));
						console.debug('Object path', filePath, decodeURIComponent(params.objectId));
					}

					if(filePath)
					{
						BXFileStorage.ObjectOpen(filePath, function(){});
					}
					else if(params.url)
					{
						urlDownload = decodeURIComponent(params.url);

						console.debug(params, params.name, decodeURIComponent(params.name));
						//fake!!!
						BitrixDisk.onStartViewingFile(decodeURIComponent(params.name), {}, null, true, true);
						BXFileStorage.ViewFile(urlDownload, decodeURIComponent(params.name));
					}
					break;

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
						//BX.desktop.syncPause(true);
						BXFileStorage.EditFile(urlDownload, urlUpload, decodeURIComponent(params.name));
						//BX.desktop.syncPause(false);
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
							var filePath = BXFileStorage.FindPathByPartOfId('|f' + decodeURIComponent(params.objectId));
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
						//BX.desktop.syncPause(true);
						BXFileStorage.EditFile(urlDownload, urlUpload, decodeURIComponent(params.name));
						//BX.desktop.syncPause(false);
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
							var filePath = BXFileStorage.FindPathByPartOfId('|f' + decodeURIComponent(params.objectId));
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
						//BX.desktop.syncPause(true);
						BXFileStorage.ViewFile(urlDownload, decodeURIComponent(params.name));
						//BX.desktop.syncPause(false);
					}
					else
					{
						console.debug('Implement please ViewFile!');
					}
					break;

			}
		});
	});

	BX.addCustomEvent('onImSettingsTabShow', function(name){
		if(name != 'disk')
			return;

		if(!window.BXFileStorage)
			return;

		if(window.name != 'settings')
			return;

		setTimeout(function(){
			BitrixDiskSettings = new BX.Disk.Desktop.Settings({
				bxim: BXIM,
				diskEnabled: typeof(BXFileStorage) == 'undefined'? false : BXFileStorage.GetStatus().status == "online"
			});
			BitrixDiskSettings.fillFolderList();
			BX.desktop.resize();

		}, 120);
	});

	if (!BX.desktop.enableInVersion(42))
	{
		BX.addCustomEvent(window, 'prepareSettingsView', function () {
			if(!window.BXFileStorage)
				return;

			if(window.name != 'settings')
				return;

			BitrixDiskSettings = new BX.Disk.Desktop.Settings({
				bxim: BXIM,
				diskEnabled: typeof(BXFileStorage) == 'undefined'? false : BXFileStorage.GetStatus().status == "online"
			});
			BitrixDiskSettings.prepareSettingsView();
		});
	}
});

function updateDiskUsage()
{
	if (BXDesktopSystem && BXDesktopSystem.GetAccountData)
	{
		var size = BXDesktopSystem.GetAccountData(BXDesktopWindow.GetBDiskServer(), BXDesktopWindow.GetLogin(), "bxd_st_size");
		BX.ajax.post('/desktop_app/storage.php?action=updateDiskUsage', {
			size: size,
			sessid: BX.bitrix_sessid()
		});

	}
}

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
		SITE_ID: BX.message('SITE_ID'),
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
		SITE_ID: BX.message('SITE_ID'),
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
			SITE_ID: BX.message('SITE_ID'),
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
	pathTemplateToRestoreObject : "",

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
		lastSyncComment: null,
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

	progressCheckPointByFile : {},

	notifyData: {
		badgeCount: 0,
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
		this.layout.lastSyncComment = BX("disk-last-sync-comment");
		this.layout.diskLoading = BX("disk-loading");

		this.layout.historyContainer = BX("disk-history-container");
		this.layout.historyHelp = BX("disk-history-help");
		this.layout.historyEmpty = BX("disk-history-empty");

		this.layout.syncFilesText = BX("disk-number-of-files-text");
		this.layout.syncContainerFilesText = BX("disk-current-file-nums-container");
		this.layout.syncProgress = BX("disk-progress-bar");
		this.layout.syncCurrentFile = BX("disk-current-file-num");
		this.layout.syncNumberOfFiles = BX("disk-number-of-files");
		this.layout.syncSpeed = BX("disk-progress-speed");
		this.layout.syncEstimatedTime = BX("disk-progress-estimated-time");
		this.layout.syncStopButton = BX("disk-btn-sync-stop");
		this.layout.syncContainerButton = BX("disk-btn-sync-container");
		this.layout.syncStartButton = BX("disk-btn-sync-start");

		this.layout.changeTargetDir = BX("disk-change-target-folder");

		this.pathToImages = settings.pathToImages;
		this.pathTemplateToRestoreObject = settings.pathTemplateToRestoreObject;
		this.storageCmdPath = settings.storageCmdPath || "";

		this.bxim = settings.bxim;
		this.bxim.desktopDisk = this;

		this.enableShowingNotify = settings.enableShowingNotify;

		this.currentUserId = settings.currentUserId;

		if (BX.type.isArray(settings.historyItems))
		{
			this.historyItems = settings.historyItems;
			for (var i = 0, length = this.historyItems.length; i < length; i++)
			{
				this.addHistoryItem(this.historyItems[i]);
			}
		}

		this.setLastSync(settings.lastSyncTimestamp, true);

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
			target: false,
			events: {
				open: BX.proxy(function(){
					BXDesktopSystem.DiskMessage('{"action":"show_window","host":"' + location.host + '","protocol":"' + location.protocol + '"}');
				}, this),
			}
		});

		if (!BX.Reflection.getClass('BX.Messenger.v2.Lib.DesktopApi'))
		{
			console.warn('BX.Messenger.v2.Lib.DesktopApi is not defined');
			this.setEvents();
		}

		if (!BX.desktop.enableInVersion(42))
		{
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
		}

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

		BX.bind(BX(this.layout.syncStopButton), 'click', BX.delegate(this.onSyncManipulateButtonClick, this));
		BX.bind(BX(this.layout.syncStartButton), 'click', BX.delegate(this.onSyncManipulateButtonClick, this));
		BX.addCustomEvent(window, "onDesktopSyncPause", BX.proxy(this.onSyncPauseEventFromDesktop, this));

		BX.bindDelegate(this.layout.historyContainer, "dblclick", {className: 'history_error_container'}, BX.delegate(this.onClickErrorEntry, this));

		this.bxim.desktop.addCustomEvent("BXFileStorageStatusSync", BX.proxy(this.onChangeStatusSync, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusStartPackage", BX.proxy(this.onStartPackage, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusStartFile", BX.proxy(this.onStartFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusProgressFile", BX.proxy(this.onProgressFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusFinalFile", BX.proxy(this.onFinalFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusFinalPackage", BX.proxy(this.onFinalPackage, this));

		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusErrorFile", BX.proxy(this.onErrorFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusConflict", BX.proxy(this.onConflictFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusDeleteFile", BX.proxy(this.onDeleteFile, this));
		this.bxim.desktop.addCustomEvent("BXFileStorageSyncStatusRenameFile", BX.proxy(this.onRenameFile, this));

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

		BX.addCustomEvent("onPullEvent-disk", BX.delegate(function(command, params) {
			params = params || {};
			if(command !== 'bdisk')
			{
				return;
			}
			console.debug(
				this.getSyncTime(),
				"onPullEvent:  ",
				"command: ", command + "  ",
				"params: ", params
			);
			switch (params.action)
			{
				case 'repair':
				case 'check':
					break;
			}
		}, this));

	},

	onClickErrorEntry: function(e)
	{
		var spanWithError = e.srcElement;
		if(!spanWithError || spanWithError.tagName.toUpperCase() !== 'SPAN')
		{
			return;
		}

		if(!e.ctrlKey)
		{
			return;
		}
		var errorText = spanWithError.getAttribute('bx-disk-error-data');
		if(errorText)
		{
			var modalWindow = new BX.PopupWindow('bx-disk-error-data', spanWithError, {
				content: errorText,
				closeByEsc: true,
				closeIcon: false,
				autoHide: true,
				events: {
					onPopupClose: function(){ this.destroy(); }
				}
			});
			modalWindow.show();
		}
	},

	onSyncPauseEventFromDesktop: function(syncStatus)
	{
		var targetElement;
		if(!syncStatus)
		{
			//state is pause
			targetElement = BX('disk-btn-sync-stop');
			this.layout.lastSyncComment.innerHTML = BX.message("disk_last_sync_paused_comment");
			this.showContainerWithSyncButtons();
			this.setPauseTabIcon();
		}
		else
		{
			targetElement = BX('disk-btn-sync-start');
			this.layout.lastSyncComment.innerHTML = '';
			this.resetTabIcon();
		}

		if(targetElement.id === 'disk-btn-sync-stop')
		{
			BX('disk-btn-sync-start').style.display = 'inline-block';
		}
		else if(targetElement.id === 'disk-btn-sync-start')
		{
			BX('disk-btn-sync-stop').style.display = 'inline-block';
			//BX.show(BX('disk-btn-sync-stop'), 'inline-block');
		}
		BX.hide(targetElement);

		console.debug(
			this.getSyncTime(),
			"onSyncPauseEventFromDesktop:  ",
			syncStatus
		);
	},

	onSyncManipulateButtonClick: function(e)
	{
		var targetElement = e.currentTarget;
		if(!targetElement)
		{
			return;
		}
		BX.hide(targetElement);
		if(targetElement.id === 'disk-btn-sync-stop')
		{
			BX.desktop.syncPause(true, true);
		}
		else if(targetElement.id === 'disk-btn-sync-start')
		{
			BX.desktop.syncPause(false, true);
		}
	},

	onStartPackage : function(numberOfFiles, packageSize, detailedData)
	{
		if(numberOfFiles <= 0)
		{
			return;
		}
		detailedData = detailedData || {};
		this.bxim.setLocalConfig("currentSyncFile", 0);
		this.bxim.setLocalConfig("numberOfSyncFiles", numberOfFiles);
		this.bxim.setLocalConfig("syncPackageSize", packageSize);
		this.bxim.setLocalConfig("syncPackageSizeDynamic", 0);

		this.bxim.setLocalConfig("startPackageTime", (new Date()).getTime());

		this.setProgress(0, numberOfFiles, null, null);

		this.setWorkingTabIcon(detailedData.upload);

		console.debug(
			this.getSyncTime(),
			"StartPackage:  ",
			"numberOfFiles: ", numberOfFiles + "  ",
			"detailedData: ", detailedData,
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
		this.bxim.setLocalConfig("currentSyncSize", 0);

		var numberOfFiles = this.bxim.getLocalConfig("numberOfSyncFiles", null);
		var packageSize = this.bxim.getLocalConfig("syncPackageSize", null);

		this.setProgress(
			currentFileNumber,
			numberOfFiles,
			this.getProgressPercent(currentFileNumber, numberOfFiles, 0, packageSize),
			null
		);

		this.addHistoryItem(this.prepareDataToHistoryItem({
			path: path, snapshot: snapshot, fileSize: fileSize, isDownloaded: isDownloaded, withProgressBar: true
		}));

		this.storeProgressCheckPointByFile(path, {size: fileSize});

		console.debug(
			this.getSyncTime(),
			"StartFile:  ",
			path + "  ",
			snapshot,
			BitrixDisk.formatSize(fileSize) + " (" + fileSize + ")" + "  ",
			"isDownloaded: " + isDownloaded
		);
	},

	onProgressFile : function(path, bytes, speed, partFileSize)
	{
		BX.desktop.onCustomEvent("sub-BXFileStorageSyncStatusProgressFile", [path, bytes, speed, partFileSize]);

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

		var realPartFileSize = bytes - this.bxim.getLocalConfig("currentSyncSize", 0);
		this.bxim.setLocalConfig("currentSyncSize", bytes);
		var sizeToWork = this.bxim.getLocalConfig("syncPackageSizeDynamic") + realPartFileSize;
		this.bxim.setLocalConfig("syncPackageSizeDynamic", sizeToWork);

		var estimatedTimeByCurrentFile = this.setHistoryItemProgress(
			path,
			this.getHistoryFileProgressBarIdFromPath(path),
			this.getProgressPercent(currentFileNumber, 1, bytes, this.getProgressCheckPointByFile(path).size),
			speed
		);

		this.setEstimatedTime(Math.floor((sizeToWork / this.bxim.getLocalConfig("syncPackageSize", 0)) * 100), estimatedTimeByCurrentFile);

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

		var idHistoryFileProgressBarIdFromPath = this.getHistoryFileProgressBarIdFromPath(path);
		this.setHistoryItemProgress(
			path,
			idHistoryFileProgressBarIdFromPath,
			100
		);
		this.deleteProgressCheckPointByFile(path);
		setTimeout(function (idHistoryFileProgressBarIdFromPath){
			return function ()
			{
				var cont = BX(idHistoryFileProgressBarIdFromPath);
				if (cont) {
					BX.hide(cont);
				}
			}
		}(idHistoryFileProgressBarIdFromPath), 200);

		var realPartFileSize = fileData.size - this.bxim.getLocalConfig("currentSyncSize", 0);
		var sizeToWork = this.bxim.getLocalConfig("syncPackageSizeDynamic") + realPartFileSize;
		this.bxim.setLocalConfig("syncPackageSizeDynamic", sizeToWork);
		this.setEstimatedTime(Math.floor((sizeToWork / this.bxim.getLocalConfig("syncPackageSize", 0)) * 100));

		//this.addHistoryItem(fileData);

		var progressContainer = BX(idHistoryFileProgressBarIdFromPath);
		if(fileData.version && progressContainer)
		{
			//fix date
			var dateNode = BX.findChild(progressContainer.parentNode, {className: 'history-file-date'}, 4);
			if(dateNode)
			{
				dateNode.innerHTML = DiskFormatDate((fileData.originalTimestamp || fileData.version) / 1000);
			}
		}

		if(fileData.id && progressContainer)
		{
			var mainFileContainer = BX(progressContainer.parentNode);
			mainFileContainer.id = this.getFileId(fileData.id);
		}

		if(fileData.modifiedBy == this.currentUserId && this.notifyData.badgeCount > 0)
		{
			this.notifyData.badgeCount--;
			if(this.notifyData.badgeCount >= 0)
			{
				BX.desktop.setTabBadge('disk', this.notifyData.badgeCount);
			}
		}

		console.debug(
			this.getSyncTime(),
			"FinalFile:  ",
			fileData
		);
	},

	onFinalPackage : function()
	{
		if(BX.desktop.getSyncStatus())
			this.resetTabIcon();
		this.setLastSync((new Date()).getTime() / 1000);
		this.updateSpaces();

		console.debug(
			this.getSyncTime(),
			"FinalPackage"
		);
	},

	onErrorFile : function(path, errorData)
	{
		console.debug(
			BX.date.format("H:i:s") + "   ",
			"ErrorFile:  ",
			arguments
		);

		this.setHistoryItemError(
			path,
			errorData
		);

		this.logError(errorData, null, path);
	},

	onConflictFile : function(path, forkedFileData, originFileData)
	{
		console.debug(
			BX.date.format("H:i:s") + "   ",
			"Conflict:  ",
			arguments
		);

		try
		{
			originFileData = JSON.parse(originFileData);
			if (!originFileData || typeof(originFileData) !== "object")
			{
				return;
			}
		}
		catch (e)
		{
			return;
		}

		this.showConflictBetweenFiles({
			forkedFileData: forkedFileData,
			originFileData: originFileData
		});
	},

	onDeleteFile : function(filePath, fileData, isLocal)
	{
		console.debug("DeleteFile:", filePath, fileData, isLocal);

		fileData.isDeleted = true;
		fileData.version = (new Date().getTime());
		if(isLocal)
		{
			fileData.deletedBy = fileData.deletedBy == "0"? this.currentUserId : fileData.deletedBy;
			fileData.modifiedBy = fileData.deletedBy;
		}
		this.addHistoryItem(fileData);

		this.setLastSync((new Date()).getTime() / 1000);
	},

	onRenameFile : function(filePath, oldFilePath, fileData, isLocal)
	{
		console.debug("RenameFile:", filePath, fileData, isLocal);

		if(fileData.old_name != fileData.name)
		{
			fileData.isRenamed = true;
		}
		else
		{
			fileData.isMoved = true;
		}

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

		BitrixDiskSettings = new BX.Disk.Desktop.Settings({
			bxim: BXIM,
			diskEnabled: typeof(BXFileStorage) == 'undefined'? false : BXFileStorage.GetStatus().status == "online"
		});
		BitrixDiskSettings.saveSettings(settings);
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
	{},

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
		this.hideContainerWithSyncButtons();

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

	logError : function(errorData, context, path)
	{
		return;

		context = context || '';
		path = path || '';

		if(errorData === 'Operation was aborted by an application callback')
			return;

		BX.ajax({
			method : "POST",
			dataType : "json",
			url : 'https://www.bitrix24.ru/bx24_disk_logging',
			data :  BX.ajax.prepareData({
				c: context,
				h: document.location.host,
				u: this.currentUserId,
				v: navigator.userAgent,
				vv: BXDesktopSystem.GetProperty('version'),
				p: path,
				e: errorData
			}),
			onsuccess: function(result) {}
		});
	},

	setProgress : function(currentFileNumber, numberOfFiles, percent, speed)
	{
		if (!this.enabled)
		{
			return;
		}

		if(currentFileNumber == 0)
		{
			this.showContainerWithSyncButtons();
		}


		if (BX.type.isNumber(currentFileNumber))
		{
			this.layout.syncCurrentFile.innerHTML = currentFileNumber;
		}

		if (BX.type.isNumber(numberOfFiles))
		{
			if(numberOfFiles == 1)
			{
				this.layout.syncContainerFilesText.style.display = 'none';
			}
			else
			{
				this.layout.syncContainerFilesText.style.display = 'inline';
			}
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

	setHistoryItemError : function(path, errorData)
	{
		var progressContainer = BX(this.getHistoryFileProgressBarIdFromPath(path));
		if(!progressContainer)
		{
			return;
		}

		if(!BX.desktop.getSyncStatus() || (typeof errorData === 'string' && errorData === 'Operation was aborted by an application callback'))
		{
			var li = BX.findParent(progressContainer, {tagName: 'li'}, 4);
			if(li)
			{
				//hide item which has error while pause is worked.
				(new BX.easing({
					duration: 400,
					start : { opacity: 0, height : 0},
					finish : { opacity : 100, height : li.offsetHeight},
					transition: BX.easing.makeEaseOut(BX.easing.transitions.quad),
					step: BX.delegate(function (state)
					{
						li.style.height = state.height + "px";
//						li.style.opacity = state.opacity / 100;
					}, this),
					complete: BX.delegate(function ()
					{
						BX.cleanNode(li, true);
					}, this)
				})).animate();
				return;
			}
		}


		BX.cleanNode(progressContainer);
		if(BX.desktop.getSyncStatus())
		{
			progressContainer.appendChild(BX.create('span', {
				text: this.getErrorTextByErrorData(errorData),
				attrs: {
					'bx-disk-error-data': errorData
				}
			}));

			var localAccessDeniedData = this.getLocalAccessDeniedData(errorData);
			if(localAccessDeniedData && localAccessDeniedData.application)
			{
				this.showLockedByProgram({
					fileData: {
						name: this.baseName(path),
						path: path
					},
					program: localAccessDeniedData.application
				});
			}
		}
		BX.addClass(progressContainer, 'history_error_container');
		BX.removeClass(progressContainer, 'history_progress_bar_container');
	},

	baseName: function (str)
	{
		var base = new String(str).substring(str.lastIndexOf('/') + 1);
		if (base.lastIndexOf(".") != -1)
		{
			base = base.substring(0, base.lastIndexOf("."));
		}
		return base;
	},

	getLocalAccessDeniedData: function(errorData)
	{
		try{
			var jsonData = JSON.parse(errorData);
			if(jsonData && typeof(jsonData) === "object" && jsonData.status === 'local_access_denied')
			{
				if(jsonData.application)
				{
					return {
						application: jsonData.application
					}
				}

				return {};
			}
		}
		catch(e){}

		return null;
	},

	getErrorTextByErrorData : function(errorData)
	{
		var text = BX.message("disk_history_error_base");
		try{
			var jsonData = JSON.parse(errorData);
			if(jsonData && typeof(jsonData) === "object")
			{
				if(
					jsonData.status === 'denied' &&
					jsonData.message &&
					jsonData.message.indexOf('name should not have') > -1
				)
				{
					text = BX.message("disk_history_error_bad_name");
				}
				else if(jsonData.status === 'access_denied')
				{
					text = BX.message("disk_history_error_access_denied");
				}
				else if(jsonData.status === 'not_found')
				{
					text = BX.message("disk_history_error_not_found");
				}
				else if(jsonData.status === 'local_access_denied')
				{
					text = BX.message("disk_history_error_blocked_by_unknown");
					if(jsonData.application)
					{
						text = BX.message("disk_history_error_blocked_by_program").replace('#PROGRAM#', jsonData.application);
					}
				}
				else if(jsonData.errors || jsonData.detail)
				{
					var errors = jsonData.errors || jsonData.detail;
					for (var i in errors)
					{
						if (!errors.hasOwnProperty(i))
						{
							continue;
						}
						var error = errors[i];
						if(error.code === 'SC_FF_22001')
						{
							this.showWarningLockedDocument({link: error.message});
							text = BX.message('disk_bdisk_storage_controller_document_was_locked');
						}
						else if(error.code === 'DISK_FILE_22006')
						{
							text = BX.message('disk_bdisk_file_error_size_restriction');
						}
					}
				}
			}
		}
		catch(e){}

		return text;
	},

	setEstimatedTime : function(percent, estimatedTimeByCurrentFile)
	{
		estimatedTimeByCurrentFile = estimatedTimeByCurrentFile || 0;

		var nowTime = (new Date()).getTime() / 1000;
		var startTime = this.bxim.getLocalConfig("startPackageTime", null);
		if(startTime && percent > 0)
		{
			var estimatedTimeOfPackage = ((nowTime - startTime/1000 ) / percent) * (100 - percent);
			var estimatedTime = this.getTextForEstimatedTime(Math.max(estimatedTimeOfPackage, estimatedTimeByCurrentFile));

			if(estimatedTime)
			{
				BX.adjust(this.layout.syncEstimatedTime, {text: estimatedTime});
				BX('bx-desktop-tab-disk').title = BX.message("disk_name") + "\n" + estimatedTime;
			}
		}
	},

	setHistoryItemProgress : function(path, progressId, percent, speed)
	{
		if (!this.enabled)
		{
			return;
		}
		if (!BX.type.isNumber(percent))
		{
			return;
		}
		var progressContainer = BX(progressId);
		if(!progressContainer)
		{
			return;
		}
		var bar = BX.findChild(progressContainer, {className: 'progress_bar_p'}, true);
		if(!bar)
		{
			return;
		}

		bar.style.width = percent + "%";

		if(speed && percent > 0)
		{
			var text = BX.findChild(progressContainer, {className: 'progress_text'}, true);
			if (!text)
			{
				return;
			}
			var nowTime = (new Date()).getTime() / 1000;
			var checkPointData = this.getProgressCheckPointByFile(path);

			var estimatedTime = ((nowTime - checkPointData.startTime) / percent) * (100 - percent);
			BX.adjust(text, {text: this.getTextForEstimatedTime(estimatedTime)});

			return estimatedTime;
		}
	},

	getTextForEstimatedTime: function(seconds)
	{
		var time = '';
		var data = '';
		if(seconds < 60)
		{
			time = BX.message('disk_estimate_time_second');
			data = Math.max(Math.floor(seconds), 1);
		}
		else if(seconds < 3600)
		{
			time = BX.message('disk_estimate_time_minute');
			data = Math.max(Math.floor(seconds/60), 1);
		}
		else
		{
			time = BX.message('disk_estimate_time_hour');
			data = Math.max(Math.floor(seconds/3600), 1);
		}

		if(isNaN(data))
		{
			return '';
		}
		return BX.message("disk_estimate_time_per_file").replace('#TIME#', time.replace('#DATA#', data));
	},

	getRandomNumber : function(min, max)
	{
		return Math.floor(Math.random() * (max - min + 1)) + min;
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

		return Math.min(Math.max(percent, 0), 94);
	},

	setLastSync : function(timestamp, isInit)
	{
		isInit = isInit || false;
		if (BX.type.isNumber(timestamp))
		{
			this.lastSyncTimestamp = timestamp;
			this.layout.lastSyncDate.innerHTML = DiskFormatDate(this.lastSyncTimestamp);
		}
		else
		{
			this.layout.lastSyncDate.innerHTML = BX.message("disk_sync_no_date");
		}

		if(!BX.desktop.getSyncStatus())
		{
			this.layout.lastSyncComment.innerHTML = BX.message("disk_last_sync_paused_comment");
		}
		else if(!isInit)
		{
			this.hideContainerWithSyncButtons();
		}

		this.layout.lastSync.style.display = "block";
		this.layout.diskLoading.style.display = "none";

		this.bxim.setLocalConfig("lastSyncTimestamp", (new Date()).getTime() / 1000);
	},

	showContainerWithSyncButtons : function()
	{
		BX.show(this.layout.syncContainerButton, 'block');
		(new BX.easing({
			duration: 600,
			start: {top: 105},
			finish: {top: 155},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quad),
			step: BX.delegate(function (state)
			{
				if(this.layout.historyContainer.style.top === '155px')
				{
					return;
				}
				this.layout.historyContainer.style.top = parseInt(state.top, 10) + "px";
			}, this),
			complete: BX.delegate(function ()
			{
			}, this)
		})).animate();
	},

	hideContainerWithSyncButtons : function()
	{
		BX.cleanNode(this.layout.lastSyncComment);
		(new BX.easing({
			duration: 600,
			start: {top: 155},
			finish: {top: 105},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quad),
			step: BX.delegate(function (state)
			{
				if(this.layout.historyContainer.style.top === '105px')
				{
					return;
				}
				this.layout.historyContainer.style.top = parseInt(state.top, 10) + "px";
			}, this),
			complete: BX.delegate(function ()
			{
				BX.hide(this.layout.syncContainerButton);
			}, this)
		})).animate();
	},

	resetTabIcon : function()
	{
		var icon = BX.findChild(BX('bx-desktop-tab-disk'), {className: 'bx-desktop-tab-icon'}, true);
		if(icon)
		{
			icon.style.cssText = '';
			this.notifyData.badgeCount = 0;
			BX.desktop.setTabBadge('disk', 0);
			BX('bx-desktop-tab-disk').title = BX.message("disk_name");
		}
	},

	setWorkingTabIcon : function(counter)
	{
		var icon = BX.findChild(BX('bx-desktop-tab-disk'), {className: 'bx-desktop-tab-icon'}, true);
		if(icon)
		{
			icon.style.background = 'url("/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/icon-working.gif") no-repeat';
			icon.style.width = '19px';
			icon.style.marginTop = '8px';

			if(counter && counter > 0)
			{
				this.notifyData.badgeCount = parseInt(counter, 10);
				BX.desktop.setTabBadge('disk', counter);
			}
		}
	},

	setPauseTabIcon : function(counter)
	{
		var icon = BX.findChild(BX('bx-desktop-tab-disk'), {className: 'bx-desktop-tab-icon'}, true);
		if(icon)
		{
			icon.style.background = 'url("/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/icon-pause.png") no-repeat';
			icon.style.width = '19px';
			icon.style.marginTop = '8px';
		}
	},

	getFileId : function(diskFileId)
	{
		return diskFileId.replace(/\|/g, "_");
	},

	getHistoryFileProgressBarIdFromPath : function(path)
	{
		return 'id-' + path.replace(/\\/g, "_");
	},

	storeProgressCheckPointByFile : function(path, data)
	{
		data = data || {};
		this.progressCheckPointByFile[this.getHistoryFileProgressBarIdFromPath(path)] = {
			startTime: (new Date()).getTime() / 1000,
			size: data.size || 0
		};
	},

	deleteProgressCheckPointByFile : function(path)
	{
		delete this.progressCheckPointByFile[this.getHistoryFileProgressBarIdFromPath(path)];
	},

	getProgressCheckPointByFile : function(path)
	{
		return this.progressCheckPointByFile[this.getHistoryFileProgressBarIdFromPath(path)] || {startTime: 0, size: 0};
	},

	prepareDataToHistoryItem : function(data)
	{
		var fileData = Object.create(null);
		fileData.path = data.path;
		fileData.size = data.fileSize;

		if(data.snapshot.name)
		{
			fileData.name = data.snapshot.name;
		}
		else
		{
			fileData.name = fileData.path.split('/').pop();
		}

		if(data.snapshot.id)
		{
			fileData.id = data.snapshot.id;
		}

		if(data.snapshot.version)
		{
			fileData.version = data.snapshot.version;
		}

		if(data.snapshot.originalTimestamp)
		{
			fileData.originalTimestamp = data.snapshot.originalTimestamp;
		}

		if(!fileData.id)
		{
			fileData.id = 'b' + (new Date()).getTime() / 100;
		}

		fileData.isDeleted = false;
		if(!data.snapshot.id)
		{
			fileData.isNew = true;
		}
		else if(data.snapshot.id)
		{
			fileData.isNew = data.snapshot.isNew;
		}

		if(data.snapshot.modifiedBy)
		{
			fileData.modifiedBy = data.snapshot.modifiedBy;
		}
		else
		{
			fileData.modifiedBy = this.currentUserId;
		}

		fileData.progressBarId = this.getHistoryFileProgressBarIdFromPath(fileData.path);
		fileData.withProgressBar = !!data.withProgressBar;

		return fileData;
	},

	addHistoryItem : function(fileData, animate)
	{
		if (!fileData || !fileData.name || !fileData.path)
		{
			return;
		}

		var withProgressBar = fileData.withProgressBar || false;

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
		else if (fileData.isRenamed)
		{
			status = BX.message("disk_file_renamed");
			statusClass = "status-renamed";
		}
		else if (fileData.isMoved)
		{
			status = BX.message("disk_file_moved");
			statusClass = "status-moved";
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
		var itemDate = (new Date()).getTime() / 1000;
		if(fileData.originalTimestamp)
		{
			itemDate = fileData.originalTimestamp / 1000;
		}
		else if(fileData.version)
		{
			itemDate = fileData.version / 1000;
		}

		if(BX(id, false))
		{
			BX.remove(BX(id, false));
		}

		var remoteDiskId = fileData.id.split(/f([0-9]+)/)[1];
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
									props : { className: "history-file-title", href : fileData.path, title: fileData.path },
									html : fileData.name,
									events: {
										click: BX.delegate(function(e){
											var action = this.getFileClickAction();
											var dataById = BXFileStorage.GetObjectDataById(fileData.id);
											if(!dataById || !dataById.path)
											{
												dataById = {path: fileData.path};
											}
											action(dataById);

											return BX.PreventDefault(e);
										}, this)
									}
								})
							),
							BX.create("span", {
								props : { className: "history-file-status" },
								html : "(" + status + ")"
							}),
							(fileData.isDeleted && fileData.modifiedBy == this.currentUserId && remoteDiskId?
								BX.create("a", {
									text: BX.message('disk_restore_deleted_object'),
									props: {
										className: 'history-link-to-restore',
										href: this.pathTemplateToRestoreObject.replace('placeForObjectId', remoteDiskId),
										target: '_blank'
									}
								}):
								null
							)
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
			}),
			(fileData.isDeleted || !withProgressBar ? null : BX.create("div", {
					props: {id: fileData.progressBarId, className: "history_progress_bar_container"},
					children : [
						BX.create("div", {
							props: {
								className: "progress_bar_container history_progress_bar"
							},
							children: [
								BX.create("div", {
									props: {className: "progress_bar_p"}
								})
							]
						}),
						BX.create("span", {
							props: {className: "progress_text"}
						})
					]
				})
			)
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
		if(!this.enableShowingNotify)
		{
			return false;
		}
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
			userAvatar: user.avatar || '/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/disk_34x34.png',
			userName: user.name,
			type: "2"
		};

		var notifyHtml = BX.create("div", {attrs : {'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item"}, children : [
			BX.create('span', {props : { className : "bx-notifier-item-content" }, children : [
				BX.create('span', {props : { className : "bx-notifier-item-avatar" }, children : [
					BX.create('img', {props : { className : "bx-notifier-item-avatar-img" },attrs : {src : encodeURI(notify.userAvatar)}})
				]}),
				BX.create("a", {attrs : {href : '#', 'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item-delete"}}),
				BX.create('span', {props : { className : "bx-notifier-item-date" }, html: DiskFormatDate(notify.date)}),
				BX.create('span', {props : { className : "bx-notifier-item-name" }, html: '<a href="'+notify.userLink+'">'+ (typeof(BX.MessengerCommon) != 'undefined'? BX.MessengerCommon.prepareText(notify.userName) : BX.IM.prepareText(notify.userName))+'</a>'}),
				BX.create('span', {props : { className : "bx-notifier-item-text" }, html: notify.text})
			]})
		]});

		var messsageJs =
			'var notifyClose = BX.findChild(document.body, {className : "bx-notifier-item-delete"}, true);'+
			'BX.bind(notifyClose, "click", function(event){ BX.desktop.windowCommand("close"); return BX.PreventDefault(event) });'+
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'+
			'BX.bind(notify, "click", function(event){ ' + eventOnClick + ' });'+
			'BX.bind(notify, "contextmenu", function(){ ' + eventOnClick + ' });';

		BXDesktopSystem.ExecuteCommand('notification.show.html', this.bxim.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	},

	showWarningLockedDocument : function(data)
	{
		var notifyHtml = BX.create("div", {
			attrs: {'data-notifyId': (new Date().getTime() / 100), 'data-notifyType': "2"},
			props: {className: "bx-notifier-item"},
			children: [
				BX.create('div', {html: BX.Disk.InformationPopups.getContentWarningLockedDocumentDesktop({
					link: data.link
				})})
			]
		});

		var messsageJs =
			'var notifyClose = BX.findChild(document.body, {className : "bx-notifier-item-delete"}, true);'+
			'BX.bind(notifyClose, "click", function(event){ BX.desktop.windowCommand("close"); return BX.PreventDefault(event) });'+
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'
		;

		BXDesktopSystem.ExecuteCommand('notification.show.html', this.bxim.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	},

	showConflictBetweenFiles : function(data)
	{
		var notifyHtml = BX.create("div", {
			attrs: {'data-notifyId': (new Date().getTime() / 100), 'data-notifyType': "2"},
			props: {className: "bx-notifier-item"},
			children: [
				BX.create('div', {html: BX.Disk.InformationPopups.getContentConflictBetweenFiles(data.forkedFileData, data.originFileData)})
			]
		});

		console.log(notifyHtml);

		var messsageJs =
			'BX.desktop.windowCommand("freeze");' +
			'var notifyClose = BX.findChild(document.body, {className : "bx-notifier-item-delete"}, true);'+
			'BX.bind(notifyClose, "click", function(event){ BX.desktop.windowCommand("close"); return BX.PreventDefault(event) });'+
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);' +
			'BX.bindDelegate(notify, "click", {className: "js-disk-open-filefolder"}, function(event){ ' +
				'BX.PreventDefault(event);' +
				'var path = event.target.getAttribute("data-href");' +
				'BXFileStorage.FileExist(path, function(exist){' +
					'if(exist)' +
						'BXFileStorage.OpenFileFolder(path, function(){});' +
				'})' +
			' });'+
			''
		;

		BXDesktopSystem.ExecuteCommand('notification.show.html', this.bxim.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	},

	showLockedByProgram : function(data)
	{
		var notifyHtml = BX.create("div", {
			attrs: {'data-notifyId': (new Date().getTime() / 100), 'data-notifyType': "2"},
			props: {className: "bx-notifier-item"},
			children: [
				BX.create('div', {html: BX.Disk.InformationPopups.getContentLockedByProgram(data.fileData, data.program)})
			]
		});

		console.log(notifyHtml);

		var messsageJs =
			'BX.desktop.windowCommand("freeze");' +
			'var notifyClose = BX.findChild(document.body, {className : "bx-notifier-item-delete"}, true);'+
			'BX.bind(notifyClose, "click", function(event){ BX.desktop.windowCommand("close"); return BX.PreventDefault(event) });'+
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);' +
			'BX.bindDelegate(notify, "click", {className: "js-disk-open-filefolder"}, function(event){ ' +
				'BX.PreventDefault(event);' +
				'var path = event.target.getAttribute("data-href");' +
				'BXFileStorage.FileExist(path, function(exist){' +
					'if(exist)' +
						'BXFileStorage.OpenFileFolder(path, function(){});' +
				'})' +
			' });'+
			''
		;

		BXDesktopSystem.ExecuteCommand('notification.show.html', this.bxim.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	},

	showSwitchOnBDisk : function()
	{
		var notifyHtml = BX.create("div", {
			attrs: {'data-notifyId': (new Date().getTime() / 100), 'data-notifyType': "2"},
			props: {className: "bx-notifier-item"},
			children: [
				BX.create('div', {html: BX.Disk.InformationPopups.getContentSwitchOnBDisk()})
			]
		});

		var messsageJs =
			'BX.desktop.windowCommand("freeze");' +
			'var notifyClose = BX.findChild(document.body, {className : "bx-notifier-item-delete"}, true);'+
			'BX.bind(notifyClose, "click", function(event){ BX.desktop.windowCommand("close"); return BX.PreventDefault(event) });'+
			'var tab_index = BXDesktopSystem.MyTabNumber()-1;' +
			'var link = BX("bx-open-bdisk-settings");' +
			'BX.bind(link, "click", function(event){ ' +
				'BX.PreventDefault(event);' +
				'window.open(\'http://bxd-internal/bdisk_settings_window.html#bdisk_settings_\'+tab_index,\'bdisk_settings_\'+tab_index,\'width=540,height=320\')' +
			' });'+
			''
		;

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
			//userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/disk_34x34.png',
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

				'if(!!window.anim) { window.anim.stop(); }' +
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

			'BX.desktop.addCustomEvent("onLaunchApp", function(){ setTimeout(function(){BX.desktop.windowCommand("close");}, 2000);   BX.adjust(BX("bx-notifier-item-text"), {text: "' + BX.message('disk_progress_start_launch_app') + '"})});' +
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
			//userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/disk_34x34.png',
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

				'if(!!window.anim) { window.anim.stop(); }' +
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

			'BX.desktop.addCustomEvent("onLaunchApp", function(){setTimeout(function(){BX.desktop.windowCommand("close");}, 2000);  BX.adjust(BX("bx-notifier-item-text"), {text: "' + BX.message('disk_progress_start_launch_app') + '"})});' +
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
			//userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/disk_34x34.png',
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
				BX.create('div', {style: {marginLeft: '50px'}, children: [BX.create('img', {props: {src: '/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/popups-alert-mov.gif'}})]}),
				BX.create('div', {props : { className : "bx-notifier-item-text" }, html: BX.message('disk_progress_start_launch_app')})
			]})
		]});

		var messsageJs =
			'window.name = "notifyProgressLaunchApp";' +
			'var notifyClose = BX.findChild(document.body, {className : "bx-notifier-item-delete"}, true);'+
			'BX.bind(notifyClose, "click", function(event){ BX.desktop.windowCommand("close"); return BX.PreventDefault(event) });'+
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
			//userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/disk_34x34.png',
			userName: BX.message('disk_name'),
			type: "2"
		};

		var notifyHtml = BX.create("div", {attrs : {'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item"}, children : [
			BX.create('span', {props : { className : "bx-notifier-item-content" }, children : [
				BX.create('span', {props : { className : "bx-notifier-item-avatar" }, children : [
					BX.create('img', {props : { className : "bx-notifier-item-avatar-img" },attrs : {src : notify.userAvatar}})
				]}),
				BX.create("a", {attrs : {href : '#', 'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item-delete"}}),
				(isFinish? null : BX.create('div', {style: {marginLeft: '50px'}, children: [BX.create('img', {props: {src: '/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/popups-alert-mov.gif'}})]})),

				(!isFinish? null : BX.create('span', {props : { className : "bx-notifier-item-name" }, html: '<a href="'+notify.userLink+'">'+notify.userName+'</a>'})),
				BX.create('span', {props : { className : "bx-notifier-item-text" }, html: notify.text})
			]})
		]});

		var messsageJs =
			'window.name = "notifyProgressExtLink";' +
			(isFinish? 'setTimeout(function(){BX.desktop.windowCommand("close");}, 2500);' : 'BX.desktop.windowCommand("freeze");') +
			'var notifyClose = BX.findChild(document.body, {className : "bx-notifier-item-delete"}, true);'+
			'BX.bind(notifyClose, "click", function(event){ BX.desktop.windowCommand("close"); return BX.PreventDefault(event) });'+
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
					if(lastWindow[0].name === name)
					{
						return lastWindow[0];
					}

					for(var i in BXWindows)
					{
						if(!BXWindows.hasOwnProperty(i))
						{
							continue;
						}
						if(BXWindows[i] && BXWindows[i].name === name)
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
					if(lastWindow[0].name.search(prefix) !== -1)
					{
						return lastWindow[0];
					}

					for(var i in BXWindows)
					{
						if(!BXWindows.hasOwnProperty(i))
						{
							continue;
						}
						if(BXWindows[i] && BXWindows[i].name.search(prefix) !== -1)
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

		var diskEnabled = typeof(BXFileStorage) == 'undefined'? false : BXFileStorage.GetStatus().status == "online";
		if(!diskEnabled)
		{
			console.debug(
				this.getSyncTime(),
				"Disk is disabled"
			);

			return;
		}

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
			//userLink: "bx://openDiskTab",
			userAvatar: '/bitrix/components/bitrix/disk.bitrix24disk/templates/.default/images/disk_34x34.png',
			userName: BX.message('disk_name'),
			type: "2"
		};

		var notifyHtml = BX.create("div", {attrs : {'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item"}, children : [
			BX.create('span', {props : { className : "bx-notifier-item-content" }, children : [
				BX.create('span', {props : { className : "bx-notifier-item-avatar" }, children : [
					BX.create('img', {props : { className : "bx-notifier-item-avatar-img" },attrs : {src : notify.userAvatar}})
				]}),
				BX.create("a", {attrs : {href : '#', 'data-notifyId' : notify.id, 'data-notifyType' : notify.type}, props : { className: "bx-notifier-item-delete"}}),
				BX.create('span', {props : { className : "bx-notifier-item-date" }, html: DiskFormatDate(notify.date)}),
				BX.create('span', {props : { className : "bx-notifier-item-name" }, html: '<a href="'+notify.userLink+'">'+notify.userName+'</a>'}),
				BX.create('span', {props : { className : "bx-notifier-item-text" }, html: notify.text})
			]})
		]});

		var messsageJs =
			'var notifyClose = BX.findChild(document.body, {className : "bx-notifier-item-delete"}, true);'+
			'BX.bind(notifyClose, "click", function(event){ BX.desktop.windowCommand("close"); return BX.PreventDefault(event) });'+
			'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'+
			'BX.bind(notify, "click", function(event){ BXDesktopSystem.GetMainWindow().ExecuteCommand("show"); BX.desktop.setActiveWindow(TAB_CP); BX.desktop.onCustomEvent("main", "BXChangeTab", [\'disk\']); BX.desktop.windowCommand("close");  });'+
			'BX.bind(notify, "contextmenu", function(){ BXDesktopSystem.GetMainWindow().ExecuteCommand("show"); BX.desktop.windowCommand("close"); });';

		BXDesktopSystem.ExecuteCommand('notification.show.html', BXIM.desktop.getHtmlPage(notifyHtml, messsageJs, false));
	}
};
