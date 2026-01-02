import { BIcon } from 'ui.icon-set.api.vue';
import { Outline } from 'ui.icon-set.api.core';

// @vue/component
export const CheckListAddItem = {
	name: 'CheckListAddItem',
	components: {
		BIcon,
	},
	props: {
		isPreview: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['addItem'],
	setup(): Object
	{
		return {
			Outline,
		};
	},
	template: `
		<div
			class="check-list-widget-add-item"
			:class="{'--preview': isPreview}"
			@mousedown="$emit('addItem')"
		>
			<div class="check-list-widget-add-item-icon">
				<BIcon :name="Outline.PLUS_L"/>
			</div>
			<div class="check-list-widget-add-item-title">
				{{ loc('TASKS_V2_CHECK_LIST_ITEM_ADD_BTN') }}
			</div>
		</div>
	`,
};
