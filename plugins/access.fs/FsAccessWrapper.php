<?php
/*
 * Copyright 2007-2017 Charles du Jeu - Abstrium SAS <team (at) pyd.io>
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
 */
namespace Pydio\Access\Driver\StreamProvider\FS;

use Pydio\Access\Core\IPydioWrapper;
use Pydio\Access\Core\Model\Node;
use Pydio\Access\Core\Model\UserSelection;

use Pydio\Core\Services\ConfService;
use Pydio\Core\Exception\PydioException;
use Pydio\Core\Services\ApplicationState;
use Pydio\Core\Utils\Vars\InputFilter;
use Pydio\Core\Utils\Vars\PathUtils;
use Pydio\Core\Utils\Vars\UrlUtils;
use Pydio\Core\Utils\TextEncoder;
use Pydio\Log\Core\Logger;

defined('PYDIO_EXEC') or die( 'Access not allowed');

/**
 * Wrapper for a local filesystem
 * @package AjaXplorer_Plugins
 * @subpackage Access
 */
class FsAccessWrapper implements IPydioWrapper
{
    /**
     * FileHandle resource
     *
     * @var resource
     */
    protected $fp;
    /**
     * DirHandle resource
     *
     * @var resource
     */
    protected $dH;

    /**
     * If dH is not used but an array containing the listing
     * instead. dH == -1 in that case.
     *
     * @var array()
     */
    protected static $currentListing;
    protected static $currentListingKeys;
    protected static $currentListingIndex;
    protected static $currentFileKey;
    protected static $crtZip;
    protected $realPath;
    protected static $lastRealSize;

    /**
     * Initialize the stream from the given path.
     *
     * @param string $path
     * @param $streamType
     * @param bool $storeOpenContext
     * @param bool $skipZip
     * @return mixed Real path or -1 if currentListing contains the listing : original path converted to real path
     * @throws \Pydio\Core\Exception\PydioException
     * @throws \Exception
     */
    protected static function initPath($path, $streamType, $storeOpenContext = false, $skipZip = false)
    {
        $path       = PathUtils::unPatchPathForBaseDir($path);
        $url        = UrlUtils::safeParseUrl($path);
        $node       = new Node($path);
        $repoObject = $node->getRepository();
        $repoId     = $node->getRepositoryId();

        if(!isSet($repoObject)) {
            throw new \Exception("Cannot find repository with id ".$repoId);
        }
        $split = UserSelection::detectZip($url["path"]);

        $resolvedOptions = self::getResolvedOptionsForNode($node);
        $resolvedPath = realpath(TextEncoder::toStorageEncoding($resolvedOptions["PATH"]));

        return $resolvedPath.$url["path"];
    }

    /**
     * @param Node $node
     * @return array
     */
    public static function getResolvedOptionsForNode($node)
    {
        return [
            "TYPE"      => "fs",
            "PATH"      => $node->getRepository()->getContextOption($node->getContext(), "PATH"),
            "CHARSET"   => TextEncoder::getEncoding()
        ];
    }

    /**
     * @param string $tmpDir
     * @param string $tmpFile
     */
    public static function removeTmpFile($tmpDir, $tmpFile)
    {
        if(is_file($tmpFile)) unlink($tmpFile);
        if(is_dir($tmpDir)) rmdir($tmpDir);
    }

    protected static function closeWrapper()
    {
        if (self::$crtZip != null) {
            self::$crtZip = null;
            self::$currentListing  = null;
            self::$currentListingKeys = null;
            self::$currentListingIndex = null;
            self::$currentFileKey = null;
        }
    }

    /**
     * @param string $path
     * @param bool $persistent
     * @return mixed
     * @throws PydioException
     * @throws \Exception
     */
    public static function getRealFSReference($path, $persistent = false)
    {
        if (self::$crtZip != null) {
            $crtZip = self::$crtZip;
            self::$crtZip = null;
        }
        $realPath = self::initPath($path, "file");
        if (isSet($crtZip)) {
            self::$crtZip = $crtZip;
        } else {
            self::closeWrapper();
        }
        return $realPath;
    }

    /**
     * @param $url
     * @return bool
     */
    public static function isRemote($url)
    {
        return false;
    }

