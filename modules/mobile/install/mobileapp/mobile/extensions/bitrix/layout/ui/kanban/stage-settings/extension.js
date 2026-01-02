/**
 * @module layout/ui/kanban/stage-settings
 */
jn.define('layout/ui/kanban/stage-settings', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');
	const { stringify } = require('utils/string');
	const { throttle } = require('utils/function');
	const { confirmDestructiveAction } = require('alert');
	const { Loc } = require('loc');
	const { ColorPicker } = require('layout/ui/color-picker');
	const { StringInput, InputSize, InputMode, InputDesign } = require('ui-system/form/inputs/string');
	const { Area } = require('ui-system/layout/area');
	const { Color } = require('tokens');
	const { Type } = require('type');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { ButtonSize, ButtonDesign, Button } = require('ui-system/form/buttons/button');
	const { Box } = require('ui-system/layout/box');
	const { Icon } = require('ui-system/blocks/icon');

	const SUCCESS_SEMANTICS = 'S';

	/**
	 * @class KanbanStageSettings
	 */
	class KanbanStageSettings extends LayoutComponent
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
		 * @typedef {Object} StageDetailProps
		 * @property {Object} layout
		 * @property {Object} stage
		 * @property {string} stage.name
		 * @property {string} stage.color
		 * @property {string} stage.id
		 * @property {string} stage.statusId
		 * @property {string} stage.semantics
		 * @property {Function} updateCrmStage
		 * @property {Function} deleteCrmStage
		 */
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
			this.state = {
				error: false,
			};
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
				this.updateNavigationTitle();
				const { id, semantics } = this.stage;
				if (semantics !== SUCCESS_SEMANTICS)
				{
					this.layout.setRightButtons([
						{
							testId: `stage-${Icon.TRASHCAN.getIconName()}`,
							type: Icon.TRASHCAN.getIconName(),
							color: Color.base4.toHex(),
							callback: () => this.openAlertOnDelete(id),
						},
					]);
				}
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
					style: {
						flexDirection: 'column',
					},
					onClick: () => Keyboard.dismiss(),
					onPan: () => Keyboard.dismiss(),
				},
				this.renderStageName(),
				this.renderColorPicker(),
			);
		}

		renderStageName()
		{
			return Area(
				{
					divider: true,
					style: {
						backgroundColor: Color.bgContentPrimary.toHex(),
					},
				},
				StringInput({
					testId: 'EntityNameField',
					focus: this.hasDefaultName(),
					readOnly: false,
					mode: InputMode.STROKE,
					design: InputDesign.GREY,
					size: InputSize.L,
					label: Loc.getMessage('STAGE_DETAIL_NAME'),
					placeholder: Loc.getMessage('STAGE_DETAIL_DEFAULT_STAGE_NAME'),
					value: Object.prototype.hasOwnProperty.call(this.changedFields, 'name')
						? this.changedFields.name
						: this.stageName,
					onChange: (value) => {
						const preparedValue = stringify(value).trim();
						const error = Type.isString(preparedValue) && preparedValue.length === 0;
						this.isChanged = true;
						this.changedFields.name = preparedValue;
						if (error !== this.state.error)
						{
							this.setState({ error });
						}

						this.updateNavigationTitle();
					},
					error: this.state.error || false,
					errorText: Loc.getMessage('STAGE_DETAIL_NAME_ERROR'),
				}),
			);
		}

		hasDefaultName()
		{
			return this.stageName === Loc.getMessage('STAGE_DETAIL_DEFAULT_STAGE_DEFAULT_NAME');
		}

		renderColorPicker()
		{
			return new ColorPicker({
				title: Loc.getMessage('STAGE_DETAIL_COLOR_PICKER_TITLE'),
				currentColor: this.changedFields.color || this.stage.color,
				onChangeColor: (color) => this.onChangeColor(color),
				layout: this.layout,
			});
		}

		onChangeColor(color)
		{
			if (this.changedFields.color !== color)
			{
				this.isChanged = true;
				this.changedFields.color = color;
			}
		}

		updateNavigationTitle()
		{
			if (!this.layout)
			{
				return;
			}

			const text = this.getTitleForNavigation();

			this.layout.setTitle({ text, type: 'dialog' }, true);
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

		renderFooter()
		{
			return BoxFooter(
				{
					safeArea: Application.getPlatform() !== 'android',
					keyboardButton: {
						text: Loc.getMessage('STAGE_DETAIL_SAVE'),
						onClick: () => this.saveAndClose(),
						testId: 'stage-detail-save-button',
						disabled: this.state.error,
					},
				},
				Button(
					{
						design: ButtonDesign.FILLED,
						size: ButtonSize.L,
						text: Loc.getMessage('STAGE_DETAIL_SAVE'),
						stretched: true,
						onClick: () => this.saveAndClose(),
						testId: 'stage-detail-save-button',
						disabled: this.state.error,
					},
				),
			);
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
