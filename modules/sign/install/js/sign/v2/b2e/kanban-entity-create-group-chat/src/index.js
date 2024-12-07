import { Messenger } from 'im.public';
import { Api } from 'sign.v2.api';
import { FeatureResolver } from 'sign.feature-resolver';

import './style.css';

export class KanbanEntityCreateGroupChat
{
	#api: Api;
	init(): void
	{
		this.#api = new Api();
	}

	async onCreateGroupChatButtonClickHandler(event): Promise<void>
	{
		const featureResolver = FeatureResolver.instance();
		if (featureResolver.released('createDocumentChat'))
		{
			const button = event.currentTarget;
			const parentElement = button.closest('[data-id]');
			const documentId = parentElement.getAttribute('data-id');
			const chatType = button.getAttribute('chat-type');
			const chatId = (await this.#api.createDocumentChat(chatType, documentId, true)).chatId;
			Messenger.openChat(`chat${chatId}`);
		}
	}
}
