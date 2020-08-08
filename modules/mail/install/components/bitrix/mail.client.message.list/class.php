<?php

use Bitrix\Mail;
use Bitrix\Mail\Helper\Mailbox;
use Bitrix\Mail\Internals\MailboxDirectoryTable;
use Bitrix\Mail\Internals\MessageAccessTable;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::includeModule('mail');

class CMailClientMessageListComponent extends CBitrixComponent
{
	protected $componentId;
	protected $mailbox;
	protected $foldersItems;
	/** @var Mailbox */
	protected $mailboxHelper;

	public function getComponentId()
	{
		if ($this->componentId === null)
		{
			$this->componentId = 'mail-client-list-manager';
		}
		return $this->componentId;
	}

	public function executeComponent()
	{
		global $USER, $APPLICATION;

		$APPLICATION->setTitle(Loc::getMessage('MAIL_CLIENT_HOME_TITLE'));

		if (!is_object($USER) || !$USER->isAuthorized())
		{
			$APPLICATION->authForm('');
			return;
		}

		$vars = $this->arParams['VARIABLES'];

		$this->arResult['MAILBOXES'] = Mail\MailboxTable::getUserMailboxes();
		$this->arResult['MAILBOX'] = array();
		$this->arResult['USER_OWNED_MAILBOXES_COUNT'] = 0;

		foreach ($this->arResult['MAILBOXES'] as $k => $item)
		{
			if (empty($item['NAME']))
			{
				$item['NAME'] = $item['EMAIL'] ?: $item['LOGIN'] ?: sprintf('#%u', $item['ID']);
			}

			$this->arResult['MAILBOXES'][$k] = $item;

			if (empty($vars['id']) && empty($this->arResult['MAILBOX']) || $vars['id'] == $item['ID'])
			{
				$this->mailbox = $this->arResult['MAILBOX'] = $item;
			}

			if ($item['USER_ID'] == $USER->getId())
			{
				$this->arResult['USER_OWNED_MAILBOXES_COUNT']++;
			}
		}

		if (empty($this->mailbox))
		{
			if (isset($_REQUEST['strict']) && 'N' == $_REQUEST['strict'])
			{
				localRedirect($this->arParams['PATH_TO_MAIL_HOME'], true);
			}
			else
			{
				showError(Loc::getMessage('MAIL_CLIENT_ELEMENT_NOT_FOUND'));
				return;
			}
		}

		$this->mailboxHelper = Mailbox::createInstance($this->mailbox['ID']);

		if (empty($this->mailboxHelper->getDirsHelper()->getDirs()))
		{
			$this->mailboxHelper->cacheDirs();
		}

		$this->rememberCurrentMailboxId($this->mailbox['ID']);
		$this->arResult['userHasCrmActivityPermission'] = Main\Loader::includeModule('crm') && \CCrmPerms::isAccessEnabled();
		$mailboxesUnseen = \Bitrix\Mail\Helper\Message::getTotalUnseenForMailboxes(Main\Engine\CurrentUser::get()->getId());
		foreach ($mailboxesUnseen as $mailboxId => $mailboxData)
		{
			$this->arResult['MAILBOXES'][$mailboxId]['__total'] = $mailboxData['TOTAL'];
			$this->arResult['MAILBOXES'][$mailboxId]['__unseen'] = $mailboxData['UNSEEN'];
		}

		$this->arResult['GRID_ID'] = 'mail-message-list-' . $this->mailbox['ID'];
		$this->arResult['FILTER_ID'] = 'mail-message-list-' . $this->mailbox['ID'];

		$this->setFilterSettings();
		$this->setFilterPresets();

		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);

		$navData = $gridOptions->getNavParams(array('nPageSize' => 25));
		$navigation = new \Bitrix\Main\UI\PageNavigation('mail-message-list');
		$navigation->setPageSize($navData['nPageSize'])->initFromUri();

		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		if (preg_match('/^\s*(\d+)\s*$/', $request->getQuery($navigation->getId()), $matches))
		{
			$navigation->setCurrentPage($matches[1]);
		}

		$filterOption = new Main\UI\Filter\Options($this->arResult['FILTER_ID'], $this->arResult['FILTER_PRESETS']);
		$filterData = $filterOption->getFilter($this->arResult['FILTER']);

		$filter = array(
			'=MAILBOX_ID' => $this->mailbox['ID'],
		);
		$filter1 = $filter2 = array();

		$uidSubquery = new ORM\Query\Query(Mail\MailMessageUidTable::getEntity());
		$uidSubquery->addFilter('=MAILBOX_ID', new Main\DB\SqlExpression('%s'));
		$uidSubquery->addFilter('=MESSAGE_ID', new Main\DB\SqlExpression('%s'));
		$uidSubquery->addFilter('=DELETE_TIME', 'IS NULL');

		$accessSubquery = new ORM\Query\Query(MessageAccessTable::getEntity());
		$accessSubquery->addFilter('=MAILBOX_ID', new Main\DB\SqlExpression('%s'));
		$accessSubquery->addFilter('=MESSAGE_ID', new Main\DB\SqlExpression('%s'));

		$closureSubquery = new ORM\Query\Query(Mail\Internals\MessageClosureTable::getEntity());
		$closureSubquery->addFilter('=PARENT_ID', new Main\DB\SqlExpression('%s'));
		$closureSubquery->addFilter('!=MESSAGE_ID', new Main\DB\SqlExpression('%s'));

