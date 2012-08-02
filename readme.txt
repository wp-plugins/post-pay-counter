=== Post Pay Counter ===
Contributors: Ste_95
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7UH3J3CLVHP8L
Tags: counter, authors, payment, stats, multi author, post management, post
Tested up to: 3.4.1
Stable tag: 1.3.4.1
Requires at least: 3.0

Easily calculate and handle author's pay on a multi-author blog by computing every written post remuneration basing on admin defined rules.

== Description ==
The Post Pay Counter plugin allows you to easily calculate and handle author’s pay on a multi-author blog by computing every written post remuneration basing on admin defined rules. Multisite compatible.

The administrator can set up the payment values, stats are then viewable from the related page where you can choose between general (all authors) and detailed (one author) countings. Below are all the available functions:

* **Counting Type**: define the counting type you prefer between post words and unique daily visits. For the latter, you have also some checkboxes to decide what visits you want to count.
* **Counting System**: choose if you want to pay with the unique payment system (eg. each word/visit is € 0.01) or with the zones one (eg. from 200 to 300 words/visits it’s € 2.00, up to 10 zones).
* **Old stats avaiability**: once stats are computed, they’re not forgotten. In fact, you can view post countings since the first written post of the blog, disregarding the plugin install date. A fancy date picker lets you shift between days and select the desired range. If something goes wrong, you can also regenerate the stats.
* **User personalizable settings**: beside general settings, you can also define special counting and permission settings that only apply to a particular user. Different settings can be made viewable in the stats or hidden depending on your needs.
* **Trial period**: another bounch of settings can be defined for the trial period users may be subjected to. The admin can then opt-in and out the trial option for single users or decide for the automatic feature which, relying on the number of written posts subscribtion date, will do the job.
* **Highly customizable permissions**: you don’t want your users to see stats and use functions they are not supposed to, and that’s the reason you can set detailed permission rules.
* **CSV export**: every stats you see can be exported in csv files for offline consulting or storing.
* **Reward posts and authors**: with a simple custom field, you can award payment bonuses to your authors. You may also define a minimum fee which will always be credited to writers so that they will earn something from every written posts.
* **Overall stats**: at the bottom of every stats page, a box with overall stats is available with interesting details about your blog.
* **And...** works with custom post types, narrow your payments only to chosen user groups, mark posts as paid.

There's much more to enjoy. Try it yourself! More details, FAQs and schreenshots at the [plugin page](http://www.thecrowned.org/post-pay-counter "Post Pay Counter page").

