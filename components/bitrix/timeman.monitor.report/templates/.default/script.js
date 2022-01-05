;(function()
{
	BX.TimemanPwtReport = function(params)
	{

	};

	BX.TimemanPwtReport.prototype.onShowTotalClick = function(event)
	{
		var placeholder = BX.create('span', {
			props: {className: 'main-grid-panel-content-text'}
		});

		var button = event.currentTarget;

		this.showTotal(placeholder).then(function(count)
		{
			button.parentElement.appendChild(placeholder);
			placeholder.innerText = count;
			BX.cleanNode(button, true);
		});

		event.stopPropagation();
		event.preventDefault();
	};

	BX.TimemanPwtReport.prototype.showTotal = function()
	{
		return new Promise(function (resolve)
		{
			BX.ajax.runComponentAction('bitrix:timeman.monitor.report', 'getRowsCount', {
				mode: 'class'
			})
				.then(function(response)
				{
					var data = response.data;
					resolve(data.rowsCount);
				})
				.catch(function(response)
				{
					if(response.errors)
					{
						response.errors.forEach(function(error)
						{
							console.error(error.message);
						})
					}
				});
		});
	};

})();