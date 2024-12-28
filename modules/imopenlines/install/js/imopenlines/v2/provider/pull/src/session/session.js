import { Core } from 'im.v2.application.core';

import type { SessionUpdateParams } from '../types/session';

export class SessionPullHandler
{
	constructor()
	{
		this.store = Core.getStore();
	}

	getModuleId(): string
	{
		return 'imopenlines';
	}

	handleUpdateSessionStatus(params: SessionUpdateParams): void
	{
		const sessionItem = params.session;

		const isClosed = sessionItem.isClosed;

		if (!isClosed)
		{
			void this.store.dispatch('recentOpenLines/set', {
				id: params.chat.dialogId,
				messageId: params.message.id,
				sessionId: sessionItem.id,
			});
		}

		void this.store.dispatch('sessions/set', sessionItem);
	}
}
