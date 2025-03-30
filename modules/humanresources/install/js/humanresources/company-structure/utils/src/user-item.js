import { memberRoles } from 'humanresources.company-structure.api';
import type { Item } from 'ui.entity-selector';

export type UserData = {
	id: number,
	avatar: ?string,
	name: string,
	workPosition: ?string,
	role: string,
	url: string,
	isInvited: boolean,
	nodeId?: number,
};

type InvitedUserItem = {
	customData: {
		email: string;
		invited: boolean;
		lastName?: string;
		name?: string;
		login: string;
		nodeId: number;
	},
	entityId: string,
	entityType: string,
	id: number,
	title: String,
	tabs: string[],
};

export const getInvitedUserData = (item: InvitedUserItem): UserData => {
	const { id, title, customData } = item;
	const { nodeId } = customData;

	return {
		id,
		avatar: '',
		name: title,
		workPosition: '',
		role: memberRoles.employee,
		url: `/company/personal/user/${id}/`,
		isInvited: true,
		nodeId,
	};
};

export const getUserDataBySelectorItem = (item: Item, role: string): UserData => {
	const { id, avatar, title, customData } = item;
	item.setLink(null);
	const link = item.getLink() ?? '';
	const workPosition = customData.get('position') ?? '';
	const isInvited = customData.get('invited') ?? false;

	return {
		id,
		avatar,
		name: title.text,
		workPosition,
		role,
		url: link,
		isInvited,
	};
};
