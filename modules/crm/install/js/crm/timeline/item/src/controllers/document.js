import { ajax as Ajax, Text, Type, Loc } from 'main.core';
import {DateTimeFormat} from "main.date";
import { Base } from './base';
import ConfigurableItem from '../configurable-item';
import { Router } from "crm.router";
import { MessageBox } from "ui.dialogs.messagebox";
import { DatetimeConverter } from "crm.timeline.tools";
import {UI} from 'ui.notification';

const ITEM_TYPE = 'Activity:Document';
const ACTION_NAMESPACE = ITEM_TYPE + ':';

export class Document extends Base
{
	onItemAction(item: ConfigurableItem, action: String, actionData: ?Object): void
	{
		const documentId = Text.toInteger(actionData?.documentId);
		if (documentId <= 0)
		{
			return;
		}

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
			this.#printDocument(actionData?.printUrl);
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
		else
		{
			console.info(`Unknown action ${action} in ${ITEM_TYPE}`);
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

	#printDocument(printUrl: ?string): void
	{
		if (Type.isStringFilled(printUrl))
		{
			window.open(printUrl, '_blank');
		}
		else
		{
			MessageBox.alert(Loc.getMessage('CRM_TIMELINE_ITEM_ACTIVITY_DOCUMENT_PRINT_NOT_READY'));
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

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return item.getType() === ITEM_TYPE;
	}
}
