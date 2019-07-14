<?php

error_reporting(0);
error_reporting(E_ALL^E_NOTICE^E_WARNING);

/**
 * 一个基于 PHP 的简单文件索引程序，可列出目录及其所有子目录的内容
 *
 * 根据 MIT 许可证分发
 * http://www.opensource.org/licenses/mit-license.php
 *
 * 更多信息，请访问 http://www.directorylister.com
 *
 * @author Chris Kankiewicz (http://www.chriskankiewicz.com)
 * @editor Peter1303 (https://pdev.top)
 * @copyright 2015 Chris Kankiewicz
 */
class DirectoryLister {
    // 定义应用程序版本
    const VERSION = '2.6.1';
    // Reserve some variables
    protected $_directory     = null;
    protected $_appDir        = null;
    protected $_appURL        = null;
    protected $_config        = null;
    protected $_fileTypes     = null;
    protected $_systemMessage = null;

    /**
     * Directorylister 构造函数，创建对象
     */
    public function __construct() {
        // 设置class目录常量
        if(!defined('__DIR__')) {
            define('__DIR__', dirname(__FILE__));
        }
        // 设置应用程序目录
        $this->_appDir = __DIR__;
        // 构建应用程序URL
        $this->_appURL = $this->_getAppUrl();
        // 加载配置文件
        $configFile = $this->_appDir . '/config.php';
        // 将配置数组设置为全局变量
        if (file_exists($configFile)) {
            $this->_config = require_once($configFile);
        } else {
            die('ERROR: Missing application config file at ' . $configFile);
        }
        // 将文件类型数组设置为全局变量
        $this->_fileTypes = require_once($this->_appDir . '/fileTypes.php');
    }

    /**
     * 如果允许压缩整个目录
     *
     * @return true or false
     * @access public
     */
    public function isZipEnabled() {
        foreach ($this->_config['zip_disable'] as $disabledPath) {
            if (fnmatch($disabledPath, $this->_directory)) {
                return false;
            }
        }
        return $this->_config['zip_dirs'];
    }

     /**
     * 创建 zipfile 的目录
     *
     * @param string $directory Relative path of directory to list
     * @access public
     */
    public function zipDirectory($directory) {
        if ($this->_config['zip_dirs']) {
            // Cleanup directory path
            $directory = $this->setDirectoryPath($directory);
            if ($directory != '.' && $this->_isHidden($directory)) {
                echo "Access denied.";
            }
            $filename_no_ext = basename($directory);
            if ($directory == '.') {
                $filename_no_ext = 'DOUBI Soft';
            }
            // We deliver a zip file
            header('Content-Type: archive/zip');
            // 浏览器的文件名保存zip文件
            header("Content-Disposition: attachment; filename=\"$filename_no_ext.zip\"");
            //change directory so the zip file doesnt have a tree structure in it.
            chdir($directory);
            // TODO: Probably we have to parse exclude list more carefully
            $exclude_list = implode(' ', array_merge($this->_config['hidden_files'], array('index.php')));
            $exclude_list = str_replace("*", "\*", $exclude_list);
            if ($this->_config['zip_stream']) {
                // zip the stuff (dir and all in there) into the streamed zip file
                $stream = popen('/usr/bin/zip -' . $this->_config['zip_compression_level'] . ' -r -q - * -x ' . $exclude_list, 'r');
                if ($stream) {
                   fpassthru($stream);
                   fclose($stream);
                }
            } else {
                // get a tmp name for the .zip
                $tmp_zip = tempnam('tmp', 'tempzip') . '.zip';
                // zip the stuff (dir and all in there) into the tmp_zip file
                exec('zip -' . $this->_config['zip_compression_level'] . ' -r ' . $tmp_zip . ' * -x ' . $exclude_list);
                // calc the length of the zip. it is needed for the progress bar of the browser
                $filesize = filesize($tmp_zip);
                header("Content-Length: $filesize");
                // deliver the zip file
                $fp = fopen($tmp_zip, 'r');
                echo fpassthru($fp);
                // clean up the tmp zip file
                unlink($tmp_zip);
            }
        }
    }

