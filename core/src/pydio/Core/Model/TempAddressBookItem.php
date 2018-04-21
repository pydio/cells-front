<?php

namespace Pydio\Core\Model;

defined('PYDIO_EXEC') or die('Access not allowed');

class TempAddressBookItem extends AddressBookItem{

    function __construct($tempLogin)
    {
        parent::__construct('user', '', '', $tempLogin, true);
    }

}