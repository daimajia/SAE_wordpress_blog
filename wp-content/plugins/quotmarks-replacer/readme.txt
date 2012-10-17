=== Quotmarks Replacer ===
Contributors: Sparanoid
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=VJRCEYRS8GTYE&lc=C2&item_name=Plugin%20Donation&button_subtype=services&currency_code=USD&bn=PP%2dBuyNowBF%3abtn_buynowCC_LG%2egif%3aNonHosted
Tags: admin, administration, comment, comments, content, contents, excerpt, excerpts, feed, feeds, html, multisite, page, pages, plugin, plugins, post, posts, template, templates, text, title, widget, widgets, wpmu, wptexturize, writing
Requires at least: 1.5
Tested up to: 3.4
Stable tag: 2.6.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Quotmarks Replacer disables wptexturize function that keeps all quotation marks and suspension points in half-width form.

== Description ==
Quotmarks Replacer disables wptexturize function that keeps all quotation marks and suspension points in half-width form. This plugin also works fine with multisite enabled WordPress (aka. WordPress Mu).

Quotmarks Replacer 通过禁用 wptexturize 函数，解决 WordPress 自动将半角的单引号、双引号和省略号转换为全角标点的问题。使后台输入的标点格式与前台读者浏览的标点格式保持一致。并且对多站点 (WordPress Mu) 有良好的支持。

== Installation ==

WordPress:

1. Upload the extracted files to the `/wp-content/plugins/` directory
2. In 'Plugins' page, choose 'Activate'

Multisite enabled WordPress:

1. Upload the extracted files to the `/wp-content/plugins/` directory
2. In 'Site Admin' mode (You need log in as site admin), go to 'Plugins' page, choose 'Network Activate'
3. Just FYI, you can also activate this plugin individually for different sites.

Old-styled WordPress Mu:

1. Upload the extracted files to the `/wp-content/mu-plugins/` directory
2. That's all. 

== Upgrade Notice ==

= 2.6.5 =
* Compatibility check for 3.4, nothing new, just bump version to tell everyone this plugin still works.

= 2.6.4 =
* Update license and readme.txt, compatibility check for 3.4-beta.

= 2.6.3 =
* Update whitespaces

= 2.6.2 =
* Compatibility check for 3.3-RC and 3.3, nothing new, just bump version to tell everyone this plugin still works.

== Changelog ==  

= 2.6.5 =
* Compatibility check for 3.4, nothing new, just bump version to tell everyone this plugin still works.

= 2.6.4 =
* Update license and readme.txt, compatibility check for 3.4-beta.

= 2.6.3 =
* Update whitespaces

= 2.6.2 =
* Compatibility check for 3.3-RC and 3.3, nothing new, just bump version to tell everyone this plugin still works.

= 2.6.1 =
* Compatibility check for 3.3-aortic-dissection, nothing new, just bump version to tell everyone this plugin still works.
* Updated setup instructions

= 2.6 =
* Compatibility check for 3.2, nothing new, just bump version to tell everyone this plugin still works.
* Updated setup instructions

= 2.5 =
* Compatibility check for 3.1
* Add functions comments
* Removed deprecated functions (`list_cats`, `link_name` and `link_notes`)

= 2.4 =
* Compatibility check for 3.0

= 2.3 =
* More tags added
* qmr.gif removed

= 2.2 =
* Added working tag widget_title
* Removed working tag comment_excerpt

= 2.1 =
* Now 2.1 now is compatible with WordPress up to v2.8.x, Thanks to MoontoC, great help.

= 2.0 =
* Now 2.0 use remove_filter make it works, instead of using add_filter to replace the wptexturized text.

= 1.2 =
* Added filter single_post_title (Thanks to yskin)

= 1.1 =
* More filters are added (category_description, comment_author, comment_excerpt, list_cats)

= 1.0.5 =  
* Error in converting #8211 and #8212.

= 1.0.4 =
* Error in converting #8243. (Thanks to Erac)

= 1.0.3 =
* Error in converting multiple quotation marks. (Thanks to herllin)

= 1.0.2 =
* New feature: Convert suspension points(…) into DBC case. (Thanks to Zeax)

= 1.0.1 =
* Error in mistaking converting blog title into DBC case. (Thanks to raenoll)

= 1.0 =
* First release
