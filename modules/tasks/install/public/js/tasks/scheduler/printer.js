(function() {

"use strict";

BX.namespace("BX.Scheduler");

BX.Scheduler.Printer = function(timeline)
{
	this.timeline = timeline;

	this.paperSizes = {
		A5: {
			width: 5.8,
			height: 8.3
		},
		A4: {
			width: 8.3,
			height: 11.7
		},
		Letter: {
			width: 8.5,
			height: 11
		},
		Legal: {
			width: 8.5,
			height: 14
		},
		A3: {
			width: 11.7,
			height: 16.5
		}
	};

	this.format = "A4";
	this.orientation = "portrait";
	this.border = 0;
	this.dpi = 72;

	this.dateFrom = this.getViewportDateFrom();
	this.dateTo = this.getViewportDateTo();

	this.maxPagesToPrint = 100;
	this.printWindow = null;
	this.fitToPage = false;
};

BX.Scheduler.Printer.prototype =
{
	print: function()
	{
		var timeline = this.getTimeline();

		var dateFrom = this.getDateFrom();
		var dateTo = this.getDateTo();

		if (dateFrom > dateTo)
		{
			throw new Error(BX.message("SCHEDULER_PRINT_WRONG_DATE_RANGE"));
		}
		else if (dateFrom < timeline.getStart())
		{
			throw new Error(BX.message("SCHEDULER_PRINT_WRONG_DATE_FROM"));
		}
		else if (dateTo > timeline.getEnd())
		{
			throw new Error(BX.message("SCHEDULER_PRINT_WRONG_DATE_TO"));
		}

		var columnWidth = timeline.getColumnWidth();
		var printableTimelineWidth = timeline.getTimespanWidth(dateFrom, dateTo);
		var diagramWidth = printableTimelineWidth + columnWidth;

		var pageWidth = this.getPageWidth();
		var pageHeight = this.getPageHeight();

		var pageXCount = this.getFitToPage() ? 1 : Math.ceil(diagramWidth / pageWidth);
		var pageXColumnCount = this.getFitToPage() ? 1 : Math.ceil(columnWidth / pageWidth);
		var pageYCount = this.getFitToPage() ? 1 : Math.ceil(timeline.getScrollHeight() / pageHeight);

		var totalPages = this.getFitToPage() ? Math.ceil(diagramWidth / pageWidth) : pageXCount * pageYCount;
		if (totalPages > this.maxPagesToPrint)
		{
			var errorText = BX.message("SCHEDULER_PRINT_TOO_MANY_PAGES");
			errorText = BX.type.isNotEmptyString(errorText) ? errorText.replace("#NUMBER#", totalPages) : errorText;

			throw new Error(errorText);
		}

		var dateOffset = timeline.getPixelsFromDate(dateFrom);
		var printWindow = this.createPrintWindow();
		var originalScrollLeft = timeline.getScrollLeft();

		//Page Width can be wider than timeline container. We need to redraw headers with a new viewport width.
		timeline.setHeaderViewportWidth(this.getFitToPage() ? diagramWidth : pageWidth);

		for (var x = 1; x <= pageXCount; x++)
		{
			timeline.scrollTo(dateOffset);

			for (var y = 1; y <= pageYCount; y++)
			{
				var printPage = document.createElement("div");
				printPage.style.overflow = "hidden";
				printPage.style.width = pageWidth + "px";
				printPage.style.height = pageHeight + "px";

				if (this.getBorder())
				{
					printPage.style.border = "1px dashed #333";
					printPage.style.marginBottom = "20px";
				}

				var pageBreak = document.createElement("div");
				pageBreak.style.cssText = "page-break-after:always";

				var newContainer = timeline.getRootContainer().cloneNode(true);
				printPage.appendChild(newContainer);
				var newTimeline = newContainer.querySelector(".task-gantt-timeline-inner");
				var newColumn = newContainer.querySelector(".task-gantt-list");

				newContainer.style.position = "relative";
				newContainer.style.top = -(y - 1) * pageHeight + "px";
				newContainer.style.width = pageWidth + "px";

				newTimeline.style.left = -dateOffset + "px";
				newTimeline.style.position = "relative";

				if (x > pageXColumnCount)
				{
					// newContainer.style.left = -columnWidth + "px";
					newColumn.style.display = "none";
				}
				else
				{
					newColumn.style.left = -(x - 1) * pageWidth + "px";
					newTimeline.parentNode.style.left = -(x - 1) * pageWidth + "px";
					newContainer.style.width = pageWidth * x + "px";
				}

				if (this.getFitToPage())
				{
					newContainer.style.width = diagramWidth + "px";
					var scale = Math.min(pageWidth / diagramWidth, pageHeight / timeline.getScrollHeight());
					newContainer.style.transform = "scale(" + scale + ")";
					newContainer.style.transformOrigin = "0 0";
				}

				printWindow.document.body.appendChild(printPage);
				printWindow.document.body.appendChild(pageBreak);
			}

			if (x === pageXColumnCount)
			{
				dateOffset += pageXColumnCount * pageWidth - columnWidth;
			}
			else if (x > pageXColumnCount)
			{
				dateOffset += pageWidth;
			}
		}

		timeline.setHeaderViewportWidth(null);
		timeline.setScrollLeft(originalScrollLeft);

		setTimeout(function() {
			printWindow.print();
		}, 1000);
	},

	createPrintWindow: function()
	{
		this.printWindow = window.open("", "scheduler-print-" + BX.util.getRandomString());

		var headTags = "";
		var links = document.head.querySelectorAll("link, style");
		for (var i = 0; i < links.length; i++)
		{
			var link = links[i];
			headTags += link.outerHTML;
		}

		headTags +=
			"<style>" +
			"html, body { " +
				"background: #fff !important; " +
				"height: 100%; " +
				"-webkit-print-color-adjust: exact; " +
				"color-adjust: exact;" +
			"}\n" +
			// "@page {" +
			// 	"size: " + this.getFormat() + " " + this.getOrientation() + ";" +
			// "}" +
			"</style>";

		this.printWindow.document.write("<!DOCTYPE html><html><head>");
		this.printWindow.document.write(headTags);
		this.printWindow.document.write("<title>Print</title>");
		this.printWindow.document.write("</head><body class='scheduler-print-mode'>");
		this.printWindow.document.write("</body></html>");
		this.printWindow.document.close();

		return this.printWindow;
	},

	closePrintWindow: function()
	{
		if (this.printWindow)
		{
			this.printWindow.close();
			this.printWindow = null;
		}
	},

	getPrintWindow: function()
	{
		return this.printWindow;
	},

	getViewportDateFrom: function()
	{
		var scrollLeft = this.getTimeline().getScrollLeft();
		return this.getTimeline().getDateFromPixels(scrollLeft);
	},

	getDateFrom: function()
	{
		return this.dateFrom;
	},

	setDateFrom: function(dateFrom)
	{
		if (BX.type.isDate(dateFrom))
		{
			this.dateFrom = dateFrom;
		}
	},

	getViewportDateTo: function()
	{
		var viewport = this.getTimeline().getViewportWidth();
		var scrollLeft = this.getTimeline().getScrollLeft();

		return this.getTimeline().getDateFromPixels(scrollLeft + viewport);
	},

	getDateTo: function()
	{
		return this.dateTo;
	},

	setDateTo: function(dateTo)
	{
		if (BX.type.isDate(dateTo))
		{
			this.dateTo = dateTo;
		}
	},

	getTimeline: function()
	{
		return this.timeline;
	},

	getPaperSizes: function()
	{
		return this.paperSizes;
	},

	getPageSize: function()
	{
		return this.getPaperSizes()[this.getFormat()];
	},

	getPageWidth: function()
	{
		var pageSize = this.getPageSize();

		return (
			this.getOrientation() === "landscape"
				? pageSize.height * this.getDPI()
				: pageSize.width * this.getDPI()
		);
	},

	getPageHeight: function()
	{
		var pageSize = this.getPageSize();

		return (
			this.getOrientation() === "landscape"
				? pageSize.width * this.getDPI()
				: pageSize.height * this.getDPI()
		);
	},

	getDPI: function()
	{
		return this.dpi;
	},

	setFormat: function(format)
	{
		if (this.getPaperSizes()[format])
		{
			this.format = format;
		}
	},

	getFormat: function()
	{
		return this.format;
	},

	getOrientation: function()
	{
		return this.orientation;
	},

	setOrientation: function(orientation)
	{
		this.orientation = orientation;
	},

	getBorder: function()
	{
		return this.border;
	},

	setBorder: function(border)
	{
		if (BX.type.isBoolean(border))
		{
			this.border = border;
		}
	},

	setFitToPage: function(flag)
	{
		if (BX.type.isBoolean(flag))
		{
			this.fitToPage = flag;
		}
	},

	getFitToPage: function()
	{
		return this.fitToPage;
	}
};

})();