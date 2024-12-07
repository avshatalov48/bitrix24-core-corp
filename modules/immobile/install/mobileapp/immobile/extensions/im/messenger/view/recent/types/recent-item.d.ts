export type NativeRecentItem = {
	actions: Array<any>,
	backgroundColor: string,
	color: string,
	date: number,
	displayedDate: string,
	id: string,
	imageUrl: string,
	isSuperEllipseIcon: boolean,
	menuMode: string,
	messageCount: number,
	params: { options: object, id: string, useLetterImage: boolean },
	sectionCode: string,
	sortValues: { order: number },
	styles: NativeRecentItemStyles,
	subtitle: string,
	title: string,
	unread: boolean,
}

export type NativeRecentItemStyles = {
	avatar: object,
	counter: { backgroundColor?: string },
	date: {
			image?: { name: string, sizeMultiplier: number },
		},
	subtitle: object,
	title: {
		additionalImage: {},
		font: {
			color: string,
			fontStyle: string,
			useColor: boolean,
		}
	},
}