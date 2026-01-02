import { Logger } from 'im.v2.lib.logger';
import { ChatService } from 'im.v2.provider.service.chat';
import { Messenger } from 'im.public';

import { OpenLinesContent } from '../content/openlines';
import { EmptyState } from './components/empty-state';

import './css/default-openlines-content.css';

// @vue/component
export const OpenLinesOpener = {
	name: 'OpenLinesOpener',
	components: { EmptyState, OpenLinesContent },
	props:
	{
		dialogId: {
			type: String,
			required: true,
		},
	},
	watch:
	{
		dialogId(newValue: string, oldValue: string)
		{
			Logger.warn(`OpenLinesContent: switching from ${oldValue || 'empty'} to ${newValue}`);
			void this.loadChat();
		},
	},
	created()
	{
		if (!this.dialogId)
		{
			return;
		}

		void this.loadChat();
	},
	methods:
	{
		async loadChat()
		{
			if (this.dialogId === '')
			{
				return;
			}

			Logger.warn(`OpenLinesContent: loading openlines ${this.dialogId}`);

			await this.getChatService().loadChatWithMessages(this.dialogId)
				.catch(() => {
					Messenger.openLines();
				});

			Logger.warn(`OpenLinesContent: openlines ${this.dialogId} is loaded`);
		},
		getChatService(): ChatService
		{
			if (!this.chatService)
			{
				this.chatService = new ChatService();
			}

			return this.chatService;
		},
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<div class="bx-imol-content-default-openlines__container bx-imol-messenger__scope">
			<EmptyState v-if="!dialogId" />
			<OpenLinesContent
				v-else
				:dialogId="dialogId"
			/>
		</div>
	`,
};
