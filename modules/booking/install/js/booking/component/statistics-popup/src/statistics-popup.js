import { PopupOptions } from 'main.popup';
import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import { Popup } from 'booking.component.popup';
import './statistics-popup.css';

export const StatisticsPopup = {
	emits: ['close'],
	props: {
		popupId: {
			type: String,
			required: true,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		rows: {
			type: Array,
			required: true,
		},
		button: {
			type: Object,
			required: false,
		},
		dataset: {
			type: Object,
			default: {},
		},
	},
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
		};
	},
	computed: {
		config(): PopupOptions
		{
			return {
				bindElement: this.bindElement,
				minWidth: 200,
				offsetTop: 10,
				offsetLeft: this.bindElement.offsetWidth / 2,
				background: '#2878ca',
				padding: 13,
				angle: true,
				angleBorderRadius: '4px 0',
			};
		},
	},
	methods: {
		prepareDataset(dataset?: Object): Object
		{
			if (!dataset)
			{
				return {};
			}

			return Object.fromEntries(
				Object.entries(dataset).map(([key, value]) => [
					`data-${key.replaceAll(/([A-Z])/g, '-$1').toLowerCase()}`,
					value,
				]),
			);
		},
	},
	components: {
		Popup,
		Button,
	},
	template: `
		<Popup
			:id="popupId"
			:config="config"
			@close="$emit('close')"
			ref="popup"
		>
			<div class="booking-statistics-popup" v-bind="prepareDataset(dataset)">
				<div class="booking-statistics-popup-title">
					{{ title }}
				</div>
				<template v-for="(row, index) of rows" :key="index">
					<div class="booking-statistics-popup-row">
						<div>
							{{ row.title }}
						</div>
						<div
							class="booking-statistics-popup-row-value"
							v-bind="prepareDataset(row.dataset)"
							v-html="row.value"
						></div>
					</div>
				</template>
				<template v-if="button">
					<Button
						class="booking-statistics-popup-button bitrix24-light-theme"
						buttonClass="ui-btn-themes"
						:text="button.title"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LIGHT_BORDER"
						@click="button.click"
					/>
				</template>
			</div>
		</Popup>
	`,
};
