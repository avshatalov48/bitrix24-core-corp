import Editor from "../editor";

/** @memberof BX.Crm.Timeline.Editors */
export default class Comment extends Editor
{
	constructor()
	{
		super();
		this._history = null;
		this._serviceUrl = "";
		this._postForm = null;
		this._editor = null;
		this._isRequestRunning = false;
		this._isLocked = false;
	}

	doInitialize()
	{
		this._serviceUrl = this.getSetting("serviceUrl", "");
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

	getHistory()
	{
		return this._history;
	}

	setHistory(history)
	{
		this._history = history;
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
				this._container.appendChild(this._editorContainer);
			}

			window.setTimeout(BX.delegate(function(){
				this.loadEditor();
			} ,this), 100);
		}

		BX.addClass(this._container, "focus");
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

		if(text === "")
		{
			if (!this.emptyCommentMessage)
			{
				this.emptyCommentMessage = new BX.PopupWindow(
					'timeline_empty_new_comment_' + this._ownerId,
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

		if(this._isRequestRunning || this._isLocked)
		{
			return;
		}

		this._isRequestRunning = this._isLocked = true;
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "SAVE_COMMENT",
						"TEXT": text,
						"OWNER_TYPE_ID": this._ownerTypeId,
						"OWNER_ID": this._ownerId,
						"ATTACHMENTS": attachmentList
					},
				onsuccess: BX.delegate(this.onSaveSuccess, this),
				onfailure: BX.delegate(this.onSaveFailure, this)
			}
		);
	}

	cancel()
	{
		this._input.value = "";
		this._input.style.minHeight = "";
		if (BX.type.isDomNode(this._editorContainer))
			this._postForm.eventNode.style.display = 'none';

		this._input.style.display = 'block';
		BX.removeClass(this._container, "focus");
		this.release();
	}

	onSaveSuccess(data)
	{
		this._isRequestRunning = false;
		if (this._postForm)
		{
			this._postForm.reinit('', {});
		}

		this.cancel();
		const itemData = BX.prop.getObject(data, "HISTORY_ITEM");
		const historyItem = this._history.createItem(itemData);
		this._history.addItem(historyItem, 0);

		const anchor = this._history.createAnchor();
		historyItem.layout({ anchor: anchor });

		const move = BX.CrmCommentAnimation.create(
			historyItem.getWrapper(),
			anchor,
			BX.pos(this._input),
			{
				start: BX.delegate(this.onAnimationStart, this),
				complete: BX.delegate(this.onAnimationComplete, this)
			}
		);
		move.run();
	}

	onSaveFailure()
	{
		this._isRequestRunning = this._isLocked = false;
	}

	onAnimationStart()
	{
		this._input.value = "";
	}

	onAnimationComplete()
	{
		this._isLocked = false;
		BX.removeClass(this._container, "focus");

		this._input.style.minHeight = "";
		this._manager.processEditingCompletion(this);

		this.release();

		this._history._anchor = null;
		this._history.refreshLayout();
	}

	static create(id, settings)
	{
		const self = new Comment();
		self.initialize(id, settings);
		Comment.items[self.getId()] = self;
		return self;
	}

	static items = {};
}
