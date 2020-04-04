(function(BX) {

var ie7 = false;
/*@cc_on
	 @if (@_jscript_version <= 5.7)
		ie7 = true;
	/*@end
@*/ 

if (ie7)
	var zIndex = 500;

BX.listNumber = function(params)
{
	if (!BX.type.isArray(params.startDiv))
		params.startDiv = [params.startDiv];

	var cnt = 1, all_cnt = 1, v = '';
	for (var d = 0; d<params.startDiv.length; d++)
	{
		params.startDiv[d] = BX(params.startDiv[d]);
		if (params.startDiv[d])
		{
			params.prefix = params.prefix || '';

			var items = BX.findChildren(params.startDiv[d], params.isItem);

			if (items && items.length > 0)
			{
				for (var i=0; i<items.length; i++)
				{
					if (ie7)
						items[i].style.zIndex = zIndex--;

					var data = (BX.proxy(params.getData, items[i]))();
					if (data.counter)
					{
						var bUpdate = false;
						if ((items[i].style.display || BX.style(items[i], 'display')) !== 'none')
						{
							bUpdate = true;
							v = (BX.proxy(params.getCounterValue, items[i]))(cnt, params.prefix);
							data.counter.innerHTML = v;
						}
						BX.onCustomEvent(items[i], 'onUpdateIndexes', [all_cnt, cnt, v]);
						if (data.children)
						{
							BX.listNumber({
								startDiv: data.children,
								isItem: params.isItem,
								getData: params.getData,
								getCounterValue: params.getCounterValue,
								prefix: v
							});
						}

						all_cnt++;
						if (bUpdate)
							cnt++;
					}
				}
			}
		}
	}

	if (ie7 && params.prefix.length <= 0)
		zIndex = 500;
}

})(BX);