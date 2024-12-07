import { Dom, Event } from 'main.core';
import Block from './block';
import UI from './ui';

type StyleOptions = {
	block: Block,
	data: {[key: string]: string}
};

export default class Style
{
	#block: Block;

	#buttons = {
		family: {
			property: 'fontFamily',
			value: null
		},
		size: {
			property: 'fontSize',
			value: 14
		},
		color: {
			property: 'color',
			value: null
		},
		bold: {
			button: null,
			property: 'fontWeight',
			value: 'bold',
			state: false
		},
		italic: {
			button: null,
			property: 'fontStyle',
			value: 'italic',
			state: false
		},
		underline: {
			button: null,
			property: 'textDecoration',
			value: 'underline',
			state: false
		},
		through: {
			button: null,
			property: 'textDecoration',
			value: 'line-through',
			state: false
		},
		left: {
			button: null,
			property: 'textAlign',
			value: 'left',
			state: false,
			group: 'align'
		},
		center: {
			button: null,
			property: 'textAlign',
			value: 'center',
			state: false,
			group: 'align'
		},
		right: {
			button: null,
			property: 'textAlign',
			value: 'right',
			state: false,
			group: 'align'
		},
		justify: {
			button: null,
			property: 'textAlign',
			value: 'justify',
			state: false,
			group: 'align'
		}
	};

	#style = {
		buttonPressed: 'sign-document__block-style--button-pressed'
	};

	/**
	 * Constructor.
	 * @param {StyleOptions} options
	 */
	constructor(options: StyleOptions)
	{
		this.#block = options.block;

		if (options.data)
		{
			this.#applyData(options.data);
		}
	}

	/**
	 * Handle on panel button click.
	 * @param {string} code
	 */
	#onPressButton(code: string)
	{
		this.#buttons[code]['state'] = !this.#buttons[code]['state'];

		if (this.#buttons[code]['state'])
		{
			Dom.addClass(this.#buttons[code]['button'], this.#style.buttonPressed);

			if (this.#buttons[code]['group'])
			{
				const group = this.#buttons[code]['group'];

				[...Object.keys(this.#buttons)].map(key => {
					if (key !== code && this.#buttons[key]['group'] === group)
					{
						this.#buttons[key]['state'] = false;
						Dom.removeClass(this.#buttons[key]['button'], this.#style.buttonPressed);
					}
				});
			}
		}
		else
		{
			Dom.removeClass(this.#buttons[code]['button'], this.#style.buttonPressed);
		}

		this.#block.renderStyle();
	}

	/**
	 * Applies initiated data to current state.
	 * @param {{[key: string]: string}} data
	 */
	#applyData(data: {[key: string]: string})
	{
		[...Object.keys(this.#buttons)].map(key => {
			const property = this.#buttons[key]['property'];

			if (data[property])
			{
				if (typeof this.#buttons[key]['state'] !== 'undefined')
				{
					if (data[property].indexOf(this.#buttons[key]['value']) !== -1)
					{
						this.#buttons[key]['state'] = true;
					}
				}
				else
				{
					this.#buttons[key]['value'] = data[property];
				}
			}
		});
	}

	/**
	 * Applies collected styles to the element.
	 * @param {HTMLElement} element
	 */
	applyStyles(element: HTMLElement)
	{
		element.removeAttribute('style');
		Dom.style(element, this.collectStyles());
	}

	updateFontSize(fontSize)
	{
		if (fontSize)
		{
			this.#buttons.size['property'] = 'fontSize';
			this.#buttons.size['value'] = fontSize;
		}
	}

	/**
	 * Collects checked styles in one dataset.
	 * @return {{[key: string]: string}}
	 */
	collectStyles(): {[key: string]: string}
	{
		const styles = {};

		[...Object.keys(this.#buttons)].map(key => {
			if (this.#buttons[key]['state'] || (typeof this.#buttons[key]['state'] === 'undefined'))
			{
				const property = this.#buttons[key]['property'];
				const value = this.#buttons[key]['value'];

				if (value === null)
				{
					return;
				}

				if (this.#buttons[key]['group'])
				{
					styles[property] = value;
				}
				else
				{
					styles[property] = (styles[property] ? styles[property] + ' ' : '') + value;
				}
			}
		});

		return styles;
	}

	/**
	 * Returns style panel layout.
	 * @return {HTMLElement}
	 */
	getLayout(): HTMLElement
	{
		const layout = UI.getStylePanel(
			(code: string, value: string) => {
				this.#buttons[code]['value'] = value;
				this.#block.renderStyle();
			},
			this.collectStyles()
		);

		[...layout.querySelectorAll('[data-action]')].map(button => {
				const action = button.getAttribute('data-action');

			if (this.#buttons[action])
			{
				Event.bind(button, 'click', () => this.#onPressButton(action));
				this.#buttons[action]['button'] = button;

				if (this.#buttons[action]['state'])
				{
					Dom.addClass(this.#buttons[action]['button'], this.#style.buttonPressed);
				}
			}
		});

		return layout;
	}

	updateColor(color: string): void
	{
		this.#buttons.color.value = color;
	}
}
