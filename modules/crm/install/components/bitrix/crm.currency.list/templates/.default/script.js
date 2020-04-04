BX.CurrencyClassifierSlider = (function ()
{
	var CurrencyClassifierSlider = function(table)
	{
		CurrencyClassifierSlider.initSlider(table);
	};

	CurrencyClassifierSlider.initSlider = function(table)
	{
		CurrencyClassifierSlider.slider.init(table);
	};

	CurrencyClassifierSlider.slider =
	{
		init: function(table)
		{
			if (!BX.SidePanel.Instance)
			{
				return;
			}

			BX.SidePanel.Instance.bindAnchors({
				rules: [
					{
						condition: ['/crm/configs/currency/add/'],
						loader: 'default-loader',
						options: {
							cacheable: false,
							events: {
								onClose: function() {
									table.Reload();
								}
							}
						}
					},
					{
						condition: [new RegExp("/crm/configs/currency/edit/[A-Z]{3}/")],
						loader: 'default-loader',
						options: {
							cacheable: false,
							events: {
								onClose: function() {
									table.Reload();
								}
							}
						}
					}
				]
			});
		}
	};

	return CurrencyClassifierSlider;
})();