/**
 * @module bizproc/task/fields
 */
jn.define('bizproc/task/fields', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { EntityManager } = require('layout/ui/entity-editor/manager');
	const { EventEmitter } = require('event-emitter');
	const { isFunction } = require('utils/object');

	class TaskFields extends PureComponent
	{
		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.editor = null;

			this.handleChangeFields = this.handleChangeFields.bind(this);
			this.handleScrollToInvalidField = this.handleScrollToInvalidField.bind(this);
			this.handleScrollToFocusedField = this.handleScrollToFocusedField.bind(this);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('UI.EntityEditor.Field::onChangeState', this.handleChangeFields);
			this.customEventEmitter.on('UI.EntityEditor::onScrollToInvalidField', this.handleScrollToInvalidField);
			this.customEventEmitter.on('UI.EntityEditor::onScrollToFocusedField', this.handleScrollToFocusedField);
		}

		componentWillUnmount()
		{
			this.customEventEmitter.off('UI.EntityEditor.Field::onChangeState', this.handleChangeFields);
			this.customEventEmitter.off('UI.EntityEditor::onScrollToInvalidField', this.handleScrollToInvalidField);
			this.customEventEmitter.off('UI.EntityEditor::onScrollToFocusedField', this.handleScrollToFocusedField);
		}

		async getData()
		{
			return new Promise((resolve, reject) => {
				this.editor.getValuesToSave()
					.then((fields) => resolve(fields))
					.catch((errors) => reject(errors))
				;
			});
		}

		isValid()
		{
			return this.editor.validate();
		}

		render()
		{
			return EntityManager.create({
				uid: this.uid,
				layout: this.props.layout,
				editorProps: this.props.editor,
				isEmbedded: true,
				refCallback: (ref) => {
					this.editor = ref;
				},
			});
		}

		handleChangeFields()
		{
			this.customEventEmitter.emit('TaskFields:onChangeFieldValue');
		}

		handleScrollToInvalidField(fieldView)
		{
			if (isFunction(this.props.onScrollToInvalidField))
			{
				this.props.onScrollToInvalidField(fieldView);
			}
		}

		handleScrollToFocusedField(fieldView)
		{
			if (isFunction(this.props.onScrollToFocusedField))
			{
				this.props.onScrollToFocusedField(fieldView);
			}
		}
	}

	module.exports = { TaskFields };
});
