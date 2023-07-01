/**
 * @module layout/ui/wizard/backdrop
 */
jn.define('layout/ui/wizard/backdrop', (require, exports, module) => {

	const MEDIUM_POSITION_PERCENT = 65;
	const BACKGROUND_COLOR = '#eef2f4';

	const { Wizard } = require('layout/ui/wizard');

	/**
	 * @class BackdropWizard
	 */
	class BackdropWizard extends LayoutComponent
	{

		constructor(props)
		{
			super(props);

			const { steps, layoutWidget } = props;
			this.layoutWidget = layoutWidget;
			this.stepsIds = steps.map(({ id }) => id);
			/**
			 * @type {Wizard}
			 */
			this.wizard = this.createWizard();
		}

		getStep(stepId)
		{
			const { steps } = this.props;

			return steps.find(({ id }) => id === stepId).step;
		}

		/**
		 * @param props
		 * @param {Object[]} props.steps
		 * @param {Object} props.layoutWidget
		 * @param widgetParams
		 */
		static open(props, widgetParams)
		{
			return new Promise((resolve) => {
				PageManager.openWidget('layout', BackdropWizard.getWidgetParams(widgetParams))
					.then((layoutWidget) => {
						const widgetWizard = new BackdropWizard({ ...props, layoutWidget });
						layoutWidget.showComponent(widgetWizard);
						layoutWidget.enableNavigationBarBorder(false);

						resolve({ layoutWidget, wizard: widgetWizard.getWizard() });
					});
			});
		}

		static getWidgetParams(widgetParams)
		{
			return {
				backdrop: {
					forceDismissOnSwipeDown: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
					mediumPositionPercent: MEDIUM_POSITION_PERCENT,
					navigationBarColor: BACKGROUND_COLOR,
					onlyMediumPosition: true,
					shouldResizeContent: true,
					swipeAllowed: true,
					swipeContentAllowed: false,
					...widgetParams,
				},
			};
		}

		getWizard()
		{
			return this.wizard;
		}

		createWizard()
		{
			return new Wizard({
				parentLayout: this.layoutWidget,
				steps: this.stepsIds,
				stepForId: this.getStep.bind(this),
			});
		}

		render()
		{
			return this.getWizard();
		}

	}

	module.exports = { BackdropWizard };
});