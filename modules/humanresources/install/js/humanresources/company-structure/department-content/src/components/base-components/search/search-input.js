import 'ui.icon-set.main';
import './search-input.css';

export const SearchInput = {
	name: 'searchInput',
	props: {
		value: {
			type: String,
			required: false,
			default: '',
		},
		placeholder: {
			type: String,
			required: true,
		},
		dataTestId: {
			type: String,
			required: false,
		},
	},

	data(): {hasFocus: boolean}
	{
		return {
			hasFocus: false,
		};
	},

	methods: {
		handleInput(event: InputEvent): void
		{
			this.$emit('inputChange', event.target.value);
		},
		handleBlur(): void
		{
			if (this.value.length === 0)
			{
				this.hasFocus = false;
			}
		},
		clearInput(): void
		{
			this.hasFocus = false;
			this.$emit('inputChange', '');
		},
	},

	template: `
		<div
			class="hr-department-detail-content__content-search"
			:class="{'--focused': hasFocus}"
		>
			<div class="hr-department-detail-content__content-search-icon ui-icon-set --search-1"/>
			<input
				class="hr-department-detail-content__content-search-input"
				type="text"
				:placeholder="!hasFocus ?  placeholder : ''"
				:data-test-id="dataTestId"
				:value="value"
				@input="handleInput"
				@focus="hasFocus = true"
				@blur="handleBlur"
			>
			<div
				class="hr-department-detail-content__content-search-close-button ui-icon-set --cross-circle-50"
				:class="{'--hide': !hasFocus}"
				@click="clearInput"
			/>
		</div>
	`,
};
