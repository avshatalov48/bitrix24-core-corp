import { Router } from 'crm.router';
import { Messenger } from 'im.public';
import { ajax, Dom, Loc, Text, Runtime } from 'main.core';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { UI } from 'ui.notification';
import ConfigurableItem from '../configurable-item';
import { Base } from './base';
import type { Api } from 'sign.v2.api';
import type { FeatureResolver } from 'sign.feature-resolver';

let featureResolver: FeatureResolver | null = null;
let api: Api | null = null;
Runtime.loadExtension(['sign.v2.api', 'sign.feature-resolver']).then(async (exports) => {
	if (exports?.Api && exports?.FeatureResolver)
	{
		featureResolver = exports?.FeatureResolver.instance();
		api = new exports.Api();
	}
}).catch((errors) => {
	UI.Notification.Center.notify({
		content: errors[0].message,
		autoHideDelay: 5000,
	});
});

export class SignB2eDocument extends Base
{
	#isCancellationInProgress: boolean = false;
	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'SignB2eDocument'
			|| item.getType() === 'Activity:SignB2eDocument'
		);
	}

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

		if (action === 'Activity:SignB2eDocument:ShowSigningCancel')
		{
			this.#cancelWithConfirm(actionData?.documentUid);
		}
		else if ((action === 'SignB2eDocument:ShowSigningProcess'
			|| action === 'Activity:SignB2eDocument:ShowSigningProcess') && processUri.length > 0)
		{
			this.#showSigningProcess(processUri);
		}
		else if ((action === 'SignB2eDocument:Preview' || action === 'Activity:SignB2eDocument:Preview') && documentId > 0)
		{
			this.#previewDocument(actionData);
		}
		else if ((action === 'SignB2eDocument:CreateDocumentChat' || action === 'Activity:SignB2eDocument:CreateDocumentChat') && documentId > 0)
		{
			if (featureResolver && featureResolver.released('createDocumentChat'))
			{
				this.#createDocumentChat(actionData);
			}
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

	#cancelWithConfirm(documentUid: string): void
	{
		if (this.#isCancellationInProgress)
		{
			return;
		}

		const signingCancelationDialog = new MessageBox({
			title: Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_DIALOG_TITLE'),
			message: Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_DIALOG_TEXT'),
			modal: true,
		});

		signingCancelationDialog.setButtons([new BX.UI.Button({
			text: Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_DIALOG_YES_BUTTON_TEXT'),
			color: BX.UI.Button.Color.DANGER,
			onclick: () => {
				this.#isCancellationInProgress = true;
				signingCancelationDialog.close();
				this.#cancelSigningProcess(documentUid).finally(() => {
					this.#isCancellationInProgress = false;
				});
			},
		}), new BX.UI.Button({
			text: Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_DIALOG_NO_BUTTON_TEXT'),
			color: BX.UI.Button.Color.LIGHT_BORDER,
			onclick: () => {
				signingCancelationDialog.close();
			},
		})]);

		signingCancelationDialog.show();
	}

	#cancelSigningProcess(documentUid): Promise
	{
		return new Promise((resolve, reject) => {
			ajax.runAction(
				'sign.api_v1.document.signing.stop',
				{
					data: {
						uid: documentUid,
					},
					preparePost: false,
					headers: [{
						name: 'Content-Type',
						value: 'application/json',
					}],
				},
			).then((response) => {
				UI.Notification.Center.notify({
					content: Loc.getMessage('CRM_TIMELINE_ITEM_SIGNING_CANCEL_SUCCESS'),
					autoHideDelay: 5000,
				});
				resolve(response);
			}, (response) => {
				response.errors.forEach((error) => {
					UI.Notification.Center.notify({
						content: error.message,
						autoHideDelay: 5000,
					});
				});
				reject(response.errors);
			}).catch(() => {
				reject();
			});
		});
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

	#previewDocument({ documentId }): Promise
	{
		return Router.openSlider(`/sign/b2e/preview/0/?docId=${documentId}&noRedirect=Y`);
	}

	async #createDocumentChat({ chatType, documentId }: { chatType: number, documentId: number }): void
	{
		if (api && featureResolver && featureResolver.released('createDocumentChat'))
		{
			const chatId = (await api.createDocumentChat(chatType, documentId, false)).chatId;
			Messenger.openChat(`chat${chatId}`);
		}
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
}
