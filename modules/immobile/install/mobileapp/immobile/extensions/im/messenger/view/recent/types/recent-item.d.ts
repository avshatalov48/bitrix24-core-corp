import {AvatarDetail} from "../../../lib/element/chat-avatar/chat-avatar";
import {RecentWidgetItemAction} from "../../../lib/element/recent/types/base";

export type RecentWidgetItem = {
	actions: Array<RecentWidgetItemAction>,
	avatar: AvatarDetail,
	backgroundColor: string,
	color: string,
	date: number,
	displayedDate: string,
	id: string,
	imageUrl: string,
	isSuperEllipseIcon: boolean,
	menuMode: string,
	messageCount: number,
	params: { options: object, id: string, useLetterImage: boolean, disableTap?: boolean },
	sectionCode: string,
	sortValues: { order: number },
	styles: RecentWidgetItemStyles,
	subtitle: string,
	title: string,
	unread: boolean,
}

export type RecentWidgetItemStyles = {
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
