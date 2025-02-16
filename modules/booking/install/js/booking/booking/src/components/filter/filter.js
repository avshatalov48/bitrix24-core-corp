import { BaseEvent, EventEmitter } from 'main.core.events';
import type { BookingUIFilter } from 'booking.lib.booking-filter';

export const FilterPreset = Object.freeze({
	NotConfirmed: 'booking-filter-preset-unconfirmed',
	Delayed: 'booking-filter-preset-delayed',
	CreatedByMe: 'booking-filter-preset-created-by-me',
});

export const FilterField = Object.freeze({
	CreatedBy: 'CREATED_BY',
	Contact: 'CONTACT',
	Company: 'COMPANY',
	Resource: 'RESOURCE',
	Confirmed: 'CONFIRMED',
	Delayed: 'DELAYED',
});

export const Filter = {
	emits: ['apply', 'clear'],
	props: {
		filterId: {
			type: String,
			required: true,
		},
	},
	data(): Object {
		return {
			filter: null,
		};
	},
	created(): void
	{
		this.filter = BX.Main.filterManager.getById(this.filterId);

		EventEmitter.subscribe('BX.Main.Filter:beforeApply', this.onBeforeApply);
	},
	methods: {
		onBeforeApply(event: BaseEvent): void
		{
			const [filterId] = event.getData();
			if (filterId !== this.filterId)
			{
				return;
			}

			if (this.isFilterEmpty())
			{
				this.$emit('clear');
			}
			else
			{
				this.$emit('apply');
			}
		},
		setPresetId(presetId: string): void
		{
			if (this.getPresetId() === presetId)
			{
				return;
			}

			this.filter.getApi().setFilter({
				preset_id: presetId,
			});
		},
		getPresetId(): string | null
		{
			const preset = this.filter.getPreset().getCurrentPresetId();

			return preset === 'tmp_filter' ? null : preset;
		},
		isFilterEmpty(): boolean
		{
			return Object.keys(this.getFields()).length === 0;
		},
		getFields(): BookingUIFilter
		{
			const booleanFields = [FilterField.Confirmed, FilterField.Delayed];
			const arrayFields = [FilterField.Company, FilterField.Contact, FilterField.CreatedBy, FilterField.Resource];

			const filterFields = this.filter.getFilterFieldsValues();
			const fields = booleanFields
				.filter((field) => ['Y', 'N'].includes(filterFields[field]))
				.reduce((acc, field) => ({
					...acc,
					[field]: filterFields[field],
				}), {})
			;

			arrayFields.forEach((field: string) => {
				if (filterFields[field]?.length > 0)
				{
					fields[field] = filterFields[field];
				}
			});

			return fields;
		},
	},
	template: `
		<div></div>
	`,
};
