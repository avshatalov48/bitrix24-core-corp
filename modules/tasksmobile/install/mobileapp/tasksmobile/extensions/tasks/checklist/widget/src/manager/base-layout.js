/**
 * @module tasks/checklist/widget/src/manager/base-layout
 */
jn.define('tasks/checklist/widget/src/manager/base-layout', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { outline } = require('assets/icons');
	const { PropTypes } = require('utils/validation');

	/**
	 * @class ChecklistBottomSheet
	 * @abstract
	 */
	class ChecklistBaseLayout
	{
		/**
		 * @param {ChecklistBottomSheetProps} props
		 */
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

		update(params)
		{
			const { highlightMoreButton } = params;

			this.showMoreButton({ highlightMoreButton });
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
		 * @return {PageManager}
		 */
		getParentWidget()
		{
			const { parentWidget } = this.props;

			if (!parentWidget)
			{
				console.warn('ChecklistWidget: parameter <parentWidget> is not passed to the component');

				return null;
			}

			return parentWidget;
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
			const { focusedItemId, highlightMoreButton } = this.props;

			this.setLayoutWidget(layoutWidget);
			this.showMoreButton({ highlightMoreButton });

			if (focusedItemId)
			{
				this.onChange();
			}
		}

		/**
		 * @protected
		 */
		showMoreButton({ highlightMoreButton } = {})
		{
			const { onShowMoreMenu } = this.props;

			if (!onShowMoreMenu)
			{
				return;
			}

			this.layoutWidget.setRightButtons([
				{
					type: 'more',
					callback: onShowMoreMenu,
					svg: {
						content: outline.moreWithBackgroundAndDot({
							moreColor: highlightMoreButton ? Color.baseWhiteFixed : Color.base4.toHex(),
							backgroundColor: highlightMoreButton ? Color.accentMainPrimary.toHex() : null,
						}),
					},
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
					color: Color.accentExtraDarkblue.toHex(),
					callback: this.handleOnComplete,
				});
			}

			this.layoutWidget.setRightButtons(button);
		}

		/**
		 * @protected
		 */
		onChange({ alert } = {})
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
