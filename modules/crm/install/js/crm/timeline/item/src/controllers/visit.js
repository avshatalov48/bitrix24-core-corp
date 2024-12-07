import { ActionParams, Base } from './base';
import ConfigurableItem from '../configurable-item';

export class Visit extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'Activity:Visit:ChangePlayerState' && actionData && actionData.recordId)
		{
			this.#changePlayerState(item, actionData.recordId);
		}

		if (action === 'Activity:Visit:Schedule' && actionData)
		{
			this.runScheduleAction(actionData.activityId, actionData.scheduleDate);
		}
	}

	#changePlayerState(item: ConfigurableItem, recordId: Number): void
	{
		const player = item.getLayoutContentBlockById('audio');
		if (!player)
		{
			return;
		}

		if (recordId !== player.id)
		{
			return;
		}

		if (player.state === 'play')
		{
			player.pause();
		}
		else
		{
			player.play();
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:Visit');
	}
}
