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
	type: ChatType,
	avatar: string,
	color: string,
	title: string,
	counter: number,
	pinned: boolean,
	liked: boolean,
	message: {
		id: number,
		text: string,
		date: Date,
		senderId: string,
		status: MessageStatus,
	},
	chat_id: 0,
	chat: {
		id: number,
		date_create: Date,
	},
	user: {
		id: number,
		last_activity_date: Date
	},
	writing: false,
};

export type RecentModelActions =
	'recentModel/setState'
	| 'recentModel/set'
	| 'recentModel/like'
	| 'recentModel/delete'
	| 'recentModel/clearAllCounters'

export type RecentModelMutation =
	'recentModel/setState'
	| 'recentModel/add'
	| 'recentModel/update'
	| 'recentModel/delete'
