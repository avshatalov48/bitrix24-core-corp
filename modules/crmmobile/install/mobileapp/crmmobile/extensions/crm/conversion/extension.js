/**
 * @module crm/conversion
 */
jn.define('crm/conversion', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Url } = require('in-app-url/url');
	const { inAppUrl } = require('in-app-url');
	const { unique } = require('utils/array');
	const { EventEmitter } = require('event-emitter');
	const { get, isEmpty } = require('utils/object');
	const { NotifyManager } = require('notify-manager');
	const { TypeId, TypeName, Type } = require('crm/type');
	const { BackdropWizard } = require('layout/ui/wizard/backdrop');
	const { ConversionWizard } = require('crm/conversion/wizard');
	const { createConversionConfig } = require('crm/conversion/utils');
	const { CrmMode } = require('crm/crm-mode');

	const AJAX_ACTION = 'crmmobile.Conversion.getConversionMenuItems';

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
			this.layoutWidget = null;
			this.isFinished = false;
			this.openEntity = true;
			this.convertParams = {
				entityIds: {},
				categoryId: null,
				entityTypeIds: [],
				requiredConfig: {},
			};
			this.uid = Random.getString();
			this.parentEventEmitter = props.uid ? EventEmitter.createWithUid(props.uid) : null;
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.customEventEmitter.on('DetailCard::onCreate', this.handleOnCreateEntity.bind(this));
			this.customEventEmitter.on('DetailCard::onTabContentLoaded', this.validateDetailCard.bind(this));
		}

		get analytics()
		{
			return {
				...BX.componentParameters.get('analytics', {}),
				...(this.props?.analytics ?? {}),
			};
		}

		getPermissions()
		{
			const { data } = this.props;

			return data.permissions || {};
		}

		isReturnCustomer()
		{
			const { data } = this.props;

			return data.isReturnCustomer || false;
		}

		getEntityItemIds()
		{
			const { data } = this.props;

			if (!Array.isArray(data.items))
			{
				return [];
			}

			return unique(data.items.flatMap(({ entityTypeIds }) => entityTypeIds)).sort();
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
			const { entityTypeIds } = this.convertParams;
			const { entityTypeId } = this.props;
			if (entityTypeIds.length > 1)
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

		async execute(params)
		{
			try
			{
				this.convertParams = params;
				const data = await this.runConvert(params);
				const conversionResult = await this.dataValidationForConversion(data);
				const result = conversionResult && conversionResult.DATA;
				if (!result)
				{
					return null;
				}

				const errors = result.CHECK_ERRORS;
				if (errors)
				{
					await this.errorProcessing(errors);

					return this.execute(this.convertParams);
				}

				return result;
			}
			catch (error)
			{
				Conversion.showError(error);
			}

			return null;
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

			if (this.layoutWidget)
			{
				this.layoutWidget.close(() => {
					this.layoutWidget = null;
					inAppUrl.open(entityUrl, {
						analytics: this.analytics,
					});
				});
			}
			else
			{
				inAppUrl.open(entityUrl, {
					analytics: this.analytics,
				});
			}
		}

		handleOnFinishConverted(entityUrl)
		{
			const { onFinishConverted } = this.props;

			if (onFinishConverted)
			{
				// eslint-disable-next-line promise/catch-or-return
				onFinishConverted()
					.catch(console.error)
					.finally(() => this.openEntityUrl(entityUrl));
			}
			else
			{
				this.openEntityUrl(entityUrl);
			}
		}

		getComponent()
		{
			const { entityTypeId } = this.props;
			const component = COMPONENT_MAP[entityTypeId];

			if (!component)
			{
				console.error(`Conversion is not supported for entity type ${entityTypeId}`);

				return null;
			}

			return component;
		}

		getConversionConfig(params)
		{
			const { entityId, entityTypeId } = this.props;

			const {
				categoryId,
				context = {},
				entityIds = {},
				requiredConfig,
				entityTypeIds = [],
				enableSynchronization = false,
			} = params;

			const conversionConfig = createConversionConfig({ entityTypeIds, categoryId, requiredConfig });

			const entitiesParams = {
				entityId,
				entityTypeId,
			};

			Object.keys(conversionConfig).forEach((entityTypeName) => {
				entitiesParams[entityTypeName] = conversionConfig[entityTypeName].active;

				if (Array.isArray(entityIds[entityTypeName]) && entityIds[entityTypeName].length > 0)
				{
					context[entityTypeName] = entityIds[entityTypeName][0];
				}
			});

			return {
				...entitiesParams,
				data: {
					...entitiesParams,
					ACTION: 'CONVERT',
					MODE: 'CONVERT',
					ENTITY_ID: entityId,
					CONFIG: conversionConfig,
					CONTEXT: context,
					ENABLE_SYNCHRONIZATION: enableSynchronization ? 'Y' : 'N',
				},
			};
		}

		runConvert(params)
		{
			const component = this.getComponent();

			if (!component)
			{
				return Promise.reject();
			}

			const conversionConfig = this.getConversionConfig(params);
			this.processAnalyticsEvents(conversionConfig, this.analytics, 'attempt');

			return new Promise((resolve, reject) => {
				BX.ajax.runComponentAction(
					`bitrix:${component.name}`,
					'convert',
					{
						mode: component.mode,
						...conversionConfig,
					},
				).then((response) => {
					const status = !response || response.ERROR ? 'error' : 'success';
					resolve(response);
					this.processAnalyticsEvents(conversionConfig, this.analytics, status);
				}).catch((error) => {
					reject(error);
					this.processAnalyticsEvents(conversionConfig, this.analytics, 'error');
				});
			});
		}

		processAnalyticsEvents(config, analytics, status)
		{
			const preparedEvent = new AnalyticsEvent(analytics)
				.setStatus(status)
				.setP1(`crmMode_${CrmMode.getCrmModeFromCache().toLowerCase()}`)
				.setP2(`from_${Type.getCommonEntityTypeName(config.entityTypeId).toLowerCase()}`);

			const conversionTargetEntitiesTypes = ['COMPANY', 'CONTACT', 'DEAL', 'SMART_INVOICE', 'QUOTE'];
			const eventsToSend = [];
			conversionTargetEntitiesTypes.forEach((type) => {
				if (config[type.toLowerCase()] === 'Y')
				{
					eventsToSend.push(new AnalyticsEvent(preparedEvent).setType(type.toLowerCase()));
				}
			});

			eventsToSend.forEach((event) => event.send());
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

			return new Promise((resolve) => {
				jn.import('crm:required-fields').then(() => {
					const { RequiredFields } = require('crm/required-fields');
					RequiredFields.show({
						errors,
						parentWidget: this.layoutWidget,
						params: { entityId, entityTypeId },
						onSave: () => {
							if (this.parentEventEmitter)
							{
								this.parentEventEmitter.emit('DetailCard::reloadTabs');
							}

							resolve();
						},
						onCancel: () => {
							this.hideLoading(false);
						},
					});
				}).catch((error) => {
					this.hideLoading(false);
					console.error(error);
				});
			});
		}

		setLayoutWidget(layoutWidget)
		{
			this.layoutWidget = layoutWidget;
		}

		static fetch(props)
		{
			const { entityTypeId, entityId } = props;

			return BX.ajax.runAction(AJAX_ACTION, { json: { entityTypeId, entityId } })
				.then(({ data }) => data)
				.catch(Conversion.showError);
		}

		static showError(error)
		{
			NotifyManager.showDefaultError();
			console.error(error);
		}

		/**
		 * @param {object} props
		 * @param {string} props.entityTypeId
		 * @param {string} props.entityId
		 * @param {function} props.onFinishConverted
		 * @return {Promise<*>}
		 */
		static async open(props)
		{
			const conversionWizard = await Conversion.createConversionWizard(props);

			return Conversion.show(conversionWizard);
		}

		/**
		 * @param {ConversionWizard} conversionWizard
		 */
		static async show(conversionWizard)
		{
			const steps = conversionWizard.getSteps();
			const firstStep = steps[0].step;
			const mediumPositionHeight = firstStep && firstStep.getMediumPositionHeight();
			const { layoutWidget, wizard } = await BackdropWizard.open({ steps }, { mediumPositionHeight });

			conversionWizard.setLayoutWidget(layoutWidget);
			conversionWizard.setWizard(wizard);

			return layoutWidget;
		}

		/**
		 * @param {object} props
		 * @param {string} props.entityTypeId
		 * @param {string} props.entityId
		 * @param {function} props.onFinishConverted
		 * @return {Promise<*>}
		 */
		static async createConversionWizard(props)
		{
			const conversionData = await Conversion.fetch(props);
			const conversion = new Conversion({ ...props, data: conversionData });

			return new ConversionWizard({ conversion });
		}
	}

	module.exports = { Conversion };
});
