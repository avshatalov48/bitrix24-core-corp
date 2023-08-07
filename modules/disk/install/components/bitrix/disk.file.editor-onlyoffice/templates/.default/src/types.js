import UserManager from "./user-manager";
import OnlyOffice from "./onlyoffice";
import {SharingControlType} from "disk.sharing-legacy-popup";

export type WaitingOptions = {
	targetNode: HTMLElement,
	documentSession: DocumentSession,
	object: BaseObject,
};

export type CommonWarningOptions = {
	container: HTMLElement,
	targetNode: HTMLElement,
	title: string,
	fileName: string,
	description: string,
	linkToDownload: string,
};

export type EditorOptions = {
	targetNode: HTMLElement,
	saveButtonNode: HTMLElement,
	cancelButtonNode: HTMLElement,
	editorNode: HTMLElement,
	userBoxNode: HTMLElement,
	currentUser: User,
	editorWrapper: HTMLElement,
	panelButtonUniqIds: ButtonUniqIds,
	documentSession: DocumentSession,
	linkToEdit: string,
	linkToView: string,
	linkToDownload: string,
	object: BaseObject,
	attachedObject: AttachedObject,
	editorJson: any,
	pullConfig: any,
	publicChannel: string,
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

export type DocumentSessionInfo = {
	contentStatus: ?number,
	wasFinallySaved: boolean,
}

export type BaseObject = {
	id: number,
	name: string,
	publicChannel: ?string,
	size: ?number,
	updatedBy: ?number,
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
	onlyOffice: OnlyOffice,
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

export type DocumentSavedMessage = {
	documentSession: DocumentSession,
	documentSessionInfo: DocumentSessionInfo,
	event: string,
}

export type ContentUpdatedMessage = {
	object: BaseObject,
	updatedBy: {
		infoToken: string,
	},
}