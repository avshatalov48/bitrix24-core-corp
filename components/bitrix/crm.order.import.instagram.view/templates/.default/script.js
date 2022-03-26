+BX.namespace("BX.Crm.Instagram.TileGrid");

BX.Crm.Instagram.Status = {
	INITIAL: 1,
	PROCESS: 2,
	STOPPED: 3,
	FINISHED: 4,
	BEFORE_RENDERED: 5,
	AFTER_RENDERED: 6
};

/**
 *
 * @param options
 * @extends {BX.TileGrid.List}
 * @constructor
 */
BX.Crm.Instagram.TileGrid.List = function(options)
{
	this.params = options.params;
	this.signedParameters = options.signedParameters;
	this.componentName = options.componentName;

	this.lastViewedTimestamp = options.lastViewedTimestamp;
	this.haveItemsToImport = options.haveItemsToImport;

	this.filterId = options.filterId;
	this.gridId = options.gridId;
	this.grid = BX.Main.tileGridManager.getById(this.gridId).instance;

	BX.removeClass(this.grid.renderTo, 'disk-tile-grid');
	BX.addClass(this.grid.renderTo, 'crm-order-instagram-grid');

	this.grid.setMultiSelectMode();

	this.state = BX.Crm.Instagram.Status.INITIAL;

	this.totalImportedProducts = 0;
	this.progressBarAnimation = null;
	this.progressBarLastStep = null;

	this.drawFooter();
	this.drawStoreFooter();
	this.drawStepImportFooter();

	this.handleDescription();
	this.handleSelectControls();
	this.handleFooter();

	this.bindCustomEvents();
};

