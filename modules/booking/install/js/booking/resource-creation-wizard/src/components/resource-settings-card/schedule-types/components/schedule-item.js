import { HelpDesk } from 'booking.const';
import { HelpDeskLoc } from 'booking.component.help-desk-loc';

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
	setup(): { code: string, anchorCode: string }
	{
		return {
			code: HelpDesk.ResourceSchedule.code,
			anchorCode: HelpDesk.ResourceSchedule.anchorCode,
		};
	},
	computed: {
		isSelected(): boolean
		{
			return this.modelValue.toString() === this.value.toString();
		},
	},
	methods: {
		selectOption()
		{
			this.$emit('update:model-value', this.value);
		},
	},
	components: {
		HelpDeskLoc,
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
				<HelpDeskLoc
					:message="description"
					:code="code"
					:anchor="anchorCode"
					class="booking--rcw--schedule-item-text-description"
					link-class="booking--rcw--more booking--rcw--schedule-item-text-description-more"
				/>
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
