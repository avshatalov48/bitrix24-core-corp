import { ajax as Ajax, Loc, Reflection } from 'main.core';

export class Responder
{
	#uid: string;

	constructor(uid: string)
	{
		this.#uid = uid;
	}

	answer(): void
	{
		if (this.#canUsePull())
		{
			this.#answerWithPull();
		}
		else
		{
			this.#answerWithAjax();
		}
	}

	#canUsePull(): boolean
	{
		return Reflection.getClass('BX.PULL.isPublishingEnabled') && BX.PULL.isPublishingEnabled() && BX.PULL.isConnected();
	}

	#answerWithPull(): void
	{
		BX.PULL.sendMessage([Loc.getMessage('USER_ID')], 'disk', 'bdisk', {
			// eslint-disable-next-line no-undef
			status: BXFileStorage.GetStatus().status,
			uidRequest: this.#uid,
		});
	}

	#answerWithAjax(): void
	{
		void Ajax.runAction('disk.documentService.setStatusWorkWithLocalDocument', {
			data: {
				// eslint-disable-next-line no-undef
				status: BXFileStorage.GetStatus().status,
				uidRequest: this.#uid,
			},
		});
	}
}
