export type DialogHeaderButtonsIds =
	'call_video'
	| 'call_audio'
	| 'add_users'
	| 'subscribed_to_comments'
	| 'unsubscribed_from_comments'

export type DialogHeaderButton = {
	id: string,
	testId: string,
	type: string,
	color?: string | null,
	badgeCode?: string,
	badgeValue?: string,
	svg?: object,
	name?: string,
	imageUrl?: string,
	accent?: boolean,
	dot?: boolean,
	callback?: () => any,
}
