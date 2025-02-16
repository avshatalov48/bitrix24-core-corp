import 'phone_number';
import { PhoneNumberInput } from 'crm.entity-editor.field.phone-number-input';
import { InputField } from './input-field';

export const PhoneInput = {
	emits: ['update:modelValue'],
	props: {
		modelValue: String,
		clientId: {
			type: Number,
			required: true,
		},
		code: {
			type: String,
			required: true,
		},
	},
	async mounted(): void
	{
		new PhoneNumberInput({
			node: this.$refs.input,
			flagNode: this.$refs.flag,
		});
	},
	components: {
		InputField,
	},
	template: `
		<InputField
			:name="loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_PHONE')"
			:data-element="'booking-client-field-phone'"
		>
			<input
				class="booking-booking-client-popup-field-input --left-icon"
				:placeholder="loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_PLACEHOLDER')"
				:value="modelValue"
				data-element="booking-client-phone-input"
				:data-id="clientId"
				:data-code="code"
				ref="input"
				@input="$emit('update:modelValue', $refs.input.value)"
			/>
			<div class="booking-booking-client-popup-field-input-icon --no-border" ref="flag"></div>
		</InputField>
	`,
};
