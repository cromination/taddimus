=== Converter for Media - Convert WebP and AVIF & Optimize Images | Ease image optimization ===
Contributors: mateuszgbiorczyk
Donate link: https://ko-fi.com/gbiorczyk/?utm_source=webp-converter-for-media&utm_medium=readme-donate
Tags: convert webp, webp, optimize images, image optimization, compress images
Requires at least: 4.9
Tested up to: 6.0
Requires PHP: 7.0
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Speed up your website by using our WebP & AVIF Converter. Serve WebP and AVIF images instead of standard formats JPEG, PNG and GIF just now!

== Description ==

**Speed up your website using our ease image optimizer by serving WebP and AVIF images.** By replacing files in standard JPEG, PNG and GIF formats with WebP and AVIF formats, you can save over a half of the page weight without losing quality.

After installing the plugin you do not have to do anything more. Your current images will be converted into a new format. When image optimization by our image optimizer is finished, users will automatically receive new, much lighter images than the original ones.

As of today, over 90% of users use browsers that support the WebP format. The loading time of your website depends to a large extent on its weight and the level of image optimization. **Using our WebP Converter, now you can and speed up it in a few seconds without much effort!**

This will be a profit both for your users who will not have to download so much data, but also for a server that will be less loaded. Remember that a better optimized website also affects your Google ranking. Image optimization is very important.

#### AVIF support

