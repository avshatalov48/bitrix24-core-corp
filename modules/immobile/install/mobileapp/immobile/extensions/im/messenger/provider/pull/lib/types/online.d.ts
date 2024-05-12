type OnlineUpdateParamsState = {
	name?: string,
	last_name?: string,
	first_name?: string,
	work_position?: string,
	color: string,
	desktop_last_date: string | false,
	id: number,
	idle: boolean,
	last_activity_date: string | false,
	mobile_last_date: string,
	status: string,
}

type OnlineUpdateParams = {
	users: {
		[userId: string]: OnlineUpdateParamsState
	}
}