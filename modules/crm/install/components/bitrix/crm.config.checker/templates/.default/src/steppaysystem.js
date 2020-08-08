import Step from './step';
import {Tag, Type} from "main.core";

export default class StepPaySystem extends Step {

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

	parseErrors(errors:Object, notes:Object)
	{
		for (let [key, error] of Object.entries(errors))
		{
			if (key !== "PAY_SYSTEM_IS_NOT_CONFIGURED")
			{
				this.node.append(
					Tag.render`<span class="crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected">${error["message"]}</span>`
				);
			}
		}
	}
}