Now in [the PRO version](https://mattplugins.com/products/webp-converter-for-media-pro/?utm_source=webp-converter-for-media&utm_campaign=upgrade-to-pro&utm_medium=readme-avif-support) you can use AVIF as the output format for your images. The AVIF format is a new extension - is the successor to WebP. **AVIF allows you to achieve even higher levels of image compression**, and the quality of the converted images after image optimization is better than in WebP.

#### How does this work?

- If you have just installed the plugin, you can optimize images with one click. Image size will be smaller after generate webp!
- New images that will be added to the Media Library will be converted automatically.
- Our image optimizer does not modify your original images in any way. This means security for you and your files.
- When the browser loads an image, our plugin checks if it supports the WebP format. If so, the image in WebP format is loaded.
- The plugin does not make redirects in default mode, so the URL is always the same. Only the MIME type of the image changes to `image/webp`.
- No redirects means no cache issues, faster and trouble-free operation of your website. If you want to know more about how it works, check out [the plugin FAQ](#faq) below.
- It does not matter if the image display as an `img` HTML tag or you use `background-image`. It works always!
- In case rewriting by rules from .htaccess file is blocked, a mode is available which loads images via PHP file. Then image URLs are changed, but the logic of operation is the same as in the case of the default mode.
- The final result after image optimization is that your users download less than half of the data, and the website itself loads faster!
- You lose nothing - if you had to remove the plugin, it will remove everything after itself. It does not leave any trace, so you can check it with ease.

#### Convert WebP - it is the future of image optimization!

Optimize images and raise your website to a new level now! Install the plugin and enjoy the website that loads faster by image optimization. Surely you and your users will appreciate it.

#### Support for additional directories

You can convert WebP and optimize images not only from `/uploads` directory but also from `/plugins` and `/themes` directories. This allows full integration with the WebP format!

#### Support to the development of plugin

We spend hours working on the development of this plugin. Technical support also requires a lot of time, but we do it because we want to offer you the best plugin. We enjoy every new plugin installation.

If you would like to appreciate it, you can try [the PRO version](https://mattplugins.com/products/webp-converter-for-media-pro/?utm_source=webp-converter-for-media&utm_campaign=upgrade-to-pro&utm_medium=readme-plugin-development). In addition, you will gain access to extra functionalities that will allow you to achieve **even better image optimization results**.

#### Please also read the FAQ below. Thank you for being with us!

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/webp-converter-for-media` directory, or install plugin through the WordPress plugins screen directly.
2. Activate plugin through `Plugins` screen in WordPress Admin Panel.
3. Use `Settings -> Settings -> Converter for Media` screen to configure the plugin.
4. Click on the button `Regenerate All`.
5. Check if everything works fine.

That's all! Your website is already loading faster!

== Frequently Asked Questions ==

= How to get technical support? (before you ask for help) =

Please always adding your thread, **read all other questions in the FAQ of plugin and other threads in support forum first**. Perhaps someone had a similar problem and it has been resolved.

When adding a thread, follow these steps and reply to each of them:

**1.** Do you have any error on the plugin settings page? Please read [this thread](https://wordpress.org/support/topic/server-configuration-error-what-to-do/) if you have any errors.

**2.** URL of your website.

**3.** Configuration of your server *(link to it can be found on the settings page of plugin in the section **"We are waiting for your message"**)* - please take a screenshot of the ENTIRE page and send it to me.

**4.** Settings of plugin - please take a screenshot of the ENTIRE page and send it to me.

**5.** Please do the test, which is described in the FAQ in question `How to check if plugin works?`. Please send a screenshot of Devtools with test results.

Please remember to include the answers for all questions by adding a thread. It is much easier and accelerate the solution of your problem.

= Error on plugin settings screen? =

If you have an error on the plugin settings screen, first of all please read it carefully. They are displayed when there is a problem with the configuration of your server or website.

The messages are designed to reduce the number of support requests that are repeated. It saves your and our time. Please read [this thread](https://wordpress.org/support/topic/server-configuration-error-what-to-do/) for more information.

= Error while converting? =

You can get several types of errors when converting. First of all, carefully read their content. For the most part, you can solve this problem yourself. Try to do this or contact the server administrator.

If you get an error: `File "%s" does not exist. Please check file path.` means that the [file_exists()](https://www.php.net/manual/en/function.file-exists.php) function in PHP returned `false` using the file path given in the error message. Check this path and make sure it is correct.

If you get an error: `File "%s" is unreadable. Please check file permissions.` means that the [is_readable()](https://www.php.net/manual/en/function.is-readable.php) function in PHP returned `false` using the file path given in the error message. Check the permissions for the file and the directory in which the file is located.

If you get an error: `"%s" is not a valid image file.` means that the file is damaged in some way. Download the file to disk, save it again using any graphics program and add again to the page. If the error applies to individual images then you can ignore it - just the original images will load, not WebP.

If you get an error: `Image "%s" converted to .webp is larger than original and converted .webp file has been deleted.` means the original image weighed less than WebP. This happens when images have been compressed before. Disable the *"Automatic removal of files in output formats larger than original"* option in plugin settings to force always using WebP.

In the case of the above problems, **contacting the support forum will be useless**. Unfortunately, we are unable to help you if your files are damaged. You have to fix it yourself. If you have previously used other tools that changed the original files and damaged them, you will do nothing more.

Remember that it happens that other plugins can cause problems with accessing files or the REST API. Please try to disable all other plugins and set the default theme to make sure that it is not one of them that causes these types of problems.

= What are requirements of plugin? =

Practically every hosting meets these requirements. You must use PHP at least 7.0 and have the `GD` or `Imagick` extension installed. **The extension must support `WebP format`.** If you have an error saying that the GD or Imagick library are not installed, but you have it installed then they are probably incorrectly configured and do not have WebP support.

They are required native PHP extensions, used among others by WordPress to generate thumbnails. Your server must also have the modules `mod_mime`, `mod_rewrite` and `mod_expires` enabled.

An example of the correct server configuration can be found [here](https://mattplugins.com/files/webp-server-config.png). Link to your current configuration can be found in the administration panel, on the management plugin page in the section **"We are waiting for your message"** *(or using the URL path: `/wp-admin/options-general.php?page=webpc_admin_page&action=server`)*.

**Note the items marked in red.** If the values marked in red do not appear in your case, it means that your server does not meet the technical requirements. Pay attention to the **WebP Support** value for the GD library and **WEBP in the list of supported extensions** for the Imagick library.

In a situation where your server does not meet the technical requirements, please contact your server Administrator. We are not able to help you. Please do not contact us about this matter, because this is a server configuration problem, not a plugin.

Also REST API must be enabled and work without additional restrictions. If you have a problem with it, please contact the Developer who created your website. He should easily find the issue with the REST API not working.

= How to check if plugin works? =

You can find more information on how the plugin works in [our manual](https://wordpress.org/support/topic/how-can-i-check-if-the-plugin-is-working-properly/).

= How to change path to uploads? =

This is possible using the following types of filters to change default paths. It is a solution for advanced users. If you are not, please skip this question.

Path to the root installation directory of WordPress *(`ABSPATH` by default)*:

`add_filter( 'webpc_site_root', function( $path ) {
	return ABSPATH;
} );`

Path to `/uploads` directory *(relative to the root directory)*:

`add_filter( 'webpc_dir_name', function( $path, $directory ) {
	if ( $directory !== 'uploads' ) {
		return $path;
	}
	return 'wp-content/uploads';
}, 10, 2 );`

Directory path with converted WebP files *(relative to the root directory)*:

`add_filter( 'webpc_dir_name', function( $path, $directory ) {
	if ( $directory !== 'webp' ) {
		return $path;
	}
	return 'wp-content/uploads-webpc';
}, 10, 2 );`

**Note that the `/uploads-webpc` directory must be at the same nesting level as the `/uploads`, `/plugins` and `/themes` directories.**

Prefix in URL of `/wp-content/` directory or equivalent *(used in .htaccess)*:

`add_filter( 'webpc_htaccess_rewrite_path', function( $prefix ) {
	return '/';
} );`

For the following sample custom WordPress structure:

`...
├── web
	...
	├── app
	│	├── mu-plugins
	│	├── plugins
	│	├── themes
	│	└── uploads
	├── wp-config.php
	...`

Use the following filters:

`add_filter( 'webpc_site_root', function( $path ) {
	return 'C:/WAMP/www/project/web'; // your valid path to root
} );
add_filter( 'webpc_dir_name', function( $path, $directory ) {
	if ( $directory !== 'uploads' ) {
		return $path;
	}
	return 'app/uploads';
}, 10, 2 );
add_filter( 'webpc_dir_name', function( $path, $directory ) {
	if ( $directory !== 'webp' ) {
		return $path;
	}
	return 'app/uploads-webpc';
}, 10, 2 );`
`add_filter( 'webpc_htaccess_rewrite_path', function( $prefix ) {
	return '/';
} );`

After setting the filters go to `Settings -> Converter for Media` in the admin panel and click the `Save Changes` button. `.htaccess` files with appropriate rules should be created in the directories `/uploads` and `/uploads-webpc`.

= How to exclude paths from converting? =

To exclude selected directories, use the following filter:

`add_filter( 'webpc_supported_source_directory', function( bool $status, string $directory_name, string $server_path ): bool {
    $excluded_directories = [ 'my-directory' ];
    if ( ! $status || in_array( $directory_name, $excluded_directories ) ) {
        return false;
    }

    return $status;
}, 10, 3 );`

To exclude selected files use the following filter:

`add_filter( 'webpc_supported_source_file', function( bool $status, string $file_name, string $server_path ): bool {
    $excluded_files = [ 'my-image.jpg' ];
    if ( ! $status || in_array( $file_name, $excluded_files ) ) {
        return false;
    }

    return $status;
}, 10, 3 );`

Argument `$server_path` is the absolute server path to a directory or file. Inside the filters, you can apply more complicated rules as needed.

Filters run before images are converted - they no longer support converted images. You have to delete them manually if they should not be converted.

= How to run manually conversion? =

By default, all images are converted when you click on the `Regenerate All` button. In addition, conversion is automatic when you add new files to your Media Library.

Remember that our plugin takes into account images generated by WordPress. There are many plugins that generate, for example, images of a different size or in a different version.

If you would like to integrate with your plugin, which generates images by yourself, you can do it. Our plugin provides the possibility of this type of integration. This works for all images in the `/wp-content` directory.

It is a solution for advanced users. If you would like to integrate with another plugin, it's best to contact the author of that plugin and give him information about the actions available in our plugin. This will help you find a solution faster.

You can manually run converting selected files, you can use the action to which you will pass an array with a list of paths *(they must be absolute server paths)*:

`do_action( 'webpc_convert_paths', $paths );`

An alternative method is to manually start converting the selected attachment by passing the post ID from the Media Library. Remember to run this action after registering all image sizes *(i.e. after running the `add_image_size` function)*:

`do_action( 'webpc_convert_attachment', $post_id );`

Argument `$paths` is array of absolute server paths and `$skip_exists` means whether to skip converted images.

You can also modify the list of image paths for an attachment, e.g. to exclude one image size. To do this, use the following filter:

`add_filter( 'webpc_attachment_paths', function( $paths, $attachment_id ) {
	return $paths;
}, 10, 2 );`

Argument `$paths` is array of absolute server paths and `$attachment_id` is the post ID of attachment, added to the Media Library.

To delete manually converted files, use the following action, providing as an argument an array of absolute server paths to the files *(this will delete manually converted files)*:

`do_action( 'webpc_delete_paths', $paths );`

= Support for WP-CLI =

The plugin supports WP-CLI, which enables faster image conversion from the server level. More information on how to get started with WP-CLI can be found in [the Handbook](https://make.wordpress.org/cli/handbook/guides/quick-start/). The supported commands are described below.

Checking how many maximum images for conversion are on website:

`wp webp-converter calculate`

Converting all images:

`wp webp-converter regenerate`

Converting all images (with "Force convert all images again" option):

`wp webp-converter regenerate -force`

= Does plugin support CDN? =

The website files (WordPress files) and the images from the Media Library must be on the same server. If they are, everything should work fine.

If only your images are on another CDN server, unfortunately correct operation is impossible, because such images are managed by another server.

= Configuration for Nginx =

For Nginx server that does not support .htaccess rules, additional Nginx server configuration is required for the plugin to function properly.

Find the configuration file in one of the paths *(remember to select configuration file used by your vhost)*:
- `/etc/nginx/sites-available/` or `/etc/nginx/sites-enabled/`
- `/etc/nginx/conf.d/`

and add this code *(add these lines at the beginning of the `server { ... }` block)*:

`# BEGIN WebP Converter for Media`
`set $ext_avif ".avif";`
`if ($http_accept !~* "image/avif") {`
`	set $ext_avif "";`
`}`
``
`set $ext_webp ".webp";`
`if ($http_accept !~* "image/webp") {`
`	set $ext_webp "";`
`}`
``
`location ~ /wp-content/(?<path>.+)\.(?<ext>jpe?g|png|gif|webp)$ {`
`	add_header Vary Accept;`
`	add_header Cache-Control "private" always;`
`	expires 365d;`
`	try_files`
`		/wp-content/uploads-webpc/$path.$ext$ext_avif`
`		/wp-content/uploads-webpc/$path.$ext$ext_webp`
`		$uri =404;`
`}`
`# END WebP Converter for Media`

Then edit the configuration file:
- `/etc/nginx/mime.types`

and add this code *(add these lines inside the `types { ... }` block)*:

`image/webp webp;`
`image/avif avif;`

After making changes, remember to restart the machine:

`systemctl restart nginx`

== Screenshots ==

1. Screenshot of the options panel
2. Screenshot when regenerating images

== Changelog ==

= 4.4.1 (2022-06-30) =
* `[Added]` Inheritance of mod_rewrite rules from parent directories
* `[Added]` Support for custom /wp-content directory name

= 4.4.0 (2022-06-19) =
* `[Changed]` Calculation of number of images to be converted
* `[Added]` Resizing of images before conversion
* `[Added]` Notification asking to clear cache for LiteSpeed

See [changelog.txt](https://plugins.svn.wordpress.org/webp-converter-for-media/trunk/changelog.txt) for previous versions.

== Upgrade Notice ==

None.
