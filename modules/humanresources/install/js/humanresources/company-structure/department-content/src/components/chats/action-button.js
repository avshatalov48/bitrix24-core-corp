import { useChartStore } from 'humanresources.company-structure.chart-store';
import { ChatListActionMenu, MenuActions } from 'humanresources.company-structure.org-chart';
import { ConfirmationPopup, RouteActionMenu } from 'humanresources.company-structure.structure-components';
import { ChatTypes, EntityTypes } from 'humanresources.company-structure.utils';
import { UI } from 'ui.notification';
import { mapState } from 'ui.vue3.pinia';
import { DepartmentContentActions } from '../../actions';
import { DepartmentAPI } from '../../api';
import { Messenger } from 'im.public.iframe';
import { ActionButtonDictionary, ActionButtonDictionaryItem } from './dictionaries/action-button-dictionary';

type DataType = {
	isMenuVisible: boolean,
	showUnbindConfirmationPopup: boolean,
	unbindLoader: boolean,
	dictionary: ActionButtonDictionaryItem | {},
};

// @vue/component
export const CommunicationListItemActionButton = {
	name: 'communicationListItemActionButton',

	components: {
		RouteActionMenu,
		ConfirmationPopup,
	},

	props: {
		/** @type CommunicationDetailed */
		communication: {
			type: Object,
			required: true,
		},
		nodeId: {
			type: Number,
			required: true,
		},
	},

	data(): DataType
	{
		return {
			isMenuVisible: false,
			showUnbindConfirmationPopup: false,
			unbindLoader: false,
			dictionary: {},
		};
	},

	computed:
	{
		entityType(): boolean
		{
			return this.departments.get(this.nodeId)?.entityType;
		},
		isTeamEntity(): boolean
		{
			return this.departments.get(this.nodeId)?.entityType === EntityTypes.team;
		},
		menu(): ChatListActionMenu
		{
			const entityType = this.departments.get(this.nodeId)?.entityType;

			return new ChatListActionMenu(entityType, this.communication, this.nodeId);
		},
		buttonDataId(): string
		{
			const type = this.communication.type.toLowerCase();

			return `hr-department-detail-content__${type}-list_chat-${this.communication.id}-action-btn`;
		},
		...mapState(useChartStore, ['departments']),
	},

	created(): void
	{
		this.dictionary = (ActionButtonDictionary[this.communication.type.toLowerCase()]
				&& ActionButtonDictionary[this.communication.type.toLowerCase()][this.entityType.toLowerCase()])
			|| {}
		;
	},

	methods:
	{
		loc(phraseCode: string, replacements: { [p: string]: string } = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		toggleMenu(): void
		{
			this.isMenuVisible = !this.isMenuVisible;
		},
		onActionMenuItemClick(actionId: string): void
		{
			if (actionId === MenuActions.openChat)
			{
				Messenger.openChat(this.communication.dialogId);
			}
			else if (actionId === MenuActions.unbindChat)
			{
				this.showUnbindConfirmationPopup = true;
			}
		},
		cancelUnbind(): void
		{
			this.showUnbindConfirmationPopup = false;
		},
		async unbind(): Promise<void>
		{
			this.unbindLoader = true;

			try
			{
				switch (this.communication.type)
				{
					case ChatTypes.chat:
						await DepartmentAPI.saveChats(this.nodeId, [], [this.communication.id]);
						break;
					case ChatTypes.channel:
						await DepartmentAPI.saveChannel(this.nodeId, [], [this.communication.id]);
						break;
					case ChatTypes.collab:
						await DepartmentAPI.saveCollab(this.nodeId, [], [this.communication.id]);
						break;
					default:
						break;
				}
			}
			catch (error)
			{
				if (error.code !== 'STRUCTURE_ACCESS_DENIED')
				{
					UI.Notification.Center.notify({
						content: this.loc(this.dictionary.error),
						autoHideDelay: 2000,
					});
				}

				return;
			}
			finally
			{
				this.unbindLoader = false;
				this.showUnbindConfirmationPopup = false;
			}

			DepartmentContentActions.unbindChatFromNode(this.nodeId, this.communication.id, this.communication.type);

			const isOwn = !this.communication.originalNodeId || this.communication.originalNodeId === this.nodeId;

			if (isOwn)
			{
				const store = useChartStore();
				store.updateChatsInChildrenNodes(this.nodeId);
			}

			UI.Notification.Center.notify({
				content: this.loc(this.dictionary.success),
				autoHideDelay: 2000,
			});
		},
	},

	template: `
		<button
			v-if="menu.items.length"
			class="ui-icon-set --more hr-department-detail-content__tab-list_item-action-btn --chat-item-action-btn ui-icon-set"
			:class="{ '--focused': isMenuVisible }"
			@click.stop="toggleMenu()"
			ref="actionCommunicationButton"
			:data-id="buttonDataId"
		/>
		<RouteActionMenu
			v-if="isMenuVisible"
			:id="'tree-node-department-menu-chat_' + this.nodeId + '_' + communication.id"
			:items="menu.items"
			:width="302"
			:bindElement="$refs.actionCommunicationButton"
			@action="onActionMenuItemClick"
			@close="isMenuVisible = false"
		/>
		<ConfirmationPopup
			ref="unbindConfirmationPopup"
			v-if="showUnbindConfirmationPopup"
			:showActionButtonLoader="unbindLoader"
			:title="loc(dictionary.title)"
			:confirmBtnText="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_UNBIND_POPUP_CONFIRM_BUTTON')"
			confirmButtonClass="ui-btn-danger"
			@action="unbind"
			@close="cancelUnbind"
			:width="364"
		>
			<template v-slot:content>
				<div class="hr-department-detail-content__user-action-text-container">
					<div
						v-html="loc(dictionary.description)"
					/>
				</div>
			</template>
		</ConfirmationPopup>
	`,
};
