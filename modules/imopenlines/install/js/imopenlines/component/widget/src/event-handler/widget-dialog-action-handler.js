import { DialogActionHandler } from "im.event-handler";
import { EventEmitter } from "main.core.events";
import { WidgetEventType } from "../const";

export class WidgetDialogActionHandler extends DialogActionHandler
{
	onClickOnDialog()
	{
		EventEmitter.emit(WidgetEventType.hideForm);
	}
}