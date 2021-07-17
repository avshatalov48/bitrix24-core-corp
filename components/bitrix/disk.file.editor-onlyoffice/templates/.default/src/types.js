import UserManager from "./user-manager";
import SharingControlType from "./sharing-control-type";

export type WaitingOptions = {
	documentSession: DocumentSession,
	object: BaseObject,
};

export type EditorOptions = {
	targetNode: HTMLElement,
	saveButtonNode: HTMLElement,
	cancelButtonNode: HTMLElement,
	editorNode: HTMLElement,
	userBoxNode: HTMLElement,
	currentUser: User,
	editorWrapper: HTMLElement,
	panelButtonUniqIds: ButtonUniqIds
	documentSession: DocumentSession,
	linkToEdit: string,
	object: BaseObject,
	attachedObject: AttachedObject,
	editorJson: any,
	pullConfig: any,
	sharingControlType: ?SharingControlType,
};

export type Context = {
	currentUser: User,
	documentSession: DocumentSession,
	object: BaseObject,
	attachedObject: AttachedObject,
}

export type DocumentSession = {
	id: number,
	hash: string,
}

export type BaseObject = {
	id: number,
	name: string,
}

export type AttachedObject = {
	id: ?number,
}

export type ButtonUniqIds = {
	edit: string,
	setupSharing: string,
}

export type UserManagerOptions = {
	context: Context,
	userBoxNode: HTMLElement,
}

export type CommandOptions = {
	userManager: UserManager,
	context: Context,
}

export type User = {
	id: number,
	name: string,
	avatar: string,
	infoToken: string,
	onlineAt: ?number,
}

export type HiDocumentMessage = {
	user: {
		id: number,
		name: string,
		avatar: string,
	}
}

export type WelcomeDocumentMessage = {
	user: {
		id: number,
		name: string,
		avatar: string,
	}
}

export type PingDocumentMessage = {
	fromUserId: number,
	infoToken: string,
}

export type ExitDocumentMessage = {
	fromUserId: number,
}
