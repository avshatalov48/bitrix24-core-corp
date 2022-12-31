BX.namespace("BX.Crm.Order.Product");

if(typeof BX.Crm.Order.Product.List === "undefined")
{
	BX.Crm.Order.Product.List = function() {
		this._controller = null;
		this._id = null;
		this._settings = null;
		this._formName = '';
		this._form = null;
		this._timerId = null;
		this._timeOutDelay = 850;
		this._canSend = true;
	};

	BX.Crm.Order.Product.List.prototype =
	{
		initialize: function (id, config)
		{
			this._id = id;
			this._settings = config ? config : {};
			this._isChanged = this.getSetting('isChanged', false);
			this._isReadOnly = this.getSetting('isReadOnly', false);
			if (!this._isReadOnly)
			{
				BX.Event.EventEmitter.unsubscribeAll('onFocusToProductList');
				BX.Event.EventEmitter.subscribe('onFocusToProductList', () => {
					this.onFocusToProductList();
					BX.onCustomEvent('crmOrderProductListFocused');
				});
			}

			BX.addCustomEvent('crmOrderDetailDiscountToggle', BX.proxy(function(data){
				this.setDiscountById(data.discountId, data.isSet, false, true);
			}, this));

			BX.onCustomEvent('crmOrderProductListInit', [{
				id: this._id,
			}]);
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

			return !!prepared && prepared.data ? prepared.data : {};
		},

		setFormData: function(data)
		{
			if(data && data.PRODUCT_COMPONENT_RESULT)
			{
				var processedData = BX.processHTML(data.PRODUCT_COMPONENT_RESULT),
					oldContainer = BX('crm-product-list-container').parentNode,
					isVisible = Boolean(
						oldContainer.offsetWidth || oldContainer.offsetHeight || oldContainer.getClientRects().length
					);

				if(isVisible)
				{
					var oldPos = oldContainer.getBoundingClientRect();
					var newContainer = oldContainer.cloneNode(true);
					BX.addClass(newContainer, 'crm-order-product-refresh-stub');
					document.body.appendChild(newContainer);
					newContainer.style.left = oldPos.left + 'px';
					newContainer.style.top = oldPos.top + 'px';
					newContainer.style.width = oldPos.width + 'px';
					newContainer.style.height = oldPos.height + 'px';
				}

				setTimeout(function(){
					oldContainer.innerHTML = processedData['HTML'];

					setTimeout(function(){
						if(isVisible)
						{
							newContainer.style.opacity = 0;
						}

						setTimeout(function(){
							if(isVisible)
							{
								newContainer.parentNode.removeChild(newContainer);
							}

							if (BX.type.isDomNode(BX(this._id + '-grid-settings-window')))
								BX.remove(BX(this._id + '-grid-settings-window'));

							setTimeout(function(){
								for (var i in processedData['SCRIPT'])
								{
									if(!processedData['SCRIPT'].hasOwnProperty(i))
										continue;

									BX.evalGlobal(processedData['SCRIPT'][i]['JS']);
									delete(processedData['SCRIPT'][i]);
								}}, 1);

						}, 120);

					}, 100);

				}, 1);

			}
		},

		setChanged: function()
		{
			this._isChanged = true;
		},

		isChanged: function()
		{
			return this._isChanged;
		},

		onDataChanged: function()
		{
			if(!this._canSend)
			{
				return;
			}

			var _this = this;

			clearTimeout(this._timerId);

			this._timerId = interval = setTimeout(
				function(){
					_this._canSend = false;
					_this._controller.onDataChanged();
					setTimeout(function(){_this._canSend = true;}, 200);
				},
				this._timeOutDelay
			);
		},

		showProductExistDialog: function(params)
		{
			BX.UI.EditorAuxiliaryDialog.create(
				"order_product_exist_dialog",
				{
					title: this._settings.messages['CRM_ORDER_PL_PROD_EXIST_DLG_TITLE'],
					content: this._settings.messages['CRM_ORDER_PL_PROD_EXIST_DLG_TEXT_FOR_DOUBLE'].replace('#NAME#', this.getProductName(params.id)),
					buttons:
						[
							{
								id: "incrementProduct",
								type: BX.Crm.DialogButtonType.accept,
								text: this._settings.messages['CRM_ORDER_PL_PROD_EXIST_DLG_BUTT_ADD'],
								callback: BX.proxy(function(button){
									this.setChanged();
									this._controller.onProductAdd(params.id, params.quantity, 'N');
									button.getDialog().close();
								},
								this)
							},
							{
								id: "cancel",
								type: BX.Crm.DialogButtonType.cancel,
								text: this._settings.messages['CRM_ORDER_PL_PROD_EXIST_DLG_BUTT_CANCEL'],
								callback: function(button){
									button.getDialog().close();
								}
							}
						]
				}
			).open();
		},

		onFocusToProductList: function()
		{
			const languageId = this.getSetting('languageId', false);
			const siteId = this.getSetting('siteId', false);
			const orderId = this.getSetting('orderId', false);
			if (languageId && siteId && orderId)
			{
				this.addProductSearch({languageId, siteId, orderId});
			}
		},

		onProductAdd: function(params, iBlockId)
		{
			if(this.isProductAlreadyInBasket(params.id))
			{
				this.showProductExistDialog(params);
			}
			else
			{
				this.setChanged();
				this._controller.onProductAdd(params.id, params.quantity);
			}
		},

		onProductCreate: function(fields)
		{
			this.setChanged();
			this._controller.onProductCreate(fields);
		},

		onProductUpdate: function(basketId, fields)
		{
			if (BX.type.isNotEmptyString(basketId))
			{
				this.setChanged();
				this._controller.onProductUpdate(basketId, fields);
			}
		},

		onProductDelete: function(basketCode)
		{
			this.setChanged();
			this._controller.onProductDelete(basketCode);
		},

		onGroupAction: function(gridId, action)
		{
			var grid = BX.Main.gridManager.getById(gridId);


		 	var basketCodes = grid.instance.getRows().getSelectedIds(),
				values = grid.instance.getActionsPanel().getValues(),
				forAll = grid.instance.getForAllKey() in values ? values[grid.instance.getForAllKey()] : 'N';

			this._controller.onProductGroupAction(basketCodes, action, forAll);
		},

		onRefreshOrderDataAndSave: function()
		{
			this._controller.onRefreshOrderDataAndSave();
		},

		onCouponAdd: function()
		{
			var coupon = BX('crm-order-product-new-coupon').value;

			if(coupon)
			{
				this._controller.onCouponAdd(coupon);
			}
		},

		onProductDiscountCheck: function(discountNode, discountId)
		{
			this.setDiscountById(discountId, discountNode.checked, true);
			this.onDataChanged();
		},

		onDiscountCheck: function(discountNode, discountId)
		{
			this.setDiscountById(discountId, discountNode.checked);
		},

		setDiscountById: function(discountId, isSet, onlyCoupons, skipEvent)
		{
			if(discountId <= 0)
				return;

			skipEvent = skipEvent || false;

			var nodes = BX.findChildren(
				document,
				{
					attribute: {'data-discount-id': discountId}
				},
				true
			);

			for(var i in nodes)
			{
				if(nodes.hasOwnProperty(i))
				{
					if(onlyCoupons && (!isSet || !nodes[i].hasAttribute('data-is-coupon')))
					{
						continue;
					}

					if(nodes[i].type === 'checkbox')
					{
						nodes[i].checked = isSet;
					}
					else if(nodes[i].type === 'hidden')
					{
						nodes[i].value = isSet ? 'Y' : 'N';
					}
				}
			}

			if(!skipEvent)
			{
				BX.onCustomEvent('crmOrderProductListDiscountToggle', [{
					discountId: discountId,
					isSet: isSet
				}]);

				this.onDataChanged();
			}
		},

		onOpenCreateDiscountBlock: function(node)
		{
			node.parentNode.parentNode.appendChild(
				BX.create('div',{
					props: {
						className: 'crm-order-product-control-discounts-list-item'
					},
					html: this._settings.customDiscountTempl
				})
			);
		},

		onApplyCustomDiscount: function()
		{
			alert('Apply discount');
		},

		onCouponDelete: function(coupon)
		{
			this._controller.onCouponDelete(coupon);
		},

		onCouponApply: function(couponNode, coupon, discountId)
		{
			var form = this.getForm();

			if(form.elements['DISCOUNTS[COUPON_LIST]['+coupon+']'])
			{
				form.elements['DISCOUNTS[COUPON_LIST]['+coupon+']'].value = (couponNode.checked ? 'Y' : 'N');
				this.setDiscountById(discountId, couponNode.checked);
				this.onDataChanged();
			}
		},

		onCloseCustomDiscount: function(node)
		{
			node.parentNode.parentNode.removeChild(node.parentNode);
		},

		isProductAlreadyInBasket: function(productId)
		{
			var nodes = BX.findChildren(
				document,
				{
					attribute: {'data-offer-id': productId}
				},
				true
			);

			for(var i in nodes)
			{
				if(nodes.hasOwnProperty(i))
				{
					return true;
				}
			}

			return false;
		},

		getProductName: function(productId)
		{
			var nodes = BX.findChildren(
				document,
				{
					attribute: {
						'data-offer-id': productId,
						'data-product-field': 'name'
					}
				},
				true
			);

			for(var i in nodes)
			{
				if(nodes.hasOwnProperty(i))
				{
					return nodes[i].dataset.fullname || nodes[i].innerHTML;
				}
			}

			return '';
		},

		addProductSearch: function(params)
		{
			var funcName = 'CrmProductListonProductAdd';
			window[funcName] = BX.proxy(function(params, iblockId){this.onProductAdd(params, iblockId);}, this);

			var popup = new BX.CDialog({
				content_url: '/bitrix/tools/sale/product_search_dialog.php?'+
				'lang='+this._settings.languageId+
				'&LID='+this._settings.siteId+
				'&caller=order_edit'+
				'&func_name='+funcName+
				'&STORE_FROM_ID=0'+
				'&public_mode=Y',
				height: Math.max(500, window.innerHeight-400),
				width: Math.max(800, window.innerWidth-400),
				draggable: true,
				resizable: true,
				min_height: 500,
				min_width: 800,
				zIndex: 800
			});

			BX.addCustomEvent(popup, 'onWindowRegister', BX.defer(function(){
				popup.Get().style.position = 'fixed';
				popup.Get().style.top = (parseInt(popup.Get().style.top) - BX.GetWindowScrollPos().scrollTop) + 'px';
			}));

			BX.addCustomEvent(window, 'EntityEditorOrderController:onInnerCancel', BX.defer(function(){
				popup.Close();
			}));

			if(typeof BX.Crm.EntityEvent !== "undefined")
			{
				BX.addCustomEvent(window, BX.Crm.EntityEvent.names.update, BX.defer(function(){
					window.setTimeout(BX.delegate(function(){
						popup.Close()
					}, this), 0);
				}));
			}


			popup.Show();
		},

		getSetting: function(name, dafaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : dafaultval;
		},

		setSetting: function(name, value)
		{
			this._settings[name] = value;
		},

		setFormInputValue: function(name, value)
		{
			var form = this.getForm(),
				input;

			if(form.elements.name)
			{
				input = form.elements.name;
				input.value = value;
			}
			else
			{
				input = BX.create('input',{props:{type: 'hidden', value: value, name: name}});
				form.appendChild(input);
			}
		},

		getVatMenuElements: function(handler)
		{
			var result = [];

			for(var i in this._settings.vatRates)
			{
				if(this._settings.vatRates.hasOwnProperty(i))
				{
					result.push({value: i	, text: this._settings.vatRates[i], onclick: handler})
				}
			}
			return result;
		},

		onSkuSelect: function(basketCode, skuId, skuValue)
		{
			var productId = this._settings.basketItemsParams[basketCode].PRODUCT_ID,
				offersIblockId = this._settings.basketItemsParams[basketCode].OFFERS_IBLOCK_ID,
				skuProps = this._settings.productSkuValues[productId];

			skuProps[skuId] = skuValue;

			this._controller.onSkuSelect({
				BASKET_CODE: basketCode,
				CHANGED_SKU_ID: skuId,
				SKU_PROPS: skuProps,
				SKU_ORDER: this._settings.skuOrder[offersIblockId],
				PRODUCT_ID: productId
			});
		},

		showProductVatMenu: function(element, basketCode)
		{
			var _this = this;
			var handler = function(e, command){
				element.innerHTML = '<span>'+BX.prop.getString(command, "text")+'</span>';
				_this.setFormInputValue('PRODUCT['+basketCode+'][VAT_RATE]', BX.prop.getString(command, "value"));
				var menu = BX.PopupMenu.getMenuById(element.id);
				if(menu)
				{
					menu.popupWindow.close();
				}
				_this.onDataChanged();
			};

			BX.PopupMenu.show(
				element.id,
				element,
				this.getVatMenuElements(handler),
				{
					angle: false,
					events:
						{
//							onPopupShow: BX.delegate(this.onContextMenuShow, this),
							onPopupClose: function(){BX.PopupMenu.destroy(element.id);}
						}
				}
			);
		}
	};

	BX.Crm.Order.Product.List.create = function (id, config)
	{
		var self = new BX.Crm.Order.Product.List();
		self.initialize(id, config);
		return self;
	};
}