import { runAction } from 'im.v2.lib.rest';
import { RestMethod } from 'imopenlines.v2.const';

export class JoinService
{
	joinToDialog(dialogId: string): Promise
	{
		const queryParams = {
			data: {
				dialogId,
			},
		};

		return runAction(RestMethod.linesV2SessionJoin, queryParams)
			.catch((error) => {
				console.error('Imol.join: request error', error);
			});
	}
}
