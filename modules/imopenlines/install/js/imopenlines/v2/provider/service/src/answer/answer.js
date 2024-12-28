import { runAction } from 'im.v2.lib.rest';
import { RestMethod } from 'imopenlines.v2.const';

export class AnswerService
{
	requestAnswer(dialogId: string): Promise
	{
		const queryParams = {
			data: {
				dialogId,
			},
		};

		return runAction(RestMethod.linesV2SessionAnswer, queryParams)
			.catch((error) => {
				console.error('Imol.OperatorAnswer: request error', error);
			});
	}
}
