/**
 * @module layout/ui/entity-editor/controller/base
 */
jn.define('layout/ui/entity-editor/controller/base', (require, exports, module) => {

	const { Type } = require('type');
	const { EventEmitter } = require('event-emitter');

	/**
	 * @class EntityEditorBaseController
	 */
	class EntityEditorBaseController extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.initialize(props.id, props.uid, props.settings);
		}

		componentWillReceiveProps(props)
		{
			this.initialize(props.id, props.uid, props.settings);

			if (this.editor && this.editor.settings.loadFromModel)
			{
				this.loadFromModel();
			}
		}

		componentDidMount()
		{
			this.bindEvents();
			this.loadFromModel();
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		bindEvents()
		{
		}

		unbindEvents()
		{
		}

		initialize(id, uid, settings)
		{
			this.id = Type.isStringFilled(id) ? id : Random.getString();

			this.uid = Type.isStringFilled(uid) ? uid : Random.getString();
			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.settings = settings ? settings : {};
			/** @type {EntityEditor} */
			this.editor = BX.prop.get(this.settings, 'editor', null);
			/** @type {EntityModel} */
			this.model = BX.prop.get(this.settings, 'model', null);
		}

		loadFromModel()
		{
		}

		getUid()
		{
			return this.uid;
		}

		getValuesToSave()
		{
			return {};
		}

		render()
		{
			return null;
		}
	}

	module.exports = { EntityEditorBaseController };
});
