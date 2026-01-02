import { DialogId, MessageId } from '../../../../types/common';

type CurrentAudioState = {
	uri: String,
	associatedId: String,
	title: String,
	imageUri: String,
	returnData: ReturnAudioData
}

type CurrentMediaState = {
	isPlaying: Boolean,
	currentAudio: CurrentAudioState
}

type ReturnAudioData = {
	associatedId: String,
	dialogId: DialogId,
	messageId: MessageId
}

export { CurrentMediaState, CurrentAudioState, ReturnAudioData };