		if (!empty($filterData['FILTER_APPLIED']))
		{
			if (isset($filterData['BIND']))
			{
				if ($filterData['BIND'] == MessageAccessTable::ENTITY_TYPE_NO_BIND)
				{
					$filter1['==MESSAGE_ACCESS'] = false;
					//$filter2['=MESSAGE_ACCESS.ENTITY_TYPE'] = false;
				}
				else
				{
					$accessSubquery->addFilter('=ENTITY_TYPE', $filterData['BIND']);
					$filter1['==MESSAGE_ACCESS'] = true;
					$filter2['=MESSAGE_ACCESS.ENTITY_TYPE'] = $filterData['BIND'];
				}
			}

			if (isset($filterData['IS_SEEN']))
			{
				if ($filterData['IS_SEEN'] == 'Y')
				{
					$uidSubquery->addFilter('@IS_SEEN', array('Y', 'S'));
					$filter2['@MESSAGE_UID.IS_SEEN'] = array('Y', 'S');
				}
				elseif ($filterData['IS_SEEN'] == 'N')
				{
					$uidSubquery->addFilter('!@IS_SEEN', array('Y', 'S'));
					$filter2['!@MESSAGE_UID.IS_SEEN'] = array('Y', 'S');
				}
			}

			if (isset($filterData['DIR']) && is_scalar($filterData['DIR']))
			{
				if ($filterData['DIR'] != '')
				{
					$uidSubquery->addFilter('=DIR_MD5', md5($filterData['DIR']));
					$filter2['=MESSAGE_UID.DIR_MD5'] = md5($filterData['DIR']);
				}
			}

			try
			{
				if (!empty($filterData['DATE_from']))
				{
					$filter['>=FIELD_DATE'] = new Main\Type\DateTime($filterData['DATE_from']);
				}

			}
			catch (\Exception $e)
			{
			}

			try
			{
				if (!empty($filterData['DATE_to']))
				{
					$filter['<=FIELD_DATE'] = new Main\Type\DateTime($filterData['DATE_to']);
				}
			}
			catch (\Exception $e)
			{
			}

			if (!empty($filterData['FIND']))
			{
				$filterKey = sprintf(
					'%sSEARCH_CONTENT',
					Mail\MailMessageTable::getEntity()->fullTextIndexEnabled('SEARCH_CONTENT') ? '*' : '*%'
				);
				$filter[$filterKey] = Mail\Helper\Message::prepareSearchString($filterData['FIND']);
			}
		}

		if (empty($filter2['=MESSAGE_UID.DIR_MD5']))
		{
			$uidSubquery->addFilter('=DIR_MD5', md5($this->mailboxHelper->getDirsHelper()->getDefaultDirPath()));
			$filter2['=MESSAGE_UID.DIR_MD5'] = md5($this->mailboxHelper->getDirsHelper()->getDefaultDirPath());
		}

		$items = Mail\MailMessageTable::getList(array(
			'runtime' => array(
				new ORM\Fields\ExpressionField(
					'MESSAGE_UID',
					sprintf('EXISTS(%s)', $uidSubquery->getQuery()),
					array('MAILBOX_ID', 'ID')
				),
				new ORM\Fields\ExpressionField(
					'MESSAGE_ACCESS',
					sprintf('EXISTS(%s)', $accessSubquery->getQuery()),
					array('MAILBOX_ID', 'ID')
				),
				new ORM\Fields\ExpressionField(
					'MESSAGE_CLOSURE',
					sprintf('EXISTS(%s)', $closureSubquery->getQuery()),
					array('ID', 'ID')
				),
			),
			'select'  => array('ID'),
			'filter'  => array_merge(
				array(
					'==MESSAGE_UID' => true,
					//'==MESSAGE_CLOSURE' => false,
				),
				$filter,
				$filter1
			),
			'order'   => array(
				'FIELD_DATE' => 'DESC',
				'ID'         => 'DESC',
			),
			'offset'  => $navigation->getOffset(),
			'limit'   => $navigation->getLimit() + 1,
		))->fetchAll();

		if (!empty($items))
		{
			$select = array(
				'MID'     => 'ID',
				'SUBJECT',
				'FIELD_FROM',
				'FIELD_TO',
				'FIELD_DATE',
				'ATTACHMENTS',
				'OPTIONS',
				'RID'     => 'MESSAGE_UID.ID',
				'IS_SEEN' => 'MESSAGE_UID.IS_SEEN',
				'DIR_MD5' => 'MESSAGE_UID.DIR_MD5',
				'MSG_UID' => 'MESSAGE_UID.MSG_UID',
				new ORM\Fields\ExpressionField(
					'BIND',
					'CONCAT(%s, "-", %s)',
					array(
						'MESSAGE_ACCESS.ENTITY_TYPE',
						'MESSAGE_ACCESS.ENTITY_ID',
					)
				),
			);

			if (Main\Loader::includeModule('crm'))
			{
				$select['CRM_ACTIVITY_OWNER'] = new ORM\Fields\ExpressionField(
					'CRM_ACTIVITY_OWNER',
					'CONCAT(%s, "-", %s)',
					array(
						'MESSAGE_ACCESS.CRM_ACTIVITY.OWNER_TYPE_ID',
						'MESSAGE_ACCESS.CRM_ACTIVITY.OWNER_ID',
					)
				);
				$select['CRM_ACTIVITY_OWNER_TYPE_ID'] = 'MESSAGE_ACCESS.CRM_ACTIVITY.OWNER_TYPE_ID';
				$select['CRM_ACTIVITY_OWNER_ID'] = 'MESSAGE_ACCESS.CRM_ACTIVITY.OWNER_ID';
			}

			$res = Mail\MailMessageTable::getList(array(
				'runtime' => array(
					new ORM\Fields\Relations\Reference(
						'MESSAGE_UID',
						Mail\MailMessageUidTable::class,
						array(
							'=this.MAILBOX_ID' => 'ref.MAILBOX_ID',
							'=this.ID'         => 'ref.MESSAGE_ID',
						),
						array(
							'join_type' => 'INNER',
						)
					),
					new ORM\Fields\Relations\Reference(
						'MESSAGE_ACCESS',
						MessageAccessTable::class,
						array(
							'=this.MAILBOX_ID' => 'ref.MAILBOX_ID',
							'=this.ID'         => 'ref.MESSAGE_ID',
						)
					),
				),
				'select'  => $select,
				'filter'  => array_merge(
					array(
						'@ID'                      => array_column($items, 'ID'),
						'=MESSAGE_UID.DELETE_TIME' => 'IS NUll',
					),
					$filter,
					$filter2
				),
				'order'   => array(
					'FIELD_DATE' => 'DESC',
					'MID'        => 'DESC',
					'MSG_UID'    => 'ASC',
				),
			));

			$items = array();
			while ($item = $res->fetch())
			{
				$item['BIND'] = (array) $item['BIND'];
				$item['CRM_ACTIVITY_OWNER'] = (array) @$item['CRM_ACTIVITY_OWNER'];

				if (array_key_exists($item['MID'], $items))
				{
					$item['IS_SEEN'] = max($items[$item['MID']]['IS_SEEN'], $item['IS_SEEN']);
					$item['BIND'] = array_unique(array_filter(array_merge(
						$items[$item['MID']]['BIND'],
						$item['BIND']
					)));
					$item['CRM_ACTIVITY_OWNER'] = array_unique(array_filter(array_merge(
						$items[$item['MID']]['CRM_ACTIVITY_OWNER'],
						$item['CRM_ACTIVITY_OWNER']
					)));
				}

				$items[$item['MID']] = $item;
			}
		}

