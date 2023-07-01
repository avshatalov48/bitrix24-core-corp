/**
 * @bxjs_lang_path extension.php
 * @module crm/entity-detail/component/menu-provider
 */
jn.define('crm/entity-detail/component/menu-provider', (require, exports, module) => {
	const { Alert } = require('alert');
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
			shouldShowAutomationMenuItem,
			isDocumentPreviewerAvailable,
			isGoToChatAvailable,
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

			if (isDocumentPreviewerAvailable)
			{
				result.push({
					id: 'documents',
					sectionCode: 'action',
					onItemSelected: () => showEntityDocuments(detailCard),
					title: Loc.getMessage('M_CRM_ENTITY_ACTION_DOCUMENTS'),
					iconUrl: `${component.path}/icons/documents.png`,
					disable: false, // todo check rights to documents
				});
			}

			result.push({
				...getCopyEntity(detailCard, canAdd),
				showTopSeparator: Boolean(isDocumentPreviewerAvailable),
			});

			// ToDo move to isCategoriesEnabled
			if (entityTypeId === TypeId.Deal)
			{
				result.push(getChangeEntityCategory(detailCard, canUpdate));
			}

			if (entityTypeId === TypeId.Deal || entityTypeId === TypeId.Lead)
			{
				result.push({
					id: 'excludeItem',
					sectionCode: 'action',
					onItemSelected: () => excludeEntity(detailCard),
					title: BX.message('M_CRM_ENTITY_ACTION_EXCLUDE'),
					iconUrl: `${component.path}/icons/exclude2.png`,
					disable: !canExclude,
				});
			}

			result.push({
				id: 'deleteItem',
				sectionCode: 'action',
				onItemSelected: () => deleteEntity(detailCard),
				title: getEntityMessage('M_CRM_ENTITY_ACTION_DELETE', detailCard.getEntityTypeId()),
				iconUrl: `${component.path}/icons/delete.png`,
				disable: !canDelete,
			});

			if (entityModel.hasOwnProperty('IS_MANUAL_OPPORTUNITY'))
			{
				result.push({
					id: 'disableManualOpportunity',
					sectionCode: 'action',
					onItemSelected: () => detailCard.customEventEmitter.emit('EntityDetails::onChangeManualOpportunity'),
					title: BX.message('M_CRM_CHANGE_MANUAL_OPPORTUNITY_SET_TO_AUTOMATIC'),
					checked: entityModel.IS_MANUAL_OPPORTUNITY !== 'Y',
					iconUrl: `${component.path}/icons/manual_opportunity.png`,
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

			const {
				id: shareId,
				title: shareTitle,
				iconUrl: shareIconUrl,
				onAction: onShareAction,
			} = getActionToShare();

			const hasOpenLinesAccess = BX.prop.getBoolean(permissions, 'openLinesAccess', false);
			if (isGoToChatAvailable && hasOpenLinesAccess)
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

			if (entityTypeId === TypeId.Deal && shouldShowAutomationMenuItem)
			{
				result.push({
					...getPaymentAutomationMenuItem(entityId, entityTypeId, categoryId, isAutomationAvailable),
					showTopSeparator: showTopSeparatorForSettingsMenu,
				});

				showTopSeparatorForSettingsMenu = false;
			}

			result.push({
				id: shareId,
				title: shareTitle,
				iconUrl: shareIconUrl,
				onItemSelected: () => onShareAction(qrUrl),
				sectionCode: 'action',
				showTopSeparator: showTopSeparatorForSettingsMenu,
			});

			showTopSeparatorForSettingsMenu = false;
		}

		result.push({
			type: UI.Menu.Types.DESKTOP,
			showHint: false,
			data: { qrUrl },
			showTopSeparator: showTopSeparatorForSettingsMenu,
		});

		const articleCode = getArticleCodeByType(detailCard.getEntityTypeId());
		if (articleCode)
		{
			result.push({
				type: UI.Menu.Types.HELPDESK,
				data: { articleCode },
			});
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
		const { id, title, iconUrl, onAction } = getActionToCopyEntity(entityTypeId);

		return {
			id,
			title,
			iconUrl,
			disable: !canCreate,
			sectionCode: 'action',
			onItemSelected: () => onAction({ entityTypeId, entityId, categoryId }),
		};
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 * @param {boolean} canUpdate
	 */
	const getChangeEntityCategory = (detailCard, canUpdate) => {
		const entityTypeId = detailCard.getEntityTypeId();
		const { id, title, iconUrl, onAction } = getActionToChangePipeline();

		return {
			id,
			title,
			iconUrl,
			disable: !canUpdate,
			sectionCode: 'action',
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
					}))
					.then(({ categoryId }) => {
						selectedCategoryId = categoryId;

						return detailCard.refreshDetailCard();
					})
					.then(() => detailCard.handleSave({ CATEGORY_ID: selectedCategoryId }));
			},
		};
	};

	/**
	 * @private
	 */
	const askAboutUnsavedChanges = () => {
		return new Promise((resolve, reject) => {
			Alert.confirm(
				BX.message('M_CRM_ENTITY_ACTION_CHANGE_CATEGORY_UNSAVED_ALERT_TITLE2'),
				BX.message('M_CRM_ENTITY_ACTION_CHANGE_CATEGORY_UNSAVED_ALERT_TEXT2'),
				[
					{
						text: BX.message('M_CRM_ENTITY_ACTION_CHANGE_CATEGORY_UNSAVED_ALERT_OK'),
						type: 'destructive',
						onPress: resolve,
					},
					{
						text: BX.message('M_CRM_ENTITY_ACTION_CHANGE_CATEGORY_UNSAVED_ALERT_CANCEL'),
						type: 'cancel',
						onPress: reject,
					},
				],
			);
		});
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const excludeEntity = (detailCard) => {
		Alert.confirm(
			BX.message('M_CRM_ENTITY_ACTION_EXCLUDE'),
			getEntityMessage('M_CRM_ENTITY_ACTION_EXCLUDE_CONFIRMATION', detailCard.getEntityTypeId()),
			[
				{
					text: BX.message('M_CRM_ENTITY_ACTION_EXCLUDE_CONFIRMATION_OK'),
					type: 'destructive',
					onPress: () => runActionWithClose('excludeEntity', detailCard),
				},
				{
					type: 'cancel',
				},
			],
		);
	};

	/**
	 * @param {DetailCardComponent} detailCard
	 */
	const deleteEntity = (detailCard) => {
		Alert.confirm(
			getEntityMessage('M_CRM_ENTITY_ACTION_DELETE', detailCard.getEntityTypeId()),
			getEntityMessage('M_CRM_ENTITY_ACTION_DELETE_CONFIRMATION', detailCard.getEntityTypeId()),
			[
				{
					text: BX.message('M_CRM_ENTITY_ACTION_DELETE_CONFIRMATION_OK'),
					type: 'destructive',
					onPress: () => runActionWithClose('deleteEntity', detailCard),
				},
				{
					type: 'cancel',
				},
			],
		);
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
