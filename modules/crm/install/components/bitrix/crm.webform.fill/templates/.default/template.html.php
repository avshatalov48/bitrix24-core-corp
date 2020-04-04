<?
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/bootstrap.css');
\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/font-awesome.css');
?>



<div class="crm-webform-fixed-right-sidebar">
	<div class="crm-webform-cart-container">
		<div class="crm-webform-cart-title-container">
			<h4 class="crm-webform-cart-title">������� �������:</h4>
		</div>
		<div class="crm-webform-cart-inner">
			<div class="crm-webform-cart-image"></div>
			<div class="crm-webform-cart-goods-container">
				<span class="crm-webform-cart-goods-name">�������� ���������� �������</span>
				<span class="crm-webform-cart-goods-cost">4444444444450 ���.</span>
			</div><!--crm-webform-cart-goods-container-->
			<div class="crm-webform-cart-services-container">
				<span class="crm-webform-cart-services-name">��������</span>
				<span class="crm-webform-cart-services-cost">111111125 ���.</span>
			</div><!--crm-webform-cart-services-container-->
			<div class="crm-webform-cart-services-container">
				<span class="crm-webform-cart-services-name">����������� ��������</span>
				<span class="crm-webform-cart-services-cost">11111125 ���.</span>
			</div><!--crm-webform-cart-services-container-->
			<div class="crm-webform-cart-services-container">
				<span class="crm-webform-cart-services-name">����������� ������</span>
				<span class="crm-webform-cart-services-cost">111111150 ���.</span>
			</div><!--crm-webform-cart-services-container-->
			<div class="crm-webform-cart-goods-total-price-container">
				<span class="crm-webform-cart-goods-total-price-name">�����:</span>
				<span class="crm-webform-cart-goods-total-price-cost">650 ���.</span>
			</div><!--crm-webform-cart-goods-total-price-container-->
		</div><!--crm-webform-cart-inner-->
		<div class="crm-webform-cart-button-container">
			<a href="#" class="webform-button webform-button-create crm-webform-cart-button">���������</a>
		</div><!--crm-webform-cart-button-container-->
	</div><!--crm-webform-cart-container-->
</div><!--crm-webform-fixed-right-sidebar-->