		$this->arResult['gridActionsData'] = $this->getGridActionsData();

		$this->arResult['ROWS'] = $this->getRows($items, $navigation);
		$this->arResult['NAV_OBJECT'] = $navigation;

		// @TODO: IX_MAIL_MSG_UID_SEEN_2
		$unseen = \Bitrix\Mail\MailMessageTable::getList(array(
			'runtime' => array(
				new \Bitrix\Main\Entity\ReferenceField(
					'MESSAGE_UID',
					'Bitrix\Mail\MailMessageUidTable',
					array(
						'=this.MAILBOX_ID' => 'ref.MAILBOX_ID',
						'=this.ID'         => 'ref.MESSAGE_ID',
					),
					array(
						'join_type' => 'INNER',
					)
				)
			),
			'select'  => array(
				new \Bitrix\Main\Entity\ExpressionField('UNSEEN', 'COUNT(1)'),
			),
			'filter'  => array(
				'=MAILBOX_ID'              => $this->mailbox['ID'],
				'=MESSAGE_UID.DIR_MD5'     => $filter2['=MESSAGE_UID.DIR_MD5'],
				'!@MESSAGE_UID.IS_SEEN'    => array('Y', 'S'),
				'=MESSAGE_UID.DELETE_TIME' => 'IS NUll',
			),
		))->fetch();

		$this->arResult['UNSEEN'] = isset($unseen['UNSEEN']) ? $unseen['UNSEEN'] : 0;

		if ($this->request->getPost('errorMessage'))
		{
			$this->arResult["MESSAGES"][] = [
				"TYPE"  => \Bitrix\Main\Grid\MessageType::ERROR,
				"TITLE" => Loc::getMessage('MAIL_CLIENT_AJAX_ERROR'),
				"TEXT"  => \Bitrix\Main\Text\Encoding::convertEncodingToCurrent($this->request->getPost('errorMessage')),
			];
		}

		$this->arResult['inboxDir'] = $this->mailboxHelper->getDirsHelper()->getDefaultDirPath();
		$this->arResult['spamDir'] = $this->mailboxHelper->getDirsHelper()->getSpamPath();
		$this->arResult['trashDir'] = $this->mailboxHelper->getDirsHelper()->getTrashPath();
		$this->arResult['outcomeDir'] = $this->mailboxHelper->getDirsHelper()->getOutcomePath();

		$this->arResult['MAX_ALLOWED_CONNECTED_MAILBOXES'] = Mail\Helper\LicenseManager::getUserMailboxesLimit();

		$dirsMenu = $this->prepareDirsHierarchyForGrid();

		$this->prepareDirsMenu($dirsMenu);

		$this->arResult['foldersItems'] = $this->foldersItems;
		$this->arResult['DIRS_MENU'] = $dirsMenu;
		$this->arResult['CONFIG_SYNC_DIRS'] = $this->mailboxHelper->getDirsHelper()->getSyncDirs();

