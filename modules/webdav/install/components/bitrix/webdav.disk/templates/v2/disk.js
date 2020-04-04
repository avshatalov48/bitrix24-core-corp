var disk_token = "";
var disk_path = "";
var isInstalledPull = true;
var urlToDiskAjax = "";
var alreadyDiskInstall = false;
BX.ready(function(){
	BX.addCustomEvent(window, 'onImInit', function (BXIM) {
		if(!window.BXFileStorage)
			return;

		window.addEventListener('BXFileStorageStatusChanged', ReloadWindow);
		window.addEventListener('BXFileStorageStatusSync', NotifySync);
		ReloadWindow();

		BX.addCustomEvent(window, 'onDesktopChangeTab', function (tabName) {
			if (!tabName)
				return;
			if (tabName == 'disk') {
				ReloadWindow();
			}
		});

		if(BXIM.desktop.enableInVersion(15))
		{
			BXIM.desktop.addCustomEvent("BXProtocolUrl", function(command, params) {
				command = command.toLowerCase();
				switch(command)
				{
					case 'opendisktab':
						BXDesktopSystem.GetMainWindow().ExecuteCommand("show");
						BXIM.desktop.changeTab(BX('desktop_tab_disk_header'));
				}
			});
		}
	});
});

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
	BX('disk_main_cont').style.display = 'none';
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
			BX('attach_disk_path').innerHTML = data.Path;
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
	BX('disk_already_connect').style.display  = 'none';
	if (BXIM && BXIM.desktop.diskAttachStatus)
		BXIM.desktop.diskAttachStatus(false);
}

function HideConnectCont()
{
	BX('disk_connect_cont').style.display  = 'none';
	BX('disk_change_path').style.display  = 'none';
}

function ShowConnectCont()
{
	SetDefaultTargetFolder();
	HideAlreadyConnectCont();
	BX('disk_connect_cont').style.display  = 'block';
	BX('disk_change_path').style.display  = 'block';
//	BX('disk_add_info').style.display  = 'block';

	return false;
}

function ShowAlreadyConnectCont()
{
	BX('disk_already_connect').style.display  = 'block';
	HideConnectCont();
//	BX('disk_add_info').style.display  = 'none';
	if (BXIM && BXIM.desktop.diskAttachStatus)
		BXIM.desktop.diskAttachStatus(true);

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
function AttachDisk()
{
	ShowLoading('disk_connectbutton');
	BXFileStorage.Attach(disk_token);
	setInstalledDisk();
}

function DetachDisk()
{
	ShowLoading('disk_disconnectbutton');
	BXFileStorage.Detach();
	setUninstalledDisk();
}

function HideErrorDisk()
{
	var errorCont = BX("disk_error_container");
	errorCont.style.display  = 'none';
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

	errorText.innerHTML = message;
	errorCont.style.display  = 'block';
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

function NotifySync(event)
{
	var info = event.detail;
	var text = '';

	if(info.files.add && info.files.add > 0 || info.folders.add && info.folders.add > 0)
	{
		text += formatNotifyString('add', info.files.add || 0, info.folders.add || 0)  + '<br>';
	}
	if(info.files.update && info.files.update > 0 || info.folders.update && info.folders.update > 0)
	{
		text += formatNotifyString('update', info.files.update || 0, info.folders.update || 0)  + '<br>';
	}
	if(info.files.delete && info.files.delete > 0 || info.folders.delete && info.folders.delete > 0)
	{
		text += formatNotifyString('delete', info.files.delete || 0, info.folders.delete || 0)  + '<br>';
	}

	var notify = {
		date: (new Date().getTime() / 1000),
		id: "bd" + info.date,
		text: text,
		userLink: "#disk",
		userAvatar: '/bitrix/components/bitrix/webdav.disk/templates/.default/images/disk_32x32_2.png',
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
			BX.create('span', {props : { className : "bx-notifier-item-name" }, html: '<a href="'+notify.userLink+'">'+BX.IM.prepareText(notify.userName)+'</a>'}),
			BX.create('span', {props : { className : "bx-notifier-item-text" }, html: notify.text})
		]})
	]});

	var messsageJs =
		'var notify = BX.findChild(document.body, {className : "bx-notifier-item"}, true);'+
		'BX.bind(notify, "click", function(event){ BXIM.desktop.windowCommand("close") });'+
		'BX.bind(notify, "contextmenu", function(){ BXIM.desktop.windowCommand("close")});';

	BXDesktopSystem.ExecuteCommand('notification.show.html', BXIM.desktop.getHtmlPage(notifyHtml, messsageJs, false));
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
