import { DashboardGroup } from './dashboard-group';
import { GroupType, type Group as group, type Scope as scope, type Dashboard as dashboard } from './type';

import './style.css';

export type Group = group;
export type Scope = scope;
export type Dashboard = dashboard;
export {
	DashboardGroup,
	GroupType,
};
