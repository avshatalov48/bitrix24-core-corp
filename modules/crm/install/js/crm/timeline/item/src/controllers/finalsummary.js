import {Base} from './base';
import ConfigurableItem from '../configurable-item';
import {EventEmitter} from 'main.core.events';
import {EcommerceDocumentsList} from '../components/content-blocks/ecommerce-documents-list';

export class FinalSummary extends Base
{
	onAfterItemLayout(item: ConfigurableItem, options): void
	{
		if (item.needBindToContainer())
		{
			EventEmitter.emit(
				'BX.Crm.Timeline.Items.FinalSummaryDocuments:onHistoryNodeAdded',
				[item.getWrapper()]
			);
		}
	}

	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			EcommerceDocumentsList,
		};
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return item.getType() === 'FinalSummary';
	}
}
