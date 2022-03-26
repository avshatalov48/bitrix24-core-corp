(() => {
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

	jnexport(EntityAsyncValidator);
})();