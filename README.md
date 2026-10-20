# Quotes Collection

- **Contributors:** SriniG, ulfben
- **Tags:** quotes, quotations, random quote, widget, quote rotator, daily quote, quote of the day
- **Requires at least:** 6.8
- **Tested up to:** 7.1
- **Requires PHP:** 8.2
- **Stable tag:** 2.6.0
- **License:** GPL-2.0-or-later
- **License URI:** https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Collect and display quotations with blocks, widgets and shortcodes. Show a random quote, a daily quote or your complete collection.

## Description

**Quotes Collection 2.6.0 is a substantial modernization and maintenance release of Srini G's original plugin.** It keeps your existing quotes and familiar ways of displaying them while improving compatibility, security and reliability.

### Upgrading from Quotes Collection 2.x

This version is designed as an **in-place upgrade**: existing quotes, settings, widgets, blocks, shortcodes and template calls are retained.

It is the recommended maintenance update for existing users on **WordPress 6.8 or newer and PHP 8.2 or newer**.

Replace the plugin files in the existing `quotes-collection` folder. **Do not delete the old plugin first: WordPress's Delete action removes its quotes and settings.**

Security fixes intentionally change how unsafe HTML and unauthorized requests are handled. Refresh controls are now buttons, so check any custom CSS or JavaScript that depends on the old markup.

### Your quotations, organized and ready to display

Collect quotations with authors, sources and tags, then choose where to display them.

* Add, edit, search and manage quotes in your WordPress dashboard.
* Organize quotes by author, source and comma-separated tags.
* Keep unfinished quotes private until you want them displayed.
* Show a complete collection, a filtered list, one random quote or a daily quote.
* Use blocks, a sidebar widget, shortcodes or a theme template function.
* Offer a Next quote button or automatic refresh for random quotes.
* Import and export collections in the original JSON format.
* Keep separate collections for sites in a WordPress multisite network.

### Start with a block or widget

Add a public quote under **Quotes Collection → Add New**. In the page editor, insert a **Quotes** block for a list or a **Random Quote** block for a single quote. Use the block settings to choose filters, attribution and presentation.

Both blocks also have a **Daily quote** setting. For widget areas, add the **Random Quote** widget under **Appearance → Widgets** and choose its settings.

### Shortcode examples

Add a Shortcode block to a page or post and paste an example:

* All public quotes: `[quotcoll]`
* One random quote: `[quotcoll orderby="random" limit="1"]`
* Today's quote: `[quotcoll daily="true"]`
* Today's quote from a tag: `[quotcoll daily="true" tags="favorites"]`
* A particular quote: `[quotcoll id="42"]`
* Quotes by an author: `[quotcoll author="Maya Angelou"]`
* Quotes from a source: `[quotcoll source="My notebook"]`
* Quotes with either tag: `[quotcoll tags="wisdom,humour"]`
* Ten quotes per page: `[quotcoll paging="true" limit_per_page="10"]`
* A Next quote button: `[quotcoll ajax_refresh="true" auto_refresh="false"]`
* Automatic refresh every ten seconds: `[quotcoll ajax_refresh="true" auto_refresh="true" refresh_interval="10"]`

Add `show_author="false"` or `show_source="false"` to hide either attribution field. Existing `[quotescollection]` and `[quotecoll]` aliases are still supported.

### A quote for the whole day

Daily mode rotates through the eligible public quotes once per calendar day, using the timezone in **Settings → General**. All visitors see the same selection for the same day and filters. Each eligible quote is visited before the rotation repeats.

### Credits and support

Originally created by **Srini G**, with modernization and maintenance by **Ulf Benjaminsson**.

