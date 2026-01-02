/**
 * @module user-profile/common-tab/src/field-change-manager
 */
jn.define('user-profile/common-tab/src/field-change-manager', (require, exports, module) => {
	/**
	 * @class FieldChangeManager
	 */
	class FieldChangeManager
	{
		constructor()
		{
			this.fields = {};
			this.valid = {};
		}

		setField(key, value, isValid = true)
		{
			this.fields[key] = value;
			this.valid[key] = isValid;
		}

		getFields()
		{
			return { ...this.fields };
		}

		hasChanges()
		{
			return Object.keys(this.fields).length > 0;
		}

		clear()
		{
			this.fields = {};
			this.valid = {};
		}

		canSave()
		{
			const keys = Object.keys(this.fields);
			if (keys.length === 0)
			{
				return false;
			}

			return keys.every((key) => this.valid[key]);
		}

		saveChanges(ownerId)
		{
			return BX.ajax.runAction('mobile.Profile.save', {
				json: {
					ownerId,
					fieldsToSave: this.getFields(),
				},
			});
		}
	}

	module.exports = { FieldChangeManager };
});
