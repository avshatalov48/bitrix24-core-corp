import { Outline } from 'ui.icon-set.api.core';
import type { UserStatusType } from '../../../../type';

export const IconSettingByStatus: Record<UserStatusType, UserStatusIconSetting> = {
	online: {
		iconName: Outline.EARTH_WITH_CHECK,
		colorVar: '--ui-color-accent-main-success',
	},
	offline: {
		iconName: Outline.EARTH_WITH_CLOCK,
		colorVar: '--ui-color-accent-main-warning',
	},
	dnd: {
		iconName: Outline.EARTH_WITH_STOP,
		colorVar: '--ui-color-accent-main-alert',
	},
	vacation: {
		iconName: Outline.EARTH_WITH_TREE,
		colorVar: '--ui-color-accent-extra-aqua',
	},
	fired: {
		iconName: Outline.EARTH_WITH_CROSS,
		colorVar: '--ui-color-gray-50',
	},
};

export type UserStatusIconSetting = {
	iconName: string,
	colorVar: string,
};
