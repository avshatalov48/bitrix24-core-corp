import {ajax as Ajax, Loc, Tag, Text, Type, Uri} from 'main.core';
import { DateTimeFormat } from "main.date";
import { type ActionAnimationCallbacks, type ActionParams, Base } from './base';
import ConfigurableItem from '../configurable-item';
import { ActionType } from "../action";
import { Router } from "crm.router";
import {MessageBox, MessageBoxButtons} from "ui.dialogs.messagebox";
import { DatetimeConverter } from "crm.timeline.tools";
import { UI } from 'ui.notification';

import 'ui.info-helper';

const ACTION_NAMESPACE = 'Document:';

export class Document extends Base
{
	static #toPrintAfterRefresh: ConfigurableItem[] = [];
	#popupConfirm: BX.PopupWindow;

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'Document'
			|| item.getType() === 'DocumentViewed'
			|| item.getType() === 'Activity:Document'
		);
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData, response, animationCallbacks} = actionParams;

		if (ActionType.isJsEvent(actionType))
		{
			this.#onJsEvent(action, actionData, animationCallbacks, item);
		}
		else if (ActionType.isAjaxAction(actionType))
		{
			this.#onAjaxAction(action, actionType, actionData, response);
		}
	}

	#onJsEvent(
		action: string,
		actionData: Object,
		animationCallbacks: ?ActionAnimationCallbacks,
		item: ConfigurableItem,
	): void
	{
		const documentId = Text.toInteger(actionData?.documentId);
		// if (documentId <= 0)
		// {
		// 	return;
		// }
		if (action === (ACTION_NAMESPACE + 'Open'))
		{
			this.#openDocument(documentId);
		}
		else if (action === (ACTION_NAMESPACE + 'CopyPublicLink'))
		{
			// todo block button while loading
			this.#copyPublicLink(documentId, actionData?.publicUrl);
		}
		else if (action === (ACTION_NAMESPACE + 'Print'))
		{
			this.#printDocument(actionData?.printUrl, animationCallbacks, item);
		}
		else if (action === (ACTION_NAMESPACE + 'DownloadPdf'))
		{
			this.#downloadPdf(actionData?.pdfUrl);
		}
		else if (action === (ACTION_NAMESPACE + 'DownloadDocx'))
		{
			this.#downloadDocx(actionData?.docxUrl);
		}
		else if (action === (ACTION_NAMESPACE + 'UpdateTitle'))
		{
			this.#updateTitle(documentId, actionData?.value);
		}
		else if (action === (ACTION_NAMESPACE + 'UpdateCreateDate'))
		{
			this.#updateCreateDate(documentId, actionData?.value);
		}
		else if (action === (ACTION_NAMESPACE + 'ConvertDeal'))
		{
			this.#convertDeal(documentId, animationCallbacks);
		}
		else if (action === (ACTION_NAMESPACE + 'ShowInfoHelperSlider'))
		{
			this.#showInfoHelperSlider(actionData?.infoHelperCode);
		}
		else if (action === (ACTION_NAMESPACE + 'Delete'))
		{
			const confirmationText = actionData.confirmationText ?? '';
			if (confirmationText)
			{
				MessageBox.show({
					message: confirmationText,
					modal: true,
					buttons: MessageBoxButtons.YES_NO,
					onYes: () => {
						return this.#deleteDocument(actionData.id, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
					},
					onNo: (messageBox) => {
						messageBox.close();
					},
				});
			}
			else
			{
				this.#deleteDocument(actionData.id, actionData.ownerTypeId, actionData.ownerId, animationCallbacks);
			}
		}
		else
		{
			console.info(`Unknown action ${action} in ${item.getType()}`);
		}
	}

	#openDocument(documentId: number): void
	{
		Router.Instance.openDocumentSlider(documentId);
	}

	async #copyPublicLink(documentId: number, publicUrl: ?string): Promise<void>
	{
		if (!Type.isStringFilled(publicUrl))
		{
			try
			{
				publicUrl = await this.#createPublicUrl(documentId);
			}
			catch (error)
			{
				MessageBox.alert(error.message);

				return;
			}
		}

		const isSuccess = BX.clipboard.copy(publicUrl);
		if (isSuccess)
		{
			UI.Notification.Center.notify({
				content: Loc.getMessage('CRM_TIMELINE_ITEM_LINK_IS_COPIED'),
				autoHideDelay: 5000,
			});
		}
		else
		{
			MessageBox.alert(Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_COPY_PUBLIC_LINK_ERROR'));
		}
	}

	async #createPublicUrl(documentId: number): Promise<string, Error>
	{
		let response: {data: {publicUrl: string}};
		try
		{
			response = await Ajax.runAction(
				'crm.documentgenerator.document.enablePublicUrl',
				{
					analyticsLabel: 'enablePublicUrl',
					data: {
						status: 1,
						id: documentId,
					}
				}
			);
		}
		catch (responseWithError)
		{
			console.error(responseWithError);

			throw new Error(Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_CREATE_PUBLIC_LINK_ERROR'));
		}

		const publicUrl = response.data.publicUrl;
		if (!Type.isStringFilled(publicUrl))
		{
			throw new Error(Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_CREATE_PUBLIC_LINK_ERROR'));
		}

		return publicUrl;
	}

	#printDocument(printUrl: ?string, animationCallbacks: ?ActionAnimationCallbacks, item: ConfigurableItem): void
	{
		if (Type.isStringFilled(printUrl))
		{
			window.open(printUrl, '_blank');
			return;
		}

		// there is no pdf yet. wait till document is transformed and update push comes in
		Document.#toPrintAfterRefresh.push(item);
		const onStart = animationCallbacks?.onStart;
		if (Type.isFunction(onStart))
		{
			onStart();
		}
	}

	#downloadPdf(pdfUrl: ?string): void
	{
		if (Type.isStringFilled(pdfUrl))
		{
			window.open(pdfUrl, '_blank');
		}
		else
		{
			MessageBox.alert(Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_PDF_NOT_READY'));
		}
	}

	#downloadDocx(docxUrl: ?string): void
	{
		if (Type.isStringFilled(docxUrl))
		{
			window.open(docxUrl, '_blank');
		}
		else
		{
			console.error('Docx download url is not found. This should be an impossible case, something went wrong');
		}
	}

	async #updateTitle(documentId: number, value: ?string): Promise<void>
	{
		let response: {data: {document?: {values?: {DocumentTitle?: string}}}};
		try
		{
			response = await Ajax.runAction('crm.documentgenerator.document.update', {
				data: {
					id: documentId,
					values: {
						DocumentTitle: value,
					},
				},
			});
		}
		catch (responseWithError)
		{
			console.error(responseWithError);

			MessageBox.alert(Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_UPDATE_DOCUMENT_ERROR'));

			return;
		}

		const newTitle = response.data.document?.values?.DocumentTitle;
		if (newTitle !== value)
		{
			console.error("Updated document title without errors, but for some reason title from the backend doesn't match sent value");
		}
	}

	async #updateCreateDate(documentId: number, value: ?Date): Promise<void>
	{
		const valueInSiteFormat = DateTimeFormat.format(DatetimeConverter.getSiteDateFormat(), value);

		let response: {data: {document?: {values?: {DocumentCreateTime?: string}}}};
		try
		{
			response = await Ajax.runAction('crm.documentgenerator.document.update', {
				data: {
					id: documentId,
					values: {
						DocumentCreateTime: valueInSiteFormat,
					},
				},
			});
		}
		catch (responseWithError)
		{
			console.error(responseWithError);

			MessageBox.alert(Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_UPDATE_DOCUMENT_ERROR'));

			return;
		}

		const newCreateDate = response.data.document?.values?.DocumentCreateTime;
		if (valueInSiteFormat !== newCreateDate)
		{
			console.error("Updated document create date without errors, but for some reason date from the backend doesn't match sent value");
		}
	}

	#deleteDocument(id: number, ownerTypeId: number, ownerId: number, animationCallbacks: Object): boolean
	{
		if (animationCallbacks.onStart)
		{
			animationCallbacks.onStart();
		}
		return Ajax.runAction(
			'crm.timeline.document.delete',
			{
				data: {
					id,
					ownerTypeId,
					ownerId,
				}
			}
		).then(() => {
			if (animationCallbacks.onStop)
			{
				animationCallbacks.onStop();
			}
			return true;
		}, (response) =>
		{
			UI.Notification.Center.notify({
				content: response.errors[0].message,
				autoHideDelay: 5000,
			});
			if (animationCallbacks.onStop)
			{
				animationCallbacks.onStop();
			}

			return true;
		});
	}

	#onAjaxAction(action: string, actionType: string, actionData: Object, response: Object): void
	{
		if (action === 'crm.api.integration.sign.convertDeal')
		{
			if (actionType === ActionType.AJAX_ACTION.FINISHED && !Type.isNil(response?.data?.SMART_DOCUMENT))
			{
				//todo extract it to router?
				const wizardUri = new Uri('/sign/doc/0/');
				wizardUri.setQueryParams({
					docId: response.data.SMART_DOCUMENT,
					stepId: 'changePartner',
					noRedirect: 'Y'
				});
				BX.SidePanel.Instance.open(wizardUri.toString());
			}
		}
	}

	onAfterItemRefreshLayout(item: ConfigurableItem)
	{
		const itemsToPrint = Document.#toPrintAfterRefresh.filter(candidate => candidate.getId() === item.getId());
		if (itemsToPrint.length <= 0)
		{
			return;
		}

		const action = item.getLayout().asPlainObject().footer?.additionalButtons?.extra?.action;
		const isPrintEvent = (
			Type.isPlainObject(action)
			&& ActionType.isJsEvent(action.type)
			&& action.value === (ACTION_NAMESPACE + 'Print')
		);
		if (!isPrintEvent)
		{
			return;
		}

		const printUrl = action.actionParams?.printUrl;
		if (!Type.isStringFilled(printUrl))
		{
			return;
		}

		this.#printDocument(printUrl, null, item);

		Document.#toPrintAfterRefresh =
			Document.#toPrintAfterRefresh.filter(remainingItem => !itemsToPrint.includes(remainingItem))
		;
	}

	#convertDeal(id: number, animationCallbacks: ?ActionAnimationCallbacks)
	{
		if (animationCallbacks.onStart)
		{
			animationCallbacks.onStart();
		}

		const convertDealAndStartSign = (
			(usePrevious) =>
			{
				Ajax.runAction('crm.api.integration.sign.convertDeal', {
					data: {
						documentId: id,
						usePrevious: !usePrevious ? 0 : 1,
					}
				}).then(
					(response) => {
						if (response?.data?.SMART_DOCUMENT)
						{
							const wizardUri = new Uri('/sign/doc/0/');
							wizardUri.setQueryParams({
								docId: response.data.SMART_DOCUMENT,
								stepId: 'changePartner',
								noRedirect: 'Y'
							});
							BX.SidePanel.Instance.open(wizardUri.toString());
						}

						if (animationCallbacks.onStop)
						{
							animationCallbacks.onStop();
						}
					},
					(response) => {
						if (response.errors[0].message)
						{
							UI.Notification.Center.notify({
								content: response.errors[0].message,
								autoHideDelay: 5000
							});
						}
						if (animationCallbacks.onStop)
						{
							animationCallbacks.onStop();
						}
					}
				).catch((response) =>
				{
					if (response.errors[0].message)
					{
						UI.Notification.Center.notify({
							content: response.errors[0].message,
							autoHideDelay: 5000,
						});
					}
					if (animationCallbacks.onStop)
					{
						animationCallbacks.onStop();
					}
				}
			);
		});

		Ajax.runAction('crm.api.integration.sign.getLinkedBlank', {
			data: {
				documentId: id
			}
		}).then(
			(response) =>
			{
				if (response?.data?.ID > 0) {
					this.#showMessage(
						Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DO_USE_PREVIOUS_MSGVER_3',
							{
								'%TITLE%': '<b>' + BX.util.htmlspecialchars(response.data.TITLE || '') + '</b>',
								'%INITIATOR%': '<b>' + BX.util.htmlspecialchars(response.data.INITIATOR || '') + '</b>',
							}),
						[
							new BX.UI.Button({
								text: BX.message('CRM_TIMELINE_ITEM_ACTIVITY_OLD_BUTTON_MSGVER_2'),
								className: "ui-btn ui-btn-md ui-btn-primary",
								events: {
									click: () =>
									{
										convertDealAndStartSign(true);
										this.#popupConfirm.destroy();
									}
								}
							}),
							new BX.UI.Button({
								text: Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_NEW_BUTTON_MSGVER_3'),
								className: "ui-btn ui-btn-md ui-btn-info",
								events: {
									click: () =>
									{
										convertDealAndStartSign(false);
										this.#popupConfirm.destroy();
									}
								}
							})
						],
						Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_POPUP_TITLE_MSGVER_2')
					);
				} else {
					convertDealAndStartSign(false);
				}
			});
	}

	#showInfoHelperSlider(code: string)
	{
		BX.UI.InfoHelper.show(code);
	}

	#showMessage(content: string, buttons: [], title: string)
	{
		this.#popupConfirm = new BX.PopupWindow(
			'bx-popup-document-activity-popup',
			null,
			{
						zIndex: 200,
						autoHide: true,
						closeByEsc: true,
						buttons: buttons,
						closeIcon: true,
						overlay : true,
						events : {
							onPopupClose : () =>
							{
								this.#popupConfirm.destroy();
							}
						},
						content: Tag.render`<div class="bx-popup-document-activity-popup-content-text">${content}</div>`,
						titleBar: title,
						className : 'bx-popup-document-activity-popup',
						maxWidth: 510
					}
		);
		this.#popupConfirm.show();
	};
}
