import { Core } from 'im.v2.application.core';
import { OpenLinesMessageManager } from 'imopenlines.v2.lib.message-manager';
import { RawSession } from 'imopenlines.v2.provider.service';

import type { MessageComponentValues } from 'imopenlines.v2.lib.message-manager';
import type { ImModelMessage } from 'im.v2.model';

export const OpenLinesManager = {
	async handleChatLoadResponse(sessionData: RawSession): Promise
	{
		if (!sessionData)
		{
			return Promise.resolve();
		}

		return Core.getStore().dispatch('sessions/set', sessionData);
	},
	getMessageName(message: ImModelMessage): ?MessageComponentValues
	{
		const openLinesManager = new OpenLinesMessageManager(message);

		if (openLinesManager.checkComponentInOpenLinesList())
		{
			return openLinesManager.getMessageComponent();
		}

		return null;
	},
};
