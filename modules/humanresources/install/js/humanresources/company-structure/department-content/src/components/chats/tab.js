import {
	PermissionActions,
	PermissionChecker,
	PermissionLevels,
} from 'humanresources.company-structure.permission-checker';
import { EntityTypes, type ChatListResponse, type CommunicationDetailed } from 'humanresources.company-structure.utils';
import { DepartmentContentActions } from '../../actions';
import { DepartmentAPI } from '../../api';
import { SearchInput } from '../base-components/search/search-input';
import { EmptyState } from '../base-components/empty-state/empty-state';
import { EmptyTabAddButtons } from './empty-tab-add-buttons';
import { ChannelListDataTestIds, ChatListDataTestIds, CollabListDataTestIds, ChatsMenuOption } from './consts';
import { CommunicationsTypeDict } from 'humanresources.company-structure.structure-components';
import { useChartStore } from 'humanresources.company-structure.chart-store';
import { mapState } from 'ui.vue3.pinia';
import { Loc, Type } from 'main.core';
import { CommunicationList } from './communication-list.js';
import { LinkDialog } from './link-dialog.js';

import './styles/tab.css';

export const ChatsTab = {
	name: 'chatsTab',

	components: {
		SearchInput,
		EmptyState,
		EmptyTabAddButtons,
		CommunicationList,
		LinkDialog,
	},

	data(): Object
	{
		return {
			chatLinkDialogVisible: false,
			channelLinkDialogVisible: false,
			collabLinkDialogVisible: false,
			isLoading: false,
			searchQuery: '',
			permissionChecker: null,
		};
	},

	methods:
	{
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
		locPlural(phraseCode: string, count: number): string
		{
			return Loc.getMessagePlural(phraseCode, count, { '#COUNT#': count });
		},
		searchChatOrChannel(searchQuery: string): void
		{
			this.searchQuery = searchQuery;
		},
		async loadChatAction(force: boolean): ?Promise<ChatListResponse>
		{
			if (this.isLoading)
			{
				return null;
			}

			const nodeId = this.focusedNode;
			const department = this.departments.get(nodeId);

			if (!department)
			{
				return null;
			}

			if (!force
				&& Type.isArray(department.chatsDetailed)
				&& Type.isArray(department.channelsDetailed)
				&& Type.isArray(department.collabsDetailed)
			)
			{
				return {
					chats: department.chatsDetailed,
					channels: department.channelsDetailed,
					collabs: department.collabsDetailed,
					chatsNoAccess: department.chatsNoAccess,
					channelsNoAccess: department.channelsNoAccess,
					collabsNoAccess: department.collabsNoAccess,
				};
			}

			this.isLoading = true;

			this.$emit('showDetailLoader');
			const loadedChatsAndChannels = await DepartmentAPI.getChatsAndChannels(nodeId);
			DepartmentContentActions.setChatsAndChannels(
				nodeId,
				loadedChatsAndChannels.chats ?? [],
				loadedChatsAndChannels.channels ?? [],
				loadedChatsAndChannels.collabs ?? [],
				loadedChatsAndChannels.chatsNoAccess ?? 0,
				loadedChatsAndChannels.channelsNoAccess ?? 0,
				loadedChatsAndChannels.collabsNoAccess ?? 0,
			);
			this.$emit('hideDetailLoader');
			this.isLoading = false;

			return loadedChatsAndChannels;
		},
		onActionMenuItemClick(actionId: string): void
		{
			switch (actionId)
			{
				case ChatsMenuOption.linkChat:
					this.chatLinkDialogVisible = true;
					break;
				case ChatsMenuOption.linkChannel:
					this.channelLinkDialogVisible = true;
					break;
				case ChatsMenuOption.linkCollab:
					this.collabLinkDialogVisible = true;
					break;
				default:
					break;
			}
		},
		getAddEmptyStateList(): { text: string }[]
		{
			let stateArray = [];

			if (this.isCollabsAvailable && this.isTeamEntity)
			{
				stateArray = [
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_LIST_W_COLLABS_ITEM_1'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_W_COLLABS_ITEM_2'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_LIST_W_COLLABS_ITEM_3'),
				];
			}
			else if (this.isCollabsAvailable)
			{
				stateArray = [
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_W_COLLABS_ITEM_1'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_W_COLLABS_ITEM_2'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_W_COLLABS_ITEM_3'),
				];
			}
			else if (this.isTeamEntity)
			{
				stateArray = [
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_LIST_ITEM_1_MSGVER_1'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_ITEM_2'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_LIST_ITEM_3'),
				];
			}
			else
			{
				stateArray = [
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_ITEM_1_MSGVER_1'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_ITEM_2'),
					this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_LIST_ITEM_3'),
				];
			}

			return stateArray.map((item) => ({ text: item }));
		},
	},

	computed:
	{
		isCollabsAvailable(): boolean
		{
			return PermissionChecker.getInstance().isCollabsAvailable;
		},
		communicationTypes(): Record<string, string>
		{
			return CommunicationsTypeDict;
		},
		chats(): CommunicationDetailed[]
		{
			return this.departments.get(this.focusedNode)?.chatsDetailed ?? [];
		},
		channels(): CommunicationDetailed[]
		{
			return this.departments.get(this.focusedNode)?.channelsDetailed ?? [];
		},
		collabs(): CommunicationDetailed[]
		{
			return this.departments.get(this.focusedNode)?.collabsDetailed ?? [];
		},
		chatsNoAccess(): number
		{
			return this.departments.get(this.focusedNode)?.chatsNoAccess ?? 0;
		},
		channelsNoAccess(): number
		{
			return this.departments.get(this.focusedNode)?.channelsNoAccess ?? 0;
		},
		collabsNoAccess(): number
		{
			return this.departments.get(this.focusedNode)?.collabsNoAccess ?? 0;
		},
		filteredChats(): Array<CommunicationDetailed>
		{
			return this.chats.filter(
				(chat) => chat.title.toLowerCase().includes(this.searchQuery.toLowerCase()),
			);
		},
		filteredChannels(): Array<CommunicationDetailed>
		{
			return this.channels.filter(
				(channel) => channel.title.toLowerCase().includes(this.searchQuery.toLowerCase()),
			);
		},
		filteredCollabs(): Array<CommunicationDetailed>
		{
			return this.collabs.filter(
				(collab) => collab.title.toLowerCase().includes(this.searchQuery.toLowerCase()),
			);
		},
		showAddEmptyState(): boolean
		{
			return this.chats.length === 0
				&& this.channels.length === 0
				&& this.collabs.length === 0
				&& this.chatsNoAccess === 0
				&& this.channelsNoAccess === 0
				&& this.collabsNoAccess === 0
			;
		},
		showSearchEmptyState(): boolean
		{
			return (this.chats.length > 0 || this.channels.length > 0 || this.collabs.length > 0)
				&& this.filteredChats.length === 0
				&& this.filteredChannels.length === 0
				&& this.filteredCollabs.length === 0
			;
		},
		areChatsLoaded(): Boolean
		{
			const department = this.departments.get(this.focusedNode);

			return Boolean(Type.isArray(department.chatsDetailed)
				&& Type.isArray(department.channelsDetailed)
				&& Type.isArray(department.collabsDetailed))
			;
		},
		entityType(): boolean
		{
			return this.departments.get(this.focusedNode)?.entityType;
		},
		isTeamEntity(): boolean
		{
			return this.entityType === EntityTypes.team;
		},
		getAddEmptyStateTitle(): string
		{
			if (this.isCollabsAvailable)
			{
				return this.isTeamEntity
					? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_TITLE_W_COLLABS')
					: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TITLE_W_COLLABS')
				;
			}

			return this.isTeamEntity
				? this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TEAM_TITLE')
				: this.loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_EMPTY_TAB_ADD_EMPTY_STATE_TITLE')
			;
		},
		canEditChat(): boolean
		{
			return this.isTeamEntity
				? this.permissionChecker.hasPermission(PermissionActions.teamChatEdit, this.focusedNode)
				: this.permissionChecker.hasPermission(PermissionActions.departmentChatEdit, this.focusedNode)
			;
		},
		canEditChannel(): boolean
		{
			return this.isTeamEntity
				? this.permissionChecker.hasPermission(PermissionActions.teamChannelEdit, this.focusedNode)
				: this.permissionChecker.hasPermission(PermissionActions.departmentChannelEdit, this.focusedNode)
			;
		},
		canEditCollab(): boolean
		{
			return this.isTeamEntity
				? this.permissionChecker.hasPermission(PermissionActions.teamCollabEdit, this.focusedNode)
				: this.permissionChecker.hasPermission(PermissionActions.departmentCollabEdit, this.focusedNode)
			;
		},
		canAddChatWithChildren(): boolean
		{
			if (this.isTeamEntity)
			{
				return this.permissionChecker.hasPermission(
					PermissionActions.teamChatEdit,
					this.focusedNode,
					{ TEAM: PermissionLevels.selfAndSub },
				)
					|| this.permissionChecker.hasPermission(
						PermissionActions.teamChatEdit,
						this.focusedNode,
						{ DEPARTMENT: PermissionLevels.selfAndSub },
					)
				;
			}

			return this.permissionChecker.hasPermission(
				PermissionActions.departmentChatEdit,
				this.focusedNode,
				PermissionLevels.selfAndSub,
			);
		},
		canAddChannelWithChildren(): boolean
		{
			if (this.isTeamEntity)
			{
				return this.permissionChecker.hasPermission(
					PermissionActions.teamChannelEdit,
					this.focusedNode,
					{ TEAM: PermissionLevels.selfAndSub },
				)
					|| this.permissionChecker.hasPermission(
						PermissionActions.teamChannelEdit,
						this.focusedNode,
						{ DEPARTMENT: PermissionLevels.selfAndSub },
					)
				;
			}

			return this.permissionChecker.hasPermission(
				PermissionActions.departmentChannelEdit,
				this.focusedNode,
				PermissionLevels.selfAndSub,
			);
		},
		canAddCollabWithChildren(): boolean
		{
			if (this.isTeamEntity)
			{
				return this.permissionChecker.hasPermission(
					PermissionActions.teamCollabEdit,
					this.focusedNode,
					{ TEAM: PermissionLevels.selfAndSub },
				)
					|| this.permissionChecker.hasPermission(
						PermissionActions.teamCollabEdit,
						this.focusedNode,
						{ DEPARTMENT: PermissionLevels.selfAndSub },
					)
				;
			}

			return this.permissionChecker.hasPermission(
				PermissionActions.departmentCollabEdit,
				this.focusedNode,
				PermissionLevels.selfAndSub,
			);
		},
		hideEmptyChatItem(): boolean
		{
			return this.searchQuery.length > 0 || this.chatsNoAccess > 0;
		},
		hideEmptyChannelItem(): boolean
		{
			return this.searchQuery.length > 0 || this.channelsNoAccess > 0;
		},
		getChatListDataTestIds(): Object
		{
			return ChatListDataTestIds;
		},
		getChannelListDataTestIds(): Object
		{
			return ChannelListDataTestIds;
		},
		getCollabListDataTestIds(): Object
		{
			return CollabListDataTestIds;
		},
		...mapState(useChartStore, ['focusedNode', 'departments']),
	},

	watch:
	{
		areChatsLoaded(isChatsLoaded): void
		{
			if (isChatsLoaded === false)
			{
				this.loadChatAction();
			}
		},
	},

	created(): void
	{
		this.permissionChecker = PermissionChecker.getInstance();
		this.loadChatAction();
	},

	template: `
		<div class="hr-department-detail-content__tab-container --chat">
			<template v-if="!showAddEmptyState">
				<SearchInput
					:placeholder="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_TAB_CHATS_CHAT_SEARCH_INPUT_PLACEHOLDER')"
					:value="searchQuery"
					@inputChange="searchChatOrChannel"
					dataTestId="hr-department-detail-content_chats-tab__chats-and-channels-search-input"
				/>
				<div
					v-if="!showSearchEmptyState"
					class="hr-department-detail-content__lists-container"
				>
					<CommunicationList
						v-if="isCollabsAvailable"
						:communications="collabs"
						:filteredCommunications="filteredCollabs"
						:communicationsNoAccess="collabsNoAccess"
						:canEdit="canEditCollab"
						:searchQuery="searchQuery"
						:focusedNode="focusedNode"
						:communicationType="communicationTypes.collab"
						:isTeamEntity="isTeamEntity"
						:dataTestId="getCollabListDataTestIds"
						@tabListAction="onActionMenuItemClick"
					/>
					<CommunicationList
						:communications="channels"
						:filteredCommunications="filteredChannels"
						:communicationsNoAccess="channelsNoAccess"
						:canEdit="canEditChannel"
						:searchQuery="searchQuery"
						:focusedNode="focusedNode"
						:communicationType="communicationTypes.channel"
						:isTeamEntity="isTeamEntity"
						:dataTestId="getChannelListDataTestIds"
						@tabListAction="onActionMenuItemClick"
					/>
					<CommunicationList
						:communications="chats"
						:filteredCommunications="filteredChats"
						:communicationsNoAccess="chatsNoAccess"
						:canEdit="canEditChat"
						:searchQuery="searchQuery"
						:focusedNode="focusedNode"
						:communicationType="communicationTypes.chat"
						:isTeamEntity="isTeamEntity"
						:dataTestId="getChatListDataTestIds"
						@tabListAction="onActionMenuItemClick"
					/>
				</div>
				<EmptyState 
					v-else
					imageClass="hr-department-detail-content__chat-empty-tab-search_tab-icon"
					:title="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_TITLE')"
					:description ="loc('HUMANRESOURCES_COMPANY_STRUCTURE_DEPARTMENT_CONTENT_EMPTY_SEARCHED_EMPLOYEES_SUBTITLE')"
				/>
			</template>
			<EmptyState 
				v-else
				imageClass="hr-department-detail-content__chat-empty-tab-add_tab-icon"
				:title="getAddEmptyStateTitle"
				:list="getAddEmptyStateList()"
			>
				<template v-slot:content>
					<EmptyTabAddButtons
						:isTeamEntity="isTeamEntity"
						:canEditChat="canEditChat"
						:canEditChannel="canEditChannel"
						:canEditCollab="canEditCollab"
						@emptyStateAddAction="onActionMenuItemClick"
					/>
				</template>
			</EmptyState>
			<LinkDialog
				v-if="isCollabsAvailable"
				:communications="collabs"
				:communicationType="communicationTypes.collab"
				:focusedNode="focusedNode"
				:isTeamEntity="isTeamEntity"
				:entityType="entityType"
				:isVisible="collabLinkDialogVisible"
				:canAddWithChildren="canAddCollabWithChildren"
				@close="collabLinkDialogVisible = false"
				@reloadList="loadChatAction(true)"
			/>
			<LinkDialog
				:communications="channels"
				:communicationType="communicationTypes.channel"
				:focusedNode="focusedNode"
				:isTeamEntity="isTeamEntity"
				:entityType="entityType"
				:isVisible="channelLinkDialogVisible"
				:canAddWithChildren="canAddChannelWithChildren"
				@close="channelLinkDialogVisible = false"
				@reloadList="loadChatAction(true)"
			/>
			<LinkDialog
				:communications="chats"
				:communicationType="communicationTypes.chat"
				:focusedNode="focusedNode"
				:isTeamEntity="isTeamEntity"
				:entityType="entityType"
				:isVisible="chatLinkDialogVisible"
				:canAddWithChildren="canAddChatWithChildren"
				@close="chatLinkDialogVisible = false"
				@reloadList="loadChatAction(true)"
			/>
		</div>
	`,
};
