declare type PullExtraParams = {
	im_revision: number,
	is_shared_event: boolean,
	im_revision_mobile: number,
	revision_im_mobile: number,
	revision_im_rest: number,
	revision_im_web: number,
	revision_mobile: number,
	revision_web: number,
	sender: {
		type: number,
		id: string
	},
	server_name: string,
	server_time: string,
	server_time_ago: number,
	server_time_unix: number,
	action_uuid?: string
};