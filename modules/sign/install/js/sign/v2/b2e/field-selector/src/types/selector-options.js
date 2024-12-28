import {BaseEvent} from 'main.core.events';
import {type Field} from './field';
import type {FieldsList} from './fields-list';
import type { CustomBackendSettings } from '../backend/backend';

export type SelectorFilter = {
	'+categories'?: Array<string>,
	'-categories'?: Array<string>,
	'+fields'?: Array<string | {[key: string]: any} | (Field) => boolean>,
	'-fields'?: Array<string | {[key: string]: any} | (Field) => boolean>,
	'query'?: string,
	allowEmptyFieldList?: boolean,
};

export type FieldsFactoryFilter = {
	'+types': Array<string | ({[key: string]: any}) => boolean>,
	'-types': Array<string | ({[key: string]: any}) => boolean>,
};

export type ControllerOptions = {
	[key: string]: any,
} | {
	'hideVirtual'?: number,
	'hideRequisites'?: number,
	'hideSmartDocument'?: number,
	'hideSmartB2eDocument'?: number,
};

export type SelectorOptions = {
	filter?: SelectorFilter | (FieldsList) => FieldsList,
	controllerOptions?: ControllerOptions,
	multiple?: boolean,
	events?: {
		[name: string]: (event: BaseEvent) => void,
	},
	resultModifier?: (Array<Field>) => any,
	fieldsFactory?: {
		filter?: (
			FieldsFactoryFilter
			| (Array<{[key: string]: any}>) => Array<{[key: string]: any}>
		),
	},
	disabledFields?: Array<string | (Field) => boolean>,
	title?: string,
	categoryCaptions?: {[category: string]: string},
	languages?: {[key: string]: { NAME: string; IS_BETA: boolean; }},
	customBackendSettings?: CustomBackendSettings,
	alwaysHideCreateFieldButton?: boolean,
};