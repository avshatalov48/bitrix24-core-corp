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
	 * @class ChecklistWidget
	 */
	class ChecklistWidget
	{
		/**
		 * @param {object} props
		 * @param {object} [props.checklist]
		 * @param {CheckListFlatTree} [props.parentWidget]
		 * @param {boolean} [props.inLayout]
		 * @param {number | string} [props.focusedItemId]
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
			this.handleOnSave = this.handleOnSave.bind(this);
			this.handleOnClose = this.handleOnClose.bind(this);
			this.handleOnChange = this.handleOnChange.bind(this);
			this.onChangeFilter = this.onChangeFilter.bind(this);
			this.handleOnShowMoreMenu = this.handleOnShowMoreMenu.bind(this);

			this.moreMenuActions = new ChecklistMoreMenu({
				...props.moreMenuActions,
				onShowOnlyMine: this.onChangeFilter,
				onHideCompleted: this.onChangeFilter,
			});
		}

		async initialOpenPageManager()
		{
			const { parentWidget, inLayout } = this.props;

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
				onShowMoreMenu: this.handleOnShowMoreMenu,
			});

			const layoutWidget = await openManager.open();

			this.setParentWidget(layoutWidget);
			this.setOpenManager(openManager);
			this.setChecklistComponent(checklistComponent);
			checklistComponent.setParentWidget(layoutWidget);

			return openManager;
		}

		async onChangeFilter(params)
		{
			this.checklistComponent.reload(params);
			const moreMenuParams = await this.moreMenuActions.reload(params);

			this.openManager.update({ highlightMoreButton: Object.values(moreMenuParams).some((value) => value) });
		}

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
			const { moreMenuActions = {}, ...restProps } = this.props;
			const { onMoveToCheckList } = moreMenuActions;

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

		handleOnShowMoreMenu()
		{
			Keyboard.dismiss();
			this.moreMenuActions.show(this.parentWidget);
		}

		handleOnChange(params)
		{
			this.openManager.onChange(params);
		}

		handleOnSave()
		{
			const { onSave } = this.props;

			if (onSave)
			{
				onSave(this.getChecklistId());
			}
		}

		handleOnClose()
		{
			const { onClose } = this.props;

			if (onClose)
			{
				onClose(this.getChecklistId());
			}
		}
	}

	ChecklistWidget.propTypes = {
		checklist: PropTypes.object,
		onSave: PropTypes.func,
		onClose: PropTypes.func,
		moreMenuActions: PropTypes.shape({
			onCreateChecklist: PropTypes.func,
			onRemove: PropTypes.func,
			onMoveToCheckList: PropTypes.func,
		}),
	};

	module.exports = { ChecklistWidget };
});
