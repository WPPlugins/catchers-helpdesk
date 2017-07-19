<?php
namespace StgHelpdesk\Helpers;

use ZipArchive;
use StgHelpdesk\Helpers\Stg_Helper_Logger;

class Stg_Helper_UploadFiles
{
    /**
     * Name form field
     * @var string
     */
    protected static $field = 'stgh_attachment';

    protected static $post_id;
    protected static $parent_id;

    /**
     * Handler form uploads file
     * @param $post_id
     * @param int $parent_id
     * @return bool
     */
    public static function handleUploadsForm($post_id, $parent_id = 0)
    {
        if (!empty($_FILES[self::$field]['name']['0'])) {
            $field = self::$field;

            if (self::_indexMultiUploads()) {
                for ($i = 0; isset($_FILES["{$field}_$i"]); ++$i) {
                    $index = "{$field}_$i";

                    if (!self::uploadFileForm($index, $post_id, $parent_id)) {
                        continue;
                        //return false;
                    }
                }
            } else {
                if (!self::uploadFileForm($field, $post_id, $parent_id)) {
                    return false;
                }
            }

            return true;
        }
    }

    /**
     * Upload file form
     * @param $field
     * @param $post_id
     * @param int $parent_id
     * @return bool
     */
    public static function uploadFileForm($field, $post_id, $parent_id = 0)
    {
        if (!isset($_FILES[$field])) {
            return false;
        }

        $upload = self::uploadFile($_FILES[$field], (int)$post_id, $parent_id);

        if ($upload && !is_wp_error($upload)) {
            return true;
        } else {
            if (is_wp_error($upload)) {
                $msg_error = $upload->get_error_message();
                $_REQUEST['stgh_message'] = getNotificationMarkup('failure',
                    __($msg_error, STG_HELPDESK_TEXT_DOMAIN_NAME));
            } else if (empty($_REQUEST['stgh_message'])) {
                $_REQUEST['stgh_message'] = getNotificationMarkup('failure',
                    __('File uploading failed', STG_HELPDESK_TEXT_DOMAIN_NAME));
            }

            return false;
        }
    }

    /**
     * Upload file
     * (File array structure analog $_FILES)
     * @param $file
     * @param $post_id
     * @param int $parent_id
     * @param string $action
     * @return bool|int|\WP_Error
     */
    public static function uploadFile($file, $post_id, $parent_id = 0, $action = 'wp_handle_upload')
    {
        /* Load media uploader related files. */
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');

        if (empty($post_id)) {
            return false;
        }

        if (get_post_type($post_id) != STG_HELPDESK_POST_TYPE && get_post_type($post_id) != STG_HELPDESK_COMMENTS_POST_TYPE) {
            $_REQUEST['stgh_message'] = getNotificationMarkup('failure',
                __('This post type is not supported', STG_HELPDESK_TEXT_DOMAIN_NAME));

            return false;
        }

        if (!self::isEnableAttachment() && !stgh_get_option('helpcatcher_enable_attachment', false)) {
            $_REQUEST['stgh_message'] = getNotificationMarkup('failure',
                __('File uploads are disabled', STG_HELPDESK_TEXT_DOMAIN_NAME));

            return false;
        }

        if (self::isFileExist($file['tmp_name'], $post_id)) {
            $_REQUEST['stgh_message'] = getNotificationMarkup('info',
                __('You are trying to add the same files', STG_HELPDESK_TEXT_DOMAIN_NAME));
            return false;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::getAllowTypeNoZip()) && $ext != 'zip') {
            $zip = new ZipArchive;
            $tmpName = $file['name'] . '.zip';
            $tmpFile = get_temp_dir() . DIRECTORY_SEPARATOR . $tmpName;

            if ($zip->open($tmpFile, ZipArchive::CREATE) === true) {
                $zip->addFile($file['tmp_name'], $file['name']);
                $zip->close();

                $action = 'wp_handle_local_upload'; //action local
                $file['tmp_name'] = $tmpFile;
                $file['name'] = $tmpName;
                $file['type'] = 'application/zip';
                $file['size'] = filesize($tmpFile);
            }
        }

