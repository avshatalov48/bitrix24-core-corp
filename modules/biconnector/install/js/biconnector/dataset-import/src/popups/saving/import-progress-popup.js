import { SavingPopup } from './saving-popup';
import { Loader } from 'main.loader';
import '../../css/save-progress-popup.css';

export const ImportProgressPopup = {
	props: {
		description: {
			type: String,
			required: false,
			default: '',
		},
	},
	mounted()
	{
		const loader = new Loader({
			target: this.$refs.loader,
			size: 65,
			color: 'var(--ui-color-primary)',
			strokeWidth: 4,
			mode: 'inline',
		});
		loader.show();
	},
	computed: {
		popupOptions()
		{
			return {
				autoHide: false,
				closeIcon: false,
			};
		},
	},
	components: {
		SaveProgressPopup: SavingPopup,
	},
	// language=Vue
	template: `
		<SaveProgressPopup
			:title="$Bitrix.Loc.getMessage('DATASET_IMPORT_PROGRESS_POPUP_HEADER')"
			:description="description"
			:options="popupOptions"
		>
			<template v-slot:icon>
				<div ref="loader" class="dataset-save-progress-loader"></div>
			</template>
		</SaveProgressPopup>
	`,
};
