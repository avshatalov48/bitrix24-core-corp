/**
 * @module lists/element-creation-guide/detail-step/component
 */
jn.define('lists/element-creation-guide/detail-step/component', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { EventEmitter } = require('event-emitter');
	const { DetailStepView } = require('lists/element-creation-guide/detail-step/view');
	const { NotifyManager } = require('notify-manager');

	class DetailStepComponent extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {String} props.uid
		 * @param {Number} props.iBlockId
		 * @param {Number} props.elementId
		 * @param {Boolean} props.isLoaded
		 * @param {String} props.sign
		 * @param {Object} props.editorConfig
		 * @param {EntityEditor} props.editorRef
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				editorConfig: this.props.editorConfig || null,
				readOnlyTrustedValues: this.getReadOnlyTrustedFieldsFromEditorConfig(this.props.editorConfig || {}),
			};
			this.sign = '';
			this.isLoading = false;

			/** @type {DetailStepView | null} */
			this.view = null;
			this.viewCallbackRef = (ref) => {
				this.view = ref;
			};

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.subscribeOnEvents();
		}

		subscribeOnEvents()
		{
			this.customEventEmitter.on('DetailStepView:onFieldChangeState', () => {
				this.customEventEmitter.emit('DetailStepComponent:onFieldChangeState');
			});
		}

		get isLoaded()
		{
			return (this.state.editorConfig !== null);
		}

		validate()
		{
			return this.view.validate();
		}

		getData()
		{
			return new Promise((resolve, reject) => {
				this.view.getData()
					.then((fields) => resolve(Object.assign(this.state.readOnlyTrustedValues, fields)))
					.catch((errors) => reject(errors))
				;
			});
		}

		loadEditor()
		{
			if (this.isLoaded || this.isLoading === true)
			{
				return;
			}

			this.isLoading = true;
			let editorConfig = '';

			BX.ajax.runAction(
				'listsmobile.ElementCreationGuide.loadDetailStep',
				{ data: { iBlockId: this.props.iBlockId, elementId: this.props.elementId } },
			)
				.then((response) => {
					editorConfig = response.data.editor;
					this.sign = response.data.signedIBlockIdAndElementId;
				})
				.catch((response) => {
					console.error(response.errors);
					if (Array.isArray(response.errors))
					{
						NotifyManager.showErrors(response.errors);
					}
				})
				.finally(() => {
					this.isLoading = false;
					this.setState({
						editorConfig,
						readOnlyTrustedValues: this.getReadOnlyTrustedFieldsFromEditorConfig(editorConfig),
					});

					this.customEventEmitter.emit(
						'DetailStepComponent:OnAfterLoad',
						[this.sign, this.state.editorConfig],
					);
				})
			;
		}

		getReadOnlyTrustedFieldsFromEditorConfig(editorConfig)
		{
			const readOnlyTrustedValues = {};
			Object.entries(editorConfig.ENTITY_FIELDS ?? []).forEach(([key, property]) => {
				if (property.custom && property.custom.isTrusted)
				{
					readOnlyTrustedValues[key] = editorConfig.ENTITY_DATA[key];
				}
			});

			return readOnlyTrustedValues;
		}

		render()
		{
			this.loadEditor();

			return new DetailStepView({
				uid: this.uid,
				layout: this.props.layout,
				editorConfig: this.state.editorConfig,
				isLoaded: this.isLoaded,
				ref: this.viewCallbackRef,
			});
		}
	}

	module.exports = { DetailStepComponent };
});
