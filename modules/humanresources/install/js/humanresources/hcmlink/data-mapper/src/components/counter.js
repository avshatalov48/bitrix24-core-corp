import { $Bitrix } from 'ui.vue3';

export const Counter = {
	name: 'Counter',

	props: {
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
		config: {
			required: true,
			type: Object,
		},
	},

	template:`
        <div class="hr-hcmlink-sync__page_counter_container">
			<template v-if="config.mode === 'direct'">
				<span class="hr-hcmlink-sync__page_count-title">{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_PAGE_MAPPED_TITLE') }}: </span>
				<span class="hr-hcmlink-sync__page_mapped-persons-count">{{ countMappedPersons }} </span>
				<span class="hr-hcmlink-sync__page_all-persons-count"> / {{ countAllPersonsForMap }} </span>
			</template>
			<template v-else>
				<span class="hr-hcmlink-sync__page_count-title">{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_PAGE_UNMAPPED_TITLE') }}: </span>
				<span class="hr-hcmlink-sync__page_mapped-persons-count">{{ countUnmappedPersons }} </span>
			</template>
        </div>
	`,
};