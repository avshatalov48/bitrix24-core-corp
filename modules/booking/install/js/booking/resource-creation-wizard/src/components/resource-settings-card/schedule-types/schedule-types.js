import { Loc } from 'main.core';
import { Set as IconSet } from 'ui.icon-set.api.vue';
import { TitleLayout } from '../title-layout/title-layout';
import { ScheduleItem } from './components/schedule-item';
import type { IScheduleItem } from './components/schedule-item';

import './schedule-types.css';

export const ScheduleTypes = {
	name: 'ResourceSettingsCardScheduleTypes',
	emits: ['update:model-value'],
	props: {
		modelValue: {
			type: Boolean,
			default: true,
		},
	},
	setup(): { title: string, titleIconType: string, items: IScheduleItem[] }
	{
		const title = Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_TITLE_MSGVER_1');
		const titleIconType = IconSet.OPENED_EYE;
		const items = [
			{
				id: 'common',
				itemClass: 'resource-creation-wizard__form-settings-schedule-view-common',
				title: Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_COLUMNS_TITLE_MSGVER_1'),
				description: Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_COLUMNS_DESCRIPTION_MSGVER_2'),
				value: true,
			},
			{
				id: 'extra',
				itemClass: 'resource-creation-wizard__form-settings-schedule-view-extra',
				title: Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_CROSS_RESOURCING_TITLE_MSGVER_1'),
				description: Loc.getMessage('BRCW_SETTINGS_CARD_SCHEDULE_CROSS_RESOURCING_DESCRIPTION_MSGVER_2'),
				value: false,
			},
		];

		return {
			items,
			title,
			titleIconType,
		};
	},
	components: {
		ScheduleItem,
		TitleLayout,
	},
	template: `
		<div class="ui-form resource-creation-wizard__form-settings --schedule">
			<TitleLayout
				:title="title"
				:iconType="titleIconType"
			/>
			<div class="resource-creation-wizard__form-settings-schedule-view">
				<ScheduleItem
					v-for="item in items"
					:key="item.id"
					:data-id="'brcw-resource-schedule-view-' + item.id"
					:model-value="modelValue"
					:item-class="item.itemClass"
					:title="item.title"
					:description="item.description"
					:value="item.value"
					@update:model-value="$emit('update:model-value', $event)"
				/>
			</div>
		</div>
	`,
};
