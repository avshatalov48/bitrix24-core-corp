/**
 * @module im/messenger/controller/sidebar-v2/tabs/audio/src/content
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/audio/src/content', (require, exports, module) => {
	const { SidebarFilesTabContent } = require('im/messenger/controller/sidebar-v2/tabs/files/src/content');
	const { AudioItem } = require('im/messenger/controller/sidebar-v2/tabs/audio/src/item');
	const { Loc } = require('im/messenger/controller/sidebar-v2/loc');

	/**
	 * @class SidebarAudioTabContent
	 */
	class SidebarAudioTabContent extends SidebarFilesTabContent
	{
		constructor(props)
		{
			super(props);

			/** @type {AudioItem|null} */
			this.currentPlayingItem = null;

			/**
			 * @type {Map<Number, AudioItem>}
			 */
			this.itemsMap = new Map();
		}

		/**
		 * @param {SidebarTabListItemModel} item
		 * @param {SidebarFile} item.data
		 * @returns {AudioItem}
		 */
		renderItem(item)
		{
			return new AudioItem({
				...item.data,
				dialogId: this.props.dialogData.dialogId,
				widgetNavigator: this.props.widgetNavigator,
				ref: (ref) => {
					if (ref)
					{
						this.itemsMap.set(ref.getId(), ref);
					}
				},
				stopCurrentPlayingItem: this.stopCurrentPlayingItem,
				setCurrentPlayingItem: this.setCurrentPlayingItem,
				getCurrentPlayingItem: this.getCurrentPlayingItem,
				onFinish: this.startNextItem,
			});
		}

		getCurrentPlayingItem = () => {
			return this.currentPlayingItem;
		};

		stopCurrentPlayingItem = () => {
			if (this.currentPlayingItem)
			{
				this.currentPlayingItem.stop();
			}
		};

		setCurrentPlayingItem = (item) => {
			this.currentPlayingItem = item;
			const stateItemIndex = this.state.items.findIndex((stateItem) => {
				return stateItem.getId() === item.getId();
			});

			if (stateItemIndex < 1)
			{
				this.setNextItem(null);
			}
			else
			{
				const nextItem = this.itemsMap.get(this.state.items[stateItemIndex - 1]?.getId());
				this.setNextItem(nextItem);
			}
		};

		setNextItem = (item) => {
			this.nextItem = item;
		};

		startNextItem = () => {
			return this.nextItem?.start();
		};

		getEmptyScreenProps()
		{
			return {
				testId: 'audio',
				title: Loc.getMessage('IMMOBILE_SIDEBAR_V2_COMMON_TAB_AUDIO_EMPTY_SCREEN_TITLE'),
				image: 'audio.svg',
			};
		}
	}

	module.exports = { SidebarAudioTabContent };
});
