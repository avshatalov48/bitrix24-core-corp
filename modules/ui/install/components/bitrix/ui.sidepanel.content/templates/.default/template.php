<?php
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-background');
\Bitrix\Main\UI\Extension::load(['ui', 'ui.sidepanel-content']);
?>
<div class="ui-slider-section">
	<div class="ui-slider-content-box">
		<div class="ui-slider-heading-2">��������� ������ ������ ��������. Heading 3</div>
		<p class="ui-slider-paragraph">Paragraph 1. ����� ��������. ������ ��� �������� �������� ������������� �������� � ����������� ��������. �� ������ �������� ����������� �� ������� �������, ��������� ���������, �������� ����� � �������</p>
	</div>
	<div class="ui-slider-content-box">
		<div class="ui-slider-heading-4">��������� ������� ��������. Heading 4</div>
		<p class="ui-slider-paragraph-2">Paragraph 2. ����� ��������. ������ ��� �������� �������� ������������� �������� � ����������� ��������. �� ������ �������� ����������� �� ������� �������, ��������� ���������, �������� ����� � �������</p>
	</div>
</div>
<div class="ui-slider-section">
	<div class="ui-slider-content-box">
		<div class="ui-slider-heading-4">���������. Heading 4</div>
		<p class="ui-slider-paragraph-2">Paragraph 2. ����� �����. ������ ��� �������� �������� ������������� �������� � ����������� ��������. ����� ��������. ������ ��� �������� �������� ������������� �������� � ����������� ��������. �� ������ �������� ����������� �� ������� �������, ��������� ���������, �������� ����� � ������� ������ ��� �������� �������� ������������� �������� � ����������� ��������.</p>
		<p class="ui-slider-paragraph-2">����� ��������. ������ ��� �������� �������� ������������� �������� � ����������� ��������. �� ������ �������� ����������� �� ������� �������, ��������� ���������, �������� ����� � ������� ������ ��� �������� �������� ������������� �������� � ����������� ��������. ����� ��������. ������ ��� �������� �������� ������������� �������� � ����������� ��������. �� ������ �������� ����������� �� ������� �������, ��������� ���������, �������� ����� � �������</p>
	</div>
</div>
<div class="ui-slider-section ui-slider-section-icon">
	<span class="ui-icon ui-slider-icon"><i></i></span>
	<div class="ui-slider-text-box">
		<div class="ui-slider-heading-3">���������� ���� ����� ��������</div>
		<div class="ui-slider-inner-box">
			<p class="ui-slider-paragraph-2">��� ����������� ���������� ������� ��������� ������� � Viber ��� ���������� ��� ������������. ���� � ��� ��� ��� ���������� ��������, �� ������� ������� ��� � ��������� ����� � ���������� � ������ �������24</p>
			<a href="#" class="ui-slider-link">��������� � �����������</a>
		</div>
	</div>
</div>
<div class="ui-slider-section">
	<div class="ui-slider-heading-4">���������. Heading 4</div>
	<ul class="ui-slider-list">
		<li class="ui-slider-list-item">
			<span class="ui-slider-list-number">1</span>
			<span class="ui-slider-list-text">������ ������ ��� ����� � ���</span>
		</li>
		<li class="ui-slider-list-item">
			<span class="ui-slider-list-number">2</span>
			<span class="ui-slider-list-text">��� �������� ����������� � ���, ��� ����� �������</span>
		</li>
	</ul>
</div>

<div class="ui-slider-no-access">
	<div class="ui-slider-no-access-inner">
		<div class="ui-slider-no-access-title">������ �� ������� ��� ������ ��������</div>
		<div class="ui-slider-no-access-subtitle">���������� � ���������� ������ ��� �������������� �������</div>
		<div class="ui-slider-no-access-img">
			<div class="ui-slider-no-access-img-inner"></div>
		</div>
	</div>
</div>

<form action="/company/detail/1/">
	<?$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
		'BUTTONS' => ['save', 'cancel' => '/company/list/']
	]);?>
</form>
