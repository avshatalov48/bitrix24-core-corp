export enum ChatType
{
	chat = 'chat',
	open = 'open',
	user = 'user',
	notification = 'notification',
}

export enum MessageStatus
{
	received = 'received',
	delivered = 'delivered',
	error = 'error',
}

export type RecentModelState = {
	id: number,
	message: {
		id: number,
		senderId: string,
		date: Date,
		status: MessageStatus,
		subTitleIcon: string,
		sending: boolean,
		text: string,
		params: object,
	},
	dateMessage: Date | null,
	unread: boolean,
	pinned: boolean,
	liked: boolean,

	avatar: string,
	color: string,
	title: string,
	counter: number,
	invitation?: {
		isActive: boolean,
		originator: number,
		canResend: boolean,
	},
	options: {
		defaultUserRecord?: boolean,
		birthdayPlaceholder?: boolean,
	}
};

export type RecentModelActions =
	'recentModel/setState'
	| 'recentModel/set'
	| 'recentModel/like'
	| 'recentModel/delete'
	| 'recentModel/clearAllCounters'
	| 'recentModel/update'

export type RecentModelMutation =
	'recentModel/setState'
	| 'recentModel/add'
	| 'recentModel/update'
	| 'recentModel/delete'