Reviewed the plugin:
* [IdeaGeek](http://www.ideageek.it/il-plugin-wordpress-per-semplificare-i-conti-post-pay-counter/ "IdeaGeek")
* [Mondofico](http://www.mondofico.com/2011/09/post-pay-counter-gestire-i-pagamenti-dei-redattori-su-wordpress/ "Mondofico").
* [WpCode.net](http://www.wpcode.net/post-pay-counter.html/ "WpCode.net").
* [Wordpress Style](http://www.wpstyle.it/plugin-wordpress/gestire-le-retribuzioni-dei-redattori-in-semplicita-con-post-pay-counter.html "Wordpress Style").
* [Risorse Geek](http://www.risorsegeek.net/wordpress/plugin-wordpress/plugin-wordpress-automatizzare-i-conti-sugli-articoli-per-i-pagamenti-degli-articolisti-con-post-pay-counter/ "Risorse Geek").

== Installation ==
1. Upload the directory of the Post Pay Counter in your wp-content/plugins directory; note that you need the whole folder, not only the single files.
2. Activate the plugin through the "Activate" button in the "Plugins" page of your WordPress.
3. Head to the configuration page first. The plugin already comes with a predefined set of settings, but I am sure you will want to configure it to better suit your needs.
4. You may want to generate stats for your old posts too: should you, use the Update Stats button in the Options Page.
5. That's it, done! You can now check the stats page to browse all the countings.

== Frequently Asked Questions ==
= Can I include in the stats the posts published before the plugin was installed? =
Sure you can. Just head to the Options Page and use the *Update countings* button in the *Update stats* box. This can take the plugin a little time depending on the number of published posts.

= The plugin hangs up when using the Update stats function =
This happens due to large databases. Try to increase the *max_execution_time* in your server's php.ini file, and see if the problem get solved.

= Can I reward more particular posts/authors? =
Sure you can! In the edit page related to the post you want to reward, create a new Custom Field named *payment_bonus* giving it the value of the rewarding (add as many decimal digits you want). Those bonuses are then shown in the stats page already summed to the post payment and also in brackets. The admin can still disable this function or simply hide the bonuses. Remember that having this function enabled potentially allows everyone who has the permission of posts editing to award bonuses!

= Can I change the currency? =
The main point here is, at the present moment, the currency symbol is not really important - not at all indeed. The euro symbol, in fact, is just a way to better distinguish payments from other numbers. Just set the plugin as if you were using your desired currency and then, even if in the countings payments will be shown preceded by a euro sign, you will know you are actually using *your* currency.

= Can I pay all posts the same, without caring about words/visits? =
It is not really an explicit feature, but again, the answer is yes. All you have to do is set the plugin to use the counting system zones putting 1 in every *Words/Visits n°* column field and the amount you want to give each post (always the same, of course) in each *Payment* field.

= I want to personalize settings for a user, but I do not see their name in the list =
Only the first 250 are shown in the list to prevent the plugin from hanging or slowing the whole page because of that part. To personalize settings for a username that is not in the list, click first on any other username of the list. Then, look at the URL in your browser and, at the end of it, put the ID of the user you would like to personalize settings for as value of the paramater *userid*.

= I am encountering a problem not listed here =
Well, the obvious answer is [Contact me](http://www.thecrowned.org/contact-me "Contact me")! But apart from detailing the problem you are experiencing, I also need some debug data to troubleshoot the problem and solve it quickly. To do so, you should open your *post-pay-counter-functions.php* file, either by FTP or by the Wordpress plugin editor, and change line 17 *const POST_PAY_COUNTER_DEBUG = FALSE;* and change it to *const POST_PAY_COUNTER_DEBUG = TRUE;* (note the semicolon is still there). Reload the page, and you will get a lot of debugging stuff: it does not contain any sensitive information, it just contains the plugin general settings and other similar things. If you feel like censoring something, you are free to do it, but please, do not delete the whole row, only replace the sensitive data with *xxxxxx* or similar. Send me the screenshot of the data, or copy it in a document, and let's see what we can do! Just keep in mind that sometimes just saving your options again may solve the problem.

== Changelog ==
= 1.3.4.1 =
* Solved more multisite-related problems that excluded some users from countings.
* Fixed an issue that set to zero the counted words when a post page was viewed and the counting type visits was not enabled. 

= 1.3.4 =
* If plugin table or its default settings are missing, they are automatically added when either the options page or the stats page are loaded.
* Update procedure now works with multisite - can not believe this was not introduced when the multisite capability was introduced!
* I took the chance to redesign the class structure of the plugin, using class inheritance and making everything cleaner.
* The debugging feature has been moved to the *post-pay-counter.php* file.
* Every time the global *$post* variable is used, is now casted to object: in some cases I found it being an array and breaking everything.

= 1.3.3 =
* Little problem (not so little, since prevented activation) with user roles permissions is fixed now! If you were experiencing the has_cap() fatal error, it should be ok now. If you were experiencing the array_intersect warning, that should be fixed too. For the latter, should it persist, try to save options and reload the page, and see if that solves.
* For the future (or the present, who knows), I have added a debug functionality that will make troubleshooting problems on my part far easier than now. It can be enabled and disabled at will, though not via a user interface as of the present release. Default option is disabled. Instructions to enable it are in the FAQ.
* Unexpected output during installation is now logged in the database as a wp_option called *ppc_install_error* and included in the debugging data.

= 1.3.2 =
* Without noticing, I was using a PHP 5.3 function that, of course, triggered a fatal error almost everywhere. Sorry!

= 1.3.1 =
* Hopefully fixed a bug that, after the update, prevented the new user roles permissions for the plugin pages to work properly.
* Fixed a uninstallation bug that prevented the ppc_current_version option from being deleted.

= 1.3 =
* Some options contained in the Counting Settings box can not be personalized by user anymore. This allows the counting routine to run much faster, and it was necessary to logically differentiate between settings that apply to everybody and the ones that may be useful to personalize. Those options, if personalized before this release, will not be taken into account anymore: the plugin will use general ones instead.
* It is now possible to mark as paid counted posts. Along each post in the stats by author there is a checkbox that allows to do that; it works with AJAX, so that there is no need to reload the page after a park is marked as such. The plugin also keeps a payment history, so that, if over time the payment for a post should change, the plugin will show you how much you have already paid and how much you still have to pay. The control is only available to administrators, other users can only see how much a post was paid (only if the related permission is checked).
* Post of a post type different than the default one can now be included into countings (including pages). Post types to include can be chosen in the Options page from a list of the registered ones, and in the stats a column will show the post type the displayed posts fit in. The post types to include can not be personalized by user.
* Choose the user groups of which you would like posts to be counted from a convenient list in the Options. In the general stats, the user group will be displayed.
* Define what user groups should be able to view and edit plugin settings and browse through the stats page.
* Update procedure changed, to line up with new Wordpress standards (we now store the installed version in a wp_option in the database and compare it with the most recent one, hardcoded in the plugin files).
* It is now possible to exclude quotations from posts counting routine: only award authors for what they write themselves.
* It is now possible to define up to 10 zones when using the zones counting type, with the second five being optional.
* It is possible to define how often payment usually takes place, so that in the stats page it will automatically be selected the right time range accordingly.
* If user is allowed to, they can now clearly see how the payment was computed by a convenient hover tooltip.
* Future scheduled posts can now be excluded from countings.
* Users are now shown by their chosen display name and not by nickname.
* Only 250 usernames are now shown for personalizing settings due to hanging in blogs with very large databases. To personalize settings for other users, you can put their IDs in the userid parameter in the URL.
* No more problems in pressing *Enter* to update settings, it works!
* Deleted the old stats permission: with the new free time frame picker, it became useless (already a couple releases ago...).
* Split in a different class the functions used to generate the HTML form fields in the options and everything related to that.
* General speed up.

= 1.2.2 =
* Word counting is now more precise.

= 1.2.1 =
* Fixed a problem with the installation which prevented the new functions to work properly because of missing database columns.

= 1.2.0 =
* The plugin now has its own toplevel menu item: it is called Post Pay Counter and is located at the bottom of the admin menu, with the stats and options pages being accesible through it.
* Introduced the minimum fee capability. Admins can now set a minimum amout of money that will be credited to posts when their ordinary payment would be too low (there are options to define how much low is).
* It is now possible to show the posts word count directly in the WordPress post list as a column.
* In the stats page, if the user can, when the payment has bonuses associated with it they are now shown on mouse overlay.
* The exported CSV files now have a little introduction with the site name and address and also report the total counting at the bottom (total payment and total posts).

= 1.1.9 =
* Changes to counting routine grant wider compatibility: Greek charachters are now supported.

= 1.1.8 =
* Bug from previous release made impossible to update settings because of two MySQL columns missing. Should be fixed now.

= 1.1.7 =
* When uninstalling it now checks for table/columns existance while already into the uninstallation process, not before it.

= 1.1.6 =
* Fixed a bug that prevented the installation process to work correctly due to MySQL errors.
* Fixed a JS error in the jQuery datepicker when no posts were available.

= 1.1.5 =
* Fixed a bug that prevented comments and images bonuses to be awarded when using the unique payment system.

= 1.1.4 =
* Manually creating a post meta named *payment_bonus* allows to award a bonus to posts. Bonuses are then shown in the stats page in brackets and with a smaller font, though the admin can decide to disable the function or hide the bonuses.
* Fixed a bug that triggered a fatal error when updating settings without having them in the database (default case of switch).

= 1.1.3 =
* Changed view counting method, it could trigger problems is headers where already sent before the plugin got in. It's now using an AJAX request to set the cookie.
* Minimal improvements in in the view counting method.

= 1.1.2 =
* Stats are not generated during installation anymore. This is to prevent the plugin hanging on activation due to large databases. If you still want to have old stats, use the *Update Stats* box in the Options Page.

= 1.1.1 =
* Made the install process lighter.

= 1.1 =
* Multisite compatibility added.

= 1.0 =
The plugin is highly derived from Monthly Post Counter, which has almost been re-written from scratch to optimize performance, include new tasty functions and carry many many bug and security fixes. Look has been restyled too, using wordpress metaboxes for the settings page.

These the changes from the old Monthly Post Counter:
* Added possibility to set different settings for each user. Stats which do involve different settings are shown only to the writer itself or the admins by default.
* The admin can define permissions for old, overall and other's stats (general and detailed), csv exporting and special settings in countings.
* The counting type can be chosen between visits and words (the latter used by default), and during the installation all the posts in database are selected and updated.
* Two counting systems are now avaiable: the zones one and the unique payment one.
* Stats time range is now customely selectable with a jQuery datepicker.
* Added possibility to pay images after the first one with a little award.
* The admin can define a set of trial settings that will be applied to new users.
* The plugin now records the words number instead of the payment value, this allows the countings to be update immediately without any post-all update.
* Tooltips added all over the options page.
* Ability to update all the posts with a single action added in the options page.
* A new box shows stats from the first published post, they are shown as "overall stats".
* Cool jQuery effects added to show/hide options.
* Improvements in csv encoding shortcomings.
* Uninstall file added instead of the deactivation method.

== Upgrade Notice ==
= 1.3.4.1 =
As far as my tests have been able to prove, this should be a 1.3-and-after working version.

== Screenshots ==
1. Post Pay Counter configuration page
2. Boxes are draggable, collapsable, and even hidable
3. Use the tooltips beside each field to know what you can do with them
4. Post Pay Counter general stats (i.e. all author are shown). The provided datapicker allows to edit the time range and select the wished stats
5. Post Pay Counter per author stats. Datapicker avaiable here, too
6. The tooltip with all the counting details
7. Post Pay Counter csv exporting sample
8. Post Pay Counter menu
9. Marking post as paid