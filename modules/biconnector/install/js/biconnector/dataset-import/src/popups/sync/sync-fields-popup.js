import type { PopupOptions } from 'main.popup';
import { Popup } from '../../layout/popup';

export const SyncFieldsPopup = {
	emits: ['close'],
	props: {
		isChange: {
			type: Boolean,
			required: true,
		},
	},
	computed: {
		popupOptions(): PopupOptions
		{
			return {
				width: 440,
				closeIcon: true,
				noAllPaddings: true,
				overlay: true,
			};
		},
	},
	methods: {
		getDescription(): string
		{
			return this.isChange
				? this.$Bitrix.Loc.getMessage('DATASET_IMPORT_SYNC_FIELDS_POPUP_DESCRIPTION_CHANGED')
				: this.$Bitrix.Loc.getMessage('DATASET_IMPORT_SYNC_FIELDS_POPUP_DESCRIPTION_NOT_CHANGED');
		},
		onClose()
		{
			this.$emit('close');
		},
	},
	components: {
		Popup,
	},
	// language=Vue
	template: `
		<Popup id="syncFields" @close="this.onClose" :options="popupOptions" wrapper-class="generic-popup">
			<h3 class="generic-popup__header">{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_SYNC_FIELDS_POPUP_HEADER') }}</h3>
			<div class="generic-popup__content">
				{{ getDescription() }}
			</div>
			<div class="generic-popup__buttons-wrapper">
				<button @click="onClose" class="ui-btn ui-btn-md ui-btn-primary">
					{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_SYNC_FIELDS_POPUP_BUTTON') }}
				</button>
			</div>
		</Popup>
	`,
};
