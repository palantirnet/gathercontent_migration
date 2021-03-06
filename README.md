# GatherContent Migration

A Drupal 8 migration source plugin for the GatherContent API. Using this module and [Migrate Plus](https://www.drupal.org/project/migrate_plus), you can write `.yml` migration configurations that import content from GatherContent to Drupal content, including nodes, taxonomy terms, and menu items.

### Examples

For examples of how to use this migration source, see:

* [examples/migrate_plus.migration.gathercontent_items.yml](examples/migrate_plus.migration.gathercontent_items.yml)
* [examples/migrate_plus.migration.gathercontent_menu_links.yml](migrate_plus.migration.gathercontent_menu_links.yml)

### Configuration

You'll need to manually configure your GatherContent API credentials in order to use this module; there is currently no admin UI.

1. Log in to GatherContent and go to the [API section of your account settings](https://palantir.gathercontent.com/settings/api)
1. Click the "Generate a new API key" button
1. Add the email you use for GatherContent and your API key to your `settings.local.php` or `settings.php`:

 ```
  $config['gathercontent_migration.gathercontent']['email'] = 'YOUR-EMAIL@EXAMPLE.COM';
  $config['gathercontent_migration.gathercontent']['api_key'] = 'YOUR-KEY-HERE';
 ```

### Resources

* [Migration source plugins](https://www.drupal.org/node/2129649)
* [GatherContent API documentation](https://gathercontent.com/developers/)

### Copyright

Copyright 2016 Palantir.net, Inc.
