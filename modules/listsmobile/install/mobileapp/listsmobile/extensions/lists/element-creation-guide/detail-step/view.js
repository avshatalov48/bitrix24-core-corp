/**
 * @module lists/element-creation-guide/detail-step/view
 */
jn.define('lists/element-creation-guide/detail-step/view', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EventEmitter } = require('event-emitter');
	const { PureComponent } = require('layout/pure-component');
	const { EntityManager } = require('layout/ui/entity-editor/manager');
	const { DetailStepSkeleton } = require('lists/element-creation-guide/detail-step/skeleton');

	class DetailStepView extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {String} props.uid
		 * @param {Object} props.editorConfig
		 * @param {Boolean} props.isLoaded
		 */
		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.editorRef = null;
			this.editorRefCallback = (ref) => {
				this.editorRef = ref;
			};

			this.subscribeOnEvents();
		}

		subscribeOnEvents()
		{
			this.customEventEmitter.on('UI.EntityEditor.Field::onChangeState', () => {
				this.customEventEmitter.emit('DetailStepView:onFieldChangeState');
			});
		}

		validate()
		{
			return this.editorRef.validate() ? Promise.resolve() : Promise.reject();
		}

		getData()
		{
			return new Promise((resolve, reject) => {
				if (this.editorRef)
				{
					this.editorRef.getValuesToSave()
						.then((fields) => resolve(fields))
						.catch((errors) => reject(errors))
					;
				}
				else
				{
					resolve({});
				}
			});
		}

		render()
		{
			return View(
				{
					style: {
						flex: 1,
						backgroundColor: this.props.isLoaded ? 'inherit' : AppTheme.colors.bgContentPrimary,
						borderTopRightRadius: 12,
						borderTopLeftRadius: 12,
					},
					resizableByKeyboard: true,
				},
				!this.props.isLoaded && this.renderSkeleton(),
				this.props.isLoaded && this.renderEditor(),
			);
		}

		renderSkeleton()
		{
			return new DetailStepSkeleton({});
		}

		renderEditor()
		{
			return EntityManager.create({
				uid: this.uid,
				layout: this.props.layout,
				editorProps: this.props.editorConfig,
				showBottomPadding: true,
				refCallback: this.editorRefCallback,
			});
		}
	}

	module.exports = { DetailStepView };
});
