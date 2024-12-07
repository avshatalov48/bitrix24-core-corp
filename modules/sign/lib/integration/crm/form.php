<?php
namespace Bitrix\Sign\Integration\CRM;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Sign\Debug\Logger;
use Bitrix\Sign\Document;
use Bitrix\Sign\Document\Member;
use Bitrix\Sign\Error;
use Bitrix\Sign\Integration\CRM;

Loc::loadMessages(__FILE__);

class Form extends \Bitrix\Sign\Internal\BaseTable
{
	public const REQUISITES_CODE = 'REQUISITES';

	/**
	 * Internal class.
	 *
	 * @var string
	 */
	public static $internalClass = 'FormTable';

	/**
	 * Creates new form for references fields.
	 *
	 * @param int $blankId Blank id.
	 * @param int $memberPart Member part index.
	 * @param array $fieldsCodes Array of references codes - fields new form.
	 * @return int|null
	 */
	public static function create(int $blankId, int $memberPart, array $fieldsCodes, int $presetId = 0): ?int
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$formTitle = "SignForm for blank #{$blankId}/{$memberPart}";
			$xmlId = "sign/blank/{$blankId}/{$memberPart}/p/{$presetId}";
			$dynamicTypeId = \Bitrix\Sign\Document\Entity\Smart::getEntityTypeId();
			$formWithRequisites = false;
			$fields = [
				['type' => 'page', 'label' => Loc::getMessage('SIGN_CORE_INTEGRATION_FORM_TITLE')]
			];

			// collect form fields
			foreach ($fieldsCodes as $code)
			{
				if ($code === self::REQUISITES_CODE)
				{
					$formWithRequisites = true;
					continue;
				}

				$fields[] = [
					'name' => $code,
					'required' => true,
					'autocomplete' => false
				];
			}

			// create form, if not exists
			$form = \Bitrix\Crm\Integration\Sign\Form::create()->loadByXmlId($xmlId);
			if (!$form->isExists())
			{
				$form
					->setXmlId($xmlId)
					->setName($formTitle)
					->setButtonCaption(Loc::getMessage('SIGN_CORE_INTEGRATION_FORM_NEXT'))
					->setDynamicTypeId($dynamicTypeId)
					->setRequisitePresetId($presetId)
					->setFields($fields)
				;
			}
			else
			{
				$form->setFields($fields);
			}

			if ($formWithRequisites)
			{
				$form->appendFieldsFromFieldSet(
					\CCrmOwnerType::Contact,
					$presetId
				);
			}

			$result = $form->save();

			if (!$result->isSuccess())
			{
				Error::getInstance()->addErrorInstance($result->getErrors()[0]);
				return false;
			}

			$formId = $form->getId();

			// register form in sign module infrastructure
			$res = self::getList([
				'select' => [
					'ID'
				],
				'filter' => [
					'BLANK_ID' => $blankId,
					'PART' => $memberPart,
					'FORM_ID' => $formId
				],
				'limit' => 1
			]);
			if (!$res->fetch())
			{
				$res = self::add([
					'BLANK_ID' => $blankId,
					'PART' => $memberPart,
					'FORM_ID' => $formId
				]);
				if (!$res->isSuccess())
				{
					Error::getInstance()->addFromResult($res);
				}

				return $res->isSuccess();
			}

