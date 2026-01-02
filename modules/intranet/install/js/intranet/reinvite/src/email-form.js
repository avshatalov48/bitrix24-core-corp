import { Form } from './form';
import { Tag, Type, Loc, Event } from 'main.core';

export class EmailForm extends Form
{
	#onButtonStateChange: ?Function;

	constructor(options)
	{
		super(options);
		this.#onButtonStateChange = Type.isFunction(options.onButtonStateChange) ? options.onButtonStateChange : null;
	}

	getTitleRender(): HTMLElement
	{
		return Tag.render`<div class="intranet-reinvite-popup-title">
			${Loc.getMessage('INTRANET_JS_EMAIL_POPUP_TITLE', {'#CODE#': 'redirect=detail&code=17729332'})}
			</div>`;
	}

	getFieldRender(): HTMLElement
	{
		const field = Tag.render`
			<div class="intranet-reinvite-popup-field-row">
				<div class="intranet-reinvite-popup-field-label">
					<label>${Loc.getMessage('INTRANET_JS_EMAIL_FIELD_LABEL')}</label>
				</div>
				<div class="ui-ctl ui-ctl-textbox">
					<input type="text" name="newEmail" value="${this.getValue()}" class="ui-ctl-element"> 
				</div>
			</div>`;

		const input = field.querySelector('input[name="newEmail"]');
		if (input && this.#onButtonStateChange)
		{
			Event.bind(input, 'input', (event) => {
				const value = event.target.value.trim();
				this.#onButtonStateChange(Type.isStringFilled(value));
			});
		}

		return field;
	}
}