/**
 * @module tasks/checklist/widget
 */
jn.define('tasks/checklist/widget', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { PropTypes } = require('utils/validation');
	const { Checklist } = require('tasks/layout/checklist/list');
	const { showChecklistMoreMenu } = require('tasks/checklist/widget/src/more-menu');

	/**
	 * @class ChecklistWidget
	 */
	class ChecklistWidget
	{
		static open(props)
		{
			const checklistWidget = new ChecklistWidget(props);
			const checklistProps = checklistWidget.getShowProps();
			const { taskTitle, onClose, parentWidget = PageManager } = props;

			return new Promise((resolve) => {
				parentWidget
					.openWidget('layout', {
						titleParams: {
							text: taskTitle,
						},
					})
					.then((layoutWidget) => {
						layoutWidget.showComponent(new Checklist(checklistProps));
						checklistWidget.setParentWidget(layoutWidget);
						checklistWidget.updateRightButtons(false);
						layoutWidget.setListener((event) => {
							if (event === 'onViewRemoved' && onClose)
							{
								onClose();
							}
						});

						resolve({ checklistWidget, layoutWidget });
					}).catch(console.error);
			});
		}

		constructor(props)
		{
			this.props = props;
			this.parentWidget = null;
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnComplete = this.handleOnComplete.bind(this);
			this.handleOnShowMoreMenu = this.handleOnShowMoreMenu.bind(this);
		}

		setParentWidget(parentWidget)
		{
			this.parentWidget = parentWidget;
		}

		getParentWidget(parentWidget)
		{
			this.parentWidget = parentWidget;
		}

		getShowProps()
		{
			const { moreMenuActions, ...restProps } = this.props;

			return {
				...restProps,
				onChange: this.handleOnChange,
			};
		}

		handleOnChange()
		{
			this.updateRightButtons(true);
		}

		handleOnComplete()
		{
			const { onSave } = this.props;

			this.updateRightButtons(false);

			onSave();
		}

		handleOnShowMoreMenu()
		{
			const { moreMenuActions } = this.props;

			showChecklistMoreMenu({
				...moreMenuActions,
				parentWidget: this.parentWidget,
			});
		}

		updateRightButtons(isChanged)
		{
			const buttons = [];

			if (isChanged)
			{
				buttons.push({
					type: 'text',
					name: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_DONE'),
					color: AppTheme.colors.accentExtraDarkblue,
					callback: this.handleOnComplete,
				});
			}
			else
			{
				buttons.push({
					type: 'more',
					callback: this.handleOnShowMoreMenu,
				});
			}

			this.parentWidget.setRightButtons(buttons);
		}
	}

	ChecklistWidget.propTypes = {
		checklist: PropTypes.object,
		taskTitle: PropTypes.string,
		onClose: PropTypes.func,
		onSave: PropTypes.func,
	};

	module.exports = { ChecklistWidget };
});
