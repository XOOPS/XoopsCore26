<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

namespace Xoops\Core;

/**
 * XOOPS file uploader
 *
 * @copyright   XOOPS Project (http://xoops.org)
 * @license     GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package     class
 * @since       2.0.0
 * @author      Kazumi Ono (http://www.myweb.ne.jp/, http://jp.xoops.org/)
 * @author      Taiwen Jiang <phppp@users.sourceforge.net>
 */


/**
 * XOOPS file uploader
 *
 * Example of usage:
 * <code>
 * $allowed_mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png');
 * $maxfilesize = 50000;
 * $maxfilewidth = 120;
 * $maxfileheight = 120;
 * $uploader = new Xoops\Core\MediaUploader(
 *      '/home/xoops/uploads',
 *      $allowed_mimetypes,
 *      $maxfilesize,
 *      $maxfilewidth,
 *      $maxfileheight
 * );
 * if ($uploader->fetchMedia($_POST['uploade_file_name'])) {
 *        if (!$uploader->upload()) {
 *           echo $uploader->getErrors();
 *        } else {
 *           echo '<h4>File uploaded successfully!</h4>'
 *           echo 'Saved as: ' . $uploader->getSavedFileName() . '<br />';
 *           echo 'Full path: ' . $uploader->getSavedDestination();
 *        }
 * } else {
 *        echo $uploader->getErrors();
 * }
 * </code>
 *
 * @category  Xoops\Core\MediaUploader
 * @package   MediaUploader
 * @author    Kazumi Ono (http://www.myweb.ne.jp/, http://jp.xoops.org/)
 * @author    Taiwen Jiang <phppp@users.sourceforge.net>
 * @copyright 2003-2014 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 */
class MediaUploader
{
    /**
     * Flag indicating if unrecognized mimetypes should be allowed (use with precaution ! may lead to security issues )
     *
     * @var bool
     */
    public $allowUnknownTypes = false;

    /**
     * @var string
     */
    public $mediaName;

    /**
     * @var string
     */
    public $mediaType;

    /**
     * @var int
     */
    public $mediaSize;

    /**
     * @var string
     */
    public $mediaTmpName;

    /**
     * @var string
     */
    public $mediaError;

    /**
     * @var string
     */
    public $mediaRealType = '';

    /**
     * @var string
     */
    public $uploadDir = '';

    /**
     * @var array
     */
    public $allowedMimeTypes = array();

    /**
     * @var array
     */
    public $deniedMimeTypes = array(
        'application/x-httpd-php'
    );

    /**
     * @var int
     */
    public $maxFileSize = 0;

    /**
     * @var int
     */
    public $maxWidth;

    /**
     * @var int
     */
    public $maxHeight;

    /**
     * @var string
     */
    public $targetFileName;

    /**
     * @var
     */
    public $prefix;

    /**
     * @var array
     */
    public $errors = array();

    /**
     * @var string
     */
    public $savedDestination;

    /**
     * @var string
     */
    public $savedFileName;

    /**
     * @var array|mixed
     */
    //public $extensionToMime = array();

    /**
     * @var bool
     */
    public $checkImageType = true;

    /**
     * @var array
     */
    public $extensionsToBeSanitized = array(
        'php', 'phtml', 'phtm', 'php3', 'php4', 'cgi', 'pl', 'asp', 'php5'
    );

    /**
     * extensions needed image check (anti-IE Content-Type XSS)
     *
     * @var array
     */
    public $imageExtensions = array(
        1 => 'gif', 2 => 'jpg', 3 => 'png', 4 => 'swf', 5 => 'psd', 6 => 'bmp', 7 => 'tif', 8 => 'tif', 9 => 'jpc',
        10 => 'jp2', 11 => 'jpx', 12 => 'jb2', 13 => 'swf', 14 => 'iff', 15 => 'wbmp', 16 => 'xbm'
    );

