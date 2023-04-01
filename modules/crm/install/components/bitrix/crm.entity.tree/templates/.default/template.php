<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm;

\Bitrix\Main\UI\Extension::load('ui.design-tokens');

if (!function_exists('CrmEntityTreeConvertDateTime'))
{
	/*
	 * For optimization format date.
	 */
	function CrmEntityTreeConvertDateTime($datetime, $to_format=false, $from_site=false, $bSearchInSitesOnly=false)
	{
		if (preg_match('/[^\d]+/', $datetime))
		{
			return \ConvertDateTime($datetime, $to_format, $from_site, $bSearchInSitesOnly);
		}

		return \ConvertTimeStamp($datetime);
	}
}

if (!function_exists('CrmEntityTreeDrawActivity'))
{
	/*
	 * Draw activity block for one entity.
	 */
	function CrmEntityTreeDrawActivity($id, $type, $activity, $leadId = null, $document = [])
	{
		static $activityLabel = null;
		static $documentLabel = null;
		static $activityTypes = [];

		$activityLabel = ($activityLabel ?? Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY'));
		$documentLabel = ($documentLabel ?? Loc::getMessage('CRM_ENTITY_TREE_DOCUMENT'));

		if (empty($activityTypes))
		{
			//crm.activity.list/templates/grid
			$activityTypes = array(
				//call
				\CCrmActivityType::Call => array(
					'title' => Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY_CALL'),
					'icon' => 'crm-doc-droplist-item-call'
				),
				\CCrmActivityType::Call . '_' . \CCrmActivityDirection::Incoming => array(
					'title' => Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY_CALL_INCOMING'),
					'icon' => 'crm-doc-droplist-item-call-in'
				),
				\CCrmActivityType::Call . '_' . \CCrmActivityDirection::Outgoing => array(
					'title' => Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY_CALL_OUTGOING'),
					'icon' => 'crm-doc-droplist-item-call-out'
				),
				//email
				\CCrmActivityType::Email => array(
					'title' => Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY_EMAIL'),
					'icon' => 'crm-doc-droplist-item-mail',
				),
				\CCrmActivityType::Email . '_' . \CCrmActivityDirection::Incoming => array(
					'title' => Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY_EMAIL_INCOMING'),
					'icon' => 'crm-doc-droplist-item-mail-in',
				),
				\CCrmActivityType::Email . '_' . \CCrmActivityDirection::Outgoing => array(
					'title' => Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY_EMAIL_OUTGOING'),
					'icon' => 'crm-doc-droplist-item-mail-out',
				),
				//other
				\CCrmActivityType::Meeting => array(
					'title' => Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY_MEETING'),
					'icon' => 'crm-doc-droplist-item-meeting',
				),
				\CCrmActivityType::Task => array(
					'title' => Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY_TASK'),
					'icon' => 'crm-doc-droplist-item-check',
				),
				\CCrmActivityType::Provider => array(
					'title' => '',
					'icon' => 'crm-doc-droplist-item-check',
					'provider' => true
				),
				'default' => array(
					'title' => Loc::getMessage('CRM_ENTITY_TREE_ACTIVITY_DEFAULT'),
					'icon' => 'crm-doc-droplist-item-check'
				)
			);
		}

		$generateDocDropHtml = static function(
			string $classType,
			int $id,
			int $type,
			string $label,
			int $count
		): string
		{
			$result = '';

			if ($count)
			{
				$target = "crm-doc-{$classType}-droplist-wrapper-{$type}-{$id}";
				$result = '<div class="crm-doc-drop-link" data-role="drop-link" data-target="' . $target . '">';
				$result .= '<span class="crm-doc-drop-link-border">' . $label . '</span>';
				$result .= '<span class="crm-doc-drop-link-number"> (' . $count . ')</span>';
				$result .= '</div>';
			}

			return $result;
		};

		if (!empty($activity[$type][$id]) || !empty($document[$type][$id])){
			//add parent lead's activity
			if (
				$leadId &&
				!empty($activity[\CCrmOwnerType::Lead][$leadId]))
			{
				$activity[$type][$id] += $activity[\CCrmOwnerType::Lead][$leadId];
				ksort($activity[$type][$id]);
				$activity[$type][$id] = array_reverse($activity[$type][$id], true);
			}

			$activityCount = count($activity[$type][$id] ?? []);
			$documentCount = count($document[$type][$id] ?? []);

			?>
			<div class="crm-doc-drop">
				<?= $generateDocDropHtml('activity', $id, $type, $activityLabel, $activityCount) ?>
				<?= $generateDocDropHtml('document', $id, $type, $documentLabel, $documentCount) ?>

				<?php
				if ($activityCount)
				{
					?>

					<div
						class="crm-doc-droplist-wrapper"
						id="crm-doc-activity-droplist-wrapper-<?= $type ?>-<?= $id ?>"
					>
						<ul class="crm-doc-droplist">
							<?foreach ($activity[$type][$id] as $item):
								$visual = $activityTypes['default'];
								if (array_key_exists($item['TYPE_ID'], $activityTypes))
								{
									$provider = isset($activityTypes[$item['TYPE_ID']]['provider']) && $activityTypes[$item['TYPE_ID']]['provider'] === true;
									if ($provider && ($provider = \CCrmActivity::GetActivityProvider($item)) !== null)
									{
										$visual = array(
											'title' => $provider::getTypeName($item['PROVIDER_TYPE_ID'], $item['DIRECTION']),
											'icon' => 'crm-doc-droplist-item-'.mb_strtolower($provider::getId())
										);
									}
									elseif (isset($activityTypes[$item['TYPE_ID'] .'_'. $item['DIRECTION']]))
									{
										$visual = $activityTypes[$item['TYPE_ID'] .'_'. $item['DIRECTION']];
									}
									elseif (isset($activityTypes[$item['TYPE_ID']]))
									{
										$visual = $activityTypes[$item['TYPE_ID']];
									}
								}
								?>
								<li class="crm-doc-droplist-item <?= $visual['icon']?>" title="<?= \htmlspecialcharsbx($visual['title']);?>"><?= $item['SUBJECT'];?></li>
							<?endforeach;?>
						</ul>
					</div>

					<?php
				}

				if ($documentCount)
				{
					?>
					<div
						class="crm-doc-droplist-wrapper"
						id="crm-doc-document-droplist-wrapper-<?= $type ?>-<?= $id ?>"
					>
						<ul class="crm-doc-droplist<?=($documentCount === 1) ? ' one-item' : '' ?>">
							<?php
							foreach ($document[$type][$id] as $item)
							{
								?>
								<li class="crm-doc-droplist-item crm-doc-droplist-item-document">
									<a
										href="javascript:void(0);"
										onclick="BX.DocumentGenerator.Document.onBeforeCreate(
											'/bitrix/components/bitrix/crm.document.view/slider.php?documentId=<?= $item['ID'] ?>',
											{sliderWidth: 1060},
											'/bitrix/components/bitrix/crm.document.view/templates/.default/images/document_view.svg'
											)">
										<?= Loc::getMessage('CRM_ENTITY_TREE_DOCUMENT_LABEL', [
											'#TITLE#' => htmlspecialcharsbx($item['TITLE']),
											'#DATE#' => $item['CREATE_TIME'],
										]) ?>
									</a>
								</li>
								<?php
							}
							?>
						</ul>
					</div>

					<?php
				}
				?>
			</div>
			<?
		}
	}
}

