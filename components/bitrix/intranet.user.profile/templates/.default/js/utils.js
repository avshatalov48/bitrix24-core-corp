;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.Utils)
	{
		return;
	}

	namespace.Utils = {

		processAjaxBlock: function(params)
		{
			if (!BX.type.isNotEmptyObject(params))
			{
				return;
			}

			var
				data = params.data,
				containerNode = params.containerNode,
				className = (BX.type.isNotEmptyString(params.className) ? params.className : '');

			if (
				!BX.type.isNotEmptyObject(data)
				|| !BX(containerNode)
			)
			{
				return;
			}

			var htmlWasInserted = false;
			var scriptsLoaded = false;

			processExternalJS(processInlineJS);
			processCSS(insertHTML);

			function processCSS(callback)
			{
				if (
					BX.type.isArray(data.CSS)
					&& data.CSS.length > 0
				)
				{
					BX.load(data.CSS, callback);
				}
				else
				{
					callback();
				}
			}

			function insertHTML()
			{
				containerNode.appendChild(BX.create('DIV', {
					props: {
						className: className
					},
					html: data.CONTENT
				}));

				htmlWasInserted = true;
				if (scriptsLoaded)
				{
					processInlineJS();
				}
			}

			function processExternalJS(callback)
			{
				if (
					BX.type.isArray(data.JS)
					&& data.JS.length > 0
				)
				{
					BX.load(data.JS, callback); // to initialize
				}
				else
				{
					callback();
				}
			}

			function processInlineJS()
			{
				scriptsLoaded = true;
				if (htmlWasInserted)
				{
					BX.ajax.processRequestData(data.CONTENT, {
						scriptsRunFirst: false,
						dataType: 'HTML'
					});
				}
			}
		}

	};

})();