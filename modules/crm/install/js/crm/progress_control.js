if(typeof(BX.CrmDealStageManager) === "undefined")
{
	BX.CrmDealStageManager = function() {};

	BX.CrmDealStageManager.prototype =
	{
		getInfos: function(typeId)
		{
			if(!BX.type.isNotEmptyString(typeId))
			{
				typeId = "category_0";
			}
			return (BX.type.isArray(BX.CrmDealStageManager.infos[typeId]) ? BX.CrmDealStageManager.infos[typeId] : []);
		},
		isMultiType: function()
		{
			return true;
		},
		getMessage: function(name)
		{
			var msgs = BX.CrmDealStageManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		},
		prepareDialogControls: function(dialog)
		{
			return null;
		}
	};

	BX.CrmDealStageManager.current = new BX.CrmDealStageManager();
	BX.CrmDealStageManager.infos =
		{
			"category_0":
				[
					{ "id": "NEW", "name": "In Progress", "sort": 10, "semantics": "process" },
					{ "id": "WON", "name": "Is Won", "sort": 20, "semantics": "success" },
					{ "id": "LOSE", "name": "Is Lost", "sort": 30, "semantics": "failure" }
				]
		};
	BX.CrmDealStageManager.messages = {};
}
if(typeof(BX.CrmDealRecurringStageManager) === "undefined")
{
	BX.CrmDealRecurringStageManager = function() {};

	BX.CrmDealRecurringStageManager.prototype =
		{
			getInfos: function(typeId)
			{
				return [];
			},
			isMultiType: function()
			{
				return false;
			},
			getMessage: function(name)
			{
				return "";
			},
			prepareDialogControls: function(dialog)
			{
				return null;
			}
		};

	BX.CrmDealRecurringStageManager.current = new BX.CrmDealRecurringStageManager();
}
if(typeof(BX.CrmLeadStatusManager) === "undefined")
{
	BX.CrmLeadStatusManager = function()
	{
		this._admissionPromise = null;
		this._admissionResult = null;
	};

	BX.CrmLeadStatusManager.prototype =
	{
		getInfos: function(typeId) { return BX.CrmLeadStatusManager.infos; },
		isMultiType: function()
		{
			return false;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.CrmLeadStatusManager.messages, name, name);
		},
		admitChange: function(previousId, currentId)
		{
			this._admissionPromise = new BX.Promise();
			this._admissionResult =
				{
					succeeded: false,
					previousId: previousId,
					currentId: currentId
				};

			if(previousId === currentId || previousId !== "CONVERTED")
			{
				window.setTimeout(
					function()
					{
						this._admissionResult["succeeded"] = true;
						this._admissionPromise.fulfill(this._admissionResult);
					}.bind(this),
					0
				);
			}
			else
			{
				var dlg = BX.Crm.ConfirmationDialog.get("cancelLeadConversion");
				if(!dlg)
				{
					dlg = BX.Crm.ConfirmationDialog.create(
						"cancelLeadConversion",
						{
							title: this.getMessage("conversionCancellationTitle"),
							content: this.getMessage("conversionCancellationContent")
						}
					);
				}
				dlg.open().then(
					function(result)
					{
						if(this._admissionPromise && this._admissionResult)
						{
							this._admissionResult["succeeded"] = !BX.prop.getBoolean(result, "cancel", true);
							this._admissionPromise.fulfill(this._admissionResult);
						}
					}.bind(this)
				);
			}

			return this._admissionPromise;
		}
	};

	BX.CrmLeadStatusManager.current = new BX.CrmLeadStatusManager();
	BX.CrmLeadStatusManager.infos =
	[
		{ "id": "NEW", "name": "Not Processed", "sort": 10, "semantics": "process" },
		{ "id": "CONVERTED", "name": "Converted", "sort": 20, "semantics": "success" },
		{ "id": "JUNK", "name": "Junk", "sort": 30, "semantics": "failure" }
	];

	BX.CrmLeadStatusManager.messages = {};
}

if(typeof(BX.CrmLeadTerminationControl) === "undefined")
{
	BX.CrmLeadTerminationControl = function()
	{
		this._id = "";
		this._settings = null;
		this._entityId = "";
		this._enabled = true;
		this._schemeData = null;
		this._selector = null;
		this._dialogOpenListener = BX.delegate(this.onDialogOpen, this);
		this._dialogCloseListener = BX.delegate(this.onDialogClose, this);
	};
	BX.CrmLeadTerminationControl.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._entityId = parseInt(this.getSetting("entityId", 0));
			this._enabled = this.getSetting("canConvert", true);
			this._schemeData = this.getSetting("conversionScheme", {});
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		setSetting: function(name, val)
		{
			this._settings.setParam(name, val);
		},
		getId: function()
		{
			return this._id;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		isEnabled: function()
		{
			return this._enabled;
		},
		prepareControlId: function(name)
		{
			return (this._id + "_" + name).toLowerCase();
		},
		prepareDialogControls: function(sender)
		{
			var results = {};
			var bindEvents = false;

			var success = sender.getSetting("success");
			if(success)
			{
				bindEvents = true;

				results["successButton"] = BX.create("SPAN",
					{
						attrs:
							{
								id: this.prepareControlId("success_btn_wrapper"),
								className: "webform-small-button-separate-wrap"
							},
						children:
							[
								BX.create("SPAN",
									{
										attrs:
											{
												id: this.prepareControlId("success_btn_inner_wrapper"),
												className: "webform-small-button webform-small-button-accept"
											},
										children:
											[
												BX.create("SPAN",
													{
														text: this._schemeData["schemeCaption"] + ": "
													}
												),
												BX.create("SPAN",
													{
														attrs: { id: this.prepareControlId("success_btn") },
														text: this._schemeData["schemeDescription"]
													}
												)
											]
									}
								),
								BX.create("SPAN",
									{
										attrs:
											{
												id: this.prepareControlId("success_btn_menu"),
												className: "webform-small-button-right-part"
											}
									}
								)
							]
					}
				);
			}

			if(bindEvents)
			{
				sender.addOpenListener(this._dialogOpenListener);
				sender.addCloseListener(this._dialogCloseListener);
			}

			return results;
		},
		onDialogOpen: function(sender)
		{
			if(this._selector)
			{
				this._selector.release();
			}

			this._selector = BX.CrmLeadConversionSchemeSelector.create(
				this.prepareControlId("conv_scheme_selector"),
				{
					typeId: this.getSetting("typeId", BX.CrmLeadConversionType.general),
					entityId: this._entityId,
					scheme: this._schemeData["schemeName"],
					containerId: this.prepareControlId("success_btn_inner_wrapper"),
					labelId: this.prepareControlId("success_btn"),
					buttonId: this.prepareControlId("success_btn_menu"),
					originUrl: this._schemeData["originUrl"],
					enableHint: false
				}
			);
		},
		onDialogClose: function(sender)
		{
			if(this._selector)
			{
				this._selector.release();
				this._selector = null;
			}

			sender.removeOpenListener(this._dialogOpenListener);
			sender.removeCloseListener(this._dialogCloseListener);
		}
	};
	BX.CrmLeadTerminationControl.create = function(id, settings)
	{
		var self = new BX.CrmLeadTerminationControl();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmQuoteStatusManager) === "undefined")
{
	BX.CrmQuoteStatusManager = function() {};

	BX.CrmQuoteStatusManager.prototype =
	{
		getInfos: function(typeId) { return BX.CrmQuoteStatusManager.infos; },
		isMultiType: function()
		{
			return false;
		},
		getMessage: function(name)
		{
			var msgs = BX.CrmQuoteStatusManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		},
		prepareDialogControls: function(dialog)
		{
			return null;
		}
	};

	BX.CrmQuoteStatusManager.current = new BX.CrmQuoteStatusManager();
	BX.CrmQuoteStatusManager.infos =
		[
			{ "id": "DRAFT", "name": "In Progress", "sort": 10, "semantics": "process" },
			{ "id": "APPROVED", "name": "Is Approved", "sort": 20, "semantics": "success" },
			{ "id": "DECLAINED", "name": "Is Declained", "sort": 30, "semantics": "failure" }
		];

	BX.CrmQuoteStatusManager.messages = {};
}

if(typeof(BX.CrmOrderShipmentStatusManager) === "undefined")
{
	BX.CrmOrderShipmentStatusManager = function() {};

	BX.CrmOrderShipmentStatusManager.prototype =
		{
			getInfos: function(typeId) { return BX.CrmOrderShipmentStatusManager.infos; },
			isMultiType: function()
			{
				return false;
			},
			getMessage: function(name)
			{
				var msgs = BX.CrmOrderShipmentStatusManager.messages;
				return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
			},
			prepareDialogControls: function(dialog)
			{
				return null;
			}
		};

	BX.CrmOrderShipmentStatusManager.current = new BX.CrmOrderShipmentStatusManager();
	BX.CrmOrderShipmentStatusManager.infos =
		[
			{ "id": "DN", "name": "In Progress", "sort": 10, "semantics": "process" },
			{ "id": "DF", "name": "Deducted", "sort": 20, "semantics": "success" }
		];

	BX.CrmOrderShipmentStatusManager.messages = {};
}

