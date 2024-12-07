import { Api } from 'sign.v2.api';
import { Event } from 'main.core';

type DocumentSendOptions = {};

export class DocumentSend extends Event.EventEmitter
{
	#api: Api;

	constructor(options: DocumentSendOptions = {})
	{
		super();
		this.setEventNamespace('bx:sign:v2:documentsend');
		this.#api = new Api();
	}

	loadStatus(memberIds: Array<number>): Promise
	{
		if (memberIds.length > 0)
		{
			return this.#api.memberLoadReadyForMessageStatus(memberIds)
				.then((response) => {
					const readyMembers = response?.readyMembers ?? [];
					this.#fireEvent(readyMembers);

					return readyMembers;
				})
			;
		}

		this.#fireEvent([]);

		return Promise.resolve([]);
	}

	send(memberIds: Array<number>): Promise
	{
		if (memberIds.length > 0)
		{
			return this.#api.memberResendMessage(memberIds);
		}

		return Promise.reject(new Error('empty members'));
	}

	#fireEvent(readyMembers: Array<number>): void
	{
		this.emit('ready', new Event.BaseEvent({
			data: {
				readyMembers,
			},
		}));
	}
}
