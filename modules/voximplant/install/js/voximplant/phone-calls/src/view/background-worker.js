import {PhoneCallView} from './view'
import {BaseWorker, callCardEvents} from "./background-worker/base-worker";
import {DesktopWorker} from "./background-worker/desktop-worker";
import {BrowserWorker} from "./background-worker/browser-worker";

export const backgroundWorkerEvents = callCardEvents;

export class BackgroundWorker
{
	platformWorker: BaseWorker;

	constructor()
	{
		this.initializePlacement();
	}

	setCallCard(callCard: ?PhoneCallView)
	{
		this.platformWorker.CallCard = callCard;
	}

	initializePlacement()
	{
		if (this.isDesktop())
		{
			this.platformWorker = new DesktopWorker();
		}
		else
		{
			this.platformWorker = new BrowserWorker();
		}

		this.platformWorker.initializePlacement();
	}

	emitEvent(name: string, params)
	{
		this.platformWorker.emitEvent(name, params);
	}

	removeDesktopEventHandlers()
	{
		this.platformWorker.removeDesktopEventHandlers();
	}
	isDesktop()
	{
		return typeof (BXDesktopSystem) !== 'undefined';
	}

	isUsed()
	{
		return this.platformWorker.isUsed();
	}

	isActiveIntoCurrentCall()
	{
		return this.isExternalCall && this.isUsed();
	}

	setExternalCall(isExternalCall: boolean)
	{
		this.platformWorker.setIsExternalCall(isExternalCall);
	}
}
