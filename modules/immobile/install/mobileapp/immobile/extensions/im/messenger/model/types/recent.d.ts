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
	chat: RecentChat,
	user: RecentUser,
	writing: boolean,
	date_update: Date | string,
	unread: boolean,
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

export type RecentUser = {
	absent: string,
	active: boolean,
	avatar: string,
	birthday: string,
	bot: boolean,
	color: string,
	connector: boolean,
	departments: Array<number>,
	desktop_last_date: string | boolean,
	externalAuthId: string,
	external_auth_id: string,
	extranet: boolean,
	firstName: string,
	first_name: string,
	gender: 'M' | 'F',
	id: number,
	idle: boolean,
	lastActivityDate: string,
	last_activity_date: Date,
	lastName: string,
	last_name: string,
	mobileLastDate: string,
	mobile_last_date: Date,
	name: string,
	network: boolean,
	phones: boolean | {
		personalMobile: string,
		workPhone: string,
		personal_mobile: string,
		work_phone: string,
	},
	status: string,
	workPosition: string,
	work_position: string,
}

export type RecentChat = {
	avatar: string,
	can_post: string,
	color: string,
	date_create: Date,
	entity_data_1: string,
	entity_data_2: string,
	entity_data_3: string,
	entity_id: string,
	entity_type: string,
	extranet: boolean,
	id: string,
	manage_settings: string,
	manage_ui: string,
	manage_users: string,
	manager_list: Array<number>,
	message_type: string,
	mute_list: Array<number>,
	name: string,
	owner: number,
	restrictions: {
		avatar: boolean,
		call: boolean,
		extend: boolean,
		leave: boolean,
		leave_owner: boolean,
		mute: boolean,
		rename: boolean,
		send: boolean,
		user_list: boolean,
	},
    role: string,
    type: string,
    user_counter: number,
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
