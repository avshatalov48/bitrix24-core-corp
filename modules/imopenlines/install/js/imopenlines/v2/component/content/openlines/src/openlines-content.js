import 'imopenlines.v2.css.tokens';

import { OpenLinesOpener } from './components/openers/openlines-opener';

// @vue/component
export const OpenLinesContent = {
	name: 'OpenLinesContent',
	components: { OpenLinesOpener },
	props:
	{
		entityId: {
			type: String,
			default: '',
		},
	},
	template: `
		<OpenLinesOpener :dialogId="entityId" />
	`,
};
