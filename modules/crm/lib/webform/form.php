<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Crm\SiteButton;
use Bitrix\Crm\UI\Webpack;
use Bitrix\Crm\WebForm\Internals;
use Bitrix\Crm\WebForm\Internals\LandingTable;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\SalesCenter;

Loc::loadMessages(__FILE__);

class Form
{
	const REDIRECT_DELAY = 5;

	public const PHONE_VERIFY_ENTITY = 'crm_webform';

	protected $id = null;
	protected static $defaultParams = array(
		'TYPE_ID' => Internals\FormTable::TYPE_DEFAULT,
		'ACTIVE' => 'N',
		'IS_SYSTEM' => 'N',
		'COPYRIGHT_REMOVED' => 'N',
		'IS_PAY' => 'N',
		'LANGUAGE_ID' => '',
		'USE_CAPTCHA' => 'N',
		'USE_LICENCE' => 'N',
		'LICENCE_BUTTON_IS_CHECKED' => 'Y',
		'FIELDS' => array(),
		'PRESET_FIELDS' => array(),
		'INVOICE_SETTINGS' => array(),
		'FORM_SETTINGS' => array(
			'VIEWS' => [],
			'DESIGN' => [],
		),
		'DEP_GROUPS' => array(),
		'DEPENDENCIES' => array(),
		'AGREEMENTS' => array(),
	);
	protected $params = array();
	protected $errors = array();
	protected $isEmbeddedAvailableChanged = false;
	protected $forceBuild = false;
	protected $integration;

	public function __construct($id = null, array $params = null)
	{
		$this->params = self::$defaultParams;
		$this->integration = new Options\Integration($this);
		if($id)
		{
			$this->load($id);
		}

		if($params)
		{
			$this->set($params);
		}
	}

	public function isSystem()
	{
		return $this->params['IS_SYSTEM'] == 'Y';
	}

	public function setSystem()
	{
		$this->params['IS_SYSTEM'] = 'Y';
	}

	public function set(array $params)
	{
		$this->params = $params;
	}

	public function get()
	{
		return $this->params;
	}

	public function getIntegration()
	{
		return $this->integration;
	}

	public function getName(): string
	{
		return $this->params['NAME'] ?? '';
	}

	public function merge($params)
	{
		$oldData = $this->get();

		if (isset($params['AGREEMENTS']))
		{
			$agreements = [];
			foreach ($params['AGREEMENTS'] as $agreement)
			{
				$agreementId = (int) ($agreement['ID'] ?? $agreement['AGREEMENT_ID']);
				$agreements[$agreementId] = $agreement;
			}
			$params['AGREEMENTS'] = array_values($agreements);
		}

		$params['FORM_SETTINGS'] = isset($params['FORM_SETTINGS']) ? $params['FORM_SETTINGS'] : [];
		$params['FORM_SETTINGS'] = $params['FORM_SETTINGS'] + $oldData['FORM_SETTINGS'];
		$this->set($params + $oldData);
	}

	public static function getIdByCode($formCode)
	{
		$idByCode = array();

		$formCodePieces = explode('_', $formCode);
		if(is_numeric($formCodePieces[0]))
		{
			return (int) $formCodePieces[0];
		}

		$cacheId = 'crm_webform_getIdByCode_' . serialize($formCode);
		$cache = \Bitrix\Main\Data\Cache::createInstance();
		if($cache->startDataCache(36000, $cacheId))
		{
			$formDb = Internals\FormTable::getList(array(
				'select' => array('ID', 'CODE'),
				'filter' => array('=CODE' => $formCode),
				'limit' => 1
			));
			while($form = $formDb->fetch())
			{
				$idByCode[$form['CODE']] = $form['ID'];
			}

			if(isset($idByCode[$formCode]))
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->startTagCache($cache->getPath($cacheId));
					$CACHE_MANAGER->RegisterTag(Form::getCacheTag($idByCode[$formCode]));
				}
			}

			$cache->endDataCache(array('CODE_BY_ID' => $idByCode));

