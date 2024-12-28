import { Dom, Event, Loc, Tag, Text, Type } from 'main.core';

import type { TextboxOptions } from './textbox-options';
import { Attention, AttentionPresets } from './attention';

import 'ui.design-tokens';
import 'ui.fonts.opensans';

import './style.css';

const ID_TEXT_CONTAINER = 'crm-copilot-text-container';

const CLASS_SEARCH_ICON = 'ui-ctl-icon-search';
const CLASS_CLEAR_ICON = 'ui-ctl-icon-clear';
const ROOT_CONTAINER_BOTTOM_PADDING = '28px';

export class Textbox
{
	#id: string;
	#text: string;
	#title: string;
	#enableSearch: boolean;
	#enableCollapse: boolean;
	#isCollapsed: boolean;
	#previousTextContent: HTMLElement = null;
	#attentions: Object = null;

	#className = {
		searchIcon: '--search-1',
		clearIcon: '--cross-30',
		arrowTopIcon: '--chevron-up',
		arrowDownIcon: '--chevron-down',
		bodyExpanded: '--body-expanded',
		nodeHidden: '--hidden',
	};

	constructor(options: TextboxOptions = {}): void
	{
		this.setText(options.text);

		this.#id = `crm-copilot-textbox-container-${Text.getRandom(8)}`;
		this.#title = (Type.isString(options.title)) ? options.title : '';
		this.#enableSearch = Type.isBoolean(options.enableSearch) ? options.enableSearch : true;
		this.#enableCollapse = Type.isBoolean(options.enableCollapse) ? options.enableCollapse : false;
		this.#isCollapsed = Type.isBoolean(options.isCollapsed) ? options.isCollapsed : false;
		this.#previousTextContent = Type.isElementNode(options.previousTextContent) ? options.previousTextContent : null;
		this.#attentions = options.attentions ?? [];
	}

	setText(text): void
	{
		this.#text = (Type.isString(text)) ? this.#prepareText(text) : '';
	}

	render(): void
	{
		this.rootContainer = Tag.render`
			<div 
				id="${this.#id}" 
				class="crm-copilot-textbox"
			></div>
		`;

		if (this.#isCollapsed)
		{
			Dom.style(this.rootContainer, 'padding-bottom', 0);
		}
		else
		{
			Dom.style(this.rootContainer, 'padding-bottom', ROOT_CONTAINER_BOTTOM_PADDING);
		}

		const sectionWrapper = Tag.render`<div class="crm-copilot-textbox__wrapper ${this.#isCollapsed ? '' : this.#className.bodyExpanded} ${this.#enableCollapse ? 'clickable' : ''}"></div>`;

		Dom.append(this.#getHeaderContainer(), sectionWrapper);
		Dom.append(this.#getBodyContainer(), sectionWrapper);
		Dom.append(sectionWrapper, this.rootContainer);
	}

	get(): HTMLElement
	{
		return this.rootContainer;
	}

	#prepareText(text): string
	{
		return text.replaceAll(/\r?\n/g, '<br>');
	}

	#getHeaderContainer(): HTMLElement
	{
		const collapseIconElement = this.#enableCollapse
			? Tag.render`<div class="crm-copilot-textbox__collapse-icon clickable ui-icon-set ${this.#isCollapsed ? this.#className.arrowDownIcon : this.#className.arrowTopIcon}"></div>`
			: ''
		;

		Event.bind(collapseIconElement, 'click', () => this.#handleCollapse());

		return Tag.render`
			<div class="crm-copilot-textbox__header">
				<div class="crm-copilot-textbox__title">${Text.encode(this.#title)}</div>
				<div class="crm-copilot-textbox__title-icon-container">
					${this.#getSearchContainer()}
					${collapseIconElement}
				</div>
			</div>
		`;
	}

	#getBodyContainer(): HTMLElement
	{
		const bodyContainer = Tag.render`<div class="crm-copilot-textbox__body-container"></div>`;

		Dom.append(
			Tag.render`<div class="crm-copilot-textbox__previous-text">${this.#previousTextContent}</div>`,
			bodyContainer,
		);
		Dom.append(this.#getContentContainer(), bodyContainer);
		Dom.append(this.#getAttentionsContainer(), bodyContainer);

		return Tag.render`<div class="crm-copilot-textbox__body">${bodyContainer}</div>`;
	}

	#getContentContainer(): HTMLElement
	{
		const contentContainer = Tag.render`<div class="crm-copilot-textbox__content"></div>`;
		const textContainer = this.#getTextContainer();

		Event.bind(textContainer, 'beforeinput', (e) => {
			e.preventDefault();
		});

		Dom.append(textContainer, contentContainer);

		return contentContainer;
	}

	#getTextContainer(): HTMLElement
	{
		if (this.textContainer)
		{
			return this.textContainer;
		}

		this.textContainer = Tag.render`
			<div 
				id="${ID_TEXT_CONTAINER}" 
				class="crm-copilot-textbox__text-container" 
				contenteditable="true" 
				spellcheck="false"
			>
				${this.#text}
			</div>
		`;

		return this.textContainer;
	}

	#getSearchContainer(): HTMLElement
	{
		if (!this.#enableSearch)
		{
			return Tag.render``;
		}

		const searchNode = Tag.render`<div class="ui-ctl ui-ctl-after-icon ui-ctl-no-border crm-copilot-textbox__search ${this.#isCollapsed ? '--hidden' : ''}"></div>`;
		const searchBtn = Tag.render`<a class="ui-ctl-after ${CLASS_SEARCH_ICON} crm-copilot-textbox__search-btn"></a>`;
		const searchInput = Tag.render`
			<input 
				type="text" 
				placeholder="${Text.encode(Loc.getMessage('CRM_COPILOT_TEXTBOX_SEARCH_PLACEHOLDER'))}" 
				class="ui-ctl-element ui-ctl-textbox crm-copilot-textbox__search-input"
			>
		`;

		searchInput.oninput = () => {
			this.#resetTextContainer();

			const value = searchInput.value;
			if (!value)
			{
				this.#switchStyle(searchBtn, CLASS_CLEAR_ICON, CLASS_SEARCH_ICON);

				return;
			}
			this.#switchStyle(searchBtn, CLASS_SEARCH_ICON, CLASS_CLEAR_ICON);

			// Highlights pieces of text that are not part of a tag
			const regexp = new RegExp(`((?<!<[^>]*?)(${value})(?![^<]*?>))`, 'gi');
			const textContainer = this.#getTextContainer();

			textContainer.innerHTML = textContainer.innerHTML.replace(regexp, '<span class="search-item">$&</span>');
		};

		let searchInputFocused = false;
		searchInput.onblur = () => {
			if (searchInput.value.length === 0)
			{
				Dom.removeClass(searchNode, 'with-input-node');
				Dom.remove(searchInput);
				searchInputFocused = false;
			}
		};

		searchBtn.onclick = () => {
			if (searchNode.contains(searchInput))
			{
				if (searchInput.value.length > 0)
				{
					searchInput.value = '';
					this.#switchStyle(searchBtn, CLASS_CLEAR_ICON, CLASS_SEARCH_ICON);
					this.#resetTextContainer();
				}

				searchInputFocused = true;
				searchInput.focus();

				return;
			}

			Dom.append(searchInput, searchNode);
			Dom.addClass(searchNode, ['with-input-node']);

			searchInputFocused = true;
			searchInput.focus();
		};

		searchBtn.onmousedown = (event) => {
			if (searchInputFocused)
			{
				event.preventDefault();
			}
		};

		Dom.append(searchBtn, searchNode);

		return searchNode;
	}

	#getAttentionsContainer(): HTMLElement
	{
		if (!Type.isArrayFilled(this.#attentions))
		{
			return Tag.render``;
		}

		const attentionsContainer = Tag.render`<div class="crm-copilot-textbox__attentions"></div>`;

		this.#attentions.forEach((attention) => Dom.append(attention.render(), attentionsContainer));

		return attentionsContainer;
	}

	#resetTextContainer(): void
	{
		this.#getTextContainer().innerHTML = this.#text;
	}

	#switchStyle(node: HTMLElement, fromStyle: string, toStyle: string): void
	{
		if (Dom.hasClass(node, fromStyle) && !Dom.hasClass(node, toStyle))
		{
			Dom.addClass(node, toStyle);
			Dom.removeClass(node, fromStyle);
		}
	}

	#handleCollapse(): void
	{
		this.#isCollapsed = !this.#isCollapsed;

		const rootNode = this.get();
		const wrapperNode = rootNode.querySelector('.crm-copilot-textbox__wrapper');
		const iconNode = rootNode.querySelector('.crm-copilot-textbox__collapse-icon');
		const bodyNode = rootNode.querySelector('.crm-copilot-textbox__body');
		const searchNode = rootNode.querySelector('.crm-copilot-textbox__search');

		// some animation
		Dom.removeClass(bodyNode, 'body-toggle-animation');
		Dom.addClass(bodyNode, 'body-toggle-animation');

		if (this.#isCollapsed)
		{
			Dom.style(rootNode, 'padding-bottom', 0);
			Dom.removeClass(wrapperNode, this.#className.bodyExpanded);
			Dom.addClass(searchNode, this.#className.nodeHidden);

			this.#switchStyle(iconNode, this.#className.arrowTopIcon, this.#className.arrowDownIcon);
		}
		else
		{
			Dom.style(rootNode, 'padding-bottom', ROOT_CONTAINER_BOTTOM_PADDING);
			Dom.addClass(wrapperNode, this.#className.bodyExpanded);
			Dom.removeClass(searchNode, this.#className.nodeHidden);

			this.#switchStyle(iconNode, this.#className.arrowDownIcon, this.#className.arrowTopIcon);
		}
	}
}

export {
	Attention,
	AttentionPresets,
};
