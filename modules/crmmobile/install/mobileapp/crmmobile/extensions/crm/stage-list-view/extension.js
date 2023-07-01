/**
 * @module crm/stage-list-view
 */
jn.define('crm/stage-list-view', (require, exports, module) => {
	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { isEqual, get, mergeImmutable } = require('utils/object');
	const { NavigationLoader } = require('navigation-loader');
	const { getEntityMessage } = require('crm/loc');
	const { CategoryStorage } = require('crm/storage/category');
	const { StageList } = require('crm/stage-list');
	const { edit } = require('crm/assets/common');
	const { CategorySvg } = require('crm/assets/category');
	const { StageSelectActions } = require('crm/stage-list/actions');
	const { throttle } = require('utils/function');
	const { stringify } = require('utils/string');
	const { TypeId } = require('crm/type');

	/**
	 * @class StageListView
	 */
	class StageListView extends LayoutComponent
	{
		static open(props, widgetParams = {}, parentWidget = PageManager)
		{
			return new Promise((resolve) => {
				const params = {
					modal: true,
					backgroundColor: '#eef2f4',
					backdrop: {
						showOnTop: true,
						forceDismissOnSwipeDown: true,
						horizontalSwipeAllowed: false,
						swipeContentAllowed: false,
						navigationBarColor: '#eef2f4',
					},
				};

				parentWidget
					.openWidget('layout', mergeImmutable(params, widgetParams))
					.then((layout) => {
						layout.showComponent(new this({ ...props, layout }));
						resolve(layout);
					})
				;
			});
		}

		constructor(props)
		{
			super(props);

			this.layout = props.layout || layout;

			const category = this.getCategoryByProps(props);
			this.state = { category };

			this.selectedStage = null;

			this.isClosing = false;

			this.onSelectedStage = this.handlerOnSelectedStage.bind(this);
			this.onOpenStageDetail = this.handlerOnOpenStageDetail.bind(this);
			this.saveOnCreateTunnelHandler = throttle(this.saveOnCreateTunnel, 1000, this);
			this.saveOnChangeTunnelDestinationHandler = throttle(this.saveOnChangeTunnelDestination, 1000, this);
		}

		getCategoryByProps(props)
		{
			const { entityTypeId, categoryId } = props;

			return CategoryStorage.getCategory(entityTypeId, categoryId);
		}

		getSelectStageAction()
		{
			return this.props.selectAction || StageSelectActions.ChangeEntityStage;
		}

		componentDidMount()
		{
			CategoryStorage
				.subscribeOnChange(() => this.reloadCategory())
				.subscribeOnLoading(({ status }) => NavigationLoader.setLoading(status, this.layout))
				.markReady()
			;

			this.layout.setListener((eventName) => {
				if (eventName === 'onViewHidden' || eventName === 'onViewRemoved')
				{
					this.handleOnViewHidden();
				}
			});

			this.bindEvents();
			this.initNavigation();
		}

		reloadCategory()
		{
			const category = this.getCategoryByProps(this.props);
			if (!isEqual(this.state.category, category))
			{
				this.setState({ category }, () => this.initNavigation());
			}
		}

		handleOnViewHidden()
		{
			const { onViewHidden } = this.props;
			if (typeof onViewHidden === 'function')
			{
				onViewHidden({
					stageAction: this.selectedStage ? this.getSelectStageAction() : null,
				});
			}
		}

		bindEvents()
		{
			BX.addCustomEvent('Crm.CategoryDetail::onClose', (category) => {
				if (!this.isClosing)
				{
					this.setState({ category });
				}
			});

			BX.addCustomEvent('Crm.CategoryDetail::onDeleteCategory', (categoryId) => {
				if (!this.state.category || this.state.category.id === categoryId)
				{
					this.closeLayout();
				}
			});

			BX.addCustomEvent('Crm.StageDetail::onUpdateStage', (stage) => {
				this.setState((state) => {
					if (
						!this.hasStageChanged(state.category, 'processStages', stage)
						&& !this.hasStageChanged(state.category, 'failedStages', stage)
						&& !this.hasStageChanged(state.category, 'successStages', stage)
					)
					{
						return;
					}

					return {
						category: state.category,
					};
				}, () => {
					BX.postComponentEvent(
						'Crm.StageList::onStageInCategoryUpdated',
						[
							this.state.category,
							stage,
						],
					);
				});
			});
		}

		closeLayout()
		{
			if (this.isClosing)
			{
				return;
			}

			this.isClosing = true;
			this.layout.back();

			return new Promise((resolve) => {
				this.layout.close(resolve);
			});
		}

		getTitleForNavigation()
		{
			const { category } = this.state;

			if (!category)
			{
				return BX.message('CRM_STAGE_LIST_VIEW_FUNNEL_NOT_LOADED_TITLE2');
			}

			if (!category.categoriesEnabled)
			{
				return category.name;
			}

			const name = stringify(category.name).trim();

			return (
				name === ''
					? BX.message('CRM_STAGE_LIST_VIEW_FUNNEL_EMPTY_TITLE2')
					: BX.message('CRM_STAGE_LIST_VIEW_FUNNEL_TITLE2').replace('#CATEGORY_NAME#', name)
			);
		}

		isEditable()
		{
			return Boolean(get(this.state.category, 'editable', false));
		}

		initNavigation()
		{
			this.layout.enableNavigationBarBorder(false);

			this.layout.setTitle({
				text: this.getTitleForNavigation(),
				svg: {
					content: CategorySvg.funnelForTitle(),
				},
			});

			if (this.isEditable() && this.getSelectStageAction() === StageSelectActions.ChangeEntityStage)
			{
				this.layout.setRightButtons([
					{
						type: 'edit',
						svg: {
							content: edit(),
						},
						callback: () => this.handlerCategoryEditOpen(),
					},
				]);
			}
		}

		saveOnCreateTunnel()
		{
			BX.postComponentEvent('Crm.TunnelList::onCreateTunnel', []);
		}

		saveOnChangeTunnelDestination(uid)
		{
			BX.postComponentEvent(`Crm.TunnelListItem::onChangeTunnelDestination-${uid}`, []);
		}

		refreshTitle()
		{
			if (this.state.category)
			{
				this.layout.setTitle({ text: this.getTitleForNavigation() }, true);
			}
		}

		/**
		 * @param {Object} category
		 * @param {String} stagesGroupName
		 * @param {Object} stage
		 * @returns {Boolean}
		 */
		hasStageChanged(category, stagesGroupName, stage)
		{
			const currentStage = category[stagesGroupName].find((item) => item.id === stage.id);
			if (!currentStage)
			{
				return false;
			}
			const index = category[stagesGroupName].indexOf(currentStage);
			category[stagesGroupName][index] = stage;

			return true;
		}

		componentWillReceiveProps(newProps)
		{
			this.state.category = this.getCategoryByProps(newProps);
		}

		handlerOnSelectedStage(stage)
		{
			const { category } = this.state;
			const { data, uid } = this.props;

			switch (this.getSelectStageAction())
			{
				case StageSelectActions.ChangeEntityStage:
					if (this.isNewLead() && this.getSuccessStagesIds().includes(stage.id))
					{
						Alert.confirm(
							Loc.getMessage('CRM_STAGE_LIST_VIEW_CHANGE_NEW_LEAD_SUCCESS_STAGE_NOTIFY_TITLE'),
							Loc.getMessage('CRM_STAGE_LIST_VIEW_CHANGE_NEW_LEAD_SUCCESS_STAGE_NOTIFY'),
						);

						return;
					}

					const { onStageSelect } = this.props;

					if (onStageSelect)
					{
						this.closeLayout().then(() => {
							onStageSelect(stage, category, data, uid);
						});
					}
					break;

				case StageSelectActions.CreateTunnel:
					this.selectedStage = stage;

					BX.postComponentEvent('Crm.TunnelList::selectStageOnCreateTunnel', [stage]);

					if (this.selectedStage)
					{
						this.saveOnCreateTunnelHandler();
					}

					this.closeLayout();

					break;

				case StageSelectActions.SelectTunnelDestination:
					this.selectedStage = stage;

					BX.postComponentEvent(`Crm.TunnelListItem::selectTunnelDestinationStage-${uid}`, [stage]);

					if (this.selectedStage)
					{
						this.saveOnChangeTunnelDestinationHandler(uid);
					}

					this.closeLayout();

					break;
			}
		}

		handlerCategoryEditOpen()
		{
			const { entityTypeId } = this.props;
			const { category } = this.state;

			ComponentHelper.openLayout({
				name: 'crm:crm.category.detail',
				componentParams: {
					entityTypeId,
					categoryId: category.id,
				},
				widgetParams: {
					modal: true,
					backgroundColor: '#eef2f4',
					backdrop: {
						showOnTop: true,
						swipeContentAllowed: false,
						horizontalSwipeAllowed: false,
						navigationBarColor: '#eef2f4',
					},
				},
			}, this.layout);
		}

		render()
		{
			this.refreshTitle();

			const { category } = this.state;
			const {
				stageParams,
				canMoveStages,
				activeStageId,
			} = this.props;

			return ScrollView(
				{
					resizableByKeyboard: true,
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
					style: {
						flexDirection: 'column',
						backgroundColor: '#eef2f4',
					},
				},
				category === null
					? new LoadingScreenComponent({ backgroundColor: '#eef2f4' })
					: new StageList({
						title: this.getStageListTitle(),
						readOnly: this.getStageReadOnly(),
						canMoveStages,
						stageParams,
						category,
						activeStageId,
						unsuitableStages: this.getUnsuitableStages(),
						processStages: category.processStages,
						finalStages: [...category.successStages, ...category.failedStages],
						onSelectedStage: this.onSelectedStage,
						onOpenStageDetail: this.onOpenStageDetail,
						enableStageSelect: this.props.enableStageSelect,
						disabledStageIds: this.getDisabledStageIdsByCategory(category),
					}),
			);
		}

		getUnsuitableStages()
		{
			const { unsuitableStages } = this.props;
			let unsuitableStagesIds = unsuitableStages;

			if (!Array.isArray(unsuitableStagesIds))
			{
				unsuitableStagesIds = [];
			}

			const successStagesIds = this.getSuccessStagesIds();
			if (this.isNewLead() && successStagesIds.length > 0)
			{
				unsuitableStagesIds = [...unsuitableStagesIds, ...successStagesIds];
			}

			return unsuitableStagesIds;
		}

		getSuccessStagesIds()
		{
			const { category } = this.state;

			if (!Array.isArray(category.successStages))
			{
				return [];
			}

			return category.successStages.map(({ id }) => id);
		}

		isNewLead()
		{
			const { entityTypeId, isNewEntity } = this.props;

			return isNewEntity && entityTypeId === TypeId.Lead;
		}

		getDisabledStageIdsByCategory(category)
		{
			const selectStageAction = this.getSelectStageAction();
			const actions = [StageSelectActions.SelectTunnelDestination, StageSelectActions.CreateTunnel];
			if (actions.includes(selectStageAction))
			{
				const stages = [...category.processStages, ...category.successStages, ...category.failedStages];
				const disabledStages = [];
				stages.map((stage) => {
					if (this.hasSameDstStage(stage.tunnels, this.disabledStageIds))
					{
						disabledStages.push(stage.id);
					}
				});

				return disabledStages;
			}

			return [];
		}

		hasSameDstStage(tunnels, dstStageIds)
		{
			const intersection = tunnels.filter((tunnel) => dstStageIds.includes(tunnel.dstStageId));

			return intersection.length > 0;
		}

		get disabledStageIds()
		{
			return BX.prop.getArray(this.props, 'disabledStageIds', []);
		}

		getStageListTitle()
		{
			if (
				this.getSelectStageAction() === StageSelectActions.SelectTunnelDestination
				|| this.getSelectStageAction() === StageSelectActions.CreateTunnel
			)
			{
				return BX.message('CRM_STAGE_LIST_VIEW_BACKDROP_TUNNEL_TITLE');
			}

			return getEntityMessage('CRM_STAGE_LIST_VIEW_TITLE', this.props.entityTypeId);
		}

		getStageReadOnly()
		{
			if (
				this.getSelectStageAction() === StageSelectActions.SelectTunnelDestination
				|| this.getSelectStageAction() === StageSelectActions.CreateTunnel
			)
			{
				return true;
			}

			return this.props.readOnly;
		}

		handlerOnOpenStageDetail(stage)
		{
			ComponentHelper.openLayout({
				name: 'crm:crm.stage.detail',
				componentParams: {
					entityTypeId: this.props.entityTypeId,
					stage,
				},
				widgetParams: {
					modal: true,
					backgroundColor: '#eef2f4',
					backdrop: {
						showOnTop: true,
						forceDismissOnSwipeDown: true,
						horizontalSwipeAllowed: false,
						swipeContentAllowed: true,
						navigationBarColor: '#eef2f4',
					},
				},
			});
		}
	}

	module.exports = { StageListView };
});
