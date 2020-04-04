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
};

ChatUploader.listener = function(event, data, taskId)
{
	if (event == BX.FileUploadEvents.FILE_CREATED)
	{
		if (taskId.indexOf('imDialog') != 0)
			return false;

		let fileId = data.result.data.file.id;

		this.fileCommit(taskId, fileId, data.file);
	}

	return true;
};

ChatUploader.fileCommit = function(taskId, fileId, fileData)
{
	let chatId = fileData.params.chatId;
	let silentMode = fileData.params.silentMode;
	let messageText = '';

	console.info('ChatUploader.fileCommit:', [taskId, fileId, fileData]);

	BX.rest.callMethod('im.disk.file.commit', {
		'CHAT_ID': chatId,
		'UPLOAD_ID': fileId,
		'MESSAGE_TEXT': messageText,
		'SILENT_MODE': silentMode? 'Y': 'N',
		'TASK_ID': taskId,
	}).then((result) => {
		let eventData = {
			file: fileData,
			result: result.data()
		};
		this.database.table(ChatTables.diskFileQueue).then(table => table.delete({id: taskId}));
		FileUploadAgent.postFileEvent(ChatUploaderEvents.DISK_MESSAGE_ADD_SUCCESS, eventData, taskId)

		if (typeof fabric != 'undefined')
		{
			fabric.Answers.sendCustomEvent("imFileCommit", {});
		}
	}).catch((result) => {
		let error = result.error();
		let eventData = {
			file: fileData,
			error: {
				'code': error.ex.error,
				'description': error.ex.error_description,
			}
		};
		this.fileRemove(taskId, fileId, fileData);
		FileUploadAgent.postFileEvent(ChatUploaderEvents.DISK_MESSAGE_ADD_FAIL, eventData, taskId)
	})
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

ChatUploader.init();