import { runAction } from 'im.v2.lib.rest';
import { RestMethod } from 'imopenlines.v2.const';

export class InterceptService
{
	interceptDialog(dialogId: string): Promise
	{
		const queryParams = {
			data: {
				dialogId,
			},
		};

		return runAction(RestMethod.linesV2SessionIntercept, queryParams)
			.catch((error) => {
				console.error('Imol.InterceptDialog: request error', error);
			});
	}
}
