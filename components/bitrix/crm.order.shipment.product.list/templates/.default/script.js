BX.namespace("BX.Crm.Order.Shipment.Product");

if(typeof BX.Crm.Order.Shipment.Product.List === "undefined")
{
	BX.Crm.Order.Shipment.Product.List = function() {
		this._controller = null;
		this._id = null;
		this._settings = null;
		this._formName = '';
		this._form = null;
	};

	BX.Crm.Order.Shipment.Product.List.prototype =
	{
		initialize: function (id, config)
		{
			this._id = id;
			this._settings = config ? config : {};
			BX.addCustomEvent('SidePanel.Slider:onMessage', BX.delegate(this.dispatchSliderMessages, this));
		},

		dispatchSliderMessages: function(event)
		{
			switch(event.getEventId())
			{
				case 'CrmOrderShipmentProductList::productAdd':
					this.onProductAdd(event);
					break;

				case 'CrmOrderShipmentProductListBarcodes::Save':
					this.onBarcodeSliderSaveData(event);
					break;

				case 'CrmOrderShipmentProductListBarcodes::Init':
					this.onBarcodeSliderInit(event);
					break;
			}
		},

		setController: function(controller)
		{
			this._controller = controller;
			this._controller.setProductList(this);
		},

		getForm: function()
		{
			if(this._form === null && this._formName)
			{
				this._form = document.getElementsByName(this._formName)[0];
			}

			return this._form;
		},

		setFormId: function(formId)
		{
			this._formName = formId;
		},

		getFormData: function()
		{
			var form = this.getForm();

			if(!form)
			{
				return {};
			}

			var prepared = BX.ajax.prepareForm(form);

			if(prepared && prepared.data && prepared.data.ID)
			{
				delete (prepared.data.ID);
			}

			//Extract barcode info
			var bInfo = this._settings.storeBarcodeInfo;

			for(var basketId in bInfo)
			{
				if(!bInfo.hasOwnProperty(basketId))
					continue;

				var noAmount = false;
				if (prepared.data['PRODUCT'][basketId]['AMOUNT'] === undefined)
				{
					noAmount = true;
					prepared.data['PRODUCT'][basketId]['AMOUNT'] = 0;
				}

				for(var storeId in bInfo[basketId])
				{
					if(!bInfo[basketId].hasOwnProperty(storeId))
						continue;

					if(bInfo[basketId][storeId].IS_USED === 'N')
						continue;

					if(!bInfo[basketId][storeId].BARCODES)
						continue;

					var barcodes = bInfo[basketId][storeId].BARCODES;

					var quantity = this.getProductStoreQuantityValue(basketId, storeId);
					quantity = quantity || bInfo[basketId][storeId].QUANTITY || 0;

					if (noAmount && quantity > 0)
					{
						prepared.data['PRODUCT'][basketId]['AMOUNT'] += parseFloat(quantity);
					}

					if(barcodes.length > 0)
					{
						var l = barcodes.length > quantity ? quantity : barcodes.length;
						for(var i = 0; i < l; i++)
						{
							prepared.data = this.createBarcodeBranch(prepared.data, [storeId, 'BARCODE_INFO', basketId, 'PRODUCT']);

							prepared.data['PRODUCT'][basketId]['BARCODE_INFO'][storeId]['BARCODE'].push(
								barcodes[i]
							);
						}
						prepared.data['PRODUCT'][basketId]['BARCODE_INFO'][storeId]['QUANTITY'] = quantity;
						prepared.data['PRODUCT'][basketId]['BARCODE_INFO'][storeId]['STORE_ID'] = storeId;
					}
				}
			}
			//

			return !!prepared && prepared.data ? prepared.data : {};
		},

		createBarcodeBranch: function(obj, keys)
		{
			if(keys.length === 0)
			{
				if(typeof (obj['BARCODE']) === 'undefined')
					obj['BARCODE'] = [];

				return obj;
			}

			var key = keys.pop();

			if(typeof (obj[key]) === 'undefined')
				obj[key] = {};

			obj[key] = this.createBarcodeBranch(obj[key], keys);
			return obj;
		},

		setFormData: function(data)
		{
			if(data && data.PRODUCT_COMPONENT_RESULT)
			{
				var processedData = BX.processHTML(data.PRODUCT_COMPONENT_RESULT);
				BX('crm-product-list-container').parentNode.innerHTML = processedData['HTML'];

				if (BX.type.isDomNode(BX(this._id + '-grid-settings-window')))
					BX.remove(BX(this._id + '-grid-settings-window'));

				setTimeout(function(){
					for (var i in processedData['SCRIPT'])
					{
						if(!processedData['SCRIPT'].hasOwnProperty(i))
							continue;

						BX.evalGlobal(processedData['SCRIPT'][i]['JS']);
						delete(processedData['SCRIPT'][i]);
					}},
					1
				);
			}
		},

		onDataChanged: function()
		{
			this._controller.onDataChanged();
		},

		markAsChanged: function()
		{
			this._controller.markAsChangedItem();
		},

		onProductAdd: function(event)
		{
			if (event.getEventId() === 'CrmOrderShipmentProductList::productAdd')
			{
				var eventData = event.getData();

				if (eventData.entityTypeId === BX.CrmEntityType.enumeration.ordershipment)
				{
					this._controller.onProductAdd(eventData.basketId);
				}
			}
		},

		onProductDelete: function(basketCode)
		{
			this._controller.onProductDelete(basketCode);
		},

		showAddProductDialog: function()
		{
			var url = this.getSetting('addProductUrl', ''),
				data = this.getFormData();

			if(data && data.PRODUCT)
			{
				for(var basketId in data.PRODUCT)
				{
					if(data.PRODUCT.hasOwnProperty(basketId))
					{
						url += '&BID[]='+parseInt(basketId);
					}
				}
			}

			BX.SidePanel.Instance.open(url+'&'+Math.floor(Math.random() * 99999));
		},

		getSetting: function(name, dafaultval)
		{
			return typeof(this._settings[name]) !== 'undefined' ? this._settings[name] : dafaultval;
		},

		setSetting: function(name, value)
		{
			this._settings[name] = value;
		},

		onBarcodeClick: function(orderId, basketId, storeId, barcodeWidgetCssPath)
		{
			var dailogLink = BX.util.add_url_param(
				this.getSetting('barcodesDialogUrl'),
				{
					basketId: basketId,
					storeId: storeId,
					additionalCssPath: barcodeWidgetCssPath
				}
			);

			BX.Crm.Page.openSlider(dailogLink, { width: 500, cacheable: false });
		},


		onProductStoreQuantityChange: function(basketId, storeId, quantity)
		{
			quantity = parseFloat(quantity);

			if(quantity > 0)
			{
				this.enableBarcodesButton(basketId, storeId);
			}
			else
			{
				if(quantity < 0 ||  isNaN(quantity))
				{
					this.getProductStoreQuantity(basketId, storeId).value = 0;
				}

				this.disableBarcodesButton(basketId, storeId);
			}

			this.markAsChanged();
			//this.onDataChanged();
		},

		enableBarcodesButton: function(basketId, storeId)
		{
			var button = BX('barcode_button_'+basketId+'_'+storeId);

			if(button && button.disabled)
			{
				button.disabled = false;
				BX.removeClass(button, 'ui-btn-disabled');
			}
		},

		disableBarcodesButton: function(basketId, storeId)
		{
			var button = BX('barcode_button_'+basketId+'_'+storeId);

			if(button && !button.disabled)
			{
				button.disabled = true;
				BX.addClass(button, 'ui-btn-disabled');
			}
		},

		getProductStoreQuantity: function(basketId, storeId)
		{
			return document.getElementsByName('PRODUCT['+basketId+'][BARCODE_INFO]['+storeId+'][QUANTITY]')[0];
		},

		getProductAmount: function(basketId)
		{
			return document.getElementById('crm-product-amount-' + basketId);

		},

		getProductStoreQuantityValue: function(basketId, storeId)
		{
			if(storeId > 0)
			{
				var productStoreQuantityField = this.getProductStoreQuantity(basketId, storeId);
				if (productStoreQuantityField !== undefined)
				{
					return productStoreQuantityField.value || 0;
				}
				else
				{
					return null;
				}
			}
			else
			{
				var productAmount = this.getProductAmount(basketId, storeId);
				if (productAmount !== null)
				{
					return productAmount.value || 0;
				}
				else
				{
					return null;
				}
			}
		},

		getProductAmout: function(basketId)
		{
			return document.getElementsByName('PRODUCT['+basketId+'][AMOUNT]')[0];
		},

		onAddStoreClick: function(basketCode)
		{
			this.showStoreDialog(basketCode);
		},

		createBarcodeWidgetHead: function(storeBarcode)
		{
			var result = {};

			if(storeBarcode.STORE_ID > 0)
			{
				result['barcode'] = {title: BX.message('CRM_ORDER_SPLT_BARCODE')};
			}

			if(this.isSupportedMarkingCode(storeBarcode))
			{
				result['markingCode'] = {title: BX.message('CRM_ORDER_SPLT_MARKING_CODE')};
			}

			return result;
		},

		isBarcodeMulti: function(storeBarcode)
		{
			return 	storeBarcode.BARCODE_MULTI === 'Y';
		},

		isSupportedMarkingCode: function(storeBarcode)
		{
			return 	storeBarcode.IS_SUPPORTED_MARKING_CODE === 'Y';
		},

		createBarcodeWidgetRows: function(barcodes, isBarcodeMulti, isSupportedMarkingCode, rowsLimit)
		{
			if (rowsLimit === undefined)
			{
				rowsLimit = Infinity;
			}
			var result = [];

			var i = 0;
			barcodes.forEach(function (item){
				if (i >= rowsLimit)
				{
					return result;
				}
				var itemData = {id: item.ID};

				itemData.barcode = item.VALUE;

				if(isSupportedMarkingCode)
				{
					itemData.markingCode = item.MARKING_CODE;
				}

				result.push(itemData);
				i++;
			});

			return result;
		},

		createBarcodeWidget: function(basketId, storeId)
		{
			var barcodeData = this.getStoreBarcode(basketId);
			var storeBarcodeData = barcodeData[storeId];

			var quantity = this.getProductStoreQuantityValue(basketId, storeId);
			quantity = quantity || storeBarcodeData.QUANTITY || 0;

			if(quantity <= 0)
			{
				return null;
			}

			if(!barcodeData || !barcodeData[storeId] || !barcodeData[storeId].BARCODES)
			{
				return null;
			}

			return new BX.Sale.Barcode.Widget({
				rowData: this.createBarcodeWidgetRows(
					storeBarcodeData.BARCODES,
					this.isBarcodeMulti(storeBarcodeData),
					this.isSupportedMarkingCode(storeBarcodeData),
					quantity
				),
				headData: this.createBarcodeWidgetHead(storeBarcodeData),
				rowsCount: quantity,
				orderId: this._settings.params.ORDER_ID,
				basketId: basketId,
				storeId: storeId,
				isBarcodeMulti: this.isBarcodeMulti(storeBarcodeData)
			});
		},

		onBarcodeSliderInit: function(event)
		{
			if (event.getEventId() === 'CrmOrderShipmentProductListBarcodes::Init')
			{
				var eventArgs = event.getData(),
					barcodeSlider = eventArgs.barcodeSlider;

				if(!this.isStoreBarcodeInfoExists(eventArgs.basketId))
				{
					return;
				}

				var widget = this.createBarcodeWidget(eventArgs.basketId, eventArgs.storeId);

				if(widget)
				{
					barcodeSlider.setWidget(widget);
					barcodeSlider.setContent(
						BX.create('div', {
							attrs: {
								className: 'crm-order-shipment-product-list-barcode-content'
							},
							children: [widget.render()]
						})
					);
				}
				else
				{
					BX.debug('Can\'t initialize barcode widget');
				}
			}
		},

		onBarcodeSliderSaveData: function(event)
		{
			if (event.getEventId() === 'CrmOrderShipmentProductListBarcodes::Save')
			{
				var eventArgs = event.getData();

				if(eventArgs.widget)
				{

					if(!this.isStoreBarcodeInfoExists(eventArgs.widget.basketId))
					{
						return;
					}

					var widget = eventArgs.widget,
						barcodes = [];

					widget.getItemsData().forEach(function(item) {
						barcodes.push({
							ID: item.id,
							VALUE: item.barcode.value,
							MARKING_CODE: item.markingCode.value
						});
					});

					this.setStoreBarcode(widget.basketId, widget.storeId, barcodes);
					this.markAsChanged();
				}
			}
		},

		checkBarcode: function(barcode, basketId, storeId, cbOk, cbError)
		{
			BX.ajax.runComponentAction('bitrix:crm.order.shipment.product.list', 'checkProductBarcode', {
				mode: 'ajax',
				data: {
					barcode: barcode,
					orderId: this._settings.params.ORDER_ID,
					basketId: basketId,
					storeId: storeId
				}
			}).then(function (response) {
				if(response.status && response.status === 'success')
				{
					var result = null;

					if(response.data === 'OK')
						result = true;
					else if(response.data === 'ERROR')
						result = false;

					if(typeof cbOk === 'function')
					{
						cbOk.apply(null, [result]);
					}

					if(response.errors.length > 0 && typeof cbError === 'function')
					{
						cbError.apply(null, [response.errors]);
					}
				}
			}, function (response) {
				{
					if(typeof cbError === 'function')
					{
						cbError.apply(null, [response.errors]);
					}
				}
			});
		},

		getStoreMarkingCode: function(basketCode)
		{
			if(typeof(this._settings.storeMarkingCodeInfo[basketCode]) !== 'undefined')
			{
				return this._settings.storeMarkingCodeInfo[basketCode];
			}
			else
			{
				BX.debug('Can\'t find storeMarkingCodeInfo');
			}

			return null;
		},

		isStoreBarcodeInfoExists: function(basketCode)
		{
			return typeof(this._settings.storeBarcodeInfo[basketCode]) !== 'undefined';
		},

		getStoreBarcode: function(basketCode)
		{
			if(this.isStoreBarcodeInfoExists(basketCode))
			{
				return this._settings.storeBarcodeInfo[basketCode];
			}
			else
			{
				BX.debug('Can\'t find storeBarcodeInfo');
			}

			return null;
		},

		setStoreBarcode: function(basketCode, storeId, barcodes)
		{
			if(typeof(this._settings.storeBarcodeInfo[basketCode][storeId].BARCODES) !== 'undefined')
			{
				return this._settings.storeBarcodeInfo[basketCode][storeId].BARCODES = barcodes;
			}
			else
			{
				BX.debug('Can\'t find storeBarcodeInfo');
			}
		},

		setStoreUsed: function(basketCode, storeId, isUsed)
		{
			if(typeof(this._settings.storeBarcodeInfo[basketCode][storeId]) !== 'undefined')
			{
				this._settings.storeBarcodeInfo[basketCode][storeId]['IS_USED'] = isUsed;
			}
			else
			{
				BX.debug('Can\' find storeInfo');
			}
		},

		getStoresBarcodeByUsing: function(basketCode, isUsed)
		{
			var result = {},
				sbi = this.getStoreBarcode(basketCode);

			if(sbi)
			{
				if(!isUsed)
				{
					result = sbi;
				}
				else
				{
					for(var i in sbi)
					{
						if(sbi.hasOwnProperty(i) && sbi[i]['IS_USED'] === isUsed)
						{
							result[i] = sbi[i];
						}
					}
				}
			}
			else
			{
				BX.debug('Can\' find storeInfo');
			}

			return result;
		},

		getStoresBarcodeCountByUsing: function(basketCode, isUsed)
		{
			return Object.keys(this.getStoresBarcodeByUsing(basketCode, isUsed)).length;
		},

		createStoresList: function(basketCode)
		{
			var selector = BX.create('select'),
				storeBarcodeInfo = this.getStoresBarcodeByUsing(basketCode, 'N');

			if(!storeBarcodeInfo)
			{
				return null;
			}

			for(var i in storeBarcodeInfo)
			{
				if(storeBarcodeInfo.hasOwnProperty(i))
				{
					if(!(storeBarcodeInfo[i].STORE_ID))
					{
						continue;
					}

					selector.options.add(
						new Option(
							storeBarcodeInfo[i].STORE_NAME,
							storeBarcodeInfo[i].STORE_ID
						)
					);
				}
			}

			return 	selector;
		},

		showStoreDialog: function(basketCode)
		{
			var stores = this.createStoresList(basketCode);

			var form = BX.create('form',{children:[
				BX.create('span', {
					props: {className: 'fields enumeration field-wrap'},
					style:{width: '100%', marginBottom: '0px'},
					children:[
						BX.create('span', {props: {className: 'fields enumeration field-item'}, style:{paddingBottom: '10px'}, children:[
							stores
						]})
					]})
				]});

			var dialog = BX.PopupWindowManager.create(
				'crm_order_shipment_product_stores',
				null,
				{
					content:form,
					titleBar: BX.message('CRM_ORDER_SPLT_CHOOSE_STORE'),
					autoHide: false,
					lightShadow: true,
					closeByEsc: true,
					width: 400,
					overlay: {backgroundColor: 'black', opacity: 500}
				}
			);

			dialog.setButtons([
				new BX.PopupWindowButton({
					text: BX.message('CRM_ORDER_SPLT_CHOOSE'),
					className: 'popup-window-button-accept',
					events: {
						click: BX.proxy(function(){
							this.addStoreRow(basketCode, stores.value);
							dialog.close();
							dialog.destroy();
						}, this)
					}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message('CRM_ORDER_SPLT_CLOSE'),
					className: 'popup-window-button-link-cancel',
					events: {
						click: BX.proxy(function(){
							dialog.close();
							dialog.destroy();
						}, this)
					}
				})
			]);

			dialog.show();
			dialog.resizeOverlay();
		},

		addStoreRow: function(basketCode, storeId)
		{
			var storeBarcodeInfo = this.getStoreBarcode(basketCode);

			if(!storeBarcodeInfo)
			{
				BX.debug('Can\'t find stores info');
				return;
			}

			if(typeof (storeBarcodeInfo[storeId]) === 'undefined')
			{
				BX.debug('Can\'t find store');
				return;
			}

			var store = this._settings.storeTmpl,
				storeQuantity = this._settings.storeQuantityTmpl,
				storeRemainingQuantity = this._settings.storeRemainingQuantityTmpl;

			if(storeBarcodeInfo[storeId].BARCODE_MULTI === 'Y' || storeBarcodeInfo[storeId].IS_SUPPORTED_MARKING_CODE === 'Y')
			{
				var storeBarcode =  this._settings.storeBarcodeTmplB;
			}
			else
			{
				storeBarcode = this._settings.storeBarcodeTmplI;
			}

			var quantity = 0;
			if (storeBarcodeInfo[storeId].hasOwnProperty('QUANTITY'))
			{
				quantity = Number(storeBarcodeInfo[storeId].QUANTITY) || 0;
			}

			for(var i in storeBarcodeInfo[storeId])
			{
				if(!storeBarcodeInfo[storeId].hasOwnProperty(i))
				{
					continue;
				}

				var field = new RegExp('\#'+i+'\#', 'g'),
					value = BX.util.htmlspecialchars(storeBarcodeInfo[storeId][i]);

				if(typeof(value) === 'object')
				{
					continue;
				}

				store = store.replace(field, value);
				storeQuantity = storeQuantity.replace(field, value);
				storeBarcode = storeBarcode.replace(field, value);
				storeRemainingQuantity = storeRemainingQuantity.replace(field, value);
			}

			var storeNode = this.createElement(store);
			BX.addClass(storeNode, 'crm-order-product-store-row-hidden');

			var productStoresNode = BX('crm-shipment-product-store-'+basketCode);
			if (productStoresNode !== null)
			{
				productStoresNode.insertBefore(
					storeNode,
					BX('crm-shipment-product-storeadd-'+basketCode)
				);
			}
			var storeQuantityNode = this.createElement(storeQuantity);
			BX.addClass(storeQuantityNode, 'crm-order-product-store-row-hidden');
			this.addChildElementToNode('crm-shipment-product-quantity-' + basketCode, storeQuantityNode);

			var storeRemainingQuantityNode = this.createElement(storeRemainingQuantity);
			BX.addClass(storeRemainingQuantityNode, 'crm-order-product-store-row-hidden');
			this.addChildElementToNode('crm-shipment-product-rquantity-' + basketCode, storeRemainingQuantityNode);

			var storeBarcodeNode = this.createElement(storeBarcode);
			BX.addClass(storeBarcodeNode, 'crm-order-product-store-row-hidden');
			this.addChildElementToNode('crm-shipment-product-barcode-' + basketCode, storeBarcodeNode);
			if (quantity === 0)
			{
				this.disableBarcodesButton(basketCode, storeId);
			}

			setTimeout(function(){
				BX.removeClass(storeNode, 'crm-order-product-store-row-hidden');
				BX.removeClass(storeQuantityNode, 'crm-order-product-store-row-hidden');
				BX.removeClass(storeRemainingQuantityNode, 'crm-order-product-store-row-hidden');
				BX.removeClass(storeBarcodeNode, 'crm-order-product-store-row-hidden');

			}, 100);
			this.setStoreUsed(basketCode, storeId, 'Y');

			if(this.getStoresBarcodeCountByUsing(basketCode, 'N') <= 0)
			{
				this.hideStoreAdder(basketCode);
			}

			//this.onProductStoreQuantityChange(basketCode, storeId, quantity);
			this.markAsChanged();
			this.onStoreListChange(basketCode);
		},

		createElement: function(html)
		{
			return BX.create('div', {html: html}).firstChild;
		},

		getProductStoreAmountValue: function(basketCode, storeId)
		{
			return this._settings.storeBarcodeInfo[basketCode][storeId].AMOUNT;
		},

		onStoreDeleteClick: function(basketCode, storeId)
		{
			var nodes = BX.findChildren(
				this.getForm(),
				{
					attribute: {
						'data-ps-basketcode': basketCode,
						'data-ps-store-id': storeId
					}
				},
				true
			);

			for(var i in nodes)
			{
				if(nodes.hasOwnProperty(i))
				{
					BX.addClass(nodes[i], 'crm-order-product-store-row-hidden');
					(function(i, context){
						setTimeout(function(){
							nodes[i].parentNode.removeChild(nodes[i]);
							context.showStoreAdder(basketCode);
						}, 400);
					})(i, this);
				}
			}

			this.setStoreUsed(basketCode, storeId, 'N');
			this.onStoreListChange(basketCode);
			this.markAsChanged();
		},

		onStoreListChange: function (basketCode)
		{
			var storeListNode = BX('crm-shipment-product-store-' + basketCode);

			if (storeListNode !== null)
			{
				var stores = storeListNode.querySelectorAll('.crm-order-product-store-container');
				var usedStoresCount = this.getStoresBarcodeCountByUsing(basketCode, 'Y');

				if (usedStoresCount > 1)
				{
					for (var i = 0; i < stores.length; i++)
					{
						this.showStoreDeleteButton(stores[i]);
					}
				}
				else
				{
					for (var i = 0; i < stores.length; i++)
					{
						this.hideStoreDeleteButton(stores[i]);
					}
				}
			}
		},

		showStoreDeleteButton: function (store)
		{
			var storeDeleteButton = store.querySelector('.crm-order-product-store-del-container');
			if (storeDeleteButton !== null)
			{
				BX.show(storeDeleteButton, 'inline-block');
			}
		},

		hideStoreDeleteButton: function (store)
		{
			var storeDeleteButton = store.querySelector('.crm-order-product-store-del-container');
			if (storeDeleteButton !== null)
			{
				BX.hide(storeDeleteButton);
			}
		},

		onBarcodeChange: function(inputNode)
		{
			this.checkBarcode(
				inputNode.value,
				inputNode.parentNode.getAttribute('data-ps-basketcode'),
				0,
				BX.proxy(function(result){ this.showBarcodeCheckResult(inputNode, result)}, this),
				BX.proxy(function(errors){ BX.debug(errors)}, this)
			);

			this.markAsChanged();
		},

		showBarcodeCheckResult: function(inputNode, checkResult)
		{
			if(checkResult === false)
			{
				BX.addClass(inputNode, 'barcode-error');
				BX.removeClass(inputNode, 'barcode-ok');
			}
			else if(checkResult === true)
			{
				BX.addClass(inputNode, 'barcode-ok');
				BX.removeClass(inputNode, 'barcode-error');
			}
			else
			{
				BX.removeClass(inputNode, 'barcode-error');
				BX.removeClass(inputNode, 'barcode-ok');
			}
		},

		showStoreAdder: function(basketCode)
		{
			BX.removeClass(
				BX('crm-shipment-product-storeadd-'+basketCode),
				'crm-order-product-store-storeadder-hidden'
			);
		},

		hideStoreAdder: function(basketCode)
		{
			BX.addClass(
				BX('crm-shipment-product-storeadd-'+basketCode),
				'crm-order-product-store-storeadder-hidden'
			);
		},

		addChildElementToNode: function(nodeName, inputElement)
		{
			var parentNode = BX(nodeName);
			if (parentNode !== null)
			{
				parentNode.appendChild(inputElement);
				return true;
			}

			return false;
		},
	};

	BX.Crm.Order.Shipment.Product.List.create = function (id, config)
	{
		var self = new BX.Crm.Order.Shipment.Product.List();
		self.initialize(id, config);
		return self;
	};
}