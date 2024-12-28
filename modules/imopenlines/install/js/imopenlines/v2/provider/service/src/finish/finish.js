import { Messenger } from 'im.public';
import { Core } from 'im.v2.application.core';
import { Layout } from 'im.v2.const';
import { LayoutManager } from 'im.v2.lib.layout';
import { runAction } from 'im.v2.lib.rest';
import { RestMethod } from 'imopenlines.v2.const';

export class FinishService
{
	markSpamChat(dialogId: string): Promise
	{
		this.#updateModel(dialogId);

		const queryParams = {
			data: {
				dialogId,
			},
		};

		return runAction(RestMethod.linesV2SessionMarkSpam, queryParams)
			.catch((error) => {
				console.error('Imol.MarkSpam: request error', error);
			});
	}

	finishChat(dialogId: string): Promise
	{
		this.#updateModel(dialogId);

		const queryParams = {
			data: {
				dialogId,
			},
		};

		return runAction(RestMethod.linesV2SessionFinish, queryParams)
			.catch((error) => {
				console.error('Imol.Finish: request error', error);
			});
	}

	#updateModel(dialogId: string)
	{
		const chatIsOpened = Core.getStore().getters['application/isLinesChatOpen'](dialogId);
		const chatId = Core.getStore().getters['chats/get'](dialogId).chatId;
		const session = Core.getStore().getters['sessions/getByChatId'](chatId);

		if (chatIsOpened)
		{
			void Messenger.openLines();
			this.#clearLastOpenedElement();
		}

		void Core.getStore().dispatch('sessions/set', {
			...session,
			isClosed: true,
		});

		void Core.getStore().dispatch('recentOpenLines/delete', {
			id: dialogId,
		});
	}

	#clearLastOpenedElement(): void
	{
		LayoutManager.getInstance().setLastOpenedElement(Layout.openlinesV2.name, '');
	}
}
