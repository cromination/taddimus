Feature: Manage WP-Cron events and schedules

  Background:
    Given a WP install
    And I run `wp config set DISABLE_WP_CRON false --raw --type=constant --anchor="if ( ! defined( 'DISABLE_WP_CRON' ) )"`

  Scenario: Scheduling and then deleting an event
    When I run `wp cron event schedule wp_cli_test_event_1 '+1 hour 5 minutes' --0=banana`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_1'
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence,args`
    Then STDOUT should be CSV containing:
      | hook                | recurrence      | args       |
      | wp_cli_test_event_1 | Non-repeating   | ["banana"] |

    When I run `wp cron event list --fields=hook,next_run_relative | grep wp_cli_test_event_1`
    Then STDOUT should contain:
      """
      1 hour
      """

    When I run `wp cron event list --hook=wp_cli_test_event_1 --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp cron event list --hook=apple --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `wp cron event delete wp_cli_test_event_1`
    Then STDOUT should contain:
      """
      Success: Deleted a total of 1 cron event.
      """

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_1
      """

  Scenario: Scheduling and then running an event
    When I run `wp cron event schedule wp_cli_test_event_3 '-1 minutes'`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_3'
      """

    When I run `wp cron event schedule wp_cli_test_event_4`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_4'
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence`
    Then STDOUT should be CSV containing:
      | hook                | recurrence    |
      | wp_cli_test_event_3 | Non-repeating |

    When I run `wp cron event run wp_cli_test_event_3`
    Then STDOUT should not be empty

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_3
      """

  Scenario: Scheduling, running, and deleting duplicate events
    When I run `wp cron event schedule wp_cli_test_event_5 '+20 minutes' --0=banana`
    And I run `wp cron event schedule wp_cli_test_event_5 '+20 minutes' --0=bar`
    Then STDOUT should not be empty

    When I run `wp cron event list --format=csv --fields=hook,recurrence,args`
    Then STDOUT should be CSV containing:
      | hook                | recurrence    | args       |
      | wp_cli_test_event_5 | Non-repeating | ["banana"] |
      | wp_cli_test_event_5 | Non-repeating | ["bar"]    |

    When I run `wp cron event run wp_cli_test_event_5`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_cli_test_event_5'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_cli_test_event_5'
      """
    And STDOUT should contain:
      """
      Success: Executed a total of 2 cron events.
      """

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_5
      """

    When I try `wp cron event run wp_cli_test_event_5`
    Then STDERR should be:
      """
      Error: Invalid cron event 'wp_cli_test_event_5'
      """

    When I run `wp cron event schedule wp_cli_test_event_5 '+20 minutes' --0=banana`
    And I run `wp cron event schedule wp_cli_test_event_5 '+20 minutes' --0=bar`
    Then STDOUT should not be empty

    When I run `wp cron event list`
    Then STDOUT should contain:
      """
      wp_cli_test_event_5
      """

    When I run `wp cron event delete wp_cli_test_event_5`
    Then STDOUT should be:
      """
      Success: Deleted a total of 2 cron events.
      """

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_5
      """

    When I try `wp cron event delete wp_cli_test_event_5`
    Then STDERR should be:
      """
      Error: Invalid cron event 'wp_cli_test_event_5'
      """
  Scenario: Cron event with missing recurrence should be non-repeating.
    Given a wp-content/mu-plugins/schedule.php file:
      """
      <?php
      add_filter(
        'cron_schedules',
        function( $schedules ) {
          $schedules['test_schedule'] = array(
            'interval' => 3600,
            'display'  => __( 'Every Hour' ),
          );
          return $schedules;
        }
      );
      """

    When I run `wp cron event schedule wp_cli_test_event "1 hour" test_schedule`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event'
      """

    When I run `wp cron event list --hook=wp_cli_test_event --fields=hook,recurrence`
    Then STDOUT should be a table containing rows:
      | hook               | recurrence    |
      | wp_cli_test_event  | 1 hour        |

    When I run `rm wp-content/mu-plugins/schedule.php`
    Then the return code should be 0

    When I run `wp cron event list --hook=wp_cli_test_event --fields=hook,recurrence`
    Then STDOUT should be a table containing rows:
      | hook               | recurrence    |
      | wp_cli_test_event  | Non-repeating |

  Scenario: Scheduling and then running a re-occurring event
    When I run `wp cron event schedule wp_cli_test_event_4 now hourly`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_4'
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence`
    Then STDOUT should be CSV containing:
      | hook                | recurrence    |
      | wp_cli_test_event_4 | 1 hour        |

    When I run `wp cron event run wp_cli_test_event_4`
    Then STDOUT should not be empty

    When I run `wp cron event list`
    Then STDOUT should contain:
      """
      wp_cli_test_event_4
      """

  Scenario: Scheduling and then deleting a recurring event
    When I run `wp cron event schedule wp_cli_test_event_2 now daily`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_2'
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence`
    Then STDOUT should be CSV containing:
      | hook                | recurrence |
      | wp_cli_test_event_2 | 1 day      |

    When I run `wp cron event delete wp_cli_test_event_2`
    Then STDOUT should contain:
      """
      Success: Deleted a total of 1 cron event.
      """

    When I run `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_2
      """

  Scenario: Listing cron schedules
    When I run `wp cron schedule list --format=csv --fields=name,interval`
    Then STDOUT should be CSV containing:
      | name   | interval |
      | hourly | 3600     |

  Scenario: Testing WP-Cron
    Given a php.ini file:
      """
      error_log = {RUN_DIR}/server.log
      log_errors = on
      """
    And I launch in the background `wp server --host=localhost --port=8080 --config=php.ini`
    And a wp-content/mu-plugins/set_cron_site_url.php file:
      """
      <?php
      add_filter( 'cron_request', static function ( $cron_request_array ) {
        $cron_request_array['url']               = 'http://localhost:8080';
        $cron_request_array['args']['sslverify'] = false;
        return $cron_request_array;
      } );
      """

    When I run `wp cron event schedule wp_cli_test_event_1 '+1 hour 5 minutes' --0=banana`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_1'
      """

    When I run `wp cron test`
    Then STDOUT should contain:
      """
      Success: WP-Cron spawning is working as expected.
      """
    And STDERR should not contain:
      """
      Error:
      """

    # Normally we would simply check for the log file to not exist. However, when running with Xdebug for code coverage purposes,
    # the following warning might be added to the log file:
    # PHP Warning: JIT is incompatible with third party extensions that override zend_execute_ex(). JIT disabled. in Unknown on line 0
    # This workaround checks for any other possible entries in the log file.
    When I run `awk '!/JIT/' {RUN_DIR}/server.log 2>/dev/null || true`
    Then STDOUT should be empty

  Scenario: Run multiple cron events
    When I try `wp cron event run`
    Then STDERR should be:
      """
      Error: Please specify one or more cron events, or use --due-now/--all.
      """

    When I run `wp cron event run wp_version_check wp_update_plugins`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_version_check'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_plugins'
      """
    And STDOUT should contain:
      """
      Success: Executed a total of 2 cron events.
      """

    # WP throws a notice here for older versions of core.
    When I try `wp cron event run --all`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_version_check'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_plugins'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_themes'
      """
    And STDOUT should contain:
      """
      Success: Executed a total of
      """

  # Fails on WordPress 4.9 because `wp cron event run --due-now`
  # executes the "wp_privacy_delete_old_export_files" event there.
  @require-wp-5.0
  Scenario: Run currently scheduled events
    # WP throws a notice here for older versions of core.
    When I try `wp cron event run --all`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_version_check'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_plugins'
      """
    And STDOUT should contain:
      """
      Executed the cron event 'wp_update_themes'
      """
    And STDOUT should contain:
      """
      Success: Executed a total of
      """

    When I run `wp cron event run --due-now`
    Then STDOUT should contain:
      """
      Executed a total of 0 cron events
      """

    When I run `wp cron event schedule wp_cli_test_event_1 now hourly`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_1'
      """

    When I run `wp cron event run --due-now`
    Then STDOUT should contain:
      """
      Executed the cron event 'wp_cli_test_event_1'
      """
    And STDOUT should contain:
      """
      Executed a total of 1 cron event
      """

    When I run `wp cron event run --due-now`
    Then STDOUT should contain:
      """
      Executed a total of 0 cron events
      """

  Scenario: Don't trigger cron when ALTERNATE_WP_CRON is defined
    Given a alternate-wp-cron.php file:
      """
      <?php
      define( 'ALTERNATE_WP_CRON', true );
      """
    And a wp-cli.yml file:
      """
      require:
        - alternate-wp-cron.php
      """

    When I run `wp eval 'var_export( ALTERNATE_WP_CRON );'`
    Then STDOUT should be:
      """
      true
      """

    When I run `wp option get home`
    Then STDOUT should be:
      """
      https://example.com
      """

  Scenario: Listing duplicated cron events
    When I run `wp cron event schedule wp_cli_test_event_1 '+1 hour 5 minutes' hourly`
    Then STDOUT should not be empty

    When I run `wp cron event schedule wp_cli_test_event_1 '+1 hour 6 minutes' hourly`
    Then STDOUT should not be empty

    When I run `wp cron event list --format=ids`
    Then STDOUT should contain:
      """
      wp_cli_test_event_1 wp_cli_test_event_1
      """

  Scenario: Scheduling an event with non-numerically indexed arguments
    When I try `wp cron event schedule wp_cli_test_args_event '+10 minutes' --foo=banana --bar=apple`
    Then STDOUT should not be empty
    And STDERR should be:
      """
      Warning: Numeric keys should be used for the hook arguments.
      """

    When I run `wp cron event list --format=csv --fields=hook,recurrence,args`
    Then STDOUT should be CSV containing:
      | hook                   | recurrence    | args                           |
      | wp_cli_test_args_event | Non-repeating | {"foo":"banana","bar":"apple"} |

  Scenario: Warn when an invalid cron event is detected
    Given a WP install
    And a update.php file:
      """
      <?php
      $val = array(
        1647914509 => array(
          'postindexer_secondpass_cron' => array(
            '40cd750bba9870f18aada2478b24840a' => array(
              'schedule' => '5mins',
              'args' => array(),
              'interval' => 100,
            ),
          ),
        ),
        'wp_batch_split_terms' => array(
          1442323165 => array(
            '40cd750bba9870f18aada2478b24840a' => array(
              'schedule' => false,
              'args' => array()
            )
          )
        )
      );
      update_option( 'cron', $val );
      """
    And I run `wp eval-file update.php`

    When I try `wp cron event list`
    Then STDOUT should contain:
      """
      postindexer_secondpass_cron
      """
    And STDERR should contain:
      """
      Warning: Ignoring incorrectly registered cron event "wp_batch_split_terms".
      """

  Scenario: Delete multiple cron events
    When I run `wp cron event schedule wp_cli_test_event_1 '+1 hour 5 minutes' hourly`
    Then STDOUT should not be empty

    When I run `wp cron event schedule wp_cli_test_event_2 '+1 hour 5 minutes' hourly`
    Then STDOUT should not be empty

    When I try `wp cron event delete`
    Then STDERR should be:
      """
      Error: Please specify one or more cron events, or use --due-now/--all.
      """

    # WP throws a notice here for older versions of core.
    When I try `wp cron event delete --all`
    Then STDOUT should contain:
      """
      Success: Deleted a total of
      """

    When I try `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_1
      """
    And STDOUT should not contain:
      """
      wp_cli_test_event_2
      """

    When I run `wp cron event schedule wp_cli_test_event_1 now hourly`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_1'
      """

    When I run `wp cron event schedule wp_cli_test_event_2 now hourly`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_2'
      """

    When I run `wp cron event schedule wp_cli_test_event_2 '+1 hour 5 minutes' hourly`
    Then STDOUT should contain:
      """
      Success: Scheduled event with hook 'wp_cli_test_event_2'
      """

    When I run `wp cron event delete wp_cli_test_event_2 --due-now`
    Then STDOUT should contain:
      """
      Deleted a total of 1 cron event
      """

    When I try `wp cron event list`
    Then STDOUT should contain:
      """
      wp_cli_test_event_2
      """

    When I run `wp cron event list --hook=wp_cli_test_event_2 --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `wp cron event delete --due-now`
    Then STDOUT should contain:
      """
      Success: Deleted a total of
      """

    When I try `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_1
      """
    And STDOUT should contain:
      """
      wp_cli_test_event_2
      """

    When I run `wp cron event schedule wp_cli_test_event_1 '+1 hour 5 minutes' hourly`
    Then STDOUT should not be empty

    When I run `wp cron event schedule wp_cli_test_event_2 '+1 hour 5 minutes' hourly`
    Then STDOUT should not be empty

    When I run `wp cron event delete --all --exclude=wp_cli_test_event_1`
    Then STDOUT should contain:
      """
      Success: Deleted a total of
      """

    When I try `wp cron event list`
    Then STDOUT should not contain:
      """
      wp_cli_test_event_2
      """
    And STDOUT should contain:
      """
      wp_cli_test_event_1
      """

  Scenario: A valid combination of parameters should be present
    When I try `wp cron event delete --due-now --all`
    Then STDERR should be:
      """
      Error: Please use either --due-now or --all.
      """

    When I try `wp cron event delete wp_cli_test_event_1 --all`
    Then STDERR should be:
      """
      Error: Please either specify cron events, or use --all.
      """
