import { Dom, Event, Loc, Tag } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Dialog } from 'ui.entity-selector';
import { Checker } from 'ui.form-elements.view';

import 'main.polyfill.intersectionobserver';

type Params = {
	id: string,
	title: string,
	placeholder: ?string,
	value: ?string,
	entitySelector: ?Dialog,
	unit: ?string,
	size: ?string,
	isFieldDisabled: ?boolean,
	hintText?: string,
	hintOnDisabled?: boolean,
};

export class ValueChecker extends EventEmitter
{
	#params: Params;

	#layout: {
		wrap: HTMLElement,
		checker: Checker,
		checkerValue: ?HTMLInputElement,
		disabledValue: ?HTMLElement,
		entitySelector: ?HTMLElement,
		checkerContentField: HTMLElement;
	};

	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Flow.EditForm.ValueChecker');

		this.#params = params;
		this.#layout = {};

		if (this.#params.entitySelector)
		{
			this.#params.value = this.#params.entitySelector.getPreselectedItems()[0]?.[1];
		}
	}

	isChecked(): boolean
	{
		return this.#layout.checker.isChecked();
	}

	getValue(): string
	{
		const entitySelectorValue = this.#getSelectedItem()?.id;

		return this.#layout.checkerValue?.value || entitySelectorValue || this.#params.placeholder;
	}

	setErrors(errors: string[])
	{
		this.#layout.checker?.setErrors(errors);
	}

	cleanError()
	{
		this.#layout.checker?.cleanError();
	}

	getInputNode(): HTMLInputElement
	{
		return this.#renderInput();
	}

	getChecker(): Checker
	{
		return this.#layout.checker;
	}

	disable(disabled: boolean)
	{
		if (disabled)
		{
			this.#layout.checker.switcher.check(true);
		}

		this.#layout.checker.switcher.disable(disabled);
	}

	render(): HTMLElement
	{
		this.#layout.wrap = Tag.render`
			<div
				class="tasks-flow__create-value-checker ${this.#params.value ? '' : '--off'}"
				data-id="tasks-flow-value-checker-${this.#params.id}"
			>
				${this.#renderChecker()}
				${this.#renderValue()}
				${this.#renderEntitySelectorValue()}
			</div>
		`;

		this.update();

		const observer = new IntersectionObserver(() => {
			if (this.#layout.wrap.offsetWidth > 0)
			{
				this.update();

				observer.disconnect();
			}
		});
		observer.observe(this.#layout.wrap);

		return this.#layout.wrap;
	}

	#renderChecker(): HTMLElement
	{
		this.#layout.checker = new Checker({
			checked: Boolean(this.#params.value),
			title: this.#params.title,
			hideSeparator: true,
			size: this.#params.size ?? 'small',
			isFieldDisabled: this.#params.isFieldDisabled ?? false,
		});

		this.#layout.checker.subscribe('change', (baseEvent: BaseEvent) => {
			const isChecked = baseEvent.getData();

			this.update();

			if (isChecked && this.#layout.checkerValue)
			{
				const length = this.#layout.checkerValue.value.length;

				this.#layout.checkerValue.focus();
				this.#layout.checkerValue.setSelectionRange(length, length);
			}
		});
		EventEmitter.subscribe(this.#layout.checker.switcher, 'lock', () => {
			this.#setHint(true);
			this.emit('lock', this.#layout.checkerContentField);
		});
		EventEmitter.subscribe(this.#layout.checker.switcher, 'unlock', () => {
			this.#setHint(false);
			this.emit('unlock', this.#layout.checkerContentField);
		});

		this.#layout.checkerContentField = this.#layout.checker.render();

		this.#setHint(this.#params.isFieldDisabled ?? false);

		return this.#layout.checkerContentField;
	}

	#setHint(isDisabled: boolean): void
	{
		if (!this.#params.hintText)
		{
			return;
		}

		if (
			this.#params.hintOnDisabled === true
			&& isDisabled === false
		)
		{
			Dom.attr(this.#layout.checkerContentField, 'data-hint', null);
			Dom.attr(this.#layout.checkerContentField, 'data-hint-no-icon', null);
		}
		else
		{
			Dom.attr(this.#layout.checkerContentField, 'data-hint', this.#params.hintText);
			Dom.attr(this.#layout.checkerContentField, 'data-hint-no-icon', true);
		}
	}

	#renderValue(): HTMLElement | string
	{
		if (!this.#params.placeholder)
		{
			return '';
		}

		this.#layout.disabledValue = Tag.render`
			<span class="tasks-flow__create-value-checker_text">${this.getValue()}</span>
		`;

		return Tag.render`
			<div class="tasks-flow__create-value-checker_input">
				${this.#renderInput()}
				<span>${this.#params.unit ?? ''}</span>
				${this.#layout.disabledValue}
			</div>
		`;
	}

	#renderInput(): HTMLElement
	{
		if (this.#layout.checkerValue)
		{
			return this.#layout.checkerValue;
		}

		this.#layout.checkerValue = Tag.render`
			<input class="ui-ctl-element" placeholder="${this.#params.placeholder}" value="${this.#params.value ?? this.#params.placeholder}">
		`;

		Event.bind(this.#layout.checkerValue, 'input', () => this.update());

		return this.#layout.checkerValue;
	}

	#renderEntitySelectorValue(): HTMLElement|string
	{
		if (!this.#params.entitySelector)
		{
			return '';
		}

		this.#layout.entitySelector = Tag.render`
			<div class="tasks-flow-template-selector">
				${Loc.getMessage('TASKS_FLOW_EDIT_FORM_SELECT')}
			</div>
		`;

		this.#params.entitySelector.subscribe('Item:onSelect', this.#onEntitySelectorItemSelectedHandler.bind(this));
		this.#params.entitySelector.subscribe('Item:onDeselect', () => this.update());
		this.#params.entitySelector.subscribe('onLoad', () => this.update());
		this.#params.entitySelector.setTargetNode(this.#layout.entitySelector);

		Event.bind(this.#layout.entitySelector, 'click', () => {
			if (this.isChecked())
			{
				this.#params.entitySelector.show();
			}
		});

		return this.#layout.entitySelector;
	}

	#onEntitySelectorItemSelectedHandler()
	{
		this.update();
		this.#params.entitySelector.hide();
	}

	update(): void
	{
		this.#layout.wrap.closest('form')?.dispatchEvent(new window.Event('change'));

		Dom.addClass(this.#layout.wrap, '--off');
		if (this.isChecked())
		{
			Dom.removeClass(this.#layout.wrap, '--off');
		}

		if (this.#params.entitySelector)
		{
			this.#layout.entitySelector.innerText = this.#getSelectedItem()?.title.text ?? Loc.getMessage('TASKS_FLOW_EDIT_FORM_SELECT');
		}

		if (!this.#params.placeholder)
		{
			return;
		}

		this.#layout.disabledValue.innerText = this.getValue();
		Dom.style(this.#layout.disabledValue, 'display', '');
		this.#layout.checkerValue.style.width = `${this.#layout.disabledValue.offsetWidth + 7}px`;
		const checkerField = this.#layout.wrap.querySelector('.ui-section__field');
		this.#layout.checkerValue.style.height = `${checkerField.offsetHeight}px`;
	}

	#getSelectedItem(): any
	{
		return this.#params.entitySelector?.getSelectedItems()[0];
	}
}