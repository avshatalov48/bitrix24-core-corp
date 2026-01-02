import type { UserStatusCodeType } from '../type';
import { UserStatus } from '../type';

export class StatusService
{
	static isSupported(statusCode: ?string): boolean
	{
		return Object.values(UserStatus).includes(statusCode);
	}

	static getFailoverStatus(): UserStatusCodeType
	{
		return UserStatus.Offline;
	}
}
