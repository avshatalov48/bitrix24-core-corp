import { ThemeManager } from 'im.v2.lib.theme';

import '../css/empty-state.css';

import type { BackgroundStyle } from 'im.v2.lib.theme';

// @vue/component
export const EmptyState = {
	computed:
	{
		backgroundStyle(): BackgroundStyle
		{
			return ThemeManager.getCurrentBackgroundStyle();
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
		<div class="bx-imol-content-openlines-start__container" :style="backgroundStyle">
			<div class="bx-imol-content-openlines-start__content">
				<div class="bx-imol-content-openlines-start__icon --default"></div>
				<div class="bx-imol-content-openlines-start__title">
					{{ loc('IMOL_CONTENT_START_MESSAGE') }}
				</div>
			</div>
		</div>
	`,
};
