/**
 * @module im/messenger-v2/controller/recent/service/render/dummy
 */
jn.define('im/messenger-v2/controller/recent/service/render/dummy', (require, exports, module) => {
	const { BaseRecentService } = require('im/messenger-v2/controller/recent/service/base');

	/**
	 * @implements {IRenderService}
	 * @class DummyRenderService
	 */
	class DummyRenderService extends BaseRecentService
	{
		onInit()
		{
			this.logger.log('onInit');
		}

		hasItemRendered(id)
		{
			this.logger.log('hasItemRendered: return false');

			return false;
		}

		setItems(itemList, options)
		{
			this.logger.log('setItems', itemList, options);
		}

		setPreparedItems(itemList, options)
		{
			this.logger.log('setPreparedItems', itemList, options);
		}

		deleteItems(itemList, options)
		{
			this.logger.log('deleteItems', itemList, options);
		}

		upsertItems(itemList, options)
		{
			this.logger.log('upsertItems', itemList, options);
		}

		upsertPreparedItems(itemList, options)
		{
			this.logger.log('upsertPreparedItems', itemList, options);
		}

		getSections()
		{
			this.logger.log('getSections: return empty array');

			return [];
		}

		showLoader(section)
		{
			this.logger.log(`showLoader: section = ${section}`);
		}

		hideLoader(section)
		{
			this.logger.log(`hideLoader: section = ${section}`);
		}

		renderInstant()
		{
			this.logger.log('renderInstant');
		}
	}

	module.exports = DummyRenderService;
});
