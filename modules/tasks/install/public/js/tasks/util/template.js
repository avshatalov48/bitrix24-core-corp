BX.namespace('Tasks.Util');

BX.Tasks.Util.Template = function(opts)
{
	if(typeof opts.html != 'string')
	{
		throw new TypeError('Template html should be a string value');
	}

	this.html = opts.html.trim();
}

BX.Tasks.Util.Template.compile = function(html)
{
	return new BX.Tasks.Util.Template({
		html: html
	});
}

BX.mergeEx(BX.Tasks.Util.Template.prototype, {

	get: function(data)
	{
		var html = this.html;
		var value = '';

		for(var k in data)
		{
			if(typeof data[k] != 'undefined' && data.hasOwnProperty(k))
			{
				value = typeof data[k] != 'undefined' && data[k] !== null ? data[k].toString() : '';
				html = this.replace(html, k.toString().trim(), value);
			}
		}

		return html;
	},

	replace: function(where, from, to)
	{
		if(!(from.match(new RegExp('^[a-z0-9_-]+$', 'i'))))
		{
			return;
		}

		return (
			where.
				replace(
					new RegExp('{{{('+from+'|'+from.toUpperCase()+')}}}', 'g'),
					function() {
						return to;
					}
				).
				replace(
					new RegExp('{{('+from+'|'+from.toUpperCase()+')}}', 'g'),
					function() {
						return BX.util.htmlspecialchars(to)
					}
				)
		);
	},

	getNode: function(data, onlyTags)
	{
		var html = this.get(data);

		// table makeup behaves not so well when being parsed by a browser, so a little hack is on route:

		var isTableRow = false;
		var isTableCell = false;

		if(html.search(/^<\s*(tr|th)[^<]*>/) >= 0)
		{
			isTableRow = true;
		}
		else if(html.search(/^<\s*td[^<]*>/) >= 0)
		{
			isTableCell = true;
		}

		var keeper = document.createElement('div');

		if(isTableRow || isTableCell)
		{
			if(isTableRow)
			{
				keeper.innerHTML = '<table><tbody>'+html+'</tbody></table>';
				keeper = keeper.childNodes[0].childNodes[0];
			}
			else
			{
				keeper.innerHTML = '<table><tbody><tr>'+html+'</tr></tbody></table>';
				keeper = keeper.childNodes[0].childNodes[0].childNodes[0];
			}
		}
		else
		{
			keeper.innerHTML = html;
		}

		if(onlyTags)
		{
			var children = keeper.childNodes;
			var result = [];

			// we need only non-text nodes
			for(var k = 0; k < children.length; k++)
			{
				if(BX.type.isElementNode(children[k]))
				{
					result.push(children[k]);
				}
			}

			return result;
		}
		else
		{
			return Array.prototype.slice.call(keeper.childNodes);
		}
	}
});