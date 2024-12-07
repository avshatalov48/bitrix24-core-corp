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
					<span class="${classModule}-content-text">Клиент еще не перешел по ссылке</span>
				</div>
			</div>
			<div class="${classModule}-item ${classModule}-item-disabled">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-cash"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">Клиент еще не оплатил заказ</span>
				</div>
			</div>
			<div class="${classModule}-item ${classModule}-item-disabled">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-check-sent"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">Чек не отправлен клиенту</span>
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
						<span class="${classModule}-content-price-cur">руб.</span>
					</span>
					<span class="${classModule}-content-text-strong">Оплачено 22 декабря 14:31</span>
					<span class="${classModule}-content-text">Способ оплаты: Сбербанк онлайн</span>
				</div>
			</div>
			<div class="${classModule}-item">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-watch"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">Клиент просмотрел заказ 22 декабря 14:00</span>
				</div>
			</div>
			<div class="${classModule}-item">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-check"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">Создан чек №123 от 22 декабря</span>
					<a href="#">Посмотреть</a>
				</div>
			</div>
			<div class="${classModule}-item ${classModule}-item-disabled">
				<div class="salescenter-app-payment-by-sms-item-counter">
					<div class="salescenter-app-payment-by-sms-item-counter-line"></div>
					<div class="salescenter-app-payment-by-sms-item-counter-icon salescenter-app-payment-by-sms-item-counter-icon-check-sent"></div>
				</div>
				<div class="${classModule}-content">
					<span class="${classModule}-content-text">Чек №123 отправлен клиенту 22 декабря 22:34</span>
					<a href="#">Посмотреть</a>
				</div>
			</div>-->
		</div>
	`
});