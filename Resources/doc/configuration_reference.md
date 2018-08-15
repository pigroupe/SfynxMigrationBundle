#Configuration Reference

All available configuration options are listed below with their default values.

``` yaml
#
# SfynxMigrationBundle configuration
#
sfynx_migration: 
    migration_dir: "%kernel.root_dir%/migration/" # directory of all migration files.
    version_dir: "%kernel.root_dir%/version/" # directory of the file where is saved the current migration version of the last migration played.
    version_filename: 'version.txt' # name of the file in which we save the current migration version
    debug: false # active or not the debug behavior
```
