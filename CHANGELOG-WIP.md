# Release Notes for Sprig Core

## 3.0.0 - Unreleased
### Added
- Added the ability to pass elements and arrays of elements into Sprig components.

## 2.6.0 - Unreleased
### Added
- Added the Sprig component generator that scaffolds PHP components via a console command.

## 2.5.2 - 2023-05.01
### Changed
- Updated htmx to version 1.9.2 ([changelog](https://github.com/bigskysoftware/htmx/blob/master/CHANGELOG.md#192---2023-04-28)).

## 2.5.1 - 2023-04-25
### Fixed
- Fixed a bug in which the htmx file was not being published even if it did not already exist locally ([#305](https://github.com/putyourlightson/craft-sprig/issues/305)).

## 2.5.0 - 2023-04-23
### Added
- Added the [sprig.setConfig](https://putyourlightson.com/plugins/sprig#sprig-setconfigoptions) template variable that allows you to set [configuration options](https://htmx.org/docs/#config) for htmx (via a meta tag).
- Added the [s-on](https://putyourlightson.com/plugins/sprig#s-on) attribute that allows you to respond to events directly on an element.

### Changed
- Updated htmx to version 1.9.0 ([release notes](https://htmx.org/posts/2023-04-11-htmx-1-9-0-is-released/)).
- The htmx file is now loaded locally rather than from a CDN, to reduce dependency on third-party sites ([#303](https://github.com/putyourlightson/craft-sprig/issues/303)).

## 2.4.2 - 2023-03-26
### Changed
- The set user password action is now handled using a JSON request to accommodate its quirks ([#300](https://github.com/putyourlightson/craft-sprig/issues/300)).

## 2.4.1 - 2023-03-03
### Changed
- Updated htmx to version 1.8.6 ([release notes](https://htmx.org/posts/2023-03-02-htmx-1.8.6-is-released/)).

## 2.4.0 - 2023-01-18
### Added
- Added the [s-history](https://putyourlightson.com/plugins/sprig#s-history) attribute that prevents sensitive data being saved to the history cache.

### Changed
- Updated htmx to version 1.8.5 ([release notes](https://htmx.org/posts/2023-01-17-htmx-1.8.5-is-released/)).

## 2.3.0 - 2022-12-08
### Added
- Added the [sprig.triggerRefreshOnLoad](https://putyourlightson.com/plugins/sprig#sprig-triggerrefreshonloadselector) template variable that triggers a refresh event on all components on page load ([#279](https://github.com/putyourlightson/craft-sprig/issues/279)).

## 2.2.0 - 2022-11-07
### Added
- Added the [s-validate](https://putyourlightson.com/plugins/sprig#s-validate) attribute that forces an element to validate itself before it submits a request.
- Added the [sprig.isHistoryRestoreRequest](https://putyourlightson.com/plugins/sprig#sprig-ishistoryrestorerequest) template variable that returns whether the request is for history restoration after a miss in the local history cache a client-side redirect without reloading the page.

### Changed
- Updated htmx to version 1.8.4 ([changelog](https://github.com/bigskysoftware/htmx/blob/master/CHANGELOG.md#184---2022-11-05)).

## 2.1.1 - 2022-10-08
### Fixed
- Fixed a bug in which Sprig requests were failing in live preview requests ([#269](https://github.com/putyourlightson/craft-sprig/issues/269)).

## 2.1.0 - 2022-10-05
### Added
- Added the [s-replace-url](https://putyourlightson.com/plugins/sprig#s-replace-url) attribute that allows you to replace the current url of the browser location history.
- Added the [s-select-oob](https://putyourlightson.com/plugins/sprig#s-select-oob) attribute that selects one or more elements from a server response to swap in via an out-of-band swap.
- Added the [sprig.location(url)](https://putyourlightson.com/plugins/sprig#sprig-locationurl) template variable that triggers a client-side redirect without reloading the page.
- Added the [sprig.replaceUrl(url)](https://putyourlightson.com/plugins/sprig#sprig-replaceurlurl) template variable that replaces the current URL in the location bar.
- Added the [sprig.reswap(value)](https://putyourlightson.com/plugins/sprig#sprig-reswapvalue) template variable that allows you to change the swap behaviour.

### Changed
- Updated htmx to version 1.8.0 ([release notes](https://htmx.org/posts/2022-07-12-htmx-1.8.0-is-released/)).

### Fixed
- Fixed a bug in the `sprig.pushUrl()` template variable.

## 2.0.6 - 2022-09-25
### Fixed
- Fixed a bug in which tags containing line breaks were not being parsed ([#264](https://github.com/putyourlightson/craft-sprig/issues/264)).

## 2.0.5 - 2022-06-30
### Fixed
- Fixed a bug in which nested components were inheriting their parent's component type ([#243](https://github.com/putyourlightson/craft-sprig/issues/243)).

## 2.0.4 - 2022-05-25
### Changed
- Optimised the regular expression pattern in the component parser by matching all possible Sprig attributes only.

## 2.0.3 - 2022-05-25
### Changed
- Improved the parser to help prevent backtick limit errors by enforcing reasonable backtick limits.

## 2.0.2 - 2022-05-25
### Changed
- Improved the parser to return matches even if a backtick limit error is encountered.

## 2.0.1 - 2022-05-17
### Fixed
- Fixed the format of the `sprig.triggerEvents` function to work with htmx.

## 2.0.0 - 2022-05-04
### Added
- Added compatibility with Craft 4.

### Changed
- Restyled invalid variable error messages.

### Removed
- Removed the deprecated `sprig.element`, `sprig.elementName`, `sprig.elementValue` and `sprig.eventTarget` tags.
- Removed the deprecated `s-vars` attribute.
