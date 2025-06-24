<?php

namespace Drupal\Tests\civicrm\FunctionalJavascript;

use Drupal\Core\Database\Database;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Base class for FunctionalJavascript tests.
 */
abstract class CiviCrmTestBase extends WebDriverTestBase {

  protected $defaultTheme = 'starterkit_theme';

  protected static $modules = [
    'block',
    'civicrm',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanupEnvironment() {
    parent::cleanupEnvironment();
    $civicrm_test_conn = Database::getConnection('default', 'civicrm_test');
    // Disable foreign key checks so that tables can be dropped.
    $civicrm_test_conn->query('SET FOREIGN_KEY_CHECKS = 0;')->execute();
    $civicrm_schema = $civicrm_test_conn->schema();
    $tables = $civicrm_schema->findTables('%');
    // Comment out if you want to view the tables/contents before deleting them
    // throw new \Exception(var_export($tables, TRUE));
    foreach ($tables as $table) {
      if ($civicrm_schema->dropTable($table)) {
        unset($tables[$table]);
      }
    }
    $civicrm_test_conn->query('SET FOREIGN_KEY_CHECKS = 1;')->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function changeDatabasePrefix(): void {
    parent::changeDatabasePrefix();
    $connection_info = Database::getConnectionInfo('default');
    // CiviCRM does not leverage table prefixes, so we unset it. This way any
    // `civicrm_` tables are more easily cleaned up at the end of the test.
    $civicrm_connection_info = $connection_info['default'];
    unset($civicrm_connection_info['prefix']);
    Database::addConnectionInfo('civicrm_test', 'default', $civicrm_connection_info);
    Database::addConnectionInfo('civicrm', 'default', $civicrm_connection_info);

    // Assert that there are no `civicrm_` tables in the test database.
    $connection = Database::getConnection('default', 'civicrm_test');
    $schema = $connection->schema();
    $tables = $schema->findTables('civicrm_%');
    if (count($tables) > 0) {
      throw new \RuntimeException('The provided database connection in SIMPLETEST_DB contains CiviCRM tables, use a different database.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareSettings() {
    parent::prepareSettings();

    // Set the test environment variables for CiviCRM.
    $filename = $this->siteDirectory . '/settings.php';
    chmod($filename, 0666);

    $constants = <<<CONSTANTS

if (!defined('CIVICRM_CONTAINER_CACHE')) {
  define('CIVICRM_CONTAINER_CACHE', 'never');
}
if (!defined('CIVICRM_TEST')) {
  define('CIVICRM_TEST', 1);
}

CONSTANTS;

    file_put_contents($filename, $constants, FILE_APPEND);
  }

}
