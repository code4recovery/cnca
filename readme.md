# CNCA

A theme for https://www.cnca06.org

## Group lookup

The group lookup refreshes its local Airtable cache hourly using WordPress-Cron. WordPress-Cron runs when the site receives traffic, so configure a system cron to call `wp-cron.php` if refresh timing needs to be reliable. Search requests use the last successfully cached group list, do not call Airtable, and display how long ago that cache was last refreshed. Submitted searches update the URL with a field-specific parameter (for example, `?group_name=search+term` or `?group_id=search+term`), so they can be bookmarked or shared without colliding with WordPress query variables.

## Translations

```sh
wp i18n make-pot . languages/cnca.pot
```
