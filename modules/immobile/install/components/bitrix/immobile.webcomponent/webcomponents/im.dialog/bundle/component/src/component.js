"use strict";

let REVISION = 19; // api version - sync with im/lib/revision.php

/* Recent list API */
let ChatDialog = {
	cacheUpdateInterval: 2000,
	cacheUpdateQueueInterval: 500
};

ChatDialog.init = function()
{
	/* set cross-links in class */
	let links = ['base', 'disk', 'message', 'userDialog', 'userAddDialog', 'transferDialog'];
	links.forEach((subClass) => {
		if (typeof this[subClass] != 'undefined')
		{
			links.forEach((element) => {
				if (element == 'base')
				{
					this[subClass]['base'] = this;
				}
				else if (subClass != element)
				{
					this[subClass][element] = this[element];
				}
			});
		}
	});

	this.listRequestAfterErrorInterval = 10000;

	this.browserConst = null;
	this.mobileConfig = null;
	this.database = null;
	this.dialogCache = null;

	this.widgets = {
		recentList: [],
		colleaguesList: [],
		lastSearchList: [],
		businessUsersList: false,
	};

	BXMobileApp.addCustomEvent('onLinesTransferSuccess', event => {
		ChatDialog.transferDialog.closeDialog(event.chatId);
	}, true);

	BX.componentParameters.init().then((data) => {
		this.dialogId = data.DIALOG_ID;
		this.userId = data.USER_ID;
		this.languageId = data.LANGUAGE_ID;
		this.initLangAdditional(data.LANG_ADDITIONAL);

		this.getInitData();

		this.database = new ReactDatabase(ChatDatabaseName, this.userId, this.languageId);

		this.message.init((messageData) => {
			this.loadConfig(() => {
				if (!this.mobileConfig)
				{
					this.mobileConfig = {}
				}
				if (!this.mobileConfig['users'])
				{
					this.mobileConfig['users'] = {};
				}
				for (let userId in messageData.users)
				{
					if (messageData.users.hasOwnProperty(userId))
					{
						this.mobileConfig['users'][userId] = messageData.users[userId];
					}
				}

				if (!this.mobileConfig['phones'])
				{
					this.mobileConfig['phones'] = {};
				}
				for (let userId in messageData.phones)
				{
					if (messageData.phones.hasOwnProperty(userId))
					{
						this.mobileConfig['phones'][userId] = messageData.phones[userId];
					}
				}

				this.mobileConfig['message'] = messageData.message;
				this.mobileConfig['files'] = messageData.files;

				this.mobileConfig['showMessage'] = messageData.showMessage;
				this.mobileConfig['unreadMessage'] = messageData.unreadMessage;
				this.mobileConfig['readedList'] = messageData.readedList;

				this.initMessenger({type: 'cache'});
				this.loadWidgetData();
			});
		});
	});

	return true;
};

ChatDialog.initLangAdditional = function(langAdditional)
{
	this.langAdditional = langAdditional || {};

	Object.keys(this.langAdditional).forEach(code => {
		if (typeof this.langAdditional[code] !== 'string')
		{
			return;
		}

		BX.message[code] = this.langAdditional[code];
	});
}

ChatDialog.openDialog = function(dialogId)
{
	let dialogTitleParams = null;
	const isChat = dialogId.toString().substr(0, 4) == 'chat';

	if (isChat)
	{
		let chatId = dialogId.toString().substr(4);
		if (BXIM.messenger.chat[chatId])
		{
			let description = '';
			if (BXIM.messenger.chat[chatId].type == 'call')
			{
				description = BX.message('IM_CL_PHONE');
			}
			else if (BXIM.messenger.chat[chatId].type == 'lines')
			{
				description = BX.message('IM_CL_LINES');
			}
			else if (BXIM.messenger.chat[chatId].type == 'open')
			{
				description = BX.message('IM_CL_OPEN_CHAT_NEW');
			}
			else
			{
				description = BX.message('IM_CL_CHAT_NEW');
			}

			dialogTitleParams = {
				name: BXIM.messenger.chat[chatId].name,
				avatar: BXIM.messenger.chat[chatId].avatar,
				description: description,
			};
		}
	}
	else if (BXIM.messenger.users[dialogId])
	{
		dialogTitleParams = {
			name: BXIM.messenger.chat[chatId].name,
			avatar: BXIM.messenger.chat[chatId].avatar,
			description: BXIM.messenger.chat[chatId].work_position,
		};
	}

	BXMobileApp.Events.postToComponent("onOpenDialog", {
		dialogId : dialogId,
		dialogTitleParams : dialogTitleParams? {
			name: dialogTitleParams.name || '',
			avatar: dialogTitleParams.avatar || '',
			description: dialogTitleParams.description || '',
		}: false,
	}, 'im.recent');
};

