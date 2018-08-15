# Summary

- [Basic command](#Basic-command)
- [Command options](#Command options)

## Basic command

To play all migrations from current version, which has been saved in the `version_dir` repository
(cf reference configuration), execute the following command

```sh
$ php app/console sfynx:migration
```

What is important to know:
- In the end of execution of all migrations, the command store in the `version_filename` file (cf reference configuration)
the last migration played.
- The information stored is the timestamp of the migration filename.
- A migration file must be created with a specific name construct like this: `Migration_<timestamp>.php`
- The current migration vesrion is store in file and not in a database table in order to play migrations
with multiple entityManager which can be connected to any databases.

## Command options

Below the possible options of the command

```
--currentVersion <timestamp>   : force the version of migration instead of get timestamp value from versionFilename file
--migrationDir <pathDirectory> : directory with all migration scripts
--versionDir <pathDirectory>   : directory to store the last current version of migration
--versionFilename <fileName>   : filename to store the last current version of migration
--debug <true|false>           : with true value, force all migrations to run from the current version despite erroneous migrations
```
