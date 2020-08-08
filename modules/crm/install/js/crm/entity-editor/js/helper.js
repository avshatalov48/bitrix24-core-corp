BX.namespace("BX.Crm");

//region HELPERS
if(typeof BX.Crm.EditorTextHelper === "undefined")
{
	BX.Crm.EditorTextHelper = function()
	{
	};
	BX.Crm.EditorTextHelper.prototype =
		{
			selectAll: function(input)
			{
				if(!(BX.type.isElementNode(input) && input.value.length > 0))
				{
					return;
				}

				if(BX.type.isFunction(input.setSelectionRange))
				{
					input.setSelectionRange(0, input.value.length);
				}
				else
				{
					input.select();
				}
			},
			setPositionAtEnd: function(input)
			{
				if(BX.type.isElementNode(input) && input.value.length > 0)
				{
					BX.setCaretPosition(input, input.value.length);
				}
			}
		};
	BX.Crm.EditorTextHelper._current = null;
	BX.Crm.EditorTextHelper.getCurrent = function ()
	{
		if(!this._current)
		{
			this._current = new BX.Crm.EditorTextHelper();
		}
		return this._current;
	}
}
//endregion
