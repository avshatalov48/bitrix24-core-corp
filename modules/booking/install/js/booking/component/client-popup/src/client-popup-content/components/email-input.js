import { InputField } from './input-field';

export const EmailInput = {
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
	components: {
		InputField,
	},
	template: `
		<InputField
			:name="loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_EMAIL')"
			:data-element="'booking-client-field-email'"
		>
			<input
				class="booking-booking-client-popup-field-input"
				:placeholder="loc('BOOKING_BOOKING_ADD_CLIENT_FIELD_PLACEHOLDER')"
				:value="modelValue"
				data-element="booking-client-email-input"
				:data-id="clientId"
				:data-code="code"
				ref="input"
				@input="$emit('update:modelValue', $refs.input.value)"
			/>
		</InputField>
	`,
};
