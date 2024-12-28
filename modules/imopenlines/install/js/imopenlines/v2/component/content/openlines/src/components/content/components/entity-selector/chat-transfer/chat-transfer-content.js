import { TagSelector } from 'ui.entity-selector';

import { Button as MessengerButton, ButtonSize, ButtonColor } from 'im.v2.component.elements';
import { ChatSearch } from 'im.v2.component.search.chat-search';
import { TransferService } from 'imopenlines.v2.provider.service';

import type { JsonObject } from 'main.core';
import type { ImModelUser } from 'im.v2.model';

import type { TagItemOptions } from './types/tag-item-options';

const searchConfig = Object.freeze({
	chats: false,
	users: true,
});

const SEARCH_ENTITY_ID = 'user';

export const ChatTransferContent = {
	name: 'ChatTransferContent',
	components: { MessengerButton, ChatSearch },
	props: {
		dialogId: {
			type: String,
			required: true,
		},
	},
	data(): JsonObject
	{
		return {
			searchQuery: '',
			selectedItems: new Set(),
		};
	},
	computed:
	{
		ButtonSize: () => ButtonSize,
		ButtonColor: () => ButtonColor,
		searchConfig: () => searchConfig,
	},
	created()
	{
		this.membersSelector = this.getTagSelector();
	},
	mounted()
	{
		this.membersSelector.renderTo(this.$refs['tag-selector']);
		this.membersSelector.focusTextBox();
	},
	methods:
	{
		getTagSelector(): TagSelector
		{
			return new TagSelector({
				maxHeight: 150,
				showAddButton: false,
				showTextBox: true,
				showCreateButton: false,
				events: {
					onAfterTagAdd: (event) => {
						const { tag } = event.getData();
						this.selectedItems.add(tag.id);
					},
					onAfterTagRemove: (event) => {
						const { tag } = event.getData();
						this.selectedItems.delete(tag.id);
					},
					onInput: () => {
						this.searchQuery = this.membersSelector.getTextBoxValue();
					},
				},
			});
		},
		onSelectItem(event: {dialogId: string, nativeEvent: PointerEvent})
		{
			const { dialogId, nativeEvent } = event;

			if (this.selectedItems.has(dialogId))
			{
				const tag = {
					id: dialogId,
					entityId: SEARCH_ENTITY_ID,
				};

				this.membersSelector.removeTag(tag);
			}
			else
			{
				this.membersSelector.removeTags();

				const newTag = this.getTagsByDialogId(dialogId);
				this.membersSelector.addTag(newTag);
			}

			this.membersSelector.clearTextBox();

			if (!nativeEvent.altKey)
			{
				this.searchQuery = '';
			}
		},
		getTagsByDialogId(dialogId: string): TagItemOptions
		{
			const user: ImModelUser = this.$store.getters['users/get'](dialogId, true);

			return {
				id: dialogId,
				entityId: SEARCH_ENTITY_ID,
				title: user.name,
				avatar: user.avatar.length > 0 ? user.avatar : null,
			};
		},
		onChatTransfer(): Promise
		{
			const newOperatorId = [...this.selectedItems][0];

			return this.getTransferService().chatTransfer(this.dialogId, newOperatorId);
		},
		getTransferService(): TransferService
		{
			if (!this.transferService)
			{
				this.transferService = new TransferService();
			}

			return this.transferService;
		},
		loc(key: string): string
		{
			return this.$Bitrix.Loc.getMessage(key);
		},
	},
	template: `
		<div class="bx-imol-entity-selector-chat-transfer__container">
			<div class="bx-imol-entity-selector-chat-transfer__input" ref="tag-selector"></div>
			<div class="bx-imol-entity-selector-chat-transfer__search-result-container">
				<ChatSearch
					:searchMode="true"
					:searchQuery="searchQuery"
					:selectMode="true"
					:searchConfig="searchConfig"
					:selectedItems="[...selectedItems]"
					:showMyNotes="false"
					@clickItem="onSelectItem"
				/>
			</div>
			<div class="bx-imol-entity-selector-chat-transfer__buttons">
				<MessengerButton
					:size="ButtonSize.L"
					:color="ButtonColor.Primary"
					:isRounded="true"
					:text="loc('IMOL_CONTENT_BUTTON_TRANSFER')"
					:isDisabled="selectedItems.size === 0"
					@click="onChatTransfer"
				/>
				<MessengerButton
					:size="ButtonSize.L"
					:color="ButtonColor.LightBorder"
					:isRounded="true"
					:text="loc('IMOL_ENTITY_SELECTOR_CHAT_TRANSFER_CANCEL_BUTTON')"
					@click="$emit('close')"
				/>
			</div>
		</div>
	`,
};
