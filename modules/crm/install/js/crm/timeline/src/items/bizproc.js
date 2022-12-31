import History from "./history";

/** @memberof BX.Crm.Timeline.Items */
export default class Bizproc extends History
{
	constructor()
	{
		super();
	}

	getTitle()
	{
		const type = this.getTextDataParam("TYPE");
		if (type === 'AUTOMATION_DEBUG_INFORMATION')
		{
			return this.getMessage('automationDebugger');
		}

		return this.getMessage("bizproc");
	}

	prepareContent()
	{
		const wrapper = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-bp"}
			}
		);

		wrapper.appendChild(
			BX.create("DIV",
				{ attrs: { className: "crm-entity-stream-section-icon crm-entity-stream-section-icon-bp" } }
			)
		);

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		const header = this.prepareHeaderLayout();

		content.appendChild(header);
		content.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children:
						[
							BX.create("DIV",
								{
									attrs: { className: "crm-entity-stream-content-detail-description" },
									html: this.prepareContentTextHtml()
								}
							)
						]
				}
			)
		);

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

	prepareContentTextHtml()
	{
		const type = this.getTextDataParam("TYPE");
		if (type === 'ACTIVITY_ERROR')
		{
			return '<strong>#TITLE#</strong>: #ERROR_TEXT#'
				.replace('#TITLE#', BX.util.htmlspecialchars(this.getTextDataParam("ACTIVITY_TITLE")))
				.replace('#ERROR_TEXT#', BX.util.htmlspecialchars(this.getTextDataParam("ERROR_TEXT")))
		}
		else if (type === 'AUTOMATION_DEBUG_INFORMATION')
		{
			return BX.Text.encode(this.getTextDataParam('AUTOMATION_DEBUG_TEXT'));
		}

		const workflowName = this.getTextDataParam("WORKFLOW_TEMPLATE_NAME");
		const workflowStatus = this.getTextDataParam("WORKFLOW_STATUS_NAME");
		if (!workflowName
			|| workflowStatus !== 'Created' && workflowStatus !== 'Completed' && workflowStatus !== 'Terminated'
		)
		{
			return BX.util.htmlspecialchars(this.getTextDataParam("COMMENT"));
		}

		let label = BX.message('CRM_TIMELINE_BIZPROC_CREATED');
		if (workflowStatus === 'Completed')
		{
			label = BX.message('CRM_TIMELINE_BIZPROC_COMPLETED');
		}
		else if (workflowStatus === 'Terminated')
		{
			label = BX.message('CRM_TIMELINE_BIZPROC_TERMINATED');
		}

		return BX.util.htmlspecialchars(label)
			.replace('#NAME#', '<strong>' + BX.util.htmlspecialchars(workflowName) + '</strong>');
	}

	static create(id, settings)
	{
		const self = new Bizproc();
		self.initialize(id, settings);
		return self;
	}
}
