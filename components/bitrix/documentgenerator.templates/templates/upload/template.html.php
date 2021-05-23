<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
\Bitrix\Main\UI\Extension::load("ui.buttons");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\CJSCore::init("sidepanel");
?>

<?
if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y")
{
$APPLICATION->RestartBuffer(); //���������� ���� �����
?>

<!DOCTYPE html>
<html>
	<head>
		<?$APPLICATION->ShowHead(); ?>
	</head>
	<body>
		<div class="docs-template-load-slider">
			<div class="docs-template-load-subject">
				<div class="pagetitle-wrap">
					<div class="pagetitle-inner-container">
						<div class="pagetitle">
							<span id="pagetitle" class="pagetitle-item docs-preview-pagetitle-item">��������� ������</span>
						</div>
					</div>
				</div>
			</div>
			<div class="docs-template-load-wrap docs-template-load-wrap-slider">
				<form action="">
					<div class="docs-template-load-drag">
						<div class="docs-template-load-title-inner">
							<span class="docs-template-load-title">��������� ���� �������</span>
						</div>
						<div class="docs-template-load-drag-zone">
							<div class="docs-template-load-drag-inner">
								<span class="docs-template-load-drag-text">���������� ���� ��� <span class="docs-template-load-drag-line">������� �� ����������</span></span>
							</div>
						</div>
						<div class="docs-template-load-notice">
							<span class="docs-template-load-notice-text">������ ������ ��� ������� ���������� - doc, docx</span>
						</div>
					</div>
					<div class="docs-template-load-crm">
						<div class="docs-template-load-block-wrap">
							<div class="docs-template-load-block-title-inner">
								<span class="docs-template-load-title">��������� � �������� CRM</span>
							</div>
							<div class="docs-template-load-block-section">
								<span class="docs-template-load-block-search"></span>
								<span class="docs-template-load-block-item">
							<span class="docs-template-load-block-section-inner">
								<span class="docs-template-load-block-section-name">������(������ ���)</span>
								<input value="deal" type="hidden">
								<span class="docs-template-load-block-delete"></span>
							</span>
						</span>
								<span class="docs-template-load-block-arrow">
							<span class="docs-template-load-block-arrow-icon"></span>
						</span>
							</div>
						</div>
					</div>
					<div class="docs-template-load-user">
						<div class="docs-template-load-block-wrap">
							<div class="docs-template-load-block-title-inner">
								<span class="docs-template-load-title">��� ����� �������� � ����������</span>
							</div>
							<div class="docs-template-load-block-section">
						<span class="docs-template-load-block-item docs-template-load-block-item-user">
							<span class="docs-template-load-block-section-inner">
								<span class="docs-template-load-block-section-name">��������� ����������</span>
								<input value="deal" type="hidden">
								<span class="docs-template-load-block-delete"></span>
							</span>
						</span>
								<a href="#" class="docs-template-load-block-link">�������� ���</a>
							</div>
						</div>
					</div>
					<div class="docs-template-load-buttons docs-template-load-buttons-slider">
						<div class="docs-template-load-buttons-inner">
							<button class="ui-btn ui-btn-md ui-btn-success">���������</button>
							<button class="ui-btn ui-btn-md ui-btn-light docs-template-load-btn-cancel">��������</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</body>
</html><?
}
else
{ ?>
	<? $APPLICATION->SetTitle("��������� ������"); ?>
	<div class="docs-template-load-wrap">
		<form action="">
			<div class="docs-template-load-drag">
				<div class="docs-template-load-title-inner">
					<span class="docs-template-load-title">��������� ���� �������</span>
				</div>
				<div class="docs-template-load-drag-zone">
					<div class="docs-template-load-drag-inner">
						<span class="docs-template-load-drag-text">���������� ���� ��� <span class="docs-template-load-drag-line">������� �� ����������</span></span>
					</div>
				</div>
				<div class="docs-template-load-notice">
					<span class="docs-template-load-notice-text">������ ������ ��� ������� ���������� - doc, docx</span>
				</div>
			</div>
			<div class="docs-template-load-crm">
				<div class="docs-template-load-block-wrap">
					<div class="docs-template-load-block-title-inner">
						<span class="docs-template-load-title">��������� � �������� CRM</span>
					</div>
					<div class="docs-template-load-block-section">
						<span class="docs-template-load-block-search"></span>
						<span class="docs-template-load-block-item">
							<span class="docs-template-load-block-section-inner">
								<span class="docs-template-load-block-section-name">������(������ ���)</span>
								<input value="deal" type="hidden">
								<span class="docs-template-load-block-delete"></span>
							</span>
						</span>
						<span class="docs-template-load-block-arrow">
							<span class="docs-template-load-block-arrow-icon"></span>
						</span>
					</div>
				</div>
			</div>
			<div class="docs-template-load-user">
				<div class="docs-template-load-block-wrap">
					<div class="docs-template-load-block-title-inner">
						<span class="docs-template-load-title">��� ����� �������� � ����������</span>
					</div>
					<div class="docs-template-load-block-section">
						<span class="docs-template-load-block-item docs-template-load-block-item-user">
							<span class="docs-template-load-block-section-inner">
								<span class="docs-template-load-block-section-name">��������� ����������</span>
								<input value="deal" type="hidden">
								<span class="docs-template-load-block-delete"></span>
							</span>
						</span>
						<a href="#" class="docs-template-load-block-link">�������� ���</a>
					</div>
				</div>
			</div>
			<div class="docs-template-load-buttons">
				<div class="docs-template-load-buttons-inner">
					<button class="ui-btn ui-btn-md ui-btn-success">���������</button>
					<button class="ui-btn ui-btn-md ui-btn-light docs-template-load-btn-cancel">��������</button>
				</div>
			</div>
		</form>
	</div>
<? } ?>


