BX.namespace('Tasks.Util');

BX.Tasks.Util.Template = function(opts)
{
	if(typeof opts.html != 'string')
	{
		throw new TypeError('Template html should be a string value');
	}

	this.html = opts.html.replace(/^\s\s*/, '').replace(/\s\s*$/, ''); // trim  
}

BX.Tasks.Util.Template.compile = function(html)
{
	return new BX.Tasks.Util.Template({
		html: html
	});
}

BX.merge(BX.Tasks.Util.Template.prototype, {

	get: function(data)
	{
		var html = this.html;

		for(var k in data)
		{
			if(typeof data[k] != 'undefined' && data.hasOwnProperty(k))
			{
				var replaceWith = '';
				if(k.toString().indexOf('=') == 0)
				{ // leading '=' stands for an unsafe replace - no escaping
					replaceWith = data[k].toString();
					k = k.toString().substr(1);
				}
				else
				{
					replaceWith = BX.util.htmlspecialchars(data[k]).toString();
				}

				k = k.toString();

				html = this.replace(html, k, replaceWith);
				html = this.replace(html, k.toLowerCase(), replaceWith);
			}
		}

		return html;
	},

	replace: function(where, what, to)
	{
		var placeHolder = '{{'+what+'}}';

		if(to.search(placeHolder) >= 0) // you must be joking
		{
			to = '';
		}
		while(where.search(placeHolder) >= 0) // new RegExp('', 'g') on user-controlled data seems not so harmless
		{
			where = where.replace(placeHolder, to);
		}

		return where;
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
	},
});