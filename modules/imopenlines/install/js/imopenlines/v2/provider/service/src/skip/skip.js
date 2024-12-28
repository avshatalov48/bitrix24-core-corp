import { Messenger } from 'im.public';
import { Core } from 'im.v2.application.core';
import { Layout } from 'im.v2.const';
import { LayoutManager } from 'im.v2.lib.layout';
import { runAction } from 'im.v2.lib.rest';
import { RestMethod } from 'imopenlines.v2.const';

export class SkipService
{
	requestSkip(dialogId: string): Promise
	{
		const chatIsOpened = Core.getStore().getters['application/isLinesChatOpen'](dialogId);

		if (chatIsOpened)
		{
			void Messenger.openLines();
		}

		void Core.getStore().dispatch('recentOpenLines/delete', {
			id: dialogId,
		});

		LayoutManager.getInstance().setLastOpenedElement(Layout.openlinesV2.name, '');

		const queryParams = {
			data: {
				dialogId,
			},
		};

		return runAction(RestMethod.linesV2SessionSkip, queryParams)
			.catch((error) => {
				console.error('Imol.OperatorAnswer: request error', error);
			});
	}
}
