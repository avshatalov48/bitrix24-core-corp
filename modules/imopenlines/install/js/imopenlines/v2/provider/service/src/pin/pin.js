import { Core } from 'im.v2.application.core';
import { runAction } from 'im.v2.lib.rest';
import { RestMethod } from 'imopenlines.v2.const';

type requestActionParams = {
	dialogId: string,
	action: boolean,
	restMethod: string,
}

export class PinService
{
	pinChat(dialogId: string): Promise
	{
		return this.#sendRequest({
			dialogId,
			action: true,
			restMethod: RestMethod.linesV2SessionPin,
		});
	}

	unpinChat(dialogId: string): Promise
	{
		return this.#sendRequest({
			dialogId,
			action: false,
			restMethod: RestMethod.linesV2SessionUnpin,
		});
	}

	#sendRequest(actionParams: requestActionParams): Promise
	{
		const session = Core.getStore().getters['recentOpenLines/getSession'](actionParams.dialogId);

		void Core.getStore().dispatch('sessions/pin', {
			id: session.id,
			chatId: session.chatId,
			action: actionParams.action,
		});

		const queryParams = {
			data: {
				dialogId: actionParams.dialogId,
			},
		};

		return runAction(actionParams.restMethod, queryParams)
			.catch((error) => {
				console.error('Imol.MarkSpam: request error', error);

				void Core.getStore().dispatch('sessions/pin', {
					id: session.id,
					action: !actionParams.action,
				});
			});
	}
}
