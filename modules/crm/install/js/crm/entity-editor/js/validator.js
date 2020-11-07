BX.namespace("BX.Crm");

//region VALIDATION
if(typeof BX.Crm.EntityValidator === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityValidator = BX.UI.EntityValidator;
}

if(typeof BX.Crm.EntityPersonValidator === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityPersonValidator = BX.UI.EntityPersonValidator;
}

if(typeof BX.Crm.EntityValidationError === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityValidationError = BX.UI.EntityValidationError;
}

if(typeof BX.Crm.EntityValidationResult === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityValidationResult = BX.UI.EntityValidationResult;
}
if(typeof BX.Crm.EntityAsyncValidator === "undefined")
{
	/**
	 * @deprecated
	 */
	BX.Crm.EntityAsyncValidator = BX.UI.EntityAsyncValidator;
}
//endregion
