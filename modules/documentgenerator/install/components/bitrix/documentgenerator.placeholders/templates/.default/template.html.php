<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
\Bitrix\Main\UI\Extension::load("ui.buttons");
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
		<div class="docs-placeholder-wrap docs-placeholder-wrap-slider">
			<div class="pagetitle-wrap">
				<div class="docs-template-pagetitle-wrap">
					<div class="docs-template-pagetitle-inner pagetitle-inner-container">
						<div class="pagetitle">
							<span class="docs-template-pagetitle-item pagetitle-item" id="pagetitle">���� �����</span>
						</div>
						<div class="pagetitle-container pagetitle-flexible-space">
						</div>
					</div>
				</div>
			</div>
			<div class="docs-placeholder-inner docs-placeholder-inner-slider">
				<div class="docs-placeholder-section">
					<div class="docs-placeholder-title-inner">
						<span class="docs-placeholder-title-item">����</span>
					</div>
					<div class="docs-placeholder-content">
						<div class="docs-placeholder-detail">
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
						</div>
						<div class="docs-placeholder-block-hidden">
							<div class="docs-placeholder-detail">
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">����� � ������</div>
										<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">����� � ������</div>
										<div class="docs-placeholder-code">{summa_i_valuta}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">����� � ������</div>
										<div class="docs-placeholder-code">{summa_i_valuta}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">���������</div>
										<div class="docs-placeholder-code">{obraschenie}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">���������</div>
										<div class="docs-placeholder-code">{obraschenie}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">���������</div>
										<div class="docs-placeholder-code">{obraschenie}</div>
									</div>
								</div>
							</div>
						</div>
						<div class="docs-placeholder-btn">
							<button class="ui-btn ui-btn-md ui-btn-light-border js-docs-placeholder-btn docs-placeholder-btn">�������� ���</button>
						</div>
					</div>
				</div>
				<div class="docs-placeholder-section">
					<div class="docs-placeholder-title-inner">
						<span class="docs-placeholder-title-item">������ (<span class="docs-placeholder-direction">������ ���</span>)</span>
					</div>
					<div class="docs-placeholder-content">
						<div class="docs-placeholder-detail">
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
						</div>
						<div class="docs-placeholder-block-hidden">
							<div class="docs-placeholder-detail">
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">����� � ������</div>
										<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">����� � ������</div>
										<div class="docs-placeholder-code">{summa_i_valuta}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">����� � ������</div>
										<div class="docs-placeholder-code">{summa_i_valuta}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">���������</div>
										<div class="docs-placeholder-code">{obraschenie}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">���������</div>
										<div class="docs-placeholder-code">{obraschenie}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">���������</div>
										<div class="docs-placeholder-code">{obraschenie}</div>
									</div>
								</div>
							</div>
						</div>
						<div class="docs-placeholder-btn">
							<button class="ui-btn ui-btn-md ui-btn-light-border js-docs-placeholder-btn docs-placeholder-btn">�������� ���</button>
						</div>
					</div>
				</div>
				<div class="docs-placeholder-section">
					<div class="docs-placeholder-title-inner">
						<span class="docs-placeholder-title-item">������</span>
					</div>
					<div class="docs-placeholder-content">
						<div class="docs-placeholder-detail">
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
						</div>
						<div class="docs-placeholder-block-hidden">
							<div class="docs-placeholder-detail">
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">����� � ������</div>
										<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">����� � ������</div>
										<div class="docs-placeholder-code">{summa_i_valuta}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">����� � ������</div>
										<div class="docs-placeholder-code">{summa_i_valuta}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">���������</div>
										<div class="docs-placeholder-code">{obraschenie}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">���������</div>
										<div class="docs-placeholder-code">{obraschenie}</div>
									</div>
								</div>
								<div class="docs-placeholder-item">
									<div class="docs-placeholder-item-inner">
										<div class="docs-placeholder-name">���������</div>
										<div class="docs-placeholder-code">{obraschenie}</div>
									</div>
								</div>
							</div>
						</div>
						<div class="docs-placeholder-btn">
							<button class="ui-btn ui-btn-md ui-btn-light-border js-docs-placeholder-btn docs-placeholder-btn">�������� ���</button>
						</div>
					</div>
				</div>
			</div>
		</div>
		</body>
	</html><?
}
else
{ ?>
	<div class="docs-placeholder-wrap">
		<? $APPLICATION->SetTitle("���� �����"); ?>
		<div class="docs-placeholder-inner">
			<div class="docs-placeholder-section">
				<div class="docs-placeholder-title-inner">
					<span class="docs-placeholder-title-item">����</span>
				</div>
				<div class="docs-placeholder-content">
					<div class="docs-placeholder-detail">
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">����� � ������</div>
								<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">����� � ������</div>
								<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">����� � ������</div>
								<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">���������</div>
								<div class="docs-placeholder-code">{obraschenie}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">���������</div>
								<div class="docs-placeholder-code">{obraschenie}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">���������</div>
								<div class="docs-placeholder-code">{obraschenie}</div>
							</div>
						</div>
					</div>
					<div class="docs-placeholder-block-hidden">
						<div class="docs-placeholder-detail">
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
						</div>
					</div>
					<div class="docs-placeholder-btn">
						<button class="ui-btn ui-btn-md ui-btn-light-border js-docs-placeholder-btn docs-placeholder-btn">�������� ���</button>
					</div>
				</div>
			</div>
			<div class="docs-placeholder-section">
				<div class="docs-placeholder-title-inner">
					<span class="docs-placeholder-title-item">������ (<span class="docs-placeholder-direction">������ ���</span>)</span>
				</div>
				<div class="docs-placeholder-content">
					<div class="docs-placeholder-detail">
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">����� � ������</div>
								<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">����� � ������</div>
								<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">����� � ������</div>
								<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">���������</div>
								<div class="docs-placeholder-code">{obraschenie}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">���������</div>
								<div class="docs-placeholder-code">{obraschenie}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">���������</div>
								<div class="docs-placeholder-code">{obraschenie}</div>
							</div>
						</div>
					</div>
					<div class="docs-placeholder-block-hidden">
						<div class="docs-placeholder-detail">
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
						</div>
					</div>
					<div class="docs-placeholder-btn">
						<button class="ui-btn ui-btn-md ui-btn-light-border js-docs-placeholder-btn docs-placeholder-btn">�������� ���</button>
					</div>
				</div>
			</div>
			<div class="docs-placeholder-section">
				<div class="docs-placeholder-title-inner">
					<span class="docs-placeholder-title-item">������</span>
				</div>
				<div class="docs-placeholder-content">
					<div class="docs-placeholder-detail">
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">����� � ������</div>
								<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">����� � ������</div>
								<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">����� � ������</div>
								<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">���������</div>
								<div class="docs-placeholder-code">{obraschenie}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">���������</div>
								<div class="docs-placeholder-code">{obraschenie}</div>
							</div>
						</div>
						<div class="docs-placeholder-item">
							<div class="docs-placeholder-item-inner">
								<div class="docs-placeholder-name">���������</div>
								<div class="docs-placeholder-code">{obraschenie}</div>
							</div>
						</div>
					</div>
					<div class="docs-placeholder-block-hidden">
						<div class="docs-placeholder-detail">
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta_i_chtoto_eshe}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">����� � ������</div>
									<div class="docs-placeholder-code">{summa_i_valuta}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
							<div class="docs-placeholder-item">
								<div class="docs-placeholder-item-inner">
									<div class="docs-placeholder-name">���������</div>
									<div class="docs-placeholder-code">{obraschenie}</div>
								</div>
							</div>
						</div>
					</div>
					<div class="docs-placeholder-btn">
						<button class="ui-btn ui-btn-md ui-btn-light-border js-docs-placeholder-btn docs-placeholder-btn">�������� ���</button>
					</div>
				</div>
			</div>
		</div>
	</div>
<? } ?>


