import Step from './step';
import {Dom, Loc, Tag, Type} from "main.core";
import {EventEmitter} from "main.core.events";

export default class StepCrmForm extends Step {

	constructor(options, iterator)
	{
		super(options, iterator);
	}

	onChange({target})
	{
		const select = this.node.querySelector("#crm_numbers");
		if (select.value !== null)
		{
			EventEmitter.emit(this, "Step:action", {action: "setNumber", data : {numberId : select.value}});
		}
		const butt = this.node.querySelector("#crm_button");
		if (butt)
		{
			Dom.addClass(butt, "ui-btn-wait");
			setTimeout(() => { Dom.removeClass(butt, "ui-btn-wait"); }, 2000);
		}
	}

	parseErrors(errors, notes)
	{
		for (let [key, error] of Object.entries(errors))
		{
			this.node.append(
				Tag.render`<span class="crm-wizard-settings-block-hidden-notice crm-wizard-settings-block-hidden-notice-unselected">${error["message"]}</span>`
			);
		}
		if (notes && Type.isArray(notes["items"]))
		{
			let numberIsInUse = [];
			notes["items"].forEach((number) => {
				if (number['IS_IN_USE'] === true)
				{
					numberIsInUse.push(number['LINE_NUMBER']);
				}
			});
			let numbers = '';
			if (numberIsInUse.length > 1)
			{
				numbers = `<option value="null" selected>${Loc.getMessage("CRM_SEVERAL_NUMBERS_IS_IN_USE")}</option>`
			}
			else if (numberIsInUse.length <= 0)
			{
				numbers = `<option value="null" selected>${Loc.getMessage("CRM_PICK_UP_THE_NUMBER_FOR_CRMFORM")}</option>`;
			}

			notes["items"].forEach((number) => {
				numbers += `
					<option value="${number['LINE_NUMBER']}" ${numberIsInUse.length === 1 && number['IS_IN_USE'] ? "selected" : ""}>
						[${number['LINE_NUMBER']}] ${number['SHORT_NAME']}
					</option>
			`});
			Dom.append(
				Tag.render`
					<div class="crm-wizard-settings-block-hidden-input">
						<div class="crm-wizard-settings-block-hidden-input-inner">
							<div class="crm-wizard-settings-block-hidden-input-label">${Loc.getMessage("CRM_CHANGE_CRM_FORM_NUMBER")}</div>
							<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								<select class="ui-ctl-element" id="crm_numbers">
									${numbers}
								</select>
							</div>
						</div>
						<button class="ui-btn ui-btn-light-border" id="crm_button" onclick="${this.onChange.bind(this)}">${Loc.getMessage("CRM_BUTTON_APPLY")}</button>
					</div>
`,
				this.node
			);
		}
	}
}