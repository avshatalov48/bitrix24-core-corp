/**
 * @module crm/conversion
 */
jn.define('crm/conversion', (require, exports, module) => {
	const { get, isEmpty } = require('utils/object');
	const { Url } = require('in-app-url/url');
	const { EventEmitter } = require('event-emitter');
	const { inAppUrl } = require('in-app-url');
	const { NotifyManager } = require('notify-manager');
	const { TypeId, TypeName } = require('crm/type');
	const { ConversionMenu } = require('crm/conversion/menu');
	const { ConversionWizard } = require('crm/conversion/wizard');
	const { prepareConversionFields, prepareConversionConfig } = require('crm/conversion/utils');

	const COMPONENT_MAP = {
		[TypeId.Lead]: {
			name: 'crm.lead.show',
			mode: 'ajax',
		},
		[TypeId.Deal]: {
			name: 'crm.deal.details',
			mode: 'ajax',
		},
		[TypeId.Quote]: {
			name: 'crm.quote.details',
			mode: 'class',
		},
	};

	/**
	 * @class Conversion
	 */
	class Conversion
	{
		constructor(props)
		{
			this.props = props;
			this.isFinished = false;
			this.openEntity = true;
			this.convertParams = {
				entities: [],
				categoryId: null,
				entityIds: {},
			};
			this.uid = Random.getString();
			this.parentEventEmitter = props.uid ? EventEmitter.createWithUid(props.uid) : null;
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.customEventEmitter.on('DetailCard::onCreate', this.handleOnCreateEntity.bind(this));
			this.customEventEmitter.on('DetailCard::onTabContentLoaded', this.validateDetailCard.bind(this));
		}

		validateDetailCard(tabId)
		{
			if (tabId !== 'main')
			{
				return;
			}

			this.customEventEmitter.emit('DetailCard::validate');
		}

		handleOnCreateEntity(params)
		{
			const { entities } = this.convertParams;
			const { entityTypeId } = this.props;
			if (entities.length > 1)
			{
				this.closeEntityDetailCard();
			}
			else
			{
				this.openEntity = false;
			}

			if (entityTypeId === TypeId.Lead)
			{
				const context = this.getContextParams(params);
				this.execute({ ...this.convertParams, context });
			}
		}

		getContextParams(params)
		{
			if (isEmpty(params))
			{
				return {};
			}

			const { entityTypeId, entityId } = params;

			if (entityTypeId === TypeId.Deal)
			{
				return {
					[TypeName.Deal]: entityId,
					[`IS_RECENT_${TypeName.Deal}`]: true,
				};
			}

			return {};
		}

		closeEntityDetailCard()
		{
			this.customEventEmitter.emit('DetailCard::close');
		}

		execute(params)
		{
			NotifyManager.showLoadingIndicator();
			const { close, categoryId } = params;
			if (close)
			{
				this.hideLoading(false);

				return;
			}
			this.convertParams = params;

			this.runConvert(params)
				.then((data) => this.dataValidationForConversion(data))
				.then((result) => {
					const data = result && result.DATA;
					if (data)
					{
						const errors = data.CHECK_ERRORS;
						if (errors)
						{
							this.errorProcessing(errors);

							return;
						}

						this.openWizard(data, categoryId);
					}
				})
				.catch((error) => {
					NotifyManager.showDefaultError();
					console.error(error);
				});
		}

		openWizard(result, categoryId)
		{
			const { entityTypeId } = this.props;
			let layoutMenu = null;
			let finish = false;
			ConversionWizard.open({
				categoryId,
				entityTypeId,
				isLanding: true,
				data: prepareConversionFields(result),
				onFinish: (finishParams) => {
					if (!layoutMenu)
					{
						return;
					}

					finish = true;
					layoutMenu.close(() => {
						const { entityIds } = this.convertParams;

						this.execute({
							entityIds,
							requiredConfig: result.CONFIG,
							enableSynchronization: true,
							...finishParams,
						});
					});
				},
			}).then((menu) => {
				layoutMenu = menu;
				layoutMenu.setListener((eventName) => {
					if (eventName === 'onViewRemoved' && !finish)
					{
						this.hideLoading(false);
					}
				});
			});
		}

		hideLoading(success)
		{
			if (success === undefined)
			{
				NotifyManager.hideLoadingIndicatorWithoutFallback();
			}
			else
			{
				NotifyManager.hideLoadingIndicator(success);
			}
		}

		dataValidationForConversion(data)
		{
			if (!data || data.ERROR)
			{
				const { MESSAGE } = data.ERROR;
				NotifyManager.showErrors([{ message: MESSAGE }]);

				return null;
			}

			const { REQUIRED_ACTION, DATA } = data;

			if (REQUIRED_ACTION)
			{
				return REQUIRED_ACTION;
			}

			if (!DATA || !DATA.URL)
			{
				this.hideLoading();

				return null;
			}
			const { categoryId } = this.convertParams;
			const { URL, IS_FINISHED } = DATA;
			this.isFinished = IS_FINISHED === 'Y';
			let entityUrl = `${URL}&changeTab=N&uid=${this.uid}`;

			if (BX.type.isNumber(categoryId))
			{
				const url = new Url(entityUrl);
				const conversionEntityTypeId = get(url.queryParams, 'entityTypeId', null);
				if (conversionEntityTypeId && Number(conversionEntityTypeId) === TypeId.Deal)
				{
					entityUrl += `&categoryId=${categoryId}`;
				}
			}

			if (this.isFinished)
			{
				this.hideLoading(true);

				this.handleOnFinishConverted(entityUrl);
			}
			else
			{
				this.hideLoading();
				this.openEntityUrl(entityUrl);
			}

			return null;
		}

		openEntityUrl(entityUrl)
		{
			if (!this.openEntity)
			{
				return;
			}
			const timeToSeeLoadingIndicator = 200;

			setTimeout(() => inAppUrl.open(entityUrl), timeToSeeLoadingIndicator);
		}

		handleOnFinishConverted(entityUrl)
		{
			const { onFinishConverted } = this.props;

			if (onFinishConverted)
			{
				onFinishConverted().then(() => this.openEntityUrl(entityUrl));
			}
			else
			{
				this.openEntityUrl(entityUrl);
			}
		}

		runConvert(params)
		{
			const { entityId, entityTypeId } = this.props;
			const {
				entities,
				categoryId,
				context = {},
				entityIds = {},
				requiredConfig,
				enableSynchronization = false,
			} = params;

			const config = prepareConversionConfig({ entities, categoryId, requiredConfig });

			const entitiesParams = {};
			Object.keys(config).forEach((entityTypeName) => {
				entitiesParams[entityTypeName] = config[entityTypeName].active;

				if (Array.isArray(entityIds[entityTypeName]) && entityIds[entityTypeName].length > 0)
				{
					context[entityTypeName] = entityIds[entityTypeName][0];
				}
			});
			entitiesParams.entityId = entityId;
			entitiesParams.entityTypeId = entityTypeId;

			const component = COMPONENT_MAP[entityTypeId];
			if (!component)
			{
				console.error(`Conversion is not supported for entity type ${entityTypeId}`);
				return null;
			}

			return BX.ajax.runComponentAction(
				`bitrix:${component.name}`,
				'convert',
				{
					mode: component.mode,
					...entitiesParams,
					data: {
						...entitiesParams,
						ACTION: 'CONVERT',
						MODE: 'CONVERT',
						ENTITY_ID: entityId,
						CONFIG: config,
						CONTEXT: context,
						ENABLE_SYNCHRONIZATION: enableSynchronization ? 'Y' : 'N',
					},
				},
			);
		}

		errorProcessing(requiredFields)
		{
			const { entityId, entityTypeId } = this.props;
			const errors = Object.keys(requiredFields).map((fieldName) => ({
				code: 'CRM_FIELD_ERROR_REQUIRED',
				message: requiredFields[fieldName],
				customData: {
					fieldName,
					public: true,
				},
			}));

			jn.import('crm:required-fields').then(() => {
				const { RequiredFields } = require('crm/required-fields');

				RequiredFields.show({
					errors,
					params: { entityId, entityTypeId },
					onSave: () => {
						if (this.parentEventEmitter)
						{
							this.parentEventEmitter.emit('DetailCard::reloadTabs');
						}

						NotifyManager.showLoadingIndicator();
						this.execute(this.convertParams);
					},
					onCancel: () => {
						this.hideLoading(false);
					},
				});
			})
				.catch((error) => {
					this.hideLoading(false);
					console.error(error);
				});
		}

		getMenuProps()
		{
			return {
				...this.props,
				executeConversion: this.execute.bind(this),
			};
		}

		static createMenu(props)
		{
			const conversion = new Conversion(props);

			return ConversionMenu.create(conversion.getMenuProps());
		}
	}

	module.exports = { Conversion };
});
