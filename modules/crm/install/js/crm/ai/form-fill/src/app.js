import { BitrixVue } from 'ui.vue3';
import { createStore } from 'ui.vue3.vuex';
import { Main } from './components/main';
import { EntityEditorProxy } from './services/entity-editor-proxy';
import store from './store/index';

export let entityEditorProxy: ?EntityEditorProxy = null;

export class AiFormFillApplication
{
	#application;

	#options;

	#store;

	constructor(rootNode, options = {})
	{
		this.#options = options;
		this.rootNode = document.querySelector(`#${rootNode}`);

		if (!this.#options.mergeUuid)
		{
			throw new Error('param mergeUuid is required');
		}
	}

	get application(): BitrixVue
	{
		return this.#application;
	}

	get store(): any
	{
		return this.#store;
	}

	start(): void
	{
		entityEditorProxy = new EntityEditorProxy();
		this.#store = createStore(store());
		this.#application = BitrixVue.createApp({
			name: 'AiFormFill',
			components: { Main },
			beforeCreate(): void
			{
				this.$bitrix.Application.set(this);
			},
			template: `
				<Main/>
			`,
		});

		this.#store.commit('setMergeUUID', this.#options.mergeUuid);
		this.#store.commit('setActivityId', this.#options.activityId);
		this.#store.commit('setActivityDirection', this.#options.activityDirection);
		this.#application.use(this.#store);
		this.#application.mount(this.rootNode);
	}

	stop(): void
	{
		this.#application.unmount();
		this.#application = null;
		this.#store = null;
		entityEditorProxy = null;
	}

	isNeededShowCloseConfirm(): boolean
	{
		return this.#store.getters.isNeededShowCloseConfirm;
	}

	showCloseConfirm(): void {
		this.#store.commit('setIsConfirmPopupShow', true);
	}

	isShowAiFeedbackBeforeClose(): boolean {
		return this.#store.getters.isAiFeedbackShowBeforeClose;
	}

	showAiFeedbackBeforeClose(): void {
		this.#store.dispatch('showFeedbackMessageBeforeClose');
	}

	isAppLoading(): boolean {
		return this.#store.getters.isLoading;
	}
}