if(typeof(BX.CrmOrderStatusManager) === "undefined")
{
	BX.CrmOrderStatusManager = function() {
		this.dlg = null;
	};

	BX.CrmOrderStatusManager.prototype =
		{
			getSetting: function(name, defaultval)
			{
				return (typeof(BX.CrmOrderStatusManager.settings[name]) !== 'undefined') ?
					BX.CrmOrderStatusManager.settings[name] : defaultval;
			},
			isMultiType: function()
			{
				return false;
			},
			setSetting: function(name, val)
			{
				BX.CrmOrderStatusManager.settings[name] = val;
			},
			getInfos: function(typeId) { return BX.CrmOrderStatusManager.infos; },
			getMessage: function(name)
			{
				var msgs = BX.CrmOrderStatusManager.messages;
				return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
			},
			prepareDialogControls: function(dialog)
			{
				return null;
			}
		};

	BX.CrmOrderStatusManager.current = new BX.CrmOrderStatusManager();
	BX.CrmOrderStatusManager.settings = {};
	BX.CrmOrderStatusManager.statusInfoValues = [];
	BX.CrmOrderStatusManager.infos =
		[
			{ "id": "N", "name": "In Progress", "sort": 10, "semantics": "process" },
			{ "id": "F", "name": "Is Paid", "sort": 20, "semantics": "success"},
			{ "id": "D", "name": "Is Dismiss", "sort": 30, "semantics": "failure" }
		];

	BX.CrmOrderStatusManager.messages = {};
	BX.CrmOrderStatusManager.failureDialogEventsBinded = false;

	BX.CrmOrderStatusManager.failureDialogEventsBind = function() {
		if (!BX.CrmOrderStatusManager.failureDialogEventsBinded)
		{
			BX.CrmOrderStatusManager.failureDialogEventsBinded = true;
			BX.addCustomEvent("CrmProcessFailureDialogContentCreated", function(val) {
				var self = BX.CrmOrderStatusManager.current;
				var entityType = val.getEntityType();

				if (entityType === "ORDER")
				{
					var entityId = parseInt(val.getEntityId());
					var wrapper = val.getWrapper();
					var paramsId = "crm_" + entityType + entityId + "_params";
					var failureParamsId = paramsId + '_failure';
					self.failureParamsId = failureParamsId;

					if (wrapper && entityId > 0 && entityType.length > 0)
					{
						var statusInfoValues = BX.CrmOrderStatusManager.statusInfoValues[entityId],
							content = BX.create(
							"DIV",
							{
								"attrs": {
									"id": paramsId
								},
								"children":
									[
										BX.create(
											"DIV",
											{
												"attrs": {
													"id": failureParamsId,
													"class": "crm-invoice-term-dialog-params"
												},
												"children":
													[
														BX.create(
															"SPAN",
															{
																"attrs": {
																	"class": "comment-header"
																},
																"text": self.getMessage("commentLabelText")+":"
															}
														),
														BX.create(
															"TEXTAREA",
															{
																"attrs": {
																	"class": "bx-crm-dialog-invoice-textarea",
																	"name": "REASON_CANCELED"
																},
																"text": (statusInfoValues['REASON_CANCELED']) ? statusInfoValues['REASON_CANCELED'] : ''
															}
														)
													]
											}
										)
									]
							}
						);
						if (content)
							wrapper.appendChild(content);
					}
				}
			});
			BX.addCustomEvent("CrmProgressControlBeforeFailureDialogClose", function(progressControl, failDlg) {
				var entityType = failDlg.getEntityType();
				if (entityType === "ORDER")
				{
					var self = BX.CrmOrderStatusManager.current;
					var containter = BX(self.failureParamsId);
					self.saveParams = {};
					if (containter)
					{
						var els = [];
						var inputs = BX.findChildren(containter, {"tag": "input"}, true);
						if (inputs)
							els = els.concat(inputs);
						var textareas = BX.findChildren(containter, {"tag": "textarea"}, true);
						if (textareas)
							els = els.concat(textareas);
						var name;
						for (var i in els)
						{
							name = els[i].getAttribute('name');
							if (name)
								self.saveParams[name] = els[i].value;
						}
					}
				}
			});
			BX.addCustomEvent("CrmProgressControlBeforeSave", function(progressControl, params)
			{
				if(progressControl.getEntityType() !== "ORDER")
					return;

				var self = BX.CrmOrderStatusManager.current;

				if (BX.type.isPlainObject(self.saveParams))
				{
					var entityId = progressControl.getEntityId();

					for (var name in self.saveParams)
					{
						if (entityId > 0)
							BX.CrmOrderStatusManager.statusInfoValues[entityId][name] = self.saveParams[name];

						params[name] = self.saveParams[name];
					}
				}
				params['STATE_SUCCESS'] = self.isSuccess ? "Y" : "N";
			});
			BX.addCustomEvent("CrmProgressControlAfterSaveSucces", function(progressControl, result)
			{
				if(progressControl.getEntityType() !== "ORDER")
					return;

				if (BX.type.isNotEmptyString(result['ERROR']) && BX.type.isNotEmptyString(result['STATUS_ID']))
				{
					progressControl._currentStepId = result["STATUS_ID"];
					progressControl._layout();
				}
			});
		}
	};
}

