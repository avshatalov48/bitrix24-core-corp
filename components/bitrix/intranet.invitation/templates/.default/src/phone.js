import {Dom, Type, Tag, Event} from "main.core";

export class Phone
{
	constructor(parent)
	{
		this.parent = parent;

		this.count = 0;
		this.index = 0;
		this.maxCount = 5;
		this.inputStack = [];
	}

	renderPhoneRow(inputNode)
	{
		if(this.count >= this.maxCount)
		{
			return;
		}

		if (!Type.isDomNode(inputNode))
		{
			return;
		}

		const num = inputNode.getAttribute("data-num");

		if (inputNode.parentNode.querySelector("#phone_number_" + num))
		{
			return;
		}

		const element = Tag.render`
			<span style="z-index: 3;" class="ui-ctl-before" data-role="phone-block">
				<input type="hidden" name="PHONE_COUNTRY[]" id="phone_country_${num}" value="">
				<input type="hidden" name="PHONE[]" id="phone_number_${num}" value="">
				<div class="invite-dialog-phone-flag-block" data-role="flag">
					<span data-role="phone_flag_${num}" style="pointer-events: none;"></span>
				</div>
				<input class="invite-dialog-phone-input" type="hidden" id="phone_input_${num}" value="">&nbsp;
			</span>
		`;

		inputNode.style.paddingLeft = "57px";
		Dom.append(element, inputNode.parentNode);

		const flagNode = inputNode.parentNode.querySelector("[data-role='flag']");
		if (Type.isDomNode(flagNode))
		{
			Event.bind(inputNode.parentNode.querySelector("[data-role='flag']"), 'click', () => {
				this.showCountrySelector(num);
			});
		}

		let changeCallback = function(i, inputNode)
		{
			return function(e)
			{
				if (Type.isDomNode(inputNode.parentNode))
				{
					inputNode.parentNode.querySelector('#phone_number_' + i).value = e.value;
					inputNode.parentNode.querySelector('#phone_country_' + i).value = e.country;
				}
			}
		};

		this.inputStack[num] = new BX.PhoneNumber.Input({
			node: inputNode,
			flagNode: inputNode.parentNode.querySelector("[data-role='phone_flag_"+ num +"']"),
			flagSize: 16,
			onChange: changeCallback(num, inputNode)
		});

		//for ctrl+v paste
		const id = setInterval(() => {
			if (
				!inputNode.parentNode?.querySelector('#phone_number_' + num).value
				|| !inputNode.parentNode?.querySelector('#phone_country_' + num).value
			)
			{
				changeCallback(num, inputNode)({
					value: this.inputStack[num].getValue(),
					country: this.inputStack[num].formatter ? this.inputStack[num].getCountry() : null,
				});
			}
			else
			{
				clearInterval(id);
			}
		}, 1000);
	}

	showCountrySelector(i)
	{
		this.inputStack[i]._onFlagClick();
	}
}