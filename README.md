# Admin Manager

A lightweight, modular, production-ready WordPress plugin to replace multiple small add-ons while remaining extremely lightweight, secure, and dependency-free.

## 🎯 Features

Admin Manager is 100% PHP + vanilla JavaScript with no external dependencies. It's designed to be fast, secure, and compatible with WordPress 5.9+ and PHP 7.4+.

### 9 Powerful Modules

1. **Script Manager** - Detect and disable unnecessary scripts/styles sitewide, per post type, or per page
2. **Google Fonts Manager** - Disable Google Fonts and replace with system fonts or local fonts
3. **Preload Manager** - Add preload hints for critical assets to improve performance
4. **Table of Contents** - Auto-generate table of contents for posts
5. **Author Box** - Display author info with bio, avatar, and social links
6. **Reading Time** - Calculate and display estimated reading time
7. **Virtual Media Folders** - Organize media library with taxonomy-based folders
8. **Custom Login** - Customize WordPress login page branding
9. **WP Hide** - Hide WordPress fingerprints from detector sites

## 📋 Requirements

- **WordPress**: 5.9 or higher
- **PHP**: 7.4 or higher (supports PHP 8.x)
- **Dependencies**: None! 100% standalone

## 🚀 Installation

1. Download or clone this repository
2. Upload the `admin-manager` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Admin Manager** in your WordPress admin menu
5. Enable the modules you need

## ⚙️ Configuration

### Safe Mode

Enable Safe Mode to test changes safely before applying them to all visitors:

1. Go to **Admin Manager → Modules**
2. Enable "Safe Mode"
3. Changes will only apply to logged-in administrators
4. Test thoroughly, then disable Safe Mode to apply to everyone

**Recommended**: Always test Script Manager changes in Safe Mode first!

## 📖 Quick Start Examples

### Example 1: Disable Google Fonts Sitewide
1. Enable the Google Fonts Manager module
2. Go to Admin Manager → Google Fonts Manager
3. Check "Disable Google Fonts"
4. Select "System Font Stack"
5. Save settings

### Example 2: Add Table of Contents to Posts
1. Enable the Table of Contents module
2. Go to Admin Manager → Table of Contents
3. Check "Posts" under enabled post types
4. Select heading levels (H2, H3)
5. Choose "Before Content" as position
6. Save settings

## 📖 Module Documentation

### 1. Script Manager

**Purpose**: Detect and disable unnecessary scripts/styles to improve performance.

**Key Features:**
- Safe Mode for testing
- Per-page, per-post-type, or sitewide control
- Core script warnings
- Export/import rules

**Usage:**
```
1. Go to Admin Manager → Script Manager
2. Select scope (Sitewide / Post Type)
3. Check scripts to disable
4. Save changes
```

### 2. Google Fonts Manager

**Purpose**: Control Google Fonts for privacy and performance.

**Key Features:**
- Auto-detect Google Fonts
- System font replacement
- Local font upload

**Shortcode:** N/A

### 3. Preload Manager

**Purpose**: Improve page load by preloading critical assets.

**Example Preload Config:**
```
Resource: /wp-content/uploads/fonts/custom.woff2
Type: font
MIME: font/woff2
Crossorigin: Yes
```

### 4. Table of Contents

**Shortcode:** `[am-toc]`

**Example:**
```html
<!-- Auto-insert via settings, or manual: -->
[am-toc]
```

### 5. Author Box

**Shortcode:** `[am-author]` or `[am-author author_id="1"]`

**Example:**
```html
<!-- At end of single.php: -->
<?php echo do_shortcode('[am-author]'); ?>
```

### 6. Reading Time

**Shortcode:** `[am-reading-time]` or `[am-reading-time post_id="123"]`

**PHP Function:**
```php
<?php
// Display reading time
echo am_get_reading_time( get_the_ID() );
?>
```

### 7. Virtual Media Folders

**Purpose**: Organize media library without changing file URLs.

**Usage:**
```
1. Go to Media → Library
2. Click "Enter folder name:" button
3. Create folders
4. Filter and assign files
```

**Important:** Folders are virtual - deleting them doesn't delete files.

### 8. Custom Login

**Purpose**: Brand your WordPress login page.

**Customizations:**
- Logo (320x100px recommended)
- Background color/image
- Custom CSS

### 9. WP Hide

**Purpose**: Hide WordPress fingerprints for security through obscurity.

**What It Removes:**
- Version numbers
- Generator meta tags
- Emoji scripts
- RSD/WLW links
- Readme.html, license.txt access

**Warning:** Keep WordPress updated! This is not a replacement for proper security.

## 🔒 Security

Admin Manager follows WordPress security best practices:

- ✅ Nonces for all forms and AJAX
- ✅ Capability checks
- ✅ Input sanitization
- ✅ Output escaping
- ✅ Prepared SQL statements
- ✅ No external dependencies

## 🐛 Troubleshooting

### Scripts Still Loading

1. Check Safe Mode status
2. Clear all caches
3. Check for conflicts with optimization plugins

### Google Fonts Still Showing

1. Clear cache
2. Add manual handles
3. Check theme's method of loading fonts

### Forms Not Submitting

1. Check JavaScript console
2. Verify form ID
3. Test with default theme

## 🔧 Developer Hooks

### Filters

```php
// Modify ToC output
add_filter( 'am_toc_output', function( $html, $post_id ) {
    // Modify $html
    return $html;
}, 10, 2 );

// Modify reading time HTML
add_filter( 'am_reading_time_html', function( $html, $post_id ) {
    // Modify $html
    return $html;
}, 10, 2 );

// Modify author box HTML
add_filter( 'am_author_box_html', function( $html, $author_id, $post_id ) {
    // Modify $html
    return $html;
}, 10, 3 );
```

### Helper Functions

```php
// Check if module is enabled
if ( am_is_module_enabled( 'reading-time' ) ) {
    // Do something
}

// Get reading time
$time = am_get_reading_time( $post_id );

// Get settings
$setting = am_get_module_setting( 'module-slug', 'key', 'default' );
```

## 📝 Changelog

### Version 1.0.0 (2025-01-16)
- ✨ Initial release
- ✅ 9 modules included
- ✅ WordPress 5.9+ support
- ✅ PHP 7.4+ and PHP 8.x support
- ✅ Zero external dependencies
- ✅ Safe Mode for testing
- ✅ Fully translatable
- ✅ Custom color theming for frontend modules
- ✅ Media library avatar support for Author Box

## 🤝 Contributing

Contributions welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Follow WordPress coding standards
4. Test thoroughly
5. Submit a pull request

## 📄 License

GPLv2 or later. See LICENSE file.

## 💡 Support

For issues and feature requests: [GitHub Issues](https://github.com/kevster03/Admin-manager/issues)

---

**Performance Tip**: Only enable modules you actually need. Each disabled module = better performance!