if(typeof(BX.CrmInvoiceStatusManager) === "undefined")
{
	BX.CrmInvoiceStatusManager = function() {
		this.dlg = null;
	};

	BX.CrmInvoiceStatusManager.prototype =
	{
		getSetting: function(name, defaultval)
		{
			return (typeof(BX.CrmInvoiceStatusManager.settings[name]) !== 'undefined') ?
				BX.CrmInvoiceStatusManager.settings[name] : defaultval;
		},
		setSetting: function(name, val)
		{
			BX.CrmInvoiceStatusManager.settings[name] = val;
		},
		getInfos: function(typeId) { return BX.CrmInvoiceStatusManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmInvoiceStatusManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		},
		prepareDialogControls: function(dialog)
		{
			return null;
		},
		_handleDateInputClick: function(e)
		{
			var inputId = BX(this.dlgDateControlId);
			BX.calendar({ node: BX(inputId), field: inputId, bTime: false, serverTime: this.getSetting('serverTime', ''), bHideTimebar: true });
		},
		_handleDateImageMouseOver: function(e)
		{
			BX.addClass(e.target, 'calendar-icon-hover');
		},
		_handleDateImageMouseOut: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			BX.removeClass(e.target, 'calendar-icon-hover');
		}
	};

	BX.CrmInvoiceStatusManager.current = new BX.CrmInvoiceStatusManager();
	BX.CrmInvoiceStatusManager.settings = {};
	BX.CrmInvoiceStatusManager.statusInfoValues = [];
	BX.CrmInvoiceStatusManager.infos =
		[
			{ "id": "N", "name": "In Progress", "sort": 10, "semantics": "process" },
			{ "id": "F", "name": "Is Paid", "sort": 20, "semantics": "success", "hasParams": true },
			{ "id": "D", "name": "Is Dismiss", "sort": 30, "semantics": "failure" }
		];

	BX.CrmInvoiceStatusManager.messages = {};
	BX.CrmInvoiceStatusManager.failureDialogEventsBinded = false;

	BX.CrmInvoiceStatusManager.failureDialogEventsBind = function() {
		if (!BX.CrmInvoiceStatusManager.failureDialogEventsBinded)
		{
			BX.CrmInvoiceStatusManager.failureDialogEventsBinded = true;
			BX.addCustomEvent("CrmProcessFailureDialogContentCreated", function(val) {
				var self = BX.CrmInvoiceStatusManager.current;
				var entityType = val.getEntityType();

				if (entityType === "INVOICE")
				{
					var entityId = parseInt(val.getEntityId());
					var wrapper = val.getWrapper();
					var isSuccess = (val.getValue() === val.getSuccessValue());
					var paramsId = "crm_" + entityType + entityId + "_params";
					var successParamsId = paramsId + '_success';
					var failureParamsId = paramsId + '_failure';
					var dateControlIdSuccess = "crm_" + entityType + entityId + "_date_success";
					var dateControlIdFail = "crm_" + entityType + entityId + "_date_fail";

					self.isSuccess = isSuccess;
					self.successParamsId = successParamsId;
					self.failureParamsId = failureParamsId;
					self.dlgDateControlId = isSuccess ? dateControlIdSuccess : dateControlIdFail;

					if (wrapper && entityId > 0 && entityType.length > 0)
					{
						var statusInfoValues = BX.CrmInvoiceStatusManager.statusInfoValues[entityId];
						var content = null;
						content = BX.create(
							"DIV",
							{
								"attrs": {
									"id": paramsId
								},
								"children":
									[
										BX.create(
											"DIV",
											{
												"attrs": {
													"id": successParamsId,
													"class": "crm-invoice-term-dialog-params",
													"style": isSuccess ? "": "display: none;"
												},
												"children":
													[
														BX.create(
															"TABLE",
															{
																"children":
																	[
																		BX.create(
																			"TR",
																			{
																				"children":
																					[
																						BX.create(
																							"TD",
																							{
																								"class": "left-column",
																								"text": self.getMessage("dateLabelText")+":"
																							}
																						),
																						BX.create(
																							"TD",
																							{
																								"children":
																									[
																										// date control
																										BX.create(
																											'INPUT',
																											{
																												attrs: { className: 'bx-crm-dialog-input' },
																												props:
																												{
																													type: 'text',
																													id: dateControlIdSuccess,
																													name: 'PAY_VOUCHER_DATE',
																													value: (statusInfoValues['PAY_VOUCHER_DATE']) ? statusInfoValues['PAY_VOUCHER_DATE'] : BX.formatDate(null, BX.message('FORMAT_DATE'))
																												},
																												style:
																												{
																													width:'70px'
																												},
																												events:
																												{
																													click: BX.delegate(self._handleDateInputClick, self)
																												}
																											}
																										),
																										BX.create(
																											'A',
																											{
																												props:
																												{
																													href:'javascript:void(0);',
																													title: self.getMessage('setDate')
																												},
																												children:
																													[
																														BX.create(
																															'IMG',
																															{
																																attrs:
																																{
																																	src: self.getSetting('imagePath', '') + 'calendar.gif',
																																	className: 'calendar-icon',
																																	alt: self.getMessage('setDate')
																																},
																																events:
																																{
																																	click: BX.delegate(self._handleDateInputClick, self),
																																	mouseover: BX.delegate(self._handleDateImageMouseOver, self),
																																	mouseout: BX.delegate(self._handleDateImageMouseOut, self)
																																}
																															}
																														)
																													]
																											}
																										)
																									]
																							}
																						)
																					]
																			}
																		),
																		BX.create(
																			"TR",
																			{
																				"children":
																					[
																						BX.create(
																							"TD",
																							{
																								"class": "left-column",
																								"text": self.getMessage("payVoucherNumLabelText")+":"
																							}
																						),
																						BX.create(
																							"TD",
																							{
																								"children":
																									[
																										BX.create(
																											"INPUT",
																											{
																												"attrs":
																												{
																													"class": "bx-crm-dialog-input",
																													"type": "text",
																													"name": "PAY_VOUCHER_NUM",
																													"value": (statusInfoValues['PAY_VOUCHER_NUM']) ? statusInfoValues['PAY_VOUCHER_NUM'].substring(0, 20) : '',
																													"maxlength": 20,
																													"size": 20
																												}
																											}
																										)
																									]
																							}
																						)
																					]
																			}
																		)
																	]
															}
														),
														BX.create(
															"DIV",
															{
																"attrs": {
																	"class": "separator"
																}
															}
														),
														BX.create(
															"SPAN",
															{
																"attrs": {
																	"class": "comment-header"
																},
																"text": self.getMessage("commentLabelText")+":"
															}
														),
														BX.create(
															"TEXTAREA",
															{
																"attrs": {
																	"class": "bx-crm-dialog-invoice-textarea",
																	"name": "REASON_MARKED_SUCCESS"
																},
																"text": (statusInfoValues['REASON_MARKED']) ? statusInfoValues['REASON_MARKED'] : ''
															}
														)
													]
											}
										),
										BX.create(
											"DIV",
											{
												"attrs": {
													"id": failureParamsId,
													"class": "crm-invoice-term-dialog-params",
													"style": isSuccess ? "display: none;" : ""
												},
												"children":
													[
														BX.create(
															"TABLE",
															{
																"children":
																	[
																		BX.create(
																			"TR",
																			{
																				"children":
																					[
																						BX.create(
																							"TD",
																							{
																								"class": "left-column",
																								"text": self.getMessage("dateLabelText")+":"
																							}
																						),
																						BX.create(
																							"TD",
																							{
																								"children":
																									[
																										// date control
																										BX.create(
																											'INPUT',
																											{
																												attrs: { className: 'bx-crm-dialog-input' },
																												props:
																												{
																													type: 'text',
																													id: dateControlIdFail,
																													name: 'DATE_MARKED',
																													value: (statusInfoValues['DATE_MARKED']) ? statusInfoValues['DATE_MARKED'] : BX.formatDate(null, BX.message('FORMAT_DATE'))
																												},
																												style:
																												{
																													width:'70px'
																												},
																												events:
																												{
																													click: BX.delegate(self._handleDateInputClick, self)
																												}
																											}
																										),
																										BX.create(
																											'A',
																											{
																												props:
																												{
																													href:'javascript:void(0);',
																													title: self.getMessage('setDate')
																												},
																												children:
																													[
																														BX.create(
																															'IMG',
																															{
																																attrs:
																																{
																																	src: self.getSetting('imagePath', '') + 'calendar.gif',
																																	className: 'calendar-icon',
																																	alt: self.getMessage('setDate')
																																},
																																events:
																																{
																																	click: BX.delegate(self._handleDateInputClick, self),
																																	mouseover: BX.delegate(self._handleDateImageMouseOver, self),
																																	mouseout: BX.delegate(self._handleDateImageMouseOut, self)
																																}
																															}
																														)
																													]
																											}
																										)
																									]
																							}
																						)
																					]
																			}
																		)
																	]
															}
														),
														BX.create(
															"DIV",
															{
																"attrs": {
																	"class": "separator"
																}
															}
														),
														BX.create(
															"SPAN",
															{
																"attrs": {
																	"class": "comment-header"
																},
																"text": self.getMessage("commentLabelText")+":"
															}
														),
														BX.create(
															"TEXTAREA",
															{
																"attrs": {
																	"class": "bx-crm-dialog-invoice-textarea",
																	"name": "REASON_MARKED"
																},
																"text": (statusInfoValues['REASON_MARKED']) ? statusInfoValues['REASON_MARKED'] : ''
															}
														)
													]
											}
										)
									]
							}
						);
						if (content)
							wrapper.appendChild(content);
					}
				}
			});
			BX.addCustomEvent("CrmProcessFailureDialogValueChanged", function(failDlg, val) {
				var self = BX.CrmInvoiceStatusManager.current;
				var entityType = failDlg.getEntityType();
				if (entityType === "INVOICE")
				{
					var entityId = parseInt(failDlg.getEntityId());
					var wrapper = failDlg.getWrapper();
					var isSuccess = (val === failDlg.getSuccessValue());
					var paramsId = "crm_" + entityType + entityId + "_params";
					var successParamsId = paramsId + '_success';
					var failureParamsId = paramsId + '_failure';
					var successContainer = BX(successParamsId);
					var failureContainer = BX(failureParamsId);
					var dateControlIdSuccess = "crm_" + entityType + entityId + "_date_success";
					var dateControlIdFail = "crm_" + entityType + entityId + "_date_fail";

					self.isSuccess = isSuccess;
					self.dlgDateControlId = isSuccess ? dateControlIdSuccess : dateControlIdFail;

					if (successContainer)
						successContainer.setAttribute("style", isSuccess ? "" : "display: none;");
					if (failureContainer)
						failureContainer.setAttribute("style", isSuccess ? "display: none;" : "")
				}
			});
			BX.addCustomEvent("CrmProgressControlBeforeFailureDialogClose", function(progressControl, failDlg) {
				var entityType = failDlg.getEntityType();
				if (entityType === "INVOICE")
				{
					var self = BX.CrmInvoiceStatusManager.current;
					var containter = BX(self.isSuccess ? self.successParamsId : self.failureParamsId);

					self.saveParams = {};
					if (containter)
					{
						var els = [];
						var inputs = BX.findChildren(containter, {"tag": "input"}, true);
						if (inputs)
							els = els.concat(inputs);
						var textareas = BX.findChildren(containter, {"tag": "textarea"}, true);
						if (textareas)
							els = els.concat(textareas);
						var name;
						for (var i in els)
						{
							name = els[i].getAttribute('name');
							if (name)
								self.saveParams[name] = els[i].value;
						}
					}
				}
			});
			BX.addCustomEvent("CrmProgressControlBeforeSave", function(progressControl, params)
			{
				if(progressControl.getEntityType() !== "INVOICE")
				{
					return;
				}

				var self = BX.CrmInvoiceStatusManager.current;
				if (BX.type.isPlainObject(self.saveParams))
				{
					var entityId = progressControl.getEntityId();
					var valName;
					for (var name in self.saveParams)
					{
						if (entityId > 0)
						{
							if (name === "REASON_MARKED_SUCCESS")
								valName = "REASON_MARKED";
							else
								valName = name;
							BX.CrmInvoiceStatusManager.statusInfoValues[entityId][valName] = self.saveParams[name];
						}
						params[name] = self.saveParams[name];
					}
				}
				params['STATE_SUCCESS'] = self.isSuccess ? "Y" : "N";
			});
		}
	};
}

