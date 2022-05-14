import HistoryActivity from "./history-activity";
import {HistoryEmail} from "../actions/email";

/** @memberof BX.Crm.Timeline.Items */
export default class Email extends HistoryActivity
{
	constructor()
	{
		super();
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());

		const entityData = this.getAssociatedEntityData();
		const emailInfo = BX.prop.getObject(entityData, "EMAIL_INFO", null);
		const statusText = emailInfo !== null ? BX.prop.getString(emailInfo, "STATUS_TEXT", "") : "";
		const error = emailInfo !== null ? BX.prop.getBoolean(emailInfo, "STATUS_ERROR", false) : false;
		const className = !error ? "crm-entity-stream-content-event-skipped" : "crm-entity-stream-content-event-missing";
		if(statusText !== "")
		{
			header.appendChild(
				BX.create(
					"SPAN",
					{
						props: { className: className},
						text: statusText
					}
				)
			);
		}

		const markNode = this.prepareMarkLayout();
		if(markNode)
		{
			header.appendChild(markNode);
		}

		header.appendChild(this.prepareTimeLayout());
		return header;
	}

	prepareContextMenuItems()
	{
		const menuItems = [];

		if (!this.isReadOnly())
		{
			menuItems.push({id: "view", text: this.getMessage("menuView"), onclick: BX.delegate(this.view, this)});

			menuItems.push({ id: "remove", text: this.getMessage("menuDelete"), onclick: BX.delegate(this.processRemoval, this)});

			if (this.isFixed() || this._fixedHistory.findItemById(this._id))
				menuItems.push({ id: "unfasten", text: this.getMessage("menuUnfasten"), onclick: BX.delegate(this.unfasten, this)});
			else
				menuItems.push({ id: "fasten", text: this.getMessage("menuFasten"), onclick: BX.delegate(this.fasten, this)});
		}

		return menuItems;
	}

	reply()
	{
	}

	replyAll()
	{
	}

	forward()
	{
	}

	getRemoveMessage()
	{
		const title = BX.util.htmlspecialchars(this.getTitle());
		return this.getMessage('emailRemove').replace("#TITLE#", title);
	}

	prepareContent()
	{
		const entityData = this.getAssociatedEntityData();

		let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

		const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
		const communicationTitle = BX.prop.getString(communication, "TITLE", "");
		const communicationShowUrl = BX.prop.getString(communication, "SHOW_URL", "");
		const communicationValue = BX.prop.getString(communication, "VALUE", "");

		const outerWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-email"}});
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-email" }
				}
			)
		);

		if (this.isFixed())
			BX.addClass(outerWrapper, 'crm-entity-stream-section-top-fixed');

		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		outerWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-content" },
					children: [ wrapper ]
				}
			)
		);

		//Header
		const header = this.prepareHeaderLayout();
		wrapper.appendChild(header);

		//region Context Menu
		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}
		//endregion

		//Details
		const detailWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail-email"}
			}
		);
		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: [ detailWrapper ]
				}
			)
		);

		//TODO: Add status text
		/*
		detailWrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-content-detail-email-read-status" } })
		);
		*/

		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-email-title" },
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { "click": this._headerClickHandler },
									text: this.getTitle()
								}
							)
						]
				}
			)
		);

		const communicationWrapper = BX.create("DIV",
			{attrs: {className: "crm-entity-stream-content-detail-email-to"}}
		);
		detailWrapper.appendChild(communicationWrapper);

		//Communications
		if(communicationTitle !== "")
		{
			if(communicationShowUrl !== "")
			{
				communicationWrapper.appendChild(
					BX.create("A",
						{
							attrs: { href: communicationShowUrl },
							text: communicationTitle
						}
					)
				);
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

		if(communicationValue !== "")
		{
			if(communicationTitle !== "")
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: " " }));
			}
			communicationWrapper.appendChild(
				BX.create(
					"SPAN",
					{
						attrs: { className: "crm-entity-stream-content-detail-email-address" },
						text: communicationValue
					}
				)
			);
		}

		//Content
		detailWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail-email-fragment" },
					children: this.prepareCutOffElements(description, 128, this._headerClickHandler)
				}
			)
		);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			wrapper.appendChild(authorNode);
		}
		//endregion

		//region  Actions
		this._actionContainer = BX.create("SPAN", { attrs: { className: "crm-entity-stream-content-detail-action" } });
		wrapper.appendChild(this._actionContainer);
		//endregion

		if (!this.isReadOnly())
			wrapper.appendChild(this.prepareFixedSwitcherLayout());

		return outerWrapper;
	}

	prepareActions()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			HistoryEmail.create(
				"email",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor
				}
			)
		);
	}

	showActions(show)
	{
		if(this._actionContainer)
		{
			this._actionContainer.style.display = show ? "" : "none";
		}
	}

	static create(id, settings)
	{
		const self = new Email();
		self.initialize(id, settings);
		return self;
	}
}
