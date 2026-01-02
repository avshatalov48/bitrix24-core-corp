export type CommentInfoMessageFormat = {
	title: string,
	totalCounter: number,
	unreadCounter?: object | null,
	users?: null | Array<{imageUrl: string, defaultIconSvg: string}>,
	showLoader: boolean,
}
