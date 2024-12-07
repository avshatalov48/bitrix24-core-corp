import {Cache, Tag, Dom, Type} from 'main.core';
import {Loader} from 'main.loader';

import './css/style.css';

export class LoadingStatus
{
	#cache = new Cache.MemoryCache();

	constructor({targetContainer}: {targetContainer: HTMLElement})
	{
		if (Type.isDomNode(targetContainer))
		{
			Dom.append(this.getLayout(), targetContainer);
		}
	}

	#getLoader(): Loader
	{
		return this.#cache.remember('loader', () => {
			return new Loader({
				size: 60,
			});
		});
	}

	getLayout(): HTMLDivElement
	{
		return this.#cache.remember('loaderContainer', () => {
			const layout = Tag.render`
				<div class="sign-template-selector-list-item-loader">
					${this.#getProgressLayout()}
				</div>
			`;

			void this.#getLoader().show(layout);

			return layout;
		});
	}

	#getProgressLayout(): HTMLDivElement
	{
		return this.#cache.remember('progressLayout', () => {
			return Tag.render`
				<div class="sign-template-selector-list-item-loader-progress"></div>
			`;
		});
	}

	show()
	{
		Dom.addClass(this.getLayout(), 'sign-template-selector-list-item-loader-show');
	}

	hide()
	{
		Dom.removeClass(this.getLayout(), 'sign-template-selector-list-item-loader-show');
	}

	updateStatus(value: string | number)
	{
		this.#getProgressLayout().textContent = `${value}%`;
	}
}