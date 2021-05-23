<?
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/bootstrap.css');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/font-awesome.css');
?>



<div class="crm-webform-fixed-right-sidebar">
	<div class="crm-webform-cart-container">
		<div class="crm-webform-cart-title-container">
			<h4 class="crm-webform-cart-title">Выбрано товаров:</h4>
		</div>
		<div class="crm-webform-cart-inner">
			<div class="crm-webform-cart-image"></div>
			<div class="crm-webform-cart-goods-container">
				<span class="crm-webform-cart-goods-name">Домашние утепленные Тапочки</span>
				<span class="crm-webform-cart-goods-cost">4444444444450 руб.</span>
			</div><!--crm-webform-cart-goods-container-->
			<div class="crm-webform-cart-services-container">
				<span class="crm-webform-cart-services-name">Доставка</span>
				<span class="crm-webform-cart-services-cost">111111125 руб.</span>
			</div><!--crm-webform-cart-services-container-->
			<div class="crm-webform-cart-services-container">
				<span class="crm-webform-cart-services-name">Праздничная упаковка</span>
				<span class="crm-webform-cart-services-cost">11111125 руб.</span>
			</div><!--crm-webform-cart-services-container-->
			<div class="crm-webform-cart-services-container">
				<span class="crm-webform-cart-services-name">Пластиковая посуда</span>
				<span class="crm-webform-cart-services-cost">111111150 руб.</span>
			</div><!--crm-webform-cart-services-container-->
			<div class="crm-webform-cart-goods-total-price-container">
				<span class="crm-webform-cart-goods-total-price-name">Итого:</span>
				<span class="crm-webform-cart-goods-total-price-cost">650 руб.</span>
			</div><!--crm-webform-cart-goods-total-price-container-->
		</div><!--crm-webform-cart-inner-->
		<div class="crm-webform-cart-button-container">
			<a href="#" class="webform-button webform-button-create crm-webform-cart-button">Отправить</a>
		</div><!--crm-webform-cart-button-container-->
	</div><!--crm-webform-cart-container-->
</div><!--crm-webform-fixed-right-sidebar-->

