import Activity from "./activity";
import {OpenLine as OpenLineAction} from "../../action/openline";

/** @memberof BX.Crm.Timeline.Items.Scheduled */
export default class OpenLine extends Activity
{
	constructor()
	{
		super();
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-IM";
	}

	getIconClassName()
	{
		return "crm-entity-stream-section-icon crm-entity-stream-section-icon-IM";
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
					ownerInfo: this._schedule.getOwnerInfo()
				}
			)
		);
	}

	getTypeDescription()
	{
		return this.getMessage("openLine");
	}

	getPrepositionText(direction)
	{
		return this.getMessage("reciprocal");
	}

	prepareCommunicationNode(communicationValue)
	{
		return null;
	}

	prepareDetailNodes()
	{
		const wrapper = BX.create("DIV",
			{attrs: {className: "crm-entity-stream-content-detail-IM"}}
		);

		const messageWrapper = BX.create("DIV",
			{attrs: {className: "crm-entity-stream-content-detail-IM-messages"}}
		);
		wrapper.appendChild(messageWrapper);

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

		return [ wrapper ];
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
