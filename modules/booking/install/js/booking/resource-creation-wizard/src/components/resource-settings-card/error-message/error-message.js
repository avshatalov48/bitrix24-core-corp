import './error-message.css';

export const ErrorMessage = {
	name: 'ErrorMessage',
	props: {
		message: {
			type: String,
			default: '',
		},
	},
	template: `
		<div class="booking--rcw--error-message-container">
			<div class="booking--rcw--error-message">
				<span class="ui-icon-set --warning"></span>
				<span>{{ message }}</span>
			</div>
		</div>
	`,
};
