import { ref, onMounted } from 'ui.vue3';
import type { VueRefValue } from 'ui.vue3';
import { Document } from 'booking.component.actions-popup';

type WaitListItemDocumentData = {
	isLoading: VueRefValue<boolean>;
}

// @vue/component
export const WaitListItemDocument = {
	name: 'WaitListItemDocument',
	components: {
		Document,
	},
	props: {
		waitListItemId: {
			type: Number,
			required: true,
		},
	},
	setup(): WaitListItemDocumentData
	{
		const isLoading = ref(true);

		onMounted((): void => {
			isLoading.value = false;
		});

		return {
			isLoading,
		};
	},
	template: `
		<Document
			:id="waitListItemId"
			:loading="isLoading"
			disabled
		/>
	`,
};
