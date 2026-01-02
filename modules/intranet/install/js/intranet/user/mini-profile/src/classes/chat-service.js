export class ChatService
{
	static openMessenger(userId: number): void
	{
		top.BX.Messenger.Public?.openChat(String(userId));
	}

	static call(userId: number, withVideo: boolean): void
	{
		top.BX.Messenger.Public?.startVideoCall(String(userId), withVideo);
	}

	static isMessengerAvailable(): boolean
	{
		return Boolean(top.BX.Messenger.Public);
	}
}
