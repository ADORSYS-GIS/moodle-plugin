<?php
namespace aiprovider_gisai;

use Psr\Http\Message\RequestInterface;

/**
 * GIS AI Provider class.
 *
 * @package    aiprovider_gisai
 * @copyright  2025 GIS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider extends \core_ai\provider {
    /**
     * Get the list of actions supported by this provider.
     *
     * @return array
     */
    public static function get_action_list(): array {
        return [
            \core_ai\aiactions\generate_text::class,
        ];
    }

    /**
     * Add authentication headers to the request.
     *
     * @param RequestInterface $request The request object
     * @return RequestInterface The request with authentication headers added
     */
    #[\Override]
    public function add_authentication_headers(RequestInterface $request): RequestInterface {
        return $request->withAddedHeader('Authorization', "Bearer {$this->config['apikey']}");
    }

    /**
     * Check if the provider is configured.
     *
     * @return bool
     */
    public function is_provider_configured(): bool {
        return !empty($this->config['apikey']) && !empty($this->config['apiendpoint']);
    }
}
