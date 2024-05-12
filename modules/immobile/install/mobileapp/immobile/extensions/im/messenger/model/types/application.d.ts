import {PayloadData} from "./base";

import {DialogId} from "../../types/common";

export type ApplicationModelActions =
	'applicationModel/openDialogId'
	| 'applicationModel/closeDialogId'
	| 'applicationModel/setStatus'

export type ApplicationModelMutation =
	'applicationModel/openDialogId'
	| 'applicationModel/closeDialogId'
	| 'applicationModel/setStatus'

export type ApplicationSetStatusActions = 'setStatus';
export interface ApplicationSetStatusData extends PayloadData
{
	status: {
		name: string,
		value: boolean,
	};
}

export type ApplicationOpenDialogIdActions = 'openDialogId';
export interface ApplicationOpenDialogIdData extends PayloadData
{
	dialogId: DialogId;
}

export type ApplicationCloseDialogIdActions = 'closeDialogId';
export interface ApplicationCloseDialogIdData extends PayloadData
{
	dialogId: DialogId;
}

