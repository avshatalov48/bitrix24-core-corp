import { Runtime, Type } from 'main.core';
import { BIcon as Icon, Set as IconSet } from 'ui.icon-set.api.vue';
import './search.css';

export const Search = {
	emits: ['search'],
	data(): Object
	{
		return {
			searchDebounced: Runtime.debounce(this.search, 200, this),
			query: '',
		};
	},
	computed: {
		searchIcon(): string
		{
			return IconSet.SEARCH_2;
		},
	},
	methods: {
		onInput(event: InputEvent): void
		{
			const query = event.target.value;
			this.query = query;

			if (Type.isStringFilled(query))
			{
				this.searchDebounced(query);
			}
			else
			{
				this.search(query);
			}
		},
		search(query: string): void
		{
			if (this.query === query)
			{
				this.$emit('search', query);
			}
		},
	},
	components: {
		Icon,
	},
	template: `
		<div class="booking-booking-resources-dialog-header-input-container">
			<input
				class="booking-booking-resources-dialog-header-input"
				:placeholder="loc('BOOKING_BOOKING_RESOURCES_DIALOG_SEARCH')"
				data-element="booking-resources-dialog-search-input"
				@input="onInput"
			>
			<div class="booking-booking-resources-dialog-header-input-icon">
				<Icon :name="searchIcon"/>
			</div>
		</div>
	`,
};
