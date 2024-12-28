import { Selector } from 'ui.form-elements.view';
import { Dom, Event, Tag, Loc, Runtime } from 'main.core';
import { SelectorFieldItemOption, SelectorFieldOptions } from 'types.js';
import './css/main.css';

export class SelectorField extends Selector
{
	#items: [SelectorFieldItemOption] = [];
	#additionalItems: [] = [];
	#hintTitleElement: HTMLElement;
	#hintDescElement: HTMLElement;
	#inputNode: HTMLElement;

	static timeTransition = 300;
	static defaultTopPosition = '45px';
	static extraHeightOffset = 70;
	static popupOffset = 5;

	constructor(params: SelectorFieldOptions)
	{
		super(params);
		this.#items = params.items;
		this.#additionalItems = params.additionalItems || [];
		this.#items = this.#items.map(item => {
			const newItem = item;
			newItem.recommended = (params.recommendedItems || []).includes(item.value);

			return newItem;
		});

		this.#hintTitleElement = Tag.render`<div class="ui-section__title"></div>`;
		this.#hintDescElement = Tag.render`<div class="ui-section__description"></div>`;
		this.#inputNode = this.#buildSelector();
	}

	renderContentField(): HTMLElement
	{
		const disableClass = this.isEnable() ? '' : 'ui-ctl-disabled';
		const lockElement = this.isEnable() ? null : this.renderLockElement();

		return Tag.render`
			<div id="${this.getId()}" class="ui-section__field-selector ">
				<div class="ui-section__field-container">
					<div class="ui-section__field-label_box">
						<label class="ui-section__field-label" for="${this.getName()}">${this.getLabel()}</label> 
						${lockElement}
					</div>
					<div class="ui-section__field-inline-box">
						<div class="ui-section__field">
							<div class="ui-ctl ui-ctl-w100 ui-ctl-after-icon ui-ctl-dropdown ${disableClass}">
								<div class="ui-ctl-after ui-ctl-icon-angle"></div>
								${this.getInputNode()}
							</div>
						</div>

						<div class="ui-section__hint">
							${this.#hintTitleElement}
							${this.#hintDescElement}
						</div>
					</div>
				</div>
			</div>
		`;
	}

	getInputNode(): HTMLElement
	{
		return this.#inputNode;
	}

	#buildSelector(): HTMLElement
	{
		const selectInput = Tag.render`
			<input type="hidden" class="select-input" name="${this.getName()}" value="${this.#getCurrentValue()}"/>
		`;
		const selector = Tag.render`
			<div class="select" id="${this.getName()}" data-state="">
				${selectInput}
				<div class="select-title">${this.#getCurrentName()}</div>
				<div class="select-content"></div>
			</div>
		`;
		const selectContentItems = this.#getContentItems();
		const selectContent = selector.querySelector('.select-content');
		if (selectContent && selectContentItems)
		{
			selectContentItems.forEach((option) => {
				selectContent.append(option);
			});
		}

		this.#titleBindEvents(selector);
		this.#labelBindEvents(selector);

		return selector;
	}

	#getContentItems(): []
	{
		const selectContentItems = [];
		for (const { value, name, selected, recommended } of this.#items)
		{
			let selectedClass = '';
			if (selected === true)
			{
				selectedClass = 'selected';
			}

			const recommendedLabel =
				recommended
					? Tag.render`
						<span class="select-label-recommended">
							${Loc.getMessage('AI_SELECTORFIELD_RECOMMENDED_LABEL')}
						</span>
					`
					: ''
			;

			const contentItemLabel = Tag.render`
				<div class="select-label-container ${selectedClass}">
					<label class="select-label" value="${value}">${name}</label>
					${recommendedLabel}
					<span class="select-label-icon ui-icon-set --check"></span>
				</div>
			`;
			selectContentItems.push(contentItemLabel);
		}

		const loadedIconSets = [];
		for (const { type, link, text, icon } of this.#additionalItems)
		{
			if (type === 'link')
			{
				const set = icon.set || 'ui.icon-set.main';
				if (!loadedIconSets.includes(set))
				{
					Runtime.loadExtension(set);
					loadedIconSets.push(set);
				}
				const contentItemLink = Tag.render`
					<div class="select-link-container">
						<span class="select-link-icon ui-icon-set ${icon.code}"></span>
						<a class="select-link" href="${link}">${text}</a>
					</div>
				`;
				selectContentItems.push(contentItemLink);
			}
		}

		return selectContentItems;
	}

	#getCurrentName(): string
	{
		let count = 0;
		let currentName = '';
		for (const { name, selected } of this.#items)
		{
			if (count === 0 || selected === true)
			{
				currentName = name;
			}
			count++;
		}

		return currentName;
	}

	#getCurrentValue(): string
	{
		let count = 0;
		let currentValue = '';
		for (const { value, selected } of this.#items)
		{
			if (count === 0 || selected === true)
			{
				currentValue = value;
			}
			count++;
		}

		return currentValue;
	}

	#titleBindEvents(selector): void
	{
		const selectTitle = selector.querySelector('.select-title');
		if (!selectTitle)
		{
			return;
		}

		Event.bind(selectTitle, 'click', () => this.#toggleSelect(selector));
	}

	#toggleSelect(selector)
	{
		this.#closeOtherSelects(selector);

		const selectContent = selector.querySelector('.select-content');
		const isActive = selector.getAttribute('data-state') === 'active';

		if (isActive)
		{
			this.#closeSelect(selector, selectContent);
		}
		else
		{
			this.#openSelect(selector, selectContent);
		}
	}

	#closeOtherSelects(currentSelector)
	{
		document.querySelectorAll('.select').forEach((selectField) => {
			if (currentSelector !== selectField)
			{
				selectField.setAttribute('data-state', '');
				setTimeout(() => {
					const selectContent = selectField.querySelector('.select-content');
					this.#prepareSelectContent(selectContent);
				}, SelectorField.timeTransition);
			}
		});
	}

	#closeSelect(selector, selectContent)
	{
		selector.setAttribute('data-state', '');
		setTimeout(() => {
			this.#prepareSelectContent(selectContent);
		}, SelectorField.timeTransition);
	}

	#openSelect(selector, selectContent)
	{
		const posSelectContent = selectContent.getBoundingClientRect();
		const heightToBottom = window.innerHeight - (posSelectContent.top + posSelectContent.height);

		if (heightToBottom > SelectorField.extraHeightOffset)
		{
			this.#prepareSelectContent(selectContent);
		}
		else
		{
			Dom.addClass(selectContent, 'select-content-reverse');
			const valueTop = posSelectContent.height + SelectorField.popupOffset;
			Dom.style(selectContent, 'top', `-${valueTop}px`);
		}
		selector.setAttribute('data-state', 'active');
	}

	#labelBindEvents(selector): void
	{
		const selectLabels = selector.querySelectorAll('.select-label-container .select-label');
		const selectTitle = selector.querySelector('.select-title');
		const selectInput = selector.querySelector('.select-input');
		if (selectLabels && selectTitle && selectInput)
		{
			for (const label of selectLabels)
			{
				const labelContainer = label.parentNode;
				Event.bind(labelContainer, 'click', () => {
					selector.setAttribute('data-state', '');

					setTimeout(() => {
						const selectContent = selector.querySelector('.select-content');
						this.#prepareSelectContent(selectContent);
					}, SelectorField.timeTransition);

					if (!Dom.hasClass(label.parentNode, 'selected'))
					{
						selectTitle.textContent = label.textContent;
						selectInput.setAttribute('value', label.getAttribute('value'));
						BX.UI.ButtonPanel.show();
						const selectedItem = selector.querySelector('.select-label-container.selected');
						if (selectedItem)
						{
							Dom.removeClass(selectedItem, 'selected');
						}
						Dom.addClass(label.parentNode, 'selected');
					}
				});
			}
		}
	}

	#prepareSelectContent(element)
	{
		Dom.removeClass(element, 'select-content-reverse');
		Dom.style(element, 'top', SelectorField.defaultTopPosition);
	}
}
