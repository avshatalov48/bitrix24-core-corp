import {JNChatBaseClassInterface} from '../../../types/common';

declare class DialogMentionPanel {
	ui: JNChatMentionPanel;

	on<T extends keyof MentionPanelEvents>(eventName: T, handler: MentionPanelEvents[T]): void;
	off<T extends keyof MentionPanelEvents>(eventName: T, handler: MentionPanelEvents[T]): void;
	once<T extends keyof MentionPanelEvents>(eventName: T, handler: MentionPanelEvents[T]): void;
}

declare interface JNChatMentionPanel extends JNChatBaseClassInterface<MentionPanelEvents>
{
	on<T extends keyof MentionPanelEvents>(eventName: T, handler: MentionPanelEvents[T]): void;
	off<T extends keyof MentionPanelEvents>(eventName: T, handler: MentionPanelEvents[T]): void;
	once<T extends keyof MentionPanelEvents>(eventName: T, handler: MentionPanelEvents[T]): void;

	open(items : Array<MentionItem>): void;
	close(): void;
	setItems(items : Array<MentionItem>): void;
	showLoader(): void;
	hideLoader(): void;
}

declare type MentionItem = {
	id: string,
	title: string,
	imageUrl: string,
	imageColor: string,
	displayedDate: string,
	titleColor: string,
	testId: string,
}

declare type MentionPanelEvents = {
	itemTap: (item: MentionItem) => any,
}
