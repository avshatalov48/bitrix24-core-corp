/**
 * @bxjs_lang_path extension.php
 * @module crm/kanban/toolbar/entities/base
 */
jn.define('crm/kanban/toolbar/entities/base', (require, exports, module) => {
	const { ShimmerView } = require('layout/polyfill');
	const { isEqual, mergeImmutable } = require('utils/object');
	const { NavigationLoader } = require('navigation-loader');
	const { StageToolbar } = require('crm/stage-toolbar');
	const { CategoryStorage } = require('crm/storage/category');
	const { CategoryCountersStoreManager } = require('crm/state-storage');

	const MAX_FORMATTED_SUM = 1_000_000_000;
	const PLUS_ONE_ACTION = 'plus';
	const MINUS_ONE_ACTION = 'minus';

	/**
	 * @class BaseToolbar
	 */
	class BaseToolbar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.stages = null;
			this.columns = null;

			this.state = {
				category: this.getCategoryByProps(props, true),
				categoryCounters: this.getCategoryCounters(),
				activeStageId: null,
				showValues: false,
			};

			/** @type {StageToolbar} */
			this.stageToolbarRef = null;

			this.clickToolbarHandler = this.handleToolbarClick.bind(this);
			this.onItemMovedHandler = this.handleOnItemMoved.bind(this);
			this.onItemDeletedHandler = this.handleOnItemDeleted.bind(this);
			this.onItemUpdatedHandler = this.handleOnItemUpdated.bind(this);
			this.onSimpleListRefreshHandler = this.handleOnSimpleListRefresh.bind(this);
			this.updateCountersHandler = this.updateCounters.bind(this);
			this.renderFiller = this.renderFiller.bind(this);

			this.blinkItem = BX.prop.getFunction(props, 'blinkItem', () => {});
		}

		getCategoryByProps(props, force = false)
		{
			const {
				filter,
				entityTypeId,
				filterParams: {
					CATEGORY_ID: categoryId,
				},
			} = props;

			const category = CategoryStorage.getCategory(entityTypeId, categoryId, filter, force);
			if (force)
			{
				this.clearCaches();
			}

			return category;
		}

		getCategoryCounters()
		{
			return CategoryCountersStoreManager.getStages();
		}

		updateCounters()
		{
			const newState = {
				categoryCounters: CategoryCountersStoreManager.getStages(),
				showValues: true,
			};

			this.setState(newState);
		}

		componentWillReceiveProps(newProps)
		{
			this.state.category = this.getCategoryByProps(newProps);
			this.clearCaches();
		}

		clearCaches()
		{
			this.stages = null;
			this.columns = null;
		}

		componentDidMount()
		{
			this.subscribeStoreEvents();

			CategoryStorage
				.setEventId('crm.dealToolbar')
				.subscribeOnChange(() => this.reloadCategory())
				.subscribeOnLoading(({ status }) => NavigationLoader.setLoading(status))
				.markReady()
			;

			BX.addCustomEvent('UI.SimpleList::onUpdateItem', this.onItemUpdatedHandler);
			BX.addCustomEvent('UI.SimpleList::onDeleteItem', this.onItemDeletedHandler);
			BX.addCustomEvent('UI.Kanban::onItemMoved', this.onItemMovedHandler);
			BX.addCustomEvent('UI.SimpleList::onRefresh', this.onSimpleListRefreshHandler);
			BX.addCustomEvent('Crm.Item::onChangePipeline', this.props.onChangeItemCategory);

			layout.enableNavigationBarBorder(false);
		}

		subscribeStoreEvents()
		{
			CategoryCountersStoreManager
				.subscribe('categoryCountersModel/init', this.updateCountersHandler)
				.subscribe('categoryCountersModel/updateStage', this.updateCountersHandler)
			;
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('UI.SimpleList::onUpdateItem', this.onItemUpdatedHandler);
			BX.removeCustomEvent('UI.SimpleList::onDeleteItem', this.onItemDeletedHandler);
			BX.removeCustomEvent('UI.Kanban::onItemMoved', this.onItemMovedHandler);
			BX.removeCustomEvent('UI.SimpleList::onRefresh', this.onSimpleListRefreshHandler);
			BX.removeCustomEvent('Crm.Item::onChangePipeline', this.props.onChangeItemCategory);

			CategoryStorage.unsubscribe('crm.dealToolbar');

			CategoryCountersStoreManager
				.unsubscribe('categoryCountersModel/init', this.updateCountersHandler)
				.unsubscribe('categoryCountersModel/updateStage', this.updateCountersHandler)
			;

			this.clearCaches();
		}

		handleOnItemUpdated(params)
		{
			const oldAmount = params.oldItem.data ? params.oldItem.data.price : 0;
			const amount = params.item.data.price;

			if (oldAmount === amount)
			{
				return;
			}

			const columnId = params.item.columnId;

			this.addToStageCounters(columnId, amount - oldAmount, 0);
		}

		handleOnItemDeleted(params)
		{
			const oldAmount = params.oldItem.data ? params.oldItem.data.price : 0;
			const oldColumnId = params.oldItem.data.columnId;

			this.removeFromStageCounters(oldColumnId, oldAmount);
		}

		handleOnItemMoved(params)
		{
			const oldColumnId = params.oldItem.data.columnId;
			const columnId = params.item.data.columnId;

			if (oldColumnId === columnId)
			{
				return;
			}

			const amount = params.item.data.price;
			this.addToStageCounters(columnId, amount);
			this.removeFromStageCounters(oldColumnId, amount);

			this.blinkItem(params.item.id);
		}

		addToStageCounters(columnId, amount, addCount = 1)
		{
			this.modifyCountersByColumnId(PLUS_ONE_ACTION, columnId, amount, addCount);
		}

		removeFromStageCounters(columnId, amount, removeCount = 1)
		{
			this.modifyCountersByColumnId(MINUS_ONE_ACTION, columnId, amount, removeCount);
		}

		modifyCountersByColumnId(action, columnId, amount = 0, count = 1)
		{
			if (action !== PLUS_ONE_ACTION && action !== MINUS_ONE_ACTION)
			{
				throw new Error(`ModifyCounters action type ${action} is not known`);
			}

			const stage = this.getStageByStatusId(columnId);
			if (!stage)
			{
				return;
			}

			const stageCounters = this.getCountersByStageId(stage.id);
			const total = (action === PLUS_ONE_ACTION ? stageCounters.total + amount : stageCounters.total - amount);
			count = (action === PLUS_ONE_ACTION ? stageCounters.count + count : stageCounters.count - count);

			const data = {
				total,
				count,
			};

			this.updateStageCounters(stage.id, data);
		}

		handleOnSimpleListRefresh()
		{
			void this.reload(this.state.activeStageId);
		}

		reloadCategory()
		{
			const category = this.getCategoryByProps(this.props);
			this.changeCategory(category);
		}

		changeCategory(category)
		{
			if (category && !isEqual(this.state.category, category))
			{
				const needReloadColumn = (!this.state.category || this.state.category.id !== category.id);
				this.setState({
					category,
					categoryCounters: this.getCategoryCounters(),
				}, () => {
					if (needReloadColumn)
					{
						this.props.reloadColumn();
					}
				});
			}
		}

		handleToolbarClick()
		{
			if (this.stageToolbarRef)
			{
				this.stageToolbarRef.handleSelectorClick();
			}
		}

		/**
		 * @returns {null|Number}
		 */
		getActiveStageId()
		{
			return this.state.activeStageId;
		}

		getStages(useCache = true)
		{
			if (!this.state.category)
			{
				return [];
			}

			if (this.stages === null || !useCache)
			{
				this.stages = [
					...this.state.category.processStages,
					...this.state.category.successStages,
					...this.state.category.failedStages,
				];
			}

			return this.stages;
		}

		updateStageCounters(stageId, data)
		{
			CategoryCountersStoreManager.updateStage(stageId, data);
		}

		/**
		 * @returns {null|Object}
		 */
		getActiveStage()
		{
			const stages = this.getStages();

			if (stages.length === 0 || !this.getActiveStageId())
			{
				return null;
			}

			return this.getStage(this.getActiveStageId());
		}

		getStage(id)
		{
			const stages = this.getStages(false);

			return stages.find((stage) => stage.id === id);
		}

		getFirstStage()
		{
			return this.getStages()[0];
		}

		getStageByStatusId(statusId)
		{
			const stages = this.getStages(false);

			return stages.find((stage) => stage.statusId === statusId);
		}

		getCountersById(id)
		{
			return this.state.categoryCounters.find((stage) => stage.id === id);
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

			return (
				money.amount >= MAX_FORMATTED_SUM
					? `${Math.floor(money.amount * 10 / MAX_FORMATTED_SUM) / 10} ${BX.message('M_CRM_MAX_FORMATTED_SUM')}`
					: money.formattedAmount
			);
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
				sum = Math.round(categoryCounters.reduce((sum, stage) => sum + parseFloat(stage.total, 10), 0));
			}

			return (sum > 0 ? sum : 0);
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

		getCurrency()
		{
			const stage = this.getActiveStage() || this.getFirstStage();
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

		getColumns()
		{
			if (this.columns === null || this.columns.size === 0)
			{
				const columns = new Map();

				this.getStages().forEach((stage) => {
					columns.set(stage.statusId, stage);
				});

				this.columns = columns;
			}

			return this.columns;
		}

		/**
		 * @param {Number|null} activeStageId
		 */
		updateCurrentColumnId(activeStageId)
		{
			if (this.state.activeStageId !== activeStageId)
			{
				this.setState({ activeStageId }, () => {
					this.props.changeColumn(activeStageId);
				});
			}
		}

		/**
		 * @param {Number} activeStageId
		 * @param {Boolean} force
		 */
		reload(activeStageId = 0, force = false)
		{
			return new Promise((resolve) => {
				if (this.state.activeStageId !== activeStageId || force)
				{
					this.setState({ activeStageId }, () => resolve(this.getColumns()));
				}
				else
				{
					resolve(this.getColumns());
				}
			});
		}

		/**
		 * @returns {{category}}
		 */
		getAdditionalParamsForItem()
		{
			return {
				entityTypeId: this.props.entityTypeId,
				entityTypeName: this.props.entityTypeName,
				categoryId: this.state.category && this.state.category.id,
				activeStageId: this.state.activeStageId,
				onChange: this.props.changeItemStage,
				columns: this.getColumns(),
			};
		}

		getStyles()
		{
			return mergeImmutable(defaultStyles, BX.prop.getObject(this.props, 'style', {}));
		}

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

		render()
		{
			const { entityTypeId, params } = this.props;
			const { activeStageId, category, showValues } = this.state;

			const styles = this.getStyles();

			const isShowSum = params && params.showSum;

			const money = isShowSum ? this.getTotalMoney() : null;
			const currencyText = money && money.formattedCurrency ? `, ${money.formattedCurrency}` : '';

			return View(
				{
					style: styles.rootWrapper,
					testId: 'stageToolbar',
					onClick: this.clickToolbarHandler,
				},
				Shadow(
					styles.shadow,
					View(
						{
							style: styles.mainWrapper,
						},
						View(
							{
								style: styles.toolbarWrapper,
							},
							new StageToolbar({
								ref: (ref) => this.stageToolbarRef = ref,
								entityTypeId,
								category,
								activeStageId,
								showAllStages: true,
								onStageSelect: ({ id }) => this.updateCurrentColumnId(id),
								stageParams: {
									showTotal: true,
									showCount: true,
									showCounters: true,
									showAllStagesItem: true,
									renderStageCountFiller: this.renderFiller,
									useRenderStageCountFiller: !showValues,
								},
							}),
						),
						isShowSum && View(
							{
								style: styles.moneyWrapper,
							},
							Text({
								style: styles.moneyTitle,
								text: BX.message('M_UI_KANBAN_TOOLBAR_DEAL_SUM') + currencyText,
								ellipsize: 'end',
								numberOfLines: 1,
							}),
							(showValues ? this.renderFormattedMoney(money) : this.renderFiller(78)),
						),
					),
				),
			);
		}

		renderFormattedMoney(money)
		{
			const styles = this.getStyles();

			return Text({
				style: styles.money,
				text: this.getFormattedMoneyText(money),
				ellipsize: 'end',
				numberOfLines: 1,
			});
		}

		renderFiller(width, style = {})
		{
			style = mergeImmutable({
				height: 6,
				width,
				marginTop: Application.getPlatform() === 'android' ? 10 : 8,
			}, style);

			const { marginTop, marginBottom, width: finalWidth } = style;

			return View(
				{
					style: {
						marginTop,
						marginBottom,
						width: finalWidth,
					},
				},
				ShimmerView(
					{ animating: true },
					this.renderLine(finalWidth, 6),
				),
			);
		}

		renderLine(width, height, marginTop = 0, marginBottom = 0)
		{
			const style = {
				width,
				height,
				borderRadius: 3,
				backgroundColor: '#dfe0e3',
			};

			if (marginTop)
			{
				style.marginTop = marginTop;
			}

			if (marginBottom)
			{
				style.marginBottom = marginBottom;
			}

			return View({ style });
		}
	}

	const defaultStyles = {
		rootWrapper: {
			position: 'absolute',
			left: 0,
			right: 0,
			top: 0,
		},
		shadow: {
			color: '#0f000000',
			radius: 2,
			offset: {
				y: 2,
			},
			inset: {
				left: 2,
				right: 2,
			},
			style: {
				borderBottomLeftRadius: 12,
				borderBottomRightRadius: 12,
				marginBottom: 2,
			},
		},
		mainWrapper: {
			flexDirection: 'row',
			height: 60,
			paddingHorizontal: 10,
			paddingTop: 9,
			backgroundColor: '#ffffff',
			borderBottomLeftRadius: 12,
			borderBottomRightRadius: 12,
			justifyContent: 'space-between',
			alignItems: 'flex-start',
		},
		toolbarWrapper: {
			flex: 10,
			paddingRight: 10,
		},
		moneyWrapper: {
			flex: 4,
			paddingRight: 10,
		},
		moneyTitle: {
			color: '#a8adb4',
			fontSize: 14,
			fontWeight: '500',
			marginBottom: Application.getPlatform() === 'android' ? 0 : 2,
		},
		money: {
			color: '#525c69',
			fontSize: 14,
			fontWeight: '500',
			marginTop: Application.getPlatform() === 'android' ? 1 : 3,
		},
	};

	module.exports = { BaseToolbar };
});
