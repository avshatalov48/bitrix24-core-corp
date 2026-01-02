export type MessageVideo = {
	id: string,
	type: 'video',
	localUrl: string | null,
	url: string | null,
	previewImage: string | null,
	previewParams: {
		height: number,
		width: number,
	},
	size: number,
}
