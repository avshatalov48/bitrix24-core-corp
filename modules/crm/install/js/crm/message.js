if(typeof(BX.CrmMessageHelper) === "undefined")
{
	BX.CrmMessageHelper = function()
	{
	};
	BX.CrmMessageHelper.prototype =
	{
		getNumberDeclension: function (number, nominative, genitiveSingular, genitivePlural)
		{
			if(!BX.type.isNumber(number))
			{
				number = parseInt(number);
				if(isNaN(number))
				{
					return "";
				}
			}

			if(number === 0)
			{
				return genitivePlural;
			}

			if(number < 0)
			{
				number = -number;
			}

			var lastDigit = number % 10;
			var penultimateDigit = ((number % 100) - lastDigit) / 10;

			if (lastDigit === 1 && penultimateDigit !== 1)
			{
				return nominative;
			}

			return (penultimateDigit !== 1 && lastDigit >= 2 && lastDigit <= 4
				? genitiveSingular : genitivePlural);
		},
		prepareEntityNumberDeclension: function(number, entityDeclensions)
		{
			return (
				this.getNumberDeclension(
					number,
					BX.prop.getString(entityDeclensions, "nominative", ""),
					BX.prop.getString(entityDeclensions, "genitiveSingular", ""),
					BX.prop.getString(entityDeclensions, "genitivePlural", "")
				)
			);
		}
	};

	BX.CrmMessageHelper.current = null;
	BX.CrmMessageHelper.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = new BX.CrmMessageHelper();
		}
		return this.current;
	}
}