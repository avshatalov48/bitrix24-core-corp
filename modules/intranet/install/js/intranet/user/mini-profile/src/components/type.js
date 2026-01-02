import type { UserMiniProfileData } from '../type';
import type { ErrorStateType } from './error-state/type';

export type UserMiniProfileDataType = {
	isError: boolean,
	errorType: ErrorStateType | null,
	isLoaded: boolean,
	isLoading: boolean,
	isExpanded: boolean,
	isExpandBlocked: boolean,
	backendData: UserMiniProfileData | null,
};
