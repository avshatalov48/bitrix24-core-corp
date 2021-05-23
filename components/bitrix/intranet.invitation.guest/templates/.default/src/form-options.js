import type { RowOptions } from './row-options';

export type FormOptions = {
	targetNode: HTMLElement,
	saveButtonNode: HTMLElement,
	cancelButtonNode: HTMLElement,
	rows: RowOptions[],
	userOptions: { [key: string]: any }
};