import History from "./history";
import {EventEmitter} from "main.core.events";

/** @memberof BX.Crm.Timeline.Items */
export default class FinalSummaryDocuments extends History
{
	constructor()
	{
		super();
	}

	getMessage(name)
	{
		const m = FinalSummaryDocuments.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	getTitle()
	{
		return this.getMessage('title');
	}

	getHeaderChildren()
	{
		const children = [
			BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-event-title"},
					children:
						[
							BX.create("A",
								{
									attrs: {href: "#"},
									events: {click: this._headerClickHandler},
									text: this.getTitle()
								}
							)
						]
				}
			)
		];
		children.push(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			));

		return children;
	}

	createCheckBlock(check)
	{
		const blockNode = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail-notice"}});
		blockNode.appendChild(
			BX.create(
				"a",
				{
					attrs: { href: check.URL, target: '_blank' },
					text: check.TITLE
				}
			)
		);

		return blockNode;
	}

	prepareContent()
	{
		const wrapper = BX.create("DIV", {attrs: {className: "crm-entity-stream-section crm-entity-stream-section-payment"}});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: 'crm-entity-stream-section-icon ' + this.getIconClassName() } })
		);

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-section-content"}});

		const contentItem = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});

		const header = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-header"},
				children: this.getHeaderChildren()
			});
		contentItem.appendChild(header);

		const data = this.getData();

		if (data.RESULT)
		{
			const summaryOptions = {
				'OWNER_ID': data.ASSOCIATED_ENTITY_ID,
				'OWNER_TYPE_ID': data.ASSOCIATED_ENTITY_TYPE_ID,
				'PARENT_CONTEXT': this,
				'CONTEXT': BX.CrmEntityType.resolveName(data.ASSOCIATED_ENTITY_TYPE_ID).toLowerCase(),
				'IS_WITH_ORDERS_MODE': false,
			};
			const timelineSummaryDocuments = new BX.Crm.TimelineSummaryDocuments(summaryOptions);

			const options = data.RESULT.TIMELINE_SUMMARY_OPTIONS;
			timelineSummaryDocuments.setOptions(options);
			const nodes = [
				timelineSummaryDocuments.render(),
			];

			contentItem.appendChild(
				BX.create("DIV",
					{
						attrs: {className: "crm-entity-stream-content-detail"},
						children: nodes
					})
			);

			content.appendChild(contentItem);
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

		return wrapper;
	}

	prepareLayout(options)
	{
		super.prepareLayout(options);
		const enableAdd = BX.type.isPlainObject(options) ? BX.prop.getBoolean(options, "add", true) : true;
		if (enableAdd)
		{
			EventEmitter.emit('BX.Crm.Timeline.Items.FinalSummaryDocuments:onHistoryNodeAdded', [this._wrapper]);
		}
	}

	getIconClassName()
	{
		return 'crm-entity-stream-section-icon-complete';
	}

	startSalescenterApplication(orderId, options)
	{
		if (options === undefined)
		{
			return;
		}

		BX.loadExt('salescenter.manager').then(function()
		{
			BX.Salescenter.Manager.openApplication(options);
		}.bind(this));
	}

	static create(id, settings)
	{
		const self = new FinalSummaryDocuments();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
