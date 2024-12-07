/**
 * @module tasks/checklist/widget/src/manager/bottom-sheet
 */
jn.define('tasks/checklist/widget/src/manager/bottom-sheet', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Haptics } = require('haptics');
	const { BottomSheet } = require('bottom-sheet');
	const { Alert } = require('alert');
	const { PropTypes } = require('utils/validation');
	const { ChecklistBaseLayout } = require('tasks/checklist/widget/src/manager/base-layout');

	/**
	 * @class ChecklistBottomSheet
	 */
	class ChecklistBottomSheet extends ChecklistBaseLayout
	{
		constructor(props)
		{
			super(props);

			this.handleOnPreventDismiss = this.handleOnPreventDismiss.bind(this);
		}

		/**
		 * @public
		 * @param {object} props
		 * @param {LayoutComponent} [props.component]
		 * @param {LayoutWidget} [props.parentWidget]
		 * @return {Promise<LayoutWidget>}
		 */
		async open(props)
		{
			const checklistBottomSheet = new BottomSheet({
				component: this.getComponent(),
				titleParams: {
					text: this.getTitle(),
					type: 'entity',
				},
			});

			const layoutWidget = await checklistBottomSheet
				.setParentWidget(this.getParentWidget())
				.setBackgroundColor(Color.bgContentPrimary.toHex())
				.setNavigationBarColor(Color.bgContentPrimary.toHex())
				.showNavigationBar()
				.setMediumPositionPercent(85)
				.disableContentSwipe()
				.open();

			this.actionsAfterOpenWidget(layoutWidget);
			this.initialPreventDismiss();

			return layoutWidget;
		}

		/**
		 * @private
		 */
		initialPreventDismiss()
		{
			this.layoutWidget.preventBottomSheetDismiss(true);
			this.layoutWidget.on('preventDismiss', this.handleOnPreventDismiss);
		}

		/**
		 * @private
		 */
		handleOnPreventDismiss()
		{
			Haptics.impactLight();

			if (this.alert?.show)
			{
				this.openAlert();
			}
			else
			{
				this.handleOnClose();
			}
		}

		openAlert()
		{
			const { params } = this.alert;

			Alert.alert('', params?.title);
		}

		close()
		{
			this.layoutWidget.close();
		}
	}

	ChecklistBottomSheet.propTypes = {
		parentWidget: PropTypes.object,
		component: PropTypes.object,
		onSave: PropTypes.func,
		onClose: PropTypes.func,
		onShowMoreMenu: PropTypes.func,
	};

	module.exports = { ChecklistBottomSheet };
});
