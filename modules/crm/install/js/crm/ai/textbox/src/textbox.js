import { Dom, Loc, Tag, Type, Text, Event } from 'main.core';
import 'ui.design-tokens';
import 'ui.fonts.opensans';
import './style.css';

export class Textbox
{
	textContainerID = 'crm-copilot-text-container';

	searchIcon = 'ui-ctl-icon-search';
	clearIcon = 'ui-ctl-icon-clear';

	searchInputPlaceholder = Loc.getMessage('CRM_COPILOT_TEXTBOX_SEARCH_PLACEHOLDER');

	constructor(options = {}): void
	{
		this.setText(options.text);
		this.title = (Type.isString(options.title)) ? options.title : '';
		this.enableSearch = Type.isBoolean(options.enableSearch) ? options.enableSearch : true;
		this.previousTextContent = Type.isElementNode(options.previousTextContent) ? options.previousTextContent : null;
	}

	setText(text)
	{
		this.text = (Type.isString(text)) ? this.prepareText(text) : '';
	}

	prepareText(text): String
	{
		return text.replaceAll(/\r?\n/g, '<br>');
	}

	render(): void
	{
		this.rootContainer = Tag.render`<div class="crm-copilot-textbox"></div>`;

		Dom.append(this.getHeaderContainer(), this.rootContainer);
		Dom.append(this.getPreviousTextContainer(), this.rootContainer);
		Dom.append(this.getContentContainer(), this.rootContainer);
	}

	get(): HTMLElement
	{
		return this.rootContainer;
	}

	getContentContainer(): HTMLElement
	{
		const contentContainer = Tag.render`<div class="crm-copilot-textbox__content"></div>`;
		const textContainer = this.getTextContainer();

		Event.bind(textContainer, 'beforeinput', (e) => {
			e.preventDefault();
		});

		Dom.append(textContainer, contentContainer);

		return contentContainer;
	}

	getTextContainer(): HTMLElement
	{
		if (this.textContainer)
		{
			return this.textContainer;
		}

		this.textContainer = Tag.render`
			<div 
				id="${this.textContainerID}" 
				class="crm-copilot-textbox__text-container" 
				contenteditable="true" 
				spellcheck="false"
			>
				${this.text}
			</div>
		`;

		return this.textContainer;
	}

	getHeaderContainer(): HTMLElement
	{
		return Tag.render`
			<div class="crm-copilot-textbox__header">
				<div class="crm-copilot-textbox__title">${Text.encode(this.title)}</div>
				${this.getSearchContainer()}
			</div>
		`;
	}

	getSearchContainer(): HTMLElement
	{
		if (!this.enableSearch)
		{
			return Tag.render``;
		}

		const searchNode = Tag.render`<div class="ui-ctl ui-ctl-after-icon ui-ctl-no-border crm-copilot-textbox__search"></div>`;
		const searchBtn = Tag.render`<a class="ui-ctl-after ${this.searchIcon} crm-copilot-textbox__search-btn"></a>`;
		const searchInput = Tag.render`
			<input 
				type="text" 
				placeholder="${Text.encode(this.searchInputPlaceholder)}" 
				class="ui-ctl-element ui-ctl-textbox crm-copilot-textbox__search-input"
			>
		`;

		searchInput.oninput = () => {
			this.resetTextContainer();

			const value = searchInput.value;
			if (!value)
			{
				this.switchStyle(searchBtn, this.clearIcon, this.searchIcon);

				return;
			}
			this.switchStyle(searchBtn, this.searchIcon, this.clearIcon);

			// Highlights pieces of text that are not part of a tag
			const regexp = new RegExp(`((?<!<[^>]*?)(${value})(?![^<]*?>))`, 'gi');
			const textContainer = this.getTextContainer();

			textContainer.innerHTML = textContainer.innerHTML.replace(regexp, '<span class="search-item">$&</span>');
		};

		let searchInputFocused = false;
		searchInput.onblur = (event) => {
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
					this.switchStyle(searchBtn, this.clearIcon, this.searchIcon);
					this.resetTextContainer();
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

	getPreviousTextContainer(): HTMLElement
	{
		return Tag.render`<div class="crm-copilot-textbox__previous-text">${this.previousTextContent}</div>`;
	}

	resetTextContainer(): void
	{
		this.getTextContainer().innerHTML = this.text;
	}

	switchStyle(node, fromStyle, toStyle): void
	{
		if (Dom.hasClass(node, fromStyle) && !Dom.hasClass(node, toStyle))
		{
			Dom.addClass(node, toStyle);
			Dom.removeClass(node, fromStyle);
		}
	}
}
