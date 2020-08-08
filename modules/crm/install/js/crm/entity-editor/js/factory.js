BX.namespace("BX.Crm");

//region FACTORY
if(typeof BX.Crm.EntityEditorValidatorFactory === "undefined")
{
	BX.Crm.EntityEditorValidatorFactory =
		{
			create: function(type, settings)
			{
				if(type === "person")
				{
					return BX.Crm.EntityPersonValidator.create(settings);
				}

				return null;
			}
		}
}

if(typeof BX.Crm.EntityEditorControlFactory === "undefined")
{
	BX.Crm.EntityEditorControlFactory =
		{
			initialized: false,
			methods: {},

			isInitialized: function()
			{
				return this.initialized;
			},
			initialize: function()
			{
				if(this.initialized)
				{
					return;
				}

				var eventArgs = { methods: {} };
				BX.onCustomEvent(
					window,
					"BX.Crm.EntityEditorControlFactory:onInitialize",
					[ this, eventArgs ]
				);

				for(var name in eventArgs.methods)
				{
					if(eventArgs.methods.hasOwnProperty(name))
					{
						this.registerFactoryMethod(name, eventArgs.methods[name]);
					}
				}

				this.initialized = true;
			},
			registerFactoryMethod: function(name, method)
			{
				if(BX.type.isFunction(method))
				{
					this.methods[name] = method;
				}
			},
			create: function(type, controlId, settings)
			{
				if(!this.initialized)
				{
					this.initialize();
				}


				if(type === "section")
				{
					return BX.Crm.EntityEditorSection.create(controlId, settings);
				}
				else if(type === "text")
				{
					return BX.Crm.EntityEditorText.create(controlId, settings);
				}
				else if(type === "number")
				{
					return BX.Crm.EntityEditorNumber.create(controlId, settings);
				}
				else if(type === "datetime")
				{
					return BX.Crm.EntityEditorDatetime.create(controlId, settings);
				}
				else if(type === "boolean")
				{
					return BX.Crm.EntityEditorBoolean.create(controlId, settings);
				}
				else if(type === "list")
				{
					return BX.Crm.EntityEditorList.create(controlId, settings);
				}
				else if(type === "multilist")
				{
					return BX.Crm.EntityEditorMultiList.create(controlId, settings);
				}
				else if(type === "html")
				{
					return BX.Crm.EntityEditorHtml.create(controlId, settings);
				}
				else if(type === "money")
				{
					return BX.Crm.EntityEditorMoney.create(controlId, settings);
				}
				else if(type === "moneyPay")
				{
					return BX.Crm.EntityEditorMoneyPay.create(controlId, settings);
				}
				else if(type === "image")
				{
					return BX.Crm.EntityEditorImage.create(controlId, settings);
				}
				else if(type === "user")
				{
					return BX.Crm.EntityEditorUser.create(controlId, settings);
				}
				else if(type === "multiple_user")
				{
					return BX.Crm.EntityEditorMultipleUser.create(controlId, settings);
				}
				else if(type === "address_form")
				{
					return BX.Crm.EntityEditorAddress.create(controlId, settings);
				}
				else if(type === "address")
				{
					return BX.Crm.EntityEditorAddressField.create(controlId, settings);
				}
				else if(type === "crm_entity")
				{
					return BX.Crm.EntityEditorEntity.create(controlId, settings);
				}
				else if(type === "file_storage")
				{
					return BX.Crm.EntityEditorFileStorage.create(controlId, settings);
				}
				else if(type === "phone")
				{
					return BX.Crm.EntityEditorPhone.create(controlId, settings);
				}
				else if(type === "client")
				{
					return BX.Crm.EntityEditorClient.create(controlId, settings);
				}
				else if(type === "client_light")
				{
					return BX.Crm.EntityEditorClientLight.create(controlId, settings);
				}
				else if(type === "multifield")
				{
					return BX.Crm.EntityEditorMultifield.create(controlId, settings);
				}
				else if(type === "product_row_summary")
				{
					return BX.Crm.EntityEditorProductRowSummary.create(controlId, settings);
				}
				else if(type === "requisite_selector")
				{
					return BX.Crm.EntityEditorRequisiteSelector.create(controlId, settings);
				}
				else if(type === "requisite")
				{
					return BX.Crm.EntityEditorRequisiteField.create(controlId, settings);
				}
				else if(type === "requisite_address")
				{
					return BX.Crm.EntityEditorRequisiteAddressField.create(controlId, settings);
				}
				else if(type === "requisite_list")
				{
					return BX.Crm.EntityEditorRequisiteList.create(controlId, settings);
				}
				else if(type === "userField")
				{
					return BX.Crm.EntityEditorUserField.create(controlId, settings);
				}
				else if(type === "userFieldConfig")
				{
					return BX.Crm.EntityEditorUserFieldConfigurator.create(controlId, settings);
				}
				else if(type === "recurring")
				{
					return BX.Crm.EntityEditorRecurring.create(controlId, settings);
				}
				else if(type === "recurring_custom_row")
				{
					return BX.Crm.EntityEditorRecurringCustomRowField.create(controlId, settings);
				}
				else if(type === "recurring_single_row")
				{
					return BX.Crm.EntityEditorRecurringSingleField.create(controlId, settings);
				}
				else if(type === "custom")
				{
					return BX.Crm.EntityEditorCustom.create(controlId, settings);
				}
				else if(type === "shipment")
				{
					return BX.Crm.EntityEditorShipment.create(controlId, settings);
				}
				else if(type === "payment")
				{
					return BX.Crm.EntityEditorPayment.create(controlId, settings);
				}
				else if(type === "payment_status")
				{
					return BX.Crm.EntityEditorPaymentStatus.create(controlId, settings);
				}
				else if(type === "payment_check")
				{
					return BX.Crm.EntityEditorPaymentCheck.create(controlId, settings);
				}
				else if(type === "order_subsection")
				{
					return BX.Crm.EntityEditorSubsection.create(controlId, settings);
				}
				else if(type === "order_property_wrapper")
				{
					return BX.Crm.EntityEditorOrderPropertyWrapper.create(controlId, settings);
				}
				else if(type === "order_property_subsection")
				{
					return BX.Crm.EntityEditorOrderPropertySubsection.create(controlId, settings);
				}
				else if(type === "order_property_file")
				{
					return BX.Crm.EntityEditorOrderPropertyFile.create(controlId, settings);
				}
				else if(type === "order_product_property")
				{
					return BX.Crm.EntityEditorOrderProductProperty.create(controlId, settings);
				}
				else if(type === "order_person_type")
				{
					return BX.Crm.EntityEditorOrderPersonType.create(controlId, settings);
				}
				else if(type === "order_quantity")
				{
					return BX.Crm.EntityEditorOrderQuantity.create(controlId, settings);
				}
				else if(type === "order_user")
				{
					return BX.Crm.EntityEditorOrderUser.create(controlId, settings);
				}
				else if(type === "order_client")
				{
					return BX.Crm.EntityEditorOrderClient.create(controlId, settings);
				}
				else if(type === "hidden")
				{
					return BX.Crm.EntityEditorHidden.create(controlId, settings);
				}
				else if(type === "delivery_selector")
				{
					return BX.Crm.EntityEditorDeliverySelector.create(controlId, settings);
				}
				else if(type === "shipment_extra_services")
				{
					return BX.Crm.EntityEditorShipmentExtraServices.create(controlId, settings);
				}

				for(var name in this.methods)
				{
					if(!this.methods.hasOwnProperty(name))
					{
						continue;
					}

					var control = this.methods[name](type, controlId, settings);
					if(control)
					{
						return control;
					}
				}

				return null;
			}
		};
}

