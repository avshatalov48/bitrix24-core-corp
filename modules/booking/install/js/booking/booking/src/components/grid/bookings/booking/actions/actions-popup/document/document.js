import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { Loader } from 'booking.component.loader';
import { bookingActionsService } from 'booking.provider.service.booking-actions-service';

import './document.css';

export const Document = {
	name: 'BookingActionsPopupDocument',
	props: {
		bookingId: {
			type: [Number, String],
			required: true,
		},
	},
	components: {
		Button,
		Icon,
		Loader,
	},
	data(): Object
	{
		return {
			IconSet,
			ButtonSize,
			ButtonColor,
			ButtonIcon,
			isLoading: true,
		};
	},
	async mounted()
	{
		await bookingActionsService.getDocData();

		this.isLoading = false;
	},
	methods: {
		linkDoc(): void {},
	},
	template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-doc-content --disabled">
			<Loader v-if="isLoading" class="booking-actions-popup__item-doc-loader" />
			<template v-else>
				<div class="booking-actions-popup__item-doc">
					<div class="booking-actions-popup-item-icon">
						<Icon :name="IconSet.DOCUMENT"/>
					</div>
					<div class="booking-actions-popup-item-info">
						<div class="booking-actions-popup-item-title">
							<span>{{ loc('BB_ACTIONS_POPUP_DOC_LABEL') }}</span>
							<Icon :name="IconSet.HELP"/>
						</div>
						<div class="booking-actions-popup-item-subtitle">
							{{ loc('BB_ACTIONS_POPUP_DOC_ADD_LABEL') }}
						</div>
					</div>
				</div>
				<div class="booking-actions-popup-item-buttons">
					<Button
						class="booking-actions-popup-plus-button"
						buttonClass="ui-btn-shadow"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT"
						:round="true"
						:disabled="true"
					>
						<Icon :name="IconSet.PLUS_30"/>
					</Button>
					<Button
						buttonClass="ui-btn-shadow"
						:text="loc('BB_ACTIONS_POPUP_DOC_BTN_LABEL')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT"
						:round="true"
						@click="linkDoc"
					/>
				</div>
			</template>
			<div class="booking-booking-actions-popup-label">
				{{ loc('BB_ACTIONS_POPUP_LABEL_SOON') }}
			</div>
		</div>
	`,
};