For help, use [GitHub Issues](https://github.com/ulfben/quotes-collection/issues). Include WordPress/PHP versions, relevant settings and reproduction steps.

## Installation

### New installation

1. Check that your site runs WordPress 6.8+ and PHP 8.2+.
2. Donwload the Quotes Collection ZIP
3. Open **Plugins → Add New Plugin → Upload Plugin**, select the ZIP and install it.
4. Activate Quotes Collection.
5. Add a public quote under **Quotes Collection → Add New**.
6. Place a Quotes or Random Quote block, a widget, or a shortcode on your site.

### Updating an existing installation

1. Back up your database and existing plugin files.
2. Upload the packaged ZIP and choose **Replace current with uploaded**. Alternatively, replace the files inside `wp-content/plugins/quotes-collection` using your hosting file manager.
3. Keep the folder name `quotes-collection`; do not install a second copy alongside the original.
4. Check your existing quote pages and widgets, then clear your page cache.

Do not uninstall or delete the plugin as an upgrade step, as this will delete your existing quotes collection.

## Frequently Asked Questions

### Will my existing quotes and pages keep working?

Yes: the original 2.x storage and settings are retained. Replace the existing plugin in place.

Older pre-2.0 installations may need their widgets recreated.

### Why are some quotes missing?

Only public quotes are shown. Check the quote's visibility and any author, source or tag filters. The Random Quote widget and block also use a character limit, which defaults to 500. Increase it for longer quotes; the widget's limit can be left blank to remove that filter.

### How do daily quotes handle filters and timezones?

The rotation uses the site's calendar date, including daylight-saving changes. Author, source and tag filters still apply.

The shortcode spelling `todays_quote="true"` is also accepted.

### How do I hide or rename the Next quote button?

Turn off **Refresh feature** in the widget, or use a non-refreshing shortcode such as `[quotcoll orderby="random" limit="1"]`. Daily mode automatically disables refresh.

Change the button text under **Quotes Collection → Options**.

### Why does refresh fail or an old daily quote remain visible?

Clear your cache and check whether script optimization blocks AJAX or serves stale scripts. Custom themes must call `wp_head()` and `wp_footer()`.

For daily quotes, arrange for full-page caches to expire at site-local midnight. For random quotes on cached pages, the plugin's dynamic-fetch option can retrieve the initial quote after the page loads.

### Can I move a collection from the original plugin?

Yes. On a different site, export JSON through **Quotes Collection → Export**, then use **Import** on the destination site. Original Quotes Collection JSON exports are supported.

Imports append entries; they do not replace the collection or remove duplicates. Unsupported HTML is sanitized when quotes are imported or saved.

### What happens when I deactivate or delete the plugin?

Deactivation preserves quotes and settings. Deleting the plugin through WordPress removes its tables and settings. On multisite, uninstall removes collections across the network. Export your quotes and keep a database backup before deleting it.

### Does the plugin send my quotes to an external service?

No. Quotes are stored in your WordPress database. And the plugin adds no analytics or tracking.

## Changelog

### 2.6.0 (2026-10-20)

Modernization and maintenance by Ulf Benjaminsson. Preserves existing quotes, settings, shortcodes, blocks and widgets when upgrading from Srini G's original Quotes Collection 2.x.

* Requires PHP 8.2 or newer and WordPress 6.8 or newer.
* Added daily quotes to shortcodes, both blocks, widgets and template calls. Quotes rotate daily using the site's timezone.
* Security: Strengthened database queries, HTML sanitization, output escaping and permission checks.
* Improved import validation to reject malformed quote data before importing any rows.
* Updated both blocks to use current WordPress block-editor APIs.
* Replaced the “Next quote” link with a keyboard-accessible button and removed its reliance on inline JavaScript. Custom CSS or JavaScript targeting the old link may need updating.
* Bugfix: Corrected quote serialization.
* Bugfix: Preserved backslashes when importing quotes.
* Bugfix: Corrected quote lookup using the shortcode's id attribute.
* Bugfix: Corrected how multiple shortcode filters are combined.
* Bugfix: Corrected pagination totals and handling of zero or negative limits.
* Bugfix: Corrected shortcode-to-block conversion, including tag filters.
* Bugfix: Validated widget settings to prevent invalid quote counts and refresh intervals.
* Bugfix: Delayed loading translated defaults until WordPress initializes the plugin.
* Bugfix: Corrected installation, database selection and removal across multisite networks.
* Bugfix: Prevented failed database upgrades from being marked as successful.


### 2.5.3 (fork maintenance, April-September 2024)

Changes by Ulf Benjaminsson; the fork retained version 2.5.3 throughout these updates.

* 2024-09-09: Fixed a PHP deprecation warning when registering the Random Quote widget.
* 2024-04-11: Replaced the deprecated WordPress block-category hook.
* 2024-04-11: Fixed activation warnings caused by treating missing plugin settings as an array.
* 2024-04-11: Fixed malformed SQL that prevented bulk quote deletion and bulk visibility changes.
* 2024-04-11: Used prepared statements for individual and bulk quote deletion and bulk visibility changes.
* 2024-04-11: Fixed an undefined widget_id warning when displaying the widget without a supplied widget ID.

### 2.5.2 (2019-03-06)
* Fixed issues with auto_refresh in shortcode.

### 2.5.1 (2019-03-05)
* Fix for an issue where JS arguments break HTML loop when widget is used inside certain page builders.

### 2.5 (2019-02-23)
* Gutenberg blocks included. *Note: to make use of these blocks, you should have WP 5.0 or above, or have the [Gutenberg plugin](https://wordpress.org/plugins/gutenberg/) installed and activated.*
  * 'Random Quote' block that functions similarly to the widget, with additional presentation options.
  * 'Quotes' block to display all the quotes or a set of quotes. With presentation, filtering, paging and other options.
  * Existing shortcodes can be transformed into the 'Quotes' block, with all the attributes intact.
* The shortcode now includes refresh options and options to hide author and/or source.
* Option to specify the minimum user level required to add and manage quotes. Previously, anyone with 'Editor' or greater credentials could add and manage quotes.
* Fixed the bug that included a dummy 'Quotes Collection' menu in admin menus for unauthorized users.
* Localization is now entirely handled at [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/quotes-collection).

### 2.0.10 (2018-04-07)
* Removing apply_filters('the_title') for the widget title as it's out of place here, also could be problematic without argument 2.

### 2.0.9 (2017-04-17)
* CSS fix.

### 2.0.8 (2016-11-24)
* Security updates

### 2.0.7 (2016-11-23)
* Security fixes

### 2.0.5 (2016-04-04)
* Changed footer elements in widget back to div to prevent HTML validation errors
* Minor modifications in admin page header markup and styling

### 2.0.4 (2015-05-25)
* Updates to localization in Hungarian and Swedish languages

### 2.0.3 (2015-04-29)
* Dutch localization updates by Guido

### 2.0.2 (2015-04-25)
* Update to Turkish localization by Gürkan Gür

### 2.0.1 (2015-04-20)
* Fix for unformatted output on refresh.

### 2.0 (2015-04-20)
* Complete overhaul of the plugin. File organization modified, code refactored, the code is more class based.
* New and improved admin interface with
  * Improved quotes list table using the `WP_List_Table` class
  * Screen options to customize the number of quotes displayed per page and show/hide columns
  * Search functionality
  * Option to import/export quotes in `JSON` format
  * An options page with options to customize the 'Next Quote' text, specify the maximum number of iterations for the auto-refresh feature, and enable dynamic fetching of the first random quote in cached websites.
* Multi-widget support added. Now as many instances of the widget can be added.
* Not-so-noticable, but important improvements to the markup generated for the front-end.
* `uninstall.php` added. Now, when the plugin is deleted, the plugin's database table, plugin options, all will be removed... no trace left behind.
* The translation template file `quotes-collection.pot` updated. Many of the translation strings have changed. Many new strings added, many old strings given up.
* Updated localization in Tamil, Hebrew, Slovak, French, Ukrainian, German and Norwegian (Bokmål) languages

### 1.5.9 (2012-12-16)
* Fix for cases where random refresh always fetches only two quotes
* Bugfix for widget

### 1.5.8 (2012-12-10)
* Modified html tags filtering for 'quote'. Now all html tags allowed for blog posts can be used in the 'quote' field.
* Fixed quotes count display in admin so that plural shows as 'quotes' and not as 'quote'.
* Fixed Ajax refresh bug.

### 1.5.7 (2012-12-08)
* Localization in Macedonian language added, Persian language updated.
* Code improvements
* Documentation changes (FAQ updated)

### 1.5.6 (2012-07-02)
* Security fix (pointed out by Charlie Eriksen via Secunia SVCRP)

### 1.5.5.1 (2012-03-28)
* Minor fix (the missing semicolon in <code>&amp;nbsp;</code>)

### 1.5.5 (2012-03-27)
* Security fixes
* Shortcode output pagination issue fixed
* Shortcode: 'time_added' value for 'orderby' parameter fixed.
* Localization in Estonian, Greek, Belarusian and Romanian languages added.

### 1.5.4 (2011-08-31)
* 30 and 60 seconds added to widget auto refresh time option.
* Updates for Italian and Japanese localizations.

### 1.5.3 (2011-08-08)
* Hebrew localization added
* id attribute added for blockquote tags for shortcode quotes.

### 1.5.2 (2011-07-18)
* Slovak localization added
* Fixes

### 1.5.1 (2011-07-01)
* Bahasa Indonesia localization updated

### 1.5 (2011-06-30)
* Shortcodes revamp. The new shortcode `[quotcoll]` uses the WordPress shortcode API and comes with various options. The old `[quote]` is deprecated, but will still work as a measure of backwards compatibility.
* Ajax calls are now made to `wp-admin/admin-ajax.php`. This could potentially fix problems some websites had with the older system.
* Pagination in admin page. Other minor improvements in the admin page.
* Fixes for deprecated functions and undefined variables. Various other minor fixes and improvements.
* Bahasa Indonesia (id_ID) localization added. Tamil localization updated.
* The `.po` template file `quotes-collection.pot` is updated. New strings added, few strings have become obsolete.

### 1.4.4 (2010-12-03)
* Updated Simplified Chinese localization

### 1.4.3 (2010-11-26)
* Norwegian translation added
* French and Simplified Chinese localizations updated

### 1.4.2 (2010-06-24)
* Italian localization updated

### 1.4.1 (2010-06-19)
* Compatibility with WP 3.0 multi-site functionality
* Tamil localization updated

### 1.4 (2010-06-17)
* Added ability to refresh quotes sequentially in the order added instead of random refresh.
* Added ability to refresh quotes automatically in a specified time interval
* The widget has two additional options (random refresh and auto refresh (+ time interval))
* 'Quotes Collection' admin panel is now listed as a first-level menu from being a sub-menu under 'Tools'
* Other minor fixes, changes and improvements

### 1.3.8 (2010-06-06)
* Fix for the backslashes issue.

### 1.3.7 (2010-03-02)
* Localization in Hindi added.

### 1.3.6 (2009-11-10)
* Localization in Bulgarian and Czech languages added.

### 1.3.5 (2009-09-22)
* Brazilian Portugese localization added.
* Modifications in quotes-collection.js (for better debugging in case of error)

### 1.3.4 (2009-08-24)
* Finnish localization added.
* FAQ updated.

### 1.3.3 (2009-08-12)
* Localization in Simplified Chinese added.

### 1.3.2 (2009-06-12)
* Latvian translation added. Hungarian translation updated.

### 1.3.1 (2009-05-29)
* Bug fix (URL parsing issue)
* Lithuanian translation added. Spanish and Russian updated

### 1.3 (2009-05-28)
* Uses jQuery instead of SACK library for the AJAX refresh functionality
* New widget option to filter based on tags
* New widget option to set character limit for the random quote
* Template function changed to `quotescollection_quote()`. The old function `quotescollection_display_randomquote()` will still work.
* Parameters now passed in string format in the template function
* Hungarian, Belarusian translations added. Swedish, Italian, Croatian, Turkish, Japanese, Persian, French and Tamil updated.
* If you insert a url in quote, author, source, it becomes clickable in the random quote and  in quotes pages.
* Other minor improvements

### 1.2.8 (2009-04-20)
* Correcting a mistake in the previous update.

### 1.2.7 (2009-04-20)
* Added localization in Portugese language
* Fix to handle directory paths in windows servers

### 1.2.6 (2009-04-14)
* Added localization in Serbian, Bosnian, Dutch and Persian languages

### 1.2.5 (2009-02-27)
* Added localization in Swedish language
* Minor tweaks and fixes

### 1.2.4 (2009-02-04)
* Added translation in Danish, Croatian and Japanese languages
* Minor fixes
* FAQ section added in readme.txt to answer the frequently asked questions.

### 1.2.3 (2008-11-08)
* Added Ukrainian translation (thanks to Stas for the translation)
* Tested the plugin for the new admin interface that comes with WordPress 2.7 and a few tweaks. The plugin will work just fine in older WP versions

### 1.2.2 (2008-10-06)
* Security fix, HTML tidy fix, other fixes
* Updated Turkish trasnlation

### 1.2.1 (2008-09-24)
* Arabic translation added
* Minor fix (quotes-collection.js: errotext -> errortext)

### 1.2 (2008-09-22)
* All javascript code moved to quotes-collection.js. This makes the code neater.
* Translations for French, Polish and Turkish languages added.
* Italian and Russian translations updated.
* A few minor fixes and small improvements.

### 1.1.4 (2008-07-02)
* Bug fixes. The plugin was not handling properly apostrophes in author and source fields. This is fixed now.
* Other small fixes.

### 1.1.3.1 (2008-06-05)
* Added Spanish translation.
* Updated Italian translation.

### 1.1.3 (2008-06-01)
* Improvements
* Updated German translation
* Added Russian translation

### 1.1.2.1 (2008-05-28)
* VARCHAR(256) -> VARCHAR(255) (VARCHAR(256) doesnt work with MySQL 4.0)

### 1.1.2 (2008-05-28)
* Modifications in the automatic database update functionality
* Fixed problem with German translation
* Added Italian translation

### 1.1.1 (2008-05-25)
* security fix

### 1.1 (2008-05-25)
* Tagging feature
* Internationalization
* Fixes and improvements

### 1.0 (2008-03-11)
* Compatible with WordPress 2.5
* Bug fixes and various other improvements

### 0.9.5 (2008-02-06)
* Fixed problem with non English characters in author names while using the tag `[quote|author=]`

### 0.9.4 (2008-01-16)
* Support for utf-8 characters
* Fixed problem with linebreaks

### 0.9.3 (2007-12-19)
* Fixed a JavaScript issue
* Removed unnecessary `<h2></h2>` tags above random quote when title field is left blank in widget control options. `<h2>` tags displayed only when there is a title.

### 0.9.2 (2007-12-18)
* Provision to add random quote anywhere in the template.

### 0.9.1 (2007-12-16)
* Bug fix

### 0.9 (2007-12-15)
* Initial release

## Upgrade Notice

### 2.6.0

Major maintenance update with daily quotes and security fixes. Requires PHP 8.2+ and WordPress 6.8+. Back up, then replace the existing plugin; do not delete it first.
