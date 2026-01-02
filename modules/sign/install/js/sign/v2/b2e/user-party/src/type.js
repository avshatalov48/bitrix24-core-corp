import type { MemberRoleType } from 'sign.type';

export const UserPartyViewMode = {
	edit: 'edit',
	view: 'view',
};

export type UserPartyOptions = {
	mode: ?string,
	preselectedUserIds: [],
	b2eSignersLimitCount: ?Number,
	role: MemberRoleType,
}
