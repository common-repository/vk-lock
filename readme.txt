=== VK Lock ===
Contributors: igrp
Donate link:
Tags: vkontakte, vk.com, access, autorization, limit, lock, oauth, member, group
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Requires at least: 4.0
Tested up to: 4.8.1
Stable tag: 0.1.8

Restrict page\post access to authorized VKontakte group members only.

== Description ==

VK Lock make is possible to restrict page\post access to authorized VKontakte group members only.
Plugin will take care about site visitor authorization in VKontakte and checking if he\she is member of VKontakte group specified by you.
Access to the protected page\post content will be allowed only if user is authorized and is a group member.

Restriction works for password protected pages\posts only - this means that your have to set page\post type to "password protected" and specify any password (plugin does not use this password, if you like you can access protected page with that password via conditional password access form, provided by Wordpress).

Plugin works via VKontakte API (https://oauth.vk.com/authorize) therefore you need to make some proper (its very easy) settings at VK.COM side. See Installation instructions.

This plugin may not work properly with PHP versions earlier than PHP 5.2

== Installation ==

Plugin works via VKontakte API (https://oauth.vk.com/authorize) therefore you need to make some proper (its very easy) settings at VK.COM side:

1. You need to have an active VKontakte account

2. You have to create new "VKontakte application". Don't panic - "application" means only some settings on VK.com that enables integration between VK.com and your wordpress site (this plugin)

3. Go to https://vk.com/editapp?act=create, and create new "VKontakte application" - set its type to 'Web-site' and specify address of your Wordpress site. Its wise to specify application Name, Description and Icon (better to match your wordpress site) - because this information will be presented by VK.COM to the user during authorization process. User will be asked if he\she trust this application to provide information about his\her name and VK groups he\she belongs to.

4. Write down the following VK application params: ApplicationID (its a number), and SecureKey (its a set of letters like JKdasAKdaKLKsklsjndas)

5. Go to Plugin settings at Wordpress admin console (Plugings -> VK Lock) and enter ApplicationID & SecureKey in proper fields

6. Thats all - you may setup access for your posts\pages now


== Frequently Asked Questions ==

= How to use plugin ? =

Open WP admin console and go to the page\post. Go to [VK Lock] section on page edit screen (meta-box at bottom of the page).
Within [VK Lock] section specify:

1. URL to the VKontakte group (just copy&paste http address from your browser of the VK group main page - its like https://vk.com/club123456789), if it is not specified - plugin won't affect your page at all

2. Timelimit (date in YYYY-MM-DD format after which the access to the page will be blocked even for proper VK group members), you may leave it empty

= How to configure plugin ? =

Plugin has the following parameters (see at Plugings -> VK Lock menu) :

1. 'VK Application ID' - the ID of the application you have to create in VK

2. 'VK SecureKey' - security key of your VK application

3. 'Show default Password box' - as the plugin works for password protected pages, you may either show (1) or not (0) the default password box the the page

4. 'No access notice Header' - header text that be shown to the user (usually its ATTENSION or AUTHENTICATION)

5. 'No access notice text' - text that show to the user as no-access description \ explanation (there is %s param that will be replaced by VK group URL)

6. 'SingIn Button Text' - text that will be put on 'SingIn' button below the notice

7. 'No access after expiration time notice' - text that shown to the user in case timelimit was reached (there are two %s params - first one will be replaced by VK group URL, the second - by Timelimit-date)

8. 'CSS class for SingIn <div> area' - CSS classes to put set in <div> section of the no-access notice

9. 'CSS class for VK SingIn anchor' - CSS class to VK SingIn URL <a href= ...>, usually its 'button'

= Does it slow down my site ? =

The short answer is NO. During page load VK-Lock Plugin checks if VK URL is set, if not - nothing will happen. So the overhead is very very small. Additionally, to minimize load on VK.COM and speed up page load VK-Lock also rely on vk_lock private cookies. VK Lock set secured cookie that confirms user was properly authorized in VK.COM (cookie valid for 2-hours max).

== Screenshots ==


== Changelog ==

= 0.1.8 = 
Initial public realease

= 0.1.7 = 

* Timelimit feature added (access to VK.com group members get blocked after specified date)
* Translation into Russian added

= 0.1.6 =

* VK.com integration changed from VK-Widgets to OAuth

= 0.1 =
Initial Revision