ChatDialog.getInitData = function()
{
	clearTimeout(this.refreshTimeout);

	ChatRestRequest.abort('update');
	console.info("ChatDialog.getInitData: send request to server");

	let requestMethods = {
		revision: ['im.revision.get'],
		browserConst: ['mobile.browser.const.get'],
		mobileConfig: ['im.mobile.config.get'],
	};

	ChatTimer.start('data', 'load', 3000, () => {
		console.warn("ChatDialog.getInitData: slow connection show progress icon");
	});

	let executeTime = new Date();

	BX.rest.callBatch(requestMethods, (result) =>
	{
		ChatRestRequest.unregister('update');
		ChatTimer.stop('data', 'load', true);

		this.loadingFlag = false;

		let revisionError = result.revision.error();
		let browserConstError = result.browserConst.error();
		let mobileConfigError = result.mobileConfig.error();

		// revision block
		if (result.revision && !revisionError)
		{
			let data = result.revision.data();
			if (!this.checkRevision(data.mobile))
			{
				return true;
			}
		}

		// recent block
		let loadFromCache = false;
		if (this.browserConst && this.mobileConfig)
		{
			loadFromCache = true;
		}
		if (result.browserConst && !browserConstError)
		{
			console.info("ChatDialog.getInitData: browser const received", result.browserConst.data());
			this.browserConst = result.browserConst.data();
		}

		if (result.mobileConfig && !mobileConfigError)
		{
			console.info("ChatDialog.getInitData: mobile config received", result.mobileConfig.data());
			this.mobileConfig = result.mobileConfig.data();
		}

		if (!browserConstError && !mobileConfigError)
		{
			if (!loadFromCache)
			{
				this.initMessenger({type: 'server'});
			}
			this.updateConfig();
		}

		console.info("ChatDialog.getInitData: receive answer from server and update variables ("+(new Date() - executeTime)+'ms)', result);

		if (browserConstError)
		{
			let error = null;
			if (browserConstError)
			{
				error = browserConstError;
			}
			else if (mobileConfigError)
			{
				error = mobileConfigError;
			}

			if (error)
			{
				if (error.ex.error == 'ERROR_NETWORK')
				{
					console.error("ChatDialog.getInitData: connection error, stop trying connect", error.ex);
				}
				else if (error.ex.error == 'REQUEST_CANCELED')
				{
					console.error("ChatDialog.getInitData: execute request canceled by user", error.ex);
				}
				else
				{
					console.error("ChatDialog.getInitData: we have some problems with request, we will be check again soon\n", error.ex);

					clearTimeout(this.refreshTimeout);
					this.refreshTimeout = setTimeout(() => {
						this.getInitData();
					}, this.listRequestAfterErrorInterval);
				}
			}
		}
	}, false, (xhr) => {
		ChatRestRequest.register('data', xhr);
	});

	return true;
};

ChatDialog.checkRevision = function(newRevision)
{
	if (typeof(newRevision) != "number" || REVISION >= newRevision)
	{
		return true;
	}

	console.warn('ChatDialog.checkRevision: reload scripts because revision up ('+REVISION+' -> '+newRevision+')');
	reload();

	return false;
};

ChatDialog.initMessenger = function(config)
{
	BX.message(this.browserConst);

	if (!window.BXIM)
	{
		window.BXIM = new BX.ImMobile();
	}

	this.mobileConfig['initType'] = config.type;
	window.BXIM.initParams(this.mobileConfig);
};

ChatDialog.loadConfig = function (successCallback)
{
	let callback = typeof successCallback == 'function'? successCallback: () => {};

	let executeTimeMobileConfig = new Date();
	this.database.table(ChatTables.dialogConfig).then(table =>
	{
		table.get().then(items =>
		{
			if (items.length > 0)
			{
				let cacheData = JSON.parse(items[0].VALUE);

				let cacheSuccess = false;
				if (this.browserConst && this.mobileConfig)
				{
					console.info("ChatDialog.loadConfig: cache file \"mobileConfig\" has been ignored because it was loaded a very late");
				}
				else
				{
					if (!this.browserConst)
					{
						this.browserConst = cacheData['browserConst'];
					}
					if (!this.mobileConfig)
					{
						this.mobileConfig = cacheData['mobileConfig'];
					}

					cacheSuccess = true;
				}
				console.info("ChatDialog.loadConfig: const and config load from cache ("+(new Date() - executeTimeMobileConfig)+'ms)');

				if (cacheSuccess)
				{
					callback();
				}
			}
		})
	});

	return true;
};

