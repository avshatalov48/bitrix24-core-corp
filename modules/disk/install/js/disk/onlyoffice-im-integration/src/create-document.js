import {CreateDocumentOptions, CreateIframeDocumentOptions, IframeDocumentOptions} from "./types";
import {ajax as Ajax, Extension, Reflection, Runtime} from "main.core";

export default class CreateDocument
{
	options: CreateDocumentOptions;

	constructor(options: CreateDocumentOptions)
	{
		this.options = options;

		this.bindEvents();
	}

	bindEvents()
	{
		window.addEventListener("message", (event: MessageEvent) => {
			if (event.data.type === 'selectedTemplate')
			{
				this.options.delegate.setMaxWidth(961);
				this.options.delegate.onDocumentCreated();
			}
		});
	}

	getIframeUrlForCreate(options: CreateIframeDocumentOptions): Promise
	{
		this.options.delegate.setMaxWidth(961);

		return new Promise((resolve, reject) => {
			const url = BX.util.add_url_param('/bitrix/services/main/ajax.php', {
				action: 'disk.api.integration.messengerCall.createDocumentInCall' ,
				typeFile: options.typeFile,
				callId: this.options.call.id,
				dialogId: this.options.dialog.id,
			});

			resolve(url);
		});

	}

	getIframeUrl(options: IframeDocumentOptions): Promise
	{
		this.options.delegate.setMaxWidth(961);

		return new Promise((resolve, reject) => {
			options.viewerItem.enableEditInsteadPreview();

			const queryParameters = options.viewerItem.getSliderQueryParameters();
			const url = BX.util.add_url_param('/bitrix/services/main/ajax.php', queryParameters);

			resolve(url);
		});

	}

	getIframeUrlForTemplates(): Promise
	{
		return new Promise((resolve, reject) => {
			Ajax.runAction('disk.api.integration.messengerCall.selectTemplateOrOpenExisting', {
				data: {
					callId: this.options.call.id,
					dialogId: this.options.dialog.id,
				}
			}).then((response) => {
				if (response.data.document && response.data.document.urlToEdit)
				{
					this.options.delegate.setMaxWidth(961);
					resolve(response.data.document.urlToEdit);
				}
				else if (response.data.template && response.data.template.urlToSelect)
				{
					this.options.delegate.setMaxWidth(328);
					resolve(response.data.template.urlToSelect);
				}
				else
				{
					reject();
				}
			});
		});
	}

	listResumesInChat(chatId: number): Promise
	{
		return new Promise((resolve, reject) => {
			Ajax.runAction('disk.api.integration.messengerCall.listResumesInChat', {
				data: {
					chatId: chatId,
				}
			}).then((response) => {
				if (response.data)
				{
					resolve(response.data.resumes);
				}
				else
				{
					reject();
				}
			}).catch(() => {
				reject();
			});
		});
	}

	onCloseIframe(iframe: HTMLIFrameElement): boolean
	{
		iframe.contentWindow.postMessage('closeIframe', '*');

		return true;
	}
};