/**
 * @module crm/timeline/item/ui/body/blocks/audio-block
 */
jn.define('crm/timeline/item/ui/body/blocks/audio-block', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { AudioPlayer } = require('layout/ui/audio-player');

	const DEFAULT_IMAGE_URL = '/bitrix/mobileapp/crmmobile/extensions/crm/timeline/item/ui/body/blocks/audio-block/images/icon.png';

	/**
	 * @class TimelineItemBodyAudioBlock
	 */
	class TimelineItemBodyAudioBlock extends TimelineItemBodyBlock
	{
		get imageUrl()
		{
			const url = BX.prop.getString(this.props, 'imageUrl', DEFAULT_IMAGE_URL);

			return url.startsWith('/') ? currentDomain + url : url;
		}

		get title()
		{
			return BX.prop.getString(this.props, 'title', null);
		}

		get fileName()
		{
			return BX.prop.getString(this.props, 'recordName', null);
		}

		get uri()
		{
			return currentDomain + this.props.src;
		}

		getBottomGap()
		{
			return this.fileName ? 20 : super.getBottomGap();
		}

		componentDidMount()
		{
			this.itemScopeEventBus.on('AudioPlayer::onPlay', ({ duration, currentTime, speed, uri, title }) => {
				if (duration && uri === this.uri)
				{
					this.openDetailCardTopToolbar('AudioPlayer', {
						uri,
						duration,
						currentTime,
						speed,
						title,
						play: true,
						uid: this.itemScopeEventBus.getUid(),
					});
				}
			});
		}

		render()
		{
			return View(
				{},
				new AudioPlayer({
					uri: this.uri,
					uid: this.itemScopeEventBus.getUid(),
					title: this.title,
					fileName: this.fileName,
					imageUri: encodeURI(this.imageUrl),
				}),
			);
		}
	}

	module.exports = { TimelineItemBodyAudioBlock };
});
