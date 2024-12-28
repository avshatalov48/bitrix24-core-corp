<?php
$module_id = 'ai';

use Bitrix\AI\Cloud\Configuration;
use Bitrix\AI\Prompt;
use Bitrix\AI\Tuning;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

/** @var \CMain $APPLICATION */

if (!Loader::includeModule($module_id))
{
	return;
}

// vars
$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$docRoot = $context->getServer()->getDocumentRoot();
$mid = $request->get('mid');
$backUrl = $request->get('back_url_settings');
$clearPrompts = $request->get('reload_prompts');
$postRight = $APPLICATION->getGroupRight($module_id);

// lang and assets
IncludeModuleLangFile($docRoot . '/bitrix/modules/main/options.php');
Loc::loadMessages(__FILE__);

CJSCore::init(['access']);
Extension::load(['popup', 'loader', 'ai.cloud-client-registration']);

$shouldUseB24 = \Bitrix\AI\Facade\Bitrix24::shouldUseB24();

function clearPrompts(): void
{
	Prompt\Manager::clearAndRefresh();
}

if ($postRight >= 'R'):

	if ($shouldUseB24)
	{
		$allOptions[] = [
			'check_limits',
			Loc::getMessage('AI_OPT_CHECK_LIMITS') . ':',
			['checkbox'],
		];

		$clearLinkTitle = Loc::getMessage('AI_OPT_PROMPT_HOST_REMOTE_DB_CLEAR');
		$clearLink =
			"<a href=\"{$APPLICATION->getCurPageParam('reload_prompts=Y&amp;'.bitrix_sessid_get())}\">{$clearLinkTitle}</a>";

		$allOptions[] = [
			'prompt_host_remote_db',
			Loc::getMessage('AI_OPT_PROMPT_HOST_REMOTE_DB') . ':',
			[null],
			null,
			$clearLink,
		];

		$allOptions[] = [
			'public_url',
			Loc::getMessage('AI_OPT_PUBLIC_URL') . ':',
			['text', 50],
		];

		$allOptions[] = [
			'header',
			Loc::getMessage('AI_OPT_HEADER_QUEUE'),
		];

		$allOptions[] = [
			'force_queue',
			Loc::getMessage('AI_OPT_FORCE_QUEUE') . ':',
			['checkbox'],
		];

		$allOptions[] = [
			'queue_url',
			Loc::getMessage('AI_OPT_QUEUE_URL') . ':',
			['text', 50],
		];

		$allOptions[] = [
			'header',
			Loc::getMessage('AI_OPT_HEADER_ENGINES'),
		];

		$internalItemsCodes = array_reduce(
			Tuning\Defaults::getInternalItems(),
			function ($codes, $items)
			{
				return array_merge($codes, array_keys($items));
			},
			[]
		);
		$manager = new Tuning\Manager();
		foreach ($manager->getList() as $group)
		{
			/**
			 * @var Tuning\Group $group
			 */
			foreach ($group->getItems() as $item)
			{
				if (in_array($item->getCode(), $internalItemsCodes))
				{
					$title = $item->getTitle();
					if (strpos($title, ':', -1) === false)
					{
						$title .= ':';
					}
					$allOptions[] = [
						$item->getCode(),
						$title,
						['selectbox', $item->getOptions()],
					];
				}
			}
		}

		$allOptions[] = [
			'openai_bearer',
			Loc::getMessage('AI_OPT_OPENAI_BEARER') . ':',
			['text', 50],
		];

		$allOptions[] = [
			'stable_diffusion_bearer',
			Loc::getMessage('AI_OPT_STABLE_DIFFUSION_BEARER') . ':',
			['text', 50],
		];
	}
	else
	{
		$allOptions[] = [
			'header',
			Loc::getMessage('AI_OPT_HEADER_CLOUD'),
		];
		$configuration = new Configuration();
		if ($configuration->hasCloudRegistration())
		{
			$allOptions[] = [
				'cloud_unregister',
				Loc::getMessage('AI_OPT_CLOUD_LABEL_BUTTON_REGISTER'),
				[
					'button',
					Loc::getMessage('AI_OPT_CLOUD_BUTTON_UNREGISTER'),
					'adm-btn',
				],
			];
		}
		else
		{
			$allOptions[] = [
				'cloud_register',
				Loc::getMessage('AI_OPT_CLOUD_LABEL_BUTTON_REGISTER'),
				[
					'button',
					Loc::getMessage('AI_OPT_CLOUD_BUTTON_REGISTER'),
					'adm-btn-save',
				],
			];
		}
	}

	$allOptions[] = [
		'header',
		Loc::getMessage('AI_OPT_HEADER_HISTORY'),
	];

	$allOptions[] = [
		'write_history_always',
		Loc::getMessage('AI_OPT_WRITE_HISTORY_ALWAYS') . ':',
		['checkbox'],
	];

	$allOptions[] = [
		'write_history_request',
		Loc::getMessage('AI_OPT_WRITE_HISTORY_REQUEST') . ':',
		['checkbox'],
	];

	$allOptions[] = [
		'write_errors',
		Loc::getMessage('AI_OPT_WRITE_ERRORS') . ':',
		['checkbox'],
	];

	$allOptions[] = [
		'max_history_per_user',
		Loc::getMessage('AI_OPT_MAX_HISTORY_PER_USER') . ':',
		['number', 0, 1000, 1],
	];

	$allOptions[] = [
		'disable_history_for_users',
		Loc::getMessage('AI_OPT_DISABLE_HISTORY_FOR_USERS') . ':',
		['access'],
	];

	// tabs
	$tabControl = new CAdmintabControl('tabControl', [
		['DIV' => 'edit1', 'TAB' => Loc::getMessage('MAIN_TAB_SET'), 'ICON' => ''],
	]);

	$Update = $_POST['Update'] ?? '';
	$Apply = $_POST['Apply'] ?? '';

	// clear prompts
	if ($clearPrompts && ($postRight === 'W' || $postRight === 'X'))
	{
		if (\check_bitrix_sessid())
		{
			clearPrompts();
			localRedirect($APPLICATION->getCurPageParam('', ['reload_prompts', 'sessid']));
		}
		else
		{
			$message = new CAdminMessage(['TYPE' => 'ERROR', 'MESSAGE' => 'Error occurred during session checking']);
		}
	}

	// post save
	if (
		$Update . $Apply <> ''
		&& ($postRight === 'W' || $postRight === 'X')
		&& \check_bitrix_sessid()
	)
	{
		foreach ($allOptions as $arOption)
		{
			if ($arOption[0] === 'header')
			{
				continue;
			}

			$name = $arOption[0];

			if ($arOption[2][0] === 'text-list')
			{
				$val = '';
				for ($j = 0; $j < count($$name); $j++)
				{
					if (trim(${$name}[$j]) <> '')
					{
						$val .= ($val <> '' ? ',' : '') . trim(${$name}[$j]);
					}
				}
			}
			elseif ($arOption[2][0] === 'doubletext')
			{
				$val = ${$name . '_1'} . 'x' . ${$name . '_2'};
			}
			elseif (
				$arOption[2][0] === 'selectbox'
				|| $arOption[2][0] === 'selectboxtree'
			)
			{
				$val = '';
				if (isset($$name))
				{
					for ($j = 0; $j < count($$name); $j++)
					{
						if (trim(${$name}[$j]) <> '')
						{
							$val .= ($val <> '' ? ',' : '') . trim(${$name}[$j]);
						}
					}
				}
			}
			elseif ($arOption[2][0] === 'access')
			{
				$val = implode(',', $$name ?? []);
			}
			else
			{
				$val = $$name ?? null;
			}

			if ($arOption[2][0] === 'checkbox' && $val <> 'Y')
			{
				$val = 'N';
			}

			$val = trim($val);

			\COption::setOptionString($module_id, $name, $val);
		}

		if (!empty($Update) && !empty($backUrl))
		{
			localRedirect($backUrl);
		}
		else
		{
			localRedirect(
				$APPLICATION->getCurPage() .
				'?mid=' . urlencode($mid) .
				'&lang=' . urlencode(LANGUAGE_ID) .
				'&back_url_settings=' . urlencode($backUrl ?? '') .
				'&' . $tabControl->ActiveTabParam());
		}
	}

	if (!empty($message))
	{
		echo $message->show();
	}

	?><form method="post" action="<?php echo $APPLICATION->getCurPage()?>?mid=<?php echo urlencode($mid)?>&amp;lang=<?php echo LANGUAGE_ID?>"><?php
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	$assetsForFieldsMarkers = [];
foreach($allOptions as $Option):
	if ($Option[0] === 'header')
	{
		?>
		<tr class="heading">
			<td colspan="2">
				<?php echo $Option[1]?>
			</td>
		</tr>
		<?php if (isset($Option[2])):?>
		<tr>
			<td></td>
			<td>
				<?php
				echo BeginNote();
				echo $Option[2];
				echo EndNote();
				?>
			</td>
		</tr>
	<?php endif;

		continue;
	}
	$type = $Option[2];
	$val = \COption::getOptionString(
		$module_id,
		$Option[0],
		$Option[3] ?? null
	);
	?>
	<tr>
	<td style="width: 40%;"><?php
		if ($type[0] === 'checkbox')
		{
			echo '<label for="' . htmlspecialcharsbx($Option[0]) . '">' . $Option[1] . '</label>';
		}
		else
		{
			echo $Option[1];
		}
		?></td>
	<td><?php
		if ($type[0] === 'checkbox'):
			?><input type="checkbox" name="<?php echo htmlspecialcharsbx($Option[0])?>" id="<?php echo htmlspecialcharsbx($Option[0])?>" value="Y"<?php echo ($val === 'Y') ? ' checked="checked"' : ''?> /><?php
		elseif ($type[0] === 'button'):
			?><input type="button" id="<?php echo htmlspecialcharsbx($Option[0])?>" name="<?php echo htmlspecialcharsbx($Option[0])?>" value="<?= $type[1] ?>" class="<?= $type[2] ?>"><?php
		elseif ($type[0] === 'text'):
			?><input type="text" size="<?php echo $type[1]?>" maxlength="255" value="<?php echo htmlspecialcharsbx($val)?>" name="<?php echo htmlspecialcharsbx($Option[0])?>" /><?php
		elseif ($type[0] === 'doubletext'):
			[$val1, $val2] = explode('x', $val);
			?><input type="text" size="<?php echo $type[1]?>" maxlength="255" value="<?php echo htmlspecialcharsbx($val1)?>" name="<?php echo htmlspecialcharsbx(
			$Option[0] . '_1')?>" /><?php
			?><input type="text" size="<?php echo $type[1]?>" maxlength="255" value="<?php echo htmlspecialcharsbx($val2)?>" name="<?php echo htmlspecialcharsbx(
			$Option[0] . '_2')?>" /><?php
		elseif ($type[0] === 'number'):
			?><input type="number" min="<?php echo $type[1]?>" max="<?=$type[2]?>" value="<?php echo (int)htmlspecialcharsbx($val)?>" name="<?php echo htmlspecialcharsbx($Option[0])?>" /><?php
		elseif ($type[0] === 'textarea'):
			?><textarea rows="<?php echo $type[1]?>" cols="<?php echo $type[2]?>" name="<?php echo htmlspecialcharsbx(
			$Option[0])?>"><?php echo htmlspecialcharsbx($val)?></textarea><?php
		elseif ($type[0] === 'text-list'):
			$aVal = explode(",", $val);
			for($j=0; $j<count($aVal); $j++):
				?><input type="text" size="<?php echo $type[2]?>" value="<?php echo htmlspecialcharsbx(
				$aVal[$j])?>" name="<?php echo htmlspecialcharsbx($Option[0]) . '[]'?>" /><br /><?php
			endfor;
			for($j=0; $j<$type[1]; $j++):
				?><input type="text" size="<?php echo $type[2]?>" value="" name="<?php echo htmlspecialcharsbx($Option[0]) . '[]'?>" /><br /><?php
			endfor;
		elseif ($type[0] === 'selectbox'):
			$arr = $type[1];
			$arr_keys = array_keys($arr);
			$currValue = explode(',', $val);
			?><select name="<?php echo htmlspecialcharsbx($Option[0])?>[]"<?php echo $type[2] ?? null?>><?php
			for($j = 0; $j < count($arr_keys); $j++):
				?><option value="<?php echo $arr_keys[$j]?>"<?php echo in_array($arr_keys[$j], $currValue) ? ' selected="selected"' : ''?>><?php echo htmlspecialcharsbx(
				$arr[$arr_keys[$j]])?></option><?php
			endfor;
			?></select><?php
		elseif ($type[0] === 'access'):
			$fieldName =  htmlspecialcharsbx($Option[0]);
			$htmlId =  "bx_access_$fieldName";
			$access = new CAccess();
			$accessCodes = !empty($val) ? explode(',', $val) : [];
			$accessNames = !empty($accessCodes) ? $access->getNames($accessCodes) : [];
			?>
			<div id="<?= $htmlId?>">
				<?php
				foreach ($accessCodes as $code)
				{
					echo '<div style="margin-bottom:4px">'
							. '<input type="hidden" name="' . $fieldName . '[]" value="' . $code . '">'
							. (!empty($accessNames[$code]['provider']) ? $accessNames[$code]['provider'].': ' : '')
							. htmlspecialcharsbx($accessNames[$code]['name'])
							. '&nbsp;<a href="javascript:void(0);" onclick="AI_DeleteAccess(this, \''.$code.'\')" class="access-delete"></a>'
						. '</div>';
				}
				?>
			</div>
			<a href="javascript:void(0)" class="bx-action-href" onclick="AI_ShowAccessPanel('<?= $htmlId?>', '<?= $fieldName?>')"><?php
				?><?= Loc::getMessage('AI_OPT_DISABLE_HISTORY_FOR_USERS_ADD')?><?php
			?></a>
			<?php
			if (in_array('access', $assetsForFieldsMarkers))
			{
				continue;
			}
			$assetsForFieldsMarkers[] = 'access';
			?>
			<script>
				function AI_InsertAccess(arRights, divId, hiddenName)
				{
					let div = BX(divId);

					for (let provider in arRights)
					{
						for(let id in arRights[provider])
						{
							let pr = BX.Access.GetProviderPrefix(provider, id);
							let newDiv = document.createElement('DIV');
							newDiv.style.marginBottom = '4px';
							newDiv.innerHTML = '<input type="hidden" name="'+hiddenName+'" value="'+id+'">'
								+ (pr ? pr + ': ' : '') + BX.util.htmlspecialchars(arRights[provider][id].name)
								+ '&nbsp;<a href="javascript:void(0);" onclick="AI_DeleteAccess(this, \''+id+'\')" class="access-delete"></a>';
							div.appendChild(newDiv);
						}
					}
				}
				function AI_DeleteAccess(ob)
				{
					let div = BX.findParent(ob, {'tag':'div'});
					div.parentNode.removeChild(div);
				}
				function AI_ShowAccessPanel(divId, hiddenName)
				{
					BX.Access.Init();
					BX.Access.SetSelected({});
					BX.Access.ShowForm({
						callback: function(obSelected)
						{
							AI_InsertAccess(obSelected, divId, hiddenName + '[]');
						}
					});
				}
			</script>
			<?php
		endif;
		echo $Option[4] ?? '';
		?>
	</td>
<?php
endforeach;

	$tabControl->Buttons();
	?>
	<input <?php echo ($postRight < 'W') ? 'disabled="disabled"' : ''?> type="submit" name="Update" value="<?php  echo Loc::getMessage('MAIN_SAVE')?>" title="<?php echo Loc::getMessage('MAIN_OPT_SAVE_TITLE')?>" class="adm-btn-save" />
	<input <?php echo ($postRight < 'W') ? 'disabled="disabled"' : ''?> type="submit" name="Apply" value="<?php echo Loc::getMessage('MAIN_OPT_APPLY')?>" title="<?php echo Loc::getMessage('MAIN_OPT_APPLY_TITLE')?>" />
	<?if($_REQUEST["back_url_settings"] <> ''):?>
		<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<?php echo bitrix_sessid_post()?>
	<?php $tabControl->End()?>
	</form>
	<script>
		BX.ready(function(){
			BX.bind(BX('cloud_register'), 'click', function(e){
				e.preventDefault();

				(new BX.AI.ClientRegistration()).start();
			});
			BX.bind(BX('cloud_unregister'), 'click', function(e){
				e.preventDefault();

				(new BX.AI.ClientUnRegistration()).start();
			});
		});
	</script>
<?php endif;
