import { Base, type BasePayloadMarkers } from './basepayload';

type TextPayloadPromptCommand = {
	code: string;
}

type TextPayload = {
	prompt: TextPayloadPromptCommand | string;
	engineCode?: string;
	roleCode?: string;
}

type TextPayloadMarkers = {
	original_message?: string,
	user_message?: string;
} & BasePayloadMarkers;

export class Text extends Base
{
	payload: TextPayload;

	/**
	 *
	 * @param {TextPayload} payload
	 */
	// eslint-disable-next-line no-useless-constructor
	constructor(payload: TextPayload) {
		super(payload);
	}

	setMarkers(markers: TextPayloadMarkers): this {
		return super.setMarkers(markers);
	}

	getMarkers(): TextPayloadMarkers {
		return super.getMarkers();
	}

	getPrettifiedData(): TextPayload
	{
		return super.getPrettifiedData();
	}

	getRawData(): TextPayload
	{
		return super.getRawData();
	}
}
