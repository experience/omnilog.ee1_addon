<?php if ( ! defined('EXT')) exit('Invalid file request.');

/**
 * OmniLog model tests.
 *
 * @author          Stephen Lewis (http://github.com/experience/)
 * @copyright       Experience Internet
 * @package         Omnilog
 */

require_once PATH_MOD .'testee/classes/mocks/testee_mock_eemail' .EXT;
require_once PATH_MOD .'omnilog/models/omnilog_model' .EXT;

class Test_omnilog_model extends Testee_unit_test_case {

  private $_package_name;
  private $_package_version;
  private $_site_id;
  private $_subject;


  /* --------------------------------------------------------------
   * PUBLIC METHODS
   * ------------------------------------------------------------ */

  /**
   * Constructor.
   *
   * @access  public
   * @return  void
   */
  public function setUp()
  {
    global $PREFS, $SESS;

    parent::setUp();

    $this->_package_name    = 'example_package';
    $this->_package_version = '1.0.0';

    $this->_site_id = 10;
    $PREFS->setReturnValue('ini', $this->_site_id, array('site_id'));

    $SESS->cache = array();

    $this->_subject = new Omnilog_model($this->_package_name,
      $this->_package_version);
  }


  public function test__constructor__package_name_and_version()
  {
    $package_name     = 'Example_package';
    $package_version  = '1.0.0';

    $subject = new Omnilog_model($package_name, $package_version);

    $this->assertIdentical(strtolower($package_name),
      $subject->get_package_name());

    $this->assertIdentical($package_version, $subject->get_package_version());
  }


  public function testget_installed_version__success()
  {
    global $DB;

    $sql = "SELECT module_version FROM exp_modules
      WHERE module_name = '{$this->_package_name}'
      LIMIT 1";

    $version    = '1.0.0';
    $db_result  = $this->_get_mock('db_cache');
    $db_row     = array('module_version' => $version);

    $DB->expectOnce('query',
      array(new EqualWithoutWhitespaceExpectation($sql)));

    $DB->setReturnReference('query', $db_result);

    $db_result->setReturnValue('__get', 1, array('num_rows'));
    $db_result->setReturnValue('__get', $db_row, array('row'));

    $this->assertIdentical($version, $this->_subject->get_installed_version());
  }


  public function test__get_installed_version__not_installed()
  {
    global $DB;

    $db_result  = $this->_get_mock('db_cache');

    $DB->expectOnce('query');
    $DB->setReturnReference('query', $db_result);
    $db_result->setReturnValue('__get', 0, array('num_rows'));

    $this->assertIdentical('', $this->_subject->get_installed_version());
  }


  public function test__get_log_entries__success_default_site_id()
  {
    global $DB;

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
      WHERE site_id = '{$this->_site_id}'
      ORDER BY log_entry_id DESC";

    $db_result = $this->_get_mock('db_cache');
    $db_rows = array(
      array(
        'addon_name'    => 'Example A',
        'admin_emails'  => 'adam@ants.com|bob@dylan.com',
        'date'          => time() - 5000,
        'log_entry_id'  => '10',
        'message'       => 'Example message A-A',
        'extended_data' => 'Example extended data A-A',
        'notify_admin'  => 'n',
        'type'          => Omnilog_entry::NOTICE
      ),
      array(
        'addon_name'    => 'Example A',
        'admin_emails'  => '',
        'date'          => time() - 4000,
        'log_entry_id'  => '20',
        'message'       => 'Example message A-B',
        'extended_data' => 'Example extended data A-B',
        'notify_admin'  => 'y',
        'type'          => Omnilog_entry::ERROR
      ),
      array(
        'addon_name'    => 'Example B',
        'admin_emails'  => 'chas@dave.com|eric@roberts.com|dead@weather.com',
        'date'          => time() - 3000,
        'log_entry_id'  => '30',
        'message'       => 'Example message B-A',
        'extended_data' => 'Example extended data B-A',
        'notify_admin'  => 'n',
        'type'          => Omnilog_entry::WARNING
      )
    );

    $DB->expectOnce('query',
      array(new EqualWithoutWhitespaceExpectation($sql)));

    $DB->setReturnReference('query', $db_result);
    $db_result->setReturnValue('__get', count($db_rows), array('num_rows'));
    $db_result->setReturnValue('__get', $db_rows, array('result'));

    $expected_result = array();

    foreach ($db_rows AS $db_row)
    {
      $db_row['admin_emails'] = explode('|', $db_row['admin_emails']);
      $db_row['notify_admin'] = (strtolower($db_row['notify_admin']) === 'y');
      $expected_result[]      = new Omnilog_entry($db_row);
    }

    $actual_result = $this->_subject->get_log_entries();
    $this->assertIdentical(count($expected_result), count($actual_result));

    $length = count($expected_result);

    for ($count = 0, $length = $length; $count < $length; $count++)
    {
      $this->assertIdentical(
        $expected_result[$count]->to_array(TRUE),
        $actual_result[$count]->to_array(TRUE)
      );
    }
  }


