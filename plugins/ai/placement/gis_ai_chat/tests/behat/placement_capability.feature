Feature: GIS AI Chat capability
  In order to use the GIS AI Chat placement
  As an admin
  I need to see the capability and description in Define roles

  Scenario: Capability appears in Define roles page
    Given I log in as "admin"
    And I navigate to "Users > Permissions > Define roles" in site administration
    And I follow "Student"
    Then I should see "Use GIS AI Chat to generate text"
    And I should see "aiplacement/gis_ai_chat:generate_text"
