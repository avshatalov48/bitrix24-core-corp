/**
 * @module bizproc/workflow/starter/parameters-step/component
 */
jn.define('bizproc/workflow/starter/parameters-step/component', (require, exports, module) => {
	const { EventEmitter } = require('event-emitter');
	const { NotifyManager } = require('notify-manager');
	const { PureComponent } = require('layout/pure-component');
	const { ParametersStepView } = require('bizproc/workflow/starter/parameters-step/view');
	class ParametersStepComponent extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.state = { editorConfig: this.props.editorConfig || null };

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.signedDocument = props.signedDocument;

			this.isLoading = false;

			this.view = null;
			this.viewCallbackRef = (ref) => {
				this.view = ref;
			};

			this.handleEditorFieldChangeState = this.handleEditorFieldChangeState.bind(this);
		}

		handleEditorFieldChangeState()
		{
			this.customEventEmitter.emit('ParametersStepComponent:OnFieldChangeState');
		}

		componentDidMount()
		{
			this.customEventEmitter.on('ParametersStepView:OnFieldChangeState', this.handleEditorFieldChangeState);

			this.loadEditor();
		}

		componentWillUnmount()
		{
			this.customEventEmitter.off('ParametersStepView:OnFieldChangeState', this.handleEditorFieldChangeState);
		}

		get isLoaded()
		{
			return this.state.editorConfig !== null;
		}

		validate()
		{
			return this.view.validate();
		}

		getData()
		{
			return new Promise((resolve, reject) => {
				this.view.getData()
					.then((parameters) => resolve(parameters))
					.catch((errors) => reject(errors))
				;
			});
		}

		loadEditor()
		{
			if (this.isLoaded || this.isLoading)
			{
				return;
			}

			this.isLoading = true;
			let editorConfig = {};
			let hasErrors = false;

			BX.ajax.runAction(
				'bizprocmobile.Workflow.loadParametersEditor',
				{ data: { templateId: this.props.templateId, signedDocument: this.signedDocument } },
			)
				.then((response) => {
					editorConfig = (response.data && response.data.editorConfig) || {};
				})
				.catch((response) => {
					console.error(response.errors);
					if (Array.isArray(response.errors))
					{
						NotifyManager.showErrors(response.errors);
					}
					hasErrors = true;
				})
				.finally(() => {
					this.isLoading = false;
					this.setState({ editorConfig });

					this.customEventEmitter.emit(
						'ParametersStepComponent:OnAfterLoadEditor',
						[{ editorConfig: this.state.editorConfig, hasErrors }],
					);
				})
			;
		}

		render()
		{
			return new ParametersStepView({
				uid: this.uid,
				layout: this.props.layout,
				editorConfig: this.state.editorConfig,
				isLoaded: this.isLoaded,
				ref: this.viewCallbackRef,
			});
		}
	}

	module.exports = { ParametersStepComponent };
});