BX.Crm.Instagram.TileGrid.List.prototype =
	{
		handleDescription: function()
		{
			if (this.state >= BX.Crm.Instagram.Status.FINISHED)
			{
				this.showImportedDescription();
			}
			else if (this.haveItemsToImport)
			{
				this.showDescription(BX.message('CRM_OIIV_SELECT_MEDIA'));
			}
			else
			{
				this.showDescription(BX.message('CRM_OIIV_SELECT_NO_MEDIA'));
			}
		},

		showImportedDescription: function()
		{
			var container = document.querySelector('[data-entity="parent-container"]');

			if (BX.type.isDomNode(container))
			{
				BX.addClass(container, 'crm-order-instagram-view-imported');

				var description = container.querySelector('[data-entity="step-description-text"]');

				if (BX.type.isDomNode(description))
				{
					description.textContent = BX.message('CRM_OIIV_STEP_IMPORTED') + ': ' + this.grid.items.length;
				}

				description = container.querySelector('[data-entity="total-description-text"]');

				if (BX.type.isDomNode(description))
				{
					description.textContent = BX.message('CRM_OIIV_TOTAL_IMPORTED') + ': ' + this.totalImportedProducts;
				}
			}
		},

		showDescription: function(message)
		{
			var container = document.querySelector('[data-entity="parent-container"]');

			if (BX.type.isDomNode(container))
			{
				BX.removeClass(container, 'crm-order-instagram-view-imported');

				var description = container.querySelector('[data-entity="step-description-text"]');

				if (BX.type.isDomNode(description))
				{
					description.textContent = message;
				}

				description = container.querySelector('[data-entity="total-description-text"]');

				if (BX.type.isDomNode(description))
				{
					description.textContent = '';
				}
			}
		},

		handleSelectControls: function()
		{
			if (
				this.state === BX.Crm.Instagram.Status.INITIAL
				&& this.grid.items.length
				&& this.hasItemsToImport()
			)
			{
				this.showSelectorControls();
			}
			else
			{
				this.hideSelectorControls();
			}
		},

		hasItemsToImport: function()
		{
			return this.grid.items.some(function(item)
			{
				return !item.imported;
			});
		},

		getLinkWithLastViewedTimestamp: function(link)
		{
			return BX.util.add_url_param(link, {
				'last_viewed_timestamp': this.lastViewedTimestamp,
				'show_new': 'n'
			});
		},

		handleBeforeGridRequest: function(ctx, requestParams)
		{
			if (this.gridId !== requestParams.gridId)
			{
				return;
			}

			if (requestParams.url)
			{
				requestParams.url = this.getLinkWithLastViewedTimestamp(requestParams.url);
			}
		},

		handleBeforeRedraw: function()
		{
			this.grid.items.forEach(function(item)
			{
				BX.remove(item.layout.container);
			}, this);
		},

		handleFooter: function()
		{
			if (this.state === BX.Crm.Instagram.Status.PROCESS)
			{
				BX.removeClass(this.footer, 'crm-section-control-active');
				BX.removeClass(this.storeFooter, 'crm-section-control-active');
				BX.addClass(this.stepImportFooter, 'crm-section-control-active');
			}
			else if (this.state === BX.Crm.Instagram.Status.STOPPED)
			{
				BX.removeClass(this.footer, 'crm-section-control-active');
				BX.removeClass(this.storeFooter, 'crm-section-control-active');
				BX.removeClass(this.stepImportFooter, 'crm-section-control-active');
			}
			else if (this.state !== BX.Crm.Instagram.Status.FINISHED)
			{
				if (this.state >= BX.Crm.Instagram.Status.BEFORE_RENDERED || this.getCheckedItems().length === 0)
				{
					BX.removeClass(this.footer, 'crm-section-control-active');
					BX.removeClass(this.stepImportFooter, 'crm-section-control-active');
					BX.addClass(this.storeFooter, 'crm-section-control-active');
				}
				else
				{
					BX.removeClass(this.storeFooter, 'crm-section-control-active');
					BX.removeClass(this.stepImportFooter, 'crm-section-control-active');
					BX.addClass(this.footer, 'crm-section-control-active');
				}
			}
		},

		bindCustomEvents: function()
		{
			BX.bind(document.querySelector('[data-entity="tile-grid-import-more"]'), 'click', this.handleImportMore.bind(this));

			BX.bind(document.querySelector('[data-entity="tile-grid-select-all"]'), 'click', this.handleCheckAllItems.bind(this));
			BX.bind(document.querySelector('[data-entity="tile-grid-unselect-all"]'), 'click', this.handleUnCheckAllItems.bind(this));

			BX.addCustomEvent('BX.TileGrid.Grid:beforeReload', this.handleBeforeGridRequest.bind(this));
			BX.addCustomEvent('BX.TileGrid.Grid:beforeRedraw', this.handleBeforeRedraw.bind(this));
			BX.addCustomEvent('BX.TileGrid.Grid:checkItem', this.handleFooter.bind(this));
			BX.addCustomEvent('BX.TileGrid.Grid:unCheckItem', this.handleFooter.bind(this));
			BX.addCustomEvent('BX.TileGrid.Grid:afterResetSelectAllItems', this.handleFooter.bind(this));
			BX.addCustomEvent('BX.TileGrid.Grid:multiSelectModeOff', this.handleMultiSelectModeOff.bind(this));

			BX.addCustomEvent('BX.TileGrid.Grid:selectItem', this.handleUnselectItem.bind(this));
			BX.addCustomEvent('BX.TileGrid.Grid:checkItem', this.handleCheckItem.bind(this));

			BX.addCustomEvent('SidePanel.Slider:onClose', this.handleSliderClose.bind(this));

			BX.addCustomEvent('BX.Main.Filter:beforeApply', this.onBeforeFilterApply.bind(this));
			BX.addCustomEvent('BX.Main.Filter:apply', this.onFilterApply.bind(this));
		},

		handleCheckItem: function(item)
		{
			if (this.state === BX.Crm.Instagram.Status.INITIAL && item.imported)
			{
				this.grid.unCheckItem(item);
			}
		},

		handleUnselectItem: function(item)
		{
			if (this.state === BX.Crm.Instagram.Status.INITIAL && item.imported)
			{
				this.grid.unSelectItem(item);
			}
		},

		handleImportMore: function(event)
		{
			this.addLoader();
			document.location.href = this.getLinkWithLastViewedTimestamp(document.location.href);
			event.preventDefault();
		},

		handleCheckAllItems: function(event)
		{
			this.grid.selectAllItems();
			event.preventDefault();
		},

		handleUnCheckAllItems: function(event)
		{
			this.uncheckAllItems();
			event.preventDefault();
		},

		handleMultiSelectModeOff: function()
		{
			this.grid.setMultiSelectMode();
		},

		handleSliderClose: function()
		{
			if (this.state > BX.Crm.Instagram.Status.INITIAL)
			{
				var grid = this.getCatalogGridInstance();

				if (grid)
				{
					grid.reloadTable('POST');
				}
			}
		},

		onBeforeFilterApply: function(filterId, data, filter, promise)
		{
			if (filterId !== this.filterId)
			{
				return;
			}

			promise.then(function()
			{
				this.fade();
			}.bind(this));
		},

		onFilterApply: function(filterId, data, filter, promise, params)
		{
			if (filterId !== this.filterId)
			{
				return;
			}

			promise
				.then(function()
				{
					return this.grid.reload();
				}.bind(this))
				.then(function()
				{
					this.handleSelectControls();
					this.unFade();
				}.bind(this));
		},

		fade: function()
		{
			this.grid.setFadeContainer();
			this.grid.getLoader();
			this.grid.showLoader();
		},

		unFade: function()
		{
			this.grid.getLoader().hide();
			this.grid.unSetFadeContainer();
		},

		importButtonHandler: function(event)
		{
			var items = [];

			this.getCheckedItems().forEach(function(item)
			{
				items.push(item.getRawData(true));
			});

			if (items.length)
			{
				Promise.resolve()
					.then(function()
					{
						this.state = BX.Crm.Instagram.Status.PROCESS;
						this.lockActionButton(BX.getEventTarget(event));
					}.bind(this))
					.then(this.startImport())
					.then(this.import(items))
					.then(this.finishImport())
					.then(function()
					{
						this.state = BX.Crm.Instagram.Status.AFTER_RENDERED;
						this.unlockActionButton(BX.getEventTarget(event));
					}.bind(this))
					.catch(function(errors)
					{
						if (this.state !== BX.Crm.Instagram.Status.STOPPED)
						{
							this.failProgressBar();
							this.disableDotAnimation();
							this.disableActionButton();
							this.showStepImportTitle(BX.message('CRM_OIIV_STEP_STOPPED_TITLE'));
							this.unlockActionButton();
							this.handleFooter();
							this.showImportMoreButton();
						}

						this.fillErrors(errors);
					}.bind(this));
			}
		},

		startImport: function()
		{
			return function()
			{
				this.importedItems = {};
				this.showStepImport();
				this.handleFooter();
			}.bind(this);
		},

		import: function(items)
		{
			return function()
			{
				var queue = Promise.resolve();

				var chunks = [];
				var itemsClone = [].concat(items);

				while (itemsClone.length)
				{
					chunks.push(itemsClone.splice(0, this.params.IMPORT_MEDIA_STEP));
				}

				chunks.forEach(function(chunk, index)
				{
					queue = queue
						.then(function()
						{
							if (this.state === BX.Crm.Instagram.Status.STOPPED)
							{
								return Promise.reject();
							}

							this.animateProgressBar(index + 1, chunks.length, chunk.length);

							var lastStep = index + 1 === chunks.length;

							if (lastStep)
							{
								this.disableActionButton();
							}

							return this.requestImportChunk(chunk, lastStep, items.length);
						}.bind(this))
						.then(function(data)
						{
							BX.merge(this.importedItems, data.addedItems);

							if (BX.type.isNumber(data.total))
							{
								this.totalImportedProducts = parseInt(data.total);
							}
						}.bind(this))
						.catch(function(errors)
						{
							return Promise.reject(errors);
						}.bind(this));
				}.bind(this));

				return queue;
			}.bind(this);
		},

		finishImport: function()
		{
			return function()
			{
				return Promise.resolve()
					.then(function()
					{
						this.state = BX.Crm.Instagram.Status.FINISHED;
					}.bind(this))
					.then(this.processSuccessImport())
					.then(this.delay(1500))
					.then(function()
					{
						this.state = BX.Crm.Instagram.Status.BEFORE_RENDERED;

						this.handleActiveContainer();
						this.showImportMoreButton();
						this.handleFooter();
						this.makeImportNotification();
						this.clearErrors();
						this.setGridFilter();
					}.bind(this));
			}.bind(this);
		},

		requestImportChunk: function(chunk, lastStep, totalCount)
		{
			return new Promise(function(resolve, reject)
				{
					BX.ajax.runComponentAction(
						this.componentName,
						'importAjax',
						{
							mode: 'class',
							signedParameters: this.signedParameters,
							data: {
								analyticsLabel: {
									source: 'InstagramStore',
									entity: 'Import',
									action: 'requestImportChunk'
								},
								items: chunk,
								totalCount: totalCount,
								total: lastStep ? 'Y' : 'N'
							}
						}
					)
						.then(function(response)
						{
							resolve(response.data);
						}.bind(this))
						.catch(function(response)
						{
							reject(BX.util.array_values(response.errors));
						}.bind(this));
				}.bind(this)
			);
		},

		addLoader: function()
		{
			if (!this.loaderNode)
			{
				this.loaderNode = BX.create('div', {
					props: {
						className: 'side-panel-overlay side-panel-overlay-open',
						style: 'position: fixed; background-color: rgba(255, 255, 255, .7);'
					},
					children: [
						BX.create('div', {
							props: {
								className: 'side-panel-default-loader-container'
							},
							html:
								'<svg class="side-panel-default-loader-circular" viewBox="25 25 50 50">' +
								'<circle ' +
								'class="side-panel-default-loader-path" ' +
								'cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"' +
								'/>' +
								'</svg>'
						})
					]
				});

				document.body.appendChild(this.loaderNode);
			}
		},

		removeLoader: function()
		{
			this.loaderNode.parentNode.removeChild(this.loaderNode);
		},

		processSuccessImport: function()
		{
			return function()
			{
				this.finishProgressBar();
				this.hideFilter();
				BX.addClass(this.grid.renderTo, 'crm-order-instagram-grid-imported');
				this.grid.redraw(this.filterImportedItems(this.importedItems));
				this.handleDescription();
				this.handleSelectControls();
			}.bind(this);
		},

		filterImportedItems: function(items)
		{
			return this.grid.items.filter(function(item)
			{
				if (items.hasOwnProperty(item.id))
				{
					item.setProductId(items[item.id]);
					item.setEditable(true);

					return true;
				}

				return false;
			});
		},

		getCatalogGridInstance: function()
		{
			if (window.top.BX.Main.gridManager)
			{
				var result = window.top.BX.Main.gridManager.data.filter(function(current)
				{
					return current.id.indexOf('tbl_product_admin') === 0;
				});

				if (result[0] && result[0].hasOwnProperty('instance'))
				{
					return result[0].instance;
				}
			}

			return false;
		},

		getCatalogFilterInstance: function()
		{
			if (window.top.BX.Main.filterManager)
			{
				for (var i in window.top.BX.Main.filterManager.data)
				{
					if (window.top.BX.Main.filterManager.data.hasOwnProperty(i) && i.indexOf('tbl_product_admin') === 0)
					{
						return window.top.BX.Main.filterManager.data[i];
					}
				}
			}

			return false;
		},

		setGridFilter: function()
		{
			if (!this.params.IFRAME)
				return;

			var catalogGridInstance = this.getCatalogGridInstance();

			if (catalogGridInstance.filterApplied)
				return;

			var filterInstance = this.getCatalogFilterInstance();

			if (filterInstance && !filterInstance.filterApplied)
			{
				filterInstance.getSearch().clearInput();
				filterInstance.getPreset().applyPreset('import_instagram');
				filterInstance.applyFilter();

				catalogGridInstance.filterApplied = true;
			}
		},

		makeImportNotification: function()
		{
			window.top.BX.UI.Notification.Center.notify({
				content: BX.message('CRM_OIIV_PRODUCTS_ADDED_SUCCESSFUL'),
				category: 'InstagramStore::general',
				width: 'auto'
			});
		},

		fillErrors: function(errors)
		{
			if (!errors || !BX.type.isArray(errors))
				return;

			var errorNode = document.querySelector('div.crm-section-control-active div.crm-entity-section-control-error-block');

			if (BX.type.isDomNode(errorNode))
			{
				var html = '';

				errors.forEach(function(error)
				{
					if (error.code === 'NETWORK_ERROR')
					{
						error.message = BX.message('CRM_OIIV_IMPORT_NETWORK_ERROR');
					}

					html += '<div class="crm-entity-section-control-error-text">' + error.message + '</div>';
				});

				errorNode.innerHTML = html;
			}
		},

		clearErrors: function()
		{
			BX.cleanNode(BX('bx-crm-error'));
		},

		unlockActionButton: function(button)
		{
			button = button || document.querySelector('div.crm-section-control-active .ui-btn-clock');

			if (BX.type.isDomNode(button))
			{
				BX.removeClass(button, 'ui-btn-clock');
			}
		},

		lockActionButton: function(button)
		{
			button = button || document.querySelector('div.crm-section-control-active .ui-btn-clock');

			if (BX.type.isDomNode(button))
			{
				BX.addClass(button, 'ui-btn-clock');
			}
		},

		disableActionButton: function(button)
		{
			button = button || document.querySelector('div.crm-section-control-active .ui-btn-success');

			if (BX.type.isDomNode(button))
			{
				BX.addClass(button, 'ui-btn-disabled');
			}
		},

		showStepImport: function()
		{
			this.handleActiveContainer();
			this.showProgressBar();
			this.enableDotAnimation();
		},

		handleActiveContainer: function()
		{
			if (this.state === BX.Crm.Instagram.Status.PROCESS)
			{
				BX.show(document.querySelector('[data-entity="import-step-process"]'));
				BX.hide(document.querySelector('[data-entity="import-selector"]'));
			}
			else
			{
				BX.show(document.querySelector('[data-entity="import-selector"]'));
				BX.hide(document.querySelector('[data-entity="import-step-process"]'));
			}
		},

		delay: function(delay)
		{
			delay = BX.type.isNumber(delay) ? delay : 0;

			return function()
			{
				return new Promise(function(resolve)
				{
					setTimeout(resolve, delay);
				});
			}
		},

		showProgressBar: function()
		{
			this.progressBar = new BX.UI.ProgressBar({
				maxValue: 100,
				value: 0,
				column: true,
				statusType: BX.UI.ProgressBar.Status.PERCENT
			});

			BX.addClass(this.progressBar.getContainer(), 'crm-order-instagram-view-block-progressbar');
			document.querySelector('[data-entity="progress-bar"]').appendChild(this.progressBar.getContainer());
		},

		animateProgressBar: function(step, steps, stepItems)
		{
			if (this.progressBarAnimation === null)
			{
				this.progressBarAnimation = new BX.easing({
					transition: BX.easing.makeEaseInOut(BX.easing.transitions.linear),
					step: function(state)
					{
						this.progressBar.update(state.value);
					}.bind(this)
				});
			}

			this.progressBarAnimation.stop();

			this.progressBarAnimation.options.duration = this.getProgressBarStepDuration(stepItems);
			this.progressBarAnimation.options.start = {
				value: this.progressBar.getValue()
			};
			this.progressBarAnimation.options.finish = {
				value: step / steps * 100
			};

			this.progressBarAnimation.animate();
		},

		finishProgressBar: function()
		{
			if (this.progressBarAnimation)
			{
				this.progressBarAnimation.stop();

				this.progressBarAnimation.options.duration = 500;
				this.progressBarAnimation.options.start = {
					value: this.progressBar.getValue()
				};
				this.progressBarAnimation.options.finish = {
					value: this.progressBar.getMaxValue()
				};
				this.progressBarAnimation.options.complete = function()
				{
					this.showStepImportTitle(BX.message('CRM_OIIV_STEP_READY_TITLE'));
					this.disableDotAnimation();
				}.bind(this);

				this.progressBarAnimation.animate();
			}
		},

		failProgressBar: function()
		{
			if (this.progressBarAnimation)
			{
				this.progressBarAnimation.stop();
				this.progressBar.setColor(BX.UI.ProgressBar.Color.DANGER);
			}
		},

		getProgressBarStepDuration: function(stepItems)
		{
			var duration;

			if (BX.type.isNumber(this.progressBarLastStep))
			{
				duration = Date.now() - this.progressBarLastStep;
			}
			else
			{
				duration = 15000;
			}

			duration = duration * stepItems / this.params.IMPORT_MEDIA_STEP;

			this.progressBarLastStep = Date.now();

			return duration;
		},

		showStepImportTitle: function(message)
		{
			var node = document.querySelector('[data-entity="step-import-title"]');

			if (BX.type.isDomNode(node))
			{
				node.textContent = message || '';
			}
		},

		enableDotAnimation: function()
		{
			this.importAnimation = setInterval(function()
			{
				var node = document.querySelector('[data-entity="step-import-pointer"]');

				if (BX.type.isDomNode(node))
				{
					if (node.textContent[node.textContent.length - 1] === '.')
					{
						node.textContent = String.fromCharCode(160) + String.fromCharCode(160) + String.fromCharCode(160);
					}

					for (var i = 0; i < node.textContent.length; i++)
					{
						if (node.textContent[i] === String.fromCharCode(160))
						{
							node.textContent = node.textContent.substr(0, i) + '.' + node.textContent.substr(i + 1);
							break;
						}
					}
				}
			}, 1000);
		},

		disableDotAnimation: function()
		{
			clearTimeout(this.importAnimation);

			var node = document.querySelector('[data-entity="step-import-pointer"]');

			if (BX.type.isDomNode(node))
			{
				node.textContent = '';
			}
		},

		uncheckAllItems: function()
		{
			this.grid.items.forEach(function(item)
			{
				this.grid.unSelectItem(item);
				this.grid.unCheckItem(item);
			}, this);
		},

		cancelBtnHandler: function(event)
		{
			this.uncheckAllItems();
		},

		getTemplatePreviewInstance: function()
		{
			var context;

			var openSliders = window.top.BX.SidePanel.Instance.getOpenSliders();

			for (var i = 0; i < openSliders.length; i++)
			{
				context = openSliders[i].getWindow();

				if (context.BX.Landing && context.BX.Landing.TemplatePreviewInstance)
				{
					return context.BX.Landing.TemplatePreviewInstance;
				}
			}

			return null;
		},

		createStoreBtnHandler: function(event)
		{
			var templatePreviewInstance = this.getTemplatePreviewInstance();

			if (templatePreviewInstance)
			{
				BX.addClass(templatePreviewInstance.createByImportButton, 'ui-btn-wait');
				templatePreviewInstance.onCreateButtonClick(event);
				window.top.BX.SidePanel.Instance.getTopSlider().close();
			}
		},

		closeBtnHandler: function(event)
		{
			this.cancelBtnHandler(event);
			window.top.BX.SidePanel.Instance.getTopSlider().close();
		},

		cancelStepImport: function(event)
		{
			if (this.state === BX.Crm.Instagram.Status.STOPPED)
				return;

			if (BX.hasClass(BX.getEventTarget(event), 'ui-btn-disabled'))
				return;

			this.state = BX.Crm.Instagram.Status.STOPPED;

			this.failProgressBar();
			this.showStepImportTitle(BX.message('CRM_OIIV_STEP_STOPPED_TITLE'));
			this.disableDotAnimation();
			this.unlockActionButton();
			this.showImportMoreButton();
			this.handleFooter();
		},

		getCheckedItems: function()
		{
			return this.grid.items.filter(function(item)
			{
				return item.checked;
			});
		},

		showImportMoreButton: function()
		{
			BX.show(document.querySelector('[data-entity="tile-grid-import-more"]'));
		},

		hideFilter: function()
		{
			BX.hide(document.querySelector('[data-entity="filter"]'));
		},

		hideSelectorControls: function()
		{
			BX.hide(document.querySelector('[data-entity="select-controls"]'));
		},

		showSelectorControls: function()
		{
			BX.show(document.querySelector('[data-entity="select-controls"]'));
		},

		drawFooter: function()
		{
			this.footer = BX.create('div', {
				props: {className: 'crm-entity-wrap'},
				children: [
					BX.create('div', {
						props: {className: 'crm-entity-section crm-entity-section-control'},
						children: [
							BX.create('button', {
								props: {
									className: 'ui-btn ui-btn-success'
								},
								text: BX.message('CRM_OIIV_IMPORT'),
								events: {
									click: this.importButtonHandler.bind(this)
								}
							}),
							BX.create('button', {
								props: {
									className: 'ui-btn ui-btn-link'
								},
								text: BX.message('CRM_OIIV_CANCEL'),
								events: {
									click: this.cancelBtnHandler.bind(this)
								}
							}),
							BX.create('div', {
								props: {
									className: 'crm-entity-section-control-error-block'
								}
							})
						]
					})
				]
			});
			document.querySelector('[data-entity="footer"]').appendChild(this.footer);
		},

		canCreateStore: function()
		{
			return this.getTemplatePreviewInstance() !== null;
		},

		drawStoreFooter: function()
		{
			if (!this.canCreateStore())
				return;

			this.storeFooter = BX.create('div', {
				props: {className: 'crm-entity-wrap'},
				children: [
					BX.create('div', {
						props: {className: 'crm-entity-section crm-entity-section-control'},
						children: [
							BX.create('button', {
								props: {
									className: 'ui-btn ui-btn-success'
								},
								text: BX.message('CRM_OIIV_CREATE_STORE'),
								events: {
									click: this.createStoreBtnHandler.bind(this)
								}
							}),
							BX.create('button', {
								props: {
									className: 'ui-btn ui-btn-link'
								},
								text: BX.message('CRM_OIIV_CANCEL'),
								events: {
									click: this.closeBtnHandler.bind(this)
								}
							}),
							BX.create('div', {
								props: {
									className: 'crm-entity-section-control-error-block'
								}
							})
						]
					})
				]
			});
			document.querySelector('[data-entity="store-footer"]').appendChild(this.storeFooter);
		},

		drawStepImportFooter: function()
		{
			this.stepImportFooter = BX.create('div', {
				props: {className: 'crm-entity-wrap'},
				children: [
					BX.create('div', {
						props: {className: 'crm-entity-section crm-entity-section-control'},
						children: [
							BX.create('button', {
								props: {
									className: 'ui-btn ui-btn-success'
								},
								text: BX.message('CRM_OIIV_STOP'),
								events: {
									click: this.cancelStepImport.bind(this)
								}
							}),
							BX.create('div', {
								props: {
									className: 'crm-entity-section-control-error-block'
								}
							})
						]
					})
				]
			});
			document.querySelector('[data-entity="step-import-footer"]').appendChild(this.stepImportFooter);
		}
	};

