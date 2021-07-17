import {Type,Event,Runtime,Dom} from "main.core";
import {MobileImageViewer} from "mobile.imageviewer";
import {Ajax} from "mobile.ajax";

export default class ImageController
{
	constructor(params)
	{
		const
			imagesNode = (params.imagesNode && Type.isDomNode(params.imagesNode) ? params.imagesNode : null),
			moreFilesNode = (params.moreFilesNode && Type.isDomNode(params.moreFilesNode) ? params.moreFilesNode : null),
			toggleViewNode = (params.toggleViewNode && Type.isDomNode(params.toggleViewNode) ? params.toggleViewNode : null),
			imagesIdList = (params.imagesIdList && Type.isArray(params.imagesIdList) ? params.imagesIdList : []);

		this.signedParameters = (params.signedParameters && Type.isStringFilled(params.signedParameters) ? params.signedParameters : '');

		if (imagesIdList.length > 0)
		{
			BitrixMobile.LazyLoad.registerImages(imagesIdList, (typeof oMSL != 'undefined' ? oMSL.checkVisibility : false));
		}

		if (imagesNode)
		{
			this.initViewer(imagesNode);
		}

		if (moreFilesNode)
		{
			Event.bind(moreFilesNode, 'click', (e) => {
				this.showMoreDiskFiles(e.currentTarget);
				e.preventDefault();
			});
		}

		if (toggleViewNode)
		{
			Event.bind(toggleViewNode, 'click', (e) => {
				const
					viewType = e.currentTarget.getAttribute('data-bx-view-type'),
					container = e.currentTarget.closest('.disk-ui-file-container');

				if (container)
				{
					this.toggleViewType({
						viewType: viewType,
						container: container
					});
				}

				e.preventDefault();
			});
		}
	}

	initViewer(node)
	{
		if (!Type.isDomNode(node))
		{
			return;
		}

		MobileImageViewer.viewImageBind(
			node,
			'img[data-bx-image]'
		);
	}

	showMoreDiskFiles(linkNode)
	{
		if (!Type.isDomNode(linkNode))
		{
			return;
		}

		const
			filesBlock = linkNode.closest('.post-item-attached-file-wrap');

		if (filesBlock)
		{
			const
				filesList = filesBlock.querySelectorAll('.post-item-attached-file'),
				moreBlock = filesBlock.querySelector('.post-item-attached-file-more');

			for (let i = 0; i < filesList.length; i++)
			{
				filesList[i].classList.remove('post-item-attached-file-hidden');
			}

			if (moreBlock)
			{
				moreBlock.parentNode.removeChild(moreBlock);
			}
		}
	}

	toggleViewType(params)
	{
		const container = (params.container && Type.isDomNode(params.container) ? params.container : null);

		if (!container)
		{
			return;
		}

		app.showPopupLoader({text: ''});

		Ajax.runComponentAction('bitrix:disk.uf.file', 'toggleViewType', {
			mode: 'class',
			signedParameters: this.signedParameters,
			data: {
				params: {
					viewType: params.viewType
				}
			}
		}).then((response) => {
			app.hidePopupLoader();
			Dom.clean(container);
			Runtime.html(container, response.data.html).then(() => {
				BitrixMobile.LazyLoad.showImages();
			});
		}, () => {
			app.hidePopupLoader();
		});
	}
}