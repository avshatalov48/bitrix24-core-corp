import { Counter } from './counter';
import { Loc } from 'main.core';

import '../styles/toolbar.css';

export const Toolbar = {
	name: 'Toolbar',

	components: {
		Counter,
	},

	emits: [
		'search',
	],

	props: {
		isMappingReady: {
			required: true,
			type: Boolean,
		},
		mode: {
			required: true,
			type: String,
		},
		countAllPersonsForMap: {
			required: true,
			type: Number,
		},
		countMappedPersons: {
			required: true,
			type: Number,
		},
		countUnmappedPersons: {
			required: true,
			type: Number,
		},
		lastJobFinishedAt: {
			required: false,
			type: Date,
			default: null,
		},
	},

	computed: {
		searchPlaceholder(): string
		{
			return this.mode === 'direct'
				? Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_USERS_SEARCH_PLACEHOLDER_DIRECT')
				: Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_USERS_SEARCH_PLACEHOLDER_REVERSE');
		},
		mappedPercent(): Number
		{
			return Math.round(this.countMappedPersons / (this.countMappedPersons + this.countUnmappedPersons) * 100);
		},
		isDone(): boolean
		{
			return this.mappedPercent === 100;
		},
	},

	methods: {
		onSearchPersonName(query): void
		{
			this.$emit('search', query);
		},
	},

	template: `
		<div class="hr-hcmlink-sync__toolbar-row">
			<div class="hr-hcmlink-sync__title-wrapper">
				<div class="hr-hcmlink-sync__title-box">
					<span class="hr-hcmlink-sync__title-item">${Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_TITLE')}</span>
				</div>
			</div>
			<div class="hr-hcmlink-sync__search-container">
				<div v-if="isMappingReady"
					class="hr-hcmlink-sync__toolbar-bubble hr-hcmlink-sync__toolbar-bubble-right"
					:class="[isDone ? '--done' : '--not-done']"
				>
					{{
						$Bitrix.Loc.getMessage(
							'HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_PAGE_MAPPED_TITLE',
							{ '#PERCENT#': mappedPercent }
						)
					}}
				</div>
			</div>
		</div>
		<div v-if="isMappingReady" class="hr-hcmlink-sync__toolbar-row hr-hcmlink-sync__toolbar-row-counter">
			<Counter
				:countAllPersonsForMap=countAllPersonsForMap
				:countMappedPersons=countMappedPersons
				:countUnmappedPersons=countUnmappedPersons
				:lastJobFinishedAt=lastJobFinishedAt
				:mode=mode
			></Counter>
		</div>
	`,
};
