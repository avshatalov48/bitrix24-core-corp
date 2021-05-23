import Step from './step';
import {Tag, Type} from "main.core";

export default class StepImconnector extends Step {

	constructor(options, iterator)
	{
		super(options, iterator);
	}

	parseErrors(errors, notes)
	{
		let node = null;
		for (let [key, error] of Object.entries(errors))
		{
			if (error["code"] === "IMCONNECTOR_IS_NOT_CORRECT" && Type.isArray(error["customData"]))
			{
				node = Tag.render`<ul class="crm-wizard-settings-block-list"></ul>`;
				error["customData"].forEach((item) => {
					node.append(
						Tag.render`
	<li class="${item["icon_class"]}">
		<a href="${item["link"]}" onclick="BX.SidePanel.Instance.open(this.href); return false;">${item["name"]}</a>
	</li>
						`);
				});
				node = Tag.render`<div class="crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected">${error["message"]}${node}</div>`;
			}
			else
			{
				node = Tag.render`<div class="crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected">${error["message"]}</div>`;
			}
			this.node.append(
				node
			);
		}
	}
}