    /**
     * 创建目录列表并返回格式化的 XHTML
     *
     * @param string $directory 要列出的目录的相对路径
     * @return array 列出的目录数组
     * @access public
     */
    public function listDirectory($directory) {
        // Set directory
        $directory = $this->setDirectoryPath($directory);
        // Set directory variable if left blank
        if ($directory === null) {
            $directory = $this->_directory;
        }
        // Get the directory array
        $directoryArray = $this->_readDirectory($directory);
        // Return the array
        return $directoryArray;
    }

    /**
     * 分析并返回一个 breadcrumbs 数组
     *
     * @param string $directory Path to be breadcrumbified
     * @return array breadcrumbs 数组
     * @access public
     */
    public function listBreadcrumbs($directory = null) {
        // Set directory variable if left blank
        if ($directory === null) {
            $directory = $this->_directory;
        }
        // Explode the path into an array
        $dirArray = explode('/', $directory);
        // 静态设置主页路径
        $breadcrumbsArray[] = array(
            'link' => $this->_appURL,
            'text' => 'Home'
        );
        // Generate breadcrumbs
        foreach ($dirArray as $key => $dir) {
            if ($dir != '.') {
                $dirPath  = null;
                // 构建目录路径
                for ($i = 0; $i <= $key; $i++) {
                    $dirPath = $dirPath . $dirArray[$i] . '/';
                }
                // 删除尾部斜杠
                if(substr($dirPath, -1) == '/') {
                    $dirPath = substr($dirPath, 0, -1);
                }
                // 组合基本路径和dir路径
                $link = $this->_appURL . '?dir=' . rawurlencode($dirPath);
                $breadcrumbsArray[] = array(
                    'link' => $link,
                    'text' => $dir
                );
            }
        }
        // 返回breadcrumb数组
        return $breadcrumbsArray;
    }

