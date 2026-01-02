import { Dom } from 'main.core';
import { Popup } from 'main.popup';
import { BitrixVue, type VueCreateAppResult } from 'ui.vue3';
import { locMixin } from 'ui.vue3.mixins.loc-mixin';

import { CyclePopupContent } from './cycle-popup-content';
import { meta } from './meta';

export type Params = {
	context: string,
	scrollToCard: string,
};

export class CyclePopupOpener
{
	#application: ?VueCreateAppResult = null;

	show(params: Params): void
	{
		new Popup({
			...meta.popupOptions,
			events: {
				onAfterPopupShow: (popup: Popup): void => {
					this.#application = BitrixVue.createApp(CyclePopupContent, params);

					this.#application.mixin(locMixin);
					const { $el } = this.#application.mount(Dom.create('div'));
					popup.getContentContainer().replaceWith($el);
				},
				onPopupAfterClose: (): void => {
					this.#application.unmount();
				},
			},
		}).show();
	}
}

export const cyclePopupOpener = new CyclePopupOpener();