ChatDialog.updateConfig = function ()
{
	clearTimeout(this.cacheUpdateTimeout);
	this.cacheUpdateTimeout = setTimeout(() =>
	{
		let executeTime = new Date();

		this.database.table(ChatTables.dialogConfig).then(table =>
		{
			table.delete().then(() =>
			{
				table.add({value : {
					browserConst: this.browserConst,
					mobileConfig: this.mobileConfig
				}}).then(() =>
				{
					console.info("ChatDialog.updateConfig: cache updated ("+(new Date() - executeTime)+'ms)');
				});
			})
		});

	}, this.cacheUpdateInterval);

	return true;
};

ChatDialog.loadWidgetData = function ()
{
	let executeTimeRecent = new Date();
	this.database.table(
		ChatTables.recent
	).then(table =>
	{
		table.get().then(items =>
		{
			if (items.length > 0)
			{
				let cacheData = JSON.parse(items[0].VALUE);
				this.widgets.recentList = ChatDataConverter.getListFormat(cacheData.list);
				console.info("ChatDialog.loadWidgetData: recent load from cache ("+(new Date() - executeTimeRecent)+'ms)', "count: "+this.widgets.recentList.length);
			}
		})
	});

	let executeTimeLastSearch = new Date();
	this.database.table(ChatTables.lastSearch).then(table =>
	{
		table.get().then(items =>
		{
			if (items.length > 0)
			{
				let cacheData = JSON.parse(items[0].VALUE);

				this.widgets.lastSearchList = ChatDataConverter.getListFormat(cacheData.recent);

				console.info("ChatDialog.loadWidgetData: last search load from cache \"\" ("+(new Date() - executeTimeLastSearch)+'ms)', "count: "+this.widgets.lastSearchList.length);
			}
		})
	});

	let executeTimeColleaguesList = new Date();
	this.database.table(ChatTables.colleaguesList).then(table =>
	{
		table.get().then(items =>
		{
			if (items.length > 0)
			{
				let cacheData = JSON.parse(items[0].VALUE);

				this.widgets.colleaguesList = ChatDataConverter.getUserListFormat(cacheData.colleaguesList);

				console.info("ChatDialog.loadWidgetData: colleagues list load from cache ("+(new Date() - executeTimeColleaguesList)+'ms)', "count: "+this.widgets.colleaguesList.length);
			}
		})
	});

	let executeTimeBusinessUsersList = new Date();
	this.database.table(ChatTables.businessUsersList).then(table =>
	{
		table.get().then(items =>
		{
			if (items.length > 0)
			{
				let cacheData = JSON.parse(items[0].VALUE);
				this.widgets.businessUsersList = cacheData.businessUsersList !== false? ChatDataConverter.getUserListFormat(cacheData.businessUsersList): false;

				console.info("ChatDialog.loadWidgetData: colleagues list load from cache ("+(new Date() - executeTimeBusinessUsersList)+'ms)', this.widgets.businessUsersList !== false? "count: "+this.widgets.businessUsersList.length: "not available");
			}
		})
	});

	return true;
};


/* File API */
ChatDialog.message = {};

ChatDialog.message.init = function(callback)
{
	this.dialogCache = new ChatDialogCache();
	this.dialogCache.setDatabase(this.base.database);
	this.dialogCache.getDialog(this.base.dialogId)
		.then(dialog => {})
		.catch(error => {})
	;

	let messageData = {
		message: {},
		files: {},
		users: {},
		phones: {},
		showMessage: {},
		unreadMessage: {},
		readedList: {},
	};
	callback(messageData);

	BX.addCustomEvent('onImLoadLastMessage', this.onLoadLastMessage.bind(this));

	return true;
};

