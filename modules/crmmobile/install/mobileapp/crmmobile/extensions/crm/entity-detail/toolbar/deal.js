/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-detail/toolbar/deal
 */

jn.define('crm/entity-detail/toolbar/deal', (require, exports, module) => {
	const { isEqual, mergeImmutable } = require('utils/object');
	const { FadeView } = require('animation/components/fade-view');
	const { Alert } = require('alert');
	const { NavigationLoader } = require('navigation-loader');
	const { TypeId } = require('crm/type');
	const { StageToolbar } = require('crm/stage-toolbar');
	const { CategoryStorage } = require('crm/storage/category');
	const { connectionComponent } = require('communication/connection');

	/**
	 * @class DealDetailToolbar
	 */
	class DealDetailToolbar extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const { detailCard, model } = props;

			if (detailCard)
			{
				this.setDetailCard(detailCard);
			}

			if (model)
			{
				this.setModel(model);
			}

			this.state = this.state || {};
			this.state.readOnly = true;
			this.state.category = this.loadCategory();
			this.state.activeStageId = this.getActiveStageId();

			this.readOnly = true;
			this.needDetailModel = true;

			this.handleStageSelect = this.handleStageSelect.bind(this);
			this.processStageChange = this.processStageChange.bind(this);
		}

		setDetailCard(detailCard)
		{
			/** @type {DetailCardComponent} */
			this.detailCard = detailCard;

			return this;
		}

		setModel(model)
		{
			this.model = model;

			return this;
		}

		componentDidMount()
		{
			CategoryStorage
				.subscribeOnChange(() => this.reloadCategory())
				.subscribeOnLoading(({ status }) => NavigationLoader.setLoading(status))
				.markReady()
			;

			layout.enableNavigationBarBorder(false);
		}

		loadCategory()
		{
			const categoryId = this.getCategoryId();

			return this.loadCategoryById(categoryId);
		}

		getCategoryId()
		{
			const categoryId = this.getCategoryIdByModel();
			if (categoryId)
			{
				return categoryId;
			}

			if (this.props.hasOwnProperty('categoryId'))
			{
				return this.props.categoryId;
			}

			return null;
		}

		getCategoryIdByModel()
		{
			if (!this.model || !this.model.hasOwnProperty('CATEGORY_ID'))
			{
				return null;
			}

			return this.model['CATEGORY_ID'];
		}

		loadCategoryById(categoryId)
		{
			if (categoryId !== null)
			{
				return CategoryStorage.getCategory(TypeId.Deal, categoryId);
			}

			return null;
		}

		getActiveStageId()
		{
			const statusId = this.model && this.model['STAGE_ID'];
			const stageId = this.getStageIdByStatusId(statusId);
			if (stageId !== null)
			{
				return stageId;
			}

			if (!this.isExistingEntity())
			{
				return this.findFirstStageId();
			}

			return null;
		}

		reloadCategory()
		{
			const category = this.loadCategory();
			if (!isEqual(this.state.category, category))
			{
				this.setState({ category });
			}
		}

		refresh()
		{
			const readOnly = this.readOnly;
			const category = this.loadCategory();
			const activeStageId = this.getActiveStageId();

			if (
				this.state.readOnly !== readOnly
				|| this.state.activeStageId !== activeStageId
				|| !isEqual(this.state.category, category)
			)
			{
				this.setState({ readOnly, category, activeStageId });
			}
		}

		/**
		 * @param {Boolean} readOnly
		 * @returns {DealDetailToolbar}
		 */
		setReadOnly(readOnly)
		{
			this.readOnly = Boolean(readOnly);

			return this;
		}

		getAllStages()
		{
			const { category } = this.state;
			if (category === null)
			{
				return [];
			}

			return [
				...category.processStages,
				...category.successStages,
				...category.failedStages,
			];
		}

		findFirstStageId()
		{
			const stages = this.getAllStages();

			return stages[0] && stages[0].id;
		}

		getStageIdByStatusId(statusId)
		{
			const stage = this.getAllStages().find((stage) => stage.statusId === statusId);
			if (stage)
			{
				return stage.id;
			}

			return null;
		}

		getStatusIdByStageId(stageId)
		{
			const stage = this.getAllStages().find((stage) => stage.id === stageId);
			if (stage)
			{
				return stage.statusId;
			}

			return null;
		}

		isExistingEntity()
		{
			if (this.model)
			{
				return this.model['ID'] > 0;
			}

			if (this.props.hasOwnProperty('entityId'))
			{
				return this.props.entityId > 0;
			}

			return false;
		}

		showHint()
		{
			Notify.showUniqueMessage(
				BX.message('M_CRM_E_D_TOOLBAR_DISABLED_HINT'),
				BX.message('M_CRM_E_D_TOOLBAR_DISABLED_TITLE'),
			);
		}

		handleStageSelect({ id })
		{
			if (this.state.readOnly)
			{
				return;
			}

			if (this.state.activeStageId !== id)
			{
				this.lastStageId = this.state.activeStageId;
				this.setState({ activeStageId: id });
			}
		}

		rollbackStageChange()
		{
			if (this.state.activeStageId !== this.lastStageId)
			{
				this.setState({ activeStageId: this.lastStageId });
			}
		}

		processStageChange()
		{
			if (this.state.readOnly)
			{
				return;
			}

			if (this.state.activeStageId === this.getActiveStageId())
			{
				return;
			}

			Keyboard.dismiss();

			let promise = Promise.resolve();

			if (this.detailCard.isToolPanelVisible())
			{
				promise = promise.then(() => this.askToSaveAllData());
			}

			promise
				.then(() => this.processSaveDetailCard())
				.catch(() => this.rollbackStageChange());
		}

		askToSaveAllData()
		{
			return new Promise((resolve, reject) => {
				Alert.confirm(
					BX.message('M_CRM_E_D_TOOLBAR_CHANGE_STAGE_TITLE'),
					BX.message('M_CRM_E_D_TOOLBAR_CHANGE_STAGE_DESCRIPTION'),
					[
						{
							text: BX.message('M_CRM_E_D_TOOLBAR_CHANGE_STAGE_CANCEL'),
							type: 'cancel',
							onPress: () => reject(),
						},
						{
							text: BX.message('M_CRM_E_D_TOOLBAR_CHANGE_STAGE_SAVE'),
							type: 'default',
							onPress: () => resolve(),
						},
					],
				);
			});
		}

		processSaveDetailCard()
		{
			return this.detailCard.handleSave({
				STAGE_ID: this.getStatusIdByStageId(this.state.activeStageId),
			});
		}

		getStyles()
		{
			return mergeImmutable(defaultStyles, BX.prop.getObject(this.props, 'style', {}));
		}

		render()
		{
			const { readOnly, category, activeStageId } = this.state;
			if (!category)
			{
				return View();
			}

			const isExistingEntity = this.isExistingEntity();
			const styles = this.getStyles();

			return View(
				{
					style: styles.rootWrapper,
					onClick: !isExistingEntity && (() => this.showHint()),
				},
				new FadeView({
					visible: false,
					fadeInOnMount: true,
					style: {},
					slot: () => {
						return Shadow(
							styles.shadow,
							View(
								{
									style: {
										opacity: isExistingEntity ? 1 : 0.3,
										...styles.mainWrapper,
									},
								},
								View(
									{
										style: styles.toolbarWrapper,
									},
									new StageToolbar({
										entityTypeId: TypeId.Deal,
										category,
										activeStageId,
										showAllStages: false,
										isStageIconFaded: !isExistingEntity,
										isSelectorEnabled: isExistingEntity && !readOnly,
										onStageSelect: this.handleStageSelect,
										onViewHidden: this.processStageChange,
									}),
								),
								View(
									{
										style: styles.connectionWrapper,
									},
									connectionComponent({
										data: this.props.connections || {},
										options: { horizontal: true },
									}),
								),
							),
						);
					},
				}),
			);
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
			color: '#e6e6e6',
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
			flex: 2,
			marginRight: 19,
		},
		connectionWrapper: {
			marginTop: 3,
		},
	};

	module.exports = { DealDetailToolbar };
});
