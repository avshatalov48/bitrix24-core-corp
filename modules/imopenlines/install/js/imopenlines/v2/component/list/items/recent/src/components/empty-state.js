// @vue/component
export const EmptyState = {
	name: 'EmptyState',
	computed:
	{
		message(): string
		{
			return this.loc('IMOL_LIST_RECENT_EMPTY_MESSAGE');
		},
	},
	methods:
	{
		loc(phraseCode: string): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode);
		},
	},
	template: `
		<div class="bx-imol-list-recent-empty-state__container">
			<p class="bx-im-list-openlines-empty-state__text">
				{{ message }}
			</p>
		</div>
	`,
};