			return true;
		}

		return null;
	}

	/**
	 * Removes CRM Form by id.
	 *
	 * @param int $formId Form id.
	 * @return bool
	 */
	public static function remove(int $formId): bool
	{
		if (\Bitrix\Main\Loader::includeModule('crm'))
		{
			$form = \Bitrix\Crm\Integration\Sign\Form::create()->load($formId);
			$res = $form->delete();

			if ($res->isSuccess())
			{
				return true;
			}

			Error::getInstance()->addFromResult($res);
		}

		return false;
	}

	/**
	 * Returns form html code for pai document+member.
	 *
	 * @param Document $document Document instance.
	 * @param Member $member Member instance.
	 * @return array|null
	 */
	public static function getCode(Document $document, Member $member): ?array
	{
		$form = $formId = null;

		// get form id
		$res = self::getList([
			'select' => [
				'FORM_ID'
			],
			'filter' => [
				'BLANK_ID' => $document->getBlankId(),
				'PART' => $member->getPart()
			],
			'limit' => 1
		]);
		if ($row = $res->fetch())
		{
			$formId = $row['FORM_ID'];
		}

		// get form instance
		if ($formId)
		{
			$form = \Bitrix\Crm\Integration\Sign\Form::create()->load($formId);
		}
		if (!$formId || !$form || !$form->isExists())
		{
			return [];
		}

		return [
			'instanceId' => $instanceId = 'bx-sign-form-' . $form->getId(),
			'loaderScript' => Json::encode($form->getLoaderScript($instanceId)),
			'personData' => Json::encode($form->getPersonalizationData(
				$document->getEntityId(),
				$member->getContactId(),
				$document->getCompanyId()
			)),
		];
	}

	/**
	 * Calls when form was send.
	 *
	 * @param Event $event Event instance.
	 */
	public static function onSiteFormFillSign(Event $event): void
	{
		$fields = $event->getParameter('fields');
		$props = $event->getParameter('properties');

		// we use this hack because at this point form has not saved yet
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->addEventHandler('crm', 'onSiteFormFilledSign',
			function () use ($fields, $props) {
				if (
					!($props['documentHash'] ?? null)
					|| !($props['memberHash'] ?? null)
				)
				{
					return;
				}

				// get actual entity data after form filling and store to safe

				$document = Document::getByHash($props['documentHash']);
				if ($document)
				{
					$member = $document->getMemberByHash($props['memberHash']);
					if ($member === null)
					{
						return;
					}
					$userData = static::getUserData($fields, $document, $member);

					if (!empty($userData))
					{
						$member->setUserData($userData);
					}
				}

				if (!Error::getInstance()->isEmpty())
				{
					$error = Error::getInstance()->getErrorsAsArray()[0]['message'] ?? false;
					if ($error)
					{
						Logger::getInstance()->error($error);
					}
				}
			}
		);
	}

	private static function getUserData(array $fields, Document $document, Member $member): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$contactUserData = static::getContactUserData($fields, $document, $member);
		$companyUserData = static::getCompanyUserData($fields, $document);
		$documentUserData = static::getSmartDocumentUserData($fields, $document);

		return array_merge($contactUserData, $companyUserData, $documentUserData);
	}

	private static function getContactUserData(array $fields, Document $document, Member $member): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$entityTypeName = \CCrmOwnerType::ContactName;
		$memberContactEntityId = $member->getContactId();

		$fields = static::prepareFieldsWithMultifields($fields, $entityTypeName);
		$documentId = $document?->getId() ?? $member?->getDocument()?->getId();
		$userData = self::getUserDataWithEntityFieldValues($fields, $entityTypeName, $memberContactEntityId, $documentId);

		$memberPart = $member->getPart();
		$requisitesExists = array_filter(
			$document->getBlank()->getBlocks(),
			static function ($block) use ($memberPart) {
				return
					$block->getPart() === $memberPart
					&& $block->getCode() === 'requisites'
				;
			}
		);
		if ($requisitesExists)
		{
			$userData[self::REQUISITES_CODE] = CRM::getRequisitesContactFieldSetValues($memberContactEntityId, $document);
		}

		return $userData;
	}

	private static function getSmartDocumentUserData(array $fields, Document $document): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		return static::getUserDataWithEntityFieldValues(
			$fields,
			\CCrmOwnerType::SmartDocumentName,
			$document->getEntityId(),
			($document?->getId() ?? null)
		);
	}

	private static function getCompanyUserData(array $fields, Document $document): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$entityTypeName = \CCrmOwnerType::CompanyName;
		$fields = static::prepareFieldsWithMultifields($fields, $entityTypeName);

		return self::getUserDataWithEntityFieldValues(
			$fields,
			$entityTypeName,
			$document->getCompanyId()
			($document?->getId() ?? null)
		);
	}

	private static function prepareFieldsWithMultifields(array $fields, string $entityTypeName): array
	{
		if (!isset($fields[$entityTypeName]['FM']))
		{
			return $fields;
		}

		$communicationTypes = ['PHONE', 'EMAIL', 'WEB', 'IM'];
		foreach ($communicationTypes as $communicationType)
		{
			if (isset($fields[$entityTypeName]['FM'][$communicationType]))
			{
				$fields[$entityTypeName][$communicationType] = null;
			}
		}

		unset($fields[$entityTypeName]['FM']);

		return $fields;
	}

	private static function getUserDataWithEntityFieldValues(
		array $fields,
		string $entityTypeName,
		int $entityId,
		?int $documentId = null
	): array
	{
		$userData = [];
		foreach ($fields[$entityTypeName] ?? [] as $key => $value)
		{
			if (mb_strpos($key, 'RQ_') === 0)
			{
				continue;
			}
			$userData[$entityTypeName . '_' . $key] = $value
				? ['text' => $value]
				: CRM::getEntityFieldValue(
					$entityId,
					$entityTypeName . '_' . $key,
					$documentId
				);
		}

		return $userData;
	}
}