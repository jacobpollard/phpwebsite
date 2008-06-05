<?php

  /**
   * update file for menu
   *
   * @author Matthew McNaney <mcnaney at gmail dot com>
   * @version $Id$
   */

function menu_update(&$content, $currentVersion)
{
    $home_directory = PHPWS_Boost::getHomeDir();

    switch ($currentVersion) {
    case version_compare($currentVersion, '1.2.0', '<'):
        $content[] = '<pre>Menu versions prior to 1.2.0 are not supported for update.
Please download 1.2.1.</pre>';
        break;

    case version_compare($currentVersion, '1.2.1', '<'):
        $content[] = '<pre>1.2.1 changes
-----------------
+ Fixed bug with making home link.
</pre>';

    case version_compare($currentVersion, '1.3.0', '<'):
        $files = array('conf/config.php', 'templates/admin/settings.tpl',
                       'templates/links/link.tpl', 'templates/popup_admin.tpl');
        $content[] = '<pre>';
        if (PHPWS_Boost::updateFiles($files, 'menu')) {
            $content[] = '--- Successfully updated the following files:';
        } else {
            $content[] = '--- Was unable to copy the following files:';
        }
        $content[] = '     ' . implode("\n     ", $files);
        $content[] = '
1.3.0 changes
-----------------
+ Admin icon for links is now clickable. Pulls up window of options.
+ Added ability to disable floating admin links.
</pre>';

    case version_compare($currentVersion, '1.3.1', '<'):
        $files = array('templates/site_map.tpl');
        $content[] = '<pre>';
        if (PHPWS_Boost::updateFiles($files, 'menu')) {
            $content[] = '--- Successfully updated the following files:';
        } else {
            $content[] = '--- Was unable to copy the following files:';
        }
        $content[] = '     ' . implode("\n     ", $files);
        $content[] = '
1.3.1 changes
-----------------
+ Bug # 1609737. Fixed site_map.tpl file. Thanks Andy.
</pre>';

    case version_compare($currentVersion, '1.4.0', '<'):
        $content[] = '<pre>';

        $basic_dir = $home_directory . 'templates/menu/menu_layout/basic/';
        $horz_dir  = $home_directory . 'templates/menu/menu_layout/horizontal/';

        if (!is_dir($basic_dir)) {
            if (PHPWS_File::copy_directory(PHPWS_SOURCE_DIR . 'mod/menu/templates/menu_layout/basic/', $basic_dir)) {
                $content[] = "--- Successfully copied directory: $basic_dir";
            } else {
                $content[] = "--- Failed to copy directory: $basic_dir</pre>";
                return false;
            }
        }

        if (!is_dir($horz_dir)) {
            if (PHPWS_File::copy_directory(PHPWS_SOURCE_DIR . 'mod/menu/templates/menu_layout/horizontal/', $horz_dir)) {
                $content[] = "--- Successfully copied directory: $horz_dir";
            } else {
                $content[] = "--- Failed to copy directory: $horz_dir</pre>";
                return false;
            }
        }

        menuUpdateFiles(array('conf/error.php'), $content);
                        
        if (!PHPWS_Boost::inBranch()) {
            $content[] = file_get_contents(PHPWS_SOURCE_DIR . 'mod/menu/boost/changes/1_4_0.txt');
        }
                        
        $content[] = '</pre>';

    case version_compare($currentVersion, '1.4.1', '<'):
        $content[] = '<pre>';
        
        $files = array('templates/admin/settings.tpl', 'templates/admin/menu_list.tpl');
        menuUpdateFiles($files, $content);
        if (!PHPWS_Boost::inBranch()) {
            $content[] = file_get_contents(PHPWS_SOURCE_DIR . 'mod/menu/boost/changes/1_4_1.txt');
        }
                        
        $content[] = '</pre>';

    case version_compare($currentVersion, '1.4.2', '<'):
        $content[] = '<pre>';
        
        $db = new PHPWS_DB('menus');
        $db->addWhere('template', 'basic.tpl');
        $db->addValue('template', 'basic');
        if (PHPWS_Error::logIfError($db->update())) {
            $content[] = '--- Failed to update menus table.';
        } else {
            $content[] = '--- Updated menu table with correct template directory.';
        }

        $files = array('templates/admin/settings.tpl');
        menuUpdateFiles($files, $content);
        if (!PHPWS_Boost::inBranch()) {
            $content[] = file_get_contents(PHPWS_SOURCE_DIR . 'mod/menu/boost/changes/1_4_2.txt');
        }
                        
        $content[] = '</pre>';

    case version_compare($currentVersion, '1.4.3', '<'):
        $content[] = '<pre>';
        $files = array('templates/admin/settings.tpl');
        menuUpdateFiles($files, $content);
        if (!PHPWS_Boost::inBranch()) {
            $content[] = file_get_contents(PHPWS_SOURCE_DIR . 'mod/menu/boost/changes/1_4_3.txt');
        }
        $content[] = '</pre>';

    case version_compare($currentVersion, '1.4.4', '<'):
        $content[] = '<pre>
1.4.4 Changes
--------------
+ Added three new menu functions:
  o quickLink - inserts a new link on any menu pinned on all pages;
                passed a title and url.
  o quickKeyLink - same as above but passed key_id
  o updateKeyLink - causes a link to reset its url, title, and active
                    status based on the condition of the current key
                    it is based on.</pre>';

    case version_compare($currentVersion, '1.4.5', '<'):
        $content[] = '<pre>
1.4.5 Changes
--------------
+ Fixed some submenus not appearing when sibling chosen.</pre>';

    case version_compare($currentVersion, '1.4.6', '<'):
        $content[] = '<pre>';
        menuUpdateFiles(array('templates/admin/menu_list.tpl'), $content);
        $content[] = '1.4.6 Changes
--------------
+ Update to addSortHeaders.
+ Adding missing paging navigation.</pre>';
    }
    return true;
}

function menuUpdateFiles($files, &$content)
{
    if (PHPWS_Boost::updateFiles($files, 'menu')) {
        $content[] = '--- Updated the following files:';
    } else {
        $content[] = '--- Unable to update the following files:';
    }
    $content[] = "     " . implode("\n     ", $files);
}


?>