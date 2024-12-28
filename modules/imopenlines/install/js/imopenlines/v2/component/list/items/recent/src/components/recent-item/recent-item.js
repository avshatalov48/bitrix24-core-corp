import { ChatTitle, ChatAvatar, AvatarSize } from 'im.v2.component.elements';
import { Layout } from 'im.v2.const';
import { DateFormatter, DateTemplate } from 'im.v2.lib.date-formatter';

import { MessageText } from './components/message-text';
import { ItemCounter } from './components/item-counter';

import './css/recent-item.css';

import type { ImModelChat, ImModelMessage } from 'im.v2.model';
import type { ImolModelRecentItem } from 'imopenlines.v2.model';

// @vue/component
export const RecentItem = {
	name: 'RecentItem',
	components: { ChatTitle, ChatAvatar, MessageText, ItemCounter },
	props:
	{
		item: {
			type: Object,
			required: true,
		},
	},
	computed:
	{
		AvatarSize: () => AvatarSize,
		dialog(): ?ImModelChat
		{
			return this.$store.getters['chats/get'](this.item.dialogId, true);
		},
		layout(): { name: string, entityId: string }
		{
			return this.$store.getters['application/getLayout'];
		},
		message(): ?ImModelMessage
		{
			return this.$store.getters['messages/getById'](this.item.messageId);
		},
		recentItem(): ImolModelRecentItem
		{
			return this.item;
		},
		formattedDate(): string
		{
			return this.message ? this.formatDate(this.message.date) : '';
		},
		isChatSelected(): boolean
		{
			if (this.layout.name !== Layout.openlinesV2.name)
			{
				return false;
			}

			return this.layout.entityId === this.recentItem.dialogId;
		},
		wrapClasses(): { [string]: boolean }
		{
			return {
				'--selected': this.isChatSelected,
			};
		},
	},
	methods:
	{
		formatDate(date: Date): string
		{
			return DateFormatter.formatByTemplate(date, DateTemplate.recent);
		},
	},
	template: `
		<div class="bx-imol-list-recent__item" :class="wrapClasses">
			<div class="bx-imol-list-recent-item__main_content">
				<div class="bx-imol-list-recent-item__avatar_container">
					<div class="bx-imol-list-recent-item__avatar_content">
						<ChatAvatar
							:avatarDialogId="recentItem.dialogId"
							:contextDialogId="recentItem.dialogId"
							:size="AvatarSize.XL"
						/>
					</div>
				</div>
				<div class="bx-imol-list-recent-item__content_right">
					<div class="bx-imol-list-recent-item__content_header">
						<div class="bx-imol-list-recent-item__content_title">
							<ChatTitle :dialogId="recentItem.dialogId" />
						</div>
						<div class="bx-imol-list-recent-item__content_date">
							<span class="bx-imol-list-recent-item__content_date">{{ formattedDate }}</span>
						</div>
					</div>
					<div class="bx-imol-list-recent-item__content_bottom">
						<MessageText :item="recentItem" />
						<ItemCounter :item="recentItem" />
					</div>
				</div>
			</div>
		</div>
	`,
};
