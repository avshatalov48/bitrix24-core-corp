import {MenuItem} from 'main.popup';
import Item from './item';
import 'clipboard';

export default class ItemInternalLink extends Item
{
	constructor(trackedObjectId, itemData)
	{
		super(trackedObjectId, itemData);
		this.data['className'] += ' disk-documents-grid-actions-copy-internal-link';
		this.data['html'] = [
			this.data.text,
			'<span class="disk-documents-grid-actions-copy-internal-link-icon">' +
				'<span class="disk-documents-grid-actions-copy-internal-link-icon-inner">' +
				'</span>' +
			'</span>'
		].join('');
		delete this.data['text'];

		this.data['dataset'] = (this.data['dataset'] || {});
		this.data['dataset']['preventCloseContextMenu'] = true;
		this.data['onclick'] = function(event, menuItem: MenuItem) {
			const target = menuItem.getLayout().item;
			target.classList.add('menu-popup-item-accept', 'disk-folder-list-context-menu-item-accept-animate');
			target.style.minWidth = (target.offsetWidth) + 'px';
			const textNode = target.querySelector('.menu-popup-item-text');
			if (textNode)
			{
				textNode.textContent = this.data['dataset']['textCopied'];
			}
			BX.clipboard.copy(this.data['dataset']['internalLink']);
		}.bind(this);
	}

	static detect(itemData)
	{
		return itemData['id'] === 'internalLink';
	}
}

