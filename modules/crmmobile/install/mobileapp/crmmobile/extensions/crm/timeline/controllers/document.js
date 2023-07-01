/**
 * @module crm/timeline/controllers/document
 */
jn.define('crm/timeline/controllers/document', (require, exports, module) => {
	const { TimelineBaseController } = require('crm/controllers/base');
	const { CrmDocumentDetails } = require('crm/document/details');
	const { Alert, makeDestructiveButton, makeCancelButton, makeButton } = require('alert');
	const { Loc } = require('loc');
	const { Filesystem, utils } = require('native/filesystem');
	const { withCurrentDomain } = require('utils/url');
	const { Feature } = require('feature');

	const SupportedActions = {
		OPEN: 'Document:Open',
		PRINT: 'Document:Print',
		DELETE: 'Document:Delete',
		DOWNLOAD_PDF: 'Document:DownloadPdf',
		DOWNLOAD_DOCX: 'Document:DownloadDocx',
	};

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
			switch (action)
			{
				case SupportedActions.OPEN:
					this.openDocument(actionParams);
					break;

				case SupportedActions.PRINT:
					this.printDocument(actionParams);
					break;

				case SupportedActions.DELETE:
					this.deleteDocument(actionParams);
					break;

				case SupportedActions.DOWNLOAD_PDF:
					this.downloadPdf(actionParams);
					break;

				case SupportedActions.DOWNLOAD_DOCX:
					this.downloadDocx(actionParams);
					break;
			}
		}

		openDocument(actionParams)
		{
			const { pdfUrl, documentId, createdAt, title } = actionParams;
			const filename = title ? `${title}.pdf` : 'document.pdf';

			if (documentId && this.entity.isDocumentPreviewerAvailable)
			{
				CrmDocumentDetails.open({
					documentId,
					createdAt,
					title: title || filename,
				});
				return;
			}

			if (!pdfUrl)
			{
				this.notifyPdfNotReady();
				return;
			}

			viewer.openDocument(withCurrentDomain(pdfUrl), filename);
		}

		printDocument(actionParams)
		{
			const { pdfUrl } = actionParams;
			if (pdfUrl)
			{
				if (utils && 'printFile' in utils)
				{
					Notify.showIndicatorLoading();

					Filesystem.downloadFile(withCurrentDomain(pdfUrl)).then((uri) => {
						Notify.hideCurrentIndicator();
						utils.printFile(uri)
							.catch(() => this.openShareDialog(pdfUrl));
					});
				}
				else
				{
					this.openShareDialog(pdfUrl);
				}
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
							() => this.executeDeleteAction(data),
						),
						makeCancelButton(),
					],
				);
			}
			else
			{
				this.executeDeleteAction(data);
			}
		}

		downloadPdf({ pdfUrl, docxUrl })
		{
			if (!pdfUrl)
			{
				Alert.confirm(
					'',
					Loc.getMessage('M_CRM_TIMELINE_DOCUMENT_PDF_NOT_READY_USE_DOCX_INSTEAD'),
					[
						makeCancelButton(null, Loc.getMessage('M_CRM_TIMELINE_CLOSE')),
						makeButton(
							Loc.getMessage('M_CRM_TIMELINE_DOCUMENT_DOWNLOAD_DOCX'),
							() => this.downloadDocx({ docxUrl }),
						),
					],
				);
				return;
			}

			this.openShareDialog(pdfUrl);
		}

		downloadDocx({ docxUrl })
		{
			if (!docxUrl)
			{
				return;
			}

			this.openShareDialog(docxUrl, 'docx');
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

				Filesystem.downloadFile(url).then((uri) => {
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
