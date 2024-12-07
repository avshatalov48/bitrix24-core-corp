<?php
namespace Bitrix\Crm\Controller;

use Bitrix\Bitrix24\PhoneVerify;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\WebForm;
use Bitrix\Main\Loader;

/**
 * Class Form
 * @package Bitrix\Crm\Controller
 */
class Form extends Main\Engine\JsonController
{

	private const PHONE_VERIFY_ENTITY = WebForm\Form::PHONE_VERIFY_ENTITY;

	private const EMBED_DEFAULT_WIDGETS_DISPLAY_COUNT = 10;
	private const EMBED_DEFAULT_OPENLINES_DISPLAY_COUNT = 10;

	private const ERROR_CODE_FORM_READ_ACCESS_DENIED = 1;
	private const ERROR_CODE_FORM_WRITE_ACCESS_DENIED = 2;
	private const ERROR_CODE_WIDGET_READ_ACCESS_DENIED = 3;
	private const ERROR_CODE_WIDGET_WRITE_ACCESS_DENIED = 4;
	private const ERROR_CODE_OPENLINES_READ_ACCESS_DENIED = 5;
	private const ERROR_CODE_OPENLINES_WRITE_ACCESS_DENIED = 6;
	private const ERROR_CODE_PHONE_NOT_VERIFIED = 'PHONE_NOT_VERIFIED';

	// check for modify permission if set to false
	private const EMBED_OPENLINES_SHOW_ALL = true;

	private const EMBED_HELP_CENTER_ID = 13003062;

	/**
	 * List forms action.
	 *
	 * @param array $filter Filter.
	 * @return array|null
	 */
	public function listAction($filter = ['active' => true])
	{
		if (!$this->checkFormAccess())
		{
			return [];
		}

		$ormFilter = [];
		if (isset($filter['active']))
		{
			$ormFilter['=ACTIVE'] = $filter['active'] ? 'Y' : 'N';
		}
		if (isset($filter['isCallback']))
		{
			$ormFilter['=IS_CALLBACK'] = $filter['isCallback'] ? 'Y' : 'N';
		}

		$result = WebForm\Internals\FormTable::getDefaultTypeList([
			'select' => ['ID', 'NAME'],
			'filter' => $ormFilter,
			'order' => ['ID' => 'DESC'],
		]);

		return array_map('array_change_key_case', $result->fetchAll());
	}

	/**
	 * Get form action.
	 *
	 * @param int $id Form ID.
	 * @return array
	 */
	public function getAction($id)
	{
		if (!$this->checkFormAccess())
		{
			return [];
		}

		return WebForm\Options::create($id)->getArray();
	}

	/**
	 * Delete form action.
	 *
	 * @param int $id Form ID.
	 *
	 * @return bool
	 */
	public function deleteAction($id)
	{
		if (!$this->checkFormAccess(true))
		{
			$this->addError((new Main\Error('Access denied.', self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED)));
			return false;
		}

		return WebForm\Form::delete($id);
	}

	/**
	 * Reset all the statistic counters in counters column.
	 *
	 * @param $formId
	 *
	 * @return bool
	 * @throws Main\AccessDeniedException
	 */
	public function resetCountersAction($id)
	{
		if (!$this->checkFormAccess(true))
		{
			$this->addError((new Main\Error('Access denied.', self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED)));
			return false;
		}
		// TODO: merge all counters
		return WebForm\Form::resetCounters($id);
	}

	/**
	 * Copy form action.
	 *
	 * @param int $id Form ID.
	 *
	 * @return bool
	 */
	public function copyAction($id)
	{
		if (!$this->checkFormAccess(true))
		{
			$this->addError((new Main\Error('Access denied.', self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED)));
			return false;
		}

		return WebForm\Form::copy($id, Main\Engine\CurrentUser::get()->getId());
	}

	/**
	 * Activate form action.
	 *
	 * @param int $id Form ID.
	 * @param bool $mode Mode.
	 *
	 * @return bool
	 */
	public function activateAction($id, $mode)
	{
		if (!$this->checkFormAccess(true))
		{
			$this->addError((new Main\Error('Access denied.', self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED)));
			return false;
		}

		return WebForm\Form::activate($id, $mode, Main\Engine\CurrentUser::get()->getId());
	}

