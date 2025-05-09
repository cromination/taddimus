Feature: Manage WordPress themes

  Scenario: Installing and deleting theme
    Given a WP install
    And I run `wp theme delete --all --force`
    And I run `wp theme install twentyeleven --activate`

    When I run `wp theme install twentytwelve`
    Then STDOUT should not be empty

    When I run `wp theme status twentytwelve`
    Then STDOUT should contain:
      """
      Theme twentytwelve details:
          Name: Twenty Twelve
      """

    When I run `wp theme path twentytwelve`
    Then STDOUT should contain:
      """
      /themes/twentytwelve/style.css
      """

    When I run `wp option get stylesheet`
    Then save STDOUT as {PREVIOUS_THEME}

    When I run `wp theme activate twentytwelve`
    Then STDOUT should be:
      """
      Success: Switched to 'Twenty Twelve' theme.
      """

    When I try `wp theme delete twentytwelve`
    Then STDERR should be:
      """
      Warning: Can't delete the currently active theme: twentytwelve
      Error: No themes deleted.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp theme activate {PREVIOUS_THEME}`
    Then STDOUT should not be empty

    When I run `wp theme delete twentytwelve`
    Then STDOUT should not be empty

    When I try the previous command again
    Then STDERR should be:
      """
      Warning: The 'twentytwelve' theme could not be found.
      """
    And STDOUT should be:
      """
      Success: Theme already deleted.
      """
    And the return code should be 0

    When I run `wp theme list`
    Then STDOUT should not be empty

  Scenario: Checking theme status without theme parameter
    Given a WP install

    When I run `wp theme install classic --activate`
    And I run `wp theme list --field=name --status=inactive | xargs wp theme delete`
    And I run `wp theme status`
    Then STDOUT should be:
      """
      1 installed theme:
        A classic 1.6

      Legend: A = Active
      """

  Scenario: Install a theme, activate, then force install an older version of the theme
    Given a WP install
    And I run `wp theme delete --all --force`
    And I run `wp theme install twentyeleven --activate`

    When I run `wp theme install twentytwelve --version=1.4`
    Then STDOUT should not be empty

    When I run `wp theme list --name=twentytwelve --field=update_version`
    Then STDOUT should not be empty
    And save STDOUT as {UPDATE_VERSION}

    When I run `wp theme list`
    Then STDOUT should be a table containing rows:
      | name            | status   | update    | version | update_version   | auto_update |
      | twentytwelve    | inactive | available | 1.4     | {UPDATE_VERSION} | off         |

    When I run `wp theme activate twentytwelve`
    Then STDOUT should not be empty

    # Ensure no other themes interfere with update.
    When I run `wp theme list --status=inactive --field=name | xargs wp theme delete`
    Then STDOUT should contain:
      """
      Success: Deleted
      """

    When I run `wp theme install twentytwelve --version=1.5 --force`
    Then STDOUT should not be empty

    When I run `wp theme list`
    Then STDOUT should be a table containing rows:
      | name            | status   | update    | version | update_version   | auto_update |
      | twentytwelve    | active   | available | 1.5     | {UPDATE_VERSION} | off         |

    When I try `wp theme update`
    Then STDERR should be:
      """
      Error: Please specify one or more themes, or use --all.
      """
    And the return code should be 1

    When I run `wp theme update --all --format=summary | grep 'updated successfully from'`
    Then STDOUT should contain:
      """
      Twenty Twelve updated successfully from version 1.5 to version
      """

    When I run `wp theme install twentytwelve --version=1.4 --force`
    Then STDOUT should not be empty

    When I run `wp theme update --all`
    Then STDOUT should contain:
      """
      Success: Updated 1 of 1 themes.
      """

  Scenario: Exclude theme from bulk updates.
    Given a WP install
    And I run `wp theme delete --all --force`
    And I run `wp theme install twentyeleven --activate`

    When I run `wp theme install twentytwelve --version=1.4 --force`
    Then STDOUT should contain:
      """
      Downloading install
      """
    And STDOUT should contain:
      """
      package from https://downloads.wordpress.org/theme/twentytwelve.1.4.zip...
      """

    When I run `wp theme activate twentytwelve`
    Then STDOUT should not be empty

    # Ensure no other themes interfere with update.
    When I run `wp theme list --status=inactive --field=name | xargs wp theme delete`
    Then STDOUT should contain:
      """
      Success: Deleted
      """

    When I run `wp theme status twentytwelve`
    Then STDOUT should contain:
      """
      Update available
      """

    When I run `wp theme update --all --exclude=twentytwelve | grep 'Skipped'`
    Then STDOUT should contain:
      """
      twentytwelve
      """

    When I run `wp theme status twentytwelve`
    Then STDOUT should contain:
      """
      Update available
      """

  Scenario: Get the path of an installed theme
    Given a WP install
    And I run `wp theme delete --all --force`

    When I run `wp theme install twentytwelve`
    Then STDOUT should not be empty

    When I run `wp theme path twentytwelve --dir`
    Then STDOUT should contain:
      """
      wp-content/themes/twentytwelve
      """

  Scenario: Activate an already active theme
    Given a WP install
    And I run `wp theme delete --all --force`

    When I run `wp theme install twentytwelve`
    Then STDOUT should not be empty

    When I run `wp theme activate twentytwelve`
    Then STDOUT should be:
      """
      Success: Switched to 'Twenty Twelve' theme.
      """

    When I try `wp theme activate twentytwelve`
    Then STDERR should be:
      """
      Warning: The 'Twenty Twelve' theme is already active.
      """
    And STDOUT should be empty
    And the return code should be 0

  @require-wp-5.3
  Scenario: Flag `--skip-update-check` skips update check when running `wp theme list`
    Given a WP install

    When I run `wp theme install astra --version=1.0.0`
    Then STDOUT should contain:
      """
      Theme installed successfully.
      """

    When I run `wp theme list --fields=name,status,update`
    Then STDOUT should be a table containing rows:
      | name      | status   | update    |
      | astra     | inactive | available |

    When I run `wp transient delete update_themes --network`
    Then STDOUT should be:
      """
      Success: Transient deleted.
      """

    When I run `wp theme list --fields=name,status,update --skip-update-check`
    Then STDOUT should be a table containing rows:
      | name      | status   | update |
      | astra     | inactive | none   |

  Scenario: Doing wp theme list does a force check by default, deleting any existing transient values
    Given a WP install

    When I run `wp theme list`
    Then STDOUT should not be empty

    When I run `wp eval 'echo get_site_transient("update_themes")->last_checked;'`
    Then save STDOUT as {LAST_UPDATED}

    When I run `wp theme list --skip-update-check`
    Then STDOUT should not be empty

    When I run `wp eval 'echo get_site_transient("update_themes")->last_checked;'`
    Then STDOUT should be:
      """
      {LAST_UPDATED}
      """

    When I run `wp theme list`
    Then STDOUT should not be empty

    When I run `wp eval 'echo get_site_transient("update_themes")->last_checked;'`
    Then STDOUT should not contain:
      """
      {LAST_UPDATED}
      """

  Scenario: Install a theme when the theme directory doesn't yet exist
    Given a WP install
    And I run `wp theme delete --all --force`

    When I run `rm -rf wp-content/themes`
    And I run `if test -d wp-content/themes; then echo "fail"; fi`
    Then STDOUT should be empty

    When I run `wp theme install twentytwelve --activate`
    Then STDOUT should not be empty

    When I run `wp theme list --fields=name,status`
    Then STDOUT should be a table containing rows:
      | name            | status   |
      | twentytwelve    | active   |

  Scenario: Attempt to activate or fetch a broken theme
    Given a WP install

    When I run `mkdir -pv wp-content/themes/myth`
    Then the wp-content/themes/myth directory should exist

    When I try `wp theme activate myth`
    Then STDERR should contain:
      """
      Error: Stylesheet is missing.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme get myth`
    Then STDERR should contain:
      """
      Error: Stylesheet is missing.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme status myth`
    Then STDERR should be:
      """
      Error: Stylesheet is missing.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp theme install myth --force`
    Then STDOUT should contain:
      """
      Theme updated successfully.
      """

  @require-wp-5.7
  Scenario: Enabling and disabling a theme
    Given a WP multisite install
    And I run `wp theme install moina`
    And I run `wp theme install moina-blog`

    When I try `wp option get allowedthemes`
    Then the return code should be 1
    # STDERR may or may not be empty, depending on WP-CLI version.
    And STDOUT should be empty

    When I run `wp theme enable moina-blog`
    Then STDOUT should contain:
      """
      Success: Enabled the 'Moina Blog' theme.
      """

    When I run `wp option get allowedthemes`
    Then STDOUT should contain:
      """
      'moina-blog' => true
      """

    When I run `wp theme disable moina-blog`
    Then STDOUT should contain:
      """
      Success: Disabled the 'Moina Blog' theme.
      """

    When I run `wp option get allowedthemes`
    Then STDOUT should not contain:
      """
      'moina-blog' => true
      """

    When I run `wp theme enable moina-blog --activate`
    Then STDOUT should contain:
      """
      Success: Enabled the 'Moina Blog' theme.
      Success: Switched to 'Moina Blog' theme.
      """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp network-meta get 1 allowedthemes`
    Then STDOUT should not contain:
      """
      'moina-blog' => true
      """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp theme enable moina-blog --network`
    Then STDOUT should contain:
      """
      Success: Network enabled the 'Moina Blog' theme.
      """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp network-meta get 1 allowedthemes`
    Then STDOUT should contain:
      """
      'moina-blog' => true
      """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp theme disable moina-blog --network`
    Then STDOUT should contain:
      """
      Success: Network disabled the 'Moina Blog' theme.
      """

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp network-meta get 1 allowedthemes`
    Then STDOUT should not contain:
      """
      'moina-blog' => true
      """

  Scenario: Enabling and disabling a theme without multisite
    Given a WP install

    When I try `wp theme enable twentytwelve`
    Then STDERR should contain:
      """
      Error: This is not a multisite install
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme disable twentytwelve`
    Then STDERR should contain:
      """
      Error: This is not a multisite install
      """
    And STDOUT should be empty
    And the return code should be 1

  @require-wp-5.7
  Scenario: Install and attempt to activate a child theme without its parent
    Given a WP install
    And I run `wp theme install moina-blog`
    And I run `rm -rf wp-content/themes/moina`

    When I try `wp theme activate moina-blog`
    Then STDERR should contain:
      """
      Error: The parent theme is missing. Please install the "moina" parent theme.
      """
    And STDOUT should be empty
    And the return code should be 1

  @require-wp-5.7
  Scenario: List an active theme with its parent
    Given a WP install
    And I run `wp theme install moina`
    And I run `wp theme install --activate moina-blog`

    # Hybrid_Registry throws warning for PHP 8+.
    When I try `wp theme list --fields=name,status`
    Then STDOUT should be a table containing rows:
      | name          | status   |
      | moina-blog    | active   |
      | moina         | parent   |

  Scenario: When updating a theme --format should be the same when using --dry-run
    Given a WP install
    And I run `wp theme delete --all --force`

    When I run `wp theme install --force twentytwelve --version=1.0`
    Then STDOUT should not be empty

    When I run `wp theme list --name=twentytwelve --field=update_version`
    Then save STDOUT as {UPDATE_VERSION}

    When I run `wp theme update twentytwelve --format=summary --dry-run`
    Then STDOUT should contain:
      """
      Available theme updates:
      Twenty Twelve update from version 1.0 to version {UPDATE_VERSION}
      """

    When I run `wp theme update twentytwelve --format=json --dry-run`
    Then STDOUT should be JSON containing:
      """
      [{"name":"twentytwelve","status":"inactive","version":"1.0","update_version":"{UPDATE_VERSION}"}]
      """

    When I run `wp theme update twentytwelve --format=csv --dry-run`
    Then STDOUT should contain:
      """
      name,status,version,update_version
      twentytwelve,inactive,1.0,{UPDATE_VERSION}
      """

  Scenario: When updating a theme --dry-run cannot be used when specifying a specific version.
    Given a WP install

    When I try `wp theme update --all --version=whatever --dry-run`
    Then STDERR should be:
      """
      Error: --dry-run cannot be used together with --version.
      """
    And the return code should be 1

  Scenario: Check json and csv formats when updating a theme
    Given a WP install
    And I run `wp theme delete --all --force`

    When I run `wp theme install --force twentytwelve --version=1.0`
    Then STDOUT should not be empty

    When I run `wp theme list --name=twentytwelve --field=update_version`
    Then save STDOUT as {UPDATE_VERSION}

    When I run `wp theme update twentytwelve --format=json`
    Then STDOUT should contain:
      """
      [{"name":"twentytwelve","old_version":"1.0","new_version":"{UPDATE_VERSION}","status":"Updated"}]
      """

    When I run `wp theme install --force twentytwelve --version=1.0`
    Then STDOUT should not be empty

    When I run `wp theme update twentytwelve --format=csv`
    Then STDOUT should contain:
      """
      name,old_version,new_version,status
      twentytwelve,1.0,{UPDATE_VERSION},Updated
      """

  @require-wp-5.7
  Scenario: Automatically install parent theme for a child theme
    Given a WP install

    When I try `wp theme status moina`
    Then STDERR should contain:
      """
      Error: The 'moina' theme could not be found.
      """
    And STDOUT should be empty
    And the return code should be 1

    When I run `wp theme install moina-blog`
    Then STDOUT should contain:
      """
      This theme requires a parent theme. Checking if it is installed
      """

    When I run `wp theme status moina`
    Then STDOUT should contain:
      """
      Theme moina details:
      """
    And STDERR should be empty

  Scenario: Get status field in theme detail
    Given a WP install
    And I run `wp theme delete --all --force`

    When I run `wp theme install twentytwelve`
    Then STDOUT should not be empty

    When I run `wp theme get twentytwelve`
    Then STDOUT should be a table containing rows:
    | Field   | Value     |
    | status  | inactive  |

    When I run `wp theme get twentytwelve --field=status`
    Then STDOUT should be:
      """
      inactive
      """

    When I run `wp theme activate twentytwelve`
    Then STDOUT should not be empty

    When I run `wp theme get twentytwelve --field=status`
    Then STDOUT should be:
      """
      active
      """

  Scenario: Theme activation fails when slug does not match exactly
    Given a WP install
    And I run `wp theme delete --all --force`

    When I run `wp theme install twentytwelve`
    Then the return code should be 0

    When I try `wp theme activate TwentyTwelve`
    Then STDERR should contain:
      """
      Error: The 'TwentyTwelve' theme could not be found. Did you mean 'twentytwelve'?
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme activate twentytwelve3`
    Then STDERR should contain:
      """
      Error: The 'twentytwelve3' theme could not be found. Did you mean 'twentytwelve'?
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme activate twentytwelves2`
    Then STDERR should contain:
      """
      Error: The 'twentytwelves2' theme could not be found. Did you mean 'twentytwelve'?
      """
    And STDOUT should be empty
    And the return code should be 1

    When I try `wp theme activate completelyoff`
    Then STDERR should contain:
      """
      Error: The 'completelyoff' theme could not be found.
      """
    And STDERR should not contain:
      """
      Did you mean
      """
    And STDOUT should be empty
    And the return code should be 1

  Scenario: Only valid status filters are accepted when listing themes
    Given a WP install

    When I run `wp theme list`
    Then STDERR should be empty

    When I run `wp theme list --status=active`
    Then STDERR should be empty

    When I try `wp theme list --status=invalid-status`
    Then STDERR should be:
      """
      Error: Parameter errors:
       Invalid value specified for 'status' (Filter the output by theme status.)
      """
  @require-wp-5.7
  Scenario: Parent theme is active when its child is active
    Given a WP install
    And I run `wp theme delete --all --force`
    And I run `wp theme install twentytwelve`
    And I run `wp theme install moina-blog --activate`

    When I run `wp theme is-active moina-blog`
    Then the return code should be 0

    When I run `wp theme is-active moina`
    Then the return code should be 0

    When I try `wp theme is-active twentytwelve`
    Then the return code should be 1

  Scenario: Excluding a missing theme should not throw an error
    Given a WP install
    And I run `wp theme delete --all --force`
    And I run `wp theme install twentytwelve --version=1.5 --activate`
    And I run `wp theme update --all --exclude=missing-theme`
    Then STDERR should be empty
    And STDOUT should contain:
      """
      Success:
      """
    And the return code should be 0

  @require-wp-5.5
  Scenario: Listing themes should include auto_update
    Given a WP install
    When I run `wp theme list --fields=auto_update`
    Then STDOUT should be a table containing rows:
      | auto_update          |
      | off                  |

    When I run `wp theme auto-updates enable --all`
    And I try `wp theme list --fields=auto_update`
    Then STDOUT should be a table containing rows:
      | auto_update          |
      | on                   |

  Scenario: Show theme update as unavailable if it doesn't meet WordPress requirements
    Given a WP install
    And a wp-content/themes/example/style.css file:
      """
      /*
      Theme Name: example
      Version: 1.0.0
      */
      """
    And a wp-content/themes/example/index.php file:
      """
      <?php
      // Silence is golden.
      """
    And that HTTP requests to https://api.wordpress.org/themes/update-check/1.1/ will respond with:
      """
      HTTP/1.1 200 OK

      {
        "themes": {
          "example": {
            "theme": "example",
            "new_version": "2.0.0",
            "requires": "100",
            "requires_php": "5.6"
          }
        },
        "translations": [],
        "no_update": []
      }
      """

    When I run `wp theme list`
    Then STDOUT should be a table containing rows:
      | name            | status   | update       | version  | update_version   | auto_update | requires   | requires_php   |
      | example         | inactive | unavailable  | 1.0.0    | 2.0.0            | off         | 100        | 5.6            |

    When I try `wp theme update example`
    Then STDERR should contain:
      """
      Warning: example: This update requires WordPress version 100
      """

  Scenario: Show theme update as unavailable if it doesn't meet PHP requirements
    Given a WP install
    And a wp-content/themes/example/style.css file:
      """
      /*
      Theme Name: example
      Version: 1.0.0
      */
      """
    And a wp-content/themes/example/index.php file:
      """
      <?php
      // Silence is golden.
      """
    And that HTTP requests to https://api.wordpress.org/themes/update-check/1.1/ will respond with:
      """
      HTTP/1.1 200 OK

      {
        "themes": {
          "example": {
            "theme": "example",
            "new_version": "2.0.0",
            "requires": "3.7",
            "requires_php": "100"
          }
      },
        "translations": [],
        "no_update": []
      }
      """

    When I run `wp theme list`
    Then STDOUT should be a table containing rows:
      | name            | status   | update       | version  | update_version   | auto_update | requires   | requires_php   |
      | example         | inactive | unavailable  | 1.0.0    | 2.0.0            | off         | 3.7        | 100            |

    When I try `wp theme update example`
    Then STDERR should contain:
      """
      Warning: example: This update requires PHP version 100
      """
