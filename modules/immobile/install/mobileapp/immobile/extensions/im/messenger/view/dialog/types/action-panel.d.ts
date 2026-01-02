import {JNChatBaseClassInterface} from '../../../types/common';
import {ActionPanelButton} from "../../../controller/dialog/lib/select-manager/types/select-manager";

declare class DialogActionPanel {
	ui: JNChatActionPanel;

	on<T extends keyof ActionPanelEvents>(eventName: T, handler: ActionPanelEvents[T]): void;
	off<T extends keyof ActionPanelEvents>(eventName: T, handler: ActionPanelEvents[T]): void;
	once<T extends keyof ActionPanelEvents>(eventName: T, handler: ActionPanelEvents[T]): void;
}

declare interface JNChatActionPanel extends JNChatBaseClassInterface<ActionPanelEvents>
{
	on<T extends keyof ActionPanelEvents>(eventName: T, handler: ActionPanelEvents[T]): void;
	off<T extends keyof ActionPanelEvents>(eventName: T, handler: ActionPanelEvents[T]): void;
	once<T extends keyof ActionPanelEvents>(eventName: T, handler: ActionPanelEvents[T]): void;

	show(titleData: object, buttons: Array<ActionPanelButton>): Promise<void>;
	hide(animated: boolean): Promise<void>;
	setTitle(title: object): Promise<void>;
	setButtons(buttons: Array<ActionPanelButton>): Promise<void>;
}

declare type ActionPanelEvents = {
	buttonTap: (id: string) => any,
	disabledButtonTap:  (id: string) => any,
}
