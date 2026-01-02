import { BIcon as Icon, Set as SetIcon } from 'ui.icon-set.api.vue';
import { ref, type VueRefValue } from 'ui.vue3';

import { ReminderMenu } from './reminder-menu';
import type { ReminderMenuItem } from './types';

// @vue/component
export const ReminderAdd = {
	name: 'ReminderAdd',
	components: {
		Icon,
		ReminderMenu,
	},
	props: {
		disabled: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['add'],
	setup(): ReminderAddData
	{
		const iconName = SetIcon.PLUS_30;
		const shown = ref(false);

		return {
			iconName,
			shown,
		};
	},
	methods: {
		select(reminderItem: ReminderMenuItem): void
		{
			this.$emit('add', reminderItem.value);
			this.shown = false;
		},
		toggleMenu(): void
		{
			if (this.disabled)
			{
				return;
			}

			this.shown = !this.shown;
		},
	},
	template: `
		<div class="booking--reminder booking--reminder-add" :class="{ '--disabled': disabled }" @click="toggleMenu">
			<span ref="add" class="booking--reminder-add__label">{{ loc('BOOKING_REMINDER_CREATE') }}</span>
			<Icon :name="iconName" :size="18" />
		</div>
		<ReminderMenu v-if="shown" :bindElement="$refs['add']" :shown @select="select" />
	`,
};

type ReminderAddData = {
	iconName: string,
	shown: VueRefValue<boolean>,
}
