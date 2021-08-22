import {type BaseEvent, EventEmitter} from 'main.core.events';
import {Dom, Reflection, Type} from 'main.core';

export default class VariationGridController extends BX.UI.EntityEditorController
{
	areaHeight = null

	constructor(id, settings)
	{
		super();
		this.initialize(id, settings);
	}

	doInitialize()
	{
		super.doInitialize();

		EventEmitter.subscribe('Grid::thereEditedRows', this.markAsChangedHandler.bind(this));
		EventEmitter.subscribe('Grid::noEditedRows', this.checkEditorToolbar.bind(this));
		EventEmitter.subscribe('Grid::updated', this.checkEditorToolbar.bind(this));
		EventEmitter.subscribe('Grid::beforeRequest', this.onBeforeGridRequest.bind(this));

		EventEmitter.subscribe('onAjaxSuccess', this.ajaxSuccessHandler.bind(this));

		EventEmitter.subscribe('BX.UI.EntityEditorIncludedArea:onBeforeLoad', this.onBeforeIncludedAreaLoaded.bind(this));
		EventEmitter.subscribe('BX.UI.EntityEditorIncludedArea:onAfterLoad', this.onAfterIncludedAreaLoaded.bind(this));

		this.subscribeToFormSubmit();
	}

	onBeforeIncludedAreaLoaded(event: BaseEvent)
	{
		if (Type.isNumber(this.areaHeight))
		{
			Dom.style(this.getVariationGridLoader(), 'height', this.areaHeight + 'px');
		}
	}

	onAfterIncludedAreaLoaded(event: BaseEvent)
	{
		Dom.style(this.getVariationGridLoader(), 'height', '');
		this.areaHeight = null;
	}

	getVariationGridLoader()
	{
		const control = this.getGridControl();

		if (control)
		{
			const wrapper = control.getWrapper();

			if (wrapper)
			{
				return wrapper.querySelector('.ui-entity-editor-included-area-container-loader');
			}
		}

		return null;
	}

	rollback()
	{
		super.rollback();
		this.checkEditorToolbar();
		this.unsubscribeGridEvents();
	}

	onAfterSave()
	{
		if (this.isChanged() || this._editor.isChanged())
		{
			this.setGridControlCache(null);
		}

		this.subscribeToFormSubmit();
		super.onAfterSave();
	}

	setGridControlCache(html)
	{
		const control = this.getGridControl();

		if (control)
		{
			control._loadedHtml = html;
		}
	}

	onBeforeSubmit()
	{
		this.unsubscribeGridEvents();
	}

	/**
	 * @returns {BX.Catalog.VariationGrid|null}
	 */
	getVariationGridComponent()
	{
		return Reflection.getClass('BX.Catalog.VariationGrid.Instance');
	}

	unsubscribeGridEvents()
	{
		const gridComponent = this.getVariationGridComponent();
		if (gridComponent)
		{
			gridComponent.unsubscribeCustomEvents();
		}
		EventEmitter.emit(this.getGrid().getSettingsWindow().getPopup(), 'onDestroy');
		EventEmitter.unsubscribeAll('BX.Main.grid:paramsUpdated');
		this.getGrid().destroy();
	}

	ajaxSuccessHandler(event: BaseEvent)
	{
		const [, xhrData] = event.getCompatData();

		if (xhrData.url.indexOf(this.getReloadUrl()) === 0)
		{
			this.setGridControlCache(null);
		}
	}

	// ajax form initializes every "save" action
	subscribeToFormSubmit()
	{
		EventEmitter.subscribe(this._editor._ajaxForm, 'onBeforeSubmit', this.onBeforeSubmitForm.bind(this));
	}

	markAsChangedHandler()
	{
		if (!this._editor.isNew())
		{
			this.markAsChanged();
		}
	}

	checkEditorToolbar()
	{
		this._isChanged = false;

		if (this._editor.getActiveControlCount() > 0)
		{
			this._editor.showToolPanel();
		}
		else
		{
			this._editor.hideToolPanel();
		}

		if (this._editor._toolPanel)
		{
			this._editor._toolPanel.clearErrors();
		}
	}