/**
 *
 * @param options
 * @extends {BX.TileGrid.Item}
 * @constructor
 */
BX.Crm.Instagram.TileGrid.Item = function(options)
{
	BX.TileGrid.Item.apply(this, arguments);

	this.isDraggable = options.isDraggable;
	this.isDroppable = options.isDroppable;
	this.isEditable = options.isEditable;
	this.dblClickDelay = 0;

	this.id = options.id;
	this.productId = options.productId;

	this.imported = options.imported || false;
	this.name = options.name;
	this.caption = options.caption;

	this.currency = options.currency;
	this.price = BX.type.isNumber(options.price) ? options.price : null;
	this.formattedPrice = this.getFormattedPrice(this.price, this.currency);
	this.formattedCurrency = this.getFormattedCurrency(this.currency);

	this.images = options.images;
	this.image = options.images[0];
	this.mediaType = options.mediaType;
	this.permalink = options.permalink;
	this.sourceData = options.sourceData;

	this.actions = [];

	this.componentName = options.componentName;
	this.signedParameters = options.signedParameters;

	this.item = {
		container: null,
		action: null,
		title: null,
		titleWrapper: null,
		titleLink: null,
		titleInput: null,
		price: null,
		priceWrapper: null,
		priceSubWrapper: null,
		priceLink: null,
		priceInput: null,
		lock: null,
		symlink: null,
		imageBlock: null,
		picture: null,
		fileType: null,
		icons: null
	};
};