ChatDialog.message.onLoadLastMessage = function(dialogId, result, data)
{
	if (!result)
	{
		return false;
	}

	let dialogReadList = {};

	if (data.READED_LIST[dialogId])
	{
		if (dialogId.toString().startsWith('chat'))
		{
			for (let userId in data.READED_LIST[dialogId])
			{
				if (!data.READED_LIST[dialogId].hasOwnProperty(userId))
				{
					continue;
				}

				if (parseInt(data.READED_LIST[dialogId][userId].messageId) > 0)
				{
					dialogReadList[userId] = data.READED_LIST[dialogId][userId];
				}
			}
		}
		else
		{
			dialogReadList[dialogId] = data.READED_LIST[dialogId];
		}
	}

	let dialogUsers = data.USERS? data.USERS: {};
	let phoneList = data.PHONES && data.PHONES[dialogId]? data.PHONES[dialogId]: {};

	this.dialogCache.updateDialog(dialogId, {
		readList: dialogReadList,
		userList: dialogUsers,
		phoneList: phoneList,
		unreadList: []
	});

	for (let messageId in data.MESSAGE)
	{
		if (!data.MESSAGE.hasOwnProperty(messageId))
		{
			continue;
		}

		this.dialogCache.addMessage(this.base.dialogId, this.dialogCache.getMessageFormat({
			message: data.MESSAGE[messageId],
			files: data.FILES,
			users: data.USERS
		}));
	}

	BXMobileApp.Events.postToComponent("onLoadLastMessage", {dialogId : dialogId}, 'im.recent');
	BXMobileApp.Events.postToComponent("onLoadLastMessage", {dialogId : dialogId}, 'im.openlines.recent');
};


/* File API */
ChatDialog.disk =
{
	dialogId: 0,

	folderId: 0,
	chatId: 0,
	queue: [],
	nodes: {},

	process: {},

	TYPE_ADD: 'add',
	TYPE_LISTEN: 'listen',
};

ChatDialog.disk.init = function(dialogId)
{
	console.info("ChatDialog.disk.init: "+dialogId);

	this.process[this.TYPE_ADD] = [];
	this.process[this.TYPE_LISTEN] = [];

	this.dialogId = dialogId;

	this.loadConfig();
	BX.addCustomEvent("onImDrawTab", () => {
		this.loadQueue();
	});
	BX.MobileUploadProvider.setListener(this.eventRouter.bind(this));

	return true;
};

ChatDialog.disk.getDialogId = function()
{
	return this.dialogId;
};

ChatDialog.disk.getChatId = function()
{
	return this.chatId;
};

ChatDialog.disk.setChatId = function (chatId)
{
	chatId = parseInt(chatId);
	if (chatId <= 0 || this.chatId == chatId)
		return true;

	this.chatId = parseInt(chatId);
	this.updateConfig();

	this.processItems();

	return true;
};

ChatDialog.disk.getFolderId = function()
{
	return this.folderId;
};

ChatDialog.disk.setFolderId = function (folderId)
{
	folderId = parseInt(folderId);
	if (!folderId || this.folderId == folderId)
		return true;

	this.folderId = folderId;
	this.updateConfig();
	this.processItems();

	return true;
};

ChatDialog.disk.getTmpId = function()
{
	return 'imDialog'+this.chatId+this.base.userId+(+new Date());
};

ChatDialog.disk.loadConfig = function ()
{
	let executeTime = new Date();

	this.base.database.table(ChatTables.diskConfig).then(table =>
	{
		table.get({dialogId: this.dialogId}).then(items =>
		{
			if (items.length <= 0)
			{
				return false;
			}

			let cacheData = JSON.parse(items[0].VALUE);

			if (!this.chatId)
			{
				this.chatId = cacheData.chatId;
			}

			if (!this.folderId)
			{
				this.folderId = cacheData.folderId;
			}

			this.processItems();

			console.info("ChatDialog.disk.loadConfig: cache loaded ("+(new Date() - executeTime)+'ms)');

			return true;
		})
	});
};

ChatDialog.disk.updateConfig = function ()
{
	clearTimeout(this.cacheUpdateTimeout);
	this.cacheUpdateTimeout = setTimeout(() =>
	{
		if (!this.chatId || !this.folderId)
			return false;

		let executeTime = new Date();

		this.base.database.table(ChatTables.diskConfig).then(table =>
		{
			table.delete({dialogId: this.dialogId}).then(() =>
			{
				table.add({
					dialogId: this.dialogId,
					value : {
						chatId: this.chatId,
						folderId: this.folderId
					}
				}).then(() =>
				{
					console.info("ChatDialog.disk.updateConfig: cache updated ("+(new Date() - executeTime)+'ms)');
				});
			})
		});

		return true;
	}, this.cacheUpdateInterval);

	return true;
};

ChatDialog.disk.loadQueue = function ()
{
	this.base.database.table(ChatTables.diskFileQueue).then(table =>
	{
		table.get().then(items =>
		{
			if (items.length <= 0)
			{
				return false;
			}

			items.map(item => {
				let element = JSON.parse(item.VALUE);
				this.queue.push(element);
				this.process[this.TYPE_LISTEN].push(element);
			});

			this.processItems();

			return true;
		})
	});

	return true;
};

