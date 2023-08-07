import {BaseWorker} from "./base-worker";


export class BrowserWorker extends BaseWorker
{

	initializePlacement()
	{
		const placement = BX.rest.AppLayout.initializePlacement('PAGE_BACKGROUND_WORKER');

		this.initializeInterface(placement);
		this.initializeInterfaceEvents(placement)
	}

	initializeInterface(placement)
	{
		placement.prototype.CallCardSetMute = (params, callback) => this.setMute(params, callback);
		placement.prototype.CallCardSetHold = (params, callback) => this.setHold(params, callback);
		placement.prototype.CallCardSetUiState = (params, callback) => this.setUiState(params, callback);
		placement.prototype.CallCardGetListUiStates = (params, callback) => this.getListUiStates(params, callback);
		placement.prototype.CallCardSetCardTitle = (params, callback) => this.setCardTitle(params, callback);
		placement.prototype.CallCardSetStatusText = (params, callback) => this.setStatusText(params, callback);
		placement.prototype.CallCardClose = (params, callback) => this.close(params, callback);
		placement.prototype.CallCardStartTimer = (params, callback) => this.startTimer(params, callback);
		placement.prototype.CallCardStopTimer = (params, callback) => this.stopTimer(params, callback);
	}
	emitEvent(name: string, params)
	{
		if (!this.isExternalCall)
		{
			return;
		}

		BX.onCustomEvent(window, 'BackgroundCallCard::' + name, [params]);
	}

	emitInitializeEvent(params)
	{
		if (!this.isExternalCall)
		{
			return;
		}

		BX.onCustomEvent(window, "BackgroundCallCard::initialized", [params]);
	}
}