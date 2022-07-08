import {ReadingHandler} from "im.event-handler";

export class WidgetReadingHandler extends ReadingHandler
{
	application: Object = null;

	constructor($Bitrix)
	{
		super($Bitrix);
		this.application = $Bitrix.Application.get();
	}

	readMessage(messageId, skipTimer = false, skipAjax = false): Promise
	{
		if (this.application.offline)
		{
			return false;
		}

		return super.readMessage(messageId, skipTimer, skipAjax);
	}
}