ChatDialog.disk.requestFolderId = function()
{
	if (!this.chatId)
	{
		console.warn('ChatDialog.disk.getFolderId: chatId is not defined');
		return false;
	}

	if (this.folderId)
	{
		return this.folderId;
	}

	if (this.requestFolderIdSend)
	{
		return false;
	}

	this.requestFolderIdSend = true;

	BX.rest.callMethod('im.disk.folder.get', {CHAT_ID: this.chatId}).then((result) => {
		let folderData = result.data();
		if (folderData)
		{
			this.setFolderId(folderData.ID);
		}
		this.requestFolderIdSend = false;
	}).catch(() => {
		this.requestFolderIdSend = false;
	});

	return null;
};

ChatDialog.disk.processItems = function()
{
	if (
		this.process[this.TYPE_ADD].length <= 0
		&& this.process[this.TYPE_LISTEN].length <= 0
	)
	{
		return true;
	}

	if (this.chatId <= 0)
	{
		return false;
	}

	if (this.folderId <= 0)
	{
		this.requestFolderId();
		return false;
	}

	[this.TYPE_LISTEN, this.TYPE_ADD].map(type =>
	{
		for (let i=0, l=this.process[type].length; i<l; i++)
		{
			let item = this.process[type][i];
			if (!item)
			{
				continue;
			}

			let isImage = BXIM.disk.isImage(item.params.type);
			let isVideo = !isImage && BXIM.disk.isVideo(item.params.type);

			if (!this.nodes[item.taskId])
			{
				let previewUrl = '';
				if (item.previewUrl)
				{
					previewUrl = BX.MobileUploadProvider.toBXUrl(item.previewUrl);
				}
				let detailUrl = '';
				if (item.url.startsWith('file://'))
				{
					detailUrl = BX.MobileUploadProvider.toBXUrl(item.url);
				}
				else
				{
					detailUrl = previewUrl;
				}

				BXIM.disk.fileRegister({
					id: item.taskId,
					isImage: isImage,
					isVideo: isVideo,
					url: detailUrl,
					preview: previewUrl,
					name: item.name,
					size: item.size || 0
				}, {
					dialogId: item.params.dialogId,
					chatId: item.params.chatId,
					silentMode: item.params.silentMode,
				});

				if (BX('im-file-'+item.taskId))
				{
					this.nodes[item.taskId] = BX('im-file-'+item.taskId);
				}
			}

			let itemConfig = {
				url: item.url,
				resize: item.resize,
				params: item.params,
				name: item.name,
				type: item.params.type,
				taskId: item.taskId,
				previewUrl: !isImage? item.previewUrl: '',
				onDestroyEventName: ChatUploaderEvents.DISK_MESSAGE_ADD_SUCCESS
			};
			if (this.nodes[item.taskId])
			{
				itemConfig.progressNode = BX.findChildByClassName(BX('im-file-'+item.taskId), "bx-messenger-file-image-src");
				itemConfig.imageNode = BX.findChildByClassName(BX('im-file-'+item.taskId), "bx-messenger-file-image-text");
			}

			if (type == this.TYPE_ADD)
			{
				itemConfig.params.dialogId = this.getDialogId();
				itemConfig.params.chatId = this.getChatId();
				itemConfig.params.hasPreview = item.preview != '';
				itemConfig.folderId = this.getFolderId();

				this.addToCache(item);
				BX.MobileUploadProvider.addTasks([itemConfig]);
			}
			else
			{
				itemConfig.params.dialogId = item.params.dialogId;
				itemConfig.params.chatId = item.params.chatId;
				itemConfig.folderId = item.folderId;

				BX.MobileUploadProvider.attachToTasks([itemConfig]);
			}

			this.process[type][i] = null;
		}
	});

};

ChatDialog.disk.getType = function(type)
{
	type = type.toString().toLowerCase().split('.').splice(-1)[0];

	switch(type)
	{
		case 'png':
		case 'jpe':
		case 'jpg':
		case 'jpeg':
		case 'gif':
		case 'heic':
		case 'bmp':
		case 'webp':
			return 'image';

		case 'mp4':
		case 'mkv':
		case 'webm':
		case 'mpeg':
		case 'hevc':
		case 'avi':
		case '3gp':
		case 'flv':
		case 'm4v':
		case 'ogg':
		case 'wmv':
		case 'mov':
			return 'video';

		case 'mp3':
			return 'audio';
	}

	return 'file';
}

