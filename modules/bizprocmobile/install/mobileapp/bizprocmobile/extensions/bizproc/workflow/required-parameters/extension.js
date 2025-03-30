/**
 * @module bizproc/workflow/required-parameters
 */
jn.define('bizproc/workflow/required-parameters', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { BottomSheet } = require('bottom-sheet');
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { Type } = require('type');
	const { PureComponent } = require('layout/pure-component');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');
	const { EntityManager } = require('layout/ui/entity-editor/manager');
	const { WorkflowRequiredParametersSkeleton } = require('bizproc/workflow/required-parameters/skeleton');

	class WorkflowRequiredParameters extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {?string} props.widgetTitle
		 * @param {string} props.signedDocument
		 * @param layout
		 *
		 * @returns {Promise}
		 */
		static async open(props = {}, layout = PageManager)
		{
			return new Promise((resolve, reject) => {
				new BottomSheet()
					.setParentWidget(layout)
					.setTitle(props.widgetTitle || Loc.getMessage('M_BP_WORKFLOW_REQUIRED_PARAMETERS_WIDGET_TITLE'))
					.setComponent((widget) => {
						return new WorkflowRequiredParameters({
							widget,
							onSaveCallback: resolve,
							onCancelCallback: reject,
							signedDocument: props.signedDocument,
						});
					})

					.disableSwipe()
					.disableContentSwipe()
					.disableHorizontalSwipe()
					.enableResizeContent()

					.disableOnlyMediumPosition()
					.setMediumPositionPercent(50)

					.showNavigationBar()
					.setNavigationBarColor(AppTheme.colors.bgSecondary)

					.setBackgroundColor(AppTheme.colors.bgContentPrimary)

					.open()
					.then((widget) => {
						widget.preventBottomSheetDismiss(true);
					})
					.catch(() => {})
				;
			});
		}

		/**
		 * @param props
		 * @param {LayoutWidget} props.widget
		 * @param {Function} props.onSaveCallback
		 * @param {Function} props.onCancelCallback
		 * @param {string} props.signedDocument
		 */
		constructor(props)
		{
			super(props);

			this.checkProps(props);

			this.state = {
				editorConfig: null,
			};

			this.isSaved = false;
			this.editorRef = null;
			this.startTime = null;

			this.headerButtons = new WidgetHeaderButton({
				widget: this.props.widget,
				text: Loc.getMessage('M_BP_WORKFLOW_REQUIRED_PARAMETERS_WIDGET_SAVE_BUTTON'),
				loadingText: Loc.getMessage('M_BP_WORKFLOW_REQUIRED_PARAMETERS_WIDGET_SAVING_BUTTON'),
				disabled: () => !this.isLoaded,
				onClick: this.onSaveClick.bind(this),
			});
		}

		/**
		 * @private
		 */
		checkProps(props)
		{
			if (!props.widget)
			{
				throw new Error('"widget" property must be specified');
			}

			if (!Type.isStringFilled(props.signedDocument))
			{
				throw new Error('"signedDocument" property must be a non-empty string');
			}

			if (!Type.isFunction(props.onSaveCallback))
			{
				throw new TypeError('"onSaveCallback" property must be a function');
			}

			if (!Type.isFunction(props.onCancelCallback))
			{
				throw new TypeError('"onCancelCallback" property must be a function');
			}
		}

		componentDidMount()
		{
			this.props.widget.on('onViewHidden', () => {
				if (this.isSaved === false)
				{
					this.props.onCancelCallback({ cancel: true });
				}
			});

			this.loadParameters();
		}

		loadParameters()
		{
			let editorConfig = {};

			BX.ajax.runAction(
				'bizprocmobile.RequiredParameters.load',
				{ data: { signedDocument: this.props.signedDocument } },
			)
				.then(({ data }) => {
					editorConfig = data.editorConfig;
					if (Array.isArray(editorConfig))
					{
						this.closeSuccess({});
					}
					else
					{
						this.startTime = this.getCurrentTime();
						this.setState({ editorConfig });
						this.headerButtons.refresh();
					}
				})
				.catch(({ errors }) => {
					this.showErrorsAndClose(errors);
				})
			;
		}

		onSaveClick()
		{
			if (this.editorRef?.validate())
			{
				return new Promise((resolve, reject) => {
					this.editorRef.getValuesToSave()
						.then((fields) => {
							resolve();
							this.closeSuccess(fields);
						})
						.catch((errors) => {
							NotifyManager.showErrors(errors);
							reject();
						})
					;
				});
			}

			return Promise.reject();
		}

		get isLoaded()
		{
			return this.state.editorConfig !== null;
		}

		showErrorsAndClose(errors)
		{
			console.error(errors);

			const firstError = Array.isArray(errors) ? errors[0] : {};
			if (firstError.message)
			{
				// eslint-disable-next-line no-undef
				Notify.alert(
					firstError.message,
					Loc.getMessage('M_BP_WORKFLOW_REQUIRED_PARAMETERS_WIDGET_ALERT_TITLE'),
					Loc.getMessage('M_BP_WORKFLOW_REQUIRED_PARAMETERS_WIDGET_ALERT_BUTTON_LABEL'),
					this.close.bind(this),
				);
			}
			else
			{
				this.close();
			}
		}

		closeSuccess(fields)
		{
			if (this.startTime)
			{
				// eslint-disable-next-line no-param-reassign
				fields.timeToStart = this.getCurrentTime() - this.startTime;
			}

			this.isSaved = true;
			this.props.onSaveCallback({ data: fields });
			this.close();
		}

		close()
		{
			if (this.props.widget)
			{
				this.props.widget.close();
			}
		}

		getCurrentTime()
		{
			return Math.round(Date.now() / 1000);
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						paddingHorizontal: 6,
					},
				},
				!this.isLoaded && new WorkflowRequiredParametersSkeleton({}),
				this.isLoaded && EntityManager.create({
					layout: this.props.widget,
					editorProps: this.state.editorConfig,
					showBottomPadding: true,
					refCallback: (ref) => {
						this.editorRef = ref;
					},
				}),
			);
		}
	}

	module.exports = { WorkflowRequiredParameters };
});
