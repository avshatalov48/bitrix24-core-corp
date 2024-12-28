export type ForwardMessage = {
	username: string,
	message: [
		{
			type: string,
			text: string,
		}],
	id: ForwardMessageId,
}

export type ForwardMessageId = number
export type ForwardMessageIds = Array<ForwardMessageId>
