import {TimelineSummaryDocuments} from 'crm.entity-editor.field.payment-documents';

export const EcommerceDocumentsList = {
	props: {
		ownerId: {
			type: Number,
			required: true,
		},
		ownerTypeId: {
			type: Number,
			required: true,
		},
		isWithOrdersMode: {
			type: Boolean,
			required: true,
		},
		summaryOptions: {
			type: Object,
			required: true,
		}
	},
	mounted()
	{
		const timelineSummaryDocuments = new TimelineSummaryDocuments({
			'OWNER_ID': this.ownerId,
			'OWNER_TYPE_ID': this.ownerTypeId,
			'PARENT_CONTEXT': this,
			'CONTEXT': BX.CrmEntityType.resolveName(this.ownerTypeId).toLowerCase(),
			'IS_WITH_ORDERS_MODE': this.isWithOrdersMode,
		});
		timelineSummaryDocuments.setOptions(this.summaryOptions);

		this.$el.appendChild(
			timelineSummaryDocuments.render()
		);
	},
	methods:
	{
		startSalescenterApplication(orderId, options)
		{
			if (options === undefined)
			{
				return;
			}

			BX.loadExt('salescenter.manager').then(() => {
				BX.Salescenter.Manager.openApplication(options);
			});
		},
	},
	template: `<div></div>`
}