		$this->includeComponentTemplate();
	}

	/**
	 * @param $items
	 * @param \Bitrix\Main\UI\PageNavigation $navigation
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @throws Main\LoaderException
	 */
	private function getRows($items, $navigation)
	{
		$rows = [];
		$avatarConfigs = $this->getAvatarConfigs($items);
		foreach ($items as $index => $item)
		{
			if (count($rows) >= $navigation->getLimit())
			{
				$this->arResult['ENABLE_NEXT_PAGE'] = true;
				break;
			}

			$item['ID'] = $item['RID'] . '-' . $this->mailbox['ID'];

			$columns = array();

			$columns['DATE'] = \CComponentUtil::getDateTimeFormatted(
				makeTimeStamp($item['FIELD_DATE']),
				(\Bitrix\Main\Loader::includeModule('intranet') ? \CIntranetUtils::getCurrentDatetimeFormat() : false),
				\CTimeZone::getOffset()
			);

			$columns['FROM'] = '<span class="mail-msg-from-title">' . htmlspecialcharsbx($item['FIELD_FROM']) . '</span>';
			$columns['SUBJECT'] = htmlspecialcharsbx($item['SUBJECT'] ?: Loc::getMessage('MAIL_MESSAGE_EMPTY_SUBJECT_PLACEHOLDER'));

			$from = new \Bitrix\Main\Mail\Address(current(explode(',', $item['FIELD_FROM'])));
			if ($from->validate())
			{
				// @TODO: outcome folders
				if ($from->getEmail() == $this->mailbox['EMAIL'] && !empty($item['FIELD_TO']))
				{
					$columns['FROM'] = '<span class="mail-msg-from-title">' . htmlspecialcharsbx($item['FIELD_TO']) . '</span>';

					$from = new \Bitrix\Main\Mail\Address(current(explode(',', $item['FIELD_TO'])));
				}
			}
			if ($from->validate())
			{
				$columns['FROM'] = sprintf(
					'<span class="mail-msg-from-title" title="%s">%s</span>',
					htmlspecialcharsbx($from->getEmail()),
					htmlspecialcharsbx($from->getName() ? $from->getName() : $from->getEmail())
				);
			}
			$avatarParams = !empty($from->getEmail()) && !empty($avatarConfigs[$from->getEmail()]) ? $avatarConfigs[$from->getEmail()] : [];

			$columns['FROM'] = $this->getSenderColumnCell($avatarParams) . $columns['FROM'];

			$columns['SUBJECT'] = sprintf(
				'<a href="%s" class="mail-msg-list-subject" onclick="BX.PreventDefault(); ">%s</a>',
				htmlspecialcharsbx(\CComponentEngine::makePathFromTemplate(
					$this->arParams['PATH_TO_MAIL_MSG_VIEW'],
					array('id' => $item['MID'])
				)),
				$columns['SUBJECT']
			);
			if ($item['OPTIONS']['attachments'] > 0 || $item['ATTACHMENTS'] > 0)
			{
				$columns['SUBJECT'] .= '<span class="mail-msg-list-attach-icon" title="' . Loc::getMessage('MAIL_MESSAGE_LIST_ATTACH_ICON_HINT') . '"></span>';
			}

			$dir = $this->mailboxHelper->getDirsHelper()->getDirByHash($item['DIR_MD5']);

			$isDisabled = ($item['MSG_UID'] == 0);
			$jsFromClassNames = $dir && $dir->isSpam() ? 'js-spam ' : '';
			$jsFromClassNames .= $isDisabled ? 'js-disabled ' : '';
			$columns['FROM'] = sprintf(
				'<span data-message-id="%u" class="' . $jsFromClassNames . ' mail-msg-list-cell-%u mail-msg-list-cell-nowrap mail-msg-list-cell-flex %s">%s</span>',
				$item['MID'],
				$item['MID'],
				!in_array($item['IS_SEEN'], array('Y', 'S')) ? 'mail-msg-list-cell-unseen' : '',
				$columns['FROM']
			);
			$columns['SUBJECT'] = sprintf(
				'<span class="mail-msg-list-cell-%u %s">%s</span>',
				$item['ID'],
				!in_array($item['IS_SEEN'], array('Y', 'S')) ? 'mail-msg-list-cell-unseen' : '',
				$columns['SUBJECT']
			);
			$columns['BIND'] = '<span class="js-bind-' . $item['MID'] . '">';
			if ($item['BIND'])
			{
				$bindColumns = [];
				foreach ((array)$item['BIND'] as $bindWithId)
				{
					list($bindEntityType, $bindEntityId) = explode('-', $bindWithId);
					switch ($bindEntityType)
					{
						case MessageAccessTable::ENTITY_TYPE_TASKS_TASK:
							$bindColumns[$bindEntityType] = sprintf(
								'<a data-type="%s" href="%s">%s</a>',
								$bindEntityType,
								\CComponentEngine::makePathFromTemplate(
									$this->arParams['PATH_TO_USER_TASKS_TASK'],
									[
										'action'  => 'view',
										'task_id' => $bindEntityId,
									]
								),
								Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_BIND_' . $bindEntityType)
							);
							break;
						case MessageAccessTable::ENTITY_TYPE_CRM_ACTIVITY:
							if ($this->arResult['userHasCrmActivityPermission'])
							{
								list($ownerTypeId, $ownerId) = explode('-', end($item['CRM_ACTIVITY_OWNER']));
								$bindColumns[$bindEntityType] = sprintf(
									'<span data-role="crm-binding-link" data-entity-id="%s" data-type="%s">
										<a href="%s">%s</a>
									</span>',
									$bindEntityId,
									$bindEntityType,
									\CCrmOwnerType::getEntityShowPath($ownerTypeId, $ownerId),
									Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_BIND_' . $bindEntityType)
								);
								break;
							}
							break;
						case MessageAccessTable::ENTITY_TYPE_BLOG_POST:
							$bindColumns[$bindEntityType] = sprintf(
								'<a data-type="%s" target="_blank" href="%s" onclick="%s">%s</a>',
								$bindEntityType,
								\CComponentEngine::makePathFromTemplate(
									$this->arParams['PATH_TO_USER_BLOG_POST'],
									[
										'post_id' => $bindEntityId,
									]
								),
								"top.BX.SidePanel.Instance.open(this.href, {loader: 'socialnetwork:userblogpost'}); return false;",
								Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_BIND_' . $bindEntityType)
							);
							break;
						default:
							$bindColumns[$bindEntityType] = sprintf(
								'<span data-type="%s">%s</span>',
								$bindEntityType,
								Loc::getMessage('MAIL_MESSAGE_LIST_COLUMN_BIND_' . $bindEntityType)
							);
							break;
					}
				}
				$columns['BIND'] .= implode('<span data-role="comma-separator">,&nbsp;</span>', $bindColumns);
			}
			$columns['BIND'] .= '</span>';

			$rows[$item['ID']] = array(
				'id'      => $item['ID'],
				'data'    => $item,
				'columns' => $columns,
			);

			$taskHref = \CHTTP::urlAddParams(
				\CComponentEngine::makePathFromTemplate(
					$this->arParams['PATH_TO_USER_TASKS_TASK'],
					array(
						'action'  => 'edit',
						'task_id' => '0',
					)
				),
				array(
					'TITLE'           => rawurlencode(
						Loc::getMessage('MAIL_MESSAGE_TASK_TITLE', array('#SUBJECT#' => $item['SUBJECT']))
					),
					'UF_MAIL_MESSAGE' => (int)$item['MID'],
				)
			);

			$postHref = \CHTTP::urlAddParams(
				\CComponentEngine::makePathFromTemplate(
					$this->arParams['PATH_TO_USER_BLOG_POST_EDIT'],
					array(
						'post_id' => '0',
					)
				),
				array(
					'TITLE'           => rawurlencode(
						Loc::getMessage('MAIL_MESSAGE_POST_TITLE',
							array('#SUBJECT#' => $item['SUBJECT']))
					),
					'UF_MAIL_MESSAGE' => (int)$item['MID'],
				)
			);

			$rows[$item['ID']]['actions'] = [
				[
					'id'                => $this->arResult['gridActionsData']['view']['id'],
					'text'              => $this->arResult['gridActionsData']['view']['text'],
					'icon'              => $this->arResult['gridActionsData']['view']['icon'],
					'default'           => true,
					'href'              => \CComponentEngine::makePathFromTemplate(
						$this->arParams['PATH_TO_MAIL_MSG_VIEW'],
						array('id' => $item['MID'])
					),
					'hideInActionPanel' => true,
				],
				[
					'id'        => $this->arResult['gridActionsData']['notRead']['id'],
					'text'      => '<span data-role="not-read-action">'
						. $this->arResult['gridActionsData']['notRead']['text'] . '</span>',
					'icon'      => $this->arResult['gridActionsData']['notRead']['icon'],
					'disabled'  => $isDisabled,
					'className' => "menu-popup-no-icon",
					'default'   => true,
					'onclick'   => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($this->getComponentId()) . "'].onReadClick('{$item['ID']}');",
				],
				[
					'id'        => $this->arResult['gridActionsData']['read']['id'],
					'text'      => '<span data-role="read-action">'
						. $this->arResult['gridActionsData']['read']['text'] . '</span>',
					'icon'      => $this->arResult['gridActionsData']['read']['icon'],
					'disabled'  => $isDisabled,
					'className' => "menu-popup-no-icon",
					'default'   => true,
					'onclick'   => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($this->getComponentId()) . "'].onReadClick('{$item['ID']}');",
				],
				[
					'id'       => $this->arResult['gridActionsData']['delete']['id'],
					'icon'     => $this->arResult['gridActionsData']['delete']['icon'],
					'text'     => $this->arResult['gridActionsData']['delete']['text'],
					'disabled' => $isDisabled,
					'onclick'  => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($this->getComponentId()) . "'].onDeleteClick('{$item['ID']}');",
				],
				[
					'id'       => $this->arResult['gridActionsData']['notSpam']['id'],
					'icon'     => $this->arResult['gridActionsData']['notSpam']['icon'],
					'text'     => '<span data-role="not-spam-action">'
						. $this->arResult['gridActionsData']['notSpam']['text'] . '</span>',
					'disabled' => $isDisabled,
					'onclick'  => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($this->getComponentId()) . "'].onSpamClick('{$item['ID']}');",
				],
				[
					'id'       => $this->arResult['gridActionsData']['spam']['id'],
					'icon'     => $this->arResult['gridActionsData']['spam']['icon'],
					'text'     => '<span data-role="spam-action">'
						. $this->arResult['gridActionsData']['spam']['text'] . '</span>',
					'disabled' => $isDisabled,
					'onclick'  => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($this->getComponentId()) . "'].onSpamClick('{$item['ID']}');",
				],
				[
					'id'             => $this->arResult['gridActionsData']['move']['id'] . $item['ID'],
					'icon'           => $this->arResult['gridActionsData']['move']['icon'],
					'text'           => $this->arResult['gridActionsData']['move']['text'],
					'submenuOptions' => isset($this->arResult['gridActionsData']['move']['submenuOptions']) ? $this->arResult['gridActionsData']['move']['submenuOptions'] : [],
					'items'          => $this->prepareDirsHierarchyForGrid(),
					'gridRowId'      => $item['ID'],
				],
				[
					'id' => $this->arResult['gridActionsData']['task']['id'],
					'icon' => $this->arResult['gridActionsData']['task']['icon'],
					'text' => $this->arResult['gridActionsData']['task']['text'],
					'href' => $isDisabled ? '' : $taskHref,
					'onclick' => "top.BX.SidePanel.Instance.open('" . \CUtil::jsEscape($taskHref) . "', {'cacheable': false, 'loader': 'task-new-loader'}); if (event = event || window.event) event.preventDefault(); ",
					'dataset' => ['sliderIgnoreAutobinding' => true],
					'disabled' => $isDisabled,
				],
			];
			if ($this->arResult['userHasCrmActivityPermission'])
			{
				$rows[$item['ID']]['actions'] = array_merge($rows[$item['ID']]['actions'], [
					[
						'id'      => $this->arResult['gridActionsData']['addToCrm']['id'],
						'icon'    => $this->arResult['gridActionsData']['addToCrm']['icon'],
						'text'    => '<span data-role="crm-action">' . $this->arResult['gridActionsData']['addToCrm']['text'] . '</span>',
						'onclick' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($this->getComponentId()) . "'].onCrmClick('{$item['ID']}');",
					],
					[
						'id'      => $this->arResult['gridActionsData']['excludeFromCrm']['id'],
						'icon'    => $this->arResult['gridActionsData']['excludeFromCrm']['icon'],
						'text'    => '<span data-role="not-crm-action">' . $this->arResult['gridActionsData']['excludeFromCrm']['text'] . '</span>',
						'onclick' => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($this->getComponentId()) . "'].onCrmClick('{$item['ID']}');",
					],
				]);
			}
			$rows[$item['ID']]['actions'] = array_merge($rows[$item['ID']]['actions'], [
				[
					'id' => $this->arResult['gridActionsData']['liveFeed']['id'],
					'icon' => $this->arResult['gridActionsData']['liveFeed']['icon'],
					'text' => $this->arResult['gridActionsData']['liveFeed']['text'],
					'href' => $isDisabled ? '' : $postHref,
					'onclick' => "top.BX.SidePanel.Instance.open('" . \CUtil::jsEscape($postHref) . "', {'cacheable': false, 'loader': 'socialnetwork:userblogposteditex'}); if (event = event || window.event) event.preventDefault(); ",
					'dataset' => ['sliderIgnoreAutobinding' => true],
					'disabled' => $isDisabled,
				],
				[
					'id'       => $this->arResult['gridActionsData']['discuss']['id'],
					'icon'     => $this->arResult['gridActionsData']['discuss']['icon'],
					'text'     => $this->arResult['gridActionsData']['discuss']['text'],
					'disabled' => true,
				],
				[
					'id'       => $this->arResult['gridActionsData']['event']['id'],
					'icon'     => $this->arResult['gridActionsData']['event']['icon'],
					'text'     => $this->arResult['gridActionsData']['event']['text'],
					'disabled' => true,
				],
			]);
		}
		return $rows;
	}

	/**
	 * @param $emails
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getAvatarConfigs($items)
	{
		$emails = [];
		foreach ($items as $key => $element)
		{
			foreach (array('FIELD_FROM', 'FIELD_TO') as $column)
			{
				if ((isset($element[$column]) || $element[$column]))
				{
					$emails[$element[$column]] = $element[$column];
				}
			}
		}
		$emails = array_values($emails);
		$configs = (new Mail\MessageView\AvatarManager(Main\Engine\CurrentUser::get()->getId()))
			->getAvatarParamsFromEmails($emails);

		return $configs;
	}

	private function getGridActionsData()
	{
		return [
			'view'           => [
				'id'   => 'view',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_open_mail.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_LIST_BTN_VIEW'),
			],
			'delete'         => [
				'id'   => 'delete',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_remove.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_LIST_BTN_DELETE'),
			],
			'spam'           => [
				'id'   => 'spam',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_lock.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_LIST_BTN_SPAM'),
			],
			'notSpam'        => [
				'id'   => 'notSpam',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_not_spam.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_LIST_BTN_NOT_SPAM'),
			],
			'addToCrm'       => [
				'id'   => 'addToCrm',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_save_to_crm.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_CREATE_CRM_BTN'),
			],
			'excludeFromCrm' => [
				'id'   => 'excludeFromCrm',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_exclude.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_CREATE_CRM_EXCLUDE_BTN'),
			],
			'task'           => [
				'id'   => 'task',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_create.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_CREATE_TASK_BTN'),
			],
			'event'          => [
				'id'   => 'event',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_event.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_CREATE_EVENT_BTN'),
			],
			'liveFeed'       => [
				'id'   => 'liveFeed',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_discuss.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_CREATE_LF_BTN'),
			],
			'discuss'        => [
				'id'   => 'discuss',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_discuss_in_chat.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_CREATE_IM_BTN'),
			],
			'read'           => [
				'id'   => 'read',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_read.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_LIST_BTN_SEEN'),
			],
			'notRead'        => [
				'id'   => 'notRead',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_not_read.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_LIST_BTN_UNSEEN'),
			],
			'move'           => [
				'id'   => ':move:',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_move_to_folder.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_LIST_BTN_MOVE'),
				//'submenuOptions' => \Bitrix\Main\Web\Json::encode(['maxHeight' => 450]),
			],
			'readAll'        => [
				'id'   => 'readAll',
				'icon' => '/bitrix/js/ui/actionpanel/images/ui_icon_actionpanel_read.svg',
				'text' => Loc::getMessage('MAIL_MESSAGE_LIST_BTN_SEEN_ALL'),
			],
		];
	}

	private function getSenderColumnCell($avatarParams)
	{
		global $APPLICATION;
		static $contactAvatars = [];

		$email = !empty($avatarParams['email']) ? $avatarParams['email'] : 'default';
		$name = !empty($avatarParams['name']) ? $avatarParams['name'] : 'default';
		$key = md5($email . $name);

		if (!array_key_exists($key, $contactAvatars))
		{
			ob_start();
			$APPLICATION->includeComponent(
				'bitrix:mail.contact.avatar',
				'',
				$avatarParams,
				null,
				array(
					'HIDE_ICONS' => 'Y',
				)
			);
			$contactAvatars[$key] = ob_get_clean();
		}
		return $contactAvatars[$key];

	}

	private function setFilterSettings()
	{
		$syncDirs = $this->mailboxHelper->getDirsHelper()->getSyncDirs();
		$defaultDirPath = $this->mailboxHelper->getDirsHelper()->getDefaultDirPath();
		$dirs = [];

		foreach ($syncDirs as $dir)
		{
			if ($dir->getPath() === $defaultDirPath)
			{
				$dirs = array_merge(['' => $dir->getName()], $dirs);
			}
			else
			{
				$dirs[$dir->getPath()] = $dir->getName();
			}
		}

		if (empty($dirs))
		{
			$dirs = ['' => 'Inbox'];
		}

		$this->arResult['FILTER'] = array(
			array(
				'id'      => 'DIR',
				'name'    => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_DIR'),
				'type'    => 'list',
				'params'  => array('multiple' => 'N'),
				'items'   => $dirs,
				'default' => true,
				'strict'  => true,
			),
			array(
				'id'      => 'DATE',
				'name'    => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_DATE'),
				'type'    => 'date',
				'default' => true,
				'exclude' => array(
					\Bitrix\Main\UI\Filter\DateType::TOMORROW,
					\Bitrix\Main\UI\Filter\DateType::NEXT_DAYS,
					\Bitrix\Main\UI\Filter\DateType::NEXT_WEEK,
					\Bitrix\Main\UI\Filter\DateType::NEXT_MONTH,
				),
			),
			array(
				'id'      => 'IS_SEEN',
				'name'    => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_IS_SEEN'),
				'type'    => 'list',
				'params'  => array('multiple' => 'N'),
				'items'   => array(
					''  => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_OPTION_ANY'),
					'Y' => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_OPTION_Y'),
					'N' => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_OPTION_N'),
				),
				'default' => true,
			),
			array(
				'id'      => 'BIND',
				'name'    => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_BIND'),
				'type'    => 'list',
				'default' => true,
				'params'  => array('multiple' => 'N'),
				'items'   => array(
					''                                           => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_OPTION_ANY'),
					MessageAccessTable::ENTITY_TYPE_TASKS_TASK   => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_BIND_TASK'),
					MessageAccessTable::ENTITY_TYPE_CRM_ACTIVITY => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_BIND_CRM'),
					MessageAccessTable::ENTITY_TYPE_BLOG_POST    => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_BIND_POST'),
					MessageAccessTable::ENTITY_TYPE_NO_BIND      => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_OPTION_N'),
				),
			),
		);
	}

	private function setFilterPresets()
	{
		$presetBindings = [
			'bindTask' => [
				'name'   => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_BIND_TASK'),
				'fields' => [
					'BIND' => MessageAccessTable::ENTITY_TYPE_TASKS_TASK,
				],
			],
			'bindCrm'  => [
				'name'   => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_BIND_CRM'),
				'fields' => [
					'BIND' => MessageAccessTable::ENTITY_TYPE_CRM_ACTIVITY,
				],
			],
			'bindPost' => [
				'name'   => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_BIND_POST'),
				'fields' => [
					'BIND' => MessageAccessTable::ENTITY_TYPE_BLOG_POST,
				],
			],
		];
		$presetDirs = [
			'income' => [
				'name'   => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_INCOME'),
				'fields' => [
					'DIR' => $this->mailboxHelper->getDirsHelper()->getIncomePath(),
				],
			],
			'outcome' => [
				'name'   => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_OUTCOME'),
				'fields' => [
					'DIR' => $this->mailboxHelper->getDirsHelper()->getOutcomePath(),
				],
			],
			'spam' => [
				'name'   => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_SPAM'),
				'fields' => [
					'DIR' => $this->mailboxHelper->getDirsHelper()->getSpamPath(),
				],
			],
			'trash' => [
				'name'   => Loc::getMessage('MAIL_MESSAGE_LIST_FILTER_PRESET_TRASH'),
				'fields' => [
					'DIR' => $this->mailboxHelper->getDirsHelper()->getTrashPath(),
				],
			],
		];
		$defaultPresetKeys = array_keys(array_merge($presetDirs, $presetBindings));
		$defaultPresetKeys[] = '';
		$this->arResult['FILTER_PRESETS'] = [];
		$defaultPreset = [];
		$defaultDirPath = $this->mailboxHelper->getDirsHelper()->getDefaultDirPath();
		foreach ($presetDirs as $presetKey => $preset)
		{
			$dirPath = $preset['fields']['DIR'];
			$dir = $this->mailboxHelper->getDirsHelper()->getDirByPath($dirPath);

			if ('' == $dirPath || $dir === null)
			{
				continue;
			}

			if ($dir->isSync())
			{
				if ($dir->getPath() === $defaultDirPath)
				{
					if (empty($defaultPreset))
					{
						$preset['fields']['DIR'] = '';
						$preset['default'] = true;
						$defaultPreset[$presetKey] = $preset;
					}

					continue;
				}

				$this->arResult['FILTER_PRESETS'][$presetKey] = $preset;
			}
		}
		if (!empty($defaultPreset))
		{
			$keys = array_keys($defaultPreset);
			$values = array_values($defaultPreset);
			$this->arResult['FILTER_PRESETS'] = array_merge(
				[array_pop($keys) => array_pop($values)],
				$this->arResult['FILTER_PRESETS']
			);
		}
		$this->arResult['FILTER_PRESETS'] = $this->arResult['FILTER_PRESETS'] + $presetBindings;
		$currentAllowedPresetKeys = array_keys($this->arResult['FILTER_PRESETS']);
		$filterOptions = new \Bitrix\Main\UI\Filter\Options(
			$this->arResult['FILTER_ID'],
			$this->arResult['FILTER_PRESETS']
		);
		$userPresets = $filterOptions->getPresets();
		foreach ($userPresets as $presetUserKey => $userPreset)
		{
			if (in_array($presetUserKey, $defaultPresetKeys, true))
			{
				$userPresets[$presetUserKey]['fields']['DIR'] = $this->arResult['FILTER_PRESETS'][$presetUserKey]['fields']['DIR'];
				$userPresets[$presetUserKey]['name'] = $this->arResult['FILTER_PRESETS'][$presetUserKey]['name'];
				if (!in_array($presetUserKey, $currentAllowedPresetKeys, true))
				{
					unset($userPresets[$presetUserKey]);
				}
			}
			elseif ('' != $userPreset['fields']['DIR'])
			{
				$dir = $this->mailboxHelper->getDirsHelper()->getDirByPath($userPreset['fields']['DIR']);

				if (!$dir)
				{
					unset($userPresets[$presetUserKey]);
				}
				elseif ($dir && !$dir->isSync())
				{
					unset($userPresets[$presetUserKey]);
				}
			}
		}
		$curPresets = $filterOptions->getPresets();
		if ($this->arrayDiffRecursive($curPresets, $userPresets))
		{
			$filterOptions->setPresets($userPresets);
			$filterOptions->save();
		}
	}

	private function prepareDirsHierarchyForGrid()
	{
		if (empty($this->foldersItems))
		{
			$res = Mail\MailMessageUidTable::getList(array(
				'select' => array(
					'DIR_MD5',
					new Main\Entity\ExpressionField('UNSEEN', 'COUNT(1)'),
				),
				'filter' => array(
					'MAILBOX_ID'   => $this->arResult['MAILBOX']['ID'],
					'@IS_SEEN'     => array('N', 'U'),
					'>MESSAGE_ID'  => 0,
					'=DELETE_TIME' => 'IS NUll',
				),
				'group'  => array('DIR_MD5'),
			));

			$counts = array();
			while ($item = $res->fetch())
			{
				$counts[$item['DIR_MD5']] = $item;
			}

			$this->foldersItems = $this->dirsTreeForGrid($counts);
		}

		return $this->foldersItems;
	}

	private function dirsTreeForGrid($counts)
	{
		$flat = [];
		$list = [];
		$dirs = $this->mailboxHelper->getDirsHelper()->getDirs();

		foreach ($dirs as $dir)
		{
			$path = $dir->getPath();
			$hasChild = (bool)preg_match('/(HasChildren)/ix', $dir->getFlags());
			$isCounted = ($dir->isTrash() || $dir->isSpam()) ? false : true;

			$flat[$dir->getId()] = [
				'id'        => $path,
				'order'     => $this->mailboxHelper->getDirsHelper()->getOrderByDefault($dir),
				'delimiter' => $dir->getDelimiter(),
				'text'      => sprintf('<span class="mail-msg-list-menu-item">%s</span>', $dir->getName()),
				'dataset'   => [
					'path'       => $path,
					'dirMd5'     => $dir->getDirMd5(),
					'isDisabled' => $dir->isDisabled(),
					'hasChild'   => $hasChild,
					'isCounted'  => $isCounted
				],
				'unseen'    => isset($counts[$dir->getDirMd5()]['UNSEEN']) ? (int)$counts[$dir->getDirMd5()]['UNSEEN'] : 0,
				'onclick'   => "BX.Mail.Client.Message.List['" . CUtil::JSEscape($this->getComponentId()) . "'].onMoveToFolderClick(event)",
				'items'     => $hasChild ? [
					[
						'id'       => 'loading',
						'text'     => Loc::getMessage('MAIL_CLIENT_BUTTON_LOADING'),
						'disabled' => true,
						'items'    => []
					]
				] : []
			];

			if (!empty($flat[$dir->getParentId()]))
			{
				foreach ($flat[$dir->getParentId()]['items'] as $k => $item)
				{
					if (!empty($item['id']) && $item['id'] === 'loading')
					{
						array_splice($flat[$dir->getParentId()]['items'], $k, 1);
					}
				}

				$flat[$dir->getParentId()]['items'][] = &$flat[$dir->getId()];
			}
			else
			{
				$list[] = &$flat[$dir->getId()];
			}
		}

		usort($list, function ($a, $b)
		{
			$aSort = $a['order'];
			$bSort = $b['order'];

			if ($aSort === $bSort)
			{
				return 0;
			}

			return $aSort > $bSort ? 1 : -1;
		});

		return $list;
	}

	private function prepareDirsMenu(&$list)
	{
		$count = 0;

		foreach ($list as $k => $item)
		{
			$syncDirs = $this->mailboxHelper->getDirsHelper()->getSyncDirsPath();

			if (!empty($syncDirs) && !in_array($item['id'], $syncDirs))
			{
				$list[$k]['dataset']['isDisabled'] = $item['dataset']['isDisabled'] = true;
			}

			$list[$k]['onclick'] = "BX.Mail.Client.Message.List['" . \CUtil::jsEscape($this->getComponentId()) . "'].onDirsMenuItemClick(this);";
			$list[$k]['items_unseen'] = $item['items_unseen'] = isset($item['items']) ? $this->prepareDirsMenu($item['items']) : 0;
			$list[$k]['items'] = $item['items'];

			if ($item['dataset']['isDisabled'] && empty($item['items']))
			{
				unset($list[$k]);
				continue;
			}

			$unseen = $item['unseen'] + $item['items_unseen'];

			if ($unseen > 0)
			{
				$list[$k]['text'] .= sprintf(
					'&nbsp;<span class="main-buttons-item-counter %s">%u</span>',
					$item['unseen'] > 0 ? '' : ' mail-msg-list-menu-fake-counter',
					$unseen
				);
			}

			$count += $unseen;
		}

		$list = array_values($list);

		return $count;
	}

	private function arrayDiffRecursive($arr1, $arr2)
	{
		$modified = array();
		foreach ($arr1 as $key => $value)
		{
			if (array_key_exists($key, $arr2))
			{
				if (is_array($value) && is_array($arr2[$key]))
				{
					$arDiff = $this->arrayDiffRecursive($value, $arr2[$key]);
					if (!empty($arDiff))
					{
						$modified[$key] = $arDiff;
					}
				}
				elseif ($value != $arr2[$key])
				{
					$modified[$key] = $value;
				}
			}
			else
			{
				$modified[$key] = $value;
			}
		}
		return $modified;
	}

	private function rememberCurrentMailboxId($mailboxId)
	{
		$previousSeenMailboxId = CUserOptions::GetOption('mail', 'previous_seen_mailbox_id', null);

		if ((int)$previousSeenMailboxId !== (int)$mailboxId)
		{
			CUserOptions::SetOption('mail', 'previous_seen_mailbox_id', $mailboxId);
		}
	}
}
