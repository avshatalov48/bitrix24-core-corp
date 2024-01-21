import { EventType } from 'im.v2.const';
import { DesktopApi } from 'im.v2.lib.desktop-api';
import { Responder } from './responder';
import { EditFile } from './scenarios/edit-file';
import { OpenReadOnlyFile } from './scenarios/open-read-only-file';
import { Command } from './types';

export class BxLinkHandler
{
	static init(): BxLinkHandler
	{
		return new BxLinkHandler();
	}

	constructor()
	{
		this.#subscribeToBxProtocolEvent();
	}

	#subscribeToBxProtocolEvent(): void
	{
		DesktopApi.subscribe(EventType.desktop.onBxLink, (command: $Keys<typeof Command>, rawParams) => {
			const params = rawParams ?? {};

			Object.entries(params).forEach(([key, value]) => {
				params[key] = decodeURIComponent(value);
			});

			const { objectId, url, name, uidRequest } = params;
			if (!objectId && !url)
			{
				return;
			}

			if (uidRequest)
			{
				(new Responder(uidRequest)).answer();
			}

			if (command === Command.openFile)
			{
				const editFileScenario = new EditFile({ objectId, url, name });
				editFileScenario.run();
			}
			else if (command === Command.viewFile)
			{
				const readOnlyFileScenario = new OpenReadOnlyFile({ objectId, url, name });
				readOnlyFileScenario.run();
			}
		});
	}
}
