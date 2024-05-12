declare interface MentionPanel
{
	open(items : Array<MentionItem>): void;
	setItems(items : Array<MentionItem>): void;
	close(): void;
	showLoader(): void;
	hideLoader(): void;
	on<T extends keyof MentionPanelEvents>(eventName: T, handler: MentionPanelEvents[T]): void;
	off<T extends keyof MentionPanelEvents>(eventName: T, handler: MentionPanelEvents[T]): void;
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