import {JNChatBaseClassInterface} from '../../../types/common';

declare class DialogStatusField {
	ui: JNStatusTextField;

	on<T extends keyof DialogStatusFieldEvents>(eventType: T, handler: DialogStatusFieldEvents[T]): void;
	off<T extends keyof DialogStatusFieldEvents>(eventType: T, handler: DialogStatusFieldEvents[T]): void;
	once<T extends keyof DialogStatusFieldEvents>(eventType: T, handler: DialogStatusFieldEvents[T]): void;
}

declare interface JNStatusTextField extends JNChatBaseClassInterface<DialogStatusFieldEvents>
{
	on<T extends keyof DialogStatusFieldEvents>(eventType: T, handler: DialogStatusFieldEvents[T]): void;
	off<T extends keyof DialogStatusFieldEvents>(eventType: T, handler: DialogStatusFieldEvents[T]): void;
	once<T extends keyof DialogStatusFieldEvents>(eventType: T, handler: DialogStatusFieldEvents[T]): void;

	set(params: StatusFieldSetParams): void;
	clear(): void;
}

declare type DialogStatusFieldEvents = {
	tap: () => any,
}

declare type StatusFieldSetParams = {
	iconType: StatusFieldIconType;
	text: string;
	additionalText: string;
}

declare type StatusFieldIconType = 'sended_silent' | 'viewed' | 'recording_audio' | 'typing' | 'sending_video';
