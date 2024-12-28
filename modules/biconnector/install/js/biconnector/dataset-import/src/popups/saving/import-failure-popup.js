import { SavingPopup } from './saving-popup';
import '../../css/save-progress-popup.css';

export const ImportFailurePopup = {
	props: {
		title: {
			type: String,
			required: true,
		},
		description: {
			type: String,
			required: true,
		},
	},
	emits: ['click'],
	computed: {},
	methods:
	{
		onClick()
		{
			this.$emit('click');
		},
	},
	components: {
		SaveProgressPopup: SavingPopup,
	},
	// language=Vue
	template: `
		<SaveProgressPopup
			:title="title"
			:description="description"
		>
			<template v-slot:icon>
				<div class="dataset-save-progress-popup__failure-logo"></div>
			</template>
			<template v-slot:buttons>
				<button @click="onClick" class="ui-btn ui-btn-md ui-btn-primary">{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_FAILURE_POPUP_BUTTON') }}</button>
			</template>
		</SaveProgressPopup>
	`,
};
