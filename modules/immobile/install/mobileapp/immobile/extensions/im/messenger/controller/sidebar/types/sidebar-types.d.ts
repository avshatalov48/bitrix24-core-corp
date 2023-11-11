import { UsersModelState } from '../../../model/types/users';

type SidebarViewProps = {
	isGroupDialog: boolean,
	isNotes: boolean,
	isBot: boolean,
	headData: {
		desc: string | null,
		title: string,
		imageUrl: string,
		svg: string,
		imageColor: string,
	},
	userData: {} | {
		departmentName: string | null,
		statusSvg: string,
		chevron: string,
		lastActivityDate: object | null,
		userModelData: UsersModelState,
	},
	dialogId: number | string,
	buttonElements: object[],
	callbacks: {
		onClickInfoBLock: Function
	},
	restService: object,
}

type SidebarViewState = {
	userData: {} | {
		departmentName: string | null,
		statusSvg: string,
		chevron: string,
		lastActivityDate: object | null,
		userModelData: UsersModelState,
	},
}

type SidebarProfileBtnProps = {
	buttonElements: object[],
}
type SidebarProfileBtnState = {
	buttonElements: object[],
}

type SidebarProfileCounterProps = {
	dialogId: string,
}
type SidebarProfileCounterState = {
	userCounter: number,
	userCounterLocal: string,
}

type SidebarTabViewProps = {
	dialogId: string | number,
	isNotes: boolean,
}

type SidebarTabViewState = {
	isNotes: boolean,
	tabItems: Array<object>,
	selectedTab: object,
}

type SidebarParticipantsViewProps = {
	dialogId: string | number,
	isNotes: boolean,
}
type SidebarParticipantsViewState = {
	participants: Array<object>,
	permissions: {
		isCanRemoveParticipants: boolean,
		isCanAddParticipants: boolean,
	},
}
