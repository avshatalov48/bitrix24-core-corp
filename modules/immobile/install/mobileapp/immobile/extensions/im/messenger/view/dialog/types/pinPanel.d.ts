declare interface PinPanel
{
	showNextItem(): void;
	showPreviousItem(): void;
	showItemById(id: string): void;
	show(params: PinPanelShowParams): void;
	hide(): void;
	updateItem(item: Message): void;
	addItem(item: Message): void;
	updateItem(item: Message): void;
	on<T extends keyof PinPanelEvents>(eventName: T, handler: PinPanelEvents[T]): PinPanel;
	off<T extends keyof PinPanelEvents>(eventName: T, handler: PinPanelEvents[T]): PinPanel;
}

export type PinPanelShowParams = {
	itemList: Array<Message>,
	selectedItemId: string,
	title: string,
	buttonType: PinPanelButtonType,
}

declare type PinPanelEvents = {
	itemTap: (messageId: string) => any,
	buttonTap: (messageId: string, buttonType: PinPanelButtonType) => any,
}

declare type PinPanelButtonType = 'delete' | 'edit';