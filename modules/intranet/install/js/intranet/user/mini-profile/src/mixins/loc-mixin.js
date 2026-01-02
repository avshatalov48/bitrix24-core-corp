import { Loc } from 'main.core';

// @vue/mixin
export const LocMixin = {
	methods: {
		loc(code: string, replacements: ? {[key: string]: string} = null): ?string
		{
			return Loc.getMessage(code, replacements);
		},
		locPlural(code: string, value: number, replacements: ? {[key: string]: string} = null): ?string
		{
			return Loc.getMessagePlural(code, value, replacements);
		},
	},
};
