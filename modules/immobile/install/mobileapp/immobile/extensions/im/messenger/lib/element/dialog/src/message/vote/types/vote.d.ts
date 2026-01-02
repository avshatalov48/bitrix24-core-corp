export type VoteMessageData = {
	button: VoteMessageButtonData,
	questions: Array<VoteMessageQuestionData>,
	shouldShowResults: boolean,
	bottomText: string,
}

export type VoteMessageButtonData = {
	design: string,
	disabled: boolean,
	hasLoader: boolean,
	height: string,
	id: string,
	text: string,
	type: string,
}

export type VoteMessageQuestionData = {
	id: string, text: string, progressPercent: number, isSelected: boolean,
}
