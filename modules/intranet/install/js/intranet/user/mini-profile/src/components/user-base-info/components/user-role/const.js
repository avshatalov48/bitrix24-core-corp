import { Loc } from 'main.core';
import type { UserRole } from '../../../../type';

export const UserRoleTitleByCode: Record<UserRole, string> = {
	shop: Loc.getMessage('INTRANET_USER_MINI_PROFILE_ROLE_SHOP'),
	email: Loc.getMessage('INTRANET_USER_MINI_PROFILE_ROLE_EMAIL'),
	integrator: Loc.getMessage('INTRANET_USER_MINI_PROFILE_ROLE_INTEGRATOR'),
	visitor: Loc.getMessage('INTRANET_USER_MINI_PROFILE_ROLE_VISITOR'),
};
