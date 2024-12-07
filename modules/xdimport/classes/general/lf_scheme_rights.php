<?php

use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);

class CXDILFSchemeRights
{
	public $LAST_ERROR = '';

	function CheckFields($action, &$arFields)
	{
		global $APPLICATION;

		$this->LAST_ERROR = '';
		$aMsg = [];

		if (
			(
				(
					$action === 'update'
					&& array_key_exists('SCHEME_ID', $arFields)
				)
				|| $action === 'add'
			)
			&& (int)$arFields['SCHEME_ID'] <= 0)
		{
			$aMsg[] = [
				'id' => 'SCHEME_ID',
				'text' => Loc::getMessage('LFP_CLASS_SCHEME_RIGHT_ERR_SCHEME_ID')
			];
		}

		if (
			(
				(
					$action === 'update'
					&& array_key_exists('GROUP_CODE', $arFields)
				)
				|| $action === 'add'
			)
			&& $arFields['GROUP_CODE'] == ''
		)
		{
			$aMsg[] = [
				'id' => 'GROUP_CODE',
				'text' => Loc::getMessage('LFP_CLASS_SCHEME_RIGHT_ERR_ENTITY_GROUP_CODE')
			];
		}

		if (!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			$this->LAST_ERROR = $e->GetString();
			return false;
		}

		return true;
	}

	function Add($arFields)
	{
		global $DB;

		if (!$this->CheckFields('add', $arFields))
		{
			return false;
		}

		$rsTmp = self::getList(
			[],
			[
				'SCHEME_ID' => $arFields['SCHEME_ID'],
				'GROUP_CODE' => $arFields['GROUP_CODE']
			]
		);
		$arTmp = $rsTmp->Fetch();
		if (!$arTmp)
		{
			$arFields['ID'] = 1;
			$DB->Add('b_xdi_lf_scheme_right', $arFields);
		}

		return true;
	}

	public static function DeleteBySchemeID($schemeId)
	{
		global $DB, $APPLICATION;

		$schemeId = (int)$schemeId;

		$strSql = '
			DELETE
			FROM b_xdi_lf_scheme_right
			WHERE SCHEME_ID = ' . $schemeId . '
		';

		$res = $DB->Query($strSql);
		if (is_object($res))
		{
			return true;
		}

		$e = $APPLICATION->GetException();
		$strError = Loc::getMessage('LFP_CLASS_SCHEME_RIGHT_DELETE_ERROR', [
			'#error_msg#' => (is_object($e) ? $e->GetString() : '')
		]);

		$APPLICATION->ResetException();
		$e = new CApplicationException($strError);
		$APPLICATION->ThrowException($e);
		return false;
	}

	public static function getList($aSort = [], $aFilter = [])
	{
		global $DB;

		$arFilter = [];

		foreach ($aFilter as $key => $val)
		{
			$val = $DB->ForSql($val);
			if ($val == '')
			{
				continue;
			}

			switch (mb_strtoupper($key))
			{
				case 'SCHEME_ID':
					$arFilter[] = 'SR.SCHEME_ID = ' . $val;
					break;
				case 'GROUP_CODE':
					$arFilter[] = 'SR.GROUP_CODE = \'' . $val . '\'';
					break;
			}
		}

		$arOrder = [];
		foreach ($aSort as $key=>$val)
		{
			$ord = (mb_strtoupper($val) !== 'ASC' ? 'DESC' : 'ASC');
			switch (mb_strtoupper($key))
			{
				case 'SCHEME_ID':
					$arOrder[] = 'SR.SCHEME_ID ' . $ord;
					break;
				case 'GROUP_CODE':
					$arOrder[] = 'SR.GROUP_CODE ' . $ord;
					break;
			}
		}
		if (count($arOrder) === 0)
		{
			$arOrder[] = 'SR.SCHEME_ID ASC';
		}

		$sOrder = '
			ORDER BY ' . implode(', ', $arOrder);

		if (count($arFilter) === 0)
		{
			$sFilter = '';
		}
		else
		{
			$sFilter = '
			WHERE ' . implode(
				'
					AND ',
				$arFilter
			);
		}

		$strSql = '
			SELECT
				SR.SCHEME_ID,
				SR.GROUP_CODE
			FROM
				b_xdi_lf_scheme_right SR
			' . $sFilter . $sOrder;

		return $DB->Query($strSql);
	}

	function GetByID($ID)
	{
		global $DB;
		$ID = (int)$ID;

		$strSql = '
			SELECT
				SR.*
			FROM b_xdi_lf_scheme_right SR
			WHERE SR.ID = ' . $ID . '
		';

		return $DB->Query($strSql);
	}

	function Set($schemeId, $arRights = [], $arEUV = [])
	{
		if ((int)$schemeId <= 0)
		{
			return false;
		}

		if (!is_array($arRights))
		{
			return false;
		}

		if (
			!is_array($arEUV)
			|| !array_key_exists('ENTITY_TYPE', $arEUV)
			|| !array_key_exists('EVENT_ID', $arEUV)
		)
		{
			return false;
		}

		if (
			!array_key_exists('ENTITY_ID', $arEUV)
			|| (int)$arEUV['ENTITY_ID'] <= 0
		)
		{
			$arEUV['ENTITY_ID'] = 0;
		}

		self::DeleteBySchemeID($schemeId);

		$obXDIUser = new CXDIUser();

		foreach ($arRights as $prefix => $arRightsTmp)
		{
			if (in_array($prefix, [ 'UA', 'UN' ]))
			{
				$this->Add(
					[
						'SCHEME_ID' => $schemeId,
						'GROUP_CODE' => $prefix
					]
				);
			}
			else
			{
				if (!is_array($arRightsTmp))
				{
					continue;
				}

				foreach ($arRightsTmp as $user_id_tmp)
				{
					if ((int)$user_id_tmp > 0)
					{
						$obXDIUser->Add(
							[
								'USER_ID' => $user_id_tmp,
								'GROUP_CODE' => $prefix . $user_id_tmp
							]
						);
						
						$this->Add(
							[
								'SCHEME_ID' => $schemeId,
								'GROUP_CODE' => $prefix . $user_id_tmp
							]
						);
					}
				}
			}
		}

		return true;
	}
}
