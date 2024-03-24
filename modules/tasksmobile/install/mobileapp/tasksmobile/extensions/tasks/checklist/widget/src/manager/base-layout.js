/**
 * @module tasks/checklist/widget/src/manager/base-layout
 */
jn.define('tasks/checklist/widget/src/manager/base-layout', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { PropTypes } = require('utils/validation');

	/**
	 * @class ChecklistBottomSheet
	 * @abstract
	 */
	class ChecklistBaseLayout
	{
		constructor(props)
		{
			this.props = props;
			this.alert = null;
			this.layoutWidget = null;
			this.onPreventDismiss = false;
			this.handleOnSave = this.handleOnSave.bind(this);
			this.handleOnClose = this.handleOnClose.bind(this);
			this.handleOnComplete = this.handleOnComplete.bind(this);
		}

		/**
		 * @public
		 * @abstract
		 */
		close()
		{}

		/**
		 * @public
		 * @abstract
		 * @return {Promise}
		 */
		open()
		{}

		/**
		 * @private
		 * @return {string}
		 */
		getTitle()
		{
			return Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_WIDGET_TITLE');
		}

		/**
		 * @public
		 * @return {Checklist}
		 */
		getParentWidget()
		{
			const { parentWidget } = this.props;

			return parentWidget || PageManager;
		}

		/**
		 * @public
		 * @return {Checklist}
		 */
		getComponent()
		{
			const { component } = this.props;

			return component;
		}

		setLayoutWidget(layoutWidget)
		{
			this.layoutWidget = layoutWidget;
		}

		getLayoutWidget()
		{
			return this.layoutWidget;
		}

		/**
		 * @protected
		 * @param {JSStackNavigation} layoutWidget
		 */
		actionsAfterOpenWidget(layoutWidget)
		{
			const { focusedItemId } = this.props;

			this.setLayoutWidget(layoutWidget);
			this.showMoreButton();

			if (focusedItemId)
			{
				this.onChange();
			}
		}

		/**
		 * @protected
		 */
		showMoreButton()
		{
			const { onShowMoreMenu } = this.props;

			this.layoutWidget.setRightButtons([
				{
					type: 'more',
					callback: onShowMoreMenu,
				},
			]);
		}

		/**
		 * @protected
		 * @param {boolean} show
		 */
		showSaveButton(show)
		{
			return null;

			const button = [];

			if (show)
			{
				button.push({
					type: 'text',
					name: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MORE_MENU_DONE'),
					color: AppTheme.colors.accentExtraDarkblue,
					callback: this.handleOnComplete,
				});
			}

			this.layoutWidget.setRightButtons(button);
		}

		/**
		 * @protected
		 */
		onChange({ alert })
		{
			this.alert = alert;
			this.showSaveButton(true);
			this.showPreventDismiss(true);
		}

		/**
		 * @protected
		 */
		handleOnComplete()
		{
			this.showSaveButton(false);
			this.showPreventDismiss(false);
			this.handleOnSave();
		}

		handleOnClose()
		{
			const { onClose } = this.props;
			if (onClose)
			{
				onClose();
			}

			this.close();
		}

		handleOnSave()
		{
			const { onSave } = this.props;

			if (onSave)
			{
				onSave();
			}

			this.close();
		}

		/**
		 * @public
		 * @param {boolean} show
		 */
		showPreventDismiss(show)
		{
			this.onPreventDismiss = show;
		}
	}

	ChecklistBaseLayout.propTypes = {
		parentWidget: PropTypes.object,
		component: PropTypes.object,
		onSave: PropTypes.func,
		onClose: PropTypes.func,
		onShowMoreMenu: PropTypes.func,
	};

	module.exports = { ChecklistBaseLayout };
});
