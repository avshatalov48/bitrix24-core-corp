import { BIcon as Icon, Set as SetIcon } from 'ui.icon-set.api.vue';
import { ref, type VueRefValue } from 'ui.vue3';

import { ReminderMenu } from './reminder-menu';
import { reminderValues } from './reminder-values';
import type { ReminderMenuItem } from './types';

// @vue/component
export const ReminderItem = {
	name: 'ReminderItem',
	components: {
		Icon,
		ReminderMenu,
	},
	props: {
		remind: {
			type: Number,
			required: true,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['update', 'remove'],
	setup(): ReminderItemData
	{
		const iconName = SetIcon.CROSS_30;
		const shown = ref(false);

		return {
			iconName,
			shown,
		};
	},
	computed: {
		remindItem(): ReminderMenuItem
		{
			return reminderValues.find(({ value }) => value === this.remind);
		},
	},
	methods: {
		select(item: ReminderMenuItem): void
		{
			if (item.value !== this.remind)
			{
				this.$emit('update', { remind: this.remind, selected: item.value });
			}
		},
		toggleMenu(): void
		{
			if (this.disabled)
			{
				return;
			}

			this.shown = !this.shown;
		},
		removeItem(): void
		{
			if (this.disabled)
			{
				return;
			}

			this.$emit('remove', this.remind);
		},
	},
	template: `
		<div
			:class="[
				'booking--reminder',
				'booking--reminder-item',
				{ '--disabled': disabled },
			]"
		>
			<span ref="item" class="booking--reminder-item__title" @click="toggleMenu">
				{{ remindItem.shortLabel }}
			</span>
			<Icon :name="iconName" :size="18" @click="removeItem"/>
			<ReminderMenu v-if="shown" :bindElement="$refs['item']" :shown @select="select"/>
		</div>
	`,
};

type ReminderItemData = {
	iconName: string;
	shown: VueRefValue<boolean>;
}
