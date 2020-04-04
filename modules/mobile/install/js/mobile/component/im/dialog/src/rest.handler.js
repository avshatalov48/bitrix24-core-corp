/**
 * Bitrix Mobile Dialog
 * Dialog Rest answers (Rest Answer Handler)
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2019 Bitrix
 */

import {BaseRestAnswerHandler} from "im.provider.rest";
import {LocalStorage} from "im.tools.localstorage";
import {EventType} from "im.const";

export class MobileRestAnswerHandler extends BaseRestAnswerHandler
{
	constructor(params)
	{
		super(params);

		if (typeof params.context === 'object' && params.context)
		{
			this.context = params.context;
		}
	}

	handleImChatGetSuccess(data)
	{
		this.store.commit('application/set', {dialog: {
			chatId: data.id,
			dialogId: data.dialog_id,
			diskFolderId: data.disk_folder_id,
		}});

		this.context.redrawHeader();
	}
	handleImChatGetError(error)
	{
		if (error.ex.error === 'ACCESS_ERROR')
		{
			app.closeController();
		}
	}

	handleMobileBrowserConstGetSuccess(data)
	{
		this.store.commit('application/set', {disk: {
			enabled: true,
			maxFileSize: data.phpUploadMaxFilesize
		}});

		this.context.addLocalize(data);
		BX.message(data);

		LocalStorage.set(this.controller.getSiteId(), 0, 'serverVariables', data || {});
	}

	handleImDialogMessagesGetInitSuccess()
	{
		this.controller.emit(EventType.dialog.sendReadMessages);
	}

	handleImMessageAddSuccess(messageId, message)
	{
		this.context.messagesQueue = this.context.messagesQueue.filter(el => el.id !== message.id);
	}

	handleImMessageAddError(error, message)
	{
		this.context.messagesQueue = this.context.messagesQueue.filter(el => el.id !== message.id);
	}

	handleImDiskFileCommitSuccess(result, message)
	{
		this.context.messagesQueue = this.context.messagesQueue.filter(el => el.id !== message.id);
	}
}