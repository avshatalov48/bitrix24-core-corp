<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?

CJSCore::Init(array('finder'));

$this->setFrameMode(true);

$inputId = trim($arParams["~INPUT_ID"]);
if($inputId == '')
	$inputId = "title-search-input";
$inputId = CUtil::JSEscape($inputId);

$containerId = trim($arParams["~CONTAINER_ID"]);
if($containerId == '')
{
	$containerId = "title-search";
}
$containerId = CUtil::JSEscape($containerId);

$className =
	!isModuleInstalled("timeman") || (CModule::IncludeModule("bitrix24") && SITE_ID == "ex") ? " timeman-simple" : "";
?>

<div class="header-search<?=$className?>" >
	<div class="header-search-inner">
		<form class="header-search-form" method="get" name="search-form" action="<?=$arResult["FORM_ACTION"]?>" id="<?=$containerId?>">
			<input
				class="header-search-input" name="q" id="<?=$inputId?>" type="text" autocomplete="off"
				placeholder = "<?=GetMessage("CT_BST_SEARCH_HINT")?>"
				onclick="BX.addClass(this.parentNode.parentNode.parentNode,'header-search-active')"
				onblur="BX.removeClass(this.parentNode.parentNode.parentNode, 'header-search-active')"
			/>
			<span class="header-search-icon header-search-icon-title" onclick="document.forms['search-form'].submit();"></span>
			<span class="search-title-top-delete"></span>
		</form>
	</div>
</div>


<!--������� ������ ������-->
<div class="title-search-result title-search-result-header search-title-top-result-header" style="position: absolute; top: 54px; left: 285px; width: 575px; display: none!important;">
	<div class="search-title-top-result">
		<div class="search-title-top-nothing">
			<div class="search-title-top-nothing-icon"></div>
			<div class="search-title-top-nothing-text-block">
				<span class="search-title-top-nothing-text">������ �� �������</span>
			</div>
		</div>
		<!--<div class="search-title-top-block search-title-top-block-users">
			<div class="search-title-top-subtitle">
				<span class="search-title-top-subtitle-text">����������</span>
			</div>
			<div class="search-title-top-list-wrap">
				<div class="search-title-top-list">
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="/company/personal/user/99/">
							<span style="background-image: url('/upload/resize_cache/main/3eb/100_100_2/9b.jpg')" class="search-title-top-item-img search-title-top-item-img-users"></span>
							<span class="search-title-top-item-text">
								<span>�����_�������_��������_������_��_����������_�������_��������_������_��_�����</span>
							</span>
						</a>
						<span class="search-title-top-item-message"></span>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="/company/personal/user/99/">
							<span style="background-image: url('/upload/resize_cache/main/3eb/100_100_2/9b.jpg')" class="search-title-top-item-img search-title-top-item-img-users"></span>
							<span class="search-title-top-item-text">
								<span>����������� ������</span>
							</span>
						</a>
						<span class="search-title-top-item-message"></span>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="/company/personal/user/99/">
							<span style="background-image: url('/upload/resize_cache/main/3eb/100_100_2/9b.jpg')" class="search-title-top-item-img search-title-top-item-img-users"></span>
							<span class="search-title-top-item-text">
								<span>��������� ������</span>
							</span>
						</a>
						<span class="search-title-top-item-message"></span>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="/company/personal/user/99/">
							<span style="background-image: url('/upload/resize_cache/main/3eb/100_100_2/9b.jpg')" class="search-title-top-item-img search-title-top-item-img-users"></span>
							<span class="search-title-top-item-text">
								<span>������� ������</span>
							</span>
						</a>
						<span class="search-title-top-item-message"></span>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="/company/personal/user/99/">
							<span style="background-image: url('/upload/resize_cache/main/3eb/100_100_2/9b.jpg')" class="search-title-top-item-img search-title-top-item-img-users"></span>
							<span class="search-title-top-item-text">
								<span>������� ������</span>
							</span>
						</a>
						<span class="search-title-top-item-message"></span>
					</div>
				</div>
			</div>
			<div class="search-title-top-more">
				<span class="search-title-top-more-text">��� 5 �����������</span>
			</div>
		</div>
		<div class="search-title-top-block search-title-top-block-sonetgroups">
			<div class="search-title-top-subtitle">
				<span class="search-title-top-subtitle-text">������</span>
			</div>
			<div class="search-title-top-list-wrap">
				<div class="search-title-top-list">
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="/company/personal/user/99/">
							<span style="background-image: url('/upload/resize_cache/main/3eb/100_100_2/9b.jpg')" class="search-title-top-item-img search-title-top-item-img-users"></span>
							<span class="search-title-top-item-text">��������� ������������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="/company/personal/user/99/">
							<span style="background-image: url('/upload/resize_cache/main/3eb/100_100_2/9b.jpg')" class="search-title-top-item-img search-title-top-item-img-users"></span>
							<span class="search-title-top-item-text">������� � ��������</span>
						</a>
					</div>
				</div>
			</div>
		</div>
		<div class="search-title-top-block search-title-top-block-section">
			<div class="search-title-top-subtitle">
				<span class="search-title-top-subtitle-text">�������</span>
			</div>
			<div class="search-title-top-list-wrap">
				<div class="search-title-top-list">
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">�������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">������� ��������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">�������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">������� ��������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">�������</span>
						</a>
					</div>
				</div>
			</div>
		</div>
		<div class="search-title-top-block search-title-top-block-tools">
			<div class="search-title-top-subtitle">
				<span class="search-title-top-subtitle-text">������ �</span>
			</div>
			<div class="search-title-top-list-wrap">
				<div class="search-title-top-list">
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">����</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">�������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">����</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">����� �����</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">�������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">����</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">��������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">���</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">�����</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">������</span>
						</a>
					</div>
					<div class="search-title-top-item">
						<a class="search-title-top-item-link" href="#">
							<span class="search-title-top-item-text">���������</span>
						</a>
					</div>
				</div>
				<div class="search-title-top-arrow"></div>
			</div>
		</div>
		<div class="search-title-top-block search-title-top-block-limits">
			<div class="search-title-top-subtitle">
				<span class="search-title-top-subtitle-text">CRM: ������</span>
			</div>
			<div class="search-title-top-list-wrap">
				<div class="search-title-top-list">
					<div class="search-title-top-list-limits">
						<div class="search-title-top-list-limits-block">
							<span class="search-title-top-list-limits-icon"></span>
						</div>
						<div class="search-title-top-list-limits-block">
							<div class="search-title-top-list-limits-name">��������� ����� � 5000 �����</div>
							<div class="search-title-top-list-limits-content">
								<p>�� ��������� ��������� �������� �������� ������ � �������� ������������ � ������������ � ����� ��������� �������. ������ ��������, ������ ����� ������ (����� �����, ������ ��� ������ ���������) � ���������� ������������� �������24.</p>
								<p>��� ������ ������� ������ �������, ��� �� �������� ������ ���������� ���������� ������, � �������� �������� ������� �� �����. ����� ��������� ����� ������� � ������������,  ��������� ����� � ����������� �� ������ ����� ������.</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		-->
	</div>
</div>