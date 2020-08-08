import {config} from './config';
import {Vue} from 'ui.vue';

import "./bx-salescenter-app-add-payment-product";

let classModule = 'salescenter-app-payment-by-sms-timeline';

Vue.component(config.templateAddPaymentBySmsTimeline,
{
	data()
	{
		return {
			type: null,
		}
	},

	mounted()
	{
	
	},

	computed:
	{
	
	},

	methods:
	{
	
	},

	template: `
		<div class="${classModule}">
			<div class="${classModule}-item ${classModule}-item-disabled">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-sent"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">������ ��� �� ������� �� ������</span>
				</div>
			</div>
			<div class="${classModule}-item ${classModule}-item-disabled">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-cash"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">������ ��� �� ������� �����</span>
				</div>
			</div>
			<div class="${classModule}-item ${classModule}-item-disabled">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-check-sent"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">��� �� ��������� �������</span>
				</div>
			</div>
			
			<!--<div class="${classModule}-item ${classModule}-item-payment">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-cash"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-price">
						890
						<span class="${classModule}-content-price-cur">���.</span>
					</span>
					<span class="${classModule}-content-text-strong">�������� 22 ������� 14:31</span>
					<span class="${classModule}-content-text">������ ������: �������� ������</span>
				</div>
			</div>
			<div class="${classModule}-item">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-watch"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">������ ���������� ����� 22 ������� 14:00</span>
				</div>
			</div>
			<div class="${classModule}-item">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-check"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">������ ��� �123 �� 22 �������</span>
					<a href="#">����������</a>
				</div>
			</div>
			<div class="${classModule}-item ${classModule}-item-disabled">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-check-sent"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">��� �123 ��������� ������� 22 ������� 22:34</span>
					<a href="#">����������</a>
				</div>
			</div>-->
		</div>
	`
});