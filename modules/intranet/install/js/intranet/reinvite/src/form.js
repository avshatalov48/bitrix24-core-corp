import { Tag } from 'main.core';
export class Form
{
	#id: string;
	#value: string;
	#userId: number;
	#content: HTMLElement;
	#formNode: HTMLElement;

	constructor(options)
	{
		this.#id = `form-${options.id}`;
		this.#value = options.inputValue;
		this.#userId = options.userId;
	}
	getTitleRender(): HTMLElement
	{
		return new HTMLElement();
	}

	getFieldRender(): HTMLElement
	{
		return new HTMLElement();
	}

	getFormNode(): HTMLElement
	{
		return this.render().querySelector('form#' + this.#id);
	}

	render(): HTMLElement
	{
		if (this.#content)
		{
			return this.#content;
		}
		this.#content = Tag.render`
		<div class="intranet-reinvite-popup-wrapper">
			<form method="POST" id="${this.#id}">
				<input type="hidden" name="userId" value="${this.#userId}">
				${this.getTitleRender()}
				${this.getFieldRender()}
			</form>
		</div>`;

		return this.#content;
	}

	getValue(): string
	{
		return this.#value;
	}

	getData(): FormData
	{
		return new FormData(this.getFormNode());
	}
}
