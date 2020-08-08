<?php
namespace Bitrix\Crm\Widget\Data\Activity;
use Bitrix\Main;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Widget\FilterPeriodType;
use Bitrix\Crm\Widget\Data\Activity\ChannelStatistics as ActivityChannelStatistics;
use Bitrix\Crm\Integration\Channel\IChannelInfo;
use Bitrix\Crm\Integration\Channel\IChannelGroupInfo;
use Bitrix\Crm\Integration\Channel\ChannelTrackerManager;
use Bitrix\Crm\Integration\Channel\ChannelType;
use Bitrix\Main\Localization\Loc;

class ActivityDynamic extends DataSource
{
	const TYPE_NAME = 'ACTIVITY_DYNAMIC';
	static $counters = array();
	private static $data = null;
	private static $messagesLoaded = array();
	/**
	 * @return string
	 */
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}

	/**
	 * @param array $params
	 * @return array
	 */
	public function getList(array $params)
	{
		/** @var Filter $filter */
		$filter = isset($params['filter']) ? $params['filter'] : null;
		if(!($filter instanceof Filter))
			throw new Main\ObjectNotFoundException("The 'filter' is not found in params.");

		$source = new ActivityChannelStatistics(array());
		$results = $source->getList(
			array(
				'filter' => $filter,
				'select' => array(array('name' => 'COUNT', 'aggregate' => 'COUNT')),
				'group' => array(
//						ActivityChannelStatistics::GROUP_BY_DATE,
//						ActivityChannelStatistics::GROUP_BY_USER,
					ActivityChannelStatistics::GROUP_BY_CHANNEL,
					ActivityChannelStatistics::GROUP_BY_DIRECTION
				)
			)
		);
		$items = array();
		foreach (self::getChannelData() as $i => $item)
		{
			if (!isset($item["URL"]))
			{
				foreach($item['PARAMS'] as $k => $v)
					$filter->setExtraParam($k, $v);
				$item["URL"] = $source->getDetailsPageUrl(array('filter' => $filter));
			}
			$items[$i] = $item;
		}
		if (\Bitrix\Crm\Integration\Channel\VoxImplantTracker::getInstance()->isEnabled() &&
			\Bitrix\Crm\Integration\Channel\VoxImplantTracker::getInstance()->isInUse())
		{
			$deleteItems = array(
				\Bitrix\Crm\Integration\Channel\ChannelType::VOXIMPLANT_NAME => 0,
				\Bitrix\Crm\Integration\Channel\ChannelType::VOXIMPLANT_NAME.'|'.\CVoxImplantConfig::LINK_BASE_NUMBER => 0);
			foreach ($results as $res)
			{
				if (isset($deleteItems[$res["CHANNEL_ID"]]))
				{
					unset($deleteItems[$res["CHANNEL_ID"]]);
					if (empty($deleteItems))
						break;
				}
			}
			foreach ($deleteItems as $k => $c)
				unset($items[$k]);
		}
		else if (\Bitrix\Crm\Integration\Channel\VoxImplantTracker::getInstance()->isEnabled())
		{
			unset($items[\Bitrix\Crm\Integration\Channel\ChannelType::VOXIMPLANT_NAME]);
		}

		return array(
			array(
				"title" => GetMessage("CRM_ACTIVITY_DYNAMIC_TITLE"),
				"html" => self::parseTemplate(self::getGroups(), $items, ($results?:array())),
			)
		);
	}

	/**
	 * @param array $groupItems
	 * @param array $items
	 * @param array $results
	 * @return string
	 */
	public static function parseTemplate(array $groupItems, array $items, array $results)
	{
		if (!(is_array($groupItems) && !empty($groupItems) && is_array($items) && !empty($items)))
		{
			Loc::getMessage("CRM_ACTIVITY_DYNAMIC_CHANNELS_ARE_NOT_FOUND");
		}

		$mess = self::includeModuleFile();

		$maxCounter = array(
			\CCrmActivityDirection::Incoming => 0,
			\CCrmActivityDirection::Outgoing => 0
		);

		foreach ($results as $res)
		{
			$value = (int)$res["COUNT"];
			if (array_key_exists($res["CHANNEL_ID"], $items))
			{
				$item = &$items[$res["CHANNEL_ID"]];
				$direction = ((int)$res["DIRECTION"] == \CCrmActivityDirection::Outgoing ? \CCrmActivityDirection::Outgoing : \CCrmActivityDirection::Incoming);
				$item["COUNTERS"][$direction] += $value;
				$groupID = $item["GROUP_ID"];
				if ($groupID !== '' && isset($groupItems[$groupID]))
				{
					$groupItems[$groupID]["COUNTERS"][$direction] += $item["COUNTERS"][$direction];
					$groupItems[$groupID]["MAX_COUNTER"][$direction] = max($groupItems[$groupID]["MAX_COUNTER"][$direction], $item["COUNTERS"][$direction]);
					$maxCounter[$direction] = max($maxCounter[$direction], $groupItems[$groupID]["COUNTERS"][$direction]);
					$parentId = $groupItems[$groupID]["PARENT_ID"];
					if ($parentId && isset($groupItems[$parentId]))
					{
						$groupItems[$parentId]["COUNTERS"][$direction] += $item["COUNTERS"][$direction];
						$groupItems[$parentId]["MAX_COUNTER"][$direction] = max($groupItems[$parentId]["MAX_COUNTER"][$direction], $item["COUNTERS"][$direction]);
						$maxCounter[$direction] = max($maxCounter[$direction], $groupItems[$parentId]["COUNTERS"][$direction]);
					}
				}
			}
		}
		unset($item);

		$activities = array("active" => "", "inactive" => "");
		$inactiveItems = array();
		$inactiveItemCaptions = array();
		$activeCount = 0;
		$telephonyIsChecked = false;
		foreach($activities as $status => &$html)
		{
			if ($item = reset($items))
			{
				$groupStack = array();
				$innerHTML = array();
				$currentGroupID = '';
				do
				{
					$groupID = $item['GROUP_ID'];
					$group = isset($groupItems[$groupID]) ? $groupItems[$groupID] : null;
					if ($status === "active" && ($groupID === '' || is_null($group) ||
							isset($group['IS_IN_USE']) && !$group['IS_IN_USE'] ||
							isset($item['IS_IN_USE']) && !$item['IS_IN_USE']))
					{
						if (isset($item['IS_CONFIGURABLE']) && $item['IS_CONFIGURABLE'])
						{
							$inactiveItems[] = $item;
							if ($groupID !== \Bitrix\Crm\Integration\Channel\VoxImplantTracker::GROUP_ID)
							{
								$inactiveItemCaptions[] = $item["CAPTION"];
							}
							else if ($telephonyIsChecked === false)
							{
								$telephonyIsChecked = true;
								$inactiveItemCaptions[] = $group["CAPTION"];
							}
						}
						continue;
					}
					$activeCount++;
					if ($groupID !== $currentGroupID)
					{
						$innerHTML["#" . $groupID . "#"] = "";
						$parent = ($group === null ? null : $group["PARENT_ID"]);
						$parentGroup = isset($groupItems[$parent]) ? $groupItems[$parent] : null;

						if (is_null($group) || ($status === "active" && ($parentGroup === null || $parentGroup["ID"] != ChannelType::IMOPENLINE_NAME) && $group['ACTIVE_ITEMS_COUNT'] <= 1))
						{
							if (!is_null($group))
								$group["hidden"] = "Y";
							$html .= "#" . $groupID . "#";
						}
						else
						{
							$groups = array();
							reset($groupStack);
							while ($parentGroup !== null)
							{
								if ($parentGroup && (
									$status !== "active" ||
									$parentGroup["ID"] != ChannelType::IMOPENLINE_NAME ||
									$parentGroup["ITEMS_COUNT"] > $group["ITEMS_COUNT"])
								)
								{
									$groups[] = array(
										"id" => $parentGroup['ID'],
										"url" => $parentGroup['IS_CONFIGURABLE'] ? $parentGroup['URL'] : '',
										"caption" => $parentGroup['CAPTION'],
										"counters" => $parentGroup["COUNTERS"]);
									$groupStack[] = $parent;
									$parent = $parentGroup["PARENT_ID"];
								}
								else
								{
									$parent = null;
								}
								$parentGroup = isset($groupItems[$parent]) ? $groupItems[$parent] : null;
							}
							$groups = array_reverse($groups);
							$groups[] = array(
								"id" => $group['ID'],
								"caption" => $group['CAPTION'],
								"url" => $group['IS_CONFIGURABLE'] ? $group['URL'] : '',
								"counters" => $group["COUNTERS"]);
							$g = reset($groups);
							$lastGroupID = null;
							do
							{
								$id = "#{$g["id"]}#";
								$caption = htmlspecialcharsbx($g["caption"]);
								$caption = ($g["url"] !== '' ? '<a target="_blank" href="' . htmlspecialcharsbx($g["url"]) . '#activityDynamic" title="' . $caption . '">' . $caption . '</a>' : '<span title="' . $caption . '">' . $caption . '</span>');
								if (mb_strpos($html, $id) === false)
								{
									if ($status !== "active")
									{
										$g["counters"][\CCrmActivityDirection::Incoming] = 0;
										$g["counters"][\CCrmActivityDirection::Outgoing] = 0;
									}
									$g["percentage"][\CCrmActivityDirection::Incoming] = 0;
									if ($maxCounter[\CCrmActivityDirection::Incoming] > 0)
										$g["percentage"][\CCrmActivityDirection::Incoming] = intval($g["counters"][\CCrmActivityDirection::Incoming] * 100 / $maxCounter[\CCrmActivityDirection::Incoming]);
									$g["percentage"][\CCrmActivityDirection::Incoming] = max($g["percentage"][\CCrmActivityDirection::Incoming], 1);
									$g["percentage"][\CCrmActivityDirection::Outgoing] = 0;
									if ($maxCounter[\CCrmActivityDirection::Outgoing] > 0)
										$g["percentage"][\CCrmActivityDirection::Outgoing] = intval($g["counters"][\CCrmActivityDirection::Outgoing] * 100/ $maxCounter[\CCrmActivityDirection::Outgoing]);
									$g["percentage"][\CCrmActivityDirection::Outgoing] = max($g["percentage"][\CCrmActivityDirection::Outgoing], 1);
									$htmlG = <<<HTML
							<div class="crm-widget-activity-dynamic-item">
								<div class="crm-widget-activity-dynamic-item-header" data-role="open-chanel">
									<div class="crm-widget-activity-dynamic-graph">
										<div class="crm-widget-activity-dynamic-graph-half">
											<div class="crm-widget-activity-dynamic-graph-total">{$g["counters"][\CCrmActivityDirection::Incoming]}</div>
											<div class="crm-widget-activity-dynamic-graph-line" style="max-width: {$g["percentage"][\CCrmActivityDirection::Incoming]}%"></div>
										</div>
										<div class="crm-widget-activity-dynamic-graph-half crm-widget-activity-dynamic-graph-half-right">
											<div class="crm-widget-activity-dynamic-graph-line" style="max-width: {$g["percentage"][\CCrmActivityDirection::Outgoing]}%"></div>
											<div class="crm-widget-activity-dynamic-graph-total">{$g["counters"][\CCrmActivityDirection::Outgoing]}</div>
										</div>
									</div>
									<div class="crm-widget-activity-dynamic-open"></div>
									<div class="crm-widget-activity-dynamic-title">{$caption}</div>
								</div>
								<div class="crm-widget-activity-dynamic-deep" data-role="inner-chanel">
									<div class="crm-widget-activity-dynamic-deep-wrapper">
										{$id}
									</div>
								</div>
							</div>
HTML;
									if ($lastGroupID === null)
									{
										$html .= $htmlG;
									}
									else
									{
										$html = str_replace("#{$lastGroupID}#", $htmlG . "#{$lastGroupID}#", $html);
									}

									if (!isset($innerHTML[$id]))
									{
										$innerHTML[$id] = "";
									}
								}
								$lastGroupID = $g["id"];
							} while ($g = next($groups));
						}
					}
					$caption = htmlspecialcharsbx($item["CAPTION"]);
					$caption = ($item["CONFIG_URL"] !== '' && $item['IS_CONFIGURABLE'] ? '<a target="_blank" href="' . htmlspecialcharsbx($item["CONFIG_URL"]) . '#activityDynamic">' . $caption . '</a>' : $caption);

					$item["PERCENTAGE"][\CCrmActivityDirection::Incoming] = 0;
					$item["PERCENTAGE"][\CCrmActivityDirection::Outgoing] = 0;
					if (is_null($group) || $group["hidden"] === "Y")
					{
						if ($maxCounter[\CCrmActivityDirection::Incoming] > 0)
							$item["PERCENTAGE"][\CCrmActivityDirection::Incoming] = intval($item["COUNTERS"][\CCrmActivityDirection::Incoming] * 100 / $maxCounter[\CCrmActivityDirection::Incoming]);
						$item["PERCENTAGE"][\CCrmActivityDirection::Incoming] = max($item["PERCENTAGE"][\CCrmActivityDirection::Incoming], 1);
						if ($maxCounter[\CCrmActivityDirection::Outgoing] > 0)
							$item["PERCENTAGE"][\CCrmActivityDirection::Outgoing] = intval($item["COUNTERS"][\CCrmActivityDirection::Outgoing] * 100/ $maxCounter[\CCrmActivityDirection::Outgoing]);
						$item["PERCENTAGE"][\CCrmActivityDirection::Outgoing] = max($item["PERCENTAGE"][\CCrmActivityDirection::Outgoing], 1);

						$innerHTML["#" . $groupID . "#"] .= <<<HTML
							<div class="crm-widget-activity-dynamic-item crm-widget-activity-dynamic-item-final">
								<div class="crm-widget-activity-dynamic-item-header">
									<div class="crm-widget-activity-dynamic-graph">
										<div class="crm-widget-activity-dynamic-graph-half">
											<div class="crm-widget-activity-dynamic-graph-total">{$item["COUNTERS"][\CCrmActivityDirection::Incoming]}</div>
											<div class="crm-widget-activity-dynamic-graph-line" style="max-width: {$item["PERCENTAGE"][\CCrmActivityDirection::Incoming]}%"></div>
										</div>
										<div class="crm-widget-activity-dynamic-graph-half crm-widget-activity-dynamic-graph-half-right">
											<div class="crm-widget-activity-dynamic-graph-line" style="max-width: {$item["PERCENTAGE"][\CCrmActivityDirection::Outgoing]}%"></div>
											<div class="crm-widget-activity-dynamic-graph-total">{$item["COUNTERS"][\CCrmActivityDirection::Outgoing]}</div>
										</div>
									</div>
									<div class="crm-widget-activity-dynamic-open"></div>
									<div class="crm-widget-activity-dynamic-title">{$caption}</div>
								</div>
							</div>
HTML;
					}
					else
					{
						if ($group["MAX_COUNTER"][\CCrmActivityDirection::Incoming] > 0)
							$item["PERCENTAGE"][\CCrmActivityDirection::Incoming] = intval($item["COUNTERS"][\CCrmActivityDirection::Incoming] * 100 / $maxCounter[\CCrmActivityDirection::Incoming]);
						$item["PERCENTAGE"][\CCrmActivityDirection::Incoming] = max($item["PERCENTAGE"][\CCrmActivityDirection::Incoming], 1);
						if ($group["MAX_COUNTER"][\CCrmActivityDirection::Outgoing] > 0)
							$item["PERCENTAGE"][\CCrmActivityDirection::Outgoing] = intval($item["COUNTERS"][\CCrmActivityDirection::Outgoing] * 100/ $maxCounter[\CCrmActivityDirection::Outgoing]);
						$item["PERCENTAGE"][\CCrmActivityDirection::Outgoing] = max($item["PERCENTAGE"][\CCrmActivityDirection::Outgoing], 1);
						$innerHTML["#" . $groupID . "#"] .= <<<HTML
										<div class="crm-widget-activity-dynamic-deep-item">
											<div class="crm-widget-activity-dynamic-deep-title">{$caption}</div>
											<div class="crm-widget-activity-dynamic-graph">
												<div class="crm-widget-activity-dynamic-graph-half">
													<div class="crm-widget-activity-dynamic-graph-total">{$item["COUNTERS"][\CCrmActivityDirection::Incoming]}</div>
													<div class="crm-widget-activity-dynamic-graph-line" style="max-width: {$item["PERCENTAGE"][\CCrmActivityDirection::Incoming]}%"></div>
												</div>
												<div class="crm-widget-activity-dynamic-graph-half crm-widget-activity-dynamic-graph-half-right">
													<div class="crm-widget-activity-dynamic-graph-line" style="max-width: {$item["PERCENTAGE"][\CCrmActivityDirection::Outgoing]}%"></div>
													<div class="crm-widget-activity-dynamic-graph-total">{$item["COUNTERS"][\CCrmActivityDirection::Outgoing]}</div>
												</div>
											</div>
										</div>
HTML;
					}
					$currentGroupID = $groupID;
				}
				while ($item = next($items)) ;
				$html = str_replace(array_keys($innerHTML), array_values($innerHTML), $html);
			}
			$items = $inactiveItems;
		}
		$innerHTML = '';

		if ($activities["active"] !== "")
		{
			$innerHTML = <<<HTML
			<div class="crm-widget-activity-dynamic">
				<div class="crm-widget-activity-dynamic-header">
					<div class="crm-widget-activity-dynamic-header-title">{$mess["CRM_ACTIVITY_DYNAMIC_CONNECTED_CHANNELS"]}</div>
					<div class="crm-widget-activity-dynamic-header-title-column">
						<div class="crm-widget-activity-dynamic-header-title-inner">{$mess["CRM_ACTIVITY_DYNAMIC_CONNECTED_CHANNELS_IN"]}</div>
						<div class="crm-widget-activity-dynamic-header-title-outer">{$mess["CRM_ACTIVITY_DYNAMIC_CONNECTED_CHANNELS_OUT"]}</div>
					</div>
				</div>{$activities["active"]}
			</div>
HTML;
		}
		$id = randString(10);
		if ($activities["inactive"] !== "")
		{
			$inactiveItemCaptions = implode(", ", array_slice($inactiveItemCaptions, 0, 3))." ".(count($inactiveItemCaptions) > 3 ? $mess["CRM_ACTIVITY_DYNAMIC_UNCONNECTED_1"] : "");
			$inactiveItems = count($inactiveItems);
			$activeCount -= $inactiveItems;
			$innerHTML .= <<<HTML
			<div class="crm-widget-activity-dynamic">
				<div class="crm-widget-activity-dynamic-more" id="custom_widget_inactive_control_$id">
					<div class="crm-widget-activity-dynamic-more-toggler"></div>
					<div class="crm-widget-activity-dynamic-more-header">
						<div class="crm-widget-activity-dynamic-more-left">
							<span class="crm-widget-activity-dynamic-connect-more">{$mess["CRM_ACTIVITY_DYNAMIC_UNCONNECTED_3"]}</span>
							<a href="#" class="crm-widget-activity-dynamic-link" onclick="return false;">{$inactiveItemCaptions}</a>
						</div>
						<div class="crm-widget-activity-dynamic-more-right">
							<div class="crm-widget-activity-dynamic-connected">
								{$mess["CRM_ACTIVITY_DYNAMIC_CONNECTED"]}{$activeCount}</div>
							<div class="crm-widget-activity-dynamic-connect">{$mess["CRM_ACTIVITY_DYNAMIC_CONNECT"]}{$inactiveItems}</div>
							<div class="crm-widget-activity-dynamic-show-more"></div>
						</div>
					</div>
				</div>
				<div id="custom_widget_inactive_container_$id" style="display: none;">{$activities["inactive"]}</div>
			</div>
HTML;
		}

		if ($innerHTML !== '')
		{
			$videoUrl = GetMessageJS("CRM_CH_TRACKER_START_VIDEO", array("#VOLUME#" => ""));
			$videoFnc = "";
			if (!(($res = \CUserOptions::GetOption("crm.widget", "activityDynamic")) && is_array($res) && $res["firstSeen"] === "N"))
			{
				$videoFnc = "f(true);";
				$videoUrl = GetMessageJS("CRM_CH_TRACKER_START_VIDEO", array("#VOLUME#" => "&volume=0&mute=1"));
			}

			$innerHTML .= <<<HTML
<script>
BX.ready(function() {
	var node = BX('custom_widget_{$id}');
	if (node && (node = BX.findParent(node, {className : "crm-widget-container"})) && node)
		BX.addClass(node, "crm-widget-container-height-auto");
	if (node && (node = BX.findParent(node, {className : "crm-widget-row"})) && node)
		BX.addClass(node, "crm-widget-row-height-auto");
	var openChanelDeeper = function() {
			var block				= this.parentNode,
				blockDeeper 		= block.querySelector('[data-role="inner-chanel"]'),
				blockDeeperHeight 	= blockDeeper.firstElementChild.offsetHeight,
				i;

			if (block.classList.contains('crm-widget-activity-dynamic-item-open'))
			{
				block.classList.remove('crm-widget-activity-dynamic-item-open');
				blockDeeper.style.height = '';

				var blockDeeperInner = block.querySelectorAll('[data-role="inner-chanel"]');

				for (i = 0; i < blockDeeperInner.length; i++)
				{
					blockDeeperInner[i].parentNode.classList.remove('crm-widget-activity-dynamic-item-open');
					blockDeeperInner[i].style.height = '';
				}
			}
			else
			{
				block.classList.add('crm-widget-activity-dynamic-item-open');
				blockDeeper.style.height = blockDeeperHeight + 'px';
				setTimeout(function () { blockDeeper.style.height = 'auto'; }, 220);
			}
		}, 
		showChanelButton = node.querySelectorAll('[data-role="open-chanel"]'), i;
	for (i = 0; i < showChanelButton.length; i++)
		BX.bind(showChanelButton[i], "click", openChanelDeeper);
	var visState = false;
	BX.bind(BX('custom_widget_inactive_control_$id'), "click", function() {
		BX.fx[(visState === true ? "hide" : "show")](BX('custom_widget_inactive_container_$id'));
		visState = !visState;
	});
	var f = function(firstTime) {
		(new BX.PopupWindow('crm-start-video-window', null, {
			className: "crm-start-video-window",
			autoHide: false,
			zIndex: 200,
			overlay: {opacity: 50, backgroundColor: "#000000"},
			closeByEsc: true,
			closeIcon : true,
			contentColor : "white",
			content : '{$videoUrl}',
			events : {
				onPopupClose : function(){
					this.destroy();
					BX.onCustomEvent(window, "crm.widget", ["activityDynamic", "close", BX("custom_widget_{$id}_click_node")]);
					BX.userOptions.save('crm.widget', 'activityDynamic', 'firstSeen', 'N');
				}
			}
		})).show();
	};
	node = BX.findParent(BX('custom_widget_{$id}'), {className : "crm-widget-container"});
	if (node && (node = BX.findChild(node, {className : "crm-widget-settings"}, true)) && node)
	{
		var vNode = node.cloneNode(true);
		BX.addClass(vNode, "crm-start-title-icons-item-video");
		vNode.setAttribute("id", "custom_widget_{$id}_click_node");
		BX.bind(vNode, "click", f);
		if (node.nextSibling)
			node.parentNode.insertBefore(vNode, node.nextSibling);
		else
			node.parentNode.appendChild(vNode);
		BX.onCustomEvent(window, "crm.widget", ["activityDynamic", "append", vNode]);
		if (BX('custom_widget_inactive_control_$id'))
			BX.onCustomEvent(window, "crm.widget", ["inactiveControl", "append", BX('custom_widget_inactive_control_$id')]);
	}
	{$videoFnc}
});
</script>
HTML;
		}
		return '<div id="custom_widget_'.$id.'">'.$innerHTML.'</div>';

	}

	/**
	 * @return array
	 */
	public static function getChannelData()
	{
		return (self::initChannels() ? self::$data["channelData"] : array());
	}
	/**
	 * @return array
	 */
	public static function getGroups()
	{
		return (self::initChannels() ? self::$data["groupItems"] : array());
	}

	private static function initChannels()
	{
		if (is_array(self::$data))
			return true;
		self::$data = array(
			"groupItems" => array(),
			"channelData" => array()
		);
		foreach (ChannelTrackerManager::getGroupInfos() as $groupInfo)
		{
			$groupID = $groupInfo->getID();
			self::$data["groupItems"][$groupID] = array(
				'ID' => $groupID,
				'PARENT_ID' => $groupInfo->getParentId(),
				'CAPTION' => $groupInfo->getCaption(),
				'URL' => $groupInfo->getUrl(),
				'IS_DISPLAYABLE' => $groupInfo->isDisplayable(),
				'IS_IN_USE' => false,
				'IS_CONFIGURABLE' => false,
				'COUNTERS' => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				'MAX_COUNTER' =>  array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				'ITEMS_COUNT' => 0,
				'ACTIVE_ITEMS_COUNT' => 0
			);
		}

		foreach (ChannelTrackerManager::getInfos() as $info)
		{
			$groupID = $info->getGroupID();
			if (isset(self::$data["groupItems"][$groupID]))
			{
				$parentId = self::$data["groupItems"][$groupID]["PARENT_ID"];
				if ($info->isInUse())
				{
					self::$data["groupItems"][$groupID]["IS_IN_USE"] = true;
					if (self::$data["groupItems"][$parentId])
						self::$data["groupItems"][$parentId]["IS_IN_USE"] = true;
					self::$data["groupItems"][$groupID]["ACTIVE_ITEMS_COUNT"]++;
					if (self::$data["groupItems"][$parentId])
						self::$data["groupItems"][$parentId]["ACTIVE_ITEMS_COUNT"]++;
				}
				if ($info->checkConfigurationPermission())
				{
					self::$data["groupItems"][$groupID]["IS_CONFIGURABLE"] = true;
					if (self::$data["groupItems"][$parentId])
						self::$data["groupItems"][$parentId]["IS_CONFIGURABLE"] = true;
				}
				self::$data["groupItems"][$groupID]["ITEMS_COUNT"]++;
				if (self::$data["groupItems"][$parentId])
					self::$data["groupItems"][$parentId]["ITEMS_COUNT"]++;
			}
			$key = $info->getKey();
			self::$data["channelData"][$key] = array(
				'PARAMS' => array(
					'channelTypeID' => $info->getChannelTypeID(),
					'channelOriginID' => $info->getChannelOrigin(),
					'channelComponentID' => $info->getChannelComponent()
				),
				'GROUP_ID' => $info->getGroupID(),
				'IS_IN_USE' => $info->isInUse(),
				'IS_CONFIGURABLE' => $info->checkConfigurationPermission(),
				'CAPTION' => $info->getCaption(),
				'CONFIG_URL' => $info->getConfigurationUrl(),
				'COUNTERS' => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				)
			);
		}
		return true;
	}

	/**
	 * Prepare permission SQL.
	 * @return string|boolean
	 */
	protected function preparePermissionSql()
	{
		return '';
	}
	/**
	 * @return array
	 */
	protected static function includeModuleFile()
	{
		if (empty(self::$messagesLoaded))
		{
			Main\Localization\Loc::loadMessages(__FILE__);
			self::$messagesLoaded = Loc::loadLanguageFile(__FILE__);
		}
		return self::$messagesLoaded;
	}

	/**
	 * Initialize Demo data.
	 * @param array $data Data.
	 * @param array $params Parameters.
	 * @return array
	 */
	public function initializeDemoData(array $data, array $params)
	{
		$groups = array(
			"EMAIL" => array(
				"ID" => "EMAIL",
				"PARENT_ID" => null,
				"CAPTION" => "Email",
				"URL" => "",
				"IS_DISPLAYABLE" => true,
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"COUNTERS" => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				"MAX_COUNTER" => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				"ITEMS_COUNT" => 0,
				"ACTIVE_ITEMS_COUNT" => 0
			),
			"IMOPENLINE" => array(
				"ID" => "IMOPENLINE",
				"PARENT_ID" => null,
				"CAPTION" => GetMessage("CRM_ACTIVITY_DYNAMIC_DEMO_IMOPENLINE"),
				"URL" => "/openlines/list/",
				"IS_DISPLAYABLE" => true,
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"COUNTERS" => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				"MAX_COUNTER" => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				"ITEMS_COUNT" => 0,
				"ACTIVE_ITEMS_COUNT" => 0
			),
			"IMOPENLINE_1" => array(
				"ID" => "IMOPENLINE_1",
				"PARENT_ID" => "IMOPENLINE",
				"CAPTION" => GetMessage("CRM_ACTIVITY_DYNAMIC_DEMO_IMOPENLINE_1"),
				"URL" => "/openlines/list/edit.php?ID=1",
				"IS_DISPLAYABLE" => true,
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"COUNTERS" => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				"MAX_COUNTER" => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				"ITEMS_COUNT" => 0,
				"ACTIVE_ITEMS_COUNT" => 0
			),
			"WEBFORM" => array (
				"ID" => "WEBFORM",
				"PARENT_ID" => null,
				"CAPTION" => GetMessage("CRM_ACTIVITY_DYNAMIC_DEMO_WEBFORM"),
				"URL" => "/crm/webform/",
				"IS_DISPLAYABLE" => true,
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"COUNTERS" => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				"MAX_COUNTER" => array(
					\CCrmActivityDirection::Incoming => 0,
					\CCrmActivityDirection::Outgoing => 0
				),
				"ITEMS_COUNT" => 0,
				"ACTIVE_ITEMS_COUNT" => 0
			)
		);
		$items = array(
			"EMAIL|479" => array(
				"PARAMS" => array(
					"channelTypeID" => 2,
					"channelOriginID" => 479,
					"channelComponentID" => ""
				),
				"GROUP_ID" => "EMAIL",
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"CAPTION" => "E-mail (test@example.com)"
			),
			"IMOPENLINE|1|livechat" => array(
				"PARAMS" => array(
					"channelTypeID" => 4,
					"channelOriginID" => 1,
					"channelComponentID" => "livechat"
				),
				"GROUP_ID" => "IMOPENLINE_1",
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"CAPTION" => GetMessage("CRM_ACTIVITY_DYNAMIC_DEMO_LIVECHAT")
			),
			"IMOPENLINE|1|facebook" => array(
				"PARAMS" => array(
					"channelTypeID" => 4,
					"channelOriginID" => 1,
					"channelComponentID" => "facebook"
				),
				"GROUP_ID" => "IMOPENLINE_1",
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"CAPTION" => "Facebook"
			),
			"IMOPENLINE|1|vkgroup" => array(
				"PARAMS" => array(
					"channelTypeID" => 4,
					"channelOriginID" => 1,
					"channelComponentID" => "vkgroup"
				),
				"GROUP_ID" => "IMOPENLINE_1",
				"IS_IN_USE" => LANGUAGE_ID == 'ru',
				"IS_CONFIGURABLE" => false,
				"CAPTION" => GetMessage("CRM_ACTIVITY_DYNAMIC_DEMO_VKGROUP"),
				"CONFIG_URL" => "/openlines/connector/?ID=vkgroup&LINE=1",
			),
			"WEBFORM|1" => array(
				"PARAMS" => array(
					"channelTypeID" => 5,
					"channelOriginID" => 1,
					"channelComponentID" => "",
				),
				"GROUP_ID" => "WEBFORM",
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"CAPTION" => GetMessage("CRM_ACTIVITY_DYNAMIC_DEMO_WEBFORM_1"),
				"CONFIG_URL" => "/crm/webform/"
			),

			"WEBFORM|2" => array(
				"PARAMS" => array(
					"channelTypeID" => 5,
					"channelOriginID" => 2,
					"channelComponentID" => true
				),
				"GROUP_ID" => "WEBFORM",
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"CAPTION" => GetMessage("CRM_ACTIVITY_DYNAMIC_DEMO_WEBFORM_2"),
				"CONFIG_URL" => "/crm/webform/",
			),
			"WEBFORM|4" => array(
				"PARAMS" => array(
					"channelTypeID" => 5,
					"channelOriginID" => 4,
					"channelComponentID" => ""
				),
				"GROUP_ID" => "WEBFORM",
				"IS_IN_USE" => true,
				"IS_CONFIGURABLE" => false,
				"CAPTION" => GetMessage("CRM_ACTIVITY_DYNAMIC_DEMO_WEBFORM_4"),
				"CONFIG_URL" => "/crm/webform/",
			)
		);
		$result = array(
			array(
				"CHANNEL_ID" => "EMAIL|479",
				"DIRECTION" => \CCrmActivityDirection::Incoming,
				"COUNT" => 20,
			),
			array(
				"CHANNEL_ID" => "EMAIL|479",
				"DIRECTION" => \CCrmActivityDirection::Outgoing,
				"COUNT" => 10,
			),
			array(
				"CHANNEL_ID" => "IMOPENLINE|1|livechat",
				"DIRECTION" => \CCrmActivityDirection::Incoming,
				"COUNT" => 22,
			),
			array(
				"CHANNEL_ID" => "IMOPENLINE|1|livechat",
				"DIRECTION" => \CCrmActivityDirection::Outgoing,
				"COUNT" => 0,
			),
			array(
				"CHANNEL_ID" => "IMOPENLINE|1|facebook",
				"DIRECTION" => \CCrmActivityDirection::Incoming,
				"COUNT" => 45,
			),
			array(
				"CHANNEL_ID" => "IMOPENLINE|1|facebook",
				"DIRECTION" => \CCrmActivityDirection::Outgoing,
				"COUNT" => 23,
			),
			array(
				"CHANNEL_ID" => "IMOPENLINE|1|vkgroup",
				"DIRECTION" => \CCrmActivityDirection::Incoming,
				"COUNT" => 78,
			),
			array(
				"CHANNEL_ID" => "IMOPENLINE|1|vkgroup",
				"DIRECTION" => \CCrmActivityDirection::Outgoing,
				"COUNT" => 25,
			),
			array(
				"CHANNEL_ID" => "WEBFORM|1",
				"DIRECTION" => \CCrmActivityDirection::Incoming,
				"COUNT" => 24,
			),
			array(
				"CHANNEL_ID" => "WEBFORM|1",
				"DIRECTION" => \CCrmActivityDirection::Outgoing,
				"COUNT" => 0,
			),
			array(
				"CHANNEL_ID" => "WEBFORM|2",
				"DIRECTION" => \CCrmActivityDirection::Incoming,
				"COUNT" => 14,
			),
			array(
				"CHANNEL_ID" => "WEBFORM|2",
				"DIRECTION" => \CCrmActivityDirection::Outgoing,
				"COUNT" => 0,
			),
			array(
				"CHANNEL_ID" => "WEBFORM|4",
				"DIRECTION" => \CCrmActivityDirection::Incoming,
				"COUNT" => 6,
			),
			array(
				"CHANNEL_ID" => "WEBFORM|4",
				"DIRECTION" => \CCrmActivityDirection::Outgoing,
				"COUNT" => 0,
			),
		);
		foreach($items as $item)
		{
			$groupID = $item["GROUP_ID"];
			$parentId = $groups[$groupID]["PARENT_ID"];
			$groups[$groupID]["ITEMS_COUNT"]++;
			if ($groups[$parentId])
				$groups[$parentId]["ITEMS_COUNT"]++;
			$groups[$groupID]["ACTIVE_ITEMS_COUNT"]++;
			if ($groups[$parentId])
				$groups[$parentId]["ACTIVE_ITEMS_COUNT"]++;
		}

		$title = false;
		$html = false;
		if (isset($data["items"]) && is_array($data["items"]) &&
			($item = reset($data["items"])) && is_array($item))
		{
			if (isset($item["title"]))
				$title = $item["title"];
			if (isset($item["html"]))
				$html = $item["html"];
		}
		return array("items" => array(
			array(
				"title" => $title ?: GetMessage("CRM_ACTIVITY_DYNAMIC_TITLE"),
				"html" => $html ?: self::parseTemplate($groups, $items, $result)
			)
		));
	}
}