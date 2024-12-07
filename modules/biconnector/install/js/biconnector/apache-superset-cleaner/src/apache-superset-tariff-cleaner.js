import { Loc } from 'main.core';
import { ApacheSupersetCleanPopup } from './apache-superset-clean-popup';

export class ApacheSupersetTariffCleaner
{
	#popup: ApacheSupersetCleanPopup;

	handleButtonClick(button: BaseButton)
	{
		this.#popup = new ApacheSupersetCleanPopup({
			message: Loc.getMessage('SUPERSET_CLEANER_DELETE_POPUP_TARIFF_TEXT'),
			onSuccess: () => {
				window.top.location.reload();
			},
			onAccept: () => {
				button.addClass('ui-btn-wait');
			},
		});

		this.#popup.show();
	}
}
