;(function()
{
	'use strict';
	BX.namespace('BX.Crm.Scoring');

	BX.Crm.Scoring.ModelList = function(params)
	{
		this.container = params.container;
		this.trainingList = params.trainingList || {};
		this.trainingErrors = params.trainingErrors || {};
		this.models = this.createModels(Object.values(params.models || {}));
		this.scoringEnabled = params.scoringEnabled;

		this.trainingStepTimeout = 0;

		this.init();
		this.render();
	};

	BX.Crm.Scoring.ModelList.prototype = {
		init: function()
		{
			for (var key in this.trainingErrors)
			{
				if(!this.trainingErrors.hasOwnProperty(key))
				{
					continue;
				}
				this.getModelByName(key).trainingError = this.trainingErrors[key];
			}

			this.scheduleContinueTraining();
			BX.addCustomEvent("onPullEvent-crm", this.onPullEvent.bind(this));
		},

		createModels: function(models)
		{
			if(!BX.type.isArray(models))
			{
				return [];
			}
			return models.map(function(modelFields)
			{
				var modelName = modelFields.name;
				var currentTraining = this.trainingList[modelName];
				return new BX.Crm.Scoring.ModelList.Model({
					modelFields: modelFields,
					currentTraining: currentTraining,
					parent: this
				});
			}.bind(this))
		},

		getRunningTrainings: function()
		{
			var result = {};
			for(var i = 0; i < this.models.length; i++)
			{
				var model = this.models[i];
				if(model.shouldContinueTraining())
				{
					result[model.modelFields.name] = model.currentTraining;
				}
			}
			return result;
		},

		render: function()
		{
			BX.clean(this.container);
			BX.adjust(this.container, {
				children: this.models.map(function(model)
				{
					return model.render();
				})
			})
		},
		scheduleContinueTraining: function()
		{
			clearTimeout(this.trainingStepTimeout);
			if(Object.keys(this.getRunningTrainings()).length > 0)
			{
				this.trainingStepTimeout = setTimeout(this.continueTraining.bind(this), 1000);
			}
		},

		continueTraining: function()
		{
			clearTimeout(this.trainingStepTimeout);
			this.trainingStepTimeout = 0;

			var runningTrainings = this.getRunningTrainings();
			if(Object.keys(runningTrainings).length === 0)
			{
				return;
			}
			var currentModelName = Object.keys(runningTrainings)[0];

			BX.ajax.runComponentAction(
				"bitrix:crm.ml.entity.detail",
				"continueTraining", {
					data: {
						modelName: currentModelName
					}
				}
			).then(function()
			{
				this.scheduleContinueTraining();
			}.bind(this)).catch(function(response)
			{
				if(response.errors)
				{
					console.error(response.errors.map(function(e){return e.message}).join("; "));
				}
				else
				{
					console.error(response);
				}
			});
		},

		getModelByName: function(modelName)
		{
			for (var i = 0; i < this.models.length; i++)
			{
				if(this.models[i].modelFields.name === modelName)
				{
					return this.models[i];
				}
			}
			return null;
		},

		onPullEvent: function(command, params)
		{
			var model = this.getModelByName(params.model.name);
			if(!model)
			{
				return;
			}
			switch(command)
			{
				case "trainingProgress":
					model.onTrainingProgress(params);
					break;
				default:
					break;
			}
		},

		showHelp: function()
		{
			top.BX.Helper.show('redirect=detail&code=9578357');
		},

		showFeedbackForm: function()
		{

		}
	};

	BX.Crm.Scoring.ModelList.Model = function(params)
	{
		this.modelFields = params.modelFields;
		this.currentTraining = params.currentTraining;
		this.parent = params.parent;
		this.entityType = params.modelFields.name.substr(4,4).toLowerCase();
		this.trainingError = false;

		this.progressBar = new BX.UI.ProgressBar({
			value: this.calcProgress(),
			statusType: BX.UI.ProgressBar.Status.NONE,
			column: true,
		});

		this.roundProgress = new BX.UI.ProgressRound({
			width: 53,
			lineSize: 2,
			value: this.calcProgress()
		});

		this.deleteModelPopup = null;
		this.elements = {
			container: null,
			header: null,
			description: null,
			buttons: null,
			startTrainingButton: null,
		};
	};

	BX.Crm.Scoring.ModelList.Model.prototype =
	{
		inTraining: function()
		{
			return ["pending_creation", "gathering", "training", "evaluating"].indexOf(this.currentTraining.STATE) != -1;
		},

		shouldContinueTraining: function()
		{
			return ["pending_creation", "gathering"].indexOf(this.currentTraining.STATE) != -1;
		},

		render: function()
		{
			var containerClass;
			switch (this.modelFields.state)
			{
				case "new":
				case "training":
				case "evaluating":
					containerClass = "crm-ml-model-block-process";
					break;
				case "ready":
					containerClass = "crm-ml-model-block-default";
					break;
				default:
					if(this.inTraining())
					{
						containerClass = "crm-ml-model-block-process";
					}
					else
					{
						containerClass = this.trainingError ? "crm-ml-model-block-unprepared" : "crm-ml-model-block-prepared";
					}
			}

			this.elements.container = BX.create("div", {
				props: {className: "crm-ml-model-block " + containerClass},
				children: [
					BX.create("div", {
						props: {className: "crm-ml-model-block-inner"},
						children: [
							BX.create("div", {
								props: {className: "crm-ml-model-progress-container"},
								children: [
									this.inTraining() ? this.roundProgress.getContainer() : null
								]
							}),
							this.renderHeader(),
							this.renderDescription(),
							this.renderButtons(),
							this.inTraining() ?
								BX.create("div", {
									props: {className: "crm-ml-model-progress"},
									children: [this.progressBar.getContainer()]
								})
								: null
						]
					}),
				]
			});

			return this.elements.container;
		},

		renderHeader: function()
		{
			this.elements.header = BX.create("div", {
				props: {className: "crm-ml-model-header"},
				children: [
					BX.create("span", {
						props: {className: "crm-ml-model-title"},
						text: this.modelFields.title
					})
				]
			});
			return this.elements.header;
		},

		renderButtons: function ()
		{
			var isLead = this.entityType === "lead";
			var buttons = [];

			if(!this.modelFields.state && !this.inTraining() && !this.trainingError)
			{
				this.elements.startTrainingButton = BX.create("button", {
					props: {className: "ui-btn ui-btn-primary ui-btn-sm ui-btn-round"},
					text: BX.message("CRM_ML_MODEL_LIST_BUTTON_TRAIN_FREE_OF_CHARGE"),
					events: {
						click: this.onStartTrainingButtonClick.bind(this)
					}
				});
				buttons.push(this.elements.startTrainingButton);
			}
			else if (this.modelFields.state === "ready")
			{
				this.elements.deleteModelButton = BX.create("button", {
					props: {className: "ui-btn ui-btn-danger ui-btn-sm ui-btn-round"},
					text: isLead? BX.message("CRM_ML_MODEL_LIST_LEAD_SCORING_DISABLE") : BX.message("CRM_ML_MODEL_LIST_DEAL_SCORING_DISABLE"),
					events: {
						click: this.onDeleteModelButtonClick.bind(this)
					}
				});
				buttons.push(this.elements.deleteModelButton);
			}

			if (buttons.length > 0)
			{
				this.elements.buttons = BX.create("div", {
					props: {className: "crm-ml-model-btn"},
					children: buttons
				});
			}
			else
			{
				this.elements.buttons = null;
			}

			return this.elements.buttons;
		},

		renderDescription: function ()
		{
			var result = BX.create("div", {
				props: {className: "crm-ml-model-description"},
			});

			if(!this.modelFields.state || this.modelFields.state !== "ready")
			{
				if(this.inTraining())
				{
					result.appendChild(document.createTextNode(
						BX.message("CRM_ML_MODEL_LIST_SCORING_TRAINING_IN_PROCESS").replace("#PROGRESS#", this.calcProgress())
					));
				}
				else
				{
					if(!this.trainingError)
					{
						result.appendChild(document.createTextNode(
							BX.message("CRM_ML_MODEL_LIST_SCORING_ENOUGH_DATA")
						));
					}
					else if (this.trainingError.code == "too_soon")
					{
						result.appendChild(document.createTextNode(
							BX.message("CRM_ML_MODEL_LIST_SCORING_ERROR_TOO_SOON_2").replace("#DATE#", this.formatDate(this.currentTraining["NEXT_DATE"]))
						));
					}
					else if (this.trainingError.code == "not_enough_data")
					{
						var parsedMessage = /(.*)#LINK_START#(.*)#LINK_END#(.*)/.exec(BX.message("CRM_ML_MODEL_LIST_SCORING_NOT_ENOUGH_DATA"));

						result.appendChild(document.createTextNode(parsedMessage[1]));
						result.appendChild(BX.create("a", {
							text: parsedMessage[2],
							attrs: {
								href: "#"
							},
							events: {
								click: function(event)
								{
									this.parent.showHelp();
									event.preventDefault();
								}.bind(this)
							}
						}));
						result.appendChild(document.createTextNode(parsedMessage[3]));
					}
				}
			}
			else
			{
				var modelQuality = Math.round(this.currentTraining["AREA_UNDER_CURVE"] * 100).toString(10);

				result.appendChild(document.createTextNode(
					BX.message("CRM_ML_MODEL_LIST_SCORING_MODEL_READY")
				));
				result.appendChild(BX.create("br"));
				result.appendChild(document.createTextNode(
					BX.message("CRM_ML_MODEL_LIST_SCORING_MODEL_QUALITY").replace("#QUALITY#", modelQuality) + " " +
						BX.message("CRM_ML_MODEL_LIST_SCORING_MODEL_TRAINING_DATE").replace("#TRAINING_DATE#", this.formatDate(this.currentTraining["DATE_START"]))
				));
			}

			return result;
		},

		updateLayout: function ()
		{
			var currentContainer = this.elements.container;
			this.render();
			currentContainer.parentElement.replaceChild(this.elements.container, currentContainer);

			this.roundProgress.update(this.calcProgress());
			this.progressBar.update(this.calcProgress());
		},

		onDeleteModelButtonClick: function()
		{
			this.confirmScoringDisable().then(function(result)
			{
				if(!result)
				{
					this.deleteModelPopup.close();
					return;
				}

				BX.ajax.runComponentAction("bitrix:crm.ml.entity.detail", "disableScoring", {
					data: {
						modelName: this.modelFields.name
					}
				}).then(function(response)
				{
					var data = response.data;
					this.modelFields = data.model;
					this.currentTraining = data.currentTraining;
					this.trainingError = {
						code: "too_soon"
					};
					this.updateLayout();
					this.deleteModelPopup.close();
				}.bind(this)).catch(function(response)
				{
					this.deleteModelPopup.close();
					var error = response.errors.map(function(err){return err.message}).join("; ");
					alert(error);
				}.bind(this))

			}.bind(this));
		},

		onStartTrainingButtonClick: function()
		{
			if(!this.parent.scoringEnabled)
			{
				BX.UI.InfoHelper.show("limit_AI_scoring");
				return;
			}

			this.elements.startTrainingButton.classList.add("ui-btn-wait");
			BX.ajax.runComponentAction("bitrix:crm.ml.entity.detail", "startModelTraining", {
				data: {
					modelName: this.modelFields["name"]
				}
			}).then(function(response)
			{
				var data = response.data;
				this.modelFields = data.model;
				this.currentTraining = data.currentTraining;
				this.parent.scheduleContinueTraining();

				this.updateLayout();
			}.bind(this)).catch(function(response)
			{
				if (response.errors)
				{
					var errors = response.errors;
					var error = errors.map(function(err){return err.message}).join("<br>");

					alert(error);
				}
				else
				{
					console.error(response);
				}
				this.elements.startTrainingButton.classList.remove("ui-btn-wait");
			}.bind(this));
		},

		onTrainingProgress: function(params)
		{
			this.modelFields = params.model;
			this.currentTraining = params.currentTraining;

			this.updateLayout();
		},

		calcProgress: function()
		{
			if(this.modelFields["state"] === "ready")
			{
				return 100;
			}
			else if (this.modelFields["state"] === "evaluating")
			{
				return 86;
			}
			else if (this.modelFields["state"] === "training")
			{
				return 66;
			}
			else if (this.modelFields["state"] === false)
			{
				return 0;
			}
			else
			{
				var totalRecords = this.modelFields["recordsFailed"] + this.modelFields["recordsSuccess"];
				var ratio = (parseInt(this.currentTraining["RECORDS_SUCCESS"], 10) + parseInt(this.currentTraining["RECORDS_FAILED"], 10)) / totalRecords;
				return isNaN(ratio) ? 0 : Math.floor(ratio * 66);
			}
		},

		confirmScoringDisable: function()
		{
			var isLead = this.entityType == "lead";
			return new Promise(function(resolve)
			{
				var popupContent = BX.create("div", {
					children: [
						BX.create("p", {
							text: isLead ? BX.message("CRM_ML_MODEL_LIST_DISABLE_LEAD_SCORING") : BX.message("CRM_ML_MODEL_LIST_DISABLE_DEAL_SCORING")
						}),
					]
				});

				if(this.currentTraining)
				{
					popupContent.appendChild(BX.create("p", {
						text: BX.message("CRM_ML_MODEL_LIST_SCORING_REENABLE_WARNING").replace("#DATE#", this.formatDate(this.currentTraining["NEXT_DATE"]))
					}));
				}

				this.deleteModelPopup = new BX.PopupWindow("delete-scoring-model", null, {
					titleBar: BX.message("CRM_ML_MODEL_LIST_CONFIRMATION"),
					content: popupContent,
					buttons: [
						new BX.PopupWindowCustomButton({
							id: "button-continue",
							text: BX.message("CRM_ML_MODEL_LIST_BUTTON_DISABLE"),
							className: "ui-btn ui-btn-sm ui-btn-primary",
							events: {
								click: function ()
								{
									this.buttonNode.classList.add("ui-btn-wait");
									resolve(true);
								}
							}
						}),
						new BX.PopupWindowCustomButton({
							id: "button-cancel",
							text: BX.message("CRM_ML_MODEL_LIST_BUTTON_CANCEL"),
							className: "ui-btn ui-btn-sm ui-btn-link",
							events: {
								click: function()
								{
									resolve(false);
								}
							}
						})
					],
					events: {
						onPopupClose: function()
						{
							this.destroy();
						},
						onPopupDestroy: function()
						{
							this.deleteModelPopup = null;
						}.bind(this)
					}
				});

				this.deleteModelPopup.show();
			}.bind(this));
		},

		formatDate: function(date)
		{
			if (typeof(date) === "string")
			{
				date = new Date(date);
			}
			else if (!(date instanceof Date))
			{
				BX.debug("date should be instance of Date");
				return "";
			}

			var dateFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATE'));
			return BX.date.format(dateFormat, date);
		},
	}
})();