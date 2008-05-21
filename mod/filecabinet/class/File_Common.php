<?php

/**
 * Factory file for files.
 *
 * @author Matthew McNaney <matt at tux dot appstate dot edu>
 * @version $Id$
 */

class File_Common {
    var $id              = 0;
    var $file_name       = null;
    var $file_directory  = null;
    var $folder_id       = 0;
    var $file_type       = null;
    var $title           = null;
    var $description     = null;
    var $size            = 0;

    /**
     * PEAR upload object
     */
    var $_upload         = null;
    var $_errors         = array();
    var $_allowed_types  = null;
    var $_max_size       = 0;
    var $_ext            = null;

    function allowSize($size=null)
    {
        if (!isset($size)) {
            $size = $this->getSize();
        }

        return ($size <= $this->_max_size && $size <= ABSOLUTE_UPLOAD_LIMIT) ? true : false;
    }

    /**
     * Compares against a set of allowable extensions. This should not be
     * used alone as it is specific to File Cabinet. It is assumed you
     * have run PHPWS_File::checkMimeType first.
     */
    function allowType($ext=null)
    {
        if (!isset($ext)) {
            $ext = $this->_ext;
        }
        $ext = strtolower($ext);
        return in_array($ext, $this->_allowed_types);
    }

    function formatSize($size)
    {
        if ($size >= 1000000) {
            return round($size / 1000000, 2) . 'MB';
        } else {
            return round($size / 1000, 2) . 'K';
        }
    }

    function setMaxSize($max_size)
    {
        $this->_max_size = (int)$max_size;
    }


    function getSize($format=false)
    {
        if ($format) {
            return $this->formatSize($this->size);
        } else {
            return $this->size;
        }
    }


