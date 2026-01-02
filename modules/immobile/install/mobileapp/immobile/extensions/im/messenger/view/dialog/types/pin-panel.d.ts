import {JNChatBaseClassInterface} from '../../../types/common';

declare class DialogPinPanel {
	ui: JNChatPinPanel;

	on<T extends keyof PinPanelEvents>(eventName: T, handler: PinPanelEvents[T]): void;
	off<T extends keyof PinPanelEvents>(eventName: T, handler: PinPanelEvents[T]): void;
	once<T extends keyof PinPanelEvents>(eventName: T, handler: PinPanelEvents[T]): void;
}

declare interface JNChatPinPanel extends JNChatBaseClassInterface<PinPanelEvents>
{
	on<T extends keyof PinPanelEvents>(eventName: T, handler: PinPanelEvents[T]): void;
	off<T extends keyof PinPanelEvents>(eventName: T, handler: PinPanelEvents[T]): void;
	once<T extends keyof PinPanelEvents>(eventName: T, handler: PinPanelEvents[T]): void;

	showNextItem(): void;
	showPreviousItem(): void;
	showItemById(id: string): void;
	show(params: PinPanelShowParams): void;
	update(params: object): void;
	hide(): void;
	updateItem(item: object): void;
	updateItems(items: object[]): void;
}

export type PinPanelShowParams = {
	itemList: Array<object>,
	selectedItemId: string,
	title: string,
	buttonType: PinPanelButtonType,
}

declare type PinPanelEvents = {
	itemTap: (messageId: string) => any,
	buttonTap: (messageId: string, buttonType: PinPanelButtonType) => any,
}

declare type PinPanelButtonType = 'delete' | 'edit';
