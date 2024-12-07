import { Router } from 'crm.router';
import { Type } from 'main.core';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { createStore } from 'ui.vue3.vuex';
import { Main } from './components/main';
import { store } from './store/index';

export class App
{
	#container: HTMLElement;
	#initialActiveTabId: string;
	#initialState: ?Object;

	#app: ?VueCreateAppResult = null;

	constructor({ containerId, activeTabId, state })
	{
		this.#container = document.getElementById(containerId);

		if (!Type.isDomNode(this.#container))
		{
			throw new Error('container not found');
		}

		this.#initialActiveTabId = String(activeTabId);

		if (Type.isPlainObject(state))
		{
			this.#initialState = state;
		}
	}

	start(): void
	{
		// eslint-disable-next-line unicorn/no-this-assignment
		const appWrapperRef = this;

		this.#app = BitrixVue.createApp(
			{
				...Main,
				beforeCreate(): void
				{
					this.$bitrix.Application.set(appWrapperRef);
				},
			},
			{
				initialActiveTabId: this.#initialActiveTabId,
			},
		);

		const vuexStore = createStore(store);
		if (this.#initialState)
		{
			vuexStore.dispatch('setState', this.#initialState);
		}

		this.#app.use(vuexStore);

		this.#app.mount(this.#container);
	}

	stop(): void
	{
		this.#app.unmount();

		this.#app = null;
	}

	reloadWithNewUri(automatedSolutionId: number, queryParams: Object = {}): never
	{
		setTimeout(() => {
			const uri = Router.Instance.getAutomatedSolutionDetailUrl(automatedSolutionId);

			uri.setQueryParams({
				...queryParams,
				IFRAME: 'Y',
				IFRAME_TYPE: 'SIDE_SLIDER',
			});

			window.location.href = uri.toString();
		});
	}

	closeSliderOrRedirect(): never
	{
		setTimeout(() => {
			Router.Instance.closeSliderOrRedirect(
				Router.Instance.getAutomatedSolutionListUrl(),
				window,
			);
		});
	}
}
