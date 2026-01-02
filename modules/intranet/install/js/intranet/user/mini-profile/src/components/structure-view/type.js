import type { DepartmentType } from '../../type';

export type LockedDepartmentType = 'locked-department';

export type BranchProp = Array<DepartmentType | LockedDepartmentType>;

export type StructureViewListDataType = {
	blocks: Array<HTMLElement>,
	connectorBindElementPairs: Array<Array<HTMLElement>>
};
