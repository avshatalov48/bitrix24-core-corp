/**
 * @module crm/document/details/pdf-view
 */
jn.define('crm/document/details/pdf-view', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const AppTheme = require('apptheme');
	const { CrmDocumentDetailsErrorPanel } = require('crm/document/details/error-panel');
	const isAndroid = Application.getPlatform() === 'android';
	const isPdfViewSupported = typeof PDFView !== 'undefined';

	const CrmDocumentDetailsPdfView = ({ uri }) => View(
		{
			style: {
				flex: 1,
				backgroundColor: AppTheme.colors.bgSecondary,
				paddingHorizontal: isAndroid ? 12 : 0,
				paddingTop: isAndroid ? 12 : 0,
				paddingBottom: 3,
				justifyContent: 'center',
			},
		},
		!isPdfViewSupported && CrmDocumentDetailsErrorPanel({
			title: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PDF_VIEWER_NOT_SUPPORTED_ERROR_TITLE'),
			subtitle: Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PDF_VIEWER_NOT_SUPPORTED_ERROR_BODY'),
		}),
		isPdfViewSupported && PDFView({
			style: {
				flex: 1,
				paddingTop: isAndroid ? 0 : 13,
				paddingBottom: isAndroid ? 0 : 3,
				paddingHorizontal: isAndroid ? 0 : 33,
			},
			url: uri,
			onFailure: () => Alert.alert(
				Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PDF_ERROR_TITLE'),
				Loc.getMessage('M_CRM_DOCUMENT_DETAILS_PDF_ERROR_BODY'),
			),
		}),
	);

	module.exports = { CrmDocumentDetailsPdfView };
});
