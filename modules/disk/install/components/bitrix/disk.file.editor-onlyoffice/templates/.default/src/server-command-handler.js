import BaseCommandHandler from "./base-command-handler";
import {ajax as Ajax} from "main.core";

export default class ServerCommandHandler extends BaseCommandHandler
{
	getMap(): Object
	{
		return {
			onlyoffice: this.filterCurrentObject(this.handleSavedDocument.bind(this)),
		};
	}

	handleSavedDocument(data): void
	{
		console.log('handleSavedDocument', data);

		Ajax.runAction('disk.api.onlyoffice.continueWithNewSession', {
			mode: 'ajax',
			json: {
				sessionId: this.context.documentSession.id,
				documentSessionHash: this.context.documentSession.hash,
			}
		}).then((response) => {
			if (response.status === 'success')
			{
				document.location.href = response.data.documentSession.link;
			}
		});
	}
}