export type CopilotChatMessage = {
	id?: number;
	content: string;
	authorId: number;
	status?: 'sending' | 'delivered' | '';
	params?: CopilotChatMessageParams;
	dateCreated?: string;
	type?: 'Default' | 'GreetingFlows' | 'GreetingSiteWithAi' | 'System' | 'ButtonClicked';
	viewed: boolean;
};

type CopilotChatMessageParams = {
	buttons?: CopilotChatMessageButton[];
	buttonId?: number;
	messageId?: number;
}

export type CopilotChatMessageButton = {
	id: number;
	text: string;
	isSelected: boolean;
	title: string;
}

export const CopilotChatMessageType = Object.freeze({
	DEFAULT: 'Default',
	BUTTON_CLICK_MESSAGE: 'ButtonClicked',
	WELCOME_FLOWS: 'WelcomeFlows',
	WELCOME_SITE_WITH_AI: 'GreetingSiteWithAi',
	SYSTEM: 'System',
});
