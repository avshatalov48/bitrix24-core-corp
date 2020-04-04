(function() {

"use strict";

BX.namespace("BX.Scheduler");

BX.Scheduler.PrintSettings = function(timeline)
{
	this.timeline = timeline;
	this.printer = new BX.Scheduler.Printer(timeline);
	this.defaultFormat = "A4";
	this.init();
};

BX.Scheduler.PrintSettings.prototype =
{
	init: function()
	{
		this.popupWindow = null;

		this.layout = {
			content: null,
			errorAlert: null,
			errorText: null,
			dateFrom: null,
			dateTo: null,
			format: null,
			orientation: null,
			border: null,
			fitToPage: null
		};
	},

	show: function()
	{
		this.getPopup().show();
		this.getPrinter().closePrintWindow();
	},

	close: function()
	{
		this.getPopup().destroy();
		this.init();
	},

	handlePrintButtonClick: function()
	{
		var printer = this.getPrinter();

		printer.setFormat(this.getFormat());
		printer.setOrientation(this.getOrientation());
		printer.setBorder(this.getBorder());
		printer.setDateFrom(this.getDateFrom());
		printer.setDateTo(this.getDateTo());
		printer.setFitToPage(this.getFitToPage());

		try
		{
			printer.print();
			this.close();

			if (this.isChrome())
			{
				var alert = new BX.Scheduler.PrintSettingsAlert(printer);
				alert.show();
			}
		}
		catch (exception)
		{
			this.showError(exception.message)
		}
	},

	/**
	 *
	 * @return {BX.Scheduler.Printer}
	 */
	getPrinter: function()
	{
		return this.printer;
	},

	showError: function(error)
	{
		this.layout.content.insertBefore(this.getErrorAlert(), this.layout.content.firstElementChild);
		this.layout.errorText.innerHTML = error;
	},

	hideError: function()
	{
		BX.remove(this.getErrorAlert());
	},

	getErrorAlert: function()
	{
		if (this.layout.errorAlert === null)
		{
			this.layout.errorAlert = BX.create("div", {
				props: {
					className: "ui-alert ui-alert-danger"
				},
				children: [
					this.layout.errorText = BX.create("span", {
						props: {
							className: "ui-alert-message"
						}
					}),
					BX.create("span", {
						props: {
							className: "ui-alert-close-btn"
						},
						events: {
							click: function() {
								this.hideError();
							}.bind(this)
						}
					})
				]
			});
		}

		return this.layout.errorAlert;
	},

	isChrome: function()
	{
		return /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
	},

	/**
	 *
	 * @return {BX.PopupWindow}
	 */
	getPopup: function()
	{
		if (this.popupWindow !== null)
		{
			return this.popupWindow;
		}

		this.popupWindow = new BX.PopupWindow("scheduler-print-settings", null, {
			autoHide: false,
			closeByEsc: true,
			titleBar: BX.message("SCHEDULER_PRINT_SETTINGS_TITLE"),
			closeIcon: true,
			draggable: true,
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message("SCHEDULER_PRINT_BUTTON_TITLE"),
					className: "popup-window-button-accept",
					events: {
						click: this.handlePrintButtonClick.bind(this)
					}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message("SCHEDULER_PRINT_CANCEL_BUTTON_TITLE"),
					className: "popup-window-button-link theme-dialog-button-link",
					events: {
						click: function() {
							this.popupWindow.close();
						}
					}
				})
			],
			events: {
				onPopupClose: function() {
					this.close();
				}.bind(this)
			}
		});

		this.layout.content = BX.create("div", {
			props: {
				className: "scheduler-print-settings"
			},
			children: [
				this.createRow([
					this.createField(BX.message("SCHEDULER_PRINT_DATE_FROM_TITLE"), this.getDateFromField()),
					this.createField(BX.message("SCHEDULER_PRINT_DATE_TO_TITLE"), this.getDateToField())]
				),
				this.createRow([
					this.createField(BX.message("SCHEDULER_PRINT_FORMAT_TITLE"), this.getFormatField()),
					this.createField(BX.message("SCHEDULER_PRINT_ORIENTATION_TITLE"), this.getOrientationField())]
				),

				this.createRow([
					this.createField(null, [
						BX.create("label", {
							props: {
								className: "scheduler-print-control-label"
							},
							children: [
								this.getBorderField(),
								BX.create("span", {
									text: BX.message("SCHEDULER_PRINT_BORDER_TITLE")
								})
							]
						}),
						BX.create("label", {
							props: {
								className: "scheduler-print-control-label"
							},
							children: [
								this.getFitToPageField(),
								BX.create("span", {
									text: BX.message("SCHEDULER_PRINT_FIT_TO_PAGE")
								})
							]
						})
					])
				])
			]
		});

		this.popupWindow.setContent(this.layout.content);

		return this.popupWindow;
	},

	/**
	 *
	 * @param {string} caption
	 * @param {Element} control
	 * @return {Element}
	 */
	createField: function(caption, control)
	{
		var controls = Array.isArray(control) ? control : [control];

		return BX.create("div", {
			props: {
				className: "scheduler-print-box"
			},
			children: [
				BX.type.isNotEmptyString(caption)
					? BX.create("div", { props: { className: "scheduler-print-caption" }, text: caption })
					: null
			].concat(controls)
		});
	},

	/**
	 *
	 * @param {Element[]} fields
	 * @return {Element}
	 */
	createRow: function(fields)
	{
		return BX.create("div", {
			props: {
				className: "scheduler-print-row"
			},
			children: fields
		});
	},

	/**
	 *
	 * @return {HTMLInputElement}
	 */
	getDateFromField: function()
	{
		if (this.layout.dateFrom === null)
		{
			this.layout.dateFrom = BX.create("input", {
				attrs: {
					type: "text",
					readonly: "true"
				},
				props: {
					className: "scheduler-print-control scheduler-print-control-date",
					value: this.formatDate(this.printer.getViewportDateFrom())
				},
				events: {
					click: this.handleDateClick.bind(this)
				}
			});
		}

		return this.layout.dateFrom;
	},

	/**
	 *
	 * @return {HTMLInputElement}
	 */
	getDateToField: function()
	{
		if (this.layout.dateTo === null)
		{
			this.layout.dateTo = BX.create("input", {
				attrs: {
					type: "text",
					readonly: "true"
				},
				props: {
					className: "scheduler-print-control scheduler-print-control-date",
					value: this.formatDate(this.printer.getViewportDateTo())
				},
				events: {
					click: this.handleDateClick.bind(this)
				}
			});
		}

		return this.layout.dateTo;
	},

	/**
	 *
	 * @param {Date} date
	 * @return {string}
	 */
	formatDate: function(date)
	{
		return BX.date.format(
			BX.date.convertBitrixFormat(BX.message("FORMAT_DATETIME")).replace(/:s/g, ""), date, null, true
		);
	},

	handleDateClick: function(event)
	{
		BX.calendar({
			node: event.currentTarget,
			field: event.currentTarget,
			bTime: true,
			bUseSecond: false,
			bSetFocus: false
		});
	},

	/**
	 *
	 * @return {HTMLSelectElement}
	 */
	getFormatField: function()
	{
		if (this.layout.format === null)
		{
			this.layout.format = BX.create("select", {
				props: {
					className: "scheduler-print-control scheduler-print-control-select"
				},
				children: Object.keys(this.printer.getPaperSizes()).map(function(size) {
					return BX.create("option", {
						text: size,
						attrs: {
							value: size
						},
						props: {
							selected: this.defaultFormat === size
						}
					})
				}, this)

			});
		}

		return this.layout.format;
	},

	/**
	 *
	 * @return {HTMLSelectElement}
	 */
	getOrientationField: function()
	{
		if (this.layout.orientation === null)
		{
			this.layout.orientation = BX.create("select", {
				props: {
					className: "scheduler-print-control scheduler-print-control-select"
				},
				children: [
					BX.create("option", {
						text: BX.message("SCHEDULER_PRINT_PORTRAIT_TITLE"),
						attrs: {
							value: "portrait"
						}
					}),
					BX.create("option", {
						text: BX.message("SCHEDULER_PRINT_LANDSCAPE_TITLE"),
						attrs: {
							value: "landscape"
						}
					})
				]
			});
		}

		return this.layout.orientation;
	},

	/**
	 *
	 * @return {HTMLInputElement}
	 */
	getBorderField: function()
	{
		if (this.layout.border === null)
		{
			this.layout.border = BX.create("input", {
				attrs: {
					type: "checkbox"
				},
				props: {
					className: "scheduler-print-control-checkbox",
					checked: true
				}
			});
		}

		return this.layout.border;
	},

	/**
	 *
	 * @return {HTMLInputElement}
	 */
	getFitToPageField: function()
	{
		if (this.layout.fitToPage === null)
		{
			this.layout.fitToPage = BX.create("input", {
				attrs: {
					type: "checkbox"
				},
				props: {
					className: "scheduler-print-control-checkbox",
					checked: true
				}
			});
		}

		return this.layout.fitToPage;
	},

	getDateFrom: function()
	{
		return BX.parseDate(this.getDateFromField().value, true);
	},

	getDateTo: function()
	{
		return BX.parseDate(this.getDateToField().value, true);
	},

	getFormat: function()
	{
		var select = this.getFormatField();
		return select.options[select.selectedIndex].value;
	},

	getOrientation: function()
	{
		var select = this.getOrientationField();
		return select.options[select.selectedIndex].value;
	},

	getBorder: function()
	{
		return this.getBorderField().checked;
	},

	getFitToPage: function()
	{
		return this.getFitToPageField().checked;
	}
};


