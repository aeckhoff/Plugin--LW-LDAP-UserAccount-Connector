<?php

/**************************************************************************
*  Copyright notice
*
*  Copyright 2013 Logic Works GmbH
*
*  Licensed under the Apache License, Version 2.0 (the "License");
*  you may not use this file except in compliance with the License.
*  You may obtain a copy of the License at
*
*  http://www.apache.org/licenses/LICENSE-2.0
*  
*  Unless required by applicable law or agreed to in writing, software
*  distributed under the License is distributed on an "AS IS" BASIS,
*  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
*  See the License for the specific language governing permissions and
*  limitations under the License.
*  
***************************************************************************/

namespace lwLdapUserConnector;

class lwLdapUserConnector 
{
    public function __construct($host, $baseDN) 
    {
        $this->host = $host;
        $this->baseDN = $baseDN;
    }
    
    protected function connect()
    {
        $this->connection = ldap_connect($this->host);
    }
    
    public function setAllowedParameterFields($array)
    {
        $this->allowedParameter = $array;
    }
    
    public function setObjectCategory($objectCategory)
    {
        $this->objectCategory = $objectCategory;
    }
    
    public function setUserAccountControl($userAccountControl)
    {
        $this->userAccountControl = $userAccountControl;
    }
    
    public function setSearchBase($searchBase)
    {
        $this->searchBase = $searchBase;
    }
    
    protected function getEntriesBySearchString($searchString)
    {
        if ($this->connection) {
            $res = ldap_search($this->connection, $this->baseDN, $searchString);
            return ldap_get_entries($this->connection, $res);
        }
        else {
            throw new Exception("Error: not connected to LDAP Server");
        }
    }
    
    protected function getSingleUserAccountBySearchString($searchString)
    {
        $user = $this->getEntriesBySearchString($searchString);
        if ($user["count"] != 1) {
            return false;
        }
        return $user;
    }

    protected function getSingleUserAccount($parameterField, $parameterValue)
    {
        return $this->getSingleUserAccountBySearchString("(&(objectClass=user) (($parameterField=".$parameterValue.")))");
    }
    
    public function getEntryByEmail($email)
    {
        return $this->getSingleUserAccount('mail', $email);
    }
    
    public function getEntryByName($name)
    {
        return $this->getSingleUserAccount('sAMAccountName', $name);
    }
    
    public function login($login, $password)
    {
        try {
            $user = $this->getSingleUserAccountBySearchString("(&(objectClass=user) (|(mail=$login)(sAMAccountName=$login)))");
            if ($user === false) {
                throw new Exception("Error: given User doesn't exist");
            }
            else {
                return @ldap_bind($this->connection, $user[0]["dn"], $passwd);
            }
        }
        catch (Exception $e)
        {
            throw new Exception($e->getMessage());
        }
    }    
    
    public function getEntriesByParameterAndValue($parameterField, $parameterValue)
    {
        return $this->getEntriesBySearchString($this->searchBase."(&(objectCategory=".$this->objectCategory.")(!(userAccountControl:".$this->userAccountControl."))(".$parameterField."=".$parameterValue."))");
    }
    
    public function getAllEntries()
    {
        return $this->getEntriesBySearchString($this->searchBase."(&(objectCategory=".$this->objectCategory.")(!(userAccountControl:".$this->userAccountControl.")))");
    }
}