	/**
	 * Activate list of forms action.
	 *
	 * @param array $list ID list.
	 * @param bool $mode Mode.
	 *
	 * @return void
	 */
	public function activateListAction(array $list, $mode = true)
	{
		if (!$this->checkFormAccess(true))
		{
			$this->addError((new Main\Error('Access denied.', self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED)));
			return;
		}
		foreach ($list as $id)
		{
			WebForm\Form::activate($id, $mode, Main\Engine\CurrentUser::get()->getId());
		}
	}

	/**
	 * Get dict.
	 *
	 * @return array
	 */
	public function getDictAction()
	{
		if (!$this->checkFormAccess())
		{
			return [];
		}

		$tariffRestricted = false;

		if (Loader::includeModule('bitrix24') )
		{
			$tariffRestricted = !\Bitrix\Bitrix24\Feature::isFeatureEnabled('crm_webform_edit');
		}

		return WebForm\Options\Dictionary::instance()->toArray() + [
			'permissions' => [
				'userField' => [
					'add' => Crm\Service\Container::getInstance()->getUserPermissions()->canWriteConfig(),
				],
				'form' => [
					'edit' => $this->getFormAccess(true),
				],
				'tariff' => [
					'restricted' => $tariffRestricted,
				],
			],
		];
	}