    /**
     * @param String $url
     * @return bool
     */
    public static function isSeekable($url)
    {
        if(strpos($url, ".zip/") !== false) return false;
        return true;
    }

    /**
     * @param string $path
     * @param resource $stream
     */
    public static function copyFileInStream($path, $stream)
    {
        $fp = fopen(self::getRealFSReference($path), "rb");
        if(!is_resource($fp)) return;
        while (!feof($fp)) {
            if(!ini_get("safe_mode")) @set_time_limit(60);
             $data = fread($fp, 4096);
             fwrite($stream, $data, strlen($data));
        }
        fclose($fp);
    }

    /**
     * @param string $path
     * @param number $chmodValue
     * @throws PydioException
     * @throws \Exception
     */
    public static function changeMode($path, $chmodValue)
    {
        $realPath = self::initPath($path, "file");
        @chmod($realPath, $chmodValue);
    }

    /**
     * Opens the strem
     *
     * @param String $path Maybe in the form "ajxp.fs://repositoryId/pathToFile"
     * @param String $mode
     * @param string $options
     * @param resource $context
     * @return bool
     */
    public function stream_open($path, $mode, $options, &$context)
    {
        try {
            $this->realPath = InputFilter::securePath(self::initPath($path, "file"));
        } catch (\Exception $e) {
            Logger::error(__CLASS__,"stream_open", "Error while opening stream $path (".$e->getMessage().")");
            return false;
        }
        if ($this->realPath == -1) {
            $this->fp = -1;
            return true;
        } else {
            $this->fp = fopen($this->realPath, $mode, $options);
            return ($this->fp !== false);
        }
    }

    /**
     * @param int $offset
     * @param int $whence
     * @return bool
     */
    public function stream_seek($offset , $whence = SEEK_SET)
    {
        return fseek($this->fp, $offset, $whence);
    }

    /**
     * @return int
     */
    public function stream_tell()
    {
        return ftell($this->fp);
    }

    /**
     * @return array|mixed|null
     */
    public function stream_stat()
    {
        if (is_resource($this->fp)) {
            $statValue = fstat($this->fp);
            FsAccessWrapper::$lastRealSize = false;
            return $statValue;
        }
        if (is_resource($this->dH)) {
            return fstat($this->dH);
        }
        if ($this->fp == -1) {
            return self::$currentListing[self::$currentFileKey];
        }
        return null;
    }

    /**
     * @param string $path
     * @param int $flags
     * @return array|null
     * @throws PydioException
     * @throws \Exception
     */
    public function url_stat($path, $flags)
    {
        // File and zip case
        $patchedPath = PathUtils::patchPathForBaseDir($path);
        if (ini_get("open_basedir") && preg_match('/__ZIP_EXTENSION__/', $patchedPath)) {
            // Zip Folder case
            self::$lastRealSize = false;
            $search = basename($path);
            $realBase = $this->initPath(dirname($path), "dir");
            if ($realBase == -1) {
                if (array_key_exists($search, self::$currentListing)) {
                    return self::$currentListing[$search];
                }
            }
        }
        if ($fp = @fopen($path, "r")) {
            $stat = fstat($fp);
            fclose($fp);
            return $stat;
        }
        // Folder case
        $real = $this->initPath($path, "dir", false, true);
        if ($real!=-1 && is_dir($real)) {
            return stat($real);
        }
        // Zip Folder case
        $search = basename($path);
        $realBase = $this->initPath(dirname($path), "dir");
        if ($realBase == -1) {
            if (array_key_exists($search, self::$currentListing)) {
                return self::$currentListing[$search];
            }
        }
        // 000 permission file
        if ($real != -1 && is_file($real)) {
            return stat($real);
        }
        // Handle symlinks!
        if ($real != -1 && is_link($real)) {
               $realFile = @readlink($real);
               if (is_file($realFile) || is_dir($realFile)) {
                   return stat($realFile);
               } else {
                // symlink is broken, delete it.
                   @unlink($real);
                   return null;
               }
           }

        // Non existing file
           return null;
    }

    /**
     * @param string $from
     * @param string $to
     * @return bool
     * @throws PydioException
     * @throws \Exception
     */
    public function rename($from, $to)
    {
        return rename($this->initPath($from, "file", false, true), $this->initPath($to, "file", false, true));
    }

