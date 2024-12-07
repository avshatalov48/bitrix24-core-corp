import {PayloadData} from "./base";
import {DialogId} from "../../types/common";

export type ApplicationModelState = {
	dialog: {
		currentId: number,
		idList: Array<any>,
	},
	common: {
		host: string,
		status: {
			networkWaiting: boolean,
			connection: boolean,
			sync: boolean,
			running: boolean,
		},
	},
	settings: {
		audioRate: AudioRate,
	},
}

export type AudioRate = 1.0 | 1.5 | 2.0;

export type ApplicationModelActions =
	'applicationModel/openDialogId'
	| 'applicationModel/closeDialogId'
	| 'applicationModel/setAudioRateSetting'
	| 'applicationModel/setStatus'

export type ApplicationModelMutation =
	'applicationModel/openDialogId'
	| 'applicationModel/closeDialogId'
	| 'applicationModel/setStatus'
	| 'applicationModel/setSettings'

export type ApplicationSetStatusActions = 'setStatus';
export interface ApplicationSetStatusData extends PayloadData
{
	status: {
		name: string,
		value: boolean,
	};
}

export type ApplicationSetSettingsActions = 'setSettings';
export interface ApplicationSetSettingsData extends PayloadData
{
	audioRate: string,
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