    /**
     * Tests file upload to determine if it may be saved to the server.
     * Returns true if so, false otherwise.
     * Called from Image_Manager's postImageUpload function and Cabinet_Action's
     * postDocument function.
     */
    function importPost($var_name, $use_folder=true, $ignore_missing_file=false)
    {
        require 'HTTP/Upload.php';

        if (!empty($_POST['folder_id'])) {
            $this->folder_id = (int)$_POST['folder_id'];
        } elseif (!$this->folder_id && $use_folder) {
            $this->_errors[] = PHPWS_Error::get(FC_MISSING_FOLDER, 'filecabinet', 'File_Common::importPost');
        }

        if (isset($_POST['title'])) {
            $this->setTitle($_POST['title']);
        } else {
            $this->title = null;
        }

        if (isset($_POST['alt'])) {
            $this->setAlt($_POST['alt']);
        }

        if (isset($_POST['description'])) {
            $this->setDescription($_POST['description']);
        } else {
            $this->description = null;
        }

        if ($this->id && $this->isVideo()) {
            if (isset($_POST['width'])) {
                $width = (int)$_POST['width'];
                if ($width > 20) {
                    $this->width = & $width;
                }
            }
            
            if (isset($_POST['height'])) {
                $height = (int)$_POST['height'];
                if ($height > 20) {
                    $this->height = & $height;
                }
            }
        }

        if (!empty($_FILES[$var_name]['error'])) {
            switch ($_FILES[$var_name]['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $this->_errors[] =  PHPWS_Error::get(PHPWS_FILE_SIZE, 'core', 'File_Common::getFiles');
                break;

            case UPLOAD_ERR_FORM_SIZE:
                $this->_errors[] = PHPWS_Error::get(FC_MAX_FORM_UPLOAD, 'filecabinet', 'PHPWS_Document::importPost', array($this->_max_size));
                return false;
                break;

            case UPLOAD_ERR_NO_FILE:
                // Missing file is not important for an update or if they specify to ignore it.
                if ($this->id || $ignore_missing_file) {
                    return true;
                } else {
                    $this->_errors[] = PHPWS_Error::get(FC_NO_UPLOAD, 'filecabinet', 'PHPWS_Document::importPost');
                    return false;
                }
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $this->_errors[] = PHPWS_Error::get(FC_MISSING_TMP, 'filecabinet', 'PHPWS_Document::importPost', array($this->_max_size));
                return false;
            }
        }

        // need to get language
        $oUpload = new HTTP_Upload('en');
        $this->_upload = $oUpload->getFiles($var_name);

        if (PEAR::isError($this->_upload)) {
            $this->_errors[] = $this->_upload();
            return false;
        }

        if ($this->_upload->isValid()) {
            $file_vars = $this->_upload->getProp();

            $this->setFilename($file_vars['real']);
            $this->_upload->setName($this->file_name);

            $this->setSize($file_vars['size']);

            $this->file_type = $file_vars['type'];
            if ($this->file_type == 'application/octet-stream') {
                $mime = PHPWS_File::getMimeType($file_vars['tmp_name']);
                if ($mime != $this->file_type) {
                    $this->file_type = & $mime;
                }
            }

            if (!PHPWS_File::checkMimeType($file_vars['tmp_name'], $file_vars['ext'])) {
                $this->_errors[] = PHPWS_Error::get(FC_FILE_TYPE_MISMATCH, 'filecabinet', 'File_Common::importPost', 
                                                    $file_vars['ext'] . ':' . PHPWS_File::getMimeType($file_vars['tmp_name']));
                return false;
            }

            if (!$this->allowType($file_vars['ext'])) {
                if ($this->_classtype == 'document') {
                    $this->_errors[] = PHPWS_Error::get(FC_DOCUMENT_WRONG_TYPE, 'filecabinet', 'File_Common::importPost');
                } elseif ($this->_classtype == 'image') {
                    $this->_errors[] = PHPWS_Error::get(FC_IMG_WRONG_TYPE, 'filecabinet', 'File_Common::importPost');
                } else {
                    $this->_errors[] = PHPWS_Error::get(FC_MULTIMEDIA_WRONG_TYPE, 'filecabinet', 'File_Common::importPost');
                }
                return false;
            }

            if ($this->size && !$this->allowSize()) {
                if ($this->_classtype == 'document') {
                    $this->_errors[] = PHPWS_Error::get(FC_DOCUMENT_SIZE, 'filecabinet', 'File_Common::importPost', array($this->size, $this->_max_size));
                } elseif ($this->_classtype == 'image') {
                    $this->_errors[] = PHPWS_Error::get(FC_IMG_SIZE, 'filecabinet', 'File_Common::importPost', array($this->size, $this->_max_size));
                } else {
                    $this->_errors[] = PHPWS_Error::get(FC_MULTIMEDIA_SIZE, 'filecabinet', 'File_Common::importPost', array($this->size, $this->_max_size));
                }
                return false;
            }

            if ($this->_classtype == 'image') {
                list($this->width, $this->height, $image_type, $image_attr) = getimagesize($this->_upload->upload['tmp_name']);
                
                $result = $this->prewriteResize();
                if (PEAR::isError($result)) {
                    $this->errors[] = $result;
                    return false;
                }
                
                $result = $this->prewriteRotate();
                if (PEAR::isError($result)) {
                    $this->errors[] = $result;
                    return false;
                }
            }

        } elseif ($this->_upload->isError()) {
            $this->_errors[] = $this->_upload->getMessage();
            return false;
        } elseif ($this->_upload->isMissing()) {
            $this->_errors[] = PHPWS_Error::get(FC_NO_UPLOAD, 'filecabinet', 'File_Common::importPost');
            return false;
        }

        return true;
    }


    function setDescription($description)
    {
        $this->description = PHPWS_Text::parseInput(strip_tags($description, '<em><strong><b><i><u>'));
    }

    function getDescription()
    {
        return PHPWS_Text::parseOutput($this->description);
    }

    function setDirectory($directory)
    {
        if (!preg_match('@/$@', $directory)) {
            $directory .= '/';
        }

        $this->file_directory = $directory;
    }

    function setFilename($filename)
    {
        $this->file_name = preg_replace('/[^\w\.]/', '_', $filename);
    }

    function setSize($size)
    {
        $this->size = (int)$size;
    }

    function setTitle($title)
    {
        $this->title = strip_tags($title);
    }

    /**
     * Writes the file to the server
     */
    function write($public=true)
    {
        if (!is_writable($this->file_directory)) {
            return PHPWS_Error::get(FC_BAD_DIRECTORY, 'filecabinet', 'File_Common::write', $this->file_directory);
        }
        
        if (!$this->id && is_file($this->getPath())) {
            $this->file_name = mktime() . $this->file_name;
            PHPWS_Error::log(FC_DUPLICATE_FILE, 'filecabinet', 'File_Common::write', $this->getPath());
        }

        if ($this->_upload) {
            $this->_upload->setName($this->file_name);
            $directory = preg_replace('@[/\\\]$@', '', $this->file_directory);

            if (!is_dir($directory)) {
                return PHPWS_Error::get(FC_BAD_DIRECTORY, 'filecabinet', 'File_Common::write', $directory);
            }

            $moved = $this->_upload->moveTo($directory);
            if (!PEAR::isError($moved)) {
                if ($public) {
                    chmod($directory . '/' . $moved, 0644);
                } else {
                    chmod($directory . '/' . $moved, 0640);
                }
                return $moved;
            }
        }

        return true;
    }

    function getPath()
    {
        return $this->file_directory . $this->file_name;
    }

    function logErrors()
    {
        if ( !empty($this->_errors) && is_array($this->_errors) ) {
            foreach ($this->_errors as $error) {
                PHPWS_Error::log($error);
            }
        }
    }

    function getErrors()
    {
        $foo = array();
        if ( !empty($this->_errors) && is_array($this->_errors) ) {
            foreach ($this->_errors as $error) {
                $foo[] = $error->getMessage();
            }
        }
        return $foo;
    }

    function printErrors()
    {
        $foo = $this->getErrors();
        return implode('<br />', $foo);
    }

    function loadFileSize()
    {
        if (empty($this->file_directory) ||
            empty($this->file_name) ||
            !is_file($this->getPath())) {
            return false;
        }

        $this->size = filesize($this->getPath());
    }

    function getVideoTypes()
    {
        static $video_types = null;

        if (empty($video_types)) {
            $video_types = explode(',', FC_VIDEO_TYPES);
        }

        return $video_types;
    }

    /**
     * Checks if a file is a known video file type
     */
    function isVideo()
    {
        if ($this->_classtype != 'multimedia') {
            return false;
        }

        $videos = $this->getVideoTypes();
        $ext = $this->getExtension();

        return in_array($ext, $videos);
    } 

    function dropExtension()
    {
        $last_dot = strrpos($this->file_name, '.');
        return substr($this->file_name, 0, $last_dot);
    }

    function getExtension()
    {
        if (!$this->_ext) {
            $this->loadExtension();
        }

        return $this->_ext;
    }

    function loadExtension()
    {
        if (!$this->_ext && $this->file_name) {
            $this->_ext = PHPWS_File::getFileExtension($this->file_name);
        }
    }

    /**
     * Deletes a file database entry, its directory, and its file association
     * Requires each to have a deleteAssoc function.
     */
    function commonDelete()
    {
        if (!$this->id) {
            return false;
        }

        switch ($this->_classtype) {
        case 'image':
            $db = new PHPWS_DB('images');
            break;

        case 'document':
            $db = new PHPWS_DB('documents');
            break;

        case 'multimedia':
            $db = new PHPWS_DB('multimedia');
            break;
        }

        $db->addWhere('id', $this->id);
        $result = $db->delete();

        if (PEAR::isError($result)) {
            return $result;
        }

        $path = $this->getPath();

        if (!@unlink($path)) {
            PHPWS_Error::log(FC_COULD_NOT_DELETE, 'filecabinet', 'File_Common::commonDelete', $path);
        }

        PHPWS_Error::logIfError($this->deleteAssoc());
        return true;
    }

    function moveToFolder()
    {
        if (empty($_POST['move_to_folder']) || $_POST['move_to_folder'] == $this->folder_id) {
            return false;
        }

        $new_folder = new Folder($_POST['move_to_folder']);
        $old_folder = new Folder($this->folder_id);

        if ($new_folder->ftype != $old_folder->ftype) {
            return false;
        }

        $dest_dir = $new_folder->getFullDirectory();

        $source = $this->getPath();
        $dest   = $dest_dir . $this->file_name;

        if ($this->_classtype != 'document') {
            $stn  = $this->thumbnailPath();
            $dtn  = $dest_dir . 'tn/' . $this->tnFileName();
        }

        // A embedded file just needs thumbnails moved
        if ($this->_classtype == 'multimedia' && $this->embedded) {
            $this->folder_id      = $new_folder->id;
            $this->file_directory = $dest_dir;
            if (@copy($stn, $dtn)) {
                if (!PHPWS_Error::logIfError($this->save(false, false))) {
                    // no error occurs, unlink the source file and thumbnail
                    unlink($stn);
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        // If file already exists in folder, don't copy.
        if (is_file($dest)) {
            return false;
        }
        
        // copy the source file to the new destination
        if (@copy($source, $dest)) {
            $this->folder_id      = $new_folder->id;
            $this->file_directory = $dest_dir;
            switch ($this->_classtype) {
            case 'image':
                // copy the thumbnail
                if (@copy($stn, $dtn)) {
                    if (!PHPWS_Error::logIfError($this->save(false, false, false))) {
                        // no error occurs, unlink the source file and thumbnail
                        unlink($source);
                        unlink($stn);
                        return true;
                    } else {
                        // error occurred, delete the copy file
                        unlink($dest);
                        return false;
                    }
                } else {
                    // thumbnail copy failed, remove copy
                    @unlink($dest);
                    return false;
                }
                break;

            case 'document':
                if (!PHPWS_Error::logIfError($this->save(false, false))) {
                    // no error occurs, unlink the source file
                    unlink($source);
                    return true;
                } else {
                    // error occurred, delete the copy file
                    unlink($dest);
                    return false;
                }
                break;

            case 'multimedia':
                // copy the thumbnail
                if (@copy($stn, $dtn)) {
                    if (!PHPWS_Error::logIfError($this->save(false, false))) {
                        // no error occurs, unlink the source file and thumbnail
                        unlink($source);
                        unlink($stn);
                        return true;
                    } else {
                        // error occurred, delete the copy file
                        unlink($dest);
                        return false;
                    }
                } else {
                    // thumbnail copy failed, remove copy
                    @unlink($dest);
                    return false;
                }
                break;

            }
        }
    }
}
?>