import { Core } from 'im.v2.application.core';
import { RawSession } from 'imopenlines.v2.provider.service';

export const OpenLinesManager = {
	async handleChatLoadResponse(sessionData: RawSession): Promise
	{
		if (!sessionData)
		{
			return Promise.resolve();
		}

		return Core.getStore().dispatch('sessions/set', sessionData);
	},
};
