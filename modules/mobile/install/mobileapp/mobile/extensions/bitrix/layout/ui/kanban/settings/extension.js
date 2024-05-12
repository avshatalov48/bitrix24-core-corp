/**
 * @module layout/ui/kanban/settings
 */
jn.define('layout/ui/kanban/settings', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { confirmDestructiveAction } = require('alert');
	const { stringify } = require('utils/string');
	const { isEqual } = require('utils/object');
	const { throttle } = require('utils/function');
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { Notify } = require('notify');

	const { funnelIcon } = require('assets/stages');
	const { NavigationLoader } = require('navigation-loader');

	const { EntityName } = require('layout/ui/entity-name');
	const {
		Stage,
		STAGE_TYPE,
		SEMANTICS,
		DEFAULT_PROCESS_STAGE_COLOR,
		DEFAULT_FAILED_STAGE_COLOR,
	} = require('layout/ui/kanban/settings/stage');

	/**
	 * @class KanbanSettings
	 */
	class KanbanSettings extends LayoutComponent
	{
		static getWidgetParams()
		{
			return {
				modal: true,
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					showOnTop: true,
					forceDismissOnSwipeDown: true,
					horizontalSwipeAllowed: false,
					swipeContentAllowed: false,
					navigationBarColor: AppTheme.colors.bgSecondary,
				},
				titleParams: {
					svg: {
						content: funnelIcon(),
					},
				},
			};
		}

		get layout()
		{
			return BX.prop.get(this.props, 'layout', null);
		}

		get isDefault()
		{
			return BX.prop.getBoolean(this.props, 'isDefault', false);
		}

		get status()
		{
			return BX.prop.getString(this.props, 'status', null);
		}

		get stageIdsBySemantics()
		{
			return BX.prop.getObject(this.props, 'stageIdsBySemantics', {});
		}

		get kanbanSettingsId()
		{
			return BX.prop.getString(this.props, 'kanbanSettingsId', null);
		}

		get kanbanSettingsName()
		{
			return BX.prop.getString(this.props, 'name', '');
		}

		constructor(props)
		{
			super(props);
			this.navigationLoader = NavigationLoader.getInstance(this.layout);
			this.changedFields = {};

			this.layoutClose = this.onLayoutClose.bind(this);

			this.scrollViewRef = null;
			this.categoryNameRef = null;
			this.onOpenStageDetail = throttle(this.openStageDetail, 500, this);

			this.createStageAndOpenStageDetail = throttle((semantics, semanticsType) => {
				this.createStage(semantics, semanticsType).then((stage) => {
					this.openStageDetail(stage);
				}).catch(console.error);
			}, 500, this);

			this.deleteCategoryHandler = throttle(this.deleteCategory, 500, this);
		}

		onLayoutClose()
		{
			this.layout.close();
		}

		componentDidMount()
		{
			this.initNavigation();
		}

		initNavigation()
		{
			if (this.layout)
			{
				this.layout.enableNavigationBarBorder(false);
				this.layout.setRightButtons([
					{
						name: Loc.getMessage('CATEGORY_DETAIL_SAVE'),
						type: 'text',
						color: AppTheme.colors.accentMainLinks,
						callback: () => this.saveAndClose(),
					},
				]);

				this.updateLayoutTitle();
			}
		}

		updateLayoutTitle()
		{
			this.layout.setTitle({
				text: this.getTitleForNavigation(),
				svg: {
					content: funnelIcon(),
				},
			});
		}

		saveAndClose()
		{
			if (this.hasChangedFields())
			{
				this.save()
					.then(() => {
						this.layout.close();
					})
					.catch(console.error);
			}
			else
			{
				this.layout.close();
			}
		}

		hasChangedFields()
		{
			const hasNameChanger = (typeof this.changedFields.name === 'string')
				&& this.changedFields.name !== this.kanbanSettingsName;
			const hasStageSortChanged = this.changedFields.stageIdsBySemantics
				&& !isEqual(
					this.changedFields.stageIdsBySemantics.processStages,
					this.stageIdsBySemantics.processStages,
				);

			return hasNameChanger || hasStageSortChanged;
		}

		getTitleForNavigation(props)
		{
			return Loc.getMessage('CATEGORY_DETAIL_FUNNEL_NOT_LOADED2');
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: AppTheme.colors.bgSecondary,
					},
				},
				this.isLoading()
					? this.renderLoader()
					: this.renderContentWrapper(),
			);
		}

		isLoading()
		{
			return this.stageIdsBySemantics.processStages.length === 0;
		}

		renderLoader()
		{
			return new LoadingScreenComponent();
		}

		renderContentWrapper()
		{
			return ScrollView(
				{
					ref: (ref) => {
						this.scrollViewRef = ref;
					},
					resizableByKeyboard: true,
					safeArea: {
						bottom: true,
						top: true,
						left: true,
						right: true,
					},
					style: {
						flex: 1,
					},
				},
				this.renderContent(),
			);
		}

		renderContent()
		{
			return View(
				{
					onClick: () => Keyboard.dismiss(),
					onPan: () => Keyboard.dismiss(),
				},
				this.renderCategoryName(),
				this.renderStageList(),
				this.renderStageButtons(),
				this.renderDeleteButton(),
			);
		}

		renderCategoryName()
		{
			return View(
				{
					style: {
						marginBottom: 8,
					},
				},
				new EntityName({
					focus: this.hasDefaultName(),
					name: this.changedFields.name || this.kanbanSettingsName,
					placeholder: Loc.getMessage('CATEGORY_DETAIL_DEFAULT_CATEGORY_NAME2'),
					required: true,
					showRequired: false,
					iconColor: AppTheme.colors.base3,
					onChange: (value) => {
						this.changedFields.name = stringify(value);
						this.layout.setTitle({
							text: this.getTitleForNavigation(),
						}, true);
					},
					config: {
						selectionOnFocus: this.hasDefaultName(),
						deepMergeStyles: {
							wrapper: {
								paddingTop: 0,
								paddingBottom: 0,
							},
							title: {
								marginBottom: 0,
							},
						},
					},
				}),
			);
		}

		hasDefaultName()
		{
			return this.kanbanSettingsName === Loc.getMessage('CATEGORY_DETAIL_DEFAULT_CATEGORY_NAME2');
		}

		renderStageList()
		{
			throw new Error('Method "renderStageList" must be implemented.');
		}

		renderStageButtons()
		{
			return View(
				{
					style: {
						borderRadius: 12,
						marginBottom: 8,
					},
				},
				this.renderCreateStageButton({
					buttonText: Loc.getMessage('CATEGORY_DETAIL_CREATE_PROCESS_STAGE'),
					onClick: () => {
						this.createStageAndOpenStageDetail(SEMANTICS.PROCESS, 'processStages');
					},
				}),
				this.renderCreateStageButton({
					buttonText: Loc.getMessage('CATEGORY_DETAIL_CREATE_FAILED_STAGE'),
					onClick: () => {
						this.createStageAndOpenStageDetail(SEMANTICS.FAILED, 'failedStages');
					},
				}),
			);
		}

		renderCreateStageButton({ buttonText, onClick })
		{
			return new BaseButton({
				style: {
					button: {
						paddingTop: 18,
						paddingBottom: 18,
						paddingLeft: 25,
						paddingRight: 25,
						borderRadius: 0,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						flexDirection: 'row',
						alignItems: 'center',
						height: 'auto',
						justifyContent: 'flex-start',
					},
					icon: {
						tintColor: AppTheme.colors.base3,
						width: 12,
						height: 12,
						marginRight: 22,
					},
					text: {
						color: AppTheme.colors.base1,
						fontSize: 18,
						fontWeight: 'normal',
					},
				},
				icon: svgImages.createIcon,
				text: buttonText,
				onClick,
			});
		}

		renderDeleteButton()
		{
			return new BaseButton({
				style: {
					button: {
						paddingTop: 18,
						paddingBottom: 18,
						paddingLeft: 25,
						paddingRight: 25,
						borderRadius: 12,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						flexDirection: 'row',
						alignItems: 'center',
						height: 'auto',
						justifyContent: 'flex-start',
						marginBottom: 8,
						opacity: this.isDefault ? 0.6 : 1,
					},
					icon: {
						tintColor: AppTheme.colors.base3,
						width: 15,
						height: 20,
						marginRight: 16,
					},
					text: {
						color: AppTheme.colors.base1,
						fontSize: 18,
						fontWeight: 'normal',
					},
				},
				icon: svgImages.deleteIcon,
				text: Loc.getMessage('CATEGORY_DETAIL_DELETE2'),
				onClick: () => this.openAlertOnDeleteCategory(),
			});
		}

		openAlertOnDeleteCategory()
		{
			if (this.isDefault)
			{
				const title = Loc.getMessage('CATEGORY_DETAIL_IS_DEFAULT_TITLE2_MSGVER_1');
				const text = Loc.getMessage('CATEGORY_DETAIL_IS_DEFAULT_TEXT');

				Haptics.notifyWarning();
				Notify.showUniqueMessage(text, title, { time: 5 });
			}
			else
			{
				confirmDestructiveAction({
					title: '',
					description: Loc.getMessage('CATEGORY_DETAIL_DELETE_CATEGORY2'),
					onDestruct: () => this.deleteCategoryHandler(this.kanbanSettingsId).then(this.layoutClose),
				});
			}
		}

		/**
		 * @abstract
		 */
		createStage(stageSemantics)
		{
			throw new Error('Method "createStage" must be implemented.');
		}

		/**
		 * @abstract
		 */
		deleteCategory(categoryId)
		{
			throw new Error('Method "deleteCategory" must be implemented.');
		}

		/**
		 * @abstract
		 */
		openStageDetail(stage)
		{
			throw new Error('Method "openStageDetail" must be implemented.');
		}

		/**
		 * @abstract
		 */
		save()
		{
			throw new Error('Method "openStageDetail" must be implemented.');
		}
	}

	const svgImages = {
		deleteIcon: `<svg width="16" height="21" viewBox="0 0 16 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.22602 0H6.15062V1.54677H1.43631C0.64306 1.54677 0 2.18983 0 2.98309V4.64037H15.377V2.98309C15.377 2.18983 14.7339 1.54677 13.9407 1.54677H9.22602V0Z" fill="${AppTheme.colors.base3}"/><path d="M1.53777 6.18721H13.8394L12.6864 19.2351C12.6427 19.7294 12.2287 20.1084 11.7326 20.1084H3.64459C3.14842 20.1084 2.73444 19.7294 2.69077 19.2351L1.53777 6.18721Z" fill="${AppTheme.colors.base3}"/></svg>`,
		createIcon: `<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 0H7V12H5V0Z" fill="${AppTheme.colors.base3}"/><path d="M12 5V7L0 7L1.19209e-07 5L12 5Z" fill="${AppTheme.colors.base3}"/></svg>`,
	};

	module.exports = {
		KanbanSettings,
		Stage,
		STAGE_TYPE,
		SEMANTICS,
		DEFAULT_PROCESS_STAGE_COLOR,
		DEFAULT_FAILED_STAGE_COLOR,
	};
});
