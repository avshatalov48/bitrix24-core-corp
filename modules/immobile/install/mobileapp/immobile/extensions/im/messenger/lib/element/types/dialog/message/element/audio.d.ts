import { AudioRate } from '../../../../../../model/types/application';

export type MessageAudio = {
	id: string | number,
	type: 'audio',
	localUrl: string | null,
	url: string | null,
	size: number | null,
	isPlaying: boolean,
	playingTime: number | null,
	rate: AudioRate,
}
