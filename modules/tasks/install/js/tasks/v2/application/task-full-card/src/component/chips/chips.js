import { Chip, ChipDesign } from 'ui.system.chip.vue';
import { Outline } from 'ui.icon-set.api.vue';
import 'ui.icon-set.outline';

import { heightTransition } from 'tasks.v2.lib.height-transition';
import './chips.css';

// @vue/component
export const Chips = {
	components: {
		Chip,
	},
	props: {
		taskId: {
			type: [Number, String],
			required: true,
		},
		chips: {
			type: Array,
			required: true,
		},
	},
	setup(): Object
	{
		return {
			Outline,
			ChipDesign,
		};
	},
	data(): Object
	{
		return {
			chipsCollapsed: true,
		};
	},
	mounted(): void
	{
		heightTransition.animate(this.$el);
	},
	updated(): void
	{
		heightTransition.animate(this.$el);
	},
	template: `
		<div class="tasks-full-card-chips">
			<template v-for="(chip, index) of chips" :key="index">
				<component
					v-if="!chip.collapsed || !chipsCollapsed"
					:is="chip.component ?? chip"
					v-bind="{ taskId }"
					v-on="chip.events ?? {}"
				/>
			</template>
			<Chip
				:design="ChipDesign.ShadowNoAccent"
				:icon="chipsCollapsed ? Outline.APPS : Outline.CHEVRON_TOP_L"
				:text="chipsCollapsed ? loc('TASKS_V2_TASK_FULL_CARD_MORE') : ''"
				@click="chipsCollapsed = !chipsCollapsed"
			/>
		</div>
	`,
};
