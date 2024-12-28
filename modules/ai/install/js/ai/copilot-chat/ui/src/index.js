import { type CopilotChatBotOptions as chpo, type CopilotChatOptions as cco, CopilotChat, CopilotChatEvents } from './copilot-chat';

export * from './types';
export type CopilotChatPopupOptions = chpo;
export type CopilotChatOptions = cco;

export {
	CopilotChat,
	CopilotChatEvents,
};
