import {ajax as Ajax, Type} from "main.core";
import {PULL} from "pull.client";
import type {WaitingOptions, DocumentSession, BaseObject} from "./types";

export default class Waiting
{
	documentSession: DocumentSession = null;
	object: BaseObject = null;

	constructor(waitingOptions: WaitingOptions)
	{
		const options = Type.isPlainObject(waitingOptions) ? waitingOptions : {};

		this.documentSession = options.documentSession;
		this.object = options.object;

		const loader = new BX.Loader({
			target: options.targetNode,
		});
		loader.show();

		this.bindEvents();
	}

	bindEvents(): void
	{
		PULL.subscribe({
			type: BX.PullClient.SubscriptionType.Server,
			moduleId: 'disk',
			command: 'onlyoffice',
			callback: this.handleSavedDocument.bind(this),
		});
	}

	handleSavedDocument(data): void
	{
		console.log('handleSavedDocument', data);

		Ajax.runAction('disk.api.onlyoffice.continueWithNewSession', {
			mode: 'ajax',
			json: {
				sessionId: this.documentSession.id,
				documentSessionHash: this.documentSession.hash,
			}
		}).then((response) => {
			if (response.status === 'success')
			{
				document.location.href = response.data.documentSession.link;
			}
		});
	}
}