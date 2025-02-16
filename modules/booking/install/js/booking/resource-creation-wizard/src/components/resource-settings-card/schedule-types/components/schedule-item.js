import { HelpDesk } from 'booking.const';
import { helpDesk } from 'booking.lib.help-desk';

export const ScheduleItem = {
	name: 'ScheduleItem',
	emits: ['update:model-value'],
	props: {
		modelValue: {
			type: Boolean,
			required: true,
		},
		itemClass: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: true,
		},
		value: {
			type: Boolean,
			required: true,
		},
	},
	methods: {
		selectOption()
		{
			this.$emit('update:model-value', this.value);
		},
		showHelpDesk(): void
		{
			helpDesk.show(
				HelpDesk.ResourceSchedule.code,
				HelpDesk.ResourceSchedule.anchorCode,
			);
		},
	},
	computed: {
		isSelected(): boolean
		{
			return this.modelValue.toString() === this.value.toString();
		},
		moreLabel(): string
		{
			return this.loc('BRCW_SETTINGS_CARD_MORE');
		},
	},
	template: `
		<div 
			:class="['booking--rcw--schedule-item', { '--selected': isSelected }]" 
			@click="selectOption"
		>
			<div class="booking--rcw--schedule-item-radio ui-ctl-radio">
				<input
					:id="itemClass"
					:checked="isSelected"
					:value="value.toString()"
					type="radio"
					class="ui-ctl-element"
					@input="$emit('update:model-value', $event.target.value === 'true')"
				/>
			</div>
			<div class="booking--rcw--schedule-item-text">
				<label
					:for="itemClass"
					class="booking--rcw--schedule-item-text-title">
					{{ title }}
				</label>
				<div class="booking--rcw--schedule-item-text-description">{{ description }}</div>
				<span 
					class="booking--rcw--more booking--rcw--schedule-item-text-description-more"
					@click="showHelpDesk"
				>
					{{ moreLabel }}
				</span>
			</div>
			<div class="booking--rcw--schedule-item-view" :class="itemClass"></div>
		</div>
	`,
};

export type IScheduleItem = {
	itemClass: string;
	title: string;
	description: string;
	value: boolean;
}
