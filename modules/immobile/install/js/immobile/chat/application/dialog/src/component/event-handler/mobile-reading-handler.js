import { ReadingHandler } from 'im.event-handler';
import { Logger } from 'im.lib.logger';

export class MobileReadingHandler extends ReadingHandler
{
	onReadMessage({data: {id = null, skipAjax = false}}): Promise
	{
		return this.readMessage(id, true, true).then(messageData => {
			if (messageData.lastId <= 0 || skipAjax)
			{
				return;
			}

			this.addTaskToReadMessage(messageData);
		});
	}

	processMessagesToRead(chatId, skipAjax = false): Promise
	{
		const lastMessageToRead = this.getMaxMessageIdFromQueue(chatId);
		const dialogId = this.getDialogId();
		delete this.messagesToRead[chatId];
		if (lastMessageToRead <= 0)
		{
			return Promise.resolve({dialogId, lastId: lastMessageToRead});
		}

		return new Promise((resolve, reject) =>
		{
			this.readMessageOnClient(chatId, lastMessageToRead).then(readResult => {
				return this.decreaseChatCounter(chatId, readResult.count);
			}).then(() => {
				resolve({dialogId, lastId: lastMessageToRead});
			}).catch(error => {
				Logger.error('Reading messages error', error);
				reject();
			});
		});
	}

	addTaskToReadMessage(messageData)
	{
		BXMobileApp.Events.postToComponent('chatbackground::task::action', [
			'readMessage',
			`readMessage|${messageData.dialogId}`,
			messageData,
			false,
			200
		], 'background');
	}
}