  public function test__get_log_entries__success_custom_site_id()
  {
    global $DB;

    $site_id = 999;

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

    $db_rows = array(
      array(
        'addon_name'    => 'Example A',
        'admin_emails'  => '',
        'date'          => time() - 3000,
        'log_entry_id'  => '10',
        'message'       => 'Example message A-A',
        'extended_data' => 'Example extended data A-A',
        'notify_admin'  => 'n',
        'type'          => Omnilog_entry::WARNING
      )
    );

    $db_result = $this->_get_mock('db_cache');

    $DB->expectOnce('query',
      array(new EqualWithoutWhitespaceExpectation($sql)));

    $DB->setReturnReference('query', $db_result);
    $db_result->setReturnValue('__get', count($db_rows), array('num_rows'));
    $db_result->setReturnValue('__get', $db_rows, array('result'));

    $expected_result = array();

    foreach ($db_rows AS $db_row)
    {
      $db_row['admin_emails'] = explode('|', $db_row['admin_emails']);
      $db_row['notify_admin'] = (strtolower($db_row['notify_admin']) === 'y');
      $expected_result[]      = new Omnilog_entry($db_row);
    }

    $actual_result = $this->_subject->get_log_entries($site_id);
    $this->assertIdentical(count($expected_result), count($actual_result));

    $length = count($expected_result);
    for ($count = 0, $length = $length; $count < $length; $count++)
    {
      $this->assertIdentical(
        $expected_result[$count]->to_array(TRUE),
        $actual_result[$count]->to_array(TRUE)
      );
    }
  }


  public function test__get_log_entries__no_entries()
  {
    global $DB;

    $db_result = $this->_get_mock('db_cache');

    $DB->setReturnReference('query', $db_result);
    $db_result->setReturnValue('__get', 0, array('num_rows'));

    $this->assertIdentical(array(), $this->_subject->get_log_entries());
  }


  public function test__get_log_entries__success_with_limit()
  {
    global $DB;

    $limit  = 10;

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
      WHERE site_id = '{$this->_site_id}'
      ORDER BY log_entry_id DESC
      LIMIT {$limit}";

    $db_result = $this->_get_mock('db_cache');

    // NOTE: We're only testing that the SQL includes a limit.
    $DB->expectOnce('query',
      array(new EqualWithoutWhitespaceExpectation($sql)));

    $DB->setReturnReference('query', $db_result);
    $db_result->setReturnValue('__get', 0, array('num_rows'));

    $this->assertIdentical(array(),
      $this->_subject->get_log_entries(NULL, $limit));
  }


  public function test__get_site_id__success()
  {
    global $PREFS;

    $PREFS->expectOnce('ini', array('site_id'));

    $this->assertIdentical(intval($this->_site_id),
      $this->_subject->get_site_id());
  }


