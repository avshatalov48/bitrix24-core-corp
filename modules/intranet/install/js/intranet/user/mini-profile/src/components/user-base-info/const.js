import type { AvatarType } from 'ui.vue3.components.avatar';
import type { UserRole } from '../../type';

export const UserAvatarTypeByRole: Record<UserRole, AvatarType> = Object.freeze({
	collaber: 'round-guest',
	extranet: 'round-extranet',
	employee: 'round',
});