if (!function_exists('CrmEntityTreeDrawItem'))
{
	/*
	 * Draw one item of tree.
	 */
	function CrmEntityTreeDrawItem($item, $params, $result)
	{
		static $lang = array();
		static $counter = 0;
		$counter++;
		if (empty($lang))
		{
			$lang = array(
				'ASSIGNED_BY' => Loc::getMessage('CRM_ENTITY_TREE_ASSIGNED_BY'),
				'QUOTE' => Loc::getMessage('CRM_ENTITY_TREE_QUOTE'),
				'INVOICE' => Loc::getMessage('CRM_ENTITY_TREE_INVOICE'),
				'ORDER' => Loc::getMessage('CRM_ENTITY_TREE_ORDER'),
				'ORDER_PAYMENT' => Loc::getMessage('CRM_ENTITY_TREE_ORDER_PAYMENT'),
				'ORDER_SHIPMENT' => Loc::getMessage('CRM_ENTITY_TREE_ORDER_SHIPMENT'),
				'LEAD' => Loc::getMessage('CRM_ENTITY_TREE_LEAD'),
				'DEAL' => Loc::getMessage('CRM_ENTITY_TREE_DEAL'),
				'DATE_BEGIN' => Loc::getMessage('CRM_ENTITY_TREE_DATE_BEGIN'),
				'DATE_CREATE' => Loc::getMessage('CRM_ENTITY_TREE_DATE_CREATE'),
				'DATE_CLOSE' => Loc::getMessage('CRM_ENTITY_TREE_DATE_CLOSE'),
				'DATE_PAYED' => Loc::getMessage('CRM_ENTITY_TREE_DATE_PAYED'),
				'DATE_BILL' => Loc::getMessage('CRM_ENTITY_TREE_DATE_BILL'),
				'SUM' => Loc::getMessage('CRM_ENTITY_TREE_SUM'),
				'EMAIL' => Loc::getMessage('CRM_ENTITY_TREE_EMAIL'),
				'PHONE' => Loc::getMessage('CRM_ENTITY_TREE_PHONE'),
				'SOURCE' => Loc::getMessage('CRM_ENTITY_TREE_SOURCE')
			);
		}
		$statuses = $params['STATUSES'];
		$selected = false;
		if (
			\CCrmOwnerType::ResolveID($params['ENTITY_TYPE_NAME']) === $item['TREE_TYPE']
			&& $params['ENTITY_ID'] == $item['ID']
		)
		{
			$selected = true;
		}

		$category = null;
		if ($item['TREE_TYPE'] === \CCrmOwnerType::Company)
		{
			$factory = Container::getInstance()->getFactory($item['TREE_TYPE']);
			if ($factory)
			{
				$category = $factory->getItemCategory($item['ID']);
			}
		}

		$renderProgressBar = function(string $statusId, array $statuses): string {
			$name = $statuses[$statusId]['NAME'];
			$width = isset($statuses[$statusId]['CHUNK'])
				? round($statuses[$statusId]['CHUNK'] * 100 / $statuses['__COUNT'], 2)
				: 100;
			$color = $statuses[$statusId]['COLOR'];
			if (!preg_match('#^\#[aAbBcCdDeEfF0-9]{3,6}$#', $color))
			{
				$color = \Bitrix\Crm\Color\PhaseColorScheme::getDefaultColorBySemantics($statuses[$statusId]['SEMANTICS'] ?? \Bitrix\Crm\PhaseSemantics::PROCESS);
			}

			return '<div class="crm-doc-info-progressbar">
				<div class="crm-doc-info-progressbar-indikator" style="background-color: '. htmlspecialcharsbx($color) .'; width: '.(int)$width.'%"></div>
			</div>
			<div class="crm-doc-info-text">'.htmlspecialcharsbx($name).'</div>';
		};

		echo '<li class="crm-doc-ul-li">';
		switch ($item['TREE_TYPE'])
		{
			case \CCrmOwnerType::Lead:
				?>
				<div class="crm-doc-cart<?= $selected ? ' crm-tree-active' : ''?><?= $counter == 1 ? ' crm-doc-cart-top' : ''?>">
					<div class="crm-doc-title"><span class="crm-doc-title-gray"><?= $lang['LEAD']?>:</span>
						<a href="<?= $item['URL']?>" class="crm-tree-link" data-id="<?= $item['ID']?>" data-type="<?= $item['TREE_TYPE']?>"><?= htmlspecialcharsbx($item['TITLE'])?></a>
						<?if ($item['IS_RETURN_CUSTOMER'] === 'Y'):?>
							<div>
								<?= Loc::getMessage('CRM_ENTITY_TREE_IS_RETURN_CUSTOMER');?>
							</div>
						<?endif;?>
					</div>
					<div class="crm-doc-info">
						<?if ($item['STATUS_ID']):
							?>
							<div class="crm-doc-info-progress">
								<?=$renderProgressBar($item['STATUS_ID'], $statuses['STATUS']);?>
							</div>
						<?endif;?>
						<?if ($item['ASSIGNED_BY_ID'] > 0):?>
							<div class="crm-doc-info-responsible">
								<div class="crm-doc-info-text"><?= $lang['ASSIGNED_BY']?>:
									<?=CCrmViewHelper::PrepareUserBaloonHtml([
										'PREFIX' => 'LEAD_'.$item['ID'].'_'.$item['ASSIGNED_BY_ID'],
										'USER_ID' => $item['ASSIGNED_BY_ID'],
										'USER_NAME'=> $item['ASSIGNED_BY_FORMATTED_NAME'],
										'USER_PROFILE_URL' => $item['ASSIGNED_BY_URL'],
										'ENCODE_USER_NAME' => true,
									])?>
								</div>
							</div>
						<?endif;?>
						<div class="crm-doc-info-param">
							<table class="crm-doc-table">
								<tbody>
								<?if ($item['SOURCE_ID']):?>
									<tr>
										<td><?= $lang['SOURCE']?>:</td>
										<td><?= \htmlspecialcharsbx($statuses['SOURCE'][$item['SOURCE_ID']]['NAME'])?></td>
									</tr>
								<?endif;?>
								<tr>
									<td><?= $lang['DATE_CREATE']?>:</td>
									<td><?= CrmEntityTreeConvertDateTime($item['DATE_CREATE'], FORMAT_DATE)?></td>
								</tr>
								</tbody>
							</table>
						</div>
						<?CrmEntityTreeDrawActivity($item['ID'], $item['TREE_TYPE'], $result['ACTIVITY'], null, $result['DOCUMENT']);?>
					</div>
				</div>
				<?
				break;
			case \CCrmOwnerType::Company:
			case \CCrmOwnerType::Contact:
				?>
				<div class="crm-doc-cart<?= $selected ? ' crm-tree-active' : ''?><?= $counter == 1 ? ' crm-doc-cart-top' : ''?>">
					<div class="crm-doc-cart-user">
						<div class="crm-doc-cart-<?= $item['TREE_TYPE'] === \CCrmOwnerType::Company ? 'company' : 'user'?>-avatar"<?
						?><?if ($item['TREE_TYPE'] === \CCrmOwnerType::Company && $item['LOGO']){?> style="background-image: url('<?= $item['LOGO_FILE']['src']?>'); background-position: center;"<?}?><?
						?><?if ($item['TREE_TYPE'] === \CCrmOwnerType::Contact && $item['PHOTO']){?> style="background-image: url('<?= $item['PHOTO_FILE']['src']?>'); background-position: center;"<?}?><?
						?>></div>
						<div class="crm-doc-cart-user-info">
							<a href="<?= $item['URL']?>" class="crm-doc-cart-user-name crm-tree-link" data-id="<?= $item['ID']?>" data-type="<?= $item['TREE_TYPE']?>"><?
								if ($item['TREE_TYPE'] === \CCrmOwnerType::Company)
								{
									echo htmlspecialcharsbx($item['TITLE']);
								}
								elseif ($item['TREE_TYPE'] === \CCrmOwnerType::Contact)
								{
									echo htmlspecialcharsbx($item['LAST_NAME'] . ' ' . $item['NAME'] . ' ' . $item['SECOND_NAME']);
								}
								?></a>
							<?if ($item['TREE_TYPE'] === \CCrmOwnerType::Contact && $item['COMPANY_TITLE']):?>
								<div class="crm-doc-cart-user-company"><?= htmlspecialcharsbx($item['COMPANY_TITLE'])?></div>
							<?endif;?>
							<?if (
								$item['TREE_TYPE'] === \CCrmOwnerType::Company
								&& $item['COMPANY_TYPE']
								&& (
									!$category
									|| !in_array(Crm\Item::FIELD_NAME_TYPE_ID, $category->getDisabledFieldNames(), true)
								)
							):?>
								<div class="crm-doc-cart-user-company"><?= htmlspecialcharsbx($statuses['COMPANY_TYPE'][$item['COMPANY_TYPE']]['NAME'])?></div>
							<?endif;?>
							<?CrmEntityTreeDrawActivity(
								$item['ID'],
								$item['TREE_TYPE'],
								$result['ACTIVITY'],
								$item['LEAD_ID'] ?? null,
								$result['DOCUMENT']);
							?>
						</div>
					</div>
					<?if (isset($item['FM_VALUES'])):?>
						<div class="crm-doc-cart-contact">
							<table>
								<?if (isset($item['FM_VALUES']['EMAIL'])):?>
									<tr>
										<td><?= $lang['EMAIL']?>:</td>
										<td>
											<?foreach ($item['FM_VALUES']['EMAIL'] as $p => $val):?>
												<a href="mailto:<?= htmlspecialcharsbx($val)?>" class="crm-doc-gray crm-doc-bold crm-doc-clear crm-doc-cart-contact-item-email"><?= htmlspecialcharsbx($val)?></a>
											<?endforeach;?>
										</td>
									</tr>
								<?endif;?>
								<?if (isset($item['FM_VALUES']['PHONE'])):?>
									<tr>
										<td><?= $lang['PHONE']?>: </td>
										<td>
											<?foreach ($item['FM_VALUES']['PHONE'] as $p => $val):
												$formatCU = \CCrmCallToUrl::PrepareLinkAttributes($val, [
													'ENTITY_TYPE' => \CCrmOwnerType::ResolveName($item['TREE_TYPE']),
													'ENTITY_ID' => $item['ID'],
												]);
												?>
												<a href="<?= htmlspecialcharsbx($formatCU['HREF'])?>"<?
												?><?if ($formatCU['ONCLICK'] != ''){?> onclick="<?= htmlspecialcharsbx($formatCU['ONCLICK'])?>"<?}?><?
												?> class="crm-doc-gray crm-doc-bold crm-doc-clear crm-doc-cart-contact-item-phone"><?
													?><?= $val?><?
													?></a>
											<?endforeach;?>
										</td>
									</tr>
								<?endif;?>
							</table>
						</div>
					<?endif;?>
					<div class="crm-doc-cart-create">
						<?= $lang['DATE_CREATE']?>: <span class="crm-doc-gray"><?= CrmEntityTreeConvertDateTime($item['DATE_CREATE'], FORMAT_DATE)?></span>
					</div>
				</div>
				<?
				break;
			case \CCrmOwnerType::Deal:
				?>
				<div class="crm-doc-cart<?= $selected ? ' crm-tree-active' : ''?><?= $counter == 1 ? ' crm-doc-cart-top' : ''?>">
					<div class="crm-doc-cart-info">
						<a href="<?= $item['URL']?>" class="crm-doc-cart-title crm-doc-cart-title-deal crm-tree-link" data-id="<?= $item['ID']?>" data-type="<?= $item['TREE_TYPE']?>"><?
							?><span class="crm-doc-gray"><?= $lang['DEAL']?>:</span> <?
							?><?= htmlspecialcharsbx($item['TITLE'])?><?
							?></a>
						<?if ($item['ASSIGNED_BY_ID'] > 0):?>
							<div class="crm-doc-info-text"><?= $lang['ASSIGNED_BY']?>:
								<?=CCrmViewHelper::PrepareUserBaloonHtml([
									'PREFIX' => 'DEAL_'.$item['ID'].'_'.$item['ASSIGNED_BY_ID'],
									'USER_ID' => $item['ASSIGNED_BY_ID'],
									'USER_NAME'=> $item['ASSIGNED_BY_FORMATTED_NAME'],
									'USER_PROFILE_URL' => $item['ASSIGNED_BY_URL'],
									'ENCODE_USER_NAME' => true,
								])?>
							</div>
						<?endif;?>
						<?CrmEntityTreeDrawActivity(
							$item['ID'],
							$item['TREE_TYPE'],
							$result['ACTIVITY'],
							$item['LEAD_ID'] ?? null,
							$result['DOCUMENT']);
						?>
					</div>
					<div class="crm-doc-cart-param">
						<div class="crm-doc-info-progress">
							<?if ($item['STAGE_ID']):
								$statusGroup = $item['CATEGORY_ID'] > 0 ? $statuses['DEAL_STAGE_' . $item['CATEGORY_ID']] : $statuses['DEAL_STAGE'];
								echo $renderProgressBar($item['STAGE_ID'], $statusGroup);
							endif;?>
							<table class="crm-doc-info-table">
								<tr>
									<td><?= $lang['DATE_BEGIN']?>:</td>
									<td><?= CrmEntityTreeConvertDateTime($item['BEGINDATE'] ? $item['BEGINDATE'] : $item['DATE_CREATE'], FORMAT_DATE)?></td>
								</tr>
								<?if (isset($item['CLOSEDATE'])):?>
									<tr>
										<td><?= $lang['DATE_CLOSE']?>:</td>
										<td><?= CrmEntityTreeConvertDateTime($item['CLOSEDATE'], FORMAT_DATE)?></td>
									</tr>
								<?endif;?>
								<?if ($item['OPPORTUNITY'] > 0):?>
									<tr>
										<td><?= $lang['SUM']?>:</td>
										<td><?= $item['OPPORTUNITY_FORMATTED']?></td>
									</tr>
								<?endif;?>
							</table>
						</div>
					</div>
				</div>
				<?
				break;
			case \CCrmOwnerType::Quote:
				?>
				<div class="crm-doc-cart<?= $selected ? ' crm-tree-active' : ''?><?= $counter == 1 ? ' crm-doc-cart-top' : ''?>">
					<div class="crm-doc-cart-info">
						<a href="<?= $item['URL']?>" target="_top" class="crm-doc-cart-title crm-doc-cart-title-sentence crm-tree-link" data-id="<?= $item['ID']?>" data-type="<?= $item['TREE_TYPE']?>"><?
							?><span class="crm-doc-gray"><?= $lang['QUOTE']?>:</span> <?
							?><?= htmlspecialcharsbx($item['TITLE'])?><?
							?></a>
						<?if ($item['ASSIGNED_BY_ID'] > 0):?>
							<div class="crm-doc-info-text"><?= $lang['ASSIGNED_BY']?>:
								<?=CCrmViewHelper::PrepareUserBaloonHtml([
									'PREFIX' => 'QUOTE_'.$item['ID'].'_'.$item['ASSIGNED_BY_ID'],
									'USER_ID' => $item['ASSIGNED_BY_ID'],
									'USER_NAME'=> $item['ASSIGNED_BY_FORMATTED_NAME'],
									'USER_PROFILE_URL' => $item['ASSIGNED_BY_URL'],
									'ENCODE_USER_NAME' => true,
								])?>
							</div>
						<?endif;?>
						<?CrmEntityTreeDrawActivity(
							$item['ID'],
							$item['TREE_TYPE'],
							$result['ACTIVITY'],
							$item['LEAD_ID'] ?? null,
							$result['DOCUMENT']);
						?>
					</div>
					<div class="crm-doc-cart-param">
						<div class="crm-doc-info-progress">
							<?if ($item['STATUS_ID']):
								echo $renderProgressBar($item['STATUS_ID'], $statuses['QUOTE_STATUS']);
							endif;?>
							<table class="crm-doc-info-table">
								<col class="crm-doc-info-table-1">
								<col class="crm-doc-info-table-2">
								<tr>
									<td><?= $lang['DATE_BILL']?>:</td>
									<td><?= CrmEntityTreeConvertDateTime($item['BEGINDATE'] ? $item['BEGINDATE'] : $item['DATE_CREATE'], FORMAT_DATE)?></td>
								</tr>
								<?if ($item['CLOSEDATE']):?>
									<tr>
										<td><?= $lang['DATE_CLOSE']?>:</td>
										<td><?= CrmEntityTreeConvertDateTime($item['CLOSEDATE'], FORMAT_DATE)?></td>
									</tr>
								<?endif;?>
							</table>
						</div>
					</div>
				</div>
				<?
				break;
			case \CCrmOwnerType::Order:
			case \CCrmOwnerType::OrderPayment:
			case \CCrmOwnerType::OrderShipment:
			case \CCrmOwnerType::Invoice:
				?>
				<div class="crm-doc-cart<?= $selected ? ' crm-tree-active' : ''?><?= $counter == 1 ? ' crm-doc-cart-top' : ''?>">
					<div class="crm-doc-cart-info">
						<a href="<?= $item['URL']?>" target="_top" class="crm-doc-cart-title crm-doc-cart-title-invoice crm-tree-link" data-id="<?= $item['ID']?>" data-type="<?= $item['TREE_TYPE']?>"><?
							?><span class="crm-doc-gray"><?= $lang[\CCrmOwnerType::ResolveName($item['TREE_TYPE'])]?><?= htmlspecialcharsbx($item['ACCOUNT_NUMBER'])?>:</span> <?
							?><?= $item['ORDER_TOPIC'] <> '' ? htmlspecialcharsbx($item['ORDER_TOPIC']) : Loc::getMessage('CRM_ENTITY_TREE_UNTITLED')?><?
							?></a>
						<?if ($item['RESPONSIBLE_ID'] > 0):?>
							<div class="crm-doc-info-text"><?= $lang['ASSIGNED_BY']?>:
								<?=CCrmViewHelper::PrepareUserBaloonHtml([
									'PREFIX' => 'INVOICE_'.$item['ID'].'_'.$item['RESPONSIBLE_ID'],
									'USER_ID' => $item['RESPONSIBLE_ID'],
									'USER_NAME'=> $item['RESPONSIBLE_FORMATTED_NAME'],
									'USER_PROFILE_URL' => $item['RESPONSIBLE_URL'],
									'ENCODE_USER_NAME' => true,
								])?>
							</div>
						<?endif;?>
						<?CrmEntityTreeDrawActivity($item['ID'], $item['TREE_TYPE'], $result['ACTIVITY'], null, $result['DOCUMENT']);?>
					</div>
					<div class="crm-doc-cart-param">
						<div class="crm-doc-info-progress">
							<?if ($item['STATUS_ID']):
								$typeStatusName = \CCrmOwnerType::ResolveName($item['TREE_TYPE']) . '_STATUS';
								echo $renderProgressBar($item['STATUS_ID'], $statuses[$typeStatusName]);
							endif;?>
							<table class="crm-doc-info-table">
								<tr>
									<td><?= $lang['DATE_BILL']?>:</td>
									<td><?= CrmEntityTreeConvertDateTime($item['DATE_BILL'] ? $item['DATE_BILL'] : $item['DATE_INSERT_FORMAT'], FORMAT_DATE)?></td>
								</tr>
								<?if ($item['DATE_PAY_BEFORE']):?>
									<tr>
										<td><?= $lang['DATE_PAYED']?>:</td>
										<td><?= CrmEntityTreeConvertDateTime($item['DATE_PAY_BEFORE'], FORMAT_DATE)?></td>
									</tr>
								<?endif;?>
								<tr>
									<td><?= $lang['SUM']?>:</td>
									<td><?= $item['PRICE_FORMATTED']?></td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				<?
				break;
			case \CCrmOwnerType::StoreDocument:
				?>
				<div class="crm-doc-cart<?= $selected ? ' crm-tree-active' : ''?><?= $counter == 1 ? ' crm-doc-cart-top' : ''?>">
					<div class="crm-doc-cart-info">
						<a href="<?=$item['URL']?>" target="_top" class="crm-doc-cart-title crm-doc-cart-title-sentence crm-tree-link" data-id="<?=$item['ID']?>" data-type="<?=$item['TREE_TYPE']?>">
							<span class="crm-doc-gray">
								<?=htmlspecialcharsbx($item['TITLE'])?>
							</span>
						</a>
						<?if ($item['RESPONSIBLE_ID'] > 0):?>
							<div class="crm-doc-info-text"><?= $lang['ASSIGNED_BY']?>:
								<?=CCrmViewHelper::PrepareUserBaloonHtml([
									'PREFIX' => 'INVOICE_'.$item['ID'].'_'.$item['RESPONSIBLE_ID'],
									'USER_ID' => $item['RESPONSIBLE_ID'],
									'USER_NAME'=> $item['RESPONSIBLE_FORMATTED_NAME'],
									'USER_PROFILE_URL' => $item['RESPONSIBLE_URL'],
									'ENCODE_USER_NAME' => true,
								])?>
							</div>
						<?endif;?>
						<?CrmEntityTreeDrawActivity($item['ID'], $item['TREE_TYPE'], $result['ACTIVITY'], null, $result['DOCUMENT']);?>
					</div>
					<div class="crm-doc-cart-param">
						<div class="crm-doc-info-progress">
							<table class="crm-doc-info-table">
								<tr>
									<td>
										<?=Loc::getMessage('CRM_ENTITY_TREE_STORE_DOCUMENT_STATUS')?>:
									</td>
									<td>
										<?=StoreDocumentTable::getStatusName($item['STATUS'])?>
									</td>
								</tr>
								<tr>
									<td>
										<?=Loc::getMessage('CRM_ENTITY_TREE_STORE_DOCUMENT_DATE_STATUS')?>:
									</td>
									<td>
										<?=CrmEntityTreeConvertDateTime($item['DATE_STATUS'], FORMAT_DATE)?></td>
								</tr>
								<tr>
									<td><?=Loc::getMessage('CRM_ENTITY_TREE_STORE_DOCUMENT_TOTAL')?>:</td>
									<td><?=\CCrmCurrency::MoneyToString($item['TOTAL'], $item['CURRENCY']);?></td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				<?
				break;
			default:
				if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($item['TREE_TYPE'])):
					?>
					<div class="crm-doc-cart<?= $selected ? ' crm-tree-active' : ''?><?= $counter == 1 ? ' crm-doc-cart-top' : ''?>">
						<div class="crm-doc-cart-info">
							<a href="<?= $item['URL']?>" target="_top" class="crm-doc-cart-title crm-doc-cart-title-invoice crm-tree-link" data-id="<?= $item['ID']?>" data-type="<?= $item['TREE_TYPE']?>"><?
								?><span class="crm-doc-gray"><?= htmlspecialcharsbx(\CCrmOwnerType::GetDescription($item['TREE_TYPE']))?>: </span><?= htmlspecialcharsbx($item['NAME'])?><?
								?></a>
							<div class="crm-doc-info-text"><?= $lang['ASSIGNED_BY']?>:
								<?=CCrmViewHelper::PrepareUserBaloonHtml([
									'USER_ID' => $item['ASSIGNED_BY_ID'],
									'USER_NAME'=> $item['ASSIGNED_BY_FORMATTED_NAME'],
									'USER_PROFILE_URL' => $item['ASSIGNED_BY_URL'],
									'ENCODE_USER_NAME' => true,
								]);
								?>
							</div>
							<?CrmEntityTreeDrawActivity($item['ID'], $item['TREE_TYPE'], $result['ACTIVITY'], null, $result['DOCUMENT']);?>
						</div>
						<div class="crm-doc-cart-param">
							<div class="crm-doc-info-progress">
								<?if ($item['STAGE_ID']):
									$typeStatusName = \CCrmOwnerType::ResolveName($item['TREE_TYPE']) . '_STAGE_' . $item['CATEGORY_ID'];
									echo $renderProgressBar($item['STAGE_ID'], $statuses[$typeStatusName] ?? []);
								endif;?>
								<table class="crm-doc-info-table">
									<tr>
										<td><?= $lang['DATE_CREATE']?>:</td>
										<td><?= CrmEntityTreeConvertDateTime($item['CREATED_TIME'], FORMAT_DATE)?></td>
									</tr>
									<?if (!empty($item['OPPORTUNITY_FORMATTED'])):?>
										<tr>
											<td><?= $lang['SUM']?>:</td>
											<td><?= $item['OPPORTUNITY_FORMATTED']?></td>
										</tr>
									<?endif;?>
								</table>
							</div>
						</div>
					</div>
				<?php
				endif;
				break;
		}
		echo '</li>';
	}
}