ChatDialog.disk.uploadFile = function (item)
{
	item.taskId = this.getTmpId()+(item.index? item.index: '');
	var fileType = this.getType(item.name);
	var needConvert = fileType === 'image' && item.type !== 'image/gif' || fileType === 'video';

	console.warn(item);
	if (needConvert)
	{
		item.resize = {
			"quality":80,
			"width":1920,
			"height":1080,
		};
	}
	else
	{
		item.preview = '';
		item.previewUrl = '';
		item.previewHeight = '';
		item.previewWidth = '';
	}

	delete item.index;

	this.process[this.TYPE_ADD].push(item);
	this.processItems();
};

ChatDialog.disk.addToCache = function (item)
{
	this.queue.push(
		ChatUtils.objectClone(item)
	);

	this.base.database.table(ChatTables.diskFileQueue).then(table => {
		table.add({id: item.taskId, value : item});
	});

	return true;
};

ChatDialog.disk.removeFromCache = function (taskId)
{
	let index = this.queue.findIndex((item) => item && item.taskId == taskId);
	if (index > -1)
	{
		this.queue[index] = null;
	}

	this.base.database.table(ChatTables.diskFileQueue).then(table => {
		table.delete({id: taskId});
	});

	return true;
};

ChatDialog.disk.removeFromDialog = function (taskId)
{
	BX.MobileUploadProvider.removeTasks([taskId]);

	if (BX('im-message-tempFile'+taskId))
	{
		let element = BX.findChildByClassName(BXIM.messenger.popupMessengerBodyWrap, "bx-messenger-content-item-id-tempFile"+taskId, false);
		BX.remove(element);
	}

	this.removeFromCache(taskId);

	return true;
};

ChatDialog.disk.eventRouter = function (eventName, eventData, taskId)
{
	if (eventName != BX.MobileUploaderConst.FILE_UPLOAD_PROGRESS)
	{
		console.log("ChatDialog.disk.eventRouter: ", eventName, taskId, eventData);
	}

	if (eventName == BX.MobileUploaderConst.FILE_UPLOAD_START)
	{
		if (!eventData.file.params.hasPreview)
		{
			BXIM.disk.fileUpdateData(taskId, {
				size: eventData.file.file.size
			});
		}
	}
	else if (eventName == BX.MobileUploaderConst.FILE_CREATED)
	{
		this.removeFromCache(taskId);

		if (eventData.result.status == 'error')
		{
			BXIM.disk.fileAborted(taskId, eventData.result.errors[0].message);
		}
		else
		{
			BXIM.disk.fileUploaded(taskId, eventData.result.data.file.id);
		}
	}
	else if (eventName == BX.MobileUploaderConst.FILE_PROCESSING_DONE)
	{
		BXIM.disk.fileUpdateDetailFile(taskId, eventData.url)
	}
	else if (eventName == ChatUploaderEvents.DISK_MESSAGE_ADD_SUCCESS)
	{
		console.info('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_SUCCESS: ', eventData, taskId);
		BXIM.disk.fileSuccess(taskId, eventData.result);
	}
	else if (eventName == ChatUploaderEvents.DISK_MESSAGE_ADD_FAIL)
	{
		console.error('ChatDialog.disk.eventRouter: DISK_MESSAGE_ADD_FAIL: ', eventData, taskId);
		let error = BX.message('IM_F_ERROR');
		if (eventData.error.code === 'ERROR_FROM_OTHER_MODULE')
		{
			error = eventData.error.description;
		}
		BXIM.disk.fileAborted(taskId, error);
	}
	else if (
		eventName == BX.MobileUploaderConst.TASK_CANCELLED
		|| eventName == BX.MobileUploaderConst.TASK_NOT_FOUND
	)
	{
		this.removeFromDialog(taskId);
	}
	else if (
		eventName == BX.MobileUploaderConst.FILE_CREATED_FAILED
		|| eventName == BX.MobileUploaderConst.FILE_UPLOAD_FAILED
		|| eventName == BX.MobileUploaderConst.FILE_READ_ERROR
	)
	{
		this.removeFromCache(taskId);
		BXIM.disk.fileAborted(taskId, BX.message('IM_F_ERROR'));
	}


	return true;
};

/* Dialog Add recipient API */
ChatDialog.userDialog = {};

