/**
 * @module lists/element-creation-guide
 */
jn.define('lists/element-creation-guide', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { EventEmitter } = require('event-emitter');
	const { Wizard } = require('layout/ui/wizard');
	const { PureComponent } = require('layout/pure-component');

	const { AlertWindow } = require('lists/element-creation-guide/alert');
	const { CatalogStep } = require('lists/element-creation-guide/catalog-step');
	const { DescriptionStep } = require('lists/element-creation-guide/description-step');
	const { DetailStep } = require('lists/element-creation-guide/detail-step');

	class ElementCreationGuide extends PureComponent
	{
		static open(props, widgetParams = {}, layout = PageManager)
		{
			const defaultParams = {
				modal: true,
				titleParams: {
					text: Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_TITLE'),
				},
				backgroundColor: AppTheme.colors.bgContentPrimary,
				backdrop: {
					forceDismissOnSwipeDown: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
					mediumPositionPercent: 90,
					navigationBarColor: AppTheme.colors.bgSecondary,
					onlyMediumPosition: true,
					shouldResizeContent: true,
					swipeAllowed: true,
					swipeContentAllowed: true,
				},
				onReady: (readyLayout) => {
					readyLayout.showComponent(new ElementCreationGuide({ ...props, layout: readyLayout }));
				},
			};

			layout.openWidget(
				'layout',
				{ ...defaultParams, ...widgetParams },
				layout,
			);
		}

		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			/** @type { Wizard | null } */
			this.wizard = null;
			this.isClosing = false;
			this.isChanged = false;

			this.selectedIBlockId = null;

			this.steps = [];
			this.initSteps();
		}

		getCurrentTime()
		{
			return Math.round(Date.now() / 1000);
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.layout.preventBottomSheetDismiss(true);
			this.layout.on('preventDismiss', () => this.handleExit());
		}

		handleExit()
		{
			if (this.isClosing)
			{
				return Promise.resolve();
			}

			let promise = Promise.resolve();
			if (this.isChanged && this.wizard.getCurrentStepId() !== 0)
			{
				promise = promise.then(() => {
					this.isClosing = true;

					return this.showConfirmExitEntity();
				});
			}

			return promise
				.then(() => this.props.layout.close())
				.finally(() => {
					this.isClosing = false;
				})
			;
		}

		showConfirmExitEntity()
		{
			if (this.wizard.getCurrentStepId() === 1)
			{
				return new Promise((resolve, reject) => {
					(new AlertWindow([
						AlertWindow.getExitWithoutSaveButton(resolve),
						AlertWindow.getContinueButton(() => {
							this.wizard.moveToNextStep();
							reject();
						}),
					]))
						.show()
					;
				});
			}

			return new Promise((resolve, reject) => {
				(new AlertWindow([
					AlertWindow.getSaveAndExitButton(() => {
						this.wizard.getCurrentStep().onMoveToNextStep()
							.then((response) => {
								return response.next ? resolve() : reject();
							})
							.catch(() => reject())
						;
					}),
					AlertWindow.getExitWithoutSaveButton(resolve),
					AlertWindow.getContinueButton(reject),
				]))
					.show()
				;
			});
		}

		initSteps()
		{
			const totalSteps = 3;
			const wizardTitle = Loc.getMessage('LISTSMOBILE_EXT_ELEMENT_CREATION_GUIDE_TITLE');

			const props = {
				uid: this.uid,
				layout: this.layout,
				title: wizardTitle,
				totalSteps,
			};

			const catalogStep = new CatalogStep({ ...props, stepNumber: 1 });
			const descriptionStep = new DescriptionStep({ ...props, stepNumber: 2 });
			const detailStep = new DetailStep({ ...props, stepNumber: 3 });

			this.steps = [catalogStep, descriptionStep, detailStep];

			this.customEventEmitter
				.on('CatalogStep:OnSelectItem', (item) => {
					this.selectedIBlockId = item.key;
					descriptionStep.updateProps({
						iBlockId: this.selectedIBlockId,
						name: item.title || null,
						formattedTime: item.formattedTime || null,
					});
					detailStep.updateProps({
						iBlockId: this.selectedIBlockId,
						elementId: 0,
					});
				})
				.on('DescriptionStep:onAfterLoadDescription', (startTime) => {
					this.isChanged = false;
					detailStep.updateProps({ startTime });
				})
				.on('DetailStep:onFieldChangeState', () => {
					this.isChanged = true;
				})
			;
		}

		get layout()
		{
			return this.props.layout || layout || {};
		}

		render()
		{
			return View(
				{ style: { backgroundColor: AppTheme.colors.bgSecondary } },
				this.renderWizard(),
			);
		}

		renderWizard()
		{
			return new Wizard({
				parentLayout: this.layout,
				steps: Array.from({ length: this.steps.length }).map((value, index) => index),
				stepForId: this.getStepForId.bind(this),
				useProgressBar: true,
				isNavigationBarBorderEnabled: true,
				showNextStepButtonAtBottom: true,
				ref: (ref) => {
					this.wizard = ref;
				},
			});
		}

		getStepForId(stepId)
		{
			return this.steps[stepId] || null;
		}
	}

	module.exports = { ElementCreationGuide };
});
