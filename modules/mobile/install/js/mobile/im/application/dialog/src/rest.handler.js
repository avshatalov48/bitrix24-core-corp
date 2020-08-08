/**
 * Bitrix Mobile Dialog
 * Dialog Rest answers (Rest Answer Handler)
 *
 * @package bitrix
 * @subpackage mobile
 * @copyright 2001-2019 Bitrix
 */

import {BaseRestHandler} from "im.provider.rest";
import {LocalStorage} from "im.lib.localstorage";
import {EventType} from "im.const";

export class MobileRestAnswerHandler extends BaseRestHandler
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

		if (data.restrictions)
		{
			this.store.dispatch('dialogues/update', {
				dialogId: data.dialog_id,
				fields: data.restrictions
			});
		}

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
		this.controller.application.emit(EventType.dialog.sendReadMessages);
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