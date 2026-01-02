import type { PopupOptions } from 'main.popup';
import { Popup } from '../../layout/popup';
import '../../css/file-checking-popup.css';

export const CheckingFailedPopup = {
	emits: ['close'],
	props: {},
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
		<Popup 
			id="file-check-failed"
			@close="this.onClose" 
			:options="popupOptions" 
		>
			<div class="file-check file-check-failed">
				<div class="biconnector-save-progress-popup__failure-logo file-check-icon-failed"></div>
				<div class="file-check-title">{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_CHECK_FAILED_TITLE') }}</div>
				<div class="file-check-subtitle">{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_CHECK_FAILED_SUBTITLE') }}</div>
				<button @click="this.onClose" class="ui-btn ui-btn-md ui-btn-primary file-check-button">
					{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_CSV_ERROR_POPUP_CHECK_FAILED_BUTTON') }}
				</button>
			</div>
		</Popup>
	`,
};