if (!function_exists('CrmEntityTreeDrawRecur'))
{
	/*
	 * Draw tree recursive.
	 */
	function CrmEntityTreeDrawRecur($entities, $params, $result)
	{
		echo '<ul class="crm-doc-ul">';
		foreach ($entities as $type => $entity)
		{
			foreach ($entity as $id => $entityItem)
			{
				CrmEntityTreeDrawItem($entityItem, $params, $result);
				if (isset($entityItem['SUB_ENTITY']) && !empty($entityItem['SUB_ENTITY']))
				{
					CrmEntityTreeDrawRecur($entityItem['SUB_ENTITY'], $params, $result);
				}
			}
			//echo '<a href="javascript:void(0);" class="crm-entity-more" data-page="', $params[$entity['TREE_TYPE'] . '_PAGE'], '" data-block="', $type, '">???</a>';
		}
		echo '</ul>';
	}
}
?>
<div class="crm-doc">
	<div class="crm-doc-three">
		<?
		//parent with base element
		foreach ($arResult['BASE'] as $item)
		{
			echo '<ul class="crm-doc-ul">';
			CrmEntityTreeDrawItem($item, $arParams, $arResult);
		}
		CrmEntityTreeDrawRecur($arResult['TREE'], $arParams, $arResult);
		echo str_repeat('</ul>', count($arResult['BASE']));
		?>
	</div>
