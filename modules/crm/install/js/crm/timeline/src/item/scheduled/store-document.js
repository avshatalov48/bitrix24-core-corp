import Activity from "./activity";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class StoreDocument extends Activity
{
	constructor()
	{
		super();
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-planned-store-document";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-store-document";
	}

	getTypeDescription()
	{
		return this.getMessage("storeDocument");
	}

	getPrepositionText(direction)
	{
		return this.getMessage("reciprocal");
	}

	getRemoveMessage()
	{
		const entityData = this.getAssociatedEntityData();
		let title = BX.prop.getString(entityData, "SUBJECT", "");
		title = BX.util.htmlspecialchars(title);
		return this.getMessage('taskRemove').replace("#TITLE#", title);
	}

	isEditable()
	{
		return false;
	}

	prepareContent(options)
	{
		const deadline = this.getDeadline();
		const timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");

		const entityData = this.getAssociatedEntityData();
		const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		const isDone = this.isDone();

		let wrapperClassName = this.getWrapperClassName();
		if(wrapperClassName !== "")
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned" + " " + wrapperClassName;
		}
		else
		{
			wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-planned";
		}

		const wrapper = BX.create("DIV", {attrs: {className: wrapperClassName}});

		let iconClassName = this.getIconClassName();
		if(this.isCounterEnabled())
		{
			iconClassName += " crm-entity-stream-section-counter";
		}
		wrapper.appendChild(BX.create("DIV", { attrs: { className: iconClassName } }));

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		const contentWrapper = BX.create("DIV",
			{attrs: {className: "crm-entity-stream-section-content"}}
		);
		wrapper.appendChild(contentWrapper);

		//region Details
		const contentInnerWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-event"}
			}
		);
		contentWrapper.appendChild(contentInnerWrapper);

		this._deadlineNode = BX.create("SPAN",
			{ attrs: { className: "crm-entity-stream-content-event-time" }, text: timeText }
		);

		const headerWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-header"}
			}
		);
		headerWrapper.appendChild(BX.create("SPAN",
			{
				attrs:
					{
						className: "crm-entity-stream-content-event-title"
					},
				text: this.getTypeDescription(direction)
			}
		));

		const statusNode = this.getStatusNode();
		if (statusNode)
		{
			headerWrapper.appendChild(statusNode);
		}
		headerWrapper.appendChild(this._deadlineNode);

		contentInnerWrapper.appendChild(headerWrapper);

		const detailWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail"}
			}
		);
		contentInnerWrapper.appendChild(detailWrapper);

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-detail-description"},
					children: [
						BX.create("SPAN", { text: this.getMessage("storeDocumentDescription") }),
						BX.create("A", {
							attrs: {
								className: "crm-entity-stream-content-detail-target",
								href: "#",
							},
							events: {
								click: BX.delegate(function (e) {
									top.BX.Helper.show('redirect=detail&code=14828480');
									e.preventDefault ? e.preventDefault() : (e.returnValue = false);
								})
							},
							text: " " + BX.message('CRM_TIMELINE_DETAILS'),
						})
					]
				}
			)
		);

		const additionalDetails = this.prepareDetailNodes();
		if(BX.type.isArray(additionalDetails))
		{
			let i = 0;
			const length = additionalDetails.length;
			for(; i < length; i++)
			{
				detailWrapper.appendChild(additionalDetails[i]);
			}
		}
		//endregion
		//region Set as Done Button
		const setAsDoneButton = BX.create("INPUT",
			{
				attrs:
					{
						type: "checkbox",
						className: "crm-entity-stream-planned-apply-btn",
						checked: isDone
					},
				events: {change: this._setAsDoneButtonHandler}
			}
		);

		if(!this.canComplete())
		{
			setAsDoneButton.disabled = true;
		}

		const buttonContainer = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail-planned-action"},
				children: [setAsDoneButton]
			}
		);
		contentInnerWrapper.appendChild(buttonContainer);
		//endregion

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentInnerWrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("DIV",
			{
				attrs: { className: "crm-entity-stream-content-detail-action" }
			}
		);
		contentInnerWrapper.appendChild(this._actionContainer);
		//endregion

		return wrapper;
	}

	static create(id, settings)
	{
		const self = new StoreDocument();
		self.initialize(id, settings);
		return self;
	}
}
