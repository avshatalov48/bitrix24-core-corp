/**
 * @module im/messenger/controller/sidebar-v2/tabs/media/src/items/model
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/media/src/items/model', (require, exports, module) => {
	const { FileType } = require('im/messenger/const');
	const { makeLibraryImagePath } = require('im/messenger/assets');
	/**
	 * @class SidebarTabMediaItemModel
	 */
	class SidebarTabMediaItemModel
	{
		constructor(store, data = {})
		{
			this.data = data;

			this.file = store.getters['filesModel/getById'](data.fileId);
		}

		/**
		 * @public
		 * @return {string}
		 */
		getType()
		{
			return this.file.type;
		}

		/**
		 * @public
		 * @return {number}
		 */
		getId()
		{
			return this.file.id;
		}

		/**
		 * @public
		 * @return {string}
		 */
		getUrlShow()
		{
			return this.file.urlShow;
		}

		/**
		 * @public
		 * @return {string}
		 */
		getName()
		{
			return this.file.name;
		}

		/**
		 * @public
		 * @return {string}
		 */
		getPreviewUrl()
		{
			if (this.file.mediaUrl?.preview[250])
			{
				return this.file.mediaUrl.preview[250];
			}

			if (
				this.file.type === FileType.image
				&& !this.file.urlPreview
			)
			{
				return makeLibraryImagePath('image-preview.png', 'sidebar/media');
			}

			return this.file.urlPreview;
		}

		/**
		 * @public
		 * @return {number}
		 */
		getFileId()
		{
			return this.file.id;
		}

		/**
		 * @public
		 * @return {MessageId}
		 */
		getMessageId()
		{
			return this.file.messageId;
		}

		/**
		 * @public
		 * @return {number}
		 */
		getSortKey()
		{
			return -(new Date(this.file.date)).getTime();
		}

		/**
		 * @public
		 * @return {string}
		 */
		getSectionId()
		{
			const date = new Date(this.file.date);
			date.setDate(1);
			date.setHours(0, 0, 0, 0);

			return String(date.getTime());
		}

		/**
		 * @public
		 * @return {string}
		 */
		getDownloadUrl()
		{
			return this.file.urlDownload;
		}

		/**
		 * @public
		 * @returns {{fileId: *, type: string, urlShow: *, sectionId: *, insideSectionSortKey: number}}
		 */
		toMediaGridView()
		{
			if (!this.file)
			{
				return null;
			}

			return {
				fileId: this.getFileId(),
				type: this.getType(),
				name: this.getName(),
				urlShow: this.getUrlShow(),
				urlPreview: this.getPreviewUrl(),
				urlDownload: this.getDownloadUrl(),
				sectionId: this.getSectionId(),
				insideSectionSortKey: this.getSortKey(),
				customData: {
					messageId: this.getMessageId(),
				},
			};
		}
	}

	module.exports = { SidebarTabMediaItemModel };
});
