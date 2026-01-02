import '../../css/fields-settings.css';

export const SyncFieldsButton = {
	emits: ['buttonClick'],
	props: {
		disabled: {
			type: Boolean,
			default: false,
		},
	},
	computed: {
		isDisabled(): boolean
		{
			return this.disabled;
		},
	},
	methods: {
		onButtonClick()
		{
			this.$emit('buttonClick');
		},
	},
	// language=Vue
	template: `
		<div class="ui-form-row">
			<button :disabled="isDisabled" class="ui-btn ui-btn-sm ui-btn-primary ui-btn-no-caps" @click="onButtonClick">
				{{ $Bitrix.Loc.getMessage('DATASET_IMPORT_SYNC_FIELDS_BUTTON') }}
			</button>
		</div>
	`,
};
