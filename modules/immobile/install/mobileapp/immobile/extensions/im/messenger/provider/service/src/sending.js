/**
 * @module im/messenger/provider/service/sending
 */
jn.define('im/messenger/provider/service/sending', (require, exports, module) => {
	const { Type } = require('type');
	const { Uuid } = require('utils/uuid');

	const { EventType } = require('im/messenger/const');
	const { core } = require('im/messenger/core');
	const { Logger } = require('im/messenger/lib/logger');
	const { FileService } = require('im/messenger/provider/service/classes/sending/file');

	/**
	 * @class SendingService
	 */
	class SendingService
	{
		/*
		* @return {SendingService}
		*/
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		constructor()
		{
			/** @private */
			this.store = core.getStore();

			/** @private */
			this.fileService = new FileService();
		}

		sendFilesFromDevice(dialogId, deviceFileList)
		{
			if (deviceFileList.length === 0)
			{
				return;
			}

			Logger.log('SendingService.sendFilesFromDevice', dialogId, deviceFileList);

			this.fileService.getDiskFolderId(dialogId)
				.then((diskFolderId) => {
					deviceFileList.forEach((deviceFile) => {
						const temporaryMessageId = Uuid.getV4();
						const temporaryFileId = Uuid.getV4();
						const fileToUpload = {
							temporaryMessageId,
							temporaryFileId,
							deviceFile,
							diskFolderId,
							dialogId,
						};

						this.fileService.uploadFile(fileToUpload)
							.then(() => {
								this.sendMessage({
									dialogId: dialogId,
									temporaryMessageId: temporaryMessageId,
									fileId: temporaryFileId,
								});
							})
						;
					});
				})
			;
		}

		cancelFileUpload(temporaryMessageId, temporaryFileId)
		{
			this.fileService.cancelFileUpload(temporaryMessageId, temporaryFileId);
		}

		sendMessage(params)
		{
			const { text = '', fileId = '', temporaryMessageId, dialogId } = params;
			if (!Type.isStringFilled(text) && !Type.isStringFilled(fileId))
			{
				return Promise.resolve();
			}

			Logger.warn('SendingService: sendMessage', params);

			const message = this.prepareMessage({ text, fileId, temporaryMessageId, dialogId });

			return this.handlePagination(dialogId).then(() => {
				return this.addMessageToModels(message);
			}).then(() => {
				this.sendScrollEvent({ dialogId });
				// this._sendMessageToServer(message);
			});
		}

		sendFilesFromDisk(dialogId, diskFileList)
		{
			Object.values(diskFileList).forEach((file) => {
				const messageWithFile = this.prepareFileFromDisk(file, dialogId);

				this.fileService.uploadFileFromDisk(messageWithFile)
					.then(() => {
						return this.sendMessage({
							temporaryMessageId: messageWithFile.temporaryMessageId,
							fileId: messageWithFile.temporaryFileId,
							dialogId: messageWithFile.dialogId,
						});
					})
					.then(() => {
						this.fileService.commitFile({
							chatId: messageWithFile.chatId,
							temporaryFileId: messageWithFile.temporaryFileId,
							temporaryMessageId: messageWithFile.temporaryMessageId,
							realFileId: messageWithFile.realFileId,
							fromDisk: true,
						});
					})
				;
			});
		}

		/**
		 * @private
		 */
		prepareFileFromDisk(file, dialogId)
		{
			const temporaryMessageId = Uuid.getV4();
			const realFileId = file.dataAttributes.ID;
			const temporaryFileId = `${temporaryMessageId}|${realFileId}`;

			return {
				temporaryMessageId,
				temporaryFileId,
				realFileId,
				dialogId,
				file,
				chatId: this.getDialog(dialogId).chatId,
			};
		}

		/**
		 * @private
		 */
		prepareMessage(params)
		{
			const {
				text,
				fileId,
				temporaryMessageId,
				dialogId,
			} = params;

			const messageParams = {};
			if (fileId)
			{
				messageParams.FILE_ID = [fileId];
			}

			const temporaryId = temporaryMessageId || Uuid.getV4();

			return {
				uuid: temporaryId,
				chatId: this.getDialog(dialogId).chatId,
				dialogId: dialogId,
				authorId: core.getUserId(),
				text,
				date: new Date(),
				params: messageParams,
				withFile: !!fileId,
				unread: false,
				sending: true,
			};
		}

		/**
		 * @private
		 */
		handlePagination(dialogId)
		{
			if (!this.getDialog(dialogId).hasNextPage)
			{
				return Promise.resolve();
			}

			Logger.warn('SendingService: sendMessage: there are unread pages, move to chat end');

			// TODO: handle pagination
			return Promise.resolve();
		}

		/**
		 * @private
		 */
		addMessageToModels(message)
		{
			return this.store.dispatch('messagesModel/add', message);
		}

		/**
		 * @private
		 */
		sendScrollEvent(options = {})
		{
			const { dialogId } = options;
			const scrollToBottomEventData = {
				chatId: this.getDialog(dialogId).chatId,
			};

			BX.postComponentEvent(EventType.dialog.external.scrollToBottom, [scrollToBottomEventData]);
		}

		/**
		 * @private
		 */
		getDialog(dialogId)
		{
			return this.store.getters['dialoguesModel/getById'](dialogId) || {};
		}
	}

	module.exports = {
		SendingService,
	};
});
