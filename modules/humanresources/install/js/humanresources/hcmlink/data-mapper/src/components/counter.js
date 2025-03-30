import { Loc, Text } from 'main.core';
import { DateFormatter, DateTemplate } from 'im.v2.lib.date-formatter';
import { EventEmitter } from 'main.core.events';
import { EventList } from '../types';

import '../styles/counter.css';

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
		mode: {
			required: true,
			type: String,
		},
		lastJobFinishedAt: {
			required: false,
			type: Date,
			default: null,
		},
	},

	computed: {
		leftCounterPhrase(): string
		{
			return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_PAGE_UNMAPPED_TITLE_MSGVER_1', {
				'[SPAN]': '<span class="hr-hcmlink-sync__counter_count-accent">',
				'[/SPAN]': '</span>',
				'#COUNT#': Text.encode(this.countUnmappedPersons),
			});
		},
		formatDate(): string
		{
			if (this.lastJobFinishedAt)
			{
				return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_COUNTER_RIGHT', {
					'#FORMATTED_DATE#': DateFormatter.formatByTemplate(this.lastJobFinishedAt, DateTemplate.messageReadStatus),
				});
			}

			return Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_COUNTER_RIGHT', {
				'#FORMATTED_DATE#': Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_COUNTER_RIGHT_DATE_NEVER'),
			});
		},
	},

	methods: {
		forceSync(): void
		{
			EventEmitter.emit(EventList.HR_DATA_MAPPER_FORCE_SYNC);
		},
	},

	template: `
		<div v-html="leftCounterPhrase" class="hr-hcmlink-sync__toolbar-bubble hr-hcmlink-sync__counter_container-left"/>
		<div class="hr-hcmlink-sync__toolbar-bubble hr-hcmlink-sync__counter_container-right">
			<div class="hr-hcmlink-sync__toolbar-format-date">{{formatDate}}</div>
			<div class="hr-hcmlink-sync__toolbar-separator"></div>
			<div 
				class="hr-hcmlink-sync__toolbar-update-button"
				@click="forceSync"
			>
				{{ $Bitrix.Loc.getMessage('HUMANRESOURCES_HCMLINK_MAPPER_SLIDER_COUNTER_RIGHT_UPDATE_BUTTON') }}
			</div>
		</div>
	`,
};
