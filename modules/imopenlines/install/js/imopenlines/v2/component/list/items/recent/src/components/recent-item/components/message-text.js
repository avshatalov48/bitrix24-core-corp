import { Utils } from 'im.v2.lib.utils';
import { Parser } from 'im.v2.lib.parser';

import '../css/recent-item.css';

import type { ImModelMessage } from 'im.v2.model';
import type { ImolModelRecentItem } from 'imopenlines.v2.model';

// @vue/component
export const MessageText = {
	name: 'MessageText',
	props:
	{
		item: {
			type: Object,
			required: true,
		},
	},
	computed:
	{
		recentItems(): ImolModelRecentItem
		{
			return this.item;
		},
		message(): ?ImModelMessage
		{
			return this.$store.getters['messages/getById'](this.recentItems.messageId);
		},
		lastMessageAuthorAvatar(): string
		{
			const authorDialog = this.$store.getters['chats/get'](this.message.authorId);

			if (!authorDialog)
			{
				return '';
			}

			return authorDialog.avatar;
		},
		lastMessageAuthorAvatarStyle(): Object
		{
			return { backgroundImage: `url('${this.lastMessageAuthorAvatar}')` };
		},
		formattedMessageText(): string
		{
			if (this.message.isDeleted)
			{
				return this.loc('IMOL_LIST_RECENT_DELETED_MESSAGE');
			}

			const SPLIT_INDEX = 27;

			const formattedText = Parser.purifyRecent(this.recentItems);

			return Utils.text.insertUnseenWhitespace(formattedText, SPLIT_INDEX);
		},
	},
	methods:
	{
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<div class="bx-imol-list-recent-item__message">
			<span class="bx-imol-list-recent-item__message_text-container">
				<template v-if="message.authorId">
					<span v-if="lastMessageAuthorAvatar" :style="lastMessageAuthorAvatarStyle" class="bx-imol-list-recent-item__message_author-icon --user"></span>
					<span v-else class="bx-imol-list-recent-item__message_author-icon --user --default"></span>
				</template>
				<span class="bx-imol-list-recent-item__message_text">{{ formattedMessageText }}</span>
			</span>
		</div>
	`,
};