BX.Crm.Instagram.TileGrid.Item.prototype =
	{
		__proto__: BX.TileGrid.Item.prototype,
		constructor: BX.TileGrid.Item,

		getRawData: function(source)
		{
			var data = {
				ID: this.id,
				NAME: this.name,
				DESCRIPTION: this.caption,
				IMAGES: this.images,
				MEDIA_TYPE: this.mediaType,
				LINK: this.permalink
			};

			if (BX.type.isNumber(this.price))
			{
				data.PRICE = this.price;
			}

			if (this.productId)
			{
				data.PRODUCT_ID = this.productId;
			}

			if (source)
			{
				data.SOURCE_DATA = this.sourceData;
			}

			return data;
		},

		setProductId: function(productId)
		{
			this.productId = productId;
		},

		setEditable: function(state)
		{
			this.isEditable = !!state;
		},

		getCheckBox: function()
		{
			if (this.isEditable)
				return;

			return this.layout.checkbox = BX.create('div', {
				props: {
					className: 'ui-grid-tile-item-checkbox'
				},
				events: {
					click: function(event)
					{
						if (this !== this.gridTile.getCurrentItem())
						{
							this.gridTile.checkItem(this.gridTile.getCurrentItem());
						}

						this.gridTile.checkItem(this);
						this.gridTile.selectItem(this);
						this.gridTile.setCurrentItem(this);
						this.gridTile.setFirstCurrentItem(this);

						if (!this.gridTile.isLastSelectedItem())
						{
							if (this.gridTile.isMultiSelectMode())
								this.gridTile.checkItem(this.gridTile.getFirstCurrentItem());
						}

						this.focusItem();
						this.resetFocusItem();
						event.stopPropagation();
					}.bind(this)
				}
			})
		},
		/**
		 *
		 * @returns {Element}
		 */
		getContent: function()
		{
			this.item.container = BX.create('div', {
				attrs: {
					className: 'crm-order-instagram-grid-item'
				},
				children: [
					this.getImage(),
					this.isEditable
						? BX.create('div', {
							props: {
								className: 'crm-order-instagram-grid-item-bottom crm-order-instagram-grid-item-bottom-without-icons'
							},
							children: [
								this.getNameAndPrice()
							]
						})
						: null
				],
				events: {
					contextmenu: function(event)
					{
						if (event.ctrlKey)
						{
							return;
						}

						this.gridTile.resetSelection();
						this.gridTile.selectItem(this);
						event.preventDefault();
					}.bind(this)
				}
			});

			if (this.image)
			{
				this.imageItemHandler = BX.throttle(this.appendImageItem, 20, this);
				BX.bind(window, 'resize', this.imageItemHandler);
				BX.bind(window, 'scroll', this.imageItemHandler);
			}

			return this.item.container
		},

		appendImageItem: function()
		{
			if (this.isVisibleInList())
			{
				this.item.picture.setAttribute('src', this.image);
				BX.unbind(window, 'resize', this.imageItemHandler);
				BX.unbind(window, 'scroll', this.imageItemHandler);
			}
		},

		getFormattedPrice: function(price, currency)
		{
			if (!BX.type.isNumber(price))
				return '';

			var formattedPrice = price.toString();

			if (BX.Currency)
			{
				var currencyFormat = BX.Currency.currencyFormat(price, currency);

				if (currencyFormat !== '')
				{
					formattedPrice = currencyFormat;
				}
			}

			return formattedPrice;
		},

		getFormattedCurrency: function(currency)
		{
			var formattedCurrency = '';

			if (BX.Currency)
			{
				var currencyFormat = BX.Currency.getCurrencyFormat(currency);

				if (currencyFormat)
				{
					formattedCurrency = BX.util.trim(currencyFormat.FORMAT_STRING.replace('#', ' '));
				}
			}

			return formattedCurrency;
		},

		getNameAndPrice: function()
		{
			this.item.title = BX.create('div', {
				children: [
					this.item.titleWrapper = BX.create('div', {
						props: {
							className: 'crm-order-instagram-grid-item-title-wrapper'
						},
						children: [
							this.getNameInput(),
							this.item.titleLink = BX.create('span', {
								attrs: {
									className: 'crm-order-instagram-grid-item-title-link',
									title: this.name
								},
								text: this.name,
								events: {
									click: this.onChangeName.bind(this)
								},
								dataset: {
									field: 'name',
									productId: this.productId
								}
							}),
							BX.create('span', {
								props: {
									className: 'crm-order-instagram-view-item-edit'
								},
								events: {
									click: this.onChangeName.bind(this)
								}
							})
						]
					})
				]
			});
			this.item.price = BX.create('div', {
				children: [
					this.item.priceWrapper = BX.create('div', {
						props: {
							className: 'crm-order-instagram-grid-item-title-wrapper crm-order-instagram-grid-item-title-wrapper-price'
						},
						children: [
							this.getPriceInput(),
							this.item.priceSubWrapper = BX.create('span', {
								attrs: {
									className: 'crm-order-instagram-view-item-price'
										+ (BX.type.isNumber(this.price) ? '' : ' crm-order-instagram-view-item-price-no')
								},
								children: [
									this.item.priceLink = BX.create('span', {
										attrs: {
											title: BX.type.isNumber(this.price) ? this.formattedPrice : ''
										},
										html: BX.type.isNumber(this.price) ? this.formattedPrice : BX.message('CRM_OIIV_NO_PRICE')
									}),
									this.item.currency = BX.create('span', {
										props: {
											className: 'crm-order-instagram-view-item-currency'
										},
										html: BX.type.isNumber(this.price) ? this.formattedCurrency : ''
									})
								],
								events: {
									click: this.onChangePrice.bind(this)
								},
								dataset: {
									field: 'price',
									productId: this.productId
								}
							}),
							BX.create('span', {
								props: {
									className: 'crm-order-instagram-view-item-edit'
								},
								events: {
									click: this.onChangePrice.bind(this)
								}
							})
						]
					})
				]
			});

			return BX.create('div', {
				props: {
					className: 'crm-order-instagram-grid-item-title'
				},
				children: [
					this.item.title,
					this.item.price
				]
			});
		},

		getGridItemByProductId: function(id)
		{
			var items = this.gridTile.items.filter(function(gridItem)
			{
				return gridItem.productId == id;
			});

			return items[0] ? items[0] : null;
		},

		focusNextInput: function(current)
		{
			if (!BX.type.isDomNode(current))
				return;

			var inputs = this.gridTile.container.querySelectorAll('[data-entity="grid-item-input"]');
			for (var i = 0; i < inputs.length; i++)
			{
				if (inputs[i] === current && BX.type.isDomNode(inputs[i + 1]))
				{
					var productId = inputs[i + 1].getAttribute('data-id');
					var gridItem = this.getGridItemByProductId(productId);
					var field = inputs[i + 1].getAttribute('data-field');

					if (gridItem && field)
					{
						var handlerName = 'onChange' + field[0].toUpperCase() + field.substring(1).toLowerCase();

						gridItem[handlerName] && gridItem[handlerName]();
					}

					break;
				}
			}
		},

		getNameInput: function()
		{
			this.item.titleInput = BX.create('input', {
				attrs: {
					className: 'crm-order-instagram-grid-item-title-input',
					type: 'text',
					value: this.name || ''
				},
				dataset: {
					entity: 'grid-item-input',
					id: this.productId,
					field: 'name'
				}
			});

			BX.bind(this.item.titleInput, 'click', function(event)
			{
				event.stopPropagation();
			});

			BX.bind(this.item.titleInput, 'keydown', function(event)
			{
				if (event.key === 'Escape')
				{
					this.cancelRenaming();

					event.preventDefault();
				}

				if (event.key === 'Enter' || event.key === 'Tab')
				{
					this.cancelRenaming();
					this.focusNextInput(event.target);

					event.preventDefault();
				}

				event.stopPropagation();
			}.bind(this));

			BX.bind(this.item.titleInput, 'blur', function(event)
			{
				BX.removeClass(this.item.title, 'crm-order-instagram-grid-item-title-rename');
				jsDD.Enable();

				if (this.name !== this.item.titleInput.value)
				{
					this.runRename();
				}

				event.stopPropagation();
			}.bind(this));

			return this.item.titleInput
		},

		getPriceInput: function()
		{
			this.item.priceInput = BX.create('input', {
				attrs: {
					className: 'crm-order-instagram-grid-item-title-input',
					type: 'text',
					value: BX.type.isNumber(this.price) ? this.price : ''
				},
				dataset: {
					entity: 'grid-item-input',
					id: this.productId,
					field: 'price'
				}
			});

			BX.bind(this.item.priceInput, 'click', function(event)
			{
				event.stopPropagation();
			});

			BX.bind(this.item.priceInput, 'keydown', function(event)
			{
				if (event.key === 'Escape')
				{
					this.cancelPriceChange();

					event.preventDefault();
				}

				if (event.key === 'Enter' || event.key === 'Tab')
				{
					this.cancelPriceChange();
					this.focusNextInput(event.target);

					event.preventDefault();
				}

				event.stopPropagation();
			}.bind(this));

			BX.bind(this.item.priceInput, 'blur', function(event)
			{
				BX.removeClass(this.item.price, 'crm-order-instagram-grid-item-title-rename');
				jsDD.Enable();

				if (BX.type.isNumber(parseFloat(this.item.priceInput.value)) && this.price !== parseFloat(this.item.priceInput.value))
				{
					this.runChangePrice();
				}

				event.stopPropagation();
			}.bind(this));

			return this.item.priceInput
		},

		onChangeName: function(event)
		{
			this.gridTile.resetSelection();
			jsDD.Disable();

			this.item.titleInput.value = this.name;
			BX.addClass(this.item.title, 'crm-order-instagram-grid-item-title-rename');

			this.item.titleInput.focus();
			this.item.titleInput.select();

			event && event.stopPropagation();
		},

		onChangePrice: function(event)
		{
			this.gridTile.resetSelection();
			jsDD.Disable();

			this.item.priceInput.value = this.price;
			BX.addClass(this.item.price, 'crm-order-instagram-grid-item-title-rename');

			this.item.priceInput.focus();
			this.item.priceInput.select();

			event && event.stopPropagation();
		},

		cancelRenaming: function()
		{
			BX.removeClass(this.item.title, 'crm-order-instagram-grid-item-title-rename');
			this.item.titleInput.blur();

			jsDD.Enable();
		},

		cancelPriceChange: function()
		{
			BX.removeClass(this.item.price, 'crm-order-instagram-grid-item-title-rename');
			this.item.priceInput.blur();

			jsDD.Enable();
		},

		rename: function(newName)
		{
			newName = BX.util.trim(newName);

			if (newName === '')
				return;

			BX.addClass(this.item.titleLink, 'crm-order-instagram-grid-item-title-link-renamed');

			this.item.titleLink.addEventListener('animationend', function()
			{
				BX.removeClass(this.item.titleLink, 'crm-order-instagram-grid-item-title-link-renamed');
			}.bind(this));

			this.item.titleLink.textContent = newName;
			this.item.titleLink.setAttribute('title', newName);
			this.name = newName;
			this.rebuildLinkAfterRename(newName);

			jsDD.Enable();
		},

		changePrice: function(newPrice)
		{
			newPrice = parseFloat(newPrice);

			if (!BX.type.isNumber(newPrice))
				return;

			var formattedPrice = this.getFormattedPrice(newPrice, this.currency);

			BX.addClass(this.item.priceLink, 'crm-order-instagram-grid-item-title-link-renamed');

			this.item.priceLink.addEventListener('animationend', function()
			{
				BX.removeClass(this.item.priceLink, 'crm-order-instagram-grid-item-title-link-renamed');
			}.bind(this));

			this.item.priceLink.setAttribute('title', newPrice);

			if (formattedPrice)
			{
				this.item.priceLink.innerHTML = formattedPrice;
				BX.removeClass(this.item.priceSubWrapper, 'crm-order-instagram-view-item-price-no');
			}
			else
			{
				this.item.priceLink.textContent = BX.message('CRM_OIIV_NO_PRICE');
				BX.addClass(this.item.priceSubWrapper, 'crm-order-instagram-view-item-price-no');
			}

			this.item.currency.innerHTML = BX.type.isNumber(newPrice) ? this.formattedCurrency : '';

			this.price = BX.type.isNumber(newPrice) ? newPrice : null;
			this.formattedPrice = formattedPrice;

			jsDD.Enable();
		},

		rebuildLinkAfterRename: function(name)
		{
			if (this.isFile)
			{
				this.permalink = this.permalink.substring(0, this.permalink.lastIndexOf('/') + 1) + encodeURIComponent(name);
			}
			else
			{
				this.permalink = this.permalink.substring(0, this.permalink.lastIndexOf('/', this.permalink.length - 2) + 1) + encodeURIComponent(name) + '/';
			}

			this.item.titleLink.href = this.permalink;
		},

		makeFastNotification: function(message)
		{
			if (BX.type.isNotEmptyString(message))
			{
				window.top.BX.UI.Notification.Center.notify({
					content: message,
					category: 'InstagramStore::product',
					autoHideDelay: 3000,
					width: 'auto'
				});
			}
		},

		runRename: function()
		{
			var oldTitle = this.name;

			this.rename(this.item.titleInput.value);

			if (this.name !== oldTitle)
			{
				BX.ajax.runComponentAction(this.componentName, 'modifyProductAjax', {
					mode: 'class',
					signedParameters: this.signedParameters,
					data: {
						productId: this.productId,
						newName: this.name
					}
				})
					.then(function(response)
					{
						this.makeFastNotification(BX.message('CRM_OIIV_PRODUCT_RENAME_SUCCESSFUL'));
					}.bind(this))
					.catch(function(response)
					{
						// todo show error
						this.rename(oldTitle);
					}.bind(this));
			}
		},

		runChangePrice: function()
		{
			var oldPrice = this.price;

			this.changePrice(this.item.priceInput.value);

			if (this.price !== oldPrice)
			{
				BX.ajax.runComponentAction(this.componentName, 'modifyProductAjax', {
					mode: 'class',
					signedParameters: this.signedParameters,
					data: {
						productId: this.productId,
						newPrice: this.price
					}
				})
					.then(function(response)
					{
						this.makeFastNotification(BX.message('CRM_OIIV_PRODUCT_CHANGE_PRICE_SUCCESSFUL'));
					}.bind(this))
					.catch(function(response)
					{
						// todo show error
						this.changePrice(oldPrice);
					}.bind(this));
			}
		},

		markImported: function()
		{
			BX.addClass(this.layout.container, 'ui-grid-tile-item-imported');
		},

		afterRender: function()
		{
			if (this.imported)
			{
				this.markImported();
			}

			if (!this.item.picture)
				return;

			this.appendImageItem();

			this.item.picture.onload = function()
			{
				BX.show(this.item.picture);
				BX.hide(this.item.fileType);
			}.bind(this);
		},

		isVisibleInList: function()
		{
			var rect = this.layout.container.getBoundingClientRect();
			var rectBody = document.body.getBoundingClientRect();
			var itemHeight = this.layout.container.offsetHeight * 2;

			if (rect.top < 0 || rect.bottom < 0)
				return false;

			return rectBody.height > (rect.top - itemHeight) && rectBody.height >= (rect.bottom - itemHeight);
		},

		getImage: function()
		{
			this.item.imageBlock = BX.create('div', {
				attrs: {
					className: 'crm-order-instagram-grid-item-image'
				},
				children: [
					this.item.fileType = BX.create('div', {
						attrs: {
							className: 'ui-icon ui-icon-file ui-icon-file-img'
						},
						style: {
							width: this.isFolder ? '85%' : '70%'
						},
						html: '<i></i>'
					}),
					this.item.picture = (this.image ? BX.create('img', {
						attrs: {
							className: 'crm-order-instagram-grid-item-image-img'
							// src: this.image
						},
						style: {
							display: 'none'
						}
					}) : null)
				]
			});

			return this.item.imageBlock
		},

		getActions: function()
		{
			return this.actions;
		}
	};