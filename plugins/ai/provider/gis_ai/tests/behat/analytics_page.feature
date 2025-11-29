@aiprovider_gis_ai @javascript
Feature: Provider analytics admin page
  In order to monitor AI usage
  As an admin user
  I need to access the provider analytics page and view metrics

  Scenario: Admin can access the provider analytics page
    Given I log in as "admin"
    When I am on "/ai/provider/gis_ai/analytics.php"
    Then I should see "Analytics"
    And ".aiprovider-gis-analytics" "css_element" should exist
