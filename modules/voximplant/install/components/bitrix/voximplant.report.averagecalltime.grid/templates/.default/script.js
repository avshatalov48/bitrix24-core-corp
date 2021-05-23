;(function()
{
	BX.namespace("BX.Voximplant.Report");

	BX.Voximplant.Report.AverageCallTimeGrid = {
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
	};

	BX.Voximplant.Report.AverageCallTimeGrid.bindEvents();
})();

