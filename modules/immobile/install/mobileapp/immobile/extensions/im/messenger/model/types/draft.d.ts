export enum DraftType {
	text = 'text',
	reply = 'reply',
	forward = 'forward',
	edit = 'edit',
}

export type DraftModelState = {
	dialogId: string | number,
	messageId: number,
	messageType: 'text' | 'audio' | 'image',
	type: DraftType,
	text: string,
	userName: string,
	message: Array<{
		type: string,
		text: string,
	}>
};

export type DraftModelActions =
	'draftModel/set'
	| 'draftModel/setState'
	| 'draftModel/delete'

export type DraftModelMutation =
	'draftModel/add'
	| 'draftModel/setState'
	| 'draftModel/update'
	| 'draftModel/delete'