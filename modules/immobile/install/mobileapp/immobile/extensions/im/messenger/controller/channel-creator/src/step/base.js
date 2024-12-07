/**
 * @module im/messenger/controller/channel-creator/step/base
 */
jn.define('im/messenger/controller/channel-creator/step/base', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('loc');

	class Step extends LayoutComponent
	{
		/**
		 * @param props
		 * @param parentWidget
		 *
		 * @return Promise<LayoutWidget>
		 */
		static async open(props = {}, parentWidget = PageManager)
		{
			let resolveOpen;
			let rejectOpen;
			const openPromise = new Promise((resolve, reject) => {
				resolveOpen = resolve;
				rejectOpen = reject;
			});

			try
			{
				const widgetName = 'layout';
				/**
				 * @type PageManagerProps
				 */
				const widgetParams = {
					titleParams: this.getTitleParams(),
					useLargeTitleMode: true,
					backgroundColor: Theme.colors.bgSecondary,
				};

				if (parentWidget === PageManager)
				{
					widgetParams.backdrop = {
						mediumPositionPercent: 85,
						horizontalSwipeAllowed: false,
						onlyMediumPosition: true,
					};
				}

				parentWidget.openWidget(widgetName, widgetParams)
					.then((layoutWidget) => {
						layoutWidget.showComponent(new this(props, layoutWidget));

						resolveOpen(layoutWidget);
					})
					.catch((error) => {
						console.error(error);

						rejectOpen(error);
					});
			}
			catch (error)
			{
				console.error(error);

				rejectOpen(error);
			}

			return openPromise;
		}

		/**
		 * @abstract
		 * @return WidgetTitleParamsType
		 */
		static getTitleParams()
		{
			throw new Error('Step: getTitle() must be override in subclass.');
		}

		constructor(props, layoutWidget)
		{
			super(props);

			/**
			 * @type LayoutWidget
			 */
			this.layoutWidget = layoutWidget;
			this.bindMethods();
			this.layoutWidget.setRightButtons(this.getRightButtons());
			this.layoutWidget.setLeftButtons(this.getLeftButtons());
		}

		bindMethods()
		{
			this.goToNextStep = this.goToNextStep.bind(this);
			this.goToPrevStep = this.goToPrevStep.bind(this);
		}

		/**
		 * @return Array<PageManagerButton>
		 */
		getRightButtons()
		{
			return [
				{
					id: 'goToNextStep',
					name: Loc.getMessage('IMMOBILE_CHANNEL_CREATOR_GO_TO_NEXT_STEP'),
					callback: this.goToNextStep,
					color: Theme.colors.accentMainLink === '#FFFFFF'
						? Theme.colors.accentMainLinks
						: Theme.colors.accentMainLink
					,
				},
			];
		}

		getLeftButtons()
		{
			return [
				{
					id: 'back',
					type: 'back',
					isCloseButton: true,
					callback: this.goToPrevStep,
				},
			];
		}

		/**
		 * @abstract
		 * @return {{}}
		 */
		getStepData()
		{
			return {}; // TODO implement
		}

		goToNextStep(stepResult)
		{
			this.props.goToNextStep(this.getStepData());
		}

		goToPrevStep()
		{
			this.layoutWidget.back();
		}
	}

	module.exports = {
		Step,
	};
});