</div>

<script>
	BX.ready(function ()
	{
		var dropLink = document.querySelectorAll('[data-role="drop-link"]');
		var openClass = 'crm-doc-drop-open';
		var openItemClass = 'crm-doc-drop-item-open';
		var openedElement = null;

		for(var i = 0; i <= dropLink.length; i++) {
			BX.bind(dropLink[i], 'click', function ()
			{
				if ('target' in this.dataset)
				{
					var openItems = document.querySelectorAll('.' + openItemClass);
					Array.from(openItems).forEach(function(openItem){
						openItem.classList.remove(openItemClass);
					});

					if (openedElement)
					{
						openedElement.style.height = '0px';
						openedElement.parentNode.classList.remove(openClass);
					}

					var target = this.dataset.target;
					var targetElement = document.getElementById(target);
					var getNextElheight = targetElement.offsetHeight;
					var getNextInner = targetElement.firstElementChild.offsetHeight;
					if (getNextElheight > 0)
					{
						targetElement.style.height = '0px';
						this.parentNode.classList.remove(openClass);
					}
					else
					{
						targetElement.style.height = getNextInner + 'px';
						this.parentNode.classList.add(openClass);
						this.classList.add(openItemClass);
						openedElement = targetElement;
					}
				}
			})
		}
	});
</script>
