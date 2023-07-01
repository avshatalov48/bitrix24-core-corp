/**
 * @bxjs_lang_path extension.php
 * @module crm/kanban/toolbar/deal
 */
jn.define('crm/kanban/toolbar/deal', (require, exports, module) => {
	const {
		isEqual,
		mergeImmutable,
	} = require('utils/object');
	const { NavigationLoader } = require('navigation-loader');
	const { StageToolbar } = require('crm/stage-toolbar');
	const { CategoryStorage } = require('crm/storage/category');
	const { CategoryCountersStoreManager } = require('crm/state-storage');

	const MAX_FORMATTED_SUM = 1_000_000_000;
	const PLUS_ONE_ACTION = 'plus';
	const MINUS_ONE_ACTION = 'minus';

	/**
	 * @class DealToolbar
	 */
	class DealToolbar extends LayoutComponent
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

			this.blinkItem = BX.prop.getFunction(props, 'blinkItem', () => {});

			this.toolbarRef = null;
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
				.subscribeOnChange(() => this.reloadCategory())
				.subscribeOnLoading(({ status }) => NavigationLoader.setLoading(status))
				.markReady()
			;

			BX.addCustomEvent('UI.SimpleList::onUpdateItem', this.onItemUpdatedHandler);
			BX.addCustomEvent('UI.SimpleList::onDeleteItem', this.onItemDeletedHandler);
			BX.addCustomEvent('UI.Kanban::onItemMoved', this.onItemMovedHandler);
			BX.addCustomEvent('UI.SimpleList::onRefresh', this.onSimpleListRefreshHandler);

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

		handleOnItemMoved({ oldItem, item, resolveParams = {} })
		{
			const oldColumnId = oldItem.data.columnId;
			const columnId = item.data.columnId;

			if (oldColumnId === columnId)
			{
				return;
			}

			const amount = item.data.price;
			this.addToStageCounters(columnId, amount);
			this.removeFromStageCounters(oldColumnId, amount);

			const { action } = resolveParams;
			const showUpdated = action !== 'delete';

			this.blinkItem(item.id, showUpdated);
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
			if (!stageCounters)
			{
				return;
			}

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
				const stages = this.getCategoryCounters();
				const unsuitableStages = stages.filter((stage) => stage.dropzone).map((stage) => stage.id);

				this.stageToolbarRef.handleSelectorClick(unsuitableStages);
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
			if (currentStage)
			{
				const stageCounters = this.getCountersByStageId(currentStage.id);
				if (!stageCounters)
				{
					return 0;
				}

				return Math.round(stageCounters.total);
			}

			return Math.round(categoryCounters.reduce((sum, stage) => sum + parseFloat(stage.total, 10), 0));
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

				this.getStages().forEach((stage) => columns.set(stage.statusId, stage));

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
			const { entityTypeId } = this.props;
			const { activeStageId, category } = this.state;

			const styles = this.getStyles();

			const money = this.getTotalMoney();
			const currencyText = money ? `, ${money.formattedCurrency}` : '';

			return View(
				{
					style: styles.rootWrapper,
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
								clickable: false,
								onStageSelect: ({ id }) => this.updateCurrentColumnId(id),
								stageParams: {
									showTotal: true,
									showCount: true,
									showCounters: true,
									showAllStagesItem: true,
									renderStageCountFiller: this.renderFiller,
									useRenderStageCountFiller: !this.state.showValues,
								},
							}),
						),
						View(
							{
								style: styles.moneyWrapper,
							},
							Text({
								style: styles.moneyTitle,
								text: BX.message('M_UI_KANBAN_TOOLBAR_DEAL_SUM') + currencyText,
								ellipsize: 'end',
								numberOfLines: 1,
							}),
							(this.state.showValues ? this.renderFormattedMoney(money) : this.renderFiller(78)),
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

		renderFiller(width, style)
		{
			style = mergeImmutable({
				height: 6,
				width,
				marginTop: Application.getPlatform() === 'android' ? 10 : 8,
			}, style);

			return Image({
				style,
				svg: {
					content: `<svg width="${width}" height="6" viewBox="0 0 ${width} 6" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="${width}" height="6" rx="2" fill="#DFE0E3"/></svg>`,
				},
			});
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
			color: '#e6e7e9',
			radius: 3,
			offset: {
				y: 3,
			},
			inset: {
				left: 3,
				right: 3,
			},
			style: {
				borderBottomLeftRadius: 12,
				borderBottomRightRadius: 12,
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

	module.exports = { DealToolbar };
});
