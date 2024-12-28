declare type TaskReduxModel = {
	id: number,
	guid?: string,
	name?: string,
	description?: string,
	parsedDescription?: string,
	groupId?: number,
	timeElapsed?: number,
	timeEstimate?: number,
	commentsCount?: number,
	serviceCommentsCount?: number,
	newCommentsCount?: number,
	resultsCount?: number,
	parentId?: number,

	status?: number,
	subStatus?: number,
	priority?: number,
	mark?: string | null,

	creator?: number,
	responsible?: number,
	accomplices?: number[],
	auditors?: number[],

	relatedTaskId?: number;
	relatedTasks?: number[],

	crm?: CrmDTO[],
	tags?: TagsDTO[],
	files?: FilesDTO[],
	uploadedFiles: UploadingFilesDTO[],
	userFieldNames: string[],

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
	areUserFieldsLoaded?: boolean,

	deadline?: number,
	activityDate?: number,
	startDatePlan?: number,
	endDatePlan?: number,
	startDate?: number,
	endDate?: number,

	checklist?: ChecklistDTO,
	checklistDetails?: ChecklistDetailsDTO[],
	checklistFlatTree?: object[],
	counter?: CounterDTO,

	// todo: remove this after removing old task card
	actionsOld?: object;

	canRead?: boolean,
	canUpdate?: boolean,
	canUpdateDeadline?: boolean,
	canUpdateCreator?: boolean,
	canUpdateResponsible?: boolean,
	canUpdateAccomplices?: boolean,
	canDelegate?: boolean,
	canUpdateMark?: boolean,
	canUpdateReminder?: boolean,
	canUpdateElapsedTime?: boolean,
	canAddChecklist?: boolean,
	canUpdateChecklist?: boolean,
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

	isCreationErrorExist?: boolean,
	creationErrorText?: string,

	imChatId?: number,
	imMessageId?: number,
};

export type ChecklistDTO = {
	completed: number,
	uncompleted: number,
}

export type ChecklistDetailsDTO = {
	title: string,
	completed: number,
	uncompleted: number,
};

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
	id: string,
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
	objectId: number,
	name: string,
	size: string,
	url: string,
	type: string,
	isImage: boolean,
}

export type UploadingFilesDTO = {
	id: string,
	uuid: string,
	name: string,
	url: string,
	height: number,
	width: number,
	previewUrl: string,
	previewHeight: number,
	previewWidth: number,
	type: string,
	isUploading: boolean,
	hasError: boolean,
}

export type UserFieldsDTO = {
	id: number,
	type: string,
	entityId: string,
	fieldName: string,
	title: string,
	value: string | string[],
	sort: number,
	isMandatory: boolean,
	isMultiple: boolean,
	isVisible: boolean,
	isEditable: boolean,
	settings: any[],
}
