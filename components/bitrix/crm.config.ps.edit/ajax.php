<?
use \Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem;

define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$arReturn = array();

Loc::loadMessages(__FILE__);

if (!CModule::IncludeModule('crm'))
	$arReturn['ERROR'][] = Loc::getMessage('CRM_PS_MODULE_NOT_INSTALLED');

global $APPLICATION, $USER;

if(!isset($arReturn['ERROR']))
{
	$CrmPerms = new CCrmPerms($USER->GetID());
	$bCrmWritePerm = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

	if($USER->IsAuthorized() && check_bitrix_sessid() && $bCrmWritePerm)
	{
		$ID = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
		$personTypeId = isset($_REQUEST['person_type']) ? $_REQUEST['person_type'] : 0;
		$paySystemId = isset($_REQUEST['pay_system_id']) ? $_REQUEST['pay_system_id'] : 0;
		$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';
		$psMode = isset($_REQUEST['ps_mode']) ? trim($_REQUEST['ps_mode']) : null;

		switch ($action)
		{
			case 'get_fields':

				$arReturn['FIELDS'] = CCrmPaySystem::getPSCorrespondence($ID, $psMode);

				$path = \Bitrix\Sale\PaySystem\Manager::getPathToHandlerFolder($ID);
				if (\Bitrix\Main\IO\File::isFileExists(\Bitrix\Main\Application::getDocumentRoot().$path.'/.description.php'))
				{
					require \Bitrix\Main\Application::getDocumentRoot().$path.'/.description.php';
					if (isset($data))
						$arReturn["NAME"] = $data['NAME'];
					elseif (isset($psTitle))
						$arReturn["NAME"] = $psTitle;
				}

				foreach ($arReturn['FIELDS'] as $code => $data)
				{
					if ($data['TYPE'] == 'SELECT')
					{
						if (array_key_exists('VALUE', $data) && is_array($data['VALUE']))
						{
							foreach ($data['VALUE'] as $key => $option)
							{
								if (is_array($option) && array_key_exists('NAME', $option))
								{
									$arReturn['FIELDS'][$code]['VALUE'][$key] = $option['NAME'];
								}
							}
						}
					}

					$map = \Bitrix\Sale\BusinessValue::getMapping($code);
					if ($map === null)
						continue;

					if ($map['PROVIDER_VALUE'] != '' && $map['PROVIDER_KEY'] != '')
					{
							if (!in_array($data['TYPE'], array('SELECT', 'FILE', 'CHECKBOX', 'USER_COLUMN_LIST')))
							$arReturn['FIELDS'][$code]['TYPE'] = $map['PROVIDER_KEY'];

						if ($data['TYPE'] != 'SELECT')
							$arReturn['FIELDS'][$code]['VALUE'] = $map['PROVIDER_VALUE'];
					}
				}

				CCrmPaySystem::rewritePSCorrByRqSource($personTypeId, $arReturn['FIELDS'], array('PSA_CODE' => $ID));

				$arReturn['FIELDS_BY_GROUP'] = array();
				foreach ($arReturn['FIELDS'] as $key => $value)
				{
					if (!array_key_exists($value['GROUP'], $arReturn['FIELDS_BY_GROUP']))
						$arReturn['FIELDS_BY_GROUP'][$value['GROUP']] = array();

					$arReturn['FIELDS_BY_GROUP'][$value['GROUP']][$key] = $value;
				}
				$arReturn['FIELDS_LIST'] =  implode(',', array_keys($arReturn['FIELDS']));

				$arReturn['GROUP_LIST'] = \Bitrix\Sale\BusinessValue::getGroups();

				/** @var PaySystem\BaseServiceHandler $className */
				$className = PaySystem\Manager::getClassNameFromPath($ID);
				if (!class_exists($className))
				{
					$path = PaySystem\Manager::getPathToHandlerFolder($ID);
					$fullPath = \Bitrix\Main\Application::getDocumentRoot().$path.'/handler.php';
					if ($path && \Bitrix\Main\IO\File::isFileExists($fullPath))
						require_once $fullPath;
				}

				$handlerModeList = array();
				if (class_exists($className))
				{
					$handlerModeList = $className::getHandlerModeList();
					if ($handlerModeList)
					{
						$arReturn['PS_MODE_LIST'] = $handlerModeList;

						$psMode = $psMode ?? array_shift(array_keys($handlerModeList));
						$arReturn["PAYMENT_MODE"] = Bitrix\Sale\Internals\Input\Enum::getEditHtml(
							'PS_MODE',
							array(
								'OPTIONS' => $handlerModeList,
								'ID' => 'PS_MODE',
								'ONCHANGE' => "BX.crmPSActionFile.onPsModeSelect()",
							),
							$psMode
						);
					}

					if (mb_strpos($ID, 'invoicedocument') === 0)
					{
						$arReturn["PAYMENT_MODE_TITLE"] = Loc::getMessage('CRM_PS_PS_MODE_DOCUMENT_TITLE');

						$componentPath = \CComponentEngine::makeComponentPath('bitrix:documentgenerator.templates');
						$componentPath = getLocalPath('components'.$componentPath.'/slider.php');
						$uri = new \Bitrix\Main\Web\Uri($componentPath);
						$params = [
							'PROVIDER' => \Bitrix\Crm\Integration\DocumentGenerator\DataProvider\Invoice::class,
							'MODULE' => 'crm'
						];
						$arReturn['INVOICE_DOC_ADD_LINK'] = $uri->addParams($params)->getLocator();
					}
					else
					{
						$arReturn["PAYMENT_MODE_TITLE"] = Loc::getMessage('CRM_PS_PS_MODE_TITLE');
					}
				}

				$service = new \Bitrix\Sale\PaySystem\Service(
					array('ACTION_FILE' => CCrmPaySystem::getActionHandler($ID))
				);

				$arParamsTemplate = array();
				foreach ($arReturn['FIELDS'] as $fieldId => $field)
				{
						if ($field['TYPE'] == '' || $field['TYPE'] == 'VALUE' || $field['TYPE'] == 'CHECKBOX' || $field['TYPE'] == 'USER_COLUMN_LIST')
							$arParamsTemplate[$fieldId] = $field['VALUE'];
				}

				$arParamsTemplate = array_merge($arParamsTemplate, $service->getDemoParams());
				$service->setTemplateMode(\Bitrix\Sale\PaySystem\BaseServiceHandler::STRING);
				$service->setTemplateParams($arParamsTemplate);
				if (mb_strpos($service->getField('ACTION_FILE'), 'bill') !== false || mb_strpos($service->getField('ACTION_FILE'), 'quote') !== false)
				{
					$payment = PaySystem\Manager::getPaymentObjectByData(array('PERSON_TYPE_ID' => $personTypeId));
					$result = $service->showTemplate($payment, 'template');
					$arReturn['TEMPLATE'] = $result->getTemplate();
				}

				break;

			case 'refresh_template':
				$instance = \Bitrix\Main\Application::getInstance();
				$context = $instance->getContext();
				$request = $context->getRequest();
				$formData = $request->get('formData');

				$arPsActFields = CCrmPaySystem::getPSCorrespondence($formData['data']["ACTION_FILE"], $formData['data']['PS_MODE'] ?? null);

				$filedList = explode(",", $formData['data']['PS_ACTION_FIELDS_LIST']);
				$arActParams = array();

				foreach ($filedList as $val)
				{
					$val = trim($val);

					if (empty($arPsActFields[$val]))
						continue;

					$typeTmp = $formData['data']["TYPE_".$val];
					$valueTmp = $formData['data']["VALUE1_".$val];

					if (is_string($typeTmp) && $typeTmp == '')
						$valueTmp = $formData['data']["VALUE2_".$val];

					if ($val == 'USER_COLUMNS')
					{
						if ($valueTmp && $formData['data']["VALUE2_".$val])
						{
							$valueTmp = array_replace_recursive($valueTmp, $formData['data']["VALUE2_".$val]);
							foreach ($valueTmp as $propId => $columns)
							{
								if (!isset($columns['ACTIVE']))
									unset($valueTmp[$propId]);
							}
						}
						else
						{
							$arActParams['USER_COLUMNS'] = array();
						}
					}

					if (is_string($typeTmp) && $typeTmp == '' || $typeTmp === 'CHECKBOX')
					{
						$arActParams[$val] = $valueTmp;
					}
					else if ($typeTmp === 'USER_COLUMN_LIST')
					{
						if ($valueTmp)
						{
							foreach ($valueTmp as $id => $columns)
								$arActParams['USER_COLUMNS']['PROPERTY_'.$id] = array(
									'NAME' => $columns['NAME'],
									'SORT' => $columns['SORT']
								);
						}
					}
				}

				$service = new \Bitrix\Sale\PaySystem\Service(array(
						'ID' => $formData['data']["ps_id"],
						'ACTION_FILE' => CCrmPaySystem::getActionHandler($formData['data']["ACTION_FILE"])
					)
				);

				if (mb_strpos($service->getField('ACTION_FILE'), 'bill') !== false || mb_strpos($service->getField('ACTION_FILE'), 'quote') !== false)
				{
					$arParamsTemplate = array_merge($service->getDemoParams(), $arActParams);
					$service->setTemplateMode(\Bitrix\Sale\PaySystem\BaseServiceHandler::STRING);
					$service->setTemplateParams($arParamsTemplate);
					$payment = PaySystem\Manager::getPaymentObjectByData(array('PERSON_TYPE_ID' => $formData['data']['PERSON_TYPE_ID']));
					$result = $service->showTemplate($payment, 'template');

					$arReturn['TEMPLATE'] = $result->getTemplate();
				}
				break;
			case 'get_private_key':
				if (\Bitrix\Main\Loader::includeModule('sale'))
				{
					$data = \Bitrix\Sale\PaySystem\Manager::getById($paySystemId);
					if ($data['ACTION_FILE'] == 'yandexinvoice')
					{
						$personTypeList = \Bitrix\Sale\PaySystem\Manager::getPersonTypeIdList($paySystemId);
						$personTypeId = array_shift($personTypeList);

						$shopId = \Bitrix\Sale\BusinessValue::get('YANDEX_INVOICE_SHOP_ID', 'PAYSYSTEM_'.$paySystemId, $personTypeId);
						if ($shopId <> '')
						{
							$dbRes = \Bitrix\Sale\Internals\YandexSettingsTable::getById($shopId);
							if (!$dbRes->fetch())
							{
								if (IsModuleInstalled('bitrix24') && function_exists('bx_yandex_pkey'))
								{
									$privateKey = bx_yandex_pkey();
								}
								else
								{
									$command = 'openssl ecparam -name prime256v1 -genkey | openssl pkcs8 -topk8 -nocrypt';
									$descriptorSpec = array(1 => array("pipe", "w"));
									$process = proc_open($command, $descriptorSpec, $pipes);
									$privateKey = stream_get_contents($pipes[1]);
									$return_value = proc_close($process);
								}

								$dbRes = \Bitrix\Sale\Internals\YandexSettingsTable::getById($shopId);
								if ($dbRes->fetch())
									\Bitrix\Sale\Internals\YandexSettingsTable::update($shopId, array('PKEY' => $privateKey));
								else
									\Bitrix\Sale\Internals\YandexSettingsTable::add(array('SHOP_ID' => $shopId, 'PKEY' => $privateKey));
							}
							else
							{
								$arReturn['ERROR'] = Loc::getMessage('CRM_PS_ALREADY_CONFIGURED');
							}
						}
						else
						{
							$arReturn['ERROR'] = Loc::getMessage('CRM_PS_NOT_CONFIGURED');
						}
					}
				}
				break;
		}
	}
	else
	{
		$arReturn['ERROR'] = Loc::getMessage('CRM_PS_ACCESS_DENIED');
	}
}

header('Content-Type: application/json');

echo json_encode($arReturn);

\CMain::FinalActions();
die();