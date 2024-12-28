import { Messenger } from 'im.public';
import { Layout } from 'im.v2.const';
import { LayoutManager } from 'im.v2.lib.layout';
import { runAction } from 'im.v2.lib.rest';
import { RestMethod } from 'imopenlines.v2.const';

export class TransferService
{
	chatTransfer(dialogId: string, transferId: string): Promise
	{
		void Messenger.openLines();

		LayoutManager.getInstance().setLastOpenedElement(Layout.openlinesV2.name, '');

		const queryParams = {
			data: {
				dialogId,
				transferId,
			},
		};

		return runAction(RestMethod.linesV2SessionTransfer, queryParams)
			.catch((error) => {
				console.error('Imol.transfer: request error', error);
			});
	}
}
