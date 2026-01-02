/**
 * @module layout/ui/kanban/settings
 */
jn.define('layout/ui/kanban/settings', (require, exports, module) => {
	const { confirmDestructiveAction } = require('alert');
	const { stringify } = require('utils/string');
	const { isEqual } = require('utils/object');
	const { throttle } = require('utils/function');
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { Notify } = require('notify');
	const { Color, Indent, Corner } = require('tokens');
	const { StringInput, InputSize, InputMode, InputDesign } = require('ui-system/form/inputs/string');
	const { Type } = require('type');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { NavigationLoader } = require('navigation-loader');
	const { LoadingScreenComponent } = require('layout/ui/loading-screen');
	const {
		Stage,
		STAGE_TYPE,
		SEMANTICS,
		DEFAULT_PROCESS_STAGE_COLOR,
		DEFAULT_FAILED_STAGE_COLOR,
	} = require('layout/ui/kanban/settings/stage');
	const { Icon, IconView } = require('ui-system/blocks/icon');
	const { Text3 } = require('ui-system/typography/text');

	/**
	 * @class KanbanSettings
	 */
	class KanbanSettings extends LayoutComponent
	{
		static getWidgetParams()
		{
			return {
				modal: true,
				backgroundColor: Color.bgSecondary.toHex(),
				backdrop: {
					showOnTop: true,
					forceDismissOnSwipeDown: true,
					horizontalSwipeAllowed: false,
					swipeContentAllowed: false,
					navigationBarColor: Color.bgSecondary.toHex(),
				},
				titleParams: {
					type: 'dialog',
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
			this.state = {
				error: false,
			};
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
				if (!this.isDefault)
				{
					this.layout.setRightButtons([
						{
							testId: `kanban-${Icon.TRASHCAN.getIconName()}`,
							type: Icon.TRASHCAN.getIconName(),
							color: Color.base4.toHex(),
							callback: () => this.openAlertOnDeleteCategory(),
						},
					]);
				}

				this.updateLayoutTitle();
			}
		}

		updateLayoutTitle()
		{
			this.layout.setTitle({
				text: this.getTitleForNavigation(),
				type: 'dialog',
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
				{},
				this.isLoading() ? this.renderLoader() : this.renderContentWrapper(),
			);
		}

		isLoading()
		{
			return this.stageIdsBySemantics.processStages.length === 0;
		}

		renderLoader()
		{
			return new LoadingScreenComponent({ backgroundColor: Color.bgContentPrimary.toHex() });
		}

		renderContentWrapper()
		{
			return Box(
				{
					resizableByKeyboard: true,
					safeArea: { bottom: true },
					footer: this.renderFooter(),
					withScroll: true,
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
			);
		}

		renderCategoryName()
		{
			return Area(
				{
					divider: true,
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				StringInput({
					testId: 'category-name',
					focus: this.hasDefaultName(),
					readOnly: false,
					mode: InputMode.STROKE,
					design: InputDesign.GREY,
					size: InputSize.L,
					label: Loc.getMessage('CATEGORY_DETAIL_NAME_TITLE'),
					placeholder: Loc.getMessage('CATEGORY_DETAIL_DEFAULT_CATEGORY_NAME2'),
					value: this.changedFields?.name ?? this.kanbanSettingsName,
					onChange: (value) => {
						const error = Type.isString(value) && value.length === 0;

						this.changedFields.name = stringify(value);
						this.layout.setTitle({
							text: this.getTitleForNavigation(),
						}, true);

						if (error !== this.state.error)
						{
							this.setState({ error });
						}
					},
					error: this.state.error || false,
					errorText: Loc.getMessage('CATEGORY_DETAIL_NAME_ERROR'),
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
						borderRadius: Corner.L.toNumber(),
						marginBottom: Indent.M.toNumber(),
					},
				},
				this.renderButton({
					text: Loc.getMessage('CATEGORY_DETAIL_CREATE_PROCESS_STAGE'),
					onClick: () => {
						this.createStageAndOpenStageDetail(SEMANTICS.PROCESS, 'processStages');
					},
				}),
				this.renderButton({
					text: Loc.getMessage('CATEGORY_DETAIL_CREATE_FAILED_STAGE'),
					onClick: () => {
						this.createStageAndOpenStageDetail(SEMANTICS.FAILED, 'failedStages');
					},
				}),
			);
		}

		renderFooter()
		{
			return BoxFooter(
				{
					safeArea: Application.getPlatform() !== 'android',
					keyboardButton: {
						text: Loc.getMessage('CATEGORY_DETAIL_SAVE'),
						onClick: () => this.saveAndClose(),
						testId: 'category-detail-save-button',
						disabled: this.state.error,
					},
				},
				Button(
					{
						design: ButtonDesign.FILLED,
						size: ButtonSize.L,
						text: Loc.getMessage('CATEGORY_DETAIL_SAVE'),
						stretched: true,
						onClick: () => this.saveAndClose(),
						testId: 'category-detail-save-button',
						disabled: this.state.error,
					},
				),
			);
		}

		renderButton({ text, onClick })
		{
			return View(
				{
					style: {
						padding: Indent.XL3.toNumber(),
						flexDirection: 'row',
					},
					onClick: () => onClick(),
				},
				IconView({
					icon: Icon.PLUS,
					size: 28,
					color: Color.base4,
				}),
				Text3({
					text,
					color: Color.base4,
					style: {
						marginHorizontal: Indent.M.toNumber(),
					},
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
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

	module.exports = {
		KanbanSettings,
		Stage,
		STAGE_TYPE,
		SEMANTICS,
		DEFAULT_PROCESS_STAGE_COLOR,
		DEFAULT_FAILED_STAGE_COLOR,
	};
});
