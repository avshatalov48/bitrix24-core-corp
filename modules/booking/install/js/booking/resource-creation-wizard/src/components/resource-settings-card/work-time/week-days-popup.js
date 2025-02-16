import { PopupOptions } from 'main.popup';
import { Popup } from 'booking.component.popup';
import { WorkTimeMixin } from './work-time-mixin';

import './week-days-popup.css';

export const WeekDaysPopup = {
	name: 'ResourceSettingsCardWeekDaysPopup',
	emits: ['select', 'close'],
	props: {
		id: {
			type: String,
			required: true,
		},
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		initialDays: {
			type: Array,
			required: true,
		},
	},
	components: {
		Popup,
	},
	mixins: [WorkTimeMixin],
	methods: {
		close(): void
		{
			this.$emit('close');
		},
		click(day: string): void
		{
			const trueKeys = Object.keys(this.selected).filter((key) => this.selected[key] === true);

			if (trueKeys.length === 1 && trueKeys[0] === day)
			{
				return;
			}

			this.selected[day] = !this.selected[day];

			this.$emit('select', this.selectedDays, this.formatDaysLabel());
		},
	},
	computed: {
		popupId(): string
		{
			return `booking-work-time-popup-${this.id}`;
		},
		config(): PopupOptions
		{
			return {
				bindElement: this.bindElement,
				targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
				offsetRight: this.bindElement.offsetWidth,
				angle: false,
				bindOptions: {
					forceBindPosition: true,
					forceLeft: true,
				},
			};
		},
	},
	template: `
		<Popup
			:id="popupId"
			:config="config"
			@close="close"
			ref="popup"
		>
			<div class="resource-creation-wizard__form-week-days-popup">
				<div
					v-for="(day, index) in daysLabelMap"
					:key="index"
					:data-id="'brcw-resource-work-time-week-day-' + index + '-' + id"
					class="resource-creation-wizard__form-week-days-popup-weekday"
					:class="{ '--selected': selected[index] }"
					@click="() => click(index)"
				>
					<div class="resource-creation-wizard__form-week-days-popup-weekday-text">
						{{ day }}
					</div>
					<div class="resource-creation-wizard__form-week-days-popup-weekday-icon"></div>
				</div>
			</div>
		</Popup>
	`,
};
