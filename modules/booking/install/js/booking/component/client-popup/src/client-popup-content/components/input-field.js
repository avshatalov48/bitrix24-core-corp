export const InputField = {
	props: {
		name: {
			type: String,
			required: true,
		},
		dataElement: {
			type: String,
			required: true,
		},
	},
	template: `
		<div
			class="booking-booking-client-popup-field"
			:data-element="dataElement"
		>
			<div class="booking-booking-client-popup-field-name">
				{{ name }}
			</div>
			<div class="booking-booking-client-popup-field-input-container">
				<slot></slot>
			</div>
		</div>
	`,
};
