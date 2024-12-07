import { Loc, Text, Type } from 'main.core';
import { UI } from 'ui.notification';

import { ActionParams, Base } from './base';
import ConfigurableItem from '../configurable-item';

const DOWNLOAD_DELAY = 300;

export class Zoom extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData } = actionParams;

		if (actionType !== 'jsEvent' || !actionData)
		{
			return;
		}

		if (action === 'Activity:Zoom:CopyInviteUrl')
		{
			this.#copyToClipboard(actionData.url);
		}

		if (action === 'Activity:Zoom:Schedule')
		{
			this.runScheduleAction(actionData.activityId, actionData.scheduleDate);
		}

		if (action === 'Activity:Zoom:CopyPassword')
		{
			this.#copyToClipboard(actionData.password);
		}

		if (action === 'Activity:Zoom:DownloadAllRecords' && Type.isArray(actionData.urlList))
		{
			this.#downloadAllRecords(actionData.urlList);
		}
	}

	#copyToClipboard(input: String): void
	{
		if (Type.isStringFilled(input))
		{
			const isSuccess = BX.clipboard.copy(input);
			if (isSuccess)
			{
				UI.Notification.Center.notify({
					content: Loc.getMessage('CRM_COMMON_ACTION_COPY_TO_CLIPBOARD_SUCCESS'),
					autoHideDelay: 2000,
				});
			}
		}
	}

	#downloadAllRecords(urlList: Array): void
	{
		const download = (urls: Array) => {
			const url = urls.pop();
			const a = document.createElement('a');
			a.setAttribute('href', url);
			if ('download' in a)
			{
				a.setAttribute('download', `zoom_record_file_${Text.getRandom(5)}.m4a`);
			}
			a.setAttribute('target', '_blank');
			a.click();

			if (urls.length === 0)
			{
				clearInterval(interval);
			}
		};
		const interval = setInterval(download, DOWNLOAD_DELAY, urlList);
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:Zoom');
	}
}
