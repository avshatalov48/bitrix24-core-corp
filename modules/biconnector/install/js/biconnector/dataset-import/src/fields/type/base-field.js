import '../../css/dataset-import-field.css';

export const BaseField = {
	props: {
		defaultValue: {
			required: false,
			default: '',
		},
		name: {
			type: String,
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		placeholder: {
			type: String,
			required: false,
			default: '',
		},
		isValid: {
			type: Boolean,
			required: false,
			default: true,
		},
		errorMessage: {
			type: String,
			required: false,
			default: '',
		},
		isDisabled: {
			type: Boolean,
			required: false,
			default: false,
		},
		hintText: {
			type: String,
			required: false,
		},
	},
	emits: [
		'valueChange',
	],
	data()
	{
		return {
			value: this.defaultValue,
			areValidationErrorsShown: false,
		};
	},
	methods: {
		showValidationErrors()
		{
			this.areValidationErrorsShown = true;
		},
		onInputChange(newValue)
		{
			this.$emit('valueChange', {
				newValue,
				fieldName: this.name,
			});
		},
	},
	watch: {
		defaultValue(newValue)
		{
			this.value = newValue;
		},
	},
};
