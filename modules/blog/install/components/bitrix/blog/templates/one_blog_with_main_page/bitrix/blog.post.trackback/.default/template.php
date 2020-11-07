<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<a name="trackback"></a>
<?
if($arResult["MESSAGE"] <> '')
{
	?>
	<?=$arResult["MESSAGE"]?><br /><br />
	<?
}
if($arResult["ERROR_MESSAGE"] <> '')
{
	?>
	<span class='errortext'><?=$arResult["ERROR_MESSAGE"]?></span><br /><br />
	<?
}
if($arResult["FATAL_MESSAGE"] <> '')
{
	?>
	<span class='errortext'><?=$arResult["FATAL_MESSAGE"]?></span><br /><br />
	<?
}
else
{
	if($arResult["Post"]["ENABLE_TRACKBACK"]=="Y")
	{
		?>
		<div class="blog-trackback">
		<a href="<?=$arResult["urlToTrackback"]?>"><?=GetMessage("B_B_MES_TBA")?></a><br />
		<?
		if(!empty($arResult["TrackBack"]))
		{
			?>
			<b>Trackbacks:</b>
			<table width="100%" cellpadding="0" cellspacing="0" border="0" class="blog-trackback-table">
			<?
			foreach($arResult["TrackBack"] as $arTrack)
			{
				?>
				<tr>
					<td colspan="2"><div class="blog-line"></div></td>
				</tr>
				<tr>
					<td class="blogpostdate">
						<b><a href="<?=$arTrack["URL"]?>"><?=$arTrack["BLOG_NAME"]?></a>:</b>&nbsp;<?=$arTrack["POST_DATE_FORMATED"]?><br />
				<a href="<?=$arTrack["URL"]?>"><?=$arTrack["TITLE"]?></a>
					</td>
					<?
					if($arTrack["urlToDelete"] <> '')
					{
						?>
						<td align="right" valign="top"><a href="<?=$arTrack["urlToDelete"]."&".bitrix_sessid_get()?>" class="blog-post-delete"></a></td>
						<?
					}
					?>
				</tr>
				<tr><td>
					<?=$arTrack["PREVIEW_TEXT"]?>
				<br /><br /></td></tr>
				<?
			}
			?>
			</table>
			<?
		}
		?>
		</div>
		<?
	}
}
?>