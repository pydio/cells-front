<?php
/*
 * Copyright 2007-2016 Abstrium <contact (at) pydio.com>
 * This file is part of Pydio.
 *
 * Pydio is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Pydio is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Pydio.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The latest code can be found at <https://pydio.com/>.
 */
namespace Pydio\Core\Services;

use Pydio\Core\Http\Client\SimpleStoreApi;

defined('PYDIO_EXEC') or die('Access not allowed');

class BinaryService {

    /**
     * @param array $context
     * @param String $fileName
     * @param String $ID
     * @return String $ID
     */
    public static function saveBinary($context, $fileName, $ID = null)
    {
        $store = new SimpleStoreApi();
        return $store->saveBinary($context, $fileName, $ID);
    }

    /**
     * @abstract
     * @param array $context
     * @param String $ID
     * @return boolean
     */
    public static function deleteBinary($context, $ID)
    {
        $store = new SimpleStoreApi();
        return $store->deleteBinary($context, $ID);
    }


    /**
     * @param array $context
     * @param String $ID
     * @param Resource $outputStream
     * @return boolean
     */
    public static function loadBinary($context, $ID, $outputStream = null)
    {
        $store = new SimpleStoreApi();
        return $store->loadBinary($context, $ID, $outputStream);
    }



}