export type InvitationWidgetOptions = {
	button?: HTMLElement,
	isCurrentUserAdmin?: boolean,
	isExtranetAvailable?: boolean,
	isInvitationAvailable?: boolean,
	structureLink?: string,
	invitationLink?: string,
}

export type InvitationPopupOptions = {
	isAdmin?: boolean,
	target?: HTMLElement,
	isExtranetAvailable?: boolean,
	isInvitationAvailable?: boolean,
	params?: {
		structureLink?: string,
		invitationLink?: string,
	}
}

export type InvitationContentOptions = {
	isAdmin?: boolean,
	invitationLink?: string,
	isInvitationAvailable?: string,

}

export type StructureContentOptions = {
	link?: string,
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