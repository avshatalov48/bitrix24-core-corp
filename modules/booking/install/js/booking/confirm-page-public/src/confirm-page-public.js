import { BitrixVue } from 'ui.vue3';
import { BookingModel } from 'booking.model.bookings';
import { locMixin } from 'booking.component.mixin.loc-mixin';
import { App } from './components/app';

export type ConfirmPageParams = {
	container: HTMLElement,
	booking: BookingModel,
	hash: String,
	company: String,
	context: String,
};

export class ConfirmPagePublic
{
	constructor(params: ConfirmPageParams)
	{
		const app = BitrixVue.createApp(App, params);

		app.mixin(locMixin);
		app.mount(params.container);
	}
}
