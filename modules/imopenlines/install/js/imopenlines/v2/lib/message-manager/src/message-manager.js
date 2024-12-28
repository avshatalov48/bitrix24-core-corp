import { MessageComponent } from 'im.v2.const';
import { OpenLinesMessageComponent, FormType } from 'imopenlines.v2.const';

import type { ImModelMessage } from 'im.v2.model';

type MessageComponentValues = $Values<typeof OpenLinesMessageComponent> | $Values<typeof MessageComponent>;

const OpenLinesComponentList = new Set([
	OpenLinesMessageComponent.StartDialogMessage,
	OpenLinesMessageComponent.HiddenMessage,
	OpenLinesMessageComponent.FeedbackFormMessage,
	OpenLinesMessageComponent.ImOpenLinesForm,
	OpenLinesMessageComponent.ImOpenLinesMessage,
]);

const componentForReplace = new Set([
	OpenLinesMessageComponent.ImOpenLinesForm,
	OpenLinesMessageComponent.ImOpenLinesMessage,
]);

export class OpenLinesMessageManager
{
	#message: ImModelMessage & {
		componentParams: {
			imolForm: $Values<typeof FormType>
		};
	};

	constructor(message: ImModelMessage)
	{
		this.#message = message;
	}

	checkComponentInOpenLinesList(): boolean
	{
		return OpenLinesComponentList.has(this.#message.componentId);
	}

	getMessageComponent(): MessageComponentValues
	{
		if (componentForReplace.has(this.#message.componentId))
		{
			return this.#getUpdatedComponentId();
		}

		return this.#message.componentId;
	}

	#getUpdatedComponentId(): MessageComponentValues
	{
		if (this.#message.componentParams.imolForm === FormType.like)
		{
			return OpenLinesMessageComponent.FeedbackFormMessage;
		}

		return MessageComponent.system;
	}
}
