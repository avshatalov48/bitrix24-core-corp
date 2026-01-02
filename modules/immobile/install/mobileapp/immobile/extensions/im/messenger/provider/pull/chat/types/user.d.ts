
export type UserUpdateParams = {
	bot?: RawPullBotData,
	user: RawPullUserData,
	userInGroup: Array<any>,
}

export type BotUpdateParams = {
	bot: RawPullBotData,
	user: RawPullUserData,
	userInGroup: Array<any>,
}

export type RawPullUserData = {
	absent: boolean,
	active: boolean,
	avatar: string,
	avatar_id: null
	birthday: boolean,
	bot: boolean,
	bot_data: RawPullBotDataFromUser,
	color: string,
	connector: boolean,
	departments: []
	desktop_last_date: boolean,
	external_auth_id: string,
	extranet: boolean,
	first_name: string,
	gender: string,
	id: string,
	idle: boolean,
	last_activity_date: boolean,
	last_name: null
	mobile_last_date: boolean,
	name: string,
	network: boolean,
	phone_device: boolean,
	phones: boolean,
	profile: string,
	services: null | object,
	status: string,
	type: string,
	tz_offset: number,
	work_position: string,
}

export type RawPullBotDataFromUser = {
	app_id: string,
	background_id: string,
	code: string,
	is_hidden: boolean,
	is_support_openline: boolean,
	type: string,
}

export type RawPullBotData = {
	backgroundId: string,
	code: string,
	id: string,
	openline: boolean,
	type: string,
}

export type UserInvitePullHandlerParams = {
	userId: number,
	invited: boolean,
	user: RawPullUserData,
	date: string,
};