BX.Scheduler.PrintSettingsAlert = function(printer)
{
	this.printer = printer;

	var printWindow = this.printer.getPrintWindow();
	if (printWindow)
	{
		printWindow.addEventListener("beforeprint", this.handleBeforePrint.bind(this));
		printWindow.addEventListener("afterprint", this.handleAfterPrint.bind(this));
	}

	var timer = setInterval(function() {
		if (printWindow && printWindow.closed)
		{
			clearInterval(timer);
			this.close();
		}
	}.bind(this), 500);

	this.popupWindow = null;
};

BX.Scheduler.PrintSettingsAlert.prototype =
{
	show: function()
	{
		this.getPopup().show();
	},

	close: function()
	{
		this.getPopup().close();
	},

	getPrinter: function()
	{
		return this.printer;
	},

	getPopup: function()
	{
		if (this.popupWindow !== null)
		{
			return this.popupWindow;
		}

		var self = this; //BX.PopupButton doesn't pass its object to the event callback
		this.popupWindow = new BX.PopupWindow("scheduler-print-settings-alert", null, {
			autoHide: false,
			closeByEsc: false,
			closeIcon: false,
			overlay: true,
			content:
			'<div class="scheduler-print-alert-container">' +
				BX.message("SCHEDULER_PRINT_ALERT_TEXT") +
			'</div>',
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message("SCHEDULER_PRINT_ALERT_BUTTON_TITLE"),
					className: "popup-window-button-accept",
					events: {
						click: function() {
							this.popupWindow.close();
							self.getPrinter().closePrintWindow();
						}
					}
				})
			]
		});

		return this.popupWindow;
	},

	handleBeforePrint: function()
	{
		this.show();
	},

	handleAfterPrint: function()
	{
		this.close();
	}
};

})();