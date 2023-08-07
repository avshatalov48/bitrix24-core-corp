import {UiState, PhoneCallView, desktopEvents} from "../view";


export const callCardEvents = {
	addCommentButtonClick: 'addCommentButtonClick',
	muteButtonClick: 'muteButtonClick',
	holdButtonClick: 'holdButtonClick',
	transferButtonClick: 'transferButtonClick',
	cancelTransferButtonClick: 'cancelTransferButtonClick',
	completeTransferButtonClick: 'completeTransferButtonClick',
	hangupButtonClick: 'hangupButtonClick',
	nextButtonClick: 'nextButtonClick',
	skipButtonClick: 'skipButtonClick',
	answerButtonClick: 'answerButtonClick',
	entityChanged: 'entityChanged',
	qualityMeterClick: 'qualityMeterClick',
	dialpadButtonClick: 'dialpadButtonClick',
	makeCallButtonClick: 'makeCallButtonClick',
	notifyAdminButtonClick: 'notifyAdminButtonClick',
	closeButtonClick: 'closeButtonClick',
};

const UndefinedCallCard = {
	result: 'error',
	errorCode: 'Call card is undefined'
}

/** @abstract */
export class BaseWorker
{

	isExternalCall: boolean = false;
	used: boolean = false;
	CallCard: ?PhoneCallView;



	/** @abstract */
	initializePlacement(): BaseWorker
	{
		throw new Error('You have to implement the method initializePlacement!');
	}

	/** @abstract */
	initializeInterface(placement: Object): BaseWorker
	{
		throw new Error('You have to implement the method initializeInterface!');
	}

	/** @abstract */
	emitInitializeEvent(params)
	{
		throw new Error('You have to implement the method emitInitializeEvent!');
	}

	/** @abstract */
	emitEvent(name: string, params)
	{
		throw new Error('You have to implement the method emitEvent!');
	};

	setCallCard(callCard: ?PhoneCallView): BaseWorker
	{
		this.CallCard = callCard;

		return this;
	}

	setIsExternalCall(isExternalCall: boolean): BaseWorker
	{
		this.isExternalCall = isExternalCall;

		return this;
	}

	isCardActive(): boolean
	{
		return this.CallCard instanceof PhoneCallView;
	}

	isUsed(): boolean
	{
		return this.used;
	}

	initializeInterfaceEvents(placement: Object): BaseWorker
	{
		placement.prototype.events.push('BackgroundCallCard::initialized');
		placement.prototype.events.push('BackgroundCallCard::addCommentButtonClick');
		placement.prototype.events.push('BackgroundCallCard::muteButtonClick');
		placement.prototype.events.push('BackgroundCallCard::holdButtonClick');
		placement.prototype.events.push('BackgroundCallCard::closeButtonClick');
		placement.prototype.events.push('BackgroundCallCard::transferButtonClick');
		placement.prototype.events.push('BackgroundCallCard::cancelTransferButtonClick');
		placement.prototype.events.push('BackgroundCallCard::completeTransferButtonClick');
		placement.prototype.events.push('BackgroundCallCard::hangupButtonClick');
		placement.prototype.events.push('BackgroundCallCard::nextButtonClick');
		placement.prototype.events.push('BackgroundCallCard::skipButtonClick');
		placement.prototype.events.push('BackgroundCallCard::answerButtonClick');
		placement.prototype.events.push('BackgroundCallCard::entityChanged');
		placement.prototype.events.push('BackgroundCallCard::qualityMeterClick');
		placement.prototype.events.push('BackgroundCallCard::dialpadButtonClick');
		placement.prototype.events.push('BackgroundCallCard::makeCallButtonClick');
		placement.prototype.events.push('BackgroundCallCard::notifyAdminButtonClick');

		return this;
	}

	getEvents()
	{
		return callCardEvents;
	}

	getListUiStates(params: {}, callback: Function): void
	{
		this.used = true;
		callback(Object.keys(UiState).filter((state) =>
		{
			switch (state)
			{
				case 'sipPhoneError':
					return false;
				case 'idle':
					return false;
				case 'externalCard':
					return false;
				default:
					return true;
			}
		}));
	}

	setUiState(params: {uiState: string, disableAutoStartTimer: ?boolean}, callback: Function): void
	{
		this.used = true;
		if (!this.isCardActive() || !this.isExternalCall)
		{
			callback([this.getUndefinedCallCardError()]);

			return;
		}

		if (params && params.uiState && UiState[params.uiState])
		{
			this.CallCard.setUiState(UiState[params.uiState]);
			// BX.onCustomEvent(window, "CallCard::CallStateChanged", [callState, additionalParams]);
			// this.setOnSlave(desktopEvents.setCallState, [callState, additionalParams]);
		}
		else
		{
			callback([{
				result: 'error',
				errorCode: 'Invalid ui state'
			}]);

			return;
		}
		if (params.uiState === 'connected')
		{
			if (params.disableAutoStartTimer)
			{
				this.CallCard.stopTimer();
				this.hideTimer();
			}
			else
			{
				this.showTimer();
			}
		}

		if (params.uiState !== 'connected' && !this.CallCard.isTimerStarted())
		{
			this.hideTimer();
		}

		callback([]);
	}

