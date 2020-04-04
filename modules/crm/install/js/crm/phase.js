BX.namespace("BX.Crm");

if(typeof BX.Crm.EntityPhaseLayout === "undefined")
{
	BX.Crm.EntityPhaseLayout = function()
	{
	};

	BX.Crm.EntityPhaseLayout.prototype =
	{
		getBackgroundColor: function(semantics)
		{
			return BX.prop.getString(BX.Crm.EntityPhaseLayout.colors, semantics, "");
		},
		calculateTextColor: function(baseColor)
		{
			var r, g, b;
			if ( baseColor > 7 )
			{
				var hexComponent = baseColor.split("(")[1].split(")")[0];
				hexComponent = hexComponent.split(",");
				r = parseInt(hexComponent[0]);
				g = parseInt(hexComponent[1]);
				b = parseInt(hexComponent[2]);
			}
			else
			{
				if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(baseColor))
				{
					var c = baseColor.substring(1).split('');
					if(c.length === 3)
					{
						c= [c[0], c[0], c[1], c[1], c[2], c[2]];
					}
					c = '0x'+c.join('');
					r = ( c >> 16 ) & 255;
					g = ( c >> 8 ) & 255;
					b =  c & 255;
				}
			}

			var y = 0.21 * r + 0.72 * g + 0.07 * b;
			return ( y < 145 ) ? "#fff" : "#333";
		}
	};

	BX.Crm.EntityPhaseLayout.current = null;
	BX.Crm.EntityPhaseLayout.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = new BX.Crm.EntityPhaseLayout();
		}
		return this.current;
	};


	if(typeof(BX.Crm.EntityPhaseLayout.colors) === "undefined")
	{
		BX.Crm.EntityPhaseLayout.colors = {};
	}
}