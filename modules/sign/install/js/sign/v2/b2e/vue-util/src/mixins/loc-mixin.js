export const LocMixin = {
	methods: {
		loc(phraseCode: string, replacements?: { [key: string]: string }): string
		{
			return this.$Bitrix.Loc.getMessage(phraseCode, replacements);
		},
	},
};
