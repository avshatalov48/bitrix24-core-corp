export type CopilotMessageCopilotData = {
	buttons: Array<CopilotMessageButtonsData>,
	promt?: CopilotMessagePromtData,
	error?: { text: string },
	footnote?: string
}

export type CopilotMessageButtonsData = {
	id: string,
	text: string,
	editable: boolean,
	leftIcon?: null | string,
	code?: 'ability' | 'greeting' | 'congratulation',
}

export type CopilotMessagePromtData = {
	title: string,
	text: string,
}

export type InitialPromptMessageData = {
	code: string,
	isNew: boolean,
	promptType: string,
	title: string,
	text: string,
}
