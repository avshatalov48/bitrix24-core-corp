/**
 * @module bizproc/workflow/starter
 */
jn.define('bizproc/workflow/starter', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { EventEmitter } = require('event-emitter');
	const { Feature } = require('feature');
	const { Haptics } = require('haptics');
	const { Alert, ButtonType } = require('alert');
	const { PureComponent } = require('layout/pure-component');
	const { Wizard } = require('layout/ui/wizard');
	const { CatalogStep } = require('bizproc/workflow/starter/catalog-step');
	const { DescriptionStep } = require('bizproc/workflow/starter/description-step');
	const { ParametersStep } = require('bizproc/workflow/starter/parameters-step');

	class WorkflowStarter extends PureComponent
	{
		static open(props = {}, layout = PageManager)
		{
			layout.openWidget(
				'layout',
				{
					modal: true,
					titleParams: { text: Loc.getMessage('M_BP_WORKFLOW_STARTER_TITLE') },
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
						swipeContentAllowed: false,
					},
					onReady: (readyLayout) => {
						readyLayout.showComponent(new WorkflowStarter({ ...props, layout: readyLayout }));
					},
				},
				layout,
			);
		}

		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.signedDocument = props.signedDocument || null;
			this.documentType = props.documentType || null;

			this.wizard = null;
			this.isClosing = false;
			this.isChanged = false;

			this.selectedTemplateId = null;

			this.steps = [];
			this.initSteps();

			this.handleSelectTemplate = this.handleSelectTemplate.bind(this);
			this.handleEditorChangeFields = this.handleEditorChangeFields.bind(this);
			this.handleExit = this.handleExit.bind(this);
		}

		get layout()
		{
			return this.props.layout || layout || {};
		}

		componentDidMount()
		{
			if (Feature.isPreventBottomSheetDismissSupported())
			{
				this.layout.preventBottomSheetDismiss(true);
				this.layout.on('preventDismiss', this.handleExit);
			}

			this.customEventEmitter
				.on('CatalogStep:OnSelectTemplate', this.handleSelectTemplate)
				.on('ParametersStep:OnFieldChangeState', this.handleEditorChangeFields)
			;
		}

		componentWillUnmount()
		{
			if (Feature.isPreventBottomSheetDismissSupported())
			{
				this.layout.preventBottomSheetDismiss(false);
				this.layout.off('preventDismiss', this.handleExit);
			}

			this.customEventEmitter
				.off('CatalogStep:OnSelectTemplate', this.handleSelectTemplate)
				.off('ParametersStep:OnFieldChangeState', this.handleEditorChangeFields)
			;
		}

		handleSelectTemplate(template)
		{
			this.selectedTemplateId = template.key;

			/** @type DescriptionStep */
			const descriptionStep = this.steps[1];
			if (descriptionStep)
			{
				descriptionStep.updateProps({
					templateId: this.selectedTemplateId,
					name: template.name || '',
					description: template.description || '',
					hasParameters: template.hasParameters,
					isConstantsTuned: template.isConstantsTuned,
					formattedTime: template.formattedTime,
				});
			}

			/** @type ParametersStep */
			const parametersStep = this.steps[2];
			if (parametersStep)
			{
				parametersStep.updateProps({
					templateId: this.selectedTemplateId,
				});
			}
		}

		handleEditorChangeFields()
		{
			this.isChanged = true;
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

					return this.showConfirmExitAlert();
				});
			}

			return promise
				.then(() => this.props.layout.close())
				.finally(() => {
					this.isClosing = false;
				})
			;
		}

		showConfirmExitAlert()
		{
			if (this.wizard.getCurrentStepId() === 1)
			{
				return new Promise((resolve, reject) => {
					Haptics.impactLight();

					Alert.confirm(
						Loc.getMessage('M_BP_WORKFLOW_STARTER_CONFIRM_TITLE'),
						'',
						[
							{
								text: Loc.getMessage('M_BP_WORKFLOW_STARTER_CONFIRM_BUTTON_EXIT_WITHOUT_SAVE'),
								type: ButtonType.DESTRUCTIVE,
								onPress: () => resolve(),
							},
							{
								text: Loc.getMessage('M_BP_WORKFLOW_STARTER_CONFIRM_BUTTON_CONTINUE'),
								type: ButtonType.CANCEL,
								onPress: () => {
									this.wizard.moveToNextStep();
									reject();
								},
							},
						],
					);
				});
			}

			return new Promise((resolve, reject) => {
				Haptics.impactLight();

				Alert.confirm(
					Loc.getMessage('M_BP_WORKFLOW_STARTER_CONFIRM_TITLE'),
					'',
					[
						{
							text: Loc.getMessage('M_BP_WORKFLOW_STARTER_CONFIRM_BUTTON_SAVE_AND_EXIT'),
							type: ButtonType.DESTRUCTIVE,
							onPress: () => {
								this.wizard.getCurrentStep().onMoveToNextStep()
									.then((response) => {
										return response.next ? resolve() : reject();
									})
									.catch(() => reject())
								;
							},
						},
						{
							text: Loc.getMessage('M_BP_WORKFLOW_STARTER_CONFIRM_BUTTON_EXIT_WITHOUT_SAVE'),
							type: ButtonType.DESTRUCTIVE,
							onPress: () => resolve(),
						},
						{
							text: Loc.getMessage('M_BP_WORKFLOW_STARTER_CONFIRM_BUTTON_CONTINUE'),
							type: ButtonType.CANCEL,
							onPress: () => reject(),
						},
					],
				);
			});
		}

		initSteps()
		{
			const totalSteps = 3;

			const props = {
				uid: this.uid,
				layout: this.layout,
				title: Loc.getMessage('M_BP_WORKFLOW_STARTER_TITLE'),
				totalSteps,
				signedDocument: this.signedDocument,
			};

			const catalogStep = new CatalogStep({ ...props, stepNumber: 1, documentType: this.documentType });
			const descriptionStep = new DescriptionStep({ ...props, stepNumber: 2 });
			const parametersStep = new ParametersStep({ ...props, stepNumber: 3 });

			this.steps = [catalogStep, descriptionStep, parametersStep];
		}

		render()
		{
			return View(
				{ style: { backgroundColor: AppTheme.colors.bgSecondary } },
				new Wizard({
					parentLayout: this.layout,
					steps: Array.from({ length: this.steps.length }).map((value, index) => index),
					stepForId: this.getStepForId.bind(this),
					useProgressBar: true,
					isNavigationBarBorderEnabled: true,
					showNextStepButtonAtBottom: true,
					ref: (ref) => {
						this.wizard = ref;
					},
				}),
			);
		}

		getStepForId(stepId)
		{
			return this.steps[stepId] || null;
		}
	}

	module.exports = { WorkflowStarter };
});
