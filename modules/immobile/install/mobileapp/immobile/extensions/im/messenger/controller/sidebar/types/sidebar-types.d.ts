import { UsersModelState } from '../../../model/types/users';
import { SidebarFile } from '../../../model/types/sidebar/files';
import { SidebarLink } from '../../../model/types/sidebar/links';
import { DialogId } from '../../../types/common';

type SidebarViewProps = {
	isGroupDialog: boolean,
	isNotes: boolean,
	isCopilot: boolean,
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
	isSuperEllipseAvatar: boolean,
}

type ChannelSidebarViewProps = {
	headData: {
		desc: string | null,
		title: string,
		imageUrl: string,
		svg: string,
		imageColor: string,
	},
	dialogId: number | string,
	buttonElements: object[],
	callbacks: {
		onClickInfoBLock: Function
	},
	restService: object,
	isSuperEllipseAvatar: boolean,
}

type ChannelSidebarViewState = {
}

type CommentSidebarViewProps = {
	headData: {
		desc: string | null,
		title: string,
		imageUrl: string,
		svg: string,
		imageColor: string,
	},
	dialogId: number | string,
	buttonElements: object[],
	callbacks: {
		onClickInfoBLock: Function
	},
	restService: object,
	isSuperEllipseAvatar: boolean,
}

type CommentSidebarViewState = {
}

type SidebarViewState = {
	userData: {} | {
		lastActivityDate: object | null,
		userModelData: UsersModelState,
	},
	isHistoryLimitExceeded: boolean,
}

type SidebarProfileInfoProps = {
	isGroupDialog: boolean,
	isNotes: boolean,
	isCopilot: boolean,
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
	isSuperEllipseAvatar: boolean,
}

type SidebarProfileInfoState = {
	userData: {} | {
		departmentName: string | null,
		statusSvg: string,
		chevron: string,
	},
	imageUrl: string,
	desc: string,
}

type SidebarProfileBtnProps = {
	buttonElements: object[],
}
type SidebarProfileBtnState = {
	buttonElements: object[],
}

type SidebarProfileCounterProps = {
	dialogId: string,
	isCopilot: boolean,
}
type SidebarProfileCounterState = {
	userCounter: number,
	userCounterLocal: string,
}

type SidebarTabViewProps = {
	dialogId: string | number,
	hideParticipants: boolean,
	isCopilot: boolean,
}

type SidebarTabViewState = {
	isNotes: boolean,
	tabItems: Array<object>,
	selectedTab: object,
}

type ChannelSidebarTabViewProps = {
	dialogId: string | number,
}

type ChannelSidebarTabViewState = {
	tabItems: Array<object>,
	selectedTab: object,
}

type SidebarParticipantsViewProps = {
	dialogId: string | number,
	isNotes: boolean,
	isCopilot: boolean,
	id: string,
}
type SidebarParticipantsViewState = {
	participants: Array<object>,
	permissions: {
		isCanRemoveParticipants: boolean,
		isCanAddParticipants: boolean,
		isCanLeave: boolean,
	},
}

type SidebarFilesViewProps = {
	dialogId: string | number,
	id: string,
}

type SidebarFilesViewState = {
	files: Array<SidebarFile>,
	hasNextPage: boolean,
	isHistoryLimitExceeded: boolean,
}

type SidebarLinksViewProps = {
	dialogId: DialogId,
	id: string,
}

type SidebarLinksViewState = {
	links: Array<SidebarLink>,
	hasNextPage: boolean,
	isHistoryLimitExceeded: boolean,
}
