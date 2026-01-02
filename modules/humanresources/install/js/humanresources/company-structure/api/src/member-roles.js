import { EntityTypes } from 'humanresources.company-structure.utils';

export type MemberRolesType = {
	employee: string,
	head: string,
	deputyHead: string,
}

export const memberRolesKeys: Readonly<MemberRolesType> = Object.freeze({
	employee: 'employee',
	head: 'head',
	deputyHead: 'deputyHead',
});

export const memberRoles: Readonly<MemberRolesType> = Object.freeze({
	employee: 'MEMBER_EMPLOYEE',
	head: 'MEMBER_HEAD',
	deputyHead: 'MEMBER_DEPUTY_HEAD',
});

export const teamMemberRoles: Readonly<MemberRolesType> = Object.freeze({
	employee: 'MEMBER_TEAM_EMPLOYEE',
	head: 'MEMBER_TEAM_HEAD',
	deputyHead: 'MEMBER_TEAM_DEPUTY_HEAD',
});

export function getMemberRoles(entityType: $Values<typeof EntityTypes>): MemberRolesType
{
	return entityType === EntityTypes.team ? teamMemberRoles : memberRoles;
}
