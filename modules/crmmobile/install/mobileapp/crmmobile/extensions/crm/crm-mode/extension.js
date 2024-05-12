/**
 * @module crm/crm-mode
 */
jn.define('crm/crm-mode', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const AppTheme = require('apptheme');
	const { Type, TypeId } = require('crm/type');
	const { withPressed } = require('utils/color');
	const { capitalize } = require('utils/string');
	const { NotifyManager } = require('notify-manager');
	const store = require('statemanager/redux/store');
	const { fetchCrmKanbanList } = require('crm/statemanager/redux/slices/kanban-settings');
	const { BackdropWizard } = require('layout/ui/wizard/backdrop');
	const { prepareConversionFields, createConversionConfig } = require('crm/conversion/utils');
	const { wizardSteps, ModeStep, MODE, MODES, CONVERSION, FIELDS } = require('crm/crm-mode/wizard/steps');
	const AJAX_ACTIONS = {
		configCrmMode: 'crmmobile.Conversion.getConfigCrmMode',
		leadMode: '/bitrix/tools/crm_lead_mode.php',
		conversion: '/bitrix/components/bitrix/crm.lead.list/list.ajax.php',
	};
	const BUTTON_CANCEL_EVENT = 'Crm.LoadingProgress::cancelChangeCrmMode';
	const CONVERSION_TYPES_IDS = [TypeId.Deal, TypeId.Contact, TypeId.Company];
	const CRM_MODE_CACHE_NAME = 'CrmMode';

	/**
	 * @class CrmMode
	 */
	class CrmMode
	{
		constructor(props)
		{
			const { crmModeConfig } = props;

			this.wizard = null;
			this.category = null;
			this.changeCanceled = false;
			this.isProgress = false;
			this.crmModeConfig = crmModeConfig;
			this.layoutWidget = null;
			this.result = {
				[CONVERSION]: {
					moveCase: true,
					selectedEntities: [TypeId.Contact],
					categoryId: 0,
				},
				[FIELDS]: CONVERSION_TYPES_IDS,
				[MODE]: {
					mode: crmModeConfig.currentCrmMode,
				},
			};
			this.conversionData = {};
			this.cancelChangeCrmMode = this.cancelChangeCrmMode.bind(this);
			this.bindEvents();
		}

		bindEvents()
		{
			BX.addCustomEvent(BUTTON_CANCEL_EVENT, this.cancelChangeCrmMode);
		}

		unBindEvents()
		{
			BX.removeCustomEvent(BUTTON_CANCEL_EVENT, this.cancelChangeCrmMode);
		}

		handleOnChange(stepId, params)
		{
			this.result[stepId] = params;

			if (stepId === MODE)
			{
				this.changeMode(params);
			}
		}

		closeWidget()
		{
			if (this.layoutWidget)
			{
				this.layoutWidget.close();
			}
		}

		changeMode({ mode: selectedMode })
		{
			if (selectedMode === MODES.simple)
			{
				this.wizard.moveToNextStep();
			}
			else if (selectedMode === MODES.classic)
			{
				this.runChangeMode();
				this.closeWidget();
			}
		}

		getSteps()
		{
			const { existActiveLeads } = this.crmModeConfig;
			const steps = existActiveLeads ? wizardSteps : [ModeStep];

			return steps.map((WizardStep) => {
				const stepId = WizardStep.getId();
				const props = this.getStepsProps(stepId);

				return { id: stepId, step: new WizardStep(props) };
			});
		}

		getStepsProps(stepId)
		{
			const props = {
				getLayoutWidget: this.getLayoutWidget.bind(this),
				onClose: this.closeWidget.bind(this),
				onChange: (params) => {
					this.handleOnChange(stepId, params);
				},
				...this.result[stepId],
			};

			// eslint-disable-next-line default-case
			switch (stepId)
			{
				case MODE:
					props.onMoveToNextStep = this.findDefaultCategory.bind(this);
					props.onFinish = this.runChangeMode.bind(this);
					break;
				case CONVERSION:
					props.onMoveToNextStep = this.runPrepareConversion.bind(this);
					props.onFinish = this.runConversionLeads.bind(this);
					props.getCategory = this.getCategory.bind(this);
					break;
				case FIELDS:
					props.getFieldsConfig = this.getFieldsConfig.bind(this);
					props.onFinish = this.runConversionLeads.bind(this);
					break;
			}

			return props;
		}

		getFieldsConfig()
		{
			const { DATA } = this.conversionData;
			const fieldsConfig = prepareConversionFields(DATA);

			return fieldsConfig.map(({ id, type, data }) => ({
				id,
				type,
				data,
				onChange: (result) => {
					this.handleOnChange(FIELDS, result);
				},
			}));
		}

		setWidgetParams({ layoutWidget, wizard })
		{
			this.wizard = wizard;
			this.layoutWidget = layoutWidget;
		}

		getLayoutWidget()
		{
			return this.layoutWidget;
		}

		cancelChangeCrmMode()
		{
			BX.postComponentEvent('CrmTabs::reloadKanbanTab', []);
			this.changeCanceled = true;
			this.closeProgressBar(false);
			this.hapticsNotify(false);
		}

		runPrepareConversion()
		{
			const { categoryId, moveCase, selectedEntities } = this.result[CONVERSION];
			const entityData = {};
			selectedEntities.forEach((entityTypeId) => {
				entityData[`create${capitalize(Type.resolveNameById(entityTypeId).toLowerCase())}`] = 'Y';
			});
			// eslint-disable-next-line max-len
			const entities = CONVERSION_TYPES_IDS.filter((entityTypeId) => selectedEntities.includes(entityTypeId) || entityTypeId === TypeId.Deal);
			this.handleOnChange(FIELDS, entities);

			return this.ajaxPromise({
				url: AJAX_ACTIONS.leadMode,
				data: {
					action: 'setConverterConfig',
					dealCategoryId: categoryId,
					completeActivities: moveCase ? 'Y' : 'N',
					...entityData,
				},
			}).then(
				() => this.prepareConversionLeads(entities),
			).then((conversionData) => {
				this.conversionData = conversionData;
				const { DATA, ERRORS } = conversionData;
				if (Array.isArray(ERRORS) && ERRORS.length > 0)
				{
					NotifyManager.showErrors(ERRORS);

					return Promise.reject('error conversion leads');
				}

				return Promise.resolve({ finish: !DATA.REQUIRES_SYNCHRONIZATION });
			}).catch(console.error);
		}

		prepareConversionLeads(entityTypeIds)
		{
			const { categoryId } = this.result[CONVERSION];

			const config = createConversionConfig({
				categoryId,
				entityTypeIds,
				additionalEntityConfig: {
					[TypeId.Contact]: {
						initData: { defaultName: Loc.getMessage('MCRM_CRM_MODE_CONTACT_DEFAULT_NAME') },
					},
				},
			});

			return this.ajaxPromise({
				url: AJAX_ACTIONS.conversion,
				data: {
					ACTION: 'PREPARE_BATCH_CONVERSION',
					PARAMS: {
						GRID_ID: 'simpleCrmConvert',
						CONFIG: config,
						ENABLE_CONFIG_CHECK: 'N',
						ENABLE_USER_FIELD_CHECK: 'N',
						FILTER: {
							'=STATUS_SEMANTIC_ID': 'P',
						},
					},
				},
			});
		}

		runConversionLeads()
		{
			if (this.changeCanceled)
			{
				return Promise.reject();
			}

			const { DATA } = this.conversionData;
			const config = createConversionConfig({ entityTypeIds: this.result[FIELDS], requiredConfig: DATA.CONFIG });

			if (!this.isProgress)
			{
				NotifyManager.showLoadingIndicator();
			}

			this.ajaxPromise({
				url: AJAX_ACTIONS.conversion,
				data: {
					ACTION: 'PROCESS_BATCH_CONVERSION',
					PARAMS: {
						GRID_ID: 'simpleCrmConvert',
						CONFIG: config,
					},
				},
			}).then((result) => {
				const { ERRORS, STATUS } = result;
				if (Array.isArray(ERRORS) && ERRORS.length > 0)
				{
					this.onError();

					return Promise.reject('error conversion leads');
				}

				// eslint-disable-next-line default-case
				switch (STATUS)
				{
					case 'PROGRESS':
						this.showProgress(result);

						return this.runConversionLeads();
					case 'COMPLETED':
						this.showProgress(result);

						return this.runChangeMode();
					case 'ERROR':
						this.onError();

						return Promise.reject('error conversion leads');
				}

				return Promise.reject();
			}).catch(console.error);
		}

		onError(errors)
		{
			NotifyManager.showErrors(errors);
			this.closeProgressBar(false);
		}

		showProgress(result = {})
		{
			if (this.changeCanceled)
			{
				return;
			}

			const {
				PROCESSED_ITEMS: value,
				TOTAL_ITEMS: maxValue,
			} = result;

			const progressParams = this.isProgress ? { value } : this.getProgressParams(value, maxValue);

			BX.postComponentEvent('Crm.LoadingProgress::updateProgress', [progressParams]);

			if (!this.isProgress)
			{
				this.isProgress = true;
			}
		}

		getProgressParams(value, maxValue)
		{
			return {
				value,
				maxValue,
				show: true,
				title: Loc.getMessage('MCRM_CRM_MODE_PROGRESS_BAR_TITLE'),
				description: Loc.getMessage('MCRM_CRM_MODE_PROGRESS_BAR_DESCRIPTION'),
				button: {
					text: Loc.getMessage('MCRM_CRM_MODE_PROGRESS_BAR_BUTTON'),
					onClickEvent: BUTTON_CANCEL_EVENT,
					style: {
						marginTop: 30,
						fontSize: 17,
						paddingVertical: Application.getPlatform() === 'android' ? 2 : 8,
						paddingHorizontal: 34,
						backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
					},
				},
			};
		}

		showNotify()
		{
			const typeMode = this.result[MODE].mode.toUpperCase();

			Notify.showUniqueMessage(
				'',
				Loc.getMessage(`MCRM_CRM_MODE_PROGRESS_SUCCESS_CHANGE_NOTIFY_${typeMode}`),
				{ time: 3 },
			);
		}

		hapticsNotify(success)
		{
			if (success)
			{
				Haptics.notifySuccess();
			}
			else
			{
				Haptics.notifyFailure();
			}
		}

		closeProgressBar(success)
		{
			this.isProgress = false;
			this.reloadTabs(success);
			this.unBindEvents();
			BX.postComponentEvent('Crm.LoadingProgress::updateProgress', [
				{
					show: false,
				},
			]);
		}

		ajaxPromise({ url, data })
		{
			return new Promise((resolve, reject) => {
				BX.ajax({
					url,
					data,
					method: 'POST',
					dataType: 'json',
					tokenSaveRequest: true,
				}).then(resolve).catch((error) => {
					NotifyManager.showDefaultError();
					console.error(error);
					reject();
				});
			});
		}

		runChangeMode()
		{
			if (this.changeCanceled || !this.isChangeMode())
			{
				return;
			}
			const crmType = this.result[MODE].mode.toLowerCase();

			this.ajaxPromise({
				url: AJAX_ACTIONS.leadMode,
				data: {
					action: 'changeCrmType',
					crmType,
				},
			}).then((result) => {
				this.finishChangeMode(result, crmType);
			}).catch(console.error);
		}

		finishChangeMode({ success, error }, crmType)
		{
			const isSuccess = success === 'Y';

			if (error)
			{
				this.onError([{ message: Loc.getMessage('MCRM_CRM_MODE_PROGRESS_ERROR') }]);
			}

			if (isSuccess)
			{
				this.setCrmModeInCache(crmType);
				if (this.isProgress)
				{
					this.closeProgressBar(true);
				}

				this.reloadTabs(isSuccess);
				this.hapticsNotify(isSuccess);
				void this.loadEntities();

				console.log(`change crm to to ${crmType} success`);
			}
		}

		async loadEntities()
		{
			const { EntityDetailOpener } = await requireLazy('crm:entity-detail/opener');

			EntityDetailOpener.loadEntities();
		}

		isChangeMode()
		{
			const { currentCrmMode } = this.crmModeConfig;

			return this.result[MODE].mode !== currentCrmMode;
		}

		reloadTabs(success)
		{
			BX.postComponentEvent('CrmTabs::loadTabs', [{ isProgress: false }]);
			if (success)
			{
				this.showNotify();
			}
		}

		async getCategories()
		{
			return new Promise((resolve) => {
				store.dispatch(fetchCrmKanbanList({
					entityTypeId: TypeId.Deal,
				})).then((response) => {
					resolve(response.payload.data);
				}).catch((error) => {
					console.error(error);
				});
			});
		}

		getCategory()
		{
			return this.category;
		}

		async findDefaultCategory()
		{
			const { categories = [] } = await this.getCategories();
			this.category = categories.find(({ isDefault }) => isDefault) || categories[0];

			return Promise.resolve();
		}

		static getCrmModeRunActionExecutor()
		{
			return new RunActionExecutor(AJAX_ACTIONS.configCrmMode, {})
				.setCacheId(CRM_MODE_CACHE_NAME);
		}

		static getCrmModeFromCache()
		{
			const cache = CrmMode.getCrmModeRunActionExecutor().getCache();

			return cache.getData()?.data?.currentCrmMode ?? '';
		}

		setCrmModeInCache(crmMode)
		{
			const executor = CrmMode.getCrmModeRunActionExecutor();
			const cache = executor.getCache();
			const cacheData = cache.getData()?.data ?? null;
			if (cacheData)
			{
				const newCacheData = {
					status: 'success',
					data: {
						...cacheData,
						currentCrmMode: crmMode.toUpperCase(),
					},
					errors: [],
				};
				executor.getCache().saveData(newCacheData);
			}
		}

		static async getCrmModeConfig()
		{
			try
			{
				return new Promise((resolve) => {
					CrmMode.getCrmModeRunActionExecutor()
						.setHandler((response) => {
							if (response && response.status === 'success')
							{
								resolve(response.data);
							}
						})
						.call(true);
				})
					.catch((error) => {
						console.error(error);
					});
			}
			catch (e)
			{
				console.error(e);
			}

			return null;
		}

		static async loadCrmProps()
		{
			const crmModeConfig = await CrmMode.getCrmModeConfig();

			return { crmModeConfig };
		}

		static async openWizard()
		{
			const props = await CrmMode.loadCrmProps();
			const crmMode = new CrmMode(props);

			const backDropWizard = await BackdropWizard.open(
				{
					steps: crmMode.getSteps(),
				},
				{
					testId: 'changeCrmMode',
					mediumPositionPercent: 90,
					helpUrl: helpdesk.getArticleUrl('17596822'),
				},
			);

			crmMode.setWidgetParams(backDropWizard);
		}
	}

	module.exports = { CrmMode };
});
