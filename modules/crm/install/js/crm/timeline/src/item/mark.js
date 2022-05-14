import History from "./history";
import {Mark as MarkType} from "../types";

/** @memberof BX.Crm.Timeline.Items */
export default class Mark extends History
{
	constructor()
	{
		super();
	}

	doInitialize()
	{
		super.doInitialize();
		if(!(this._activityEditor instanceof BX.CrmActivityEditor))
		{
			throw "Mark. The field 'activityEditor' is not assigned.";
		}
	}

	getMessage(name)
	{
		const m = Mark.messages;
		if (m.hasOwnProperty(name))
		{
			return m[name];
		}

		return super.getMessage(name);
	}

	getTitle()
	{
		let title = "";
		const entityData = this.getAssociatedEntityData();
		const associatedEntityTypeId = this.getAssociatedEntityTypeId();
		const typeCategoryId = this.getTypeCategoryId();
		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			const entityTypeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
			const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
			const activityProviderId = BX.prop.getString(entityData, "PROVIDER_ID", '');

			if(entityTypeId === BX.CrmActivityType.email)
			{
				if(typeCategoryId === MarkType.success)
				{
					title = this.getMessage(
						(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail") +
						"SuccessMark"
					);
				}
				else if(typeCategoryId === MarkType.renew)
				{
					title = this.getMessage(
						(direction === BX.CrmActivityDirection.incoming ? "incomingEmail" : "outgoingEmail") +
						"RenewMark"
					);
				}
			}
			else if(entityTypeId === BX.CrmActivityType.call)
			{
				if(typeCategoryId === MarkType.success)
				{
					title = this.getMessage(
						(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall") +
						"SuccessMark"
					);
				}
				else if(typeCategoryId === MarkType.renew)
				{
					title = this.getMessage(
						(direction === BX.CrmActivityDirection.incoming ? "incomingCall" : "outgoingCall") +
						"RenewMark"
					);
				}
			}
			else if(entityTypeId === BX.CrmActivityType.meeting)
			{
				if(typeCategoryId === MarkType.success)
				{
					title = this.getMessage("meetingSuccessMark");
				}
				else if(typeCategoryId === MarkType.renew)
				{
					title = this.getMessage("meetingRenewMark");
				}
			}
			else if(entityTypeId === BX.CrmActivityType.task)
			{
				if(typeCategoryId === MarkType.success)
				{
					title = this.getMessage("taskSuccessMark");
				}
				else if(typeCategoryId === MarkType.renew)
				{
					title = this.getMessage("taskRenewMark");
				}
			}
			else if(entityTypeId === BX.CrmActivityType.provider)
			{
				if (activityProviderId === 'CRM_REQUEST')
				{
					if(typeCategoryId === MarkType.success)
					{
						title = this.getMessage("requestSuccessMark");
					}
					else if(typeCategoryId === MarkType.renew)
					{
						title = this.getMessage("requestRenewMark");
					}
				}
				else if(typeCategoryId === MarkType.success)
				{
					title = this.getMessage("webformSuccessMark");
				}
				else if(typeCategoryId === MarkType.renew)
				{
					title = this.getMessage("webformRenewMark");
				}
			}
		}
		else if(associatedEntityTypeId === BX.CrmEntityType.enumeration.deal)
		{
			if(typeCategoryId === MarkType.success)
			{
				title = this.getMessage("dealSuccessMark");
			}
			else if(typeCategoryId === MarkType.failed)
			{
				title = this.getMessage("dealFailedMark");
			}
		}
		else if(associatedEntityTypeId === BX.CrmEntityType.enumeration.order)
		{
			if(typeCategoryId === MarkType.success)
			{
				title = this.getMessage("orderSuccessMark");
			}
			else if(typeCategoryId === MarkType.failed)
			{
				title = this.getMessage("orderFailedMark");
			}
		}
		else
		{
			if (BX.CrmEntityType.isDefined(associatedEntityTypeId))
			{
				if (typeCategoryId === MarkType.success)
				{
					title = this.getMessage('entitySuccessMark');
				}
				else if (typeCategoryId === MarkType.failed)
				{
					title = this.getMessage('entityFailedMark');
				}
			}
		}

		return title;
	}

	prepareTitleLayout()
	{
		const associatedEntityTypeId = this.getAssociatedEntityTypeId();

		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.order)
		{
			return BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-title" },
					text: this.getTitle()
				}
			);
		}
		else
		{
			return BX.create("A",
				{
					attrs: { href: "#", className: "crm-entity-stream-content-event-title" },
					events: { "click": this._headerClickHandler },
					text: this.getTitle()
				}
			);
		}
	}

	prepareContent()
	{
		const entityData = this.getAssociatedEntityData();
		const associatedEntityTypeId = this.getAssociatedEntityTypeId();

		const wrapper = BX.create(
			"DIV",
			{attrs: {className: "crm-entity-stream-section crm-entity-stream-section-completed"}}
		);

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		const header = this.prepareHeaderLayout();
		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			const entityTypeId = BX.prop.getInteger(entityData, "TYPE_ID", 0);
			let iconClassName = "crm-entity-stream-section-icon";
			if(entityTypeId === BX.CrmActivityType.email)
			{
				iconClassName += " crm-entity-stream-section-icon-email";
			}
			else if(entityTypeId === BX.CrmActivityType.call)
			{
				iconClassName += " crm-entity-stream-section-icon-call";
			}
			else if(entityTypeId === BX.CrmActivityType.meeting)
			{
				iconClassName += " crm-entity-stream-section-icon-meeting";
			}
			else if(entityTypeId === BX.CrmActivityType.task)
			{
				iconClassName += " crm-entity-stream-section-icon-task";
			}
			else if(entityTypeId === BX.CrmActivityType.provider)
			{
				const providerId = BX.prop.getString(entityData, "PROVIDER_ID", "");
				if(providerId === "CRM_WEBFORM")
				{
					iconClassName += " crm-entity-stream-section-icon-crmForm";
				}
			}

			wrapper.appendChild(BX.create("DIV", { attrs: { className: iconClassName } }));
			content.appendChild(header);


			const detailWrapper = BX.create("DIV",
				{attrs: {className: "crm-entity-stream-content-detail"}}
			);
			content.appendChild(detailWrapper);

			detailWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail-title" },
						children:
							[
								BX.create("A",
									{
										attrs: { href: "#" },
										events: { "click": this._headerClickHandler },
										text: this.cutOffText(BX.prop.getString(entityData, "SUBJECT", ""), 128)
									}
								)
							]
					}
				)
			);

			const summary = this.getTextDataParam("SUMMARY");
			if(summary !== "")
			{
				detailWrapper.appendChild(
					BX.create("DIV",
						{
							attrs: { className: "crm-entity-stream-content-detail-description" },
							text: summary
						}
					)
				);
			}
		}
		else if(associatedEntityTypeId === BX.CrmEntityType.enumeration.order)
		{
			wrapper.appendChild(BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info" } }));
			content.appendChild(header);
			content.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-entity-stream-content-detail" },
						text: this.cutOffText(this.getTextDataParam("MESSAGE"), 128)
					}
				)
			);
		}
		else
		{
			wrapper.appendChild(BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-info" } }));
			content.appendChild(header);

			const innerWrapper = BX.create(
				"DIV",
				{
					attrs: {className: "crm-entity-stream-content-detail"}
				}
			);

			const associatedEntityTitle = this.cutOffText(BX.prop.getString(entityData, "TITLE", ""), 128);

			if (BX.CrmEntityType.isDefined(associatedEntityTypeId))
			{
				let link = BX.prop.getString(entityData, 'SHOW_URL', '');
				if (link.indexOf('/') !== 0)
				{
					link = '#';
				}

				const contentTemplate =
					this.getMessage('entityContentTemplate')
						.replace('#ENTITY_TYPE_CAPTION#', BX.Text.encode(BX.prop.getString(entityData, 'ENTITY_TYPE_CAPTION', '')))
						.replace('#LINK#', BX.Text.encode(link))
						.replace('#LINK_TITLE#', BX.Text.encode(associatedEntityTitle))
				;

				innerWrapper.appendChild(
					BX.create(
						'SPAN',
						{
							html: contentTemplate
						}
					)
				);
			}
			else
			{
				innerWrapper.innerText = associatedEntityTitle;
			}

			content.appendChild(innerWrapper);
		}

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);

		if (!this.isReadOnly())
			wrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	}

	prepareContextMenuItems()
	{
		const menuItems = [];

		if (!this.isReadOnly())
		{
			if (this.isFixed() || this._fixedHistory.findItemById(this._id))
				menuItems.push({ id: "unfasten", text: this.getMessage("menuUnfasten"), onclick: BX.delegate(this.unfasten, this)});
			else
				menuItems.push({ id: "fasten", text: this.getMessage("menuFasten"), onclick: BX.delegate(this.fasten, this)});
		}

		return menuItems;
	}

	view()
	{
		const entityData = this.getAssociatedEntityData();
		const associatedEntityTypeId = this.getAssociatedEntityTypeId();
		if(associatedEntityTypeId === BX.CrmEntityType.enumeration.activity)
		{
			const id = BX.prop.getInteger(entityData, "ID", 0);
			if(id > 0)
			{
				this._activityEditor.viewActivity(id);
			}
		}
		else
		{
			const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
			if(showUrl !== "")
			{
				BX.Crm.Page.open(showUrl);
			}
		}
	}

	static create(id, settings)
	{
		const self = new Mark();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
