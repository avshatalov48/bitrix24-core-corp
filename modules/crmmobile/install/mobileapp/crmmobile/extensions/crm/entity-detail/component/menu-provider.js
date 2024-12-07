/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-detail/component/menu-provider
 */
jn.define('crm/entity-detail/component/menu-provider', (require, exports, module) => {
	const { confirmDestructiveAction } = require('alert');
	const { AnalyticsEvent } = require('analytics');
	const { AnalyticsLabel } = require('analytics-label');
	const { NotifyManager } = require('notify-manager');
	const { TypeId } = require('crm/type');
	const { getEntityMessage } = require('crm/loc');
	const { Loc } = require('loc');
	const { getActionToCopyEntity, getActionToChangePipeline, getActionToShare } = require('crm/entity-actions');
	const { getSmartActivityMenuItem } = require('crm/entity-detail/component/smart-activity-menu-item');
	const { CrmDocumentList } = require('crm/document/list');
	const { getPaymentAutomationMenuItem } = require('crm/entity-detail/component/payment-automation-menu-item');
	const { getOpenLinesMenuItems } = require('crm/entity-detail/component/open-lines-menu-items');
	const { EntityChatOpener } = require('crm/entity-chat-opener');
	const { Icon } = require('ui-system/blocks/icon');

	/**
	 * @param {DetailCardComponent} detailCard
	 * @returns {*[]}
	 */
	const menuProvider = (detailCard) => {
		let result = [];

		const {
			entityTypeId,
			entityId,
			categoryId,
			qrUrl,
			permissions = {},
			todoNotificationParams,
			isAutomationAvailable,
			isLinkWithProductsEnabled,
			isDocumentGenerationEnabled,
			isCategoriesEnabled,
			isClientEnabled,
			isChatSupported,
		} = detailCard.getComponentParams();
		const { entityModel } = detailCard;

		if (!entityModel)
		{
			return [];
		}

		let showTopSeparatorForSwitchSection = true;
		let showTopSeparatorForSettingsMenu = true;

		if (!detailCard.isNewEntity())
		{
			const canAdd = BX.prop.getBoolean(permissions, 'add', false);
			const canUpdate = BX.prop.getBoolean(permissions, 'update', false);
			const canDelete = BX.prop.getBoolean(permissions, 'delete', false);
			const canExclude = BX.prop.getBoolean(permissions, 'exclude', false);

			if (isDocumentGenerationEnabled)
			{
				result.push(
					{
						id: 'documents',
						sectionCode: 'top',
						sort: 100,
						onItemSelected: () => showEntityDocuments(detailCard),
						title: Loc.getMessage('M_CRM_ENTITY_ACTION_DOCUMENTS'),
						icon: Icon.FILE,
						disable: false, // todo check rights to documents
					},
				);
			}

			const hasOpenLinesPermission = BX.prop.getBoolean(permissions, 'openLinesAccess', null);
			const isClientRelatedEntity = entityTypeId === TypeId.Contact
				|| entityTypeId === TypeId.Company
				|| isClientEnabled;

			if (hasOpenLinesPermission && isClientRelatedEntity)
			{
				const openLineItems = getOpenLinesMenuItems(entityTypeId, layout);

				openLineItems.forEach((openLineItem) => {
					result.push({
						...openLineItem,
						showTopSeparator: showTopSeparatorForSettingsMenu,
					});
				});

				showTopSeparatorForSettingsMenu = false;
			}

			if (isCategoriesEnabled)
			{
				result.push(getChangeEntityCategory(detailCard, canUpdate));
			}

			if (entityTypeId === TypeId.Deal)
			{
				result.push({
					...getPaymentAutomationMenuItem(entityId, entityTypeId, categoryId, isAutomationAvailable),
					showTopSeparator: showTopSeparatorForSettingsMenu,
				});

				showTopSeparatorForSettingsMenu = false;
			}

			if (entityTypeId === TypeId.Deal || entityTypeId === TypeId.Lead)
			{
				result.push({
					id: 'excludeItem',
					sectionCode: 'top',
					sort: 500,
					onItemSelected: () => excludeEntity(detailCard),
					title: Loc.getMessage('M_CRM_ENTITY_ACTION_EXCLUDE'),
					icon: Icon.CIRCLE_CROSS,
					disable: !canExclude,
				});
			}

			if (isLinkWithProductsEnabled)
			{
				result.push({
					id: 'disableManualOpportunity',
					sectionCode: 'action',
					sort: 600,
					onItemSelected: () => detailCard.customEventEmitter.emit('EntityDetails::onChangeManualOpportunity'),
					title: Loc.getMessage('M_CRM_CHANGE_MANUAL_OPPORTUNITY_SET_TO_AUTOMATIC'),
					checked: entityModel.IS_MANUAL_OPPORTUNITY !== 'Y',
					icon: Icon.SIGMA_SUMM,
					disable: !canUpdate,
					showTopSeparator: showTopSeparatorForSwitchSection,
				});

				showTopSeparatorForSwitchSection = false;
			}

			if (todoNotificationParams && todoNotificationParams.notificationSupported)
			{
				result.push({
					...getSmartActivityMenuItem(todoNotificationParams.notificationEnabled, entityTypeId),
					showTopSeparator: showTopSeparatorForSwitchSection,
				});
			}

			if (isChatSupported)
			{
				result.push(
					{
						id: 'openChat',
						sectionCode: 'additional',
						sort: 800,
						onItemSelected: () => openChat(detailCard),
						title: Loc.getMessage('M_CRM_ENTITY_ACTION_CHAT'),
						icon: Icon.EMPTY_MESSAGE,
						disable: false,
					},
				);
			}

			const {
				id: shareId,
				title: shareTitle,
				onAction: onShareAction,
			} = getActionToShare();

			result.push({
				id: shareId,
				title: shareTitle,
				sort: 900,
				icon: Icon.SHARE,
				onItemSelected: () => onShareAction(qrUrl),
				sectionCode: 'additional',
				showTopSeparator: showTopSeparatorForSettingsMenu,
			});

			result.push({
				type: UI.Menu.Types.DESKTOP,
				showHint: false,
				data: { qrUrl },
				sectionCode: 'additional',
				showTopSeparator: showTopSeparatorForSettingsMenu,
			});

			const articleCode = getArticleCodeByType(detailCard.getEntityTypeId());
			if (articleCode)
			{
				result.push({
					type: UI.Menu.Types.HELPDESK,
					data: { articleCode },
					sectionCode: 'additional',
					sort: 1050,
				});
			}

			result.push(
				{
					...getCopyEntity(detailCard, canAdd),
					showTopSeparator: isDocumentGenerationEnabled,
				},
			);

			result.push({
				id: 'deleteItem',
				sectionCode: 'additional',
				sort: 1200,
				isDestructive: true,
				onItemSelected: () => deleteEntity(detailCard),
				title: Loc.getMessage('M_CRM_ENTITY_ACTION_DELETE_MSGVER_1'),
				icon: Icon.TRASHCAN,
				disable: !canDelete,
			});

			showTopSeparatorForSettingsMenu = false;
		}

		result = mixinAnalyticsSend(result, detailCard);

		return result;
	};

	/**
	 * @param {array} result
	 * @param {DetailCardComponent} detailCard
	 * @return {array}
	 */
	const mixinAnalyticsSend = (result, detailCard) => {
		const entityTypeId = detailCard.getEntityTypeId();

		return result.map((menuItem) => {
			const { id, onItemSelected } = menuItem;

			if (!onItemSelected)
			{
				return menuItem;
			}

			return {
				...menuItem,
				onItemSelected: (...params) => {
					onItemSelected(params);

					if (id)
					{
						AnalyticsLabel.send({
							module: 'crm',
							source: 'detail-card-top-menu',
							entityTypeId,
							id,
						});
					}
				},
			};
		});
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 * @param {boolean} canCreate
	 */
	const getCopyEntity = (detailCard, canCreate) => {
		const entityTypeId = detailCard.getEntityTypeId();
		const entityId = detailCard.getEntityId();
		const { categoryId } = detailCard.getComponentParams();
		const { id, title, onAction } = getActionToCopyEntity(entityTypeId);
		const analytics = new AnalyticsEvent(BX.componentParameters.get('analytics', {}))
			.setSubSection('element_card')
			.setElement('top_context_menu');

		return {
			id,
			title,
			icon: Icon.COPY,
			sort: 1100,
			disable: !canCreate,
			sectionCode: 'additional',
			onItemSelected: () => onAction({ entityTypeId, entityId, categoryId, analytics }),
		};
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 * @param {boolean} canUpdate
	 */
	const getChangeEntityCategory = (detailCard, canUpdate) => {
		const entityTypeId = detailCard.getEntityTypeId();
		const { id, title, onAction } = getActionToChangePipeline();

		return {
			id,
			title,
			icon: Icon.CHANGE_FUNNEL,
			sort: 300,
			disable: !canUpdate,
			sectionCode: 'top',
			onItemSelected: () => {
				let promise = Promise.resolve();
				let selectedCategoryId = null;

				if (detailCard.isToolPanelVisible())
				{
					promise = promise.then(() => askAboutUnsavedChanges());
				}

				promise
					.then(() => onAction({
						entityTypeId,
						categoryId: detailCard.entityModel.CATEGORY_ID,
						itemId: detailCard.getComponentParams().entityId,
						onlyEmitEvent: true,
					}))
					.then(({ categoryId }) => {
						selectedCategoryId = categoryId;

						return detailCard.refreshDetailCard();
					})
					.then(() => detailCard.handleSave({ CATEGORY_ID: selectedCategoryId, isCategoryUpdated: true }));
			},
		};
	};

	/**
	 * @private
	 */
	const askAboutUnsavedChanges = () => {
		return new Promise((resolve, reject) => {
			confirmDestructiveAction({
				title: Loc.getMessage('M_CRM_ENTITY_ACTION_CHANGE_CATEGORY_UNSAVED_ALERT_TITLE2'),
				description: Loc.getMessage('M_CRM_ENTITY_ACTION_CHANGE_CATEGORY_UNSAVED_ALERT_TEXT2'),
				destructionText: Loc.getMessage('M_CRM_ENTITY_ACTION_CHANGE_CATEGORY_UNSAVED_ALERT_OK'),
				cancelText: Loc.getMessage('M_CRM_ENTITY_ACTION_CHANGE_CATEGORY_UNSAVED_ALERT_CANCEL'),
				onDestruct: resolve,
				onCancel: reject,
			});
		});
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const excludeEntity = (detailCard) => {
		confirmDestructiveAction({
			title: Loc.getMessage('M_CRM_ENTITY_ACTION_EXCLUDE'),
			description: getEntityMessage('M_CRM_ENTITY_ACTION_EXCLUDE_CONFIRMATION', detailCard.getEntityTypeId()),
			destructionText: Loc.getMessage('M_CRM_ENTITY_ACTION_EXCLUDE_CONFIRMATION_OK'),
			onDestruct: () => runActionWithClose('excludeEntity', detailCard),
		});
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const deleteEntity = (detailCard) => {
		confirmDestructiveAction({
			title: getEntityMessage('M_CRM_ENTITY_ACTION_DELETE', detailCard.getEntityTypeId()),
			description: getEntityMessage('M_CRM_ENTITY_ACTION_DELETE_CONFIRMATION', detailCard.getEntityTypeId()),
			onDestruct: () => runActionWithClose('deleteEntity', detailCard),
		});
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const openChat = (detailCard) => {
		EntityChatOpener.openChatByEntity(detailCard.getEntityTypeId(), detailCard.getEntityId());
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const showEntityDocuments = (detailCard) => {
		const { documentGeneratorProvider } = detailCard.getComponentParams();
		CrmDocumentList.open({
			documentGeneratorProvider,
			entityTypeId: detailCard.getEntityTypeId(),
			entityId: detailCard.getEntityId(),
		});
	};

	/**
	 * @private
	 * @param {String} actionName
	 * @param {DetailCardComponent} detailCard
	 * @returns {void}
	 */
	const runActionWithClose = (actionName, detailCard) => {
		NotifyManager.showLoadingIndicator();

		BX.ajax.runAction(`crmmobile.EntityDetails.${actionName}`, {
			json: {
				entityTypeId: detailCard.getEntityTypeId(),
				entityId: detailCard.getEntityId(),
			},
		})
			.then(() => {
				detailCard.emitEntityUpdate(actionName);
				NotifyManager.hideLoadingIndicatorWithoutFallback();
				detailCard.close();
			})
			.catch((error) => {
				NotifyManager.showDefaultError();
				console.error(error);
			})
		;
	};

	const getArticleCodeByType = (entityTypeId) => {
		switch (entityTypeId)
		{
			case TypeId.SmartInvoice:
				return '17418408';
		}

		return null;
	};

	module.exports = { menuProvider };
});
