import {JNChatBaseClassInterface} from '../../../types/common';

declare class DialogSelector {
	ui: JNChatMultiSelector;

	on<T extends keyof DialogSelectorEvents>(eventType: T, handler: DialogSelectorEvents[T]): void;
	off<T extends keyof DialogSelectorEvents>(eventType: T, handler: DialogSelectorEvents[T]): void;
	once<T extends keyof DialogSelectorEvents>(eventType: T, handler: DialogSelectorEvents[T]): void;
}

declare interface JNChatMultiSelector extends JNChatBaseClassInterface<DialogSelectorEvents> {
	on<T extends keyof DialogSelectorEvents>(eventType: T, handler: DialogSelectorEvents[T]): void;
	off<T extends keyof DialogSelectorEvents>(eventType: T, handler: DialogSelectorEvents[T]): void;
	once<T extends keyof DialogSelectorEvents>(eventType: T, handler: DialogSelectorEvents[T]): void;

	setEnabled(isEnabled: boolean, animated: boolean): Promise<void>;
	select(itemIdList: Array<string>): Promise<void>;
	unselect(itemIdList: Array<string>): Promise<void>;
	getSelectedItems(): Array<string>;

	enabled: boolean;
	maxCount: number;
}

declare type DialogSelectorEvents = {
	maxCountExceeded: () => any;
	selected: (messageId: string, allSelectedItems: Array<string>) => any;
	unselected: (messageId: string, allSelectedItems: Array<string>) => any;
}