ChatDialog.userDialog.open = function (params)
{
	let {title, users, type = 'LIST', options} = params;

	const items = this.prepareItems(type, users, options);

	app.exec("openComponent", {
		name: "JSStackComponent",
		componentCode: "im.chat.user.list",
		scriptPath: "/mobile/mobile_component/im:im.chat.user.list/?version="+BX.componentParameters.get('WIDGET_CHAT_USERS_VERSION', '1.0.0'),
		params: {
			"LIST_TYPE": type,

			"DIALOG_ID": ChatMessengerCommon.getDialogId(),
			"USER_ID": this.base.userId,

			"ITEMS": items
		},
		rootWidget: {
			name: "list",
			settings: {
				objectName: "ChatUserListInterface",
				title: title,
				items: items,
			}
		}
	}, false);
};

ChatDialog.userDialog.prepareItems = function (type, users, options)
{
	let items = [];
	let itemsIndex = {};

	if (type == 'USERS')
	{
		let {
			isChatOwner = false,
			isLines = false,
			linesUsers = [],
			chatOwnerId = 0
		} = options;

		users.map(element =>
		{
			if (!element || itemsIndex[element.id] || !element.active)
			{
				return false;
			}

			element.id = parseInt(element.id);

			let item = ChatDataConverter.getSearchElementFormat(element);
			item.actions = [];

			if (isChatOwner)
			{
				if (isLines)
				{
					if (
						this.base.userId != item.id
						&& linesUsers.indexOf(item.id) < 0
					)
					{
						item.actions.push({
							title : BX.message("CHAT_USER_LIST_KICK"),
							identifier : "kick",
							iconName : "action_delete",
							destruct : true,
							color : "#df532d"
						});
					}
				}
				else if (this.base.userId != item.id)
				{
					item.actions.push({
						title : BX.message("CHAT_USER_LIST_OWNER"),
						identifier : "owner",
						color : "#aac337"
					});
					item.actions.push({
						title : BX.message("CHAT_USER_LIST_KICK"),
						identifier : "kick",
						destruct : true,
						color : "#df532d"
					});
				}
			}

			if (item.id == chatOwnerId)
			{
				item.styles.title.image = {name: 'name_status_owner'};
			}

			items.push(item);
			itemsIndex[element.id] = true;

			return true;
		});
	}
	else
	{
		users.map(element =>
		{
			if (!element || itemsIndex[element.id] || !element.active)
			{
				return false;
			}

			items.push(ChatDataConverter.getSearchElementFormat(element));
			itemsIndex[element.id] = true;

			return true;
		});
	}

	return items;
};


ChatDialog.userAddDialog = {};

ChatDialog.userAddDialog.open = function (dialogId)
{
	this.dialogId = dialogId;

	let listUsers = this.prepareItems();

	let skipList = ChatMessengerCommon.getChatUsers();
	if (skipList.indexOf(this.base.userId) == -1)
	{
		skipList.push(this.base.userId)
	}

	app.exec("openComponent", {
		name: "JSStackComponent",
		componentCode: "im.chat.user.selector",
		scriptPath: "/mobile/mobile_component/im:im.chat.user.selector/?version="+BX.componentParameters.get('WIDGET_CHAT_RECIPIENTS_VERSION', '1.0.0'),
		params: {
			"DIALOG_ID": dialogId,
			"USER_ID": this.base.userId,

			"LIST_USERS": listUsers,
			"LIST_DEPARTMENTS": [],
			"SKIP_LIST": skipList,

			"SEARCH_MIN_SIZE": BX.componentParameters.get('SEARCH_MIN_TOKEN_SIZE', 3),
		},
		rootWidget: {
			name: "chat.recipients",
			settings: {
				objectName: "ChatUserSelectorInterface",
				title: BX.message('IM_M_MENU_USERS'),
				limit: 100,
				items: listUsers.map(element => ChatDataConverter.getListElementByUser(element)),
				scopes: [
					{ title: BX.message('IM_TRANSFER_SCOPE_USERS'), id: "user" },
					{ title: BX.message('IM_TRANSFER_SCOPE_DEPARTMENTS'), id: "department" }
				],
				modal: true,
			}
		}
	}, false);
};

