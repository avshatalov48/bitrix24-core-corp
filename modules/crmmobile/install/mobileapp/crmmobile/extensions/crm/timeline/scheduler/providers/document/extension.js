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
			return '<svg width="30" height="31" viewBox="0 0 30 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.75 5.8252C9.19772 5.8252 8.75 6.27291 8.75 6.8252V23.5752C8.75 24.1275 9.19772 24.5752 9.75 24.5752H22.7784C23.3307 24.5752 23.7784 24.1275 23.7784 23.5752V11.8803C23.7784 11.3781 23.5895 10.8943 23.2491 10.525L19.5124 6.46989C19.1337 6.05897 18.6004 5.8252 18.0416 5.8252H9.75ZM12.0671 10.8032C11.6309 10.8032 11.2773 11.1568 11.2773 11.593V11.9878C11.2773 12.424 11.6309 12.7776 12.0671 12.7776H19.7341C20.1703 12.7776 20.5239 12.424 20.5239 11.9878V11.593C20.5239 11.1568 20.1703 10.8032 19.7341 10.8032H12.0671ZM12.0671 14.5532C11.6309 14.5532 11.2773 14.9068 11.2773 15.343V15.7378C11.2773 16.174 11.6309 16.5276 12.0671 16.5276H19.7341C20.1703 16.5276 20.5239 16.174 20.5239 15.7378V15.343C20.5239 14.9068 20.1703 14.5532 19.7341 14.5532H12.0671ZM11.2773 19.0427C11.2773 18.6065 11.6309 18.2529 12.0671 18.2529H17.604C18.0402 18.2529 18.3937 18.6065 18.3937 19.0427V19.4376C18.3937 19.8738 18.0402 20.2273 17.604 20.2273H12.0671C11.6309 20.2273 11.2773 19.8738 11.2773 19.4376V19.0427Z" fill="#767C87"/></svg>';
		}

		static getMenuPosition()
		{
			return 800;
		}

		static isSupported(context = {})
		{
			if (!context.detailCard)
			{
				return false;
			}
			const detailCardParams = context.detailCard.getComponentParams();

			return Boolean(get(detailCardParams, 'isDocumentPreviewerAvailable', false));
		}

		static isAvailableInMenu(context = {})
		{
			if (!context.detailCard)
			{
				return false;
			}
			const detailCardParams = context.detailCard.getComponentParams();

			return Boolean(get(detailCardParams, 'isDocumentPreviewerAvailable', false));
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
						entityId: entity.id,
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
			provider = provider.replace(/\\/g, '\\\\');
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
