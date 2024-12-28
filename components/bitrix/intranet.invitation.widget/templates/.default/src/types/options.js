export type InvitationWidgetOptions = {
	button?: HTMLElement,
	isCurrentUserAdmin?: boolean,
	isExtranetAvailable?: boolean,
	isCollabAvailable?: boolean,
	isInvitationAvailable?: boolean,
	structureLink?: string,
	invitationLink?: string,
	invitationCounter?: number,
	counterId?: string,
	shouldShowStructureCounter?: boolean,
}

export type InvitationPopupOptions = {
	isAdmin?: boolean,
	target?: HTMLElement,
	isExtranetAvailable?: boolean,
	isCollabAvailable?: boolean,
	isInvitationAvailable?: boolean,
	params?: {
		structureLink?: string,
		invitationLink?: string,
		shouldShowStructureCounter?: boolean,
	}
}

export type InvitationContentOptions = {
	isAdmin?: boolean,
	invitationLink?: string,
	isInvitationAvailable?: string,

}

export type StructureContentOptions = {
	link?: string,
	shouldShowStructureCounter?: boolean,
}

export type EmployeesContentOptions = {
	isAdmin?: boolean,
	awaitData?: Promise,
}

export type ExtranetContentOptions = {
	isAdmin?: boolean,
	awaitData?: Promise,
	invitationLink?: string,
	isInvitationAvailable?: boolean,
}

export type CollabContentOptions = {
	isAdmin?: boolean,
	awaitData?: Promise,
}
