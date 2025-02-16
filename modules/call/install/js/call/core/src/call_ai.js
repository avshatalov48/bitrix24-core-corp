import { Extension } from 'main.core';
import { CopilotNotifyType } from './view/copilot-notify';


export type CallAiOptions = {
	serviceEnabled?: boolean;
	settingsEnabled?: boolean;
	recordingMinUsers?: number;
	agreementAccepted?: boolean;
	tariffAvailable?: boolean;
	baasAvailable?: boolean;
	feedBackLink?: string;
	baasPromoSlider?: string;
	helpSlider?: string;
};

export const CallAiError = {
	AI_UNAVAILABLE_ERROR: 'AI_UNAVAILABLE_ERROR',
	AI_SETTINGS_ERROR: 'AI_SETTINGS_ERROR',
	AI_AGREEMENT_ERROR: 'AI_AGREEMENT_ERROR',
	AI_NOT_ENOUGH_BAAS_ERROR: 'AI_NOT_ENOUGH_BAAS_ERROR',
};

class CallAi
{
	constructor()
	{
		this.serviceEnabled = false;
		this.settingsEnabled = false;
		this.recordingMinUsers = 1000;
		this.agreementAccepted = false;
		this.tariffAvailable = false;
		this.baasAvailable = false;
		this.feedBackLink = '';
		this.baasPromoSlider = '';
		this.helpSlider = '';

		if (Extension.getSettings('call.core').ai)
		{
			this.setup(Extension.getSettings('call.core').ai);
		}
	}

	setup(options: CallAiOptions)
	{
		if (options.serviceEnabled !== undefined)
		{
			this.serviceEnabled = options.serviceEnabled;
		}
		if (options.settingsEnabled !== undefined)
		{
			this.settingsEnabled = options.settingsEnabled;
		}
		if (options.recordingMinUsers)
		{
			this.recordingMinUsers = options.recordingMinUsers;
		}
		if (options.agreementAccepted !== undefined)
		{
			this.agreementAccepted = options.agreementAccepted;
		}
		if (options.tariffAvailable !== undefined)
		{
			this.tariffAvailable = options.tariffAvailable;
		}
		if (options.baasAvailable !== undefined)
		{
			this.baasAvailable = options.baasAvailable;
		}
		if (options.feedBackLink)
		{
			this.feedBackLink = options.feedBackLink;
		}
		if (options.baasPromoSlider)
		{
			this.baasPromoSlider = options.baasPromoSlider;
		}
		if (options.helpSlider)
		{
			this.helpSlider = options.helpSlider;
		}
	}

	get serviceEnabled(): boolean
	{
		return this._serviceEnabled;
	}
	set serviceEnabled(flag: boolean)
	{
		this._serviceEnabled = flag;
	}

	get settingsEnabled(): boolean
	{
		return this._settingsEnabled;
	}
	set settingsEnabled(flag: boolean)
	{
		this._settingsEnabled = flag;
	}

	get recordingMinUsers(): number
	{
		return this._recordingMinUsers;
	}
	set recordingMinUsers(value: number)
	{
		this._recordingMinUsers = value;
	}

	get agreementAccepted(): boolean
	{
		return this._agreementAccepted;
	}
	set agreementAccepted(flag: boolean)
	{
		this._agreementAccepted = flag;
	}

	get tariffAvailable(): boolean
	{
		return this._tariffAvailable;
	}
	set tariffAvailable(flag: boolean)
	{
		this._tariffAvailable = flag;
	}

	get baasAvailable(): boolean
	{
		return this._baasAvailable;
	}
	set baasAvailable(flag: boolean)
	{
		this._baasAvailable = flag;
	}

	get feedBackLink(): string
	{
		return this._feedBackLink;
	}
	set feedBackLink(value: string)
	{
		this._feedBackLink = value;
	}

	get baasPromoSlider(): string
	{
		return this._baasPromoSlider;
	}
	set baasPromoSlider(value: string)
	{
		this._baasPromoSlider = value;
	}

	get helpSlider(): string
	{
		return this._helpSlider;
	}
	set helpSlider(value: string)
	{
		this._helpSlider = value;
	}

	handleCopilotError(errorType: string)
	{
		switch (errorType)
		{
			case CallAiError.AI_UNAVAILABLE_ERROR:
				this.tariffAvailable = false;
				break;
			case CallAiError.AI_SETTINGS_ERROR:
				this.settingsEnabled = false;
				break;
			case CallAiError.AI_AGREEMENT_ERROR:
				this.agreementAccepted = false;
				break;
			case CallAiError.AI_NOT_ENOUGH_BAAS_ERROR:
				this.baasAvailable = false;
				break;
			default:
				console.error('there are no such errorTypes');
				break;
		}
	}
}

export const CallAI = new CallAi();