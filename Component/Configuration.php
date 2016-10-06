<?php
/**
 * Created by PhpStorm.
 * User: ransomware
 * Date: 06/10/16
 * Time: 10:32
 **/

namespace Mapbender\SearchBundle\Component;

use Eslider\Entity\UniqueBaseEntity;

class Configuration extends UniqueBaseEntity
{

    const DEFAULT_CONNECTION = "default";

    /** @var string Connection ** */
    protected $connection = DEFAULT_CONNECTION;


    /**
     * Permission
     **/

    /** @var string AllowedSchemes ** */
    protected $allowedSchemes;

    /** @var bool AllowRowSQl ** */
    protected $allowRowSql;

    /** @var bool AllowSave ** */
    protected $allowSave;

    /** @var bool AllowRemove ** */
    protected $allowRemove;

    /** @var bool AllowExport ** */
    protected $allowExport;

    /** @var bool UserOwnQuery ** */
    protected $userOwnQuery;

    /** @var bool $allowUserPublishing ** */
    protected $allowUserPublishing;

    /**
     * @return string
     **/
    public function getAllowedSchemes()
    {
        return $this->allowedSchemes;
    }

    /**
     * @param string $allowedSchemes
     * @return Configuration
     ***/
    public function setAllowedSchemes($allowedSchemes)
    {
        $this->allowedSchemes = $allowedSchemes;
        return $this;
    }

    /**
     * @return boolean
     ***/
    public function isAllowRowSql()
    {
        return $this->allowRowSql;
    }

    /**
     * @param boolean $allowRowSql
     * @return Configuration
     ***/
    public function setAllowRowSql($allowRowSql)
    {
        $this->allowRowSql = $allowRowSql;
        return $this;
    }

    /**
     * @return boolean
     **/
    public function isAllowSave()
    {
        return $this->allowSave;
    }

    /**
     * @param boolean $allowSave
     * @return Configuration
     **/
    public function setAllowSave($allowSave)
    {
        $this->allowSave = $allowSave;
        return $this;
    }

    /**
     * @return boolean
     **/
    public function isAllowRemove()
    {
        return $this->allowRemove;
    }

    /**
     * @param boolean $allowRemove
     * @return Configuration
     **/
    public function setAllowRemove($allowRemove)
    {
        $this->allowRemove = $allowRemove;
        return $this;
    }

    /**
     * @return boolean
     **/
    public function isAllowExport()
    {
        return $this->allowExport;
    }

    /**
     * @param boolean $allowExport
     * @return Configuration
     **/
    public function setAllowExport($allowExport)
    {
        $this->allowExport = $allowExport;
        return $this;
    }

    /**
     * @return boolean
     **/
    public function isUserOwnQuery()
    {
        return $this->userOwnQuery;
    }

    /**
     * @param boolean $userOwnQuery
     * @return Configuration
     **/
    public function setUserOwnQuery($userOwnQuery)
    {
        $this->userOwnQuery = $userOwnQuery;
        return $this;
    }

    /**
     * @return boolean
     **/
    public function isAllowUserPublishing()
    {
        return $this->allowUserPublishing;
    }

    /**
     * @param boolean $allowUserPublishing
     * @return Configuration
     **/
    public function setAllowUserPublishing($allowUserPublishing)
    {
        $this->allowUserPublishing = $allowUserPublishing;
        return $this;
    }

    /**
     * @return string
     */
    public function getConnection()
    {
        return $this->connection;
    }


}