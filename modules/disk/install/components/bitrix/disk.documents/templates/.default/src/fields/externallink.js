import {Sharing} from './sharing';
import {ExternalLinkForTrackedObject} from 'disk.external-link';

export class ExternalLink extends Sharing
{
	init()
	{
		this.actionName = 'getExternalLink';
	}

	showLoading()
	{
	}

	hideLoading()
	{
	}

	renderData(data)
	{
		this.node.innerHTML = '';
		const res = new ExternalLinkForTrackedObject(this.id, data);
		this.node.appendChild(res.getContainer());
	}
}
