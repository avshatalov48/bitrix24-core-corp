import History from "./history";

/** @memberof BX.Crm.Timeline.Actions */
export default class OrderModification extends History
{
	constructor()
	{
		super();
	}

	getMessage(name)
	{
		const m = OrderModification.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	}

	getTitle()
	{
		return this.getTextDataParam("TITLE");
	}

	getStatusInfo()
	{
		const statusInfo = {};
		let value = null;
		let classCode = null;
		const fieldName = this.getTextDataParam("CHANGED_ENTITY");
		const fields = this.getObjectDataParam('FIELDS');
		const entityData = this.getAssociatedEntityData();

		if (fieldName === BX.CrmEntityType.names.order)
		{
			if (BX.prop.get(fields, 'ORDER_CANCELED') === 'Y')
			{
				value = "canceled";
				classCode  = "not-paid";
			}
			else if (BX.prop.get(fields, 'ORDER_DONE') === 'Y')
			{
				value = "done";
				classCode  = "done";
			}
			else if (BX.prop.getString(entityData, "VIEWED", '') === 'Y')
			{
				value = "viewed";
				classCode  = "done";
			}
			else if (BX.prop.getString(entityData, "SENT", '') === 'Y')
			{
				value = "sent";
				classCode  = "sent";
			}
		}
		if (fieldName === BX.CrmEntityType.names.orderpayment)
		{
			const psStatusCode = BX.prop.get(fields, 'STATUS_CODE', false);
			if (psStatusCode)
			{
				if (psStatusCode === 'ERROR')
				{
					value = "orderPaymentError";
					classCode = "payment-error";
				}
			}
			else if (BX.prop.getString(entityData, "VIEWED", '') === 'Y')
			{
				value = "viewed";
				classCode  = "done";
			}
			else if (BX.prop.getString(entityData, "SENT", '') === 'Y')
			{
				value = "sent";
				classCode  = "sent";
			}
			else
			{
				value = BX.prop.get(fields, 'ORDER_PAID') === 'Y' ? "paid" : "unpaid";
				classCode  = BX.prop.get(fields, 'ORDER_PAID') === 'Y' ? "paid" : "not-paid";
			}
		}
		else if (fieldName === BX.CrmEntityType.names.ordershipment && BX.prop.get(fields, 'ORDER_DEDUCTED', false))
		{
			value = BX.prop.get(fields, 'ORDER_DEDUCTED') === 'Y' ? "deducted" : "unshipped";
			classCode  = BX.prop.get(fields, 'ORDER_DEDUCTED') === 'Y' ? "shipped" : "not-shipped";
		}
		else if (fieldName === BX.CrmEntityType.names.ordershipment && BX.prop.get(fields, 'ORDER_ALLOW_DELIVERY', false))
		{
			value = BX.prop.get(fields, 'ORDER_ALLOW_DELIVERY') === 'Y' ? "allowedDelivery" : "disallowedDelivery";
			classCode  = BX.prop.get(fields, 'ORDER_ALLOW_DELIVERY') === 'Y' ? "allowed-delivery" : "disallowed-delivery";
		}

		if (value)
		{
			statusInfo.className = "crm-entity-stream-content-event-" + classCode;
			statusInfo.message = this.getMessage(value);
		}

		return statusInfo;
	}

