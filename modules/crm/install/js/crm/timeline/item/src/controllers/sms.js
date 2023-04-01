import {Activity} from './activity';
import ConfigurableItem from '../configurable-item';
import {PULL} from 'pull.client';
import {ajax, Text, Type} from 'main.core';
import {SmsMessage} from '../components/content-blocks/sms-message';

export class Sms extends Activity
{
	#isPullSubscribed: boolean = false;

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'Activity:Sms'
			|| item.getType() === 'Activity:Notification'
		);
	}
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{

	}
	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			SmsMessage
		};
	}

	onInitialize(item: ConfigurableItem): void
	{
		this.#subscribePullEvents(item);
	}

	#subscribePullEvents(item: ConfigurableItem)
	{
		if (this.#isPullSubscribed)
		{
			return;
		}

		const dataPayload = item.getDataPayload();
		if (dataPayload.pull && dataPayload.message)
		{
			PULL.subscribe({
				moduleId: dataPayload.pull.moduleId,
				command: dataPayload.pull.command,
				callback: (params) => {
					const messages = this.#getEventMessages(params);
					for (const message of messages)
					{
						if (
							message
							&& message.ID == dataPayload.message.id
						)
						{
							item.reloadFromServer();
						}
					}
				}
			});
			PULL.extendWatch(dataPayload.pull.tagName);

			/**
			 * For cases when a push event happened on the current hit before the subcription
			 */
			setTimeout(() => {
				item.reloadFromServer();
			}, 0);

			this.#isPullSubscribed = true;
		}
	}

	#getEventMessages(params)
	{
		if (Type.isArray(params.messages))
		{
			return params.messages;
		}

		if (params.message)
		{
			return [
				params.message
			];
		}

		return [];
	}
}
