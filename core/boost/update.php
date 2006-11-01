<?php

  /**
   * @version $Id$
   * @author Matthew McNaney <mcnaney at gmail dot com>
   */

function core_update(&$content, $version) {
    
    switch ($version) {
    case version_compare($version, '1.0.2', '<'):
    
        $db = & new PHPWS_DB('core_version');
        $db->addValue('version', 'varchar(10) NOT NULL default \'\'');
        $result = $db->createTable();
        if (PEAR::isError($result)) {
            return $result;
        }
        $db->reset();
        $db->addValue('version', '1.0.0');
        $result = $db->insert();
        if (PEAR::isError($result)) {
            return $result;
        }
        $content[] = '- Created core_version table.';

    case version_compare($version, '1.0.3', '<'):
        $content[] = 'Please see changes_1_0_3.txt in the core\'s boost directory for a listing of changes.';

    case version_compare($version, '1.0.5', '<'):
        $content[] = '- Fixed core version on installation.';
        $content[] = '- Changed Core.php and Module.php to track core\'s version better. Helps Boost with dependencies';

    case version_compare($version, '1.0.6', '<'):
        $content[] = '- Fixed locale cookie saving incorrectly.';

    case version_compare($version, '1.0.7', '<'):
        $content[] = '- Key.php : Added parameter to avoid home keys when calling getCurrent.';
        $content[] = '- Database.php : fixed a small bug with adding columns using "as". Value was carrying over to other columns.';
        $content[] = '- Form.php : Added an error check on a select value.';
        $content[] = '- Documentation : updated DB_Pager.txt with information on setting a column order.';
        $content[] = '- Init.php - Commented out putenv functions.';
        $content[] = '- Javascript : close_refresh - added option to not auto-close';

    case version_compare($version, '1.0.8', '<'):
        $content[] = '- Module.php : now adds error to _error variable if module could not be loaded.';

    case version_compare($version, '1.0.9', '<'):
        $content[] = '- Form.php : fixed crutch function for radio buttons and check boxes.';

    case version_compare($version, '1.1.0', '<'):
        $content[] = 'Fix - Added a define for CURRENT_LANGUAGE if gettext is not working.';
        $content[] = 'Fix - Altered the count type for select slightly.';
        $content[] = 'Fix - PHPWS_File\'s copy_directory function was reporting the wrong value in its error messages.';
        $content[] = 'Fix - In Settings, added an error check to prevent null values from being saved in the integer columns.';
        
        $content[] = 'New - Reworked Database class to allow table prefixing and concurrent connections.';
        $content[] = 'New - Added table prefixing back to install process in Setup.';
        $content[] = 'New - DB factory files have been broken out into specially named classes, hopefully this will allow dual connections on different database systems.';
        $content[] = 'New - Removed Crutch_Db.php.';
        $content[] = 'New - Null values are not considered recursive values in the Debug test function.';
        $content[] = 'New - In Convert, added a table check to getSourceDB function. Calendar updated.';
        $content[] = 'New - In Settings, added a reset function that sets a value back to the default.';
        $content[] = 'New - Error checks added to Batch.';
        $content[] = 'New - Removed the static tables variable in Database\'s isTable function. Possibility exists that two or more databases could be used and the static list would return faulty information.';

    case version_compare($version, '1.1.1', '<'):
        $content[] = 'Fix - Blog conversion now copies summary and body correctly.';
        $content[] = 'Fix - File Cabinet conversion checks for Documents module before beginning.';
        $content[] = 'Fix - Users conversion now sets users as active and approved.';
        $content[] = 'Fix - Settings reloads after saving values. Prevent bad data.';
    }
    
    return true;
}


?>