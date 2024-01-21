import {Tag} from "main.core";
import {Loader} from 'main.loader';

export class LoaderPage
{
	static #wrapper: HTMLElement;

	static getWrapper()
	{
		if (LoaderPage.#wrapper)
		{
			return LoaderPage.#wrapper;
		}

		LoaderPage.#wrapper = Tag.render`
			<div class="intranet-settings__loader"></div>
		`;
		// const loader = new Loader({target: LoaderPage.#wrapper, size: 200});
		// loader.show();

		return LoaderPage.#wrapper;
	}
}
