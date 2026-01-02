import {JNChatBaseClassInterface} from '../../../types/common';

declare class DialogCommentsButton {
	ui: JNChatFloatingCommentButton;

	on<T extends keyof CommentsButtonEvents>(eventName: T, handler: CommentsButtonEvents[T]): void;
	off<T extends keyof CommentsButtonEvents>(eventName: T, handler: CommentsButtonEvents[T]): void;
	once<T extends keyof CommentsButtonEvents>(eventName: T, handler: CommentsButtonEvents[T]): void;
}

declare interface JNChatFloatingCommentButton extends JNChatBaseClassInterface<CommentsButtonEvents>
{
	on<T extends keyof CommentsButtonEvents>(eventName: T, handler: CommentsButtonEvents[T]): void;
	off<T extends keyof CommentsButtonEvents>(eventName: T, handler: CommentsButtonEvents[T]): void;
	once<T extends keyof CommentsButtonEvents>(eventName: T, handler: CommentsButtonEvents[T]): void;

	show(): void;
	hide(params: {animated: boolean}): void;
	setCounter(value: string): void;
}

declare type CommentsButtonEvents = {
	tap: () => any,
}
