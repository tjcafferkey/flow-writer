# Flow Writer

**Flow Writer** is a powerful, AI-driven automation tool for WordPress designed to streamline your content strategy. By leveraging the native WordPress **Connectors API** (WP 7.0+), it allows you to generate high-quality, SEO-optimized blog posts automatically based on your site's specific categories and context.

Whether you're looking to maintain a consistent posting schedule or need a jumpstart on new articles, Flow Writer handles the heavy lifting, ensuring your site remains active and relevant.

## ✨ Key Features

- **🌐 Site-Wide Context**: Define a global AI personality and site-wide instructions (e.g., "You are an expert tech reviewer") to ensure all generated content maintains a consistent brand voice.
- **📁 Category-Based Intelligence**: Provide unique context for each category. The AI uses these specific instructions to tailor content to the niche of the category.
- **🔗 Smart Internal Linking**: The plugin automatically scans recent posts within the same category and instructs the AI to reference and link to them, boosting SEO and improving user navigation.
- **⏰ Advanced Scheduling & Frequency**: Configure exactly when and how often you want new content to appear. Choose from Daily, Every Other Day, or Weekly frequencies with multiple specific time-of-day slots.
- **📏 Flexible Post Lengths**: Choose between Short, Regular or Long to suit your content needs.
- **🧩 Gutenberg-Ready**: Content is generated natively in WordPress block markup, ensuring it looks perfect and is fully editable the moment it's created.
- **🔄 Topic Deduplication**: The AI is aware of your most recent posts per category, preventing it from repeating topics and ensuring fresh angles every time.
- **⚡ Manual & Auto Generation**: Generate posts on-demand from the category edit screen or let the automated cron engine handle it on your schedule.

## 🛠 Requirements

### Plugin Requirements
- **AI Connector**: Requires an active AI provider configured in WordPress (e.g., OpenAI, Anthropic, Gemini).
- **Setup**: You must select a valid **Connector ID** in the plugin settings.

### Technical Requirements
- **WordPress**: 7.0+ (Mandatory for `Connectors API` support).
- **PHP**: 7.4 or higher.

## 📝 Quick Start

1. **Configure**: Navigate to **Settings → Flow Writer**. Select your AI connector, post status (Draft/Publish), and set your global context.
2. **Context**: Go to **Posts → Categories** and edit a category to add specific AI instructions for that niche.
3. **Automate**: Set a frequency and time in the settings page to enable automated background generation.

## 👨‍💻 Development

```bash
npm install          # Install JS dependencies
npm run env:start    # Start local development environment
npm run test         # Run PHPUnit tests via wp-env
npm run lint         # Check coding standards (PHPCS)
npm run build        # Package the plugin
```

## 📄 License

GPL-2.0-or-later