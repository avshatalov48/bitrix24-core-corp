import { Extension, Type, Uri } from 'main.core';
import { EventEmitter } from 'main.core.events';
import type { StructureOpenOptions } from './types';

export class Structure
{
	static open(options: StructureOpenOptions = {}): void
	{
		const settings = Extension.getSettings('humanresources.company-structure.public');
		const baseUrl = settings.get('url');
		if (!baseUrl)
		{
			return;
		}

		if (!this.#isCurrentPageStructureView(baseUrl))
		{
			const url = (new Uri(baseUrl));
			this.#appendParamsFromOptions(url, options);

			this.#openSlider(url);

			return;
		}

		this.#handleInPlaceStructureNavigation(options);
	}

	static #isCurrentPageStructureView(url: string): boolean
	{
		return window.location.href.includes(url);
	}

	static #appendParamsFromOptions(url: Uri, options: StructureOpenOptions): Uri
	{
		if (Type.isInteger(options.focusNodeId))
		{
			url.setQueryParam('focusNodeId', options.focusNodeId);
		}

		return url;
	}

	static #handleInPlaceStructureNavigation(options: StructureOpenOptions): void
	{
		if (options.focusNodeId)
		{
			EventEmitter.emit('HumanResources.CompanyStructure:focusNode', {
				nodeId: options.focusNodeId,
			});
		}
	}

	static #openSlider(url: Uri): void
	{
		top.BX.SidePanel.Instance.open(url.toString());
	}
}
