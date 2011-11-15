<?php if ( ! defined('EXT')) exit('Direct script access is not permitted.');

/**
 * OmniLogger class.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Omnilog
 */

require_once PATH_MOD .'omnilog/classes/omnilog_entry' .EXT;
require_once PATH_MOD .'omnilog/models/omnilog_model' .EXT;

class Omnilogger {
    
  /* --------------------------------------------------------------
   * STATIC METHODS
   * ------------------------------------------------------------ */

  /**
   * Adds an entry to the log.
   *
   * @access  public
   * @param   Omnilog_entry   $entry        The log entry.
   * @param   object          $mock_model   Mock model, used for testing.
   * @return  bool
   */
  public static function log(Omnilog_entry $entry, $mock_model = NULL)
  {
    $model = $mock_model ? $mock_model : new Omnilog_model();

    try
    {
      $saved_entry = $model->save_entry_to_log($entry);

      if ($entry->get_notify_admin() === TRUE)
      {
        $model->notify_site_admin_of_log_entry($saved_entry);
      }

      return TRUE;
    }
    catch (Exception $e)
    {
      // Don't OmniLog the error, it could result in an infinite loop.
      return FALSE;
    }
  }

    
}


/* End of file      : omnilogger.php */
/* File location    : /system/modules/omnilog/classes/omnilogger.php */