	getHeaderChildren()
	{
		const children = [
			BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-event-title"},
					events: {click: this._headerClickHandler},
					text: this.getTitle(),
				}
			)
		];
		const statusInfo = this.getStatusInfo();
		if (BX.type.isNotEmptyObject(statusInfo))
		{
			children.push(
				BX.create("SPAN",
					{
						attrs: { className: statusInfo.className },
						text: statusInfo.message
					}
				));
		}
		children.push(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			));
		return children;
	}

	prepareContentDetails()
	{
		const entityData = this.getAssociatedEntityData();
		const entityTypeId = this.getAssociatedEntityTypeId();
		const entityId = this.getAssociatedEntityId();
		const title = BX.prop.getString(entityData, "TITLE");
		const htmlTitle = BX.prop.getString(entityData, "HTML_TITLE", "");
		const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
		const nodes = [];
		if(title !== "")
		{
			const descriptionNode = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail-description"}});

			if(showUrl === "" || (entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()))
			{
				descriptionNode.appendChild(BX.create("SPAN", { text: title + " " + htmlTitle}));
			}
			else
			{
				if (htmlTitle === "")
				{
					descriptionNode.appendChild(BX.create("A", {attrs: {href: showUrl}, text: title}));
				}
				else
				{
					descriptionNode.appendChild(BX.create("SPAN", { text: title + " "}));
					descriptionNode.appendChild(BX.create("A", {attrs: {href: showUrl}, text: htmlTitle}));
				}
			}

			const legend = BX.prop.getString(entityData, "LEGEND");
			if(legend !== "")
			{
				descriptionNode.appendChild(BX.create("SPAN", { html: " " + legend }));
			}

			const sublegend = BX.prop.getString(entityData, "SUBLEGEND", '');
			if (sublegend !== '')
			{
				descriptionNode.appendChild(BX.create("BR"));
				descriptionNode.appendChild(BX.create("SPAN", { text: " " + sublegend}));
			}
			nodes.push(descriptionNode);
		}

		return nodes;
	}

	prepareViewedContentDetails()
	{
		const entityData = this.getAssociatedEntityData();
		const entityTypeId = this.getAssociatedEntityTypeId();
		const entityId = this.getAssociatedEntityId();
		const title = BX.prop.getString(entityData, "TITLE");
		const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");
		const nodes = [];

		if (title !== "")
		{
			const sublegend = BX.prop.getString(entityData, "SUBLEGEND", '');
			if (sublegend !== "")
			{
				const descriptionNode = BX.create("DIV", {
					attrs: {className: "crm-entity-stream-content-detail-description"},
					text: sublegend,
				});
				nodes.push(descriptionNode);
			}


			if(entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId())
			{
				nodes.push(BX.create("SPAN", { text: title }));
			}
			else
			{
				nodes.push(BX.create("A", { attrs: { href: showUrl }, text: title }));
			}

			const legend = BX.prop.getString(entityData, "LEGEND");
			if(legend !== "")
			{
				nodes.push(BX.create("SPAN", { html: " " + legend }));
			}
		}

		return nodes;
	}

	prepareSentContentDetails()
	{
		const entityData = this.getAssociatedEntityData();
		const entityTypeId = this.getAssociatedEntityTypeId();
		const entityId = this.getAssociatedEntityId();
		const title = BX.prop.getString(entityData, "TITLE");
		const showUrl = BX.prop.getString(entityData, 'SHOW_URL', '');
		const destination = BX.prop.getString(entityData, 'DESTINATION_TITLE', '');
		const nodes = [];

		if (title !== "")
		{
			const detailNode = BX.create('DIV', {attrs: {className: 'crm-entity-stream-content-detail-description'}});
			if(showUrl === "" || (entityTypeId === this.getOwnerTypeId() && entityId === this.getOwnerId()))
			{
				detailNode.appendChild(BX.create("SPAN", { text: title }));
			}
			else
			{
				detailNode.appendChild(BX.create('A', { attrs: { href: showUrl }, text: title }));
			}

			const legend = BX.prop.getString(entityData, "LEGEND");
			if(legend !== "")
			{
				detailNode.appendChild(BX.create("SPAN", { html: " " + legend }));
			}

			if (destination)
			{
				detailNode.appendChild(BX.create('SPAN', {
					attrs: { className: 'crm-entity-stream-content-detail-order-destination' },
					text: destination,
				}));
			}

			nodes.push(detailNode);

			const sliderLinkNode = BX.create('A', {
				attrs: {href: "#"},
				text: this.getMessage('orderPaymentProcess'),
				events: {
					click: BX.proxy(this.startSalescenterApplication, this),
				},
			});
			nodes.push(sliderLinkNode);
		}

		return nodes;
	}

	startSalescenterApplication()
	{
		BX.loadExt('salescenter.manager').then(function()
		{
			const fields = this.getObjectDataParam('FIELDS'),
				ownerTypeId = BX.prop.get(fields, 'OWNER_TYPE_ID', BX.CrmEntityType.enumeration.deal);
			let ownerId = BX.prop.get(fields, 'OWNER_ID', 0);
			const paymentId = BX.prop.get(fields, 'PAYMENT_ID', 0),
				shipmentId = BX.prop.get(fields, 'SHIPMENT_ID', 0),
				orderId = BX.prop.get(fields, 'ORDER_ID', 0)
			;

			// compatibility
			if (!ownerId)
			{
				ownerId = BX.prop.get(fields, 'DEAL_ID', 0);
			}

			BX.Salescenter.Manager.openApplication({
				disableSendButton: '',
				context: 'deal',
				ownerTypeId: ownerTypeId,
				ownerId: ownerId,
				mode: ownerTypeId === BX.CrmEntityType.enumeration.deal ? 'payment_delivery' : 'payment',
				templateMode: 'view',
				orderId: orderId,
				paymentId: paymentId,
				shipmentId: shipmentId,
			});
		}.bind(this));
	}

	preparePaidPaymentContentDetails()
	{
		const entityData = this.getAssociatedEntityData(),
			title = BX.prop.getString(entityData, "TITLE"),
			date = BX.prop.getString(entityData, "DATE", ""),
			paySystemName = BX.prop.getString(entityData, "PAY_SYSTEM_NAME", ""),
			sum = BX.prop.getString(entityData, 'SUM', ''),
			currency = BX.prop.getString(entityData, 'CURRENCY', ''),
			nodes = [];

		if(title !== "")
		{
			const paymentDetail = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail-payment"}});
			paymentDetail.appendChild(
				BX.create("DIV", {
					attrs: { className: "crm-entity-stream-content-detail-payment-value"},
					children: [
						BX.create('SPAN', {
							attrs: { className: "crm-entity-stream-content-detail-payment-text"},
							html: sum,
						}),
						BX.create('SPAN', {
							attrs: { className: "crm-entity-stream-content-detail-payment-currency"},
							html: currency,
						}),
					]
				})
			);

			const logotip = BX.prop.getString(entityData, "LOGOTIP", null);
			if (logotip)
			{
				paymentDetail.appendChild(
					BX.create("DIV", {
						attrs: { className: "crm-entity-stream-content-detail-payment-logo"},
						style: {
							backgroundImage: "url(" + encodeURI(logotip) + ")",
						}
					})
				);
			}
			nodes.push(paymentDetail);

			const descriptionNode = BX.create("DIV", {
				attrs: {className: "crm-entity-stream-content-detail-description"},
				children: [
					BX.create('SPAN', {
						text: date
					}),
					BX.create('SPAN', {
						attrs: {className: "crm-entity-stream-content-detail-description-info"},
						text: this.getMessage('orderPaySystemTitle')
					}),
					BX.create('SPAN', {
						text: paySystemName
					}),
				]
			});
			nodes.push(descriptionNode);
		}

		return nodes;
	}

	prepareContent()
	{
		const fields = this.getObjectDataParam('FIELDS'),
			isPaid = BX.prop.get(fields, 'ORDER_PAID') === 'Y',
			isClick = BX.prop.get(fields, 'PAY_SYSTEM_CLICK') === 'Y',
			isManualContinuePay = BX.prop.get(fields, 'MANUAL_CONTINUE_PAY') === 'Y',
			isManualAddCheck = BX.prop.get(fields, 'NEED_MANUAL_ADD_CHECK') === 'Y',
			entityId = this.getAssociatedEntityTypeId();

		if (entityId === BX.CrmEntityType.enumeration.orderpayment && isPaid)
		{
			return this.preparePaidPaymentContent();
		}
		else if (entityId === BX.CrmEntityType.enumeration.orderpayment && isClick)
		{
			return this.prepareClickedPaymentContent();
		}
		else if (entityId === BX.CrmEntityType.enumeration.order && isManualContinuePay)
		{
			return this.prepareManualContinuePayContent();
		}
		else if (entityId === BX.CrmEntityType.enumeration.orderpayment && isManualAddCheck)
		{
			return this.prepareManualAddCheck();
		}

		return this.prepareItemOrderContent();
	}

	prepareItemOrderContent()
	{
		const entityData = this.getAssociatedEntityData();
		const isViewed = BX.prop.getString(entityData, "VIEWED", '') === 'Y';
		const isSent = BX.prop.getString(entityData, "SENT", '') === 'Y';
		const fields = this.getObjectDataParam('FIELDS');
		const psStatusCode = BX.prop.get(fields, 'STATUS_CODE', false);

		const wrapper = BX.create("DIV", {attrs: {className: 'crm-entity-stream-section crm-entity-stream-section-history'}});
		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: 'crm-entity-stream-section-icon ' + this.getIconClassName() } })
		);

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		const header = BX.create("DIV",
			{
				attrs: {className: "crm-entity-stream-content-header"},
				children: this.getHeaderChildren()
			});

		let contentChildren = null;
		if (isViewed)
		{
			contentChildren = this.prepareViewedContentDetails();
		}
		else if (isSent)
		{
			contentChildren = this.prepareSentContentDetails();
		}
		else if (psStatusCode === 'ERROR')
		{
			contentChildren = this.prepareErrorPaymentContentDetails();
		}
		else
		{
			contentChildren = this.prepareContentDetails();
		}

		content.appendChild(header);
		content.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: contentChildren
				})
		);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);

		return wrapper;
	}

	preparePaidPaymentContent()
	{
		const wrapper = BX.create("DIV", {attrs: {className: 'crm-entity-stream-section'}});
		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: 'crm-entity-stream-section-icon crm-entity-stream-section-icon-wallet' } })
		);

		const header = [
			BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-event-title"},
					children:
						[
							BX.create("A",
								{
									attrs: {href: "#"},
									events: {click: this._headerClickHandler},
									text: this.getMessage('orderPaymentSuccessTitle')
								}
							)
						]
				}
			)
		];
		header.push(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			)
		);

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		const headerWrap = BX.create("DIV", {
			attrs: {className: "crm-entity-stream-content-header"},
			children: header
		});

		const contentChildren = this.preparePaidPaymentContentDetails();
		content.appendChild(headerWrap);
		content.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: contentChildren
				})
		);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);

		return wrapper;
	}

	prepareErrorPaymentContentDetails()
	{
		const entityData = this.getAssociatedEntityData(),
			date = BX.prop.getString(entityData, 'DATE', ''),
			fields = this.getObjectDataParam('FIELDS'),
			paySystemName = BX.prop.getString(fields, 'PAY_SYSTEM_NAME', ''),
			paySystemError = BX.prop.getString(fields, 'STATUS_DESCRIPTION', ''),
			nodes = [];

		const descriptionNode = BX.create('DIV', {
			attrs: {className: 'crm-entity-stream-content-detail-description'},
			children: [
				BX.create('SPAN', {
					text: date
				}),
				BX.create('SPAN', {
					attrs: {className: 'crm-entity-stream-content-detail-description-info'},
					text: this.getMessage('orderPaySystemTitle')
				}),
				BX.create('SPAN', {
					text: paySystemName
				}),
			]
		});
		nodes.push(descriptionNode);

		const errorDetailNode = BX.create('DIV', {
			attrs: {className: 'crm-entity-stream-content-event-payment-initiate-pay-error'},
			text: this.getMessage('orderPaymentStatusErrorReason').replace("#PAYSYSTEM_ERROR#", paySystemError),
		});

		nodes.push(errorDetailNode);

		return nodes;
	}

	prepareClickedPaymentContent()
	{
		const wrapper = BX.create("DIV", {attrs: {className: 'crm-entity-stream-section crm-entity-stream-section-history'}});
		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: 'crm-entity-stream-section-icon ' + this.getIconClassName() } })
		);

		const header = [
			BX.create("DIV",
				{
					attrs: {className: "crm-entity-stream-content-event-title"},
					children:
						[
							BX.create("A",
								{
									attrs: {href: "#"},
									events: {click: this._headerClickHandler},
									text: this.getTitle(),
								}
							)
						]
				}
			)
		];
		header.push(
			BX.create("SPAN",
				{
					attrs: { className: "crm-entity-stream-content-event-time" },
					text: this.formatTime(this.getCreatedTime())
				}
			)
		);

		const content = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-event"}});
		const headerWrap = BX.create("DIV", {
			attrs: {className: "crm-entity-stream-content-header"},
			children: header
		});

		const contentChildren = this.prepareClickedPaymentContentDetails();
		content.appendChild(headerWrap);
		content.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "crm-entity-stream-content-detail" },
					children: contentChildren
				})
		);

		//region Author
		const authorNode = this.prepareAuthorLayout();
		if(authorNode)
		{
			content.appendChild(authorNode);
		}
		//endregion

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-section-content" }, children: [ content ] })
		);

		return wrapper;
	}

	prepareClickedPaymentContentDetails()
	{
		const fields = this.getObjectDataParam('FIELDS'),
			paySystemName = BX.prop.getString(fields, 'PAY_SYSTEM_NAME', ''),
			nodes = [];

		if(paySystemName !== '')
		{
			const descriptionNode = BX.create("DIV", {attrs: {className: "crm-entity-stream-content-detail-description"}});
			descriptionNode.appendChild(
				BX.create('SPAN', {
					attrs: { className: "crm-entity-stream-content-clicked-description-info" },
					text: this.getMessage('orderPaymentPaySystemClick')
				})
			);
			descriptionNode.appendChild(
				BX.create('SPAN', {
					attrs: { className: "crm-entity-stream-content-clicked-description-name" },
					text: paySystemName
				})
			);

			nodes.push(descriptionNode);
		}

		return nodes;
	}

	prepareManualContinuePayContent()
	{
		const wrapper = BX.create("DIV", {attrs: {className: 'crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-advice'}});
		wrapper.appendChild(
			BX.create("DIV", {
				attrs: { className: 'crm-entity-stream-section-icon crm-entity-stream-section-icon-advice' },
				children: [BX.create('i')],
			})
		);

		const content = BX.create("DIV", {
			attrs: {className: "crm-entity-stream-advice-info"},
			text: this.getMessage('orderManualContinuePay'),
		});

		wrapper.appendChild(
			BX.create("DIV", { attrs: { className: "crm-entity-stream-advice-content" }, children: [ content ] })
		);

		return wrapper;
	}

	prepareManualAddCheck()
	{
		const entityData = this.getAssociatedEntityData();
		const showUrl = BX.prop.getString(entityData, "SHOW_URL", "");

		const wrapper = BX.create(
			"DIV",
			{
				attrs: {
					className: 'crm-entity-stream-section crm-entity-stream-section-history crm-entity-stream-section-advice'
				}
			}
		);
		wrapper.appendChild(
			BX.create(
				"DIV",
				{
					attrs: {
						className: 'crm-entity-stream-section-icon crm-entity-stream-section-icon-advice'
					}
				}
			)
		);

		const htmlTitle = this.getMessage('orderManualAddCheck').replace("#HREF#", showUrl);
		const content = BX.create(
			"DIV",
			{
				attrs: {
					className: "crm-entity-stream-advice-info",
				},
				html: htmlTitle,
			}
		);

		const link = BX.create(
			"DIV",
			{
				attrs: {
					className: "crm-entity-stream-advice-info",
				},
				children: [
					BX.create(
						"A",
						{
							attrs: {
								className: "crm-entity-stream-content-detail-target",
								href: "#",
							},
							events: {
								click: BX.delegate(function (e) {
									top.BX.Helper.show('redirect=detail&code=13742126');
									e.preventDefault ? e.preventDefault() : (e.returnValue = false);
								})
							},
							html: this.getMessage('orderManualAddCheckHelpLink'),
						}
					)
				]
			}
		);

		wrapper.appendChild(
			BX.create(
				"DIV",
				{
					attrs: {
						className: "crm-entity-stream-advice-content"
					},
					children: [ content, link ]
				}
			)
		);

		return wrapper;
	}

	getIconClassName()
	{
		return 'crm-entity-stream-section-icon-store';
	}

	static create(id, settings)
	{
		const self = new OrderModification();
		self.initialize(id, settings);
		return self;
	}

	static messages = {};
}
