import { ajax } from 'main.core';
import type { UserMiniProfileData } from '../type';

export class Backend
{
	static load(userId: number): Promise<UserMiniProfileData>
	{
		return new Promise((resolve, reject) => {
			ajax.runAction('intranet.user.miniProfile.load', {
				data: {
					userId,
				},
			})
				.then((result) => {
					resolve(result.data);
				})
				.catch((result) => {
					reject(result.errors[0]?.code ?? null);
				})
			;
		});
	}
}