    /**
     * Constructor
     *
     * @param string $uploadDir        upload directory
     * @param array  $allowedMimeTypes allowed mime types
     * @param int    $maxFileSize      max size
     * @param int    $maxWidth         max width
     * @param int    $maxHeight        max height
     */
    public function __construct($uploadDir, $allowedMimeTypes, $maxFileSize = 0, $maxWidth = null, $maxHeight = null)
    {
        if (is_array($allowedMimeTypes)) {
            $this->allowedMimeTypes = $allowedMimeTypes;
        }
        $this->uploadDir = $uploadDir;
        $this->maxFileSize = (int)($maxFileSize);
        if (isset($maxWidth)) {
            $this->maxWidth = (int)($maxWidth);
        }
        if (isset($maxHeight)) {
            $this->maxHeight = (int)($maxHeight);
        }
    }

    /**
     * Fetch the uploaded file
     *
     * @param string $media_name Name of the file field
     * @param int    $index      Index of the file (if more than one uploaded under that name)
     *
     * @return bool
     */
    public function fetchMedia($media_name, $index = null)
    {
        if (!isset($_FILES[$media_name])) {
            $this->setErrors(\XoopsLocale::E_FILE_NOT_FOUND);
            return false;
        } else {
            if (is_array($_FILES[$media_name]['name']) && isset($index)) {
                $index = (int)($index);
                $this->mediaName = $_FILES[$media_name]['name'][$index];
                $this->mediaType = $_FILES[$media_name]['type'][$index];
                $this->mediaSize = $_FILES[$media_name]['size'][$index];
                $this->mediaTmpName = $_FILES[$media_name]['tmp_name'][$index];
                $this->mediaError = !empty($_FILES[$media_name]['error'][$index])
                    ? $_FILES[$media_name]['error'][$index] : 0;
            } else {
                $media_name = $_FILES[$media_name];
                $this->mediaName = $media_name['name'];
                $this->mediaType = $media_name['type'];
                $this->mediaSize = $media_name['size'];
                $this->mediaTmpName = $media_name['tmp_name'];
                $this->mediaError = !empty($media_name['error']) ? $media_name['error'] : 0;
            }
        }

        $path_parts = pathinfo($this->mediaName);
        $ext = (isset($path_parts['extension'])) ? $path_parts['extension'] : '';
        $this->mediaRealType = \Xoops\Core\MimeTypes::findType($ext);

        $this->errors = array();
        if ((int)($this->mediaSize) < 0) {
            $this->setErrors(\XoopsLocale::E_INVALID_FILE_SIZE);
            return false;
        }
        if ($this->mediaName == '') {
            $this->setErrors(\XoopsLocale::E_FILE_NAME_MISSING);
            return false;
        }
        if ($this->mediaTmpName === 'none' || !is_uploaded_file($this->mediaTmpName)) {
            $this->setErrors(\XoopsLocale::NO_FILE_UPLOADED);
            return false;
        }
        if ($this->mediaError > 0) {
            $this->setErrors(sprintf(\XoopsLocale::EF_UNEXPECTED_ERROR, $this->mediaError));
            return false;
        }
        return true;
    }

    /**
     * Set the target filename
     *
     * @param string $value file name
     *
     * @return void
     */
    public function setTargetFileName($value)
    {
        $this->targetFileName = (string)(trim($value));
    }

    /**
     * Set the prefix
     *
     * @param string $value prefix
     *
     * @return void
     */
    public function setPrefix($value)
    {
        $this->prefix = (string)(trim($value));
    }

    /**
     * Get the uploaded filename
     *
     * @return string
     */
    public function getMediaName()
    {
        return $this->mediaName;
    }

    /**
     * Get the type of the uploaded file
     *
     * @return string
     */
    public function getMediaType()
    {
        return $this->mediaType;
    }

    /**
     * Get the size of the uploaded file
     *
     * @return int
     */
    public function getMediaSize()
    {
        return $this->mediaSize;
    }

