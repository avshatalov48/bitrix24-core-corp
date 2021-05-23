;(function()
{
	BX.namespace("BX.Voximplant.Report");

	BX.Voximplant.Report.PeriodCompareGrid = {
		gridId: null,
		boardId: null,
		widgetId: null,
		init: function(options)
		{
			this.gridId = options.gridId;
			this.boardId = options.boardId;
			this.widgetId = options.widgetId;

			this.board = BX.VisualConstructor.BoardRepository.getBoard(this.boardId);
			this.widget = this.board.dashboard.getWidget(this.widgetId);

			var clickableElements = document.getElementsByClassName("telephony-report-period-compare-grid-value-clickable");
			for(var i = 0; i < clickableElements.length; i++)
			{
				if(clickableElements[i].dataset.target)
				{
					clickableElements[i].addEventListener('click', this.onElementClick.bind(this))
				}
			}

			this.elements = {
				hint: null
			};
		},
		bindEvents: function()
		{
			BX.addCustomEvent("Grid::beforeRequest", this.onGridBeforeRequest.bind(this));
		},
		onGridBeforeRequest: function(ctx, requestParams)
		{
			if (this.gridId !== requestParams.gridId)
			{
				return;
			}

			requestParams.cancelRequest = true;

			setTimeout(this.reloadWidget.bind(this), 10);
		},
		reloadWidget: function()
		{
			this.widget.reload();
		},
		onElementClick: function(e)
		{
			if(BX.SidePanel)
			{
				BX.SidePanel.Instance.open(e.currentTarget.dataset.target, {
					cacheable: false,
					loader: "voximplant:grid-loader"
				});
			}
			else
			{
				window.open(e.currentTarget.dataset.target);
			}
		},
	};

	BX.Voximplant.Report.PeriodCompareGrid.bindEvents();
})();

