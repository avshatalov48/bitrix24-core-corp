import { Loc, Type } from 'main.core';

export const ErrorBlock = {
	data(): Object
	{
		return {
			errorText: null,
		};
	},

	methods: {
		setErrorMessage(message: string): void
		{
			this.errorText = message;
		},
	},

	computed: {
		explanationText(): string
		{
			return (
				Type.isStringFilled(this.errorText)
					? this.errorText
					: Loc.getMessage('CRM_COPILOT_CALL_QUALITY_ERROR_TEXT')
			);
		},
	},

	template: `
		<div class="call-quality__explanation">
			<div class="call-quality__explanation__container --error">
				<div class="call-quality__explanation-title">
					{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_ERROR_TITLE') }}
				</div>
				<div class="call-quality__explanation-text">
					{{ explanationText }}
				</div>
			</div>
		</div>
	`,
};
