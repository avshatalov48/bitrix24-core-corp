import { Popup } from 'main.popup';

import { BitrixVue } from 'ui.vue3';
import type { VueCreateAppResult } from 'ui.vue3';

import { Core } from 'booking.core';
import { Model } from 'booking.const';
import { locMixin } from 'booking.component.mixin.loc-mixin';
import { BookingInfo } from 'booking.model.booking-info';

import { App } from './components/app';
import './booking-event-popup.css';

export type BookingEventPopupParams = {
	container: HTMLElement;
	bookingId: number;
};

let popup: Popup | null = null;

export class BookingEventPopup
{
	#bookingId: number;
	#app: VueCreateAppResult;

	constructor(params: BookingEventPopupParams)
	{
		this.#bookingId = params.bookingId;
	}

	async show(): void
	{
		if (popup)
		{
			return;
		}

		if (!popup)
		{
			this.#initPopup();
		}

		await Core.init({
			skipBookingCore: true,
			skipPull: true,
		});
		await Core.addDynamicModule(
			BookingInfo
				.create()
				.setVariables({ bookingId: this.#bookingId }),
		);

		popup.show();

		this.#mountApplication(popup.getContentContainer());
	}

	#initPopup(): void
	{
		if (popup)
		{
			return;
		}

		popup = new Popup({
			id: `calendar-entity-booking-event-popup-${this.#bookingId}`,
			bindElement: null,
			content: '',
			width: 490,
			minHeight: 100,
			maxHeight: 330,
			closeByEsc: true,
			closeIcon: true,
			className: 'booking-event-popup',
			autoHide: true,
			events: {
				onPopupAfterClose: async (): Promise<void> => {
					await this.#close();
				},
			},
			padding: 13,
		});
	}

	#mountApplication(container: HTMLElement): void
	{
		const app = BitrixVue.createApp(App, {
			...Core.getParams(),
			bookingId: this.#bookingId,
		});
		app.mixin(locMixin);
		app.use(Core.getStore());
		app.mount(container);

		this.#app = app;
	}

	async #close(): void
	{
		this.#app.unmount();
		this.#app = null;

		await Core.removeDynamicModule(Model.BookingInfo);

		popup?.destroy();
		popup = null;
	}
}
