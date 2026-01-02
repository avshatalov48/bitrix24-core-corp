import { AudioRate } from '../../../../../../model/application/src/types';
import { TranscriptStatus } from '../../../../../../model/files/src/transcript/types';

export type Speech2Text = {
	text: string,
	textColor: string,
	status: TranscriptStatus,
}

export type MessageAudio = {
	id: string | number,
	type: 'audio',
	localUrl: string | null,
	url: string | null,
	size: number | null,
	isPlaying: boolean,
	playingTime: number | null,
	rate: AudioRate,
	speech2text: Speech2Text,
}
