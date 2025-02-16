import { DatePicker } from 'ui.date-picker';
import { Button, ButtonSize, ButtonColor } from 'booking.component.button';
import './drum.css';

export const Drum = {
	components: {
		Button,
	},
	emits: ['onSave', 'onCancel'],
	props: {
		selectedTime: {
			type: Date,
			default: new Date(),
		},
	},
	data(): Object
	{
		return {
			ButtonSize,
			ButtonColor,
			selectedTimeLocal: this.selectedTime,
		};
	},
	mounted(): void
	{
		this.getDatePicker().selectDate(this.selectedTime);
		this.getDatePicker().show();
	},
	watch: {
		selectedTime(newVal): void
		{
			this.getDatePicker().selectDate(newVal);
		},
	},
	beforeUnmount(): void
	{
		this.datePicker?.destroy();
		this.datePicker = null;
	},
	methods:
	{
		getDatePicker(): DatePicker
		{
			if (!this.datePicker)
			{
				this.datePicker = new DatePicker({
					targetNode: this.$refs.datepicker,
					inline: true,
					type: 'time',
					timePickerStyle: 'wheel',
				});
			}

			return this.datePicker;
		},
		save(): void
		{
			const selectedDate = this.getDatePicker().getSelectedDate();
			if (selectedDate === null)
			{
				this.$emit('onCancel');
			}
			else
			{
				const localTime = new Date(selectedDate.toLocaleString('en-US', { timeZone: 'UTC' }));
				this.$emit('onSave', localTime);
			}
		},
		cancel(): void
		{
			this.$emit('onCancel');
		},
	},
	template: `
		<div class="booking-drum">
			<p class="booking-drum-header">
				{{ loc('BOOKING_COMPONENT_DRUM_HEADER') }}
			</p>
			<div ref="datepicker" class="booking-drum-datepicker"></div>
			<div class="booking-drum-controls">
				<div class="booking-drum-controls-btn-save">
					<Button
						:text="loc('BOOKING_COMPONENT_DRUM_BTN_SAVE')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.PRIMARY"
						:round="true"
						@click="save"
					/>
				</div>
				<div class="booking-drum-controls-btn-cancel">
					<Button
						:text="loc('BOOKING_COMPONENT_DRUM_BTN_CANCEL')"
						:size="ButtonSize.EXTRA_SMALL"
						:color="ButtonColor.LINK"
						:round="true"
						@click="cancel"
					/>
				</div>
			</div>
		</div>
	`,
};