        $time = current_time('mysql');
        if ($post = get_post($post_id)) {
            if (substr($post->post_date, 0, 4) > 0)
                $time = $post->post_date;
        }

        self::$post_id = $post_id;
        self::$parent_id = $parent_id;

        //filter upload_dir
        add_filter('upload_dir', 'stgh_filter_upload_dir');

        $fileUpload = wp_handle_upload($file, array('test_form' => false, 'action' => $action), $time);

        //filter upload_dir
        remove_filter('upload_dir', 'stgh_filter_upload_dir');

        if (isset($fileUpload['error'])) {
            $logger = Stg_Helper_Logger::getLogger('upload_files');
            $logger->log($file);
            $logger->log($fileUpload);
            return new \WP_Error('upload_error', $fileUpload['error']);
        }

        $filename = $file['name'];
        $attachment = array(
            'post_mime_type' => $fileUpload['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_content' => '',
            'post_parent' => $post_id,
            'post_status' => 'inherit',
            'guid' => $fileUpload['file']
        );

        $attachment_id = wp_insert_attachment($attachment, $fileUpload['url']);

        $attachment_data = wp_generate_attachment_metadata($attachment_id, $filename);

        wp_update_attachment_metadata($attachment_id, $attachment_data);

        if (0 < intval($attachment_id)) {
            return $attachment_id;
        }

        return false;
    }

    /**
     * Check file exists
     *
     * @param $file
     * @param $post_id
     * @return bool
     */
    public static function isFileExist($file, $post_id)
    {
        if (!$file)
            return false;
        $attachments = self::getAttachments($post_id);
        if ($attachments)
            foreach ($attachments as $attach) {
                if (md5_file($file) == md5_file($attach['url'])) {
                    return true;
                }
            }

        return false;
    }

    /**
     * Get attachments post
     * @param $post_id
     * @return array
     */
    public static function getAttachments($post_id)
    {
        $post = get_post($post_id);

        if (is_null($post)) {
            return array();
        }

        $args = array(
            'post_parent' => $post_id,
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'cache_results' => false,
            'update_post_term_cache' => false,
            'update_post_meta_cache' => false,
        );

        $attachments = new \WP_Query($args);
        $list = array();

        if (empty($attachments->posts)) {
            return array();
        }

        foreach ($attachments->posts as $key => $attachment) {
            $list[$attachment->ID] = array('id' => $attachment->ID, 'name' => $attachment->post_title, 'url' => $attachment->guid);
        }

        return $list;
    }

    /**
     * Html block list attachments for downloads
     * @param $post_id
     * @return string
     */
    public static function getAttachmentsBlock($post_id)
    {
        $attachments = Stg_Helper_UploadFiles::getAttachments($post_id);
        Stg_Helper_Template::getTemplate('stg-attachements-block', array('attachments' => $attachments));
    }