	setMute(params: {muted: boolean}, callback: Function): void
	{
		this.used = true;
		if (!this.isCardActive() || !this.isExternalCall)
		{
			callback([this.getUndefinedCallCardError()]);

			return;
		}

		if (this.CallCard.isMuted() === !!params.muted)
		{
			callback([]);

			return;
		}
		if (params.muted)
		{
			this.CallCard.setMuted(params.muted)
			BX.addClass(this.CallCard.elements.buttons.mute, 'active');
			if (this.CallCard.isDesktop() && this.CallCard.slave)
			{
				BX.desktop.onCustomEvent(desktopEvents.onMute, []);
			}
			else
			{
				this.CallCard.callbacks.mute();
			}
		}
		else
		{
			this.CallCard.setMuted(params.muted)
			BX.removeClass(this.CallCard.elements.buttons.mute, 'active');
			if (this.CallCard.isDesktop() && this.CallCard.slave)
			{
				BX.desktop.onCustomEvent(desktopEvents.onUnMute, []);
			}
			else
			{
				this.CallCard.callbacks.unmute();
			}
		}
		callback([]);
	}

	setHold(params: {held: boolean}, callback: Function): void
	{
		this.used = true;
		if (!this.isCardActive() || !this.isExternalCall)
		{
			callback([this.getUndefinedCallCardError()]);

			return;
		}

		if (this.CallCard.isHeld() === !!params.held)
		{
			callback([]);

			return;
		}
		if (params.held)
		{
			this.CallCard.setHeld(params.held)
			BX.addClass(this.CallCard.elements.buttons.hold, 'active');
			if (this.CallCard.isDesktop() && this.CallCard.slave)
			{
				BX.desktop.onCustomEvent(desktopEvents.onHold, []);
			}
			else
			{
				this.CallCard.callbacks.hold();
			}
		}
		else
		{
			this.CallCard.setHeld(params.held);
			BX.removeClass(this.CallCard.elements.buttons.hold, 'active');
			if (this.CallCard.isDesktop() && this.CallCard.slave)
			{
				BX.desktop.onCustomEvent(desktopEvents.onUnHold, []);
			}
			else
			{
				this.CallCard.callbacks.unhold();
			}
		}
		callback([]);
	}

	startTimer(params: {}, callback: Function): void
	{
		this.used = true;
		if (!this.isCardActive() || !this.isExternalCall)
		{
			callback([this.getUndefinedCallCardError()]);

			return;
		}

		this.showTimer();
		this.CallCard.startTimer();
	}

	stopTimer(params: {}, callback: Function): void
	{
		this.used = true;
		if (!this.isCardActive() || !this.isExternalCall)
		{
			callback([this.getUndefinedCallCardError()]);

			return;
		}

		this.CallCard.stopTimer();
	}

	close(params: {}, callback: Function): void
	{
		this.used = true;
		if (!this.isCardActive() || !this.isExternalCall)
		{
			callback([this.getUndefinedCallCardError()]);

			return;
		}

		this.CallCard.close();
		callback([]);

		this.CallCard = false;
	}

	setCardTitle(params: {title: string}, callback: Function): void
	{
		this.used = true;
		if (!this.isCardActive() || !this.isExternalCall)
		{
			callback([this.getUndefinedCallCardError()]);

			return;
		}
		this.CallCard.setTitle(params.title);
		callback([]);
	}

	setStatusText(params: {statusText: string}, callback: Function): void
	{
		this.used = true;
		if (!this.isCardActive() || !this.isExternalCall)
		{
			callback([this.getUndefinedCallCardError()]);

			return;
		}
		this.CallCard.setStatusText(params.statusText);
		callback([]);
	}

	showTimer(): void
	{
		if (!this.CallCard.elements.timer.visible)
		{
			this.CallCard.sections.timer.visible = true;
			this.CallCard.elements.timer.style.display = '';
			if(this.CallCard.isFolded())
			{
				this.CallCard.unfoldedElements.timer.style.display = '';
			}
		}
	}

	hideTimer(): void
	{
		if (this.CallCard.sections.timer)
		{
			this.CallCard.sections.timer.visible = false;
		}
		if (this.CallCard.elements.timer)
		{
			this.CallCard.elements.timer.style.display = 'none';
		}
		this.CallCard.initialTimestamp = 0;
	}

	getUndefinedCallCardError()
	{
		return UndefinedCallCard;
	}
}