    /**
     * 确定目录是否包含索引文件
     *
     * @param string $dirPath 要检查索引的目录路径
     * @return boolean 如果目录包含有效的索引文件，则返回 true 否则返回 false
     * @access public
     */
    public function containsIndex($dirPath) {
        // 检查目录是否包含索引文件
        foreach ($this->_config['index_files'] as $indexFile) {
            if (file_exists($dirPath . '/' . $indexFile)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取列出目录的路径
     *
     * @return string 列出目录的路径
     * @access public
     */
    public function getListedPath() {
        // Build the path
        if ($this->_directory == '.') {
            $path = $this->_appURL;
        } else {
            $path = $this->_appURL . $this->_directory;
        }
        // Return the path
        return $path;
    }

    /**
     * 返回其他窗口中打开的链接
     *
     * @return boolean 如果在 config 启用了，则返回 true，没有则返回 false
     * @access public
     */
    public function externalLinksNewWindow() {
        return $this->_config['external_links_new_window'];
    }

    /**
     * 获取错误消息数组或空时为 false
     *
     * @return array|bool 错误消息数组或错误
     * @access public
     */
    public function getSystemMessages() {
        if (isset($this->_systemMessage) && is_array($this->_systemMessage)) {
            return $this->_systemMessage;
        } else {
            return false;
        }
    }

    /**
     * 以可读格式返回文件大小的字符串
     *
     * @param  string $filePath 文件路径
     * @return string 可读文件大小
     * @access public
     */
    function getFileSize($filePath) {
        // 获取文件大小
        $bytes = filesize($filePath);
        // 文件大小后缀数组
        $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        // 计算文件大小后缀系数
        $factor = floor((strlen($bytes) - 1) / 3);
        // 计算文件大小
        $fileSize = sprintf('%.2f', $bytes / pow(1024, $factor)) . $sizes[$factor];
        return $fileSize;
    }

    /**
     * 设置目录路径变量
     *
     * @param string $path 目录路径
     * @return string Sanitizd 目录路径
     * @access public
     */
    public function setDirectoryPath($path = null) {
        // Set the directory global variable
        $this->_directory = $this->_setDirectoryPath($path);
        return $this->_directory;
    }

    /**
     * 获取目录路径变量
     *
     * @return string Sanitizd 目录路径
     * @access public
     */
    public function getDirectoryPath() {
        return $this->_directory;
    }

    /**
     * 向系统消息数组添加消息
     *
     * @param string $type 消息类型（IE-Error，Success，Notice 等）
     * @param $text $message 要向用户显示的消息
     * @return bool 成功即真
     * @access public
     */
    public function setSystemMessage($type, $text) {
        // Create empty message array if it doesn't already exist
        if (isset($this->_systemMessage) && !is_array($this->_systemMessage)) {
            $this->_systemMessage = array();
        }
        // Set the error message
        $this->_systemMessage[] = array(
            'type'  => $type,
            'text'  => $text
        );
        return true;
    }

    /**
     * 验证并返回目录路径
     *
     * @param string $dir 目录路径
     * @return string 要列出的目录路径
     * @access protected
     */
    protected function _setDirectoryPath($dir) {
        // Check for an empty variable
        if (empty($dir) || $dir == '.') {
            return '.';
        }
        // Eliminate double slashes
        while (strpos($dir, '//')) {
            $dir = str_replace('//', '/', $dir);
        }
        // Remove trailing slash if present
        if(substr($dir, -1, 1) == '/') {
            $dir = substr($dir, 0, -1);
        }
        // Verify file path exists and is a directory
        if (!file_exists($dir) || !is_dir($dir)) {
            // Set the error message
            $this->setSystemMessage('danger', '<b>ERROR:</b> 文件路径不存在');

            // Return the web root
            return '.';
        }
        // Prevent access to hidden files
        if ($this->_isHidden($dir)) {
            // Set the error message
            $this->setSystemMessage('danger', '<b>ERROR:</b> 拒绝访问');
            // Set the directory to web root
            return '.';
        }
        // Prevent access to parent folders
        if (strpos($dir, '<') !== false || strpos($dir, '>') !== false
        || strpos($dir, '..') !== false || strpos($dir, '/') === 0) {
            // Set the error message
            $this->setSystemMessage('danger', '<b>ERROR:</b> 检测到无效的路径字符串');
            // Set the directory to web root
            return '.';
        } else {
            // Should stop all URL wrappers (Thanks to Hexatex)
            $directoryPath = $dir;
        }
        // Return
        return $directoryPath;
    }

    /**
     * 循环访问目录并返回包含文件信息的数组，包括文件路径、大小、修改时间、图标和排序顺序
     *
     * @param string $directory 目录路径
     * @param string $sort 排序方法 (default = natcase)
     * @return array 目录内容数组
     * @access protected
     */
    protected function _readDirectory($directory, $sort = 'natcase') {
        // Initialize array
        $directoryArray = array();
        // Get directory contents
        $files = scandir($directory);
        // Read files/folders from the directory
        foreach ($files as $file) {
            if ($file != '.') {
                // Get files relative path
                $relativePath = $directory . '/' . $file;
                if (substr($relativePath, 0, 2) == './') {
                    $relativePath = substr($relativePath, 2);
                }
                // Don't check parent dir if we're in the root dir
                if ($this->_directory == '.' && $file == '..'){
                    continue;
                } else {
                    // Get files absolute path
                    $realPath = realpath($relativePath);
                    // Determine file type by extension
                    if (is_dir($realPath)) {
                        $iconClass = 'folder';
                        $sort = 1;
                    } else {
                        // Get file extension
                        $fileExt = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
                        if (isset($this->_fileTypes[$fileExt])) {
                            $iconClass = $this->_fileTypes[$fileExt];
                        } else {
                            $iconClass = $this->_fileTypes['blank'];
                        }
                        $sort = 2;
                    }
                }
                if ($file == '..') {
                    if ($this->_directory != '.') {
                        // Get parent directory path
                        $pathArray = explode('/', $relativePath);
                        unset($pathArray[count($pathArray)-1]);
                        unset($pathArray[count($pathArray)-1]);
                        $directoryPath = implode('/', $pathArray);
                        if (!empty($directoryPath)) {
                            $directoryPath = '?dir=' . rawurlencode($directoryPath);
                        }
                        // Add file info to the array
                        $directoryArray['..'] = array(
                            'file_path'  => $this->_appURL . $directoryPath,
                            'url_path'   => $this->_appURL . $directoryPath,
                            'file_size'  => '-',
                            'mod_time'   => date('Y-m-d H:i:s', filemtime($realPath)),
                            'icon_class' => 'reply',
                            'sort'       => 0
                        );
                    }
                } elseif (!$this->_isHidden($relativePath)) {
                    // Add all non-hidden files to the array
                    if ($this->_directory != '.' || $file != 'index.php') {
                        // Build the file path
                        $urlPath = implode('/', array_map('rawurlencode', explode('/', $relativePath)));
                        if (is_dir($relativePath)) {
                            $urlPath = '?dir=' . $urlPath;
                        } else {
                            $urlPath = $urlPath;
                        }
                        // Add the info to the main array by larry
                        preg_match('/\/([^\/]*)$/', $relativePath, $matches);
                        $pathname = isset($matches[1]) ? $matches[1] : $relativePath;
                        //$directoryArray[pathinfo($relativePath, PATHINFO_BASENAME)] = array(
                        $directoryArray[$pathname] = array(
                            'file_path'  => $relativePath,
                            'url_path'   => $urlPath,
                            'file_size'  => is_dir($realPath) ? '-' : $this->getFileSize($realPath),
                            'mod_time'   => date('Y-m-d H:i:s', filemtime($realPath)),
                            'icon_class' => $iconClass,
                            'sort'       => $sort
                        );
                    }
                }
            }
        }
        // Sort the array
        $reverseSort = in_array($this->_directory, $this->_config['reverse_sort']);
        $sortedArray = $this->_arraySort($directoryArray, $this->_config['list_sort_order'], $reverseSort);
        // Return the array
        return $sortedArray;

    }

    /**
     * 按提供的排序方法对数组排序
     *
     * @param array $array 要排序的数组
     * @param string $sortMethod 排序方法（可接受的输入：natsort、natcasesort等）
     * @param bool $reverse 如果为真，则反转排序后的数组顺序（默认值=false）
     * @return array
     * @access protected
     */
    protected function _arraySort($array, $sortMethod, $reverse = false) {
        // Create empty arrays
        $sortedArray = array();
        $finalArray  = array();
        // Create new array of just the keys and sort it
        $keys = array_keys($array);
        switch ($sortMethod) {
            case 'asort':
                asort($keys);
                break;
            case 'arsort':
                arsort($keys);
                break;
            case 'ksort':
                ksort($keys);
                break;
            case 'krsort':
                krsort($keys);
                break;
            case 'natcasesort':
                natcasesort($keys);
                break;
            case 'natsort':
                natsort($keys);
                break;
            case 'shuffle':
                shuffle($keys);
                break;
        }
        // Loop through the sorted values and move over the data
        if ($this->_config['list_folders_first']) {
            foreach ($keys as $key) {
                if ($array[$key]['sort'] == 0) {
                    $sortedArray['0'][$key] = $array[$key];
                }
            }
            foreach ($keys as $key) {
                if ($array[$key]['sort'] == 1) {
                    $sortedArray[1][$key] = $array[$key];
                }
            }
            foreach ($keys as $key) {
                if ($array[$key]['sort'] == 2) {
                    $sortedArray[2][$key] = $array[$key];
                }
            }
            if ($reverse) {
                $sortedArray[1] = array_reverse($sortedArray[1]);
                $sortedArray[2] = array_reverse($sortedArray[2]);
            }
        } else {
            foreach ($keys as $key) {
                if ($array[$key]['sort'] == 0) {
                    $sortedArray[0][$key] = $array[$key];
                }
            }
            foreach ($keys as $key) {
                if ($array[$key]['sort'] > 0) {
                    $sortedArray[1][$key] = $array[$key];
                }
            }
            if ($reverse) {
                $sortedArray[1] = array_reverse($sortedArray[1]);
            }
        }
        // Merge the arrays
        foreach ($sortedArray as $array) {
            if (empty($array)) continue;
            foreach ($array as $key => $value) {
                $finalArray[$key] = $value;
            }
        }
        // Return sorted array
        return $finalArray;
    }

    /**
     * 确定是否将文件指定为隐藏
     *
     * @param string $filePath 要检查的文件的路径（如果隐藏）
     * @return boolean 如果文件在隐藏数组中，则返回 true，否则返回 false
     * @access protected
     */
    protected function _isHidden($filePath) {
        // Add dot files to hidden files array
        if ($this->_config['hide_dot_files']) {
            $this->_config['hidden_files'] = array_merge(
                $this->_config['hidden_files'],
                array('.*', '*/.*')
            );
        }
        // Compare path array to all hidden file paths
        foreach ($this->_config['hidden_files'] as $hiddenPath) {
            if (fnmatch($hiddenPath, $filePath)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 从服务器变量生成根应用程序 URL
     *
     * @return string 应用程序 URL
     * @access protected
     */
    protected function _getAppUrl() {
        // Get the server protocol
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        // Get the server hostname
        $host = $_SERVER['HTTP_HOST'];
        // Get the URL path
        $pathParts = pathinfo($_SERVER['PHP_SELF']);
        $path      = $pathParts['dirname'];
        // Remove backslash from path (Windows fix)
        if (substr($path, -1) == '\\') {
            $path = substr($path, 0, -1);
        }
        // Ensure the path ends with a forward slash
        if (substr($path, -1) != '/') {
            $path = $path . '/';
        }
        // Build the application URL
        $appUrl = $protocol . $host . $path;
        // Return the URL
        return $appUrl;
    }

    /**
     * 比较两条 path 并返回从一条到另一条的相对路径
     *
     * @param string $fromPath 起始路径
     * @param string $toPath 结束路径
     * @return string $relativePath 从 $fromPath 到 $toPath 的相对路径
     * @access protected
     */
    protected function _getRelativePath($fromPath, $toPath) {
        // Define the OS specific directory separator
        if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
        // Remove double slashes from path strings
        $fromPath = str_replace(DS . DS, DS, $fromPath);
        $toPath = str_replace(DS . DS, DS, $toPath);
        // Explode working dir and cache dir into arrays
        $fromPathArray = explode(DS, $fromPath);
        $toPathArray = explode(DS, $toPath);
        // Remove last fromPath array element if it's empty
        $x = count($fromPathArray) - 1;
        if(!trim($fromPathArray[$x])) {
            array_pop($fromPathArray);
        }
        // Remove last toPath array element if it's empty
        $x = count($toPathArray) - 1;
        if(!trim($toPathArray[$x])) {
            array_pop($toPathArray);
        }
        // Get largest array count
        $arrayMax = max(count($fromPathArray), count($toPathArray));
        // Set some default variables
        $diffArray = array();
        $samePath = true;
        $key = 1;
        // Generate array of the path differences
        while ($key <= $arrayMax) {
            // Get to path variable
            $toPath = isset($toPathArray[$key]) ? $toPathArray[$key] : null;
            // Get from path variable
            $fromPath = isset($fromPathArray[$key]) ? $fromPathArray[$key] : null;
            if ($toPath !== $fromPath || $samePath !== true) {
                // Prepend '..' for every level up that must be traversed
                if (isset($fromPathArray[$key])) {
                    array_unshift($diffArray, '..');
                }
                // Append directory name for every directory that must be traversed
                if (isset($toPathArray[$key])) {
                    $diffArray[] = $toPathArray[$key];
                }
                // Directory paths have diverged
                $samePath = false;
            }
            // Increment key
            $key++;
        }
        // Set the relative thumbnail directory path
        $relativePath = implode('/', $diffArray);
        // Return the relative path
        return $relativePath;
    }
}