<div class="crm-webform-wrapper">
	<div class="container crm-webform-main-container">
		<div class="row">
			<div class="col-md-12 col-sm-12">
				<div class="crm-webform-block crm-webform-default">
					<div class="crm-webform-header-container">
						<h2 class="crm-webform-header">����� �������� �����</h2>
					</div>
					<div class="crm-webform-body" onclick="BX.toggleClass(BX('close'), 'crm-webform-close')">
						<form action="" class="crm-webform-form-container">
							<fieldset id="close" class="crm-webform-fieldset">
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-label-title-container">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label crm-webform-label-required-field">�����</label>
											</div>
										</div>
										<div class="crm-webform-group">
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-user"></i>
													<input class="crm-webform-input" type="text" name="username" placeholder="">
													<b class="tooltip crm-webform-tooltip-bottom-right">������� ��� �����</b>
												</label>
											</div>
										</div>
										<div class="crm-webform-add-input-container">
											<a href="#" class="crm-webform-add-input">��� ���� &#10010;</a>
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
													<b class="tooltip crm-webform-tooltip-bottom-right">������� ��� E-mail</b>
												</label>
											</div>
										</div>
										<div class="crm-webform-add-input-container">
											<a href="#" class="crm-webform-add-input">��� ���� &#10010;</a>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label">������</label>
											</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-lock"></i>
													<input class="crm-webform-input" type="text" name="username" placeholder="">
													<b class="tooltip crm-webform-tooltip-bottom-right">������� ������</b>
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">
												<label class="crm-webform-label">���� ���</label>
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
												<label class="crm-webform-label">���� �������</label>
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
												<label class="crm-webform-label">�������</label>
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
												<label class="crm-webform-label">���� ���� ��������</label>
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
												<label class="crm-webform-label">���������� ����</label>
											</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label crm-webform-file-upload">
													<span class="crm-webform-file-button">�������</span>
													<mark class="crm-webform-file-text-field">���� �� ������</mark>
													<input class="crm-webform-input" type="file" name="file">
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
							</fieldset>
							
							<fieldset class="crm-webform-fieldset">
								<div class="crm-webform-inner-header-container">
									<h2 class="crm-webform-inner-header">�������</h2>
								</div>
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-level-down" aria-hidden="true"></i>
													<select name="buying" class="crm-webform-form-section crm-webform-input">
														<option value="0" selected="" disabled="">�������</option>
														<option value="244">���������</option>
														<option value="1">�����</option>
														<option value="2">�����</option>
														<option value="3">������</option>
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
													<textarea class="crm-webform-input" rows="3" name="info" placeholder="�����������"></textarea>
												</label>
											</div>
										</div>
									</div>
								</div><!--row-->
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group">
											<div class="crm-webform-label-title">���� ���������</div>
											<div class="crm-webform-label-content">
												<label class="crm-webform-input-label">
													<i class="crm-webform-icon fa fa-level-down" aria-hidden="true"></i>
													<select name="interests" class="crm-webform-form-section crm-webform-input">
														<option value="244">���������</option>
														<option value="1">�����</option>
														<option value="2">�����</option>
														<option value="3">������</option>
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
													<label class="crm-webform-label">���������� � ���������</label>
												</div>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-products">
													<input class="crm-webform-checkbox crm-webform-input" type="checkbox" name="subscription">
													<i></i><span class="crm-webform-checkbox-name">��������</span>
												</label>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-products">
													<input class="crm-webform-checkbox crm-webform-input" type="checkbox" name="subscription">
													<i></i><span class="crm-webform-checkbox-name">���������� ��������</span>
												</label>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-products">
													<input class="crm-webform-checkbox crm-webform-input" type="checkbox" name="subscription">
													<i></i><span class="crm-webform-checkbox-name">����������� ������</span>
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
													<label class="crm-webform-label">���</label>
												</div>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-radio">
													<input class="crm-webform-checkbox crm-webform-input-radio" type="radio" name="subscription">
													<i></i>
													<span class="crm-webform-checkbox-name">�������</span>
												</label>
												<label class="crm-webform-checkbox-container crm-webform-checkbox-radio">
													<input class="crm-webform-checkbox crm-webform-input-radio" type="radio" name="subscription">
													<i></i>
													<span class="crm-webform-checkbox-name">�������</span>
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
<!--													<input class="crm-webform-checkbox crm-webform-input" type="checkbox" name="subscription" id="�����������">-->
<!--													<i></i> � �����������, ��� ����������� � <a href="#">��������� � ���������</a> �����.-->
<!--												</label>-->
<!--											</div>-->
<!--										</div>-->
<!--									</div>-->
<!--									<div class="col-md-5 col-sm-5">-->
<!--										<div class="crm-webform-group crm-webform-button-container">-->
<!--											<button class="webform-button webform-button-create crm-webform-submit-button" type="submit">������������������</button>-->
<!--										</div>-->
<!--									</div>-->
<!--								</div><!--row-->
<!--							</fieldset>-->

							<div class="crm-webform-mini-cart-container">
								<div class="crm-webform-mini-cart-title-container">
									<h4 class="crm-webform-mini-cart-title">������� �������:</h4>
								</div><!--crm-webform-mini-cart-title-container-->
								<div class="crm-webform-mini-cart-inner">
									<div class="crm-webform-mini-cart-goods-container">
										<span class="crm-webform-mini-cart-goods-name">�������� ���������� �������</span>
										<span class="crm-webform-mini-cart-goods-cost">4444444444450 ���.</span>
									</div><!--crm-webform-mini-cart-goods-container-->
									<div class="crm-webform-mini-cart-services-container">
										<span class="crm-webform-mini-cart-services-name">��������</span><!--crm-webform-mini-cart-services-name-->
										<span class="crm-webform-mini-cart-services-cost">111111125 ���.</span><!--crm-webform-mini-cart-services-cost-->
									</div><!--crm-webform-mini-cart-services-container-->
									<div class="crm-webform-mini-cart-services-container">
										<span class="crm-webform-mini-cart-services-name">����������� ��������</span><!--crm-webform-mini-cart-services-name-->
										<span class="crm-webform-mini-cart-services-cost">11111125 ���.</span><!--crm-webform-mini-cart-services-cost-->
									</div><!--crm-webform-mini-cart-services-container-->
									<div class="crm-webform-mini-cart-services-container">
										<span class="crm-webform-mini-cart-services-name">����������� ������</span><!--crm-webform-mini-cart-services-name-->
										<span class="crm-webform-mini-cart-services-cost">111111150 ���.</span><!--crm-webform-mini-cart-services-cost-->
									</div><!--crm-webform-mini-cart-services-container-->
									<div class="crm-webform-mini-cart-services-container">
										<span class="crm-webform-mini-cart-services-name">�����:</span><!--crm-webform-mini-cart-services-name-->
										<span class="crm-webform-mini-cart-services-cost">650 ���.</span><!--crm-webform-mini-cart-services-cost-->
									</div><!--crm-webform-mini-cart-services-container-->
								</div><!--crm-webform-mini-cart-inner-->
							</div><!--crm-webform-mini-cart-container-->

							<fieldset class="crm-webform-fieldset-footer">
								<div class="row">
									<div class="col-md-12 col-sm-12">
										<div class="crm-webform-group crm-webform-button-container">
											<button style="background: tomato;" class="crm-webform-submit-button crm-webform-submit-button-loader crm-webform-submit-button-loader-customize" type="submit">���������</button>
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
					<span class="crm-webform-bottom-text">�������� ��</span>
					<span class="crm-webform-bottom-image"></span>
				</a>
			</div>
		</div><!--row-->
	</div><!--container-->
</div><!--crm-webform-wrapper-->


<div class="crm-webform-popup-mask">
	<div class="crm-webform-popup-container">
		<div class="crm-webform-popup-content">
			<div class="crm-webform-popup-success">�����������!</div>
			<div class="crm-webform-popup-text crm-webform-popup-content crm-webform-popup-content-loader">�������� ������ �������!</div>
		</div><!--crm-webform-popup-content-->
		<div class="crm-webform-popup-button">
			<a class="webform-button webform-button-create" href="#">��������� � �����</a>
		</div><!--crm-webform-popup-button-->
	</div><!--crm-webform-popup-container-->
</div><!--crm-webform-popup-mask-->