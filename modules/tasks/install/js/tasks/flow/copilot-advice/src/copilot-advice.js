import { Chat } from './chat';
import { ExampleChat } from './example-chat';

export type FlowData = {
	flowId: number,
	flowEfficiency: number,
	canEditFlow: boolean,
	createTaskUrl: string,
};

export class CopilotAdvice
{
	static currentChat: Chat | ExampleChat | null = null;

	static showExample(): void
	{
		if (CopilotAdvice.currentChat)
		{
			CopilotAdvice.currentChat.hide();
		}

		CopilotAdvice.currentChat = new ExampleChat();
		CopilotAdvice.currentChat.show();
	}

	static show(flowData: FlowData)
	{
		if (CopilotAdvice.currentChat)
		{
			CopilotAdvice.currentChat.hide();
		}

		CopilotAdvice.currentChat = new Chat(flowData);
		CopilotAdvice.currentChat.show();
	}
}
