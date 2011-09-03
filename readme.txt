=== Post Pay Counter ===
Contributors: Ste_95
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7UH3J3CLVHP8L
Tags: counter, authors, payment, stats, multi author
Tested up to: 3.2.1
Stable tag: 1.0
Requires at least: 3.0

Easily calculate and handle author's pay on a multi-author blog by computing every written post remuneration basing on admin defined rules.

== Description ==
The Post Pay Counter plugin allows you to easily calculate and handle author’s pay on a multi-author blog by computing every written post remuneration basing on admin defined rules.

The administrator can set up the payment values, stats are then viewable from the related page where you can choose between general (all authors) and detailed (one author) countings. Below are all the avaiable functions:

* **Counting Type**: define the counting type you prefer between post words and unique daily visits. For the latter, you have also some checkboxes to decide what visits you want to count.
* **Counting System**: choose if you want to pay with the unique payment system (eg. each word/visit is € 0.01) or with the zones one (eg. from 200 to 300 words/visits it’s € 2.00).
* **Old stats avaiability**: once stats are computed, they’re not forgotten. In fact, you can view post countings since the first written post of the blog, disregarding the plugin install date. A fancy date picker lets you shift between days and select the desired range. If something goes wrong, you can also regenerate the stats.
* **User personalizable settings**: beside general settings, you can also define special counting and permission settings that only apply to a particular user. Different settings can be made viewable in the stats or hidden depending on your needs.
* **Trial period**: another bounch of settings can be defined for the trial period users may be subjected to. The admin can then opt-in and out the trial option for single users or decide for the automatic feature which, relying on the number of written posts subscribtion date, will do the job.
* **Highly customizable permissions**: you don’t want your users to see stats and use functions they are not supposed to, and that’s the reason you can set detailed permission rules.
* **CSV export**: every stats you see can be exported in csv files for offline consulting or storing.
* **Overall stats**: at the bottom of every stats page, a box with overall stats is avaiable with interesting details about your blog.

There's much more to enjoy. Try it yourself! More details at the [plugin page](http://www.thecrowned.org/post-pay-counter "Post Pay Counter page").

== Installation ==
1. Just upload the directory of the Post Pay Counter in your wp-content/plugins directory, note that you need the whole folder, not only the single files.
2. Activate the plugin through the "Activate" button in "Plugins" page of your WordPress.
3. Visit the settings page first. The plugin already comes with a predefined set of settings, but I am sure you will want to configure it to better suit your need.
4. That's it, done! You should now check the stats page for results.

== Changelog ==
= 1.0 =
The plugin is highly derived from Monthly Post Counter, which has almost been re-written from scratch to optimize performance, include new tasty functions and carry many many bug and security fixes. Look has been restyled too, using wordpress metaboxes for the settings page.

* These the changes from the old Monthly Post Counter:
* Added possibility to set different settings for each user. Stats which do involve different settings are shown only to the writer itself or the admins by default.
* The admin can define permissions for old, overall and other's stats (general and detailed), csv exporting and special settings in countings.
* The counting type can be chosen between visits and words (the latter used by default), and during the installation all the posts in database are selected and updated.
* Two counting systems are now avaiable: the zones one and the unique payment one.
* Stats time range is now customely seelctable with a jQuery datepicker.
* Added possibility to pay images after the first one with a little award.
* The admin can define a set of trial settings that will be applied to new users.
* The plugin now records the words number instead of the payment value, this allows the countings to be update immediately without any post-all update.
* Tooltips added all over the options page.
* Ability to update all the posts with a single action added in the options page.
* A new box shows stats from the first published post, they are shown as "overall stats".
* Cool jQuery effects added to show/hide options.
* Improvements in csv encoding shortcomings.
* Uninstall file added instead of the deactivation method.

== Screenshots ==
1. Post Pay Counter configuration page
2. Boxes are draggable, collapsable, and even hidable
3. Use the tooltips beside each field to know what you can do with them
4. Post Pay Counter general stats (i.e. all author are shown). The provided datapicker allows to edit the time range and select the wished stats
5. Post Pay Counter per author stats. Datapicker avaiable here, too
6. Post Pay Counter csv exporting sample
7. Post Pay Counter menus