<div class="crm-webform-wrapper">
	<div class="container crm-webform-main-container">
		<div class="row">
			<div class="col-md-12 col-sm-12">
				<div class="crm-webform-block crm-webform-default">
					<div class="crm-webform-header-container">
						<h2 class="crm-webform-header">Форма обратной связи</h2>
					</div>
					<div class="crm-webform-body" onclick="BX.toggleClass(BX('close'), 'crm-webform-close')">
						<form action="" class="crm-webform-form-container">
							<fieldset id="close" class="crm-webform-fieldset">
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-label-title-container">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label crm-webform-label-required-field">Логин</label>
											</div>
										</div>
										<div class="crm-webform-group">
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-user"></i>
													<input class="crm-webform-input" type="text" name="username" placeholder="">
													<b class="tooltip crm-webform-tooltip-bottom-right">Введите ваш логин</b>
												</label>
											</div>
										</div>
										<div class="crm-webform-add-input-container">
											<a href="#" class="crm-webform-add-input">Еще поле &#10010;</a>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-label-title-container">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label">E-mail</label>
											</div>
										</div>
										<div class="crm-webform-group">
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-envelope-o"></i>
													<input class="crm-webform-input" type="email" name="email" placeholder="">
													<b class="tooltip crm-webform-tooltip-bottom-right">Введите ваш E-mail</b>
												</label>
											</div>
										</div>
										<div class="crm-webform-add-input-container">
											<a href="#" class="crm-webform-add-input">Еще поле &#10010;</a>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label">Пароль</label>
											</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-lock"></i>
													<input class="crm-webform-input" type="text" name="username" placeholder="">
													<b class="tooltip crm-webform-tooltip-bottom-right">Введите пароль</b>
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label">Ваше имя</label>
											</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-user"></i>
													<input class="crm-webform-input" type="text" name="fname" placeholder="">
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label">Ваша фамилия</label>
											</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-user"></i>
													<input class="crm-webform-input" type="text" name="lname" placeholder="">
												</label>
											</div>
										</div>
									</div>
								</div> <!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label">Телефон</label>
											</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-phone"></i>
													<input class="crm-webform-input" type="tel" name="phone" placeholder="">
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label">Ваша дата рождения</label>
											</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-calendar"></i>
													<input class="crm-webform-input" type="date" name="calendar">
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label">Прикрепите файл</label>
											</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label crm-webform-file-upload">
													<span class="crm-webform-file-button">Выбрать</span>
													<mark class="crm-webform-file-text-field">Файл не выбран</mark>
													<input class="crm-webform-input" type="file" name="file">
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
							</fieldset>
							
							<fieldset class="crm-webform-fieldset">
								<div class="crm-webform-inner-header-container">
									<h2 class="crm-webform-inner-header">Покупка</h2>
								</div>
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-level-down" aria-hidden="true"></i>
													<select name="buying" class="crm-webform-form-section crm-webform-input">
														<option value="0" selected="" disabled="">Продукт</option>
														<option value="244">Говядиина</option>
														<option value="1">Айфон</option>
														<option value="2">Чулки</option>
														<option value="3">Брбрбр</option>
													</select>
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<textarea class="crm-webform-input" rows="3" name="info" placeholder="Комментарий"></textarea>
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">Ваши увлечения</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-level-down" aria-hidden="true"></i>
													<select name="interests" class="crm-webform-form-section crm-webform-input">
														<option value="244">Говядиина</option>
														<option value="1">Айфон</option>
														<option value="2">Чулки</option>
														<option value="3">Брбрбр</option>
													</select>
													<i></i>
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-content">
												<div class="crm-webform-label-title">
													<label class="crm-webform-label">Дополнения к продуктам</label>
												</div>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-products">
													<input class="crm-webform-checkbox crm-webform-input" type="checkbox" name="subscription">
													<i></i><span class="crm-webform-checkbox-name">Доставка</span>
												</label>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-products">
													<input class="crm-webform-checkbox crm-webform-input" type="checkbox" name="subscription">
													<i></i><span class="crm-webform-checkbox-name">Праздичная упаковка</span>
												</label>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-products">
													<input class="crm-webform-checkbox crm-webform-input" type="checkbox" name="subscription">
													<i></i><span class="crm-webform-checkbox-name">Пластиковая посуда</span>
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-content">
												<div class="crm-webform-label-title">
													<label class="crm-webform-label">Пол</label>
												</div>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-radio">
													<input class="crm-webform-checkbox crm-webform-input-radio" type="radio" name="subscription">
													<i></i>
													<span class="crm-webform-checkbox-name">Мужчина</span>
												</label>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-radio">
													<input class="crm-webform-checkbox crm-webform-input-radio" type="radio" name="subscription">
													<i></i>
													<span class="crm-webform-checkbox-name">Женщина</span>
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-fill-br">br</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-fill-separator"></div>
									</div>
								</div><!--row-->
							</fieldset>
