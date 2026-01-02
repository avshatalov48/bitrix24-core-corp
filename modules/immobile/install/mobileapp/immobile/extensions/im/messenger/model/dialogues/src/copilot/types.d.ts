import { DialogId } from '../../../../types/common';
import { MessengerModel, PayloadData } from '../../../base';

declare type CopilotModelState = {
	dialogId: DialogId,
	roles: CopilotRoleData,
	aiProvider: string,
	messages: Array<MessageCopilotDataItem>,
	chats: Array<ChatsCopilotDataItem>,
}

declare type CopilotRoleData = {
	avatar: {
		small?: string,
		medium?: string,
		large?: string,
	},
	code: string,
	default: boolean,
	desc: string,
	name: string,
	prompts: Array<copilotPrompt>,
}

export type copilotPrompt = {
	code: string,
	promptType: string,
	title: string,
	text: string,
}

export type CopilotModelActions =
	'dialoguesModel/copilotModel/update'
	| 'dialoguesModel/copilotModel/updateRole'
	| 'dialoguesModel/copilotModel/setCollection'
	| 'dialoguesModel/copilotModel/setFromSync'

export type CopilotModelMutation =
	'dialoguesModel/copilotModel/add'
	| 'dialoguesModel/copilotModel/addCollection'
	| 'dialoguesModel/copilotModel/update'
	| 'dialoguesModel/copilotModel/updateCollection'

export type CopilotUpdateActions =
	'setCollection'
	| 'update'
	| 'updateRole'
export interface CopilotUpdateData extends PayloadData
{
	dialogId: DialogId;
	fields: CopilotModelState;
}

export interface CopilotUpdateCollectionData extends PayloadData
{
	updateItems: Array<CopilotPayloadDataItem>
}

export type CopilotAddActions =
	'setCollection'
export interface CopilotAddData extends PayloadData
{
	dialogId: DialogId;
	fields: CopilotModelState;
}

export interface CopilotAddCollectionData extends PayloadData
{
	addItems: Array<CopilotPayloadDataItem>
}

export interface MessageCopilotDataItem
{
	id: number;
	role: string;
}

export interface ChatsCopilotDataItem
{
	dialogId: DialogId;
	role: string;
}

export interface CopilotPayloadDataItem
{
	dialogId: DialogId;
	fields: CopilotModelState;
}

declare type CopilotModelCollection = {
	collection: Record<DialogId, CopilotModelState>,
}

export type CopilotModel = MessengerModel<CopilotModelCollection>;
