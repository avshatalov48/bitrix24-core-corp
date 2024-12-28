import { DialogId } from '../../../types/common';
import { DialogPermissions } from '../../../model/types/dialogues';
import { NestedDepartmentSelectorItem } from './area';

declare type ManagersViewProps = {
	users: Array<object>,
	callbacks?: {
		onClickAddManager?: () => any,
		onClickRemoveManager?: () => any,
		onDestroyView: () => any,
	},
}
declare type ManagersViewState = {
	users: Array<object>,
}

declare type RulesListViewProps = {
	dialogType: string,
	permissions: DialogPermissions,
	callbacks?: {
		onChangeUserRoleInRule?: (rule: string, userRole: UserRole) => any,
		onDestroyView: () => any,
	},
}

declare type RulesListViewState = DialogPermissions

declare type DialogTypeViewProps = {
	dialogType: string,
	callbacks?: {
		onChangeDialogType?: () => any,
		onDestroyView: () => any,
	},
};

declare type DialogTypeViewState = {
	dialogType: string,
}

declare type GroupChatViewProps = {
	dialogId: DialogId,
	isCreate: boolean,
	title: string,
	type: string,
	description: string,
	avatar: string,
	userCounter: number,
	managerCounter: number,
	permissions: {
		update: boolean,
	},
	participantsList?: Array<NestedDepartmentSelectorItem>,
	callbacks?: {
		onClickDoneButton?: (props: { title: string, description: string, avatar: string }) => any,
		onClickCreateButton?: (props: { title: string, description: string, avatar: string }) => any,
		onChangeAvatar?: () => any,
		onClickDialogTypeAction?: () => any,
		onClickParticipantAction?: () => any,
		onClickManagersAction?: () => any,
		onClickRulesAction?: () => any,
		onDestroy: () => any,
	},
}

declare type GroupChatViewState = {
	name: string,
	description: string,
	avatar: string,
	type: string,
	userCounter: number,
	managerCounter: string,
	permissions: {
		update: boolean,
	},
	isInputChanged: boolean,
	updateDialogInfoState: boolean,
}

declare type ChannelViewProps = GroupChatViewProps;
declare type ChannelViewState = GroupChatViewState
