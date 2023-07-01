import { ajax as Ajax, Runtime, Text, Type } from 'main.core';
import { Loader } from "main.loader";
import { UI } from "ui.notification";

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
		this.#editorName = 'CrmTimeLineComment' + this.#commentId + BX.util.getRandomString(4);
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
				name: this.#editorName
			}
		}).then(result => {
			const assets = result.data.assets;
			const assetsToLoad = [
				...assets.hasOwnProperty('css')
					? assets.css
					: [],
				...assets.hasOwnProperty('js')
					? assets.js
					: [],
			];

			BX.load(assetsToLoad, () => {
				if (assets.hasOwnProperty('string'))
				{
					Promise
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
		}).catch(result => {
			this.#onRunRequestError(result);
		});
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
				content: BX.message('CRM_TIMELINE_EMPTY_COMMENT_MESSAGE')
			});
		}

		return content;
	}

	getHtmlContent(): String
	{
		let content = '';

		if (this.#postForm)
		{
			content =this.#postForm.oEditor.currentViewName === 'wysiwyg'
				? this.#postForm.oEditor.iframeView.GetValue()
				: this.#postForm.oEditor.content;
		}

		return content;
	}

	getAttachments(): Array
	{
		let attachmentList = [];

		if (this.#postForm)
		{
			this.#postForm.eventNode
				.querySelectorAll('input[name="UF_CRM_COMMENT_FILES[]"]')
				.forEach(input => attachmentList.push(input.value))
			;
		}

		return attachmentList;
	}

	#onEditorHtmlLoad(result: Object): void
	{
		if (Type.isObject(result) && Type.isObject(result.data) && Type.isStringFilled(result.data.html))
		{
			this.#showLoader(false);

			Runtime.html(this.#editorContainer, result.data.html).then(() => {
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
		this.#postForm = LHEPostForm.getHandler(this.#editorName);
		this.#editor = BXHtmlEditor.Get(this.#editorName);

		BX.onCustomEvent(this.#postForm.eventNode, 'OnShowLHE', [true]);

		this.#commentMessage = this.#postForm.oEditor.GetContent();

		if (this.#editor.dom)
		{
			this.#editor.dom.textareaCont.style.opacity = 1;
			this.#editor.dom.iframeCont.style.opacity = 1;
		}

		setTimeout(() => { this.#editor.Focus(true); }, 100);
	}

	#showLoader(showLoader: boolean): void
	{
		if (showLoader)
		{
			if (!this.#loader && Loader)
			{
				this.#loader = new Loader({size: 45, offset: { top: '1%' }});
			}

			this.#loader.show(this.#editorContainer);
		}
		else
		{
			if (!this.#loader && Loader)
			{
				this.#loader.hide();
			}
		}
	}
}
