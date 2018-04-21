<?php
/*
 * Copyright 2007-2017 Charles du Jeu <contact (at) cdujeu.me>
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
 * The latest code can be found at <https://pydio.com>.
 *
 * These configuration must be set at the very root loading of the framework
 */

/*
 * If you have a charset warning, or problems displaying filenames with accented characters,
 * check your system locale and set it in the form lang_country.charset
 * Example : fr_FR.UTF-8, fr_FR.ISO-8859-1, fr_FR.CP1252 (windows), en_EN.UTF-8, etc.
 *
 * Windows users may define an empty string
 * define("PYDIO_LOCALE", "");
 */
//define("PYDIO_LOCALE", "en_EN.UTF-8");
//define("PYDIO_LOCALE", "");


/*
 * If you encounter problems writing to the standard php tmp directory, you can
 * define your own tmp dir here. Suggested value is ajxp_path/data/tmp/
 * PYDIO_DATA_PATH, PYDIO_INSTALL_PATH are replaced automatically.
 *
 * See php.ini settings below for the session.save_path value as well.
 */
//define("PYDIO_TMP_DIR", PYDIO_DATA_PATH."/tmp");


/*
 * Additionnal php.ini settings
 * > Problems with tmp dir : set your own session tmp dir (create it and make it writeable!)
 * > Concurrent versions of Pydio : use session.cookie_path to differentiate them.
 */
$PYDIO_INISET = array();
//$PYDIO_INISET["session.save_path"] = PYDIO_DATA_PATH."/tmp/sessions";
//$PYDIO_INISET["session.cookie_path"] = "/pydio";