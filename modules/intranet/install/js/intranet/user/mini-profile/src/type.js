export type UserMiniProfileOptions = {
	userId: number,
	bindElement: HTMLElement,
};
export type UserRole = 'admin' | 'employee' | 'integrator' | 'collaber' | 'extranet' | 'visitor' | 'email' | 'shop' | 'external';

export type UserMiniProfileData = {
	access: {
		canChat: boolean,
	},
	baseInfo: UserData & {
		time: string,
		status: UserStatusType,
		utcOffset: number,
		role: UserRole | null,
		access: {
			canChat: boolean,
		},
	},
	detailInfo?: {
		personalMobile: string;
		innerPhone: string;
		email: string;
	} | null,
	structure?: StructureType | null,
};

export type UserData = {
	name: string;
	workPosition: string,
	avatar: string,
	id: number,
	url: string,
}

export type StructureType = {
	title: string,
	departmentDictionary: Record<string, DepartmentType>,
	userDepartmentIds: Array<number>,
	headDictionary: HeadDictionary,
	teams: Array<TeamType>,
};

export type HeadDictionary = Record<string, UserData>;

export type NodeType = {
	id: number,
	title: string,
}

export type DepartmentType = NodeType & {
	employeeCount: number,
	headIds: Array<number>,
	parentId: number | null,
};

export type TeamType = NodeType;

export type UserStatusType = {
	code: UserStatusCodeType,
	lastSeenTs?: number | null,
	vacationTs?: number | null,
};

export type UserStatusCodeType = 'online' | 'offline' | 'dnd' | 'vacation' | 'fired';
export const UserStatus: Record<string, UserStatusCodeType> = Object.freeze({
	Online: 'online',
	Offline: 'offline',
	DoNotDisturb: 'dnd',
	Vacation: 'vacation',
	Fired: 'fired',
});
