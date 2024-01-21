/**
 * @module crm/disabling-tools
 */
jn.define('crm/disabling-tools', (require, exports, module) => {
	const { Type } = require('crm/type');
	const { NotifyManager } = require('notify-manager');
	const { RunActionExecutor } = require('rest/run-action-executor');

	const CONTROLLER_ENDPOINT = 'crmmobile.DisablingTools';
	const STATIC_ENTITIES_LOADER = 'getSlidersCodesForDisabledStaticEntityIds';
	const ENTITY_SLIDER_CODE_LOADER = 'getEntitySliderCodeIfDisabled';
	const CRM_CODE_LOADER = 'getCrmSliderCodeIfDisabled';

	/**
	 * @class DisablingTools
	 */
	class DisablingTools
	{
		constructor(options = {})
		{
			this.ttl = options.ttl ?? 86_400_000;

			this.loadCrmSetting();
			this.loadStaticEntitiesTools();
		}

		/**
		 * @public
		 * @param entityTypeId
		 * @returns {Promise<String|null>}
		 */
		async getSliderCode(entityTypeId)
		{
			await NotifyManager.showLoadingIndicator();

			if (!this.hasCacheType('crm'))
			{
				await this.loadCrmSetting();
			}

			if (!this.hasCacheType(entityTypeId))
			{
				if (Type.isDynamicTypeById(entityTypeId))
				{
					await this.loadDynamicEntityTool(entityTypeId);
				}
				else
				{
					await this.loadStaticEntitiesTools();
				}
			}

			if (!this.isCrmAvailable() && !this.isExternal(entityTypeId))
			{
				NotifyManager.hideLoadingIndicatorWithoutFallback();

				return this.extractSliderCode('crm');
			}

			NotifyManager.hideLoadingIndicatorWithoutFallback();

			return this.extractSliderCode(entityTypeId);
		}

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
				return cacheData[type]?.code;
			}

			return cacheData[type];
		}

		isExternal(type)
		{
			if (!Type.isDynamicTypeById(type))
			{
				return false;
			}

			return this.getDynamicEntityCacheData(type)[type]?.isExternal;
		}

		/**
		 * @private
		 * @param type
		 * @returns {Object}
		 */
		getCacheDataByType(type)
		{
			if (type === 'crm')
			{
				return this.getCrmCacheData();
			}

			if (Type.isDynamicTypeById(type))
			{
				return this.getDynamicEntityCacheData(type);
			}

			return this.getStaticEntitiesCacheData();
		}

		/**
		 * @private
		 * @returns {Promise<void>}
		 */
		async loadCrmSetting()
		{
			await this.load(`${CONTROLLER_ENDPOINT}.${CRM_CODE_LOADER}`);
		}

		/**
		 * @private
		 * @returns {Object|null}
		 */
		getCrmCacheData()
		{
			return this.getCache(`${CONTROLLER_ENDPOINT}.${CRM_CODE_LOADER}`);
		}

		/**
		 * @private
		 * @returns {Promise<void>}
		 */
		async loadStaticEntitiesTools()
		{
			await this.load(`${CONTROLLER_ENDPOINT}.${STATIC_ENTITIES_LOADER}`);
		}

		/**
		 * @private
		 * @returns {Object|null}
		 */
		getStaticEntitiesCacheData()
		{
			return this.getCache(`${CONTROLLER_ENDPOINT}.${STATIC_ENTITIES_LOADER}`);
		}

		/**
		 * @private
		 * @param entityTypeId
		 * @returns {Promise<void>}
		 */
		async loadDynamicEntityTool(entityTypeId)
		{
			await this.load(`${CONTROLLER_ENDPOINT}.${ENTITY_SLIDER_CODE_LOADER}`, { entityTypeId });
		}

		/**
		 * @private
		 * @param entityTypeId
		 * @returns {Object|null}
		 */
		getDynamicEntityCacheData(entityTypeId)
		{
			return this.getCache(`${CONTROLLER_ENDPOINT}.${ENTITY_SLIDER_CODE_LOADER}`, { entityTypeId });
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
