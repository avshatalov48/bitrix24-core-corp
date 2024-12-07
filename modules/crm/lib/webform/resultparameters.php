<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\Uri;
use Bitrix\Crm;

/**
 * Class ResultParameters
 * @package Bitrix\Crm\WebForm
 */
class ResultParameters
{
	const EVENT_FIELDS_FILE = 'fields-file';

	/** @var array $fields Fields. */
	protected $fields = [];

	/** @var Form $form Form instance. */
	protected $form;

	/** @var array visitedPages Visited pages. */
	protected $visitedPages = [];

	/** @var array $presets Presets. */
	protected $presets = [];

	/** @var string|null $fromUrl From url. */
	protected $fromUrl = null;

	/** @var bool $stop Stop callback. */
	protected $stopCallback = false;

	/** @var callable[] $callbacks Callbacks. */
	protected $callbacks = [];

	/**
	 * ResultParameters constructor.
	 *
	 * @param Form $form Form instance.
	 */
	public function __construct(Form $form)
	{
		$this->form = $form;
	}

	/**
	 * To array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return [
			'COMMON_FIELDS' => $this->getCommonFields(),
			'PLACEHOLDERS' => $this->getPlaceholders(),
			'STOP_CALLBACK' => $this->stopCallback,
			'COMMON_DATA' => [
				'VISITED_PAGES' => $this->visitedPages
			],
		];
	}

	/**
	 * Add callback.
	 *
	 * @param string $event Event.
	 * @param callable $callable Event handler.
	 * @return $this
	 */
	public function addCallback($event, $callable)
	{
		$this->callbacks[$event] = $callable;
		return $this;
	}

	/**
	 * Fire event.
	 *
	 * @param string $event Event.
	 * @param mixed &$parameter Parameter.
	 * @return mixed
	 */
	protected function fireEvent($event, &$parameter)
	{
		if (!isset($this->callbacks[$event]))
		{
			return $parameter;
		}

		return call_user_func_array($this->callbacks[$event], [&$parameter]);
	}

	/**
	 * Set fields.
	 *
	 * @param array $fields Fields.
	 * @return $this
	 */
	public function setFields(array $fields)
	{
		$this->fields = $fields;
		return $this;
	}

	/**
	 * Add visited page.
	 *
	 * @param string $url Url.
	 * @param string $title Title.
	 * @param int $timestamp Timestamp.
	 * @return $this
	 */
	public function addVisitedPage($url, $title, $timestamp)
	{
		$this->visitedPages[] = array(
			'HREF' => $url,
			'DATE' => is_numeric($timestamp) ? $timestamp : null,
			'TITLE' => $title
		);

		return $this;
	}

	/**
	 * Set from.
	 *
	 * @param null|string $fromUrl From url.
	 * @return $this
	 */
	public function setFromUrl($fromUrl)
	{
		$this->fromUrl = $fromUrl;
		return $this;
	}

	/**
	 * Set presets.
	 *
	 * @param array $presets Presets.
	 * @return $this
	 */
	public function setPresets(array $presets)
	{
		$this->presets = $presets;
		return $this;
	}

	/**
	 * Set stop callback.
	 *
	 * @param bool $stopCallback Stop callback.
	 * @return $this
	 */
	public function setStopCallback($stopCallback)
	{
		$this->stopCallback = $stopCallback;
		return $this;
	}

	/**
	 * Get stop callback.
	 *
	 * @return bool
	 */
	public function getStopCallback()
	{
		return $this->stopCallback;
	}

	/**
	 * Get fields map.
	 *
	 * @return array
	 */
	public function getFieldsMap()
	{
		$fieldsMap = $this->form->getFieldsMap();
		foreach ($fieldsMap as $fieldKey => $field)
		{
			if($field['type'] == 'file')
			{
				$values = $this->fireEvent(self::EVENT_FIELDS_FILE, $field);
			}
			else
			{
				$values = isset($this->fields[$field['name']]) ? $this->fields[$field['name']] : null;
			}

			if(!is_array($values))
			{
				$values = array($values);
			}

			if($field['type'] == 'phone')
			{
				$valuesTmp = array();
				foreach($values as $value)
				{
					$value = preg_replace("/[^0-9+]/", '', $value);
					$valuesTmp[] = $value;
				}
				$values = $valuesTmp;
			}

			if ($field['entity_field_name'] == 'COMMENTS')
			{
				$valuesTmp = array();
				foreach($values as $value)
				{
					$valuesTmp[] = htmlspecialcharsbx($value);
				}
				$values = $valuesTmp;
			}

			$field['values'] = $values;
			$fieldsMap[$fieldKey] = $field;
		}

		return $fieldsMap;
	}

	/**
	 * Get placeholders.
	 *
	 * @return array
	 */
	public function getPlaceholders()
	{
		$placeholders = $this->presets;

		if ($this->fromUrl)
		{
			$uri = new Uri($this->fromUrl);
			if ($uri->getLocator())
			{
				if ($uri->getQuery())
				{
					$queryParamList = [];
					parse_str($uri->getQuery(), $queryParamList);
					if (count($queryParamList) > 0)
					{
						$placeholders = $placeholders + $queryParamList;
					}
					foreach ($queryParamList as $queryParamKey => $queryParamVal)
					{
						if (!is_string($queryParamVal))
						{
							continue;
						}

						$placeholders[$queryParamKey] = $queryParamVal;
					}
				}

				$placeholders['from_url'] = $uri->getLocator();
				$placeholders['from_domain'] = $uri->getHost();
			}
		}

		return $placeholders;
	}

	/**
	 * @return array
	 */
	public function getCommonFields()
	{
		$commonFields = [];

		// prepare utm fields in common fields
		$utmDictionary = Crm\UtmTable::getCodeList();
		foreach ($this->getPlaceholders() as $placeholderCode => $placeholderValue)
		{
			$utmName = mb_strtoupper($placeholderCode);
			if (!in_array($utmName, $utmDictionary))
			{
				continue;
			}

			$commonFields[$utmName] = $placeholderValue;
		}

		return $commonFields;
	}
}
