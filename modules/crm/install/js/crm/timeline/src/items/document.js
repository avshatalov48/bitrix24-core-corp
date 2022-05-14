import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Items */
export default class Document extends HistoryActivity
{
	constructor()
	{
		super();
	}

	getTitle()
	{
		const typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);
		if(typeCategoryId === 3)
		{
			return BX.Loc.getMessage('CRM_TIMELINE_DOCUMENT_VIEWED');
		}

		return this.getMessage("document");
	}

	prepareTitleLayout()
	{
		return BX.create("SPAN", {
			attrs:{ className: "crm-entity-stream-content-event-title"},
			children: [
				BX.create("A", {
					attrs: { href: "#" },
					events: { "click": BX.delegate(this.editDocument, this) },
					text: this.getTitle()
				})
			]
		});
	}

	prepareTitleStatusLayout()
	{
		const typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);
		if(typeCategoryId === 3)
		{
			return BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-done" },
					text: BX.Loc.getMessage('CRM_TIMELINE_DOCUMENT_VIEWED_STATUS')
				}
			);
		}
		if(typeCategoryId === 2)
		{
			return BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-sent" },
					text: BX.Loc.getMessage('CRM_TIMELINE_DOCUMENT_CREATED_STATUS')
				}
			);
		}

		return null;
	}

	prepareTimeLayout()
	{
		return BX.create("SPAN",
			{
				attrs: { className: "crm-entity-stream-content-event-time" },
				text: this.formatTime(this.getCreatedTime())
			}
		);
	}

	isContextMenuEnabled()
	{
		const typeCategoryId = BX.prop.getInteger(this._data, "TYPE_CATEGORY_ID", 0);

		return typeCategoryId !== 3;
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());
		const statusLayout = this.prepareTitleStatusLayout();
		if(statusLayout)
		{
			header.appendChild(statusLayout);
		}
		header.appendChild(this.prepareTimeLayout());

		return header;
	}

	prepareContent()
	{
		const text = this.getTextDataParam("COMMENT", "");

		const wrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-document"}
			}
		);

		if(this.isFixed())
		{
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');
		}

		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-document" }
				}
			)
		);

		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}

		if(!this.isReadOnly())
		{
			wrapper.appendChild(this.prepareFixedSwitcherLayout());
		}

		const contentWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [contentWrapper]
				}
			)
		);

		const header = this.prepareHeaderLayout();
		contentWrapper.appendChild(header);

		const detailWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail"},
				html: text
			}
		);
		const title = BX.findChildByClassName(detailWrapper, 'document-title-link');
		if(title)
		{
			BX.bind(title, 'click', BX.proxy(this.editDocument, this));
		}
		contentWrapper.appendChild(detailWrapper);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		contentWrapper.appendChild(this._actionContainer);
		//endregion

		return wrapper;
	}

	prepareActions()
	{
	}

	showActions(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	}

	prepareContextMenuItems()
	{
		const menuItems = [];

		if(!this.isReadOnly())
		{
			menuItems.push({ id: "edit", text: this.getMessage("menuEdit"), onclick: BX.delegate(this.editDocument, this)});
			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.confirmDelete, this)});

			if (this.isFixed() || this._fixedHistory.findItemById(this._id))
			{
				menuItems.push({ id: "unfasten", text: this.getMessage("menuUnfasten"), onclick: BX.delegate(this.unfasten, this)});
			}
			else
			{
				menuItems.push({ id: "fasten", text: this.getMessage("menuFasten"), onclick: BX.delegate(this.fasten, this)});
			}
		}

		return menuItems;
	}

	confirmDelete()
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
					content: this.getMessage('documentRemove')
				}
			);
		}

		dlg.open().then(BX.delegate(this.onConfirmDelete, this), BX.DoNothing);
	}

	onConfirmDelete(result)
	{
		if(BX.prop.getBoolean(result, "cancel", true))
		{
			return;
		}

		this.deleteDocument();
	}

	deleteDocument()
	{
		if(this._isRequestRunning)
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
						"ACTION": "DELETE_DOCUMENT",
						"OWNER_TYPE_ID":  this.getOwnerTypeId(),
						"OWNER_ID": this.getOwnerId(),
						"ID": this.getId()
					},
				onsuccess: BX.delegate(function(result)
				{
					this._isRequestRunning = false;
					if(BX.type.isNotEmptyString(result.ERROR))
					{
						alert(result.ERROR);
					}
					else
					{
						const deleteItem = this._history.findItemById(this._id);
						if (deleteItem instanceof Document)
						{
							deleteItem.clearAnimate();
						}

						const deleteFixedItem = this._fixedHistory.findItemById(this._id);
						if (deleteFixedItem instanceof Document)
						{
							deleteFixedItem.clearAnimate();
						}
					}
				}, this),
				onfailure: BX.delegate(function()
				{
					this._isRequestRunning = false;
				}, this)
			}
		);
	}

	editDocument()
	{
		const documentId = this.getData().DOCUMENT_ID || 0;
		if(documentId > 0)
		{
			let url = '/bitrix/components/bitrix/crm.document.view/slider.php';
			url = BX.util.add_url_param(url, {documentId: documentId});
			if(BX.SidePanel)
			{
				BX.SidePanel.Instance.open(url, {width: 980});
			}
			else
			{
				top.location.href = url;
			}
		}
	}

	updateWrapper()
	{
		const wrapper = this.getWrapper();
		if(wrapper)
		{
			const detailWrapper = BX.findChildByClassName(wrapper, 'crm-entity-stream-content-detail');
			if(detailWrapper)
			{
				BX.adjust(detailWrapper, {html: this.getTextDataParam("COMMENT", "")});
				const title = BX.findChildByClassName(detailWrapper, 'document-title-link');
				if(title)
				{
					BX.bind(title, 'click', BX.proxy(this.editDocument, this));
				}
			}
		}
	}

	static create(id, settings)
	{
		const self = new Document();
		self.initialize(id, settings);
		return self;
	}
}
