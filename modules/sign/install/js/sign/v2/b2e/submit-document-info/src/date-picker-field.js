import { Tag, Text, Type } from 'main.core';
import { DatePicker } from 'ui.date-picker';
import { BaseField } from 'ui.form-elements.view';

export class DatePickerField extends BaseField
{
	#datepicker: DatePicker;
	#inputNode: HTMLElement;
	defaultValue: string;
	constructor(params: {value?: string})
	{
		super(params);
		this.defaultValue = Type.isStringFilled(params.value) ? params.value : '';
		this.#datepicker = new DatePicker({
			type: 'date',
			inputField: this.getInputNode(),
			targetNode: this.getInputNode(),
		});
	}

	getValue(): string
	{
		return this.getInputNode().value;
	}

	getInputNode(): HTMLElement
	{
		this.#inputNode ??= this.#renderInputNode();

		return this.#inputNode;
	}

	#renderInputNode(): HTMLElement
	{
		return Tag.render`
			<input
				value="${Text.encode(this.defaultValue)}" 
				name="${Text.encode(this.getName())}" 
				type="text" 
				class="ui-ctl-element --readonly" 
				readonly
			>
		`;
	}

	renderContentField(): HTMLElement
	{
		const lockElement = !this.isEnable ? this.renderLockElement() : null;

		return Tag.render`
			<div id="${this.getId()}" class="ui-section__field-selector">
				<div class="ui-section__field-container">
					<div class="ui-section__field-label_box">
						<label for="${this.getName()}" class="ui-section__field-label">
							${this.getLabel()}
						</label> 
						${lockElement}
					</div>  
					<div class="ui-ctl ui-ctl-textbox ui-ctl-block ui-ctl-after-icon ${this.inputDefaultWidth ? '' : 'ui-ctl-w100'}">
						<div class="ui-ctl-after ui-ctl-icon-calendar"></div>
						${this.getInputNode()}
					</div>
					${this.renderErrors()}
				</div>
				<div class="ui-section__hint">
					${this.hintTitle}
				</div>
			</div>
		`;
	}
}
