import { Dom, Event, Loc, Tag, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Copilot } from 'ai.copilot';

import { getCaretCoordinates } from './textarea-caret-coordinates';

import 'ui.design-tokens';
import './copilot-textarea.css';

declare type CopilotTextareaParams = {
	id: string,
	target: HTMLElement,
	copilotParams: Object,
	isDebugEnabled: ?boolean,
};

declare type ShowCopilotParams = {
	context: string,
	selectedText: string,
};

const COPILOT_BUTTON_WIDTH = 80;
const COPILOT_BUTTON_HEIGHT = 32;
const COPILOT_RESULT_TEXT_WRAP_LEFT = '<<<';
const COPILOT_RESULT_TEXT_WRAP_RIGHT = '>>>';

export const Events = {
	EVENT_VALUE_CHANGE: 'crm:ai:copilot-textarea:value-change',
};

export class CopilotTextarea
{
	#id: string;
	#copilot: any;
	#element: HTMLElement;
	#isDebugEnabled: boolean = false;
	#copilotLoaded: boolean = false;
	#copilotBtnPopup: ?Popup = null;
	#currentSelectedText: string = '';

	constructor(params: CopilotTextareaParams)
	{
		this.#assertValidParams(params);

		this.#id = params.id;
		this.#element = params.target;
		this.#copilot = new Copilot(params.copilotParams); // @see CopilotOptions [ai/install/js/ai/copilot/src/copilot.js]
		this.#isDebugEnabled = params.isDebugEnabled || false;

		this.#bindHandlers();
		this.#copilot.init();

		Event.bind(this.#element, 'keydown', (event: BaseEvent) => this.#handleKeyDown(event));
		Event.bind(this.#element, 'select', (event: BaseEvent) => this.#handleSelect(event));
	}

	getId(): string
	{
		return this.#id;
	}

	setReadOnly(flag: boolean = true): void
	{
		// NOTE: Dom.attr method NOT WORKED, so use setAttribute/removeAttribute
		if (flag)
		{
			this.#element.setAttribute('readonly', 1);
		}
		else
		{
			this.#element.removeAttribute('readonly');
		}
	}

	#showCopilot(params: ShowCopilotParams): void
	{
		const coordinates = this.#getElementCoordinates();
		if (!coordinates)
		{
			return;
		}

		const context = params.context || '';
		const selectedText = params.selectedText || '';

		this.#copilot.setContext(context);
		this.#copilot.setSelectedText(selectedText);
		this.#copilot.show({
			bindElement: coordinates,
			width: this.#element.offsetWidth - 10,
		});

		this.#copilot.subscribe('cancel', (event) => {
			this.#logEventInfo('CoPilot canceled', event);

			this.#cleanWrappedText();
			this.#copilot.adjust({
				hide: false,
				position: this.#getElementCoordinates(),
			});
		});

		const handleKeyUpEscape = this.#handleKeyUpEscape.bind(this);
		this.#copilot.subscribe('hide', (event) => {
			this.#logEventInfo('CoPilot hidden', event);

			Event.unbind(window, 'keyup', handleKeyUpEscape);
		});
		Event.bind(window, 'keyup', handleKeyUpEscape);
	}

	#showCopilotButton(): void
	{
		const copilotButton = Tag.render`
			<button class="show-copilot-btn">
				<div class="show-copilot-btn-icon ui-icon-set --copilot-ai"></div>
				${Loc.getMessage('CRM_COMMON_COPILOT').toUpperCase()}
			</button>
		`;

		Event.bind(copilotButton, 'click', (event) => {
			this.#showCopilot({
				context: this.#getTextAreaValue(),
				selectedText: this.#currentSelectedText,
			});
			this.#copilotBtnPopup.close();
		});

		const coordinates = this.#getElementCoordinates();

		this.#copilotBtnPopup = new Popup({
			id: `copilot_textarea_popup_button_${Text.getRandom(5)}`,
			content: copilotButton,
			bindElement: {
				top: coordinates.top - (COPILOT_BUTTON_HEIGHT / 2),
				left: coordinates.left + (this.#element.offsetWidth / 2 - COPILOT_BUTTON_WIDTH / 2),
			},
			padding: 5,
			borderRadius: '4px',
		});

		Event.bind(document, 'keyup', (event) => {
			this.#cleanWrappedText();

			this.#copilotBtnPopup.close();
		});

		Event.bind(copilotButton, 'click', () => {
			this.#copilotBtnPopup.close();
		});

		setTimeout(() => {
			Event.bind(window, 'mouseup', (event) => {
				this.#copilotBtnPopup.close();
			});
		}, 100);

		this.#copilotBtnPopup.show();
	}

	// region Handlers
	#bindHandlers(): void
	{
		this.#copilot.subscribe('start-init', (event) => {
			this.#logEventInfo('CoPilot load start', event);
			this.setReadOnly();
		});

		this.#copilot.subscribe('finish-init', (event) => {
			this.#logEventInfo('CoPilot loaded', event);
			this.#copilotLoaded = true;
			this.setReadOnly(false);
		});

		this.#copilot.subscribe('aiResult', (event) => {
			this.#logEventInfo('CoPilot result received', event);

			let newValue = '';
			if (Type.isStringFilled(this.#currentSelectedText))
			{
				const start = this.#element.selectionStart;
				const end = this.#element.selectionEnd;
				const allText = this.#getTextAreaValue();

				newValue = allText.slice(0, Math.max(0, start))
					+ this.#wrapText(event.data.result)
					+ allText.slice(end, allText.length)
				;
			}
			else
			{
				newValue = this.#getTextAreaValue() + this.#wrapText(event.data.result);
			}

			this.#setTextAreaValue(newValue);
			this.#copilot.adjust({
				hide: false,
				position: this.#getElementCoordinates(),
			});
		});

		this.#copilot.subscribe('save', (event) => {
			this.#logEventInfo('CoPilot result saved', event);

			this.#replaceSelectionText(event.data.result);
			this.#cleanWrapChars();
			this.#copilot.hide();
		});

		this.#copilot.subscribe('add_below', (event) => {
			this.#logEventInfo('CoPilot result text place below', event);

			const currentText = this.#getTextAreaValue();
			this.#setTextAreaValue(`${currentText}\n${event.data.result}`);
			this.#copilot.hide();
		});
	}

	#handleKeyDown(event: BaseEvent): void
	{
		const isSpacePressed = event.key === ' ' || event.code === 'Space';

		if (
			!isSpacePressed
			|| !this.#copilotLoaded
			|| this.#copilot.isShown()
			|| !this.#isCursorAtBeginningOfLine()
		)
		{
			return;
		}

		this.#logEventInfo('Space pressed', event);

		this.#showCopilot({
			context: this.#getTextAreaValue(),
			selectedText: '',
		});

		event.preventDefault();
	}

	#handleSelect(event: BaseEvent): void
	{
		const target = event.target;
		if (!target)
		{
			return;
		}

		if (this.#copilotBtnPopup?.isShown())
		{
			return;
		}

		this.#currentSelectedText = target.value.slice(target.selectionStart, target.selectionEnd);
		if (Type.isStringFilled(this.#currentSelectedText))
		{
			this.#logEventInfo('Text selected', event);

			setTimeout(() => this.#showCopilotButton(), 100);
		}
	}

	#handleKeyUpEscape(event: BaseEvent): void
	{
		if (event.key === 'Escape' && this.#copilot.isShown())
		{
			this.#cleanWrapChars();
			this.#copilot.hide();
			this.#element.focus();
		}
	}
	// endregion

	// region Utils
	#assertValidParams(params: CopilotTextareaParams): void
	{
		if (!Type.isPlainObject(params))
		{
			throw new TypeError('BX.Crm.AI.CopilotTextarea: The CoPilot textarea params must be object');
		}

		if (!Type.isStringFilled(params.id))
		{
			throw new TypeError('BX.Crm.AI.CopilotTextarea: The "id" argument must be filled');
		}

		if (!Type.isDomNode(params.target))
		{
			throw new Error('BX.Crm.AI.CopilotTextarea: The "target" argument must be DOM node');
		}

		if (params.target.tagName.toLowerCase() !== 'textarea')
		{
			throw new Error('BX.Crm.AI.CopilotTextarea: The "target" argument must be textarea element');
		}
	}

	#isCursorAtBeginningOfLine(): boolean
	{
		const val = this.#getTextAreaValue();
		const element = this.#getElement();
		const currentLineIndex = val.lastIndexOf('\n', element.selectionStart - 1) + 1;

		return !Type.isStringFilled(val.slice(currentLineIndex, element.selectionStart));
	}

	#getTextAreaValue(): string
	{
		return this.#getElement().value;
	}

	#setTextAreaValue(value: string): void
	{
		if (this.#getElement().value === value)
		{
			return;
		}

		this.#getElement().value = value;
		Dom.style(this.#element, 'height', 'auto');

		const currentTextareaHeight = this.#getElement().scrollHeight;
		Dom.style(this.#element, 'height', `${currentTextareaHeight}px`);

		EventEmitter.emit(this, Events.EVENT_VALUE_CHANGE, { id: this.#id, value });
	}

	#getElementCoordinates(pressToLeft: boolean = true): ?Coordinates
	{
		const elementRect = this.#element.getBoundingClientRect();
		if (
			elementRect.top === 0
			&& elementRect.right === 0
			&& elementRect.bottom === 0
			&& elementRect.left === 0
		)
		{
			return null;
		}

		const coordinates = getCaretCoordinates(this.#element, this.#element.selectionEnd);

		return {
			left: pressToLeft
				? elementRect.left + window.scrollX + 5
				: elementRect.left + window.scrollX + coordinates.left + 2,
			top: elementRect.top + window.scrollY + coordinates.top + 21,
		};
	}

	#wrapText(text: string): string
	{
		return COPILOT_RESULT_TEXT_WRAP_LEFT + text + COPILOT_RESULT_TEXT_WRAP_RIGHT;
	}

	#cleanWrappedText(): void
	{
		const re = new RegExp(
			`${COPILOT_RESULT_TEXT_WRAP_LEFT}(.*)${COPILOT_RESULT_TEXT_WRAP_RIGHT}`,
			'gs',
		);

		this.#setTextAreaValue(this.#getTextAreaValue().replaceAll(re, ''));
	}

	#cleanWrapChars(): void
	{
		const wrapLeftRe = new RegExp(COPILOT_RESULT_TEXT_WRAP_LEFT, 'gs');
		const wrapRightRe = new RegExp(COPILOT_RESULT_TEXT_WRAP_RIGHT, 'gs');

		this.#setTextAreaValue(
			this.#getTextAreaValue()
				.replaceAll(wrapLeftRe, '')
				.replaceAll(wrapRightRe, ''),
		);
	}

	#replaceSelectionText(text: string): void
	{
		if (
			Type.isStringFilled(this.#currentSelectedText)
			&& Type.isStringFilled(text)
		)
		{
			this.#setTextAreaValue(this.#getTextAreaValue().replace(this.#currentSelectedText, text));
		}
	}

	#getElement(): HTMLElement
	{
		return BX(this.#element);
	}

	#logEventInfo(name: string, event: BaseEvent): void
	{
		if (this.#isDebugEnabled)
		{
			// eslint-disable-next-line no-console
			console.debug(name, event);
		}
	}
	// endregion
}
