import Activity from "./activity";
import Item from "../../item";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class Zoom extends Activity
{
	constructor()
	{
		super();
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-zoom";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-zoom";
	}

	getTypeDescription()
	{
		return this.getMessage("zoom");
	}

	getPrepositionText(direction)
	{
	}

	prepareCommunicationNode(communicationValue)
	{
		return null;
	}

	prepareContent(options)
	{
		const deadline = this.getDeadline();
		const timeText = deadline ? this.formatDateTime(deadline) : this.getMessage("termless");

		const entityData = this.getAssociatedEntityData();
		const direction = BX.prop.getInteger(entityData, "DIRECTION", 0);
		const isDone = this.isDone();
		const subject = BX.prop.getString(entityData, "SUBJECT", "");
		let description = BX.prop.getString(entityData, "DESCRIPTION_RAW", "");

		const communication = BX.prop.getObject(entityData, "COMMUNICATION", {});
		const title = BX.prop.getString(communication, "TITLE", "");
		const showUrl = BX.prop.getString(communication, "SHOW_URL", "");
		const communicationValue = BX.prop.getString(communication, "TYPE", "") !== ""
			? BX.prop.getString(communication, "VALUE", "") : "";

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
		if(description !== "")
		{
			//trim leading spaces
			description = description.replace(/^\s+/,'');
		}

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
				attrs: {className: "crm-entity-stream-content-header"},
				children:
					[
						BX.create("SPAN",
							{
								attrs:
									{
										className: "crm-entity-stream-content-event-title"
									},
								text: this.getTypeDescription(direction)
							}
						),
						this._deadlineNode
					]
			}
		);
		contentInnerWrapper.appendChild(headerWrapper);

		const detailWrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-detail"}
			}
		);
		contentInnerWrapper.appendChild(detailWrapper);

		if (entityData['ZOOM_INFO'])
		{
			const topic = entityData['ZOOM_INFO']['TOPIC'];
			const duration = entityData['ZOOM_INFO']['DURATION'];
			const startTimeStamp = BX.parseDate(
				entityData['ZOOM_INFO']['CONF_START_TIME'],
				false,
				"YYYY-MM-DD",
				"YYYY-MM-DD HH:MI:SS"
			);
			const date = new Date(startTimeStamp.getTime() + 1000 * Item.getUserTimezoneOffset());
			const detailZoomMessage = BX.create("span",
				{
					text: this.getMessage("zoomCreatedMessage")
						.replace("#CONFERENCE_TITLE#", topic)
						.replace("#DATE_TIME#", this.formatDateTime(date))
						.replace("#DURATION#", duration)
				}
			);

			const detailZoomInfoLink = BX.create("A",
				{
					attrs:
						{
							href: entityData['ZOOM_INFO']['CONF_URL'],
							target: "_blank",
						},
					text: entityData['ZOOM_INFO']['CONF_URL']
				}
			);

			const detailZoomInfo = BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-detail-zoom-info"},
					children: [detailZoomMessage, detailZoomInfoLink]
				}
			);

			detailWrapper.appendChild(detailZoomInfo);

			const detailZoomCopyInviteLink = BX.create("A",
				{
					attrs: {
						className: 'ui-link ui-link-dashed',
						"data-url": entityData['ZOOM_INFO']['CONF_URL']
					},
					text: this.getMessage("zoomCreatedCopyInviteLink"),
				}
			);
			BX.clipboard.bindCopyClick(detailZoomCopyInviteLink, {
				text: entityData['ZOOM_INFO']['CONF_URL'],
			});

			const detailZoomStartConferenceButton = BX.create("BUTTON",
				{
					attrs: {className: 'ui-btn ui-btn-sm ui-btn-primary'},
					text: this.getMessage("zoomCreatedStartConference"),
					events: {
						"click": function () {
							window.open(entityData['ZOOM_INFO']['CONF_URL']);
						}
					}
				}
			);

			const detailZoomCopyInviteLinkWrapper = BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-detail-zoom-link-wrapper"},
					children: [detailZoomCopyInviteLink]
				}
			);
			detailWrapper.appendChild(detailZoomCopyInviteLinkWrapper);
			detailWrapper.appendChild(detailZoomStartConferenceButton);
		}

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

	prepareDetailNodes()
	{
	}

	static create(id, settings)
	{
		const self = new Zoom();
		self.initialize(id, settings);
		return self;
	}
}
