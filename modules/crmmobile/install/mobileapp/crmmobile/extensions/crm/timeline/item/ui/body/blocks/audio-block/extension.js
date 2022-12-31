/**
 * @module crm/timeline/item/ui/body/blocks/audio-block
 */
jn.define('crm/timeline/item/ui/body/blocks/audio-block', (require, exports, module) => {

	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { AudioPlayer } = require('layout/ui/audio-player');

	const DEFAULT_IMAGE_URL = '/bitrix/mobileapp/crmmobile/extensions/crm/timeline/item/ui/body/blocks/audio-block/images/icon.png';

	/**
	 * @class TimelineItemBodyTextBlock
	 */
	class TimelineItemBodyAudioBlock extends TimelineItemBodyBlock
	{
		get imageUrl()
		{
			const url =  BX.prop.getString(this.props, 'imageUrl', DEFAULT_IMAGE_URL);

			return url.startsWith('/') ? currentDomain + url : url;
		}

		get title()
		{
			return BX.prop.getString(this.props, 'title', null);
		}

		render()
		{
			return View(
				{},
				new AudioPlayer({
					uri: currentDomain + this.props.src,
					uid: this.itemScopeEventBus.getUid(),
					title: this.title,
					imageUri: encodeURI(this.imageUrl),
				}),
			);
		}
	}

	module.exports = { TimelineItemBodyAudioBlock };

});