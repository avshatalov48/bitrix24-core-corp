import { Messenger } from 'im.public';

export const CallTypes = {
	video: {
		id: 'video',
		locCode: 'CALL_CONTENT_CHAT_HEADER_VIDEOCALL',
		start: (dialogId: string) => {
			Messenger.startVideoCall(dialogId);
		},
	},
	audio: {
		id: 'audio',
		locCode: 'CALL_CONTENT_CHAT_HEADER_CALL_MENU_AUDIO',
		start: (dialogId: string) => {
			Messenger.startVideoCall(dialogId, false);
		},
	},
};
