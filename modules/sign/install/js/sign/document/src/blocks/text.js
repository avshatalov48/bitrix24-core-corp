import { Event, Loc, Tag, Text as TextFormat } from 'main.core';
import Dummy from './dummy';
import Block from "../block";

export default class Text extends Dummy
{
	#textContainer: HTMLElement;

	constructor(block: Block)
	{
		super(block);
		this.setEventNamespace('BX.Sign.Blocks.Text');
	}

	/**
	 * Calls when action button was clicked.
	 */
	onActionClick()
	{
		this.#textContainer.contentEditable = true;
		this.#textContainer.focus();
	}

	/**
	 * Calls when typing in text container.
	 */
	#onKeyUp(event): void
	{
		this.setText(this.#textContainer.innerText.replaceAll("\n", '[br]'));
		this.onChange();
	}

	getText(): string
	{
		return this.data.text;
	}

	getContainer(): HTMLElement
	{
		return this.#textContainer;
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement}
	 */
	getViewContent(): HTMLElement
	{
		const content = this.data.text === Loc.getMessage('SIGN_JS_DOCUMENT_TEXT_PLACEHOLDER')
			? ''
			: this.data.text
		;

		this.#textContainer = Tag.render`
			<div class="sign-document-block-text" placeholder="${Loc.getMessage('SIGN_JS_DOCUMENT_TEXT_PLACEHOLDER')}">
				${TextFormat.encode(content).replaceAll('[br]', '<br>')}
			</div>
		`;

		Event.bind(this.#textContainer, 'keyup', this.#onKeyUp.bind(this));
		Event.bind(this.#textContainer, 'paste', event => setTimeout(this.#onPaste.bind(this, event)));

		return this.#textContainer;
	}

	#onPaste(event: ClipboardEvent): void
	{
		this.#textContainer.innerHTML = this.#textContainer.innerText.replaceAll('\n', '<br>');

		const range = document.createRange();
		range.selectNodeContents(this.#textContainer);
		range.collapse(false)

		const selection = document.getSelection();
		selection.removeAllRanges();
		selection.addRange(range);

		this.setText(
			this.#textContainer
				.innerHTML
				.replaceAll('<br>', '[br]'),
		);
		this.onChange();
	}

	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...Text.defaultTextBlockPaddingStyles };
	}
}