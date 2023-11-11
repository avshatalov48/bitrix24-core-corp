import {BaseWorker} from "./base-worker";
import {UiState} from "../view";

const desktopMethodEvents = {
	setUiState: 'DesktopCallCardSetUiState',
	setMute: 'DesktopCallCardSetMute',
	setHold: 'DesktopCallCardSetHold',
	getListUiState: 'DesktopCallCardGetListUiState',
	setCardTitle: 'DesktopCallCardSetCardTitle',
	setStatusText: 'DesktopCallCardSetStatusText',
	close: 'DesktopCallCardClose',
	startTimer: 'DesktopCallCardStartTimer',
	stopTimer: 'DesktopCallCardStopTimer',
};

const corporatePortalPageEvents = {
	addCommentButtonClick: 'DesktopCallCardAddCommentButtonClick',
	muteButtonClick: 'DesktopCallCardMuteButtonClick',
	holdButtonClick: 'DesktopCallCardHoldButtonClick',
	transferButtonClick: 'DesktopCallCardTransferButtonClick',
	cancelTransferButtonClick: 'DesktopCallCardCancelTransferButtonClick',
	completeTransferButtonClick: 'DesktopCallCardCompleteTransferButtonClick',
	hangupButtonClick: 'DesktopCallCardHangupButtonClick',
	nextButtonClick: 'DesktopCallCardNextButtonClick',
	skipButtonClick: 'DesktopCallCardSkipButtonClick',
	answerButtonClick: 'DesktopCallCardAnswerButtonClick',
	entityChanged: 'DesktopCallCardEntityChanged',
	qualityMeterClick: 'DesktopCallCardQualityMeterClick',
	dialpadButtonClick: 'DesktopCallCardDialpadButtonClick',
	makeCallButtonClick: 'DesktopCallCardMakeCallButtonClick',
	notifyAdminButtonClick: 'DesktopCallCardNotifyAdminButtonClick',
};

export class DesktopWorker extends BaseWorker
{

	eventHandlers: Array;
	constructor() {
		super();

		this.eventHandlers = [];
	}

	isCardActive(): boolean
	{
		return super.isCardActive() || this.CallCard !== null;
	}

	initializePlacement()
	{
		const placement = BX.rest.AppLayout.initializePlacement('PAGE_BACKGROUND_WORKER');

		this.initializeInterfaceMethods(placement);
		this.initializeInterfaceEvents(placement);
		this.addEventHandlersForEventsFromCallCard();
	}

	initializeInterface(placement)
	{

		this.initializeInterfaceMethods(placement);
		this.addTransmitEventHandlers();

	}
	initializeInterfaceMethods(placement)
	{
		placement.prototype.CallCardGetListUiStates = (params, callback) => this.getListUiStates(params, callback);

		placement.prototype.CallCardSetMute = (params, callback) => this.transmitSetMute(params, callback);
		placement.prototype.CallCardSetHold = (params, callback) => this.transmitSetHold(params, callback);
		placement.prototype.CallCardSetUiState = (params, callback) => this.transmitSetUiState(params, callback);
		placement.prototype.CallCardSetCardTitle = (params, callback) => this.transmitSetCardTitle(params, callback);
		placement.prototype.CallCardSetStatusText = (params, callback) => this.transmitSetStatusText(params, callback);
		placement.prototype.CallCardClose = (params, callback) => this.transmitClose(params, callback);
		placement.prototype.CallCardStartTimer = (params, callback) => this.transmitStartTimer(params, callback);
		placement.prototype.CallCardStopTimer = (params, callback) => this.transmitStopTimer(params, callback);
	}

	emitInitializeEvent(params)
	{
		if (!this.isExternalCall)
		{
			return;
		}

		if (this.isCallCardPage())
		{
			BXDesktopSystem.BroadcastEvent('DesktopCallCardInitialized', [params]);

			return;
		}

		BX.onCustomEvent(window, "BackgroundCallCard::initialized", [params]);
	}

	emitEvent(name: string, params)
	{
		if (!this.isExternalCall)
		{
			return;
		}

		if (this.isCallCardPage())
		{
			const desktopEventName = 'DesktopCallCard' + (name[0].toUpperCase() + name.slice(1));
			BXDesktopSystem.BroadcastEvent(desktopEventName, [params]);
		}

		BX.onCustomEvent(window, 'BackgroundCallCard::' + name, [params]);
	}

