<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @var array $arUrls */
/** @var array $arHeaders */

$bPriceType  = false;
$bPropsColumn  = false;
$bDeleteColumn = true;
?>
<div id="basket_items_subscribed" class="bx_ordercart_order_table_container" style="display:none">
	<table>
		<thead>
			<tr>
				<td class="margin"></td>
				<?
				foreach ($arResult["GRID"]["HEADERS"] as $id => $arHeader):
					if (in_array($arHeader["id"], array("TYPE"))) // some header columns are shown differently
					{
						$bPriceType = true;
						continue;
					}
					elseif ($arHeader["id"] == "PROPS")
					{
						$bPropsColumn = true;
						continue;
					}
					elseif ($arHeader["id"] == "DELAY")
					{
						$bDelayColumn = true;
						continue;
					}
					elseif ($arHeader["id"] == "DELETE")
					{
						$bDeleteColumn = true;
						continue;
					}
					elseif ($arHeader["id"] == "WEIGHT")
					{
						$bWeightColumn = true;
					}

					if ($arHeader["id"] == "NAME"):
					?>
						<td class="item" colspan="2">
					<?
					elseif ($arHeader["id"] == "PRICE"):
					?>
						<td class="price">
					<?
					else:
					?>
						<td class="custom">
					<?
					endif;
					?>
						<?=$arHeader["name"]; ?>
						</td>
				<?
				endforeach;
				?>
					<td class="custom"></td>
					<td class="margin"></td>
			</tr>
		</thead>

		<tbody>
			<?
			$skipHeaders = array('PROPS', 'DELAY', 'DELETE', 'TYPE');

			foreach ($arResult["GRID"]["ROWS"] as $k => $arItem):

				if ($arItem["CAN_BUY"] == "N" && $arItem["SUBSCRIBE"] == "Y"):
			?>
				<tr>
					<td class="margin"></td>
					<?
					foreach ($arResult["GRID"]["HEADERS"] as $id => $arHeader):

						if (in_array($arHeader["id"], $skipHeaders)) // some values are not shown in columns in this template
							continue;

						if ($arHeader["id"] == "NAME"):
						?>
							<td class="itemphoto">
								<div class="bx_ordercart_photo_container">
									<?
									if (strlen($arItem["PREVIEW_PICTURE_SRC"]) > 0):
										$url = $arItem["PREVIEW_PICTURE_SRC"];
									elseif (strlen($arItem["DETAIL_PICTURE_SRC"]) > 0):
										$url = $arItem["DETAIL_PICTURE_SRC"];
									else:
										$url = $templateFolder."/images/no_photo.png";
									endif;
									?>

									<?if (strlen($arItem["DETAIL_PAGE_URL"]) > 0):?><a href="<?=$arItem["DETAIL_PAGE_URL"] ?>"><?endif;?>
										<div class="bx_ordercart_photo" style="background-image:url('<?=$url?>')"></div>
									<?if (strlen($arItem["DETAIL_PAGE_URL"]) > 0):?></a><?endif;?>
								</div>
								<div class="bx_ordercart_brand">
									<?
									if (!empty($arItem["BRAND"])):
									?>
										<img alt="" src="<?=$arItem["BRAND"]?>" />
									<?
									endif;
									?>
								</div>
							</td>
							<td class="item">
								<h2 class="bx_ordercart_itemtitle">
									<?if (strlen($arItem["DETAIL_PAGE_URL"]) > 0):?><a href="<?=$arItem["DETAIL_PAGE_URL"] ?>"><?endif;?>
										<?=$arItem["NAME"]?>
									<?if (strlen($arItem["DETAIL_PAGE_URL"]) > 0):?></a><?endif;?>
								</h2>
								<div class="bx_ordercart_itemart">
								<?
								if ($bPropsColumn):
									foreach ($arItem["PROPS"] as $val):

										if (is_array($arItem["SKU_DATA"]))
										{
											$bSkip = false;
											foreach ($arItem["SKU_DATA"] as $propId => $arProp)
											{
												if ($arProp["CODE"] == $val["CODE"])
												{
													$bSkip = true;
													break;
												}
											}
											if ($bSkip)
												continue;
										}

										echo htmlspecialcharsbx($val["NAME"]).":&nbsp;<span>".$val["VALUE"]."<span><br/>";
									endforeach;
								endif;

								if (is_array($arItem["SKU_DATA"])):
										$propsMap = array();
										foreach ($arItem["PROPS"] as $propValue)
										{
											if (empty($propValue) || !is_array($propValue))
												continue;
											$propsMap[$propValue['CODE']] = $propValue['VALUE'];
										}
										foreach ($arItem["SKU_DATA"] as $propId => $arProp):
											$selectedIndex = 0;
											// is image property
											$isImgProperty = false;
											if (!empty($arProp["VALUES"]) && is_array($arProp["VALUES"]))
											{
												$counter = 0;
												foreach ($arProp["VALUES"] as $id => $arVal)
												{
													$counter++;
													if (isset($propsMap[$arProp['CODE']]))
													{
														if ($propsMap[$arProp['CODE']] == $arVal['NAME'] || $propsMap[$arProp['CODE']] == $arVal['XML_ID'])
															$selectedIndex = $counter;
													}
													if (isset($arVal["PICT"]) && !empty($arVal["PICT"]))
													{
														$isImgProperty = true;
													}
												}
												unset($counter);
											}
											$countValues = count($arProp["VALUES"]);
											$full = ($countValues > 5) ? "full" : "";

											$marginLeft = 0;
											if ($countValues > 5 && $selectedIndex > 5)
												$marginLeft = ((5 - $selectedIndex)*20).'%';

											if ($isImgProperty):
											?>
												<div class="bx_item_detail_scu_small_noadaptive <?=$full?>">
													<span class="bx_item_section_name_gray">
														<?=htmlspecialcharsbx($arProp["NAME"]);?>:
													</span>
													<div class="bx_scu_scroller_container">
														<div class="bx_scu">
															<ul id="prop_<?=$arProp["CODE"]?>_<?=$arItem["ID"]?>" style="width: 200%; margin-left: <?=$marginLeft; ?>">
															<?
															$counter = 0;
															foreach ($arProp["VALUES"] as $valueId => $arSkuValue):
																$counter++;
																$selected = ($selectedIndex == $counter ? ' class="bx_active"' : '');
															?>
															<li style="width:10%;"<?=$selected?>>
																<a href="javascript:void(0)" class="cnt"><span class="cnt_item" style="background-image:url(<?=$arSkuValue["PICT"]["SRC"];?>)"></span></a>
															</li>
															<?
															endforeach;
															unset($counter);
															?>
															</ul>
														</div>
														<div class="bx_slide_left" onclick="leftScroll('<?=$arProp["CODE"]?>', <?=$arItem["ID"]?>, <?=$countValues?>);"></div>
														<div class="bx_slide_right" onclick="rightScroll('<?=$arProp["CODE"]?>', <?=$arItem["ID"]?>, <?=$countValues?>);"></div>
													</div>
												</div>
											<?
											else:
											?>
												<div class="bx_item_detail_size_small_noadaptive <?=$full?>">
													<span class="bx_item_section_name_gray">
														<?=htmlspecialcharsbx($arProp["NAME"]);?>:
													</span>
													<div class="bx_size_scroller_container">
														<div class="bx_size">
															<ul id="prop_<?=$arProp["CODE"]?>_<?=$arItem["ID"]?>" style="width: 200%; margin-left: <?=$marginLeft; ?>">
																<?
																$counter = 0;
																foreach ($arProp["VALUES"] as $valueId => $arSkuValue):
																	$counter++;
																	$selected = ($selectedIndex == $counter ? ' class="bx_active"' : '');
																?>
																	<li style="width:10%;"<?=$selected?>>
																		<a href="javascript:void(0);" class="cnt"><?=$arSkuValue["NAME"]?></a>
																	</li>
																<?
																endforeach;
																unset($counter);
																?>
															</ul>
														</div>
														<div class="bx_slide_left" onclick="leftScroll('<?=$arProp["CODE"]?>', <?=$arItem["ID"]?>, <?=$countValues?>);"></div>
														<div class="bx_slide_right" onclick="rightScroll('<?=$arProp["CODE"]?>', <?=$arItem["ID"]?>, <?=$countValues?>);"></div>
													</div>
												</div>
											<?
											endif;
										endforeach;
								endif;
								?>
								</div>
							</td>
						<?
						elseif ($arHeader["id"] == "QUANTITY"):
						?>
							<td class="custom">
								<span><?=$arHeader["name"]; ?>:</span>
								<div style="text-align: center;">
									<?echo $arItem["QUANTITY"];
										if (isset($arItem["MEASURE_TEXT"]))
											echo "&nbsp;".htmlspecialcharsbx($arItem["MEASURE_TEXT"]);
									?>
								</div>
							</td>
						<?
						elseif ($arHeader["id"] == "PRICE"):
						?>
							<td class="price">
								<?if (doubleval($arItem["DISCOUNT_PRICE_PERCENT"]) > 0):?>
									<div class="current_price"><?=$arItem["PRICE_FORMATED"]?></div>
									<div class="old_price"><?=$arItem["FULL_PRICE_FORMATED"]?></div>
								<?else:?>
									<div class="current_price"><?=$arItem["PRICE_FORMATED"];?></div>
								<?endif?>

								<?if ($bPriceType && strlen($arItem["NOTES"]) > 0):?>c
									<div class="type_price"><?=GetMessage("SALE_TYPE")?></div>
									<div class="type_price_value"><?=$arItem["NOTES"]?></div>
								<?endif;?>
							</td>
						<?
						elseif ($arHeader["id"] == "DISCOUNT"):
						?>
							<td class="custom">
								<span><?=$arHeader["name"]; ?>:</span>
								<?=$arItem["DISCOUNT_PRICE_PERCENT_FORMATED"]?>
							</td>
						<?
						elseif ($arHeader["id"] == "WEIGHT"):
						?>
							<td class="custom">
								<span><?=$arHeader["name"]; ?>:</span>
								<?=$arItem["WEIGHT_FORMATED"]?>
							</td>
						<?
						else:
						?>
							<td class="custom">
								<span><?=$arHeader["name"]; ?>:</span>
								<?=$arItem[$arHeader["id"]]?>
							</td>
						<?
						endif;
					endforeach;

					?>
						<td class="control">
							<a href="<?=str_replace("#ID#", $arItem["ID"], $arUrls["delete"])?>"><?=GetMessage("SALE_DELETE")?></a><br />
						</td>
						<td class="margin"></td>
				</tr>
				<?
				endif;
			endforeach;
			?>
		</tbody>

	</table>
</div>