ChatDialog.userAddDialog.prepareItems = function ()
{
	let items = [];
	let itemsIndex = {};

	if (this.base.widgets.recentList.length > 0)
	{
		this.base.widgets.recentList.map(element =>
		{
			if (!element || itemsIndex[element.id])
			{
				return false;
			}
			if (element.type !== 'user')
			{
				return false;
			}

			if (element.user.network || element.user.connector)
			{
				return false;
			}

			items.push(element.user);
			itemsIndex[element.id] = true;

			return true;
		});
	}

	this.base.widgets.colleaguesList.map(element =>
	{
		if (!element || itemsIndex[element.id])
		{
			return false;
		}

		if (element.network || element.connector)
		{
			return false;
		}

		items.push(element);
		itemsIndex[element.id] = true;
	});

	this.base.widgets.lastSearchList.map(element =>
	{
		if (!element || itemsIndex[element.id])
		{
			return false;
		}

		if (!element)
		{
			return false;
		}
		if (element.type == 'user')
		{
			if (element.user.network || element.user.connector)
			{
				return false;
			}

			items.push(element.user);
			itemsIndex[element.id] = true;
		}

		return true;
	});

	let skipList = ChatMessengerCommon.getChatUsers();
	if (skipList.indexOf(this.base.userId) == -1)
	{
		skipList.push(this.base.userId)
	}
	items = items.filter((element) => skipList.indexOf(element.id) == -1);

	return items;
};


/* Dialog Lines transfer  API */
ChatDialog.transferDialog = {};

ChatDialog.transferDialog.open = function (chatId)
{
	this.chatId = chatId;

	let listUsers = this.prepareItems().filter(element => !element.bot);
	let listLines = this.prepareItems('line');

	app.exec("openComponent", {
		name: "JSStackComponent",
		componentCode: "im.chat.transfer.selector",
		scriptPath: "/mobile/mobile_component/im:im.chat.transfer.selector/?version="+BX.componentParameters.get('WIDGET_CHAT_TRANSFER_VERSION', '1.0.0'),
		params: {
			"CHAT_ID": chatId,
			"USER_ID": this.base.userId,

			"LIST_USERS": listUsers,
			"LIST_LINES": listLines,
			"SKIP_LIST": [this.base.userId],

			"SEARCH_MIN_SIZE": BX.componentParameters.get('SEARCH_MIN_TOKEN_SIZE', 3),
			"BUSINESS_ONLY": this.base.widgets.businessUsersList !== false,
		},
		rootWidget: {
			name: "chat.recipients",
			settings: {
				objectName: "ChatTransferSelectorInterface",
				title: BX.message('IM_M_MENU_TRANSFER'),
				singleChoose: true,
				items: listUsers.map(element => ChatDataConverter.getListElementByUser(element)),
				scopes: listLines.length <= 0? []: [
					{ title: BX.message('IM_TRANSFER_SCOPE_USERS'), id: "user" },
					{ title: BX.message('IM_TRANSFER_SCOPE_LINES'), id: "line" }
				],
				modal: true,
			}
		}
	}, false);
};

ChatDialog.transferDialog.prepareItems = function (type = 'user')
{
	let items = [];
	let itemsIndex = {};

	if (type == 'user')
	{
		if (this.base.widgets.businessUsersList !== false)
		{
			if (this.base.widgets.businessUsersList.length > 0)
			{
				this.base.widgets.businessUsersList.map(element =>
				{
					if (!element || itemsIndex[element.id])
					{
						return false;
					}

					items.push(element);
					itemsIndex[element.id] = true;

					return true;
				});
			}
		}
		else
		{
			if (this.base.widgets.recentList.length > 0)
			{
				this.base.widgets.recentList.map(element =>
				{
					if (!element || itemsIndex[element.id])
					{
						return false;
					}
					if (element.type == 'user')
					{
						items.push(element.user);
						itemsIndex[element.id] = true;
					}

					return true;
				});
			}

			this.base.widgets.colleaguesList.map(element =>
			{
				if (!element || itemsIndex[element.id])
				{
					return false;
				}
				items.push(element);
			});

			this.base.widgets.lastSearchList.map(element =>
			{
				if (!element || itemsIndex[element.id])
				{
					return false;
				}
				if (element.type == 'user')
				{
					items.push(element.user);
					itemsIndex[element.id] = true;
				}

				return true;
			});
		}

		items = items.filter((element) => element.id != this.base.userId);
	}
	else if (type == 'line')
	{
		BXIM.messenger.openlines.queue.sort(function(i1, i2) {
			if (i1.transfer_count > i2.transfer_count) {
				return -1;
			}
			else if (i1.transfer_count < i2.transfer_count)
			{
				return 1;
			}
			else
			{
				if (i1.id > i2.id)
				{
					return 1;
				}
				else if (i1.id < i2.id)
				{
					return -1;
				}
				else
				{
					return 0;
				}
			}
		});
		items = BXIM.messenger.openlines.queue;
	}

	return items;
};

ChatDialog.transferDialog.closeDialog = function(chatId)
{
	if (this.chatId == chatId)
	{
		app.closeController();
	}
};

/* Initialization */
ChatDialog.init();