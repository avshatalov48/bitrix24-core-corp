import { Set as IconSet } from 'ui.icon-set.api.vue';
import { TitleLayout } from '../title-layout/title-layout';
import { ScheduleItem, type IScheduleItem } from './components/schedule-item';

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
	components: {
		TitleLayout,
		ScheduleItem,
	},
	computed: {
		title(): string
		{
			return this.loc('BRCW_SETTINGS_CARD_SCHEDULE_TITLE');
		},
		titleIconType(): string
		{
			return IconSet.COLLABORATION;
		},
		items(): Array<IScheduleItem>
		{
			return [
				{
					id: 'common',
					itemClass: 'resource-creation-wizard__form-settings-schedule-view-common',
					title: this.loc('BRCW_SETTINGS_CARD_SCHEDULE_COLUMNS_TITLE'),
					description: this.loc('BRCW_SETTINGS_CARD_SCHEDULE_COLUMNS_DESCRIPTION'),
					value: true,
				},
				{
					id: 'extra',
					itemClass: 'resource-creation-wizard__form-settings-schedule-view-extra',
					title: this.loc('BRCW_SETTINGS_CARD_SCHEDULE_CROSS_RESOURCING_TITLE'),
					description: this.loc('BRCW_SETTINGS_CARD_SCHEDULE_CROSS_RESOURCING_DESCRIPTION'),
					value: false,
				},
			];
		},
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
