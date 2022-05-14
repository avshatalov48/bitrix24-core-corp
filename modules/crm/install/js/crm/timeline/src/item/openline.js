import HistoryActivity from "./history-activity";
import {OpenLine as OpenLineAction} from "../action/openline";

/** @memberof BX.Crm.Timeline.Items */
export default class OpenLine extends HistoryActivity
{
	constructor()
	{
		super();
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());
		header.appendChild(this.prepareTimeLayout());

		return header;
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

		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-IM"}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-IM" } })
		);

		if(this.isContextMenuEnabled())
		{
			wrapper.appendChild(this.prepareContextMenuButton());
		}

		if (this.isFixed())
			BX.addClass(wrapper, 'crm-entity-stream-section-top-fixed');

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

		const detailWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail"}});
		contentWrapper.appendChild(detailWrapper);

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
									text: this.getTitle()
								}
							)
						]
				}
			)
		);

		//Content
		const entityDetailWrapper = BX.create("DIV",
			{attrs: {className: "crm-entity-stream-content-detail-IM"}}
		);
		detailWrapper.appendChild(entityDetailWrapper);

		const messageWrapper = BX.create("DIV",
			{attrs: {className: "crm-entity-stream-content-detail-IM-messages"}}
		);
		entityDetailWrapper.appendChild(messageWrapper);

		const openLineData = BX.prop.getObject(this.getAssociatedEntityData(), "OPENLINE_INFO", null);
		if(openLineData)
		{
			const messages = BX.prop.getArray(openLineData, "MESSAGES", []);
			let i = 0;
			const length = messages.length;
			for(; i < length; i++)
			{
				const message = messages[i];
				const isExternal = BX.prop.getBoolean(message, "IS_EXTERNAL", true);

				messageWrapper.appendChild(
					BX.create("DIV",
						{
							attrs:
								{
									className: isExternal
										? "crm-entity-stream-content-detail-IM-message-incoming"
										: "crm-entity-stream-content-detail-IM-message-outgoing"
								},
							html: BX.prop.getString(message, "MESSAGE", "")
						}
					)
				);
			}
		}


		const communicationWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail-contact-info"}});
		detailWrapper.appendChild(communicationWrapper);

		if(communicationTitle !== '')
		{
			communicationWrapper.appendChild(
				BX.create("SPAN",
					{ text: this.getMessage("reciprocal") + ": " }
				)
			);

			if(communicationShowUrl !== '')
			{
				communicationWrapper.appendChild(BX.create("A", { attrs: { href: communicationShowUrl }, text: communicationTitle }));
			}
			else
			{
				communicationWrapper.appendChild(BX.create("SPAN", { text: communicationTitle }));
			}
		}

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

		if (!this.isReadOnly())
			contentWrapper.appendChild(this.prepareFixedSwitcherLayout());

		return wrapper;
	}

	prepareActions()
	{
		if(this.isReadOnly())
		{
			return;
		}

		this._actions.push(
			OpenLineAction.create(
				"openline",
				{
					item: this,
					container: this._actionContainer,
					entityData: this.getAssociatedEntityData(),
					activityEditor: this._activityEditor,
					ownerInfo: this._history.getOwnerInfo()
				}
			)
		);
	}

	view()
	{
		if(typeof(window.top['BXIM']) === 'undefined')
		{
			window.alert(this.getMessage("openLineNotSupported"));
			return;
		}

		let slug = "";
		const communication = BX.prop.getObject(this.getAssociatedEntityData(), "COMMUNICATION", null);
		if(communication)
		{
			if(BX.prop.getString(communication, "TYPE") === "IM")
			{
				slug = BX.prop.getString(communication, "VALUE");
			}
		}

		if(slug !== "")
		{
			window.top['BXIM'].openMessengerSlider(slug, {RECENT: 'N', MENU: 'N'});
		}
	}

	static create(id, settings)
	{
		const self = new OpenLine();
		self.initialize(id, settings);
		return self;
	}
}
