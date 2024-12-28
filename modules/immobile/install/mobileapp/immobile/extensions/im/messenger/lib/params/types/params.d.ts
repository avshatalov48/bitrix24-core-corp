import { UserType } from '../../../const/types/user';

export type PlanLimits = {
	fullChatHistory?: {
			isAvailable: boolean,
			limitDays: number | null,
		}
}

declare type ImFeatures = {
	chatDepartments: boolean,
	chatV2: boolean,
	collabAvailable: boolean,
	collabCreationAvailable: boolean,
	copilotActive: boolean,
	copilotAvailable: boolean,
	giphyAvailable: boolean,
	sidebarBriefs: boolean,
	sidebarFiles: boolean,
	sidebarLinks: boolean,
	zoomActive: boolean,
	zoomAvailable: boolean,
}

declare type UserInfo = {
	id: number,
	type: UserType,
}

declare type Permissions = {
	byChatType: object,
	byUserType: object,
	actionGroups: object,
	actionGroupsDefaults: object,
}
