/* eslint-disable */
BX.namespace("BX.Crm");
if(typeof BX.Crm.EntityDetailProgressControl === "undefined")
{
	BX.Crm.EntityDetailProgressControl = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._entityId = 0;
		this._entityTypeId = 0;
		this._stepInfoTypeId = "";
		this._currentStepId = "";
		this._previousStepId = "";
		this._currentSemantics = "";
		this._previousSemantics = "";
		this._manager = null;
		this._stepInfos = null;
		this._steps = [];
		this._terminationDlg = null;
		this._failureDlg = null;
		this._isReadOnly = false;
		this._terminationControl = null;
		this._permissionChecker = null;
		this._beforeFailureDialogOpenStepId = null;

		this._entityEditorDialog = null;
		this._entityEditorDialogHandler = BX.delegate(this.onEntityEditorDialogClose, this);
		this._analyticsData = null;
	};
	BX.Crm.EntityDetailProgressControl.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._container = BX(BX.prop.getString(this._settings, "containerId", ""));
				this._entityId = BX.prop.getNumber(this._settings, "entityId", 0);
				this._entityTypeId = BX.prop.getNumber(this._settings, "entityTypeId", 0);
				this._entityType = BX.CrmEntityType.resolveName(this._entityTypeId);
				this._currentStepId = BX.prop.getString(this._settings, "currentStepId", "");
				this._currentSemantics = BX.prop.getString(this._settings, "currentSemantics", "");
				this._stepInfoTypeId = BX.prop.getString(this._settings, "stepInfoTypeId", "");

				this._isReadOnly = BX.prop.getBoolean(this._settings, "readOnly", false);

				if(this._entityTypeId === BX.CrmEntityType.enumeration.deal)
				{
					this._manager = BX.CrmDealStageManager.current;
				}
				else if(this._entityTypeId === BX.CrmEntityType.enumeration.dealrecurring)
				{
					this._manager = BX.CrmDealRecurringStageManager.current;
				}
				if(this._entityTypeId === BX.CrmEntityType.enumeration.quote)
				{
					this._manager = BX.CrmQuoteStatusManager.current;
				}
				else if(this._entityTypeId === BX.CrmEntityType.enumeration.lead)
				{
					this._manager = BX.CrmLeadStatusManager.current;
					this._terminationControl = BX.CrmLeadTerminationControl.create(
						this._id,
						BX.CrmParamBag.create(
							{
								entityId: this._entityId,
								typeId : BX.prop.getInteger(settings, "conversionTypeId", BX.CrmLeadConversionType.general),
								canConvert: BX.prop.getBoolean(settings, "canConvert", false),
								conversionScheme: BX.prop.get(settings, "conversionScheme", null),
								converterId: BX.prop.getString(settings, 'converterId', null),
								permissionCheckCallback: this.isHasPermissionToMoveSuccessStage.bind(this),
								showPermissionErrorCallback: this.showMissPermissionError.bind(this),
							}
						)
					);
				}
				else if(this._entityTypeId === BX.CrmEntityType.enumeration.order)
				{
					this._manager = BX.CrmOrderStatusManager.current;
				}
				else if(this._entityTypeId === BX.CrmEntityType.enumeration.ordershipment)
				{
					this._manager = BX.CrmOrderShipmentStatusManager.current;
				}

				this._stepInfos = this._manager.getInfos(this._stepInfoTypeId);
				var currentStepIndex = this.findStepInfoIndex(this._currentStepId);
				var currentStepInfo = this._stepInfos[currentStepIndex];

				for(var i = 0, l = this._stepInfos.length; i < l; i++)
				{
					var info = this._stepInfos[i];
					var stepId = BX.prop.getString(info, "id", "");
					var stepContainer = this.getStepContainer(stepId);
					if(!stepContainer)
					{
						continue;
					}

					var stepContainerText = stepContainer.querySelector(".crm-entity-section-status-step-item-text");
					if (stepContainerText.scrollWidth > (stepContainerText.clientWidth))
					{
						BX.addClass(stepContainer, "crm-entity-section-status-step-hover");
						stepContainer.style.maxWidth = stepContainerText.scrollWidth + 23 + "px"
					}

					const step = BX.Crm.EntityDetailProgressStep.create(
						stepId,
						{
							name: info["name"],
							hint: BX.prop.getString(info, "hint", ""),
							sort: BX.prop.getNumber(info, "sort", 0),
							semantics: BX.prop.getString(info, "semantics", ""),
							index: i,
							isPassed: currentStepIndex >= 0 && i <= currentStepIndex,
							isReadOnly: this._isReadOnly,
							isVisible: stepContainer.style.display !== "none",
							container: stepContainer,
							control: this
						}
					);

					this._steps.push(step);
				}

				if (currentStepIndex >= 0)
				{
					this.adjustStepsByCurrentStep();
					this.adjustStepDisabling();
				}

				BX.addCustomEvent(window, 'Crm.EntityModel.Change', this.onEntityModelChange.bind(this));
				BX.addCustomEvent(window, 'BX.Crm.EntityEditor:onEntityReload', this.onEntityReload.bind(this));
			},
			onEntityReload(event)
			{
				const { entityId, entityTypeName, entityData } = event;
				const { entityFieldName: stepIdFieldName } = this.getSettings();

				if (
					this.getEntityId() !== entityId
					|| this.getEntityTypeName() !== entityTypeName
					|| stepIdFieldName === ''
				)
				{
					return;
				}

				const stepId = entityData[stepIdFieldName];
				if (stepId === this.getCurrentStepId())
				{
					return;
				}

				this.setCurrentStepByIdAndAdjustSteps(stepId);
			},
			onEntityModelChange(sender, eventArgs)
			{
				if (
					BX.prop.getInteger(eventArgs, 'entityTypeId', 0) !== this.getEntityTypeId()
					|| BX.prop.getInteger(eventArgs, 'entityId', 0) !== this.getEntityId()
				)
				{
					return;
				}

				const { entityFieldName: stepIdFieldName } = this.getSettings();
				if (stepIdFieldName === '')
				{
					return;
				}

				if (
					!BX.prop.getBoolean(eventArgs, 'forAll', false)
					&& stepIdFieldName !== BX.prop.getString(eventArgs, 'fieldName', '')
				)
				{
					return;
				}

				const currentStepId = sender.getField(stepIdFieldName, '');
				if (currentStepId === this.getCurrentStepId())
				{
					return;
				}

				this.setCurrentStepByIdAndAdjustSteps(currentStepId);
			},
			getPermissionChecker()
			{
				if (this._permissionChecker === null)
				{
					const stepInfos = this._stepInfos ?? this._manager.getInfos(this._stepInfoTypeId);
					this._permissionChecker = BX.Crm.Stage.PermissionChecker.createFromStageInfos(stepInfos);
				}

				return this._permissionChecker;
			},
			isHasPermissionToMove(stepId)
			{
				return this.getPermissionChecker().isHasPermissionToMove(this.getCurrentStepId(), stepId);
			},
			isHasPermissionToMoveTerminationStages()
			{
				return this.isHasPermissionToMoveSuccessStage() || this.isHasPermissionToMoveFailureStages();
			},
			isHasPermissionToMoveFailureStages()
			{
				return this.getPermissionChecker().isHasPermissionToMoveAtLeastOneFailureStage(this.getCurrentStepId());
			},
			isHasPermissionToMoveSuccessStage()
			{
				const canConvert = !this._terminationControl || this._terminationControl.isEnabled();

				return canConvert && this.getPermissionChecker()
					.isHasPermissionToMoveSuccessStage(this.getCurrentStepId())
				;
			},
			getTerminationSemantics()
			{
				return ['success', 'failure'];
			},
			isTerminationSemantics(semantics)
			{
				return this.getTerminationSemantics().includes(semantics);
			},
			showMissPermissionError()
			{
				this.getPermissionChecker().showMissPermissionError();
			},
			getSettings()
			{
				return this._settings;
			},
			getEntityId: function()
			{
				return this._entityId;
			},
			getEntityTypeId: function()
			{
				return this._entityTypeId;
			},
			getEntityTypeName: function()
			{
				return BX.CrmEntityType.resolveName(this._entityTypeId);
			},
			getCurrentStepId: function()
			{
				return this._currentStepId;
			},
			getCurrentStepName: function()
			{
				var index = this.findStepInfoIndex(this._currentStepId);
				return index >= 0 ? this._stepInfos[index]["name"] : ("[" + this._currentStepId + "]");
			},
			getCurrentSemantics: function()
			{
				return this._currentSemantics;
			},
			getStepContainer: function(id)
			{
				return this._container.querySelector('.crm-entity-section-status-step[data-id="'+ id +'"]');
			},
			getTerminationStep: function()
			{
				return this._steps.length > 0 ? this._steps[this._steps.length - 1] : null;
			},
			getStepInfo(stepIndex)
			{
				return this._stepInfos[stepIndex];
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
			findStepInfoIndex: function(id)
			{
				for(var i = 0, l = this._stepInfos.length; i < l; i++)
				{
					if(this._stepInfos[i]["id"] === id)
					{
						return i;
					}
				}

				return -1;
			},
			isStepDisable(id)
			{
				const step = this.getStepById(id);
				if (!step)
				{
					return false;
				}

				if (this.isTerminationSemantics(step.getSemantics()))
				{
					return !this.isHasPermissionToMoveTerminationStages();
				}

				return !this.isHasPermissionToMove(step.getId());
			},
			findStepInfoBySemantics: function(semantics)
			{
				for(var i = 0, l = this._stepInfos.length; i < l; i++)
				{
					var info = this._stepInfos[i];
					var s = BX.type.isNotEmptyString(info["semantics"]) ? info["semantics"] : '';
					if(semantics === s)
					{
						return info;
					}
				}
				return null;
			},
			findAllStepInfoBySemantics: function(semantics)
			{
				var result = [];
				for(var i = 0, l = this._stepInfos.length; i < l; i++)
				{
					var info = this._stepInfos[i];
					var s = BX.prop.getString(info, "semantics", "");
					if(semantics === s)
					{
						result.push(info);
					}
				}

				return result;
			},
			setCurrentStep: function(stepInfo, options)
			{
				const stepId = stepInfo.id;
				if(this._currentStepId === stepId)
				{
					return false;
				}

				if(BX.prop.getBoolean(options, 'keepPreviousStep', true))
				{
					this._previousStepId = this._currentStepId;
					this._previousSemantics = this._currentSemantics;
				}

				this._currentStepId = stepId;

				var semantics = stepInfo["semantics"];
				if(this._currentSemantics !== semantics)
				{
					this._currentSemantics = semantics;
				}

				this.adjustStepsVisibility();
				this.adjustFinalStepName();
				this.adjustStepDisabling();

				BX.onCustomEvent(
					window,
					"Crm.EntityProgress.Change",
					[
						this,
						{
							entityTypeId: this._entityTypeId,
							entityId: this._entityId,
							currentStepId: this._currentStepId,
							semantics: this._currentSemantics
						}
					]
				);

				return true;
			},
			setCurrentStepByIdAndAdjustSteps(id)
			{
				const stepIndex = this.findStepInfoIndex(id);
				if (stepIndex < 0)
				{
					return;
				}

				const stepInfo = this.getStepInfo(stepIndex);
				const stepColor = BX.Crm.EntityDetailProgressControl.getStepColor(stepInfo);

				this.setCurrentStep(stepInfo);
				this.adjustSteps(stepIndex, stepColor);
			},
			adjustFinalStepName: function()
			{
				var info = this.findStepInfoBySemantics("success");
				if(info)
				{
					var index = this.findStepInfoIndex(info["id"]);
					if(index >= 0)
					{
						this._steps[index].setDisplayName(this._currentSemantics === "process"
							? BX.prop.getString(this._settings, "terminationTitle", "") : ""
						);
					}
				}
			},
			adjustStepsVisibility: function()
			{
				for(var i = 0, l = this._steps.length; i < l; i++)
				{
					var step = this._steps[i];
					var isVisible = true;
					var semantics  = step.getSemantics();
					if(this._currentSemantics === "process" || this._currentSemantics === "success")
					{
						isVisible = (semantics === "process" || semantics === "success");
					}
					else //if(this._currentSemantics === "failure" || this._currentSemantics === "apology")
					{
						if(semantics === "success")
						{
							isVisible = false;
						}
						else if(semantics === "failure" || semantics === "apology")
						{
							isVisible = step.getId() === this._currentStepId;
						}
					}
					step.setVisible(isVisible);
				}
			},
			adjustSteps: function(index, baseColor)
			{
				if(index >= this._steps.length)
				{
					index = (this._steps.length - 1);
				}

				let i, l;
				for (i = index, l = this._steps.length; i < l; i++)
				{
					this._steps[i].recoverColors();
					this._steps[i].saveStyles();
				}

				let textColor = BX.Crm.EntityDetailProgressStep.calculateTextColor(baseColor);
				for (i = 0, l = index; i <= l; i++)
				{
					this._steps[i].setColor(textColor);
					this._steps[i].setBackgroundColor(baseColor);
				}

				for (i = 0, l = index; i <= l; i++)
				{
					this._steps[i].saveStyles();
				}
			},
			adjustStepDisabling: function()
			{
				for (let i = 0, l = this._steps.length; i < l; i++)
				{
					if (this.isStepDisable(this._steps[i].getId()))
					{
						this._steps[i].setDisabled();
					}
					else
					{
						this._steps[i].removeDisabled();
					}
				}
			},
			setStepColorsBefore: function(step)
			{
				var textColor = step.calculateTextColor();
				var baseColor = step.getBackgroundColor();
				for(var i = 0, l = step.getIndex(); i <= l; i++)
				{
					this._steps[i].setColor(textColor);
					this._steps[i].setBackgroundColor(baseColor);
				}
			},
			recoverStepColorsBefore: function(step)
			{
				for(var i = 0, l = step.getIndex(); i <= l; i++)
				{
					this._steps[i].recoverStyles();
				}
			},
			save: function()
			{
				var serviceUrl = BX.prop.getString(this._settings, "serviceUrl");
				var value = this.getCurrentStepId();
				var type = this.getEntityTypeName();
				var id = this.getEntityId();

				if(serviceUrl === "" || value === "" || type === "" || id <= 0)
				{
					return;
				}

				const step = this._stepInfos.find((obj) => obj.id === value);

				if (step.semantics === 'apology' || step.semantics === 'failure')
				{
					this._analyticsData.c_element = BX.Crm.Integration.Analytics.Dictionary.ELEMENT_LOSE_BUTTON;
					this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_ATTEMPT);
				}
				else if (step.semantics === 'success')
				{
					this._analyticsData.c_element = BX.Crm.Integration.Analytics.Dictionary.ELEMENT_WON_BUTTON;
					this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_ATTEMPT);
				}
				else
				{
					this._analyticsData = null;
				}


				var data = {
					"ACTION" : "SAVE_PROGRESS",
					"VALUE": value,
					"TYPE": type,
					"ID": id,
					sessid: BX.bitrix_sessid(),
				};

				BX.onCustomEvent(this, 'Crm.EntityProgress.onSaveBefore', [ this, data ]);

				BX.ajax(
					{
						url: serviceUrl,
						method: "POST",
						dataType: 'json',
						data: data,
						onsuccess: BX.delegate(this.onSaveRequestSuccess, this)
					}
				);
			},
			onSaveRequestSuccess: function(data)
			{
				var checkErrors = BX.prop.getObject(data, "CHECK_ERRORS", null);
				if(checkErrors)
				{
					this.openEntityEditorDialog(
						{
							title: this._manager.getMessage("checkErrorTitle"),
							helpData: { text: this._manager.getMessage("checkErrorHelp"), code: this._manager.getMessage("checkErrorHelpArticleCode") },
							fieldNames: Object.keys(checkErrors),
							initData: BX.prop.getObject(data, "EDITOR_INIT_DATA", null),
							context: BX.prop.getObject(data, "CONTEXT", null)
						}
					);

					this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_ERROR);

					return;
				}

				const errorMessage = BX.prop.getString(data, 'ERROR', null);
				if (errorMessage)
				{
					BX.UI.Notification.Center.notify({
						content: errorMessage,
						autoHideDelay: 5000,
					});

					this.setCurrentStepByIdAndAdjustSteps(this._previousStepId);

					this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_ERROR);

					return;
				}

				this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_SUCCESS);

				BX.onCustomEvent(
					window,
					"Crm.EntityProgress.Saved",
					[
						this,
						{
							entityTypeId: this._entityTypeId,
							entityId: this._entityId,
							currentStepId: this._currentStepId,
							currentSemantics: this._currentSemantics,
							previousStepId: this._previousStepId,
							previousSemantics: this._previousSemantics,
							requestData: data
						}
					]
				);
			},
			openEntityEditorDialog: function(params)
			{
				BX.Crm.PartialEditorDialog.close("progressbar-entity-editor");

				this._entityEditorDialog = BX.Crm.PartialEditorDialog.create(
					"progressbar-entity-editor",
					{
						title: BX.prop.getString(params, "title", "Please fill in all required fields"),
						entityTypeId: this._entityTypeId,
						entityId: this._entityId,
						fieldNames: BX.prop.getArray(params, "fieldNames", []),
						helpData: BX.prop.getObject(params, "helpData", null),
						context: BX.prop.getObject(params, "context", null)
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
			onEntityEditorDialogClose: function(sender, eventParams)
			{
				if(
					!(this._entityTypeId === BX.prop.getInteger(eventParams, "entityTypeId", 0)
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
					this.setCurrentStepByIdAndAdjustSteps(this._previousStepId);
				}
			},
			openTerminationDialog: function()
			{
				if(this._terminationDlg)
				{
					this._terminationDlg.close();
					this._terminationDlg = null;
				}
				const apologies = this.findAllStepInfoBySemantics("apology");

				this._terminationDlg = BX.CrmProcessTerminationDialog.create(
					(this._id + "_TERMINATION"),
					BX.CrmParamBag.create(
						{
							title: this._manager.getMessage("dialogTitle"),
							failureTitle: apologies.length > 0 ? this._manager.getMessage("failureTitle") : "",
							// anchor: this._container,
							success: this.findStepInfoBySemantics("success"),
							failure: this.findStepInfoBySemantics("failure"),
							apologies: apologies,
							callback: this.onTerminationDialogClose.bind(this),
							buttonPrepareCallback: this.terminationButtonPrepareCallback.bind(this),
							terminationControl: this._terminationControl,
						}
					)
				);

				this._terminationDlg.open();
			},
			terminationButtonPrepareCallback(button, stepInfo)
			{
				if (this.isHasPermissionToMove(stepInfo.id))
				{
					return;
				}

				if (
					stepInfo.semantics === 'failure'
					&& this.isHasPermissionToMoveFailureStages()
				)
				{
					return;
				}

				BX.Dom.addClass(button, '--disabled');
			},
			closeTerminationDialog: function()
			{
				if(!this._terminationDlg)
				{
					return;
				}

				this._terminationDlg.close(false);
				this._terminationDlg = null;
			},
			onTerminationDialogClose: function(dialog, params)
			{
				if(this._terminationDlg !== dialog)
				{
					return;
				}

				this._analyticsData = BX.Crm.Integration.Analytics.Builder.Entity.CloseEvent.createDefault(
						this._entityTypeId,
						this._entityId,
					)
					.setSubSection(BX.Crm.Integration.Analytics.Dictionary.SUB_SECTION_DETAILS)
					.setElement(BX.Crm.Integration.Analytics.Dictionary.ELEMENT_DETAILS_PROGRESS_BAR)
					.buildData();
				this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_ATTEMPT);

				this.closeTerminationDialog();

				let stepId = BX.type.isNotEmptyString(params.result) ? params.result : '';
				let stepIndex = this.findStepInfoIndex(stepId);
				if(stepIndex < 0)
				{
					this.adjustStepsByCurrentStep();

					return;
				}

				let stepInfo = this._stepInfos[stepIndex];

				let openFailureDialog = false;
				const failure = this.findStepInfoBySemantics("failure");
				if(failure && failure["id"] === stepId)
				{
					openFailureDialog = true;
					if (!this.isHasPermissionToMove(stepId))
					{
						const availableApology = this.findAllStepInfoBySemantics('apology')
							.find((apology) => this.isHasPermissionToMove(apology.id))
						;

						if (
							!this.isHasPermissionToMoveFailureStages()
							|| !availableApology
						)
						{
							this.showMissPermissionError();
							this.adjustStepsByCurrentStep();

							this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_ERROR_PERMISSIONS);
							return;
						}

						stepId = availableApology.id;
						stepIndex = this.findStepInfoIndex(stepId);
						stepInfo = { ...availableApology };
					}
				}
				else if(stepInfo["semantics"] === "success")
				{
					if (!this.isHasPermissionToMove(stepId))
					{
						this.showMissPermissionError();
						this.adjustStepsByCurrentStep();

						this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_ERROR_PERMISSIONS);
						return;
					}

					if(typeof(stepInfo["hasParams"]) !== "undefined" && stepInfo["hasParams"] === true)
					{
						openFailureDialog = true;
					}
					else
					{
						const finalScript = BX.prop.getString(this._settings, "finalScript", "");
						if(finalScript !== "")
						{
							this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_SUCCESS);
							eval(finalScript);
							return;
						}

						const finalUrl = BX.prop.getString(this._settings, "finalUrl", "");
						if(finalUrl !== "")
						{
							this._registerAnalyticsCloseEvent(BX.Crm.Integration.Analytics.Dictionary.STATUS_SUCCESS);
							window.location = finalUrl;
							return;
						}
					}
				}

				this._beforeFailureDialogOpenStepId = openFailureDialog ? this._currentStepId : null;
				this.setCurrentStep(stepInfo);
				this.adjustSteps(stepIndex, BX.Crm.EntityDetailProgressControl.getStepColor(stepInfo));

				if(openFailureDialog)
				{
					this.openFailureDialog();

					return;
				}

				this.save();
			},
			openFailureDialog: function()
			{
				if(this._failureDlg)
				{
					this._failureDlg.close();
					this._failureDlg = null;
				}

				const currentStepIndex = this.findStepInfoIndex(this._currentStepId);
				const info = currentStepIndex >= 0 ? this._stepInfos[currentStepIndex] : null;
				const initValue = info ? info["id"] : "";

				const apologies = this.findAllStepInfoBySemantics("apology");
				this._failureDlg = BX.CrmProcessFailureDialog.create(
					(this._id + "_FAILURE"),
					BX.CrmParamBag.create(
						{
							entityType: this._entityType,
							entityId: this._entityId,
							initValue: initValue,
							failureTitle: apologies.length > 0 ? this._manager.getMessage("failureTitle") : "",
							selectorTitle: this._manager.getMessage("selectorTitle"),
							// anchor: this._container,
							success: this.findStepInfoBySemantics("success"),
							failure: this.findStepInfoBySemantics("failure"),
							apologies: apologies,
							callback: this.onFailureDialogClose.bind(this),
						}
					)
				);

				BX.addCustomEvent('CrmProcessFailureDialogContentCreated', this.onCrmProcessFailureDialogContentCreated.bind(this));

				this._failureDlg.open();
			},
			closeFailureDialog: function()
			{
				if(!this._failureDlg)
				{
					return;
				}

				this._failureDlg.close(false);
				this._failureDlg = null;
			},
			onFailureDialogClose: function(dialog, params)
			{
				if(this._failureDlg !== dialog)
				{
					return;
				}

				let stepInfo, stepIndex;
				BX.onCustomEvent(this, 'CrmProgressControlBeforeFailureDialogClose', [ this, this._failureDlg ]);
				this.closeFailureDialog();

				let bid = BX.type.isNotEmptyString(params["bid"]) ? params["bid"] : "";
				if (bid !== 'accept')
				{
					//Rollback current step
					if(this._previousStepId !== '')
					{
						this.setCurrentStepByIdAndAdjustSteps(this._previousStepId);
					}

					return;
				}

				const id = BX.type.isNotEmptyString(params.result) ? params.result : '';
				const fromId = this._beforeFailureDialogOpenStepId ?? this._currentStepId;
				if (!this.getPermissionChecker().isHasPermissionToMove(fromId, id))
				{
					this.showMissPermissionError();
					//Rollback current step
					if (this._previousStepId !== '')
					{
						this.setCurrentStepByIdAndAdjustSteps(this._previousStepId);
					}

					return;
				}

				stepIndex = this.findStepInfoIndex(id);
				if(stepIndex >= 0)
				{
					stepInfo = this._stepInfos[stepIndex];
					if(stepInfo.semantics === 'success')
					{
						const finalScript = BX.prop.getString(this._settings, "finalScript", "");
						if(finalScript !== "")
						{
							eval(finalScript);
							return;
						}

						const finalUrl = BX.prop.getString(this._settings, "finalUrl", "");
						if(finalUrl !== "")
						{
							window.location = finalUrl;
							return;
						}

						const verboseMode = BX.prop.getBoolean(this._settings, "verboseMode", false);
						if(verboseMode)
						{
							//Rollback current step
							if(this._previousStepId !== '')
							{
								this.setCurrentStepByIdAndAdjustSteps(this._previousStepId);
							}

							//User have to make choice
							this.openTerminationDialog();
							return;
						}
					}

					this.setCurrentStep(stepInfo, { keepPreviousStep: false });
					this.adjustSteps(
						stepIndex,
						BX.Crm.EntityDetailProgressControl.getStepColor(stepInfo)
					);
					this.save();
				}
			},
			onCrmProcessFailureDialogContentCreated(failureDialog, wrapper)
			{
				if (this._failureDlg !== failureDialog)
				{
					return;
				}

				const fromId = this._beforeFailureDialogOpenStepId ?? this.getCurrentStepId();

				const radioButtonWrappers = wrapper.querySelectorAll('.crm-list-end-deal-button-wrapper');
				radioButtonWrappers.forEach((radioButtonWrapper) => {
					const radioButton = radioButtonWrapper.querySelector('input');
					if (!radioButton)
					{
						return;
					}

					const stepId = radioButton.value;
					if (this.getPermissionChecker().isHasPermissionToMove(fromId, stepId))
					{
						return;
					}

					BX.Dom.addClass(radioButtonWrapper, '--disabled');
					radioButton.onclick = (event) => {
						this.showMissPermissionError();
						event.preventDefault();
					};
				});
			},
			processStepHover: function(step)
			{
				if (step.isDisabled())
				{
					return;
				}

				if(step.getIndex() < (this._steps.length - 1))
				{
					this.setStepColorsBefore(step);
				}
			},
			processStepLeave: function(step)
			{
				if(step.getIndex() < (this._steps.length - 1))
				{
					this.recoverStepColorsBefore(step);
				}
			},
			processStepSelect: function(step)
			{
				if(this._isReadOnly)
				{
					return;
				}

				this.closeTerminationDialog();

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
				if(this._entityEditorDialog !== null)
				{
					return;
				}

				var stepIndex = this.findStepInfoIndex(step.getId());
				if(stepIndex < 0)
				{
					return;
				}

				var stepInfo = this._stepInfos[stepIndex];
				var stepSemantics = stepInfo["semantics"];

				if(stepSemantics === "failure"
					|| stepSemantics === "apology"
					|| (stepSemantics === "success" && (this._terminationControl || this.findStepInfoBySemantics("failure")))
				)
				{
					if (!this.isHasPermissionToMoveTerminationStages())
					{
						this.showMissPermissionError();

						return;
					}

					//User have to make choice
					this.adjustSteps(step.getIndex(), step.getBackgroundColor());
					this.openTerminationDialog();
				}
				else
				{
					if (!this.isHasPermissionToMove(step.getId()))
					{
						this.showMissPermissionError();

						return;
					}

					this.adjustSteps(step.getIndex(), step.getBackgroundColor());
					if(this._currentStepId !== stepInfo["id"] && this.setCurrentStep(stepInfo))
					{
						this.save();
					}
				}
			},
			adjustStepsByCurrentStep: function()
			{
				const currentStepIndex = this.findStepInfoIndex(this._currentStepId);
				if (currentStepIndex < 0)
				{
					return;
				}

				this.adjustSteps(
					currentStepIndex,
					BX.Crm.EntityDetailProgressControl.getStepColor(this._stepInfos[currentStepIndex])
				);
			},
			_registerAnalyticsCloseEvent(status)
			{
				if (!this._analyticsData)
				{
					return;
				}

				this._analyticsData.status = status;

				BX.UI.Analytics.sendData(this._analyticsData);
			}
		};

	if(typeof(BX.Crm.EntityDetailProgressControl.defaultColors) === "undefined")
	{
		BX.Crm.EntityDetailProgressControl.defaultColors = {};
	}
	BX.Crm.EntityDetailProgressControl.getStepColor = function(stepInfo)
	{
		var color = BX.prop.getString(stepInfo, "color");

		if(color !== "")
		{
			return color;
		}

		var semantics = BX.prop.getString(stepInfo, "semantics");
		return BX.Crm.EntityDetailProgressControl.defaultColors[semantics];
	};

	BX.Crm.EntityDetailProgressControl.create = function(id, settings)
	{
		var self = new BX.Crm.EntityDetailProgressControl();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof BX.Crm.EntityDetailProgressStep === "undefined")
{
	BX.Crm.EntityDetailProgressStep = function()
	{
		this._id = "";
		this._settings = {};
		this._control = null;
		this._container = null;
		this._element = null;
		this._clickHandler = BX.delegate(this.onClick, this);
		this._hoverHandler = BX.delegate(this.onMouseHover, this);
		this._leaveHandler = BX.delegate(this.onMouseLeave, this);

		this._isVisible = true;
		this._isDisabled = false;
		this._displayName = "";
	};
	BX.Crm.EntityDetailProgressStep.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._control = BX.prop.get(this._settings, "control");
				this._container = BX.prop.getElementNode(this._settings, "container");
				this._element = this._container.querySelector(".crm-entity-section-status-step-item-text");
				BX.bind(this._container, "click", this._clickHandler);
				BX.bind(this._element, "mouseenter", this._hoverHandler);
				BX.bind(this._element, "mouseleave", this._leaveHandler);

				if(BX.prop.getBoolean(this._settings, "isPassed", false))
				{
					this._element.style.color = this.calculateTextColor();
				}
				this.saveStyles();

				this._isVisible = BX.prop.getBoolean(this._settings, "isVisible", true);
			},
			getId: function()
			{
				return this._id;
			},
			getIndex: function()
			{
				return BX.prop.getNumber(this._settings, "index", 0);
			},
			isVisible: function()
			{
				return this._isVisible;
			},
			setVisible: function(visible)
			{
				visible = !!visible;
				if(this._isVisible === visible)
				{
					return;
				}

				this._isVisible = visible;
				this._container.style.display = visible ? "" : "none";
			},
			getSemantics: function()
			{
				return BX.prop.getString(this._settings, "semantics", "");
			},
			getDisplayName: function()
			{
				return this._displayName;
			},
			setDisplayName: function(name)
			{
				this._displayName = name;
				if(this._element)
				{
					this._element.innerHTML = BX.util.htmlspecialchars(
						this._displayName !== "" ? this._displayName : BX.prop.getString(this._settings, "name", this._id)
					);
				}
			},
			onMouseHover: function(e)
			{
				this._control.processStepHover(this);
			},
			onMouseLeave: function(e)
			{
				this._control.processStepLeave(this);
			},
			onClick: function(e)
			{
				this._control.processStepSelect(this);
			},
			calculateTextColor: function()
			{
				var baseColor = (this._element.getAttribute("data-base-color"))
					? this._element.attributes["data-base-color"].value
					: getComputedStyle(this._element).borderBottomColor;

				return BX.Crm.EntityDetailProgressStep.calculateTextColor(baseColor);
			},
			getBackgroundColor: function()
			{
				return ((this._element.getAttribute("data-base-color"))
					? this._element.attributes["data-base-color"].value
					: getComputedStyle(this._element).borderBottomColor);
			},
			setBackgroundColor: function(color)
			{
				var encodedColor = encodeURIComponent(color);
				this._element.style.borderImage = BX.Crm.EntityDetailProgressStep.backgroundImageCss
					.replace(/#COLOR1#/gi, encodedColor)
					.replace(/#COLOR2#/gi, encodedColor);
			},
			setColor: function(color)
			{
				this._element.style.color = color;
			},
			recoverColors: function()
			{
				var encodedDefaultColor = encodeURIComponent(BX.Crm.EntityDetailProgressStep.defaultBackgroundColor);
				if (this._element.getAttribute("data-base-color"))
				{
					this._element.style.color = "";
					var encodedColor = encodeURIComponent(this._element.getAttribute("data-base-color"));
					this._element.style.borderImage = BX.Crm.EntityDetailProgressStep.backgroundImageCss
						.replace(/#COLOR1#/gi, encodedColor)
						.replace(/#COLOR2#/gi, encodedDefaultColor);
				}
				else
				{
					this._element.style.cssText = "";
				}
			},
			saveStyles: function()
			{
				if(this._element.getAttribute("style") )
				{
					BX.adjust(this._element, {attrs: { "data-style": this._element.getAttribute("style") } });
				}
			},
			recoverStyles: function()
			{
				this._element.style.cssText = (this._element.getAttribute("data-style")) ? this._element.getAttribute("data-style") : "";
			},
			isDisabled: function()
			{
				return this._isDisabled;
			},
			setDisabled: function()
			{
				if (this._isDisabled)
				{
					return;
				}

				BX.addClass(this._container, '--disabled');
				this._isDisabled = true;
			},
			removeDisabled: function()
			{
				if (!this._isDisabled)
				{
					return;
				}

				BX.removeClass(this._container, '--disabled');
				this._isDisabled = false;
			},
		};

	if(BX.Crm.EntityDetailProgressStep.backgroundImageCss === "undefined")
	{
		BX.Crm.EntityDetailProgressStep.backgroundImageCss = "";
	}

	if(BX.Crm.EntityDetailProgressStep.defaultBackgroundColor === "undefined")
	{
		BX.Crm.EntityDetailProgressStep.defaultBackgroundColor = "";
	}

	BX.Crm.EntityDetailProgressStep.calculateTextColor = function(baseColor)
	{
		var r, g, b;
		if ( baseColor > 7 )
		{
			var hexComponent = baseColor.split("(")[1].split(")")[0];
			hexComponent = hexComponent.split(",");
			r = parseInt(hexComponent[0]);
			g = parseInt(hexComponent[1]);
			b = parseInt(hexComponent[2]);
		}
		else
		{
			if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(baseColor))
			{
				var c = baseColor.substring(1).split('');
				if(c.length === 3)
				{
					c= [c[0], c[0], c[1], c[1], c[2], c[2]];
				}
				c = '0x'+c.join('');
				r = ( c >> 16 ) & 255;
				g = ( c >> 8 ) & 255;
				b =  c & 255;
			}
		}

		var y = 0.21 * r + 0.72 * g + 0.07 * b;
		return ( y < 145 ) ? "#fff" : "#333";
	};
	BX.Crm.EntityDetailProgressStep.create = function(id, settings)
	{
		var self = new BX.Crm.EntityDetailProgressStep();
		self.initialize(id, settings);
		return self;
	};
}
