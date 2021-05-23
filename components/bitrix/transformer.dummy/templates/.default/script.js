;(function(){

BX.namespace('BX.Transformer');

if(window.BX.Transformer.onTransformLinkClick)
{
	return;
}

BX.Transformer.onTransformLinkClick = function(e)
{
	var target = e.srcElement || e.target;

	BX.ajax({
		'method': 'GET',
		'dataType': 'json',
		'url': target.href,
		'onsuccess': function(data)
		{
			BX.Transformer.onTransformAjaxSuccess(data, target);
		}
	});
	BX.PreventDefault(e);
};

BX.Transformer.onTransformAjaxSuccess = function(data, target)
{
	data = data || {};
	var container = BX.findParent(BX(target), {'class': 'transformer-uf-file-container'});
	var title = BX.findChild(container, {'class': 'transformer-uf-file-loader-title'}, true, false);
	var desc = BX.findChild(container, {'class': 'transformer-uf-file-loader-desc'}, true, false);
	var inner = BX.findChild(container, {'class': 'transformer-uf-file-loader-inner'}, true, false);
	if(data['success'] == 'success')
	{
		BX.adjust(title, { 'text': BX.message('VIDEO_TRANSFORMATION_IN_PROCESS_TITLE') });
		if(desc)
		{
			BX.adjust(desc, { 'text': BX.message('VIDEO_TRANSFORMATION_IN_PROCESS_DESC') });
		}
		if(inner)
		{
			BX.adjust(inner, {html: BX.Transformer.getDummyLoaderHtml()});
		}
	}
	else
	{
		BX.adjust(title, { 'text': BX.message('VIDEO_TRANSFORMATION_ERROR_TITLE') });
		if(desc)
		{
			var message = BX.Transformer.getErrorMessageByCode(data['success']);
			if(message)
			{
				BX.adjust(desc, {text: message});
			}
		}
		if(inner)
		{
			BX.adjust(inner, {html: BX.Transformer.getDummyErrorHtml()});
		}
	}
	BX(target).remove();
};

BX.Transformer.getDummyLoaderHtml = function()
{
	return '<svg class="transformer-uf-file-circular hidden" viewBox="25 25 50 50"><circle class="transformer-uf-file-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/><circle class="transformer-uf-file-inner-path" cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"/></svg>';
};

BX.Transformer.getDummyErrorHtml = function()
{
	return '<div class="transformer-uf-file-loader-button transformer-uf-file-loader-button-sad"></div>';
};

BX.Transformer.getErrorMessageByCode = function(code)
{
	var message = null;
	switch(code)
	{
		case 'not allowed': message = BX.message('VIDEO_TRANSFORMATION_ERROR_TRANSFORM_NOT_ALLOWED'); break;
		case 'no module': message = BX.message('VIDEO_TRANSFORMATION_ERROR_TRANSFORM_NOT_INSTALLED'); break;
		case 'was transformed': message = BX.message('VIDEO_TRANSFORMATION_ERROR_TRANSFORM_TRANSFORMED'); break;
	}
	return message;
};

BX.Transformer.onPull = function (command, params)
{
	if(command === 'refreshPlayer' && params['id'])
	{
		var containers = BX.findChildrenByClassName(document, 'transformer-uf-file-container-' + params['id'], true);
		if(containers.length > 0)
		{
			for(var i = 0; i < containers.length; i++)
			{
				var container = containers[i];
				var refreshUrl = container.getAttribute('data-bx-refresh-url');
				if(refreshUrl)
				{
					BX.ajax({
						'method': 'GET',
						'dataType': 'json',
						'url': refreshUrl,
						'onsuccess': function(data)
						{
							if(data.html)
							{
								var containers = BX.findChildrenByClassName(document, 'transformer-uf-file-container-' + params['id'], true);
								for(var i = 0; i < containers.length; i++)
								{
									var container = containers[i];
									if(container.getAttribute('data-bx-refresh-url') == this.url)
									{
										var html = BX.processHTML(data.html);
										container.removeAttribute('data-bx-refresh-url');
										var node = BX.create('div', {html: html.HTML});
										node = node.firstChild;
										if(container.parentNode && BX.hasClass(container.parentNode, 'disk-player-container'))
										{
											container.parentNode.parentNode.replaceChild(node, container.parentNode);
										}
										else
										{
											container.parentNode.replaceChild(node, container);
										}
										if(html.SCRIPT)
										{
											BX.ajax.processScripts(html.SCRIPT);
										}
										break;
									}
								}
							}
						}
					});
				}
			}
		}
	}
};

BX.addCustomEvent("onPullEvent-transformer", BX.Transformer.onPull);

})(window);