import {PayloadData} from "./base";
import {DialogId} from "../../types/common";

export type SidebarModelState = {
	dialogId: string,
	isMute: boolean,
};

export type SidebarModelActions =
	'sidebarModel/set'
	| 'sidebarModel/add'
	| 'sidebarModel/delete'
	| 'sidebarModel/update'
	| 'sidebarModel/changeMute'

export type SidebarModelMutation =
	'sidebarModel/add'
	| 'sidebarModel/delete'
	| 'sidebarModel/update'
;


export type SidebarAddActions =
	'set'
	| 'add'
;
export interface SidebarAddData extends PayloadData
{
	dialogId?: DialogId;
	fields: SidebarModelState
}


export type SidebarUpdateActions =
	'set'
	| 'update'
	| 'changeMute'
;
export interface SidebarUpdateData extends PayloadData
{
	dialogId?: DialogId;
	fields: Partial<SidebarModelState>
}


export type SidebarDeleteActions = 'delete';
export interface SidebarDeleteData extends PayloadData
{
	dialogId?: DialogId;
}