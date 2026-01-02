import { Event, Validation, Type, Dom, Tag, Loc } from 'main.core';
import { Phone } from './phone';

export const InputRowType = Object.freeze({
	PHONE: 'phone',
	EMAIL: 'email',
	ALL: 'all',
});

export type InputRowFactoryType = {
	inputRowType?: InputRowType,
}

export class InputRowFactory
{
	#inputRowType: InputRowType;
	#isPhoneEnabled: boolean;
	#isEmailEnabled: boolean;

	constructor(params)
	{
		this.inputNum = 0;
		this.#inputRowType = params.inputRowType ?? InputRowType.ALL;

		this.#isPhoneEnabled = [InputRowType.ALL, InputRowType.PHONE].includes(this.#inputRowType);
		this.#isEmailEnabled = [InputRowType.ALL, InputRowType.EMAIL].includes(this.#inputRowType);

		if (this.#isPhoneEnabled)
		{
			this.phoneObj = new Phone();
		}
	}

	#checkPhoneInput(element)
	{
		const phoneExp = /^[\d+][\d ()-]{2,14}\d$/;

		if (element.value && phoneExp.test(String(element.value).toLowerCase()))
		{
			this.phoneObj.renderPhoneRow(element);
		}
		else if (element.value.length === 0)
		{
			this.#renderDefaultRow(element)?.focus();
		}
	}

	#bindPhoneChecker(element)
	{
		if (this.#isPhoneEnabled && Type.isDomNode(element))
		{
			const inputNodes = Array.prototype.slice.call(element.querySelectorAll('.js-email-phone-input'));
			if (inputNodes)
			{
				inputNodes.forEach((element) => {
					Event.bind(element, 'input', () => {
						this.#checkPhoneInput(element);
					});
				});
			}
		}
	}

	#renderDefaultRow(node)
	{
		const phoneFlagBlock = node.parentNode.querySelector('[data-role="phone-block"]');

		if (Type.isDomNode(phoneFlagBlock))
		{
			Dom.remove(phoneFlagBlock);
		}

		const phoneBlock = node.parentNode.querySelector('input.ui-ctl-element');

		if (Type.isDomNode(phoneBlock))
		{
			const newInput = Tag.render`
				<input
					name="${phoneBlock.name}"
					type="text"
					maxlength="50"
					data-num="${node.getAttribute('data-num')}"
					class="ui-ctl-element js-email-phone-input"
					placeholder="${phoneBlock.placeholder}"
				/>
			`;

			Dom.replace(phoneBlock, newInput);
			this.bindCloseIcons(newInput.parentNode);
			this.#bindPhoneChecker(newInput.parentNode);
			Dom.remove(phoneBlock);

			return newInput;
		}

		return phoneBlock;
	}

	bindCloseIcons(container)
	{
		const inputNodes = Array.prototype.slice.call(container.querySelectorAll('input'));

		(inputNodes || []).forEach((node) => {
			const closeIcon = node.nextElementSibling;

			Event.bind(node, 'input', () => {
				Dom.style(closeIcon, 'display', node.value === '' ? 'none' : 'block');
			});

			Event.bind(closeIcon, 'click', (event) => {
				event.preventDefault();

				if (Type.isDomNode(node.parentNode))
				{
					this.#renderDefaultRow(node);
				}

				Dom.style(closeIcon, 'display', 'none');
			});
		});
	}

	renderInputRowTo(target: HTMLElement, showTitles = false)
	{
		const element = this.renderEmailInputsRow(showTitles);

		Dom.append(element, target);
	}

	renderInputRowsTo(target: HTMLElement, count = 3)
	{
		for (let i = 0; i < count; i++)
		{
			this.renderInputRowTo(target, i === 0);
		}
	}

	renderEmailInputsRow(showTitles: boolean): HTMLElement
	{
		let fieldTitle = '';
		let nameTitle = '';
		let lastNameTitle = '';

		if (showTitles)
		{
			fieldTitle = `
				<div class="invite-content__field-lable">
					${this.#getDialogInputMessage()}
				</div>
			`;

			nameTitle = `
				<div class="invite-content__field-lable">
					${Loc.getMessage('BX24_INVITE_DIALOG_ADD_NAME_TITLE')}
				</div>
			`;

			lastNameTitle = `
				<div class="invite-content__field-lable">
					${Loc.getMessage('BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE')}
				</div>
			`;
		}

		const element = Tag.render`
			<div class="invite-form-row js-form-row">
				<div class="invite-form-col">
					${showTitles ? fieldTitle : ''}
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input 
							name="EMAIL[]" 
							type="text" 
							maxlength="50"
							data-num="${this.inputNum++}" 
							class="ui-ctl-element js-email-phone-input" 
							placeholder="${this.#getDialogInputMessage()}"
						/>
						<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
				<div class="invite-form-col">
					${showTitles ? nameTitle : ''}
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input name="NAME[]" type="text" class="ui-ctl-element js-email-name-input" placeholder="${Loc.getMessage('BX24_INVITE_DIALOG_ADD_NAME_TITLE')}">
						<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
				<div class="invite-form-col">
					${showTitles ? lastNameTitle : ''}
					<div class="ui-ctl ui-ctl-w100 ui-ctl-textbox ui-ctl-block ui-ctl-after-icon">
						<input name="LAST_NAME[]" type="text" class="ui-ctl-element js-email-last-name-input" placeholder="${Loc.getMessage('BX24_INVITE_DIALOG_ADD_LAST_NAME_TITLE')}">
						<button type="button" class="ui-ctl-after ui-ctl-icon-clear" style="display: none"></button>
					</div>
				</div>
			</div>
		`;
		this.bindCloseIcons(element);
		this.#bindPhoneChecker(element);

		return element;
	}

	#getDialogInputMessage(): string
	{
		if (this.#isPhoneEnabled && this.#isEmailEnabled)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_OR_PHONE_INPUT');
		}
		else if (this.#isPhoneEnabled)
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_PHONE_INPUT');
		}
		else
		{
			return Loc.getMessage('INTRANET_INVITE_DIALOG_EMAIL_INPUT');
		}
	}

	parseEmailAndPhone(form: HTMLElement)
	{
		if (!Type.isDomNode(form))
		{
			return;
		}

		const errorInputData = [];
		const items = [];
		const phoneExp = /^[\d+][\d ()-]{4,22}\d$/;
		const rows = Array.prototype.slice.call(form.querySelectorAll('.js-form-row'));

		(rows || []).forEach((row) => {
			const emailInput = row.querySelector("input[name='EMAIL[]']");
			const phoneInput = row.querySelector("input[name='PHONE[]']");
			const nameInput = row.querySelector("input[name='NAME[]']");
			const lastNameInput = row.querySelector("input[name='LAST_NAME[]']");
			const emailValue = emailInput.value.trim();

			if (this.#isPhoneEnabled && Type.isDomNode(phoneInput))
			{
				const phoneValue = phoneInput.value.trim();
				if (phoneValue)
				{
					if (phoneExp.test(String(phoneValue).toLowerCase()))
					{
						const phoneCountryInput = row.querySelector("input[name='PHONE_COUNTRY[]']");
						items.push({
							PHONE: phoneValue,
							PHONE_COUNTRY: phoneCountryInput.value.trim(),
							NAME: nameInput.value,
							LAST_NAME: lastNameInput.value,
						});
					}
					else
					{
						errorInputData.push(phoneValue);
					}
				}
			}
			else if (this.#isEmailEnabled && emailValue)
			{
				if (Validation.isEmail(emailValue))
				{
					items.push({
						EMAIL: emailValue,
						NAME: nameInput.value,
						LAST_NAME: lastNameInput.value,
					});
				}
				else
				{
					errorInputData.push(emailValue);
				}
			} else if (this.#isPhoneEnabled && !this.#isEmailEnabled && emailValue)
			{
				errorInputData.push(emailValue);
			}
		});

		return [items, errorInputData];
	}
}