	removeDesktopEventHandlers()
	{
		for (const event in this.getEvents())
		{
			this.removeCustomEvents('DesktopCallCard' + (event[0].toUpperCase() + event.slice(1)));
		}
		this.removeCustomEvents('DesktopCallCardInitialized');
		this.removeCustomEvents('DesktopCallCardCloseButtonClick');
	}
	/*
	 Transmit an event about changing the call card by calling methods
	 from the rest application from the corporate portal window
	 */
	//region Transmit events

	addTransmitEventHandlers()
	{
		this
			.addCustomEvent(
				desktopMethodEvents.getListUiState,
				(params, callback) =>
					this.onTransmitHandler(params, callback, this.getListUiStates)
			)
			.addCustomEvent(
				desktopMethodEvents.setUiState,
				(params, callback) =>
					this.onTransmitHandler(params, callback, this.getCallCardPlatformWorker().setUiState)
			)
			.addCustomEvent(
				desktopMethodEvents.setMute,
				(params, callback) =>
					this.onTransmitHandler(params, callback, this.getCallCardPlatformWorker().setMute)
			)
			.addCustomEvent(
				desktopMethodEvents.setHold,
				(params, callback) =>
					this.onTransmitHandler(params, callback, this.getCallCardPlatformWorker().setHold)
			)
			.addCustomEvent(
				desktopMethodEvents.setCardTitle,
				(params, callback) =>
					this.onTransmitHandler(params, callback, this.getCallCardPlatformWorker().setCardTitle)
			)
			.addCustomEvent(
				desktopMethodEvents.setStatusText,
				(params, callback) =>
					this.onTransmitHandler(params, callback, this.getCallCardPlatformWorker().setStatusText)
			)
			.addCustomEvent(
				desktopMethodEvents.close,
				(params, callback) =>
					this.onTransmitHandler(params, callback, this.getCallCardPlatformWorker().close)
			)
			.addCustomEvent(
				desktopMethodEvents.startTimer,
				(params, callback) =>
					this.onTransmitHandler(params, callback, this.getCallCardPlatformWorker().startTimer)
			)
			.addCustomEvent(
				desktopMethodEvents.stopTimer,
				(params, callback) =>
					this.onTransmitHandler(params, callback, this.getCallCardPlatformWorker().stopTimer)
			);
	}

	onTransmitHandler(params: any, callback: Function, handler: Function)
	{
		if (this.isCorporatePortalPage())
		{
			return;
		}
		this.used = true;
		callback = typeof (callback) === 'function' ? callback : BX.DoNothing;

		handler(params, callback);
	}

