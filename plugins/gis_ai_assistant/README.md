# Moodle AI Assistant Plugin

A comprehensive AI assistant plugin for Moodle that integrates with OpenAI-compatible APIs to provide intelligent chat functionality, content summarization, and educational support.

## Features

- **AI Chat Interface**: Full-featured chat interface with streaming responses
- **Chat Widget**: Floating chat widget for quick access across Moodle
- **OpenAI Compatible**: Works with OpenAI, Azure OpenAI, and other compatible APIs
- **Enhanced Security**:
  - Secure API key encryption using Moodle's encryption system
  - IP-based and user-based rate limiting
  - Input validation and sanitization
  - Comprehensive error handling and logging
- **Optimized Performance**:
  - Multi-level caching with configurable TTL
  - Database query optimization with proper indexing
  - Static acceleration for frequently accessed data
  - Efficient rate limiting implementation
- **Analytics**: Comprehensive usage analytics and reporting
- **Multi-model Support**: Support for different AI models via configuration
- **Streaming Responses**: Real-time streaming for better user experience

## Requirements

- Moodle 4.5+ (compatible with Moodle 5.0)
- PHP 8.1+
- OpenAI API key or compatible service
- cURL extension enabled

## Installation

1. **Download and Extract**
   ```bash
   cd /path/to/moodle/local/
   git clone <repository-url> ai
   ```

2. **Set Environment Variables**
   ```bash
   export OPENAI_API_KEY="your-api-key-here"
   export OPENAI_BASE_URL="https://api.openai.com/v1"  # Optional, defaults to OpenAI
   export OPENAI_MODEL="gpt-4o-mini"  # Optional, can be set in admin settings
   ```

3. **Install Plugin**
   - Log in as administrator
   - Go to Site Administration > Notifications
   - Follow the installation prompts

4. **Configure Settings**
   - Go to Site Administration > Plugins > Local plugins > AI Assistant
   - Configure default model, rate limits, and other settings

## Configuration

### Environment Variables (Required)

| Variable | Description | Example |
|----------|-------------|---------|
| `OPENAI_API_KEY` | Your API key | `sk-...` |
| `OPENAI_BASE_URL` | API base URL | `https://api.openai.com/v1` |
| `OPENAI_MODEL` | Default model | `gpt-4o-mini` |

### Admin Settings

- **Enable AI**: Toggle AI functionality site-wide
- **Default Model**: Default AI model to use
- **Max Tokens**: Maximum tokens per request
- **Temperature**: AI response randomness (0.0-1.0)
- **Rate Limits**: Requests and tokens per hour per user
- **Caching**: Enable response caching and TTL
- **Analytics**: Enable usage tracking
- **System Prompt**: Default system prompt for AI context

## Usage

### For Users

1. **Access AI Chat**
   - Navigate to the AI Assistant from the main navigation
   - Or use the floating chat widget on supported pages

2. **Chat Interface**
   - Type your question or request
   - Press Enter or click Send
   - View real-time streaming responses
   - Use Clear to reset the conversation

3. **Features Available**
   - General Q&A assistance
   - Content explanation and summarization
   - Educational support
   - Course-related help

### For Administrators

1. **Analytics Dashboard**
   - Access via Site Administration > Reports > AI Analytics
   - View usage statistics, top users, model usage
   - Monitor response times and system performance

2. **Rate Limiting**
   - Configure per-user limits in plugin settings
   - Monitor usage in analytics dashboard
   - Adjust limits based on usage patterns

## API Integration

### Supported Endpoints

The plugin integrates with OpenAI-compatible APIs using:

- **Endpoint**: `/chat/completions`
- **Method**: POST
- **Headers**: 
  - `Authorization: Bearer {API_KEY}`
  - `x-user-email: {user_email}` (for tracking)
- **Streaming**: Supported via Server-Sent Events

### Custom Headers

The plugin automatically adds the user's email as `x-user-email` header for API requests, enabling user-level tracking and analytics on the API provider side.

## Security

### Data Protection

- **Input Sanitization**: All user inputs are sanitized before processing
- **No Credential Storage**: API keys stored only in environment variables
- **Privacy Compliance**: GDPR-compliant data handling and export
- **Rate Limiting**: Prevents abuse and excessive usage
- **Capability Checks**: Proper permission validation

### Privacy

- User requests are logged for analytics (can be disabled)
- No message content is stored locally
- User email is sent to API provider for identification
- Full privacy provider implementation for GDPR compliance

## Development

### File Structure

```
plugins/local_ai/
├── classes/
│   ├── api/
│   │   └── inference_service.php    # Core API service
│   ├── external/
│   │   └── chat_api.php            # External web services
│   ├── exceptions/                 # Custom exceptions
│   └── privacy/
│       └── provider.php            # Privacy compliance
├── db/
│   ├── install.xml                 # Database schema
│   ├── access.php                  # Capabilities
│   ├── services.php                # Web services
│   └── caches.php                  # Cache definitions
├── lang/en/
│   └── local_ai.php               # Language strings
├── templates/
│   ├── chat.mustache              # Chat interface
│   └── analytics.mustache         # Analytics dashboard
├── amd/src/
│   ├── chat.js                    # Chat functionality
│   ├── chat_widget.js             # Floating widget
│   └── analytics.js               # Analytics charts
├── tests/                         # PHPUnit and Behat tests
├── index.php                      # Main chat page
├── analytics.php                  # Analytics page
├── stream.php                     # Streaming endpoint
└── settings.php                   # Admin settings
```

### Testing

```bash
# Run PHPUnit tests
vendor/bin/phpunit local/ai/tests/

# Run Behat tests
vendor/bin/behat --config local/ai/tests/behat/behat.yml
```

### Extending

The plugin is designed with extensibility in mind:

- **Custom Models**: Add support for new AI models
- **Additional Features**: Extend with new AI capabilities
- **Custom UI**: Modify templates and JavaScript
- **API Providers**: Support additional API providers

## Troubleshooting

### Common Issues

1. **API Key Not Found**
   - Ensure `OPENAI_API_KEY` environment variable is set
   - Check web server environment variable configuration

2. **Rate Limit Exceeded**
   - Check user rate limits in admin settings
   - Monitor usage in analytics dashboard
   - Adjust limits as needed

3. **Streaming Not Working**
   - Verify Server-Sent Events support in web server
   - Check firewall and proxy configurations
   - Ensure proper CORS headers

4. **Performance Issues**
   - Enable response caching
   - Adjust cache TTL settings
   - Monitor API response times

### Logs

Check Moodle logs for AI-related errors:
- Site Administration > Reports > Logs
- Filter by "local_ai" component

## Support

For support and bug reports:
- Create issues in the project repository
- Check Moodle community forums
- Review documentation and troubleshooting guide

## License

This plugin is licensed under the GNU GPL v3 or later.

## Changelog

### Version 1.0.0
- Initial release
- OpenAI API integration
- Chat interface with streaming
- Rate limiting and analytics
- Privacy compliance
- Comprehensive testing

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

Please follow Moodle coding standards and include appropriate tests for any new features.
