import History from "./history";
import {EditorMode} from "../types";

/** @memberof BX.Crm.Timeline.Items */
export default class Comment extends History
{
	constructor()
	{
		super();
		this._isCollapsed = false;
		this._isMenuShown = false;
		this._isFixed = false;
		this._hasFiles = false;
		this._postForm = null;
		this._editor = null;
		this._commentMessage = '';
		this._mode = EditorMode.view;
		this._streamContentEventBlock = '';
		this._playerWrappers = {};
		BX.Event.EventEmitter.subscribe(
			"BX.Disk.Files:onShowFiles",
			BX.delegate(this.addPlayer, this)
		);
	}

	doInitialize()
	{
		super.doInitialize();
		this._hasFiles = (this.getTextDataParam("HAS_FILES") === 'Y');
	}

	getTitle()
	{
		return this.getMessage("comment");
	}

	onPlayerDummyClick(file)
	{
		const playerWrapper = this._playerWrappers[file.id];
		const stubNode = playerWrapper.querySelector(".crm-audio-cap-wrap");
		if (stubNode)
		{
			BX.addClass(stubNode, "crm-audio-cap-wrap-loader");
		}
		this._history.getManager().getAudioPlaybackRateSelector().addPlayer(
			this._history.getManager().loadMediaPlayer(
				"history_" + this.getId() + '_' + file.id,
				file.url,
				'audio/mp3',
				playerWrapper,
				null,
				{
					playbackRate: this._history.getManager().getAudioPlaybackRateSelector().getRate()
				}
			)
		);
	}

	addPlayer(event)
	{
		if (event.data.entityValueId === parseInt(this.getId(), 10))
		{
			this.files = event.data.files;
			event.data.files.forEach(function(file){
				if (file.extension === 'mp3')
				{
					if (this._playerWrappers[file.id])
					{
						return;
					}
					const callInfoWrapper = BX.create("DIV",
						{
							attrs: {
								className: "crm-entity-stream-content-detail-call crm-entity-stream-content-detail-call-inline"
							}
						}
					);
					this._streamContentEventBlock.appendChild(callInfoWrapper);
					this._playerWrappers[file.id] = this._history.getManager().renderAudioDummy(
						null,
						this.onPlayerDummyClick.bind(this, file)
					);

					this._playerWrappers[file.id].firstElementChild.classList.add("crm-audio-cap-wrap-without-duration-text");

					callInfoWrapper.appendChild(
						this._playerWrappers[file.id]
					);
					callInfoWrapper.appendChild(
						this._history.getManager().getAudioPlaybackRateSelector().render()
					);
				}
			}.bind(this));
		}
	}

