import { Loc } from 'main.core';
import type { ErrorStateType, ErrorStateSetting } from './type';

export const ErrorStateSettingByType: Record<ErrorStateType, ErrorStateSetting> = {
	default: {
		class: '--default',
		title: Loc.getMessage('INTRANET_USER_MINI_ERROR_STATE_TITLE'),
		description: Loc.getMessage('INTRANET_USER_MINI_ERROR_STATE_DESCRIPTION'),
	},
	'access-denied': {
		class: '--access-denied',
		title: Loc.getMessage('INTRANET_USER_MINI_ERROR_STATE_ACCESS_DENIED_TITLE'),
		description: Loc.getMessage('INTRANET_USER_MINI_ERROR_STATE_ACCESS_DENIED_DESCRIPTION'),
	},
};

export const ErrorStateDict: Record<string, ErrorStateType> = Object.freeze({
	AccessDenied: 'access-denied',
	Default: 'default',
});
