import type { DisplayStrategy } from 'crm.copilot.call-assessment-selector';
import { Dom, Tag } from 'main.core';

export class ScriptSelectorDisplayStrategy implements DisplayStrategy
{
	#container: HTMLElement;
	titleNode: HTMLElement;
	innerTitleNode: HTMLElement;
	#isLoading: boolean = false;

	constructor()
	{
		this.#container = this.#createContainer();
	}

	getTargetNode(): HTMLElement
	{
		return this.titleNode;
	}

	updateTitle(title: string): void
	{
		this.innerTitleNode.innerText = title;
		this.innerTitleNode.title = title;
	}

	setLoading(isLoading: boolean): void
	{
		if (this.#isLoading === isLoading)
		{
			return;
		}

		this.#isLoading = isLoading;
		Dom.toggleClass(this.#container, '--loading');
	}

	#createContainer(): HTMLElement
	{
		this.innerTitleNode = Tag.render`<span></span>`;
		this.titleNode = Tag.render`
			<div class="call-quality__script-selector">
				${this.innerTitleNode}
			</div>
		`;

		return this.titleNode;
	}
}