			if(isset($idByCode[$formCode]))
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->endTagCache();
				}
			}
		}
		else
		{
			$cacheVars = $cache->getVars();
			if(isset($cacheVars['CODE_BY_ID']))
			{
				$idByCode = $cacheVars['CODE_BY_ID'];
			}
		}

		return isset($idByCode[$formCode]) ? $idByCode[$formCode] : null;
	}

	public static function updateBackgroundImage($formId, $fileId)
	{
		$updateResult = Internals\FormTable::update($formId, array('BACKGROUND_IMAGE' => $fileId));
		return $updateResult->isSuccess();
	}

	public static function delete($formId, $forceSystem = false)
	{
		$form = Internals\FormTable::getRowById($formId);
		if(!$form || (!$forceSystem && $form['IS_SYSTEM'] == 'Y'))
		{
			return false;
		}

		(new self($formId))->getIntegration()->delete();
		$deleteResult = Internals\FormTable::delete($formId);
		if($deleteResult->isSuccess())
		{
			Webpack\Form::instance($formId)->delete();
			static::cleanCacheByTag($formId);
			return true;
		}
		else
		{
			return false;
		}
	}

	public function isActive()
	{
		return $this->params['ACTIVE'] == 'Y';
	}

	public function isUsedCaptcha()
	{
		return $this->params['USE_CAPTCHA'] == 'Y';
	}

	public function isCallback()
	{
		return $this->params['IS_CALLBACK_FORM'] == 'Y';
	}

	public function isWhatsApp()
	{
		return $this->params['IS_WHATSAPP_FORM'] == 'Y';
	}

	public function checkSecurityCode($code)
	{
		return $this->params['SECURITY_CODE'] === $code;
	}

	public function loadOnlyForm($id)
	{
		$this->setId($id);
		$result = Internals\FormTable::getRowById($id);
		if(!$result)
		{
			return false;
		}

		if(!is_array($result['FORM_SETTINGS']))
		{
			$result['FORM_SETTINGS'] = [];
		}
		if (empty($result['FORM_SETTINGS']['VIEWS']))
		{
			$result['FORM_SETTINGS']['VIEWS'] = [
				'click' => [
					'type' => 'panel',
					'position' => 'right',
					'vertical' => 'bottom',
				],
				'auto' => [
					'type' => 'popup',
					'position' => 'center',
					'vertical' => 'bottom',
					'delay' => 5,
				]
			];
		}
		if (empty($result['FORM_SETTINGS']['DESIGN']))
		{
			$result['FORM_SETTINGS']['DESIGN'] = [];
		}

		$this->params = $result;
		return true;
	}

	public function load($id)
	{
		$this->setId($id);
		if(count($this->params) == count(self::$defaultParams) && !$this->loadOnlyForm($this->id))
		{
			return false;
		}

		$this->params['PRESET_FIELDS'] = array();
		$dbPresetField = Internals\PresetFieldTable::getList(array(
			'select' => array('ENTITY_NAME', 'FIELD_NAME', 'VALUE'),
			'filter' => array('=FORM_ID' => $id)
		));
		while($presetField = $dbPresetField->fetch())
		{
			$this->params['PRESET_FIELDS'][] = $presetField;
		}
		unset($dbPresetField);

		$fieldResult = Internals\FieldTable::getList(array(
			'filter' => array('=FORM_ID' => $id),
			'order' => array('SORT' => 'ASC', 'CAPTION')
		));
		$fieldResult->addFetchDataModifier(
			function ($data)
			{
				$data['ITEMS'] = is_array($data['ITEMS']) ? $data['ITEMS'] : [];
				return $data;
			}
		);
		$this->params['FIELDS'] = $fieldResult->fetchAll();
		unset($fieldResult);


		$this->params['DEPENDENCIES'] = Internals\FieldDependenceTable::getList(array(
			'filter' => array('=FORM_ID' => $id)
		))->fetchAll();
		$this->params['DEP_GROUPS'] = Internals\FieldDepGroupTable::getList([
			'select' => ['ID', 'TYPE_ID'],
			'filter' => ['=FORM_ID' => $id]
		])->fetchAll();
		if ($this->params['DEPENDENCIES'] && !$this->params['DEP_GROUPS'])
		{
			$this->params['DEP_GROUPS'][] = [
				'ID' => 0,
				'TYPE_ID' => Internals\FieldDepGroupTable::TYPE_DEF,
			];
		}

		$this->params['AGREEMENTS'] = Internals\AgreementTable::getList([
			'select' => ['AGREEMENT_ID', 'CHECKED', 'REQUIRED'],
			'filter' => ['=FORM_ID' => $id]
		])->fetchAll();
		if ($this->params['AGREEMENT_ID'])
		{
			$this->params['AGREEMENTS'] = array_merge(
				[[
					'AGREEMENT_ID' => $this->params['AGREEMENT_ID'],
					'CHECKED' => $this->params['LICENCE_BUTTON_IS_CHECKED'] === 'Y' ? 'Y' : 'N',
					'REQUIRED' => 'Y',
				]],
				$this->params['AGREEMENTS']
			);
		}
		elseif (!empty($this->params['AGREEMENTS']))
		{
			$this->params['AGREEMENT_ID'] = $this->params['AGREEMENTS'][0]['AGREEMENT_ID'];
			$this->params['LICENCE_BUTTON_IS_CHECKED'] = $this->params['AGREEMENTS'][0]['CHECKED'];
		}

		$responsibleQueue = new ResponsibleQueue($id);
		$responsibles = $responsibleQueue->getList();
		if ($this->params['ASSIGNED_BY_ID'])
		{
			$responsibles[] = $this->params['ASSIGNED_BY_ID'];
		}
		$this->params['ASSIGNED_BY_ID'] = array_unique($responsibles);
		$this->params['ASSIGNED_WORK_TIME'] = $responsibleQueue->isWorkTimeCheckEnabled() ? 'Y' : 'N';
		$this->params['INTEGRATION'] = $this->getIntegration()->load();

		return true;
	}

	public function save($onlyCheck = false)
	{
		$this->errors = array();
		$result = $this->params;
		unset($result['SCRIPTS']);

		$agreements = [];
		foreach ($result['AGREEMENTS'] ?? [] as $agreement)
		{
			$agreementId = $agreement['ID'] ?? $agreement['AGREEMENT_ID'] ?? null;
			if (!$agreementId || in_array($agreementId, array_column($agreements, 'AGREEMENT_ID')))
			{
				continue;
			}

			$agreements[] = [
				'AGREEMENT_ID' => $agreementId,
				'CHECKED' => $agreement['CHECKED'] === 'Y' ? 'Y' : 'N',
				'REQUIRED' => $agreement['REQUIRED'] === 'Y' ? 'Y' : 'N',
			];
		}
		unset($result['AGREEMENTS']);
		if ($result['AGREEMENT_ID'])
		{
			if (!in_array($result['AGREEMENT_ID'], array_column($agreements, 'AGREEMENT_ID')))
			{
				$agreements[] = [
					'AGREEMENT_ID' => $result['AGREEMENT_ID'],
					'CHECKED' => $result['LICENCE_BUTTON_IS_CHECKED'] === 'Y' ? 'Y' : 'N',
					'REQUIRED' => 'Y',
				];
			}
			elseif (isset($result['LICENCE_BUTTON_IS_CHECKED']))
			{
				foreach ($agreements as $index => $agreement)
				{
					if ($agreement['AGREEMENT_ID'] != $result['AGREEMENT_ID'])
					{
						continue;
					}

					$agreements[$index]['CHECKED'] = $result['LICENCE_BUTTON_IS_CHECKED'] === 'Y' ? 'Y' : 'N';
				}
			}
		}
		$result['AGREEMENT_ID'] = null;

		$fields = $result['FIELDS'];
		unset($result['FIELDS']);


		$depGroups = $result['DEP_GROUPS'];
		unset($result['DEP_GROUPS']);
		$dependencies = $result['DEPENDENCIES'];
		unset($result['DEPENDENCIES']);

		$presetFields = $result['PRESET_FIELDS'];
		unset($result['PRESET_FIELDS']);


		unset($result['INTEGRATION']);

		$assignedById = $result['ASSIGNED_BY_ID'];
		$assignedWorkTime = $result['ASSIGNED_WORK_TIME'];
		$result['ASSIGNED_BY_ID'] = null;
		unset($result['ASSIGNED_WORK_TIME']);

		// captcha
		$captchaKey = $result['CAPTCHA_KEY'] ?? '';
		$captchaSecret = $result['CAPTCHA_SECRET'] ?? '';
		$captchaVersion = $result['CAPTCHA_VERSION'] ?? '';
		if ($captchaKey <> '' && $captchaSecret <> '')
		{
			if (
				$captchaKey !== ReCaptcha::getKey($captchaVersion)
				&& $captchaSecret !== ReCaptcha::getSecret($captchaVersion)
			)
			{
				$this->forceBuild = true;
				ReCaptcha::setKey($captchaKey, $captchaSecret, $captchaVersion);
			}
		}
		unset($result['CAPTCHA_KEY']);
		unset($result['CAPTCHA_SECRET']);
		unset($result['CAPTCHA_VERSION']);

		$result['ENTITY_SCHEME'] = (string) $result['ENTITY_SCHEME'];

		if($onlyCheck)
		{
			/*INTEGRATION*/
			$this->prepareResult('INTEGRATION',$this->getIntegration()->checkData());

			if(!in_array($result['ENTITY_SCHEME'], $this->getAllowedEntitySchemes()))
			{
				$this->errors[] = Loc::getMessage('CRM_WEBFORM_FORM_ERROR_SCHEME');
			}

			// captcha
			if($result['USE_CAPTCHA'] == 'Y')
			{
				$hasCaptchaKey = ReCaptcha::getKey(2) <> '' && ReCaptcha::getSecret(2) <> '';
				$hasCaptchaDefaultKey = ReCaptcha::getDefaultKey(2) <> '' && ReCaptcha::getDefaultSecret(2) <> '';
				if (!$hasCaptchaKey && !$hasCaptchaDefaultKey)
				{
					$this->errors[] = Loc::getMessage('CRM_WEBFORM_FORM_ERROR_CAPTCHA_KEY');
				}
			}

			$formResult = new Main\Entity\Result;
			$result['DATE_CREATE'] = new Main\Type\DateTime();
			Internals\FormTable::checkFields($formResult, $this->id, $result);
			$this->prepareResult('FIELDS', $formResult);

			foreach($presetFields as $presetField)
			{
				$presetField['FORM_ID'] = (int) $this->id;
				$presetFieldResult = new Main\Entity\Result;
				Internals\PresetFieldTable::checkFields($presetFieldResult, null, $presetField);
				$replaceList = null;
				if(!$presetFieldResult->isSuccess())
				{
					$field = EntityFieldProvider::getField($presetField['ENTITY_NAME'] . '_' . $presetField['FIELD_NAME']);
					if($field)
					{
						$replaceList = array('VALUE' => $field['caption']);
					}
				}
				$this->prepareResult('PRESET_FIELDS', $presetFieldResult, $replaceList);
			}

			$fieldCodeList = array();
			foreach($fields as $field)
			{
				$field['FORM_ID'] = (int) $this->id;

				$fieldResult = new Main\Entity\Result;
				Internals\FieldTable::checkFields($fieldResult, null, $field);
				$this->prepareResult('FIELDS', $fieldResult);

				$fieldCodeList[] = $field['CODE'];
			}

			foreach($depGroups as $depGroup)
			{
				$depGroup['FORM_ID'] = (int) $this->id;

				if(!in_array($depGroup['IF_FIELD_CODE'], array_keys(Internals\FieldDepGroupTable::getDepGroupTypes())))
				{
					continue;
				}

				$depGroupResult = new Main\Entity\Result;
				Internals\FieldDepGroupTable::checkFields($depGroupResult, null, $depGroup);
				$this->prepareResult('DEP_GROUPS', $depGroupResult);
			}

			foreach($dependencies as $dependency)
			{
				$dependency['FORM_ID'] = (int) $this->id;
				$dependency['GROUP_ID'] = (int) ($dependency['GROUP_ID'] ?? 0);

				if(!in_array($dependency['IF_FIELD_CODE'], $fieldCodeList))
				{
					continue;
				}
				if(!in_array($dependency['DO_FIELD_CODE'], $fieldCodeList))
				{
					continue;
				}

				$dependencyResult = new Main\Entity\Result;
				Internals\FieldDependenceTable::checkFields($dependencyResult, null, $dependency);
				$this->prepareResult('DEPENDENCIES', $dependencyResult);
			}

			foreach($agreements as $agreement)
			{
				$agreement['FORM_ID'] = (int) $this->id;
				$agreementResult = new Main\Entity\Result;
				Internals\AgreementTable::checkFields($agreementResult, null, $agreement);
				$this->prepareResult('AGREEMENTS', $agreementResult);
			}

			return;
		}

		if(!$this->check())
		{
			return;
		}

		if($this->id)
		{
			unset($result['ID']);
			$formResult = Internals\FormTable::update($this->id, $result);
			$isAdded = false;
		}
		else
		{
			$result['DATE_CREATE'] = new Main\Type\DateTime();
			$formResult = Internals\FormTable::add($result);
			$this->id = $formResult->getId();
			$isAdded = true;
		}

		if(!$formResult->isSuccess())
		{
			return;
		}

		/* RESPONSIBLE QUEUE */
		$assignedById = is_array($assignedById) ? $assignedById : array($assignedById);
		$responsibleQueue = new ResponsibleQueue($this->id);
		$responsibleQueue->setList($assignedById, $assignedWorkTime == 'Y');

		/* PRESET FIELDS */
		Internals\PresetFieldTable::delete(array('FORM_ID' => $this->id));
		foreach($presetFields as $presetField)
		{
			$presetFieldResult = Internals\PresetFieldTable::add(array(
				'ENTITY_NAME' => $presetField['ENTITY_NAME'],
				'FIELD_NAME' => $presetField['FIELD_NAME'],
				'VALUE' => $presetField['VALUE'],
				'FORM_ID' => $this->id
			));
			$this->prepareResult('PRESET_FIELDS', $presetFieldResult);
		}

		/*INTEGRATION*/
		$this->prepareResult('INTEGRATION',$this->getIntegration()->save());

		/* FIELDS */
		$existedFieldList = array();
		$existedFieldDb = Internals\FieldTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=FORM_ID' => $this->id)
		));
		while($existedField = $existedFieldDb->fetch())
		{
			$existedFieldList[] = $existedField['ID'];
		}

		$newFieldList = array();
		foreach($fields as $field)
		{
			$field['FORM_ID'] = $this->id;
			$fieldWithoutId = $field;
			unset($fieldWithoutId['ID']);
			if($field['ID'] > 0)
			{
				$fieldId = $field['ID'];
				$fieldResult = Internals\FieldTable::update($fieldId, $fieldWithoutId);
				$newFieldList[] = $fieldId;
			}
			else
			{
				$fieldResult = Internals\FieldTable::add($fieldWithoutId);
			}

			$this->prepareResult('FIELDS', $fieldResult);
		}

		$deleteFieldList = array_diff($existedFieldList, $newFieldList);
		foreach($deleteFieldList as $deleteFieldId)
		{
			Internals\FieldTable::delete($deleteFieldId);
		}

		/* DEPENDENCIES */
		$fieldCodeList = array();
		$fieldCodeDb = Internals\FieldTable::getList(array(
			'select' => array('CODE'),
			'filter' => array('=FORM_ID' => $this->id)
		));
		while($fieldCode = $fieldCodeDb->fetch())
		{
			$fieldCodeList[] = $fieldCode['CODE'];
		}

		$fieldDepGroupDb = Internals\FieldDepGroupTable::getList(['select' => ['ID'], 'filter' => ['=FORM_ID' => $this->id]]);
		while($fieldDepGroup = $fieldDepGroupDb->fetch())
		{
			Internals\FieldDepGroupTable::delete($fieldDepGroup['ID']);
		}

		$depGroupMap = [];
		foreach($depGroups as $depGroup)
		{
			$depGroupId = $depGroup['ID'];
			$depGroup['FORM_ID'] = $this->id;
			unset($depGroup['ID']);
			$depGroupResult = Internals\FieldDepGroupTable::add($depGroup);
			$this->prepareResult('DEP_GROUPS', $depGroupResult);
			$depGroupMap[$depGroupId] = $depGroupResult->getId();
		}
		foreach($dependencies as $depIndex => $dependency)
		{
			$dependency['GROUP_ID'] = !empty($dependency['GROUP_ID']) ? $dependency['GROUP_ID'] : 0;
			$dependency['FORM_ID'] = $this->id;
			if (isset($depGroupMap[$dependency['GROUP_ID']]))
			{
				$dependency['GROUP_ID'] = $depGroupMap[$dependency['GROUP_ID']];
			}
			elseif (count($depGroupMap) === 1)
			{
				$dependency['GROUP_ID'] = current($depGroupMap);
			}
			else
			{
				$dependency['GROUP_ID'] = 0;
			}
			$dependencies[$depIndex] = $dependency;
		}

		$fieldDepDb = Internals\FieldDependenceTable::getList(['select' => ['ID'], 'filter' => ['=FORM_ID' => $this->id]]);
		while($fieldDep = $fieldDepDb->fetch())
		{
			Internals\FieldDependenceTable::delete($fieldDep['ID']);
		}
		foreach($dependencies as $dependency)
		{
			if(!in_array($dependency['IF_FIELD_CODE'], $fieldCodeList))
			{
				continue;
			}
			if(!in_array($dependency['DO_FIELD_CODE'], $fieldCodeList))
			{
				continue;
			}

			$dependency['FORM_ID'] = $this->id;

			$dependencyResult = Internals\FieldDependenceTable::add($dependency);
			$this->prepareResult('DEPENDENCIES', $dependencyResult);
		}

		$agreementDb = Internals\AgreementTable::getList(['select' => ['ID'], 'filter' => ['=FORM_ID' => $this->id]]);
		while($agreement = $agreementDb->fetch())
		{
			Internals\AgreementTable::delete($agreement['ID']);
		}
		foreach($agreements as $agreement)
		{
			$agreement['FORM_ID'] = $this->id;
			$agreementResult = Internals\AgreementTable::add($agreement);
			$this->prepareResult('AGREEMENTS', $agreementResult);
		}

		$this->buildScript();
		if ($isAdded && (int)$this->params['TYPE_ID'] === Internals\FormTable::TYPE_DEFAULT)
		{
			LandingTable::createLanding($this->id, $result['NAME']);
		}
	}

	public function buildScript()
	{
		$resourceBooking = Webpack\Form\ResourceBooking::instance();
		if (Main\ModuleManager::isModuleInstalled('calendar') && !$resourceBooking->isBuilt())
		{
			$resourceBooking->build();
		}

		$app = Webpack\Form\App::instance();
		if ($this->forceBuild || !$app->isBuilt(new Main\Type\Date()))
		{
			if ($app->build())
			{
				Webpack\Form::addCheckResourcesAgent();
			}
		}

		$result = Webpack\Form::instance($this->id)->build();
		if (Manager::isEmbeddingAvailable() && $this->isEmbeddingAvailable()
			&& ($this->isEmbeddingEnabled() || $this->isEmbeddedAvailableChanged))
		{
			SiteButton\Manager::updateScriptCacheWithForm($this->getId());
		}

		$this->forceBuild = false;
		self::cleanCacheByTag($this->id);
		return $result;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function hasErrors()
	{
		return count($this->errors) > 0;
	}

	/**
	 * Set design options.
	 *
	 * @param bool $mode Mode.
	 * @return $this
	 */
	public function setEmbeddingEnabled($mode)
	{
		$old = $this->isEmbeddingEnabled();
		$this->params['FORM_SETTINGS']['EMBEDDING_ENABLED'] = $mode ? 'Y' : 'N';
		$new = $this->isEmbeddingEnabled();
		if ($old !== $new)
		{
			$this->isEmbeddedAvailableChanged = true;
		}

		return $this;
	}

	/**
	 * Set design options.
	 *
	 * @param array $options Options.
	 * @return $this
	 */
	public function setDesignOptions(array $options = [])
	{
		$designOptions = (new Design($options))->getOptions();
		unset($designOptions['backgroundImage']);
		$this->params['FORM_SETTINGS']['DESIGN'] = $designOptions;

		$this->params['BUTTON_COLOR_BG'] = $options['color']['primary']
			?: $this->params['BUTTON_COLOR_BG'];

		$this->params['BUTTON_COLOR_FONT'] = $options['color']['primaryText']
			?: $this->params['BUTTON_COLOR_FONT'];

		return $this;
	}

	/**
	 * Get design options.
	 *
	 * @param bool $asTyped Return as typed.
	 * @return array
	 */
	public function getDesignOptions($asTyped = false)
	{
		$options = $this->params['FORM_SETTINGS']['DESIGN'];
		$options = new Design($options);
		$options = $asTyped ? $options->toTypedArray() : $options->getOptions();
		$options['color']['primary'] = $this->params['BUTTON_COLOR_BG']
			?: $options['color']['primary'];
		$options['color']['primaryText'] = $this->params['BUTTON_COLOR_FONT']
			?: $options['color']['primaryText'];

		$options['backgroundImage'] = '';
		$bgImageId = $this->params['BACKGROUND_IMAGE'] ?? 0;
		if ($bgImageId)
		{
			$bgImagePath = \CFile::getFileArray($bgImageId)['SRC'] ?? '';
			if ($bgImagePath)
			{
				if (!preg_match('#^(https?://)#', $bgImagePath))
				{
					$bgImagePath = Main\Web\WebPacker\Builder::getDefaultSiteUri() . $bgImagePath;
				}

				$options['backgroundImage'] = $bgImagePath;
			}
		}

		return $options;
	}

	protected function prepareResult($sect, Main\Result $entityResult, $replaceList = null)
	{
		if($entityResult->isSuccess())
		{
			return;
		}

		$errors = $entityResult->getErrors();
		foreach($errors as $error)
		{
			$errorMessage = $error->getMessage();
			if($replaceList)
			{
				$errorMessage = str_replace(array_keys($replaceList), array_values($replaceList), $errorMessage);
			}

			switch ($sect)
			{
				case 'PRESET_FIELDS':
					$errorMessage = Loc::getMessage('CRM_WEBFORM_FORM_PRESET_FIELDS') . ": " . $errorMessage;
					break;
			}

			$this->errors[] = $errorMessage;
		}
	}

	public function check()
	{
		$this->save(true);

		return count($this->errors) === 0;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getButtonCaption()
	{
		return $this->params['BUTTON_CAPTION'] ? $this->params['BUTTON_CAPTION'] : Loc::getMessage('CRM_WEBFORM_FORM_BUTTON_CAPTION_DEFAULT');
	}

	public function getLandingUrl()
	{
		return Internals\LandingTable::getLandingPublicUrl($this->getId());
	}

	public function getAgreementUrl()
	{
		return Script::getAgreementUrl($this->get());
	}

	public function getSuccessPageUrl()
	{
		return $this->params['RESULT_SUCCESS_URL'] ?: Script::getSuccessPageUrl($this->get());
	}

	public function getFields()
	{
		return is_array($this->params['FIELDS']) ? $this->params['FIELDS'] : [];
	}

	public function getFieldsByType(string $type): array
	{
		return array_filter(
			$this->getFields(),
			function ($value) use ($type) {
				return $value['TYPE'] === $type;
			}
		);
	}

	public function hasField(string $code): bool
	{
		 return !empty(
			 array_filter(
				$this->getFields(),
				function ($value) use ($code) {
					return $value['CODE'] === $code;
				}
			 )
		);
	}

	public function getAllowedEntitySchemes()
	{
		//TODO: fields checker
		return array_keys(Entity::getSchemes());
		//return array(Helper::ENUM_ENTITY_SCHEME_CONTACT);
	}

	public function getPresetFields()
	{
		return $this->params['PRESET_FIELDS'];
	}

	public function getDependencies($opposites = true)
	{
		$dependencyList = array();
		foreach($this->params['DEPENDENCIES'] as $dependency)
		{
			$dependencyList[$dependency['DO_FIELD_CODE']][] = array(
				'if' => array(
					'fieldname' => $dependency['IF_FIELD_CODE'],
					'action' => $dependency['IF_ACTION'],
					'value' => $dependency['IF_VALUE'],
				),
				'do' => array(
					'action' => $dependency['DO_ACTION'],
					'value' => $dependency['DO_VALUE'],
				),
			);

			if (!$opposites)
			{
				continue;
			}

			// add mirror dependency
			if($dependency['IF_ACTION'] != 'change')
			{
				continue;
			}

			if(!in_array($dependency['DO_ACTION'], array('show', 'hide')))
			{
				continue;
			}

			$mirror = $dependency['DO_ACTION'] == 'hide' ? 'show' : 'hide';
			$dependencyList[$dependency['DO_FIELD_CODE']][] = array(
				'if' => array(
					'fieldname' => $dependency['IF_FIELD_CODE'],
					'action' => $dependency['IF_ACTION'],
					'value' => $dependency['IF_VALUE'],
					'operation' => '!='
				),
				'do' => array(
					'action' => $mirror,
				),
			);
		}

		return $dependencyList;
	}

	public function getFieldsDescription()
	{
		return EntityFieldProvider::getFieldsDescription($this->getFields(), $this->get()['FORM_SETTINGS']['REQUISITE_PRESET_ID'] ?? null);
	}

	public function getFieldsMap()
	{
		$fields = $this->getFieldsDescription();
		$dependencyList = $this->getDependencies();
		$currencyId = $this->getCurrencyId();

		$fieldList = array();
		foreach($fields as $field)
		{
			$preparedField = [
				'id' => $field['ID'],
				'type' => $field['TYPE'],
				'name' => $field['CODE'],
			];
			if($field['TYPE'] == 'section')
			{
				$preparedField += array(
					'caption' => $field['CAPTION'],
				);
			}
			else
			{
				$preparedField += array(
					'type_original' => $field['TYPE_ORIGINAL'],
					'entity_name' => $field['ENTITY_NAME'],
					'entity_field_name' => $field['ENTITY_FIELD_NAME'],
					'caption' => $field['CAPTION'] ? $field['CAPTION'] : $field['ENTITY_FIELD_CAPTION'],
					'required' => $field['REQUIRED'] == 'Y' ? true : false,
					'autocomplete' => $field['SETTINGS_DATA']['AUTOCOMPLETE'] == 'Y' ? true : false,
					'multiple' => $field['MULTIPLE'] == 'Y' ? true : false,
					'multiple_original' => $field['MULTIPLE_ORIGINAL'],
					'hidden' => false,
					'placeholder' => $field['PLACEHOLDER'],
					'value' => $field['VALUE'],
					'value_type' => $field['VALUE_TYPE'],
					'settings_data' => $field['SETTINGS_DATA']
				);

				if(isset($field['ITEMS']) && is_array($field['ITEMS']))
				{
					$preparedField['items'] = array();
					foreach($field['ITEMS'] as $item)
					{
						$price = isset($item['PRICE']) ? $item['PRICE'] : null;
						if ($price !== null && !is_numeric($price))
						{
							$price = 0;
						}
						$discount = isset($item['DISCOUNT']) ? $item['DISCOUNT'] : null;
						if ($discount !== null && !is_numeric($discount))
						{
							$discount = 0;
						}

						if (empty($item['ID']))
						{
							continue;
						}

						$preparedItem = array(
							'title' => $item['VALUE'],
							'value' => $item['ID'],
						);
						if ($price !== null)
						{
							$preparedItem['price'] = $price;
							$preparedItem['changeablePrice'] = isset($item['CUSTOM_PRICE'])
								&& $item['CUSTOM_PRICE'] === 'Y'
								&& Manager::isOrdersAvailable();
							$preparedItem['discount'] = $discount ?: 0;
							$preparedItem['price_formatted'] = \CCrmCurrency::MoneyToString($price, $currencyId);
						}
						$preparedField['items'][] = $preparedItem;
					}
				}
			}

			if(isset($dependencyList[$field['CODE']]))
			{
				$preparedField['dependences'] = $dependencyList[$field['CODE']];
				$preparedField['hidden'] = true;
			}

			$fieldList[] = $preparedField;
		}

		return $fieldList;
	}

	public function getExternalAnalyticsData()
	{
		$data = Helper::getExternalAnalyticsData($this->params['CAPTION'] ?: '#' . $this->getId(), $this->getId());
		$steps = array();

		$steps[] = array(
			'NAME' => $data['view']['name'],
			'CODE' => $data['view']['code']
		);
		$steps[] = array(
			'NAME' => $data['start']['name'],
			'CODE' => $data['start']['code']
		);
		foreach($this->getFieldsMap() as $field)
		{
			if(Internals\FieldTable::isUiFieldType($field['type']))
			{
				continue;
			}

			$steps[] = array(
				'NAME' => str_replace('%name%', $field['caption'], $data['field']['name']),
				'CODE' => str_replace('%code%', $field['name'], $data['field']['code']),
			);
		}
		$steps[] = array(
			'NAME' => $data['end']['name'],
			'CODE' => $data['end']['code']
		);

		foreach($steps as $stepIndex => $step)
		{
			$step['NAME'] = str_replace('%name%', $step['NAME'], $data['template']['name']);
			$step['EVENT'] = str_replace(array('%code%', '%form_id%'), array($step['CODE'], (int) $this->getId()), $data['eventTemplate']['code']);
			$step['CODE'] = str_replace('%code%', $step['CODE'], $data['template']['code']);
			$steps[$stepIndex] = $step;
		}

		return array(
			'CATEGORY' => $data['category'],
			'STEPS' => $steps
		);
	}

	public function getCurrencyId()
	{
		return empty($this->params['CURRENCY_ID'])
			? \CCrmCurrency::GetBaseCurrencyID()
			: $this->params['CURRENCY_ID'];
	}

	/**
	 * Return true if form payable.
	 *
	 * @return bool
	 */
	public function isPayable()
	{
		return isset($this->params['IS_PAY']) && $this->params['IS_PAY'] === 'Y';
	}

	/**
	 * Get redirect delay.
	 *
	 * @return int
	 */
	public function getRedirectDelay()
	{
		return (int) ($this->params['FORM_SETTINGS']['REDIRECT_DELAY'] ?? Form::REDIRECT_DELAY);
	}

	/**
	 * Get refill options.
	 *
	 * @return array
	 */
	public function getRefill()
	{
		return ($this->params['FORM_SETTINGS']['REFILL'] ?? ['ACTIVE' => 'N', 'CAPTION' => '']);
	}

	/**
	 * Get success text.
	 *
	 * @return string
	 */
	public function getSuccessText()
	{
		return $this->params['RESULT_SUCCESS_TEXT'];
	}

	/**
	 * Get failure text.
	 *
	 * @return string
	 */
	public function getFailureText()
	{
		return $this->params['RESULT_FAILURE_TEXT'];
	}

	public function getLanguageId()
	{
		return ($this->params['LANGUAGE_ID'] ? $this->params['LANGUAGE_ID'] : Context::getCurrent()->getLanguage());
	}

	public static function copy($formId, $userId = null)
	{
		// copy form
		$form = Internals\FormTable::getRowById($formId);
		if(!$form)
		{
			return null;
		}

		unset($form['ID'], $form['DATE_CREATE'], $form['ACTIVE_CHANGE_DATE'], $form['SECURITY_CODE'], $form['XML_ID']);
		$form['NAME'] = Loc::getMessage('CRM_WEBFORM_FORM_COPY_NAME_PREFIX') . ' ' . $form['NAME'];
		$form['ACTIVE'] = 'N';
		$form['IS_SYSTEM'] = 'N';
		$form['ACTIVE_CHANGE_BY'] = $userId;
		$form['DATE_CREATE'] = new Main\Type\DateTime();
		$resultFormAdd = Internals\FormTable::add($form);
		if(!$resultFormAdd->isSuccess())
		{
			return null;
		}
		$newFormId = $resultFormAdd->getId();

		// copy fields
		$fieldDb = Internals\FieldTable::getList(array(
			'filter' => array('=FORM_ID' => $formId)
		));
		while($field = $fieldDb->fetch())
		{
			unset($field['ID']);
			$field['FORM_ID'] = $newFormId;
			Internals\FieldTable::add($field);
		}

		// copy field dependencies
		$fieldDepGroupMap = [];
		$fieldDepGroups = Internals\FieldDepGroupTable::getList(array(
			'filter' => array('=FORM_ID' => $formId)
		));
		foreach ($fieldDepGroups as $fieldDepGroup)
		{
			$fieldDepGroupId = $fieldDepGroup['ID'];
			unset($fieldDepGroup['ID']);
			$fieldDepGroup['FORM_ID'] = $newFormId;
			$fieldDepGroupMap[$fieldDepGroupId] = Internals\FieldDepGroupTable::add($fieldDepGroup)->getId() ?: 0;
		}

		$fieldDeps = Internals\FieldDependenceTable::getList(array(
			'filter' => array('=FORM_ID' => $formId)
		));
		while($fieldDep = $fieldDeps->fetch())
		{
			unset($fieldDep['ID']);
			$fieldDep['FORM_ID'] = $newFormId;
			$fieldDep['GROUP_ID'] = $fieldDepGroupMap[$fieldDep['GROUP_ID']] ?? 0;
			Internals\FieldDependenceTable::add($fieldDep);
		}

		// copy preset fields
		$presetFieldDb = Internals\PresetFieldTable::getList(array(
			'filter' => array('=FORM_ID' => $formId)
		));
		while($presetField = $presetFieldDb->fetch())
		{
			$presetField['FORM_ID'] = $newFormId;
			Internals\PresetFieldTable::add($presetField);
		}

		// copy agreements
		$agreements = Internals\AgreementTable::getList(array(
			'filter' => array('=FORM_ID' => $formId)
		));
		while($agreement = $agreements->fetch())
		{
			unset($agreement['ID']);
			$agreement['FORM_ID'] = $newFormId;
			Internals\AgreementTable::add($agreement);
		}

		// copy assigned by
		$queue = Internals\QueueTable::getList(array(
			'filter' => array('=FORM_ID' => $formId)
		));
		while($queueRow = $queue->fetch())
		{
			unset($queueRow['ID']);
			$queueRow['FORM_ID'] = $newFormId;
			Internals\QueueTable::add($queueRow);
		}


		return $newFormId;
	}

	public static function activate($formId, $isActivate = true, $changeUserBy = null)
	{
		$updateFields = array('ACTIVE' => $isActivate ? 'Y' : 'N');
		if($changeUserBy)
		{
			$updateFields['ACTIVE_CHANGE_BY'] = $changeUserBy;
		}
		$updateResult = Internals\FormTable::update($formId, $updateFields);
		if($updateResult->isSuccess())
		{
			(new static($formId))->buildScript();
			return true;
		}
		else
		{
			return false;
		}
	}

	/*
	 * Return true if has result.
	 *
	 * @param string $originId Origin ID.
	 * @return bool
	 * */
	public function hasResult($originId)
	{
		$webFormResult = Internals\ResultTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=FORM_ID' => $this->getId(),
				'=ORIGIN_ID' => $originId,
			),
			'limit' => 1
		));

		return $webFormResult->getSelectedRowsCount() > 0;
	}

	/*
	 * Fill.
	 *
	 * @return Fill
	 * */
	public function fill()
	{
		return new Fill($this);
	}

	/*
	 * Add result.
	 *
	 * @param array $resultFields Result fields.
	 * @param array $resultParameters Result parameters.
	 * @return Result
	 * */
	public function addResult($resultFields, $resultParameters = array())
	{
		$this->errors = array();

		// get section fields
		$sectionFields = array();
		$currentSection = '';
		$fieldsMap = $this->getFieldsMap();
		foreach($fieldsMap as $field)
		{
			if($field['type'] == 'section')
			{
				$currentSection = $field['name'];
				continue;
			}

			if(!$currentSection)
			{
				continue;
			}

			$sectionFields[$currentSection][] = $field['name'];
		}

		// format fields by name
		$fields = array();
		foreach($resultFields as $fieldKey => $field)
		{
			$fields[$field['name']] = $field;
		}

		$hiddenFieldNames = array();
		// set hidden flag
		foreach($resultFields as $fieldKey => $field)
		{
			if(!isset($field['dependences']))
			{
				$field['hidden'] = false;
				$data['FIELDS'][$fieldKey] = $field;
				continue;
			}

			$isHidden = false;
			foreach($field['dependences'] as $dep)
			{
				if($dep['if']['action'] != 'change')
				{
					continue;
				}

				if(!isset($fields[$dep['if']['fieldname']]))
				{
					continue;
				}

				$valuesA = $fields[$dep['if']['fieldname']]['values'];
				$valueB = $dep['if']['value'];
				switch($dep['if']['operation'])
				{
					case '!=':
						$isSuccess = !in_array($valueB, $valuesA);
						break;

					default:
						$isSuccess = in_array($valueB, $valuesA);
						break;
				}

				if(!$isSuccess)
				{
					continue;
				}

				$isHidden = $dep['do']['action'] == 'hide';
			}

			if($field['type'] == 'section')
			{
				foreach($sectionFields[$field['name']] as $sectionFieldName)
				{
					$hiddenFieldNames[$sectionFieldName][] = $isHidden;
				}
			}
			else
			{
				$field['hidden'] = $isHidden;
				$resultFields[$fieldKey] = $field;
			}
		}

		$fieldEmailValue = null;
		$fieldPhoneValue = null;
		$fieldPhoneEntityTypeName = null;
		foreach($resultFields as $fieldKey => $field)
		{
			if(($field['entity_field_name'] == 'EMAIL' || $field['type'] == 'email') && $field['values'][0])
			{
				$fieldEmailValue = $field['values'][0];
			}
			if(($field['entity_field_name'] == 'PHONE' || $field['type'] == 'phone') && $field['values'][0])
			{
				$fieldPhoneEntityTypeName = $field['entity_name'];
				$fieldPhoneValue = $field['values'][0];
			}

			if(!isset($hiddenFieldNames[$field['name']]))
			{
				continue;
			}

			$field['hidden'] = end($hiddenFieldNames[$field['name']]);
			$resultFields[$fieldKey] = $field;
		}

		$resultProducts = array();
		$activityFields = array();
		foreach($resultFields as $fieldKey => $field)
		{

			if(!isset($field['values'][0]) || (!$field['values'][0] && $field['values'][0] !== '0'))
			{
				continue;
			}

			$activityFieldValues = array();
			if(is_array($field['items']) && count($field['items']) > 0)
			{
				if (!empty($field['values'][0]['id']))
				{
					$fieldValues = array_column($field['values'], 'id');
				}
				elseif (!empty($field['values'][0]['value']))
				{
					$fieldValues = array_column($field['values'], 'value');
				}
				else
				{
					$fieldValues = $field['values'];
				}

				foreach($field['items'] as $item)
				{
					if(!in_array($item['value'], $fieldValues))
					{
						continue;
					}

					$activityFieldValues[] = $item;
				}
			}
			else
			{
				$activityFieldValues = $field['values'];
			}

			if ($field['type'] === Internals\FieldTable::TYPE_ENUM_DATETIME)
			{
				$activityFieldValues = array_map(
					static function ($fieldValue) {
						$dateTimeWithAtomFormat = DateTime::tryParse($fieldValue, DATE_ATOM);
						return ($dateTimeWithAtomFormat ?? DateTime::createFromUserTime($fieldValue))->getTimestamp();
					},
					$activityFieldValues
				);
			}

			$activityFields[] = array(
				'type' => $field['type'],
				'code' => $field['name'],
				'required' => $field['required'],
				'caption' => $field['caption'],
				'value' => $activityFieldValues,
			);


			if($field['type'] != 'product')
			{
				continue;
			}

			$productValues = [];
			foreach ($field['values'] as $value)
			{
				$productValue = [
					'id' => 0,
					'quantity' => 1
				];
				if (is_array($value))
				{
					$productValue['id'] = $value['id'] ?: 0;
					$productValue['quantity'] = $value['quantity'] ?: 1;
					$productValue['price'] = $value['price'] ?: null;
				}
				else
				{
					$productValue['id'] = $value;
				}

				$productValues[$productValue['id']] = $productValue;
			}

			foreach($field['items'] as $item)
			{
				$productValue = $productValues[$item['value']];
				if(!is_array($productValue))
				{
					continue;
				}

				$productId = is_numeric($item['value']) ? $item['value'] : 0;
				$product = [
					'ID' => $productId,
					'NAME' => $item['title'],
					'PRICE' => $item['changeablePrice'] && $productValue['price']
						? $productValue['price']
						: $item['price'],
					'DISCOUNT' => $item['discount'] ?: 0,
					'QUANTITY' => $productValue['quantity'],
				];
				if ($productId && ($productData = \CCrmProduct::GetByID($productId)))
				{
					$product['TYPE'] = $productData['TYPE'];
					$product['VAT_INCLUDED'] = $productData['VAT_INCLUDED'];
					if ($productData['VAT_ID'])
					{
						$vatData = \CCrmVat::GetByID($productData['VAT_ID']);
						if ($vatData && $vatData['RATE'])
						{
							$product['VAT_RATE'] = floatval($vatData['RATE']) / 100;
						}
					}
				}
				$resultProducts[] = $product;
			}
		}

		// set responsible
		$responsibleQueue = new ResponsibleQueue($this->id);
		$responsibleId = $responsibleQueue->getNextId();
		$this->params['ASSIGNED_BY_ID'] = $responsibleId ? $responsibleId : 1;

		// add Result
		$data = array(
			'FORM' => $this->params,
			'DUPLICATE_MODE' => $this->params['DUPLICATE_MODE'],
			'PRESET_FIELDS' => $this->params['PRESET_FIELDS'],
			'COMMON_FIELDS' => isset($resultParameters['COMMON_FIELDS']) ? $resultParameters['COMMON_FIELDS'] : array(),
			'AGREEMENTS' => $resultParameters['AGREEMENTS'] ?? [],
			'COMMON_DATA' => $resultParameters['COMMON_DATA'],
			'PLACEHOLDERS' => isset($resultParameters['PLACEHOLDERS']) ? $resultParameters['PLACEHOLDERS'] : array(),
			'DISABLE_FIELD_CHECKING' => $resultParameters['DISABLE_FIELD_CHECKING'] ?? false,
			'ORIGIN_ID' => isset($resultParameters['ORIGIN_ID']) ? $resultParameters['ORIGIN_ID'] : null,
			'ENTITY_SCHEME' => $this->params['ENTITY_SCHEME'],
			'INVOICE_SETTINGS' => $this->params['INVOICE_SETTINGS'],
			'FORM_ID' => $this->id,
			'FIELDS' => $resultFields,
			'ASSIGNED_BY_ID' => $this->params['ASSIGNED_BY_ID'],
			'PRODUCTS' => $resultProducts,
			'CURRENCY_ID' => $this->getCurrencyId(),
			'ACTIVITY_FIELDS' => $activityFields,
			'IS_CALLBACK' => $this->isCallback(),
			'CALLBACK_PHONE' => $fieldPhoneValue,
			'ENTITIES' => isset($resultParameters['ENTITIES']) ? $resultParameters['ENTITIES'] : [],
		);
		$result = new Result(null, $data);
		$result->save();
		if($result->hasErrors())
		{
			$this->errors = $result->getErrors();
		}
		else
		{
			if($fieldEmailValue)
			{
				self::sendEventFormSent(array(
						'RESULT_SUCCESS_TEXT' => $this->params['RESULT_SUCCESS_TEXT'],
						'RESULT_SUCCESS_URL' => $this->params['RESULT_SUCCESS_URL'],
						'RESULT_FAILURE_TEXT' => $this->params['RESULT_FAILURE_TEXT'],
						'RESULT_FAILURE_URL' => $this->params['RESULT_FAILURE_URL'],
						'FORM_CAPTION' => $this->params['CAPTION'],
						'EMAIL_TO' => $fieldEmailValue,
					)
				);
			}

			$stopCallBack = false;
			if (isset($resultParameters['STOP_CALLBACK']) && $resultParameters['STOP_CALLBACK'])
			{
				$stopCallBack = true;
			}

			if($fieldPhoneValue && $this->isCallback())
			{
				if(Callback::hasPhoneNumbers())
				{
					Callback::sendCallEvent(array(
						'CRM_ENTITY_TYPE' => $fieldPhoneEntityTypeName,
						'CRM_ENTITY_ID' => $result->getResultEntity()->getEntityIdByTypeName($fieldPhoneEntityTypeName),
						'CALL_FROM' => $this->params['CALL_FROM'],
						'CALL_TO' => $fieldPhoneValue,
						'TEXT' => $this->params['CALL_TEXT'],
						'STOP_CALLBACK' => $stopCallBack,
						'CRM_ENTITY_LIST' => $result->getResultEntity()->getResultEntities()
					));
				}
			}
			if ($this->isWhatsApp())
			{
				WhatsApp::sendEvent(array(
					'FORM_ID' => $this->id,
					'LANG_ID' => $this->getLanguageId(),
					'CRM_ENTITY_TYPE' => $fieldPhoneEntityTypeName,
					'CRM_ENTITY_ID' => $result->getResultEntity()->getEntityIdByTypeName($fieldPhoneEntityTypeName),
					'PHONE_NUMBER' => $fieldPhoneValue,
					'CRM_ENTITY_LIST' => $result->getResultEntity()->getResultEntities()
				));
			}

			$redirectUrl = $this->params['RESULT_SUCCESS_URL'];
			if($this->isPayable())
			{
				$resultEntity = $result->getResultEntity();
				if ($resultEntity)
				{
					$resultRedirectUrl = null;
					if ($resultEntity->getOrderId())
					{
						$excludedPaySystems = $this->params['FORM_SETTINGS']['DISABLED_PAY_SYSTEMS'] ?? [];
						$urlInfo = SalesCenter\Integration\LandingManager::getInstance()->getUrlInfoByOrderId(
							$resultEntity->getOrderId(),
							[
								'paymentId' => $resultEntity->getPaymentId() ?: 0,
								'excludedPS' => $excludedPaySystems
							]
						);
						$resultRedirectUrl = $urlInfo['url'] ?? null;
					}
					elseif($resultEntity->getInvoiceId())
					{
						$resultRedirectUrl = \CAllCrmInvoice::getPublicLink($resultEntity->getInvoiceId());
					}

					if($resultRedirectUrl)
					{
						$redirectUrl = new \Bitrix\Main\Web\Uri($resultRedirectUrl);
						$redirectUrl->addParams(array('form_id' => $this->getId()));
						$redirectUrl = $redirectUrl->getLocator();
					}
				}
			}
			$result->setUrl($redirectUrl);
		}

		return $result;
	}

	protected function sendEventFormSent($fields)
	{
		Main\Mail\Event::send(array(
				'EVENT_NAME' => 'CRM_WEB_FORM_FILLED',
				'C_FIELDS' => $fields,
				'LID' => Main\Context::getCurrent()->getSite()
		));

		Main\Mail\Event::send(array(
				'EVENT_NAME' => 'CRM_WEB_FORM_FILLED_' . $this->getId(),
				'C_FIELDS' => $fields,
				'LID' => Main\Context::getCurrent()->getSite()
		));
	}

	public function getScripts($publicFormPath)
	{
		$script = new Script(
			Main\Context::getCurrent()->getServer()->getHttpHost(),
			Main\Context::getCurrent()->getRequest()->isHttps()
		);

		$scriptParams = array(
			'id' => $this->params['ID'],
			'lang' => $this->getLanguageId(),
			'sec' => $this->params['SECURITY_CODE']
		);

		return array(
			'INLINE' => $script->getInline($scriptParams),
			'BUTTON' => $script->getButton($scriptParams + array('button_caption' => Loc::getMessage('CRM_WEBFORM_FORM_SCRIPT_BUTTON_TEXT'))),
			'LINK' => $script->getLink($scriptParams + array('button_caption' => Loc::getMessage('CRM_WEBFORM_FORM_SCRIPT_BUTTON_TEXT'))),
			'DELAY' => $script->getDelay($scriptParams + array('delay' => 5))
		);
	}

	public function sendScriptsEmail($email)
	{

	}

	public function isEmbeddingEnabled()
	{
		return true;
	}

	public function isEmbeddingAvailable()
	{
		return true;
	}

	public static function getCacheTag($formId)
	{
		return 'BX_CRM_WEBFORM_ID_' . $formId;
	}

	public static function cleanCacheByTag($formId)
	{
		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$taggedCache = Main\Application::getInstance()->getTaggedCache();
			$taggedCache->clearByTag(static::getCacheTag($formId));
		}
	}

	public static function getCounters($formId, $schemeId = null)
	{
		$result = array(
			'ENTITY' => array(),
			'COMMON' => array()
		);
		$entityList = Entity::getList();
		$scheme = Entity::getSchemes($schemeId);
		if($scheme)
		{
			foreach($entityList as $entityName => $entityCaption)
			{
				if(!in_array($entityName, $scheme['ENTITIES']))
				{
					unset($entityList[$entityName]);
				}
			}
		}

		$entityNameMap = [
			\CCrmOwnerType::InvoiceName => \CCrmOwnerType::OrderName
		];

		$entityFieldMap = Internals\FormCounterTable::getEntityFieldsMap();
		$counters = Internals\FormCounterTable::getByFormId($formId);
		foreach($counters as $counter => $value)
		{
			if(isset($entityFieldMap[$counter]))
			{
				$entityName = $entityFieldMap[$counter];

				$isDynamic = $entityName === 'DYNAMIC';
				if ($isDynamic && ($scheme['DYNAMIC'] ?? false))
				{
					$entityName = \CCrmOwnerType::resolveName($scheme['MAIN_ENTITY']) ?: $entityName;
				}

				if(!isset($entityList[$entityName]))
				{
					continue;
				}

				$entityCaption = $entityList[$entityName];
				$entityTypeId = \CCrmOwnerType::resolveID($entityName);
				$entityName = $entityNameMap[$entityName] ?? $entityName;

				$link = $isDynamic
					? "/crm/type/{$entityTypeId}/list/category/0/"
					: Option::get(
						'crm',
						'path_to_'.mb_strtolower($entityNameMap[$entityName] ?? $entityName) . '_list',
						''
					)
				;

				$link .= mb_strpos($link, '?') === false ? '?' : '&';
				$link .= 'WEBFORM_ID[]=' . $formId . '&apply_filter=Y';
				if (!$link || $entityName === \CCrmOwnerType::OrderName)
				{
					$link = null;
				}

				$result['ENTITY'][] = array(
					'ENTITY_NAME' => $entityName,
					'ENTITY_CAPTION' => $entityCaption,
					'VALUE' => $value,
					'LINK' => $link,
				);
			}
			else
			{
				$result['COMMON'][$counter] = $value;
			}
		}

		return $result;
	}

	public static function incCounterView($formId)
	{
		Main\Application::getInstance()->addBackgroundJob(function() use ($formId) {
			Internals\FormCounterDailyTable::incrementViews(new Main\Type\Date(), (int)$formId);
			Internals\FormCounterTable::incCounters($formId, array('VIEWS'));
		});
		return true;
	}

	public static function incCounterStartFill($formId)
	{
		Internals\FormStartEditTable::add(array('FORM_ID' => $formId));
		Internals\FormCounterDailyTable::incrementStartFill(new Main\Type\Date(), (int)$formId);
		return Internals\FormCounterTable::incCounters($formId, array('START_FILL'));
	}

	public static function incCounterEndFill($formId)
	{
		Internals\FormCounterDailyTable::incrementEndFill(new Main\Type\Date(), (int)$formId);
		return Internals\FormCounterTable::incCounters($formId, array('END_FILL'));
	}

	public static function resetCounters($formId)
	{
		Internals\FormCounterDailyTable::resetCounters(new Main\Type\Date(), (int)$formId);
		$newCounterId = Internals\FormCounterTable::addByFormId($formId);
		// TODO: merge all counters
		return $newCounterId;
	}

	public static function canRemoveCopyright()
	{
		if(!Main\Loader::includeModule('bitrix24'))
		{
			return true;
		}

		return \CBitrix24::IsLicensePaid();
	}

	public static function getMaxActivatedFormLimit()
	{
		return intval(Option::get('crm', '~crm_webform_max_activated', 99999));
	}

	public static function canActivateForm()
	{
		if(!Main\Loader::includeModule("bitrix24"))
		{
			return true;
		}

		$maxActivated = self::getMaxActivatedFormLimit();
		return $maxActivated > Internals\FormTable::getCount(array('=ACTIVE' => 'Y', '=IS_SYSTEM' => 'N'));
	}

	public static function actualizeFormsActiveState($maxActivated = null)
	{
		if(!$maxActivated)
		{
			$maxActivated = self::getMaxActivatedFormLimit();
		}

		$formDb = Internals\FormTable::getDefaultTypeList(array(
			'select' => array('ID'),
			'filter' => array('=ACTIVE' => 'Y', '=IS_SYSTEM' => 'N'),
			'order' => array('ID' => 'ASC')
		));
		while($form = $formDb->fetch())
		{
			if($maxActivated > 0)
			{
				--$maxActivated;
				continue;
			}

			static::activate($form['ID'], false);
		}

		if(!self::canRemoveCopyright())
		{
			$connection = Main\Application::getConnection();
			$connection->query("UPDATE b_crm_webform SET COPYRIGHT_REMOVED='N'");
		}
	}

	public static function onAfterSetOptionCrmWebFormMaxActivated(\Bitrix\Main\Event $event)
	{
		self::actualizeFormsActiveState();
	}

	public static function onBitrix24LicenseChange(\Bitrix\Main\Event $event)
	{
		preg_match("/(project|tf|team)$/is", $event->getParameter(0), $matches);
		$licenseType = mb_strtolower($matches[0]);
		if ($licenseType)
		{
			$maxActivated = null;
			switch($licenseType)
			{
				case 'project':
					$maxActivated = 1;
					break;
				case 'tf':
					$maxActivated = 2;
					break;
				case 'team':
					$maxActivated = 4;
					break;
				case 'demo':
					$maxActivated = 9999;
					break;
			}

			self::actualizeFormsActiveState($maxActivated);
		}
	}
}
