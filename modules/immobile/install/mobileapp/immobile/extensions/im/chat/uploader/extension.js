"use strict";

/**
 * @requires module:db
 * @requires module:chat/tables
 * @requires module:chat/uploaderconst
 * @module chat/uploader
 */

var ChatUploader = {};

ChatUploader.init = function()
{
	BX.addCustomEvent("onFileUploadStatusChanged", this.listener.bind(this));

	this.userId = BX.componentParameters.get("USER_ID", 0);
	this.languageId = BX.componentParameters.get("LANGUAGE_ID", "en");

	this.database = new ReactDatabase(ChatDatabaseName, this.userId, this.languageId);
	this.storage = Application.sharedStorage(`chatBackgroundQueue_${this.userId}`);
	this.restartUploadTasks();

	BX.addCustomEvent('chatuploader::task::restart', () => this.restartUploadTasks());

	BX.addCustomEvent('chatbackground::task::status::success', (taskId, data, fileData) => {
		this.executeBackgroundTaskSuccess(taskId, data, fileData);
	});
	BX.addCustomEvent('chatbackground::task::status::failure', (taskId, code, text, status, extra) => {
		this.executeBackgroundTaskFailure(taskId, code, text, status, extra);
	});
};

ChatUploader.listener = function(event, data, taskId)
{
	if (!taskId.startsWith('imDialog'))
	{
		return false;
	}

	if (event === BX.FileUploadEvents.FILE_CREATED)
	{
		if (data && data.result && data.result.data && data.result.data.file)
		{
			const fileId = data.result.data.file.id;

			this.fileCommit(taskId, fileId, data.file);
		}
		else
		{
			console.error('ChatUploader.listener error', event, data, taskId);
			this.removeUploadTaskFromStorage(taskId);

			return;
		}
	}

	this.handleUploadTask(event, data, taskId);

	return true;
};

ChatUploader.fileCommit = function(taskId, fileId, fileData)
{
	let chatId = fileData.params.chatId;
	let silentMode = fileData.params.silentMode;
	let messageText = '';
	fileData.fileId = fileId;

	console.info('ChatUploader.fileCommit:', [taskId, fileId, fileData]);

	ChatBackgroundTasks.addTask(taskId, ['im.disk.file.commit', {
		'CHAT_ID': chatId,
		'UPLOAD_ID': fileId,
		'MESSAGE_TEXT': messageText,
		'SILENT_MODE': silentMode? 'Y': 'N',
		'TEMPLATE_ID': fileData.params.file? fileData.params.id: 0,
		'FILE_TEMPLATE_ID': fileData.params.file? fileData.params.file.id: 0,
	}], fileData);
};

ChatUploader.fileRemove = function (taskId, fileId, fileData)
{
	let chatId = fileData.params.chatId;

	console.error('ChatUploader.fileRemove:', [taskId, fileId, fileData]);

	BX.rest.callMethod('im.disk.file.delete', {
		'CHAT_ID': chatId,
		'DISK_ID': fileId,
	})
};

ChatUploader.handleUploadTask = function(eventName, eventData, taskId)
{
	console.log('ChatUploader.handleUploadTask: ', eventName, eventData, taskId);

	switch (eventName) {
		case 'onloadstart':
			this.uploadTasks[taskId] = { taskId, eventData };
			this.storage.set('uploadTasks', JSON.stringify(this.uploadTasks));
			break;
		case 'onimdiskmessageaddsuccess':
			this.removeUploadTaskFromStorage(taskId);
			break;
		case 'onimdiskmessageaddfail':
		case 'onloadstartfailed':
		case 'onerrorfilecreate':
		case 'onfileuploadfailed':
		case 'ontasknotfound':
		case 'onfilereaderror':
			if (
				eventData.error.error
				&& eventData.error.error.code
				&& eventData.error.error.code === -2 // no internet connection
			)
			{
				FileUploadAgent.FileUploader.deleteTask(taskId);
				break;
			}
			this.removeUploadTaskFromStorage(taskId);
			break;
		case 'ontaskcancelled':
			this.removeUploadTaskFromStorage(taskId);
			break;
		default:
			break;
	}
};

ChatUploader.getUploadTasksFromStorage = function()
{
	const uploadActionsRaw = this.storage.get('uploadTasks');

	return uploadActionsRaw ? JSON.parse(uploadActionsRaw) : {};
};

ChatUploader.restartUploadTasks = function()
{
	this.uploadTasks = this.getUploadTasksFromStorage();
	if (Object.keys(this.uploadTasks).length === 0)
	{
		return;
	}

	const files = Object.values(this.uploadTasks)
		.filter(uploadTask => uploadTask.taskId.startsWith('imDialog'))
		.map(uploadTask => {
			return { taskId: uploadTask.taskId };
		});

	BX.postComponentEvent('onFileUploadTaskRequest', [{ files }], 'background');
};

ChatUploader.removeUploadTaskFromStorage = function(taskId)
{
	if (this.uploadTasks.length === 0)
	{
		return;
	}

	if (this.uploadTasks.hasOwnProperty(taskId))
	{
		delete this.uploadTasks[taskId];
	}

	this.storage.set('uploadTasks', JSON.stringify(this.uploadTasks));
}

ChatUploader.executeBackgroundTaskSuccess = function(taskId, resultData, fileData)
{
	if (taskId.toString().startsWith('imDialog'))
	{
		let eventData = {
			file: fileData,
			result: resultData
		};
		this.database.table(ChatTables.diskFileQueue).then(table => table.delete({id: taskId}));
		FileUploadAgent.postFileEvent(ChatUploaderEvents.DISK_MESSAGE_ADD_SUCCESS, eventData, taskId)

		if (typeof fabric != 'undefined')
		{
			fabric.Answers.sendCustomEvent("imFileCommit", {});
		}
	}
}

ChatUploader.executeBackgroundTaskFailure = function(taskId, code, text, status, fileData)
{
	if (!taskId.toString().startsWith('imDialog'))
	{
		return false;
	}
	let eventData = {
		file: fileData,
		error: {
			'status': status,
			'code': code,
			'description': text,
		}
	};
	this.fileRemove(taskId, fileData.fileId, fileData);
	FileUploadAgent.postFileEvent(ChatUploaderEvents.DISK_MESSAGE_ADD_FAIL, eventData, taskId);
}

ChatUploader.init();