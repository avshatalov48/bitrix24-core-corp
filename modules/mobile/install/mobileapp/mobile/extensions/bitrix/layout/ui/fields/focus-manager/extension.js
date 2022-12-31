/**
 * @module layout/ui/fields/focus-manager
 */
jn.define('layout/ui/fields/focus-manager', (require, exports, module) => {

	/** @type{?BaseField} */
	let focusedField = null;

	/**
	 * @class FocusManager
	 */
	class FocusManager
	{
		static hasFocusedField()
		{
			return focusedField !== null && focusedField.state.focus;
		}

		/**
		 * @param {?BaseField} field
		 */
		static setFocusedField(field)
		{
			// skip fields like BaseMultipleField, CombinedField, etc
			if (field && field.hasNestedFields())
			{
				return;
			}

			focusedField = field;
		}

		/**
		 * @param {?BaseField} nextFocusedField
		 */
		static blurFocusedFieldIfHas(nextFocusedField = null)
		{
			const needToShowKeyboard = nextFocusedField && nextFocusedField.hasKeyboard();
			if (!needToShowKeyboard)
			{
				Keyboard.dismiss();
			}

			if (focusedField)
			{
				let fieldsToBlur;

				// fields with the same parent fields do not blur them (blur only different ancestors)
				if (
					nextFocusedField
					&& this.getRootField(nextFocusedField) === this.getRootField(focusedField)
				)
				{
					const commonAncestor = this.findCommonAncestor(focusedField, nextFocusedField);
					fieldsToBlur = this.getFieldParents(focusedField, commonAncestor);
				}
				else
				{
					fieldsToBlur = this.getFieldParents(focusedField);
				}

				let promise = Promise.resolve();

				fieldsToBlur.forEach((field) => {
					promise = promise.then(() => field.removeFocus());
				});

				return promise;
			}

			return Promise.resolve();
		}

		/**
		 * @private
		 * @param {BaseField} field
		 * @return {BaseField}
		 */
		static getRootField(field)
		{
			const fieldParents = this.getFieldParents(field);

			return fieldParents[fieldParents.length - 1];
		}

		/**
		 * @private
		 * @param {BaseField} field
		 * @param {?BaseField} untilField
		 * @return {BaseField[]}
		 */
		static getFieldParents(field, untilField = null)
		{
			const parents = [field];

			while (field = field.getParent())
			{
				if (field === untilField)
				{
					break;
				}

				parents.push(field);
			}

			return parents;
		}

		/**
		 * @private
		 * @param {BaseField} field1
		 * @param {BaseField} field2
		 * @return {?BaseField}
		 */
		static findCommonAncestor(field1, field2)
		{
			const field1Parents = this.getFieldParents(field1);
			const field2Parents = this.getFieldParents(field2);

			return field1Parents.find((currentField1) => {
				return field2Parents.find((currentField2) => currentField1 === currentField2);
			});
		}
	}

	module.exports = { FocusManager };

});
