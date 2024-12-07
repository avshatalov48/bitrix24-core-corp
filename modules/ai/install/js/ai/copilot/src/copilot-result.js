import { Tag, Text } from 'main.core';

export class CopilotResult
{
	#container: HTMLElement;
	#rawResult: string;

	render(): HTMLElement
	{
		this.#container = Tag.render`<div class="ai__copilot-result"></div>`;
		this.#rawResult = '';

		return this.#container;
	}

	addResult(result: string): void
	{
		this.#rawResult = result;
		this.#container.innerHTML += String(Text.encode(result).replaceAll(/(\r\n|\r|\n)/g, '<br>'));
	}

	clearResult(): void
	{
		this.#rawResult = '';
		this.#container.innerHTML = '';
	}

	getResult(): string
	{
		return this.#rawResult;
	}
}
