import { BitrixVue } from 'ui.vue3';
import { Core, type BookingParams } from 'booking.core';
import { locMixin } from 'booking.component.mixin.loc-mixin';
import { App } from './components/app';

export class Booking
{
	constructor(params: BookingParams)
	{
		Core.setParams(params);

		void this.#mountApplication();
	}

	async #mountApplication(): Promise<void>
	{
		await Core.init();

		const application = BitrixVue.createApp(App, Core.getParams());

		application.mixin(locMixin);
		application.use(Core.getStore());
		application.mount(Core.getParams().container);
	}
}
