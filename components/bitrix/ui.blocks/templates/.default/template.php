<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load("ui.fonts.opensans");
\Bitrix\Main\UI\Extension::load("ui.forms");

?>

<div class="ui-block-wrapper">
	<div class="ui-block-title">
		<div class="ui-block-title-text">���������� ����������</div>
		<div class="ui-block-title-actions">
			<a href="" class="ui-block-title-actions-link">��������</a>
		</div>
	</div>
	<div class="ui-block-content">
		<div class="ui-block-field-container">
			<div class="ui-block-field-title">���</div>
			<div class="ui-block-field-content">���������� ����������� ����������</div>
		</div>
		<div class="ui-block-field-container">
			<div class="ui-block-field-title">���������</div>
			<div class="ui-block-field-content">�������� �� ����������</div>
		</div>
		<div class="ui-block-field-container">
			<div class="ui-block-field-title">�������������</div>
			<div class="ui-block-field-content">��� ��������</div>
		</div>
	</div>
	<div class="ui-block-content-actions">
		<a class="ui-block-content-actions-link" href="">������� ����</a>
		<a class="ui-block-content-actions-link" href="">������� ����</a>
	</div>
</div>




<div class="ui-block-wrapper">
	<div class="ui-block-title">
		<div class="ui-block-title-text">���������� ����������</div>
		<div class="ui-block-title-actions">
			<a href="" class="ui-block-title-actions-link">��������</a>
		</div>
	</div>
	<div class="ui-block-content">
		<div class="ui-block-field-container">
			<label for="" class="ui-block-field-title">input[type=text]</label>
			<div class="ui-block-field-editor">
				<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
					<input type="text" class="ui-ctl-element">
				</div>
			</div>
		</div>
		<div class="ui-block-field-container">
			<label for="" class="ui-block-field-title">select (custom div)</label>
			<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
				<div class="ui-ctl-after ui-ctl-icon-angle"></div>
				<div class="ui-ctl-element"> ��������� ����� </div>
			</div>
		</div>
		<div class="ui-block-field-container">
			<label for="" class="ui-block-field-title">Select</label>
			<div class="ui-block-field-editor">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<select class="ui-ctl-element">
						<option value="">����� #1</option>
						<option value="">����� #2</option>
						<option value="">����� #3</option>
					</select>
				</div>
			</div>
		</div>
		<div class="ui-block-field-container">
			<label for="" class="ui-block-field-title">Input-Select</label>
			<div class="ui-block-field-editor">
				<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100">
					<div class="ui-ctl-after ui-ctl-icon-angle"></div>
					<input type="text" class="ui-ctl-element">
				</div>
			</div>
		</div>
		<div class="ui-block-field-container">
			<label for="" class="ui-block-field-title">Select[multiple]</label>
			<div class="ui-block-field-editor">
				<div class="ui-ctl ui-ctl-multiple-select ui-ctl-w100  ui-ctl-lg">
					<select class="ui-ctl-element" multiple size="3">
						<option value="">����� #1</option>
						<option value="">����� #2</option>
						<option value="">����� #3442</option>
						<option value="">����� #5434</option>
						<option value="">����� #6433</option>
					</select>
				</div>
			</div>
		</div>
		<div class="ui-block-field-container">
			<label for="" class="ui-block-field-title">Select</label>
			<div class="ui-block-field-editor">
				<div class="ui-ctl ui-ctl-textarea ui-ctl-no-resize ui-ctl-w100">
					<textarea class="ui-ctl-element"></textarea>
				</div>
			</div>
		</div>
	</div>
	<div class="ui-block-content-actions">
		<a class="ui-block-content-actions-link" href="">������� ����</a>
		<a class="ui-block-content-actions-link" href="">������� ����</a>
	</div>
</div>