import { Type } from 'main.core';

import { Messenger } from 'im.public';
import { Layout } from 'im.v2.const';
import { LayoutManager } from 'im.v2.lib.layout';
import { Core } from 'im.v2.application.core';
import { Logger } from 'im.v2.lib.logger';

import type {
	ChatHideParams, MessageAddParams,
	ReadMessageParams,
	UnreadMessageParams,
} from 'im.v2.provider.pull';
import type { ChatUserLeaveParams } from 'imopenlines.v2.provider.pull';

export class LinesPullHandler
{
	constructor()
	{
		this.store = Core.getStore();
	}

	getModuleId(): string
	{
		return 'im';
	}

	handleMessage(params, extra)
	{
		this.handleMessageAdd(params, extra);
	}

	handleMessageChat(params)
	{
		this.handleMessageAdd(params);
		this.updateUnloadedLinesCounter(params);
	}

	handleMessageAdd(params: MessageAddParams)
	{
		if (!params.lines)
		{
			return;
		}

		const userId = Core.getUserId();
		const userInChat = params.userInChat[params.chatId];

		const isClosed = params.lines.isClosed;

		if (userInChat.includes(userId) && !isClosed)
		{
			void this.store.dispatch('recentOpenLines/set', {
				id: params.dialogId,
				messageId: params.message.id,
				sessionId: params.lines.id,
			});
		}

		void this.store.dispatch('sessions/set', {
			...params.lines,
			chatId: params.chatId,
			status: params.lines.statusGroup,
		});
	}

	handleReadMessageChat(params: ReadMessageParams)
	{
		this.updateUnloadedLinesCounter(params);
	}

	handleUnreadMessageChat(params: UnreadMessageParams)
	{
		this.updateUnloadedLinesCounter(params);
	}

	handleChatHide(params: ChatHideParams)
	{
		this.updateUnloadedLinesCounter({
			dialogId: params.dialogId,
			chatId: params.chatId,
			lines: params.lines,
			counter: 0,
		});

		const recentItem = this.store.getters['recentOpenLines/get'](params.dialogId);

		if (!recentItem)
		{
			return;
		}

		void this.store.dispatch('recentOpenLines/delete', {
			id: params.dialogId,
		});
	}

	updateUnloadedLinesCounter(params: {
		dialogId: string,
		chatId: number,
		counter: number,
		lines: ?Object<string, any>,
	})
	{
		const { dialogId, chatId, counter, lines } = params;
		if (!lines || Type.isUndefined(counter))
		{
			return;
		}

		Logger.warn('LinesPullHandler: updateUnloadedLinesCounter:', { dialogId, chatId, counter });
		void this.store.dispatch('counters/setUnloadedLinesCounters', { [chatId]: counter });
	}

	handleChatUserLeave(params: ChatUserLeaveParams)
	{
		const recentItem = this.store.getters['recentOpenLines/get'](params.dialogId);
		const chatIsOpened = Core.getStore().getters['application/isLinesChatOpen'](params.dialogId);

		const userId = Core.getUserId();

		if (chatIsOpened && params.userId === userId)
		{
			void Messenger.openLines();
			LayoutManager.getInstance().setLastOpenedElement(Layout.openlinesV2.name, '');
		}

		if (!recentItem || params.userId !== Core.getUserId())
		{
			return;
		}

		void this.store.dispatch('recentOpenLines/delete', {
			id: params.dialogId,
		});
	}
}
