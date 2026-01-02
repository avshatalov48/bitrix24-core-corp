import { DateTimeFormat } from 'main.date';
import { RoundedSmallSelectedItemView } from './../sign-selector/components/selected-item-view/rounded-small-selected-item-view';

import { DatePicker, DatePickerEvent } from 'ui.date-picker';

import './style.css';

// @vue/component
export const DateSelector = {
	name: 'DateSelector',
	components: {
		RoundedSmallSelectedItemView,
	},
	props: {
		value: {
			type: Date,
			required: true,
		},
		showTime: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['onSelect'],
	computed: {
		formattedDate(): string
		{
			const datePart = this.formatDate(this.value, 'DAY_MONTH_FORMAT');
			const timePart = this.formatDate(this.value, 'SHORT_TIME_FORMAT');

			return this.showTime
				? `${datePart}, ${timePart}`
				: datePart
			;
		},
	},
	created(): void
	{
		this.datePicker = new DatePicker({
			selectionMode: 'single',
			selectedDates: [
				this.value,
			],
			...(this.showTime && {
				enableTime: true,
			}),
		});

		this.datePicker.subscribe(DatePickerEvent.SELECT, ({ data }) => {
			this.selectDate(data.date);
		});
	},
	mounted(): void
	{
		this.datePicker.getPopup().setBindElement(this.$refs.target);
	},
	unmounted(): void
	{
		this.datePicker.hide();
		this.datePicker.destroy();
	},
	methods: {
		openPicker(): void
		{
			this.datePicker.show();
		},
		selectDate(date: Date): void
		{
			this.$emit('onSelect', date);
		},
		formatDate(date: Date, formatType: string): string
		{
			const template = DateTimeFormat.getFormat(formatType);

			return DateTimeFormat.format(template, date);
		},
	},

	template: `
		<div
			class="sign-b2e-vue-util-date-selector"
			ref="target"
			@click="openPicker"
		>
			<RoundedSmallSelectedItemView
				:title="formattedDate"
			/>
		</div>
	`,
};
