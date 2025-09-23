<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/api_client.php');

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/openai_assistant/chat.php'));
$PAGE->set_title(get_string('chat_with_ai', 'local_openai_assistant'));
$PAGE->set_heading(get_string('chat_with_ai', 'local_openai_assistant'));

// Add some CSS for better styling
$PAGE->requires->css('/local/openai_assistant/style/styles.css');

echo $OUTPUT->header();

// Display connection status
echo '<div class="alert alert-info">';
echo '<h4>🤖 OpenAI Assistant Status</h4>';
$client = new \local_openai_assistant\api_client();
echo '<p>Plugin loaded successfully. Binary path: <code>/bitnami/moodle/openai-sidecar/openai-moodle-sidecar</code></p>';
echo '<p>Environment variables configured: ';
echo getenv('OPENAI_API_KEY') ? '✅ API Key' : '❌ API Key';
echo ', ';
echo getenv('OPENAI_MODEL') ? '✅ Model (' . getenv('OPENAI_MODEL') . ')' : '❌ Model';
echo '</p>';
echo '</div>';

// Handle form submission
if ($_POST && confirm_sesskey()) {
    $message = required_param('message', PARAM_TEXT);
    $action = optional_param('action', 'chat', PARAM_ALPHA);
    $context = optional_param('context', '', PARAM_TEXT);
    
    echo '<div class="card mb-3">';
    echo '<div class="card-header"><strong>Your Request (' . htmlspecialchars($action) . ')</strong></div>';
    echo '<div class="card-body">' . nl2br(htmlspecialchars($message)) . '</div>';
    echo '</div>';
    
    $start_time = microtime(true);
    
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
        echo '<div class="card-body">' . nl2br(htmlspecialchars($response['data'])) . '</div>';
    } else {
        echo '<div class="card-header bg-danger text-white"><strong>❌ Error</strong> <small>(took ' . $duration . 'ms)</small></div>';
        echo '<div class="card-body">' . htmlspecialchars($response['error']) . '</div>';
    }
    echo '</div>';
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
                <h4>📝 Test Examples</h4>
            </div>
            <div class="card-body">
                <h5>Chat Examples:</h5>
                <ul>
                    <li>"Explain quantum physics in simple terms"</li>
                    <li>"Create a quiz about photosynthesis"</li>
                    <li>"Help me understand calculus derivatives"</li>
                </ul>
                
                <h5>Summarize Examples:</h5>
                <ul>
                    <li>Paste a long article or text</li>
                    <li>Course content that needs condensing</li>
                </ul>
                
                <h5>Analyze Examples:</h5>
                <ul>
                    <li>Educational content for difficulty assessment</li>
                    <li>Course materials for learning objectives</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
?>