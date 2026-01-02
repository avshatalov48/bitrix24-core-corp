export type InterfaceModelParams = {
	currentUserId: number,
	defaultDeadline: DefaultDeadline,
};

export type InterfaceModelState = {
	currentUserId: number,
	titleFieldOffsetHeight: ?number,
	defaultDeadline: DefaultDeadline,
	deletingCheckListIds: { [key: number ]: number },
	disableCheckListAnimations: boolean,
	checkListCompletionCallbacks: CheckListCompletionCallbacks;
};

export type DefaultDeadline = {
	defaultDeadlineInSeconds: number,
	defaultDeadlineDate: string,
};

export type CheckListCompletionCallbacks = { [id: string]: CheckListCompletionCallback };

export type CheckListCompletionCallback = () => void;