    /**
     * Get the temporary name that the uploaded file was stored under
     *
     * @return string
     */
    public function getMediaTmpName()
    {
        return $this->mediaTmpName;
    }

    /**
     * Get the saved filename
     *
     * @return string
     */
    public function getSavedFileName()
    {
        return $this->savedFileName;
    }

    /**
     * Get the destination the file is saved to
     *
     * @return string
     */
    public function getSavedDestination()
    {
        return $this->savedDestination;
    }

    /**
     * Check the file and copy it to the destination
     *
     * @param int $chmod file permissions to set
     *
     * @return bool
     */
    public function upload($chmod = 0644)
    {
        if ($this->uploadDir == '') {
            $this->setErrors(\XoopsLocale::E_UPLOAD_DIRECTORY_NOT_SET);
            return false;
        }
        if (!is_dir($this->uploadDir)) {
            $this->setErrors(sprintf(\XoopsLocale::EF_DIRECTORY_NOT_OPENED, $this->uploadDir));
            return false;
        }
        if (!is_writeable($this->uploadDir)) {
            $this->setErrors(sprintf(\XoopsLocale::EF_DIRECTORY_WITH_WRITE_PERMISSION_NOT_OPENED, $this->uploadDir));
            return false;
        }
        $this->sanitizeMultipleExtensions();

        if (!$this->checkMaxFileSize()) {
            return false;
        }
        if (!$this->checkMaxWidth()) {
            return false;
        }
        if (!$this->checkMaxHeight()) {
            return false;
        }
        if (!$this->checkMimeType()) {
            return false;
        }
        if (!$this->checkImageType()) {
            return false;
        }
        if (count($this->errors) > 0) {
            return false;
        }
        return $this->copyFile($chmod);
    }

    /**
     * Copy the file to its destination
     *
     * @param int $chmod file permissions to set
     *
     * @return bool
     */
    protected function copyFile($chmod)
    {
        $matched = array();
        if (!preg_match("/\.([a-zA-Z0-9]+)$/", $this->mediaName, $matched)) {
            $this->setErrors(\XoopsLocale::E_INVALID_FILE_NAME);
            return false;
        }
        if (isset($this->targetFileName)) {
            $this->savedFileName = $this->targetFileName;
        } else {
            if (isset($this->prefix)) {
                $this->savedFileName = uniqid($this->prefix) . '.' . strtolower($matched[1]);
            } else {
                $this->savedFileName = strtolower($this->mediaName);
            }
        }

        $this->savedDestination = $this->uploadDir . '/' . $this->savedFileName;
        if (!move_uploaded_file($this->mediaTmpName, $this->savedDestination)) {
            $this->setErrors(sprintf(\XoopsLocale::EF_FILE_NOT_SAVED_TO, $this->savedDestination));
            return false;
        }
        // Check IE XSS before returning success
        $ext = strtolower(substr(strrchr($this->savedDestination, '.'), 1));
        if (in_array($ext, $this->imageExtensions)) {
            $info = @getimagesize($this->savedDestination);
            if ($info === false || $this->imageExtensions[(int)$info[2]] != $ext) {
                $this->setErrors(\XoopsLocale::E_SUSPICIOUS_IMAGE_UPLOAD_REFUSED);
                @unlink($this->savedDestination);
                return false;
            }
        }
        @chmod($this->savedDestination, $chmod);
        return true;
    }

    /**
     * Is the file the right size?
     *
     * @return bool
     */
    public function checkMaxFileSize()
    {
        if (!isset($this->maxFileSize)) {
            return true;
        }
        if ($this->mediaSize > $this->maxFileSize) {
            $this->setErrors(sprintf(\XoopsLocale::EF_FILE_SIZE_TO_LARGE, $this->maxFileSize, $this->mediaSize));
            return false;
        }
        return true;
    }

