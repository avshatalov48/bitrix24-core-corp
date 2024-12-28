/**
 * @module im/messenger/provider/pull/sidebar
 */
jn.define('im/messenger/provider/pull/sidebar', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/base/pull-handler');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const { Feature } = require('im/messenger/lib/feature');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--sidebar');

	/**
	 * @class SidebarPullHandler
	 */
	// class SidebarPullHandler
	class SidebarPullHandler extends BasePullHandler
	{
		// region links
		/**
		 * @param {SidebarLink} params.link
		 */
		handleUrlAdd(params, extra, command)
		{
			if (
				!this.isSidebarInited(params.link.chatId)
				|| !this.isLinksMigrated()
				|| this.interceptEvent(params, extra, command)
			)
			{
				return;
			}

			logger.info(`${this.constructor.name}.handleUrlAdd`, params);
			void this.store.dispatch('sidebarModel/sidebarLinksModel/set', {
				chatId: params.link.chatId,
				links: [params.link],
			});
		}

		/**
		 * @param {number} params.chatId
		 * @param {number} params.linkId
		 */
		handleUrlDelete(params, extra, command)
		{
			if (
				!this.isSidebarInited(params.chatId)
				|| !this.isLinksMigrated()
				|| this.interceptEvent(params, extra, command)
			)
			{
				return;
			}

			logger.info(`${this.constructor.name}.handleUrlDelete`, params);
			void this.store.dispatch('sidebarModel/sidebarLinksModel/delete', {
				chatId: params.chatId,
				id: params.linkId,
			});
		}
		// endregion

		// region files
		/**
		 * @param {SidebarFile} params.link
		 */
		handleFileAdd(params, extra, command)
		{
			const { chatId, subType } = params.link;
			if (
				!this.isSidebarInited(chatId)
				|| !this.isFilesMigrated()
				|| this.interceptEvent(params, extra, command)
			)
			{
				return;
			}

			logger.info(`${this.constructor.name}.handleFileAdd`, params);

			void this.store.dispatch('sidebarModel/sidebarFilesModel/set', {
				chatId,
				files: [params.link],
				subType,
			});
		}

		/**
		 * @param {number} params.link.chatId
		 * @param {number} params.link.linkId
		 */
		handleFileDelete(params, extra, command)
		{
			const { chatId, linkId } = params;
			if (
				!this.isFilesMigrated()
				|| !this.isSidebarInited(chatId)
				|| this.interceptEvent(params, extra, command)
			)
			{
				return;
			}

			logger.info(`${this.constructor.name}.handleFileDelete`, params);

			void this.store.dispatch('sidebarModel/sidebarFilesModel/delete', {
				chatId,
				id: linkId,
			});
		}
		// endregion

		/**
		 * @desc is the sidebar initialized
		 * param {number} chatId
		 * @return {boolean}
		 */
		isSidebarInited(chatId)
		{
			return this.store.getters['sidebarModel/isInited'](chatId);
		}

		/**
		 * @desc Has the files migration been completed?
		 * @return {boolean}
		 */
		isFilesMigrated()
		{
			return Feature.isSidebarFilesEnabled;
		}

		/**
		 * @desc Has the links migration been completed?
		 * @return {boolean}
		 */
		isLinksMigrated()
		{
			return Feature.isSidebarLinksEnabled;
		}
	}

	module.exports = {
		SidebarPullHandler,
	};
});
