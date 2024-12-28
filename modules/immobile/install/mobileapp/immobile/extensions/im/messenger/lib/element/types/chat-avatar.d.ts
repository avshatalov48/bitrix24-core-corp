export type ChatAvatarTitleParams = {
	useLetterImage: boolean,
	imageUrl?: string,
	imageColor?: string,
}

export type AvatarDetail = {
	type: 'hexagon' | 'circle' | 'square',
	polygonAngle: number, // only IOS
	radius: number,
	accentType: 'extranet' | 'collab' | 'accent',
	accentColorGradient: { // only IOS
		start: string,
		middle: string,
		end: string,
		angle: number,
	},
	backBorderWidth: number,
	backColor: string, // only IOS
	hideOutline: true,
	uri: string,
	title: string,
	placeholder: {
		type: string,
		backgroundColor: string,
		letters: {
			fontSize: number,
		},
	},
}