    /**
     * Handler request page ticket attachment
     */
    public static function handlerPageAttachment()
    {
        $attachment_id = get_query_var('ticket-attachment');

        if (!empty($attachment_id)) {
            $attachment = get_post($attachment_id);

            if (empty($attachment)) {
                status_header(404);

                include(get_query_template('404'));
                die();
            }

            if ('attachment' !== $attachment->post_type) {
                wp_die(__('The file you requested is not a valid attachment', STG_HELPDESK_TEXT_DOMAIN_NAME));
            }

            if (empty($attachment->post_parent)) {
                wp_die(__('The attachment you requested is not attached to any ticket', STG_HELPDESK_TEXT_DOMAIN_NAME));
            }

            $parent = get_post($attachment->post_parent);
            $parent_id = empty($parent->post_parent) ? $parent->ID : $parent->post_parent;

            if (!stgh_check_user_access($parent_id)) {
                wp_die(__('You are not allowed to view this attachment', STG_HELPDESK_TEXT_DOMAIN_NAME));
            }

            $homePath = self::get_home_path();
            $siteUrl = get_site_url();

            $attachmentPath = $attachment->guid;

            $attachmentUrl = wp_get_attachment_url( $attachment_id );

            if(!is_readable($attachmentPath))
            {
                if(strpos($attachmentUrl,$siteUrl) === 0)
                {
                    $attachmentPathAlt = str_replace($siteUrl."/",$homePath,$attachmentUrl);
                }else
                {
                    $attachmentPathAlt = false;
                }

                if(!is_readable($attachmentPathAlt)){

                    $logger = Stg_Helper_Logger::getLogger('attach.error');
                    $logger->log("Not found:" );
                    $logger->log($attachmentPath);
                    $logger->log($attachmentPathAlt);
                    $logger->log($attachmentUrl);
                    $logger->log($attachment);

                    status_header(404);
                    include(get_query_template('404'));
                    die();
                }
                else{
                    $attachmentPath = $attachmentPathAlt;
                }
            }

            header("Content-Type: $attachment->post_mime_type");
            header("Content-Disposition: attachment; filename=\"" . basename($attachment->guid) . "\"");
            readfile($attachmentPath);
            die();
        }
    }

    /**
     * Has attachments post
     * @param $post_id
     * @return bool
     */
    public static function hasAttachments($post_id)
    {
        $attachments = self::getAttachments($post_id);

        if (empty($attachments)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get allow file types (not zip in upload)
     * @return array
     */
    public static function getAllowTypeNoZip()
    {
        return array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'pps', 'ppsx', 'odt', 'xls', 'xlsx',
            'mp3', 'm4a', 'ogg', 'wav', 'mp4', 'm4v', 'mov', 'wmv', 'avi', 'mpg', 'ogv', '3gp', '3g2', 'zip');
    }

    /**
     * Get allow file types
     * @return array
     */
    public static function getAllowType()
    {
        $mimes = wp_get_mime_types();
        $allow = array_values($mimes);
        return $allow;
    }

    /**
     * Accept list form types
     * @return string
     */
    public static function getAllowTypeAccept()
    {
        $types = self::getAllowType();
        $result = '';

        foreach ($types as $item) {
            $result .= $item . ',';
        }

        return $result;
    }

    /**
     * Code allow list form types
     * @return string
     */
    public static function getAllowTypeCode()
    {
        $types = self::getAllowType();
        $result = '';

        foreach ($types as $item) {
            $result .= '<code>.' . $item . ',</code>';
        }

        return $result;
    }

    /**
     * Extremely simple function to get human filesize.
     * @param $bytes
     * @param int $decimals
     * @return string
     */
    public static function getHumanFilesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = (int)floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    /**
     * Delete post attachments
     * @param $post_id
     */
    public static function deleteAttachments($post_id)
    {
        $attachments = self::getAttachments($post_id);

        if (!empty($attachments)) {
            foreach ($attachments as $id => $attachment) {
                wp_delete_attachment($id, true);
            }
        }
    }

    /**
     * Is enable attachment to settings
     * @return bool
     */
    public static function isEnableAttachment()
    {
        return stgh_get_option('stgh_enable_attachment', false) ? true : false;
    }


    /**
     * Get field name
     * @return string
     */
    public static function getFieldName()
    {
        return self::$field;
    }

    /**
     * Reference message code error
     * @param $code
     * @return string
     */
    public static function getCodeErrorMessage($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = __('The uploaded file exceeds the upload_max_filesize directive in php.ini', STG_HELPDESK_TEXT_DOMAIN_NAME);
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = __('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', STG_HELPDESK_TEXT_DOMAIN_NAME);
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = __('The uploaded file was only partially uploaded', STG_HELPDESK_TEXT_DOMAIN_NAME);;
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = __('No file was uploaded', STG_HELPDESK_TEXT_DOMAIN_NAME);
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = __('Missing a temporary folder', STG_HELPDESK_TEXT_DOMAIN_NAME);
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = __('Failed to write file to disk', STG_HELPDESK_TEXT_DOMAIN_NAME);
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = __('File upload stopped by extension', STG_HELPDESK_TEXT_DOMAIN_NAME);
                break;
            default:
                $message = __('Unknown upload error', STG_HELPDESK_TEXT_DOMAIN_NAME);
                break;
        }
        return $message;
    }

