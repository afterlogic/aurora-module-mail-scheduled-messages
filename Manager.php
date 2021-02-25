<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailScheduledMessages;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2021, Afterlogic Corp.
 */
class Manager extends \Aurora\System\Managers\AbstractManagerWithStorage
{
	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
	{
		parent::__construct($oModule, new Storages\Db\Storage($this));
	}

	/**
	 * @return array|bool
	 */
	public function getMessagesForSend($iTimestamp)
	{
		$aMessagesForSend = $this->oStorage->getMessagesForSend($iTimestamp);
        return $aMessagesForSend;
	}

    /**
     * @param int $iAccountID
     * @param string $sFolderFullName
     * @param string $sMessageUid
     * @param string $sDeviceId
     *
     * @return bool
     */
    public function addMessage($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp)
    {
        return $this->oStorage->addMessage($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp);
    }

    public function updateMessageScheduleTimestamp($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp)
    {
        return $this->oStorage->updateMessageScheduleTimestamp($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp);
    }

	public function getMessage($iAccountID, $sFolderFullName, $sMessageUid)
    {
        return $this->oStorage->getMessage($iAccountID, $sFolderFullName, $sMessageUid);
    }

    public function removeMessage($iAccountID, $sFolderFullName, $sMessageUid)
    {
        return $this->oStorage->removeMessage($iAccountID, $sFolderFullName, $sMessageUid);
    }

    /**
	 * Creates tables required for module work by executing create.sql file.
	 *
	 * @return boolean
	 */
	public function createTablesFromFile()
	{
		$bResult = false;

		try
		{
			$sFilePath = dirname(__FILE__) . '/Storages/Db/sql/create.sql';
			$bResult = \Aurora\System\Managers\Db::getInstance()->executeSqlFile($sFilePath);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * Temp.
	 *
	 * @return boolean
	 */
	public function insertDataFromFile()
	{
		$bResult = false;

		try
		{
			$sFilePath = dirname(__FILE__) . '/Storages/Db/sql/insert.sql';
			$bResult = \Aurora\System\Managers\Db::getInstance()->executeSqlFile($sFilePath);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * Update tables required for module work by executing update.sql file.
	 *
	 * @return boolean
	 */
	public function updateTables()
	{
		return true;

//		$bResult = false;
//
//		try
//		{
//			$sFilePath = dirname(__FILE__) . '/Storages/Db/sql/update.sql';
//			$bResult = \Aurora\System\Managers\Db::getInstance()->executeSqlFile($sFilePath);
//		}
//		catch (\Aurora\System\Exceptions\BaseException $oException)
//		{
//			$this->setLastException($oException);
//		}
//
//		return $bResult;
	}
}
