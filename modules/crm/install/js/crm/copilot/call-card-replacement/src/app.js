import store from './store';
import { BitrixVue } from 'ui.vue3';
import { Main } from './components/main';
import { VueCreateAppResult } from 'ui.vue';
import { createStore } from 'ui.vue3.vuex';

export type ReplacementOptions = {
	callId: string,
	callAssessment?: {
		id: number,
		title: string,
		prompt: string,
	},
	hasAvailableSelectorItems: boolean,
};

export class CallCardReplacementApp
{
	#application: VueCreateAppResult = null;

	constructor(options: ReplacementOptions)
	{
		this.#application = BitrixVue.createApp({
			name: 'CrmCopilotCallCardReplacementApp',
			components: { Main },
			template: `
				<Main />
			`,
		});

		const storage = createStore(store());
		storage.commit('initializeApplicationState', options);

		this.#application.use(storage);
	}

	mount(target: Element | string): void
	{
		this.#application.mount(target);
	}
}
