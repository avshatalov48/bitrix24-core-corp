import { CopilotChat, CopilotChatMessageType } from 'ai.copilot-chat.ui';
import type { CopilotChatMessage, CopilotChatOptions } from 'ai.copilot-chat.ui';
import { Loc, ajax, Tag } from 'main.core';
import { EditForm } from 'tasks.flow.edit-form';
import { getDefaultChatOptions } from './default-chat-options';
import { Main as IconSetMain } from 'ui.icon-set.api.core';
import 'ui.icon-set.main';
import type { FlowData } from './copilot-advice';

import './style.css';

export class Chat
{
	#flowData: FlowData;
	#copilotChat: CopilotChat;
	#ifFirstShow: boolean;

	constructor(flowData: FlowData)
	{
		this.#flowData = flowData;
		this.#copilotChat = new CopilotChat(this.#getChatOptions());
		this.#ifFirstShow = true;
	}

	show()
	{
		if (this.#ifFirstShow)
		{
			this.#copilotChat.showLoader();
			void this.#fetchAdvices();
			this.#ifFirstShow = false;
		}

		this.#copilotChat.show();
	}

	#getChatOptions(): CopilotChatOptions
	{
		const chatOptions = getDefaultChatOptions();

		chatOptions.botOptions.messageMenuItems = [
			{
				id: 'create-task',
				text: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_CREATE_TASK'),
				html: this.#getContextMenuItemHtml(Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_CREATE_TASK'), IconSetMain.TASKS),
				onclick: (event, menuItem, data) => {
					BX.SidePanel.Instance.open(this.#flowData.createTaskUrl, {
						requestMethod: 'post',
						requestParams: { DESCRIPTION: `[QUOTE]${data.message.content}[/QUOTE]` },
						cacheable: false,
					});
				},
			},
			{
				id: 'create-meeting',
				html: this.#getContextMenuItemHtml(Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_CREATE_MEETING'), IconSetMain.CALENDAR_1),
				onclick: (event, menuItem, data) => {
					const quotedMessage = `[QUOTE]${data.message.content}[/QUOTE]`;
					const sliderLoader = new BX.Calendar.SliderLoader('NEW', { entryDescription: quotedMessage });
					sliderLoader.show();
				},
			},
		];

		if (this.#flowData.canEditFlow)
		{
			chatOptions.header.menu = {
				items: [
					{
						id: 'edit-flow',
						text: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_EDIT_FLOW'),
						onclick: (event, menuItem) => {
							menuItem?.menuWindow.close?.();
							EditForm.createInstance({ flowId: this.#flowData.flowId });
						},
					},
				],
			};
		}

		return chatOptions;
	}

	#getContextMenuItemHtml(text: string, icon: string): string
	{
		return Tag.render`
			<div class="tasks-flow__copilot-chat-context-menu-item">
				<span>${text}</span>
				<span class="ui-icon-set --${icon}"></span>
			</div>
		`;
	}

	async #fetchAdvices(): void
	{
		const result = await ajax.runAction('tasks.flow.Copilot.Advice.get', { data: { flowId: this.#flowData.flowId } });

		if (result.status !== 'success')
		{
			return;
		}

		const advices: Array = result.data?.advices ?? [];
		const createDate: Date = new Date(result.data?.createDateTime ?? new Date());

		this.#copilotChat.addBotMessage(this.#getFirstMessageByEfficiency(this.#flowData.flowEfficiency, createDate));

		advices.forEach((advice: string) => {
			this.#copilotChat.addBotMessage({
				content: advice,
				status: 'delivered',
				dateCreated: createDate.toString(),
				viewed: true,
			});
		});

		this.#copilotChat.hideLoader();
	}

	#getFirstMessageByEfficiency(efficiency: number, dateCreated: Date): CopilotChatMessage
	{
		const subtitle = efficiency > 80
			? Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_SUBTITLE_HIGH', { '#EFFICIENCY#': efficiency })
			: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_SUBTITLE_LOW', { '#EFFICIENCY#': efficiency })
		;

		const content = efficiency > 80
			? Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_HIGH')
			: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_LOW')
		;

		return {
			content: '',
			status: 'delivered',
			type: CopilotChatMessageType.WELCOME_FLOWS,
			dateCreated: dateCreated.toString(),
			viewed: true,
			params: {
				title: Loc.getMessage('TASKS_FLOW_COPILOT_ADVICE_POPUP_SYSTEM_MESSAGE_TITLE'),
				subtitle,
				content,
			},
		};
	}

	hide(): void
	{
		this.#copilotChat.hide();
	}
}
