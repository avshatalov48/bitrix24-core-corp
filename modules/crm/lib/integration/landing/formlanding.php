<?php

namespace Bitrix\Crm\Integration\Landing;

use Bitrix\Crm\UI\Webpack;
use Bitrix\Landing\Agent;
use Bitrix\Landing\Internals\LandingTable;
use Bitrix\Landing\Landing;
use Bitrix\Landing\Subtype;
use Bitrix\Landing\Rights;
use Bitrix\Main;
use Bitrix\Crm\WebForm;

/**
 * Class FormLanding.
 * @package Bitrix\Crm\Integration\Landing
 */
class FormLanding
{
	/**
	 * Option code for site id storage.
	 */
	const OPT_CODE_LANDINGS_SITE_ID = 'forms_landing_site_id';

	/**
	 * Landing site type.
	 */
	const SITE_TYPE = 'PAGE';

	public const LANDING_CODE_PRERIX = 'crm_form_';

	/**
	 * If object initialization was successfully.
	 * @var bool
	 */
	protected $init = false;

	/**
	 * Last error message if occurred.
	 * @var string
	 */
	protected $lastError = null;

	/** @var int|null|false $siteId  */
	protected $siteId = false;

	/** @var static */
	protected static $instance;

	/** @var static */
	protected static $landingSites = [];

	private $deletingLandingId;

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->init = Main\Loader::includeModule('landing');
		if (!$this->init)
		{
			$this->setErrorMessage('Module Landing is not installed.');
		}
	}

	/**
	 * Return true if it can be used.
	 *
	 * @return bool
	 */
	public function canUse()
	{
		return $this->init;
	}

	/**
	 * Set error message.
	 *
	 * @param string|Main\Error $error Error.
	 * @return void
	 */
	protected function setErrorMessage($error): void
	{
		if (is_string($error))
		{
			$this->lastError = $error;
		}
		else if ($error instanceof Main\Error)
		{
			$this->lastError = $error->getMessage();
		}
	}

	/**
	 * Returns last error message if occurred.
	 * @return string|null
	 */
	public function getErrorMessage(): ?string
	{
		return $this->lastError;
	}

	/**
	 * Returns (creates if no exists) special site for Landing Form.
	 * @return int|null
	 */
	public function getSiteId(): ?int
	{
		if (!$this->canUse())
		{
			return null;
		}

		if ($this->siteId !== false)
		{
			return $this->siteId;
		}

		$this->siteId = null;
		$storedSiteId = (int) Main\Config\Option::get(
			'crm', $this::OPT_CODE_LANDINGS_SITE_ID
		);
		// site is not exist, create new one
		if (!$storedSiteId)
		{
			Rights::setGlobalOff();
			$res = \Bitrix\Landing\Site::add([
				'TITLE' => 'CRM Forms',
				'TYPE' => $this::SITE_TYPE,
				'CODE' => \Bitrix\Landing\Site\Type::PSEUDO_SCOPE_CODE_FORMS,
				'SPECIAL' => 'Y'
			]);
			Rights::setGlobalOn();
			if ($res->isSuccess())
			{
				$this->siteId = (int) $res->getId();
				Main\Config\Option::set(
					'crm', $this::OPT_CODE_LANDINGS_SITE_ID, $this->siteId
				);
				return $this->siteId;
			}
			else
			{
				$this->setErrorMessage($res->getErrors()[0]);
			}
		}

		if ($storedSiteId)
		{
			$this->siteId = $storedSiteId;
		}
		// check that exists
		if ($this->siteId)
		{
			$res = \Bitrix\Landing\Site::getList([
				'select' => [
					'ID'
				],
				'filter' => [
					'=ID' => $this->siteId,
					'CHECK_PERMISSIONS' => 'N'
				]
			]);
			if (!$res->fetch())
			{
				Main\Config\Option::set(
					'crm', $this::OPT_CODE_LANDINGS_SITE_ID, 0
				);
				$this->siteId = false;
				return $this->getSiteId();
			}
		}

		return $this->siteId;
	}

	/**
	 * Creates new landing id.
	 * @param int $formId - ID of created form
	 * @param string $formName - Name of created form
	 * @return int|null
	 */
	public function createLanding($formId, $formName = null): ?int
	{
		$siteId = $this->getSiteId();
		if (!$siteId)
		{
			return $siteId;
		}
		if (!$formId)
		{
			return null;
		}

		Rights::setGlobalOff();

		$result = Landing::add([
			'TITLE' => $formName ?: 'CRM Form',
			'SITE_ID' => $siteId,
			'CODE' => self::LANDING_CODE_PRERIX . mb_strtolower(\Bitrix\Main\Security\Random::getString(5, true))
		]);
		if ($result->isSuccess())
		{
			$lid = $result->getId();
			$landing = Landing::createInstance($lid);
			$blockId = $landing->addBlock('66.90.form_new_default', [
				'ACCESS' => 'W'
			]);
			if ($blockId)
			{
				Subtype\Form::setFormIdToBlock($blockId, $formId);
			}

			$webpack = Webpack\Form::instance($formId);
			if (!$webpack->getEmbeddedFileUrl())
			{
				Agent::addUniqueAgent('rePublicationLanding', [$lid], 7200, 60);
			}

			Rights::setGlobalOn();

			return $lid;
		}

		Rights::setGlobalOn();
		$this->setErrorMessage($result->getErrors()[0]);

		return null;
	}

	/**
	 * Get form ID by landing ID.
	 *
	 * @param int $landingId Landing ID.
	 * @return int|null
	 */
	public function canDelete($landingId)
	{
		$row = WebForm\Internals\LandingTable::getRow([
			'select' => ['ID'],
			'filter' => ['=LANDING_ID' => $landingId]
		]);

		return empty($row['ID']) || (int) $this->deletingLandingId === (int) $landingId;
	}

	/**
	 * Delete landing.
	 *
	 * @param int $landingId Landing ID.
	 * @return void
	 */
	public function deleteLanding($landingId)
	{
		if ($this->canUse())
		{
			Rights::setGlobalOff();
			$this->deletingLandingId = $landingId;
			Landing::delete($landingId)->isSuccess();
			Rights::setGlobalOn();
		}
	}

	/**
	 * Returns public url of landing by id.
	 *
	 * @param int|int[] $landingId Landing id.
	 * @return string|string[]
	 */
	public function getPublicUrl($landingId)
	{
		static $landing = null;
		$url = '';

		if ($this->canUse())
		{
			Rights::setGlobalOff();
			if ($landing === null)
			{
				$landing = Landing::createInstance(0);
			}
			$url = $landing->getPublicUrl($landingId);
			Rights::setGlobalOn();
		}

		return $url;
	}

	/**
	 * Returns edit url of landing by id.
	 * @param int $landingId Landing id.
	 * @return string
	 */
	public function getEditUrl($landingId)
	{
		if (!$this->canUse())
		{
			return null;
		}

		if (empty(static::$landingSites))
		{
			$sites = WebForm\Internals\LandingTable::getList([
				'select' => [
					'SITE_ID' => 'LANDING.SITE_ID',
					'LANDING_ID',
				],
			]);

			foreach ($sites as $site)
			{
				static::$landingSites[$site['LANDING_ID']] = $site['SITE_ID'];
			}
		}

		$siteId = static::$landingSites[$landingId] ?? false;

		if (!$siteId)
		{
			$siteId = $this->getSiteId();
		}

		if (!$siteId)
		{
			$siteId = 0;
		}

		return "/sites/site/$siteId/view/$landingId/";
	}
}
