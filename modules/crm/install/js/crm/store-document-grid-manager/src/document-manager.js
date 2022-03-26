import {Uri} from "main.core";

export class DocumentManager
{
	static getRealizationDocumentDetailUrl(id)
	{
		return new Uri('/shop/documents/details/sales_order/' + id + '/');
	}

	static openRealizationDetailDocument(id)
	{
		const documentUrl = DocumentManager.getRealizationDocumentDetailUrl(id);
		return BX.SidePanel.Instance.open(documentUrl.toString());
	}
}
