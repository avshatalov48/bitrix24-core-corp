;(function()
{
	BX.namespace("BX.Voximplant.Report");

	BX.Voximplant.Report.CallDynamics = {
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

			var clickableElements = document.getElementsByClassName("telephony-report-call-dynamics-grid-value-clickable");
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

			this.updateWidgetTitle();
			BX.addCustomEvent(this.widget, "Dashboard.Board.Widget:onAfterRender",
				function()
				{
					this.updateWidgetTitle();
				}.bind(this)
			)
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
		updateWidgetTitle: function()
		{
			BX.clean(this.widget.layout.titleContainer);
			BX.adjust(this.widget.layout.titleContainer, {
				children: [
					BX.create("span", {
						text: this.widget.config.title
					}),
					this.elements.hint = BX.UI.Hint.createNode(BX.message("TELEPHONY_REPORT_CALL_DYNAMICS_HELP"))
				]
			});
		}
	};

	BX.Voximplant.Report.CallDynamics.bindEvents();
})();

