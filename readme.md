# CNCA

A theme for https://www.cnca06.org

## Asset versioning

Bump the theme version in `functions.php` and the `style.css` header whenever a CSS or JavaScript asset changes. The version in `functions.php` is used for cache-busting enqueued assets.

## Group lookup

The group lookup refreshes its local Airtable cache hourly using WordPress-Cron. WordPress-Cron runs when the site receives traffic, so configure a system cron to call `wp-cron.php` if refresh timing needs to be reliable. Search requests use the last successfully cached group list, do not call Airtable, and display how long ago that cache was last refreshed. Submitted searches update the URL with a field-specific parameter (for example, `?group_name=search+term` or `?group_id=search+term`), so they can be bookmarked or shared without colliding with WordPress query variables.

The public JSON endpoint `POST /wp-json/cnca/v1/group-lookup` searches the cached group list. `GET /wp-json/cnca/v1/groups` returns the full cached list. Add one or more `district[]` query parameters to include groups whose district starts with any supplied, case-insensitive prefix. For example, `/wp-json/cnca/v1/groups?district%5B%5D=04&district%5B%5D=40` includes districts starting with `04` or `40`.

## Translations

```sh
wp i18n make-pot . languages/cnca.pot
```