if(typeof(BX.CrmItemStatusManager) === "undefined")
{
	BX.CrmItemStatusManager = function() {};

	BX.CrmItemStatusManager.prototype =
		{
			getInfos: function(typeId)
			{
				if (BX.CrmItemStatusManager.infos === [])
				{
					console.log('Error: CrmItemStatusManager has not default stages');
				}
				return (BX.type.isArray(BX.CrmItemStatusManager.infos[typeId]) ? BX.CrmItemStatusManager.infos[typeId] : []);
			},
			isMultiType: function()
			{
				return false;
			},
			getMessage: function(name)
			{
				var messages = BX.CrmItemStatusManager.messages;
				return BX.type.isNotEmptyString(messages[name]) ? messages[name] : "";
			},
			prepareDialogControls: function(dialog)
			{
				return null;
			}
		};

	BX.CrmItemStatusManager.current = new BX.CrmItemStatusManager();

	BX.CrmItemStatusManager.messages = {};
	BX.CrmItemStatusManager.infos = [];
}

if(typeof(BX.CrmProgressManager) === "undefined")
{
	BX.CrmProgressManager = function() {};
	BX.CrmProgressManager.prototype =
	{
		resolve: function(entityTypeId)
		{
			if(entityTypeId === BX.CrmEntityType.enumeration.deal)
			{
				return BX.CrmDealStageManager.current;
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.dealrecurring)
			{
				return BX.CrmDealRecurringStageManager.current;
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.quote)
			{
				return BX.CrmQuoteStatusManager.current;
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.lead)
			{
				return BX.CrmLeadStatusManager.current;
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.order)
			{
				return BX.CrmOrderStatusManager.current;
			}
			else if(entityTypeId === BX.CrmEntityType.enumeration.ordershipment)
			{
				return  BX.CrmOrderShipmentStatusManager.current;
			}
			return null;
		},
		isMultiType: function(entityTypeId)
		{
			var manager = this.resolve(entityTypeId);
			if(!manager)
			{
				return false;
			}
			return BX.type.isFunction(manager.isMultiType) && manager.isMultiType();
		}
	};
	BX.CrmProgressManager.current = new BX.CrmProgressManager();
}

