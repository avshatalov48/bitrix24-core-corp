;(function()
{
	var MLDETAILS_URL = "/crm/ml/#entity_type#/#id#/detail";

	var instances = [];

	BX.CrmScoringButton = function(params)
	{
		if(!params)
		{
			params = {};
		}
		this.entityType = params.entityType.toString().toLowerCase();
		this.entityId = params.entityId;
		this.isEntityFinal = params.isFinal;
		this.mlInstalled = params.mlInstalled;
		this.scoringEnabled = params.scoringEnabled;

		var scoringParameters = BX.prop.getObject(params, "scoringParameters", {});

		this.spotlightEnabled = scoringParameters["SHOW_SPOTLIGHT"] === true;
		this.currentPrediction = scoringParameters["CURRENT_PREDICTION"];
		this.isModelReady = scoringParameters["MODEL_READY"];

		this.spotlightId = scoringParameters["SPOTLIGHT_ID"];
		this.spotlightTimeout = null;
		this.spotlight = null;

		this.elements = {
			scoring: null,
			title: null
		};

		this.init();
		instances.push(this);
	};
	BX.CrmScoringButton.getInstances = function()
	{
		return instances;
	};
	BX.CrmScoringButton.prototype = {
		init: function()
		{
			BX.addCustomEvent(window, "BX.Crm.EntityEditorSection:onLayout", this.onEntityEditorLayout.bind(this));
			BX.addCustomEvent("onPullEvent-crm", this.onPullEvent.bind(this));
			if(!this.isEntityFinal && this.mlInstalled && this.scoringEnabled && this.isModelReady && !this.currentPrediction)
			{
				setTimeout(this.getFirstPrediction.bind(this), 200);
			}
		},
		onPullEvent: function(command, params)
		{
			switch(command)
			{
				case "predictionUpdate":
					this.onPredictionUpdate(params);
					break;
				default:
					break;
			}
		},
		getFirstPrediction: function()
		{
			BX.ajax.runAction("crm.api.ml.scoring.tryCreateFirstPrediction", {
				data: {
					entityType: this.entityType,
					entityId: this.entityId
				}
			}).catch(function(response)
			{
				response.errors.forEach(console.error);
			})
		},
		getTitle: function()
		{
			var title = BX.message("CRM_ML_SCORING_BUTTON_TITLE");
			if(this.currentPrediction)
			{
				var score = this.currentPrediction["SCORE"];
				var percent = Math.floor(score * 100);

				title += ": " + percent + "%";
			}
			return title;
		},
		onEntityEditorLayout: function(editorSection, e)
		{
			if (!editorSection.getEditor().isEmbedded())
			{
				var sectionSerialNumber = e.serialNumber;
				if (sectionSerialNumber === 0 && this.mlInstalled)
				{
					if(!this.elements.scoring)
					{
						this.elements.scoring = BX.create("div", {
							props: {className: "crm-entity-widget-scoring"},
							events: {
								click: this.onScoringButtonClick.bind(this)
							},
							children: [
								BX.create("div", {
									props: {className: "crm-entity-widget-scoring-icon"}
								}),
								this.elements.title = BX.create("div", {
									props: {className: "crm-entity-widget-scoring-text"},
									text: this.getTitle()
								})
							]
						});
					}
					e.customNodes.push(this.elements.scoring);
					if(this.spotlightEnabled)
					{
						this.showSpotlight();
					}
				}
			}
		},
		onPredictionUpdate: function(params)
		{
			var entityType = params.entityType.toString().toLowerCase();
			var entityId = params.entityId;
			if(entityType !== this.entityType || entityId != this.entityId)
			{
				return;
			}

			this.currentPrediction = params.predictionRecord;
			if(this.elements.title)
			{
				BX.adjust(this.elements.title, {
					text: this.getTitle()
				});
			}
		},
		showSpotlight: function()
		{
			clearTimeout(this.spotlightTimeout);
			this.spotlightTimeout = setTimeout(function()
			{
				this.spotlight = new BX.SpotLight({
					id: this.spotlightId,
					targetElement: this.elements.scoring,
					targetVertex: "middle-center",
					content: this.entityType === "lead" ? BX.message("CRM_ML_SCORING_SPOTLIGHT_TEXT_LEAD") : BX.message("CRM_ML_SCORING_SPOTLIGHT_TEXT_DEAL"),
					autoSave: true
				});
				this.spotlight.show();

			}.bind(this), 200);
		},
		onScoringButtonClick: function(e)
		{
			var url = MLDETAILS_URL.replace("#id#", this.entityId).replace("#entity_type#", this.entityType);
			BX.SidePanel.Instance.open(url, {
				cacheable: false,
				width: 840
			});
		}
	}
})();