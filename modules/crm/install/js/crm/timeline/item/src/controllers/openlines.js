import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import ChatMessage from '../components/content-blocks/chat-message';

export class OpenLines extends Base
{
	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			ChatMessage,
		};
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData} = actionParams;
		if (actionType !== 'jsEvent')
		{
			return;
		}
		if (action === 'Openline:OpenChat' && actionData && actionData.dialogId)
		{
			this.#openChat(actionData.dialogId);
		}
	}

	#openChat(dialogId): void
	{
		if (window.top['BXIM'])
		{
			window.top['BXIM'].openMessengerSlider(dialogId, {RECENT: 'N', MENU: 'N'});
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:OpenLine');
	}
}