if(typeof(BX.CrmProgressControl) === "undefined")
{
	BX.CrmProgressControl = function()
	{
		this._id = "";
		this._settings = null;
		this._container = null;
		this._legendContainer = null;
		this._entityId = 0;
		this._entityType = null;
		this._previousStepId = "";
		this._currentStepId = "";
		this._infoTypeId = "";

		this._manager = null;
		this._stepInfos = null;
		this._steps = [];
		this._terminationControl = null;
		this._terminationDlg = null;
		this._failureDlg = null;
		this._isFrozen = false;
		this._isReadOnly = false;

		this._entityEditorDialog = null;

		this._externalEventHandler = null;
		this._entityConvertHandler = null;
		this._entityEditorDialogHandler = BX.delegate(this._onEntityEditorDialogClose, this);

		this._enableCustomColors = false;
		this._defaultProcessColor = "#ACE9FB";
		this._defaultSuccessSuccessColor = "#DBF199";
		this._defaultFailureColor = "#FFBEBD";
	};

	BX.CrmProgressControl.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._container = BX(this.getSetting("containerId"));

			var legendContainerId = this.getSetting("legendContainerId");
			if(BX.type.isNotEmptyString(legendContainerId))
			{
				this._legendContainer = BX(legendContainerId);
			}

			if(!BX.type.isElementNode(this._legendContainer))
			{
				this._legendContainer = BX.findNextSibling(this._container, { "className": "crm-list-stage-bar-title" });
			}

			this._entityId = parseInt(this.getSetting("entityId", 0));
			this._entityType = this.getSetting("entityType");
			this._currentStepId = this.getSetting("currentStepId");
			this._infoTypeId = this.getSetting("infoTypeId", "");

			this._enableCustomColors = this.getSetting("enableCustomColors");

			if(this._entityType === 'DEAL')
			{
				this._manager = BX.CrmDealStageManager.current;
			}
			else if(this._entityType === 'LEAD')
			{
				this._manager = BX.CrmLeadStatusManager.current;
				this._terminationControl = BX.CrmLeadTerminationControl.create(
					this._id,
					BX.CrmParamBag.create(
						{
							entityId: this._entityId,
							conversionScheme: this.getSetting("conversionScheme", null),
							canConvert: this.getSetting("canConvert", true),
							typeId: this.getSetting("conversionTypeId", BX.CrmLeadConversionType.general)
						}
					)
				);
			}
			else if(this._entityType === 'QUOTE')
			{
				this._manager = BX.CrmQuoteStatusManager.current;
			}
			else if(this._entityType === 'ORDER')
			{
				this._manager = BX.CrmOrderStatusManager.current;
			}
			else if(this._entityType === 'ORDER_SHIPMENT')
			{
				this._manager = BX.CrmOrderShipmentStatusManager.current;
			}
			else if(this._entityType === 'INVOICE')
			{
				this._manager = BX.CrmInvoiceStatusManager.current;
			}
			else if(BX.CrmEntityType.isUseDynamicTypeBasedApproachByName(this._entityType))
			{
				this._manager = BX.CrmItemStatusManager.current;
			}

			var stepInfos = this._stepInfos = this._manager.getInfos(this._infoTypeId);
			var currentStepIndex = this._findStepInfoIndex(this._currentStepId);
			var currentStepInfo = currentStepIndex >= 0 ? stepInfos[currentStepIndex] : null;

			this._isReadOnly = this.getSetting("readOnly", false);
			this._isFrozen = this._isReadOnly
				|| (currentStepInfo && BX.type.isBoolean(currentStepInfo["isFrozen"]) ? currentStepInfo["isFrozen"] : false);

			for(var i = 0; i < stepInfos.length; i++)
			{
				var info = stepInfos[i];
				var stepContainer = this.getStepContainer(info["id"]);
				if(!stepContainer)
				{
					continue;
				}
				var sort = parseInt(info["sort"]);
				this._steps.push(
					BX.CrmProgressStep.create(
						info["id"],
						BX.CrmParamBag.create(
							{
								"name": info["name"],
								"hint": BX.type.isNotEmptyString(info["hint"]) ? info["hint"] : '',
								"sort": sort,
								"isPassed": i <= currentStepIndex,
								"isReadOnly": this._isReadOnly,
								"control": this
							}
						)
					)
				);
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		setSetting: function(name, val)
		{
			this._settings.setParam(name, val);
		},
		getId: function()
		{
			return this._id;
		},
		getEntityType: function()
		{
			return this._entityType;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		getCurrentStepId: function()
		{
			return this._currentStepId;
		},
		getStepById: function(stepId)
		{
			for(var i = 0, l = this._steps.length; i < l; i++)
			{
				var step = this._steps[i];
				if(step.getId() === stepId)
				{
					return step;
				}
			}
			return null;
		},
		isFrozen: function()
		{
			return this._isFrozen;
		},
		isReadOnly: function()
		{
			return this._isReadOnly;
		},
		onExternalEvent: function(params)
		{
			var key = BX.prop.getString(params, "key", "");
			if(key !== "onCrmEntityConvert")
			{
				return;
			}

			var v = BX.prop.getObject(params, "value", {});
			if(this._entityType === BX.prop.getString(v, "entityTypeName") && this._entityId === BX.prop.getInteger(v, "entityId", 0))
			{
				this._closeTerminationDialog();
				this._closeFailureDialog();
			}
		},
		onEntityConvert: function(sender, eventArgs)
		{
			if(this._entityType === BX.prop.getString(eventArgs, "entityTypeName") && this._entityId === BX.prop.getInteger(eventArgs, "entityId", 0))
			{
				this._closeTerminationDialog();
				this._closeFailureDialog();
			}
		},
		getStepContainer: function(id)
		{
			return BX.type.isNotEmptyString(id)
				? BX.findChild(this._container, { "tag": "DIV", "class": "crm-stage-" + id.toLowerCase() }, true)
				: null;
		},
		setCurrentStep: function(step)
		{
			this._closeTerminationDialog();

			if(!step || this._isReadOnly || this._isFrozen || this._entityEditorDialog !== null)
			{
				return;
			}

			if(BX.type.isFunction(this._manager["admitChange"]))
			{
				this._manager.admitChange(this._currentStepId, step.getId()).then(
					function(result)
					{
						if(!BX.prop.getBoolean(result, "succeeded", false))
						{
							return;
						}

						var step = this.getStepById(BX.prop.getString(result, "currentId", ""));
						if(step)
						{
							this.setupStep(step);
						}
					}.bind(this)
				);
			}
			else
			{
				this.setupStep(step);
			}
		},
		setupStep: function(step)
		{
			var stepIndex = this._findStepInfoIndex(step.getId());
			if(stepIndex < 0)
			{
				return;
			}

			if(stepIndex === (this._steps.length - 1)
				&& this._findStepInfoBySemantics("success")
				&& this._findStepInfoBySemantics("failure"))
			{
				if(!this._terminationControl || this._terminationControl.isEnabled())
				{
					//User have to make choice
					this._openTerminationDialog();
				}
				return;
			}

			var stepId = step.getId();
			if(this._currentStepId === stepId)
			{
				return;
			}

			this._previousStepId = this._currentStepId;
			this._currentStepId = stepId;

			this._layout();
			this._save();
		},
		setCurrentStepId: function(stepId)
		{
			if(this._currentStepId !== stepId)
			{
				this._previousStepId = this._currentStepId;
				this._currentStepId = stepId;

				this._layout();
			}
		},
		getCurrentStepInfo: function()
		{
			var stepIndex = this._findStepInfoIndex(this._currentStepId);
			return stepIndex >= 0 ? this._stepInfos[stepIndex] : null;
		},
		isCustomColorsEnabled: function()
		{
			return this._enableCustomColors;
		},
		setColor: function(stepIndex)
		{
			if(!this._stepInfos[stepIndex] || !this._enableCustomColors)
			{
				return;
			}

			var wrappers = BX.findChildren(this._container,
				{'tag': 'td', 'attribute': {'class': "crm-list-stage-bar-part"}}, true);
			for(var k = 0; k < wrappers.length; k++)
			{
				if(k > stepIndex)
				{
					wrappers[k].style.background = "";
				}
				else
				{
					var stepInfo = this._stepInfos[stepIndex];
					var color = BX.type.isNotEmptyString(stepInfo["color"]) ? stepInfo["color"] : "";
					if(color === "")
					{
						var semantics = BX.type.isNotEmptyString(stepInfo["semantics"]) ? stepInfo["semantics"] : "";
						if(semantics === "success")
						{
							color = this._defaultSuccessSuccessColor;
						}
						else if(semantics === "failure")
						{
							color = this._defaultFailureColor;
						}
						else
						{
							color = this._defaultProcessColor;
						}
					}
					wrappers[k].style.background = color;
				}
			}
		},
		_layout: function()
		{
			var stepIndex = this._findStepInfoIndex(this._currentStepId);
			if(stepIndex < 0)
			{
				return;
			}

			for(var i = 0; i < this._steps.length; i++)
			{
				this._steps[i].setPassed(i <= stepIndex);
			}

			this.setColor(stepIndex);

			var stepInfo = this._stepInfos[stepIndex];

			this._isFrozen = BX.type.isBoolean(stepInfo["isFrozen"]) ? stepInfo["isFrozen"] : false;
			var semantics = BX.type.isNotEmptyString(stepInfo["semantics"]) ? stepInfo["semantics"] : "";

			if(semantics === "success")
			{
				if(this._enableCustomColors)
				{
					this._container.style.background = stepInfo["color"];
				}
				else
				{
					BX.addClass(this._container, "crm-list-stage-end-good");
					BX.removeClass(this._container, "crm-list-stage-end-bad");
				}

			}
			else if(semantics === "failure" || semantics === "apology")
			{
				if(this._enableCustomColors)
				{
					this._container.style.background = stepInfo["color"];
				}
				else
				{
					BX.removeClass(this._container, "crm-list-stage-end-good");
					BX.addClass(this._container, "crm-list-stage-end-bad");
				}
			}
			else
			{
				if(this._enableCustomColors)
				{
					this._container.style.background = "";
				}
				else
				{
					BX.removeClass(this._container, "crm-list-stage-end-good");
					BX.removeClass(this._container, "crm-list-stage-end-bad");
				}
			}

			if(this._legendContainer)
			{
				this._legendContainer.innerHTML = BX.util.htmlspecialchars(BX.type.isNotEmptyString(stepInfo["name"]) ? stepInfo["name"] : stepInfo["id"]);
			}
		},
		_openTerminationDialog: function()
		{
			this._enableStepHints(false);

			if(this._terminationDlg)
			{
				this._terminationDlg.close();
				this._terminationDlg = null;
			}

			var apologies = this._findAllStepInfoBySemantics("apology");
			this._terminationDlg = BX.CrmProcessTerminationDialog.create(
				(this._id + "_TERMINATION"),
				BX.CrmParamBag.create(
					{
						"title": this._manager.getMessage("dialogTitle"),
						//"apologyTitle": this._manager.getMessage("apologyTitle"),
						"failureTitle": apologies.length > 0 ? this._manager.getMessage("failureTitle") : "",
						"anchor": this._container,
						"success": this._findStepInfoBySemantics("success"),
						"failure": this._findStepInfoBySemantics("failure"),
						"apologies": apologies,
						"callback": BX.delegate(this._onTerminationDialogClose, this),
						"terminationControl": this._terminationControl
					}
				)
			);
			this._terminationDlg.open();

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}

			if(!this._entityConvertHandler)
			{
				this._entityConvertHandler = BX.delegate(this.onEntityConvert, this);
				BX.addCustomEvent(window, "Crm.EntityConverter.Converted", this._entityConvertHandler);
			}
		},
		_closeTerminationDialog: function()
		{
			if(!this._terminationDlg)
			{
				return;
			}

			this._terminationDlg.close(false);
			this._terminationDlg = null;
			this._enableStepHints(true);

			if(this._externalEventHandler)
			{
				BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
				this._externalEventHandler = null;
			}
		},
		_onTerminationDialogClose: function(dialog, params)
		{
			if(this._terminationDlg !== dialog)
			{
				return;
			}

			this._closeTerminationDialog();

			var stepId = BX.type.isNotEmptyString(params["result"]) ? params["result"] : "";

			var index = this._findStepInfoIndex(stepId);
			if(index < 0)
			{
				return;
			}

			this._previousStepId = this._currentStepId;
			this._currentStepId = stepId;

			var openFailureDialog = false;

			var info = this._stepInfos[index];
			var failure = this._findStepInfoBySemantics("failure");

			if(failure && failure["id"] === stepId)
			{
				openFailureDialog = true;
			}
			else if(info["semantics"] === "success")
			{
				if(typeof(info["hasParams"]) !== "undefined" && info["hasParams"] === true)
				{
					openFailureDialog = true;
				}
				else
				{
					var finalScript = this.getSetting("finalScript", "");
					if(finalScript !== "")
					{
						eval(finalScript);
						return;
					}

					var finalUrl = this.getSetting("finalUrl", "");
					if(finalUrl !== "")
					{
						window.location = finalUrl;
						return;
					}
				}
			}

			if(openFailureDialog)
			{
				this._openFailureDialog();
				return;
			}

			this._layout();
			this._save();
		},
		_openFailureDialog: function()
		{
			this._enableStepHints(false);

			if(this._failureDlg)
			{
				this._failureDlg.close();
				this._failureDlg = null;
			}

			var currentStepIndex = this._findStepInfoIndex(this._currentStepId);
			var info = currentStepIndex >= 0 ? this._stepInfos[currentStepIndex] : null;
			var initValue = info ? info["id"] : "";

			var apologies = this._findAllStepInfoBySemantics("apology");
			this._failureDlg = BX.CrmProcessFailureDialog.create(
				(this._id + "_FAILURE"),
				BX.CrmParamBag.create(
					{
						//"title": this._manager.getMessage("dialogTitle"),
						"entityType": this._entityType,
						"entityId": this._entityId,
						"initValue": initValue,
						"failureTitle": apologies.length > 0 ? this._manager.getMessage("failureTitle") : "",
						"selectorTitle": this._manager.getMessage("selectorTitle"),
						"anchor": this._container,
						"success": this._findStepInfoBySemantics("success"),
						"failure": this._findStepInfoBySemantics("failure"),
						"apologies": apologies,
						"callback": BX.delegate(this._onFailureDialogClose, this)
					}
				)
			);
			this._failureDlg.open();

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}
		},
		_closeFailureDialog: function()
		{
			if(!this._failureDlg)
			{
				return;
			}

			this._failureDlg.close(false);
			this._failureDlg = null;
			this._enableStepHints(true);

			if(this._externalEventHandler)
			{
				BX.removeCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
				this._externalEventHandler = null;
			}
		},
		_onFailureDialogClose: function(dialog, params)
		{
			if(this._failureDlg !== dialog)
			{
				return;
			}

			BX.onCustomEvent(this, 'CrmProgressControlBeforeFailureDialogClose', [ this, this._failureDlg ]);
			this._closeFailureDialog();
			var bid = BX.type.isNotEmptyString(params["bid"]) ? params["bid"] : "";
			if(bid !== "accept")
			{
				return;
			}

			var id = BX.type.isNotEmptyString(params["result"]) ? params["result"] : "";
			var index = this._findStepInfoIndex(id);
			if(index >= 0)
			{
				var info = this._stepInfos[index];
				if(info["semantics"] === "success")
				{
					var finalScript = this.getSetting("finalScript", "");
					if(finalScript !== "")
					{
						eval(finalScript);
						return;
					}

					var finalUrl = this.getSetting("finalUrl", "");
					if(finalUrl !== "")
					{
						window.location = finalUrl;
						return;
					}

					var verboseMode = !!this.getSetting("verboseMode", false);
					if(verboseMode)
					{
						//User have to make choice
						this._openTerminationDialog();
						return;
					}
				}
				this._currentStepId = info["id"];

				this._layout();
				this._save();
			}
		},
		_save: function()
		{
			var serviceUrl = this.getSetting("serviceUrl");
			var value = this.getCurrentStepId();
			var type = this.getEntityType();
			var id = this.getEntityId();

			if(serviceUrl === "" || value === "" || type === "" || id <= 0)
			{
				return;
			}
			if (BX.CrmEntityType.isUseDynamicTypeBasedApproachByName(type))
			{
				BX.ajax.runAction(serviceUrl, {
						data: {
							entityTypeId: BX.CrmEntityType.resolveId(type),
							id: id,
							fields: {
								stageId: this.getCurrentStepId(),
							},
						}
					})
					.then(function(response) {
						this._onSaveRequestSuccess(response.data);
					}.bind(this))
					.catch(function(response) {
						var isRequiredFieldsError = false;
						var checkErrors = {};
						var errorMessage = '';
						for (var i in response.errors)
						{
							if (response.errors[i].code === 'CRM_FIELD_ERROR_REQUIRED')
							{
								checkErrors[response.errors[i].customData.fieldName] = response.errors[i].message;
								isRequiredFieldsError = true;
							}
							if (errorMessage === '')
							{
								errorMessage = response.errors[i].message;
							}
							else
							{
								errorMessage = errorMessage + ', ' + response.errors[i].message;
							}
						}

						var errors = {
							'CHECK_ERRORS': checkErrors,
							'ERROR': errorMessage,
							'ID': this.getEntityId(),
							'TYPE': this.getEntityType(),
							'VALUE': this.getCurrentStepId(),
						};

						if (isRequiredFieldsError)
						{
							this._onSaveRequestSuccess(errors);
						}
						else
						{
							console.log(errorMessage);
							this._onSaveRequestFailure(errors);
						}
					}.bind(this));
			}
			else
			{
				var data =
					{
						"ACTION" : "SAVE_PROGRESS",
						"VALUE": value,
						"TYPE": type,
						"ID": id,
						sessid: BX.bitrix_sessid(),
					};
				BX.onCustomEvent(this, 'CrmProgressControlBeforeSave', [ this, data ]);

				BX.ajax(
					{
						"url": serviceUrl,
						"method": "POST",
						"dataType": 'json',
						"data": data,
						"onsuccess": BX.delegate(this._onSaveRequestSuccess, this),
						"onfailure": BX.delegate(this._onSaveRequestFailure, this)
					}
				);
			}
		},
		_onSaveRequestSuccess: function(data)
		{
			var checkErrors = BX.prop.getObject(data, "CHECK_ERRORS", null);
			if(checkErrors)
			{
				this._openEntityEditorDialog(
					{
						title: this._manager.getMessage("checkErrorTitle"),
						helpData: { text: this._manager.getMessage("checkErrorHelp"),
						code: this._manager.getMessage("checkErrorHelpArticleCode") },
						fieldNames: Object.keys(checkErrors),
						initData: BX.prop.getObject(data, "EDITOR_INIT_DATA", null),
						context: BX.prop.getObject(data, "CONTEXT", null),
						isController: BX.CrmEntityType.isUseFactoryBasedApproachByName(this.getEntityType()),
						stageId: BX.prop.getString(data, 'VALUE', null)
					}
				);
				return;
			}

			BX.onCustomEvent(this, 'CrmProgressControlAfterSaveSucces', [ this, data ]);
			BX.CrmProgressControl._synchronize(this);
		},
		_onSaveRequestFailure: function(data)
		{
			BX.onCustomEvent(self, 'CrmProgressControlAfterSaveFailed', [ this, data ]);
		},
		_openEntityEditorDialog: function(params)
		{
			BX.Crm.PartialEditorDialog.close("progressbar-entity-editor");

			this._entityEditorDialog = BX.Crm.PartialEditorDialog.create(
				"progressbar-entity-editor",
				{
					title: BX.prop.getString(params, "title", "Please fill in all required fields"),
					entityTypeName: this._entityType,
					entityId: this._entityId,
					fieldNames: BX.prop.getArray(params, "fieldNames", []),
					helpData: BX.prop.getObject(params, "helpData", null),
					context: BX.prop.getObject(params, "context", null),
					isController: BX.prop.getBoolean(params, 'isController', false),
					stageId: BX.prop.getString(params, 'stageId', null)
				}
			);

			window.setTimeout(
				function()
				{
					this._entityEditorDialog.open();
					BX.addCustomEvent(window, "Crm.PartialEditorDialog.Close", this._entityEditorDialogHandler);
				}.bind(this),
				150
			);
		},
		_onEntityEditorDialogClose: function(sender, eventParams)
		{
			if(!(this._entityType === BX.prop.getString(eventParams, "entityTypeName", 0)
				&& this._entityId === BX.prop.getInteger(eventParams, "entityId", 0))
			)
			{
				return;
			}

			this._entityEditorDialog = null;
			BX.removeCustomEvent(window, "Crm.PartialEditorDialog.Close", this._entityEditorDialogHandler);

			if(BX.prop.getBoolean(eventParams, "isCancelled", true) && this._previousStepId !== "")
			{
				//Rollback current step
				var previousStepIndex = this._findStepInfoIndex(this._previousStepId);
				if(previousStepIndex >= 0)
				{
					this._currentStepId = this._previousStepId;
					this._previousStepId = "";
					this._layout();
				}
			}
		},
		_findStepInfoBySemantics: function(semantics)
		{
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				var info = infos[i];
				var s = BX.type.isNotEmptyString(info["semantics"]) ? info["semantics"] : '';
				if(semantics === s)
				{
					return info;
				}
			}

			return null;
		},
		_findAllStepInfoBySemantics: function(semantics)
		{
			var result = [];
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				var info = infos[i];
				var s = BX.type.isNotEmptyString(info["semantics"]) ? info["semantics"] : '';
				if(semantics === s)
				{
					result.push(info);
				}
			}

			return result;
		},
		_findStepInfoIndex: function(id)
		{
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				if(infos[i]["id"] === id)
				{
					return i;
				}
			}

			return -1;
		},
		_enableStepHints: function(enable)
		{
			for(var i = 0; i < this._steps.length; i++)
			{
				this._steps[i].enableHint(enable);
			}
		}
	};

	BX.CrmProgressControl.items = {};
	BX.CrmProgressControl.create = function(id, settings)
	{
		var self = new BX.CrmProgressControl();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
	BX.CrmProgressControl._synchronize = function(item)
	{
		var type = item.getEntityType();
		var id = item.getEntityId();

		for(var itemId in this.items)
		{
			if(!this.items.hasOwnProperty(itemId))
			{
				continue;
			}

			var curItem = this.items[itemId];
			if(curItem === item)
			{
				continue;
			}

			if(curItem.getEntityType() === type && curItem.getEntityId() === id)
			{
				curItem.setCurrentStepId(item.getCurrentStepId());
			}
		}
	}
}