	transmitSetUiState(params: {uiState: string, disableAutoStartTimer?: boolean}, callback: Function)
	{
		if (!this.isCardActive())
		{
			callback([this.getUndefinedCallCardError()]);
			return;
		}
		if (!params.hasOwnProperty('uiState') || !UiState[params.uiState])
		{
			callback([{
				result: 'error',
				errorCode: 'Invalid ui state'
			}]);

			return;
		}
		BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setUiState, [params, BX.DoNothing]);
		callback([]);
	}

	transmitSetMute(params: {muted: boolean}, callback: Function)
	{
		if (!this.isCardActive())
		{
			callback([this.getUndefinedCallCardError()]);
			return;
		}
		if (!params.hasOwnProperty('muted'))
		{
			callback({
				result: 'error',
				errorCode: 'missing field muted'
			});

			return;
		}

		BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setMute, [params, BX.DoNothing]);
		callback([]);
	}

	transmitSetHold(params: {held: boolean}, callback: Function)
	{
		if (!this.isCardActive())
		{
			callback([this.getUndefinedCallCardError()]);
			return;
		}

		if (!params.hasOwnProperty('held'))
		{
			callback([{
				result: 'error',
				errorCode: 'missing field held'
			}]);
		}

		BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setHold, [params, BX.DoNothing]);
		callback([]);
	}

	transmitStartTimer(params: {}, callback: Function)
	{
		if (!this.isCardActive())
		{
			callback([this.getUndefinedCallCardError()]);
			return;
		}

		BXDesktopSystem.BroadcastEvent(desktopMethodEvents.startTimer, [params, BX.DoNothing]);
		callback([]);
	}

	transmitStopTimer(params: {}, callback: Function)
	{
		if (!this.isCardActive())
		{
			callback([this.getUndefinedCallCardError()]);
			return;
		}

		BXDesktopSystem.BroadcastEvent(desktopMethodEvents.stopTimer, [params, BX.DoNothing]);
		callback([]);
	}

	transmitClose(params: {}, callback: Function)
	{
		if (!this.isCardActive())
		{
			callback([this.getUndefinedCallCardError()]);
			return;
		}

		this.CallCard = null;
		BXDesktopSystem.BroadcastEvent(desktopMethodEvents.close, [params, BX.DoNothing]);
		callback([]);
	}

	transmitSetCardTitle(params: {title: string}, callback: Function)
	{
		if (!this.isCardActive())
		{
			callback([this.getUndefinedCallCardError()]);
			return;
		}
		if (!params.hasOwnProperty('title'))
		{
			callback([{
				result: 'error',
				errorCode: 'missing field title'
			}]);

			return
		}

		BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setCardTitle, [params, BX.DoNothing]);
		callback([]);
	}

	transmitSetStatusText(params: {statusText: string}, callback: Function)
	{
		if (!this.isCardActive())
		{
			callback([this.getUndefinedCallCardError()]);
			return;
		}
		if (!params.hasOwnProperty('statusText'))
		{
			callback([{
				result: 'error',
				errorCode: 'missing field statusText'
			}]);

			return;
		}

		BXDesktopSystem.BroadcastEvent(desktopMethodEvents.setStatusText, [params, BX.DoNothing]);
		callback([]);
	}
	//endregion

	addEventHandlersForEventsFromCallCard()
	{
		if (!this.isCorporatePortalPage())
		{
			return;
		}
		for (const event in this.getEvents())
		{
			this.addCustomEvent(corporatePortalPageEvents[event], (params, callback) =>
			{
				BX.onCustomEvent(window, 'BackgroundCallCard::' + event, [params, callback]);
			})
		}

		this.addCustomEvent('DesktopCallCardInitialized', (params, callback) =>
		{
			if (!this.CallCard)
			{
				this.CallCard = true;
			}
			BX.onCustomEvent(window, 'BackgroundCallCard::initialized', [params, callback]);
		});

		this.addCustomEvent('DesktopCallCardCloseButtonClick', (params, callback) =>
		{
			this.CallCard = null;
			BX.onCustomEvent(window, 'BackgroundCallCard::closeButtonClick', [params, callback]);
		});
	}
	addCustomEvent(eventName: string, eventHandler: Function)
	{
		const realHandler = function (e)
		{
			const arEventParams = [];
			for (const i in e.detail)
			{
				arEventParams.push(e.detail[i]);
			}

			eventHandler.apply(window, arEventParams);
		};

		if (!this.eventHandlers[eventName])
		{
			this.eventHandlers[eventName] = [];
		}

		this.eventHandlers[eventName].push(realHandler);
		window.addEventListener(eventName, realHandler);

		return this;
	}

	removeCustomEvents(eventName)
	{
		if (!this.eventHandlers[eventName])
		{
			return false;
		}

		this.eventHandlers[eventName].forEach(eventHandler => window.removeEventListener(eventName, eventHandler));
		this.eventHandlers[eventName] = [];
	}

	isCallCardPage(): boolean
	{
		return BXDesktopWindow.GetWindowId() !== BXDesktopSystem.GetMainWindow().GetWindowId()
	}

	isCorporatePortalPage(): boolean
	{
		return typeof(BXDesktopSystem) == "undefined" && typeof(BXDesktopWindow) == "undefined";
	}

	getCallCardWindow(): window
	{
		return BXWindows.find(element => element.name === 'callWindow');
	}

	getCallCardPlatformWorker(): BaseWorker
	{
		const callWindow = this.getCallCardWindow();

		return callWindow.PCW.backgroundWorker.platformWorker;
	}
}