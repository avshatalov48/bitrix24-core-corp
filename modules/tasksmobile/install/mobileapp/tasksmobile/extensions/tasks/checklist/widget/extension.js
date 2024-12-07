/**
 * @module tasks/checklist/widget
 */
jn.define('tasks/checklist/widget', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { Checklist } = require('tasks/layout/checklist/list');
	const { checklistWidgetFactoryLayout, BOTTOM_SHEET, PAGE_LAYOUT } = require(
		'tasks/checklist/widget/src/manager/factory-layout',
	);
	const { ChecklistMoreMenu } = require('tasks/checklist/widget/src/more-menu');

	/**
	 * @typedef {Object} ChecklistWidgetProps
	 * @property {object} [checklist]
	 * @property {CheckListFlatTree} [parentWidget]
	 * @property {boolean} [inLayout]
	 * @property {number | string} [focusedItemId]
	 * @property {boolean} [hideCompleted]
	 * @property {boolean} [hideMoreMenu=false]
	 *
	 * @class ChecklistWidget
	 */
	class ChecklistWidget
	{
		/**
		 * @param {ChecklistWidgetProps} props
		 * @return {Promise}
		 */
		static async open(props)
		{
			const checklistWidget = new ChecklistWidget(props);

			return checklistWidget.initialOpenPageManager();
		}

		constructor(props)
		{
			this.props = props;
			/** @type {ChecklistBaseLayout} */
			this.openManager = null;

			/** @type {Checklist} */
			this.checklistComponent = null;
			this.parentWidget = props.parentWidget;
			this.menuMore = this.createMoreMenu();
		}

		createMoreMenu()
		{
			const {
				hideCompleted,
				menuMore: {
					actions,
					accessRestrictions,
				},
			} = this.props;

			return new ChecklistMoreMenu({
				...actions,
				accessRestrictions,
				hideCompleted,
				onShowOnlyMine: this.onChangeFilter,
				onHideCompleted: this.onHideCompleted,
			});
		}

		async initialOpenPageManager()
		{
			const { parentWidget, inLayout, hideCompleted, hideMoreMenu } = this.props;

			const checklistComponent = this.getChecklistComponent();
			const layoutType = inLayout ? PAGE_LAYOUT : BOTTOM_SHEET;

			/** @type ChecklistBaseLayout */
			const openManager = await checklistWidgetFactoryLayout({
				layoutType,
				parentWidget,
				checklist: Checklist,
				onSave: this.handleOnSave,
				onClose: this.handleOnClose,
				component: checklistComponent,
				onShowMoreMenu: hideMoreMenu ? null : this.handleOnShowMoreMenu,
				highlightMoreButton: hideCompleted,
			});

			const layoutWidget = await openManager.open();

			this.setParentWidget(layoutWidget);
			this.setOpenManager(openManager);
			this.setChecklistComponent(checklistComponent);
			checklistComponent.setParentWidget(layoutWidget);

			return openManager;
		}

		onHideCompleted = (params = {}) => {
			const { menuMore: menuMoreParams } = this.props;
			const { onToggleCompletedItems } = menuMoreParams;

			if (onToggleCompletedItems)
			{
				onToggleCompletedItems(params.hideCompleted);
			}

			void this.onChangeFilter(params);
		};

		onChangeFilter = async (params) => {
			this.checklistComponent.reload(params);
			const moreMenuParams = await this.menuMore.reload(params);

			this.openManager.update({
				highlightMoreButton: Object.values(moreMenuParams).some((value) => value),
			});
		};

		/**
		 * @private
		 * @return {Checklist}
		 */
		getChecklistComponent()
		{
			const checklistProps = this.getChecklistProps();

			return new Checklist(checklistProps);
		}

		getChecklistProps()
		{
			const { menuMore = {}, ...restProps } = this.props;
			const { onMoveToCheckList } = menuMore.actions;

			return {
				...restProps,
				onMoveToCheckList,
				onChange: this.handleOnChange,
				onChangeFilter: this.onChangeFilter,
			};
		}

		getChecklistId()
		{
			const { checklist } = this.props;

			return checklist.getId();
		}

		setParentWidget(layoutWidget)
		{
			this.parentWidget = layoutWidget;
		}

		setOpenManager(openManager)
		{
			this.openManager = openManager;
		}

		setChecklistComponent(checklistComponent)
		{
			this.checklistComponent = checklistComponent;
		}

		handleOnShowMoreMenu = () => {
			Keyboard.dismiss();
			this.menuMore.show(this.parentWidget);
		};

		handleOnChange = () => {
			const { onCompletedChanged } = this.props;

			if (onCompletedChanged)
			{
				onCompletedChanged();
			}

			this.openManager.onChange();
		};

		handleOnSave = () => {
			const { onSave } = this.props;

			if (onSave)
			{
				onSave(this.getChecklistId());
			}
		};

		handleOnClose = () => {
			const { onClose } = this.props;

			if (onClose)
			{
				onClose(this.getChecklistId());
			}
		};
	}

	ChecklistWidget.propTypes = {
		checklist: PropTypes.object,
		onSave: PropTypes.func,
		onClose: PropTypes.func,
		menuMore: PropTypes.object,
	};

	module.exports = { ChecklistWidget };
});
