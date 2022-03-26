import {Type, Uri} from "main.core";

export default class DocumentManager
{
	static openRealizationDetailDocument(id, params = {})
	{
		let url = DocumentManager.getRealizationDocumentDetailUrl(id, params);
		let sliderOptions = params.hasOwnProperty('sliderOptions') ? params.sliderOptions : {};
		return new Promise((resolve, reject) =>
		{
			DocumentManager.openSlider(url.toString(), sliderOptions).then((slider) =>
			{
				resolve(slider.getData());
			}).catch((reason) =>
			{

			});
		});
	}

	static openNewRealizationDocument(params = {})
	{
		let sliderOptions = {};
		if (params.hasOwnProperty('sliderOptions'))
		{
			sliderOptions = params.sliderOptions;
			delete params.sliderOptions;
		}

		let url = DocumentManager.getNewRealizationDocumentUrl(params);

		return new Promise((resolve, reject) =>
		{
			DocumentManager.openSlider(url.toString(), sliderOptions).then((slider) =>
			{
				resolve(slider.getData());
			}).catch((reason) =>
			{

			});
		});
	}

	static openSlider(url, options)
	{
		if (!Type.isPlainObject(options))
		{
			options = {};
		}

		options = {...{cacheable: false, allowChangeHistory: false, events: {}}, ...options};
		return new Promise((resolve) =>
		{
			if (Type.isString(url) && url.length > 1)
			{
				options.events.onClose = function(event)
				{
					resolve(event.getSlider());
				};

				BX.SidePanel.Instance.open(url, options);
			}
			else
			{
				resolve();
			}
		});
	}

	static getRealizationDocumentDetailUrl(id, params)
	{
		let url = new Uri('/shop/documents/details/sales_order/' + id + '/');
		if (Type.isPlainObject(params))
		{
			url.setQueryParams(params);
		}

		return url;
	}

	static getNewRealizationDocumentUrl(params)
	{
		let url = new Uri('/shop/documents/details/sales_order/0/?DOCUMENT_TYPE=W');
		if (Type.isPlainObject(params))
		{
			url.setQueryParams(params);
		}

		return url;
	}
}
