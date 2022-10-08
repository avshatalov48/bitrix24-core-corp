import {ajax as Ajax, Text, Loc} from 'main.core';
import {Router} from 'crm.router';
import {Base} from './base';
import {ajax} from 'main.core';
import {DateTimeFormat} from "main.date";
import { DatetimeConverter } from "crm.timeline.tools";
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';

import ConfigurableItem from '../configurable-item';
import {UI} from "ui.notification";

declare type Signer = {
	title: string,
}

export class SignDocument extends Base
{
	onItemAction(item: ConfigurableItem, action: String, actionData: ?Object): void
	{
		const documentId = Text.toInteger(actionData?.documentId);
		const activityId = Text.toInteger(actionData?.activityId);

		if ((action === 'SignDocument:Open' || action === 'Activity:SignDocument:Open') && documentId > 0)
		{
			this.#openDocument(actionData);
		}
		else if ((action === 'SignDocument:Modify' || action === 'Activity:SignDocument:Modify') && documentId > 0)
		{
			this.#modifyDocument(actionData);
		}
		else if ((action === 'SignDocument:UpdateActivityDeadline' || action === 'Activity:SignDocument:UpdateActivityDeadline') && activityId > 0)
		{
			this.#updateActivityDeadline(activityId, actionData?.value);
		}
		else if (action === 'SignDocument:Resend' && documentId > 0 && actionData?.recipientHash)
		{
			this.#resendDocument(actionData);
		}
		else if (action === 'SignDocument:TouchSigner' && documentId > 0)
		{
			this.#touchSigner(actionData);
		}
		else if (action === 'SignDocument:Download' && documentId > 0)
		{
			this.#download(actionData);
		}
		else if (action === 'SignDocumentEntry:Delete' && actionData?.entryId)
		{
			MessageBox.show({
				message: actionData?.confirmationText || '',
				modal: true,
				buttons: MessageBoxButtons.YES_NO,
				onYes: () =>
				{
					return this.#deleteEntry(actionData.entryId);
				},
				onNo: (messageBox) =>
				{
					messageBox.close();
				},
			});
		}
	}

	#deleteEntry(entryId): Promise
	{
		console.log('delete entry' + entryId);
	}

	#openDocument({
		documentId,
		memberHash
	}): Promise
	{
		return Router.Instance.openSignDocumentSlider(documentId, memberHash);
	}

	#modifyDocument({documentId}): Promise
	{
		return Router.Instance.openSignDocumentModifySlider(documentId);
	}

	async #updateActivityDeadline(activityId: number, value: ?Date): Promise<void>
	{
		const valueInSiteFormat = DateTimeFormat.format(DatetimeConverter.getSiteDateFormat(), value);

		let response: {data: {document?: {activityDeadline?: string}, success?: boolean, code?: number}};
		try
		{
			response = await Ajax.runAction('crm.timeline.signdocument.updateActivityDeadline', {
				data: {
					activityId: activityId,
					activityDeadline: valueInSiteFormat,
				},
			});
		}
		catch (responseWithError)
		{
			console.error(responseWithError);
			return;
		}

		const newCreateDate = response.data.document?.activityDeadline;

		if (valueInSiteFormat !== newCreateDate)
		{
			console.error("Updated document create date without errors, but for some reason date from the backend doesn't match sent value");
		}
	}

	#resendDocument({documentId, recipientHash}): void
	{
		ajax.runAction(
			'sign.document.resendFile',
			{
				data: {
					memberHash: recipientHash,
					documentId: documentId
				},
			}
		).then(() =>
		{
			UI.Notification.Center.notify({
				content: Loc.getMessage('CRM_TIMELINE_ITEM_SIGN_DOCUMENT_RESEND_SUCCESS'),
				autoHideDelay: 5000,
			});
		}, (response) =>
		{
			UI.Notification.Center.notify({
				content: response.errors[0].message,
				autoHideDelay: 5000,
			});
		});

		console.log('resend document ' + documentId + ' for ' + recipientHash);
	}

	#touchSigner({documentId}): void
	{
		console.log('touch signer document ' + documentId);
	}

	#download({documentHash, memberHash}): void
	{
		console.log('download ' + documentHash);
		const req = ajax.xhr();
		req.open("GET", '/bitrix/services/main/ajax.php?action=sign.document.getFileForSrc' +
			'&memberHash=' + memberHash +
			'&documentHash=' + documentHash, true);
		req.responseType = "blob";

		req.onload = (oEvent) => {
			const blob = req.response;
			const url = window.URL.createObjectURL(new Blob([blob]));
			const link = document.createElement('a');
			link.href = url;
			link.setAttribute('download', 'doc.pdf');
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
		};

		req.send()
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'SignDocument'
			|| item.getType() === 'Activity:SignDocument'
		);
	}
}