	prepareContent()
	{
		const wrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-comment"}
			}
		);

		if (this.isReadOnly())
		{
			BX.addClass(wrapper, "crm-entity-stream-section-comment-read-only");
		}

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-comment" } }
			)
		);

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		this._streamContentEventBlock = BX.create("DIV", { attrs: { className: "crm-entity-stream-content-event" } });
		const header = this.prepareHeaderLayout();

		this._streamContentEventBlock.appendChild(header);

		if (!this.isReadOnly())
			wrapper.appendChild(this.prepareFixedSwitcherLayout());

		const detailChildren = [];
		if (this._mode !== EditorMode.edit)
		{
			this._commentWrapper = BX.create("DIV", {
					attrs: { className: "crm-entity-stream-content-detail-description" }
				}
			);
			BX.html(this._commentWrapper, this.getTextDataParam("COMMENT", ""));
			detailChildren.push(this._commentWrapper);

			if (!this.isReadOnly())
			{
				BX.bind(this._commentWrapper, "click", BX.delegate(this.switchToEditMode, this));
				BX.bind(header, "click", BX.delegate(this.switchToEditMode, this));
			}
		}
		else
		{
			if (!BX.type.isDomNode(this._editorContainer))
				this._editorContainer = BX.create("div", {attrs: {className: "crm-entity-stream-section-comment-editor"}});

			detailChildren.push(this._editorContainer);

			const buttons = BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-detail-comment-edit-btn-container"},
					children:
						[
							BX.create("button",
								{
									attrs: {className: "ui-btn ui-btn-xs ui-btn-primary"},
									html: this.getMessage("send"),
									events: {
										click: BX.delegate(this.save, this)
									}
								}
							),
							BX.create("a",
								{
									attrs: {className: "ui-btn ui-btn-xs ui-btn-link"},
									html: this.getMessage("cancel"),
									events: {
										click: BX.delegate(this.switchToViewMode, this)
									}
								}
							)
						]
				}
			);

			detailChildren.push(buttons);
		}

		this._streamContentEventBlock.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: detailChildren
				}
			)
		);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			this._streamContentEventBlock.appendChild(authorNode);
		}
		//endregion
		const cleanText = this.getTextDataParam("TEXT", "");
		const _hasInlineAttachment = (this.getTextDataParam("HAS_INLINE_ATTACHMENT", "") === 'Y');
		if ((cleanText.length <= 128 && !_hasInlineAttachment) || this._mode === EditorMode.edit)
		{
			this._isCollapsed = false;

			wrapper.appendChild(
				BX.create("DIV", {
					attrs: {
						className: "crm-entity-stream-section-content"
					},
					children: [
						this._streamContentEventBlock
					]
				})
			);
		}
		else
		{
			this._isCollapsed = true;

			wrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-section-content crm-entity-stream-section-content-collapsed" },
						children:
							[
								this._streamContentEventBlock
							]
					}
				)
			);

			wrapper.querySelector(".crm-entity-stream-content-event").appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-section-content-expand-btn-container" },
						children:
							[
								BX.create("A",
									{
										attrs:
											{
												className: "crm-entity-stream-section-content-expand-btn",
												href: "#"
											},
										events:
											{
												click: BX.delegate(this.onExpandButtonClick, this)
											},
										text: this.getMessage("expand")
									}
								)
							]
					}
				)
			);
		}

		if (this._mode === EditorMode.view && this._hasFiles)
		{
			this._textLoaded = false;
			this._fileBlock = BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-files-inner" },
					children: [BX.create("DIV", { attrs: { className: "crm-timeline-wait" }})]
				});
			wrapper.querySelector(".crm-entity-stream-section-content").appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-section-files" },
						children: [this._fileBlock]
					})
			);
			BX.ready(BX.delegate(function() {
				window.setTimeout(BX.delegate(function(){
					this.loadContent(this._fileBlock, "GET_FILE_BLOCK")
				} ,this), 100);
			},this));
		}

		return wrapper;
	}

	prepareActions()
	{
		if (this._mode === EditorMode.view && BX.type.isDomNode(this._commentWrapper))
		{
			this.registerImages(this._commentWrapper);
			if (!BX.getClass('BX.Disk.apiVersion'))
			{
				BX.viewElementBind(
					this._commentWrapper,
					{showTitle: true},
					function(node){
						return BX.type.isElementNode(node) && (node.getAttribute('data-bx-viewer') || node.getAttribute('data-bx-image'));
					}
				);
			}
		}
	}

	loadContent(node, type)
	{
		if (!BX.type.isDomNode(node))
			return;

		BX.ajax(
			{
				url: this._history._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "GET_COMMENT_CONTENT",
						"ID": this.getId(),
						"ENTITY_TYPE_ID": this.getOwnerTypeId(),
						"ENTITY_ID": this.getOwnerId(),
						"TYPE": type
					},
				onsuccess: BX.delegate(function(result)
				{
					if (BX.type.isNotEmptyString(result.ERROR) && type === 'GET_FILE_BLOCK')
					{
						BX.remove(node);
						return;
					}

					if (BX.type.isNotEmptyString(result.BLOCK))
					{
						const promise = BX.html(node, result.BLOCK);
						promise.then(
							BX.delegate(function(){
								this.registerImages(node);
								BX.LazyLoad.showImages();
							}, this)
						);
					}
				}, this)
			}
		);
	}

	loadEditor()
	{
		this._editorName = 'CrmTimeLineComment'+this._id + BX.util.getRandomString(4);
		if (this._postForm)
		{
			this._postForm.oEditor.SetContent(this._commentMessage);
			this._editor.ReInitIframe();
			return;
		}

		const actionData = {
			data: {
				id: this._id,
				name: this._editorName
			}
		};
		BX.ajax.runAction("crm.api.timeline.loadEditor", actionData)
			.then(this.onLoadEditorSuccess.bind(this))
			.catch(	this.switchToViewMode.bind(this));
	}

	onLoadEditorSuccess(result)
	{
		if (!BX.type.isDomNode(this._editorContainer))
			this._editorContainer = BX.create("div", {attrs: {className: "crm-entity-stream-section-comment-editor"}});

		const html = BX.prop.getString(BX.prop.getObject(result, "data", {}), "html", '');
		BX.html(this._editorContainer, html).then(BX.delegate(this.showEditor,this));
	}

	showEditor()
	{
		if (LHEPostForm)
		{
			window.setTimeout(BX.delegate(function(){
				this._postForm = LHEPostForm.getHandler(this._editorName);
				this._editor = BXHtmlEditor.Get(this._editorName);
				BX.onCustomEvent(this._postForm.eventNode, 'OnShowLHE', [true]);
				this._commentMessage = this._postForm.oEditor.GetContent();
			} ,this), 0);
		}
	}

	registerImages(node)
	{
		const commentImages = node.querySelectorAll('[data-bx-viewer="image"]');
		const commentImagesLength = commentImages.length;
		const idsList = [];
		if (commentImagesLength > 0)
		{
			for (let i = 0; i < commentImagesLength; ++i)
			{
				if (BX.type.isDomNode(commentImages[i]))
				{
					commentImages[i].id += BX.util.getRandomString(4);
					idsList.push(commentImages[i].id);
				}
			}

			if (idsList.length > 0)
			{
				BX.LazyLoad.registerImages(idsList);
			}
		}
		BX.LazyLoad.registerImages(idsList);
	}

	toggleMode(type)
	{
		this._mode = parseInt(type);
		this._hasFiles = (this.getTextDataParam("HAS_FILES") === 'Y');
		this.refreshLayout();
		this.closeContextMenu();
	}

	switchToViewMode(e)
	{
		// if (LHEPostForm)
		// 	LHEPostForm.unsetHandler(this._editorName);
		this.toggleMode(EditorMode.view);
	}

	switchToEditMode(e)
	{
		const tagName = e.target.tagName.toLowerCase();
		if (tagName === 'a'
			|| tagName === 'img'
			|| BX.hasClass(e.target, "feed-con-file-changes-link-more")
			|| BX.hasClass(e.target, "feed-com-file-inline")
			|| BX.type.isNotEmptyString(document.getSelection().toString())
		)
		{
			return;
		}

		this.toggleMode(EditorMode.edit);
		window.setTimeout(BX.delegate(function(){
			this.loadEditor();
		} ,this), 100);
	}

	prepareContextMenuItems()
	{
		if(this._isMenuShown)
		{
			return;
		}

		const menuItems = [];

		if (!this.isReadOnly())
		{
			if (this._mode !== EditorMode.edit)
			{
				menuItems.push({ id: "edit", text: this.getMessage("menuEdit"), onclick: BX.delegate(this.switchToEditMode, this)});
			}
			else
			{
				menuItems.push({ id: "cancel", text: this.getMessage("menuCancel"), onclick: BX.delegate(this.switchToViewMode, this)});
			}

			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.processRemoval, this)});

			if (this.isFixed() || this._fixedHistory.findItemById(this._id))
				menuItems.push({ id: "unfasten", text: this.getMessage("menuUnfasten"), onclick: BX.delegate(this.unfasten, this)});
			else
				menuItems.push({ id: "fasten", text: this.getMessage("menuFasten"), onclick: BX.delegate(this.fasten, this)});
		}

		return menuItems;
	}

	save(e)
	{
		const attachmentList = [];
		let text = "";
		if (this._postForm)
		{
			text = this._postForm.oEditor.GetContent();
			this._commentMessage = text;
			this._postForm.eventNode
				.querySelectorAll('input[name="UF_CRM_COMMENT_FILES[]"]')
				.forEach(function(input) {
					attachmentList.push(input.value)
				});
		}

		if (!BX.type.isNotEmptyString(text))
		{
			if (!this.emptyCommentMessage)
			{
				this.emptyCommentMessage = new BX.PopupWindow(
					'timeline_empty_comment_' + this._id,
					e.target,
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

		if(this._isRequestRunning && BX.type.isNotEmptyString(text))
		{
			return;
		}

		this._isRequestRunning = true;
		BX.ajax(
			{
				url: this._history._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "UPDATE_COMMENT",
						"ID": this.getId(),
						"TEXT": text,
						"OWNER_TYPE_ID":  this.getOwnerTypeId(),
						"OWNER_ID": this.getOwnerId(),
						"ATTACHMENTS": attachmentList
					},
				onsuccess: BX.delegate(this.onSaveSuccess, this),
				onfailure: BX.delegate(this.onRequestFailure, this)
			}
		);
	}

	processRemoval()
	{
		this.closeContextMenu();
		this._detetionConfirmDlgId = "entity_timeline_deletion_" + this.getId() + "_confirm";
		let dlg = BX.Crm.ConfirmationDialog.get(this._detetionConfirmDlgId);
		if(!dlg)
		{
			dlg = BX.Crm.ConfirmationDialog.create(
				this._detetionConfirmDlgId,
				{
					title: this.getMessage("removeConfirmTitle"),
					content: this.getMessage('commentRemove')
				}
			);
		}

		dlg.open().then(BX.delegate(this.onRemovalConfirm, this), BX.delegate(this.onRemovalCancel, this));
	}

	onRemovalConfirm(result)
	{
		if(BX.prop.getBoolean(result, "cancel", true))
		{
			return;
		}

		this.remove();
	}

	onRemovalCancel()
	{
	}

	remove(e)
	{
		if(this._isRequestRunning)
		{
			return;
		}

		const history = this._history._manager.getHistory();
		const deleteItem = history.findItemById(this._id);
		if (deleteItem instanceof Comment)
			deleteItem.clearAnimate();

		const fixedHistory = this._history._manager.getFixedHistory();
		const deleteFixedItem = fixedHistory.findItemById(this._id);
		if (deleteFixedItem instanceof Comment)
			deleteFixedItem.clearAnimate();

		this._isRequestRunning = true;
		BX.ajax(
			{
				url: this._history._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
					{
						"ACTION": "DELETE_COMMENT",
						"OWNER_TYPE_ID":  this.getOwnerTypeId(),
						"OWNER_ID": this.getOwnerId(),
						"ID": this.getId()
					},
				onsuccess: BX.delegate(this.onRemoveSuccess, this),
				onfailure: BX.delegate(this.onRequestFailure, this)
			}
		);
	}

	refreshLayout()
	{
		this._playerWrappers = {};
		super.refreshLayout();
	}

	onSaveSuccess(data)
	{
		this._isRequestRunning = false;
		const itemData = BX.prop.getObject(data, "HISTORY_ITEM");

		const updateFixedItem = this._fixedHistory.findItemById(this._id);
		if (updateFixedItem instanceof Comment)
		{
			if (!BX.type.isNotEmptyString(itemData['IS_FIXED']))
				itemData['IS_FIXED'] = 'Y';

			updateFixedItem.setData(itemData);
			updateFixedItem._id = BX.prop.getString(itemData, "ID");
			updateFixedItem.switchToViewMode();
		}

		const updateItem = this._history.findItemById(this._id);
		if (updateItem instanceof Comment)
		{
			updateItem.setData(itemData);
			updateItem._id = BX.prop.getString(itemData, "ID");
			updateItem.switchToViewMode();
		}

		this._postForm = null;
	}

	onRemoveSuccess(data)
	{
	}

	onRequestFailure(data)
	{
		this._isRequestRunning = this._isLocked = false;
	}

	onExpandButtonClick(e)
	{
		if(!this._wrapper)
		{
			return BX.PreventDefault(e);
		}

		const contentWrapper = this._wrapper.querySelector("div.crm-entity-stream-section-content");
		if(!contentWrapper)
		{
			return BX.PreventDefault(e);
		}

		if (this._hasFiles && BX.type.isDomNode(this._commentWrapper) && !this._textLoaded)
		{
			this._textLoaded = true;
			this.loadContent(this._commentWrapper, "GET_TEXT")
		}
		const eventWrapper = contentWrapper.querySelector(".crm-entity-stream-content-event");
		if(this._isCollapsed)
		{
			eventWrapper.style.maxHeight = eventWrapper.scrollHeight + 130 + "px";
			BX.removeClass(contentWrapper, "crm-entity-stream-section-content-collapsed");
			BX.addClass(contentWrapper, "crm-entity-stream-section-content-expand");
			setTimeout(
				BX.delegate(function() {
					eventWrapper.style.maxHeight = "";
				}, this),
				300
			);
		}
		else
		{
			eventWrapper.style.maxHeight = eventWrapper.clientHeight + "px";
			BX.removeClass(contentWrapper, "crm-entity-stream-section-content-expand");
			BX.addClass(contentWrapper, "crm-entity-stream-section-content-collapsed");
			setTimeout(
				BX.delegate(function() {
					eventWrapper.style.maxHeight = "";
				}, this),
				0
			);
		}

		this._isCollapsed = !this._isCollapsed;

		const button = contentWrapper.querySelector("a.crm-entity-stream-section-content-expand-btn");
		if(button)
		{
			button.innerHTML = this.getMessage(this._isCollapsed ? "expand" : "collapse");
		}
		return BX.PreventDefault(e);
	}

	static create(id, settings)
	{
		const self = new Comment();
		self.initialize(id, settings);
		return self;
	}
}
