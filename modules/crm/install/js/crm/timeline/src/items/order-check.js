import History from "./history";

/** @memberof BX.Crm.Timeline.Items */
export default class OrderCheck extends History
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
			throw "OrderCheck. The field 'activityEditor' is not assigned.";
		}
	}

	getTitle()
	{
		let result = this.getMessage('orderCheck');

		const checkName = this.getTextDataParam('CHECK_NAME');
		if (checkName !== '')
		{
			result += ' "' + checkName + '"';
		}

		return result;
	}

	getWrapperClassName()
	{
		return "crm-entity-stream-section-createOrderEntity";
	}

	getHeaderChildren()
	{
		let statusMessage = '';
		let statusClass = '';
		let title = this.getTitle();

		if (this.getTextDataParam("SENDED") !== '')
		{
			title = this.getMessage('sendedTitle');
		}
		else
		{
			statusMessage = this.getMessage("printed");
			statusClass = "crm-entity-stream-content-event-successful";
			if (this.getTextDataParam("PRINTED") !== 'Y')
			{
				statusMessage = this.getMessage("unprinted");
				statusClass = "crm-entity-stream-content-event-missing";
			}
		}

		return [
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-event-title" },
					children:
						[
							BX.create("A",
								{
									attrs: { href: "#" },
									events: { click: this._headerClickHandler },
									text: title
								}
							)
						]
				}
			),
			BX.create("SPAN",
				{
					attrs: { className: statusClass },
					text: statusMessage
				}
			),
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			)
		];
	}

	prepareContentDetails()
	{
		const entityData = this.getAssociatedEntityData();
		const title = this.getTextDataParam("TITLE");
		const showUrl = BX.prop.getString(entityData, "SHOW_URL", '');
		const nodes = [];
		if(title !== "")
		{
			const isSended = this.getTextDataParam("SENDED") !== '';
			const className = isSended
				? 'crm-entity-stream-content-detail-order'
				: 'crm-entity-stream-content-detail-description'
			;

			const descriptionNode = BX.create("DIV", {attrs: {className: className}});
			if (showUrl !== "")
			{
				descriptionNode.appendChild(BX.create("A", {
					attrs: { href: showUrl},
					events: {
						click: BX.delegate(function(e) {
							BX.Crm.Page.openSlider(showUrl, { width: 500 });
							e.preventDefault ? e.preventDefault() : (e.returnValue = false);
						}, this)
					},
					text: title
				}));
			}

			const legend = this.getTextDataParam("LEGEND");
			let legendNode;
			if(legend !== "")
			{
				legendNode = BX.create("SPAN", { html: " " + legend });
			}

			if (isSended)
			{
				nodes.push(descriptionNode);
				if (legendNode)
				{
					nodes.push(legendNode);
				}
			}
			else
			{
				if (legendNode)
				{
					descriptionNode.appendChild(legendNode);
				}
				nodes.push(descriptionNode);
			}
		}

		const checkUrl = this.getTextDataParam("CHECK_URL");
		if (checkUrl)
		{
			nodes.push(
				BX.create("DIV", {
					attrs: { className: 'crm-entity-stream-content-detail-payment-info' },
					children: [
						BX.create("A", { attrs: { href: checkUrl, target: '_blank'}, text: this.getMessage('urlLink') })
					]
				})
			);
		}

		return nodes;
	}

	prepareContent()
	{
		const wrapperClassName = "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-createOrderEntity";
		const wrapper = BX.create("DIV", {attrs: {className: wrapperClassName}});
		wrapper.appendChild(BX.create("DIV", { attrs: { className: this.getIconClassName() } }));

		const contentWrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-content" }, children: [ contentWrapper ] }
			)
		);

		const header = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-header"},
				children: this.getHeaderChildren()
			}
		);
		contentWrapper.appendChild(header);

		contentWrapper.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: this.prepareContentDetails()
				}
			)
		);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			contentWrapper.appendChild(authorNode);
		}
		//endregion

		return wrapper;
	}

	getMessage(name)
	{
		const m = OrderCheck.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	static create(id, settings)
	{
		const self = new OrderCheck();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