  public function test__install_module_entries_table__success()
  {
    global $DB;

    $engine = 'Wibble_engine';

    $sql = "CREATE TABLE IF NOT EXISTS exp_omnilog_entries (
      log_entry_id int(10) unsigned NOT NULL auto_increment,
      site_id int(5) unsigned NOT NULL default 1,
      addon_name varchar(50) NOT NULL,
      admin_emails mediumtext,
      date int(10) unsigned NOT NULL,
      notify_admin char(1) NOT NULL default 'n',
      type varchar(10) NOT NULL,
      message text,
      extended_data text,
    CONSTRAINT pk_omnilog_entries PRIMARY KEY(log_entry_id),
    CONSTRAINT fk_omnilog_entries_site_id FOREIGN KEY(site_id)
      REFERENCES exp_sites(site_id),
    KEY k_omnilog_entries_addon_name (addon_name))
    ENGINE {$engine}";

    $DB->expectOnce('query',
      array(new EqualWithoutWhitespaceExpectation($sql)));

    $this->_subject->install_module_entries_table($engine);
  }


  public function test__install_module_register__success()
  {
    global $DB;

    $sql = 'INSERT_STRING';
    $insert_data = array(
      'has_cp_backend'  => 'y',
      'module_id'       => '',
      'module_name'     => ucfirst($this->_package_name),
      'module_version'  => $this->_package_version
    );

    $DB->expectOnce('insert_string', array('exp_modules', $insert_data));
    $DB->setReturnValue('insert_string', $sql);
    $DB->expectOnce('query', array($sql));

    $this->_subject->install_module_register();
  }


  public function test__notify_site_admin_of_log_entry__success()
  {
    global $LANG, $PREFS, $REGX;

    $entry_data = array(
      'addon_name'    => 'Example Add-on',
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'type'          => Omnilog_entry::ERROR
    );

    $entry = new Omnilog_entry($entry_data);

    $cp_url         = 'http://example.com/system/index.php';
    $site_name      = 'Example Website';
    $webmaster_email = 'webmaster@example.com';
    $webmaster_name = 'Lord Vancellator';

    $lang_subject       = 'Subject';
    $lang_addon_name    = 'Add-on Name:';
    $lang_cp_url        = 'Control Panel URL:';
    $lang_log_date      = 'Date Logged:';
    $lang_log_message   = 'Log Message:';
    $lang_log_extended  = 'Extended Data:';
    $lang_entry_type    = 'Severity:';
    $lang_error         = 'Error';
    $lang_preamble      = 'The bit before the details.';
    $lang_postscript    = '-- End of message --';

    $subject            = $lang_subject .' (' .$site_name .')';
    $addon_name         = $lang_addon_name .NL .$entry_data['addon_name'];
    $log_cp_url         = $lang_cp_url .NL .$cp_url;
    $log_date           = $lang_log_date .NL .date('r', $entry_data['date']);
    $log_message        = $lang_log_message .NL .$entry_data['message'];
    $log_extended_data  = $lang_log_extended .NL .$entry_data['extended_data'];
    $entry_type         = $lang_entry_type .NL .$lang_error;

    $message = $lang_preamble
      .NL .NL
      .$addon_name .NL .NL
      .$log_date .NL .NL
      .$entry_type .NL .NL
      .$log_message .NL .NL
      .$log_extended_data .NL .NL
      .$log_cp_url .NL .NL
      .$lang_postscript;

    $LANG->setReturnValue('line', $lang_subject, array('email_subject'));
    $LANG->setReturnValue('line', $lang_addon_name, array('email_addon_name'));
    $LANG->setReturnValue('line', $lang_cp_url, array('email_cp_url'));
    $LANG->setReturnValue('line', $lang_log_date, array('email_log_date'));
    $LANG->setReturnValue('line', $lang_log_message, array('email_log_message'));
    $LANG->setReturnValue('line', $lang_log_extended, array('email_log_extended_data'));
    $LANG->setReturnValue('line', $lang_entry_type, array('email_entry_type'));
    $LANG->setReturnValue('line', $lang_error, array('email_entry_type_error'));
    $LANG->setReturnValue('line', $lang_preamble, array('email_preamble'));
    $LANG->setReturnValue('line', $lang_postscript, array('email_postscript'));

    $PREFS->expectCallCount('ini', 4);
    $PREFS->setReturnValue('ini', $cp_url, array('cp_url'));
    $PREFS->setReturnValue('ini', $site_name, array('site_name'));
    $PREFS->setReturnValue('ini', $webmaster_email, array('webmaster_email'));
    $PREFS->setReturnValue('ini', $webmaster_name, array('webmaster_name'));

    $REGX->expectOnce('entities_to_ascii', array($message));
    $REGX->setReturnValue('entities_to_ascii', $message, array($message));

    Mock::generate('Testee_mock_eemail', get_class($this) .'_mock_eemail');
    $email = $this->_get_mock('eemail');

    $email->expectOnce('valid_email', array($webmaster_email));
    $email->setReturnValue('valid_email', TRUE);

    $email->expectOnce('from', array($webmaster_email, $webmaster_name));
    $email->expectOnce('to', array(array($webmaster_email)));
    $email->expectOnce('subject', array($subject));
    $email->expectOnce('message', array($message));

    $email->expectOnce('Send');
    $email->setReturnValue('Send', TRUE);

    $this->_subject->notify_site_admin_of_log_entry($entry, $email);
  }


  public function test__notify_site_admin_of_log_entry__custom_email_success()
  {
    global $LANG, $PREFS, $REGX;
    
    $entry_data = array(
      'addon_name'    => 'Example Add-on',
      'admin_emails'  => array('adam@adamson.com', 'bob@bobson.com'),
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'type'          => Omnilog_entry::ERROR
    );

    $entry = new Omnilog_entry($entry_data);

    $cp_url         = 'http://example.com/system/index.php';
    $site_name      = 'Example Website';
    $webmaster_email = 'webmaster@example.com';
    $webmaster_name = 'Lord Vancellator';

    $lang_subject       = 'Subject';
    $lang_addon_name    = 'Add-on Name:';
    $lang_cp_url        = 'Control Panel URL:';
    $lang_log_date      = 'Date Logged:';
    $lang_log_message   = 'Log Message:';
    $lang_log_extended  = 'Log Extended Data:';
    $lang_entry_type    = 'Severity:';
    $lang_error         = 'Error';
    $lang_preamble      = 'The bit before the details.';
    $lang_postscript    = '-- End of message --';

    $subject            = $lang_subject .' (' .$site_name .')';
    $addon_name         = $lang_addon_name .NL .$entry_data['addon_name'];
    $log_cp_url         = $lang_cp_url .NL .$cp_url;
    $log_date           = $lang_log_date .NL .date('r', $entry_data['date']);
    $log_message        = $lang_log_message .NL .$entry_data['message'];
    $log_extended_data  = $lang_log_extended .NL .$entry_data['extended_data'];
    $entry_type         = $lang_entry_type .NL .$lang_error;

    $message = $lang_preamble
      .NL .NL
      .$addon_name .NL .NL
      .$log_date .NL .NL
      .$entry_type .NL .NL
      .$log_message .NL .NL
      .$log_extended_data .NL .NL
      .$log_cp_url .NL .NL
      .$lang_postscript;

    $LANG->setReturnValue('line', $lang_subject, array('email_subject'));
    $LANG->setReturnValue('line', $lang_addon_name, array('email_addon_name'));
    $LANG->setReturnValue('line', $lang_cp_url, array('email_cp_url'));
    $LANG->setReturnValue('line', $lang_log_date, array('email_log_date'));
    $LANG->setReturnValue('line', $lang_log_message, array('email_log_message'));
    $LANG->setReturnValue('line', $lang_log_extended, array('email_log_extended_data'));
    $LANG->setReturnValue('line', $lang_entry_type, array('email_entry_type'));
    $LANG->setReturnValue('line', $lang_error, array('email_entry_type_error'));
    $LANG->setReturnValue('line', $lang_preamble, array('email_preamble'));
    $LANG->setReturnValue('line', $lang_postscript, array('email_postscript'));

    $PREFS->expectCallCount('ini', 4);
    $PREFS->setReturnValue('ini', $cp_url, array('cp_url'));
    $PREFS->setReturnValue('ini', $site_name, array('site_name'));
    $PREFS->setReturnValue('ini', $webmaster_email, array('webmaster_email'));
    $PREFS->setReturnValue('ini', $webmaster_name, array('webmaster_name'));

    $REGX->expectOnce('entities_to_ascii', array($message));
    $REGX->setReturnValue('entities_to_ascii', $message, array($message));

    Mock::generate('Testee_mock_eemail', get_class($this) .'_mock_eemail');
    $email = $this->_get_mock('eemail');

    $email->expectOnce('valid_email', array($webmaster_email));
    $email->setReturnValue('valid_email', TRUE);

    $email->expectOnce('from', array($webmaster_email, $webmaster_name));
    $email->expectOnce('to', array($entry_data['admin_emails']));
    $email->expectOnce('subject', array($subject));
    $email->expectOnce('message', array($message));
    $email->expectOnce('Send');
    $email->setReturnValue('Send', TRUE);

    $this->_subject->notify_site_admin_of_log_entry($entry, $email);
  }


  public function test__notify_site_admin_of_log_entry__success_no_webmaster_name()
  {
    global $PREFS;

    $entry_data = array(
      'addon_name'    => 'Example Add-on',
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'type'          => Omnilog_entry::ERROR
    );

    $entry = new Omnilog_entry($entry_data);

    $webmaster_email = 'webmaster@example.com';

    $PREFS->setReturnValue('ini', $webmaster_email, array('webmaster_email'));

    Mock::generate('Testee_mock_eemail', get_class($this) .'_mock_eemail');
    $email = $this->_get_mock('eemail');

    $email->expectOnce('valid_email', array($webmaster_email));
    $email->setReturnValue('valid_email', TRUE);

    $email->expectOnce('from', array($webmaster_email, ''));
    $email->expectOnce('to', array(array($webmaster_email)));
    $email->expectOnce('subject');
    $email->expectOnce('message');

    $email->expectOnce('Send');
    $email->setReturnValue('Send', TRUE);

    $this->_subject->notify_site_admin_of_log_entry($entry, $email);
  }


  public function test__notify_site_admin_of_log_entry__success_no_site_name()
  {
    global $LANG, $PREFS;

    $entry_data = array(
      'addon_name'    => 'Example Add-on',
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'type'          => Omnilog_entry::ERROR
    );

    $entry = new Omnilog_entry($entry_data);

    $webmaster_email = 'webmaster@example.com';
    $webmaster_name = 'Lord Vancellator';
    $lang_subject   = 'Subject';

    $LANG->setReturnValue('line', $lang_subject, array('email_subject'));

    $PREFS->setReturnValue('ini', $webmaster_email, array('webmaster_email'));
    $PREFS->setReturnValue('ini', $webmaster_name, array('webmaster_name'));

    Mock::generate('Testee_mock_eemail', get_class($this) .'_mock_eemail');
    $email = $this->_get_mock('eemail');

    $email->expectOnce('valid_email', array($webmaster_email));
    $email->setReturnValue('valid_email', TRUE);

    $email->expectOnce('from', array($webmaster_email, $webmaster_name));
    $email->expectOnce('to', array(array($webmaster_email)));
    $email->expectOnce('subject', array($lang_subject));
    $email->expectOnce('message');
    
    $email->expectOnce('Send');
    $email->setReturnValue('Send', TRUE);

    $this->_subject->notify_site_admin_of_log_entry($entry, $email);
  }


  public function test__notify_site_admin_of_log_entry__missing_log_data()
  {
    global $LANG, $PREFS;

    $entry_data = array(
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'type'          => Omnilog_entry::ERROR
    );

    $entry = new Omnilog_entry($entry_data);

    $error_message = 'Error';
    $LANG->setReturnValue('line', $error_message, array('exception__notify_admin__missing_data'));

    $PREFS->expectNever('ini');

    Mock::generate('Testee_mock_eemail', get_class($this) .'_mock_eemail');
    $email = $this->_get_mock('eemail');

    $email->expectNever('valid_email');
    $email->expectNever('from');
    $email->expectNever('to');
    $email->expectNever('subject');
    $email->expectNever('message');
    $email->expectNever('Send');

    $this->expectException(new Exception($error_message));
    $this->_subject->notify_site_admin_of_log_entry($entry, $email);
  }


  public function test__notify_site_admin_of_log_entry__invalid_webmaster_email()
  {
    global $LANG, $PREFS;

    $entry_data = array(
      'addon_name'    => 'Example Add-on',
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'type'          => Omnilog_entry::ERROR
    );

    $entry = new Omnilog_entry($entry_data);

    $webmaster_email = 'invalid';

    $error_message = 'Error';
    $LANG->setReturnValue('line', $error_message, array('exception__notify_admin__invalid_webmaster_email'));

    $PREFS->expectOnce('ini', array('webmaster_email'));
    $PREFS->setReturnValue('ini', $webmaster_email);

    Mock::generate('Testee_mock_eemail', get_class($this) .'_mock_eemail');
    $email = $this->_get_mock('eemail');

    $email->expectOnce('valid_email', array($webmaster_email));
    $email->setReturnValue('valid_email', FALSE);

    $email->expectNever('from');
    $email->expectNever('to');
    $email->expectNever('subject');
    $email->expectNever('message');
    $email->expectNever('Send');

    $this->expectException(new Exception($error_message));
    $this->_subject->notify_site_admin_of_log_entry($entry, $email);
  }


  public function test__notify_site_admin_of_log_entry__email_not_sent()
  {
    global $LANG, $PREFS;

    $entry_data = array(
      'addon_name'    => 'Example Add-on',
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'type'          => Omnilog_entry::ERROR
    );

    $entry = new Omnilog_entry($entry_data);

    $webmaster_email = 'webmaster@example.com';

    $error_message = 'Error';
    $LANG->setReturnValue('line', $error_message, array('exception__notify_admin__email_not_sent'));

    $PREFS->setReturnValue('ini', $webmaster_email);

    Mock::generate('Testee_mock_eemail', get_class($this) .'_mock_eemail');
    $email = $this->_get_mock('eemail');

    $email->expectOnce('valid_email');
    $email->setReturnValue('valid_email', TRUE);

    $email->expectOnce('from');
    $email->expectOnce('to');
    $email->expectOnce('subject');
    $email->expectOnce('message');
    $email->expectOnce('Send');
    $email->setReturnValue('Send', FALSE);

    $this->expectException(new Exception($error_message));
    $this->_subject->notify_site_admin_of_log_entry($entry, $email);
  }


  public function test__save_entry_to_log__success()
  {
    global $DB;

    $entry_data = array(
      'addon_name'    => 'Example Add-on',
      'admin_emails'  => array('adam@ants.com', 'bob@dylan.com'),
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'notify_admin'  => FALSE,
      'type'          => Omnilog_entry::NOTICE
    );

    $insert_data = array(
      'addon_name'    => 'Example Add-on',
      'admin_emails'  => 'adam@ants.com|bob@dylan.com',
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'notify_admin'  => 'n',
      'type'          => Omnilog_entry::NOTICE,
      'site_id'       => $this->_site_id
    );

    $entry      = new Omnilog_entry($entry_data);
    $insert_id  = 10;
    $insert_sql = 'INSERT_SQL';

    $DB->expectOnce('table_exists', array('exp_omnilog_entries'));
    $DB->setReturnValue('table_exists', TRUE);

    $DB->expectOnce('insert_string',
      array('exp_omnilog_entries', $insert_data));

    $DB->setReturnValue('insert_string', $insert_sql);

    $DB->expectOnce('query', array($insert_sql));
    $DB->setReturnValue('__get', $insert_id, array('insert_id'));

    $expected_props = array_merge($entry_data,
      array('log_entry_id' => $insert_id));

    $expected_result = new Omnilog_entry($expected_props);
    $actual_result = $this->_subject->save_entry_to_log($entry);

    $this->assertIdentical($expected_result->to_array(TRUE),
      $actual_result->to_array(TRUE));
  }


  public function test__save_entry_to_log__success_with_notify_admin()
  {
    global $DB;

    $entry_data = array(
      'addon_name'    => 'Example Add-on',
      'admin_emails'  => array('adam@ants.com', 'bob@dylan.com'),
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'notify_admin'  => TRUE,
      'type'          => Omnilog_entry::ERROR
    );

    $insert_data = array(
      'addon_name'    => 'Example Add-on',
      'admin_emails'  => 'adam@ants.com|bob@dylan.com',
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'notify_admin'  => 'y',
      'type'          => Omnilog_entry::ERROR,
      'site_id'       => $this->_site_id
    );

    $entry      = new Omnilog_entry($entry_data);
    $insert_sql = 'INSERT_SQL';
    $insert_id  = 10;

    $DB->expectOnce('table_exists', array('exp_omnilog_entries'));
    $DB->setReturnValue('table_exists', TRUE);

    $DB->expectOnce('insert_string',
      array('exp_omnilog_entries', $insert_data));

    $DB->setReturnValue('insert_string', $insert_sql);

    $DB->expectOnce('query', array($insert_sql));

    $DB->setReturnValue('__get', $insert_id, array('insert_id'));

    $expected_props = array_merge($entry_data,
      array('log_entry_id' => $insert_id));

    $expected_result = new Omnilog_entry($expected_props);
    $actual_result = $this->_subject->save_entry_to_log($entry);

    $this->assertIdentical($expected_result->to_array(TRUE),
      $actual_result->to_array(TRUE));
  }


  public function test__save_entry_to_log__not_installed()
  {
    global $DB, $LANG;

    $exception_message = 'Exception';
    $LANG->setReturnValue('line', $exception_message);

    $DB->expectOnce('table_exists', array('exp_omnilog_entries'));
    $DB->setReturnValue('table_exists', FALSE);

    $DB->expectNever('insert_string');
    $DB->expectNever('query');

    $this->expectException(new Exception($exception_message));
    $this->_subject->save_entry_to_log(new Omnilog_entry());
  }


  public function test__save_entry_to_log__missing_entry_data()
  {
    global $DB, $LANG;

    $exception_message = 'Exception';
    $LANG->setReturnValue('line', $exception_message);

    $DB->expectOnce('table_exists', array('exp_omnilog_entries'));
    $DB->setReturnValue('table_exists', TRUE);

    $DB->expectNever('insert_string');
    $DB->expectNever('query');

    $this->expectException(new Exception($exception_message));
    $this->_subject->save_entry_to_log(new Omnilog_entry());
  }


  public function test__save_entry_to_log__no_insert_id()
  {
    global $DB, $LANG;

    $entry_props = array(
      'addon_name'    => 'Example Add-on',
      'date'          => time() - 100,
      'message'       => 'Example OmniLog entry.',
      'extended_data' => 'Example OmniLog extended data.',
      'type'          => Omnilog_entry::NOTICE
    );

    $entry = new Omnilog_entry($entry_props);

    $exception_message = 'Exception';
    $LANG->setReturnValue('line', $exception_message);

    $DB->expectOnce('table_exists', array('exp_omnilog_entries'));
    $DB->setReturnValue('table_exists', TRUE);

    $DB->expectOnce('insert_string');
    $DB->expectOnce('query');
    $DB->expectOnce('__get', array('insert_id'));
    $DB->setReturnValue('__get', 0, array('insert_id'));

    $this->expectException(new Exception($exception_message));
    $this->_subject->save_entry_to_log($entry);
  }


  public function test__uninstall_module__success()
  {
    global $DB;

    $module_id      = 10;
    $module_name    = ucfirst($this->_package_name);
    $db_select_row  = array('module_id' => (string) $module_id);

    $select_sql = "SELECT module_id FROM exp_modules
      WHERE module_name = '{$module_name}'
      LIMIT 1";

    $delete_groups_sql = "DELETE FROM exp_module_member_groups
      WHERE module_id = '{$module_id}'";

    $delete_modules_sql = "DELETE FROM exp_modules
      WHERE module_name = '{$module_name}'";

    $drop_table_sql = 'DROP TABLE IF EXISTS exp_omnilog_entries';

    $DB->expectCallCount('query', 4);

    $DB->expectAt(0, 'query',
      array(new EqualWithoutWhitespaceExpectation($select_sql)));

    $DB->setReturnReference('query', $db_select_result,
      array(new EqualWithoutWhitespaceExpectation($select_sql)));

    $db_select_result = $this->_get_mock('db_cache');
    $db_select_result->setReturnValue('__get', 1, array('num_rows'));
    $db_select_result->setReturnValue('__get', $db_select_row, array('row'));

    $DB->expectAt(1, 'query',
      array(new EqualWithoutWhitespaceExpectation($delete_groups_sql)));

    $DB->expectAt(2, 'query',
      array(new EqualWithoutWhitespaceExpectation($delete_modules_sql)));

    $DB->expectAt(3, 'query',
      array(new EqualWithoutWhitespaceExpectation($drop_table_sql)));

    $this->assertIdentical(TRUE, $this->_subject->uninstall_module());
  }


  public function test__uninstall_module__module_not_found()
  {
    global $DB;

    $module_name = ucfirst($this->_package_name);

    $select_sql = "SELECT module_id FROM exp_modules
      WHERE module_name = '{$module_name}'
      LIMIT 1";

    $DB->expectOnce('query',
      array(new EqualWithoutWhitespaceExpectation($select_sql)));

    $DB->setReturnReference('query', $db_select_result,
      array(new EqualWithoutWhitespaceExpectation($select_sql)));

    $db_select_result = $this->_get_mock('db_cache');
    $db_select_result->setReturnValue('__get', 0, array('num_rows'));

    $this->assertIdentical(FALSE, $this->_subject->uninstall_module());
      $db_module_result = $this->_get_mock('db_query');
  }


  public function test__update_package__no_update_required()
  {
    $installed_version = $this->_package_version;
    $this->assertIdentical(FALSE,
      $this->_subject->update_package($installed_version));
  }


  public function test__update_package__update_required()
  {
    /**
     * Arbitrarily high numbers, so no
     * update scripts are triggered.
     */

    $installed_version  = '10.0.0';
    $package_version    = '10.0.1';
    $package_name       = 'example_package';
    $subject            = new Omnilog_model($package_name, $package_version);

    $this->assertIdentical(TRUE, $subject->update_package($installed_version));
  }


  public function test__update_package__update_required_force_version_bump()
  {
    global $DB;

    /**
     * Arbitrarily high numbers, so no
     * update scripts are triggered.
     */

    $installed_version  = '10.0.0';
    $package_version    = '10.0.1';
    $package_name       = 'example_package';
    $subject            = new Omnilog_model($package_name, $package_version);
    $update_sql         = 'UPDATE_SQL';

    $DB->expectOnce('update_string', array(
      'exp_modules',
      array('module_version' => $package_version),
      array('module_name' => ucfirst($this->_package_name))
    ));

    $DB->setReturnValue('update_string', $update_sql);
    $DB->expectOnce('query', array($update_sql));

    $this->assertIdentical(TRUE,
      $subject->update_package($installed_version, TRUE));
  }


  public function test__update_package__no_installed_version()
  {
    $installed_version = '';
    $this->assertIdentical(FALSE,
      $this->_subject->update_package($installed_version));
  }


}


/* End of file      : test.omnilog_model.php */
/* File location    : third_party/omnilog/tests/test.omnilog_model.php */
