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
					this.onBarcodesSave(event);
					break;

				case 'CrmOrderShipmentProductListBarcodes::Init':
					this.onBarcodesInit(event);
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

				for(var storeId in bInfo[basketId])
				{
					if(!bInfo[basketId].hasOwnProperty(storeId))
						continue;

					if(bInfo[basketId][storeId].IS_USED === 'N')
						continue;

					if(!bInfo[basketId][storeId].BARCODES)
						continue;

					var barcodes = bInfo[basketId][storeId].BARCODES,
						quantity = this.getProductStoreQuantityValue(basketId, storeId);

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

		onBarcodeClick: function(orderId, basketId, storeId)
		{
			var dailogLink = BX.util.add_url_param(
				this.getSetting('barcodesDialogUrl'),
				{
					basketId: basketId,
					storeId: storeId
				}
			);

			BX.Crm.Page.openSlider(dailogLink, { width: 500, cacheable: false });
		},


		onProductStoreQuantityChange: function(basketId, storeId, quantity)
		{
			var amount = this.getProductStoreAmountValue(basketId, storeId);
			quantity = parseFloat(quantity);

			if(quantity > 0)
			{
				if(quantity > amount)
				{
					this.getProductStoreQuantity(basketId, storeId).value = amount;
				}

				this.enableBarcodesButton(basketId, storeId);
			}
			else
			{
				if(quantity < 0)
				{
					this.getProductStoreQuantity(basketId, storeId).value = 0;
				}

				this.disableBarcodesButton(basketId, storeId);
			}

			this.markAsChanged();
			this.onDataChanged();
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

		getProductStoreQuantityValue: function(basketId, storeId)
		{
			return this.getProductStoreQuantity(basketId, storeId).value;
		},

		onAddStoreClick: function(basketCode)
		{
			this.showStoreDialog(basketCode);
		},

		onBarcodesInit: function(event)
		{
			if (event.getEventId() === 'CrmOrderShipmentProductListBarcodes::Init')
			{
				var eventArgs = event.getData(),
					productBarcodes = eventArgs.productBarcodes,
					storeId = eventArgs.storeId,
					basketId = eventArgs.basketId;

				var barcodes = [],
					basketBarcodes;

				if(basketBarcodes = this.getStoreBarcode(basketId))
				{
					if(basketBarcodes[storeId] && basketBarcodes[storeId].BARCODES)
					{
						barcodes = basketBarcodes[storeId].BARCODES;
					}
				}

				productBarcodes.setBarcodes(
					barcodes,
					this.getProductStoreQuantityValue(basketId, storeId)
				);

				productBarcodes.setBarcodeCheckMethod(
					BX.delegate(this.checkBarcode, this)
				);
			}
		},


		onBarcodesSave: function(event)
		{
			if (event.getEventId() === 'CrmOrderShipmentProductListBarcodes::Save')
			{
				var eventArgs = event.getData();

				if(eventArgs.basketId && eventArgs.storeId && eventArgs.barcodes)
				{
					this.setStoreBarcode(eventArgs.basketId, eventArgs.storeId, eventArgs.barcodes);
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

		getStoreBarcode: function(basketCode)
		{
			if(typeof(this._settings.storeBarcodeInfo[basketCode]) !== 'undefined')
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

			if(storeBarcodeInfo[storeId].BARCODE_MULTI === 'Y')
			{
				var storeBarcode =  this._settings.storeBarcodeTmplB;
			}
			else
			{
				storeBarcode = this._settings.storeBarcodeTmplI;
			}

			var quantity = 0;

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

				if(field === 'QUANTITY')
				{
					quantity = value;
				}
			}

			var storeNode = this.createElement(store);
			BX.addClass(storeNode, 'crm-order-product-store-row-hidden');

			BX('crm-shipment-product-store-'+basketCode).insertBefore(
				storeNode,
				BX('crm-shipment-product-storeadd-'+basketCode)
			);

			var storeQuantityNode = this.createElement(storeQuantity);
			BX.addClass(storeQuantityNode, 'crm-order-product-store-row-hidden');
			BX('crm-shipment-product-quantity-'+basketCode).appendChild(storeQuantityNode);

			var storeRemainingQuantityNode = this.createElement(storeRemainingQuantity);
			BX.addClass(storeRemainingQuantityNode, 'crm-order-product-store-row-hidden');
			BX('crm-shipment-product-rquantity-'+basketCode).appendChild(storeRemainingQuantityNode);

			var storeBarcodeNode = this.createElement(storeBarcode);
			BX.addClass(storeBarcodeNode, 'crm-order-product-store-row-hidden');
			BX('crm-shipment-product-barcode-'+basketCode).appendChild(storeBarcodeNode);

			setTimeout(function(){
				BX.removeClass(storeNode, 'crm-order-product-store-row-hidden');
				BX.removeClass(storeQuantityNode, 'crm-order-product-store-row-hidden');
				BX.removeClass(storeRemainingQuantityNode, 'crm-order-product-store-row-hidden');
				BX.removeClass(storeBarcodeNode, 'crm-order-product-store-row-hidden');
			}, 100);

			this.setStoreUsed(basketCode, storeId, true);

			if(this.getStoresBarcodeCountByUsing(basketCode, 'N') <= 0)
			{
				this.hideStoreAdder(basketCode);
			}

			this.onProductStoreQuantityChange(basketCode, storeId, quantity);
			this.markAsChanged();
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
			this.markAsChanged();
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
				'crm-order-product-store-row-hidden'
			);
		},

		hideStoreAdder: function(basketCode)
		{
			BX.addClass(
				BX('crm-shipment-product-storeadd-'+basketCode),
				'crm-order-product-store-row-hidden'
			);
		}
	};

	BX.Crm.Order.Shipment.Product.List.create = function (id, config)
	{
		var self = new BX.Crm.Order.Shipment.Product.List();
		self.initialize(id, config);
		return self;
	};
}