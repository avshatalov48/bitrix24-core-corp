/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2014 Bitrix
 */

function tasks_TableColumnResize(table, minWidth, params)
{
	var i                = 0;
	var resizableDiv     = null;
	var item             = null;
	var self             = this;
	var allTableColumns  = null;
	var resizableColumns = null;
	var resizableDivs    = null;
	var resizableColumnsCnt = null;
	var tableWidth       = 0;
	var curVirtualWidth  = null;	// cur "virtual" width of column (could be less then zero or less then minimalWidth)
	var curRealWidth     = null;	// cur real width of column
	var minimalWidth     = minWidth || 65;	

	var eventMouseX;                // prev. mouse position
	var curColumn;                  // currently resized column
	var curColumnIndex;             // index of currently reszied column
	var originalOnMouseUpHandler;
	var originalOnMouseMoveHandler;
	var originalOnWindowResizeHandler;

	// input params
	var callbackOnStopResize  = null;
	var lastColumnAsElastic   = false;
	var elasticColumnMinWidth = 50;

	// additional vars (its usage depends on input params)
	var elasticColumn       = null;
	var columnsWidthExceptElastic = null;
	var tableContainerWidth = null;	// width of container, where table is located

	if (params)
	{
		if (params.callbackOnStopResize)
			callbackOnStopResize = params.callbackOnStopResize;

		if (params.lastColumnAsElastic)
			lastColumnAsElastic = true;

		if (params.elasticColumnMinWidth)
			elasticColumnMinWidth = parseInt(params.elasticColumnMinWidth);
	}

	if (table.tagName != 'TABLE')
		return;

	this.id = table.id;
	this.table = table;

	if ( ! table.rows[0] )
		return;

	allTableColumns = table.rows[0].cells;

	if ( ! allTableColumns )
		return;


	this.changeColumnWidth = function(deltaWidth)
	{
		var realDeltaWidth = null;
		var widthToBeSet = null;
		var elasticColumnWidth = null;
		var newElasticColumnWidth = null;

		curVirtualWidth = curVirtualWidth + deltaWidth;

		// ignore 0px - 2px moving
		if (
			((curVirtualWidth - curRealWidth) <= 2)
			&& ((curVirtualWidth - curRealWidth) >= -2)
		)
		{
			return;
		}

		if (curVirtualWidth > minimalWidth)
		{
			widthToBeSet = curVirtualWidth;
		}
		else if (
			(curVirtualWidth <= minimalWidth)
			&& (curRealWidth > minimalWidth)
		)
		{
			widthToBeSet = minimalWidth;
		}

		if (widthToBeSet === null)
			return;

		realDeltaWidth = widthToBeSet - curRealWidth;

		elasticColumnWidth    = parseInt(elasticColumn.offsetWidth);
		newElasticColumnWidth = elasticColumnWidth - realDeltaWidth;

		if (newElasticColumnWidth < elasticColumnMinWidth)
			newElasticColumnWidth = elasticColumnMinWidth;

		tableWidth = tableWidth + realDeltaWidth - (elasticColumnWidth - newElasticColumnWidth);

		resizableDivs[curColumnIndex].style.width = widthToBeSet +'px';
		curColumn.style.width     = widthToBeSet +'px';
		elasticColumn.style.width = newElasticColumnWidth +'px';
		resizableDivs[resizableDivs.length - 1].style.width = newElasticColumnWidth +'px';
		table.style.width         = tableWidth + 'px';

		// sometimes, real width of column != style.width
		// handle this situation
		curRealWidth = parseInt(curColumn.offsetWidth);
		if (curRealWidth != widthToBeSet)
		{
			realDeltaWidth = curRealWidth - widthToBeSet;

			widthToBeSet = curRealWidth;

			elasticColumnWidth    = parseInt(elasticColumn.offsetWidth);
			newElasticColumnWidth = elasticColumnWidth - realDeltaWidth;

			if (newElasticColumnWidth < elasticColumnMinWidth)
				newElasticColumnWidth = elasticColumnMinWidth;

			tableWidth = tableWidth + realDeltaWidth - (elasticColumnWidth - newElasticColumnWidth);


			resizableDivs[curColumnIndex].style.width = widthToBeSet +'px';
			curColumn.style.width = widthToBeSet +'px';
			elasticColumn.style.width = newElasticColumnWidth +'px';
			resizableDivs[resizableDivs.length - 1].style.width = newElasticColumnWidth +'px';
			table.style.width     = tableWidth + 'px';
		}

		return;
	};


	this.columnResize = function(e)
	{
		var e = e || window.event;
		var X = e.clientX || e.pageX;
		var deltaWidth;

		if (X != eventMouseX)
		{
			self.changeColumnWidth(X - eventMouseX);
			eventMouseX = X;
		}

		BX.PreventDefault(e);

		return (false);
	};


	this.stopColumnResize = function(e)
	{
		var e = e || window.event;

		BX.removeClass(table, 'task-list-resize-column');

		document.onmouseup   = originalOnMouseUpHandler;
		document.onmousemove = originalOnMouseMoveHandler;

		if (callbackOnStopResize)
		{
			callbackOnStopResize({
				columnIndex : curColumnIndex,
				columnWidth : curRealWidth
			});
		}

		self.normalizeElasticColumnWidth();

		BX.PreventDefault(e);

		return (false);
	};


	this.startColumnResize = function(e)
	{
		var i;
		var widthes = [];
		var e = e || window.event;
		var target = e.target || e.srcElement;

		target = target.parentNode;

		while (target.tagName !== 'TH')
			target = target.parentNode;

		if ( ! resizableColumnsCnt )
			return false;

		if ((curColumnIndex < 0) || (curColumnIndex > resizableColumnsCnt))
			return false;

		curColumnIndex = target.cellIndex;
		curColumn      = resizableColumns[curColumnIndex];

		if ( ! curColumn )
			return false;

		// fix widthes of all columns
		tableWidth = 0;

		// Firstly, get real width of every column in the table
		for (i = 0; i < allTableColumns.length; i++)
		{
			widthes[i] = parseInt(allTableColumns[i].offsetWidth);
			tableWidth = tableWidth + widthes[i];
		}

		// Now, set it up in style.width
		for (i = 0; i < allTableColumns.length; i++)
		{
			allTableColumns[i].style.width = widthes[i] + 'px';
			if (resizableDivs[i])
				resizableDivs[i].style.width = widthes[i] +'px';
		}

		table.style.width = tableWidth + 'px';
		table.style.tableLayout = "fixed";

		curRealWidth = parseInt(curColumn.style.width);

		curVirtualWidth = curRealWidth;
		eventMouseX     = e.clientX || e.pageX;

		originalOnMouseUpHandler = document.onmouseup;
		document.onmouseup       = self.stopColumnResize;

		originalOnMouseMoveHandler = document.onmousemove;
		document.onmousemove       = self.columnResize;

		BX.addClass(table, 'task-list-resize-column');

		BX.PreventDefault(e);

		return (false);
	};


	this.expandElasticColumn = function()
	{
		var outerWidth;
		var delta;

		outerWidth = parseInt(table.parentNode.offsetWidth);
		tableWidth = parseInt(table.offsetWidth);

		delta = outerWidth - tableWidth;

		if (delta > 0)
		{
			tableWidth                = tableWidth + delta;
			table.style.width         = tableWidth + 'px';
			elasticColumn.style.width = (elasticColumnMinWidth + delta) + 'px';
			resizableDivs[resizableDivs.length - 1].style.width = (elasticColumnMinWidth + delta) +'px';
		}
	};


	this.normalizeElasticColumnWidth = function()
	{
		var delta;

		delta = parseInt(elasticColumn.offsetWidth) - elasticColumnMinWidth;

		// set minimal width for last column
		if (delta > 0)
		{
			tableWidth                = parseInt(table.offsetWidth) - delta;
			table.style.width         = tableWidth + 'px';
			elasticColumn.style.width = elasticColumnMinWidth + 'px';
			resizableDivs[resizableDivs.length - 1].style.width = elasticColumnMinWidth +'px';
		}

		self.expandElasticColumn();
	};


	this.reinit = function()
	{
		resizableDivs    = [];
		resizableColumns = [];
		resizableColumnsCnt = allTableColumns.length;
		for (i = 0; i < resizableColumnsCnt; i++)
		{
			if ( ! allTableColumns[i] )
				break;

			resizableDiv = BX.findChild(
				allTableColumns[i],
				{tagName: "div"},
				false,		// recursive
				false		// get_all
			);

			item = null;
			if (resizableDiv)
			{
				item = BX.findChild(
					resizableDiv,
					{tagName: "div", className: "task-head-drag-btn"},
					false,		// recursive
					false		// get_all
				);

				resizableDivs.push(resizableDiv);
			}
			else
				resizableDivs.push(null);

			if (resizableDiv && item)
			{
				resizableColumns.push(allTableColumns[i]);
				item.onmousedown = this.startColumnResize;
			}
			else
				resizableColumns.push(null);
		}
	}

	this.reinit();

	if (lastColumnAsElastic)
	{
		elasticColumn = allTableColumns[allTableColumns.length - 1];

		originalOnWindowResizeHandler = window.onresize;

		window.onresize = function(e)
		{
			var e = e || window.event;

			self.normalizeElasticColumnWidth();

			if (typeof originalOnWindowResizeHandler === 'function')
				originalOnWindowResizeHandler(e);
		};
	}
}
