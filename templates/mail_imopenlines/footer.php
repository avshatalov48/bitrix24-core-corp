<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

								

							</td>
						</tr>
					</table>
					<div style="padding-top: 20px;padding-bottom:20px; text-align: center;">
						<?if (\Bitrix\Main\Loader::includeModule('intranet')):?>
						<a href="<?=CIntranetUtils::getB24Link('pub'); ?>" target="_blank" style="color: #71a5b6;text-decoration: none;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: 12px;display: inline-block;vertical-align: middle;">
							<img height="19" width="101" src="<?=$arParams['TEMPLATE_SERVER_ADDRESS']?>/bitrix/templates/mail_imopenlines/images/<?=GetMessage('IMOL_MAIL_BITRIX24_IMAGEFILE')?>" alt="<?=GetMessage('IMOL_MAIL_BITRIX24_IMAGEFILE_ALT')?>" style="outline: none;text-decoration: none;-ms-interpolation-mode: bicubic;border: none;font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;font-size: 17px;color: #71a5b6;font-weight: bold;vertical-align: middle;">
						</a>
						<?endif?>
					</div>
				</center>
			</td>
		</tr>
	</table>
</body>
</html>