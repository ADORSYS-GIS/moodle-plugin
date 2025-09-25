# This file is part of Moodle - http://moodle.org/
#
# Moodle is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Moodle is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

# Behat tests for AI chat functionality.
#
# @package    local_gis_ai_assistant
# @copyright  2025 Adorsys GIS
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@local_gis_ai_assistant @javascript
Feature: AI Chat Interface
  In order to get AI assistance
  As a user
  I need to be able to chat with the AI assistant

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following config values are set as admin:
      | config  | value | plugin   |
      | enabled | 1     | local_gis_ai_assistant |
    And I set the following environment variables:
      | OPENAI_API_KEY  | test-api-key                |
      | OPENAI_BASE_URL | https://api.openai.com/v1   |

  @javascript
  Scenario: Access AI chat as a teacher
    Given I log in as "teacher1"
    When I navigate to "AI Assistant" in current page administration
    Then I should see "AI Chat Assistant"
    And I should see the AI chat interface
    And I should see "Ask me anything..."

  @javascript
  Scenario: Send a message in AI chat
    Given I log in as "teacher1"
    And I am on the AI chat page
    When I type "Hello, how can you help me?" in the chat input
    And I press "Send"
    Then I should see "Hello, how can you help me?" in the chat messages
    And I should see "AI is thinking..."
    # Note: In real tests, you'd mock the API response

  @javascript
  Scenario: Send a streaming message in AI chat (fallback error path)
    Given I log in as "teacher1"
    And I am on the AI chat page
    When I type "Stream this please" in the chat input
    And I press "Send (stream)"
    Then I should see "Stream this please" in the chat messages
    # In test runs without the external API mocked, the streaming connection will fail
    # and the UI should display a helpful error message.
    And I should see "Streaming connection failed"

  @javascript
  Scenario: Clear chat history
    Given I log in as "teacher1"
    And I am on the AI chat page
    And I have sent a message "Test message"
    When I click the "Clear chat" button
    And I confirm the action
    Then the chat should be cleared
    And I should see the welcome message

  @javascript
  Scenario: AI chat is disabled
    Given the following config values are set as admin:
      | config  | value | plugin   |
      | enabled | 0     | local_gis_ai_assistant |
    And I log in as "teacher1"
    When I try to access the AI chat page
    Then I should see "AI functionality is currently disabled"

  @javascript
  Scenario: User without capability cannot access AI
    Given I log in as "student1"
    And the user "student1" does not have the capability "local/gis_ai_assistant:use"
    When I try to access the AI chat page
    Then I should see "You do not have permission to access this page"

  @javascript
  Scenario: Chat widget appears on course pages
    Given I log in as "teacher1"
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    When I am on "Course 1" course homepage
    Then I should see the AI chat widget
    When I click the chat widget toggle
    Then I should see the mini chat popup

  @javascript
  Scenario: Rate limiting prevents excessive requests
    Given I log in as "teacher1"
    And I am on the AI chat page
    And the rate limit is set to 1 request per hour
    When I send a message "First message"
    And I try to send another message "Second message"
    Then I should see "Rate limit exceeded"

  @javascript
  Scenario: Analytics are recorded for AI usage
    Given I log in as "admin"
    And I have the capability "local/gis_ai_assistant:viewanalytics"
    And some AI requests have been made
    When I navigate to the AI analytics page
    Then I should see usage statistics
    And I should see "Total requests"
    And I should see "Total tokens used"
    And I should see "Top users"