    /**
     * Is the picture the right width?
     *
     * @return bool
     */
    public function checkMaxWidth()
    {
        if (!isset($this->maxWidth)) {
            return true;
        }
        if (false !== $dimension = getimagesize($this->mediaTmpName)) {
            if ($dimension[0] > $this->maxWidth) {
                $this->setErrors(sprintf(\XoopsLocale::EF_FILE_WIDTH_TO_LARGE, $this->maxWidth, $dimension[0]));
                return false;
            }
        } else {
            trigger_error(sprintf(\XoopsLocale::EF_IMAGE_SIZE_NOT_FETCHED, $this->mediaTmpName), E_USER_WARNING);
        }
        return true;
    }

    /**
     * Is the picture the right height?
     *
     * @return bool
     */
    public function checkMaxHeight()
    {
        if (!isset($this->maxHeight)) {
            return true;
        }
        if (false !== $dimension = getimagesize($this->mediaTmpName)) {
            if ($dimension[1] > $this->maxHeight) {
                $this->setErrors(sprintf(\XoopsLocale::EF_FILE_HEIGHT_TO_LARGE, $this->maxHeight, $dimension[1]));
                return false;
            }
        } else {
            trigger_error(sprintf(\XoopsLocale::EF_IMAGE_SIZE_NOT_FETCHED, $this->mediaTmpName), E_USER_WARNING);
        }
        return true;
    }

    /**
     * Check whether or not the uploaded file type is allowed
     *
     * @return bool
     */
    public function checkMimeType()
    {
        if (empty($this->mediaRealType) && empty($this->allowUnknownTypes)) {
            $this->setErrors(\XoopsLocale::E_FILE_TYPE_REJECTED);
            return false;
        }

        if ((!empty($this->allowedMimeTypes)
            && !in_array($this->mediaRealType, $this->allowedMimeTypes))
            || (!empty($this->deniedMimeTypes)
            && in_array($this->mediaRealType, $this->deniedMimeTypes))
        ) {
            $this->setErrors(sprintf(\XoopsLocale::EF_FILE_MIME_TYPE_NOT_ALLOWED, $this->mediaType));
            return false;
        }
        return true;
    }

    /**
     * Check whether or not the uploaded image type is valid
     *
     * @return bool
     */
    public function checkImageType()
    {
        if (empty($this->checkImageType)) {
            return true;
        }

        if (('image' === substr($this->mediaType, 0, strpos($this->mediaType, '/')))
            || (!empty($this->mediaRealType)
            && 'image' === substr($this->mediaRealType, 0, strpos($this->mediaRealType, '/')))
        ) {
            if (!@getimagesize($this->mediaTmpName)) {
                $this->setErrors(\XoopsLocale::E_INVALID_IMAGE_FILE);
                return false;
            }
        }
        return true;
    }

    /**
     * Sanitize executable filename with multiple extensions
     *
     * @return void
     */
    public function sanitizeMultipleExtensions()
    {
        if (empty($this->extensionsToBeSanitized)) {
            return;
        }

        $patterns = array();
        $replaces = array();
        foreach ($this->extensionsToBeSanitized as $ext) {
            $patterns[] = "/\." . preg_quote($ext) . "\./i";
            $replaces[] = "_" . $ext . ".";
        }
        $this->mediaName = preg_replace($patterns, $replaces, $this->mediaName);
    }

    /**
     * Add an error
     *
     * @param string $error message
     *
     * @return void
     */
    public function setErrors($error)
    {
        $this->errors[] = trim($error);
    }

    /**
     * Get generated errors
     *
     * @param bool $ashtml Format using HTML?
     *
     * @return array |string    Array of array messages OR HTML string
     */
    public function getErrors($ashtml = true)
    {
        if (!$ashtml) {
            return $this->errors;
        } else {
            $ret = '';
            if (count($this->errors) > 0) {
                $ret = '<h4>'
                . sprintf(\XoopsLocale::EF_ERRORS_RETURNED_WHILE_UPLOADING_FILE, $this->mediaName) . '</h4>';
                foreach ($this->errors as $error) {
                    $ret .= $error . '<br />';
                }
            }
            return $ret;
        }
    }
}
