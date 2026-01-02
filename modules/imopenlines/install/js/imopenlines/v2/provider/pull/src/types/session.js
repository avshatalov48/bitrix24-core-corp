import type { RawMessage, RawChat } from 'im.v2.provider.service.types';
import type { RawSession } from 'imopenlines.v2.provider.service';

export type SessionUpdateParams = {
	chat: RawChat,
	message: RawMessage,
	session: RawSession,
	chatId: string,
	status: number,
};
