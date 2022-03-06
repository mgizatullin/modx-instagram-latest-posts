# MODX Revolution: latest posts from Instagram

```diff
- SORRY, IT'S OUT OF MAINTENANCE
```

This snippet allows us to get the latest posts from any non-private Instagram account.

N.B.! Please take into account, 12 latest posts can be returned only as it's limited by Instagram.

|    Property   |                                           Description                                          |     Default    |
|:-------------:|:----------------------------------------------------------------------------------------------:|:--------------:|
| &accountName  | Instagram account name                                                                         |                |
| &limit        | Set the limit on the maximum number of items that will be displayed                            |        6       |
| &showVideo    | Do we need to show the video as well? Available options: 1, 0                                  |        0       |
| &innerTpl     | Inner chunk name                                                                               | Instagram-Inner |
| &outerTpl     | Outer chunk name                                                                               | Instagram-Outer |
| &errorTpl     | Error chunk name                                                                               | Instagram-Error |
| &cacheEnabled | Do we need to cache the data from Instagram? Available options: 1, 0                           |        1       |
| &cacheExpTime | Set the cache expiry time in seconds                                                           |      3600      |

Installation
---------
Please download the package "InstagramLatestPosts" via MODX Package Manager or from [MODX Extras](https://modx.com/extras/package/getlatestpostsfrominstagram) and install it

OR

Follow the steps below

1. Create the snippet called InstagramLatestPosts and copy the snippet code there
2. Create three chunks with the following names
  * Instagram-Outer
  * Instagram-Inner
  * Instagram-Error
3. [optional] You can modify the chunk names above; if you do that please specify these names in the snippet parameters
4. Copy the corresponding HTML code to the chunks above
5. [optional] You can modify the chunk code as well; if you do that please use the following placeholders:
  * Instagram-Outer
  
  | Placeholder     | Description                       |
  |-----------------|-----------------------------------|
  | [[+accountUrl]] | The link to the Instagram profile |
  | [[+items]]      | The items returned from Instagram |
  
  * Instagram-Inner
  
  |        Placeholder        |                  Description                    |
  |---------------------------|-------------------------------------------------|
  |         [[+link]]         |     The direct link to the corresponding post   |
  | [[+type]] | Type of the item; it can have two values only: image, video     |
  | [[+url]] | URL of the image or video depending on what you want to show     |
  | [[+user.profile_picture]] | URL of the user avatar                          |
  | [[+user.username]] | Instagram account name                                 |
  | [[+user.full_name]] | Full name of account                                  |
  | [[+caption]] | Post caption                                                 |
  | [[+likes]] | Post likes count                                               |
  | [[+comments]] | Post comments count                                         |
  | [[+poster]] | A poster image for video                                      |
  
  * Instagram-Error

  | Placeholder | Description                    |
  |-------------|--------------------------------|
  | [[+error]]  | The error explaining the issue |

6. Place the snippet call in MODX where it's needed
```
[[!InstagramLatestPosts? &accountName=`nike`]]
```
7. Modify the properties if you like as shown below

Usage
---------
```
[[!InstagramLatestPosts?
	&accountName=`nike`
	&limit=`10`
	&showVideo=`1`
	&innerTpl=`MyInnerTemplate`
	&outerTpl=`MyOuterTemplate`
	&cacheEnabled=`1`
	&cacheExpTime=`1800`
]]
```

Contributing
------------

If you have any idea or bug fix, feel free to fork the code and submit your pull request back to me. I will be happy to include your awesome changes in the code!

Donations
---------

The donations are not required - just a few "thank you" words from you would be really great to get as well :) Though if you like this MODX Extra and you would like me to release the updates more often, feel free to send any amount through PayPal.

<p align="center">
	<a href="https://www.paypal.me/IgorSukhinin/10"><img src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" /></a>
	<br>
	Thanks to all supporters :)
</p>
