import { BIcon, Outline } from 'ui.icon-set.api.vue';

import './description.css';

// @vue/component
export const MiniFormButton = {
	name: 'TaskDescriptionMiniFormButton',
	components: {
		BIcon,
	},
	props: {
		filesCount: {
			type: Number,
			required: true,
		},
	},
	setup(): void
	{
		return {
			Outline,
		};
	},
	computed: {
		iconSize(): number
		{
			return 20;
		},
	},
	template: `
		<div class="tasks-card-change-description-mini-btn">
			<div class="tasks-full-card-field-container --small-vertical-padding">
				<div class="tasks-card-change-description" :class="{ '--no-hover': filesCount }">
					<template v-if="filesCount">
						<BIcon
							:name="Outline.ATTACH"
							:size=iconSize
							class="tasks-card-description-field-icon-link"
						/>
						<div class="tasks-card-change-description-mini-text-files">
							{{ loc('TASKS_V2_DESCRIPTION_FILES_COUNT', { '#COUNT#': String(filesCount) }) }}
						</div>
					</template>
					<template v-else>
						<div class="tasks-card-change-description-mini-text">
							{{ loc('TASKS_V2_CHANGE_DESCRIPTION') }}
						</div>
						<BIcon
							:name="Outline.CREATE_CHAT"
							:size=iconSize
							:hoverable="true"
							class="tasks-card-description-field-icon"
						/>
					</template>
				</div>
			</div>
		</div>
	`,
};
