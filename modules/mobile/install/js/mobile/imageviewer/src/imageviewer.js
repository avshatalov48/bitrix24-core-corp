import {Type, Event} from "main.core";

class ImageViewer
{
	constructor()
	{
	}

	viewImageBind(div, targetCriteria)
	{
		if (Type.isStringFilled(div))
		{
			div = document.getElementById(div);
		}

		if (!Type.isDomNode(div))
		{
			return;
		}

		let targetSelector = '';

		if (Type.isPlainObject(targetCriteria))
		{
			let
				tagString = '',
				attrString = '';

			for (let key in targetCriteria)
			{
				if (!targetCriteria.hasOwnProperty(key))
				{
					continue;
				}

				switch(key)
				{
					case 'tag':
						tagString = targetCriteria[key];
						break;
					case 'attr':
						attrString = targetCriteria[key];
						break;
					default:
				}
			}

			targetSelector = (Type.isStringFilled(tagString) ? tagString : '') + (Type.isStringFilled(attrString) ? '[' + attrString + ']' : '');
		}
		else if (Type.isStringFilled(targetCriteria))
		{
			targetSelector = targetCriteria;
		}

		if (!Type.isStringFilled(targetSelector))
		{
			return;
		}

		Event.bind(div, 'click', (e) => {

			if (e.target.tagName.toUpperCase() === 'A')
			{
				return;
			}

			let found = false;
			const siblings = e.target.parentNode.querySelectorAll(targetSelector);
			for(let i=0; i<siblings.length; i++)
			{
				if (siblings[i].parentNode === e.target.parentNode)
				{
					found = true;
					break;
				}
			}

			if (!found)
			{
				return;
			}

			const imgNodeList = e.currentTarget.querySelectorAll(targetSelector);
			let
				imgList = [],
				photosList = [],
				currentImage = false,
				currentPreview = false;

			for(let i=0; i<imgNodeList.length; i++)
			{
				currentImage = imgNodeList[i].getAttribute('data-bx-image');

				if (!imgList.includes(currentImage))
				{
					currentPreview = imgNodeList[i].getAttribute('data-bx-preview');
					imgList.push(imgNodeList[i].getAttribute('data-bx-image'));
					photosList.push({
						url: currentImage,
						preview: (Type.isStringFilled(currentPreview) ? currentPreview : ''),
						description: ''
					});
				}
			}

			var viewerParams = {
				photos: photosList
			};

			let target = null;

			if (e.target.tagName.toUpperCase() == 'IMG')
			{
				target = e.target;
			}
			else
			{
				let
					container = e.target.closest('[data-bx-disk-image-container]');

				if (!container)
				{
					container = e.target.closest('div');
				}

				if (container)
				{
					target = container.querySelector('img');
				}
			}

			if (target)
			{
				currentImage = target.getAttribute('data-bx-image');
				if (Type.isStringFilled(currentImage))
				{
					viewerParams.default_photo = currentImage;
				}

				currentPreview = target.getAttribute('data-bx-preview');
				if (Type.isStringFilled(currentPreview))
				{
					viewerParams.default_preview = currentPreview;
				}
			}

			BXMobileApp.UI.Photo.show(viewerParams);

			e.stopPropagation();
			return e.preventDefault();
		});
	}

	view(e)
	{
		e.currentTarget
	}

}

var MobileImageViewer = new ImageViewer;

export {
	MobileImageViewer
};