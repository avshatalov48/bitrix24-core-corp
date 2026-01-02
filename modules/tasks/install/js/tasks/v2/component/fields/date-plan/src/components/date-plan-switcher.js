import { TextSm } from 'ui.system.typography.vue';
import { SwitcherSize, type SwitcherOptions } from 'ui.switcher';
import { Switcher } from 'ui.vue3.components.switcher';
import { hint, type HintParams } from 'ui.vue3.directives.hint';

import { tooltip } from 'tasks.v2.component.elements.hint';

export const DatePlanSwitcher = {
	components: {
		TextSm,
		Switcher,
	},
	directives: { hint },
	props: {
		modelValue: {
			type: Boolean,
			required: true,
		},
		text: {
			type: String,
			required: true,
		},
		hint: {
			type: String,
			default: '',
		},
	},
	computed: {
		switcherOptions(): SwitcherOptions
		{
			return {
				size: SwitcherSize.extraSmall,
				useAirDesign: true,
			};
		},
		tooltip(): Function
		{
			return (): HintParams => tooltip({
				text: this.hint,
				popupOptions: {
					offsetLeft: this.$refs.hint.offsetWidth / 2,
				},
				timeout: 100,
			});
		},
	},
	template: `
		<div class="tasks-field-date-plan-switcher" @click="$emit('update:modelValue', !modelValue)">
			<Switcher :isChecked="modelValue" :options="switcherOptions"/>
			<TextSm>{{ text }}</TextSm>
			<div v-if="hint" v-hint="tooltip" class="tasks-hint-badge" ref="hint" @click.capture.stop>?</div>
		</div>
	`,
};
