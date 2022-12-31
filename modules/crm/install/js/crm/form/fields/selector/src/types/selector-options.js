import {BaseEvent} from 'main.core.events';
import {type Field} from './field';
import type {FieldsList} from './fields-list';

export type SelectorFilter = {
	'+categories'?: Array<string>,
	'-categories'?: Array<string>,
	'+fields'?: Array<string | {[key: string]: any} | (Field) => boolean>,
	'-fields'?: Array<string | {[key: string]: any} | (Field) => boolean>,
	'query'?: string,
};

export type FieldsFactoryFilter = {
	'+types': Array<string | ({[key: string]: any}) => boolean>,
	'-types': Array<string | ({[key: string]: any}) => boolean>,
};

export type SelectorOptions = {
	filter?: SelectorFilter | (FieldsList) => FieldsList,
	controllerOptions?: {
		[key: string]: any,
	} | {
		'hideVirtual'?: number,
		'hideRequisites'?: number,
		'hideSmartDocument'?: number,
	},
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
};