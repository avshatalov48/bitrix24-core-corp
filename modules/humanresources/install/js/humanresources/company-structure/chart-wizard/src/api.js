import { getData, postData } from 'humanresources.company-structure.api';
import type { ChatListResponse } from 'humanresources.company-structure.utils';
import { DepartmentUserIds } from './types';

export const WizardAPI = {
	createDepartment: (
		name: string,
		parentId: number,
		description: ?string,
		userIds: DepartmentUserIds,
		moveUsersToDepartment: number,
		createChat: number,
		bindingChatIds: number[],
		createChannel: number,
		bindingChannelIds: number[],
		createCollab: number,
		bindingCollabIds: number[],
		settings: {},
	): Promise<void> => {
		return postData('humanresources.api.Structure.Department.create', {
			name,
			parentId,
			description,
			userIds,
			moveUsersToDepartment,
			createChat,
			bindingChatIds,
			createChannel,
			bindingChannelIds,
			createCollab,
			bindingCollabIds,
			settings,
		});
	},
	createTeam: (
		name: string,
		parentId: number,
		description: ?string,
		colorName: ?string,
		userIds: DepartmentUserIds,
		createChat: number,
		bindingChatIds: number[],
		createChannel: number,
		bindingChannelIds: number[],
		createCollab: number,
		bindingCollabIds: number[],
		settings: {},
	): Promise<void> => {
		return postData('humanresources.api.Structure.Team.create', {
			name,
			parentId,
			description,
			colorName,
			userIds,
			createChat,
			bindingChatIds,
			createChannel,
			bindingChannelIds,
			createCollab,
			bindingCollabIds,
			settings,
		});
	},
	addDepartment: (
		name: string,
		parentId: number,
		description: ?string,
		entityType: ?string,
		colorName: ?string,
	): Promise<void> => {
		return postData('humanresources.api.Structure.Node.add', {
			name,
			parentId,
			description,
			entityType,
			colorName,
		});
	},
	getEmployees: (nodeId: number): Promise<Array<number>> => {
		return postData('humanresources.api.Structure.Node.Member.Employee.getIds', { nodeId });
	},
	updateDepartment: (
		nodeId: number,
		parentId: number,
		name: string,
		description: ?string,
		colorName: ?string,
	): Promise<void> => {
		return postData('humanresources.api.Structure.Node.update', {
			nodeId,
			name,
			parentId,
			description,
			colorName,
		});
	},
	saveUsers: (nodeId: number, userIds: DepartmentUserIds, parentId: ?number): Promise<Array> => {
		return postData('humanresources.api.Structure.Node.Member.saveUserList', {
			nodeId,
			userIds,
			parentId,
		});
	},
	moveUsers: (nodeId: number, userIds: DepartmentUserIds, parentId: ?number): Promise<Array> => {
		return postData('humanresources.api.Structure.Node.Member.moveUserListToDepartment', {
			nodeId,
			userIds,
			parentId,
		});
	},
	saveChats: (
		nodeId: number,
		ids: number[],
		createDefault: number,
		removeIds: number[],
	): Promise<void> => {
		return postData('humanresources.api.Structure.Node.Member.Chat.saveChatList', {
			nodeId,
			createDefault,
			ids,
			removeIds,
		});
	},
	saveChannels: (
		nodeId: number,
		ids: number[],
		createDefault: number,
		removeIds: number[],
	): Promise<void> => {
		return postData('humanresources.api.Structure.Node.Member.Chat.saveChannelList', {
			nodeId,
			createDefault,
			ids,
			removeIds,
		});
	},
	saveCollabs: (
		nodeId: number,
		ids: number[],
		createDefault: number,
		removeIds: number[],
	): Promise<void> => {
		return postData('humanresources.api.Structure.Node.Member.Chat.saveCollabList', {
			nodeId,
			createDefault,
			ids,
			removeIds,
		});
	},
	createSettings: (
		nodeId: number,
		settings: Record<string, {values: string[], replace: boolean}>,
		parentId: ?number,
	): Promise<void> => {
		return postData('humanresources.api.Structure.Node.NodeSettings.create', {
			settings,
			nodeId,
			parentId,
		});
	},
	updateSettings: (
		nodeId: number,
		settings: Record<string, {values: string[], replace: boolean}>,
		parentId: ?number,
	): Promise<void> => {
		return postData('humanresources.api.Structure.Node.NodeSettings.update', {
			settings,
			nodeId,
			parentId,
		});
	},
	getSettings: (nodeId: number): Promise<Array<number>> => {
		return postData('humanresources.api.Structure.Node.NodeSettings.get', { nodeId });
	},
	getChatsAndChannels: (nodeId: number): Promise<ChatListResponse> => {
		return getData('humanresources.api.Structure.Node.Member.Chat.getList', {
			nodeId,
		});
	},
};