if(typeof BX.Crm.EntityEditorControllerFactory === "undefined")
{
	BX.Crm.EntityEditorControllerFactory =
		{
			create: function(type, controllerId, settings)
			{
				if(type === "requisite_controller")
				{
					return BX.Crm.EntityEditorRequisiteController.create(controllerId, settings);
				}
				if(type === "product_row_proxy")
				{
					return BX.Crm.EntityEditorProductRowProxy.create(controllerId, settings);
				}
				else if(type === "order_controller")
				{
					return BX.Crm.EntityEditorOrderController.create(controllerId, settings);
				}
				else if(type === "order_shipment_controller")
				{
					return BX.Crm.EntityEditorOrderShipmentController.create(controllerId, settings);
				}
				else if(type === "order_payment_controller")
				{
					return BX.Crm.EntityEditorOrderPaymentController.create(controllerId, settings);
				}
				else if(type === "order_product_controller")
				{
					return BX.Crm.EntityEditorOrderProductController.create(controllerId, settings);
				}

				return null;
			}
		};
}

if(typeof BX.Crm.EntityEditorModelFactory === "undefined")
{
	BX.Crm.EntityEditorModelFactory =
		{
			create: function(entityTypeId, id, settings)
			{
				if(entityTypeId === BX.CrmEntityType.enumeration.lead)
				{
					return BX.Crm.LeadModel.create(id, settings);
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.contact)
				{
					return BX.Crm.ContactModel.create(id, settings);
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.company)
				{
					return BX.Crm.CompanyModel.create(id, settings);
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.deal)
				{
					return BX.Crm.DealModel.create(id, settings);
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.dealrecurring)
				{
					return BX.Crm.DealRecurringModel.create(id, settings);
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.quote)
				{
					return BX.Crm.QuoteModel.create(id, settings);
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.order)
				{
					return BX.Crm.OrderModel.create(id, settings);
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.orderpayment)
				{
					return BX.Crm.OrderPaymentModel.create(id, settings);
				}
				else if(entityTypeId === BX.CrmEntityType.enumeration.ordershipment)
				{
					return BX.Crm.OrderShipmentModel.create(id, settings);
				}
				return BX.Crm.EntityModel.create(id, settings);
			}
		};
}
//endregion
