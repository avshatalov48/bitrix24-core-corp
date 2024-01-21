declare interface DialogTextField
{
	on<T extends keyof DialogTextFieldEvents>(eventType: T, handler: DialogTextFieldEvents[T]): void;
	off<T extends keyof DialogTextFieldEvents>(eventType: T, handler: DialogTextFieldEvents[T]): void;
	getText(): string;
	setText(text: string): void;
	replaceText(fromIndex: number, toIndex: number, text: string): void;
	getCursorIndex(): number;
	hideKeyboard?: () => void;
	showKeyboard?: () => void;
}

declare type DialogTextFieldEvents = {
	changeState: (text: string, inputCharacters: string, cursorPosition: number) => any,
}