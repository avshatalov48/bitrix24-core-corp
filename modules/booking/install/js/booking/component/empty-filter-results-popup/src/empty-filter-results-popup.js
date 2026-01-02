import type { PopupOptions } from 'main.popup';
import { Popup } from 'booking.component.popup';
import './empty-filter-results-popup.css';

const FILTER_ELEMENT_ID = 'BOOKING_FILTER_ID_search_container';

// @vue/component
export const EmptyFilterResultsPopup = {
	name: 'EmptyFilterResultsPopup',
	components: {
		Popup,
	},
	date(): Object
	{
		return {
			bindElement: null,
		};
	},
	computed: {
		popupId(): string
		{
			return 'booking-empty-filter-result-popup';
		},
		config(): PopupOptions
		{
			return {
				bindElement: this.bindElement,
				minWidth: 200,
				offsetTop: 10,
				background: '#2878ca',
				padding: 13,
				angle: {
					offset: this.bindElement.offsetWidth / 2,
					position: 'top',
				},
				angleBorderRadius: '4px 0',
			};
		},
	},
	beforeMount()
	{
		this.bindElement = document.querySelector(`#${FILTER_ELEMENT_ID}`);
	},
	template: `
		<Popup
			:id="popupId"
			:config
			ref="popup"
		>
			<div class="booking--booking--empty-filter-results-popup-content">
				<div class="booking--booking--empty-filter-results-popup-content__title">{{ loc('BOOKING_BOOKING_FILTER_EMPTY_RESULT_TITLE') }}</div>
				<div class="booking--booking--empty-filter-results-popup-content__subtitle">{{ loc('BOOKING_BOOKING_FILTER_EMPTY_RESULT_SUBTITLE') }}</div>
			</div>
		</Popup>
	`,
};
