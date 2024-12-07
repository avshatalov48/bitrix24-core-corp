import { Tag, bind, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { getCursorPosition, setCursorPosition } from './textarea-helpers';

export class CopilotInputFieldTextarea extends EventEmitter
{
	#container: HTMLElement;

	constructor(options)
	{
		super(options);

		this.value = '';

		this.setEventNamespace('AI.CopilotInputFieldTextarea');
	}

	getContainer(): HTMLElement
	{
		return this.#container;
	}

	render(): HTMLElement
	{
		this.#container = Tag.render`
			<div
				class="ai__copilot_input"
				contenteditable="true"></div>
		`;

		const observer = new MutationObserver(this.#observeRemovingStrongTagAfterDeletingBracket.bind(this));

		observer.observe(this.#container, {
			childList: true,
			subtree: true,
			characterDataOldValue: true,
		});

		bind(this.#container, 'input', this.#handleInputEvent.bind(this));
		bind(this.#container, 'paste', this.#handlePasteEvent.bind(this));
		bind(this.#container, 'focus', (e) => {
			this.emit('focus');
		});

		return this.#container;
	}

	#observeRemovingStrongTagAfterDeletingBracket(mutations: MutationRecord[]): void
	{
		for (const mutation of mutations)
		{
			if (mutation.target.parentElement?.tagName !== 'STRONG')
			{
				continue;
			}

			const nodeText = mutation.target.nodeValue || '';
			const openBracketPosition = [...nodeText].indexOf('[');
			const closeBracketPosition = [...nodeText].indexOf(']');

			if (closeBracketPosition === -1 || openBracketPosition === -1)
			{
				const pos = this.getCursorPosition();
				mutation.target.parentElement.replaceWith(...mutation.target.parentElement.childNodes);
				this.setCursorPosition(pos);
			}
		}
	}

	set value(text: string): void
	{
		if (this.#container)
		{
			this.#container.innerText = text;
		}
	}

	get value(): string
	{
		return this.#container.innerText;
	}

	get disabled(): boolean
	{
		return this.#container?.getAttribute('contenteditable') === 'false';
	}

	set disabled(disabled: boolean): void
	{
		if (disabled === false)
		{
			Dom.attr(this.#container, 'contenteditable', true);
		}
		else
		{
			Dom.attr(this.#container, 'contenteditable', false);
		}
	}

	focus(setCursorAtStart: boolean): void
	{
		const cursorPosition = setCursorAtStart ? 0 : 999;

		this.#container?.focus();
		this.setCursorPosition(cursorPosition);
	}

	getComputedStyle(): CSSStyleDeclaration
	{
		return getComputedStyle(this.#container);
	}

	addClass(className: string): void
	{
		Dom.addClass(this.#container, className);
	}

	removeClass(className: string): void
	{
		Dom.removeClass(this.#container, className);
	}

	setStyle(prop: string, value: string): void
	{
		Dom.style(this.#container, prop, value);
	}

	get scrollHeight(): number
	{
		return this.#container?.scrollHeight || 0;
	}

	isCursorInTheEnd(): boolean
	{
		const pos = this.getCursorPosition();
		const contentLength = this.#container.innerText.length;

		return pos >= contentLength;
	}

	getCursorPosition(): number
	{
		return getCursorPosition(this.#container);
	}

	setCursorPosition(position): void
	{
		setCursorPosition(this.#container, position);
	}

	setHtmlContent(html: string)
	{
		this.#container.innerHTML = html;

		this.focus();

		this.emit('input', this.value);
	}

	#handlePasteEvent(e): void
	{
		e.preventDefault();
		const text = e.clipboardData.getData('text/plain');
		document.execCommand('insertText', false, text);
	}

	#handleInputEvent(e): void
	{
		if (e.inputType === 'deleteContentBackward' || e.inputType === 'deleteWordBackward' || e.inputType === 'deleteContentForward')
		{
			this.emit('input', this.value);

			return;
		}

		const selection = window.getSelection();
		const cursorPosition = this.getCursorPosition();

		if (
			selection.anchorNode.parentElement?.tagName === 'STRONG'
			&& selection.focusOffset === selection.anchorNode.length
			&& selection.anchorNode.textContent.at(0) === '['
		)
		{
			let nextNode = selection.anchorNode.parentElement.nextSibling;

			if (!nextNode || nextNode.nodeName === 'BR')
			{
				nextNode = document.createTextNode(' ');
				Dom.insertAfter(nextNode, selection.anchorNode.parentElement);
			}

			selection.anchorNode.textContent = selection.anchorNode.textContent.slice(0, -e.data.length);
			nextNode.textContent = e.data + nextNode.textContent;
			this.setCursorPosition(cursorPosition);

			e.preventDefault();
			e.stopPropagation();

			return;
		}

		this.emit('input', this.value);
	}
}