	getGridControl()
	{
		return this._editor.getControlById('variation_grid');
	}

	onBeforeGridRequest(event: BaseEvent)
	{
		const [, eventArgs] = event.getCompatData();

		eventArgs.sessid = BX.bitrix_sessid();
		eventArgs.method = 'POST';
		eventArgs.url = this.getReloadUrl();
		eventArgs.data = {
			...eventArgs.data,
			signedParameters: this.getSignedParameters()
		};

		this.unsubscribeGridEvents();
	}

	getReloadUrl()
	{
		return this.getConfigStringParam('reloadUrl', '');
	}

	getSignedParameters()
	{
		return this.getConfigStringParam('signedParameters', '');
	}

	getGridId()
	{
		return this.getConfigStringParam('gridId', '');
	}

	getGrid()
	{
		if (!Reflection.getClass('BX.Main.gridManager.getInstanceById'))
		{
			return null;
		}

		return BX.Main.gridManager.getInstanceById(this.getGridId());
	}

	onBeforeSubmitForm(event: BaseEvent)
	{
		const [, eventArgs] = event.getCompatData();
		const grid = this.getGrid();

		if (!grid)
		{
			return;
		}

		const skuGridName = this.getGridId();
		const skuGridData = grid.getRows().getEditSelectedValues();
		const copyItemsMap = grid.getParam('COPY_ITEMS_MAP', {});

		// replace sku custom properties edit data names with original names
		for (let id in skuGridData)
		{
			if (!skuGridData.hasOwnProperty(id))
			{
				continue;
			}

			for (let name in skuGridData[id])
			{
				if (!skuGridData[id].hasOwnProperty(name))
				{
					continue;
				}

				if (name.includes('SKU_GRID_CATALOG_GROUP')
					|| name.includes('SKU_GRID_PURCHASING')
				)
				{
					for (let priceField in skuGridData[id][name])
					{
						if (skuGridData[id][name].hasOwnProperty(priceField))
						{
							skuGridData[id][priceField] = skuGridData[id][name][priceField];
						}
					}
				}
				else if (name.includes('[EDIT_HTML]'))
				{
					let newName = name.replace('[EDIT_HTML]', '');

					// lookup for a custom file fields
					if (newName.endsWith('_custom'))
					{
						if ('bxu_files[]' in skuGridData[id][name])
						{
							skuGridData[id][name].isFile = true;
							delete skuGridData[id][name]['bxu_files[]'];
						}

						if (skuGridData[id][name].isFile)
						{
							for (let fieldName in skuGridData[id][name])
							{
								if (skuGridData[id][name].hasOwnProperty(fieldName))
								{
									// check for new files like "MORE_PHOTO_n1[name]"(multiple) or "DETAIL_PICTURE[name]"(single)
									let newFilesRegExp = new RegExp(/([0-9A-Za-z_]+?(_n\d+)*)\[([A-Za-z_]+)\]/);

									if (newFilesRegExp.test(fieldName))
									{
										let fileCounter, fileSetting;

										[, fileCounter, , fileSetting] = fieldName.match(newFilesRegExp);

										if (fileCounter && fileSetting)
										{
											skuGridData[id][name][fileCounter] = skuGridData[id][name][fileCounter] || {};
											skuGridData[id][name][fileCounter][fileSetting] = skuGridData[id][name][fieldName];
											delete skuGridData[id][name][fieldName];
										}
									}
								}
							}
						}
					}

					skuGridData[id][newName] = skuGridData[id][name];
					delete skuGridData[id][name];
				}
			}

			if (!Type.isNil(copyItemsMap[id]))
			{
				skuGridData[id]['COPY_SKU_ID'] = copyItemsMap[id];
			}
		}

		if (!Type.isPlainObject(eventArgs.options))
		{
			eventArgs.options = {};
		}

		if (!Type.isPlainObject(eventArgs.options.data))
		{
			eventArgs.options.data = {};
		}

		eventArgs.options.data[skuGridName] = skuGridData;

		this.areaHeight = this.getGridControl().getWrapper().offsetHeight;
	}
}