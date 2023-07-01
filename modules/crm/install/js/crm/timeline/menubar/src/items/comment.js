import WithEditor from "./witheditor";
import {Tag, Loc, ajax} from "main.core";

/** @memberof BX.Crm.Timeline.MenuBar */
export default class Comment extends WithEditor
{
	createLayout(): HTMLElement
	{
		this._saveButton = Tag.render`<button onclick="${this.onSaveButtonClick.bind(this)}" class="ui-btn ui-btn-xs ui-btn-primary" >${Loc.getMessage('CRM_TIMELINE_SEND')}</button>`;
		this._cancelButton = Tag.render`<span onclick="${this.onCancelButtonClick.bind(this)}"  class="ui-btn ui-btn-xs ui-btn-link">${Loc.getMessage('CRM_TIMELINE_CANCEL_BTN')}</span>`;
		this._input = Tag.render`<textarea  rows="1" class="crm-entity-stream-content-new-comment-textarea" placeholder="${Loc.getMessage('CRM_TIMELINE_COMMENT_PLACEHOLDER')}"></textarea>`;

		return Tag.render`<div class="crm-entity-stream-content-new-detail --hidden">
					${this._input}
					<div class="crm-entity-stream-content-new-comment-btn-container">
						${this._saveButton}
						${this._cancelButton}
					</div>
				</div>`;
	}

	doInitialize()
	{
		this._postForm = null;
		this._editor = null;
		this._isRequestRunning = false;
		this._isLocked = false;
		BX.unbind(this._input, "blur", this._blurHandler);
		BX.unbind(this._input, "keyup", this._keyupHandler);
	}

	loadEditor()
	{
		this._editorName = 'CrmTimeLineComment0';

		if (this._postForm)
			return;

		BX.ajax.runAction(
			"crm.api.timeline.loadEditor",
			{ data: { name: this._editorName } }
		).then(this.onLoadEditorSuccess.bind(this));
	}

	onLoadEditorSuccess(result)
	{
		const html = BX.prop.getString(BX.prop.getObject(result, "data", {}), "html", '');
		BX.html(this._editorContainer, html)
			.then(BX.delegate(this.showEditor,this))
			.then(BX.delegate(this.addEvents,this));
	}

	addEvents()
	{
		BX.addCustomEvent(
			this._editorContainer.firstElementChild,
			'onFileIsAppended',
			BX.delegate(function(id, item) {
				BX.addClass(this._saveButton, 'ui-btn-disabled');
				BX.addClass(this._saveButton, 'ui-btn-clock');
				this._saveButton.removeEventListener("click", this._saveButtonHandler);
			}, this)
		);

		BX.addCustomEvent(
			this._editorContainer.firstElementChild,
			'onFileIsAdded',
			BX.delegate(function(file, controller, obj, blob) {
				BX.removeClass(this._saveButton, 'ui-btn-clock');
				BX.removeClass(this._saveButton, 'ui-btn-disabled');
				this._saveButton.addEventListener("click", this._saveButtonHandler);
			}, this)
		);
	}

	showEditor()
	{
		if (LHEPostForm)
		{
			window.setTimeout(BX.delegate(function(){
				this._postForm = LHEPostForm.getHandler(this._editorName);
				this._editor = BXHtmlEditor.Get(this._editorName);
				BX.onCustomEvent(this._postForm.eventNode, 'OnShowLHE', [true]);
			} ,this), 100);
		}
	}

	onFocus(e)
	{
		this._input.style.display = 'none';
		if (this._editor && this._postForm)
		{
			this._postForm.eventNode.style.display = 'block';
			this._editor.Focus();
		}
		else
		{
			if (!BX.type.isDomNode(this._editorContainer))
			{
				this._editorContainer = BX.create("div", {attrs: {className: "crm-entity-stream-section-comment-editor"}});
				this._editorContainer.appendChild(
					BX.create("DIV",
						{
							attrs: { className: "crm-timeline-wait" }
						})
				);
				this.getContainer().appendChild(this._editorContainer);
			}

			window.setTimeout(BX.delegate(function(){
				this.loadEditor();
			} ,this), 100);
		}

		this.setFocused(true);
	}

	save()
	{
		let text = "";
		const attachmentList = [];

		if (this._postForm)
		{
			text = this._postForm.oEditor.GetContent();

			this._postForm.eventNode
				.querySelectorAll('input[name="UF_CRM_COMMENT_FILES[]"]')
				.forEach(function(input) {
					attachmentList.push(input.value)
				});
		}
		else
		{
			text = this._input.value;
		}

		if (text === "")
		{
			if (!this.emptyCommentMessage)
			{
				this.emptyCommentMessage = new BX.PopupWindow(
					'timeline_empty_new_comment_' + this.getEntityId(),
					this._saveButton,
					{
						content: BX.message('CRM_TIMELINE_EMPTY_COMMENT_MESSAGE'),
						darkMode: true,
						autoHide: true,
						zIndex: 990,
						angle: {position: 'top', offset: 77},
						closeByEsc: true,
						bindOptions: { forceBindPosition: true}
					}
				);
			}

			this.emptyCommentMessage.show();

			return;
		}

		if (this._isRequestRunning || this._isLocked)
		{
			return;
		}

		this._isRequestRunning = this._isLocked = true;

		return ajax.runAction(
			'crm.timeline.comment.add',
			{
				data: {
					fields: {
						ENTITY_ID: this.getEntityId(),
						ENTITY_TYPE_ID: this.getEntityTypeId(),
						COMMENT: text,
						ATTACHMENTS: attachmentList
					}
				}
			}
		).then((result) => {
			this.onSaveSuccess();

			return result;
		}).catch((result) => {
			this.onSaveFailure();

			return result;
		});
	}

	cancel()
	{
		this._input.value = "";
		this._input.style.minHeight = "";
		if (BX.type.isDomNode(this._editorContainer))
			this._postForm.eventNode.style.display = 'none';

		this._input.style.display = 'block';
		this.setFocused(false);
		this.release();
	}

	onSaveSuccess(data)
	{
		this._isRequestRunning = false;
		this._isLocked = false;
		this.release();

		if (this._postForm)
		{
			this._postForm.reinit('', {});
		}
		this.emitFinishEditEvent();
		this.cancel();
	}

	onSaveFailure()
	{
		this._isRequestRunning = this._isLocked = false;
	}
}
