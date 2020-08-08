import Step from './step';
import {Tag, Type} from "main.core";

export default class StepMessageService extends Step {

	constructor(options, iterator)
	{
		super(options, iterator);
	}

	onClickUrl()
	{
		if (Type.isStringFilled(this.url))
		{
			window.open(this.url);
		}
	}

	parseErrors(errors)
	{
		let node = null, node2 = null;
		const errorNode = Tag.render`<div class="crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected"></div>`;

		for (let [key, error] of Object.entries(errors))
		{
			if (error["code"] === "NONEXISTENT_PROVIDER")
			{
				if (node2 === null)
				{
					node2 = Tag.render`<ul class="crm-wizard-settings-block-provider-list"></ul>`;
					errorNode.append(
						Tag.render`<div class="crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected">${node2}</div>`
					);
				}
				node2.append(Tag.render`<li>${error["message"]}</li>`);
			}
			else if (error["code"] === "NONWORKING_PROVIDER")
			{
				if (node === null)
				{
					node = Tag.render`<ul class="crm-wizard-settings-block-provider-list"></ul>`;
					errorNode.append(
						Tag.render`<div class="crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected">${node}</div>`
					);
				}
				node.append(
					Tag.render`
	<li>
		${error["message"]}
		<a href="${error["customData"]["manageUrl"]}" onclick="BX.SidePanel.Instance.open(this.href); return false;">${error["customData"]["shortName"]}</a>
	</li>
						`);
			}
			else
			{
				errorNode.append(
					Tag.render`<div>${error["message"]}</div>`
				);
			}
		}
		if (errorNode.hasChildNodes())
		{
			this.node.append(
				errorNode
			);
		}
	}
}