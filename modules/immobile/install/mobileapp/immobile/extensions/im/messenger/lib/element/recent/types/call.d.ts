export type RecentCallData = {
	associatedEntity: {
		advanced: object,
		avatar: string,
		avatarColor: string,
		chatId: number,
		id: string,
		name: string,
		type: string,
		userCounter: number,
	},
	id: string,
	provider: string,
	uuid: string,
}

export type RecentCallStatus = 'local' | 'none' | 'remote';

export type RecentWidgetCallItem = {
	backgroundColor: string,
	color: string,
	id: string,
	imageUrl: string,
	params: {
		call: RecentCallData,
		isLocal: boolean,
		canJoin: boolean,
		type: string,
	},
	sectionCode: string,
	styles: { title: object, subtitle: object },
	subtitle: string,
	title: string,
	unselectable: boolean,
	useBackgroundColor: boolean,
	useColor: boolean,
	useLetterImage: boolean,
};
