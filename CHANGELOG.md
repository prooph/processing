# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]

## [0.4.0] - 2015-05-10
### Fixes
- Aliasing the configuration with config caused an error when config is already registered as service name
  - Added a check to only set alias when it is not present

## Changed
- Name of the stream table from process_stream to prooph_processing_stream - BC Break!
- Use Prooph\Common\ServiceLocator as type hint in environment - BC Break!
- Use Prooph\Common\Event\... for triggering processor action events - BC Break!

## Added
- Environment now provides a EventStoreDoctrineSchema to help set up the stream table when using doctrine/migrations

## [0.3.0] - 2015-05-09
### Added
- Add change log
- All targets wildcard (*) for channel matching
- Switch to new prooph components versions
  - PES 3.x
  - PSB 3.x
  - EventSourcing 2.x
  - common ~1.5
- Renamed message property createdOn to createdAt and changed type to \DateTimeImmutable


## [0.2.0] - 2015-03-13
- [Release Notes](https://github.com/prooph/processing/releases/tag/v0.2)

[unreleased]: https://github.com/prooph/processing/compare/v0.3...HEAD
[0.4.0]: https://github.com/prooph/processing/compare/v0.3...v0.4
[0.3.0]: https://github.com/prooph/processing/compare/v0.2...v0.3
