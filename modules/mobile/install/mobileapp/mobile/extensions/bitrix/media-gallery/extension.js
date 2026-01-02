/**
 * @module media-gallery
 */
jn.define('media-gallery', (require, exports, module) => {
	const { Feature } = require('feature');
	const { Gallery } = require('native/media');
	const { isEmpty } = require('utils/object');

	const CONTEXT_MENU_SECTION = 'media';

	/**
	 * @class MediaGallery
	 */
	class MediaGallery
	{
		constructor()
		{
			this.menuItemCallbacksMap = {};
			this.handleOnMenuItemSelected = this.handleOnMenuItemSelected.bind(this);
		}

		/**
		 * @returns {Promise<void>}
		 */
		close()
		{
			return Gallery.close();
		}

		/**
		 * @param {mediaGalleryItem} mediaItems
		 * @returns {Promise<Gallery>}
		 */
		open(mediaItems)
		{
			return Feature.isSupportedMediaGalleryCollection && Boolean(Gallery)
				? this.openGallery(mediaItems)
				: this.openCollection(mediaItems);
		}

		/**
		 * @param {mediaGalleryItem} mediaItems
		 * @returns {Promise<*>}
		 */
		async openGallery(mediaItems)
		{
			const gallery = await Gallery.open(this.prepareGalleryMediaItems(mediaItems));

			gallery.on('menuItemSelected', this.handleOnMenuItemSelected);

			return gallery;
		}

		/**
		 * @param {popupMenuItem} menuItem
		 * @param {mediaGalleryItem} galleryItem
		 */
		handleOnMenuItemSelected(menuItem, galleryItem)
		{
			const callbackHandler = this.menuItemCallbacksMap[menuItem.id];

			if (callbackHandler)
			{
				callbackHandler({ menuItem, galleryItem: galleryItem.customData });
			}
		}

		/**
		 * @param {mediaGalleryItem} mediaItems
		 * @returns {Promise}
		 */
		openCollection(mediaItems)
		{
			const selectedItem = this.findDefaultItem(mediaItems);

			if (selectedItem.type === 'video')
			{
				viewer.openVideo(selectedItem.url);
			}
			else
			{
				viewer.openImageCollection(this.prepareImageCollectionItems(mediaItems));
			}

			return Promise.resolve();
		}

		/**
		 * @returns {imageGalleryItem}
		 */
		prepareImageCollectionItems(mediaItems)
		{
			return mediaItems.map((mediaItem) => {
				return {
					url: mediaItem.url,
					default: mediaItem.default,
					previewUrl: mediaItem.previewUrl,
					description: mediaItem.description,
				};
			});
		}

		/**
		 * @param {Array<mediaGalleryItem>} mediaItems
		 * @returns {*}
		 */
		prepareGalleryMediaItems(mediaItems)
		{
			return mediaItems.map((mediaItem) => {
				return {
					...mediaItem,
					menu: this.prepareMenuItems(mediaItem.menu),
				};
			});
		}

		/**
		 * @param {mediaGalleryItemMenu} menu
		 * @returns {mediaGalleryItemMenu}
		 */
		prepareMenuItems(menu)
		{
			if (isEmpty(menu) && isEmpty(menu?.items))
			{
				return null;
			}

			const menuItems = Array.isArray(menu) ? menu : menu.items;

			const items = [];
			const sections = menu?.sections || this.getDefaultSection();

			menuItems.forEach((menuItem) => {
				const { id, sectionCode, onItemSelected, ...restMenuParams } = menuItem;

				if (onItemSelected)
				{
					this.menuItemCallbacksMap[id] = onItemSelected;
				}

				items.push({
					id,
					sectionCode: sectionCode ?? CONTEXT_MENU_SECTION,
					...restMenuParams,
				});
			});

			return {
				items,
				sections,
			};
		}

		/**
		 * @returns {[{id: string}]}
		 */
		getDefaultSection()
		{
			return [
				{ id: CONTEXT_MENU_SECTION },
			];
		}

		/**
		 * @param mediaItems
		 * @returns {mediaGalleryItem}
		 */
		findDefaultItem(mediaItems)
		{
			return mediaItems.find(({ default: defaultItem }) => Boolean(defaultItem)) ?? mediaItems[0];
		}
	}

	module.exports = {
		MediaGallery: new MediaGallery(),
	};
});
