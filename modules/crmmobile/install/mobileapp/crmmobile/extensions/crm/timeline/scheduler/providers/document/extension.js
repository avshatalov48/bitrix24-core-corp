/**
 * @module crm/timeline/scheduler/providers/document
 */
jn.define('crm/timeline/scheduler/providers/document', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Alert, makeButton, makeCancelButton } = require('alert');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { CrmDocumentDetails } = require('crm/document/details');
	const { NotifyManager } = require('notify-manager');
	const { get } = require('utils/object');
	const { Icon } = require('assets/icons');
	const { EntitySelectorFactory } = require('selector/widget/factory');

	/**
	 * @class TimelineSchedulerDocumentProvider
	 */
	class TimelineSchedulerDocumentProvider extends TimelineSchedulerBaseProvider
	{
		static getId()
		{
			return 'document';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DOCUMENT_TITLE');
		}

		static getMenuShortTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DOCUMENT_SHORT_TITLE');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DOCUMENT_MENU_FULL_TITLE');
		}

		static getMenuIcon()
		{
			return Icon.FILE;
		}

		static getDefaultPosition()
		{
			return 8;
		}

		static isAvailableInMenu(context = {})
		{
			if (!context.detailCard)
			{
				return false;
			}

			const detailCardParams = context.detailCard.getComponentParams();

			return get(detailCardParams, 'isDocumentGenerationEnabled', false);
		}

		static isSupported(context = {})
		{
			return true;
		}

		/**
		 * @public
		 */
		static open({ scheduler, context = {} })
		{
			const { entity, onActivityCreate, parentWidget } = scheduler;
			const providerClassName = entity.documentGeneratorProvider;

			const selector = EntitySelectorFactory.createByType(EntitySelectorFactory.Type.DOCUMENTGENERATOR_TEMPLATE, {
				createOptions: {
					enableCreation: true,
				},
				provider: {
					options: {
						providerClassName,
						value: entity.id,
					},
				},
				widgetParams: {
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DOCUMENT_TITLE'),
					backdrop: {
						mediumPositionPercent: 70,
						horizontalSwipeAllowed: false,
					},
				},
				allowMultipleSelection: false,
				closeOnSelect: true,
				events: {
					onCreate: () => {
						qrauth.open({
							title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DESKTOP_VERSION'),
							redirectUrl: `/crm/documents/templates/?entityTypeId=${providerClassName}`,
							layout: parentWidget === PageManager ? null : parentWidget,
							analyticsSection: 'crm',
						});
					},
					onWidgetClosed: (templates) => {
						if (templates && templates.length > 0)
						{
							const template = templates[0];
							const templateId = Number(template.id);

							this.askAboutUsingPreviousDocumentNumber(
								providerClassName,
								templateId,
								entity.id,
								(previousNumber) => this.addDocument(
									providerClassName,
									templateId,
									entity.id,
									previousNumber,
									onActivityCreate,
									parentWidget,
								),
								() => {},
							);
						}
					},
				},
			});

			selector.show({}, parentWidget);
		}

		/**
		 * @private
		 */
		static askAboutUsingPreviousDocumentNumber(provider, templateId, entityId, onSuccess, onDecline)
		{
			provider = provider.replaceAll('\\', '\\\\');
			BX.ajax.runAction('documentgenerator.api.document.list', {
				data: {
					select: ['id', 'number'],
					filter: {
						provider,
						templateId,
						value: entityId,
					},
					order: { id: 'desc' },
				},
				navigation: {
					size: 1,
				},
			}).then((response) => {
				if (response.data.documents.length > 0)
				{
					const previousNumber = response.data.documents[0].number;
					Alert.confirm(
						Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DOCUMENT_PREVIOUS_NUMBER_TITLE'),
						Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DOCUMENT_PREVIOUS_NUMBER_BODY'),
						[
							makeButton(
								Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DOCUMENT_PREVIOUS_NUMBER_NEW_BUTTON'),
								() => onSuccess(),
							),
							makeButton(
								Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_DOCUMENT_PREVIOUS_NUMBER_OLD_BUTTON'),
								() => onSuccess(previousNumber),
							),
							makeCancelButton(() => onDecline()),
						],
					);
				}
				else
				{
					onSuccess();
				}
			}).catch((err) => {
				console.error('Cannot load document list', err);
				onDecline();
			});
		}

		/**
		 * @private
		 */
		static addDocument(provider, templateId, entityId, previousNumber, onActivityCreate, parentWidget)
		{
			const action = 'documentgenerator.document.add';
			const data = {
				templateId,
				providerClassName: provider,
				value: entityId,
				values: {},
			};
			if (previousNumber)
			{
				data.values.DocumentNumber = previousNumber;
			}

			NotifyManager.showLoadingIndicator();
			BX.ajax.runAction(action, { data })
				.then((response) => {
					NotifyManager.hideLoadingIndicator(true, '', 300);
					if (onActivityCreate)
					{
						onActivityCreate(response.data.document);
					}
					setTimeout(() => {
						CrmDocumentDetails.open({
							parentWidget,
							documentId: response.data.document.id,
							createdAt: response.data.document.createTime,
							title: response.data.document.title,
						});
					}, 400);
				})
				.catch((err) => {
					// todo add some alert
					console.error(err);
					NotifyManager.hideLoadingIndicator(false);
				});
		}
	}

	module.exports = { TimelineSchedulerDocumentProvider };
});
