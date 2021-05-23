<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>
<?
if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm("");
}
elseif (strlen($arResult["FatalError"]) > 0)
{
	?>
	<span class='errortext'><?=$arResult["FatalError"]?></span><br /><br />
	<?
}
else
{
	if (strlen($arResult["ErrorMessage"]) > 0)
	{
		?>
		<span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br />
		<?
	}
	?>

	<?if (StrLen($arResult["NAV_STRING"]) > 0):?>
		<?=$arResult["NAV_STRING"]?><br /><br />
	<?endif;?></p>

	<div class="bx-calendar-layout">
	<table class="bx-calendar-main-table" cellSpacing="1">
		<thead>
			<tr>
				<td class="bx-calendar-control" rowspan="2">
					<table class="bx-calendar-control-table"><tr><td class="bx-calendar-control-text"><?= GetMessage("INTASK_T078_NAME") ?></td></tr></table>
				</td>
				<?
				$minDate = "";
				$maxDate = "";
				$nDates = 0;
				if ($arResult["Tasks"])
				{
					foreach ($arResult["Tasks"] as $task)
					{
						if (StrLen($task["FIELDS"]["DATE_ACTIVE_FROM"]) > 0)
						{
							$d1 = MakeTimeStamp($task["FIELDS"]["DATE_ACTIVE_FROM"], FORMAT_DATETIME);
							if ($minDate == "" || $d1 < $minDate)
								$minDate = $d1;
						}
						if (StrLen($task["FIELDS"]["DATE_ACTIVE_TO"]) > 0)
						{
							$d1 = MakeTimeStamp($task["FIELDS"]["DATE_ACTIVE_TO"], FORMAT_DATETIME);
							if ($maxDate == "" || $d1 > $maxDate)
								$maxDate = $d1;
						}
					}
				}

				if ($minDate == "" && $maxDate == "")
				{
					$minDate = MkTime(0, 0, 0, Date("m"), 1, Date("Y"));
					$maxDate = MkTime(0, 0, 0, Date("m"), 31, Date("Y"));
				}
				elseif ($minDate != "" && $maxDate == "")
				{
					$maxDate = MkTime(0, 0, 0, Date("m", $minDate), Date("d", $minDate) + 31, Date("Y", $minDate));
				}
				elseif ($minDate == "" && $maxDate != "")
				{
					$minDate = MkTime(0, 0, 0, Date("m", $maxDate), Date("d", $maxDate) - 31, Date("Y", $maxDate));
				}

				$minDate = MkTime(0, 0, 0, Date("m", $minDate), Date("d", $minDate), Date("Y", $minDate));
				$maxDate = MkTime(0, 0, 0, Date("m", $maxDate), Date("d", $maxDate), Date("Y", $maxDate));

				$m = IntVal(Date("m", $minDate));
				$y = IntVal(Date("Y", $minDate));
				$n = 0;
				$i = $minDate;
				while ($i <= $maxDate)
				{
					$n++;
					$m1 = IntVal(Date("m", $i));
					$y1 = IntVal(Date("Y", $i));
					if ($m1 > $m || $m1 == 1 && $y1 > $y)
					{
						?>
						<td class="bx-calendar-day" colspan="<?= $n-1 ?>">
							<?= GetMessage('MONTH_'.$m) ?>
						</td>
						<?
						$m = $m1;
						$y = $y1;
						$n = 1;
					}
					$i = MkTime(0, 0, 0, Date("m", $i), Date("d", $i) + 1, Date("Y", $i));
				}
				?>
					<td class="bx-calendar-day" colspan="<?= $n ?>">
						<?= GetMessage('MONTH_'.$m) ?>
					</td>
				</tr><tr>
				<?
				$i = $minDate;
				while ($i <= $maxDate)
				{
					$nDates++;
					?>
					<td class="bx-calendar-day<?= (Date("w", $i) == 0 || Date("w", $i) == 6) ? " bx-calendar-holiday" : "" ?><?= (Date("d.m.Y", $i) == Date("d.m.Y")) ? " bx-calendar-today" : "" ?>">
						<?= Date("d", $i) ?>
					</td>
					<?
					$i = MkTime(0, 0, 0, Date("m", $i), Date("d", $i) + 1, Date("Y", $i));
				}
				?>
			</tr>
		</thead>
		<tbody>
			<?
			if ($arResult["Tasks"])
			{
				foreach ($arResult["Tasks"] as $task)
				{
					//if (StrLen($task["FIELDS"]["DATE_ACTIVE_FROM"]) <= 0 && StrLen($task["FIELDS"]["DATE_ACTIVE_TO"]) <= 0)
					//	continue;

					$task["PRINT_PERIOD"] = "";
					if (StrLen($task['FIELDS']['DATE_ACTIVE_FROM']) > 0 && StrLen($task['FIELDS']['DATE_ACTIVE_TO']) > 0)
						$task["PRINT_PERIOD"] = $task['FIELDS']['DATE_ACTIVE_FROM']." - ".$task['FIELDS']['DATE_ACTIVE_TO']."\n";
					elseif (StrLen($task['FIELDS']['DATE_ACTIVE_FROM']) > 0 && StrLen($task['FIELDS']['DATE_ACTIVE_TO']) <= 0)
						$task["PRINT_PERIOD"] = GetMessage("INTASK_T078_FROM")." ".$task['FIELDS']['DATE_ACTIVE_FROM']."\n";
					elseif (StrLen($task['FIELDS']['DATE_ACTIVE_FROM']) <= 0 && StrLen($task['FIELDS']['DATE_ACTIVE_TO']) > 0)
						$task["PRINT_PERIOD"] = GetMessage("INTASK_T078_TO")." ".$task['FIELDS']['DATE_ACTIVE_TO']."\n";
					?>
					<tr id="bx_calendar_user_<?= $task["FIELDS"]["ID"] ?>">
						<td class="bx-calendar-first-col">
							<div title="<?= $task["PRINT_PERIOD"].GetMessage("INTASK_T078_RESP").": ".$task['FIELDS']['TASKASSIGNEDTO_PRINTABLE']."\n".$task["FIELDS"]["NAME"];?>">
								<a href="<?= $task["VIEW_URL"] ?>"><?= $task["FIELDS"]["NAME"] ?></a>
							</div>
						</td>
						<?
						$i = $minDate;
						while ($i <= $maxDate)
						{
							?>
							<td class="bx-calendar-day<?= (Date("w", $i) == 0 || Date("w", $i) == 6) ? " bx-calendar-holiday" : "" ?><?= (Date("d.m.Y", $i) == Date("d.m.Y")) ? " bx-calendar-today" : "" ?>" title="<?= $task["PRINT_PERIOD"].GetMessage("INTASK_T078_RESP").": ".$task['FIELDS']['TASKASSIGNEDTO_PRINTABLE']."\n".$task["FIELDS"]["NAME"] ?>">
								&nbsp;
							</td>
							<?
							$i = MkTime(0, 0, 0, Date("m", $i), Date("d", $i) + 1, Date("Y", $i));
						}
						?>
					</tr>
					<?
				}
			}
			?>
		</tbody>
	</table>
	</div>

	<script language="JavaScript">
	<!--
	window.onload = function() 
	{
	<?
	if ($arResult["Tasks"])
	{
		?>
		padding = 2;
		<?
		foreach ($arResult["Tasks"] as $task)
		{
			//if (StrLen($task["FIELDS"]["DATE_ACTIVE_FROM"]) <= 0 && StrLen($task["FIELDS"]["DATE_ACTIVE_TO"]) <= 0)
			//	continue;
			$d1 = IntVal(MakeTimeStamp($task["FIELDS"]["DATE_ACTIVE_FROM"]));
			$d2 = IntVal(MakeTimeStamp($task["FIELDS"]["DATE_ACTIVE_TO"]));
			if ($d2 > 0 && $minDate > $d2 || $maxDate < $d1)
				continue;

			$task["PRINT_PERIOD"] = "";
			if (StrLen($task['FIELDS']['DATE_ACTIVE_FROM']) > 0 && StrLen($task['FIELDS']['DATE_ACTIVE_TO']) > 0)
				$task["PRINT_PERIOD"] = $task['FIELDS']['DATE_ACTIVE_FROM']." - ".$task['FIELDS']['DATE_ACTIVE_TO']."\\n";
			elseif (StrLen($task['FIELDS']['DATE_ACTIVE_FROM']) > 0 && StrLen($task['FIELDS']['DATE_ACTIVE_TO']) <= 0)
				$task["PRINT_PERIOD"] = GetMessage("INTASK_T078_FROM")." ".$task['FIELDS']['DATE_ACTIVE_FROM']."\\n";
			elseif (StrLen($task['FIELDS']['DATE_ACTIVE_FROM']) <= 0 && StrLen($task['FIELDS']['DATE_ACTIVE_TO']) > 0)
				$task["PRINT_PERIOD"] = GetMessage("INTASK_T078_TO")." ".$task['FIELDS']['DATE_ACTIVE_TO']."\\n";
			?>
			var obUserRow = document.getElementById('bx_calendar_user_<?= $task["FIELDS"]["ID"] ?>');
			var perCent = <?= IntVal($task['FIELDS']['TASKCOMPLETE_ORIGINAL']) ?>;

			if (obUserRow)
			{
				var obRowPos = jsUtils.GetRealPos(obUserRow);

				var obDiv = document.body.appendChild(document.createElement('DIV'));

				obDiv.className = 'bx-calendar-entry bx-calendar-color-default';
				obDiv.style.top = (obRowPos.top + padding) + 'px';

				var obStartCell = obUserRow.cells[<?= ($d1 <= $minDate) ? 1 : IntVal(1 + ($d1 - $minDate) / 86400) ?>];
				var obFinishCell = obUserRow.cells[<?= ($d2 <= 0 || $d2 > $maxDate) ? $nDates : IntVal(1 + ($d2 - $minDate) / 86400) ?>];

				obPos = jsUtils.GetRealPos(obStartCell);
				var start_pos = obPos.left + padding;
				if (<?= Date("H", $d1) ?> > 14) 
					start_pos += parseInt((obPos.right - obPos.left)/2) - 1;

				if (obStartCell != obFinishCell)
					obPos = jsUtils.GetRealPos(obFinishCell);

				var width = obPos.right - start_pos - (jsUtils.IsIE() ? padding  * 2 : padding);

				if (<?= $d2 ?> > 0 && <?= Date("H", $d2) ?> > 0 && <?= Date("H", $d2) ?> <= 14)
				{
					var z = parseInt((obPos.right - obPos.left)/2) + 1;
					if (width > z)
						width -= z;
					else
						width = 1;
				}

				obDiv.style.left = start_pos + 'px';
				obDiv.style.width = width + 'px';

				if (perCent > 0)
				{
					var obDiv1 = document.body.appendChild(document.createElement('DIV'));
					obDiv1.className = 'bx-calendar-entry bx-calendar-color-ready';
					obDiv1.style.top = (obRowPos.top + padding) + 'px';
					obDiv1.style.left = start_pos + 'px';
					obDiv1.style.width = parseInt((width * perCent) / 100) + 'px';
				}

				var obDiv2 = document.body.appendChild(document.createElement('DIV'));
				obDiv2.className = 'bx-calendar-entry bx-calendar-color-text';
				obDiv2.style.top = (obRowPos.top + padding) + 'px';
				obDiv2.style.left = start_pos + 'px';
				obDiv2.style.width = width + 'px';

				obDiv2.innerHTML = "<?= $task['FIELDS']['TASKCOMPLETE_ORIGINAL'] ?>%";
				obDiv2.title = "<?= $task['PRINT_PERIOD']?><?= GetMessage('INTASK_T078_RESP') ?>: <?= $task['FIELDS']['TASKASSIGNEDTO_PRINTABLE']?>\n<?=$task['FIELDS']['NAME'] ?>";
			}
			<?
		}
	}
	?>
	}
	//-->
	</script>

	<?if (StrLen($arResult["NAV_STRING"]) > 0):?>
		<?=$arResult["NAV_STRING"]?>
		<br /><br />
	<?endif;?>
	<?
}
?>