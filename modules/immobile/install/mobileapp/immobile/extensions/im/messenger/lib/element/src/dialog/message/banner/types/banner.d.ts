type MessageComponentId = string;

declare type BannerMetaDataValue = {
	banner: BannerProps;
}

declare type BannerProps = {
	title: string,
	description: string,
	imageName: string,
	background: string,
	picBackground: string,
	shouldDisplayTim?: boolean,
	buttons?: Array<BannerButton>
}

declare type BannerButton = {
	id: string,
	text: string,
	design: string,
	height: string,
	type: ButtonType,
	callback: () => any,
}

type ButtonType = 'short' | 'full';

export type BannerMetaData = Record<MessageComponentId, BannerMetaDataValue>