<!--							<fieldset class="crm-webform-fieldset-footer">-->
<!--								<div class="row">-->
<!--									<div class="col-md-7 col-sm-7">-->
<!--										<div class="crm-webform-group">-->
<!--											<div class="crm-webform-label-content">-->
<!--												<label class="crm-webform-checkbox-container">-->
<!--													<input class="crm-webform-checkbox crm-webform-input" type="checkbox" name="subscription" id="Подписаться">-->
<!--													<i></i> Я подтверждаю, что ознакомился с <a href="#">условиями и правилами</a> сайта.-->
<!--												</label>-->
<!--											</div>-->
<!--										</div>-->
<!--									</div>-->
<!--									<div class="col-md-5 col-sm-5">-->
<!--										<div class="crm-webform-group crm-webform-button-container">-->
<!--											<button class="webform-button webform-button-create crm-webform-submit-button" type="submit">Зарегистрироваться</button>-->
<!--										</div>-->
<!--									</div>-->
<!--								</div><!--row-->
<!--							</fieldset>-->

							<div class="crm-webform-mini-cart-container">
								<div class="crm-webform-mini-cart-title-container">
									<h4 class="crm-webform-mini-cart-title">Выбрано товаров:</h4>
								</div><!--crm-webform-mini-cart-title-container-->
								<div class="crm-webform-mini-cart-inner">
									<div class="crm-webform-mini-cart-goods-container">
										<span class="crm-webform-mini-cart-goods-name">Домашние утепленные Тапочки</span>
										<span class="crm-webform-mini-cart-goods-cost">4444444444450 руб.</span>
									</div><!--crm-webform-mini-cart-goods-container-->
									<div class="crm-webform-mini-cart-services-container">
										<span class="crm-webform-mini-cart-services-name">Доставка</span><!--crm-webform-mini-cart-services-name-->
										<span class="crm-webform-mini-cart-services-cost">111111125 руб.</span><!--crm-webform-mini-cart-services-cost-->
									</div><!--crm-webform-mini-cart-services-container-->
									<div class="crm-webform-mini-cart-services-container">
										<span class="crm-webform-mini-cart-services-name">Праздничная упаковка</span><!--crm-webform-mini-cart-services-name-->
										<span class="crm-webform-mini-cart-services-cost">11111125 руб.</span><!--crm-webform-mini-cart-services-cost-->
									</div><!--crm-webform-mini-cart-services-container-->
									<div class="crm-webform-mini-cart-services-container">
										<span class="crm-webform-mini-cart-services-name">Пластиковая посуда</span><!--crm-webform-mini-cart-services-name-->
										<span class="crm-webform-mini-cart-services-cost">111111150 руб.</span><!--crm-webform-mini-cart-services-cost-->
									</div><!--crm-webform-mini-cart-services-container-->
									<div class="crm-webform-mini-cart-services-container">
										<span class="crm-webform-mini-cart-services-name">Итого:</span><!--crm-webform-mini-cart-services-name-->
										<span class="crm-webform-mini-cart-services-cost">650 руб.</span><!--crm-webform-mini-cart-services-cost-->
									</div><!--crm-webform-mini-cart-services-container-->
								</div><!--crm-webform-mini-cart-inner-->
							</div><!--crm-webform-mini-cart-container-->

							<fieldset class="crm-webform-fieldset-footer">
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group crm-webform-button-container">
											<button style="background: tomato;" class="crm-webform-submit-button crm-webform-submit-button-loader crm-webform-submit-button-loader-customize" type="submit">Отправить</button>
										</div>
									</div>
								</div><!--row-->
							</fieldset>
						</form>
					</div>
				</div>
			</div>
		</div><!--row-->
		<div class="row">
			<div class="col-md-12 col-sm-12 crm-webform-bottom-logo-container">
				<a class="crm-webform-bottom-link" href="https://www.bitrix24.ru/?c=192.168.1.138" target="_blank">
					<span class="crm-webform-bottom-text">Работает на</span>
					<span class="crm-webform-bottom-image"></span>
				</a>
			</div>
		</div><!--row-->
	</div><!--container-->
</div><!--crm-webform-wrapper-->


<div class="crm-webform-popup-mask">
	<div class="crm-webform-popup-container">
		<div class="crm-webform-popup-content">
			<div class="crm-webform-popup-success">Поздравляем!</div>
			<div class="crm-webform-popup-text crm-webform-popup-content crm-webform-popup-content-loader">Операция прошла успешно!</div>
		</div><!--crm-webform-popup-content-->
		<div class="crm-webform-popup-button">
			<a class="webform-button webform-button-create" href="#">Вернуться в форму</a>
		</div><!--crm-webform-popup-button-->
	</div><!--crm-webform-popup-container-->
</div><!--crm-webform-popup-mask-->