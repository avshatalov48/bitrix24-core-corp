import Step from './step';
import {Tag} from "main.core";

export default class StepTelephony extends Step {

	constructor(options, iterator)
	{
		super(options, iterator);
	}

	parseErrors(errors:Object, notes:Object)
	{
		for (let [key, error] of Object.entries(errors))
		{
			if (key !== "VOXIMPLANT_IS_NOT_CONFIGURED")
			{
				this.node.append(
					Tag.render`<span class="crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected">${error["message"]}</span>`
				);
			}
		}
	}
}