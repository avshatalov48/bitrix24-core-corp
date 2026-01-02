import { CopilotChat, CopilotChatMessageType } from 'ai.copilot-chat.ui';
import { Loc, Runtime } from 'main.core';
import { getDefaultChatOptions } from './default-chat-options';

export class ExampleChat
{
	#copilotChat: CopilotChat;

	constructor()
	{
		this.#copilotChat = this.#createCopilotChat();
	}

	#createCopilotChat(): CopilotChat
	{
		const chat = new CopilotChat(getDefaultChatOptions());

		chat.addBotMessage({
			content: '',
			status: 'delivered',
			type: CopilotChatMessageType.WELCOME_FLOWS,
			dateCreated: (new Date()).toString(),
			viewed: true,
			params: {
				title: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_TITLE'),
				subtitle: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_SUBTITLE_EXAMPLE'),
				content: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_EXAMPLE'),
			},
		});

		chat.addBotMessage({
			content: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_MESSAGE_EXAMPLE_1'),
			status: 'delivered',
			dateCreated: (new Date()).toString(),
			viewed: true,
		});

		chat.addBotMessage({
			content: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_MESSAGE_EXAMPLE_2'),
			status: 'delivered',
			dateCreated: (new Date()).toString(),
			viewed: true,
		});

		chat.addBotMessage({
			content: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_MESSAGE_EXAMPLE_3'),
			status: 'delivered',
			dateCreated: (new Date()).toString(),
			viewed: true,
		});

		return chat;
	}

	show()
	{
		this.#copilotChat.show();
		void this.#sendAnalytics();
	}

	async #sendAnalytics(): Promise<void>
	{
		const { sendData } = await Runtime.loadExtension('ui.analytics');

		sendData({
			tool: 'tasks',
			category: 'flows',
			event: 'copilot_example_view',
			c_section: 'tasks',
			c_sub_section: 'flows_grid',
		});
	}

	hide()
	{
		this.#copilotChat.hide();
	}
}