    /**
     * Change upload dir for ticket post type
     * @param $upload
     * @return mixed
     */
    public static function filterUploadDir($upload)
    {
        if (!self::isEnableAttachment()) {
            return $upload;
        }

        $ticket_id = self::$parent_id ? self::$parent_id : self::$post_id;

        if (get_post_type($ticket_id) != STG_HELPDESK_POST_TYPE && get_post_type($ticket_id) != STG_HELPDESK_COMMENTS_POST_TYPE) {
            return $upload;
        }

        $subdir = "/stg-helpdesk/ticket_$ticket_id";

        $dir = $upload['basedir'] . $subdir;
        $url = $upload['baseurl'] . $subdir;

        $upload['path'] = $dir;
        $upload['url'] = $url;
        $upload['subdir'] = $subdir;

        if (!is_dir($dir)) {
            self::_createUploadDir($dir);
        } else {
            self::_protectUploadDir($dir);
        }

        return $upload;
    }

    /**
     * @param $dir
     * @return bool
     */
    protected static function _createUploadDir($dir)
    {
        $make = mkdir($dir, 0766, true);

        if (true === $make) {
            self::_protectUploadDir($dir);
        }

        return $make;
    }

    /**
     * @param $dir
     */
    protected static function _protectUploadDir($dir)
    {
        $filename = $dir . '/.htaccess';

        if (!file_exists($filename)) {

            $data = 'Options -Indexes' . PHP_EOL . 'deny from all';

            $file = fopen($filename, 'a+');
            fwrite($file, $data);
            fclose($file);
        }
    }

    /**
     * @return bool
     */
    protected static function _indexMultiUploads()
    {

        $files_index = self::$field;

        if (!is_array($_FILES[$files_index]['name'])) {
            return false;
        }

        foreach ($_FILES[$files_index]['name'] as $id => $name) {
            $index = $files_index . '_' . $id;
            $_FILES[$index]['name'] = $name;
        }

        foreach ($_FILES[$files_index]['type'] as $id => $type) {
            $index = $files_index . '_' . $id;
            $_FILES[$index]['type'] = $type;
        }

        foreach ($_FILES[$files_index]['tmp_name'] as $id => $tmp_name) {
            $index = $files_index . '_' . $id;
            $_FILES[$index]['tmp_name'] = $tmp_name;
        }

        foreach ($_FILES[$files_index]['error'] as $id => $error) {
            $index = $files_index . '_' . $id;
            $_FILES[$index]['error'] = $error;
        }

        foreach ($_FILES[$files_index]['size'] as $id => $size) {
            $index = $files_index . '_' . $id;
            $_FILES[$index]['size'] = $size;
        }

        return true;
    }

    public static function get_home_path() {
        $home    = set_url_scheme( get_option( 'home' ), 'http' );
        $siteurl = set_url_scheme( get_option( 'siteurl' ), 'http' );
        if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
            $wp_path_rel_to_home = str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
            $pos = strripos( str_replace( '\\', '/', $_SERVER['SCRIPT_FILENAME'] ), trailingslashit( $wp_path_rel_to_home ) );
            $home_path = substr( $_SERVER['SCRIPT_FILENAME'], 0, $pos );
            $home_path = trailingslashit( $home_path );
        } else {
            $home_path = ABSPATH;
        }

        return str_replace( '\\', '/', $home_path );
    }
}