    /**
     * @param int $count
     * @return string
     */
    public function stream_read($count)
    {
        return fread($this->fp, $count);
    }

    /**
     * @param string $data
     * @return int
     */
    public function stream_write($data)
    {
        fwrite($this->fp, $data, strlen($data));
        return strlen($data);
    }

    /**
     * @return bool
     */
    public function stream_eof()
    {
        return feof($this->fp);
    }

    public function stream_close()
    {
        if (isSet($this->fp) && $this->fp!=-1 && $this->fp!==false) {
            fclose($this->fp);
        }
    }

    /**
     * 
     */
    public function stream_flush()
    {
        if (isSet($this->fp) && $this->fp!=-1 && $this->fp!==false) {
            fflush($this->fp);
        }
    }

    /**
     * @param string $path
     * @return bool
     * @throws PydioException
     * @throws \Exception
     */
    public function unlink($path)
    {
        $this->realPath = $this->initPath($path, "file", false, true);
        return unlink($this->realPath);
    }

    /**
     * @param string $path
     * @param int $options
     * @return bool
     * @throws PydioException
     * @throws \Exception
     */
    public function rmdir($path, $options)
    {
        $this->realPath = $this->initPath($path, "file", false, true);
        return rmdir($this->realPath);
    }

    /**
     * @param string $path
     * @param int $mode
     * @param int $options
     * @return bool
     * @throws PydioException
     * @throws \Exception
     */
    public function mkdir($path, $mode, $options)
    {
        return mkdir($this->initPath($path, "file"), $mode);
    }

    /**
     * Readdir functions
     *
     * @param string $path
     * @param int $options
     * @return bool
     */
    public function dir_opendir ($path , $options )
    {
        $this->realPath = $this->initPath($path, "dir", true);
        if (is_string($this->realPath)) {
            $this->dH = @opendir($this->realPath);
        } else if ($this->realPath == -1) {
            $this->dH = -1;
        }
        return $this->dH !== false;
    }

    /**
     * Close dir handle
     */
    public function dir_closedir  ()
    {
        $this->closeWrapper();
        if ($this->dH == -1) {
            return;
        } else {
            closedir($this->dH);
        }
    }

    /**
     * @return bool|string
     */
    public function dir_readdir ()
    {
        if ($this->dH == -1) {
            if (isSet(self::$currentListingKeys[self::$currentListingIndex])) {
                self::$currentListingIndex++;
                return self::$currentListingKeys[self::$currentListingIndex - 1];
            } else {
                return false;
            }
        } else {
            return readdir($this->dH);
        }
    }

    /**
     * 
     */
    public function dir_rewinddir ()
    {
        if ($this->dH == -1) {
            self::$currentListingIndex = 0;
        } else {
            rewinddir($this->dH);
        }
    }

    /**
     * @return bool|float
     */
    public static function getLastRealSize()
    {
        if(empty(self::$lastRealSize)) return false;
        return self::$lastRealSize;
    }

    /**
     * @param $file
     * @return float|string
     */
    protected function getTrueSizeOnFileSystem($file)
    {
        if (!(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')) {
            $cmd = "stat -L -c%s ".escapeshellarg($file);
            $val = trim(`$cmd`);
            if (!is_numeric($val) || $val == -1) {
                // No stat on system
                $cmd = "ls -1s --block-size=1 ".escapeshellarg($file);
                $val = trim(`$cmd`);
                if (strlen($val) == 0 || floatval($val) == 0) {
                    // No block-size on system (probably busybox), try long output
                    $cmd = "ls -l ".escapeshellarg($file)."";
                    $arr = explode("/[\s]+/", `$cmd`);
                    $val = trim($arr[4]);
                    if (strlen($val) == 0 || floatval($val) == 0) {
                        // Still not working, get a value at least, not 0...
                        $val = sprintf("%u", filesize($file));
                    }
                }
            }
            return floatval($val);
        } else if (class_exists("COM")) {
            $fsobj = new \COM("Scripting.FileSystemObject");
            $f = $fsobj->GetFile($file);
            return floatval($f->Size);
        } else if (is_file($file)) {
            return exec('FOR %A IN ("'.$file.'") DO @ECHO %~zA');
        } else return sprintf("%u", filesize($file));
    }
}
