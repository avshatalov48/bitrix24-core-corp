/**
 * @module layout/ui/entity-editor/control/opportunity/document-list
 */
jn.define('layout/ui/entity-editor/control/opportunity/document-list', (require, exports, module) => {
	const { confirmDestructiveAction } = require('alert');
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { ContextMenu } = require('layout/ui/context-menu');
	const { EventEmitter } = require('event-emitter');
	const { handleErrors } = require('crm/error');
	const { Feature } = require('feature');
	const { ImageAfterTypes } = require('layout/ui/context-menu/item');
	const { PaymentPayOpener } = require('crm/terminal/entity/payment-pay-opener');
	const { Notify } = require('notify');
	const { MultiFieldDrawer, MultiFieldType } = require('crm/multi-field-drawer');
	const { TypeId } = require('crm/type');
	const { PlanRestriction } = require('layout/ui/plan-restriction');

	/**
	 * @class DocumentList
	 */
	class DocumentList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				documents: props.documentsData.documents,
				totalAmount: props.documentsData.totalAmount,
			};

			this.currencyId = props.documentsData.currencyId;
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			this.updateTotalAmount = this.updateTotalAmount.bind(this);
			this.updateDocuments = this.updateDocuments.bind(this);
			this.documentMenu = null;
			this.isUsedInventoryManagement = props.isUsedInventoryManagement;
			this.resendParams = props.resendParams;
			this.isOnecMode = props.isOnecMode;
			this.restrictions = props.restrictions;
		}

		canAddRealization()
		{
			return this.props.isUsedInventoryManagement
				&& !this.props.modeWithOrders
				&& this.props.salesOrderRights.modify;
		}

		componentDidMount()
		{
			this.customEventEmitter.on('DetailCard::onUpdate', this.updateTotalAmount);
			this.customEventEmitter.on('EntityDocuments::reload', this.updateDocuments);
		}

		componentWillUnmount()
		{
			this.customEventEmitter.off('DetailCard::onUpdate', this.updateTotalAmount);
			this.customEventEmitter.off('EntityDocuments::reload', this.updateDocuments);
		}

		updateTotalAmount()
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction('crmmobile.EntityDetails.getEntityTotalAmount', {
					json: {
						entityTypeId: this.props.entityTypeId,
						entityId: this.props.entityId,
					},
				})
					.then((response) => {
						this.setState({
							totalAmount: response.data.totalAmount,
						});
						this.currencyId = response.data.currencyId;
						resolve();
					})
					.catch(reject);
			});
		}

		updateDocuments()
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction('crmmobile.EntityDetails.getEntityDocuments', {
					json: {
						entityTypeId: this.props.entityTypeId,
						entityId: this.props.entityId,
					},
				})
					.then((response) => {
						this.setState({
							documents: response.data.documents,
							totalAmount: response.data.totalAmount,
						});
						this.currencyId = response.data.currencyId;
						resolve();
					})
					.catch(reject);
			});
		}

		renderContent()
		{
			return View(
				{
					style: styles.documentsContainer,
					clickable: false,
				},
				this.renderTitle(),
				this.renderEmptyStateDescription(),
				...this.state.documents.map((document) => this.renderDocumentItem(document)),
				this.renderAddDocumentButton(),
				this.renderSeparator(),
				this.renderTotalSum(),
			);
		}

		renderTitle()
		{
			return View(
				{},
				Text({
					style: styles.documentsTitle,
					text: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DEAL_TITLE_MSGVER_1')
						.toLocaleUpperCase(env.languageId),
				}),
			);
		}

		renderEmptyStateDescription()
		{
			if (this.state.documents.length > 0)
			{
				return View();
			}

			let description = this.canAddRealization()
				? Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DEAL_EMPTYSTATE_DESCRIPTION_WITH_REALIZATION')
				: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DEAL_EMPTYSTATE_DESCRIPTION');

			return View(
				{},
				Text({
					style: styles.documentsEmptyStateDescription,
					text: description,
				}),
			);
		}

		renderDocumentItem(document)
		{
			switch (document.TYPE)
			{
				case 'PAYMENT':
				case 'TERMINAL_PAYMENT':
					return this.renderPaymentDocument(document);
				case 'SHIPMENT':
					return this.renderShipmentDocument(document);
				case 'SHIPMENT_DOCUMENT':
					return this.renderRealizationDocument(document);
				default:
					return View();
			}
		}

		getDocumentName(document, showDate = true)
		{
			const replacements = {
				'#DATE#': document.FORMATTED_DATE,
				'#ACCOUNT_NUMBER#': document.ACCOUNT_NUMBER,
			};

			let locPhrase = null;
			switch (document.TYPE)
			{
				case 'PAYMENT':
				case 'TERMINAL_PAYMENT':
					locPhrase = 'MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_DATE';
					break;
				case 'SHIPMENT':
					locPhrase = 'MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_DATE';
					break;
				case 'SHIPMENT_DOCUMENT':
					locPhrase = showDate
						? 'MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_DATE'
						: 'MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_TITLE';
					break;
			}

			return locPhrase
				? Loc.getMessage(locPhrase, replacements)
				: '';
		}

		getDocumentSumText(document)
		{
			const money = Money.create({
				amount: document.SUM,
				currency: document.CURRENCY,
			});

			const replacements = {
				'#SUM#': money.formatted,
			};

			let locPhrase = null;
			switch (document.TYPE)
			{
				case 'PAYMENT':
				case 'TERMINAL_PAYMENT':
				case 'SHIPMENT':
					locPhrase = 'MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_AMOUNT';
					break;
				case 'SHIPMENT_DOCUMENT':
					locPhrase = 'MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_SUM';
					break;
			}

			const deliveryNameText = document.DELIVERY_NAME ? `${document.DELIVERY_NAME}, ` : '';
			const result = Loc.getMessage(locPhrase, replacements);

			return `(${deliveryNameText}${result})`;
		}

		renderPaymentDocument(document)
		{
			return View(
				{
					testId: 'documentListPaymentItem',
					style: styles.documentItem,
				},
				View(
					{
						style: styles.documentNameBlock,
						onClick: () => this.customEventEmitter.emit('EntityPaymentDocument::Click', [document]),
					},
					Text({
						style: styles.documentNameText,
						text: this.getDocumentName(document),
					}),
					Text({
						style: styles.documentNameText,
						text: this.getDocumentSumText(document),
					}),
				),
				View(
					{
						style: styles.documentDataBlock,
					},
					this.renderBadge(document),
					this.renderDots(document),
				),
			);
		}

		renderShipmentDocument(document)
		{
			return View(
				{
					testId: 'documentListShipmentItem',
					style: styles.documentItem,
				},
				View(
					{
						style: styles.documentNameBlock,
						onClick: () => {
							// this.customEventEmitter.emit('EntityDeliveryDocument::Click', [ document ]);
							qrauth.open({
								title: this.getDocumentName(document),
								redirectUrl: `/crm/deal/details/${this.props.entityId}/`,
								hintText: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_DETAIL_HINT_MSGVER_1'),
								analyticsSection: this.props.analytics?.analyticsSection || '',
							});
						},
					},
					Text({
						style: styles.documentNameText,
						text: this.getDocumentName(document),
					}),
					Text({
						style: styles.documentNameText,
						text: this.getDocumentSumText(document),
					}),
				),
				View(
					{
						style: styles.documentDataBlock,
					},
					this.renderBadge(document),
					this.renderDots(document),
				),
			);
		}

		renderRealizationDocument(document)
		{
			return View(
				{
					testId: 'documentListRealizationItem',
					style: styles.documentItem,
				},
				View(
					{
						style: styles.documentNameBlock,
						onClick: () => {
							this.customEventEmitter.emit('EntityRealizationDocument::Click', [{
								id: document.ID,
								title: this.getDocumentName(document, false),
							}]);
						},
					},
					Text({
						style: styles.documentNameText,
						text: this.getDocumentName(document),
					}),
					Text({
						style: styles.documentNameText,
						text: this.getDocumentSumText(document),
					}),
				),
				View(
					{
						style: styles.documentDataBlock,
					},
					this.renderBadge(document),
					this.renderDots(document),
				),
			);
		}

		openDocumentMenu(document)
		{
			const menu = new ContextMenu({
				params: {
					title: this.getDocumentName(document),
				},
				actions: this.getDocumentActions(document),
			});
			this.documentMenu = menu;

			menu.show();
		}

		openChangeStatusMenu(document)
		{
			let actions = [];
			let title = '';

			if (document.TYPE === 'PAYMENT' || document.TYPE === 'TERMINAL_PAYMENT')
			{
				title = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_MENU_CHANGE_STATUS');
				actions = [
					{
						id: 'payment-paid',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_PAID'),
						data: {
							// svgIcon: svgIcons.menuSendLink,
						},
						onClickCallback: () => this.setPaymentStatus(document, true),
						isSelected: document.PAID === 'Y',
						showSelectedImage: true,
					},
					{
						id: 'payment-not-paid',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_STAGE_NOT_PAID'),
						data: {
							// svgIcon: svgIcons.menuSendLink,
						},
						onClickCallback: () => this.setPaymentStatus(document, false),
						isSelected: document.PAID === 'N',
						showSelectedImage: true,
					},
				];
			}

			if (document.TYPE === 'SHIPMENT')
			{
				title = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_MENU_CHANGE_STATUS');
				actions = [
					{
						id: 'shipping-done',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_DONE'),
						data: {
							// svgIcon: svgIcons.menuSendLink,
						},
						onClickCallback: () => this.setShipmentStatus(document, true),
						isSelected: document.DEDUCTED === 'Y',
						showSelectedImage: true,
					},
					{
						id: 'shipping-waiting',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_WAITING'),
						data: {
							// svgIcon: svgIcons.menuSendLink,
						},
						onClickCallback: () => this.setShipmentStatus(document, false),
						isSelected: document.DEDUCTED === 'N',
						showSelectedImage: true,
					},
				];
			}

			const menu = new ContextMenu({
				params: {
					title,
				},
				actions,
			});

			menu.show();
		}

		setPaymentStatus(document, isPaid)
		{
			// Setting same status
			if (
				(document.PAID === 'Y' && isPaid === true)
				|| (document.PAID === 'N' && isPaid === false)
			)
			{
				return;
			}
			const strPaid = isPaid ? 'Y' : 'N';
			const stage = isPaid ? 'PAID' : 'CANCEL';

			// Positive approach - render success first, then do actual query
			const documents = this.state.documents;
			const documentIndex = documents.findIndex((doc) => doc.ID === document.ID);
			const previousPaidStatus = documents[documentIndex].PAID;
			const previousPaymentStage = documents[documentIndex].STAGE;
			const previousTotalAmount = this.state.totalAmount;

			let newTotalAmount;
			if (isPaid)
			{
				newTotalAmount = previousTotalAmount - parseFloat(document.SUM);
			}
			else
			{
				newTotalAmount = previousTotalAmount + parseFloat(document.SUM);
			}

			documents[documentIndex].PAID = strPaid;
			documents[documentIndex].STAGE = stage;
			this.setState({
				documents,
				totalAmount: newTotalAmount,
			});

			BX.ajax.runAction('crmmobile.Document.Payment.setPaid', {
				data: {
					documentId: document.ID,
					value: strPaid,
				},
			})
				.then(() => {
					const params = {
						entityTypeId: this.props.entityTypeId,
						entityId: this.props.entityId,
						uid: this.uid,
					};
					this.customEventEmitter.emit('DetailCard::onUpdate', params);
				})
				.catch((response) => {
					documents[documentIndex].PAID = previousPaidStatus;
					documents[documentIndex].STAGE = previousPaymentStage;
					this.setState(
						{
							documents,
							totalAmount: previousTotalAmount,
						},
						() => handleErrors(response),
					);
				});
		}

		setShipmentStatus(document, isShipped)
		{
			return new Promise((resolve) => {
				// Setting same status
				if (
					(document.DEDUCTED === 'Y' && isShipped === true)
					|| (document.DEDUCTED === 'N' && isShipped === false)
				)
				{
					return;
				}
				const strShipped = isShipped ? 'Y' : 'N';

				// Positive approach - render success first, then do actual query
				const documents = this.state.documents;
				const previousDeductedStatus = document.DEDUCTED;
				document.DEDUCTED = strShipped;
				this.setState({ documents });

				let actionName = 'crmmobile.Document.Shipment.setShipped';
				if (this.isUsedInventoryManagement)
				{
					actionName = 'crmmobile.Document.Realization.setShipped';
				}

				BX.ajax.runAction(actionName, {
					data: {
						documentId: document.ID,
						value: strShipped,
					},
				})
					.then(() => {
						const params = {
							entityTypeId: this.props.entityTypeId,
							entityId: this.props.entityId,
							uid: this.uid,
						};
						if (this.isUsedInventoryManagement)
						{
							if (strShipped === 'Y')
							{
								this.customEventEmitter.emit('Catalog.StoreDocument::onConduct', ['W']);
							}
							else
							{
								this.customEventEmitter.emit('Catalog.StoreDocument::onCancel', ['W']);
							}
						}
						this.customEventEmitter.emit('DetailCard::onUpdate', params);
						resolve();
					})
					.catch((response) => {
						document.DEDUCTED = previousDeductedStatus;
						this.setState(
							{ documents },
							() => handleErrors(response),
						);
						resolve();
					});
			});
		}

		deleteDocument(document)
		{
			const actionsMap = {
				PAYMENT: 'crmmobile.Document.Payment.delete',
				TERMINAL_PAYMENT: 'crmmobile.Document.Payment.delete',
				SHIPMENT: 'crmmobile.Document.Shipment.delete',
				SHIPMENT_DOCUMENT: 'crmmobile.Document.Realization.delete',
			};
			const action = actionsMap.hasOwnProperty(document.TYPE) ? actionsMap[document.TYPE] : null
			if (!action)
			{
				return;
			}

			const data = {
				documentId: document.ID,
			};

			if (document.TYPE === 'SHIPMENT_DOCUMENT')
			{
				data.value = 'N';
			}

			// Positive approach - render success first, then do actual query
			const documents = this.state.documents;
			const filteredDocuments = documents.filter((element) => {
				return !(element.ID === document.ID && element.TYPE === document.TYPE);
			});
			this.setState({ documents: filteredDocuments });

			BX.ajax.runAction(action, {
				data,
			})
				.then(() => {
					const params = {
						entityTypeId: this.props.entityTypeId,
						entityId: this.props.entityId,
						uid: this.uid,
					};
					this.customEventEmitter.emit('DetailCard::onUpdate', params);
					this.updateDocuments();
				})
				.catch((response) => {
					this.setState(
						{ documents },
						() => handleErrors(response),
					);
				});
		}

		getDocumentActions(document)
		{
			const actions = [];
			if (
				document.TYPE === 'PAYMENT'
				|| document.TYPE === 'TERMINAL_PAYMENT'
			)
			{
				if (document.TYPE === 'PAYMENT')
				{
					actions.push({
						id: 'resend-payment-link',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_MENU_SEND_LINK'),
						data: {
							svgIcon: svgIcons.menuSendLink,
						},
						onClickCallback: () => {
							this.documentMenu.close(() => this.onClickSendMessageButton(document));
						},
					});
				}

				actions.push({
					id: 'change-payment-status',
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_MENU_CHANGE_STATUS'),
					data: {
						svgIcon: svgIcons.menuChangeStatus,
					},
					onClickCallback: () => {
						this.documentMenu.close(() => this.openChangeStatusMenu(document));
					},
				});

				if (
					document.TYPE === 'TERMINAL_PAYMENT'
					&& document.PAID === 'N'
				)
				{
					actions.push({
						id: 'open-terminal-payment-pay',
						title: Loc.getMessage(
							'MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_MENU_TERMINAL_OPEN_PAYMENT_PAY'
						),
						data: {
							svgIcon: svgIcons.terminal,
						},
						onClickCallback: () => {
							this.documentMenu.close(() => {
								PaymentPayOpener.open({
									id: document.ID,
									uid: this.uid,
									isStatusVisible: true,
								});
							});
						},
					});
				}

				actions.push({
					id: 'delete-payment',
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_MENU_DELETE'),
					data: {
						svgIcon: svgIcons.menuDelete,
					},
					isDisabled: document.PAID === 'Y',
					onClickCallback: () => this.documentMenu.close(() => {
						confirmDestructiveAction({
							title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_TITLE'),
							description: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_PAYMENT'),
							onDestruct: () => this.deleteDocument(document),
						});
					}),
					onDisableClick: () => {
						Notify.showUniqueMessage(
							Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_PAYMENT'),
							Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_TITLE_PAYMENT'),
							{ time: 3 },
						);
					},
				});
			}

			if (document.TYPE === 'SHIPMENT')
			{
				actions.push({
					id: 'change-shipment-status',
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_MENU_CHANGE_STATUS'),
					data: {
						svgIcon: svgIcons.menuChangeStatus,
					},
					onClickCallback: () => {
						this.documentMenu.close(() => this.openChangeStatusMenu(document));
					},
				});

				actions.push({
					id: 'delete-shipment',
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_MENU_DELETE'),
					data: {
						svgIcon: svgIcons.menuDelete,
					},
					isDisabled: document.DEDUCTED === 'Y',
					onClickCallback: () => this.documentMenu.close(() => {
						confirmDestructiveAction({
							title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_TITLE'),
							description: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_SHIPMENT'),
							onDestruct: () => this.deleteDocument(document),
						});
					}),
					onDisableClick: () => {
						Notify.showUniqueMessage(
							Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_SHIPMENT'),
							Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_TITLE_SHIPMENT'),
							{ time: 3 },
						);
					},
				});
			}

			if (document.TYPE === 'SHIPMENT_DOCUMENT')
			{
				if (document.DEDUCTED === 'Y' && !this.isOnecMode)
				{
					actions.push({
						id: 'cancel-deduct-realization',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_MENU_CANCEL_DEDUCT'),
						data: {
							svgIcon: svgIcons.menuArrow,
						},
						isDisabled: !this.props.salesOrderRights.conduct,
						onClickCallback: () => this.setShipmentStatus(document, false),
						onDisableClick: () => {
							Notify.showUniqueMessage(
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_CANCEL_DEDUCT_WARNING_REALIZATION'),
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_CANCEL_DEDUCT_WARNING_TITLE_REALIZATION'),
								{ time: 3 },
							);
						},
						showActionLoader: true,
					});
				}
				else if (document.DEDUCTED !== 'Y')
				{
					actions.push({
						id: 'deduct-realization',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_MENU_DEDUCT'),
						data: {
							svgIcon: svgIcons.menuArrow,
						},
						isDisabled: !this.props.salesOrderRights.conduct,
						onClickCallback: () => this.setShipmentStatus(document, true),
						onDisableClick: () => {
							Notify.showUniqueMessage(
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DEDUCT_WARNING_REALIZATION'),
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DEDUCT_WARNING_TITLE_REALIZATION'),
								{ time: 3 },
							);
						},
						showActionLoader: true,
					});
				}
				if (!(this.isOnecMode && document.DEDUCTED === 'Y'))
				{
					actions.push({
						id: 'delete-realization',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_MENU_DELETE'),
						data: {
							svgIcon: svgIcons.menuDelete,
						},
						isDisabled: document.DEDUCTED === 'Y' || !this.props.salesOrderRights.delete,
						onClickCallback: () => this.documentMenu.close(() => {
							confirmDestructiveAction({
								title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_TITLE'),
								description: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_REALIZATION'),
								onDestruct: () => this.deleteDocument(document),
							});
						}),
						onDisableClick: () => {
							Notify.showUniqueMessage(
								this.props.salesOrderRights.delete
									? Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_REALIZATION')
									: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_ACCESS_DENIED_REALIZATION'),
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_TITLE_REALIZATION'),
								{ time: 3 },
							);
						},
					});
				}

				if (this.isOnecMode && document.DEDUCTED === 'Y')
				{
					actions.push({
						id: 'open-realization',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_MENU_OPEN'),
						onClickCallback: () => this.documentMenu.close(() => {
							this.customEventEmitter.emit('EntityRealizationDocument::Click', [{
								id: document.ID,
								title: this.getDocumentName(document, false),
							}]);
						}),
					});
				}
			}

			return actions;
		}

		onClickSendMessageButton(document)
		{
			if (!Feature.isReceivePaymentSupported())
			{
				Feature.showDefaultUnsupportedWidget();

				return;
			}

			if (this.resendParams.entityHasContact && !this.resendParams.contactHasPhone)
			{
				const multiFieldDrawer = new MultiFieldDrawer({
					entityTypeId: TypeId.Contact,
					entityId: this.resendParams.contactId,
					fields: [MultiFieldType.PHONE],
					onSuccess: () => this.openSendMessageStep(document),
					warningBlock: {
						description: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_PHONE_DRAWER_WARNING_TEXT'),
					},
				});

				multiFieldDrawer.show();
			}
			else
			{
				this.openSendMessageStep(document);
			}
		}

		openSendMessageStep(document)
		{
			const backdropParams = {
				swipeAllowed: false,
				forceDismissOnSwipeDown: false,
				horizontalSwipeAllowed: false,
				bounceEnable: true,
				showOnTop: true,
				topPosition: 60,
				navigationBarColor: AppTheme.colors.bgSecondary,
			};

			const componentParams = {
				entityHasContact: this.resendParams.entityHasContact,
				entityId: this.props.entityId,
				entityTypeId: this.props.entityTypeId,
				mode: 'payment',
				uid: this.uid,
				resendMessageMode: true,
				paymentId: document.ID,
			};

			ComponentHelper.openLayout({
				name: 'crm:salescenter.receive.payment',
				object: 'layout',
				widgetParams: {
					objectName: 'layout',
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_RESEND_LINK'),
					modal: true,
					backgroundColor: AppTheme.colors.bgSecondary,
					backdrop: backdropParams,
				},
				componentParams,
			});
		}

		renderAddDocumentButton()
		{
			return View(
				{
					style: styles.addDocumentButton,
					onClick: () => {
						this.getCreationDocumentContextMenu().show();
					},
				},
				Text({
					style: styles.addDocumentButtonText,
					text: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_ADD_MSGVER_1'),
				}),
			);
		}

		getCreationDocumentContextMenu()
		{
			const orderIds = this.props.orderList.map((order) => parseInt(order.ORDER_ID));
			const latestOrderId = orderIds.length > 0 ? Math.max(...orderIds) : 0;
			const actions = [
				{
					id: 'payment',
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_CREATION_MENU_PAYMENT'),
					onClickCallback: () => {
						menu.close(() => {
							this.customEventEmitter.emit('OpportunityButton::Click');
						});
					},
				},
			];

			if (this.props.isTerminalAvailable)
			{
				actions.push(
					{
						id: 'terminal_payment',
						title: Loc.getMessage(
							'MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_CREATION_MENU_TERMINAL_PAYMENT'
						),
						onClickCallback: () => {
							menu.close(() => {
								this.customEventEmitter.emit('TerminalCreatePayment::Click');
							});
						},
					},
				);
			}

			if (this.canAddRealization())
			{
				actions.push(
					{
						id: 'realization',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_CREATION_MENU_REALIZATION'),
						onClickCallback: () => {
							menu.close(() => {
								if (this.restrictions.realization.isRestricted)
								{
									PlanRestriction.open({
										title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_1C_RESTRICTION_TITLE'),
									});

									return;
								}

								this.customEventEmitter.emit('EntityRealizationDocument::Click', [{
									uid: this.uid,
									ownerId: parseInt(this.props.entityId),
									ownerTypeId: parseInt(this.props.entityTypeId),
									orderId: latestOrderId,
								}]);
							});
						},
					},
				);
			}
			actions.push(
				{
					id: 'delivery',
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_CREATION_MENU_DELIVERY'),
					data: {
						svgIconAfter: {
							type: ImageAfterTypes.WEB,
						},
					},
					onClickCallback: () => {
						menu.close(() => {
							qrauth.open({
								redirectUrl: `/crm/deal/details/${this.props.entityId}/`,
								analyticsSection: this.props.analytics?.analyticsSection || '',
							});
						});
					},
				},
			);

			const menu = new ContextMenu({
				params: {
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_ADD_MSGVER_1'),
				},
				actions,
			});

			return menu;
		}

		renderSeparator()
		{
			return View(
				{
					style: styles.separator,
				},
			);
		}

		renderTotalSum()
		{
			const money = Money.create({
				amount: this.state.totalAmount,
				currency: this.currencyId,
			});

			return View(
				{
					style: styles.totalContainer,
				},
				Text({
					style: styles.totalText,
					text: `${Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_TOTAL_SUM')}: `,
				}),
				Text({
					style: styles.totalSum,
					text: money.formatted,
				}),
			);
		}

		renderBadge(document)
		{
			let badgeText = '';
			let bagdeColor = '';
			let bagdeTextColor = '';

			if (
				document.TYPE === 'PAYMENT'
				|| document.TYPE === 'TERMINAL_PAYMENT'
			)
			{
				if (document.PAID === 'Y')
				{
					bagdeColor = AppTheme.colors.accentSoftGreen2;
					bagdeTextColor = AppTheme.colors.accentSoftElementGreen1;
				}
				else if (document.STAGE === 'VIEWED_NO_PAID')
				{
					bagdeColor = AppTheme.colors.accentSoftBlue2;
					bagdeTextColor = AppTheme.colors.accentSoftElementBlue1;
				}
				else
				{
					bagdeColor = AppTheme.colors.base6;
					bagdeTextColor = AppTheme.colors.base3;
				}
				badgeText = Loc.getMessage(`MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_STAGE_${document.STAGE}`);
			}

			if (document.TYPE === 'SHIPMENT')
			{
				if (document.DEDUCTED === 'Y')
				{
					bagdeColor = AppTheme.colors.accentSoftGreen2;
					bagdeTextColor = AppTheme.colors.accentSoftElementGreen1;
					badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_DONE');
				}
				else
				{
					bagdeColor = AppTheme.colors.base6;
					bagdeTextColor = AppTheme.colors.base3;
					badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_WAITING');
				}
			}

			if (document.TYPE === 'SHIPMENT_DOCUMENT')
			{
				if (document.DEDUCTED === 'Y')
				{
					bagdeColor = AppTheme.colors.accentSoftGreen2;
					bagdeTextColor = AppTheme.colors.accentSoftElementGreen1;
					badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_DEDUCTED');
				}
				else
				{
					bagdeColor = AppTheme.colors.base6;
					bagdeTextColor = AppTheme.colors.base3;
					badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_NOT_DEDUCTED');
				}
			}

			return View(
				{
					style: styles.badge(bagdeColor),
				},
				Text({
					style: styles.badgeText(bagdeTextColor),
					text: badgeText.toUpperCase(),
					ellipsize: 'end',
				}),
			);
		}

		renderDots(document)
		{
			return View(
				{
					style: styles.dotsContainer,
					onClick: () => this.openDocumentMenu(document),
				},
				Image({
					style: styles.dotsButton,
					svg: {
						content: svgIcons.documentMenuDots,
					},
				}),
			);
		}

		render()
		{
			return View(
				{
					onClick: () => Keyboard.dismiss(),
				},
				this.renderContent(),
			);
		}
	}

	const styles = {
		documentsContainer: {
			marginBottom: 10,
			marginHorizontal: 6,
			paddingHorizontal: 16,
			backgroundColor: AppTheme.colors.bgContentSecondary,
			borderWidth: 1,
			borderRadius: 6,
			borderColor: AppTheme.colors.base6,
		},
		documentsTitle: {
			fontSize: 10,
			color: AppTheme.colors.base4,
			marginTop: 16,
			marginBottom: 14,
		},
		documentsEmptyStateDescription: {
			fontSize: 13,
			color: AppTheme.colors.base4,
			marginBottom: 10,
		},
		documentItem: {
			flexDirection: 'row',
			justifyContent: 'space-between',
			alignItems: 'flex-start',
			marginBottom: 14,
		},
		documentDataBlock: {
			flexDirection: 'row',
			justifyContent: 'space-between',
		},
		documentNameBlock: {
			flexDirection: 'column',
			flexShrink: 2,
		},
		documentNameText: {
			fontSize: 13,
			color: AppTheme.colors.accentMainLinks,
			flexShrink: 2,
		},
		badge: (color) => ({
			backgroundColor: color,
			height: 18,
			borderRadius: 10.5,
			paddingLeft: 8,
			paddingRight: 8,
			marginLeft: 4,
			marginRight: 4,
			fontWeight: 700,
			justifyContent: 'center',
		}),
		badgeText: (color) => ({
			color,
			fontSize: 8,
			fontWeight: '700',
			textAlign: 'center',
		}),
		dotsContainer: {
			width: 28,
			height: 19,
			alignItems: 'center',
			justifyContent: 'center',
		},
		dotsButton: {
			width: 16,
			height: 5,
		},
		addDocumentButton: {
			flexDirection: 'row',
			alignItems: 'center',
			justifyContent: 'flex-start',
			height: 16,
			marginTop: 5,
			marginBottom: 14,
		},
		addDocumentButtonText: {
			fontSize: 13,
			color: AppTheme.colors.accentMainLinks,
			borderBottomWidth: 1,
			borderBottomColor: AppTheme.colors.accentMainLinks,
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 3,
		},
		plusIcon: {
			width: 9,
			height: 9,
			margin: 6,
		},
		separator: {
			borderBottomColor: AppTheme.colors.bgSeparatorPrimary,
			borderBottomWidth: 1,
		},
		totalContainer: {
			flexDirection: 'row',
			justifyContent: 'space-between',
			flexWrap: 'wrap',
			marginTop: 9,
			marginBottom: 12,
		},
		totalText: {
			fontSize: 13,
			marginBottom: 4,
			color: AppTheme.colors.base3,
		},
		totalSum: {
			fontSize: 15,
			color: AppTheme.colors.base2,
			fontWeight: '700',
			marginBottom: 4,
		},
	};

	const svgIcons = {
		documentMenuDots: `<svg width="16" height="5" viewBox="0 0 16 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 4.63977C3.10457 4.63977 4 3.74434 4 2.63977C4 1.5352 3.10457 0.639771 2 0.639771C0.89543 0.639771 0 1.5352 0 2.63977C0 3.74434 0.89543 4.63977 2 4.63977Z" fill="${AppTheme.colors.base5}"/><path d="M8 4.63977C9.10457 4.63977 10 3.74434 10 2.63977C10 1.5352 9.10457 0.639771 8 0.639771C6.89543 0.639771 6 1.5352 6 2.63977C6 3.74434 6.89543 4.63977 8 4.63977Z" fill="${AppTheme.colors.base5}"/><path d="M16 2.63977C16 3.74434 15.1046 4.63977 14 4.63977C12.8954 4.63977 12 3.74434 12 2.63977C12 1.5352 12.8954 0.639771 14 0.639771C15.1046 0.639771 16 1.5352 16 2.63977Z" fill="${AppTheme.colors.base5}"/></svg>`,
		documentAddPlus: `<svg width="10" height="9" viewBox="0 0 10 9" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.83333 0.473104H4.16666V3.80644H0.833328V5.4731H4.16666V8.80644H5.83333V5.4731H9.16666V3.80644H5.83333V0.473104Z" fill="${AppTheme.colors.base4}"/></svg>`,
		menuChangeStatus: `<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.77628 5.88513L17.2918 14.6801C17.4719 14.864 17.5798 15.1708 17.5798 15.4991C17.5798 15.8274 17.4719 16.1343 17.2918 16.3182L8.77628 25.1131C8.57566 25.3247 8.3136 25.3492 8.09725 25.1766C7.88089 25.004 7.74651 24.6633 7.74906 24.2937V6.70459C7.74733 6.33552 7.88183 5.99573 8.09786 5.82339C8.3139 5.65104 8.5755 5.67485 8.77628 5.88513ZM20.7292 14.6801L15.5555 9.33658V6.70459C15.5537 6.33552 15.6882 5.99573 15.9043 5.82339C16.1203 5.65104 16.3819 5.67485 16.5827 5.88513L25.0982 14.6801C25.2783 14.864 25.3862 15.1708 25.3862 15.4991C25.3862 15.8274 25.2783 16.1343 25.0982 16.3182L16.5827 25.1131C16.3821 25.3247 16.12 25.3492 15.9037 25.1766C15.6873 25.004 15.5529 24.6633 15.5555 24.2937V21.6617L20.7292 16.3182C20.9093 16.1343 21.0172 15.8274 21.0172 15.4991C21.0172 15.1708 20.9093 14.864 20.7292 14.6801Z" fill="${AppTheme.colors.base3}"/></svg>`,
		menuDelete: `<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.6146 6.3716H12.8881V7.74245H8.93521C8.10678 7.74245 7.43521 8.41402 7.43521 9.24245V10.485H21.0675V9.24245C21.0675 8.41402 20.3959 7.74245 19.5675 7.74245H15.6146V6.3716ZM8.79843 11.8562H19.7042L18.6942 23.2859C18.6486 23.802 18.2163 24.1978 17.6981 24.1978H10.8045C10.2864 24.1978 9.85403 23.802 9.80842 23.2859L8.79843 11.8562Z" fill="${AppTheme.colors.base3}"/></svg>`,
		menuSendLink: `<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.7407 15.19V7.85563L22.6329 9.96403C20.9168 7.85131 18.3045 6.49731 15.3695 6.49731C10.1935 6.49731 5.99762 10.6932 5.99762 15.8692C5.99762 21.0452 10.1935 25.2404 15.3695 25.2404C17.5772 25.2404 19.6023 24.4718 21.2043 23.1949L18.8294 20.2809C17.8758 21.0298 16.676 21.4807 15.3695 21.4807C12.2704 21.4807 9.75796 18.9682 9.75796 15.8692C9.75796 12.7701 12.2704 10.257 15.3695 10.257C17.2657 10.257 18.9411 11.1996 19.9564 12.6399L17.4063 15.19H24.7407Z" fill="${AppTheme.colors.base3}"/></svg>`,
		menuArrow: `<svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.00043 7.10789L13.7161 11.8236L14.9375 13L13.7161 14.1771L9.00043 18.8928L10.6645 20.5568L18.2209 13.0004L10.6645 5.444L9.00043 7.10789Z" fill="${AppTheme.colors.base4}"/></svg>`,
		terminal: '<svg width="13" height="18" viewBox="0 0 13 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.3432 7.0343L4.93557 5.674L5.58742 5.03872L6.3432 5.76373L8.17271 4.00385L8.82456 4.63913L6.3432 7.0343Z" fill="#525C69"/><path fill-rule="evenodd" clip-rule="evenodd" d="M12.6472 1.72604C12.6472 1.02142 12.0752 0.442383 11.3636 0.442383H2.25949C1.55486 0.442383 0.97583 1.01445 0.97583 1.72604V8.46475C0.97583 8.53609 1.16081 8.82653 1.34251 9.11182C1.51789 9.38718 1.6902 9.65773 1.6902 9.72188V16.6971C1.6902 17.4018 2.26227 17.9808 2.97386 17.9808H10.6491C11.3537 17.9808 11.9327 17.4087 11.9327 16.6971L11.9329 9.78345C11.9329 9.71279 12.1182 9.42264 12.3001 9.13796C12.4753 8.86379 12.6472 8.59469 12.6472 8.53163L12.6472 1.72604ZM2.85945 1.95207H10.7636C10.938 1.95207 11.0776 2.09159 11.0776 2.266C11.0776 2.44041 10.938 2.57994 10.7636 2.57994H2.85945C2.68504 2.57994 2.54552 2.44041 2.54552 2.266C2.54552 2.09159 2.68504 1.95207 2.85945 1.95207ZM2.54566 3.66824C2.54566 3.49383 2.68519 3.3543 2.8596 3.3543H10.7638C10.9382 3.3543 11.0777 3.49383 11.0777 3.66824V7.45916C11.0777 7.63357 10.9382 7.7731 10.7638 7.7731H2.8596C2.68519 7.7731 2.54566 7.63357 2.54566 7.45916V3.66824ZM9.99916 11.1116H9.08597C8.76329 11.1116 8.50152 10.8498 8.50152 10.5271C8.50152 10.2045 8.76331 9.94269 9.08597 9.94269H9.99916C10.3218 9.94269 10.5836 10.2045 10.5836 10.5271C10.5836 10.8498 10.3218 11.1116 9.99916 11.1116ZM6.35857 11.1117H7.27176C7.59443 11.1117 7.85621 10.8499 7.85621 10.5273C7.85621 10.2046 7.59444 9.94281 7.27176 9.94281H6.35857C6.0359 9.94281 5.77412 10.2046 5.77412 10.5273C5.77412 10.8499 6.03589 11.1117 6.35857 11.1117ZM4.54436 11.1118H3.63116C3.30849 11.1118 3.04672 10.85 3.04672 10.5274C3.04672 10.2047 3.3085 9.94294 3.63116 9.94294H4.54436C4.86704 9.94294 5.1288 10.2047 5.1288 10.5274C5.1288 10.85 4.86702 11.1118 4.54436 11.1118ZM3.63116 13.2487H4.54436C4.86702 13.2487 5.1288 12.9869 5.1288 12.6643C5.1288 12.3416 4.86704 12.0798 4.54436 12.0798H3.63116C3.3085 12.0798 3.04672 12.3416 3.04672 12.6643C3.04672 12.9869 3.30849 13.2487 3.63116 13.2487ZM3.63116 15.3795H4.54436C4.86702 15.3795 5.1288 15.1177 5.1288 14.795C5.1288 14.4724 4.86704 14.2106 4.54436 14.2106H3.63116C3.3085 14.2106 3.04672 14.4724 3.04672 14.795C3.04672 15.1177 3.30849 15.3795 3.63116 15.3795ZM6.35857 15.3793H7.27176C7.59443 15.3793 7.85621 15.1176 7.85621 14.7949C7.85621 14.4722 7.59444 14.2105 7.27176 14.2105H6.35857C6.0359 14.2105 5.77412 14.4722 5.77412 14.7949C5.77412 15.1176 6.03589 15.3793 6.35857 15.3793ZM7.85621 12.6641C7.85621 12.9868 7.59443 13.2486 7.27176 13.2486H6.35857C6.03589 13.2486 5.77412 12.9868 5.77412 12.6641C5.77412 12.3415 6.0359 12.0797 6.35857 12.0797H7.27176C7.59444 12.0797 7.85621 12.3415 7.85621 12.6641ZM9.99916 15.3792H9.08597C8.76329 15.3792 8.50152 15.1174 8.50152 14.7948C8.50152 14.4721 8.76331 14.2103 9.08597 14.2103H9.99916C10.3218 14.2103 10.5836 14.4721 10.5836 14.7948C10.5836 15.1174 10.3218 15.3792 9.99916 15.3792ZM9.08597 13.2485H9.99916C10.3218 13.2485 10.5836 12.9867 10.5836 12.664C10.5836 12.3413 10.3218 12.0796 9.99916 12.0796H9.08597C8.76331 12.0796 8.50152 12.3413 8.50152 12.664C8.50152 12.9867 8.76329 13.2485 9.08597 13.2485Z" fill="#525C69"/></svg>',
	};

	module.exports = { DocumentList };
});
