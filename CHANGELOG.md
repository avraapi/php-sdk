# Changelog

All notable changes to the AvraAPI PHP SDK are documented in this file.

This project follows [Semantic Versioning](https://semver.org/) and the
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format.

## [Unreleased]

## [1.5.2] - 2026-09-30

### Added

- Added `withPrivacyMode()` to every provider service. It sends
  `X-Privacy-Mode: 1` for the next SDK request only, then automatically clears
  the setting.

### Changed

- Kept Location and Utility `privacyMode: true` arguments backward compatible
  while routing them through the same Privacy Mode header mechanism.

## [1.5.1]

### Changed

- Refreshed the public README and aligned package guidance with
  [AvraAPI Documentation](https://docs.avraapi.com).

## [1.5.0]

### Added

- Added full Universal Payment Gateway integration and the SDK foundation for
  future advanced services.

## [1.1.2]

### Added

- Added Currency Service and Security Service functions.

## [1.0.2]

### Added

- Added the Universal Function for newly available API endpoints.

### Fixed

- Included small compatibility and bug fixes.

## [1.0.0]

### Added

- Initial public release with the base microservice integrations.
