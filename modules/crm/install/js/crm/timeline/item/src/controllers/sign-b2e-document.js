import { Dom, Text, Loc, ajax } from 'main.core';
import { Router } from 'crm.router';
import { Base } from './base';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';

import ConfigurableItem from '../configurable-item';
import { UI } from 'ui.notification';

declare type Signer = {
	title: string,
}

export class SignB2eDocument extends Base
{
	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const { action, actionType, actionData, animationCallbacks } = actionParams;
		if (actionType !== 'jsEvent')
		{
			return;
		}
		const documentId = Text.toInteger(actionData?.documentId);
		const processUri = actionData?.processUri;
		const documentHash = actionData?.documentHash || '';
		if ((action === 'SignB2eDocument:ShowSigningProcess'
			|| action === 'Activity:SignB2eDocument:ShowSigningProcess') && processUri.length > 0)
		{
			this.#showSigningProcess(processUri);
		}
		else if ((action === 'SignB2eDocument:Modify' || action === 'Activity:SignB2eDocument:Modify') && documentId > 0)
		{
			this.#modifyDocument(actionData);
		}
		else if (action === 'SignB2eDocument:Resend' && documentId > 0 && actionData?.recipientHash)
		{
			// eslint-disable-next-line promise/catch-or-return
			this.#resendDocument(actionData, animationCallbacks).then(() => {
				if (actionData.buttonId)
				{
					const btn = item.getLayoutFooterButtonById(actionData.buttonId);
					btn.disableWithTimer(60);
				}
			});
		}
		else if (action === 'SignB2eDocument:TouchSigner' && documentId > 0)
		{
			this.#touchSigner(actionData);
		}
		else if (action === 'SignB2eDocument:Download' && documentHash)
		{
			this.#download(actionData, animationCallbacks);
		}
		else if (action === 'SignB2eDocumentEntry:Delete' && actionData?.entryId)
		{
			MessageBox.show({
				message: actionData?.confirmationText || '',
				modal: true,
				buttons: MessageBoxButtons.YES_NO,
				onYes: () => {
					return this.#deleteEntry(actionData.entryId);
				},
				onNo: (messageBox) => {
					messageBox.close();
				},
			});
		}
	}

	#deleteEntry(entryId): Promise
	{
		console.log(`delete entry${entryId}`);
	}

	#showSigningProcess(processUri): Promise
	{
		return Router.openSlider(processUri);
	}

	#modifyDocument({ documentId }): Promise
	{
		return Router.openSlider(`/sign/b2e/doc/0/?docId=${documentId}&stepId=changePartner&noRedirect=Y`);
	}

	#resendDocument({ documentId, recipientHash }, animationCallbacks): Promise
	{
		if (animationCallbacks.onStart)
		{
			animationCallbacks.onStart();
		}

		return new Promise((resolve, reject) => {
			ajax.runAction(
				'sign.internal.document.resendFile',
				{
					data: {
						memberHash: recipientHash,
						documentId,
					},
				},
			).then(() => {
				UI.Notification.Center.notify({
					content: Loc.getMessage('CRM_TIMELINE_ITEM_SIGN_DOCUMENT_RESEND_SUCCESS'),
					autoHideDelay: 5000,
				});
				if (animationCallbacks.onStop)
				{
					animationCallbacks.onStop();
				}
				resolve();
			}, (response) => {
				UI.Notification.Center.notify({
					content: response.errors[0].message,
					autoHideDelay: 5000,
				});
				if (animationCallbacks.onStop)
				{
					animationCallbacks.onStop();
				}
				reject();
			});

			console.log(`resend document ${documentId} for ${recipientHash}`);
		});
	}

	#touchSigner({ documentId }): void
	{
		console.log(`touch signer document ${documentId}`);
	}

	#download({ filename, downloadLink }, animationCallbacks): void
	{
		if (animationCallbacks.onStart)
		{
			animationCallbacks.onStart();
		}

		const link = document.createElement('a');
		link.href = downloadLink;
		link.setAttribute('download', filename || '');

		Dom.document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);

		if (animationCallbacks.onStop)
		{
			animationCallbacks.onStop();
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'SignB2eDocument'
			|| item.getType() === 'Activity:SignB2eDocument'
		);
	}
}
