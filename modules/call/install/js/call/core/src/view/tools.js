import Util from '../util';

export function logPlaybackError(error)
{
	console.error("Playback start error: ", error);
	
	Util.sendLog({
		description: 'Playback start error',
		error: error,
	});
}

export function checkAndEncodeURI(uri)
{
	return decodeURI(uri) === uri ? encodeURI(uri) : uri;
}