/**
 * @module crm/disabling-tools
 */
jn.define('crm/disabling-tools', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { NotifyManager } = require('notify-manager');
	const { RunActionExecutor } = require('rest/run-action-executor');

	const STATIC_ENTITIES_LOAD_ACTION = 'crmmobile.DisablingTools.getSlidersCodesForDisabledStaticEntities';
	const ENTITY_SLIDER_CODE_LOAD_ACTION = 'crmmobile.DisablingTools.getEntitySliderCodeIfDisabled';

	/**
	 * @class DisablingTools
	 */
	class DisablingTools
	{
		constructor()
		{
			this.ttl = 3600;
		}

		/**
		 * @public
		 * @param entityTypeId
		 * @returns {Promise<String|null>}
		 */
		async getSliderCode(entityTypeId)
		{
			if (!this.hasCacheType(entityTypeId))
			{
				await NotifyManager.showLoadingIndicator();

				if (Type.isDynamicTypeById(entityTypeId))
				{
					await this.load(ENTITY_SLIDER_CODE_LOAD_ACTION, { entityTypeId });
				}
				else
				{
					await this.load(STATIC_ENTITIES_LOAD_ACTION);
				}
			}

			NotifyManager.hideLoadingIndicatorWithoutFallback();

			if (!this.isCrmAvailable() && !this.isExternal(entityTypeId))
			{
				return this.extractSliderCode('crm');
			}

			return this.extractSliderCode(entityTypeId);
		}

		/**
		 * @private
		 * @returns {boolean}
		 */
		isCrmAvailable()
		{
			return this.extractSliderCode('crm') === null;
		}

		/**
		 * @private
		 * @param type
		 * @returns {boolean}
		 */
		hasCacheType(type)
		{
			const cacheData = this.getCacheDataByType(type);

			if (!cacheData)
			{
				return false;
			}

			return Object.prototype.hasOwnProperty.call(cacheData, type);
		}

		/**
		 * @private
		 * @param type
		 * @returns {String|null}
		 */
		extractSliderCode(type)
		{
			const cacheData = this.getCacheDataByType(type);

			if (!cacheData)
			{
				return null;
			}

			if (Type.isDynamicTypeById(type))
			{
				return cacheData[type] ? cacheData[type].code : null;
			}

			return cacheData[type];
		}

		/**
		 * @private
		 * @param type
		 * @returns {boolean}
		 */
		isExternal(type)
		{
			if (!Type.isDynamicTypeById(type))
			{
				return false;
			}

			return this.getCache(ENTITY_SLIDER_CODE_LOAD_ACTION, { entityTypeId: type })[type]?.isExternal;
		}

		/**
		 * @private
		 * @param type
		 * @returns {Object}
		 */
		getCacheDataByType(type)
		{
			if (Type.isDynamicTypeById(type))
			{
				return this.getCache(ENTITY_SLIDER_CODE_LOAD_ACTION, { entityTypeId: type });
			}

			return this.getCache(STATIC_ENTITIES_LOAD_ACTION);
		}

		/**
		 * @private
		 * @param {String} action
		 * @param {Object} options
		 * @returns {Promise<void>}
		 */
		async load(action, options = {})
		{
			await new RunActionExecutor(action, options)
				.setCacheTtl(this.ttl)
				.call(false)
			;
		}

		/**
		 * @private
		 * @param {String} action
		 * @param {Object} options
		 * @returns {Object|null}
		 */
		getCache(action, options = {})
		{
			const cacheData = new RunActionExecutor(action, options)
				.setCacheTtl(this.ttl)
				.getCache()
				.getData();

			return cacheData ? cacheData.data : null;
		}
	}

	module.exports = { DisablingTools: new DisablingTools() };
});
