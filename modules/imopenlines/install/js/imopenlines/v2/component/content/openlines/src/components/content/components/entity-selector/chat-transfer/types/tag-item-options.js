export type TagItemOptions = {
	id: string,
	entityId: number | string,
	entityType?: string,
	title?: string,
	avatar?: string,
	avatarOptions?: AvatarOptions,
	textColor?: string,
	bgColor?: string,
	fontWeight?: string,
	link?: string,
	onclick?: Function,
	maxWidth?: number,
	deselectable?: boolean,
	animate?: boolean,
	customData?: { [key: string]: any }
};

export type AvatarOptions = {
	bgSize?: string,
	bgColor?: string,
	bgImage?: string,
	border?: string,
	borderRadius?: string,
};
