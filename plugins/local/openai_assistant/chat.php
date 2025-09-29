<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/api_client.php');
require_once(__DIR__ . '/classes/stdio_communicator.php');

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/openai_assistant/chat.php'));
$PAGE->set_title(get_string('chat_with_ai', 'local_openai_assistant'));
$PAGE->set_heading(get_string('chat_with_ai', 'local_openai_assistant'));

echo $OUTPUT->header();

// Display connection status
echo '<div class="alert alert-info">';
echo '<h4>🤖 OpenAI Assistant Status</h4>';

// Test class loading
try {
    $client = new \local_openai_assistant\api_client();
    echo '<p>✅ Plugin classes loaded successfully</p>';
    
    // Check binary
    $binary_path = '/bitnami/moodle/openai-sidecar/openai-moodle-sidecar';
    if (file_exists($binary_path)) {
        echo '<p>✅ Binary found at: <code>' . htmlspecialchars($binary_path) . '</code></p>';
        if (is_executable($binary_path)) {
            echo '<p>✅ Binary is executable</p>';
        } else {
            echo '<p>❌ Binary is not executable</p>';
        }
    } else {
        echo '<p>❌ Binary not found at: <code>' . htmlspecialchars($binary_path) . '</code></p>';
    }
    
    // Check environment variables
    echo '<p>Environment variables: ';
    echo getenv('OPENAI_API_KEY') ? '✅ API Key' : '❌ API Key';
    echo ', ';
    echo getenv('OPENAI_MODEL') ? '✅ Model (' . getenv('OPENAI_MODEL') . ')' : '❌ Model';
    echo '</p>';
    
} catch (Exception $e) {
    echo '<p>❌ Error loading plugin: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>File path: ' . __DIR__ . '/classes/</p>';
    echo '<p>Files in classes directory:</p><ul>';
    $files = scandir(__DIR__ . '/classes/');
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo '<li>' . htmlspecialchars($file) . '</li>';
        }
    }
    echo '</ul>';
}

echo '</div>';

// Handle form submission only if classes loaded successfully
if ($_POST && confirm_sesskey()) {
    $message = required_param('message', PARAM_TEXT);
    $action = optional_param('action', 'chat', PARAM_ALPHA);
    $context = optional_param('context', '', PARAM_TEXT);
    
    echo '<div class="card mb-3">';
    echo '<div class="card-header"><strong>Your Request (' . htmlspecialchars($action) . ')</strong></div>';
    echo '<div class="card-body">' . nl2br(htmlspecialchars($message)) . '</div>';
    echo '</div>';
    
    $start_time = microtime(true);
    
    try {
        $client = new \local_openai_assistant\api_client();
        
        switch ($action) {
            case 'chat':
                $response = $client->chat($message, $context ?: null, $USER->id);
                break;
            case 'summarize':
                $response = $client->summarize($message, $USER->id);
                break;
            case 'analyze':
                $response = $client->analyze($message, $USER->id);
                break;
            default:
                $response = ['success' => false, 'error' => 'Invalid action'];
        }
        
        $end_time = microtime(true);
        $duration = round(($end_time - $start_time) * 1000, 2);
        
        echo '<div class="card mb-3">';
        if ($response['success']) {
            echo '<div class="card-header bg-success text-white"><strong>✅ AI Response</strong> <small>(took ' . $duration . 'ms)</small></div>';
            // Normalize clarification-style replies into a concise prompt if the normalizer exists,
            // otherwise fall back to rendering the full response.
            $normalized = null;
            if (function_exists('local_openai_assistant_normalize_response')) {
                $normalized = local_openai_assistant_normalize_response($response['data'], $message);
            }
            if ($normalized !== null) {
                echo '<div class="card-body">' . $normalized . '</div>';
            } else {
                // Render assistant reply as HTML using the built-in lightweight markdown renderer.
                $html = local_openai_assistant_render_ai_response($response['data']);
                echo '<div class="card-body">' . $html . '</div>';
            }
        } else {
            echo '<div class="card-header bg-danger text-white"><strong>❌ Error</strong> <small>(took ' . $duration . 'ms)</small></div>';
            echo '<div class="card-body">' . htmlspecialchars($response['error']) . '</div>';
        }
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>🚀 Test OpenAI Assistant</h3>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                    
                    <div class="form-group mb-3">
                        <label for="action" class="form-label">Action:</label>
                        <select name="action" id="action" class="form-control">
                            <option value="chat">💬 Chat</option>
                            <option value="summarize">📄 Summarize</option>
                            <option value="analyze">🔍 Analyze</option>
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="message" class="form-label">Message:</label>
                        <textarea name="message" id="message" rows="5" class="form-control" 
                                  placeholder="Type your message here..." required></textarea>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="context" class="form-label">Context (optional):</label>
                        <textarea name="context" id="context" rows="3" class="form-control" 
                                  placeholder="Provide additional context for the AI..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        🚀 Send Request
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h4>📝 Debug Info</h4>
            </div>
            <div class="card-body">
                <p><strong>Plugin Path:</strong><br><code><?php echo __DIR__; ?></code></p>
                <p><strong>Classes Path:</strong><br><code><?php echo __DIR__ . '/classes/'; ?></code></p>
                <p><strong>Binary Path:</strong><br><code>/bitnami/moodle/openai-sidecar/openai-moodle-sidecar</code></p>
                
                <h5>📝 Test Examples:</h5>
                <ul>
                    <li>"Hello, test message"</li>
                    <li>"Explain photosynthesis"</li>
                    <li>"Create a simple quiz"</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
?>