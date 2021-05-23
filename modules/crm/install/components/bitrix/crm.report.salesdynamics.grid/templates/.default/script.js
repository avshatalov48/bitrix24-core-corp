;(function()
{
	BX.namespace("BX.Crm.Report");

	BX.Crm.Report.SalesDynamicGrid = {
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

			var clickableElements = document.getElementsByClassName("crm-report-salesdynamics-grid-value-clickable");
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
					cacheable: false
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
					this.elements.hint = BX.UI.Hint.createNode(BX.message("CRM_REPORT_SALESDYNAMICS_HELP"))
				]
			});

			BX.bind(this.elements.hint, "click", function()
			{
				if(top.BX.Helper)
				{
					top.BX.Helper.show("redirect=detail&code=10393548");
				}
			})
		}
	};

	BX.Crm.Report.SalesDynamicGrid.bindEvents();
})();

