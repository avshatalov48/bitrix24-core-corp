/**
 * @bxjs_lang_path extension.php
 * @module crm/kanban/toolbar/entity-toolbar
 */
jn.define('crm/kanban/toolbar/entity-toolbar', (require, exports, module) => {
	const { isEqual } = require('utils/object');
	const { NavigationLoader } = require('navigation-loader');
	const { CategoryStorage } = require('crm/storage/category');
	const { CategoryCountersStoreManager } = require('crm/state-storage');
	const { KanbanToolbar, StageSummary, StageDropdown } = require('layout/ui/kanban/toolbar');
	const { Loc } = require('loc');

	const MAX_FORMATTED_SUM = 1_000_000_000;

	class EntityToolbar extends KanbanToolbar
	{
		constructor(props)
		{
			super(props);

			CategoryStorage
				.subscribeOnChange(() => this.reloadCategory())
				.subscribeOnLoading(({ status }) => NavigationLoader.setLoading(status))
				.markReady()
			;

			this.state = {
				...this.state,
				category: this.getCategoryByProps(props),
				categoryCounters: this.getCategoryCounters(),
			};

			this.updateCountersHandler = this.updateCounters.bind(this);
		}

		componentWillReceiveProps(newProps)
		{
			this.state.category = this.getCategoryByProps(newProps);
		}

		componentDidMount()
		{
			CategoryCountersStoreManager
				.subscribe('categoryCountersModel/init', this.updateCountersHandler)
				.subscribe('categoryCountersModel/updateStage', this.updateCountersHandler)
			;
		}

		componentWillUnmount()
		{
			CategoryCountersStoreManager
				.unsubscribe('categoryCountersModel/init', this.updateCountersHandler)
				.unsubscribe('categoryCountersModel/updateStage', this.updateCountersHandler)
			;
		}

		getCategoryByProps(props)
		{
			const {
				entityTypeId,
				filterParams: {
					CATEGORY_ID: categoryId,
				},
			} = props;

			return CategoryStorage.getCategory(entityTypeId, categoryId);
		}

		getCategoryCounters()
		{
			return CategoryCountersStoreManager.getStages();
		}

		updateCounters()
		{
			this.setState({
				categoryCounters: CategoryCountersStoreManager.getStages(),
				loading: false,
			});
		}

		reloadCategory()
		{
			const category = this.getCategoryByProps(this.props);

			if (category && !isEqual(this.state.category, category))
			{
				this.setState({
					category,
					categoryCounters: this.getCategoryCounters(),
				});
			}
		}

		onToolbarClick()
		{
			const { entityTypeId } = this.props;
			const { category } = this.state;

			const unsuitableStages = this.getCategoryCounters()
				.filter((stage) => stage.dropzone)
				.map((stage) => stage.id);

			void requireLazy('crm:stage-list-view').then(({ StageListView }) => {
				void StageListView.open({
					entityTypeId,
					unsuitableStages,
					categoryId: category.id,
					activeStageId: this.getActiveStageId(),
					readOnly: true,
					canMoveStages: false,
					enableStageSelect: true,
					clickable: false,
					onStageSelect: ({ id }) => this.setActiveStage(id),
					stageParams: {
						showTotal: true,
						showCount: true,
						showCounters: true,
						showTunnels: false,
						showAllStagesItem: true,
					},
				});
			});
		}

		getStages()
		{
			if (!this.state.category)
			{
				return [];
			}

			const { processStages = [], successStages = [], failedStages = [] } = this.state.category;

			return [...processStages, ...successStages, ...failedStages];
		}

		getCountersByStageId(id)
		{
			const { categoryCounters } = this.state;

			if (categoryCounters.length === 0)
			{
				return null;
			}

			return (categoryCounters.find((stage) => stage.id === id) || null);
		}

		/**
		 * @returns {Number|null}
		 */
		getActiveStageCounter()
		{
			const activeStage = this.getActiveStage();
			if (activeStage)
			{
				const counters = this.getCountersByStageId(activeStage.id);

				return (counters ? counters.count : null);
			}

			const counters = this.getCategoryCounters();

			return counters.reduce((count, stage) => count + stage.count, 0);
		}

		/**
		 * @returns {String}
		 */
		getStageSelectorTitle()
		{
			const { category } = this.state;
			const { name = '', categoriesEnabled = false } = category || {};

			if (categoriesEnabled && name !== '')
			{
				return Loc.getMessage('MCRM_STAGE_TOOLBAR_CATEGORY_NAME2', {
					'#CATEGORY_NAME#': name,
				});
			}

			return Loc.getMessage('MCRM_STAGE_TOOLBAR_CURRENT_STAGE');
		}

		// region rendering

		renderStageSelector()
		{
			const styles = this.getStyles();

			return View(
				{
					style: styles.stageSelectorWrapper,
				},
				StageDropdown({
					onClick: () => this.onToolbarClick(),
					activeStage: this.getActiveStage(),
					counter: this.getActiveStageCounter(),
					title: this.getStageSelectorTitle(),
					loading: this.isLoading(),
				}),
			);
		}

		renderCurrentStageSummary()
		{
			if (!this.getProps().showSum)
			{
				return null;
			}

			const money = this.getTotalMoney();
			const currencyText = money && money.formattedCurrency ? `, ${money.formattedCurrency}` : '';

			return StageSummary({
				title: Loc.getMessage('M_UI_KANBAN_TOOLBAR_DEAL_SUM') + currencyText,
				text: this.getFormattedMoneyText(money),
				useFiller: this.isLoading(),
			});
		}

		// endregion

		// region money and currency

		/**
		 * @return {Money|null}
		 */
		getTotalMoney()
		{
			const counters = this.state.categoryCounters;
			if (counters.length === 0)
			{
				return null;
			}

			const amount = this.getActiveStageTotalMoney();
			const currency = this.getCurrency();

			return new Money({ amount, currency });
		}

		/**
		 * @returns {Number}
		 */
		getActiveStageTotalMoney()
		{
			const { categoryCounters } = this.state;

			if (categoryCounters.length === 0)
			{
				return 0;
			}

			const currentStage = this.getActiveStage();
			let sum = 0;

			if (currentStage)
			{
				const stageCounters = this.getCountersByStageId(currentStage.id);
				if (!stageCounters)
				{
					return 0;
				}

				sum = Math.round(stageCounters.total);
			}
			else
			{
				sum = Math.round(categoryCounters.reduce((total, stage) => total + parseFloat(stage.total, 10), 0));
			}

			return (sum > 0 ? sum : 0);
		}

		/**
		 * @param {?Money} money
		 * @returns {null|String}
		 */
		getFormattedMoneyText(money)
		{
			if (!money)
			{
				return null;
			}

			const formatBigMoney = (amount) => {
				const compact = Math.floor(amount * 10 / MAX_FORMATTED_SUM) / 10;

				return `${compact} ${Loc.getMessage('M_CRM_MAX_FORMATTED_SUM')}`;
			};

			return money.amount >= MAX_FORMATTED_SUM ? formatBigMoney(money.amount) : money.formattedAmount;
		}

		/**
		 * @return {string}
		 */
		getCurrency()
		{
			const stage = this.getActiveStage() || this.getStages()[0];
			if (!stage)
			{
				return '';
			}

			const counters = this.getCountersByStageId(stage.id);
			if (!counters)
			{
				return '';
			}

			return counters.currency;
		}

		// endregion

		getTestId()
		{
			return 'stageToolbar';
		}
	}

	module.exports = { EntityToolbar };
});
