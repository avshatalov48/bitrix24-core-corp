<?

namespace Bitrix\Transformer;

/**
 * Class Video
 * High-level logic to work with commands for video.
 * @package Bitrix\Transformer
 */
class VideoTransformer extends FileTransformer
{
	const MAX_FILESIZE = 3221225472;

	const MP4 = 'mp4';

	protected function getCommandName()
	{
		return 'Bitrix\TransformerController\Video';
	}

	protected function getFileTypeName()
	{
		return 'Video';
	}
}