/**
 * @module layout/ui/entity-editor/validator/async
 */
jn.define('layout/ui/entity-editor/validator/async', (require, exports, module) => {
	/**
	 * @class EntityAsyncValidator
	 */
	class EntityAsyncValidator
	{
		static create()
		{
			return new EntityAsyncValidator();
		}

		constructor()
		{
			this.isValid = true;
		}

		addResult(validationResult)
		{
			this.isValid = (this.isValid && validationResult);
		}

		validate()
		{
			return this.isValid;
		}
	}

	module.exports = { EntityAsyncValidator };
});