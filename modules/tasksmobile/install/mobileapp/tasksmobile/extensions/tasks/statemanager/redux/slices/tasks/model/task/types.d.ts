declare type TaskReduxModel = {
	id: number,
	name?: string,
	description?: string,
	parsedDescription?: string,
	groupId?: number,
	timeElapsed?: number,
	timeEstimate?: number,
	commentsCount?: number,
	serviceCommentsCount?: number,
	newCommentsCount?: number,
	parentId?: number,

	status?: number,
	subStatus?: number,
	priority?: number,
	mark?: string | null,

	creator?: number,
	responsible?: number,
	accomplices?: number[],
	auditors?: number[],

	// ToDo
	// relatedTasks?: object,
	// ToDo
	// subTasks?: object,

	crm?: CrmDTO[],
	tags?: TagsDTO[],
	files?: FilesDTO[],
	uploadedFiles?: string[],

	isMuted?: boolean,
	isPinned?: boolean,
	isInFavorites?: boolean,
	isResultRequired?: boolean,
	isResultExists?: boolean,
	isOpenResultExists?: boolean,
	isMatchWorkTime?: boolean,
	allowChangeDeadline?: boolean,
	allowTimeTracking?: boolean,
	allowTaskControl?: boolean,
	isTimerRunningForCurrentUser?: boolean,

	deadline?: number,
	activityDate?: number,
	startDatePlan?: number,
	endDatePlan?: number,
	startDate?: number,
	endDate?: number,

	checklist?: ChecklistDTO,
	counter?: CounterDTO,

	// ToDo
	actions?: object,

	canUpdateDeadline?: boolean,
	canDelegate?: boolean,
	canRemove?: boolean,
	canUseTimer?: boolean,
	canStart?: boolean,
	canPause?: boolean,
	canComplete?: boolean,
	canRenew?: boolean,
	canApprove?: boolean,
	canDisapprove?: boolean,
	canDefer?: boolean,

	isRemoved?: boolean,
	isExpired?: boolean,
	isConsideredForCounterChange?: boolean,
};

export type ChecklistDTO = {
	completed: number,
	uncompleted: number,
}

export type CounterDTO = {
	counters: {
		expired: number,
		newComments: number,
		mutedExpired: number,
		mutedNewComments: number,
		projectExpired: number,
		projectNewComments: number,
	},
	color: string,
	value: number,
}

export type CrmDTO = {
	id: number,
	type: string,
	title: string,
	subtitle: string,
	hidden: boolean,
}

export type TagsDTO = {
	id: number,
	name: string,
}

export type FilesDTO = {
	id: number,
	objectId: string,
	name: string,
	size: string,
	url: string,
	type: string,
	isImage: boolean,
}
