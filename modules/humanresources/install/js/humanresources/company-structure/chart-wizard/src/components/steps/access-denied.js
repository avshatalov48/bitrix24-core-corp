// @vue/component
export const AccessDenied = {
	name: 'AccessDenied',

	mounted(): void
	{
		// enable 'next' button
		this.$emit('applyData', {
			isDepartmentDataChanged: false,
			isValid: true,
		});
	},

	methods: {
		loc(phraseCode: string, replacements: {[p: string]: string} = {}): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
	},

	template: `
		<div class="chart-wizard__access-denied">
			<div class="chart-wizard__access-denied_icon"></div>
			<div class="chart-wizard__access-denied_title">
				{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ACCESS_DENIED_TITLE') }}
			</div>
			<div class="chart-wizard__access-denied_description">
				{{ loc('HUMANRESOURCES_COMPANY_STRUCTURE_WIZARD_ACCESS_DENIED_DESCRIPTION') }}
			</div>
		</div>
	`,
};
