import { runAction } from 'im.v2.lib.rest';
import { RestMethod } from 'imopenlines.v2.const';

export class StartService
{
	startDialog(dialogId: string): Promise
	{
		const queryParams = {
			data: {
				dialogId,
			},
		};

		return runAction(RestMethod.linesV2SessionStart, queryParams)
			.catch((error) => {
				console.error('Imol.start: request error', error);
			});
	}
}
