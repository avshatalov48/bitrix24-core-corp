import { MessengerModel, PayloadData } from './base';
import { DialogId } from '../../types/common';

export type SidebarModel = MessengerModel<SidebarModelState>

export type SidebarModelState = {
	collection: Record<DialogId, SidebarCollection>,
};

export type SidebarCollection = {
	dialogId: DialogId,
	isMute: boolean,
	isHistoryLimitExceeded: boolean,
};

export type SidebarModelActions =
	'sidebarModel/set'
	| 'sidebarModel/add'
	| 'sidebarModel/delete'
	| 'sidebarModel/update'
	| 'sidebarModel/changeMute'
	| 'sidebarModel/setHistoryLimitExceeded'
	| 'sidebarModel/removeHistoryLimitExceeded'

export type SidebarModelMutation =
	'sidebarModel/add'
	| 'sidebarModel/delete'
	| 'sidebarModel/update'
	| 'sidebarModel/setHistoryLimitExceeded'
;


export type SidebarAddActions =
	'set'
	| 'add'
;

export interface SidebarSetHistoryLimitExceededData extends PayloadData
{
	dialogId?: DialogId;
	isHistoryLimitExceeded: boolean
}

export type SidebarSetHistoryLimitExceededActions =
	'set'
;

export interface SidebarAddData extends PayloadData
{
	dialogId?: DialogId;
	fields: SidebarCollection
}


export type SidebarUpdateActions =
	'set'
	| 'update'
	| 'changeMute'
;
export interface SidebarUpdateData extends PayloadData
{
	dialogId?: DialogId;
	fields: Partial<SidebarCollection>
}


export type SidebarDeleteActions = 'delete';
export interface SidebarDeleteData extends PayloadData
{
	dialogId?: DialogId;
}
