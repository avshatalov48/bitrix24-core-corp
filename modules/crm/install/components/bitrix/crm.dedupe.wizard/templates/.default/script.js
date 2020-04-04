BX.namespace("BX.Crm");

if(typeof(BX.Crm.DedupeWizard) === "undefined")
{
	BX.Crm.DedupeWizard = function ()
	{
		this._id = "";
		this._settings = {};

		this._entityTypeId = 0;
		this._steps = {};
		this._config = {};
		this._typeInfos = {};
		this._currentScope = "";
		this._scopeInfos = {};
		this._contextId = "";

		this._totalItemCount = 0;
		this._totalEntityCount = 0;

		this._mergedItemCount = 0;
		this._mergedEntityCount = 0;

		this._conflictedItemCount = 0;
		this._conflictedEntityCount = 0;

		this._resolvedItemCount = 0;
	};
	BX.Crm.DedupeWizard.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._entityTypeId = BX.prop.getInteger(this._settings, "entityTypeId", 0);
			this._currentScope = BX.prop.getString(this._settings, "currentScope", "");
			this._scopeInfos = BX.prop.getObject(this._settings, "scopeInfos", {});
			this._contextId = BX.prop.getString(this._settings, "contextId", "");
			this._typeInfos = BX.prop.getObject(this._settings, "typeInfos", {});
			this._config = BX.prop.getObject(this._settings, "config", {});
			
			this._steps = BX.prop.getObject(this._settings, "steps", {});
			for(var key in this._steps)
			{
				if(!this._steps.hasOwnProperty(key))
				{
					continue;
				}

				this._steps[key].setWizard(this);
			}
		},
		getEntityTypeId: function()
		{
			return this._entityTypeId;
		},
		getEntityTypeName: function()
		{
			return BX.CrmEntityType.resolveName(this._entityTypeId);
		},
		getConfig: function()
		{
			return BX.clone(this._config);
		},
		setConfig: function(config)
		{
			this._config = config;
			BX.onCustomEvent(this, "onConfigChange");
		},
		saveConfig: function()
		{
			return BX.ajax.runComponentAction(
				"bitrix:crm.dedupe.wizard",
				"saveConfiguration",
				{ data: { guid: this._id, config: this._config } }
			);
		},
		getContextId: function()
		{
			return this._contextId;
		},
		getCurrentScope: function()
		{
			return this._currentScope;
		},
		getScopeInfos: function()
		{
			return this._scopeInfos;
		},
		getTypeInfos: function()
		{
			return this._typeInfos;
		},
		layout: function()
		{
			this._steps["scanning"].start();
		},
		getMergerUrl: function()
		{
			return BX.prop.getString(this._settings, "mergerUrl", "");
		},
		getDedupeListUrl: function()
		{
			return BX.prop.getString(this._settings, "dedupeListUrl", "");
		},
		getTotalItemCount: function()
		{
			return this._totalItemCount;
		},
		setTotalItemCount: function(count)
		{
			this._totalItemCount = count;
		},
		getTotalEntityCount: function()
		{
			return this._totalEntityCount;
		},
		setTotalEntityCount: function(count)
		{
			this._totalEntityCount = count;
		},
		getMergedItemCount: function()
		{
			return this._mergedItemCount;
		},
		setMergedItemCount: function(count)
		{
			this._mergedItemCount = count;
		},
		getMergedEntityCount: function()
		{
			return this._mergedEntityCount;
		},
		setMergedEntityCount: function(count)
		{
			this._mergedEntityCount = count;
		},
		getConflictedItemCount: function()
		{
			return this._conflictedItemCount;
		},
		setConflictedItemCount: function(count)
		{
			this._conflictedItemCount = count;
		},
		getConflictedEntityCount: function()
		{
			return this._conflictedEntityCount;
		},
		setConflictedEntityCount: function(count)
		{
			this._conflictedEntityCount = count;
		},
		calculateEntityCount: function(items)
		{
			if(!BX.type.isArray(items))
			{
				return;
			}

			var result = {};
			for(var i = 0, length = items.length; i < length; i++)
			{
				var item = items[i];

				var rootEntityId = BX.prop.getInteger(item, "ROOT_ENTITY_ID", 0);
				if(rootEntityId > 0)
				{
					result[rootEntityId.toString()] = true;
				}
				var entityIds = BX.prop.getArray(item, "ENTITY_IDS", []);
				for(var j = 0; j < entityIds.length; j++)
				{
					result[entityIds[j].toString()] = true;
				}
			}
			return Object.keys(result).length;
		},
		getResolvedItemCount: function()
		{
			return this._resolvedItemCount;
		},
		setResolvedItemCount: function(count)
		{
			this._resolvedItemCount = count;
		},
		getUnResolvedItemCount: function()
		{
			return(this._conflictedItemCount - this._resolvedItemCount);
		},
		openDedupeList: function()
		{
			var params = {
				scope: BX.prop.getString(this._config, "scope", ""),
				typeNames: BX.prop.getArray(this._config, "typeNames", [])
			};
			BX.Crm.Page.open(BX.util.add_url_param(this.getDedupeListUrl(), params));
		},
		openMerger: function(contextId)
		{
			var params = {
				scope: BX.prop.getString(this._config, "scope", ""),
				typeNames: BX.prop.getArray(this._config, "typeNames", []),
				externalContextId: contextId
			};

			BX.Crm.Page.open(BX.util.add_url_param(this.getMergerUrl(), params));
		},
		onStepStart: function(step)
		{
		},
		onStepEnd: function(step)
		{
			var nextStepId = step.getNextStepId();
			if(!(nextStepId !== "" && this._steps.hasOwnProperty(nextStepId)))
			{
				return;
			}
			this._steps[nextStepId].start();
		}
	};
	BX.Crm.DedupeWizard.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizard();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardStep) === "undefined")
{
	BX.Crm.DedupeWizardStep = function ()
	{
		this._id = "";
		this._settings = {};
		this._wizard = null;
		this._progressBar = null;
	};
	BX.Crm.DedupeWizardStep.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._wizard = BX.prop.get(this._settings, "wizard");
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.prop.getObject(this._settings, "messages", {}), name, name);
		},
		getNextStepId: function()
		{
			return BX.prop.getString(this._settings, "nextStepId", "");
		},
		setWizard: function(wizard)
		{
			this._wizard = wizard;
		},
		getWizard: function()
		{
			return this._wizard;
		},
		getWrapper: function()
		{
			return BX(BX.prop.getString(this._settings, "wrapperId"));
		},
		getTitleWrapper: function()
		{
			return BX(BX.prop.getString(this._settings, "titleWrapperId"));
		},
		getSubtitleWrapper: function()
		{
			return BX(BX.prop.getString(this._settings, "subtitleWrapperId"));
		},
		prepareProgressBar: function()
		{
			if(!this._progressBar)
			{
				this._progressBar = new BX.UI.ProgressBar(
					{
						value: 0,
						maxValue: 100,
						color: BX.UI.ProgressBar.Color.SUCCESS,
						statusType: BX.UI.ProgressBar.Status.PERCENT,
						column: true
					}
				);
			}
			this._progressBar.update(0);
			BX(
				BX.prop.getString(this._settings, "progressBarWrapperId")
			).appendChild(this._progressBar.getContainer());
		},
		setProgressBarValue: function(value)
		{
			this._progressBar.update(value);
		},
		start: function()
		{
			this.getWrapper().style.display = "";
			window.setTimeout(
				function(){ this._wizard.onStepStart(this); }.bind(this),
				0
			);
		},
		end: function()
		{
			this.getWrapper().style.display = "none";
			window.setTimeout(
				function () { this._wizard.onStepEnd(this); }.bind(this),
				0
			);
		}
	};

	BX.Crm.DedupeWizardStep.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardStep();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardScanning) === "undefined")
{
	BX.Crm.DedupeWizardScanning = function ()
	{
		BX.Crm.DedupeWizardScanning.superclass.constructor.apply(this);

		this._indexRebuildContext = "";
		this._configHandler = BX.delegate(this.onConfigButtonClick, this);
		this._scanStartHandler = BX.delegate(this.onScanStartButtonClick, this);
		this._isScanRunning = false;
	};
	BX.extend(BX.Crm.DedupeWizardScanning, BX.Crm.DedupeWizardStep);

	/*
	BX.Crm.DedupeWizardScanning.prototype.initialize = function(id, settings)
	{
		BX.Crm.DedupeWizardScanning.superclass.initialize.apply(this, arguments);
	};
	*/
	BX.Crm.DedupeWizardScanning.prototype.start = function()
	{
		BX.Crm.DedupeWizardScanning.superclass.start.apply(this, arguments);
		this.layout();
	};
	BX.Crm.DedupeWizardScanning.prototype.layout = function()
	{
		this.adjustConfigurationTitle();

		var buttonBox = document.body.querySelector('.crm-dedupe-wizard-start-control-box');
		var button = BX(BX.prop.getString(this._settings, "buttonId"));
		if(button)
		{
			buttonBox.classList.add('crm-dedupe-wizard-start-control-box-default-state');
			BX.bind(button, "click", this._scanStartHandler);
		}

		var configButton = BX(BX.prop.getString(this._settings, "configEditButtonId"));
		if(configButton)
		{
			BX.bind(configButton, "click", this._configHandler);
		}

		BX.addCustomEvent(this._wizard, "onConfigChange", BX.delegate(this.onConfigChange, this));
	};
	BX.Crm.DedupeWizardScanning.prototype.adjustConfigurationTitle = function()
	{
		var titleElement = BX(BX.prop.getString(this._settings, "configTitleId"));
		if(!titleElement)
		{
			return;
		}

		var typeInfos = this._wizard.getTypeInfos();
		var config = this._wizard.getConfig();
		var selectedTypeNames = BX.prop.getArray(config, "typeNames", []);
		var currentScope = BX.prop.getString(config, "scope", "");

		var descriptions = [];

		for(var key in typeInfos)
		{
			if(!typeInfos.hasOwnProperty(key))
			{
				continue;
			}

			var typeInfo = typeInfos[key];
			if(currentScope === BX.prop.getString(typeInfo, "SCOPE")
				&& selectedTypeNames.indexOf(BX.prop.getString(typeInfo, "NAME")) >= 0
			)
			{
				descriptions.push(BX.prop.getString(typeInfo, "DESCRIPTION"));
			}
		}

		titleElement.innerHTML = BX.util.htmlspecialchars(descriptions.join(", "));

		BX.bind(titleElement, "click", this._configHandler);
	};
	BX.Crm.DedupeWizardScanning.prototype.onConfigChange = function()
	{
		this.adjustConfigurationTitle();
	};
	BX.Crm.DedupeWizardScanning.prototype.onConfigButtonClick = function(e)
	{
		e.preventDefault();

		if(this._isScanRunning)
		{
			return;
		}

		var dialog = BX.Crm.DedupeWizardConfigurationDialog.create(
			this._id,
			{
				wizard: this._wizard,
				scopeInfos: this._wizard.getScopeInfos()
			}
		);
		dialog.show();
	};
	BX.Crm.DedupeWizardScanning.prototype.onScanStartButtonClick = function(e)
	{
		this._indexRebuildContext = BX.util.getRandomString(8);
		if(this.rebuildIndex())
		{
			this.prepareProgressBar();

			BX(BX.prop.getString(this._settings, "buttonId")).style.display = "none";
			document.body.querySelector(".crm-dedupe-wizard-start-control-box").classList.remove("crm-dedupe-wizard-start-control-box-default-state");
			document.body.querySelector(".crm-dedupe-wizard-start-icon-scanning").classList.add("crm-dedupe-wizard-start-icon-refresh-repeat-animation");
		}
	};
	BX.Crm.DedupeWizardScanning.prototype.getNextStepId = function()
	{
		return this._wizard.getTotalItemCount() > 0 ? "merging" : "finish";
	};
	BX.Crm.DedupeWizardScanning.prototype.rebuildIndex = function()
	{
		var config = this._wizard.getConfig();
		var selectedTypeNames = BX.prop.getArray(config, "typeNames", []);
		var currentScope = BX.prop.getString(config, "scope", "");

		if(selectedTypeNames.length === 0)
		{
			BX.UI.Notification.Center.notify(
				{
					content: this.getMessage("emptyConfig"),
					position: "top-center",
					autoHideDelay: 5000
				}
			);
			return false;
		}

		this._isScanRunning = true;
		BX.ajax.runComponentAction("bitrix:crm.dedupe.wizard", "rebuildIndex", {
			data:
				{
					contextId: this._indexRebuildContext,
					entityTypeName: BX.CrmEntityType.resolveName(this._wizard.getEntityTypeId()),
					types: selectedTypeNames,
					scope: currentScope
				}
		}).then(
			function(response)
			{
				var data = response.data;
				var status = BX.prop.getString(data, "STATUS", "");

				var totalItems = BX.prop.getInteger(data, "TOTAL_ITEMS", 0);
				var processedItems = BX.prop.getInteger(data, "PROCESSED_ITEMS", 0);

				if(status === "PROGRESS")
				{
					window.setTimeout(
						function () { this.rebuildIndex(); }.bind(this),
						400
					);

					if(processedItems > 0 && totalItems > 0)
					{
						this.setProgressBarValue(100 * processedItems/totalItems);
					}
				}
				else if(status === "COMPLETED")
				{
					this.setProgressBarValue(100);

					this._wizard.setTotalItemCount(BX.prop.getInteger(data, "FOUND_ITEMS", 0));
					this._wizard.setTotalEntityCount(BX.prop.getInteger(data, "TOTAL_ENTITIES", 0));

					this._isScanRunning = false;
					window.setTimeout(function(){ this.end(); }.bind(this),  200);
				}
			}.bind(this)
		).catch(
			function(){ this._isScanRunning = false; }.bind(this)
		);

		return true;
	};
	BX.Crm.DedupeWizardScanning.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardScanning();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardMerging) === "undefined")
{
	BX.Crm.DedupeWizardMerging = function ()
	{
		BX.Crm.DedupeWizardMerging.superclass.constructor.apply(this);

		this._totalItemCount = 0;
		this._totalEntityCount = 0;

		this._currentItemIndex = 0;

		this._mergedItemCount = 0;
		this._conflictedItemCount = 0;

		this._mergeStartHandler = BX.delegate(this.onMergeStartButtonClick, this);
	};
	BX.extend(BX.Crm.DedupeWizardMerging, BX.Crm.DedupeWizardStep);
	/*
	BX.Crm.DedupeWizardMerging.prototype.initialize = function(id, settings)
	{
		BX.Crm.DedupeWizardMerging.superclass.initialize.apply(this, arguments);
	};
    */
	BX.Crm.DedupeWizardMerging.prototype.start = function()
	{
		this._totalItemCount = this._wizard.getTotalItemCount();
		this._totalEntityCount = this._wizard.getTotalEntityCount();
		this.layout();

		BX.Crm.DedupeWizardMerging.superclass.start.apply(this, arguments);
	};
	BX.Crm.DedupeWizardMerging.prototype.end = function()
	{
		BX.Crm.DedupeWizardMerging.superclass.end.apply(this, arguments);
	};
	BX.Crm.DedupeWizardMerging.prototype.layout = function()
	{
		this.getTitleWrapper().innerHTML = this.getMessage("duplicatesFound").replace("#COUNT#", this._totalEntityCount);
		this.getSubtitleWrapper().innerHTML = this.getMessage("matchesFound").replace("#COUNT#", this._totalItemCount);
		var buttonBox = document.body.querySelector('.crm-dedupe-wizard-start-control-box');
		var button = BX(BX.prop.getString(this._settings, "buttonId"));
		var icon = document.body.querySelector('.crm-dedupe-wizard-start-icon-merging');

		if(button)
		{
			buttonBox.classList.add('crm-dedupe-wizard-start-control-box-default-state');
			BX.bind(button, "click", this._mergeStartHandler);
			BX.bind(button, "click", function() {
				buttonBox.classList.remove('crm-dedupe-wizard-start-control-box-default-state');
				button.style.display = 'none';
				icon.classList.add('crm-dedupe-wizard-start-icon-refresh-repeat-animation');
			});
		}

		var listButtonId = BX(BX.prop.getString(this._settings, "listButtonId"));
		if(listButtonId)
		{
			BX.bind(listButtonId, "click", this.onListButtonClick.bind(this));
		}
	};
	BX.Crm.DedupeWizardMerging.prototype.merge = function()
	{
		var config = this._wizard.getConfig();
		var selectedTypeNames = BX.prop.getArray(config, "typeNames", []);
		var currentScope = BX.prop.getString(config, "scope", "");

		BX.ajax.runComponentAction("bitrix:crm.dedupe.wizard", "merge", {
			data:
				{
					entityTypeName: BX.CrmEntityType.resolveName(this._wizard.getEntityTypeId()),
					types: selectedTypeNames,
					scope: currentScope
				}
		}).then(
			function(response)
			{
				var data = BX.prop.getObject(response, "data", {});
				var status = BX.prop.getString(data, "STATUS", "");

				if(status === "SUCCESS")
				{
					this._mergedItemCount++;
				}
				else if(status === "CONFLICT")
				{
					this._conflictedItemCount++;
				}
				else if(status === "ERROR")
				{
					BX.UI.Notification.Center.notify(
						{
							content: BX.prop.getString(
								data,
								"MESSAGE",
								"Merge failed an error occurred during the merge operation."
							),
							position: "top-right",
							autoHideDelay: 5000
						}
					);
				}

				this._currentItemIndex++;
				if(status !== "COMPLETED")
				{
					window.setTimeout(
						function () { this.merge(); }.bind(this),
						400
					);

					this.setProgressBarValue(100 * this._currentItemIndex/this._totalItemCount);
				}
				else
				{
					this._wizard.setMergedItemCount(
						this._wizard.getTotalItemCount() - BX.prop.getInteger(data, "TOTAL_ITEMS", 0)
					);
					this._wizard.setMergedEntityCount(
						this._wizard.getTotalEntityCount() - BX.prop.getInteger(data, "TOTAL_ENTITIES", 0)
					);

					this._wizard.setConflictedItemCount(
						this._wizard.getTotalItemCount() - this._wizard.getMergedItemCount()
					);
					this._wizard.setConflictedEntityCount(
						this._wizard.getTotalEntityCount() - this._wizard.getMergedEntityCount()
					);

					this.setProgressBarValue(100);
					window.setTimeout(function(){ this.end(); }.bind(this),  200);
				}
			}.bind(this)
		);
	};
	BX.Crm.DedupeWizardMerging.prototype.onMergeStartButtonClick = function(e)
	{
		this._currentItemIndex = 0;

		this.prepareProgressBar();
		this.merge();
	};
	BX.Crm.DedupeWizardMerging.prototype.onListButtonClick = function(e)
	{
		this._wizard.openDedupeList();
		e.preventDefault();
	};
	BX.Crm.DedupeWizardMerging.prototype.getNextStepId = function()
	{
		if(this._wizard.getMergedItemCount() > 0)
		{
			return "mergingSummary";
		}
		return this._wizard.getConflictedItemCount() > 0 ? "conflictResolving" : "finish";
	};
	BX.Crm.DedupeWizardMerging.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardMerging();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardMergingSummary) === "undefined")
{
	BX.Crm.DedupeWizardMergingSummary = function()
	{
		BX.Crm.DedupeWizardMergingSummary.superclass.constructor.apply(this);

		this._buttonClickHandler = BX.delegate(this.onButtonClick, this);
	};
	BX.extend(BX.Crm.DedupeWizardMergingSummary, BX.Crm.DedupeWizardStep);
	BX.Crm.DedupeWizardMergingSummary.prototype.start = function()
	{
		this.layout();

		BX.Crm.DedupeWizardMergingSummary.superclass.start.apply(this, arguments);
	};
	BX.Crm.DedupeWizardMergingSummary.prototype.layout = function()
	{
		this.getTitleWrapper().innerHTML = this.getMessage("duplicatesProcessed").replace("#COUNT#", this._wizard.getMergedEntityCount());
		this.getSubtitleWrapper().innerHTML = this.getMessage("matchesProcessed").replace("#COUNT#", this._wizard.getMergedItemCount());

		var button = BX(BX.prop.getString(this._settings, "buttonId"));
		if(button)
		{
			BX.bind(button, "click", this._buttonClickHandler);
		}
	};
	BX.Crm.DedupeWizardMergingSummary.prototype.onButtonClick = function(e)
	{
		this.end();
	};
	BX.Crm.DedupeWizardMergingSummary.prototype.getNextStepId = function()
	{
		return this._wizard.getConflictedItemCount() > 0 ? "conflictResolving" : "finish";
	};
	BX.Crm.DedupeWizardMergingSummary.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardMergingSummary();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardConflictResolving) === "undefined")
{
	BX.Crm.DedupeWizardConflictResolving = function()
	{
		BX.Crm.DedupeWizardConflictResolving.superclass.constructor.apply(this);

		this._buttonClickHandler = BX.delegate(this.onButtonClick, this);
		this._externalEventHandler = null;
		this._contextId = "";
	};
	BX.extend(BX.Crm.DedupeWizardConflictResolving, BX.Crm.DedupeWizardStep);
	BX.Crm.DedupeWizardConflictResolving.prototype.start = function()
	{
		this.layout();

		BX.Crm.DedupeWizardMergingSummary.superclass.start.apply(this, arguments);
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.layout = function()
	{
		this.adjustTitle();
		var button = BX(BX.prop.getString(this._settings, "buttonId"));
		if(button)
		{
			BX.bind(button, "click", this._buttonClickHandler);
		}

		var listButtonId = BX(BX.prop.getString(this._settings, "listButtonId"));
		if(listButtonId)
		{
			BX.bind(listButtonId, "click", this.onListButtonClick.bind(this));
		}
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.adjustTitle = function()
	{
		this.getTitleWrapper().innerHTML = this.getMessage("duplicatesConflicted").replace("#COUNT#", this._wizard.getConflictedEntityCount());
		this.getSubtitleWrapper().innerHTML = this.getMessage("matchesConflicted").replace("#COUNT#", this._wizard.getConflictedItemCount());
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.onButtonClick = function(e)
	{
		this._contextId = this._wizard.getContextId() + "_" + BX.util.getRandomString(6).toUpperCase();

		this._wizard.openMerger(this._contextId);

		if(!this._externalEventHandler)
		{
			this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
			BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
		}
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.onListButtonClick = function(e)
	{
		this._wizard.openDedupeList();
		e.preventDefault();
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.onExternalEvent = function(params)
	{
		var eventName = BX.prop.getString(params, "key", "");

		if(eventName !== "onCrmEntityMergeComplete")
		{
			return;
		}

		var value = BX.prop.getObject(params, "value", {});
		if(this._contextId !== BX.prop.getString(value, "context", ""))
		{
			return;
		}

		var entityTypeName = BX.prop.getString(value, "entityTypeName", "");
		if(entityTypeName !== this._wizard.getEntityTypeName())
		{
			return;
		}

		var currentConflictedItemCount = BX.prop.getInteger(value, "length", -1);
		if(currentConflictedItemCount >= 0)
		{
			var conflictedItemCount = this._wizard.getConflictedItemCount();
			if(conflictedItemCount >= currentConflictedItemCount)
			{
				this._wizard.setResolvedItemCount(conflictedItemCount - currentConflictedItemCount);
			}
			else
			{
				this._wizard.setResolvedItemCount(0);
				this._wizard.setConflictedItemCount(currentConflictedItemCount);
			}
		}

		if(this._wizard.getUnResolvedItemCount() === 0)
		{
			window.setTimeout(function(){ this.end(); }.bind(this),  0);
		}
	};
	BX.Crm.DedupeWizardConflictResolving.prototype.getNextStepId = function()
	{
		return "finish";
	};
	BX.Crm.DedupeWizardConflictResolving.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardConflictResolving();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.Crm.DedupeWizardConfigurationDialog) === "undefined")
{
	BX.Crm.DedupeWizardConfigurationDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._wizard = null;
		this._scope = "";
		this._selectedTypeNames = [];

		this._wrapper = null;
		this._scopeSelector = null;
		this._criterionList = null;
		this._criterionCheckBoxes = null;

		this._dlg = null;
		this._slider = null;
		this._isShown = false;

		this._saveButtonClickHandler = this.onSaveButtonClick.bind(this);
		this._cancelButtonClickHandler = this.onCancelButtonClick.bind(this);
	};

	BX.Crm.DedupeWizardConfigurationDialog.prototype =
	{
		initialize: function (id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
			this._settings = settings ? settings : {};

			this._wizard = BX.prop.get(this._settings, "wizard");

			var config = this._wizard.getConfig();
			this._scope = BX.prop.getString(config, "scope", "");
			this._selectedTypeNames = BX.prop.getArray(config, "typeNames", []);
		},
		getMessage: function(name)
		{
			return BX.prop.getString(BX.Crm.DedupeWizardConfigurationDialog.messages, name, name);
		},
		selectAll: function(selectAll)
		{
			if(!this._criterionCheckBoxes)
			{
				return;
			}

			var checked = !!selectAll;
			for(var i = 0, length = this._criterionCheckBoxes.length; i < length; i++)
			{
				this._criterionCheckBoxes[i].checked = checked;
			}
		},
		show: function()
		{
			if(this._isShown)
			{
				return;
			}

			BX.SidePanel.Instance.open("crm:dedupe-wizard-configuration",
				{
					data: {},
					cacheable: false,
					contentCallback: function(slider)
					{
						var promise = new BX.Promise();
						window.setTimeout(
							function(){ promise.fulfill(this.prepareDialogContent()); }.bind(this),
							0
						);
						return promise;
					}.bind(this),
					width: 600,
					events:
					{
						onOpenComplete: this.onSliderOpen.bind(this),
						onClose: this.onSliderClose.bind(this)
					}
				}
			);
		},
		close: function()
		{
			if(!this._isShown)
			{
				return;
			}

			if(this._slider)
			{
				this._slider.close(true);
			}
		},
		prepareDialogContent: function()
		{
			var scopeInfos = this._wizard.getScopeInfos();
			this._title = BX.create("h1", {
				attrs: {className: "crm-dedupe-wizard-search-settings-title"},
				text: this.getMessage("title")
			});
			this._wrapper = BX.create("div", { attrs: {className: "crm-dedupe-wizard-search-settings"}});
			this._content = BX.create("div", { attrs: {className: "crm-dedupe-wizard-search-settings-content"}});
			this._wrapper.appendChild(this._title);
			this._wrapper.appendChild(this._content);
			this._content.appendChild(BX.create("h4", {
				attrs: {className: "crm-dedupe-wizard-search-settings-subtitle"},
				text: this.getMessage("scopeCaption")
			}));

			var scopeSelectorOptions = [];

			for(var scopeKey in scopeInfos)
			{
				if(!scopeInfos.hasOwnProperty(scopeKey))
				{
					continue;
				}

				scopeSelectorOptions.push(
					BX.create("option",
						{
							attrs : { value: scopeKey !== "" ? scopeKey : "-", selected: this._scope === scopeKey },
							text: scopeInfos[scopeKey]
						}
					)
				);
			}

			this._scopeSelector = BX.create("select",
				{
					attrs: { className: "ui-ctl-element" },
					children: scopeSelectorOptions,
					events : { click: BX.delegate(this.onScopeChange, this) }
				}
			);

			this._content.appendChild(
				BX.create("div",
					{
						props: { className: "ui-ctl ui-ctl-after-icon ui-ctl-dropdown crm-dedupe-wizard-search-settings-input" },
						children:
							[
								BX.create("div", {
									props: {className: "ui-ctl-after ui-ctl-icon-angle"}
								}),
								this._scopeSelector
							]
					}
				)
			);

			this._content.appendChild(BX.create("h4", {
				attrs: {className: "crm-dedupe-wizard-search-settings-subtitle"},
				text: this.getMessage("criterionCaption")
			}));
			this._content.appendChild(
				BX.create("div",
					{
						props: { className: "crm-dedupe-wizard-search-settings-control-box" },
						children:
							[
								BX.create("button",
									{
										props: { className: "crm-dedupe-wizard-search-settings-control" },
										text: this.getMessage("selectAll"),
										events : { click: BX.delegate(this.onSelectAllButtonClick, this) }
									}
								),
								BX.create("button",
									{
										props: { className: "crm-dedupe-wizard-search-settings-control" },
										text: this.getMessage("unselectAll"),
										events : { click: BX.delegate(this.onUnselectAllButtonClick, this) }
									}
								)
							]
					}
				)
			);

			this._criterionList = BX.create("ul", { attrs: { className: "crm-dedupe-wizard-search-settings-list" } });
			this._content.appendChild(this._criterionList);

			this._criterionCheckBoxes = [];
			this.adjustCriterionList();

			BX.bind(BX("ui-button-panel-save"), "click", this._saveButtonClickHandler);
			BX.bind(BX("ui-button-panel-cancel"), "click", this._cancelButtonClickHandler);

			var buttonPanel = BX("crmDedupeWizardBtnPanel");
			buttonPanel.style.display = "";
			this._wrapper.appendChild(buttonPanel);

			return this._wrapper;
		},
		adjustCriterionList: function()
		{
			while(this._criterionList.firstChild)
			{
				this._criterionList.removeChild(this._criterionList.firstChild);
			}
			this._criterionCheckBoxes = [];

			var typeInfos = this._wizard.getTypeInfos();
			for(var extendedId in typeInfos)
			{
				if(!typeInfos.hasOwnProperty(extendedId))
				{
					continue;
				}

				var typeInfo = typeInfos[extendedId];
				if(BX.prop.getString(typeInfo, "SCOPE", "") !== this._scope)
				{
					continue;
				}

				var typeName = BX.prop.getString(typeInfo, "NAME", "");
				var criterionCheckBox = BX.create("input",
					{ attrs: { type: "checkbox" }, props: { id: extendedId, name: typeName, className: "crm-dedupe-wizard-search-settings-checkbox" } }
				);

				if(this._selectedTypeNames.indexOf(typeName) >= 0)
				{
					criterionCheckBox.checked = true;
				}

				this._criterionCheckBoxes.push(criterionCheckBox);

				this._criterionList.appendChild(
					BX.create("li",
						{
							props: { className: "crm-dedupe-wizard-search-settings-list-item" },
							children:
								[
									BX.create("label",
										{
											attrs: { for: extendedId },
											props: { className: "crm-dedupe-wizard-search-settings-label" },
											children:
												[
													criterionCheckBox,
													document.createTextNode(
														BX.prop.getString(typeInfo, "DESCRIPTION", "[" + typeName + "]")
													)
												]
										}
									)
								]
						}
					)
				);
			}
		},
		onScopeChange: function()
		{
			var val = this._scopeSelector.value === "-" ? "" : this._scopeSelector.value;
			if(this._scope !== val)
			{
				this._scope = val;
				this.adjustCriterionList();
			}
		},
		onSelectAllButtonClick: function(e)
		{
			this.selectAll(true);
		},
		onUnselectAllButtonClick: function(e)
		{
			this.selectAll(false);
		},
		onSaveButtonClick: function(e)
		{
			this._selectedTypeNames = [];
			for(var i = 0, length = this._criterionCheckBoxes.length; i < length; i++)
			{
				var criterionCheckBox = this._criterionCheckBoxes[i];
				if(criterionCheckBox.checked)
				{
					this._selectedTypeNames.push(criterionCheckBox.name);
				}
			}

			var config = this._wizard.getConfig();
			config["typeNames"] = this._selectedTypeNames;
			config["scope"] = this._scope;

			this._wizard.setConfig(config);
			this._wizard.saveConfig().then(function() { this.close(); }.bind(this));
		},
		onCancelButtonClick: function(e)
		{
			this.close();
		},
		onSliderOpen: function(event)
		{
			this._slider = event.getSlider();
			this._isShown = true;
		},
		onSliderClose: function(event)
		{
			if(this._slider)
			{
				BX.unbind(BX("ui-button-panel-save"), "click", this._saveButtonClickHandler);
				BX.unbind(BX("ui-button-panel-cancel"), "click", this._cancelButtonClickHandler);

				var buttonPanel = BX("crmDedupeWizardBtnPanel");
				buttonPanel.style.display = "none";
				if (BX("ui-button-panel-save").classList.contains('ui-btn-wait'))
				{
					BX("ui-button-panel-save").classList.remove('ui-btn-wait');
				}
				document.body.appendChild(buttonPanel);

				this._slider.destroy();
				this._slider = null;
			}

			this._isShown = false;
		}
	};
	if(typeof(BX.Crm.DedupeWizardConfigurationDialog.messages) === "undefined")
	{
		BX.Crm.DedupeWizardConfigurationDialog.messages = {};
	}
	BX.Crm.DedupeWizardConfigurationDialog.create = function(id, settings)
	{
		var self = new BX.Crm.DedupeWizardConfigurationDialog();
		self.initialize(id, settings);
		return self;
	};
}
