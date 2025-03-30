import { BIcon, Set } from 'ui.icon-set.api.vue';
import { EventList } from '../types';

import '../styles/search-bar.css';

export const SearchBar = {
	name: 'SearchBar',

	props: {
		placeholder: {
			required: false,
			type: String,
		},
		// how many milliseconds passes before emitting 'onSearch' event after input
		debounceWait: {
			required: false,
			default: 0,
		},
	},

	components: {
		BIcon,
	},

	directives: {
		focus: {
			mounted(el)
			{
				el.focus();
			},
		},
	},

	data(): Object
	{
		return {
			showSearchBar: false,
			searchQuery: '',
			debounceTimer: null,
		};
	},

	mounted(): void
	{
		this.$Bitrix.eventEmitter.subscribe(EventList.HR_DATA_MAPPER_DATA_WAS_SAVED, this.clearInput);
		this.$Bitrix.eventEmitter.subscribe(EventList.HR_DATA_MAPPER_CLEAR_SEARCH_INPUT, this.clearInput);
	},

	unmounted(): void
	{
		this.$Bitrix.eventEmitter.unsubscribe(EventList.HR_DATA_MAPPER_DATA_WAS_SAVED, this.clearInput);
		this.$Bitrix.eventEmitter.unsubscribe(EventList.HR_DATA_MAPPER_CLEAR_SEARCH_INPUT, this.clearInput);
	},

	emits: [
		'search',
	],

	computed: {
		IconSet(): typeof Set
		{
			return Set;
		},
	},

	methods: {
		onBlur(): void
		{
			if (this.searchQuery.length === 0)
			{
				this.searchQuery = '';
				this.hideSearchbar();
			}
		},
		clearInput(): void
		{
			this.searchQuery = '';
		},

		toggleSearchbar(): void
		{
			if (this.showSearchBar)
			{
				this.showSearchBar = false;
				this.searchQuery = '';

				return;
			}

			this.showSearchBar = true;
		},

		onAfterEnter(): void
		{
			if (this.$refs.searchNameInput)
			{
				this.$refs.searchNameInput.focus();
			}
		},

		hideSearchbar(): void
		{
			this.showSearchBar = false;
		},

		clearSearch(): void
		{
			if (this.$refs.searchNameInput)
			{
				this.searchQuery = '';
				this.$refs.searchNameInput.focus();
			}
		},
	},

	watch: {
		searchQuery(query): void {
			if (this.debounceTimer)
			{
				clearTimeout(this.debounceTimer);
			}
			this.debounceTimer = setTimeout(() => {
				this.$emit('search', query);
			}, this.debounceWait);
		},
	},

	template: `
		<div
			class="hr-hcmlink-sync__content-search-container"
		>
			<transition
				name="hr-hcmlink-sync__search-transition"
				@after-enter="onAfterEnter"
				mode="out-in"
			>
				<div
					class="hr-hcmlink-sync__content-search-block__search"
					@click="toggleSearchbar"
					key="searchIcon"
					v-if="!showSearchBar"
				>
					<BIcon :name="IconSet.SEARCH_2" :size="24" class="hr-hcmlink-sync__search-icon"></BIcon>
				</div>
				<div
					class="hr-hcmlink-sync__content-search-block__search-bar"
					key="searchBar"
					v-else
				>
					<input
						ref="searchNameInput"
						v-model="searchQuery"
						v-focus
						type="text"
						:placeholder="!searchQuery ? placeholder : ''"
						class="hr-hcmlink-sync__content-search-block__search-input"
						@blur="onBlur"
					>
					<div
						@click="clearSearch"
						class="hr-hcmlink-sync__content-search-block__search-reset"
					>
						<div class="hr-hcmlink-sync__content-search-block__search-cursor"></div>
						<BIcon
							:name="IconSet.CROSS_30"
							:size="24"
							color="#959ca4"
						></BIcon>
					</div>
				</div>
			</transition>
		</div>
	`,
};
