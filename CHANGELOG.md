# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Set application name in .env file 

### Changed

### Removed

## [0.6.0] - 2024-05-08

### Added 

- Now, it is possible to provide name of the application directly with the install command : `larafil install myNewAwesomeProject`. This argument is optionnal, if you don't provide the name, Larafil will ask you for it.


## [0.5.0] - 2024-05-07

### Added 

- Larafil can now install some Filament packages automatically. It asks you during the installation. For now, only these plugins are supported as a proof of concept : 'None (default)', 'Breezy', 'Curator', 'Shield', 'Spatie Role Permissions'

## [0.4.0] - 2024-05-01

### Added 

- a `--filament-url` option to set an admin url different to default one 'admin'

## [0.3.1] - 2024-05-01

### Changed 

- Replace `--l10` option by `--laravel-version=previous` to install Laravel 10 instead of Laravel 11

## [0.3.0] - 2024-05-01

### Added 

- a `--l10` option to install Laravel 10 instead of Laravel 11

## [0.2.4] - 2024-05-01

### Removed

- Old composer.lock

## [0.2.3] - 2024-05-01

### Removed

- Databases options like migrate, db:wipe, migrate:xxx, etc

## [0.2.1] - 2024-05-01

### Added

- a `--self-update` option

## [0.2.0] - 2024-05-01

### Added

- a `--mysql` option to use mysql instead sqlite default one

## [0.1.4] - 2024-04-30

### Changed

- Add some informations on README

## [0.1.3] - 2024-04-30

### Changed

- Update install instructions on README

## [0.1.2] - 2024-04-30

### Changed

- Feat: Update app version number

## [0.1.1] - 2024-04-30

### Changed

- Feat: Update composer

## [0.1.0] - 2024-04-30

### Added

- Prepare for Packagist deployment

