import { Dom, Tag, Type } from 'main.core';

/**
 * @memberOf BX.Crm.Autorun
 */
export class ProgressBarRepository
{
	#container: HTMLElement;

	#storage: Map<string, HTMLElement> = new Map();

	constructor(container: HTMLElement)
	{
		if (!Type.isElementNode(container))
		{
			throw new TypeError('expected element node');
		}

		this.#container = container;
	}

	getOrCreateProgressBarContainer(id: string): HTMLElement
	{
		const fullId = ProgressBarRepository.getFullId(id);

		if (this.#storage.has(fullId))
		{
			return this.#storage.get(fullId);
		}

		let progressBarContainer = this.#container.querySelector(`div#${fullId}`);
		if (!progressBarContainer)
		{
			progressBarContainer = Tag.render`<div id="${fullId}"></div>`;
			Dom.append(progressBarContainer, this.#container);
		}

		this.#storage.set(fullId, progressBarContainer);

		return progressBarContainer;
	}

	static getFullId(id: string): string
	{
		return `crm-autorun-progress-bar-${id}`;
	}
}
