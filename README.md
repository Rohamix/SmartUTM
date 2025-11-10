# SmartUTM Builder

A professional WordPress plugin that automatically generates, manages, and tracks UTM links for all site pages, posts, and campaigns. It ensures consistent and accurate campaign tagging across all marketing channels — built for efficiency, privacy, and performance.

![Version](https://img.shields.io/badge/version-1.2.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)
![License](https://img.shields.io/badge/license-Private-red.svg)

## 🚀 Features

- **Automatic UTM Generation**: Automatically creates UTM-tagged versions of every page and post based on custom templates
- **Channel Presets**: Predefined marketing channel profiles (Facebook, Instagram, Email, Telegram) to ensure UTM consistency
- **Bulk Processing**: Generate, refresh, or delete UTM links for all posts and pages in bulk
- **Dashboard Management**: Centralized dashboard for managing all generated UTM links with search, filter, and sort capabilities
- **URL Shortener Integration**: Optional integration with Bitly, Rebrandly, or custom URL shortener APIs
- **QR Code Generator**: Generate downloadable QR codes for each UTM link (ideal for print and offline campaigns)
- **Analytics Dashboard**: Simple internal report panel displaying top-performing UTM campaigns using GA4 API data

## 📋 Requirements

- WordPress 6.0 or higher
- PHP 8.1 or higher
- MySQL 5.6 or higher

## 📦 Installation

1. Upload the `smart-utm-builder` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'UTM Builder' in the WordPress admin sidebar
4. Configure your templates and presets in the respective pages
5. Start generating UTM links automatically or manually

## 🎯 Usage

### Automatic Generation

The plugin can automatically generate UTM links when posts or pages are published. Enable this in **UTM Builder → Settings**.

### Manual Generation

1. Go to **UTM Builder → Dashboard**
2. Click "Generate All" to create UTM links for all posts/pages
3. Or use the "Generate Now" button in the post editor meta box

### Managing Presets

1. Go to **UTM Builder → Presets**
2. Edit existing presets or create new ones
3. Each preset defines UTM parameters for a specific marketing channel

### Custom Templates

1. Go to **UTM Builder → Templates**
2. Customize the template using placeholders:
   - `{source}` - Source identifier
   - `{medium}` - Medium identifier
   - `{category}` - Post category slug
   - `{year}` - Current year
   - `{post_slug}` - Post slug
   - `{post_title}` - Post title (sanitized)
   - `{post_id}` - Post ID
   - `{author}` - Author username

## 🔒 Privacy & Performance

- ✅ GDPR compliant - no external tracking
- ✅ No external AI services
- ✅ Local processing only
- ✅ Lightweight hooks
- ✅ Optimized database queries

## 🛠️ Development

### Project Structure

```
smart-utm-builder/
├── admin/
│   ├── css/          # Admin styles
│   ├── js/           # Admin JavaScript
│   └── views/         # Admin page templates
├── includes/          # Core PHP classes
├── languages/         # Translation files
└── smart-utm-builder.php  # Main plugin file
```

### Core Classes

- `Smart_UTM_Generator` - Generates UTM links
- `Smart_UTM_Template_Manager` - Manages UTM templates
- `Smart_UTM_Preset_Manager` - Manages channel presets
- `Smart_UTM_Bulk_Processor` - Handles bulk operations
- `Smart_UTM_Dashboard_Manager` - Admin interface
- `Smart_UTM_REST_API` - REST API endpoints

## 📝 Changelog

### Version 1.2.0
- Initial release
- Automatic UTM generation
- Channel presets (Facebook, Instagram, Email, Telegram)
- Bulk processing
- Dashboard management
- URL shortener integration
- QR code generator
- Analytics dashboard

## 🤝 Contributing

This is a private project. For issues or suggestions, please contact the maintainer.

## 📄 License

Private - Internal Use Only

## 👤 Author

**Roham Parsa**

- Website: [rohamhub.info](https://rohamhub.info)
- GitHub: [@Rohamix](https://github.com/Rohamix)

## 🙏 Acknowledgments

Built with ❤️ for efficient marketing campaign tracking.

---

**Note**: This plugin is designed for internal use. Ensure proper testing before deploying to production environments.