if(typeof(BX.CrmProgressStep) === "undefined")
{
	BX.CrmProgressStep = function()
	{
		this._id = "";
		this._settings = null;
		this._control = null;
		this._container = null;
		this._name = "";
		this._hint = "";
		this._isPassed = false;
		this._isReadOnly = false;
		this._enableHint = true;
		this._hintPopup = null;
		this._hintPopupTimeoutId = null;
	};

	BX.CrmProgressStep.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._control = this.getSetting("control");
			this._container = this._control.getStepContainer(this._id);
			this._name = this.getSetting("name");
			this._hint = this.getSetting("hint", "");
			this._isPassed = this.getSetting("isPassed", false);
			this._isReadOnly = this.getSetting("isReadOnly", false);

			if(!this._isReadOnly)
			{
				BX.bind(this._container, "mouseover", BX.delegate(this._onMouseOver, this));
				BX.bind(this._container, "mouseout", BX.delegate(this._onMouseOut, this));
				BX.bind(this._container, "click", BX.delegate(this._onClick, this));
			}
		},
		getId: function()
		{
			return this._id;
		},
		getName: function()
		{
			return this._name;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		isPassed: function()
		{
			return this._isPassed;
		},
		setPassed: function(passed)
		{
			passed = !!passed;
			if(this._isPassed === passed)
			{
				return;
			}

			this._isPassed = passed;

			if(!this._control.isCustomColorsEnabled())
			{
				var wrapper = BX.findParent(this._container, { "class": "crm-list-stage-bar-part" });
				if(passed)
				{
					BX.addClass(wrapper, "crm-list-stage-passed");
				}
				else
				{
					BX.removeClass(wrapper, "crm-list-stage-passed");
				}
			}
		},
		isReadOnly: function()
		{
			return this._isReadOnly;
		},
		isHintEnabled: function()
		{
			return this._enableHint;
		},
		enableHint: function(enable)
		{
			enable = !!enable;
			if(this._enableHint === enable)
			{
				return;
			}

			this._enableHint = enable;
			if(!enable)
			{
				this.hideStepHint();
			}
		},
		displayStepHint: function(step)
		{
			if(!this._enableHint || this._hintPopup)
			{
				return;
			}

			var pos = BX.pos(this._container);
			this._hintPopup = BX.PopupWindowManager.create(
				"step-hint-" + this._id,
				step,
				{
					"angle": {
						"position": "bottom",
						"offset": 0
					},
					"offsetLeft": pos["width"] / 2,
					"offsetTop": 5,
					"content": BX.create(
						"SPAN",
						{
							"attrs": { "class": "crm-list-bar-popup-text" },
							"text": this._hint !== '' ? this._hint : this._name
						}
					),
					"className": "crm-list-bar-popup-table"
				}
			);
			this._hintPopup.show();
		},
		hideStepHint: function()
		{
			if(!this._hintPopup)
			{
				return;
			}

			this._hintPopup.close();
			this._hintPopup.destroy();
			this._hintPopup = null;
		},
		_onClick: function(e)
		{
			if(!this._isReadOnly)
			{
				this._control.setCurrentStep(this);
			}
		},
		_onMouseOver: function(e)
		{
			if(this._hintPopupTimeoutId !== null)
			{
				window.clearTimeout(this._hintPopupTimeoutId);
			}

			e = e || window.event;
			var target = e.target || e.srcElement;
			var self = this;
			this._hintPopupTimeoutId = window.setTimeout(function(){ self._hintPopupTimeoutId = null; self.displayStepHint(target); }, 300 );
		},
		_onMouseOut: function(e)
		{
			if(this._hintPopupTimeoutId !== null)
			{
				window.clearTimeout(this._hintPopupTimeoutId);
			}

			if(!this._enableHint)
			{
				return;
			}

			var self = this;
			this._hintPopupTimeoutId = window.setTimeout(function(){ self._hintPopupTimeoutId = null; self.hideStepHint(); }, 300 );
		}
	};

	BX.CrmProgressStep.create = function(id, settings)
	{
		var self = new BX.CrmProgressStep();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmProcessTerminationDialog) === "undefined")
{
	BX.CrmProcessTerminationDialog = function()
	{
		this._id = "";
		this._settings = null;
		this._terminationControl = null;
		this._popup = null;
		this._wrapper = null;
		this._openNotifier = null;
		this._closeNotifier = null;
		this._result = "";
		this._enableCallback = true;
	};

	BX.CrmProcessTerminationDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._terminationControl = this.getSetting("terminationControl", null);
			this._openNotifier = BX.CrmNotifier.create(this);
			this._closeNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getId: function()
		{
			return this._id;
		},
		getResult: function()
		{
			return this._result;
		},
		open: function()
		{
			if(!this._popup)
			{
				this._popup = new BX.PopupWindow(
					this._id,
					this.getSetting("anchor"),
					{
						"closeByEsc": true,
						"autoHide": false,
						"offsetLeft": -50,
						"closeIcon": true,
						"className": "crm-list-end-deal",
						"content": this._prepareContent(),
						"events":
							{
								"onPopupShow": BX.delegate(this._onPopupShow, this),
								"onPopupClose": BX.delegate(this._onPopupClose, this)
							}
					}
				);
			}
			this._popup.show();
		},
		addOpenListener: function(listener)
		{
			this._openNotifier.addListener(listener);
		},
		removeOpenListener: function(listener)
		{
			this._openNotifier.removeListener(listener);
		},
		close: function(enableCallback)
		{
			this._enableCallback = !!enableCallback;
			if(this._popup)
			{
				this._popup.close();
			}
		},
		addCloseListener: function(listener)
		{
			this._closeNotifier.addListener(listener);
		},
		removeCloseListener: function(listener)
		{
			this._closeNotifier.removeListener(listener);
		},
		_onPopupShow: function()
		{
			this._openNotifier.notify();
		},
		_onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
				this._popup = null;
			}

			this._closeNotifier.notify();
			this._executeCallback();
		},
		_prepareContent: function()
		{
			this._wrapper = BX.create("DIV");
			var table = BX.create("TABLE",
				{
					attrs: { className: "crm-list-end-deal-block" },
					props: { cellSpacing: "0", cellPadding: "0", border: "0" }
				}
			);
			this._wrapper.appendChild(table);

			var cell = table.insertRow(-1).insertCell(-1);
			cell.className = "crm-list-end-deal-text";
			cell.innerHTML = this.getSetting("title", "");

			cell = table.insertRow(-1).insertCell(-1);
			cell.className = "crm-list-end-deal-buttons-block";

			var controls = this._terminationControl !== null
				? this._terminationControl.prepareDialogControls(this)
				: null;

			if(!BX.type.isPlainObject(controls))
			{
				controls = {};
			}

			if(BX.type.isElementNode(controls["successButton"]))
			{
				cell.appendChild(controls["successButton"]);
			}
			else
			{
				var success = this.getSetting("success");
				if(success)
				{
					var successText = BX.type.isNotEmptyString(success["name"]) ? success["name"] : "Success";
					var successButton = BX.create(
						"A",
						{
							attrs: { className: "webform-small-button webform-small-button-accept", href: "#" },
							children:
							[
								BX.create("SPAN", { attrs: { className: "webform-small-button-left" } }),
								BX.create("SPAN", { attrs: { className: "webform-small-button-text" }, text: successText }),
								BX.create("SPAN", { attrs: { className: "webform-small-button-right" } })
							]
						}
					);
					cell.appendChild(successButton);
					var successId = BX.type.isNotEmptyString(success["id"]) ? success["id"] : "success";
					BX.CrmSubscriber.subscribe(
						this.getId() + "_" + successId,
						successButton, "click", BX.delegate(this._onButtonClick, this),
						BX.CrmParamBag.create({ id: successId, preventDefault: true })
					);
				}
			}

			if(BX.type.isElementNode(controls["failureButton"]))
			{
				cell.appendChild(controls["failureButton"]);
			}
			else
			{
				var failure = this.getSetting("failure");
				if(failure)
				{
					// Check if custom failure text is defined
					var failureTitle = this.getSetting("failureTitle", "");
					if(failureTitle === "")
					{
						failureTitle = BX.type.isNotEmptyString(failure["name"]) ? failure["name"] : "Failure";
					}
					var failureButton = BX.create(
						"A",
						{
							attrs: { className: "webform-small-button webform-small-button-decline", href: "#" },
							children:
							[
								BX.create("SPAN", { attrs: { className: "webform-small-button-left" } }),
								BX.create("SPAN", { attrs: { className: "webform-small-button-text" }, text: failureTitle }),
								BX.create("SPAN", { attrs: { className: "webform-small-button-right" } })
							]
						}
					);
					cell.appendChild(failureButton);
					var failureId = BX.type.isNotEmptyString(failure["id"]) ? failure["id"] : "failure";
					BX.CrmSubscriber.subscribe(
						this.getId() + '_' + failureId,
						failureButton, "click", BX.delegate(this._onButtonClick, this),
						BX.CrmParamBag.create({ id: failureId, preventDefault: true })
					);
				}
			}
			return this._wrapper;
		},
		_onButtonClick: function(subscriber, params)
		{
			this._result = subscriber.getSetting("id", "");
			this._executeCallback();
		},
		_executeCallback: function()
		{
			if(this._enableCallback)
			{
				var callback = this.getSetting("callback");
				if(BX.type.isFunction(callback))
				{
					callback(this, { "result": this._result });
				}
			}
		}
	};

	BX.CrmProcessTerminationDialog.create = function(id, settings)
	{
		var self = new BX.CrmProcessTerminationDialog();
		self.initialize(id, settings);
		return self;
	}
}

