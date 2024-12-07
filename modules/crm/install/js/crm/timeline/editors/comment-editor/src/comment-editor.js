import { ajax as Ajax, Dom, Loc, Runtime, Text, Type } from 'main.core';
import { Loader } from 'main.loader';
import { UI } from 'ui.notification';

export class CommentEditor
{
	#commentId: Number = null;
	#editorName: String = null;
	#editorContainer: HTMLElement = null;
	#editor: Object = null;
	#postForm: Object = null;
	#commentMessage: String = '';
	#loader: ?Loader;

	constructor(commentId: Number)
	{
		if (commentId <= 0)
		{
			throw new Error('Comment ID must be specified');
		}

		this.#commentId = Text.toInteger(commentId);
		this.#editorName = `CrmTimeLineComment${this.#commentId}${Text.getRandom(4)}`;
	}

	show(editorContainer: HTMLElement): void
	{
		this.#editorContainer = Type.isDomNode(editorContainer) ? editorContainer : null;
		if (!this.#editorContainer)
		{
			throw new Error('Editor container must be specified');
		}

		if (this.#postForm)
		{
			this.#postForm.oEditor.SetContent(this.#commentMessage);
			this.#editor.ReInitIframe();

			return;
		}

		this.#showLoader(true);

		Ajax.runAction('crm.api.timeline.loadEditor', {
			data: {
				id: this.#commentId,
				name: this.#editorName,
			},
		}).then((result) => {
			const assets = result.data.assets;
			const assetsToLoad = [
				...Object.prototype.hasOwnProperty.call(assets, 'css')
					? assets.css
					: [],
				...Object.prototype.hasOwnProperty.call(assets, 'js')
					? assets.js
					: [],
			];

			// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
			BX.load(assetsToLoad, () => {
				if (Object.prototype.hasOwnProperty.call(assets, 'string'))
				{
					void Promise
						.all(assets.string.map((stringValue) => Runtime.html(null, stringValue)))
						.then(() => {
							this.#onEditorHtmlLoad(result);
						})
					;
				}
				else
				{
					this.#onEditorHtmlLoad(result);
				}
			});
		}).catch((result) => this.#onRunRequestError(result));
	}

	getContent(): String
	{
		let content = '';

		if (this.#postForm)
		{
			content = this.#postForm.oEditor.GetContent().trim();

			this.#commentMessage = content;
		}

		if (!Type.isStringFilled(content))
		{
			UI.Notification.Center.notify({
				content: Loc.getMessage('CRM_TIMELINE_EMPTY_COMMENT_MESSAGE'),
			});
		}

		return content;
	}

	getHtmlContent(): String
	{
		let content = '';

		if (this.#postForm)
		{
			content = this.#postForm.oEditor.currentViewName === 'wysiwyg'
				? this.#postForm.oEditor.iframeView.GetValue()
				: this.#postForm.oEditor.content;
		}

		return content;
	}

	getAttachments(): Array
	{
		const attachmentList = [];

		if (this.#postForm)
		{
			this.#postForm.eventNode
				.querySelectorAll('input[name="UF_CRM_COMMENT_FILES[]"]')
				.forEach((input) => attachmentList.push(input.value))
			;
		}

		return attachmentList;
	}

	getAttachmentsAllowEditOptions(attachmentList: Array): Object
	{
		if (!Type.isArrayFilled(attachmentList))
		{
			return {};
		}

		const options = {};

		if (this.#postForm)
		{
			attachmentList.forEach((id: number | string) => {
				const selectorName = `input[name="CRM_TIMELINE_DISK_ATTACHED_OBJECT_ALLOW_EDIT[${id}]"`;
				const selector = this.#postForm.eventNode.querySelector(selectorName);
				if (selector)
				{
					options[id] = selector.value;
				}
			});
		}

		return options;
	}

	#onEditorHtmlLoad(result: Object): void
	{
		if (Type.isObject(result) && Type.isObject(result.data) && Type.isStringFilled(result.data.html))
		{
			this.#showLoader(false);

			void Runtime.html(this.#editorContainer, result.data.html).then(() => {
				// eslint-disable-next-line no-undef
				if (LHEPostForm)
				{
					setTimeout(this.#showEditor.bind(this), 0);
				}
			});
		}
		else
		{
			this.#onRunRequestError(result);
		}
	}

	#onRunRequestError(result: Object): void
	{
		this.#showLoader(false);

		if (Type.isObject(result) && Type.isArray(result.errors) && result.errors.length > 0)
		{
			UI.Notification.Center.notify({
				content: result.errors[0].message,
				autoHideDelay: 5000,
			});
		}

		if (result.status !== 'success')
		{
			throw new Error('Unable to load editor component');
		}
	}

	#showEditor(): void
	{
		// eslint-disable-next-line no-undef
		this.#postForm = LHEPostForm.getHandler(this.#editorName);
		// eslint-disable-next-line no-undef
		this.#editor = BXHtmlEditor.Get(this.#editorName);

		// eslint-disable-next-line @bitrix24/bitrix24-rules/no-bx
		BX.onCustomEvent(this.#postForm.eventNode, 'OnShowLHE', [true]);

		this.#commentMessage = this.#postForm.oEditor.GetContent();

		if (this.#editor.dom)
		{
			Dom.style(this.#editor.dom.textareaCont, {
				opacity: 1,
			});
			Dom.style(this.#editor.dom.iframeCont, {
				opacity: 1,
			});
		}

		setTimeout(() => {
			this.#editor.Focus(true);
		}, 100);
	}

	#showLoader(showLoader: boolean): void
	{
		if (showLoader)
		{
			if (!this.#loader && Loader)
			{
				this.#loader = new Loader({
					size: 45,
					offset: {
						top: '1%',
					},
				});
			}

			this.#loader.show(this.#editorContainer);
		}
		else if (!this.#loader && Loader)
		{
			this.#loader.hide();
		}
	}
}
