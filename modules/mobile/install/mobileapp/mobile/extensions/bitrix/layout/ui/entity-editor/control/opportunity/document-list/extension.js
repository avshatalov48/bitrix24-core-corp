/**
 * @module layout/ui/entity-editor/control/opportunity/document-list
 */
jn.define('layout/ui/entity-editor/control/opportunity/document-list', (require, exports, module) => {

	const { Alert } = require('alert');
	const { Loc } = require('loc');
	const { EventEmitter } = require('event-emitter');
	const { handleErrors } = require('crm/error');
	const { Feature } = require('feature');

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
				.then(response => {
					this.setState({
						totalAmount: response.data.totalAmount,
					});
					this.currencyId = response.data.currencyId;
				})
			})
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
				.then(response => {
					this.setState({
						documents: response.data.documents,
						totalAmount: response.data.totalAmount,
					});
					this.currencyId = response.data.currencyId;
				})
			})
		}

		renderContent()
		{
			return View(
				{
					style: styles.documentsContainer,
					clickable: false,
				},
				this.renderTitle(),
				...this.state.documents.map(document => this.renderDocumentItem(document)),
				this.renderAddDocumentButton(),
				this.renderSeparator(),
				this.renderTotalSum()
			)
		}

		renderTitle()
		{
			return View(
				{},
				Text({
					style: styles.documentsTitle,
					text: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DEAL_TITLE')
						.toLocaleUpperCase(env.languageId),
				}),
			);
		}

		renderDocumentItem(document)
		{
			switch (document.TYPE)
			{
				case 'PAYMENT':
					return this.renderPaymentDocument(document);
				case 'SHIPMENT':
					return this.renderShipmentDocument(document);
				case 'SHIPMENT_DOCUMENT':
					return this.renderRealizationDocument(document);
			}

			return View();
		}

		getDocumentName(document)
		{
			if (document.TYPE === 'PAYMENT')
			{
				return Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_DATE', {
						'#DATE#': document.FORMATTED_DATE,
						'#ACCOUNT_NUMBER#': document.ACCOUNT_NUMBER,
					});
			}

			if (document.TYPE === 'SHIPMENT')
			{
				return Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_DATE', {
						'#DATE#': document.FORMATTED_DATE,
						'#ACCOUNT_NUMBER#': document.ACCOUNT_NUMBER,
					});
			}

			if (document.TYPE === 'SHIPMENT_DOCUMENT')
			{
				return Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_DATE', {
					'#DATE#': document.FORMATTED_DATE,
					'#ACCOUNT_NUMBER#': document.ACCOUNT_NUMBER,
				});
			}

			return '';
		}

		getDocumentSumText(document)
		{
			const money = Money.create({
				amount: document.SUM,
				currency: document.CURRENCY
			});

			let result = '(';

			if (document.TYPE === 'SHIPMENT_DOCUMENT')
			{
				return result + Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_SUM', {
					'#SUM#': money.formatted
				})
				+ ')';
			}

			if (document.DELIVERY_NAME)
			{
				result += document.DELIVERY_NAME + ', ';
			}

			return result + Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_AMOUNT', {
					'#SUM#': money.formatted
				})
				+ ')';
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
						onClick: () => this.customEventEmitter.emit('EntityPaymentDocument::Click', [ document ]),
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
			)
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
							qrauth.open({
								title: this.getDocumentName(document),
								redirectUrl: `/crm/deal/details/${this.props.entityId}/`,
								hintText: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_DETAIL_HINT_MSGVER_1'),
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
			)
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

			if (document.TYPE === 'PAYMENT')
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
					title: title,
				},
				actions: actions,
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
			const documentIndex = documents.findIndex(doc => doc.ID === document.ID);
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
				documents: documents,
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
				}
				this.customEventEmitter.emit('DetailCard::onUpdate', params);
			})
			.catch(response => {
				documents[documentIndex].PAID = previousPaidStatus;
				documents[documentIndex].STAGE = previousPaymentStage;
				this.setState(
					{
						documents: documents,
						totalAmount: previousTotalAmount,
					},
					() => handleErrors(response)
				);
			});
		}

		setShipmentStatus(document, isShipped)
		{
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
			const documentIndex = documents.findIndex(doc => doc.ID === document.ID);
			const previousDeductedStatus = documents[documentIndex].DEDUCTED;
			documents[documentIndex].DEDUCTED = strShipped;
			this.setState({ documents: documents });

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
				}
				this.customEventEmitter.emit('DetailCard::onUpdate', params);
			})
			.catch(response => {
				documents[documentIndex].DEDUCTED = previousDeductedStatus;
				this.setState(
					{ documents: documents },
					() => handleErrors(response)
				);
			});
		}

		deleteDocument(document)
		{
			const actionName = this.resolveRemoveDocumentActionName(document.TYPE);
			if (!actionName)
			{
				return;
			}

			const data = {
				documentId: document.ID,
			}

			if (document.TYPE === 'SHIPMENT_DOCUMENT')
			{
				data.value = 'N';
			}

			// Positive approach - render success first, then do actual query
			const documents = this.state.documents;
			const filteredDocuments = documents.filter(element => element.ID !== document.ID && element.TYPE === document.TYPE);
			this.setState({ documents: filteredDocuments });

			BX.ajax.runAction(actionName, {
				data: data
			})
			.then(() => {
				const params = {
					entityTypeId: this.props.entityTypeId,
					entityId: this.props.entityId,
					uid: this.uid,
				}
				this.customEventEmitter.emit('DetailCard::onUpdate', params);
			})
			.catch(response => {
				this.setState(
					{ documents: documents },
					() => handleErrors(response)
				);
			});
		}

		resolveRemoveDocumentActionName(documentType)
		{
			let action = '';

			if (documentType === 'PAYMENT')
			{
				action = 'crmmobile.Document.Payment.delete';
			}
			else if (documentType === 'SHIPMENT')
			{
				action = 'crmmobile.Document.Shipment.delete';
			}
			else if (documentType === 'SHIPMENT_DOCUMENT')
			{
				action = 'crmmobile.Document.Realization.delete';
			}

			return action;
		}

		getDocumentActions(document)
		{
			let actions = [];
			if (document.TYPE === 'PAYMENT')
			{
				actions = [
					{
						id: 'resend-payment-link',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_MENU_SEND_LINK'),
						data: {
							svgIcon: svgIcons.menuSendLink,
						},
						onClickCallback: () => {
							this.documentMenu.close(() => this.openSendMessageStep(document));
						},
					},
					{
						id: 'change-payment-status',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_MENU_CHANGE_STATUS'),
						data: {
							svgIcon: svgIcons.menuChangeStatus,
						},
						onClickCallback: () => {
							this.documentMenu.close(() => this.openChangeStatusMenu(document));
						},
					},
					{
						id: 'delete-payment',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_MENU_DELETE'),
						data: {
							svgIcon: svgIcons.menuDelete,
						},
						isDisabled: document.PAID === 'Y',
						onClickCallback: () => this.documentMenu.close(() => {
							Alert.confirm(
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_TITLE'),
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_PAYMENT'),
								[
									{
										text: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_PAYMENT_MENU_DELETE'),
										type: 'destructive',
										onPress: () => this.deleteDocument(document),
									},
									{
										type: 'cancel',
									},
								],
							);
						}),
						onDisableClick: () => {
							Notify.showUniqueMessage(
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_PAYMENT'),
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_TITLE_PAYMENT'),
								{ time: 3 },
							);
						},
					}
				];
			}

			if (document.TYPE === 'SHIPMENT')
			{
				actions = [
					{
						id: 'change-shipment-status',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_MENU_CHANGE_STATUS'),
						data: {
							svgIcon: svgIcons.menuChangeStatus,
						},
						onClickCallback: () => {
							this.documentMenu.close(() => this.openChangeStatusMenu(document));
						},
					},
					{
						id: 'delete-shipment',
						title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_MENU_DELETE'),
						data: {
							svgIcon: svgIcons.menuDelete,
						},
						isDisabled: document.DEDUCTED === 'Y',
						onClickCallback: () => this.documentMenu.close(() => {
							Alert.confirm(
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_TITLE'),
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_SHIPMENT'),
								[
									{
										text: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_MENU_DELETE'),
										type: 'destructive',
										onPress: () => this.deleteDocument(document),
									},
									{
										type: 'cancel',
									},
								],
							);
						}),
						onDisableClick: () => {
							Notify.showUniqueMessage(
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_SHIPMENT'),
								Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_TITLE_SHIPMENT'),
								{ time: 3 },
							);
						},
					},
				];
			}

			if (document.TYPE === 'SHIPMENT_DOCUMENT')
			{
				actions.push({
					id: 'delete-realization',
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_MENU_DELETE'),
					data: {
						svgIcon: svgIcons.menuDelete,
					},
					isDisabled: document.DEDUCTED === 'Y',
					onClickCallback: () => this.documentMenu.close(() => {
						Alert.confirm(
							Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_TITLE'),
							Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_CONFIRM_REALIZATION'),
							[
								{
									text: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_MENU_DELETE'),
									type: 'destructive',
									onPress: () => this.deleteDocument(document),
								},
								{
									type: 'cancel',
								},
							],
						);
					}),
					onDisableClick: () => {
						Notify.showUniqueMessage(
							Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_REALIZATION'),
							Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_DELETE_WARNING_TITLE_REALIZATION'),
							{ time: 3 },
						);
					},
				});
			}

			return actions;
		}

		openSendMessageStep(document)
		{
			if (!Feature.isReceivePaymentSupported())
			{
				Feature.showDefaultUnsupportedWidget();

				return;
			}

			const backdropParams = {
				swipeAllowed: false,
				forceDismissOnSwipeDown: false,
				horizontalSwipeAllowed: false,
				bounceEnable: true,
				showOnTop: true,
				topPosition: 60,
				navigationBarColor: '#eef2f4',
			}

			const componentParams = {
				entityId: this.props.entityId,
				entityTypeId: this.props.entityTypeId,
				mode: document.TYPE.toLowerCase(),
				uid: this.uid,
				resendMessageMode: true,
				document: document,
			}

			ComponentHelper.openLayout({
				name: 'crm:salescenter.receive.payment',
				object: 'layout',
				widgetParams: {
					objectName: 'layout',
					title: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_RESEND_LINK'),
					modal: true,
					backgroundColor: '#eef2f4',
					backdrop: backdropParams,
				},
				componentParams: componentParams,
			});
		}

		renderAddDocumentButton()
		{
			return View(
				{
					style: styles.addDocumentButton,
					onClick: () => this.customEventEmitter.emit('OpportunityButton::Click'),
				},
				Image({
					style: styles.plusIcon,
					svg: {
						content: svgIcons.documentAddPlus,
					},
				}),
				Text({
					style: {
						fontSize: 13,
						color: '#525C69',
					},
					text: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_ADD'),
				})
			)
		}

		renderSeparator()
		{
			return View(
				{
					style: styles.separator,
				},
			)
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
					text: Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_TOTAL_SUM') + ': ',
				}),
				Text({
					style: styles.totalSum,
					text: money.formatted,
				})
			);
		}

		renderBadge(document)
		{
			let badgeText = '';
			let bagdeColor = '';
			let bagdeTextColor = '';

			if (document.TYPE === 'PAYMENT')
			{
				if (document.PAID === 'Y')
				{
					bagdeColor = '#eaf6c3';
					bagdeTextColor = '#688800';
				}
				else if (document.STAGE === 'VIEWED_NO_PAID')
				{
					bagdeColor = '#e5f9ff';
					bagdeTextColor = '#008dba';
				}
				else
				{
					bagdeColor = '#dfe0e3';
					bagdeTextColor = '#828b95';
				}
				badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_STAGE_' + document.STAGE);
			}

			if (document.TYPE === 'SHIPMENT')
			{
				if (document.DEDUCTED === 'Y')
				{
					bagdeColor = '#eaf6c3';
					bagdeTextColor = '#688800';
					badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_DONE');
				}
				else
				{
					bagdeColor = '#dfe0e3';
					bagdeTextColor = '#828b95';
					badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_SHIPMENT_WAITING');
				}
			}

			if (document.TYPE === 'SHIPMENT_DOCUMENT')
			{
				if (document.DEDUCTED === 'Y')
				{
					bagdeColor = '#eaf6c3';
					bagdeTextColor = '#688800';
					badgeText = Loc.getMessage('MOBILE_LAYOUT_UI_FIELDS_OPPORTUNITY_DOCUMENTS_REALIZATION_DEDUCTED');
				}
				else
				{
					bagdeColor = '#dfe0e3';
					bagdeTextColor = '#828b95';
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
				})
			)
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
			backgroundColor: '#FCFCFD',
			borderWidth: 1,
			borderRadius: 6,
			borderColor: '#dfe0e3',
		},
		documentsTitle: {
			fontSize: 10,
			color: '#A8ADB4',
			marginTop: 16,
			marginBottom: 14,
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
			color: '#2066B0',
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
			color: color,
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
		plusIcon: {
			width: 9,
			height: 9,
			margin: 6,
		},
		separator: {
			borderBottomColor: '#DFE0E3',
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
			color: '#6A737F',
		},
		totalSum: {
			fontSize: 15,
			color: '#525C69',
			fontWeight: '700',
			marginBottom: 4,
		},
	};

	const svgIcons = {
		documentMenuDots: `<svg width="16" height="5" viewBox="0 0 16 5" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 4.63977C3.10457 4.63977 4 3.74434 4 2.63977C4 1.5352 3.10457 0.639771 2 0.639771C0.89543 0.639771 0 1.5352 0 2.63977C0 3.74434 0.89543 4.63977 2 4.63977Z" fill="#C9CCD0"/><path d="M8 4.63977C9.10457 4.63977 10 3.74434 10 2.63977C10 1.5352 9.10457 0.639771 8 0.639771C6.89543 0.639771 6 1.5352 6 2.63977C6 3.74434 6.89543 4.63977 8 4.63977Z" fill="#C9CCD0"/><path d="M16 2.63977C16 3.74434 15.1046 4.63977 14 4.63977C12.8954 4.63977 12 3.74434 12 2.63977C12 1.5352 12.8954 0.639771 14 0.639771C15.1046 0.639771 16 1.5352 16 2.63977Z" fill="#C9CCD0"/></svg>`,
		documentAddPlus: `<svg width="10" height="9" viewBox="0 0 10 9" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.83333 0.473104H4.16666V3.80644H0.833328V5.4731H4.16666V8.80644H5.83333V5.4731H9.16666V3.80644H5.83333V0.473104Z" fill="#A8ADB4"/></svg>`,
		menuChangeStatus: `<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.77628 5.88513L17.2918 14.6801C17.4719 14.864 17.5798 15.1708 17.5798 15.4991C17.5798 15.8274 17.4719 16.1343 17.2918 16.3182L8.77628 25.1131C8.57566 25.3247 8.3136 25.3492 8.09725 25.1766C7.88089 25.004 7.74651 24.6633 7.74906 24.2937V6.70459C7.74733 6.33552 7.88183 5.99573 8.09786 5.82339C8.3139 5.65104 8.5755 5.67485 8.77628 5.88513ZM20.7292 14.6801L15.5555 9.33658V6.70459C15.5537 6.33552 15.6882 5.99573 15.9043 5.82339C16.1203 5.65104 16.3819 5.67485 16.5827 5.88513L25.0982 14.6801C25.2783 14.864 25.3862 15.1708 25.3862 15.4991C25.3862 15.8274 25.2783 16.1343 25.0982 16.3182L16.5827 25.1131C16.3821 25.3247 16.12 25.3492 15.9037 25.1766C15.6873 25.004 15.5529 24.6633 15.5555 24.2937V21.6617L20.7292 16.3182C20.9093 16.1343 21.0172 15.8274 21.0172 15.4991C21.0172 15.1708 20.9093 14.864 20.7292 14.6801Z" fill="#828B95"/></svg>`,
		menuDelete: `<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.6146 6.3716H12.8881V7.74245H8.93521C8.10678 7.74245 7.43521 8.41402 7.43521 9.24245V10.485H21.0675V9.24245C21.0675 8.41402 20.3959 7.74245 19.5675 7.74245H15.6146V6.3716ZM8.79843 11.8562H19.7042L18.6942 23.2859C18.6486 23.802 18.2163 24.1978 17.6981 24.1978H10.8045C10.2864 24.1978 9.85403 23.802 9.80842 23.2859L8.79843 11.8562Z" fill="#828B95"/></svg>`,
		menuSendLink: `<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M24.7407 15.19V7.85563L22.6329 9.96403C20.9168 7.85131 18.3045 6.49731 15.3695 6.49731C10.1935 6.49731 5.99762 10.6932 5.99762 15.8692C5.99762 21.0452 10.1935 25.2404 15.3695 25.2404C17.5772 25.2404 19.6023 24.4718 21.2043 23.1949L18.8294 20.2809C17.8758 21.0298 16.676 21.4807 15.3695 21.4807C12.2704 21.4807 9.75796 18.9682 9.75796 15.8692C9.75796 12.7701 12.2704 10.257 15.3695 10.257C17.2657 10.257 18.9411 11.1996 19.9564 12.6399L17.4063 15.19H24.7407Z" fill="#828B95"/></svg>`,
		menuArrow: `<svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.00043 7.10789L13.7161 11.8236L14.9375 13L13.7161 14.1771L9.00043 18.8928L10.6645 20.5568L18.2209 13.0004L10.6645 5.444L9.00043 7.10789Z" fill="#A8ADB4"/></svg>`,
	};

	module.exports = {
		DocumentList
	};

});
