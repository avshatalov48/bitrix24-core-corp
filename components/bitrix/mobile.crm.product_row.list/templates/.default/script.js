BX.namespace("BX.Mobile.Crm.ProductEditor");
BX.Mobile.Crm.ProductEditor = {
	products: [],
	productsContainerNode: "",
	isEditMode: false,
	eventName: "",

	init: function(params)
	{
		if (typeof params === "object")
		{
			this.products = params.products || [];
			this.productsContainerNode = params.productsContainerNode || "";
			this.eventName = params.eventName || "";
			this.isEditMode = params.isEditMode == "Y" ? true : false;
		}

		if (this.products)
		{
			for(var i=0; i<this.products.length; i++)
			{
				if (!this.products[i].DATA_ROLE)
					this.products[i].DATA_ROLE = this.products[i].ID;

				this.generateProductHtml(this.products[i]);
			}
		}

		BX.addCustomEvent("onProductSelect", BX.proxy(function(data){
			this.addProduct(data);
		}, this));
	},

	addProduct: function(product)
	{
		if (typeof product !== "object")
			return;

		if (product.ID)
		{
			var newProductObj = {
				"PRODUCT_ID" : product.ID,
				"PRODUCT_NAME" : product.NAME,
				"FORMATTED_PRICE" : product.FORMATTED_PRICE,
				"QUANTITY" : 1,
				//	"MEASURE_CODE" :
				//	"MEASURE_NAME" :,
				"DATA_ROLE": product.ID + "_" + Math.random()
			};

			/*json += "\"ID\":\"" + this.getId().toString() + "\"";
			 json += ", \"PRODUCT_NAME\":\"" + this.getSetting(this._fixProductName ? "FIXED_PRODUCT_NAME" : "PRODUCT_NAME", "").replace(/("|\\)/g, "\\$1") + "\"";
			 json += ", \"PRODUCT_ID\":\"" + this.getSetting("PRODUCT_ID", 0).toString() + "\"";
			 json += ", \"QUANTITY\":\"" + this.getSetting("QUANTITY", 0.0).toFixed(4) + "\"";
			 json += ", \"MEASURE_CODE\":\"" + this.getSetting("MEASURE_CODE", 0) + "\"";
			 json += ", \"MEASURE_NAME\":\"" + this.getSetting("MEASURE_NAME", "").replace(/("|\\)/g, "\\$1") + "\"";
			 json += ", \"PRICE\":\"" + this.getSetting("PRICE", 0.0).toFixed(2) + "\"";
			 json += ", \"PRICE_EXCLUSIVE\":\"" + this.getSetting("PRICE_EXCLUSIVE", 0.0).toFixed(2) + "\"";
			 json += ", \"PRICE_NETTO\":\"" + this.getSetting("PRICE_NETTO", 0.0).toFixed(2) + "\"";
			 json += ", \"PRICE_BRUTTO\":\"" + this.getSetting("PRICE_BRUTTO", 0.0).toFixed(2) + "\"";
			 json += ", \"DISCOUNT_TYPE_ID\":\"" + this.getDiscountTypeId().toString() + "\"";

			 var discountRate = this.getSetting("DISCOUNT_RATE", null);
			 if(discountRate !== null)
			 {
			 json += ", \"DISCOUNT_RATE\":\"" + discountRate.toFixed(2) + "\"";
			 }
			 var discountSum = this.getSetting("DISCOUNT_SUM", null);
			 if(discountSum !== null)
			 {
			 json += ", \"DISCOUNT_SUM\":\"" + discountSum.toFixed(2) + "\"";
			 }
			 // save original tax rate and "included" flag if taxes is not allowed
			 json += ", \"TAX_RATE\":\"" + this.getSetting('TAX_RATE', 0.0).toFixed(2) + "\"";
			 json += ", \"TAX_INCLUDED\":\"" + (this.getSetting('TAX_INCLUDED', false) ? "Y" : "N") + "\"";
			 json += ", \"CUSTOMIZED\":\"Y\"";
			 json += ", \"SORT\":\"" + parseInt(this.getSetting('SORT', 0)) + "\"";
			 return "{" + json + "}";*/

			this.products.push(newProductObj);
			this.generateProductHtml(newProductObj);

			app.onCustomEvent(this.eventName, {});
		}
	},

	generateProductHtml: function(product)
	{
		var childrenNodes = [];
		if (this.isEditMode)
		{
			childrenNodes.push(
				BX.create("i", {
					attrs: {className: "mobile-grid-menu"},
					events: {
						"click": function () {
							BX.Mobile.Crm.ProductEditor.showProductActionMenu(this);
						}
					}
				})
			);
		}
		childrenNodes.push(
			BX.create("span", {
				attrs: {className: "mobile-grid-field-textarea-photo"},
				children: [
					BX.create("img", {
						attrs: {src : "/bitrix/js/mobile/images/icon-product.png"}
					})
				]
			}),
			BX.create("span", {
				html: product.PRODUCT_NAME,
				attrs: {className: "mobile-grid-field-textarea-name-title"}
			}),
			BX.create("span", {
				html: product.FORMATTED_PRICE,
				attrs: {className: "mobile-grid-field-textarea-price"}
			})
		);
		var newProduct = BX.create('div', {
			attrs: {
				className: "mobile-grid-field-textarea mobile-grid-field-textarea-name",
				"data-role": product.DATA_ROLE
			},
			children: childrenNodes
		});
		if (this.productsContainerNode)
			this.productsContainerNode.appendChild(newProduct);

	},

	showProductActionMenu : function(element)
	{
		new BXMobileApp.UI.ActionSheet({
				buttons: [
					{
						title: BX.message("CRM_JS_EDIT"),
						callback: function()
						{
							BX.Mobile.Crm.ProductEditor.editProduct(element);
						}
					},
					{
						title: BX.message("CRM_JS_DELETE"),
						callback: function()
						{
							BX.Mobile.Crm.ProductEditor.deleteProduct(element);
						}
					}
				]
			}, 'actionSheetStatus'
		).show();
	},

	deleteProduct : function(element)
	{
		var productContainer = element.parentNode;
		var dataRole = productContainer.getAttribute("data-role");
		for(var i=0; i<this.products.length; i++)
		{
			if (this.products[i].DATA_ROLE == dataRole)
			{
				this.products.splice(i,1);
				break;
			}
		}

		BX.remove(productContainer);

		app.onCustomEvent(this.eventName, {});
	}
};