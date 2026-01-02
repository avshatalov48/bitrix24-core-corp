import { Loc } from 'main.core';
import type { UserStatusCodeType } from '../../../../type';

export const StaticDescriptionByStatus: Record<UserStatusCodeType, string> = {
	online: Loc.getMessage('INTRANET_USER_MINI_PROFILE_USER_STATUS_ONLINE'),
	dnd: Loc.getMessage('INTRANET_USER_MINI_PROFILE_USER_STATUS_DND'),
	fired: Loc.getMessage('INTRANET_USER_MINI_PROFILE_USER_STATUS_FIRED'),
};
