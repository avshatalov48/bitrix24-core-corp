import { Type, Dom, Tag } from 'main.core';

export class MessageBar
{
	#errorContainer: HTMLElement;
	#successContainer: HTMLElement;

	constructor(options)
	{
		this.#errorContainer = Type.isDomNode(options.errorContainer) ? options.errorContainer : null;
		this.#successContainer = Type.isDomNode(options.successContainer) ? options.successContainer : null;

		this.hideAll();
	}

	showError(message: string)
	{
		if (!this.#errorContainer || !Type.isStringFilled(message))
		{
			return;
		}

		Dom.clean(this.#errorContainer);
		Dom.style(this.#errorContainer, 'display', 'block');
		Dom.append(this.#wrapMessage(message), this.#errorContainer);
	}

	#wrapMessage(text: string): HTMLElement
	{
		return Tag.render`<span class="ui-alert-message">${BX.util.htmlspecialchars(text)}</span>`;
	}

	showSuccess(message: string)
	{
		if (!this.#successContainer || !Type.isStringFilled(message))
		{
			return;
		}

		Dom.clean(this.#successContainer);
		Dom.style(this.#successContainer, 'display', 'block');
		Dom.append(this.#wrapMessage(message), this.#successContainer);
	}

	hideAll()
	{
		if (this.#errorContainer)
		{
			Dom.style(this.#errorContainer, 'display', 'none');
			Dom.clean(this.#errorContainer);
		}

		if (this.#successContainer)
		{
			Dom.style(this.#successContainer, 'display', 'none');
			Dom.clean(this.#successContainer);
		}
	}
}