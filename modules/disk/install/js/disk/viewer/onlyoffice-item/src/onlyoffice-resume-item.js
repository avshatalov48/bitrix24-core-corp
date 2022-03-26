import {Reflection} from 'main.core';
import OnlyOfficeItem from "./onlyoffice-item";

export default class OnlyOfficeResumeItem extends OnlyOfficeItem
{
	constructor(options)
	{
		options = options || {};

		super(options);

		this.chatId = options.imChatId;
	}

	setPropertiesByNode (node: HTMLElement)
	{
		super.setPropertiesByNode(node);

		this.chatId = node.dataset.imChatId;
	}

	loadData ()
	{
		/** @see BXIM.callController.currentCall */
		if (!Reflection.getClass('BXIM.callController.currentCall'))
		{
			return super.loadData();
		}

		const messageId = BX.MessengerCommon.diskGetMessageId(this.chatId, this.objectId);
		if (!messageId)
		{
			return super.loadData();
		}

		const callId = BX.MessengerCommon.getMessageParam(messageId, 'CALL_ID');
		const callController = BXIM.callController;
		if (!callId || callId != callController.currentCall.id)
		{
			return super.loadData()
		}

		callController.unfold();
		callController.showDocumentEditor({
			type: BX.Call.Controller.DocumentType.Resume,
			force: true,
		});

		return new BX.Promise();
	}
}