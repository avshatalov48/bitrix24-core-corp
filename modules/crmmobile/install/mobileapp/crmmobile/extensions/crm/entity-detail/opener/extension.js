/**
 * @module crm/entity-detail/opener
 */
jn.define('crm/entity-detail/opener', (require, exports, module) => {

	const { Alert } = require('alert');
	const { NotifyManager } = require('notify-manager');
	const { EntitySvg } = require('crm/assets/entity');
	const { getEntityMessage } = require('crm/loc');
	const { Type, TypeId } = require('crm/type');
	const { Type: CoreType } = require('type');
	const { mergeImmutable } = require('utils/object');

	const CACHE_TTL = 60 * 60 * 24; // 1 day

	let storage;
	let inMemoryEntities = null;
	let inMemoryTtl = null;

	const SUPPORTED_ENTITIES = [
		TypeId.Contact,
		TypeId.Company,
		TypeId.Deal,
	];

	/**
	 * @class EntityDetailOpener
	 */
	class EntityDetailOpener
	{
		/**
		 * @public
		 * @param {Number} entityTypeId
		 * @returns {boolean}
		 */
		static supportsEntityType(entityTypeId)
		{
			return SUPPORTED_ENTITIES.includes(entityTypeId);
		}

		/**
		 * @public
		 * @param {Object} payload
		 * @param {Number} payload.entityTypeId
		 * @param {Number?} payload.entityId
		 * @param {Number?} payload.categoryId
		 * @param {Object} widgetParams
		 * @param parentWidget
		 * @param canOpenInDefault
		 */
		static open(payload, widgetParams = {}, parentWidget = null, canOpenInDefault = false)
		{
			widgetParams = mergeImmutable(this.getModalWidgetParams(), widgetParams);

			const { entityTypeId } = payload;

			this
				.checkAvailability(entityTypeId)
				.then(() => {
					widgetParams.titleParams = this.prepareTitleParams(payload, widgetParams.titleParams);

					ComponentHelper.openLayout(
						{
							name: 'crm:crm.entity.details',
							componentParams: { payload },
							widgetParams,
							canOpenInDefault,
						},
						parentWidget,
					);
				})
				.catch((error) => {
					console.error(error);
					this.showAlert(error, entityTypeId);
				})
			;
		}

		/**
		 * @private
		 * @internal
		 */
		static getModalWidgetParams()
		{
			return {
				modal: true,
				leftButtons: [{
					// type: 'cross',
					svg: {
						content: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M14.722 6.79175L10.9495 10.5643L9.99907 11.5L9.06666 10.5643L5.29411 6.79175L3.96289 8.12297L10.008 14.1681L16.0532 8.12297L14.722 6.79175Z" fill="#A8ADB4"/></svg>',
					},
					isCloseButton: true,
				}],
			};
		}

		/**
		 * @internal
		 */
		static init()
		{
			if (this.cacheExpired())
			{
				// fake timeout to avoid affecting core queries
				setTimeout(() => this.loadEntities(), 100);
			}
		}

		/**
		 * @private
		 * @internal
		 *
		 * @return {KeyValueStorage}
		 */
		static getStorage()
		{
			if (!storage)
			{
				storage = Application.storageById(`crm/entity-detail/opener/${env.languageId}`);
			}

			return storage;
		}

		/**
		 * @private
		 * @internal
		 */
		static updateStorage(entities)
		{
			this.setEntities(entities);
			this.setTtlValue(this.getCurrentTimeInSeconds());
		}

		/**
		 * @private
		 * @internal
		 */
		static getEntities()
		{
			if (inMemoryEntities === null)
			{
				inMemoryEntities = this.getStorage().getObject('entities', []);
			}

			return inMemoryEntities;
		}

		/**
		 * @private
		 * @internal
		 */
		static setEntities(entities)
		{
			inMemoryEntities = entities;

			return this.getStorage().setObject('entities', entities);
		}

		/**
		 * @private
		 * @internal
		 */
		static getTtlValue()
		{
			if (inMemoryTtl === null)
			{
				inMemoryTtl = this.getStorage().getNumber('ttl', 0);
			}

			return inMemoryTtl;
		}

		/**
		 * @private
		 * @internal
		 */
		static setTtlValue(ttl)
		{
			inMemoryTtl = ttl;

			return this.getStorage().setNumber('ttl', ttl);
		}

		/**
		 * @private
		 * @internal
		 */
		static cacheExpired(ttl = CACHE_TTL)
		{
			const cacheTime = this.getTtlValue();
			const currentTime = this.getCurrentTimeInSeconds();

			return currentTime > cacheTime + ttl;
		}

		/**
		 * @private
		 * @internal
		 */
		static getCurrentTimeInSeconds()
		{
			return Math.floor(Date.now() / 1000);
		}

		/**
		 * @private
		 * @internal
		 */
		static prepareTitleParams({ entityId, entityTypeId }, titleParams = {})
		{
			const entity = this.findEntityType(entityTypeId);
			if (!entity)
			{
				return titleParams;
			}

			const entityTitleParams = {
				useLargeTitleMode: false,
				detailTextColor: '#a8adb4',
			};

			if (entityId)
			{
				if (!CoreType.isStringFilled(titleParams.text))
				{
					titleParams.text = `${entity.title} #${entityId}`;
				}

				entityTitleParams.detailText = entity.title;
			}
			else
			{
				entityTitleParams.text = getEntityMessage('MCRM_ENTITY_DETAIL_OPENER_CREATE_TEXT', entity.entityTypeName);
			}

			const iconFunctionName = entity['entityTypeName'].toLowerCase() + 'Inverted';
			if (EntitySvg[iconFunctionName])
			{
				entityTitleParams.svg = {
					content: EntitySvg[iconFunctionName](),
				};
			}

			return mergeImmutable(entityTitleParams, titleParams);
		}

		/**
		 * @private
		 * @internal
		 */
		static checkAvailability(entityTypeId)
		{
			if (!entityTypeId || !Type.existsById(entityTypeId))
			{
				return Promise.reject();
			}

			let loading = false;
			let promise = Promise.resolve();

			if (this.cacheExpired())
			{
				loading = true;
				promise = promise.then(() => this.loadEntities(true));
			}

			return (
				promise
					.then(() => new Promise((resolve, reject) => {
							let entity = this.findEntityType(entityTypeId);
							if (entity)
							{
								this.resolveEntity(entity, resolve, reject);
							}
							else if (this.cacheExpired(5))
							{
								// retry first reject
								loading = true;
								this
									.loadEntities(true)
									.then(() => {
										entity = this.findEntityType(entityTypeId);
										if (entity)
										{
											this.resolveEntity(entity, resolve, reject);
										}
										else
										{
											reject();
										}
									})
								;
							}
							else
							{
								reject();
							}
						}),
					)
					.finally(() => {
						if (loading)
						{
							NotifyManager.hideLoadingIndicatorWithoutFallback();
						}
					})
			);
		}

		/**
		 * @private
		 * @internal
		 */
		static findEntityType(entityTypeId)
		{
			return this.getEntities().find((entity) => entity.entityTypeId === entityTypeId);
		}

		/**
		 * @private
		 * @internal
		 */
		static loadEntities(showLoader = false)
		{
			if (showLoader)
			{
				NotifyManager.showLoadingIndicator();
			}

			return new Promise((resolve, reject) => {
				BX.ajax.runAction('crmmobile.EntityDetails.getAvailableEntityTypes', { json: {} })
					.then(({ data }) => {
						this.updateStorage(data);
						resolve();
					})
					.catch(reject)
				;
			});
		}

		/**
		 * @private
		 * @internal
		 */
		static resolveEntity(entity, resolve, reject)
		{
			if (entity.hasOwnProperty('supported') && !entity.supported)
			{
				reject({
					title: getEntityMessage('MCRM_ENTITY_DETAIL_OPENER_NOT_SUPPORTED_TITLE', entity.entityTypeId),
					text: BX.message('MCRM_ENTITY_DETAIL_OPENER_NOT_SUPPORTED_TEXT'),
				});
			}
			else
			{
				resolve();
			}
		};

		/**
		 * @private
		 * @internal
		 */
		static showAlert(error, entityTypeId)
		{
			Alert.alert(
				error && error.title || getEntityMessage('MCRM_ENTITY_DETAIL_OPENER_ALERT_TITLE2', entityTypeId),
				error && error.text || BX.message('MCRM_ENTITY_DETAIL_OPENER_ALERT_TEXT2'),
			);
		}
	}

	EntityDetailOpener.init();

	module.exports = { EntityDetailOpener };
});
