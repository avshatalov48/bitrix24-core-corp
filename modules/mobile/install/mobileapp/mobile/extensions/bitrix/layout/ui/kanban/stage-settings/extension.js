/**
 * @module layout/ui/kanban/stage-settings
 */
jn.define('layout/ui/kanban/stage-settings', (require, exports, module) => {
	const { EntityName } = require('layout/ui/entity-name');
	const { mergeImmutable } = require('utils/object');
	const { stringify } = require('utils/string');
	const { getStageNavigationIcon } = require('assets/stages');
	const { throttle } = require('utils/function');
	const { confirmDestructiveAction } = require('alert');
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');

	const SUCCESS_SEMANTICS = 'S';

	const svgImages = {
		deleteIcon: '<svg width="16" height="21" viewBox="0 0 16 21" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.22602 0H6.15062V1.54677H1.43631C0.64306 1.54677 0 2.18983 0 2.98309V4.64037H15.377V2.98309C15.377 2.18983 14.7339 1.54677 13.9407 1.54677H9.22602V0Z" fill="#828B95"/><path d="M1.53777 6.18721H13.8394L12.6864 19.2351C12.6427 19.7294 12.2287 20.1084 11.7326 20.1084H3.64459C3.14842 20.1084 2.73444 19.7294 2.69077 19.2351L1.53777 6.18721Z" fill="#828B95"/></svg>',
	};

	/**
	 * @class KanbanStageSettings
	 */
	class KanbanStageSettings extends LayoutComponent
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
			};
		}

		static open(params, widgetParams = {}, parentWidget = PageManager)
		{
			return new Promise((resolve, reject) => {
				parentWidget
					.openWidget('layout', mergeImmutable(this.getWidgetParams(), widgetParams))
					.then((layout) => {
						layout.enableNavigationBarBorder(false);

						layout.showComponent(new this({ ...params, layout }));
						resolve(layout);
					})
					.catch(reject);
			});
		}

		/**
		 * @param {StageDetailProps} props
		 */
		constructor(props)
		{
			super(props);
			this.changedFields = {};

			this.isChanged = false;

			this.deleteStageHandler = throttle(this.deleteStage, 500, this);
			this.updateStageHandler = throttle(this.updateStage, 500, this);
		}

		get layout()
		{
			return BX.prop.get(this.props, 'layout', null);
		}

		get stage()
		{
			return BX.prop.getObject(this.props, 'stage', {});
		}

		get stageName()
		{
			return BX.prop.getString(this.stage, 'name', null);
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
				this.updateNavigationTitleAndIcon();
				this.layout.setRightButtons([
					{
						name: Loc.getMessage('STAGE_DETAIL_SAVE'),
						type: 'text',
						color: AppTheme.colors.accentMainLinks,
						callback: () => this.saveAndClose(),
					},
				]);
			}
		}

		getTitleForNavigation()
		{
			let name = '';
			if ('name' in this.changedFields)
			{
				name = this.changedFields.name;
			}
			else
			{
				name = this.stageName;
			}

			return (
				stringify(name) === ''
					? Loc.getMessage('STAGE_DETAIL_FUNNEL_EMPTY')
					: Loc.getMessage('STAGE_DETAIL_FUNNEL').replace('#STAGE_NAME#', name)
			);
		}

		saveAndClose()
		{
			if (this.isChanged)
			{
				this.updateStageHandler()
					.then(() => {
						this.layout.close();
					})
					.catch((error) => {
						console.error(error);
					});
			}
			else
			{
				this.layout.close();
			}
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
					},
				},
				ScrollView(
					{
						resizableByKeyboard: true,
						safeArea: {
							bottom: true,
							top: true,
							left: true,
							right: true,
						},
						style: {
							backgroundColor: AppTheme.colors.bgSecondary,
							flexDirection: 'column',
							flex: 1,
						},
					},
					this.renderContent(),
				),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
					},
					onClick: () => Keyboard.dismiss(),
					onPan: () => Keyboard.dismiss(),
				},
				this.renderStageName(),
				this.renderColorPicker(),
				this.renderDeleteButton(),
			);
		}

		renderStageName()
		{
			return View(
				{
					style: {
						marginBottom: 8,
					},
				},
				new EntityName({
					focus: this.hasDefaultName(),
					iconColor: AppTheme.colors.base3,
					title: Loc.getMessage('STAGE_DETAIL_NAME'),
					showTitle: true,
					name: this.stageName || this.changedFields.name,
					placeholder: Loc.getMessage('STAGE_DETAIL_DEFAULT_STAGE_NAME'),
					required: true,
					showRequired: false,
					onChange: (value) => {
						this.isChanged = true;
						this.changedFields.name = value;
						this.updateNavigationTitle();
					},
					config: {
						selectionOnFocus: this.hasDefaultName(),
						enableKeyboardHide: true,
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
			return this.stageName === Loc.getMessage('STAGE_DETAIL_DEFAULT_STAGE_DEFAULT_NAME');
		}

		renderColorPicker()
		{
			return View(
				{
					style: {
						marginBottom: 18,
					},
				},
				new UI.ColorPicker({
					currentColor: this.changedFields.color || this.stage.color,
					onChangeColor: (color) => this.onChangeColor(color),
					layout: this.layout,
				}),
			);
		}

		onChangeColor(color)
		{
			if (this.changedFields.color !== color)
			{
				this.isChanged = true;
				this.changedFields.color = color;
				this.updateNavigationTitleAndIcon();
			}
		}

		updateNavigationTitleAndIcon()
		{
			const color = this.changedFields.color || this.stage.color;
			this.layout.setTitle({
				text: this.getTitleForNavigation(),
				svg: {
					content: getStageNavigationIcon(color),
				},
			});
		}

		updateNavigationTitle()
		{
			if (!this.layout)
			{
				return;
			}

			const text = this.getTitleForNavigation();

			this.layout.setTitle({ text }, true);
		}

		renderDeleteButton()
		{
			const { id, semantics } = this.stage;

			if (semantics === SUCCESS_SEMANTICS)
			{
				return null;
			}

			return new BaseButton({
				style: {
					button: {
						padding: 20,
						borderRadius: 12,
						backgroundColor: AppTheme.colors.bgContentPrimary,
						flexDirection: 'row',
						alignItems: 'center',
						height: 'auto',
						justifyContent: 'flex-start',
						marginBottom: 8,
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
				text: Loc.getMessage('STAGE_DETAIL_DELETE'),
				onClick: () => this.openAlertOnDelete(id),
			});
		}

		openAlertOnDelete(stageId)
		{
			confirmDestructiveAction({
				title: '',
				description: Loc.getMessage('STAGE_DETAIL_DELETE_TEXT'),
				onDestruct: () => {
					this.deleteStageHandler(stageId)
						.then((() => {
							this.layout.close();
						}))
						.catch((error) => {
							throw error;
						});
				},
			});
		}

		/**
		 * @abstract
		 */
		updateStage()
		{
			return Promise.reject(new Error('Method "updateStage" must be implemented.'));
		}

		/**
		 * @abstract
		 */
		deleteStage()
		{
			return Promise.reject(new Error('Method "deleteStage" must be implemented.'));
		}
	}

	module.exports = { KanbanStageSettings };
});
