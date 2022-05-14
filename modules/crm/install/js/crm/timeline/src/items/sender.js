import HistoryActivity from "./history-activity";

/** @memberof BX.Crm.Timeline.Items */
export default class Sender extends HistoryActivity
{
	constructor()
	{
		super();
	}

	getDataSetting(name)
	{
		const settings = this.getObjectDataParam('SETTINGS') || {};
		return settings[name] || null;
	}

	getMessage(name)
	{
		const m = Sender.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	getTitle()
	{
		return this.getDataSetting('messageName');
	}

	prepareTitleLayout()
	{
		const self = this;
		return BX.create("SPAN", {
			attrs:{ className: "crm-entity-stream-content-event-title"},
			children: [
				this.isRemoved()
					?
					BX.create("SPAN", {text: this.getTitle()})
					:
					BX.create("A", {
						attrs: {
							href: ""
						},
						events: {
							"click": function (e)
							{
								if (BX.SidePanel)
								{
									BX.SidePanel.Instance.open(self.getDataSetting('path'));
								}
								else
								{
									top.location.href = self.getDataSetting('path');
								}

								e.preventDefault();
								e.stopPropagation();
							}
						},
						text: this.getTitle()
					})
			]
		});


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

	prepareStatusLayout()
	{
		let layoutClassName, textCaption;
		if (this.getDataSetting('isError'))
		{
			textCaption = this.getMessage('error');
			layoutClassName = "crm-entity-stream-content-event-missing";
		}
		else if (this.getDataSetting('isUnsub'))
		{
			textCaption = this.getMessage('unsub');
			layoutClassName = "crm-entity-stream-content-event-missing";
		}
		else if (this.getDataSetting('isClick'))
		{
			textCaption = this.getMessage('click');
			layoutClassName = "crm-entity-stream-content-event-successful";
		}
		else
		{
			textCaption = this.getMessage('read');
			layoutClassName = "crm-entity-stream-content-event-skipped";
		}

		return BX.create("SPAN", {attrs: {className: layoutClassName}, text: textCaption});
	}

	prepareHeaderLayout()
	{
		const header = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-header"}});
		header.appendChild(this.prepareTitleLayout());
		if (this.getDataSetting('isError') || this.getDataSetting('isRead') || this.getDataSetting('isUnsub'))
		{
			header.appendChild(this.prepareStatusLayout());
		}
		header.appendChild(this.prepareTimeLayout());

		return header;
	}

	isRemoved()
	{
		return !this.getDataSetting('letterTitle');
	}

	prepareContent()
	{
		const description = this.isRemoved() ? this.getMessage('removed') : this.getMessage('title') + ': ' + this.getDataSetting('letterTitle');

		const wrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-wait"}
			}
		);

		wrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-complete" }
				}
			)
		);

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
				html: description
			}
		);
		contentWrapper.appendChild(detailWrapper);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		return wrapper;
	}

	static create(id, settings)
	{
		const self = new Sender();
		self.initialize(id, settings);
		return self;
	}
}
