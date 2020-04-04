;(function ()
{
	BX.namespace('BX.Crm.Numerator.List');
	BX.Crm.Numerator.List = function (options)
	{
		this.numeratorCreateBtn = document.querySelector('[data-role="numerator-create-btn"]');
		this.addEventHandlers();
	};
	BX.Crm.Numerator.List.prototype = {
		addEventHandlers: function ()
		{
			BX.bind(this.numeratorCreateBtn, 'click', BX.delegate(this.onNumeratorCreateBtnClick, this));

			this.numTitles = document.querySelectorAll('[data-role^="link-title-for-"]');
			for (var i = 0; i < this.numTitles.length; i++)
			{
				BX.bind(this.numTitles[i], 'click', BX.delegate(this.onNumeratorTitleClick, this));
			}
		},
		onNumeratorTitleClick: function (event)
		{
			event.stopPropagation();
			event.preventDefault();
			var id = event.currentTarget.dataset.id;
			if (id)
			{
				var urlNumEdit = BX.util.add_url_param("/bitrix/components/bitrix/main.numerator.edit/slider.php", {NUMERATOR_TYPE: 'DOCUMENT', ID: id});
				BX.SidePanel.Instance.open(urlNumEdit, {width: 480});
			}
		},
		onNumeratorCreateBtnClick: function ()
		{
			var urlNumEdit = BX.util.add_url_param("/bitrix/components/bitrix/main.numerator.edit/slider.php", {NUMERATOR_TYPE: 'DOCUMENT'});
			BX.SidePanel.Instance.open(urlNumEdit, {width: 480, cacheable: false});
		},
		onEditNumeratorClick: function (id, type)
		{
			BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(function (event)
			{
				if (event.getEventId() === 'numerator-saved-event')
				{
					var numeratorData = event.getData();
					var numTitle = document.querySelector('[data-role="link-title-for-' + numeratorData.id + '"]');
					if (numTitle)
					{
						numTitle.innerText = numeratorData.name;
					}
					var template = document.querySelector('tr[data-id="' + numeratorData.id + '"] td:nth-child(5) >' +
						' span');
					if (template && numeratorData.template)
					{
						template.innerText = numeratorData.template;
					}
				}
			}));
			var urlNumEdit = BX.util.add_url_param("/bitrix/components/bitrix/main.numerator.edit/slider.php", {NUMERATOR_TYPE: type});

			if (id)
			{
				urlNumEdit = BX.util.add_url_param(urlNumEdit, {ID: id});
			}
			BX.SidePanel.Instance.open(urlNumEdit, {width: 480});
		}
	};
})();