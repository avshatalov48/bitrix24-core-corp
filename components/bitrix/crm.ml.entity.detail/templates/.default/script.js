/** eslint-disable */

(function ()
{
	'use strict';

	BX.namespace('BX.Crm.Scoring');

	var ViewState = {
		Idle: "idle",
		Training: "training",
		Ready: "ready",
		Error: "error"
	};

	var TrainingError = {
		NotEnoughData: "not_enough_data",
		TooSoon: "too_soon"
	};

	BX.Crm.MlEntityDetail = function (params)
	{
		this.model = BX.prop.getObject(params, "model", {});
		this.mlModelExists = BX.prop.getBoolean(params, "mlModelExists", false);
		this.scoringEnabled = BX.prop.getBoolean(params, "scoringEnabled", false);
		this.canStartTraining = BX.prop.getBoolean(params, "canStartTraining", false);
		this.trainingError = BX.prop.getObject(params, "trainingError", {});
		this.entity = params.entity;
		this.predictionHistory = BX.prop.getArray(params, "predictionHistory", []);
		this.trainingHistory = BX.prop.getArray(params, "trainingHistory", []);
		this.errors = BX.prop.getArray(params, "errors", []);
		this.currentTraining = params.currentTraining ? this.prepareTrainingModel(params.currentTraining) : null;
		this.associatedEvents = BX.prop.getArray(params, "associatedEvents", []);
		this.currentAssociatedEvent = this.associatedEvents.length > 0 ? this.associatedEvents.length - 1 : 0;
		this.node = params.node;
		this.settingsButtonId = params.settingsButtonId;
		this.feedbackParams = BX.prop.getObject(params, "feedbackParams", {});
		this.feedbackForm = new BX.UI.Feedback.Form(this.feedbackParams);

		this.modelProgress = null;
		this.currentPrediction = this.predictionHistory.length > 0 ? this.predictionHistory[this.predictionHistory.length - 1] : null;
		this.state = "";
		if (this.errors.length > 0)
		{
			this.state = ViewState.Error;
		}
		else if (!this.mlModelExists)
		{
			this.state = ViewState.Idle;
		}
		else if (this.model["state"] === "ready")
		{
			this.state = ViewState.Ready;
		}
		else
		{
			this.state = ViewState.Training;
		}

		this.elements = {
			progressWrapper: null,
			predictionChart: null,
			modelQualityChart: null,
			assocEventWrap: null,
			scoringTextBlock: null,
			scoringDescBlock: null,
			startTrainingButton: null
		};
		this.modelQualityChart = null;
		this.predictionChart = null;

		this.trainingStepTimeout = null;

		this.disableConfirmationPopup = null;


		this.init();
	};

	BX.Crm.MlEntityDetail.prototype = {
		init: function ()
		{
			BX.addCustomEvent("onPullEvent-crm", this.onPullEvent.bind(this));

			if(this.currentTraining && this.currentTraining["STATE"] === "gathering")
			{
				this.scheduleContinueTraining();
			}
		},

		onPullEvent: function(command, params)
		{
			switch(command)
			{
				case "trainingProgress":
					this.onTrainingProgress(params);
					break;
				case "mlModelUpdate":
					this.onMlModelUpdate(params);
					break;
				default:
					break;
			}
		},

		onTrainingProgress: function(params)
		{
			this.model = params.model;
			this.currentTraining = this.prepareTrainingModel(params.currentTraining);

			if(this.currentTraining["state"] == "finished")
			{

			}
			else
			{
				if(this.modelProgress)
				{
					this.modelProgress.setProgress(this.calcProgress());
				}
			}
		},

		onMlModelUpdate: function(params)
		{
			this.model = params.model;
			if(this.model["state"] === "ready")
			{
				this.updateState("ready");
			}
			else
			{
				if(this.modelProgress)
				{
					this.modelProgress.setProgress(this.calcProgress());
				}
			}
		},

		prepareTrainingModel: function(data)
		{
			var result = data;
			result["RECORDS_SUCCESS"] = parseInt(result["RECORDS_SUCCESS"]);
			result["RECORDS_SUCCESS_DELTA"] = parseInt(result["RECORDS_SUCCESS_DELTA"]);
			result["RECORDS_FAILED"] = parseInt(result["RECORDS_FAILED"]);
			result["RECORDS_FAILED_DELTA"] = parseInt(result["RECORDS_FAILED_DELTA"]);
			result["DAYS_TO_TRAIN"] = parseInt(result["DAYS_TO_TRAIN"]);
			result["NEXT_DATE"] = result["NEXT_DATE"];
			result["DATE_START"] = result["DATE_START"] ? new Date(result["DATE_START"]) : null;
			result["DATE_FINISH"] = result["DATE_FINISH"] ? new Date(result["DATE_FINISH"]) : null;
			return result;
		},

		calcProgress: function()
		{
			if(this.model["state"] === "ready")
			{
				return 100;
			}
			else if (this.model["state"] === "evaluating")
			{
				return 86;
			}
			else if (this.model["state"] === "training")
			{
				return 66;
			}
			else if (this.model["state"] === false)
			{
				return 0;
			}
			else
			{
				var totalRecords = this.model["recordsFailed"] + this.model["recordsSuccess"];
				var ratio = (parseInt(this.currentTraining["RECORDS_SUCCESS"], 10) + parseInt(this.currentTraining["RECORDS_FAILED"], 10)) / totalRecords;
				return isNaN(ratio) ? 0 : Math.floor(ratio * 66);
			}
		},

		scheduleContinueTraining: function()
		{
			clearTimeout(this.trainingStepTimeout);
			this.trainingStepTimeout = setTimeout(this.continueTraining.bind(this), 1);
		},

		continueTraining: function()
		{
			clearTimeout(this.trainingStepTimeout);
			this.trainingStepTimeout = 0;

			BX.ajax.runComponentAction(
				"bitrix:crm.ml.entity.detail",
				"continueTraining", {
					data: {
						modelName: this.model["name"]
					}
				}
			).then(function(response)
			{
				var data = response.data;
				var currentTraining = data.currentTraining;

				if(currentTraining["STATE"] === "gathering")
				{
					this.scheduleContinueTraining();
				}

			}.bind(this)).catch(function(response)
			{
				console.error(response.errors.map(function(e){return e.message}).join("; "));
			});
		},

		makeChart: function (element, data)
		{
			return window.AmCharts.makeChart(element, {
				"type": "serial",
				"theme": "light",
				"dataProvider": data,
				"valueAxes": [{
					"gridColor": "#E0E0E0",
					"gridAlpha": 0,
					"labelFrequency": 2,
					"axisColor": "#E0E0E0",
					"color": "#808992",
					"gridPosition": "start",
					"minimum": 0,
					"maximum": 100,
					"gridCount": 1,
					"fontSize": 10,
				}],
				"gridAboveGraphs": false,
				"startDuration": 1,
				"graphs": [{
					"balloonFunction": function (s)
					{
						var item = s.dataContext;
						var dateFormat = BX.date.convertBitrixFormat(BX.message("FORMAT_DATETIME")).replace(/:?\s*s/, "");
						var formattedDate = BX.date.format(dateFormat, item.date);
						var score = item.score + "%";

						return BX.message("CRM_ML_SCORE_BALLOON").replace("#DATE#", formattedDate).replace("#SCORE#", score);
					},
					"fillColorsField": "color",
					"fillAlphas": 1,
					"lineAlpha": 1,
					"lineColorField": "color",
					"type": "column",
					"valueField": "score",
					"columnWidth": 1
				}],
				"categoryField": "date",
				"categoryAxis": {
					"labelFunction": function (s)
					{
						return "";
					},
					"gridPosition": "start",
					"gridAlpha": 0.1,
					"axisColor": "#E0E0E0"
				},
				"guides": [
					{}
				],
				"export": {
					"enabled": false
				}
			});
		},

		/*

		 */
		renderTraining: function()
		{
			return BX.create("div", {
				props: {className: "crm-ml-entity-content"},
				children: [
					this.elements.progressWrapper = BX.create("div", {
						props: {className: "crm-ml-entity-content-img-block"},
					}),
					this.renderDescription()
				]
			});
		},

		renderDescription: function()
		{
			var isLead = this.entity["ENTITY_TYPE"].toLowerCase() == "lead";

			this.elements.scoringTextBlock = BX.create("div", {
				props: {className: "crm-ml-entity-content-text-block"},
				children: [
					BX.create("div", {
						props: {className: "crm-ml-entity-content-text"},
						text: BX.message("CRM_ML_SCORING_DESCRIPTION_TITLE_2")
					}),
					this.elements.scoringDescBlock = BX.create("div", {
						props: {className: "crm-ml-entity-content-decs-block"}
					})
				]
			});

			if(this.state == ViewState.Idle)
			{
				this.elements.scoringDescBlock.appendChild(
					BX.create("div", {
						props: {className: "crm-ml-entity-content-desc"},
						text: BX.message("CRM_ML_SCORING_DESCRIPTION_P1")
					})
				);
				this.elements.scoringDescBlock.appendChild(
					BX.create("div", {
						props: {className: "crm-ml-entity-content-desc"},
						text: BX.message("CRM_ML_SCORING_DESCRIPTION_P2_2")
					})
				);

				if(this.canStartTraining)
				{
					this.elements.scoringDescBlock.appendChild(
						BX.create("div", {
							props: {className: "crm-ml-entity-content-desc"},
							text: BX.message("CRM_ML_SCORING_CAN_START_TRAINING")
						})
					);

					this.elements.startTrainingButton = BX.create("button", {
						props: {className: "ui-btn ui-btn-success ui-btn-md"},
						text: BX.message("CRM_ML_SCORING_TRAIN_FREE_OF_CHARGE"),
						events: {
							click: this.onStartTrainingButtonClick.bind(this)
						}
					});
					this.elements.scoringTextBlock.appendChild(this.elements.startTrainingButton);
				}
				else
				{
					switch (this.trainingError.code)
					{
						case TrainingError.NotEnoughData:
							this.elements.scoringDescBlock.appendChild(
								BX.create("div", {
									props: {className: "crm-ml-entity-content-desc"},
									text: BX.message("CRM_ML_SCORING_NOT_ENOUGH_DATA")
								})
							);
							break;
						case TrainingError.TooSoon:
							const message = BX.message("CRM_ML_SCORING_ERROR_TOO_SOON_2");

							this.elements.scoringDescBlock.appendChild(
								BX.create("div", {
									props: {className: "crm-ml-entity-content-desc"},
									text: BX.Type.isPlainObject(this.currentTraining) && this.currentTraining["NEXT_DATE"]
										? message.replace("#DATE#", this.currentTraining["NEXT_DATE"])
										: message.substring(0, message.indexOf('.') + 1)
								})
							);
					}
				}
			}
			if(this.state == ViewState.Training)
			{
				this.elements.scoringTextBlock.appendChild(
					BX.create("div", {
						props: {className: "crm-ml-entity-content-text"},
						text: isLead ? BX.message("CRM_ML_MODEL_TRAINING_LEADS") : BX.message("CRM_ML_MODEL_TRAINING_DEALS")
					})
				);
				this.elements.scoringTextBlock.appendChild(
					BX.create("div", {
						props: {className: "crm-ml-entity-content-text-notice"},
						text: isLead ? BX.message("CRM_ML_MODEL_FUTURE_LEAD_FORECAST") : BX.message("CRM_ML_MODEL_FUTURE_DEAL_FORECAST")
					})
				);
			}

			return this.elements.scoringTextBlock;
		},

		renderPredictionReport: function()
		{
			var predictionClass = "",
				scoreMessage = "",
				probabilityMessage = "",
				forecastMessage = "",
				qualityClass = "",
				qualityMessage = "",
				successEntitiesInTrainingMessage = "",
				failedEntitiesInTrainingMessage = "",
				isLead;


			if(this.currentPrediction)
			{
				if (this.currentPrediction["SCORE"] < 0.5)
				{
					scoreMessage = BX.message("CRM_ML_SUCCESS_PROBABILITY_LOW");
					predictionClass = "crm-ml-entity-report-title-fail";
				}
				else if (this.currentPrediction["SCORE"] < 0.75)
				{
					scoreMessage = BX.message("CRM_ML_SUCCESS_PROBABILITY_MEDIUM");
					predictionClass = "crm-ml-entity-report-title-middle";
				}
				else
				{
					 scoreMessage = BX.message("CRM_ML_SUCCESS_PROBABILITY_HIGH");
					 predictionClass = "crm-ml-entity-report-title-success";
				}
			}

			if(this.currentTraining)
			{
				if (this.currentTraining["AREA_UNDER_CURVE"] < 0.5)
				{
					qualityMessage = BX.message("CRM_ML_MODEL_QUALITY_LOW");
					qualityClass = "crm-ml-entity-report-title-fail";
				}
				else if (this.currentTraining["AREA_UNDER_CURVE"] < 0.75)
				{
					qualityMessage = BX.message("CRM_ML_MODEL_QUALITY_MEDIUM");
					qualityClass = "crm-ml-entity-report-title-middle";
				}
				else
				{
					qualityMessage = BX.message("CRM_ML_MODEL_QUALITY_HIGH");
					qualityClass = "crm-ml-entity-report-title-success";
				}

			}

			if (this.entity["ENTITY_TYPE"].toLowerCase() === "lead")
			{
				isLead = true;
				probabilityMessage = BX.message("CRM_ML_LEAD_SUCCESS_PROBABILITY");
				forecastMessage = BX.message("CRM_ML_LEAD_FORECAST");
				successEntitiesInTrainingMessage = BX.message("CRM_ML_MODEL_SUCCESSFUL_LEADS_IN_TRAINING");
				failedEntitiesInTrainingMessage = BX.message("CRM_ML_MODEL_FAILED_LEADS_IN_TRAINING");
			}
			else
			{
				isLead = false;
				probabilityMessage = BX.message("CRM_ML_DEAL_SUCCESS_PROBABILITY");
				forecastMessage = BX.message("CRM_ML_DEAL_FORECAST");
				successEntitiesInTrainingMessage = BX.message("CRM_ML_MODEL_SUCCESSFUL_DEALS_IN_TRAINING");
				failedEntitiesInTrainingMessage = BX.message("CRM_ML_MODEL_FAILED_DEALS_IN_TRAINING");
			}

			return BX.create("div", {
				props: {className: "crm-ml-entity-report"},
				children: [
					BX.create("div", {
						props: {className: "crm-ml-entity-report-block crm-ml-entity-report-block-result"},
						children: [
							BX.create("div", {
								props: {className: "crm-ml-entity-report-title " + predictionClass},
								children: [
									BX.create("span", {
										props: {className: "crm-ml-entity-report-title-text"},
										text: probabilityMessage
									}),
									BX.create("span", {
										props: {className: "crm-ml-entity-report-title-result"},
										text: this.currentPrediction ? Math.round(this.currentPrediction["SCORE"] * 100) : ""
									}),
									BX.create("span", {
										props: {className: "crm-ml-entity-report-title-icon"},
									}),
									BX.create("span", {
										props: {className: "crm-ml-entity-report-title-mark"},
										text: scoreMessage
									}),
									BX.create("span", {
										dataset: {
											hint: BX.message("CRM_ML_SCORING_PREDICTION_HINT")
										},
										events: {
											click: function()
											{
												this.showHelp();
											}.bind(this)
										}
									})
								]
							}),
							/*BX.create("div", {
								props: {className: "crm-ml-entity-report-detail"},
								children: [
									BX.create("span", {
										props: {className: "crm-ml-entity-report-detail-text"},
										text: forecastMessage
									}),
									BX.create("a", {
										props: {className: "crm-ml-entity-report-detail-link"},
										text: this.entity["TITLE"]
									}),
								]
							}),*/
						]
					}),
					BX.create("div", {
						props: {className: "crm-ml-entity-report-block crm-ml-entity-report-block-chart"},
						children: [
							BX.create("div", {
								props: {className: "crm-ml-entity-report-title"},
								children: [
									BX.create("span", {
										props: {className: "crm-ml-entity-report-subtitle"},
										text: BX.message("CRM_ML_FORECAST_DYNAMICS")
									}),
								]
							}),
							this.elements.predictionChart = BX.create("div", {
								props: {className: "crm-ml-entity-chart"},
							}),
						]
					}),
					BX.create("div", {
						props: {className: "crm-ml-entity-report-block crm-ml-entity-report-block-event"},
						children: [
							BX.create("div", {
								props: {className: "crm-ml-entity-report-title"},
								children: [
									BX.create("span", {
										props: {className: "crm-ml-entity-report-subtitle"},
										text: BX.message("CRM_ML_INFLUENCING_EVENT")
									}),
									/*BX.create("span", {
										props: {className: "crm-ml-entity-report-arrow crm-ml-entity-report-arrow-prev"},
										events: {
											click: this.onLeftArrowClick.bind(this)
										}
									}),
									BX.create("span", {
										props: {className: "crm-ml-entity-report-arrow crm-ml-entity-report-arrow-next"},
										events: {
											click: this.onRightArrowClick.bind(this)
										}
									}),*/
								]
							}),
							this.elements.assocEventWrap = BX.create("div", { // todo: crm activity layout
								props: {className: "crm-ml-entity-report-event"},
								children: this.renderAssociatedEvents()
							})
						]
					}),
					BX.create("div", {
						props: {className: "crm-ml-entity-report-block crm-ml-entity-report-block-stat"},
						children: [
							BX.create("div", {
								props: {className: "crm-ml-entity-report-title with-border " + qualityClass},
								children: [
									BX.create("span", {
										props: {className: "crm-ml-entity-report-title-text"},
										text: BX.message("CRM_ML_MODEL_QUALITY")
									}),
									BX.create("span", {
										props: {className: "crm-ml-entity-report-title-result"},
										text: Math.round(this.currentTraining["AREA_UNDER_CURVE"] * 100)
									}),
									BX.create("span", {
										props: {className: "crm-ml-entity-report-title-icon"},
									}),
									BX.create("span", {
										props: {className: "crm-ml-entity-report-title-mark"},
										text: qualityMessage
									}),
									BX.create("span", {
										dataset: {
											hint: BX.message("CRM_ML_SCORING_MODEL_QUALITY_HINT")
										},
										events: {
											click: function()
											{
												this.showHelp();
											}.bind(this)
										}
									})
								]
							}),
							BX.create("div", {
								props: {className: "crm-ml-entity-report-stat"},
								children: [
									BX.create("div", {
										props: {className: "crm-ml-entity-report-info"},
										children: [
											BX.create("div", {
												props: {className: "crm-ml-entity-report-info-item"},
												children: [
													BX.create("div", {
														props: {className: "crm-ml-entity-report-info-text"},
														text: BX.message("CRM_ML_MODEL_MODEL_WILL_BE_TRAINED_AGAIN")
													}),
													BX.create("div", {
														props: {className: "crm-ml-entity-report-info-detail"},
														children: [
															BX.create("span", {
																props: {className: "crm-ml-entity-report-info-days"},
																html: BX.message("CRM_ML_MODEL_MODEL_WILL_BE_TRAINED_IN_DAYS").replace(
																	"#DAYS#",
																	'<span class="crm-ml-entity-report-info-value">' +
																	this.currentTraining["DAYS_TO_TRAIN"] +
																	'</span>'
																)
															})
														]
													}),
												]
											}),
											BX.create("div", {
												props: {className: "crm-ml-entity-report-info-item crm-ml-separator-left"},
												children: [
													BX.create("div", {
														props: {className: "crm-ml-entity-report-info-text"},
														text: successEntitiesInTrainingMessage
													}),
													BX.create("div", {
														props: {className: "crm-ml-entity-report-info-detail"},
														children: [
															BX.create("span", {
																props: {className: "crm-ml-entity-report-info-value"},
																text: this.currentTraining["RECORDS_SUCCESS"]
															}),
															(
																this.currentTraining["RECORDS_SUCCESS_DELTA"] != 0
																	?
																		BX.create("span", {
																			props: {className: this.currentTraining["RECORDS_SUCCESS_DELTA"] > 0 ? "crm-ml-entity-report-info-dif-green" : "crm-ml-entity-report-info-dif-red"},
																			text: (this.currentTraining["RECORDS_SUCCESS_DELTA"] > 0 ? "+ " : "") + this.currentTraining["RECORDS_SUCCESS_DELTA"]
																		})
																	:
																		null
															),
														]
													}),
												]
											}),
											BX.create("div", {
												props: {className: "crm-ml-entity-report-info-item crm-ml-separator-left"},
												children: [
													BX.create("div", {
														props: {className: "crm-ml-entity-report-info-text"},
														text: failedEntitiesInTrainingMessage
													}),
													BX.create("div", {
														props: {className: "crm-ml-entity-report-info-detail"},
														children: [
															BX.create("span", {
																props: {className: "crm-ml-entity-report-info-value"},
																text: this.currentTraining["RECORDS_FAILED"]
															}),
															(
																this.currentTraining["RECORDS_FAILED_DELTA"] != 0
																	?
																	BX.create("span", {
																		props: {className: (this.currentTraining["RECORDS_FAILED_DELTA"] > 0 ? "crm-ml-entity-report-info-dif-green" : "crm-ml-entity-report-info-dif-red")},
																		text: (this.currentTraining["RECORDS_FAILED_DELTA"] > 0 ? "+ " : "") + this.currentTraining["RECORDS_FAILED_DELTA"]
																	})
																	:
																	null
															),
														]
													}),
												]
											}),
										]
									}),
									this.elements.modelQualityChart = BX.create("div", {
										props: {className: "crm-ml-entity-chart"},
									}),
								]
							}),
						]
					}),
				]
			})
		},

		renderAssociatedEvents: function()
		{
			var isLead = this.entity["ENTITY_TYPE"].toLowerCase() === "lead";
			if(this.associatedEvents.length > 0 )
			{
				return this.associatedEvents.map(this.renderAssociatedEvent.bind(this));
			}
			else
			{
				return [
					BX.create("span", {
						props: {className: "crm-ml-entity-report-event-name"},
						text: isLead ? BX.message("CRM_ML_MODEL_NO_EVENTS_YET_LEAD") : BX.message("CRM_ML_MODEL_NO_EVENTS_YET_DEAL")
					})
				]
			}
		},

		renderAssociatedEvent: function(event)
		{
			//var event = this.associatedEvents[this.currentAssociatedEvent];
			var isLead = this.entity["ENTITY_TYPE"].toLowerCase() === "lead";

			if(event["EVENT_TYPE"] == "update")
			{
				return BX.create("span", {
					props: {className: "crm-ml-entity-report-event-name"},
					text: (isLead ? BX.message("CRM_ML_MODEL_EVENT_UPDATE_LEAD") : BX.message("CRM_ML_MODEL_EVENT_UPDATE_DEAL")) + ": " + this.formatPercent(event["SCORE_DELTA"])
				})
			}
			else if(event["EVENT_TYPE"] == "activity")
			{
				return BX.create("span", {
					props: {className: "crm-ml-entity-report-event-name"},
					children: [
						BX.create("a", {
							props: {className: "crm-ml-entity-report-act-link"},
							text: event["ACTIVITY"]["SUBJECT"],
							events: {
								click: function()
								{
									(new BX.Crm.Activity.Planner()).showEdit({"ID": event["ACTIVITY"]["ID"]});
								}.bind(this)
							}
						}),
						BX.create("span", {
							text: ": " + this.formatPercent(event["SCORE_DELTA"])
						})
					]
				})
			}
		},

		setCurrentAssociatedEvent: function(newAssocEvent)
		{
			if(newAssocEvent == this.currentAssociatedEvent)
			{
				return;
			}

			this.currentAssociatedEvent = newAssocEvent;

			BX.cleanNode(this.elements.assocEventWrap);

			this.elements.assocEventWrap.appendChild(this.renderAssociatedEvent());
		},

		formatPercent: function(val)
		{
			return (val > 0 ? "+" : "") + Math.floor(val * 100) + "%";
		},

		getScoreColor: function(score)
		{
			if (score === 0)
			{
				return "";
			}
			else if (score < 0.5)
			{
				return "#F65C2F";
			}
			else if (score < 0.75)
			{
				return "#FFC238";
			}
			else
			{
				return "#9DCF00"
			}
		},

		renderPredictionChart: function()
		{
			var i;
			var data = [];

			if(this.predictionHistory.length < 6)
			{
				for(i = 6; i > this.predictionHistory.length; i--)
				{
					data.push({date: null, score: 0, color: ""})
				}
			}

			for(i = 0; i < this.predictionHistory.length; i++)
			{
				data.push({
					date: new Date(this.predictionHistory[i]["CREATED"]),
					score: Math.round(this.predictionHistory[i]["SCORE"] * 100),
					color: this.getScoreColor(this.predictionHistory[i]["SCORE"])
				})
			}

			this.predictionChart = this.makeChart(
				this.elements.predictionChart,
				data
			);
		},

		renderModelQualityChart: function()
		{
			var i;
			var data = [];

			if(this.trainingHistory.length < 5)
			{
				for(i = 5; i > this.trainingHistory.length; i--)
				{
					data.push({date: null, score: 0, color: ""})
				}
			}

			for(i = 0; i < this.trainingHistory.length; i++)
			{
				data.push({
					date: new Date(this.trainingHistory[i]["DATE_FINISH"]),
					score: Math.round(this.trainingHistory[i]["AREA_UNDER_CURVE"] * 100),
					color: this.getScoreColor(this.trainingHistory[i]["AREA_UNDER_CURVE"])
				})
			}

			this.modelQualityChart = this.makeChart(
				this.elements.modelQualityChart,
				data
			);
		},

		renderErrors: function()
		{
			return BX.create("div", {
				props: {className: "crm-ml-entity-content"},
				children: [
					this.elements.progressWrapper = BX.create("div", {
						props: {className: "crm-ml-entity-content-img-block"},
					}),
					BX.create("div", {
						props: {className: "crm-ml-entity-content-text-block"},
						children: this.errors.map(function(error)
						{
							return BX.create("div", {
								props: {className: "ui-alert ui-alert-danger"},
								children: [
									BX.create("span", {
										props: {className: "ui-alert-message"},
										text: error.message
									})
								]
							})
						})

					}),
				]
			})
		},

		show: function()
		{
			if(this.state === ViewState.Error)
			{
				this.node.appendChild(this.renderErrors());

				this.modelProgress = new Progress({
					domNode: this.elements.progressWrapper,
					width: 132,
					progress: 0
				});
				this.modelProgress.show();

			}
			else if(this.state === ViewState.Idle)
			{
				this.node.appendChild(this.renderTraining());
				this.modelProgress = new Progress({
					domNode: this.elements.progressWrapper,
					width: 132,
					showProgress: false,
					animation: ProgressAnimation.Start
				});
				this.modelProgress.show();
			}
			else if(this.state === ViewState.Training)
			{
				this.node.appendChild(this.renderTraining());
				this.modelProgress = new Progress({
					domNode: this.elements.progressWrapper,
					width: 132,
					progress: this.calcProgress()
				});
				this.modelProgress.show();
			}
			else
			{
				this.node.appendChild(this.renderPredictionReport());
				this.renderPredictionChart();
				this.renderModelQualityChart();
				this.showSettingsButton();
			}

			BX.UI.Hint.init(this.node);
		},

		showHelp: function()
		{
			top.BX.Helper.show('redirect=detail&code=9578357');
		},

		showFeedbackForm: function()
		{
			this.feedbackForm.openPanel();
		},

		showSettingsButton: function()
		{
			var settingsButton = BX.UI.ButtonManager.getByUniqid(this.settingsButtonId);
			if(!settingsButton)
			{
				return;
			}
			settingsButton.removeClass("crm-ml-button-hidden");
		},

		confirmScoringDisable: function()
		{
			var isLead = this.entity["ENTITY_TYPE"].toLowerCase() == "lead";
			return new Promise(function(resolve)
			{
				var popupContent = BX.create("div", {
					children: [
						BX.create("p", {
							text: isLead ? BX.message("CRM_ML_DISABLE_LEAD_SCORING") : BX.message("CRM_ML_DISABLE_DEAL_SCORING")
						}),

					]
				});

				if (this.currentTraining)
				{
					popupContent.appendChild(BX.create("p", {
						text: BX.message("CRM_ML_SCORING_REENABLE_WARNING")
							.replace(
								"#DATE#",
								BX.Type.isPlainObject(this.currentTraining) && this.currentTraining["NEXT_DATE"]
									? this.currentTraining["NEXT_DATE"]
									: ""
							)
					}));
				}

				this.disableConfirmationPopup = new BX.PopupWindow("disable-scoring", null, {
					titleBar: BX.message("CRM_ML_CONFIRMATION"),
					content: popupContent,
					buttons: [
						new BX.PopupWindowCustomButton({
							id: "button-continue",
							text: BX.message("CRM_ML_BUTTON_DISABLE"),
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
							text: BX.message("CRM_ML_BUTTON_CANCEL"),
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
							this.disableConfirmationPopup = null;
						}.bind(this)
					}
				});

				this.disableConfirmationPopup.show();
			}.bind(this));
		},

		onDisableScoringClick: function(e, menuItem)
		{
			menuItem.getMenuWindow().close();

			this.confirmScoringDisable().then(function(result)
			{
				if(!result)
				{
					this.disableConfirmationPopup.close();
					return;
				}

				BX.ajax.runComponentAction("bitrix:crm.ml.entity.detail", "disableScoring", {
					data: {
						modelName: this.model["name"]
					}
				}).then(function()
				{
					this.disableConfirmationPopup.close();
					BX.SidePanel.Instance.close();
				}.bind(this)).catch(function(response)
				{
					this.disableConfirmationPopup.close();
					var error = response.errors.map(function(err){return err.message}).join("; ");
					alert(error);
				}.bind(this))

			}.bind(this));
		},

		updateState: function(newState)
		{
			if(this.state == newState)
			{
				return;
			}

			this.state = newState;
			if(this.state === ViewState.Ready)
			{
				this.updateData().then(function()
				{
					return this.fadeOut();
				}.bind(this)).then(function()
				{
					this.fadeIn();
				}.bind(this))
			}
			else if (this.state === ViewState.Training)
			{
				BX.clean(this.node);
				this.show();
				this.scheduleContinueTraining();
			}
		},

		updateData: function()
		{
			var result = new BX.Promise();

			BX.ajax.runComponentAction(
				"bitrix:crm.ml.entity.detail",
				"getResult", {
					data: {
						entityType: this.entity["ENTITY_TYPE"],
						entityId: this.entity["ENTITY_ID"]
					}
				}
			).then(function (response)
			{
				var params = response.data;
				this.model = BX.prop.getObject(params, "model", {});
				this.entity = params.entity;
				this.predictionHistory = BX.prop.getArray(params, "predictionHistory", []);
				this.trainingHistory = BX.prop.getArray(params, "trainingHistory", []);
				this.errors = BX.prop.getArray(params, "errors", []);
				this.currentTraining = params.currentTraining ? this.prepareTrainingModel(params.currentTraining) : null;

				result.resolve();
			}.bind(this));

			return result;
		},

		fadeOut: function()
		{
			var result = new BX.Promise();

			this.node.classList.add("crm-ml-entity-wrapper-animation-hide");
			setTimeout(
				function()
				{
					BX.clean(this.node);
					this.node.classList.remove("crm-ml-entity-wrapper-animation-hide");

					this.elements.progressWrapper = null;
					this.elements.predictionChart = null;
					this.elements.modelQualityChar = null;
					result.resolve();
				}.bind(this),
				300
			);

			return result;
		},

		fadeIn: function()
		{
			var result = new BX.Promise();

			this.node.classList.add("crm-ml-entity-wrapper-animation-show");
			this.show();
			setTimeout(
				function()
				{
					this.node.classList.remove("crm-ml-entity-wrapper-animation-show");
					result.resolve();
				}.bind(this),
				300
			);

			return result;
		},

		onLeftArrowClick: function()
		{
			if(this.currentAssociatedEvent === 0)
			{
				return;
			}
			this.setCurrentAssociatedEvent(this.currentAssociatedEvent-1);
		},

		onRightArrowClick: function()
		{
			if(this.associatedEvents.length === 0 || this.currentAssociatedEvent === this.associatedEvents.length - 1)
			{
				return;
			}
			this.setCurrentAssociatedEvent(this.currentAssociatedEvent+1);
		},

		onStartTrainingButtonClick: function()
		{
			if(!this.scoringEnabled)
			{
				BX.UI.InfoHelper.show("limit_AI_scoring");
				return;
			}

			this.elements.startTrainingButton.classList.add("ui-btn-wait");
			BX.ajax.runComponentAction("bitrix:crm.ml.entity.detail", "startModelTraining", {
				data: {
					modelName: this.model["name"]
				}
			}).then(function(response)
			{
				var params = response.data;

				this.model = BX.prop.getObject(params, "model", {});
				this.currentTraining = params.currentTraining ? this.prepareTrainingModel(params.currentTraining) : null;

				this.updateState(ViewState.Training);
			}.bind(this)).catch(function(response)
			{

			});
		}
	};

	var ProgressAnimation = {
		Start: "start",
		Progress: "progress"
	};

	var Progress = function (params)
	{
		this.domNode = params.domNode;
		this.perimetr = params.width;
		this.radius = params.width / 2;
		this.progress = Number(params.progress) > 100 ? 100 : params.progress;

		this.showProgress = params.showProgress !== false;

		this.animation = params.animation || ProgressAnimation.Progress;

		this.elements = {
			svg: null,
			progressWrapper: null,
			progressBg: null,
			progressMove: null,
			number: null
		}
	};

	Progress.prototype =
		{
			getCircumFerence: function ()
			{
				return (this.radius - 10) * 2 * Math.PI;
			},

			getCircumProgress: function ()
			{
				return this.getCircumFerence() - (this.getCircumFerence() / 100 * this.progress);
			},

			createCircle: function ()
			{
				this.elements.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
				this.elements.svg.setAttributeNS(null, 'class', 'crm-ml-entity-progress-circle-bar');
				this.elements.svg.setAttributeNS(null, 'viewport', '0 0 ' + this.radius + ' ' + this.radius);
				this.elements.svg.setAttributeNS(null, 'width', this.perimetr);
				this.elements.svg.setAttributeNS(null, 'height', this.perimetr);

				this.elements.progressBg = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
				this.elements.progressBg.setAttributeNS(null, 'r', this.radius - 10);
				this.elements.progressBg.setAttributeNS(null, 'cx', this.radius);
				this.elements.progressBg.setAttributeNS(null, 'cy', this.radius);
				this.elements.progressBg.setAttributeNS(null, 'class', 'crm-ml-entity-progress-circle-bar-bg');

				this.elements.progressMove = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
				this.elements.progressMove.setAttributeNS(null, 'r', this.radius - 10);
				this.elements.progressMove.setAttributeNS(null, 'cx', this.radius);
				this.elements.progressMove.setAttributeNS(null, 'cy', this.radius);
				this.elements.progressMove.setAttributeNS(null, 'stroke-dasharray', this.getCircumFerence());
				this.elements.progressMove.setAttributeNS(null, 'stroke-dashoffset', this.getCircumFerence());
				this.elements.progressMove.setAttributeNS(null, 'class', 'crm-ml-entity-progress-circle-bar-progress');

				this.elements.svg.appendChild(this.elements.progressBg);
				this.elements.svg.appendChild(this.elements.progressMove);

				return this.elements.svg;
			},

			render: function ()
			{
				if(this.showProgress)
				{
					this.createCircle();
					this.elements.number = BX.create('div', {
						attrs: {
							className: 'crm-ml-entity-progress-circle-number',
							'data-progress': this.progress
						}
					});
				}

				return BX.createFragment([
					BX.create("div", {
						props: {className: "crm-ml-entity-content-img"}
					}),
					this.elements.progressWrapper = BX.create("div", {
						props: {className: "crm-ml-entity-progress"},
						children: [
							this.graph = BX.create('div', {
								attrs: {
									className: 'crm-ml-entity-progress-circle-wrapper'
								},
								children: [
									this.elements.svg,
									this.elements.number
								]
							})
						]
					}),
					BX.create("div", {
						props: {className: this.animation == ProgressAnimation.Start ? "crm-ml-entity-content-circle-start" : "crm-ml-entity-content-circle"}
					})
				]);
			},

			addWrapperClass: function ()
			{
				this.graph.classList.add('crm-ml-entity-progress-circle-wrapper-animate');
			},

			animateProgress: function ()
			{
				if(!this.showProgress)
				{
					return;
				}
				this.elements.svg.setAttributeNS(null, 'class', 'crm-ml-entity-progress-circle-bar crm-ml-entity-progress-circle-bar-animate');

				if (this.progress > 0)
				{
					this.elements.progressMove.setAttributeNS(null, 'stroke-dashoffset', this.getCircumProgress());
				}
			},

			animateNumber: function (from, to)
			{
				if(!this.showProgress)
				{
					return;
				}

				if (to <= 0)
				{
					this.elements.number.innerHTML = '0' + ' <span>%</span>';
					return;
				}

				var i = from;
				var time = 1000 / Math.abs(to - from);
				var interval = setInterval(function ()
				{
					i = to > from ? i + 1 : i - 1;
					this.elements.number.innerHTML = i + ' <span>%</span>';
					if (i === this.progress)
					{
						clearInterval(interval);
					}
				}.bind(this), time);
			},

			setProgress: function (progress)
			{
				var oldProgress = this.progress;
				this.progress = Number(progress);
				if(this.progress == oldProgress)
				{
					return;
				}

				this.elements.progressMove.setAttributeNS(null, 'stroke-dashoffset', this.getCircumProgress());
				this.animateNumber(oldProgress, this.progress);
			},

			show: function ()
			{
				this.domNode.appendChild(this.render());

				setTimeout(function ()
				{
					this.addWrapperClass();
					this.animateNumber(0, this.progress);
					this.animateProgress();
				}.bind(this), 100)
			},
		};

})();
