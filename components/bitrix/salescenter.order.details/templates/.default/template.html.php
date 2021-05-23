<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
\Bitrix\Main\UI\Extension::load("ui.fonts.ruble");

$imagePath = "http://24.solj.bx/bitrix/components/bitrix/a1/templates/.default/images/";

?>

<style>
	body {
	}
	section {
		max-width:800px;
		margin:0 auto;
		width: 100%;
	}
	main {background-color: #F3F8FA;}
	.darkmode main {background-color: #2c2c36;}
</style>


<div style="position: fixed;padding: 10px;outline: 1px dotted #f00;left: 10px;bottom: 10px;z-index: 10000;">
	<button class="btn" onclick="darkmode()">Сменить цветовую схему</button>
</div>

<section class="g-mb-30 header">
	<div class="container">
		<div class="row justify-content-between align-items-center pt-3 pb-3">
			<div class="col">
				<img
					data-dark-logo="<?=$imagePath?>logo-demo-dark-small.svg"
					data-light-logo="<?=$imagePath?>logo-demo-light-small.svg"
					src="<?=$imagePath?>logo-demo-light-small.svg"
					alt="">
			</div>
			<div class="col-auto header-contact-info">+7 952 114 73 64</div>
		</div>
	</div>
</section>

<section class="mb-2">
	<div class="container">
		<div class="row">
			<div class="col">
				<h1 class="page-title">Информация о заказе</h1>
			</div>
		</div>
	</div>
</section>

<section class="g-mb-35 order">
	<div class="container">
		<div class="row">
			<div class="col">
				<!--region cart-->
				<div class="order-list-container">
					<div class="order-list-title">Заказ №56345, от 22 ноября 2020</div>
					<div class="order-list">

						<div class="order-list-item d-flex justify-content-start align-items-start">
							<div class="col-auto pl-0 pr-0 order-item-image-container">
								<img class="order-item-image" src="http://solj.bx/shop-2015/content/25.jpg" alt="">
							</div>
							<div class="col pr-0 order-item-info">
								<div class="order-item-type">Товар</div>
								<div class="order-item-title">Концерт The Good The Bad and The Queen</div>
								<div class="order-item-quantity">1 шт.</div>
								<div class="order-item-price">
									<span class="order-item-price-old">2 600<span style="font-family: 'rubleBitrix'">руб.</span></span>
									1 200<span style="font-family: 'rubleBitrix'">руб.</span>
								</div>
							</div>
						</div>

						<div class="order-list-item d-flex justify-content-start align-items-start">
							<div class="col-auto pl-0 pr-0 order-item-image-container">
								<img class="order-item-image" src="http://solj.bx/shop-2015/content/27.jpg" alt="">
							</div>
							<div class="col pr-0 order-item-info">
								<div class="order-item-type">Электронный билет</div>
								<div class="order-item-title">Концерт Thelonius Monk</div>
								<div class="order-item-quantity">2 шт.</div>
								<div class="order-item-price">1 200<span style="font-family: 'rubleBitrix'">руб.</span></div>
							</div>
						</div>

					</div>
				</div>
				<!--endregion-->

				<!--region total-->
				<div class="order-total-container">
					<table class="order-total">
						<tr>
							<td class="order-total-item">Товаров на</td>
							<td class="order-total-value">
								<span class="order-total-price-old">2 600<span style="font-family: 'rubleBitrix'">руб.</span></span>
								<span class="order-total-price">1 200<span style="font-family: 'rubleBitrix'">руб.</span></span></td>
						</tr>
						<tr>
							<td class="order-total-item">Экономия</td>
							<td class="order-total-value">
								<span class="order-total-sale-price">1 200<span style="font-family: 'rubleBitrix'">руб.</span></span></td>
						</tr>
						<tr>
							<td class="order-total-item">Доставка</td>
							<td class="order-total-value">Бесплатно</td>
						</tr>
					</table>
					<div class="order-total-result d-flex align-items-center justify-content-between">
						<div class="order-total-result-name">Итого</div>
						<div class="order-total-result-value">3 700<span style="font-family: 'rubleBitrix'">руб.</span></div>
					</div>
				</div>
				<!--endregion-->

				<!--region payment 3-->
				<div class="page-section order-payment-method-container mb-4">
					<div class="page-section-title">Выберите способ оплаты</div>
					<div class="page-section-inner">
						<div class="row align-items-stretch justify-content-start order-payment-method-list">
							<div class="order-payment-method-item" onclick="this.classList.toggle('selected')">
								<div class="order-payment-method-item-block" style="background-image: url(<?=$imagePath?>/ApplePay.svg);"></div>
								<div class="order-payment-method-item-name">Apple Pay</div>
							</div>
							<div class="order-payment-method-item selected" onclick="this.classList.toggle('selected')">
								<div class="order-payment-method-item-block" style="background-image: url(<?=$imagePath?>/VkPay.svg);"></div>
								<div class="order-payment-method-item-name">VK Pay</div>
							</div>
							<div class="order-payment-method-item" onclick="this.classList.toggle('selected')">
								<div class="order-payment-method-item-block" style="background-image: url(<?=$imagePath?>/AlfaBank.svg);"></div>
								<div class="order-payment-method-item-name">Альфа-банк</div>
							</div>
							<div class="order-payment-method-item" onclick="this.classList.toggle('selected')">
								<div class="order-payment-method-item-block" style="background-image: url(<?=$imagePath?>/Cash.svg);"></div>
								<div class="order-payment-method-item-name">Наличные</div>
							</div>
							<div class="order-payment-method-item" onclick="this.classList.toggle('selected')">
								<div class="order-payment-method-item-block" style="background-image: url(<?=$imagePath?>/Sberbank.svg);"></div>
								<div class="order-payment-method-item-name">Сбербанк</div>
							</div>
							<div class="order-payment-method-item" onclick="this.classList.toggle('selected')">
								<div class="order-payment-method-item-block" style="background-image: url(<?=$imagePath?>/SamsungPay.svg);"></div>
								<div class="order-payment-method-item-name">Samsung Pay</div>
							</div>
						</div>
						<hr>
						<div class="order-payment-method-description">
							<div class="order-payment-method-description-title">Apple Pay</div>
							<p>Оплата производится в системе Альфа клик, через сервис Яндекс.Касса.</p>

							<p>Подтверждением вашей оплаты является фискальный кассовый чек, пришедший после оплаты на электронную почту.</p>
						</div>
						<hr>
						<div class="order-payment-buttons-container">
							<button class="landing-block-node-button text-uppercase btn btn-xl u-btn-primary g-font-weight-700 g-font-size-12 g-rounded-50">Оплатить</button>
						</div>

					</div>
				</div>
				<!--endregion -->

				<!--region payment 2-->
				<div class="order-payment-container mb-4">
					<div class="order-payment-title">Платеж № 456797288, от 22 ноября, 16:30</div>
					<div class="order-payment-inner d-flex align-items-center justify-content-between">
						<div class="order-payment-operator">
							<img src="<?=$imagePath?>/AlfaBank.svg" alt="">
						</div>

						<div class="order-payment-price">Сумма: 3 700<span style="font-family: 'rubleBitrix'">руб.</span></div>
					</div>
					<hr>
					<div class="order-payment-buttons-container">
						<button class="landing-block-node-button text-uppercase btn btn-xl u-btn-primary g-font-weight-700 g-font-size-12 g-rounded-50">Оплатить</button>
					</div>
				</div>
				<!--endregion-->

				<!--region payment 1-->
				<div class="order-payment-container mb-4">
					<div class="order-payment-title">Платеж № 456797288, от 22 ноября, 16:30</div>
					<div class="order-payment-inner d-flex align-items-center justify-content-between">
						<div class="order-payment-operator">
							<img src="<?=$imagePath?>/AlfaBank.svg" alt="">
						</div>
						<div class="order-payment-status d-flex align-items-center">
							<div class="order-payment-status-ok"></div>
							<div>Оплачено</div>
						</div>
						<div class="order-payment-price">Сумма: 3 700<span style="font-family: 'rubleBitrix'">руб.</span></div>
					</div>
				</div>
				<!--endregion-->
			</div>
		</div>
	</div>
</section>

<!--region contacts-->
<section class="g-mb-35 order">
	<div class="container">
		<div class="row">
			<div class="col">
				<div class="page-section mb-4">
					<div class="page-section-title">Связаться с нами</div>
					<div class="page-section-inner">

						<div class="d-flex align-items-center justify-content-between">
							<div class="contacts-phone">+ 7 906 876-67-88</div>
							<div class="contacts-phone-button"><a class="btn btn-md text-uppercase u-btn-outline-blue g-font-weight-700 g-font-size-11 g-rounded-50 g-px-25 g-py-8" href="tel:+79068766788">Позвонить</a></div>
						</div>

					</div>
				</div>
				<div class="page-section mb-4">
					<div class="page-section-title">Адрес</div>
					<div class="page-section-inner">
						<p>ул. Академика Анохина, д.6, корп. 8 </p>
						<div class="contacts-maps-container">
							<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3191.5732977144885!2d20.47069735054184!3d54.723648533828396!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x46e33de6b9af2509%3A0x291e9e9baee095b3!2z0J_QsNC80Y_RgtC90LjQuiDQpC7QpC4g0KjQvtC_0LXQvdGD!5e0!3m2!1sru!2sru!4v1551196603043" width="100%" height="300" frameborder="0" style="border:0" allowfullscreen></iframe>
						</div>
						<div class="contacts-maps-button">
							<a class="btn btn-md text-uppercase u-btn-outline-blue g-font-weight-700 g-font-size-11 g-rounded-50 g-px-25 g-py-8" href="tel:+79068766788">Проложить маршрут</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!--endregion-->

<hr style="border: none; border-top: 1px dashed #3e3e3e">

<!--region Appointments-->

<section class="mb-2">
	<div class="container">
		<div class="row">
			<div class="col">
				<h1 class="page-title">Онлайн-запись</h1>
				<div class="page-description">Выберите подходящее вам дату и время посещения и оставьте контактные данные. Мы ждём вас!</div>
			</div>
		</div>
	</div>
</section>

<section class="g-mb-35 order">
	<div class="container">
		<div class="row">
			<div class="col">
				<div class="page-section mb-4">
					<div class="page-section-title">Выберите свободные дату и время</div>
					<div class="page-section-inner">
						<div class="appointments-form-container">
							<div class="appointments-form-days d-flex flex-nowrap">
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">17</div>
									<div class="appointments-form-day-of-week">Пт</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">18</div>
									<div class="appointments-form-day-of-week">Сб</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">19</div>
									<div class="appointments-form-day-of-week">Вс</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">20</div>
									<div class="appointments-form-day-of-week">Пн</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">21</div>
									<div class="appointments-form-day-of-week">Вт</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">22</div>
									<div class="appointments-form-day-of-week">Ср</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">23</div>
									<div class="appointments-form-day-of-week">Чт</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">24</div>
									<div class="appointments-form-day-of-week">Пт</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">25</div>
									<div class="appointments-form-day-of-week">Сб</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">26</div>
									<div class="appointments-form-day-of-week">Вс</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">27</div>
									<div class="appointments-form-day-of-week">Пн</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">28</div>
									<div class="appointments-form-day-of-week">Вт</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">29</div>
									<div class="appointments-form-day-of-week">Ср</div>
								</div>
								<div class="appointments-form-day-block">
									<div class="appointments-form-day-date">30</div>
									<div class="appointments-form-day-of-week">Чт</div>
								</div>
							</div>
							<div class="appointments-form-days-controls">
								<div class="appointments-form-days-control-prev"></div>
								<div class="appointments-form-days-control-next"></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="g-mb-35 order">
	<div class="container">
		<div class="row">
			<div class="col">
				<div class="page-section mb-4">
					<div class="page-section-title">Выберите свободные дату и время</div>
					<div class="page-section-inner">

					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!--endregion-->

<section class="g-mb-35 order">
	<div class="container">
		<div class="row">
			<div class="col">
				<nav class="menu">
					<a href="#" class="menu-item">
						<span class="menu-item-text">Записаться к нам</span>
						<span class="menu-item-arrow"></span>
					</a>
					<a href="#" class="menu-item">
						<span class="menu-item-text">Контакты</span>
						<span class="menu-item-arrow"></span>
					</a>
					<a href="#" class="menu-item">
						<span class="menu-item-text">Про оплату</span>
						<span class="menu-item-arrow"></span>
					</a>
				</nav>
			</div>
		</div>
	</div>
</section>



<script>
	function darkmode()
	{
		document.querySelector('body').classList.toggle('darkmode');

		var mode = document.querySelector('body').classList.contains('darkmode') ? "dark" : "light";

		var smallLogo = document.querySelector('img[data-'+mode+'-logo]');
		smallLogo.setAttribute("src", smallLogo.getAttribute("data-"+mode+"-logo"));

	}
</script>