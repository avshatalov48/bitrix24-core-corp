import { Dom, Extension, Runtime } from 'main.core';
import type { ListItemOptions } from 'landing.ui.component.listitem';

import { BookingSettingsPopup } from './booking-settings-popup';
import type { FormOptions } from './types';

export class Settings
{
	#isAutoSelectionOn: boolean;
	#options: ListItemOptions;

	settingsPopup: BookingSettingsPopup;

	constructor(listItemOptions: ListItemOptions, formOptions: FormOptions = {})
	{
		this.#options = listItemOptions;
		this.#isAutoSelectionOn = Boolean(formOptions?.bookingResourceAutoSelection?.use);

		this.settingsPopup = new BookingSettingsPopup({
			listItemOptions,
			isAutoSelectionOn: this.#isAutoSelectionOn,
			templateId: formOptions?.templateId,
		});
	}

	getSettings(): Object
	{
		return this.settingsPopup.getSettings();
	}

	showSettingsPopup(): void
	{
		const isToolDisabled = Extension.getSettings('booking.crm-forms.settings').isToolDisabled;
		if (isToolDisabled)
		{
			Runtime.loadExtension('ui.info-helper')
				.then(({ InfoHelper }) => {
					InfoHelper.show('limit_v2_booking_off');
				})
				.catch((err) => {
					console.error(err);
				});

			return;
		}

		const container = document.querySelector(`.landing-ui-component-list-item[data-id="${this.#options.id}"] .landing-ui-component-list-item-body`);

		if (Dom.style(container, 'display') === 'block')
		{
			this.settingsPopup.close();
		}
		else
		{
			this.settingsPopup.show();
		}
	}
}
