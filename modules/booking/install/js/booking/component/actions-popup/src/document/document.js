import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import 'ui.icon-set.main';

import { Button, ButtonSize, ButtonColor, ButtonIcon } from 'booking.component.button';
import { Loader } from 'booking.component.loader';
import { bookingActionsService } from 'booking.provider.service.booking-actions-service';

import './document.css';

type OptionsDictionary = { [string]: string };
type DocumentData = {
	iconSet: OptionsDictionary,
	buttonSize: OptionsDictionary,
	buttonColor: OptionsDictionary,
	buttonIcon: OptionsDictionary,
}

export const Document = {
	name: 'ActionsPopupDocument',
	emits: ['link'],
	props: {
		id: {
			type: [Number, String],
			required: true,
		},
		loading: {
			type: Boolean,
			default: false,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		dataId: {
			type: [Number, String],
			default: '',
		},
		dataElementPrefix: {
			type: String,
			default: '',
		},
	},
	setup(): DocumentData
	{
		const iconSet = IconSet;
		const buttonSize = ButtonSize;
		const buttonColor = ButtonColor;
		const buttonIcon = ButtonIcon;

		return {
			iconSet,
			buttonSize,
			buttonColor,
			buttonIcon,
		};
	},
	async mounted()
	{
		await bookingActionsService.getDocData();

		this.isLoading = false;
	},
	methods: {
		linkDoc(): void
		{
			this.$emit('link');
		},
	},
	components: {
		Button,
		Icon,
		Loader,
	},
	template: `
		<div class="booking-actions-popup__item booking-actions-popup__item-doc-content --disabled">
			<Loader v-if="loading" class="booking-actions-popup__item-doc-loader"/>
			<template v-else>
				<div class="booking-actions-popup__item-doc">
					<div class="booking-actions-popup-item-icon">
						<Icon :name="iconSet.DOCUMENT"/>
					</div>
					<div class="booking-actions-popup-item-info">
						<div class="booking-actions-popup-item-title">
							<span>{{ loc('BB_ACTIONS_POPUP_DOC_LABEL') }}</span>
							<Icon :name="iconSet.HELP"/>
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
						:size="buttonSize.EXTRA_SMALL"
						:color="buttonColor.LIGHT"
						:disabled="disabled"
						:round="true"
					>
						<Icon :name="iconSet.PLUS_30"/>
					</Button>
					<Button
						buttonClass="ui-btn-shadow"
						:text="loc('BB_ACTIONS_POPUP_DOC_BTN_LABEL')"
						:size="buttonSize.EXTRA_SMALL"
						:color="buttonColor.LIGHT"
						:disabled="disabled"
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
