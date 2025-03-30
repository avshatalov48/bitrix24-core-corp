import { Set as IconSet } from 'ui.icon-set.api.vue';
import { TextLayout } from '../text-layout/text-layout';
import { TitleLayout } from '../title-layout/title-layout';
import { SlotLengthSelector } from './slot-length-selector';

export const SlotLength = {
	name: 'ResourceSettingsCardSlotLength',
	emits: ['select'],
	components: {
		TitleLayout,
		TextLayout,
		SlotLengthSelector,
	},
	props: {
		initialSelectedValue: {
			type: Number,
			default: 60,
		},
	},
	data(): Object
	{
		return {
			selectedSlotLength: this.initialSelectedValue,
		};
	},
	methods: {
		updateSelectedValue(value): void
		{
			this.selectedSlotLength = value;

			this.$emit('select', value);
		},
	},
	computed: {
		title(): string
		{
			return this.loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_TITLE');
		},
		titleIconType(): string
		{
			return IconSet.GANTT_GRAPHS;
		},
	},
	template: `
		<div class="ui-form resource-creation-wizard__form-settings">
			<TitleLayout
				:title="title"
				:iconType="titleIconType"
			/>
			<TextLayout
				type="SlotLength"
				:text="loc('BRCW_SETTINGS_CARD_SLOT_LENGTH_TEXT_MSGVER_1')"
			/>
			<div>
				<SlotLengthSelector
					:initialSelectedValue="initialSelectedValue"
					@select="updateSelectedValue"
				/>
			</div>
		</div>
	`,
};
