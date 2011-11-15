<?php if ( ! defined('EXT')) exit('Invalid file request.');

/**
 * OmniLog model.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Omnilog
 * @version         1.0.0
 */

require_once PATH_CORE .'core.email' .EXT;
require_once PATH_MOD .'omnilog/classes/omnilog_entry' .EXT;

class Omnilog_model {

  private $_namespace;
  private $_package_name;
  private $_package_version;
  private $_site_id;
  private $_theme_folder_url;


  /* --------------------------------------------------------------
   * PRIVATE METHODS
   * ------------------------------------------------------------ */
  
  /**
   * Returns a references to the package cache. Should be called
   * as follows: $cache =& $this->_get_package_cache();
   *
   * @access  private
   * @return  array
   */
  private function &_get_package_cache()
  {
    return $this->_ee->session->cache[$this->_namespace][$this->_package_name];
  }


  /**
   * Returns the database engine.
   *
   * @access  public
   * @return  string
   */
  private function _get_database_engine()
  {
    global $DB, $PREFS;
    
    $engine = '';
    
    /**
     * Some older MySQL installations use MyISAM. However, new tables are automatically
     * created using INNODB, resulting in problems with foreign keys.
     *
     * We can either drop the foreign keys, which would be tantamount to admitting defeat,
     * or we can determine the engine, and explicitly specify it. We do the latter.
     */
    
    if (version_compare(mysql_get_server_info(), '5.0.0', '<'))
    {
      // We take a punt.
      $engine = 'MyISAM';
    }
    else
    {
      $db_engine = $DB->query("SELECT ENGINE AS engine
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA =  '{$PREFS->ini('db_name')}'
        AND TABLE_NAME = 'exp_sites'
        LIMIT 1");

      if ($db_engine->num_rows)
      {
        $engine = $db_engine->row['engine'];
      }
    }
    
    return $engine;
  }



  /* --------------------------------------------------------------
   * PUBLIC METHODS
   * ------------------------------------------------------------ */
  
  /**
   * Constructor.
   *
   * @access  public
   * @param   string    $package_name       Package name. Used for testing.
   * @param   string    $package_version    Package version. Used for testing.
   * @param   string    $namespace          Session namespace. Used for testing.
   * @return  void
   */
  public function __construct(
    $package_name = '',
    $package_version = '',
    $namespace = ''
  )
  {
    global $SESS;

    $this->_namespace = $namespace
      ? strtolower($namespace)
      : 'experience';
    
    $this->_package_name = $package_name
      ? strtolower($package_name)
      : 'omnilog';
    
    $this->_package_version = $package_version
      ? $package_version
      : '1.0.0';

    // Initialise the add-on cache.
    if ( ! array_key_exists($this->_namespace, $SESS->cache))
    {
      $SESS->cache[$this->_namespace] = array();
    }

    if ( ! array_key_exists($this->_package_name,
      $SESS->cache[$this->_namespace])
    )
    {
      $SESS->cache[$this->_namespace][$this->_package_name] = array();
    }
  }


  /**
   * Returns the installed package version.
   *
   * @access  public
   * @return  string
   */
  public function get_installed_version()
  {
    global $DB;

    $db_result = $DB->query("SELECT module_version
      FROM exp_modules
      WHERE module_name = '{$this->get_package_name()}'
      LIMIT 1");

    return $db_result->num_rows
      ? $db_result->row['module_version']
      : '';
  }


  /**
   * Returns the log entries. By default, only the log entries for
   * the current site are returned.
   *
   * @access  public
   * @param   int|string    $site_id    Restrict to the specified site ID.
   * @param   int           $limit      Maximum number of entries to retrieve.
   * @return  array
   */
  public function get_log_entries($site_id = NULL, $limit = NULL)
  {
    global $DB;

    if ( ! valid_int($site_id, 1))
    {
      $site_id = $this->get_site_id();
    }

    $sql = "SELECT
        addon_name,
        admin_emails,
        date,
        log_entry_id,
        message,
        extended_data,
        notify_admin,
        type
      FROM exp_omnilog_entries
      WHERE site_id = '{$site_id}'
      ORDER BY log_entry_id DESC";

    if (valid_int($limit, 1))
    {
      $sql .= " LIMIT {$limit}";
    }

    $db_result = $DB->query($sql);
    $entries = array();

    // No records? We're done.
    if ( ! $db_result->num_rows)
    {
      return $entries;
    }

    foreach ($db_result->result AS $db_row)
    {
      $db_row['admin_emails'] = explode('|', $db_row['admin_emails']);
      $db_row['notify_admin'] = (strtolower($db_row['notify_admin']) === 'y');
      $entries[]              = new Omnilog_entry($db_row);
    }

    return $entries;
  }


  /**
   * Returns the package name.
   *
   * @access  public
   * @return  string
   */
  public function get_package_name()
  {
    return $this->_package_name;
  }


  /**
   * Returns the package theme folder URL. Appends a forward slash if required.
   *
   * @access    public
   * @return    string
   */
  public function get_package_theme_url()
  {
    global $PREFS;

    if ( ! $this->_theme_folder_url)
    {
      $this->_theme_folder_url = $PREFS->ini('theme_folder_url');
      $this->_theme_folder_url .= substr($this->_theme_folder_url, -1) == '/'
        ? 'cp_themes/default/'
        : '/cp_themes/default/';

      $this->_theme_folder_url .= $this->get_package_name() .'/';
    }

    return $this->_theme_folder_url;
  }


  /**
   * Returns the package version.
   *
   * @access  public
   * @return  string
   */
  public function get_package_version()
  {
    return $this->_package_version;
  }


  /**
   * Returns the site ID.
   *
   * @access  public
   * @return  int
   */
  public function get_site_id()
  {
    global $PREFS;

    if ( ! $this->_site_id)
    {
      $this->_site_id = intval($PREFS->ini('site_id'));
    }

    return $this->_site_id;
  }


  /**
   * Installs the module.
   *
   * @access  public
   * @return  bool
   */
  public function install_module()
  {
    $this->install_module_register();
    $this->install_module_entries_table($this->_get_database_engine());

    return TRUE;
  }


  /**
   * Creates the OmniLog entries table.
   *
   * @access  public
   * @param   string    $engine   The database engine.
   * @return  void
   */
  public function install_module_entries_table($engine)
  {
    global $DB;

    $sql = "CREATE TABLE IF NOT EXISTS exp_omnilog_entries (
        log_entry_id    int(10)     unsigned NOT NULL auto_increment,
        site_id         int(5)      unsigned NOT NULL default 1,
        addon_name      varchar(50) NOT NULL,
        admin_emails    mediumtext,
        date            int(10)     unsigned NOT NULL,
        notify_admin    char(1)     NOT NULL default 'n',
        type            varchar(10) NOT NULL,
        message         text,
        extended_data   text,
      CONSTRAINT pk_omnilog_entries PRIMARY KEY(log_entry_id),
      CONSTRAINT fk_omnilog_entries_site_id FOREIGN KEY(site_id)
        REFERENCES exp_sites(site_id),
      KEY k_omnilog_entries_addon_name(addon_name))
      ENGINE {$engine}";

    $DB->query($sql);
  }


  /**
   * Registers the module in the database.
   *
   * @access  public
   * @return  void
   */
  public function install_module_register()
  {
    global $DB;

    $DB->query($DB->insert_string('exp_modules', array(
      'has_cp_backend'  => 'y',
      'module_id'       => '',
      'module_name'     => ucfirst($this->get_package_name()),
      'module_version'  => $this->get_package_version()
    )));
  }


  /**
   * Notifies the site administrator (via email) of the supplied OmniLog Entry.
   *
   * @access  public
   * @param   Omnilog_entry   $entry    The log entry.
   * @param   object          $email    Mock email class, for testing. Ugh.
   * @return  void
   */
  public function notify_site_admin_of_log_entry(Omnilog_entry $entry,
    $email = NULL
  )
  {
    global $LANG, $PREFS, $REGX;

    // Horrid. I blame the parents (Paul Burdick, mostly).
    if ( ! $email)
    {
      $email = new EEmail();
    }

    $LANG->fetch_language_file($this->get_package_name());

    if ( ! $entry->is_populated())
    {
      throw new Exception($LANG->line('exception__notify_admin__missing_data'));
    }

    $webmaster_email = $PREFS->ini('webmaster_email');

    if ($email->valid_email($webmaster_email) !== TRUE)
    {
      throw new Exception(
        $LANG->line('exception__notify_admin__invalid_webmaster_email'));
    }

    $webmaster_name = ($webmaster_name = $PREFS->ini('webmaster_name'))
      ? $webmaster_name
      : '';

    switch ($entry->get_type())
    {
      case Omnilog_entry::NOTICE:
        $lang_entry_type = $LANG->line('email_entry_type_notice');
        break;

      case Omnilog_entry::WARNING:
        $lang_entry_type = $LANG->line('email_entry_type_warning');
        break;

      case Omnilog_entry::ERROR:
        $lang_entry_type = $LANG->line('email_entry_type_error');
        break;

      default:
        $lang_entry_type = $LANG->line('email_entry_type_unknown');
        break;
    }

    $subject = ($site_name = $PREFS->ini('site_name'))
      ? $LANG->line('email_subject') .' (' .$site_name .')'
      : $LANG->line('email_subject');

    $admin_emails = ($admin_emails = $entry->get_admin_emails())
      ? $admin_emails
      : array($webmaster_email);

    $message = $LANG->line('email_preamble') .NL .NL;
    $message .= $LANG->line('email_addon_name') .NL
      .$entry->get_addon_name() .NL .NL;

    $message .= $LANG->line('email_log_date') .NL
      .date('r', $entry->get_date()) .NL .NL;

    $message .= $LANG->line('email_entry_type') .NL
      .$lang_entry_type .NL .NL;

    $message .= $LANG->line('email_log_message') .NL
      .$entry->get_message() .NL .NL;

    $message .= $LANG->line('email_log_extended_data') .NL
      .$entry->get_extended_data() .NL .NL;

    $message .= $LANG->line('email_cp_url') .NL
      .$PREFS->ini('cp_url') .NL .NL;

    $message .= $LANG->line('email_postscript');
    $message = $REGX->entities_to_ascii($message);

    $email->from($webmaster_email, $webmaster_name);
    $email->to($admin_emails);
    $email->subject($subject);
    $email->message($message);

    if ($email->Send() !== TRUE)
    {
      throw new Exception(
        $LANG->line('exception__notify_admin__email_not_sent'));
    }
  }


  /**
   * Saves the supplied OmniLog Entry to the database.
   *
   * @access  public
   * @param   Omnilog_entry       $entry          The entry to save.
   * @return  Omnilog_entry
   */
  public function save_entry_to_log(Omnilog_entry $entry)
  {
    global $DB, $LANG;

    /**
     * This method could conceivably be called when the module is
     * not installed, but the Omnilogger class is present.
     */

    if ( ! $DB->table_exists('exp_omnilog_entries'))
    {
      throw new Exception($LANG->line('exception__save_entry__not_installed'));
    }

    if ( ! $entry->is_populated())
    {
      throw new Exception($LANG->line('exception__save_entry__missing_data'));
    }

    $insert_data = array_merge(
      $entry->to_array(),
      array(
        'notify_admin'  => ($entry->get_notify_admin() === TRUE) ? 'y' : 'n',
        'site_id'       => $this->get_site_id()
      )
    );

    $insert_data['admin_emails'] = implode($insert_data['admin_emails'], '|');

    $DB->query($DB->insert_string('exp_omnilog_entries', $insert_data));

    if ( ! $insert_id = $DB->insert_id)
    {
      throw new Exception($LANG->line('exception__save_entry__not_saved'));
    }

    $entry->set_log_entry_id($insert_id);
    return $entry;
  }


  /**
   * Uninstalls the module.
   *
   * @access  public
   * @return  bool
   */
  public function uninstall_module()
  {
    global $DB;

    $module_name = ucfirst($this->get_package_name());

    // Retrieve the module information.
    $db_module = $DB->query("SELECT module_id
      FROM exp_modules
      WHERE module_name = '{$module_name}'
      LIMIT 1");

    if ( ! $db_module->num_rows)
    {
      return FALSE;
    }

    $DB->query("DELETE FROM exp_module_member_groups
      WHERE module_id = '{$db_module->row['module_id']}'");

    $DB->query("DELETE FROM exp_modules
      WHERE module_name = '{$module_name}'");

    // Drop the log entries table.
    $DB->query('DROP TABLE IF EXISTS exp_omnilog_entries');

    return TRUE;
  }


  /**
   * Updates the module.
   *
   * @access  public
   * @param   string    $installed_version    The installed version.
   * @param   bool      $force                Forcibly update the module version?
   * @return  bool
   */
  public function update_package($installed_version = '', $force = FALSE)
  {
    global $DB;

    $package_version = $this->get_package_version();

    if ( ! $installed_version
        OR version_compare($installed_version, $package_version, '>='))
    {
      return FALSE;
    }

    // Forcibly update the module version number?
    if ($force === TRUE)
    {
      $DB->query($DB->update_string(
        'exp_modules',
        array('module_version' => $package_version),
        array('module_name' => ucfirst($this->get_package_name()))
      ));
    }

    return TRUE;
  }


}


/* End of file      : omnilog_model.php */
/* File location    : third_party/omnilog/models/omnilog_model.php */
