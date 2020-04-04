<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<div class="its-menu" id="its_menu_div">
<table cellpadding="0" cellspacing="0" border="0" class="its-menu">
	<thead><tr>
		<td class="left"><div class="empty"></div></td>
		<td class="center"><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td></tr></thead>
	<tbody>
		<tr>
			<td class="left"><div class="empty"></div></td>
			<td class="center">
				<table cellpadding="0" cellspacing="0" border="0" class="its-menu-inner">
					<tr>
						<?if ($arParams["PAGE_ID"] == "meeting"):?>
							<?if ($arResult["Perms"]["CanReserve"]):?>
								<td><div class="section-separator"></div></td>
								<td>
									<div class="controls controls-view meet_reserve">
										<a href="<?= $arResult["Urls"]["ReserveMeeting"] ?>" title="<?= GetMessage("INTASK_C27T_RESERVE_TITLE") ?>">
											<?= GetMessage("INTASK_C27T_RESERVE") ?>
										</a>
									</div>
								</td>
							<?endif;?>
						<?endif;?>

						<?if ($arParams["PAGE_ID"] == "reserve_meeting" || $arParams["PAGE_ID"] == "view_item"):?>
							<td><div class="section-separator"></div></td>
							<td>
								<div class="controls controls-view meet_graph">
									<a href="<?= $arResult["Urls"]["Meeting"] ?>" title="<?= GetMessage("INTASK_C27T_GRAPH_TITLE") ?>">
										<?= GetMessage("INTASK_C27T_GRAPH") ?>
									</a>
								</div>
							</td>
						<?endif;?>

						<?if ($arParams["PAGE_ID"] == "meeting" || $arParams["PAGE_ID"] == "reserve_meeting"):?>
							<?if ($arResult["Perms"]["CanModify"]):?>
								<td><div class="section-separator"></div></td>
								<td>
									<div class="controls controls-view sections_add">
										<a href="<?= $arResult["Urls"]["ModifyMeeting"] ?>" title="<?= GetMessage("INTASK_C27T_EDIT_TITLE") ?>">
											<?= GetMessage("INTASK_C27T_EDIT") ?>
										</a>
									</div>
								</td>
							<?endif;?>
						<?endif;?>

						<td><div class="separator"></div></td>
						<td>
							<div class="controls controls-view meet_list">
								<a href="<?= $arResult["Urls"]["MeetingList"] ?>" title="<?= GetMessage("ITSRM1_MEETING_LIST_DESCR") ?>">
									<?= GetMessage("ITSRM1_MEETING_LIST") ?>
								</a>
							</div>
						</td>

						<td><div class="section-separator"></div></td>
						<td>
							<div class="controls controls-view meet_search">
								<a href="<?= $arResult["Urls"]["Search"] ?>" title="<?= GetMessage("ITSRM1_MEETING_SEARCH_DESCR") ?>">
									<?= GetMessage("ITSRM1_MEETING_SEARCH") ?>
								</a>
							</div>
						</td>

						<?if ($arParams["PAGE_ID"] == "list"):?>
							<?if ($arResult["Perms"]["CanModify"]):?>
								<td><div class="separator"></div></td>
								<td>
									<div class="controls controls-view sections_add">
										<a href="<?= $arResult["Urls"]["CreateMeeting"] ?>" title="<?= GetMessage("INTASK_C27T_CRAETE_TITLE") ?>">
											<?= GetMessage("INTASK_C27T_CREATE") ?>
										</a>
									</div>
								</td>
							<?endif;?>
						<?endif;?>
					</tr>
				</table>
			</td>
			<td class="right"><div class="empty"></div></td></tr>
	</tbody>
	<tfoot><tr>
		<td class="left"><div class="empty"></div></td>
		<td class="center"><div class="empty"></div></td>
		<td class="right"><div class="empty"></div></td>
	</tr></tfoot>
</table>

</div>