	/**
	 * @param int $formId
	 * @return array
	 * @throws Main\AccessDeniedException
	 */
	public function getEmbedAction($formId)
	{
		if (!$this->getFormAccess())
		{
			$this->addError(new Main\Error('Access denied.', self::ERROR_CODE_FORM_READ_ACCESS_DENIED));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_FORM_READ_ACCESS_DENIED]];
		}

		if ($this->shouldVerifyPhone() && !$this->isPhoneVerified($formId))
		{
			$this->addError(new Main\Error('Phone doesn\'t verified', self::ERROR_CODE_PHONE_NOT_VERIFIED, ['id' => $formId]));
			return [];
		}

		$form = new WebForm\Form($formId);
		$formData = $form->get();

		$views = $formData['FORM_SETTINGS']['VIEWS'];
		$dict = $this->getDictForEmbed();
		$viewOptions = $this->buildViewOptions(WebForm\Options::getViewOptions(), $dict);
		$scripts = WebForm\Script::getListContext($formData, []); // embed codes
		$pubLink = WebForm\Script::getUrlContext($formData); // public form link

		$previewLink = strtr(WebForm\Script::getPublicFormPath(), [
			'#id#' => $formData['ID'],
			'#form_id#' => $formData['ID'],
			'#form_code#' => $formData['CODE'],
			'#form_sec#' => $formData['SECURITY_CODE'],
		]);
		$previewLink = WebForm\Script::getDomain() . $previewLink . '?view=preview&preview=#preview#';

		return [
			'dict' => [
				'viewOptions' => $dict,
			],
			'embed' => [
				'scripts' => array_change_key_case($scripts, CASE_LOWER),
				'pubLink' => $pubLink,
				'previewLink' => $previewLink,
				'viewValues' => array_change_key_case($views, CASE_LOWER),
				'viewOptions' => array_change_key_case($viewOptions, CASE_LOWER),
				'helpCenterUrl' => self::getHelpCenterUrl(self::EMBED_HELP_CENTER_ID),
				'helpCenterId' => self::EMBED_HELP_CENTER_ID,
			],
		];
	}

	public function saveEmbedAction(int $formId, array $data)
	{
		if (!$this->getFormAccess(true))
		{
			$this->addError(new Main\Error('Access denied.', self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED]];
		}

		if ($this->shouldVerifyPhone() && !$this->isPhoneVerified($formId))
		{
			$this->addError(new Main\Error('Phone doesn\'t verified', self::ERROR_CODE_PHONE_NOT_VERIFIED, ['id' => $formId]));
			return [];
		}

		$options = WebForm\Options::create($formId);
		$optionsArray = $options->getArray();
		foreach ($data as $type => $values)
		{
			$optionsArray['embedding']['views'][$type] = $values;
		}
		$options->merge($optionsArray);
		$result = $options->save();
		$this->addErrors($result->getErrors());

		$form = new WebForm\Form($formId);
		$form->buildScript();

		return [
			'formId' => $formId,
		];
	}

	/**
	 * @param int $formId
	 * @return array
	 * @throws Main\AccessDeniedException
	 */
	public function getWidgetsForEmbedAction(int $formId, int $count = self::EMBED_DEFAULT_WIDGETS_DISPLAY_COUNT)
	{
		if (!$this->getFormAccess())
		{
			$this->addError(new Main\Error('Access denied.', self::ERROR_CODE_FORM_READ_ACCESS_DENIED));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_FORM_READ_ACCESS_DENIED]];
		}

		if ($this->shouldVerifyPhone() && !$this->isPhoneVerified($formId))
		{
			$this->addError(new Main\Error('Phone doesn\'t verified', self::ERROR_CODE_PHONE_NOT_VERIFIED, ['id' => $formId]));
			return [];
		}

		if (!$this->getSiteButtonAccess())
		{
			$this->addError(new Main\Error('Access denied.', self::ERROR_CODE_WIDGET_READ_ACCESS_DENIED));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_WIDGET_READ_ACCESS_DENIED]];
		}

		$form = new WebForm\Form($formId);
		$formData = $form->get();
		$formType = self::getFormType($form);

		$widgets = $this->loadWidgetsDataForEmbed($formId, $count + 1, $formType); // +1 to check for additional widgets

		$showMoreLink = false;
		if (count($widgets) > $count)
		{
			$showMoreLink = true;
			array_pop($widgets); // +1 to check for additional widgets
		}

		$buttonForPreview = $previewLink = null;
		foreach ($widgets as $widget)
		{
			if (count($widget['relatedFormIds']))
			{
				$buttonForPreview = $widget['id'];
			}
		}

		$previewLink = strtr(WebForm\Script::getPublicFormPath(), [
			'#id#' => $formData['ID'],
			'#form_id#' => $formData['ID'],
			'#form_code#' => $formData['CODE'],
			'#form_sec#' => $formData['SECURITY_CODE'],
		]);
		$previewLink = WebForm\Script::getDomain() . $previewLink . '?view=preview&preview=button&preview_id='.$buttonForPreview;

		return [
			'widgets' => $widgets,
			'url' => [
				'allWidgets' => \CCrmUrlUtil::ToAbsoluteUrl(Crm\SiteButton\Manager::getUrl()),
			],
			'showMoreLink' => $showMoreLink,
			'previewLink' => $previewLink,
			'formName' => $form->getName(),
			'formType' => $formType,
			'helpCenterUrl' => self::getHelpCenterUrl(self::EMBED_HELP_CENTER_ID),
			'helpCenterId' => self::EMBED_HELP_CENTER_ID,
		];
	}

	public function assignWidgetToFormAction(int $formId, int $buttonId, string $assigned)
	{
		if (!$this->getFormAccess(true))
		{
			$this->addError(new Main\Error('Access denied.', self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED]];
		}

		if ($this->shouldVerifyPhone() && !$this->isPhoneVerified($formId))
		{
			$this->addError(new Main\Error('Phone doesn\'t verified', self::ERROR_CODE_PHONE_NOT_VERIFIED, ['id' => $formId]));
			return [];
		}

		if (!$this->getSiteButtonAccess(true))
		{
			$this->addError(new Main\Error('Access denied.', self::ERROR_CODE_WIDGET_WRITE_ACCESS_DENIED));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_WIDGET_WRITE_ACCESS_DENIED]];
		}

		$isAssigned = $assigned === 'Y';

		$form = new WebForm\Form($formId);
		$formType = self::getFormType($form);

		$button = new Crm\SiteButton\Button($buttonId);
		if ($isAssigned)
		{
			$result = $button->setToForm($formId, $formType, true, true);
		}
		else
		{
			$result = $button->unsetFromForm($formId, $formType, true);
		}

		if (! $result)
		{
			$this->addError(new Main\Error('CRM_FORM_ASSIGN_GENERAL_ERROR'));
		}

		Crm\SiteButton\Manager::updateScriptCache($buttonId);

		return [
			'assigned' => $isAssigned,
			'formId' => $formId,
			'formName' => $form->getName(),
			'formType' => $formType,
			'buttonId' => $buttonId,
		];
	}

	public function getOpenlinesForEmbedAction(int $formId, int $count = self::EMBED_DEFAULT_OPENLINES_DISPLAY_COUNT)
	{
		if (!$this->getFormAccess())
		{
			$this->addError(new Main\Error('Access denied.', self::ERROR_CODE_FORM_READ_ACCESS_DENIED));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_FORM_READ_ACCESS_DENIED]];
		}

		if ($this->shouldVerifyPhone() && !$this->isPhoneVerified($formId))
		{
			$this->addError(new Main\Error('Phone doesn\'t verified', self::ERROR_CODE_PHONE_NOT_VERIFIED, ['id' => $formId]));
			return [];
		}

		if (!\Bitrix\Main\Loader::includeModule('imopenlines'))
		{
			$this->addError(new Main\Error('Module openlines not installed.', self::ERROR_CODE_OPENLINES_READ_ACCESS_DENIED));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_OPENLINES_READ_ACCESS_DENIED]];
		}

		$config = new \Bitrix\Imopenlines\Config();

		$requestOptions = self::EMBED_OPENLINES_SHOW_ALL
			? []
			: ['CHECK_PERMISSION' => \Bitrix\ImOpenlines\Security\Permissions::ACTION_MODIFY]
		;
		$openlines = $config->getList(
			[
				'select' => ['ID', 'LINE_NAME', 'USE_WELCOME_FORM', 'WELCOME_FORM_ID', 'WELCOME_FORM_DELAY'],
				'filter' => ['=ACTIVE' => 'Y', '=TEMPORARY' => 'N'],
				'limit' => $count + 1, // +1 to check for additional lines
			],
			$requestOptions
		);

		$form = new WebForm\Form($formId);
		$formData = $form->get();

		$showMoreLink = false;
		if (count($openlines) > $count)
		{
			$showMoreLink = true;
			array_pop($openlines); // +1 to check for additional lines
		}

		$openlines = $this->prepareOpenlinesDataForEmbed($formId, $openlines);

		$buttonForPreview = $previewLink = null;
		foreach ($openlines as $line)
		{
			$buttons = Crm\SiteButton\Manager::getWidgetsByOpenlineId($line['id']);
			/** @var Crm\SiteButton\Button $button */
			foreach ($buttons as $button)
			{
				$data = $button->getData();
				if ($data['ACTIVE'] === 'Y')
				{
					$buttonForPreview = $button->getId();
					break 2;
				}
			}
		}

		if ($buttonForPreview)
		{
			$previewLink = strtr(WebForm\Script::getPublicFormPath(), [
				'#id#' => $formData['ID'],
				'#form_id#' => $formData['ID'],
				'#form_code#' => $formData['CODE'],
				'#form_sec#' => $formData['SECURITY_CODE'],
			]);
			$previewLink = WebForm\Script::getDomain() . $previewLink . '?view=preview&preview=ol&preview_id='.$buttonForPreview;
		}

		return [
			'formName' => $form->getName(),
			'lines' => $openlines,
			'url' => [
				'allLines' => $this->getOpenlinesUrl(),
			],
			'showMoreLink' => $showMoreLink,
			'previewLink' => $previewLink,
			'helpCenterUrl' => self::getHelpCenterUrl(self::EMBED_HELP_CENTER_ID),
			'helpCenterId' => self::EMBED_HELP_CENTER_ID,
		];
	}

	public function assignOpenlinesToFormAction(int $formId, int $lineId, string $assigned, string $afterMessage = 'N')
	{
		if (!$this->getFormAccess(true))
		{
			$this->addError(new Main\Error('Access denied.', self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_FORM_WRITE_ACCESS_DENIED]];
		}

		if ($this->shouldVerifyPhone() && !$this->isPhoneVerified($formId))
		{
			$this->addError(new Main\Error('Phone doesn\'t verified', self::ERROR_CODE_PHONE_NOT_VERIFIED, ['id' => $formId]));
			return [];
		}

		if (!$this->getOpenlineModifyAccess($lineId))
		{
			$this->addError(new Main\Error('Access denied.', self::ERROR_CODE_OPENLINES_WRITE_ACCESS_DENIED, ['lineId' => $lineId]));
			return ['error' => ['status' => 'access denied', 'code' => self::ERROR_CODE_OPENLINES_WRITE_ACCESS_DENIED, 'lineId' => $lineId]];
		}

		$isAssigned = $assigned === 'Y';
		$isAfter = $afterMessage === 'Y';

		$config = new \Bitrix\Imopenlines\Config();

		$updateResult = $config->update($lineId, [
			"USE_WELCOME_FORM" => $isAssigned ? 'Y' : 'N',
			"WELCOME_FORM_ID" => $formId,
			"WELCOME_FORM_DELAY" => $isAfter ? 'Y' : 'N', // 'Y' - after first message, 'N' - before
		]);

		$form = new WebForm\Form($formId);

		if (! $updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());
		}

		return [
			'assigned' => $isAssigned,
			'formId' => $formId,
			'formName' => $form->getName(),
			'lineId' => $lineId,
		];
	}

	/**
	 * Save form action.
	 *
	 * @param array $options Options.
	 * @return array
	 */
	public function saveAction(array $options)
	{
		if (!$this->checkFormAccess(true))
		{
			return [];
		}

		$formOptions = WebForm\Options::createFromArray($options);
		$formId = (int)$formOptions->getForm()->getId();

		if ($this->shouldVerifyPhone() && !$this->isPhoneVerified($formId))
		{
			$this->addError(new Main\Error('Phone doesn\'t verified', static::ERROR_CODE_PHONE_NOT_VERIFIED, ['id' => $formId]));
			return [];
		}

		(new WebForm\FieldSynchronizer())->replaceOptionFields($formOptions);
		$result = $formOptions->save();
		$this->addErrors($result->getErrors());
		if ($result->getErrorCollection()->isEmpty())
		{
			return WebForm\Options::create($formOptions->getForm()->getId())->getArray();
		}

		return $formOptions->getArray();
	}

	/**
	 * Prepare form action.
	 *
	 * @param array $options Options.
	 * @param array $preparing Preparing data.
	 * @return array
	 */
	public function prepareAction(array $options, array $preparing)
	{
		if (!$this->checkFormAccess())
		{
			return [];
		}

		if (!empty($preparing['templateId']))
		{
			$preparing = Main\DI\ServiceLocator::getInstance()
				->get('crm.service.webform.scenario')
				->prepareForm($preparing['templateId'], $options)
			;
		}

		if (!empty($preparing['integration']))
		{
			$preparing['fields'] = [];
			$fieldList = [];
			$cases = $preparing['integration']['cases'] ?? [];
			foreach ($cases as $case)
			{
				foreach (($case['fieldsMapping'] ?? []) as $field)
				{
					if (!$field['crmFieldKey'])
					{
						continue;
					}

					$fieldList[] = $field['crmFieldKey'];
				}
			}

			$preparing['fields'] = array_map(
				function ($name)
				{
					return ['name' => $name];
				},
				array_unique($fieldList)
			);
			$options['data']['fields'] = [];
		}

		if (!empty($preparing['fields']) && is_array($preparing['fields']))
		{
			$fieldNames = [];
			foreach ($preparing['fields'] as $field)
			{
				if (!is_array($field) || empty($field['name']))
				{
					continue;
				}

				$fieldNames[$field['name']] = $field;
			}

			if (!empty($options['data']['fields']))
			{
				$fields = array_map(
					static function ($field) use ($fieldNames) : ?array
					{
						if (!$fieldNew = $fieldNames[$field['name']])
						{
							return null;
						}

						return array_replace_recursive($field, $fieldNew);
					},
					$options['data']['fields'],
				);
				$options['data']['fields'] = array_filter($fields);
			}
		}

		if (!empty($preparing['agreements']) && is_array($preparing['agreements']))
		{
			$existed = [];
			foreach ($preparing['agreements'] as $item)
			{
				if ($item && is_numeric($item))
				{
					$existed[] = $item;
					continue;
				}

				if (!is_array($item) || empty($item['id']))
				{
					continue;
				}

				$existed[] = $item['id'];
			}

			if (!empty($options['data']['agreements']))
			{
				$options['data']['agreements'] = array_filter(
					$options['data']['agreements'],
					function ($item) use ($existed)
					{
						return in_array($item['id'], $existed);
					}
				);
			}
		}

		$formOptions = WebForm\Options::createFromArray($options);

		if (!empty($preparing['agreements']) && is_array($preparing['agreements']))
		{
			$existed = array_column($options['data']['agreements'] ?? [], 'id');
			foreach ($preparing['agreements'] as $agreement)
			{
				$id = is_numeric($agreement) ? $agreement : null;
				$id = !empty($agreement['id']) ? $agreement['id'] : $id;
				if (!in_array($id, $existed))
				{
					$existed[] = $id;
					$formOptions->getConfig()->appendAgreement($id);
				}
			}
		}

		if (!empty($preparing['fields']) && is_array($preparing['fields']))
		{
			$fieldNames = array_column($options['data']['fields'] ?? [], 'name');
			foreach ($preparing['fields'] as $field)
			{
				if (!is_array($field))
				{
					continue;
				}

				if (!empty($field['name']) && in_array($field['name'], $fieldNames) && !$field['inPreparing'])
				{
					continue;
				}

				if (!empty($field['name']) || !empty($field['type']))
				{
					//if (in_array($field['type'], ['br', 'hr', '']))
					$field['inPreparing'] = false;
					$formOptions->getConfig()->appendField($field);
				}
			}
		}

		//$result = $formOptions->save();
		//$this->addErrors($result->getErrors());

		return $formOptions->getArray();
	}

	/**
	 * Check form action.
	 *
	 * @param array $options Options.
	 * @return array
	 */
	public function checkAction(array $options)
	{
		if (!$this->checkFormAccess())
		{
			return [];
		}

		if ($options['templateId'] && !$options['id'])
		{
			return Main\DI\ServiceLocator::getInstance()
				->get('crm.service.webform.scenario')
				->check($options['templateId'])
			;
		}

		$schemeId = (int) $options['document']['scheme'] ?? null;
		if (!$schemeId || empty($options['data']['fields']) || !is_array($options['data']['fields']))
		{
			return [];
		}

		$fieldNames = [];
		foreach ($options['data']['fields'] as $field)
		{
			if (!is_array($field) || empty($field['name']))
			{
				continue;
			}

			$fieldNames[] = $field['name'];
		}


		if(!in_array($schemeId, WebForm\Entity::getSchemesCodes()))
		{
			return [];
		}

		$syncErrors = [];
		$syncFields = [];
		$fieldNames = (new WebForm\FieldSynchronizer())->getSynchronizeFields($schemeId, $fieldNames);
		foreach ($options['data']['fields'] as $field)
		{
			if ($field['type'] === 'resourcebooking' && !WebForm\Entity::isSchemeSupportEntity($schemeId, (int) $field['editing']['entityId']))
			{
				$syncErrors[] = Main\Localization\Loc::getMessage(
					'CRM_WEBFORM_FIELD_SYNCHRONIZER_ERR_RES_BOOK',
					[
						'%fieldCaption%' => $field['label'],
						'%entityCaption%' => implode(
							', ',
							array_map(
								function ($entityName)
								{
									return \CCrmOwnerType::getCategoryCaption(\CCrmOwnerType::resolveID($entityName));
								},
								WebForm\Entity::getSchemes($schemeId)['ENTITIES'] ?? []
							)
						),
					]
				);
				continue;
			}

			if (!is_array($field) || empty($field['name']) || !in_array($field['name'], $fieldNames))
			{
				continue;
			}

			$syncFields[] = $field;
		}

		return [
			'sync' => [
				'errors' => $syncErrors,
				'fields' => $syncFields,
			],
		];
	}

	/**
	 * Set editor ID action.
	 *
	 * @param int $editorId Editor ID.
	 * @return void
	 */
	public function setEditorAction($editorId)
	{
		if (!$this->checkFormAccess(true))
		{
			return;
		}

		Crm\Settings\WebFormSettings::getCurrent()->setEditorId($editorId);
	}

	/**
	 * Get captcha action.
	 *
	 * @return array
	 * @throws Main\AccessDeniedException
	 */
	public function getCaptchaAction(): array
	{
		if (!$this->checkFormAccess())
		{
			return [];
		}

		return [
			'key' => WebForm\ReCaptcha::getKey(2),
			'secret' => WebForm\ReCaptcha::getSecret(2),
			'canChange' => $this->getFormAccess(true),
			'hasDefaults' => WebForm\ReCaptcha::getDefaultKey(2) && WebForm\ReCaptcha::getDefaultSecret(2),
		];
	}

	/**
	 * Set captcha action.
	 *
	 * @param string $key Key.
	 * @param string $secret Secret.
	 * @return array
	 * @throws Main\AccessDeniedException
	 */
	public function setCaptchaAction(string $key, string $secret): array
	{
		if (!$this->checkFormAccess(true))
		{
			return [];
		}

		if (!$key || !$secret)
		{
			$key = '';
			$secret = '';
		}

		$oldKey = WebForm\ReCaptcha::getKey(2);
		$oldSecret = WebForm\ReCaptcha::getSecret(2);

		WebForm\ReCaptcha::setKey($key, $secret, 2);
		if ($key !== $oldKey || $secret !== $oldSecret)
		{
			$app = Crm\UI\Webpack\Form\App::instance();
			if ($app->build())
			{
				Crm\UI\Webpack\Form::addCheckResourcesAgent();
			}
		}

		return $this->getCaptchaAction();
	}

	public function getFileLimitAction(): array
	{
		if (!$this->checkFormAccess())
		{
			return [];
		}

		$limitMb = WebForm\Limitations\DailyFileUploadLimit::instance()->getLimit();
		$currentBytes =  WebForm\Limitations\DailyFileUploadLimit::instance()->getCurrent();

		return [
			'limitMb' => $limitMb,
			'currentBytes' => $currentBytes,
			'canChange' => $this->getFormAccess(true),
		];
	}

	public function setFileLimitAction(?int $limitMb): array
	{
		if (!$this->checkFormAccess(true))
		{
			return [];
		}

		if ($limitMb > 0 || $limitMb === null)
		{
			WebForm\Limitations\DailyFileUploadLimit::instance()->setLimit($limitMb);
		}

		return $this->getFileLimitAction();
	}

	protected function checkFormAccess($write = false)
	{
		if(!$this->getFormAccess($write))
		{
			$this->addError(new Main\Error('Access denied.', 510));
			return false;
		}

		return true;
	}

	protected function checkSiteButtonAccess($write = false)
	{
		if(!$this->getSiteButtonAccess($write))
		{
			throw new Main\AccessDeniedException();
		}
	}

	protected function getFormAccess($write = false): bool
	{
		$hasTariffAccess = true;

		if ($write)
		{
			if (Loader::includeModule('bitrix24'))
			{
				$hasTariffAccess = \Bitrix\Bitrix24\Feature::isFeatureEnabled('crm_webform_edit');
			}
		}

		return $write
			? $hasTariffAccess && WebForm\Manager::checkWritePermission()
			: WebForm\Manager::checkReadPermission();
	}

	protected function getSiteButtonAccess($write = false): bool
	{
		return $write
			? Crm\SiteButton\Manager::checkWritePermission()
			: Crm\SiteButton\Manager::checkReadPermission()
		;
	}

	protected function getOpenlineModifyAccess(int $lineId)
	{
		if (\Bitrix\Main\Loader::includeModule('imopenlines'))
		{
			return \Bitrix\Imopenlines\Config::canEditLine($lineId);
		}

		return false;
	}

	private function getFormNames(array $formIds): array
	{
		$formIds = array_unique($formIds);
		$result = WebForm\Internals\FormTable::getDefaultTypeList([
			'select' => ['ID', 'NAME'],
			'filter' => [
				'=ID' => $formIds,
			],
		]);
		$rows = $result->fetchAll();
		$formNames = [];
		foreach ($rows as $row)
		{
			$formNames[$row['ID']] = $row['NAME'];
		}
		return $formNames;
	}

	private function loadWidgetsDataForEmbed(int $formId, int $count, string $formType): array
	{
		$result = $otherWidgets = [];
		$widgets = \Bitrix\Crm\SiteButton\Internals\ButtonTable::getList(['filter' => ['=ACTIVE' => 'Y']]);

		if (!$widgets) {
			return [];
		}

		$counted = 0;
		foreach ($widgets as $data)
		{
			$button = new Crm\SiteButton\Button();
			$button->loadByData($data);
			$buttonId = $button->getId();
			$formIds = $button->getWebFormIdList();

			if (in_array($formId, $formIds, true))
			{ // collect related widgets
				$result[$buttonId] = $this->constructEmbedWidgetsDataSet($buttonId, $button, $formType, true);
				$counted++;
			}
			else
			{ // collect all other widgets
				$otherWidgets[$buttonId] = $this->constructEmbedWidgetsDataSet($buttonId, $button, $formType, false);
			}

			if ($counted === $count)
			{
				break;
			}
		}

		// merge some more widgets
		$resultCount = count($result);
		if ($resultCount < $count)
		{
			$needMore = $count - $resultCount;
			$moreWidgets = array_slice($otherWidgets, 0, $needMore);
			array_push($result, ...$moreWidgets);
		}

		// fetch all related form names
		$allRelatedFormIds = [];
		foreach ($result as $data)
		{
			$allRelatedFormIds[] = $data['relatedFormIds'];
		}
		$allRelatedFormIds = array_merge([], ...$allRelatedFormIds);
		$formNames = $this->getFormNames($allRelatedFormIds);
		foreach ($result as $btnId => $btnData)
		{
			foreach ($btnData['relatedFormIds'] as $frmId)
			{
				$result[$btnId]['relatedFormNames'][$frmId] = $formNames[$frmId] ?? 'form #' . $frmId;
			}
		}

		return $result;
	}

	private function constructEmbedWidgetsDataSet(int $buttonId, Crm\SiteButton\Button $button, string $formType, bool $checked)
	{
		$relatedIds = $button->getWebFormIdList($formType);
		return [
			'id' => $buttonId,
			'name' => $button->getName(),
			'checked' => $checked,
			'relatedFormIds' => $relatedIds,
			'relatedFormNames' => [],
		];
	}

	private static function getFormType(WebForm\Form $form): string
	{
		if ($form->isWhatsApp())
		{
			return Crm\SiteButton\Manager::ENUM_TYPE_WHATSAPP;
		}

		// The whatsapp form is also a callback form, the order is important
		if ($form->isCallback())
		{
			return Crm\SiteButton\Manager::ENUM_TYPE_CALLBACK;
		}

		return Crm\SiteButton\Manager::ENUM_TYPE_CRM_FORM;
	}

	private function buildViewOptions(array $rules, array $dict): array
	{
		$result = [];
		foreach ($rules as $typeName => $typeData)
		{
			$result[$typeName] = self::buildOptionValues($typeData, $dict);
		}
		return $result;
	}

	private static function buildOptionValues($data, array $dict): array
	{
		$result = [];
		foreach ($data as $key => $option)
		{
			if (is_array($option))
			{
				// TODO
				$result[$key] = self::buildOptionValues($option, $dict[$key] ?? []);
			}

			$values = $dict[$option . 's'] ?? [];
			foreach ($values as $value)
			{
				$result[$option][] = $value['id'];
			}
		}
		return $result;
	}

	/**
	 * Dict for embed options
	 *
	 * @return array
	 */
	private function getDictForEmbed(): array
	{
		$dict = WebForm\Options\Dictionary::instance()->getViews();

		// add delay options in seconds from 3 to 120
		if (! isset($dict['delays']))
		{
			$secondsLoc = Main\Localization\Loc::getMessage('CRM_WEBFORM_SCRIPT_SEC');
			$dict['delays'] = array_map(
				static function ($val) use ($secondsLoc) {
					// FormatDate('sdiff', 0, $val)
					return ['id' => (string)$val, 'name' => $val.' '.$secondsLoc];
				},
				[3,5,7,10,15,20,25,30,40,60,120]
			);
		}

		return $dict;
	}

	private function prepareOpenlinesDataForEmbed(int $formId, array $data): array
	{
		$newData = [];
		$relatedFormIds = array_column($data, 'WELCOME_FORM_ID');
		$relatedFormIds = array_filter($relatedFormIds);
		$formNames = $this->getFormNames($relatedFormIds);

		foreach ($data as $row)
		{
			$relatedFormId = is_numeric($row['WELCOME_FORM_ID']) ? (int)$row['WELCOME_FORM_ID'] : null;
			$formName = ($relatedFormId && isset($formNames[$relatedFormId])) ? $formNames[$relatedFormId] : '';
			$formEnabled = $row['USE_WELCOME_FORM'] === 'Y';
			$newData[(int)$row['ID']] = [
				'checked' => $formEnabled && $formId === $relatedFormId,
				'id' => (int)$row['ID'],
				'name' => htmlspecialcharsbx($row['LINE_NAME']),
				'formEnabled' => $formEnabled,
				'formId' => $relatedFormId,
				'formName' => $formName,
				'formDelay' => $row['WELCOME_FORM_DELAY'] === 'Y',
			];
		}

		return $newData;
	}

	private function getOpenlinesUrl(): string
	{
		if (
			\Bitrix\Main\Loader::includeModule('imopenlines')
			&& method_exists(\Bitrix\ImOpenLines\Common::class, 'getDialogListUrl')
		)
		{
			return \Bitrix\ImOpenLines\Common::getDialogListUrl();
		}

		$openlinesUrl = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
			? '/openlines/list'
			: '/services/openlines/list';
		return \CCrmUrlUtil::ToAbsoluteUrl($openlinesUrl);
	}

	private static function getHelpCenterUrl(int $helpCenterId): string
	{
		return Main\Loader::includeModule('ui')
			? \Bitrix\UI\Util::getArticleUrlByCode($helpCenterId)
			: 'https://helpdesk.bitrix24.ru/open/'.$helpCenterId
		;
	}

	private function isPhoneVerified(int $formId): bool
	{
		return
			!\Bitrix\Main\Loader::includeModule('bitrix24')
			|| (new PhoneVerify(self::PHONE_VERIFY_ENTITY, $formId))->isVerified()
		;
	}

	private function shouldVerifyPhone(): bool
	{
		if (!\Bitrix\Main\Loader::includeModule('bitrix24'))
		{
			return false;
		}

		$validatedLicenseType = [
			'project',
			'demo'
		];

		return in_array(\CBitrix24::getLicenseType(), $validatedLicenseType, true);
	}
}