if(typeof(BX.CrmProcessFailureDialog) === "undefined")
{
	BX.CrmProcessFailureDialog = function()
	{
		this._id = "";
		this._settings = null;
		this._popup = null;
		this._wrapper = null;
		this._callback = null;
		this._enableCallback = true;
		this._value = "";
		this._bid = "";
		this._successInfo = null;
		this._failureInfo = null;
		this._apologyInfos = null;
		this._failureTitle = "";
		this._selectorTitle = "";
		this._radioButtonBlock = null;
		this._popupMenuId = "";
		this._popupMenu = null;
	};

	BX.CrmProcessFailureDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._callback = this.getSetting("callback", null);
			this._successInfo = this.getSetting("success", null);
			if(!this._successInfo)
			{
				throw "BX.CrmProcessFailureDialog: 'success' setting is not found!";
			}

			this._failureInfo = this.getSetting("failure", null);
			if(!this._failureInfo)
			{
				throw "BX.CrmProcessFailureDialog: 'failure' setting is not found!";
			}

			this._apologyInfos = this.getSetting("apologies", null);
			if(!BX.type.isArray(this._apologyInfos))
			{
				this._apologyInfos = [];
			}

			// Try to setup initial value
			var initValue = this.getSetting("initValue", "");
			if(initValue === "")
			{
				initValue = this._failureInfo["id"];
			}

			this._value = initValue;

			this._failureTitle = this.getSetting("failureTitle", "");
			this._selectorTitle = this.getSetting("selectorTitle", "");

			this._popupMenuId = this._id + "_MENU";
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getEntityType: function()
		{
			return this.getSetting("entityType");
		},
		getEntityId: function()
		{
			return this.getSetting("entityId");
		},
		getId: function()
		{
			return this._id;
		},
		getValue: function()
		{
			return this._value;
		},
		setValue: function(val, refresh)
		{
			if(this._value === val)
			{
				return;
			}

			this._value = val;

			if(typeof(refresh) === "undefined")
			{
				// Setup by default
				refresh = true;
			}
			else
			{
				refresh = !!refresh;
			}

			if(refresh)
			{
				var buttons = BX.findChildren(
					this._wrapper,
					{
						"className": "crm-list-fail-deal-button"
					},
					true
				);

				for(var i = 0; i < buttons.length; i++)
				{
					var button = buttons[i];
					button.checked = button.value === val;
				}
			}

			BX.onCustomEvent(this, 'CrmProcessFailureDialogValueChanged', [ this, val ]);
		},
		getSuccessValue: function()
		{
			return this._successInfo["id"];
		},
		getBid: function()
		{
			return this._bid;
		},
		getWrapper: function()
		{
			return this._wrapper;
		},
		open: function()
		{
			if(this._popup)
			{
				this._popup.show();
				return;
			}

			this._popup = new BX.PopupWindow(
				this._id,
				this.getSetting("anchor"),
				{
					closeByEsc: true,
					autoHide: true,
					offsetLeft: -50,
					closeIcon: true,
					className: "crm-list-fail-deal",
					titleBar: { content: this._prepareTitle() },
					content: this._prepareContent(),
					events: { onPopupClose: BX.delegate(this._onPopupClose, this) },
					buttons:
					[
						new BX.PopupWindowButton(
							{
								text: BX.message["JS_CORE_WINDOW_SAVE"],
								className: "popup-window-button-accept",
								events: { click: BX.delegate(this._onAcceptButtonClick, this) }
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text: BX.message["JS_CORE_WINDOW_CANCEL"],
								className: "popup-window-button-link-cancel",
								events: { click: BX.delegate(this._onCancelButtonClick, this) }
							}
						)
					]
				}
			);
			this._popup.show();
		},
		close: function(enableCallback)
		{
			this._enableCallback = !!enableCallback;
			if(this._popup)
			{
				this._popup.close();
			}
		},
		getFailureTitle: function()
		{
			var result = this._failureTitle;
			if(result == "")
			{
				result = BX.type.isNotEmptyString(this._failureInfo["name"])
					? this._failureInfo["name"] : this._failureInfo["id"];
			}

			return result;
		},
		getSuccessTitle: function()
		{
			return BX.type.isNotEmptyString(this._successInfo["name"])
					? this._successInfo["name"] : this._successInfo["id"];
		},
		_onPopupClose: function()
		{
			this._closePopupMenu();

			if(this._popup)
			{
				this._popup.destroy();
				this._popup = null;
			}

			this._executeCallback();
		},
		_closePopupMenu: function()
		{
			if(this._popupMenu)
			{
				BX.PopupMenu.Data[this._popupMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._popupMenuId];
				this._popupMenu = null;
			}
		},
		_prepareTitle: function()
		{
			var wrapper = BX.create("DIV", { "attrs": { "class": "crm-list-fail-deal-selector-block" } });

			wrapper.appendChild(
				BX.create("SPAN", { "text": this._selectorTitle + ": " })
			);

			var isSuccess = this._value === this._successInfo["id"];
			this._selector = BX.create(
				"DIV",
				{
					attrs: { className: "crm-list-end-deal-option crm-list-end-deal-option-" + (isSuccess ? "success" : "fail") },
					events: { click: BX.delegate(this._onSelectorClick, this) },
					text: isSuccess ? this.getSuccessTitle() : this.getFailureTitle()
				}
			);

			wrapper.appendChild(this._selector);
			return wrapper;
		},
		_prepareContent: function()
		{
			var wrapper = this._wrapper = BX.create("DIV", { attrs: { className: "crm-list-fail-deal-block" } });
			var title = this.getSetting("title", "");
			if(title !== "")
			{
				wrapper.appendChild(BX.create("DIV", { attrs: { className: "crm-list-end-deal-text" }, text: title }));
			}

			this._radioButtonBlock = BX.create("DIV", { attrs: { className: "crm-list-end-deal-block-section" } });

			var infos = [ this._failureInfo ];
			var apologies = this._apologyInfos;
			if(BX.type.isArray(apologies) && apologies.length >0)
			{
				for(var i = 0; i < apologies.length; i++)
				{
					infos.push(apologies[i]);
				}
			}

			for(var j = 0; j < infos.length; j++)
			{
				var radioButtonWrapper = BX.create("DIV", { attrs: { className: "crm-list-end-deal-button-wrapper" } });

				var info = infos[j];
				var curInfoId = info["id"];
				var buttonId = this._id + '_' + curInfoId;
				var button = BX.create(
					"INPUT",
					{
						attrs:
						{
							id: buttonId,
							name: this._id,
							className: "crm-list-fail-deal-button",
							type: "radio",
							value: info["id"]
						}
					}
				);

				button.checked = this._value === curInfoId;

				BX.CrmSubscriber.subscribe(
					this._id + "_" + curInfoId,
					button, "change", BX.delegate(this._onRadioButtonClick, this),
					BX.CrmParamBag.create({ "id": curInfoId })
				);


				radioButtonWrapper.appendChild(button);
				radioButtonWrapper.appendChild(
					BX.create(
						"LABEL",
						{
							attrs:
							{
								className: "crm-list-fail-deal-button-label",
								"for": buttonId
							},
							text: BX.type.isNotEmptyString(info["name"]) ? info["name"] : curInfoId
						}
					)
				);

				this._radioButtonBlock.appendChild(radioButtonWrapper);
			}

			if(this._value === this._successInfo["id"] || apologies.length === 0)
			{
				this._radioButtonBlock.style.display = "none";
			}

			wrapper.appendChild(this._radioButtonBlock);
			BX.onCustomEvent(this, 'CrmProcessFailureDialogContentCreated', [ this, wrapper ]);
			return wrapper;
		},
		_onRadioButtonClick: function(subscriber, params)
		{
			this.setValue(subscriber.getSetting("id", ""), false);
		},
		_onAcceptButtonClick: function(e)
		{
			this._bid = "accept";
			this._executeCallback();
		},
		_onCancelButtonClick: function(e)
		{
			this._bid = "cancel";
			this._value = "";
			this._executeCallback();
		},
		_onSelectorClick: function()
		{
			if(this._popupMenu)
			{
				this._closePopupMenu();
				return;
			}

			if(typeof(BX.PopupMenu.Data[this._popupMenuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._popupMenuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._popupMenuId];
			}

			BX.PopupMenu.show(
				this._popupMenuId,
				this._selector,
				[
					{
						text: this.getFailureTitle(),
						onclick: function()
							{
								this.setValue(this._failureInfo["id"], true);
								if(this._radioButtonBlock.style.display === "none" && this._apologyInfos.length > 0)
								{
									this._radioButtonBlock.style.display = "";
								}
								this._selector.innerHTML = BX.util.htmlspecialchars(this.getFailureTitle());
								BX.removeClass(this._selector, "crm-list-end-deal-option-success");
								BX.addClass(this._selector, "crm-list-end-deal-option-fail");

								window.setTimeout(function(){ this._closePopupMenu(); }.bind(this), 0);
							}.bind(this)
					},
					{
						text: this.getSuccessTitle(),
						onclick: function()
							{
								this.setValue(this._successInfo["id"], true);
								if(this._radioButtonBlock.style.display !== "none")
								{
									this._radioButtonBlock.style.display = "none";
								}
								this._selector.innerHTML = BX.util.htmlspecialchars(this.getSuccessTitle());
								BX.removeClass(this._selector, "crm-list-end-deal-option-fail");
								BX.addClass(this._selector, "crm-list-end-deal-option-success");

								window.setTimeout(function(){ this._closePopupMenu(); }.bind(this), 0);
							}.bind(this)
					}
				],
				{
					autoHide: true,
					offsetTop:0,
					offsetLeft:-30
				}
			);

			this._popupMenu = BX.PopupMenu.Data[this._popupMenuId];
		},
		_executeCallback: function()
		{
			if(this._enableCallback)
			{
				var callback = this._callback;
				if(BX.type.isFunction(callback))
				{
					callback(this, { "bid": this._bid, "result": this._value });
				}
			}
		}
	};

	BX.CrmProcessFailureDialog.create = function(id, settings)
	{
		var self = new BX.CrmProcessFailureDialog();
		self.initialize(id, settings);
		return self;
	}
}
