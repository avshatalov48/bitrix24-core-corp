import {JNChatBaseClassInterface} from '../../../types/common';

declare class DialogJoinButton {
	ui: JNChatJoinButton;

	on<T extends keyof DialogJoinButtonEvents>(eventType: T, handler: DialogJoinButtonEvents[T]): void;
	off<T extends keyof DialogJoinButtonEvents>(eventType: T, handler: DialogJoinButtonEvents[T]): void;
	once<T extends keyof DialogJoinButtonEvents>(eventType: T, handler: DialogJoinButtonEvents[T]): void;
}

declare interface JNChatJoinButton extends JNChatBaseClassInterface<DialogJoinButtonEvents>
{
	on<T extends keyof DialogJoinButtonEvents>(eventType: T, handler: DialogJoinButtonEvents[T]): void;
	off<T extends keyof DialogJoinButtonEvents>(eventType: T, handler: DialogJoinButtonEvents[T]): void;
	once<T extends keyof DialogJoinButtonEvents>(eventType: T, handler: DialogJoinButtonEvents[T]): void;

	show(params: JoinButtonShowParams): void;
	hide(animated: boolean): void;
}

declare type DialogJoinButtonEvents = {
	tap: () => any,
	hideDone: () => any,
}

declare type JoinButtonShowParams = {
	text: string,
	backgroundColor: string,
	testId: string,
	textColor: string,
}
