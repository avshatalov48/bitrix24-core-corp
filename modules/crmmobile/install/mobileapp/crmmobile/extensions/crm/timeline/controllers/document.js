/**
 * @module crm/timeline/controllers/document
 */
jn.define('crm/timeline/controllers/document', (require, exports, module) => {

	const { TimelineBaseController } = require('crm/controllers/base');
	const { CrmDocumentDetails } = require('crm/document/details');
	const { Alert, makeDestructiveButton, makeCancelButton } = require('alert');
	const { Loc } = require('loc');
	const { Filesystem } = require('native/filesystem');
	const { withCurrentDomain } = require('utils/url');
	const { Feature } = require('feature');

	const SupportedActions = {
		OPEN: 'Document:Open',
		PRINT: 'Document:Print',
		DELETE: 'Document:Delete',
	};

	const useDocumentViewer = false;

	/**
	 * @class TimelineDocumentController
	 */
	class TimelineDocumentController extends TimelineBaseController
	{
		static getSupportedActions()
		{
			return Object.values(SupportedActions);
		}

		/**
		 * @public
		 * @param {string} action
		 * @param {object} actionParams
		 */
		onItemAction({ action, actionParams = {} })
		{
			if (action === SupportedActions.OPEN)
			{
				this.openDocument(actionParams);
			}
			else if (action === SupportedActions.PRINT)
			{
				this.printDocument(actionParams);
			}
			else if (action === SupportedActions.DELETE)
			{
				this.deleteDocument(actionParams);
			}
		}

		openDocument(actionParams)
		{
			const { pdfUrl, documentId } = actionParams;
			const title = actionParams.title ? `${actionParams.title}.pdf` : 'document.pdf';

			if (documentId && useDocumentViewer)
			{
				CrmDocumentDetails.open({ documentId });
				return;
			}

			if (!pdfUrl)
			{
				this.notifyPdfNotReady();
				return;
			}

			viewer.openDocument(withCurrentDomain(pdfUrl), title);
		}

		printDocument(actionParams)
		{
			const { pdfUrl } = actionParams;
			if (pdfUrl)
			{
				this.openShareDialog(pdfUrl);
			}
			else
			{
				this.notifyPdfNotReady();
			}
		}

		deleteDocument(actionParams)
		{
			const { id, ownerTypeId, ownerId, confirmationText } = actionParams;
			const data = { id, ownerTypeId, ownerId };

			if (confirmationText)
			{
				Alert.confirm(
					'',
					confirmationText,
					[
						makeDestructiveButton(
							Loc.getMessage('CRM_TIMELINE_CONFIRM_REMOVE'),
							() => this.executeDeleteAction(data)
						),
						makeCancelButton(),
					]
				);
			}
			else
			{
				this.executeDeleteAction(data);
			}
		}

		notifyPdfNotReady()
		{
			Alert.alert(
				Loc.getMessage('M_CRM_TIMELINE_DOCUMENT_PDF_NOT_READY_TITLE'),
				Loc.getMessage('M_CRM_TIMELINE_DOCUMENT_PDF_NOT_READY_BODY'),
			);
		}

		openShareDialog(url, ext = 'pdf')
		{
			url = withCurrentDomain(url);

			if (Feature.isShareDialogSupportsFiles())
			{
				Notify.showIndicatorLoading();
				Filesystem.downloadFile(url).then(uri => {
					Notify.hideCurrentIndicator();
					dialogs.showSharingDialog({ uri });
				});
			}
			else
			{
				viewer.openDocument(url, `document.${ext}`);
			}
		}

		/**
		 * @private
		 * @param {{ id: number, ownerTypeId: number, ownerId: number }} data
		 */
		executeDeleteAction(data = {})
		{
			const action = 'crm.timeline.document.delete';

			this.item.showLoader();

			BX.ajax.runAction(action, { data })
				.catch((response) => {
					this.item.hideLoader();
					console.error('Unable to delete document', response);
					Alert.alert('', response.errors[0].message);
				});
		}
	}

	module.exports = { TimelineDocumentController };

});