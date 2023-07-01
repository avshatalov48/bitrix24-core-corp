/**
 * @module layout/ui/entity-editor/model
 */
jn.define('layout/ui/entity-editor/model', (require, exports, module) => {

	const { clone } = require('utils/object');
	const { EventEmitter } = require('event-emitter');

	/**
	 * @class EntityModel
	 */
	class EntityModel
	{
		static create(id, uid, settings)
		{
			const self = new EntityModel();

			self.initialize(id, uid, settings);

			return self;
		}

		constructor()
		{
			this.id = '';
			this.settings = {};
			this.data = null;
		}

		initialize(id, uid, settings)
		{
			this.id = CommonUtils.isNotEmptyString(id) ? id : Random.getString();

			this.uid = CommonUtils.isNotEmptyString(uid) ? uid : Random.getString();
			/** @type {EventEmitter} */
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.settings = settings ? settings : {};
			this.isIdentifiableEntity = BX.prop.getBoolean(this.settings, 'IS_IDENTIFIABLE_ENTITY', true);
			this.data = clone(BX.prop.getObject(this.settings, 'data', {}));

			this.customEventEmitter.emit('UI.EntityEditor.Model::onReady', [this.getFields()]);
		}

		getUid()
		{
			return this.uid;
		}

		isIdentifiable()
		{
			return this.isIdentifiableEntity;
		}

		hasField(name)
		{
			return this.data.hasOwnProperty(name);
		}

		getField(name, defaultValue)
		{
			if (defaultValue === undefined)
			{
				defaultValue = null;
			}

			return BX.prop.get(this.data, name, defaultValue);
		}

		setField(name, newValue)
		{
			const hasChanged = this.data[name] !== newValue;

			this.data[name] = newValue;

			if (hasChanged)
			{
				this.customEventEmitter.emit('UI.EntityEditor.Model::onChange', [this.getFields(), name]);
			}
		}

		getFields()
		{
			return { ...this.data };
		}
	}

	module.exports = { EntityModel };
});