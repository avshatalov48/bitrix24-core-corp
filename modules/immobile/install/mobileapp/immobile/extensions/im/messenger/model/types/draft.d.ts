import {PayloadData} from "./base";
import {DialogId} from "../../types/common";

export enum DraftType {
	text = 'text',
	reply = 'reply',
	forward = 'forward',
	edit = 'edit',
}

export type DraftModelState = {
	dialogId: DialogId,
	messageId: number,
	messageType: 'text' | 'audio' | 'image',
	type: DraftType,
	text: string,
	userName: string,
	message: Array<{
		type: string,
		text: string,
	}>
	image?: {
		id: number,
		url: null | string,
		previewParams: {
			height: number,
			width: number,
		},
	},
	video?: {
		id: number,
		localUrl: null | string,
		url: null | string,
		previewParams: {
			height: number,
			width: number,
		},
		size: number,
	}
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


export type DraftSetStateActions = 'setState';
export interface DraftSetStateData extends PayloadData
{
	collection: Record<DialogId, DraftModelState>;
}


export type DraftAddActions = 'set';
export interface DraftAddData extends PayloadData
{
	dialogId: DialogId;
	fields: DraftModelState;
}


export type DraftUpdateActions = 'set';
export interface DraftUpdateData extends PayloadData
{
	dialogId: DialogId;
	fields: Partial<DraftModelState>;
}


export type DraftDeleteActions = 'delete';
export interface DraftDeleteData extends PayloadData
{
	dialogId: DialogId;
}