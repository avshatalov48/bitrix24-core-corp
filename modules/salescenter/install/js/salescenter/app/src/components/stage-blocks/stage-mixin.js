import {StatusTypes as Status} from 'salescenter.component.stage-block';

const StageMixin = {
	computed:
		{
			statusClassMixin()
			{
				return {
					'salescenter-app-payment-by-sms-item'			: true,
					'salescenter-app-payment-by-sms-item-current'	: this.status === Status.current,
					'salescenter-app-payment-by-sms-item-disabled'	: this.status === Status.disabled,
				}
			},

			containerClassMixin()
			{
				return {
					'salescenter-app-payment-by-sms-item-container'	: true
				}
			},

			counterCheckedMixin()
			{
				return this.status === Status.complete
			}
		},
		methods:
			{
				onSliderClose(e)
				{
					this.$emit('on-stage-tile-collection-slider-close', e);
				}
			}
};

export {
	StageMixin
}